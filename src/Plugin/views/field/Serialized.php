<?php

namespace Drupal\advancedqueue\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to show data of serialized fields.
 *
 * Extending core Serialized handler to avoid PHP "Undefined index" notices
 * in case the requested element does not exist in the serialized array.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("advancedqueue_serialized")
 */
class Serialized extends \Drupal\views\Plugin\views\field\Serialized {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};

    if ($this->options['format'] == 'unserialized') {
      return $this->sanitizeValue(print_r(unserialize($value), TRUE));
    }
    elseif ($this->options['format'] == 'key' && !empty($this->options['key'])) {
      $value = (array) unserialize($value);
      $value = isset($value[$this->options['key']]) ? $value[$this->options['key']] : '';
      return $this->sanitizeValue($value);
    }

    return $value;
  }

}
