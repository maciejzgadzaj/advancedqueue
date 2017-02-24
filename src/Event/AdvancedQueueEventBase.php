<?php

namespace Drupal\advancedqueue\Event;

use Drupal\advancedqueue\Entity\AdvancedQueueItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired before processing a queue item.
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
   * The item about to be processed.
   *
   * @var object
   */
  protected $item;

  /**
   * AdvancedQueueEventBase constructor.
   *
   * @param string $queue_name
   *   The queue name the item to be processed belongs to.
   * @param object $item
   *   The item about to be processed.
   */
  public function __construct($queue_name, $item) {
    $this->queueName = $queue_name;
    $this->item = $item;
  }

  /**
   * Returns the queue name the item belongs to.
   *
   * @return string
   *   The queue name the item to be processed belongs to.
   */
  public function getQueueName() {
    return $this->queueName;
  }

  /**
   * Returns the item being processed.
   *
   * @return object
   *   The item about to be processed.
   */
  public function getItem() {
    return $this->item;
  }

}
