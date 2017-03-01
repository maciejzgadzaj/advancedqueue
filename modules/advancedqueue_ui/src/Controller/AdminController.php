<?php

namespace Drupal\advancedqueue_ui\Controller;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * Returns responses for Advanced Queue UI module routes.
 */
class AdminController extends ControllerBase {

  /**
   * Builds the queues overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function queueList() {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
    $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');

    $queues_by_group = $queue_worker_manager->getGroupDefinitions();
    $advancedqueue_statuses = AdvancedQueueItem::getStatusOptions();

    $header = array_merge(
      [$this->t('Queue'), $this->t('Machine name')],
      AdvancedQueueItem::getStatusOptions(),
      [$this->t('Operations')]);

    $rows = [];
    foreach ($queues_by_group as $group_name => $group_queues) {

      // If we have more than one queue group defined, display group header.
      if (count($queues_by_group) > 1) {
        $rows[$group_name] = [
          [
            'data' => $this->t($group_name),
            'colspan' => count($advancedqueue_statuses) + 3,
            'class' => 'group-name',
          ],
        ];
      }

      $group_item_count = $group_unprocessed_item_count = 0;

      foreach ($group_queues as $queue_name => $queue_info) {
        $queue = $queue_factory->get($queue_name);

        // Build queue name and machine name columns.
        $url = Url::fromUri('internal:/admin/structure/queues/' . $queue_name);
        $queue_label = (string) Link::fromTextAndUrl($queue_info['title'], $url)->toString();
        if (!empty($queue_info['description'])) {
          $queue_label .= '<div class="description">' . $queue_info['description'] . '</div>';
        }
        $queue_label_element = ['#markup' => $queue_label];
        $row = [
          [
            'data' => \Drupal::service('renderer')->render($queue_label_element),
            'class' => 'queue-name',
          ],
          [
            'data' => $queue_info['id'],
            'class' => 'queue-machine-name',
          ],
        ];
        // If we have more than one group defined, indent queue names.
        if (count($queues_by_group) > 1) {
          $row[0]['class'] .= ' queue-in-group';
        }

        // Build items count columns.
        $queue_item_count = 0;
        foreach (array_keys(AdvancedQueueItem::getStatusOptions()) as $status_code) {
          $result[$status_code] = $queue->numberOfItems($status_code);
          $url = Url::fromUri('internal:/admin/structure/queues/' . $queue_name, ['query' => ['status[]' => $status_code]]);
          $row[] = [
            'data' => Link::fromTextAndUrl($result[$status_code], $url),
            'class' => 'queue-item-count-' . $status_code,
          ];
          $queue_item_count += !empty($result[$status_code]) ? $result[$status_code] : 0;
          $group_item_count += !empty($result[$status_code]) ? $result[$status_code] : 0;

          if (in_array($status_code, [AdvancedQueueItem::STATUS_QUEUED, AdvancedQueueItem::STATUS_FAILURE_RETRY])) {
            $group_unprocessed_item_count += !empty($result[$status_code]) ? $result[$status_code] : 0;
          }
        }

        // Build queue operations column.
        $operations = [
          '#type' => 'operations',
          '#links' => [],
        ];
        if (\Drupal::currentUser()->hasPermission('advancedqueue_admin manage queues')) {
          if ((!empty($result[AdvancedQueueItem::STATUS_QUEUED]) || !empty($result[AdvancedQueueItem::STATUS_FAILURE_RETRY])) && $group_name != AdvancedQueueWorkerManager::UNGROUPED) {
            $operations['#links'][] = [
              'title' => $this->t('Process'),
              'url' => Url::fromRoute('advancedqueue_ui.queue_process_confirm', ['queue_name' => $queue_name], ['query' => $this->getDestinationArray()]),
            ];
          }
          if (!empty($queue_item_count)) {
            $operations['#links'][] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('advancedqueue_ui.queue_delete_confirm', ['queue_name' => $queue_name], ['query' => $this->getDestinationArray()]),
            ];
          }
        }
        $row[] = [
          'data' => $operations,
          'class' => 'queue-operations',
        ];

        $rows[] = $row;
      }

      // Build group operations column.
      if (count($queues_by_group) > 1 && !empty($group_item_count)) {
        $rows[$group_name][0]['colspan'] = count($advancedqueue_statuses) + 2;

        $operations = [
          '#type' => 'operations',
          '#links' => [],
        ];
        if (\Drupal::currentUser()->hasPermission('advancedqueue_admin manage queues')) {
          // Do not show 'Process' link for 'Undefined' group, as we don't know
          // what the worker function for its queues is, so we can't process them.
          if (!empty($group_unprocessed_item_count) && $group_name != 'Undefined') {
            $operations['#links'][] = [
              'title' => $this->t('Process'),
              'url' => Url::fromRoute('advancedqueue_ui.queue_process_confirm', ['queue_name' => 'group:' . $group_name], [
                'query' => $this->getDestinationArray(),
                'attributes' => ['title' => t('Process all unprocessed items in this group')],
              ]),
            ];
          }
          if (!empty($group_item_count)) {
            $operations['#links'][] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('advancedqueue_ui.queue_delete_confirm', ['queue_name' => 'group:' . $group_name], [
                'query' => $this->getDestinationArray(),
                'attributes' => ['title' => t('Delete all items from this group')],
              ]),
            ];
          }
        }
        $rows[$group_name][] = [
          'data' => $operations,
          'class' => 'group-operations',
        ];
      }

    }

    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'advancedqueue_ui_queues'],
      '#attached' => ['library' => ['advancedqueue_ui/advancedqueue_ui.queue_list']],
    ];
  }

  /**
   * Builds the queue item listing page.
   *
   * @param string $queue_name
   *   A name of the queue to show the items for.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function queueItems($queue_name = 'all') {

    $args = [$queue_name];
    $view = Views::getView('advancedqueue_ui');
    if (is_object($view)) {
      $view->setArguments($args);
      $view->setDisplay('default');
      $view->preExecute();
      $view->execute();
      return $view->buildRenderable('default', $args);
    }
  }

  /**
   * Builds the queue devel info page.
   *
   * @param string $queue_name
   *   A name of the queue to show the devel info page for.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function develQueue($queue_name) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
    $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_worker = $queue_worker_manager->createInstance($queue_name);

    return \Drupal::service('devel.dumper')->exportAsRenderable($queue_worker);
  }

  /**
   * Returns title for the queue item listing page.
   *
   * @param string $queue_name
   *   A name of the queue to return the title for.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   A page title.
   */
  public function queueItemsTitle($queue_name) {
    /** @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_worker_manager */
    $queue_worker_manager = \Drupal::service('plugin.manager.queue_worker');
    $definitions = $queue_worker_manager->getDefinitions();

    return !empty($definitions[$queue_name]['title']) ? $this->t('@queue_title (@queue_name)', [
      '@queue_title' => $definitions[$queue_name]['title'],
      '@queue_name' => $queue_name,
    ]) : $queue_name;

  }

}
