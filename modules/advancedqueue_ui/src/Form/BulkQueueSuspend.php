<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for suspending processing of a queue.
 */
class BulkQueueSuspend extends BulkConfirmQueueForm {

  /**
   * Form ID value and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_queue_suspend_confirm';

  /**
   * User-friendly operation name shown in the confirmation question.
   */
  const QUESTION_OPERATION = 'suspend processing for';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $queue_name = NULL) {

    $form['settings'] = [
      '#tree' => TRUE,
      '#weight' => 5,
    ];

    $form['settings']['time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time'),
      '#description' => $this->t('Time in seconds to suspend processing for. Leave this field empty to use default suspend time provided by each queue worker definition.'),
      '#field_suffix' => $this->t('seconds'),
      '#size' => 6,
      '#default_value' => in_array($queue_name, array_keys($this->queueManager->getDefinitions())) ? $this->queueManager->getSuspendTime($queue_name) : $this->config('advancedqueue.settings')->get('queue.suspend_time'),
    ];

    $form = parent::buildForm($form, $form_state, $queue_name);

    return $form;
  }

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

    $suspend_time = !empty($settings['time']) ? $settings['time'] : $queue_manager->getSuspendTime($queue_name);

    $queue_manager->suspendQueue($queue_name, $suspend_time);
  }

}
