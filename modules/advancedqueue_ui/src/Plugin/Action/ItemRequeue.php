<?php

namespace Drupal\advancedqueue_ui\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Requeues a queue item.
 *
 * @Action(
 *   id = "item_requeue_action",
 *   label = @Translation("Requeues the selected queue items"),
 *   type = "advancedqueue_item",
 *   confirm_form_route_name = "advancedqueue_ui.bulk_item_requeue_confirm",
 *   weight = 4,
 * )
 */
class ItemRequeue extends BulkActionBase implements ContainerFactoryPluginInterface {

  /**
   * Confirmation form ID and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_item_requeue_confirm';

}
