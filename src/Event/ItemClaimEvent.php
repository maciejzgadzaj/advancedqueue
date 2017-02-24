<?php

namespace Drupal\advancedqueue\Event;

/**
 * Event that is fired after claiming a queue item for processing.
 *
 * @package Drupal\advancedqueue
 */
class ItemClaimEvent extends ItemEventBase {

  /**
   * A time in seconds the item has been claimed for.
   *
   * @var int
   */
  protected $leaseTime;

  /**
   * ItemClaimEvent constructor.
   *
   * @param string $queue_name
   *   A queue name the event relates to.
   * @param object $item
   *   A queue item the event relates to.
   * @param int $lease_time
   *   A time in seconds the item has been claimed for.
   */
  public function __construct($queue_name, $item, $lease_time) {
    parent::__construct($queue_name, $item);

    $this->leaseTime = $lease_time;
  }

  /**
   * Returns a time in seconds the item has been claimed for.
   *
   * @return int
   *   A time in seconds the item has been claimed for.
   */
  public function getLeaseTime() {
    return $this->leaseTime;
  }

}
