<?php

namespace Drupal\advancedqueue\Command;

use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class CommandBase.
 *
 * @package Drupal\advancedqueue
 */
class CommandBase extends Command {

  use ContainerAwareCommandTrait;

  /**
   * The queue worker plugin manager.
   *
   * @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager
   */
  protected $queueManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($name = null) {
    parent::__construct($name);
    $this->queueManager = \Drupal::service('plugin.manager.queue_worker');
  }

  /**
   * Returns an array of validated queue workers for provided names.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   An InputInterface instance.
   *
   * @return array
   *   An array of validated queue workers for provided names.
   *
   * @throws \Exception
   *   An exception thrown when there was a problem loading queue workers
   *   from provided names.
   */
  protected function getQueueWorkers(InputInterface $input) {
    $queue_definitions = $this->queueManager->getDefinitions();

    if (!$queue_definitions) {
      throw new \Exception($this->trans('commands.advancedqueue.messages.errors.no_queues_exist'));
    }

    if (!$queues = $input->getArgument('queues')) {
      throw new \Exception($this->trans('commands.advancedqueue.messages.errors.empty_names_argument'));
    }
    $queues = explode(',', $queues);

    $queues_to_work_with = [];
    foreach ($queues as $queue) {
      $queues_to_work_with += $this->getQueuesForName($queue);
    }

    // Validate that we have at least one queue to work with.
    if (empty($queues_to_work_with)) {
      throw new \Exception($this->trans('commands.advancedqueue.messages.errors.no_queues_to_work_with'));
    }

    $queue_workers = [];
    foreach (array_keys($queues_to_work_with) as $queue_name) {
      $queue_workers[$queue_name] = $this->queueManager->createInstance($queue_name);
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
  private function getQueuesForName($name) {
    $queue_definitions = $this->queueManager->getDefinitions();
    $queue_group_definitions = $this->queueManager->getGroupDefinitions();

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
      throw new \Exception(sprintf($this->trans('commands.advancedqueue.messages.errors.invalid_name'), $name));
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
  protected function loadItems($item_ids = '', $conditions = []) {
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
      throw new \Exception(sprintf($this->trans('commands.advancedqueue.messages.errors.failed_loading_items'), implode(', ', $failed_item_ids)));
    }

    foreach ($items as &$item) {
      $item->data = !empty($item->data) ? unserialize($item->data) : [];
      $item->result = !empty($item->result) ? unserialize($item->result) : [];
    }

    return $items;
  }

  public static function setCustomStyles($output) {
    $formatter = $output->getFormatter();
    $formatter->setStyle('status--1', new OutputFormatterStyle('white'));
    $formatter->setStyle('status-0', new OutputFormatterStyle('yellow', NULL, ['bold']));
    $formatter->setStyle('status-1', new OutputFormatterStyle('green', NULL, ['bold']));
    $formatter->setStyle('status-2', new OutputFormatterStyle('white', 'red', ['bold']));
    $formatter->setStyle('status-3', new OutputFormatterStyle('red', NULL, ['bold']));
  }

}
