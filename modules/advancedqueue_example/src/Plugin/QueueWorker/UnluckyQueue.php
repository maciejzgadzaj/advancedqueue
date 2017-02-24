<?php

namespace Drupal\advancedqueue_example\Plugin\QueueWorker;

use Drupal\advancedqueue\Queue\AdvancedQueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * An example of basic AdvancedQueue queue worker definition.
 *
 * @QueueWorker(
 *   id = "unlucky_queue",
 *   title = @Translation("Unlucky queue"),
 *   description = @Translation("A queue which will most probably fail processing items."),
 *   group = "Examples",
 *   retry = {
 *     "attempts" = 10,
 *     "delay" = 30,
 *   },
 *   cron = {
 *     "allow": TRUE,
 *     "time" = 10,
 *   },
 * )
 *
 * @see AdvancedQueueWorkerManager::$defaults
 *   for more information on all available properties and their values.
 * @see \Drupal\advancedqueue_example\Plugin\QueueWorker\LuckyQueue
 *   for full definition example.
 */
class UnluckyQueue extends AdvancedQueueWorkerBase {

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
    if ($result < 3) {
      return 'Processed successfully.';
    }
    elseif ($result < 7) {
      throw new RequeueException('Processing failed, will retry.');
    }
    else {
      throw new \Exception('Processing failed.');
    }
  }

}
