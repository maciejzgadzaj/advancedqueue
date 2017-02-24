<?php

namespace Drupal\advancedqueue\Command;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


/**
 * Class AdvancedQueueStyle.
 *
 * @package Drupal\advancedqueue
 */
class AdvancedQueueStyle extends SymfonyStyle {

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  public function __construct(InputInterface $input, OutputInterface $output) {
    parent::__construct($input, $output);
    self::addStatusStyles($output);
  }

  /**
   * Defines item status-specific display styles.
   *
   * Defined as static as it is also used by Drush commands.
   *
   * @param OutputInterface $output
   *
   * @see drush_advancedqueue_item_list()
   */
  public static function addStatusStyles(OutputInterface $output) {
    $formatter = $output->getFormatter();

    // Status: Queued.
    $style = new OutputFormatterStyle('white');
    $formatter->setStyle('status--1', $style);

    // Status: Processing.
    $style = new OutputFormatterStyle('yellow', NULL, ['bold']);
    $formatter->setStyle('status-0', $style);

    // Status: Processed.
    $style = new OutputFormatterStyle('green', NULL, ['bold']);
    $formatter->setStyle('status-1', $style);

    // Status: Error.
    $style = new OutputFormatterStyle('white', 'red', ['bold']);
    $formatter->setStyle('status-2', $style);

    // Status: Retry.
    $style = new OutputFormatterStyle('red', NULL, ['bold']);
    $formatter->setStyle('status-3', $style);
  }

}
