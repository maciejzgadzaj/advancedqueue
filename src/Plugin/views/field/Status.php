<?php

namespace Drupal\advancedqueue\Plugin\views\field;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show queue status as string.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("advancedqueue_status")
 */
class Status extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $label = AdvancedQueueItem::getStatusLabel($value);
    return $this->sanitizeValue('<span class="advancedqueue-status-' . $value . '">' . $label . '</span>', 'xss_admin');
  }

}
