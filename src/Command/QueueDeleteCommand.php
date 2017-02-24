<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes all items from a queue.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class QueueDeleteCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:queue:delete')
      ->setAliases(['aqqd'])
      ->setDescription($this->trans('commands.advancedqueue.queue.delete.description'))
      ->setHelp($this->trans('commands.advancedqueue.queue.delete.help'))
      ->addArgument('queues', InputArgument::REQUIRED, $this->trans('commands.advancedqueue.queue.delete.arguments.queues'), NULL)
      ->addOption('hard', NULL, InputOption::VALUE_NONE, $this->trans('commands.advancedqueue.queue.delete.options.hard'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new AdvancedQueueStyle($input, $output);

    $queue_workers = $this->getQueueWorkers($input);

    // In case of permanent deleting we ask for confirmation first.
    if ($input->getOption('hard')) {
      if (!$io->confirm(sprintf($this->trans('commands.advancedqueue.queue.delete.messages.confirm'), implode(', ', array_keys($this->queueWorkers))), FALSE)) {
        $io->comment($this->trans('commands.advancedqueue.messages.user_aborted'));
        return 1;
      }
    }

    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerInterface $queue_worker */
    foreach ($queue_workers as $queue_name => $queue_worker) {
      $hard = $queue_worker->getDeleteHard() || $input->getOption('hard');
      $count = $queue_worker->getQueue()->deleteQueue($hard);

      $io->successLite(sprintf($this->trans('commands.advancedqueue.queue.delete.messages.items_deleted'), $hard ? 'Hard' : 'Soft', $count, $queue_name));
    }
  }

}
