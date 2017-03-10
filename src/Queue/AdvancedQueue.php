<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\advancedqueue\Event\PostExecuteEvent;
use Drupal\advancedqueue\Event\PreExecuteEvent;
use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Queue\QueueGarbageCollectionInterface;
use Drupal\Core\Queue\ReliableQueueInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Advanced queue implementation.
 *
 * Alternative implementation to Drupal\Core\Queue\DatabaseQueue.
 *
 * @ingroup queue
 */
class AdvancedQueue implements ReliableQueueInterface, QueueGarbageCollectionInterface {

  use DependencySerializationTrait;

  /**
   * The database table name.
   */
  const TABLE_NAME = 'advancedqueue';

  /**
   * The name of the queue this instance is working with.
   *
   * @var string
   */
  protected $name;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $connection;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a \Drupal\advancedqueue\Queue\AdvancedQueue object.
   *
   * @param string $name
   *   The name of the queue.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct($name, Connection $connection, EventDispatcherInterface $event_dispatcher) {
    $this->name = $name;
    $this->connection = $connection;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    return $this->createUniqueItem($data);
  }

  /**
   * Allows the creation of uniquely-keyed items within a single queue.
   *
   * @param mixed $data
   *   The data for the queue item. It could an array, but it might also be
   *   a string in case of non-AQ queues.
   * @param string|null $item_key
   *   If present, the unique key for the queue item.
   *
   * @return int
   *   A unique ID if the item was successfully created and was (best effort)
   *   added to the queue, otherwise FALSE.
   */
  public function createUniqueItem($data, $item_key = NULL) {
    // Make sure that queue items cannot break the title column.
    $schema = drupal_get_module_schema('advancedqueue', 'advancedqueue');
    $title_max = $schema['fields']['title']['length'];
    $title_raw = is_array($data) && isset($data['title']) ? $data['title'] : t('Unnamed item');

    $fields = [
      'name' => $this->name,
      'uid' => is_array($data) && isset($data['uid']) ? $data['uid'] : \Drupal::currentUser()->id(),
      'title' => Unicode::truncate($title_raw, $title_max, FALSE, TRUE),
      'data' => serialize($data),
      // We cannot rely on REQUEST_TIME because many items might be created
      // by a single request which takes longer than 1 second. However we allow
      // the creator to post-date their queue items as with the UID and title
      // properties.
      'created' => !empty($data['created']) ? $data['created'] : time(),
      'status' => AdvancedQueueItem::STATUS_QUEUED,
    ];
    if ($item_key) {
      // Merge onto the existing item. This updates *all* properties, meaning
      // that if "created" is set into the future and an item is updated too
      // frequently, it might never get to run. Be careful!
      $fields['item_key'] = $item_key;
      $query = $this->connection->merge('advancedqueue')
        ->key(['item_key' => $item_key])
        ->fields($fields);
    }
    else {
      // No key means just insert a new item!
      $query = $this->connection->insert('advancedqueue')
        ->fields($fields);
    }

    return $query->execute();
  }

  /**
   * Retrieves the number of items in the queue.
   *
   * @param int|null $status
   *   Optional: status to return the number of items for.
   *   If no value is provided, we will return number of unprocessed items
   *   (with status either -1 (queued), 0 (processing) or 3 (retry)).
   *
   * @return int
   *   An integer of the number of items in the queue.
   */
  public function numberOfItems($status = NULL) {
    $status_op = isset($status) && !is_array($status) ? '=' : 'IN';
    $status = isset($status) ? $status : [
      AdvancedQueueItem::STATUS_QUEUED,
      AdvancedQueueItem::STATUS_PROCESSING,
      AdvancedQueueItem::STATUS_FAILURE_RETRY,
    ];

    $query = $this->connection->select(static::TABLE_NAME, 'aq')
      ->fields('aq')
      ->condition('aq.name', $this->name)
      ->condition('aq.status', $status, $status_op)
      ->execute();

    $query->allowRowCount = TRUE;
    return $query->rowCount();
  }

