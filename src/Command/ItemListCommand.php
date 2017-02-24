<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\advancedqueue\Queue\AdvancedQueue;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Core\Database\Database;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Prints a list of items in a queue.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class ItemListCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:item:list')
      ->setAliases(['aqil'])
      ->setDescription($this->trans('commands.advancedqueue.item.list.description'))
      ->setHelp($this->trans('commands.advancedqueue.item.list.help'))
      ->addArgument('queues', InputArgument::OPTIONAL, $this->trans('commands.advancedqueue.item.list.arguments.queues'), 'all')
      ->addOption('all', NULL, InputOption::VALUE_NONE, $this->trans('commands.advancedqueue.item.list.options.all'), NULL)
      ->addOption('processed', NULL, InputOption::VALUE_NONE, $this->trans('commands.advancedqueue.item.list.options.processed'), NULL)
      ->addOption('pipe', NULL, InputOption::VALUE_NONE, $this->trans('commands.advancedqueue.item.list.options.pipe'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $queue_workers = $this->getQueueWorkers($input);

    $pending_statuses = [
      AdvancedQueueItem::STATUS_QUEUED,
      AdvancedQueueItem::STATUS_PROCESSING,
      AdvancedQueueItem::STATUS_FAILURE_RETRY,
    ];
    $status_op = $input->getOption('processed') ? 'NOT IN' : 'IN';

    $status_options = AdvancedQueueItem::getStatusOptions();

    $headers = [
      $this->trans('commands.advancedqueue.item.list.header.queue'),
      $this->trans('commands.advancedqueue.item.list.header.item_id'),
      $this->trans('commands.advancedqueue.item.list.header.title'),
      $this->trans('commands.advancedqueue.item.list.header.status'),
      $this->trans('commands.advancedqueue.item.list.header.attempts'),
    ];
    // When displaying processed or all items, we show more columns.
    if ($input->getOption('processed') || $input->getOption('all')) {
      $headers[] = $this->trans('commands.advancedqueue.item.list.header.processed');
      $headers[] = $this->trans('commands.advancedqueue.item.list.header.result');
    }

    $rows = [];
    foreach (array_keys($queue_workers) as $queue_name) {
      $query = Database::getConnection()
        ->select(AdvancedQueue::TABLE_NAME, 'aq')
        ->fields('aq', ['name', 'item_id', 'title', 'status', 'result', 'processed'])
        ->condition('aq.name', $queue_name);
      if (!$input->getOption('all')) {
        $query->condition('aq.status', $pending_statuses, $status_op);
      }
      $result = $query->orderBy('item_id', 'ASC')
        ->execute()
        ->fetchAllAssoc('item_id', \PDO::FETCH_ASSOC);

      $rows += $result;
    }
    ksort($rows);

    foreach ($rows as &$row) {
      // Replace status code with human-friendly status name.
      if (!$input->getOption('pipe')) {
        $tag = 'status-' . $row['status'];
        $row['status'] = "<$tag>" . $status_options[$row['status']] . "</$tag>";
      }
      else {
        $row['status'] = $status_options[$row['status']];
      }

      // Attempt counter.
      $row_result = unserialize($row['result']);
      $row['result'] = !empty($row_result['attempt']) ? $row_result['attempt'] : '';

      // When displaying processed or all items, show also processed date
      // and message returned by the worker.
      if ($input->getOption('processed') || $input->getOption('all')) {
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

    if ($input->getOption('pipe')) {
      foreach ($rows as $row) {
        $output->writeln(trim(implode(',', $row)));
      }
    }
    else {
      AdvancedQueueStyle::addStatusStyles($output);
      $table = new Table($output);
      $table
        ->setHeaders($headers)
        ->setRows($rows);
      $table->render();
    }
  }
}
