<?php

namespace Drupal\advancedqueue\Event;

/**
 * Contains all events thrown in the Advanced Queue module.
 */
final class AdvancedQueueEvents {

  /**
   * The QUEUE_SUSPEND event is fired after suspending queue processing.
   *
   * @Event
   *
   * @var string
   */
  const QUEUE_SUSPEND = 'queue.suspend';

  /**
   * The QUEUE_UNSUSPEND event is fired after unsuspending queue processing.
   *
   * @Event
   *
   * @var string
   */
  const QUEUE_UNSUSPEND = 'queue.unsuspend';

  /**
   * The ITEM_PREPROCESS event is fired before queue item processing starts.
   *
   * @Event
   *
   * @var string
   */
  const ITEM_PREPROCESS = 'item.preprocess';

  /**
   * The ITEM_POSTPROCESS event is fired after queue item processing finishes.
   *
   * @Event
   *
   * @var string
   */
  const ITEM_POSTPROCESS = 'item.postprocess';

  /**
   * The ITEM_RELEASE event is fired after queue item is released.
   *
   * @Event
   *
   * @var string
   */
  const ITEM_RELEASE = 'item.release';

  /**
   * The ITEM_REQUEUE event is fired after queue item is requeued.
   *
   * @Event
   *
   * @var string
   */
  const ITEM_REQUEUE = 'item.requeue';

  /**
   * The ITEM_RESET event is fired after queue item attempt counter is reset.
   *
   * @Event
   *
   * @var string
   */
  const ITEM_RESET = 'item.reset';

  /**
   * The ITEM_DELETE event is fired after queue item is deleted.
   *
   * @Event
   *
   * @var string
   */
  const ITEM_DELETE = 'item.delete';

}
