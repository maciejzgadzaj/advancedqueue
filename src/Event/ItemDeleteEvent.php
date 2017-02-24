<?php

namespace Drupal\advancedqueue\Event;

/**
 * Event that is fired after deleting a queue item.
 *
 * @package Drupal\advancedqueue
 */
class ItemDeleteEvent extends ItemEventBase {

  /**
   * A boolean indicating whether the item was hard- or soft-deleted.
   *
   * @var bool
   */
  protected $hard;

  /**
   * ItemDeleteEvent constructor.
   *
   * @param string $queue_name
   *   A queue name the event relates to.
   * @param object $item
   *   A queue item the event relates to.
   * @param bool $hard
   *   A boolean indicating whether the item was hard-deleted (permanently
   *   removed from the database) or soft-deleted (just its properties updated).
   */
  public function __construct($queue_name, $item, $hard) {
    parent::__construct($queue_name, $item);

    $this->hard = $hard;
  }

  /**
   * Returns a boolean indicating whether the item was hard- or soft-deleted.
   *
   * @return bool
   *   A boolean indicating whether the item was hard-deleted (permanently
   *   removed from the database) or soft-deleted (just its properties updated).
   */
  public function getHard() {
    return $this->hard;
  }

}
