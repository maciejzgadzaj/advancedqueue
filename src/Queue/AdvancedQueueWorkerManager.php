<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\Core\Queue\QueueWorkerManager;

/**
 * Defines the queue worker manager.
 *
 * @see \Drupal\Core\Queue\QueueWorkerInterface
 * @see \Drupal\Core\Queue\QueueWorkerBase
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see plugin_api
 */
class AdvancedQueueWorkerManager extends QueueWorkerManager {

  /**
   * Default group name used for queue workers without "group" name defined.
   */
  const UNGROUPED = 'Ungrouped';

  /**
   * Provides default values for a queue plugin definition.
   *
   * @var array
   */
  protected $defaults = [
    // A group name the queue belongs to.
    'group' => self::UNGROUPED,
    'lease' => [
      // Time in seconds for which a queue item can be claimed.
      'time' => 30,
    ],
    'retry' => [
      // How many times a queue item should be attempted to be reprocessed
      // before giving up in case the queue worker throws RequeueException.
      'attempts' => 0,
      // Time delay in seconds between subsequent processing attempts.
      // More complicated patterns can be used as well. For example, to specify
      // a different retry delay for each attempt, define 'delay' as a string
      // like "10,30,60,300" (separating each delay by a comma).
      // Retry delays could also be grouped, if the same delay is to be used by
      // multiple attempts. For example, to run first 3 retries after 30 seconds,
      // next 4 retries after 60 seconds, and the final retry (or all remaining
      // retries) after 120 seconds, define 'retry.delay' as "30*3,60*4,120".
      'delay' => 0,
    ],
    'execute_hooks' => [
      // A boolean indicating whether the preprocess hooks should be invoked
      // before processing a queue item.
      'preprocess' => TRUE,
      // A boolean indicating whether the postprocess hooks should be invoked
      // after processing a queue item.
      'postprocess' => TRUE,
    ],
    'delete' => [
      // A boolean indicating whether a queue item should be "deleted"
      // after processing.
      'when_completed' => TRUE,
      // A boolean indicating whether a queue item should be hard-deleted,
      // (permanently removed from the database) or soft-deleted (item's
      // "processed" date set to now, "expire" date unset and "status" set to
      // "Processed" if its previous value was either "Queued" or "Processing").
      'hard' => FALSE,
    ],
    'cron' => [
      // A boolean indicating whether the queue can be processed by cron.
      'allow' => FALSE,
      // Time in seconds cron will be allowed to process the queue.
      'time' => 15,
    ],
  ];

  /**
   * Gets the definition of all queue worker plugins for provided group.
   *
   * @param string|null $group
   *   A group name to return plugin definitions for, or NULL if all plugin
   *   definitions should be returned (grouped by their "group" value).
   *
   * @return array
   *   An array of plugin definitions (empty if no definitions were found).
   *   Keys are plugin IDs.
   */
  public function getGroupDefinitions($group = NULL) {
    $group_definitions = &drupal_static(__FUNCTION__);
    if (!isset($group_definitions)) {

      $definitions = $this->getDefinitions();
      ksort($definitions);

      $group_definitions = [];
      foreach ($definitions as $id => $definition) {
        $definition_group = !empty($definition['group']) ? $definition['group'] : self::UNGROUPED;
        $group_definitions[$definition_group][$id] = $definition;
      }
      ksort($group_definitions);

    }
    return isset($group) ? (isset($group_definitions[$group]) ? $group_definitions[$group] : []) : $group_definitions;
  }

  /**
   * Returns the plugin labels as an options array.
   *
   * @return array
   *   The plugin labels as an options array.
   */
  public function getOptions() {
    $options = [];

    foreach ($this->getDefinitions() as $id => $definition) {
      $options[$id] = $definition['title'];
    }

    return $options;
  }

}
