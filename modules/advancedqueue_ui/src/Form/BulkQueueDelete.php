<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;

/**
 * Provides a confirmation form for deleting all queue items.
 */
class BulkQueueDelete extends BulkItemDelete {

  /**
   * {@inheritdoc}
   */
  protected function getItemInfo() {
    // If processing a group, get all queue names which belong to this group.
    if (strpos($this->queueName, 'group:') === 0) {
      if ($definitions = $this->queueManager->getGroupDefinitions(str_replace('group:', '', $this->queueName))) {
        $this->queueName = array_keys($definitions);
      }
    }

    return AdvancedQueueItem::loadItems(['name' => $this->queueName]);
  }

}