  /**
   * {@inheritdoc}
   */
  public function claimItem($lease_time = 30, $item_id = NULL) {
    // Claim an item by updating its expire field.
    while (TRUE) {
      $query = $this->connection->select(static::TABLE_NAME, 'aq')
        ->fields('aq')
        ->condition('aq.name', $this->name)
        ->condition('aq.status', [
          AdvancedQueueItem::STATUS_QUEUED,
          AdvancedQueueItem::STATUS_FAILURE_RETRY,
        ], 'IN')
        ->condition('aq.created', time(), '<=')
        ->condition('aq.expire', 0)
        ->orderBy('aq.created', 'ASC')
        ->orderBy('aq.item_id', 'ASC');
      // Allow to claim a specific item.
      if (!empty($item_id)) {
        $query->condition('aq.item_id', $item_id);
      }
      $item = $query->execute()
        ->fetchObject();

      if ($item) {
        // Update the item lease time.
        // We cannot rely on REQUEST_TIME because items might be claimed by
        // a single consumer which runs longer than 1 second. If we continue
        // to use REQUEST_TIME instead of the current time(), we steal time
        // from the lease, and will tend to reset items before the lease
        // should really expire.
        $update = $this->connection->update('advancedqueue')
          ->fields([
            'status' => AdvancedQueueItem::STATUS_PROCESSING,
            'expire' => time() + $lease_time,
          ])
          ->condition('item_id', $item->item_id)
          ->condition('expire', 0);

        // If there are affected rows, this update succeeded.
        if ($update->execute()) {
          $item->data = !empty($item->data) ? unserialize($item->data) : [];
          $item->result = !empty($item->result) ? unserialize($item->result) : [];
          return $item;
        }
      }
      else {
        // No items currently available to claim.
        return FALSE;
      }
    }
  }

  public static function claimItemFIFO($lease_time = NULL) {
    // Claim an item by updating its expire field.
    while (TRUE) {
      $query = \Drupal::database()->select(static::TABLE_NAME, 'aq')
        ->fields('aq')
        ->condition('aq.status', [
          AdvancedQueueItem::STATUS_QUEUED,
          AdvancedQueueItem::STATUS_FAILURE_RETRY,
        ], 'IN')
        ->condition('aq.created', time(), '<=')
        ->condition('aq.expire', 0)
        ->orderBy('aq.created', 'ASC')
        ->orderBy('aq.item_id', 'ASC');
      // Allow to claim a specific item.
      if (!empty($item_id)) {
        $query->condition('aq.item_id', $item_id);
      }
      $item = $query->execute()
        ->fetchObject();

      if ($item) {
        // Update the item lease time.
        // First get default lease time from the queue worker if it was not
        // provided as function parameter.
        if (!$lease_time) {
          /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
          $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');
          /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerInterface $queue_worker */
          $queue_worker = $queue_worker_manager->createInstance($item->name);

          $lease_time = $queue_worker->getLeaseTime();
        }

        // We cannot rely on REQUEST_TIME because items might be claimed by
        // a single consumer which runs longer than 1 second. If we continue
        // to use REQUEST_TIME instead of the current time(), we steal time
        // from the lease, and will tend to reset items before the lease
        // should really expire.
        $update = \Drupal::database()->update('advancedqueue')
          ->fields([
            'status' => AdvancedQueueItem::STATUS_PROCESSING,
            'expire' => time() + $lease_time,
          ])
          ->condition('item_id', $item->item_id)
          ->condition('expire', 0);

        // If there are affected rows, this update succeeded.
        if ($update->execute()) {
          $item->data = !empty($item->data) ? unserialize($item->data) : [];
          $item->result = !empty($item->result) ? unserialize($item->result) : [];
          return $item;
        }
      }
      else {
        // No items currently available to claim.
        return FALSE;
      }
    }
  }

