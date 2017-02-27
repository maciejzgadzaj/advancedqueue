<?php

namespace Drupal\advancedqueue;

use Drupal\Core\Cron;
use Drupal\Core\Site\Settings;

/**
 * The Drupal core Cron service extension for custom queue handling.
 */
class AdvancedQueueCron extends Cron {

  /**
   * Processes cron queues.
   */
  protected function processQueues() {
    // Use core processing if AdvancedQueue service is not enabled.
    if (Settings::get('queue_default') != 'queue.advancedqueue') {
      return parent::processQueues();
    }

    // Abort if module is configured to not use cron for queue processing.
    $config = \Drupal::config('advancedqueue.settings');
    if (!$config->get('use_cron')) {
      return;
    }

    // Abort if there are no defined queues.
    if (!$queue_worker_definitions = $this->queueManager->getDefinitions()) {
      return;
    }

    // Maximum time which cron can spent on processing all queues.
    $cron_end = time() + $config->get('processing_timeout.cron');

    foreach (array_keys($queue_worker_definitions) as $queue_name) {

      // Skip to next queue if this queue is not allowed
      // to be processed by cron.
      if (!$this->queueManager->getCronAllow($queue_name)) {
        continue;
      }

      // Maximum time which cron can spend on processing this queue.
      $queue_end = time() + $this->queueManager->getCronTime($queue_name);

      /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
      $queue = $this->queueManager->getQueue($queue_name);

      while (time() < $cron_end && time() < $queue_end && ($item = $queue->claimItem())) {
        $queue->processItem($item, min($cron_end, $queue_end));
      }

    }
  }

}
