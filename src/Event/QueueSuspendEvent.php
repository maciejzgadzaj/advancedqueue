<?php

namespace Drupal\advancedqueue\Event;

/**
 * Event that is fired after suspending queue processing.
 *
 * @package Drupal\advancedqueue
 */
class QueueSuspendEvent extends QueueEventBase {

  /**
   * Time in seconds the queue processing was suspended for.
   *
   * @var int
   */
  protected $suspendTime;

  /**
   * QueueSuspendEvent constructor.
   *
   * @param string $queue_name
   *   A queue name the event relates to.
   * @param int $suspend_time
   *   Time in seconds the queue processing was suspended for.
   */
  public function __construct($queue_name, $suspend_time) {
    parent::__construct($queue_name);

    $this->suspendTime = $suspend_time;
  }

  /**
   * Returns time in seconds the queue processing was suspended for.
   *
   * @return int
   *   Time in seconds the queue processing was suspended for.
   */
  public function getSuspendTime() {
    return $this->suspendTime;
  }

}
