<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueWorkerManager;

/**
 * Extends the queue worker plugin manager.
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
  const GROUP_UNGROUPED = 'Ungrouped';

  /**
   * Default group name used for "undefined" queues (no queue worker plugin).
   */
  const GROUP_UNDEFINED = 'Undefined';

  /**
   * Provides default values for a queue plugin definition.
   *
   * @var array
   */
  protected $defaults = [
    // A group name the queue belongs to.
    'group' => self::GROUP_UNGROUPED,
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
    'suspend' => [
      // Time in seconds for which a queue processing should be suspended
      // after the queue worker throws SuspendQueueException.
      'time' => 3600,
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
    // An element indicating whether the queue can be processed by cron or not.
    // If 'cron' key is set, Drupal cron will be allowed to process the queue,
    // if processing queues via cron is enabled in the module configuration.
    // We leave it commented out here to let queue workers decide themselves
    // whether they want to allow cron processing, as otherwise it would not be
    // possible to unset this default value.
//  'cron' => [
      // Time in seconds cron will be allowed to process the queue.
//    'time' => 15,
//  ],
  ];

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct($namespaces, $cache_backend, $module_handler);

    $this->state = \Drupal::service('state');

    // Expression is not allowed as $defaults field default value,
    // so we need to set it properly here.
    $this->defaults['suspend']['time'] = \Drupal::config('advancedqueue.settings')->get('queue.suspend_time');
  }

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
        $definition_group = !empty($definition['group']) ? $definition['group'] : self::GROUP_UNGROUPED;
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
    ksort($options);

    return $options;
  }

  /**
   * Returns a queue object used by the worker.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return \Drupal\advancedqueue\Queue\AdvancedQueue
   *   The queue object used by the worker.
   */
  public function getQueue($plugin_id) {
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    return $queue_factory->get($plugin_id);
  }

  /**
   * Returns the human-readable queue worker title.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return string
   *   Human-readable queue worker title.
   */
  public function getTitle($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return !empty($plugin_definition['title']) ? $plugin_definition['title'] : $plugin_id;
  }

  /**
   * Returns the queue worker group name.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return string
   *   Queue worker group name.
   */
  public function getGroup($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return $plugin_definition['group'];
  }

  /**
   * Returns lease time for queue item.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return int
   *   Default lease time for queue item in seconds.
   */
  public function getLeaseTime($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return $plugin_definition['lease']['time'];
  }

  /**
   * Returns max retry attempts for queue item.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return int
   *   Max retry attempts for queue item.
   */
  public function getRetryAttempts($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return $plugin_definition['retry']['attempts'];
  }

  /**
   * Returns retry delay time for queue item.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   * @param int $attempt
   *   A number of attempts to process the item so far.
   *
   * @return int
   *   Retry delay time for queue item.
   */
  public function getRetryDelay($plugin_id, $attempt = 0) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $retry_after_attempts = 0;

    // Retry delays should be separated from each other by a comma.
    foreach (explode(',', $plugin_definition['retry']['delay']) as $retry_after) {
      // Handle retry delays [D] defined using multiplier [M] (ie. "D*M").
      if (strpos($retry_after, '*') !== FALSE) {
        list($retry_after, $multiplier) = explode('*', $retry_after);
        $retry_after_attempts += $multiplier;
      }
      // Handle retry delays defined without multiplier (ie. "D")
      // (M is always 1).
      else {
        $retry_after_attempts++;
      }

      // If the next attempt number falls within current retry delay range.
      if ($attempt <= $retry_after_attempts) {
        return $retry_after;
      }
    }

    // If we were not able to find a specific retry delay for the next attempt
    // number, just use the last retry delay value.
    return $retry_after;
  }

  /**
   * Returns queue suspend time in seconds.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return int
   *   Queue suspend time in seconds.
   */
  public function getSuspendTime($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return $plugin_definition['suspend']['time'];
  }

  /**
   * Whether the processing hooks should be executed.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   * @param string $type
   *   A string indicating which hook type should be checked for execution.
   *   Possible values are "preprocess" and "postprocess".
   *
   * @return bool
   *   A boolean indicating whether the processing hooks of provided type
   *   should be executed.
   */
  public function executeHooks($plugin_id, $type) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return $plugin_definition['execute_hooks'][$type];
  }

  /**
   * Whether the queue item should be "deleted" after processing.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return bool
   *   A boolean indicating whether the queue item should be deleted
   *   after processing.
   */
  public function getDeleteWhenCompleted($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return $plugin_definition['delete']['when_completed'];
  }

  /**
   * Whether the queue item should be hard-deleted after processing.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return bool
   *   A boolean indicating whether the queue item should be hard-deleted
   *   after processing.
   */
  public function getDeleteHard($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return $plugin_definition['delete']['hard'];
  }

  /**
   * Whether the queue is allowed to be processed by cron.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return bool
   *   A boolean indicating whether the queue is allowed to be processed by
   *   cron.
   */
  public function getCronAllow($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return isset($plugin_definition['cron']);
  }

  /**
   * Time in seconds the cron can spend on processing this queue.
   *
   * @param string $plugin_id
   *   A queue worker plugin id.
   *
   * @return int
   *   Time in seconds the cron can spend on processing this queue.
   */
  public function getCronTime($plugin_id) {
    $plugin_definition = $this->getDefinition($plugin_id);
    return $plugin_definition['cron']['time'];
  }

  /**
   * Suspends processing of a queue.
   *
   * @param string $queue_name
   *   A name of the queue to suspends processing of.
   * @param int|null $time
   *   Optional time in seconds to suspend processing for.
   *   If not provided, default queue worker definition time will be used.
   */
  public function suspendQueue($queue_name, $time = NULL) {
    $suspended_queues = $this->state->get('advancedqueue.suspended_queues', []);

    $suspend_time = !empty($time) ? $time : $this->getSuspendTime($queue_name);
    $suspended_queues[$queue_name] = time() + $suspend_time;

    $this->state->set('advancedqueue.suspended_queues', $suspended_queues);

    return $suspended_queues[$queue_name];
  }

  /**
   * Unsuspends processing of a suspended queue.
   *
   * @param string $queue_name
   *   A name of the queue to unsuspend processing of.
   */
  public function unsuspendQueue($queue_name) {
    $suspended_queues = $this->state->get('advancedqueue.suspended_queues', []);

    if (isset($suspended_queues[$queue_name])) {
      unset($suspended_queues[$queue_name]);
      $this->state->set('advancedqueue.suspended_queues', $suspended_queues);
    }
  }

  /**
   * Returns a list of suspended queues.
   *
   * @return array
   *   An array of all suspended queues, where keys are queue names and values
   *   are timestamps when the suspension will expire.
   */
  public function getSuspendedQueues() {
    $suspended_queues = $this->state->get('advancedqueue.suspended_queues', []);

    // Check if there are queues that should be unsuspended.
    foreach ($suspended_queues as $queue_name => $expire_time) {
      if ($expire_time <= time()) {
        $this->unsuspendQueue($queue_name);
        unset($suspended_queues[$queue_name]);
      }
    }

    return $suspended_queues;
  }

}
