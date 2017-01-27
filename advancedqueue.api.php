<?php

/**
 * @file
 * Hooks provided by the Advanced queue module.
 */

/**
 * Declare queue(s) that will be run by Advanced queue.
 *
 * @return array
 *   Queue definitions.
 *
 * @see advancedqueue_example_worker()
 */
function hook_advanced_queue_info() {
  $queue_info['example_queue'] = array(
    'label' => t('Example queue'),
    'worker callback' => 'advancedqueue_example_worker',
    // Supply arguments for module_load_include() to load a file for the
    // worker callback.
    'worker include' => array(
      'inc',
      'advancedqueue_example',
      'advancedqueue_example.worker',
    ),
    'delete when completed' => TRUE,
    // The number of seconds to retry after.
    'retry after' => 10,
    // The maximum number of attempts after a failure.
    'max attempts' => 5,
    // Queues are weighted and all items in a lighter queue are processed
    // before queues with heavier weights.
    'weight' => 10,
  );
  return $queue_info;
}

/**
 * Alter queue(s) declared for Advanced queue.
 *
 * @param array $queue_info
 *   All queues defined by other modules.
 */
function hook_advanced_queue_info_alter(&$queue_info) {
  // Change the label.
  $queue_info['example_queue']['label'] = t('Altered example queue');
}