<?php

namespace Drupal\advancedqueue_ui\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Resets attempt counter for a queue item.
 *
 * @Action(
 *   id = "item_reset_attempt_counter_action",
 *   label = @Translation("Resets attempt counter for the selected queue items"),
 *   type = "advancedqueue_item",
 *   confirm_form_route_name = "advancedqueue_ui.bulk_reset_attempt_counter_confirm",
 *   weight = 5,
 * )
 */
class ResetAttemptCounter extends BulkActionBase implements ContainerFactoryPluginInterface {

  /**
   * Confirmation form ID and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_reset_attempt_counter_confirm';

}
