<?php

namespace Drupal\advancedqueue\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Class CommandStyle.
 *
 * @package Drupal\advancedqueue
 */
class CommandStyle {

  /**
   * @param \Symfony\Component\Console\Output\ConsoleOutputInterface $output
   */
  public static function addStatusStyles($output) {
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
