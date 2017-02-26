<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;

/**
 * Provides a confirmation form for processing all unprocessed queue items.
 */
class BulkQueueProcess extends BulkItemProcess {

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

    $conditions = [
      'name' => $this->queueName,
      'status' => [
        AdvancedQueueItem::STATUS_QUEUED,
        AdvancedQueueItem::STATUS_FAILURE_RETRY,
      ],
    ];
    $order_by = [
      'created' => 'ASC',
      'item_id' => 'ASC',
    ];
    return AdvancedQueueItem::loadItems($conditions, $order_by);
  }

}
