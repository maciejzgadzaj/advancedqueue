<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Suspends processing of a queue.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class QueueSuspendCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:queue:suspend')
      ->setAliases(['aqqs'])
      ->setDescription($this->trans('commands.advancedqueue.queue.suspend.description'))
      ->setHelp($this->trans('commands.advancedqueue.queue.suspend.help'))
      ->addArgument('queues', InputArgument::REQUIRED, $this->trans('commands.advancedqueue.queue.suspend.arguments.queues'), NULL)
      ->addOption('time', NULL, InputOption::VALUE_REQUIRED, $this->trans('commands.advancedqueue.queue.suspend.options.time'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new AdvancedQueueStyle($input, $output);

    $queue_workers = $this->getQueueWorkers($input);

    $suspend_time = $input->getOption('time');

    foreach (array_keys($queue_workers) as $queue_name) {
      $suspend_time = $suspend_time ? $suspend_time : $this->queueManager->getSuspendTime($queue_name);
      $this->queueManager->suspendQueue($queue_name, $suspend_time);

      $io->successLite(sprintf($this->trans('commands.advancedqueue.queue.suspend.messages.queue_suspended'), $queue_name, $suspend_time));
    }
  }

}
