<?php

namespace Drupal\advancedqueue\Event;

/**
 * Event that is fired before processing a queue item.
 *
 * @package Drupal\advancedqueue
 */
class PostExecuteEvent extends AdvancedQueueEventBase {

  const EVENT_NAME = 'advancedqueue_post_execute';

}
