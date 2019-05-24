<?php

namespace Drupal\google_login_handler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Google Login Handler Form class
 */
class GoogleLoginhandlerForm extends ConfigFormBase {

  /**
   * @var string config settings
   */
  const SETTINGS = 'google_login_handler.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_login_handler_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Default settings
    $config = $this->config(static::SETTINGS);

    $form['google_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API URL:'),
      '#default_value' => $config->get('google_login_handler.google_api_url'),
      '#description' => $this->t('Use this URL: https://www.googleapis.com/oauth2/v1/tokeninfo. You can change this later if they change the endpoint.')
    ];

    $form['token_issuer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token issuer property:'),
      '#default_value' => $config->get('google_login_handler.token_issuer'),
      '#description' => $this->t('Find more about this here: https://jwt.io/introduction/')
    ];

    $form['token_audience'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token audience property:'),
      '#default_value' => $config->get('google_login_handler.token_audience'),
      '#description' => $this->t('Find more about this here: https://jwt.io/introduction/')
    ];

    $form['signature_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token signature key:'),
      '#default_value' => $config->get('google_login_handler.signature_key'),
      '#description' => $this->t('Find more about this here: https://jwt.io/introduction/')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('google_login_handler.google_api_url', $form_state->getValue('google_api_url'))
      ->set('google_login_handler.token_issuer', $form_state->getValue('token_issuer'))
      ->set('google_login_handler.token_audience', $form_state->getValue('token_audience'))
      ->set('google_login_handler.signature_key', $form_state->getValue('signature_key'))
      ->save();

      return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    // Returns the names of the settings files used by this module
    return [
      static::SETTINGS
    ];
  }
  
}
?>