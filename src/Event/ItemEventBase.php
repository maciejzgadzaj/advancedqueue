<?php

namespace Drupal\advancedqueue\Event;

/**
 * Base methods for item events.
 *
 * @package Drupal\advancedqueue
 */
class ItemEventBase extends AdvancedQueueEventBase {

  /**
   * A queue item the event relates to.
   *
   * @var object
   */
  protected $item;

  /**
   * ItemEventBase constructor.
   *
   * @param string $queue_name
   *   A queue name the event relates to.
   * @param object $item
   *   A queue item the event relates to.
   */
  public function __construct($queue_name, $item) {
    parent::__construct($queue_name);

    $this->item = $item;
  }

  /**
   * Returns queue item the event relates to.
   *
   * @return object
   *   A queue item the event relates to.
   */
  public function getItem() {
    return $this->item;
  }

}
