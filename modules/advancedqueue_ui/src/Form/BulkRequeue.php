<?php

namespace Drupal\advancedqueue_ui\Form;

/**
 * Provides a confirmation form for bulk requeueing queue items.
 */
class BulkRequeue extends BulkConfirmFormBase {

  /**
   * Form ID value and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_requeue_confirm';

  /**
   * User-friendly operation name shown in the confirmation question.
   */
  const QUESTION_OPERATION = 'requeue';

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
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_factory->get($item->name);

    $queue->requeueItem($item);
  }

}
