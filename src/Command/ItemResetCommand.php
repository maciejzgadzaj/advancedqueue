<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ItemResetCommand.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class ItemResetCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:item:reset')
      ->setAliases(['aqires'])
      ->setDescription($this->trans('commands.advancedqueue.item.reset.description'))
      ->setHelp($this->trans('commands.advancedqueue.item.reset.help'))
      ->addArgument('item_id', InputArgument::REQUIRED, $this->trans('commands.advancedqueue.item.reset.arguments.item_id'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new AdvancedQueueStyle($input, $output);

    $items = $this->loadItems($input->getArgument('item_id'));

    foreach ($items as $item) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
      $queue = $this->queueManager->getQueue($item->name);
      $queue->resetAttemptCounter($item);

      $io->successLite(sprintf($this->trans('commands.advancedqueue.item.reset.messages.counter_reset'), $item->item_id, $item->name));
    }
  }

}
