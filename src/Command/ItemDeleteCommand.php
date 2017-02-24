<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes a queue item.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class ItemDeleteCommand extends CommandBase {

  /**
   * An array of items to delete.
   *
   * @var array $items
   */
  private $items;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:item:delete')
      ->setAliases(['aqid'])
      ->setDescription($this->trans('commands.advancedqueue.item.delete.description'))
      ->setHelp($this->trans('commands.advancedqueue.item.delete.help'))
      ->addArgument('item_id', InputArgument::REQUIRED, $this->trans('commands.advancedqueue.item.delete.arguments.item_id'), NULL)
      ->addOption('hard', NULL, InputOption::VALUE_NONE, $this->trans('commands.advancedqueue.item.delete.options.hard'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new AdvancedQueueStyle($input, $output);

    $items = $this->loadItems($input->getArgument('item_id'));

    // In case of permanent deleting we ask for confirmation first.
    if ($input->getOption('hard')) {
      if (!$io->confirm(sprintf($this->trans('commands.advancedqueue.item.delete.messages.confirm'), implode(', ', array_keys($items))), FALSE)) {
        $io->comment($this->trans('commands.advancedqueue.messages.user_aborted'));
        return 1;
      }
    }

    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
    $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');

    foreach ($items as $item) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerInterface $queue_worker */
      $queue_worker = $queue_worker_manager->createInstance($item->name);

      $hard = $queue_worker->getDeleteHard() || $input->getOption('hard');
      $queue_worker->getQueue()->deleteItem($item, $hard, FALSE);

      $io->successLite(sprintf($this->trans('commands.advancedqueue.item.delete.messages.item_deleted'), $hard ? 'Hard' : 'Soft', $item->item_id, $item->name));
    }
  }

}
