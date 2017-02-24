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
    $queue_workers = $this->getQueueWorkers($input);

    // Get suspended queues.
    $suspended_queues = $this->queueManager->getSuspendedQueues();

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
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    foreach ($queue_workers as $queue_name => $queue_worker) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
      $queue = $this->queueManager->getQueue($queue_name);

      $queue_suspended = in_array($queue_name, array_keys($suspended_queues)) ? ' <status-2>suspended</status-2>' : '';

      if ($input->getOption('all')) {
        $row = [
          $this->queueManager->getTitle($queue_name) . $queue_suspended,
          $queue_name,
        ];
        foreach (array_keys($item_statuses) as $status_code) {
          $tag = 'status-' . $status_code;
          $item_count = $queue->numberOfItems($status_code);
          $row[] = $item_count ? "<$tag>$item_count</$tag>" : '-';
        }
        $row[] = get_class($queue_worker);

        $groups_rows[$this->queueManager->getGroup($queue_name)][] = $row;
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

    if ($input->getOption('all')) {
      ksort($groups_rows);

      foreach ($groups_rows as $group_name => $group_rows) {
        $rows[] = ['<group-name>' . $group_name . '</group-name>'];
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
          $output->writeln(trim(strip_tags(implode(',', $row))));
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
