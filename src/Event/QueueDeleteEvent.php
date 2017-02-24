<?php

namespace Drupal\advancedqueue\Event;

/**
 * Event that is fired after deleting all items from a queue.
 *
 * @package Drupal\advancedqueue
 */
class QueueDeleteEvent extends QueueEventBase {

  /**
   * A boolean indicating whether the items were hard- or soft-deleted.
   *
   * @var bool
   */
  protected $hard;

  /**
   * QueueDeleteEvent constructor.
   *
   * @param string $queue_name
   *   A queue name the event relates to.
   * @param bool $hard
   *   A boolean indicating whether the items were hard-deleted (permanently
   *   removed from the database) or soft-deleted (their properties updated).
   */
  public function __construct($queue_name, $hard) {
    parent::__construct($queue_name);

    $this->hard = $hard;
  }

  /**
   * Returns a boolean indicating whether the items were hard- or soft-deleted.
   *
   * @return bool
   *   A boolean indicating whether the items were hard-deleted (permanently
   *   removed from the database) or soft-deleted (just their properties
   *   updated).
   */
  public function getHard() {
    return $this->hard;
  }

}
