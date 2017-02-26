<?php

namespace Drupal\advancedqueue_ui\Form;

/**
 * Provides a confirmation form for suspending processing of a queue.
 */
class BulkQueueUnsuspend extends BulkConfirmQueueForm {

  /**
   * Form ID value and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_queue_suspend_confirm';

  /**
   * User-friendly operation name shown in the confirmation question.
   */
  const QUESTION_OPERATION = 'unsuspend processing for';

  /**
   * Action-specific processing for batch operation callback.
   *
   * @param object $queue_name
   *   The queue to process.
   * @param array $settings
   *   Additional settings specific for the operation.
   * @param array $context
   *   The batch context.
   *
   * @see batchProcess()
   */
  public static function doBatchProcess($queue_name, $settings, &$context) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    $queue_manager->unsuspendQueue($queue_name);
  }

}
