<?php

namespace Drupal\advancedqueue\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a AdvancedQueue Item entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup advancedqueue
 */
interface AdvancedQueueItemInterface extends ContentEntityInterface {

}
