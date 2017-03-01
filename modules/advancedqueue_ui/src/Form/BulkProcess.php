<?php

namespace Drupal\advancedqueue_ui\Form;

/**
 * Provides a confirmation form for bulk processing queue items.
 */
class BulkProcess extends BulkConfirmFormBase {

  /**
   * Form ID value and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_process_confirm';

  /**
   * User-friendly operation name shown in the confirmation question.
   */
  const QUESTION_OPERATION = 'process';

  /**
   * Action-specific processing for batch operation callback.
   *
   * @param \Drupal\advancedqueue\Entity\AdvancedQueueItemInterface $item
   *   The item entity to process.
   * @param array $settings
   *   Additional settings specific for the operation.
   * @param array $context
   *   The batch context.
   *
   * @see batchProcess()
   */
  public static function doBatchProcess($item, $settings, &$context) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
    $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerInterface $queue_worker */
    $queue_worker = $queue_worker_manager->createInstance($item->name);
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_worker->getQueue();

    if ($item = $queue->claimItem($queue_worker->getLeaseTime(), $item->item_id)) {
      $queue->processItem($item, $queue_worker, $queue_worker->getLeaseTime());
    }
    else {
      throw new \Exception(t('Error claiming item @item_id for processing.', ['@item_id' => $item->item_id]));
    }
  }

}
