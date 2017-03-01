<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a node deletion confirmation form.
 */
abstract class BulkConfirmFormBase extends ConfirmFormBase {

  /**
   * The name of the queue to process.
   *
   * @var string
   */
  protected $queueName;

  /**
   * The array of items to process.
   *
   * @var string[][]
   */
  protected $itemInfo = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a BulkConfirmFormBase form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('advancedqueue_item');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return static::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->itemInfo), 'Are you sure you want to @operation this item?', 'Are you sure you want to @operation these items?', ['@operation' => static::QUESTION_OPERATION]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('advancedqueue_ui.all_items');
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
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
    $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');
    $definitions = $queue_worker_manager->getDefinitions();

    /** @var \Drupal\advancedqueue\Entity\AdvancedQueueItemInterface $item */
    foreach ($this->itemInfo as $item_id => $item) {
      if (!isset($form[$item->name])) {
        $form[$item->name] = [
          '#theme' => 'item_list',
          '#title' => $definitions[$item->name]['title'],
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

      $batch = [
        'title' => $this->t('Processing items'),
        'operations' => [],
        'finished' => [get_class($this), 'batchFinish'],
        'init_message' => $this->t('Initializing.'),
        'progress_message' => $this->t('Completed @current out of @total.'),
        'error_message' => $this->t('Processing has encountered an error.'),
      ];

      foreach ($this->itemInfo as $item_id => $item) {
        $batch['operations'][] = [[get_class($this), 'batchProcess'], [$item, $form_state->getValue('settings')]];
      }

      batch_set($batch);

      // Clear the TempStore collection.
      $this->tempStoreFactory->get(static::FORM_ID)->delete(\Drupal::currentUser()->id());
    }
    $form_state->setRedirect('advancedqueue_ui.all_items');
  }

  /**
   * Batch operation callback: processes a single queue item.
   *
   * @param \Drupal\advancedqueue\Entity\AdvancedQueueItemInterface $item
   *   The item entity to process.
   * @param array $settings
   *   Additional settings specific for the operation.
   * @param array $context
   *   The batch context.
   *
   * @see submitForm()
   */
  public static function batchProcess($item, $settings, &$context) {
    try {
      static::doBatchProcess($item, $settings, $context);
    }
    catch (Exception $e) {
      if (!isset($context['results']['errors'])) {
        $context['results']['errors'] = [];
      }
      $context['results']['errors'][] = $e->getMessage();
    }
  }

  /**
   * Finish batch.
   *
   * @see submitForm()
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          drupal_set_message($error, 'error');
          \Drupal::logger('advancedqueue_ui')->error($error);
        }
        drupal_set_message(\Drupal::translation()->translate('The operations have been completed with errors.'), 'warning');
      }
      else {
        drupal_set_message(\Drupal::translation()->translate('The operations have been completed successfully.'));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = \Drupal::translation()->translate('An error occurred while processing %error_operation with arguments: @arguments', ['%error_operation' => $error_operation[0], '@arguments' => print_r($error_operation[1], TRUE)]);
      drupal_set_message($message, 'error');
    }
  }

}
