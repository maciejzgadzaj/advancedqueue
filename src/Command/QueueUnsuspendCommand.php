<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Unsuspends processing of a suspended queue.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class QueueUnsuspendCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:queue:unsuspend')
      ->setAliases(['aqqu'])
      ->setDescription($this->trans('commands.advancedqueue.queue.unsuspend.description'))
      ->setHelp($this->trans('commands.advancedqueue.queue.unsuspend.help'))
      ->addArgument('queues', InputArgument::REQUIRED, $this->trans('commands.advancedqueue.queue.unsuspend.arguments.queues'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new AdvancedQueueStyle($input, $output);

    $queue_workers = $this->getQueueWorkers($input);

    foreach (array_keys($queue_workers) as $queue_name) {
      $this->queueManager->unsuspendQueue($queue_name);

      $io->successLite(sprintf($this->trans('commands.advancedqueue.queue.unsuspend.messages.queue_unsuspended'), $queue_name));
    }
  }

}
