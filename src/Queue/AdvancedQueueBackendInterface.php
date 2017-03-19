<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for a QueueWorker plugin.
 *
 * @see \Drupal\Core\Queue\QueueWorkerBase
 * @see \Drupal\Core\Queue\QueueWorkerManager
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see plugin_api
 */
interface AdvancedQueueBackendInterface extends PluginInspectionInterface {

  /**
   * Creates a new queue.
   *
   * @param $queue_name
   * @return mixed
   */
  public function queueCreate($queue_name);

  /**
   * Deletes a queue and all items in it.
   *
   * @param $queue_name
   * @return mixed
   */
  public function queueDelete($queue_name);

  /**
   * Creates a new queue item.
   *
   * @param $item
   * @return mixed
   */
  public function itemCreate($item);

  /**
   * Updates an existing queue item.
   *
   * @param $item
   * @return mixed
   */
  public function itemUpdate($item);

  /**
   * Deletes a queue item.
   *
   * @param $item
   * @return mixed
   */
  public function itemDelete($item);

}
