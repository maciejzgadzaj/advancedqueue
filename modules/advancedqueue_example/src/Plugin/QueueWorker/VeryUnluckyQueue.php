<?php

namespace Drupal\advancedqueue_example\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * An example of basic AdvancedQueue queue worker definition.
 *
 * @QueueWorker(
 *   id = "very_unlucky_queue",
 *   title = @Translation("Very unlucky queue"),
 *   description = @Translation("A queue which will almost surely fail processing items."),
 *   group = "More examples",
 *   retry = {
 *     "attempts" = 10,
 *     "delay" = 30,
 *   },
 *   delete = {
 *     "when_completed" = TRUE,
 *     "hard" = TRUE,
 *   },
 *   cron = {
 *     "time" = 5,
 *   },
 * )
 *
 * @see AdvancedQueueWorkerManager::$defaults
 *   for more information on all available properties and their values.
 * @see \Drupal\advancedqueue_example\Plugin\QueueWorker\LuckyQueue
 *   for full definition example.
 */
class VeryUnluckyQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\advancedqueue_example\Plugin\QueueWorker\LuckyQueue
   *   for more information on additional parameters and processing flow.
   */
  public function processItem($data) {
    \Drupal::logger('advancedqueue_example')->debug(t('The "@name" worker is now processing item.', ['@name' => $this->getPluginId()]));

    sleep(5);

    $result = mt_rand(0, 10);
    if ($result < 1) {
      return 'Processed successfully.';
    }
    elseif ($result < 7) {
      throw new RequeueException('Processing failed, will retry.');
    }
    elseif ($result < 8) {
      throw new SuspendQueueException('Queue processing suspended.');
    }
    else {
      throw new \Exception('Processing failed.');
    }
  }

}
