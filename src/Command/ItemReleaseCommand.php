<?php

namespace Drupal\advancedqueue\Command;

use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReleaseItemCommand.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class ItemReleaseCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:item:release')
      ->setAliases(['aqirel'])
      ->setDescription($this->trans('commands.advancedqueue.item.release.description'))
      ->addArgument('item_id', InputArgument::REQUIRED, $this->trans('commands.advancedqueue.item.release.arguments.item_id'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $items = $this->loadItems($input->getArgument('item_id'));

    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');

    foreach ($items as $item) {
      $queue_factory->get($item->name)->releaseItem($item);

      $io->successLite(sprintf($this->trans('commands.advancedqueue.item.release.messages.item_released'), $item->item_id, $item->name));
    }
  }

}
