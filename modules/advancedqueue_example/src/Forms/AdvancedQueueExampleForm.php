<?php

namespace Drupal\advancedqueue_example\Forms;

use Drupal\advancedqueue\AdvancedQueuePluginManager;
use Drupal\advancedqueue\Queue\AdvancedQueue;
use Drupal\Component\Utility\Html;
use Drupal\Core\Queue\QueueGarbageCollectionInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with examples on how to use queue.
 */
class AdvancedQueueExampleForm extends FormBase {

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The CronInterface object.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * What kind of queue backend are we using?
   *
   * @var string
   */
  protected $queueType;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue factory service to get new/existing queues for use.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(QueueFactory $queue_factory, Connection $database, CronInterface $cron, Settings $settings) {
    $this->queueFactory = $queue_factory;
    $this->queueType = $settings->get('queue_default', 'queue.database');
    $this->database = $database;
    $this->cron = $cron;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('queue'), $container->get('database'), $container->get('cron'), $container->get('settings'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Return a string that is the unique ID of our form. Best practice here is
    // to namespace the form based on your module's name.
    return 'advancedqueue_example';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
    $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');

    $form = [];

    $form['queue_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Queue'),
      '#options' => $queue_worker_manager->getOptions(),
      '#default_value' => 'example_queue',
    ];

    $form['create'] = [
      '#type' => 'details',
      '#title' => $this->t('Create items'),
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];

    $form['create']['item_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item title'),
      '#default_value' => $this->t('Example item'),
    ];

    $form['create']['create_count'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Count'),
      '#default_value' => 5,
    ];

    $form['create']['create_items'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create items'),
      '#submit' => [[$this, 'submitCreateItems']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing here.
  }

  public function submitCreateItems(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_factory->get($form_state->getValue('queue_name'));

    $item_ids = [];
    for ($i = 1; $i <= $form_state->getValue('create_count'); $i++) {
      $data = [
        'title' => $form_state->getValue('item_title'),
      ];
      $item_ids[] = $queue->createItem($data);
    }

    drupal_set_message(\Drupal::translation()->formatPlural($form_state->getValue('create_count'), 'Created 1 item: @item_ids.', 'Created @count items: @item_ids.', ['@item_ids' => implode(', ', $item_ids)]));
  }

}
