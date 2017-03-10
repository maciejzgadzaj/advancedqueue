<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\advancedqueue\Queue\AdvancedQueue;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessCommand.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class QueueProcessCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:queue:process')
      ->setAliases(['aqqp'])
      ->setDescription($this->trans('commands.advancedqueue.queue.process.description'))
      ->setHelp($this->trans('commands.advancedqueue.queue.process.help'))
      ->addArgument('queues', InputArgument::REQUIRED, $this->trans('commands.advancedqueue.queue.process.arguments.queues'), NULL)
      ->addOption('timeout', NULL, InputOption::VALUE_NONE, $this->trans('commands.advancedqueue.queue.process.options.timeout'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new AdvancedQueueStyle($input, $output);

    $queue_workers = $this->getQueueWorkers($input);

    // Delete older entries and make sure there are no stale items in the table.
    $output->writeln($this->trans('commands.advancedqueue.queue.process.messages.cleanup'), OutputInterface::VERBOSITY_VERBOSE);
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerInterface $queue_worker */
    foreach ($queue_workers as $queue_name => $queue_worker) {
      $queue_worker->getQueue()->garbageCollection();
    }

    // Run the worker for a certain period of time before killing it.
    $config = \Drupal::config('advancedqueue.settings');
    $timeout = $input->getOption('timeout') ?: $config->get('processing_timeout.drush');
    $end = $timeout ? time() + $timeout : 0;

    $output->writeln($this->trans('commands.advancedqueue.queue.process.messages.processing_loop_start'), OutputInterface::VERBOSITY_VERBOSE);

    while (!$end || time() < $end) {
      // Do not process items queue-by-queue like for example cron does,
      // instead try to do proper FIFO, and claim them in order of their
      // "created" date, regardless of the queue they belong to.
      if ($item = AdvancedQueue::claimItemFIFO()) {
        $queue_name = $item->name;
        $output->writeln(sprintf($this->trans('commands.advancedqueue.queue.process.messages.processing_item_start'), $queue_name, $item->item_id, $item->title), OutputInterface::VERBOSITY_VERBOSE);

        /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
        $queue = $queue_workers[$queue_name]->getQueue();
        $queue->processItem($item, $queue_workers[$queue_name], $end);

        $callback = $item->status == AdvancedQueueItem::STATUS_SUCCESS ? 'successLite' : ($item->status == AdvancedQueueItem::STATUS_FAILURE_RETRY ? 'warningLite' : 'errorLite');
        $io->$callback(sprintf($this->trans('commands.advancedqueue.queue.process.messages.processing_item_end'), $queue_name, $item->item_id, $item->status, AdvancedQueueItem::getStatusLabel($item->status)));
      }
    }

    $output->writeln($this->trans('commands.advancedqueue.queue.process.messages.processing_loop_end'), OutputInterface::VERBOSITY_VERBOSE);
  }

}
