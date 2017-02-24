<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;

/**
 * Prints a list of all defined queues and summary of queue items.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class QueueListCommand extends CommandBase {

  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:queue:list')
      ->setAliases(['aqql'])
      ->setDescription($this->trans('commands.advancedqueue.queue.list.description'))
      ->setHelp($this->trans('commands.advancedqueue.queue.list.help'))
      ->addArgument('queues', InputArgument::OPTIONAL, $this->trans('commands.advancedqueue.queue.list.arguments.queues'), 'all')
      ->addOption('all', NULL, InputOption::VALUE_NONE, $this->trans('commands.advancedqueue.queue.list.options.all'), NULL)
      ->addOption('pipe', NULL, InputOption::VALUE_NONE, $this->trans('commands.advancedqueue.queue.list.options.pipe'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new AdvancedQueueStyle($input, $output);

    $queue_workers = $this->getQueueWorkers($input);

    if ($input->getOption('all')) {
      $item_statuses = AdvancedQueueItem::getStatusOptions();
      $headers = [
        $this->trans('commands.advancedqueue.queue.list.header.queue'),
        $this->trans('commands.advancedqueue.queue.list.header.machine_name'),
      ];
      foreach ($item_statuses as $status_code => $status_label) {
        $tag = 'status-' . $status_code;
        $headers[] = "<$tag>" . $this->trans('commands.advancedqueue.queue.list.header.status.' . $status_code) . "</$tag>";
      }
      $headers[] = $this->trans('commands.advancedqueue.queue.list.header.class');
    }
    else {
      $headers  = [
        $this->trans('commands.advancedqueue.queue.list.header.queue'),
        $this->trans('commands.advancedqueue.queue.list.header.items'),
        $this->trans('commands.advancedqueue.queue.list.header.class'),
      ];
    }

    $rows = $groups_rows = [];
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerInterface $queue_worker */
    foreach ($queue_workers as $queue_name => $queue_worker) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
      $queue = $queue_worker->getQueue();

      if ($input->getOption('all')) {
        $row = [
          $queue_worker->getTitle(),
          $queue_name,
        ];
        foreach (array_keys($item_statuses) as $status_code) {
          if ($input->getOption('pipe')) {
            $tag = 'status-' . $status_code;
            $item_count = $queue->numberOfItems($status_code);
            $row[] = $item_count ? "<$tag>$item_count</$tag>" : '-';
          }
          else {
            $row[] = $queue->numberOfItems($status_code);
          }
        }
        $row[] = get_class($queue_worker);

        $groups_rows[$queue_worker->getGroup()][] = $row;
      }
      else {
        $item_count = $queue->numberOfItems();
        $rows[] = [
          $queue_name,
          $item_count ? $item_count : '-',
          get_class($queue_worker),
        ];
      }
    }

    if ($input->getOption('all')) {
      ksort($groups_rows);

      foreach ($groups_rows as $group_name => $group_rows) {
        $rows[] = ['<info>' . $group_name . '</info>'];
        foreach ($group_rows as $group_row) {
          $group_row[0] = '  ' . $group_row[0];
          $rows[] = $group_row;
        }
      }
    }

    if ($input->getOption('pipe')) {
      foreach ($rows as $row) {
        // Skip rows with just group name.
        if (count($row) > 1) {
          $output->writeln(trim(implode(',', $row)));
        }
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
