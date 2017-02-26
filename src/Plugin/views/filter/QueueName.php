<?php

namespace Drupal\advancedqueue\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\advancedqueue\Entity\AdvancedQueueItem;

/**
 * Filter handler for advancedqueue queue names.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("advancedqueue_queue_name")
 */
class QueueName extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
      $queue_manager = \Drupal::service('plugin.manager.queue_worker');
      $this->valueOptions = $queue_manager->getOptions();
    }

    return $this->valueOptions;
  }

}
