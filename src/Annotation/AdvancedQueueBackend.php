<?php

namespace Drupal\advancedqueue\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Declare a backend class for accessing a queue item.
 *
 * Plugin Namespace: Plugin\QueueBackend
 *
 * For a working example, see
 * \Drupal\aggregator\Plugin\QueueWorker\AggregatorRefresh.
 *
 * @see \Drupal\Core\Queue\QueueWorkerInterface
 * @see \Drupal\Core\Queue\QueueWorkerBase
 * @see \Drupal\Core\Queue\QueueWorkerManager
 * @see plugin_api
 *
 * @Annotation
 */
class AdvancedQueueBackend extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable title of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

}
