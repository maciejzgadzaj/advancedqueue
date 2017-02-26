<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The queue worker plugin manager.
   *
   * @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager
   */
  protected $queueManager;

  /**
   * Constructs a BulkConfirmFormBase form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager
   *   The queue worker plugin manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, AdvancedQueueWorkerManager $queue_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $entity_type_manager->getStorage('advancedqueue_item');
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.queue_worker')
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
