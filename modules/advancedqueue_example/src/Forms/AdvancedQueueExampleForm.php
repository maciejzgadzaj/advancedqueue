<?php

namespace Drupal\advancedqueue_example\Forms;

use Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with examples on how to use queue.
 */
class AdvancedQueueExampleForm extends FormBase {

  /**
   * The queue worker plugin manager.
   *
   * @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager
   */
  protected $queueManager;

  /**
   * Constructor.
   *
   * @param \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager
   *   The queue worker plugin manager.
   */
  public function __construct(AdvancedQueueWorkerManager $queue_manager) {
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.queue_worker')
    );
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
    $form = [];

    $form['queue_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Queue'),
      '#options' => $this->queueManager->getOptions(),
      '#multiple' => TRUE,
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
      '#title' => $this->t('Number of items'),
      '#default_value' => 1,
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

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitCreateItems(array &$form, FormStateInterface $form_state) {
    if ($queue_names = $form_state->getValue('queue_name')) {
      foreach ($queue_names as $queue_name) {
        /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
        $queue = $this->queueManager->getQueue($queue_name);

        $item_ids = [];
        for ($i = 1; $i <= $form_state->getValue('create_count'); $i++) {
          $data = [
            'title' => $form_state->getValue('item_title'),
          ];
          $item_ids[] = $queue->createItem($data);
        }

        drupal_set_message(\Drupal::translation()
          ->formatPlural($form_state->getValue('create_count'), 'Created 1 item @item_ids in queue %queue_name.', 'Created @count items @item_ids in queue %queue_name.', [
            '@item_ids' => implode(', ', $item_ids),
            '%queue_name' => $queue_name
          ]));
      }
    }
  }

}
