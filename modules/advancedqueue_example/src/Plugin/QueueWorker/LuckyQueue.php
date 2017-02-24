<?php

namespace Drupal\advancedqueue_example\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * An example of full AdvancedQueue queue worker definition.
 *
 * @QueueWorker(
 *   id = "lucky_queue",
 *   title = @Translation("Lucky queue"),
 *   description = @Translation("A queue that has a good chance of successfully processing items."),
 *   group = "Examples",
 *   lease = {
 *     "time" = 30,
 *   },
 *   retry = {
 *     "attempts" = 5,
 *     "delay" = "10,30,60",
 *   },
 *   suspend = {
 *     "time" = 60,
 *   },
 *   execute_hooks = {
 *     "preprocess" = FALSE,
 *     "postprocess" = FALSE,
 *   },
 *   delete = {
 *     "when_completed" = TRUE,
 *     "hard" = FALSE,
 *   },
 *   cron = {
 *     "time" = 10,
 *   },
 * )
 *
 * @see AdvancedQueueWorkerManager::$defaults
 *   for more information on all available properties and their values.
 * @see \Drupal\advancedqueue_example\Plugin\QueueWorker\UnluckyQueue
 *   for basic definition example.
 */
class LuckyQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // By default Drupal sends only item's $data to the queue worker.
    // AdvancedQueue however will pass additionally full $item object and
    // $end_time value indicating when the item processing should end.
    // We can get them from the function arguments.
    $args = func_get_args();
    // Remove the first argument, which is item data.
    array_shift($args);
    // Second argument is full item object.
    /** @var \Drupal\advancedqueue\Entity\AdvancedQueueItemInterface $item */
    $item = array_shift($args);
    // Third argument is a timestamp when the item processing should end.
    $end_time = array_shift($args);

    // Log a happy processing message.
    $params = [
      '@name' => $item->name,
      '@id' => $item->item_id,
      '@title' => $item->title,
      '@uid' => $item->uid,
      '@time' => date('Y-m-d H:i:s', $item->created),
    ];
    \Drupal::logger('advancedqueue_example')->debug(t('The "@name" worker is now processing item ID @id "@title" for user ID @uid created on @time.', $params));

    // Make the processing last for 5 seconds, so that we can test processing
    // timeouts.
    sleep(5);

    // Return a random processing result. Obviously in a real world scenario
    // this should be based on some proper conditions.
    $result = mt_rand(0, 10);
    if ($result < 6) {
      return 'Processed successfully.';
    }
    elseif ($result < 8) {
      throw new RequeueException('Processing failed, will retry.');
    }
    elseif ($result < 9) {
      throw new SuspendQueueException('Queue suspended.');
    }
    else {
      throw new \Exception('Processing failed.');
    }
  }

}
