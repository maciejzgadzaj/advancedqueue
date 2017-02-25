<?php

namespace Drupal\advancedqueue\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advancedqueue_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'advancedqueue.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('advancedqueue.settings');

    $options = [100, 1000, 10000, 100000, 1000000];
    $form['threshold'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of completed items to keep in the database'),
      '#description' => $this->t('Setting this to any value other than <em>All</em> will force deletion of old completed items from the database (both processed successfully as well as failed).'),
      '#default_value' => $config->get('threshold'),
      '#options' => [0 => $this->t('All')] + array_combine($options, $options),
    ];

    $options = [3600, 10800, 21600, 43200, 86400, 604800];
    $form['release_timeout'] = [
      '#type' => 'select',
      '#title' => $this->t('Time to wait before releasing an expired item'),
      '#default_value' => $config->get('release_timeout'),
      '#options' => [0 => $this->t('Never')] + array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($options, $options)),
    ];

    $form['processing_timeout_drush'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Drush processing timeout'),
      '#description' => $this->t('The default maximum execution time when processing queue items using Drush. Be warned that this is a rough estimate as the time is only checked between two items. This value can be altered when executing the Drush command by providing the <code>--timeout</code> parameter. The default value of <em>0</em> will keep processing queue items until the Drush command is killed.'),
      '#field_suffix' => $this->t('seconds'),
      '#required' => TRUE,
      '#element_validate' => [[$this, 'elementValidateIntegerPositive']],
      '#default_value' => $config->get('processing_timeout.drush'),
    ];

    $form['processing_timeout_cron'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cron processing timeout'),
      '#description' => $this->t('The default maximum execution time when processing queue items using cron. Be warned that this is a rough estimate as the time is only checked between two items.'),
      '#field_suffix' => $this->t('seconds'),
      '#required' => TRUE,
      '#element_validate' => [[$this, 'elementValidateIntegerPositive']],
      '#default_value' => $config->get('processing_timeout.cron'),
    ];

    $form['use_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process Advanced Queue via cron'),
      '#multiple' => TRUE,
      '#description' => $this->t('Enable to allow queue items to to be processed using cron. This is a "poor man\'s" option that allows processing the queue, as the better solution would be to execute the Drush command via the command line.'),
      '#default_value' => $config->get('use_cron'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('advancedqueue.settings')
      ->set('threshold', $form_state->getValue('threshold'))
      ->set('release_timeout', $form_state->getValue('release_timeout'))
      ->set('processing_timeout.drush', $form_state->getValue('processing_timeout_drush'))
      ->set('processing_timeout.cron', $form_state->getValue('processing_timeout_cron'))
      ->set('use_cron', $form_state->getValue('use_cron'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Form element validation handler; Filters the #value property of an element.
   */
  public static function elementValidateIntegerPositive(&$element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value < 0)) {
      $form_state->setError($element, t('%name must be a positive integer.', ['%name' => $element['#title']]));
    }
  }

}
