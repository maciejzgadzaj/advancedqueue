<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\advancedqueue\Event\AdvancedQueueEvents;
use Drupal\advancedqueue\Event\ItemClaimEvent;
use Drupal\advancedqueue\Event\ItemCreateEvent;
use Drupal\advancedqueue\Event\ItemDeleteEvent;
use Drupal\advancedqueue\Event\ItemPostProcessEvent;
use Drupal\advancedqueue\Event\ItemPreProcessEvent;
use Drupal\advancedqueue\Event\ItemReleaseEvent;
use Drupal\advancedqueue\Event\ItemRequeueEvent;
use Drupal\advancedqueue\Event\ItemResetEvent;
use Drupal\advancedqueue\Event\QueueDeleteEvent;
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
   * The queue worker plugin manager.
   *
   * @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager
   */
  protected $queueManager;

  /**
   * Constructs a \Drupal\advancedqueue\Queue\AdvancedQueue object.
   *
   * @param string $name
   *   The name of the queue.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager
   *   The queue worker plugin manager.
   */
  public function __construct($name, Connection $connection, EventDispatcherInterface $event_dispatcher, AdvancedQueueWorkerManager $queue_manager) {
    $this->name = $name;
    $this->connection = $connection;
    $this->eventDispatcher = $event_dispatcher;
    $this->queueManager = $queue_manager;
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
      'created' => is_array($data) && !empty($data['created']) ? $data['created'] : time(),
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

    $item_id = $query->execute();

    // Dispatch "item.create" event.
    if ($this->queueManager->dispatchEvent($this->name, AdvancedQueueEvents::ITEM_CREATE)) {
      $item = (object) $fields;
      $item->item_id = $item_id;
      $event = new ItemCreateEvent($this->name, $item);
      $this->eventDispatcher->dispatch(AdvancedQueueEvents::ITEM_CREATE, $event);
    }

    return $item_id;
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
  public function claimItem($lease_time = NULL, $item_id = NULL) {
    $conditions = ['aq.name' => $this->name];
    if (!empty($item_id)) {
      $conditions['item_id'] = $item_id;
    }
    return self::claimFirstItem($conditions, $lease_time);
  }

  /**
   * Claims a first item from any queue for processing.
   *
   * This method, in comparison to native claimItem(), is queue-agnostic -
   * which means it will claim a very first item that needs processing,
   * regardless of the queue it belongs to. This allows for proper FIFO
   * processing, unlike standard implementation which processes items
   * queue-by-queue.
   *
   * @param array $conditions
   *   An array of additional conditions specifying the item to claim,
   *   for example a queue name (as used by ::claimItem()).
   * @param int $lease_time
   *   How long the processing is expected to take in seconds. After this lease
   *   expires, the item will be reset and another consumer can claim the item.
   *
   * @return object|false
   *   On success we return an item object. If the queue is unable to claim an
   *   item it returns false.
   *
   * @see drush_advancedqueue_queue_process()
   * @see QueueProcessCommand::execute()
   * @see self::claimItem()
   */
  public static function claimFirstItem($conditions = [], $lease_time = NULL) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    // Claim an item by updating its expire field.
    while (TRUE) {
      $query = \Drupal::database()->select(static::TABLE_NAME, 'aq')
        ->fields('aq')
        ->condition('aq.status', [
          AdvancedQueueItem::STATUS_QUEUED,
          AdvancedQueueItem::STATUS_FAILURE_RETRY,
        ], 'IN')
        ->condition('aq.created', time(), '<=')
        ->condition('aq.expire', 0);
      if (!empty($conditions)) {
        foreach ($conditions as $field => $value) {
          $operator = is_array($value) ? 'IN' : '=';
          $query->condition($field, $value, $operator);
        }
      }
      // Filter out suspended queues.
      if ($suspended_queues = $queue_manager->getSuspendedQueues()) {
        $query->condition('aq.name', array_keys($suspended_queues), 'NOT IN');
      }
      $item = $query->orderBy('aq.created', 'ASC')
        ->orderBy('aq.item_id', 'ASC')
        ->execute()
        ->fetchObject();

      // Update the item claim lease time.
      if ($item) {
        // If no lease time was provided in the method call parameter,
        // let's fetch the default lease time from the queue worker definition.
        if (!$lease_time) {
          $lease_time = $queue_manager->getLeaseTime($item->name);
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

          // Dispatch "item.claim" event.
          if ($queue_manager->dispatchEvent($item->name, AdvancedQueueEvents::ITEM_CLAIM)) {
            $event = new ItemClaimEvent($item->name, $item, $lease_time);
            \Drupal::service('event_dispatcher')->dispatch(AdvancedQueueEvents::ITEM_CLAIM, $event);
          }

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
   * @param int $end_time
   *   A timestamp indicating when the processing should end at the latest.
   */
  public function processItem($item, $end_time) {
    // Dispatch "item.preprocess" event.
    if ($this->queueManager->dispatchEvent($item->name, AdvancedQueueEvents::ITEM_PREPROCESS)) {
      $event = new ItemPreProcessEvent($this->name, $item);
      $this->eventDispatcher->dispatch(AdvancedQueueEvents::ITEM_PREPROCESS, $event);
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
      /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
      $queue_worker = $this->queueManager->createInstance($item->name);

      $item->status = AdvancedQueueItem::STATUS_SUCCESS;
      $item->result['return'] = $queue_worker->processItem($item->data, $item, $end_time);

      // If hard-deleting item after processing is enabled for this queue,
      // we want to delete them only in case of successful processing.
//      if ($this->queueManager->getDeleteWhenCompleted($item->name) && $this->queueManager->getDeleteHard($item->name)) {
//        $this->deleteItem($item, $this->queueManager->getDeleteHard($item->name));
//      }
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

      // In case of queue processing suspension we do not want to increase
      // item processing counter.
      $item->result['attempt']--;

      // Temporarily suspend queue processing.
      $end_time = $this->queueManager->suspendQueue($item->name);
      \Drupal::logger('advancedqueue')->warning(t('Suspended processing of "@queue_name" queue until @end_time.', [
        '@queue_name' => $item->name,
        '@end_time' => date('Y-m-d H:i:s', $end_time),
      ]));
    }
    // Any other exceptions thrown by a worker means failed processing.
    catch (\Exception $e) {
      $item->status = AdvancedQueueItem::STATUS_FAILURE;
      $item->result['return'] = $e->getMessage();
    }

    // Once we have a result, dispatch "item.postprocess" event.
    // Adventurers can use this to override the result of processing
    // (stored on the $item object).
    if ($this->queueManager->dispatchEvent($item->name, AdvancedQueueEvents::ITEM_POSTPROCESS)) {
      $event = new ItemPostProcessEvent($item->name, $item);
      $this->eventDispatcher->dispatch(AdvancedQueueEvents::ITEM_POSTPROCESS, $event);
    }

    $params['@status_code'] = $item->status;
    $params['@status'] = AdvancedQueueItem::getStatusLabel($item->status);
    \Drupal::logger('advancedqueue')->debug(t('[@queue:@id] Processing ended with result @status_code (@status).', $params));

    // Requeue item in case of soft failure.
    if ($item->status == AdvancedQueueItem::STATUS_FAILURE_RETRY) {

      // Retries are optional, and by default disabled (set to 0).
      // Skip the attempts check if the item should not be reprocessed anymore.
      $max_retry_attempts = $this->queueManager->getRetryAttempts($item->name);
      if ($item->result['attempt'] <= $max_retry_attempts) {

        // If the queue worker defined "retry delay" time, let's update item's
        // "created" time, so it is reprocessed only after required time delay.
        if ($retry_delay = $this->queueManager->getRetryDelay($item->name, $item->result['attempt'])) {
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
    // really delete the item, instead just update its properties.
    if ($this->queueManager->getDeleteWhenCompleted($item->name)) {
      $this->deleteItem($item, $this->queueManager->getDeleteHard($item->name));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) {
    $result = $this->connection->update('advancedqueue')
      ->fields([
        'expire' => 0,
        'status' => AdvancedQueueItem::STATUS_QUEUED,
      ])
      ->condition('item_id', $item->item_id)
      ->execute();

    // Dispatch "item.release" event.
    if ($this->queueManager->dispatchEvent($item->name, AdvancedQueueEvents::ITEM_RELEASE)) {
      $event = new ItemReleaseEvent($this->name, $item);
      $this->eventDispatcher->dispatch(AdvancedQueueEvents::ITEM_RELEASE, $event);
    }

    return $result;
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
    $result = $this->connection->update('advancedqueue')
      ->fields([
        'expire' => 0,
        'status' => $item->status != AdvancedQueueItem::STATUS_FAILURE_RETRY ? AdvancedQueueItem::STATUS_QUEUED : $item->status,
        'created' => $item->created,
        'data' => serialize($item->data),
      ])
      ->condition('item_id', $item->item_id)
      ->execute();

    // Dispatch "item.requeue" event.
    if ($this->queueManager->dispatchEvent($item->name, AdvancedQueueEvents::ITEM_REQUEUE)) {
      $event = new ItemRequeueEvent($this->name, $item);
      $this->eventDispatcher->dispatch(AdvancedQueueEvents::ITEM_REQUEUE, $event);
    }

    return $result;
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

    $result = $this->connection->update('advancedqueue')
      ->fields(['result' => serialize($item->result)])
      ->condition('item_id', $item->item_id)
      ->execute();

    // Dispatch "item.reset" event.
    if ($this->queueManager->dispatchEvent($item->name, AdvancedQueueEvents::ITEM_RESET)) {
      $event = new ItemResetEvent($this->name, $item);
      $this->eventDispatcher->dispatch(AdvancedQueueEvents::ITEM_RESET, $event);
    }

    return $result;
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
      $result = $this->connection->delete('advancedqueue')
        ->condition('item_id', $item->item_id)
        ->execute();
    }
    // Soft delete - just update its properties.
    else {
      $result = $this->connection->update('advancedqueue')
        ->fields([
          'expire' => 0,
          'status' => isset($item->status) && $item->status > 0 ? $item->status : AdvancedQueueItem::STATUS_SUCCESS,
          'result' => serialize(isset($item->result) ? $item->result : []),
          'processed' => time(),
        ])
        ->condition('item_id', $item->item_id)
        ->execute();
    }

    // Dispatch "item.delete" event.
    if ($this->queueManager->dispatchEvent($item->name, AdvancedQueueEvents::ITEM_DELETE)) {
      $event = new ItemDeleteEvent($this->name, $item, $hard);
      $this->eventDispatcher->dispatch(AdvancedQueueEvents::ITEM_DELETE, $event);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    // All tasks are stored in a single database table (which is created on
    // module install) so there is nothing we need to do to create a new queue.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue($hard = FALSE) {
    // Hard delete - remove items from the database.
    if ($hard) {
      $result = $this->connection->delete('advancedqueue')
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

      $result = $this->connection->query($query, [
        ':processed' => time(),
        ':status' => AdvancedQueueItem::STATUS_SUCCESS,
        ':name' => $this->name,
      ], [
        'return' => Database::RETURN_AFFECTED,
      ]);
    }

    // Dispatch "queue.delete" event.
    if ($this->queueManager->dispatchEvent($this->name, AdvancedQueueEvents::QUEUE_DELETE)) {
      $event = new QueueDeleteEvent($this->name, $hard);
      $this->eventDispatcher->dispatch(AdvancedQueueEvents::QUEUE_DELETE, $event);
    }

    return $result;
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
   * @return int
   *   The number of rows affected by the delete query.
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
    foreach ($items as $item) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
      $queue->queueManager->getQueue($item->name);
      $queue->releaseItem($item);
    }
  }

}
