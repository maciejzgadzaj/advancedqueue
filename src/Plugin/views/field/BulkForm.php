<?php

namespace Drupal\advancedqueue\Plugin\views\field;

/**
 * Defines a profile operations bulk form element.
 *
 * @ViewsField("advancedqueue_bulk_form")
 */
class BulkForm extends \Drupal\system\Plugin\views\field\BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return t('No queue item selected.');
  }

}
