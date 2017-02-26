<?php

namespace Drupal\advancedqueue\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\views\Plugin\views\field\Field;

/**
 * A field that displays queue name.
 *
 * Provides configuration to display the queue name either as human-readable
 * queue title or queue machine name.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("advancedqueue_queue_name")
 */
class QueueName extends Field {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['queue_name_source'] = ['default' => 'label'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['queue_name_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display'),
      '#options' => [
        'label' => $this->t('Queue label'),
        'machine_name' => $this->t('Queue machine name'),
      ],
      '#default_value' => $this->options['queue_name_source'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  function render_item($count, $item) {
    if ($this->options['queue_name_source'] == 'label') {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
      $queue_manager = \Drupal::service('plugin.manager.queue_worker');
      if (in_array($item['raw']->value, array_keys($queue_manager->getDefinitions()))) {
        $item['rendered']['#context']['value'] = $queue_manager->getTitle($item['raw']->value);
      }
    }

    return render($item['rendered']);
  }

}
