<?php

namespace Drupal\advancedqueue\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Defines a profile operations bulk form element.
 *
 * @ViewsField("advancedqueue_bulk_form")
 */
class AdvancedQueueBulkForm extends BulkForm {

  /**
   * An array of queue worker plugin definitions.
   *
   * @var array
   */
  private $queueDefinitions;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['name'] = 'name';

    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    $this->queueDefinitions = $queue_manager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // BulkForm::query() does not add additional fields.
    $this->ensureMyTable();
    $this->addAdditionalFields();
    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    // Do not show bulk checkboxes for "undefined" queues
    // (for example "update_fetch_tasks").
    if (in_array($row->advancedqueue_name, array_keys($this->queueDefinitions))) {
      return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return t('No queue item selected.');
  }

}
