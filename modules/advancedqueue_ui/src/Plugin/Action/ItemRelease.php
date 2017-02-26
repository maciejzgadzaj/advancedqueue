<?php

namespace Drupal\advancedqueue_ui\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Releases a queue item.
 *
 * @Action(
 *   id = "item_release_action",
 *   label = @Translation("Releases the selected queue items"),
 *   type = "advancedqueue_item",
 *   confirm_form_route_name = "advancedqueue_ui.bulk_item_release_confirm",
 *   weight = 3,
 * )
 */
class ItemRelease extends BulkActionBase implements ContainerFactoryPluginInterface {

  /**
   * Confirmation form ID and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_item_release_confirm';

}
