<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\Core\Queue\QueueWorkerInterface;

/**
 * Defines an interface for a QueueWorker plugin.
 *
 * @see \Drupal\Core\Queue\QueueWorkerBase
 * @see \Drupal\Core\Queue\QueueWorkerManager
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see plugin_api
 */
interface AdvancedQueueWorkerInterface extends QueueWorkerInterface {

  /**
   * Returns a queue object used by the worker.
   *
   * @return \Drupal\advancedqueue\Queue\AdvancedQueue
   *   The queue object used by the worker.
   */
  public function getQueue();

  /**
   * Returns the human-readable queue worker title.
   *
   * @return string
   *   Human-readable queue worker title.
   */
  public function getTitle();

  /**
   * Returns the queue worker group name.
   *
   * @return string
   *   Queue worker group name.
   */
  public function getGroup();

  /**
   * Returns lease time for queue item.
   *
   * @return int
   *   Default lease time for queue item.
   */
  public function getLeaseTime();

  /**
   * Returns retry delay time for queue item.
   *
   * @param int $attempts
   *   A number of attempts to process the item so far.
   *
   * @return int
   *   Retry delay time for queue item.
   */
  public function getRetryDelay($attempts = NULL);

  /**
   * Returns max retry attempts for queue item.
   *
   * @return int
   *   Max retry attempts for queue item.
   */
  public function getRetryAttempts();

  /**
   * Whether the processing hooks should be executed.
   *
   * @param string $type
   *   A string indicating which hook type should be executed.
   *   Possible values are 'preprocess' and 'postprocess'.
   *
   * @return bool
   *   A boolean indicating whether the processing hooks of provided type
   *   should be executed.
   */
  public function executeHooks($type);

  /**
   * Whether the queue is allowed to be processed by cron.
   *
   * @return bool
   *   A boolean indicating whether the queue is allowed to be processed by
   *   cron.
   */
  public function getCronAllow();

  /**
   * Time in seconds the cron can spend on processing this queue.
   *
   * @return int
   *   Time in seconds the cron can spend on processing this queue.
   */
  public function getCronTime();

  /**
   * Whether the queue item should be "deleted" after processing.
   *
   * @return bool
   *   A boolean indicating whether the queue item should be deleted
   *   after processing.
   */
  public function getDeleteWhenCompleted();

  /**
   * Whether the queue item should be hard-deleted after processing.
   *
   * @return bool
   *   A boolean indicating whether the queue item should be hard-deleted
   *   after processing.
   */
  public function getDeleteHard();

}
