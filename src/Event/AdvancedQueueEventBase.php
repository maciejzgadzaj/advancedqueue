<?php

namespace Drupal\advancedqueue\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Base methods for Advanced Queue events.
 *
 * @package Drupal\advancedqueue
 */
class AdvancedQueueEventBase extends Event {

  /**
   * The queue name the item to be processed belongs to.
   *
   * @var string
   */
  protected $queueName;

  /**
   * AdvancedQueueEventBase constructor.
   *
   * @param string $queue_name
   *   A queue name the event relates to.
   */
  public function __construct($queue_name) {
    $this->queueName = $queue_name;
  }

  /**
   * Returns name of the queue the event relates to.
   *
   * @return string
   *   A queue name the event relates to.
   */
  public function getQueueName() {
    return $this->queueName;
  }

}
