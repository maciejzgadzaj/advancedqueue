<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\advancedqueue\Utility\AdvancedQueueSortItemArray;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a node deletion confirmation form.
 */
class BulkConfirmItemForm extends BulkConfirmFormBase {

  /**
   * The array of items to process.
   *
   * @var string[][]
   */
  protected $itemInfo = [];

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->itemInfo), 'Are you sure you want to @operation this item?', 'Are you sure you want to @operation these items?', ['@operation' => static::QUESTION_OPERATION]);
  }

  /**
   * Returns an array of queue items to process.
   *
   * @return array
   *   An array of queue items to process.
   */
  protected function getItemInfo() {
    $item_info = $this->tempStoreFactory->get(static::FORM_ID)->get(\Drupal::currentUser()->id());
    return AdvancedQueueItem::loadItems(['item_id' => array_keys($item_info)]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $queue_name = NULL) {
    $this->queueName = $queue_name;

    if (!$this->itemInfo = $this->getItemInfo()) {
      drupal_set_message($this->t('Unable to find items matching your request.'), 'error');
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    /** @var \Drupal\advancedqueue\Entity\AdvancedQueueItemInterface $item */
    foreach ($this->itemInfo as $item_id => $item) {
      if (!isset($form[$item->name])) {
        $form[$item->name] = [
          '#theme' => 'item_list',
          '#title' => $this->queueManager->getTitle($item->name),
          '#items' => [],
        ];
      }
      $form[$item->name]['#items'][] = new FormattableMarkup('@item_id: %title', [
        '@item_id' => $item_id,
        '%title' => $item->title,
      ]);
    }

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->itemInfo)) {
      // Sort all items by their 'created' date and 'item_id' values,
      // so they are processed in the correct order.
      uasort($this->itemInfo, [AdvancedQueueSortItemArray::class, 'sortByCreatedAndItemID']);

      $batch = [
        'title' => $this->t('Processing items'),
        'operations' => [],
        'finished' => [get_class($this), 'batchFinish'],
        'init_message' => $this->t('Initializing.'),
        'progress_message' => $this->t('Completed @current out of @total.'),
        'error_message' => $this->t('Processing has encountered an error.'),
      ];

      foreach ($this->itemInfo as $item_id => $item) {
        $batch['operations'][] = [
          [get_class($this), 'batchProcess'],
          [$item, $form_state->getValue('settings')],
        ];
      }

      batch_set($batch);

      // Clear the TempStore collection.
      $this->tempStoreFactory->get(static::FORM_ID)->delete(\Drupal::currentUser()->id());
    }
    $form_state->setRedirect('advancedqueue_ui.all_items');
  }

}
