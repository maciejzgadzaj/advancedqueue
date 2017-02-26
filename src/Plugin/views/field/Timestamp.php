<?php

namespace Drupal\advancedqueue\Plugin\views\field;

use Drupal\views\Plugin\views\field\Field;

/**
 * A field that displays timestamp.
 *
 * Properly hides the value if the timestamp is 0.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("advancedqueue_timestamp")
 */
class Timestamp extends Field {

  /**
   * {@inheritdoc}
   */
  function render_item($count, $item) {
    if ($item['raw']->value) {
      return render($item['rendered']);
    }
  }

}
