<?php

namespace Drupal\advancedqueue_ui\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Processes a queue item.
 *
 * @Action(
 *   id = "item_process_action",
 *   label = @Translation("Processes the selected queue items"),
 *   type = "advancedqueue_item",
 *   confirm_form_route_name = "advancedqueue_ui.bulk_process_confirm",
 *   weight = 1,
 * )
 */
class ProcessItem extends BulkActionBase implements ContainerFactoryPluginInterface {

  /**
   * Confirmation form ID and PrivateTempStore name.
   */
  const FORM_ID = 'advancedqueue_ui_bulk_process_confirm';

}
