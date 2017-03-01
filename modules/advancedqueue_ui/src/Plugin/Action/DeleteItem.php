<?php

namespace Drupal\advancedqueue_ui\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Deletes a queue item.
 *
 * @Action(
 *   id = "item_delete_action",
 *   label = @Translation("Deletes the selected queue items"),
 *   type = "advancedqueue_item",
 *   confirm_form_route_name = "advancedqueue_ui.bulk_delete_confirm",
 *   weight = 10,
 * )
 */
class DeleteItem extends BulkActionBase implements ContainerFactoryPluginInterface {

  /**
   * Confirmation form ID and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_delete_confirm';

}
