<?php

namespace Drupal\advancedqueue\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\advancedqueue\Entity\AdvancedQueueItem;

/**
 * Filter handler for advancedqueue item statuses.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("advancedqueue_status")
 */
class Status extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = AdvancedQueueItem::getStatusOptions();
    }

    return $this->valueOptions;
  }

}
