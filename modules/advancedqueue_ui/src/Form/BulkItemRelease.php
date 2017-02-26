<?php

namespace Drupal\advancedqueue_ui\Form;

/**
 * Provides a confirmation form for bulk releasing queue items.
 */
class BulkItemRelease extends BulkConfirmItemForm {

  /**
   * Form ID value and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_item_release_confirm';

  /**
   * User-friendly operation name shown in the confirmation question.
   */
  const QUESTION_OPERATION = 'release';

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
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($item->name);

    $queue->releaseItem($item);
  }

}
