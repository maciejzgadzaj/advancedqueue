<?php

namespace Drupal\advancedqueue_ui\Controller;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Advanced Queue UI module routes.
 */
class AdminController extends ControllerBase {

  /**
   * The queue worker plugin manager.
   *
   * @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager
   */
  protected $queueManager;

  /**
   * Constructs a \Drupal\advancedqueue_ui\Controller\AdminController object.
   *
   * @param \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager
   *   The queue worker plugin manager.
   */
  public function __construct(AdvancedQueueWorkerManager $queue_manager) {
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * Builds the queues overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function queueList() {
    $queues_by_group = $this->queueManager->getGroupDefinitions();
    $advancedqueue_statuses = AdvancedQueueItem::getStatusOptions();

    // Get suspended queues.
    $suspended_queues = $this->queueManager->getSuspendedQueues();

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

      $group_item_count = $group_unprocessed_item_count = $group_suspended_count = 0;

      foreach ($group_queues as $queue_name => $queue_info) {
        $queue = $this->queueManager->getQueue($queue_name);

        // Build queue name and machine name columns.
        $url = Url::fromUri('internal:/admin/structure/queues/' . $queue_name);
        $queue_label = (string) Link::fromTextAndUrl($queue_info['title'], $url)->toString();
        if (in_array($queue_name, array_keys($suspended_queues))) {
          $queue_label .= '<div class="queue-suspended">' . $this->t('Suspended') . '</div>';
          $queue_label .= '<div class="queue-suspended-time">' . \Drupal::service('date.formatter')->formatInterval($suspended_queues[$queue_name] - time()) . '</div>';
          $group_suspended_count++;
        }
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
            'data' => Link::fromTextAndUrl($result[$status_code] ? $result[$status_code] : '-', $url),
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
          if ((!empty($result[AdvancedQueueItem::STATUS_QUEUED]) || !empty($result[AdvancedQueueItem::STATUS_FAILURE_RETRY])) && $group_name != AdvancedQueueWorkerManager::GROUP_UNDEFINED) {
            $operations['#links'][] = [
              'title' => $this->t('Process'),
              'url' => Url::fromRoute('advancedqueue_ui.bulk_queue_process_confirm', ['queue_name' => $queue_name], ['query' => $this->getDestinationArray()]),
            ];
          }
          if (!empty($queue_item_count)) {
            $operations['#links'][] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('advancedqueue_ui.bulk_queue_delete_confirm', ['queue_name' => $queue_name], ['query' => $this->getDestinationArray()]),
            ];
          }
          if (in_array($queue_name, array_keys($suspended_queues))) {
            $operations['#links'][] = [
              'title' => $this->t('Unsuspend'),
              'url' => Url::fromRoute('advancedqueue_ui.bulk_queue_unsuspend_confirm', ['queue_name' => $queue_name], ['query' => $this->getDestinationArray()]),
            ];
          }
          else {
            $operations['#links'][] = [
              'title' => $this->t('Suspend'),
              'url' => Url::fromRoute('advancedqueue_ui.bulk_queue_suspend_confirm', ['queue_name' => $queue_name], ['query' => $this->getDestinationArray()]),
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
      if (count($queues_by_group) > 1) {
        $rows[$group_name][0]['colspan'] = count($advancedqueue_statuses) + 2;

        $operations = [
          '#type' => 'operations',
          '#links' => [],
        ];

        if (\Drupal::currentUser()->hasPermission('advancedqueue_admin manage queues')) {
          if (!empty($group_item_count)) {
            // Do not show 'Process' link for 'Undefined' group, as we don't know
            // what the worker function for its queues is, so we can't process them.
            if (!empty($group_unprocessed_item_count) && $group_name != AdvancedQueueWorkerManager::GROUP_UNDEFINED) {
              $operations['#links'][] = [
                'title' => $this->t('Process'),
                'url' => Url::fromRoute('advancedqueue_ui.bulk_queue_process_confirm', ['queue_name' => 'group:' . $group_name], [
                  'query' => $this->getDestinationArray(),
                  'attributes' => ['title' => t('Process all unprocessed items in this group')],
                ]),
              ];
            }
            if (!empty($group_item_count)) {
              $operations['#links'][] = [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('advancedqueue_ui.bulk_queue_delete_confirm', ['queue_name' => 'group:' . $group_name], [
                  'query' => $this->getDestinationArray(),
                  'attributes' => ['title' => t('Delete all items from this group')],
                ]),
              ];
            }
          }
          $operations['#links'][] = [
            'title' => $this->t('Suspend'),
            'url' => Url::fromRoute('advancedqueue_ui.bulk_queue_suspend_confirm', ['queue_name' => 'group:' . $group_name], [
              'query' => $this->getDestinationArray(),
              'attributes' => ['title' => t('Suspend processing for all queues in this group')],
            ]),
          ];
          if (!empty($group_suspended_count)) {
            $operations['#links'][] = [
              'title' => $this->t('Unsuspend'),
              'url' => Url::fromRoute('advancedqueue_ui.bulk_queue_unsuspend_confirm', ['queue_name' => 'group:' . $group_name], [
                'query' => $this->getDestinationArray(),
                'attributes' => ['title' => t('Unsuspend all suspended queues in this group')],
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
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = $this->queueManager->createInstance($queue_name);

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
    if (strpos($queue_name, 'group:') === 0) {
      return $this->t('Group: %group_name', ['%group_name' => str_replace('group:', '', $queue_name)]);
    }
    else {
      return !empty($this->queueManager->getTitle($queue_name)) ? $this->t('@queue_title (@queue_name)', [
        '@queue_title' => $this->queueManager->getTitle($queue_name),
        '@queue_name' => $queue_name,
      ]) : $queue_name;
    }
  }

}
