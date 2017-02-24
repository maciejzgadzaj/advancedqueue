<?php

namespace Drupal\advancedqueue_ui\Form;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;

/**
 * Provides a confirmation form for deleting all queue items.
 */
class QueueDelete extends BulkDelete {

  /**
   * {@inheritdoc}
   */
  protected function getItemInfo() {
    // If processing a group, get all queue names which belong to this group.
    if (strpos($this->queueName, 'group:') === 0) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
      $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');
      if ($definitions = $queue_worker_manager->getGroupDefinitions(str_replace('group:', '', $this->queueName))) {
        $this->queueName = array_keys($definitions);
      }
    }

    return AdvancedQueueItem::loadItems(['name' => $this->queueName]);
  }

}