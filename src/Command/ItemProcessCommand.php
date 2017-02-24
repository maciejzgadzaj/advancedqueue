<?php

namespace Drupal\advancedqueue\Command;

use Drupal\advancedqueue\Command\AdvancedQueueStyle;
use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs a processing job for a queue item.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand(
 *   extension = "advancedqueue",
 *   extensionType = "module",
 * )
 */
class ItemProcessCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:item:process')
      ->setAliases(['aqip'])
      ->setDescription($this->trans('commands.advancedqueue.item.process.description'))
      ->setHelp($this->trans('commands.advancedqueue.item.process.help'))
      ->addArgument('item_id', InputArgument::REQUIRED, $this->trans('commands.advancedqueue.item.process.arguments.item_id'), NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new AdvancedQueueStyle($input, $output);

    $properties = [
      'status' => [AdvancedQueueItem::STATUS_QUEUED, AdvancedQueueItem::STATUS_FAILURE_RETRY],
    ];
    $items = $this->loadItems($input->getArgument('item_id'), $properties);

    $config = \Drupal::config('advancedqueue.settings');
    $timeout = $config->get('processing_timeout.drush');
    $end = $timeout ? time() + $timeout : 0;

    foreach ($items as $item) {
      /** @var \Drupal\advancedqueue\Queue\AdvancedQueue $queue */
      $queue = $this->queueManager->getQueue($item->name);

      if ($item = $queue->claimItem(NULL, $item->item_id)) {
        $output->writeln(sprintf($this->trans('commands.advancedqueue.item.process.messages.processing_item_start'), $item->name, $item->item_id, $item->title), OutputInterface::VERBOSITY_VERBOSE);

        $queue->processItem($item, $end);

        $callback = $item->status == AdvancedQueueItem::STATUS_SUCCESS ? 'successLite' : ($item->status == AdvancedQueueItem::STATUS_FAILURE_RETRY ? 'warningLite' : 'errorLite');
        $io->$callback(sprintf($this->trans('commands.advancedqueue.item.process.messages.processing_item_end'), $item->name, $item->item_id, $item->status, AdvancedQueueItem::getStatusLabel($item->status)));
      }
    }
  }

}
