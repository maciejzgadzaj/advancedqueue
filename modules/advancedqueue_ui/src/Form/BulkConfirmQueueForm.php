<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a node deletion confirmation form.
 */
class BulkConfirmQueueForm extends BulkConfirmFormBase {

  /**
   * The array of queues to process.
   *
   * @var string[][]
   */
  protected $queueInfo = [];

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->queueInfo), 'Are you sure you want to @operation this queue?', 'Are you sure you want to @operation these queues?', ['@operation' => static::QUESTION_OPERATION]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueueInfo() {
    // If processing a group, get all queue names which belong to this group.
    if (strpos($this->queueName, 'group:') === 0) {
      if ($definitions = $this->queueManager->getGroupDefinitions(str_replace('group:', '', $this->queueName))) {
        $this->queueName = array_keys($definitions);
      }
    }

    return (array) $this->queueName;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $queue_name = NULL) {
    $this->queueName = $queue_name;

    if (!$this->queueInfo = $this->getQueueInfo()) {
      drupal_set_message($this->t('Unable to find queues matching your request.'), 'error');
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['queues'] = [
      '#theme' => 'item_list',
      '#items' => [],
    ];

    foreach ($this->queueInfo as $queue_name) {
      $form['queues']['#items'][] = new FormattableMarkup('@queue_name: %title', [
        '@queue_name' => $queue_name,
        '%title' => $this->queueManager->getTitle($queue_name),
      ]);
    }

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->queueInfo)) {
      $batch = [
        'title' => $this->t('Processing queues'),
        'operations' => [],
        'finished' => [get_class($this), 'batchFinish'],
        'init_message' => $this->t('Initializing.'),
        'progress_message' => $this->t('Completed @current out of @total.'),
        'error_message' => $this->t('Processing has encountered an error.'),
      ];

      foreach ($this->queueInfo as $queue_name) {
        $batch['operations'][] = [
          [get_class($this), 'batchProcess'],
          [$queue_name, $form_state->getValue('settings')],
        ];
      }

      batch_set($batch);

      // Clear the TempStore collection.
      $this->tempStoreFactory->get(static::FORM_ID)->delete(\Drupal::currentUser()->id());
    }
    $form_state->setRedirect('advancedqueue_ui.all_items');
  }

}
