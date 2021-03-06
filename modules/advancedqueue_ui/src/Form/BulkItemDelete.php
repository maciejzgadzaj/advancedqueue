<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for bulk deleting queue items.
 */
class BulkItemDelete extends BulkConfirmItemForm {

  /**
   * Form ID value and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_item_delete_confirm';

  /**
   * User-friendly operation name shown in the confirmation question.
   */
  const QUESTION_OPERATION = 'delete';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $queue_name = NULL) {

    $form['settings'] = [
      '#tree' => TRUE,
      '#weight' => 5,
    ];

    $form['settings']['hard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hard delete'),
      '#description' => $this->t('Enabling this option will permanently delete selected items from the database, regardless of queue worker settings. This action cannot be undone.'),
    ];

    $form = parent::buildForm($form, $form_state, $queue_name);

    return $form;
  }

  /**
   * Action-specific processing for batch operation callback.
   *
   * @param object $item
   *   The item to process.
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

    $hard = $settings['hard'] || $queue_manager->getDeleteHard($item->name);
    $queue->deleteItem($item, $hard, FALSE);
  }

}
