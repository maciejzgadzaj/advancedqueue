<?php

namespace Drupal\advancedqueue\Event;

/**
 * Event that is fired before processing a queue item.
 *
 * @package Drupal\advancedqueue
 */
class PreExecuteEvent extends AdvancedQueueEventBase {

  const EVENT_NAME = 'advancedqueue_pre_execute';

}
