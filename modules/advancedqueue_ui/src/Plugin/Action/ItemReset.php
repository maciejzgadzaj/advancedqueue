<?php

namespace Drupal\advancedqueue_ui\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Resets attempt counter for a queue item.
 *
 * @Action(
 *   id = "item_reset_action",
 *   label = @Translation("Resets attempt counter for the selected queue items"),
 *   type = "advancedqueue_item",
 *   confirm_form_route_name = "advancedqueue_ui.bulk_item_reset_confirm",
 *   weight = 5,
 * )
 */
class ItemReset extends BulkActionBase implements ContainerFactoryPluginInterface {

  /**
   * Confirmation form ID and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_item_reset_confirm';

}
