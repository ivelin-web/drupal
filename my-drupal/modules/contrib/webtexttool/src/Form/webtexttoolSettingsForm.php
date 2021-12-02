<?php

/**
 * @file
 * Contains \Drupal\example\Form\webtexttoolSettingsForm
 */

namespace Drupal\webtexttool\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class webtexttoolSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'webtexttool_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['webtexttool_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => Drupal::state()->get('webtexttool.settings.name'),
      '#description' => $this->t('The username to connect to textmetrics.com.'),
    );

    $form['webtexttool_pass'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => Drupal::state()->get('webtexttool.settings.pass'),
      '#description' => $this->t('The password to connect to textmetrics.com.'),
    );

    $form['webtexttool_language'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => array('en' => t('English'), 'nl' => t('Dutch')),
      '#default_value' => Drupal::state()->get('webtexttool.settings.language'),
      '#description' => t('The default language of the tool itself. At this moment the tool itself is only available in Dutch and English.'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit'
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Save the credentials in de key_value table.
    Drupal::state()->set('webtexttool.settings.name', $form_state->getValue('webtexttool_user'));

    if ($pass = $form_state->getValue('webtexttool_pass')) {
      Drupal::state()->set('webtexttool.settings.pass', $pass);
    }

    if ($language = $form_state->getValue('webtexttool_language')) {
      Drupal::state()->set('webtexttool.settings.language', $language);
    }

    // Unset the webtexttool token.
    Drupal::service('webtexttool.webtexttool_controller')->webtexttoolSetToken('', FALSE);
  }
}