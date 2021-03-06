<?php

/**
 * @file
 * Contains the code to generate custom drush commands.
 */

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\advancedqueue\Queue\AdvancedQueue;
use Drupal\Core\Database\Database;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Implements hook_drush_command().
 */
function advancedqueue_drush_command() {
  $items = [];

  $items['advancedqueue-queue-list'] = [
    'description' => dt('Lists all defined queues and summary of queue items.'),
    'arguments' => [
      'queues' => dt('Queues or queue groups to list. Either a single name, a comma-separated list or "all". [default: "all"]'),
    ],
    'options' => [
      'all' => dt('Return extended summary of queues and queue items.'),
      'pipe' => dt('Return a comma delimited list of queues.'),
    ],
    'aliases' => ['aqql'],
    'examples' => [
      'drush aqql' => dt('Lists all defined queues and their basic summary (number of unprocessed items for each queue (queued + processing + retry)). Equivalent to "drush aqql all".'),
      'drush aqql mygroup' => dt('Lists all defined queues belonging to "mygroup" group.'),
      'drush aqql mygroup1,mygroup2' => dt('Lists all defined queues belonging to "mygroup1" or "mygroup2" group.'),
      'drush aqql all --all' => dt('Lists all defined queues split into groups and their extended summary (number of items in each status).'),
      'drush aqql --pipe' => dt('Lists all defined queues in a comma-separated format.'),
    ],
  ];

  $items['advancedqueue-queue-process'] = [
    'description' => dt('Runs a processing job for a queue.'),
    'arguments' => [
      'queues' => dt('Queues or queue groups to process. Either a single name, a comma-separated list or "all".'),
    ],
    'options' => [
      'timeout' => dt('The maximum execution time of the script. Be warned that this is a rough estimate as the time is only checked between two items.'),
    ],
    'aliases' => ['aqqp'],
    'examples' => [
      'drush aqqp all' => dt('Runs a processing job for all defined queues.'),
      'drush aqqp mygroup' => dt('Runs a processing job for all defined queues belonging to "mygroup" group.'),
      'drush aqqp mygroup1,mygroup2' => dt('Runs a processing job for all defined queues belonging to "mygroup1" or "mygroup2" group.'),
      'drush aqqp all --timeout=30' => dt('Runs a processing job for all defined queues and exits after 30 seconds.'),
    ],
  ];

  $items['advancedqueue-queue-delete'] = [
    'description' => dt('Deletes all items from a queue.'),
    'arguments' => [
      'queues' => dt('Queues or queue groups to delete items from. Either a single name, a comma-separated list or "all".'),
    ],
    'options' => [
      'hard' => dt('Hard-delete items (permanently remove them from the database) regardless of queue worker settings.'),
    ],
    'aliases' => ['aqqd'],
    'examples' => [
      'drush aqqd all' => dt('Deletes all items from all defined queues.'),
      'drush aqqd mygroup' => dt('Deletes all items from all defined queues belonging to "mygroup" group.'),
      'drush aqqd mygroup1,mygroup2' => dt('Deletes all items from all defined queues belonging to "mygroup1" or "mygroup2" group.'),
      'drush aqqd all --hard' => dt('Hard-deletes all items from all defined queues. The items will be permanently removed from the database, regardless of queue worker settings.'),
    ],
  ];

  $items['advancedqueue-queue-suspend'] = [
    'description' => dt('Suspends processing of a queue.'),
    'arguments' => [
      'queues' => dt('Queues or queue groups to suspend processing of. Either a single name, a comma-separated list or "all".'),
    ],
    'options' => [
      'time' => dt('The time in seconds to suspend processing for.'),
    ],
    'aliases' => ['aqqs'],
    'examples' => [
      'drush aqqs all' => dt('Suspends processing of all defined queues.'),
      'drush aqqs mygroup' => dt('Suspends processing of all defined queues belonging to "mygroup" group.'),
      'drush aqqs mygroup1,mygroup2' => dt('Suspends processing of all defined queues belonging to "mygroup1" or "mygroup2" group.'),
      'drush aqqs all --time=3600' => dt('Suspends processing of all defined queues for 1 hour.'),
    ],
  ];

  $items['advancedqueue-queue-unsuspend'] = [
    'description' => dt('Unsuspends processing of a suspended queue.'),
    'arguments' => [
      'queues' => dt('Queues or queue groups to unsuspend processing of. Either a single name, a comma-separated list or "all".'),
    ],
    'aliases' => ['aqqu'],
    'examples' => [
      'drush aqqu all' => dt('Unsuspends processing of all defined queues.'),
      'drush aqqu mygroup' => dt('Unsuspends processing of all defined queues belonging to "mygroup" group.'),
      'drush aqqu mygroup1,mygroup2' => dt('Unsuspends processing of all defined queues belonging to "mygroup1" or "mygroup2" group.'),
    ],
  ];

  $items['advancedqueue-item-list'] = [
    'description' => dt('Lists unprocessed items.'),
    'arguments' => [
      'queues' => dt('Queues or queue groups to list items for. Either a single name, a comma-separated list or "all". [default: "all"]'),
    ],
    'options' => [
      'all' => dt('List all items, both processed and unprocessed.'),
      'processed' => dt('List processed items instead of unprocessed.'),
      'pipe' => dt('Return a comma delimited list of items.'),
    ],
    'aliases' => ['aqil'],
    'examples' => [
      'drush aqil' => dt('Lists unprocessed items in all defined queues. Equivalent to "drush aqil all".'),
      'drush aqil mygroup' => dt('Lists unprocessed items in all defined queues belonging to "mygroup" group.'),
      'drush aqil mygroup1,mygroup2' => dt('Lists unprocessed items in all defined queues belonging to "mygroup1" or "mygroup2" group.'),
      'drush aqil all --all' => dt('Lists both processed and unprocessed items in all defined queues.'),
      'drush aqil --pipe' => dt('Lists unprocessed items in all defined queues in a comma-separated format.'),
    ],
  ];

  $items['advancedqueue-item-process'] = [
    'description' => dt('Runs a processing job for a queue item.'),
    'arguments' => [
      'item_id' => dt('ID of the item to process. Either a single ID, a range, or a comma-separated list.'),
    ],
    'aliases' => ['aqip'],
    'examples' => [
      'drush aqip 12' => dt('Runs a processing job for item ID 12.'),
      'drush aqip 21,25-27,31' => dt('Runs a processing job for item IDs 21, 25, 26, 27 and 31.'),
    ],
  ];

  $items['advancedqueue-item-release'] = [
    'description' => dt('Releases a claimed queue item.'),
    'arguments' => [
      'item_id' => dt('ID of the item to release. Either a single ID, a range, or a comma-separated list.'),
    ],
    'aliases' => ['aqirel'],
    'examples' => [
      'drush aqirel 12' => dt('Releases item ID 12.'),
      'drush aqirel 21,25-27,31' => dt('Releases item IDs 21, 25, 26, 27 and 31.'),
    ],
  ];

  $items['advancedqueue-item-requeue'] = [
    'description' => dt('Requeues a queue item.'),
    'arguments' => [
      'item_id' => dt('ID of the item to requeue. Either a single ID, a range, or a comma-separated list.'),
    ],
    'aliases' => ['aqireq'],
    'examples' => [
      'drush aqireq 12' => dt('Requeues item ID 12.'),
      'drush aqireq 21,25-27,31' => dt('Requeues item IDs 21, 25, 26, 27 and 31.'),
    ],
  ];

  $items['advancedqueue-item-reset'] = [
    'description' => dt('Resets the attempt counter for a queue item.'),
    'arguments' => [
      'item_id' => dt('ID of the item to reset the attempt counter for. Either a single ID, a range, or a comma-separated list.'),
    ],
    'aliases' => ['aqires'],
    'examples' => [
      'drush aqires 12' => dt('Resets attempt counter for item ID 12.'),
      'drush aqires 21,25-27,31' => dt('Resets attempt counter for item IDs 21, 25, 26, 27 and 31.'),
    ],
  ];

  $items['advancedqueue-item-delete'] = [
    'description' => dt('Deletes a queue item.'),
    'arguments' => [
      'item_id' => dt('ID of the item to delete. Either a single ID, a range, or a comma-separated list.'),
    ],
    'options' => [
      'hard' => dt('Hard delete items (permanently remove them from the database) regardless of queue worker settings.'),
    ],
    'aliases' => ['aqid'],
    'examples' => [
      'drush aqid 12' => dt('Deletes item ID 12.'),
      'drush aqid 21,25-27,31 --hard' => dt('Hard-deletes item IDs 21, 25, 26, 27 and 31. The items will be permanently removed from the database, regardless of queue worker settings.'),
    ],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function advancedqueue_drush_help($section) {
  switch ($section) {
    case 'drush:advancedqueue-queue-delete':
      return dt('Deletes all items from a queue.') . "\n\n"
        . dt('Deletion mode depends on specific queue worker settings ("delete.hard" in queue worker definition), and could be either "soft" (deleted items will be kept in the database, just marked as processed) or "hard" (items will be permanently removed from the database). This setting could be overriden by using the "--hard" option.');
      break;
    case 'drush:advancedqueue-item-release':
      return dt('Releases a claimed queue item.') . "\n\n"
        . dt('This operation will unset item\'s claim expiration time and change its status back to "queued".');
      break;
    case 'drush:advancedqueue-item-requeue':
      return dt('Releases a claimed queue item.') . "\n\n"
        . dt('This operation will unset item\'s claim expiration time and update its status to "queued" (unless item\'s current status is "retry", in which case it will be left unchanged).');
      break;
    case 'drush:advancedqueue-item-delete':
      return dt('Deletes a queue item.') . "\n\n"
        . dt('Deletion mode depends on specific queue worker settings ("delete.hard" in queue worker definition), and could be either "soft" (deleted items will be kept in the database, just marked as processed) or "hard" (items will be permanently removed from the database). This setting could be overriden by using the "--hard" option.');
      break;
  }
}

/**
 * Command callback for drush advancedqueue-queue-list.
 *
 * @param string $queues
 *   User-provided names of the queues to work with.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_queue_list($queues = NULL) {
  try {
    // If no queue name was provided, use "all" as default value.
    if (empty($queues)) {
      $queues = 'all';
    }
    $queue_workers = _drush_advancedqueue_get_queue_workers($queues);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  // Get suspended queues.
  $suspended_queues = $queue_manager->getSuspendedQueues();

  // Here we use --all option to show more columns in the table.
  $all_option = drush_get_option('all');

  if ($all_option) {
    $item_statuses = AdvancedQueueItem::getStatusOptions();
    $headers = ['Queue', 'Machine name'];
    foreach ($item_statuses as $status_code => $item_status) {
      $tag = 'status-' . $status_code;
      $headers[] = "<$tag>" . $item_status . "</$tag>";
    }
    $headers[] = 'Class';
  }
  else {
    $headers = ['Queue', 'Items', 'Class'];
  }

  $rows = $groups_rows = [];
  /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
  foreach ($queue_workers as $queue_name => $queue_worker) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($queue_name);

    $queue_suspended = in_array($queue_name, array_keys($suspended_queues)) ? ' <status-2>suspended</status-2>' : '';

    if ($all_option) {
      $row = [
        $queue_manager->getTitle($queue_name) . $queue_suspended,
        $queue_name,
      ];
      foreach (array_keys($item_statuses) as $status_code) {
        $tag = 'status-' . $status_code;
        $item_count = $queue->numberOfItems($status_code);
        $row[] = $item_count ? "<$tag>$item_count</$tag>" : '-';
      }
      $row[] = get_class($queue_worker);

      $groups_rows[$queue_manager->getGroup($queue_name)][] = $row;
    }
    else {
      $item_count = $queue->numberOfItems();
      $rows[] = [
        $queue_name . $queue_suspended,
        $item_count ? $item_count : '-',
        get_class($queue_worker),
      ];
    }
  }

  if ($all_option) {
    ksort($groups_rows);

    foreach ($groups_rows as $group_name => $group_rows) {
      $rows[] = ['<group-name>' . $group_name . '</group-name>'];
      foreach ($group_rows as $group_row) {
        $group_row[0] = '  ' . $group_row[0];
        $rows[] = $group_row;
      }
    }
  }

  if (drush_get_option('pipe')) {
    $pipe = [];
    foreach ($rows as $row) {
      // Skip rows with just group name.
      if (count($row) > 1) {
        $pipe[] = trim(strip_tags(implode(',', $row)));
      }
    }
    drush_print_pipe($pipe);
  }
  else {
    // Use Symfony console output to have nice tables, colors etc.
    $output = new ConsoleOutput();
    AdvancedQueueStyle::addStatusStyles($output);
    $table = new Table($output);
    $table
      ->setHeaders($headers)
      ->setRows($rows);
    $table->render();
  }
}

/**
 * Command callback for drush advancedqueue-queue-process.
 *
 * @param string $queues
 *   User-provided names of the queues to work with.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_queue_process($queues = NULL) {
  try {
    $queue_workers = _drush_advancedqueue_get_queue_workers($queues);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  // Delete older entries and make sure there are no stale items in the table.
  drush_log(dt('Cleanup processed and stale items.'));
  foreach (array_keys($queue_workers) as $queue_name) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($queue_name);
    $queue->garbageCollection();
  }

  // Run the worker for a certain period of time before killing it.
  $config = \Drupal::config('advancedqueue.settings');
  $timeout = drush_get_option('timeout') ?: $config->get('processing_timeout.drush');
  $end = $timeout ? time() + $timeout : 0;

  drush_log(dt('Starting processing loop.'));

  $conditions = ['name' => array_keys($queue_workers)];
  while (!$end || time() < $end) {
    // Do not process items queue-by-queue like for example cron does,
    // instead try to do proper FIFO, and claim them in order of their
    // "created" date, regardless of the queue they belong to.
    if ($item = AdvancedQueue::claimFirstItem($conditions)) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
      $queue = $queue_manager->getQueue($item->name);
      $queue->processItem($item, $end);
    }
    // No item processed in this round, let the CPU rest.
    else {
      sleep(1);
    }
  }

  drush_log(dt('Timeout: exiting processing loop.'));
}

/**
 * Command callback for drush advancedqueue-queue-delete.
 *
 * @param string $queues
 *   User-provided names of the queues to work with.
 *
 * @return bool|null
 *   False if no queues to work with were found.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_queue_delete($queues = NULL) {
  try {
    $queue_workers = _drush_advancedqueue_get_queue_workers($queues);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  // In case of permanent deleting we ask for confirmation first.
  if (drush_get_option('hard')) {
    if (!drush_confirm(t('Are you sure you want to permanently delete all items from the following queues: @queue_names?', ['@queue_names' => implode(', ', array_keys($queue_workers))]))) {
      return;
    }
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  foreach (array_keys($queue_workers) as $queue_name) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($queue_name);

    $hard = $queue_manager->getDeleteHard($queue_name) || drush_get_option('hard');
    $count = $queue->deleteQueue($hard);

    devel_set_message(t('@type-deleted @count items from queue "@queue_name".', [
      '@type' => $hard ? 'Hard' : 'Soft',
      '@count' => $count,
      '@queue_name' => $queue_name,
    ]), 'ok');
  }
}

/**
 * Command callback for drush advancedqueue-queue-suspend.
 *
 * @param string $queues
 *   User-provided names of the queues to work with.
 *
 * @return bool|null
 *   False if no queues to work with were found.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_queue_suspend($queues = NULL) {
  try {
    $queue_workers = _drush_advancedqueue_get_queue_workers($queues);
  } catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  $suspend_time = drush_get_option('time');

  foreach (array_keys($queue_workers) as $queue_name) {
    $suspend_time = $suspend_time ? $suspend_time : $queue_manager->getSuspendTime($queue_name);
    $queue_manager->suspendQueue($queue_name, $suspend_time);

    devel_set_message(t('Suspended processing of "@queue_name" queue for @time.', [
      '@queue_name' => $queue_name,
      '@time' => \Drupal::service('date.formatter')->formatInterval($suspend_time),
    ]), 'ok');
  }
}

/**
 * Command callback for drush advancedqueue-queue-unsuspend.
 *
 * @param string $queues
 *   User-provided names of the queues to work with.
 *
 * @return bool|null
 *   False if no queues to work with were found.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_queue_unsuspend($queues = NULL) {
  try {
    $queue_workers = _drush_advancedqueue_get_queue_workers($queues);
  } catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  foreach (array_keys($queue_workers) as $queue_name) {
    $queue_manager->unsuspendQueue($queue_name);

    devel_set_message(t('Unsuspended processing of "@queue_name" queue.', ['@queue_name' => $queue_name]), 'ok');
  }
}

/**
 * Command callback for drush advancedqueue-item-list.
 *
 * @param string $queues
 *   User-provided names of the queues to work with.
 *
 * @return array|bool
 *   The result for backend invoke.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_item_list($queues = NULL) {
  try {
    // If no queue name was provided, use "all" as default value.
    if (empty($queues)) {
      $queues = 'all';
    }
    $queue_workers = _drush_advancedqueue_get_queue_workers($queues);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  $pending_statuses = [
    AdvancedQueueItem::STATUS_QUEUED,
    AdvancedQueueItem::STATUS_PROCESSING,
    AdvancedQueueItem::STATUS_FAILURE_RETRY,
  ];
  $status_op = drush_get_option('processed') ? 'NOT IN' : 'IN';

  $status_options = AdvancedQueueItem::getStatusOptions();

  $headers = ['Queue', 'Item ID', 'Title', 'Status', 'Attempts'];
  // When displaying processed or all items, we show more columns.
  if (drush_get_option('processed') || drush_get_option('all')) {
    $headers[] = 'Processed';
    $headers[] = 'Result';
  }

  $rows = [];
  foreach (array_keys($queue_workers) as $queue_name) {
    $query = Database::getConnection()->select(AdvancedQueue::TABLE_NAME, 'aq')
      ->fields('aq', ['name', 'item_id', 'title', 'status', 'result', 'processed'])
      ->condition('aq.name', $queue_name);
    if (!drush_get_option('all')) {
      $query->condition('aq.status', $pending_statuses, $status_op);
    }
    $result = $query->orderBy('item_id', 'ASC')
      ->execute()
      ->fetchAllAssoc('item_id', \PDO::FETCH_ASSOC);

    $rows += $result;
  }
  ksort($rows);

  foreach ($rows as &$row) {
    // Replace status code with colored human-friendly status name.
    if (!drush_get_option('pipe')) {
      $tag = 'status-' . $row['status'];
      $row['status'] = "<$tag>" . $status_options[$row['status']] . "</$tag>";
    }
    else {
      $row['status'] = $row['status'];
    }

    // Attempt counter.
    $row_result = unserialize($row['result']);
    $row['result'] = !empty($row_result['attempt']) ? $row_result['attempt'] : '';

    // When displaying processed or all items, show also processed date
    // and message returned by the worker.
    if (drush_get_option('processed') || drush_get_option('all')) {
      $row['processed'] = !empty($row['processed']) ? date('Y-m-d H:i:s', $row['processed']) : '';
      if (!empty($row_result['return'])) {
        $row['return'] = (string) $row_result['return'];
      }
    }
    // When showing not processed items, unset the processed date.
    else {
      unset($row['processed']);
    }
  }

  if (drush_get_option('pipe')) {
    $pipe = [];
    foreach ($rows as $row) {
      $pipe[] = implode(',', $row);
    }
    drush_print_pipe($pipe);
  }
  else {
    // Use Symfony console output to have nice tables, colors etc.
    $output = new ConsoleOutput();
    AdvancedQueueStyle::addStatusStyles($output);
    $table = new Table($output);
    $table
      ->setHeaders($headers)
      ->setRows($rows);
    $table->render();

  }
}

/**
 * Command callback for drush advancedqueue-item-process.
 *
 * @param string $item_ids
 *   User-provided IDs of the items to work with.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_item_process($item_ids) {
  try {
    $properties = [
      'status' => [AdvancedQueueItem::STATUS_QUEUED, AdvancedQueueItem::STATUS_FAILURE_RETRY],
    ];
    $items = _drush_advancedqueue_get_queue_items($item_ids, $properties);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  $config = \Drupal::config('advancedqueue.settings');
  $timeout = $config->get('processing_timeout.drush');
  $end = $timeout ? time() + $timeout : 0;

  foreach ($items as $item) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($item->name);

    if ($item = $queue->claimItem(NULL, $item->item_id)) {
      $queue->processItem($item, $end);

      drush_log(dt('Processed item @item_id in queue "@queue_name".', [
        '@item_id' => $item->item_id,
        '@queue_name' => $item->name,
      ]), 'ok');
    }
  }
}

/**
 * Command callback for drush advancedqueue-item-release.
 *
 * @param string $item_ids
 *   User-provided IDs of the items to work with.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_item_release($item_ids) {
  try {
    $items = _drush_advancedqueue_get_queue_items($item_ids);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  foreach ($items as $item) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($item->name);
    $queue->releaseItem($item);

    drush_log(dt('Released item @item_id in queue "@queue_name".', [
      '@item_id' => $item->item_id,
      '@queue_name' => $item->name,
    ]), 'ok');
  }
}

/**
 * Command callback for drush advancedqueue-item-requeue.
 *
 * @param string $item_ids
 *   User-provided IDs of the items to work with.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_item_requeue($item_ids) {
  try {
    $items = _drush_advancedqueue_get_queue_items($item_ids);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  foreach ($items as $item) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($item->name);
    $queue->requeueItem($item);

    drush_log(dt('Requeued item @item_id in queue "@queue_name".', [
      '@item_id' => $item->item_id,
      '@queue_name' => $item->name,
    ]), 'ok');
  }
}

/**
 * Command callback for drush advancedqueue-item-reset.
 *
 * @param string $item_ids
 *   User-provided IDs of the items to work with.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_item_reset($item_ids) {
  try {
    $items = _drush_advancedqueue_get_queue_items($item_ids);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  foreach ($items as $item) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($item->name);
    $queue->resetAttemptCounter($item);

    drush_log(dt('Reset item counter for @item_id in queue "@queue_name".', [
      '@item_id' => $item->item_id,
      '@queue_name' => $item->name,
    ]), 'ok');
  }
}

/**
 * Command callback for drush advancedqueue-item-delete.
 *
 * @param string $item_ids
 *   User-provided IDs of the items to work with.
 *
 * @see advancedqueue_drush_command()
 */
function drush_advancedqueue_item_delete($item_ids) {
  try {
    $items = _drush_advancedqueue_get_queue_items($item_ids);
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }

  // In case of permanent deleting we ask for confirmation first.
  if (drush_get_option('hard')) {
    if (!drush_confirm(t('Are you sure you want to permanently delete the following items: @item_ids?', ['@item_ids' => implode(', ', array_keys($items))]))) {
      return;
    }
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  foreach ($items as $item) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
    $queue = $queue_manager->getQueue($item->name);
    $hard = $queue_manager->getDeleteHard($item->name) || drush_get_option('hard');
    $queue->deleteItem($item, $hard, FALSE);

    drush_log(dt('@type-deleted item @item_id from queue "@queue_name".', [
      '@type' => $hard ? 'Hard' : 'Soft',
      '@item_id' => $item->item_id,
      '@queue_name' => $item->name,
    ]), 'ok');
  }
}

/**
 * Returns an array of validated queue workers for provided names.
 *
 * @param array|null $queues
 *   An array of queue names to work with provided as drush command argument.
 *
 * @return array
 *   An array of validated queue workers for provided names.
 *
 * @throws \Exception
 *   An exception thrown when there was a problem loading queue workers
 *   from provided names.
 */
function _drush_advancedqueue_get_queue_workers($queues = NULL) {
  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');
  $queue_definitions = $queue_manager->getDefinitions();

  if (!$queue_definitions) {
    throw new \Exception(dt('No queues exist. Aborting.'));
  }

  if (empty($queues)) {
    throw new \Exception(dt('You have to provide either a valid queue name or group name or "all" value. Aborting.'));
  }
  $queues = explode(',', $queues);

  $queues_to_work_with = [];
  foreach ($queues as $queue) {
    $queues_to_work_with += _drush_advancedqueue_get_queues_for_name($queue);
  }

  // Validate that we have at least one queue to work with.
  if (empty($queues_to_work_with)) {
    throw new \Exception(dt('We could not find any valid queues matching your argument.'));
  }

  $queue_workers = [];
  foreach (array_keys($queues_to_work_with) as $queue_name) {
    $queue_workers[$queue_name] = $queue_manager->createInstance($queue_name);
  }

  return $queue_workers;
}

/**
 * Returns an array of queue names matching the specified string.
 *
 * @param string $name
 *   A user-provided string to match against existing queue or group names.
 *
 * @return array|null
 *   An array of valid queue names.
 *
 * @throws \Exception
 *   An exception throws when the specified name is not "all" and does not
 *   match any existing queue or group name.
 */
function _drush_advancedqueue_get_queues_for_name($name) {
  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');
  $queue_definitions = $queue_manager->getDefinitions();
  $queue_group_definitions = $queue_manager->getGroupDefinitions();

  if ($name == 'all') {
    return $queue_definitions;
  }
  elseif (in_array($name, array_keys($queue_group_definitions), TRUE)) {
    return $queue_group_definitions[$name];
  }
  elseif (in_array($name, array_keys($queue_definitions), TRUE)) {
    return [$name => $queue_definitions[$name]];
  }
  else {
    throw new \Exception(dt('The name "@name" is invalid. It should be either a valid queue name or group name or "all" value. Aborting.', ['@name' => $name]));
  }
}

/**
 * Returns an array of valid queue item entities from provided item IDs.
 *
 * @param string $item_ids
 *   A string specifying item IDs to return queue item entities for.
 * @param array $conditions
 *   An array of additional conditions to use for loading queue items.
 *
 * @return \Drupal\Core\Entity\EntityInterface[]
 *   An array of valid queue items loaded from provided item IDs.
 *
 * @throws \Exception
 *   An exception thrown if not all requested items could be loaded.
 */
function _drush_advancedqueue_get_queue_items($item_ids = '', $conditions = []) {
  if (!empty($item_ids)) {
    // First try to split received string on comma boundary.
    if (strpos($item_ids, ',') !== FALSE) {
      $item_ids = explode(',', $item_ids);
    }
    else {
      $item_ids = [$item_ids];
    }
    // Then check if any of the obtained elements is a range.
    $range_item_ids = [];
    foreach ($item_ids as $key => $item_id) {
      if (strpos($item_id, '-') !== FALSE) {
        list($start, $end) = explode('-', $item_id);
        $range_item_ids = array_merge($range_item_ids, range($start, $end));
        unset($item_ids[$key]);
      }
    }
    $item_ids = array_merge($item_ids, $range_item_ids);
    $conditions += ['item_id' => $item_ids];
  }

  /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager */
  $queue_manager = \Drupal::service('plugin.manager.queue_worker');

  $query = \Drupal::database()->select('advancedqueue', 'aq')
    ->fields('aq');
  foreach ($conditions as $name => $value) {
    if (!is_array($value)) {
      $value = [$value];
    }
    $query->condition('aq.' . $name, $value, 'IN');
  }
  $items = $query->orderBy('item_id', 'ASC')
    ->execute()
    ->fetchAllAssoc('item_id');

  if (!empty($item_ids) && ($failed_item_ids = array_diff($item_ids, array_keys($items)))) {
    throw new \Exception(dt('Failed to load following items for selected operation: @item_ids. Aborting.', ['@item_ids' => implode(', ', $failed_item_ids)]));
  }

  foreach ($items as &$item) {
    $item->data = !empty($item->data) ? unserialize($item->data) : [];
    $item->result = !empty($item->result) ? unserialize($item->result) : [];
  }

  return $items;
}
