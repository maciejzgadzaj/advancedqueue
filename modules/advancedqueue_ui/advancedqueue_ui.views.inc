<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_pre_build().
 */
function advancedqueue_ui_views_pre_build(ViewExecutable $view) {
  if ($view->storage->id() == 'advancedqueue_ui' && !empty($view->args[0]) && $view->args[0] != 'all') {
    unset($view->field['queue_name']);
    unset($view->field['queue_machine_name']);
    unset($view->filter['combine_queue_name']);
  }
}

/**
 * Implements hook_views_pre_render().
 */
function advancedqueue_ui_views_pre_render(ViewExecutable $view) {
  if (isset($view) && ($view->storage->id() == 'advancedqueue_ui')) {
    $view->element['#attached']['library'][] = 'advancedqueue_ui/advancedqueue_ui.item_list';
  }
}

/**
 * Implements hook_form_alter().
 */
function advancedqueue_ui_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Alter the bulk operations form for Advanced Queue UI view.
  if (strpos($form_id, 'views_form_advancedqueue_ui_default_') === 0) {
    // Get weights of all advancedqueue_item type action plugins.
    $weights = [];
    /** @var \Drupal\Core\Action\ActionManager $manager */
    foreach (\Drupal::service('plugin.manager.action')->getDefinitionsByType('advancedqueue_item') as $action_definition) {
      $weights[] = $action_definition['weight'];
    }

    // Sort Action form element options by plugin weights.
    // Form element could not exist if there are no items in the queue.
    if (!empty($form['header']['bulk_form']['action']['#options'])) {
      $action_options = &$form['header']['bulk_form']['action']['#options'];
      array_multisort($weights, SORT_ASC, SORT_NUMERIC, $action_options);
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for views_exposed_form.
 */
function advancedqueue_ui_form_views_exposed_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $storage = $form_state->getStorage();
  if ($storage['view']->storage->id() == 'advancedqueue_ui') {

    // Group filters together into columns.
    $form['item_id_key']['#prefix'] = '<div class="filter-group">';
    $form['combine_title_tag']['#suffix'] = '</div>';

    $form['created_op']['#prefix'] = '<div class="filter-group">';
    $form['created']['#suffix'] = '</div>';

    $form['owner']['#prefix'] = '<div class="filter-group">';
    $form['status']['#suffix'] = '</div>';

    $form['processed_op']['#prefix'] = '<div class="filter-group">';
    $form['processed']['#suffix'] = '</div>';

    $form['data']['#prefix'] = '<div class="filter-group">';
    $form['items_per_page']['#suffix'] = '</div>';

    // On Queue items page displaying items for all queues, we also have an
    // additional 'Queue name' filter shown, which should be put in the same
    // filter column as 'Item ID/key' and 'Item title/tag'.
    if (empty($storage['view']->args[0]) || $storage['view']->args[0] == 'all') {
      $form['queue_name']['#prefix'] = '<div class="filter-group">';
      unset($form['item_id_key']['#prefix']);
    }

    // Reformat date filters, so the are shown in a better way.
    foreach (['created', 'processed'] as $element_name) {
      $form[$element_name . '_op']['#title'] = $form[$element_name]['min']['#title'];
      unset($form[$element_name]['min']['#title']);
      $form[$element_name]['max']['#title'] = 'and';
      $form[$element_name]['max']['#description'] = $form[$element_name]['min']['#description'];
      unset($form[$element_name]['min']['#description']);
    }

  }
}