  /**
   * Handles item processing.
   *
   * @param object $item
   *   An item being processed.
   * @param AdvancedQueueWorkerInterface $queue_worker
   *   A queue worker used to process the item.
   * @param int $end_time
   *   A timestamp indicating when the processing should end at the latest.
   */
  public function processItem($item, AdvancedQueueWorkerInterface $queue_worker, $end_time) {
    // Invoke pre-execute hooks.
    if ($queue_worker->executeHooks('preprocess')) {
      $event = new PreExecuteEvent($this->name, $item);
      $this->eventDispatcher->dispatch(PreExecuteEvent::EVENT_NAME, $event);
    }

    $params = [
      '@queue' => $this->name,
      '@id' => $item->item_id,
      '@title' => $item->title,
    ];
    \Drupal::logger('advancedqueue')->debug(t('[@queue:@id] Starting processing item "@title".', $params));

    // Clear Drupal's static caches (including entity controllers) before
    // processing, so that each queue item can have a relatively fresh
    // start.
    drupal_static_reset();

    // Increase item processing counter.
    $item->result['attempt'] = !empty($item->result['attempt']) ? $item->result['attempt'] + 1 : 1;

    // Pass the claimed item data to the queue plugin worker.
    // Item data is what native queue workers expect to receive as the function
    // parameter, so we have to pass it this way - but we also pass the whole
    // item as well as the requested processing end time as additional params,
    // even though such params are not defined in QueueWorkerInterface.
    try {
      $item->result['return'] = $queue_worker->processItem($item->data, $item, $end_time);
      $item->status = AdvancedQueueItem::STATUS_SUCCESS;

      // If hard-deleting item after processing is enabled for this queue,
      // we want to delete them only in case of successful processing.
      if ($queue_worker->getDeleteWhenCompleted() && $queue_worker->getDeleteHard()) {
        $this->deleteItem($item, $queue_worker->getDeleteHard());
      }
    }
    // The worker requested the task to be requeued.
    catch (RequeueException $e) {
      $item->status = AdvancedQueueItem::STATUS_FAILURE_RETRY;
      $item->result['return'] = $e->getMessage();
    }
    // The worker indicated there was a problem with the whole queue.
    catch (SuspendQueueException $e) {
      $item->status = AdvancedQueueItem::STATUS_FAILURE_RETRY;
      $item->result['return'] = $e->getMessage();
    }
    // Any other exceptions thrown by a worker means failed processing.
    catch (\Exception $e) {
      $item->status = AdvancedQueueItem::STATUS_FAILURE;
      $item->result['return'] = $e->getMessage();
    }

    // Once we have a result, invoke the post-execute hooks. Adventurers can use
    // this to override the result of processing (stored on the $item object).
    if ($queue_worker->executeHooks('postprocess')) {
      $event = new PostExecuteEvent($item->name, $item);
      $this->eventDispatcher->dispatch(PostExecuteEvent::EVENT_NAME, $event);
    }

    $params['@status_code'] = $item->status;
    $params['@status'] = AdvancedQueueItem::getStatusLabel($item->status);
    \Drupal::logger('advancedqueue')->debug(t('[@queue:@id] Processing ended with result @status_code (@status).', $params));

    // Requeue item in case of soft failure.
    if ($item->status == AdvancedQueueItem::STATUS_FAILURE_RETRY) {

      // Retries are optional, and by default disabled (set to 0).
      // Skip the attempts check if the item should not be reprocessed anymore.
      $max_retry_attempts = $queue_worker->getRetryAttempts();
      if ($item->result['attempt'] <= $max_retry_attempts) {

        // If the queue worker defined "retry delay" time, let's update item's
        // "created" time, so it is reprocessed only after required time delay.
        if ($retry_delay = $queue_worker->getRetryDelay($item->result['attempt'])) {
          $item->created = time() + $retry_delay;
        }

        // requeueItem() does not update item's "processed" time, but we still
        // want to save time of the last processing attempt.
        $item->processed = time();

        // Requeue item for reprocessing.
        $this->requeueItem($item);

        \Drupal::logger('advancedqueue')->warning(t('[@queue:@id] Item failed processing and has been requeued.', $params));
      }
      else {
        $item->status = AdvancedQueueItem::STATUS_FAILURE;

        \Drupal::logger('advancedqueue')->error(t('[@queue:@id] The maximum number of processing attempts has been reached, aborting.', $params));
      }
    }

    // Delete item in case of hard failure.
    if ($item->status == AdvancedQueueItem::STATUS_FAILURE) {
      $params['@message'] = $e->getMessage();
      \Drupal::logger('advancedqueue')->error(t('[@queue:@id] Processing failed: @message', $params));
    }

    // If hard-deleting item after processing is disabled for this queue,
    // we can call deleteItem() regardless of processing result, as it won't
    // really delete the item, instead just updating its properties.
    if ($queue_worker->getDeleteWhenCompleted()) {
      $this->deleteItem($item, $queue_worker->getDeleteHard());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) {
    return $this->connection->update('advancedqueue')
      ->fields([
        'expire' => 0,
        'status' => AdvancedQueueItem::STATUS_QUEUED,
      ])
      ->condition('item_id', $item->item_id)
      ->execute();
  }

  /**
   * Requeues an item to be processed again.
   *
   * @param object $item
   *   The item returned by \Drupal\Core\Queue\QueueInterface::claimItem().
   *
   * @return bool
   *   TRUE if the item has been requeued, FALSE otherwise.
   */
  public function requeueItem($item) {
    return $this->connection->update('advancedqueue')
      ->fields([
        'expire' => 0,
        'status' => $item->status != AdvancedQueueItem::STATUS_FAILURE_RETRY ? AdvancedQueueItem::STATUS_QUEUED : $item->status,
        'created' => $item->created,
        'data' => serialize($item->data),
      ])
      ->condition('item_id', $item->item_id)
      ->execute();
  }

  /**
   * Resets the attempt counter for an item..
   *
   * @param object $item
   *   The item returned by \Drupal\Core\Queue\QueueInterface::claimItem().
   *
   * @return bool
   *   TRUE if the attempt counter has been reset, FALSE otherwise.
   */
  public function resetAttemptCounter($item) {
    if (isset($item->result['attempt'])) {
      unset($item->result['attempt']);
    }

    return $this->connection->update('advancedqueue')
      ->fields(['result' => serialize($item->result)])
      ->condition('item_id', $item->item_id)
      ->execute();
  }

  /**
   * Deletes a finished item from the queue.
   *
   * @param object $item
   *   The item returned by \Drupal\Core\Queue\QueueInterface::claimItem().
   * @param bool $hard
   *   A boolean indicating whether the item should be hard-deleted (permanently
   *   removed from the database) or soft-deleted (just its properties updated).
   * @param bool $processed_only
   *   A boolean indicating whether hard-deleting should apply to successfully
   *   processed items only, or to items with any other status as well.
   *
   * @return int
   *   The number of rows affected by the update or delete query.
   */
  public function deleteItem($item, $hard = FALSE, $processed_only = TRUE) {
    // Hard delete - remove item from the database.
    if ($hard && ($item->status == AdvancedQueueItem::STATUS_SUCCESS || !$processed_only)) {
      return $this->connection->delete('advancedqueue')
        ->condition('item_id', $item->item_id)
        ->execute();
    }
    // Soft delete - just update its properties.
    else {
      return $this->connection->update('advancedqueue')
        ->fields([
          'expire' => 0,
          'status' => isset($item->status) && $item->status > 0 ? $item->status : AdvancedQueueItem::STATUS_SUCCESS,
          'result' => serialize(isset($item->result) ? $item->result : []),
          'processed' => time(),
        ])
        ->condition('item_id', $item->item_id)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    // All tasks are stored in a single database table (which is created on
    // demand) so there is nothing we need to do to create a new queue.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue($hard = FALSE) {
    // Hard delete - remove items from the database.
    if ($hard) {
      return $this->connection->delete('advancedqueue')
        ->condition('name', $this->name)
        ->execute();
    }
    // Soft delete - just update their properties.
    else {
      $query = '
        UPDATE
          {advancedqueue} 
        SET 
          expire = 0, 
          processed = (SELECT CASE WHEN processed > 0 THEN processed ELSE :processed END), 
          status = (SELECT CASE WHEN status > 0 THEN status ELSE :status END)
        WHERE
          name = :name';

      return $this->connection->query($query, [
        ':processed' => time(),
        ':status' => AdvancedQueueItem::STATUS_SUCCESS,
        ':name' => $this->name,
      ], [
        'return' => Database::RETURN_AFFECTED,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $config = \Drupal::config('advancedqueue.settings');

    if ($preserve_rows = $config->get('threshold')) {
      $this->purgeHistory($preserve_rows);
    }

    if ($timeout = $config->get('release_timeout')) {
      $this->releaseStaleItems($timeout);
    }
  }

  /**
   * Removes old completed items (processed and failed) from the database.
   *
   * @param int $preserve_rows
   *   Number of completed items to keep in the database.
   *
   * @see garbageCollection()
   */
  protected function purgeHistory($preserve_rows) {
    // Item status we want to clean.
    $statuses = [
      AdvancedQueueItem::STATUS_SUCCESS,
      AdvancedQueueItem::STATUS_FAILURE,
    ];

    // Find the timestamp of the Xth row.
    $delete_before = $this->connection->select('advancedqueue', 'a')
      ->fields('a', ['created'])
      ->condition('name', $this->name)
      ->condition('status', $statuses, 'IN')
      ->orderBy('created', 'DESC')
      ->range($preserve_rows - 1, 1)
      ->execute()
      ->fetchField();

    // Remove all items created before the selected timestamp.
    if ($delete_before) {
      return $this->connection->delete('advancedqueue')
        ->condition('created', $delete_before, '<')
        ->condition('status', $statuses, 'IN')
        ->execute();
    }

  }

  /**
   * Requeues long expired entries that are in processing state.
   *
   * Items can be stuck in the ADVANCEDQUEUE_STATUS_PROCESSING state
   * if the PHP process crashes or is killed while processing an item.
   *
   * @param int $timeout
   *   Time to wait before releasing an expired item.
   *
   * @see garbageCollection()
   */
  protected function releaseStaleItems($timeout) {
    // Fetch items that need releasing.
    $before = REQUEST_TIME - $timeout;

    $items = $this->connection->select('advancedqueue', 'a')
      ->fields('a', ['item_id', 'name'])
      ->condition('name', $this->name)
      ->condition('status', AdvancedQueueItem::STATUS_PROCESSING)
      ->condition('expire', $before, '<=')
      ->orderBy('name')
      ->execute();

    // Release stale items putting them back in queued status.
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    foreach ($items as $item) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
      $queue = $queue_factory->get($item->name);
      $queue->releaseItem($item);
    }
  }

}
