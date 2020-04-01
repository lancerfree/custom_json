<?php

/**
 * @file
 * Contains \Drupal\custom_json\Form\CardBlockSettingsForm.
 */

namespace Drupal\custom_json\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Defines a form that configures Cart Block.
 */
class CardBlockSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_json_card_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_json.card_block.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('custom_json.card_block.settings');

    $form['header_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Header Card Block Text'),
      '#default_value' => $config->get('header_text'),
      '#required' => TRUE,
    );

    $form['body_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body Card Block Text1'),
      '#default_value' => $config->get('body_text'),
      '#required' => TRUE,
    );

    $form['footer_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Footer Card Block Text'),
      '#default_value' => $config->get('footer_text'),
      '#required' => TRUE,
    );

    $form['shop_link'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link to shop'),
      '#default_value' => $config->get('shop_link'),
      '#required' => TRUE,
    );

    $form['main_image_id'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Main image of Card Block'),
      '#multiple' => FALSE,
      '#default_value' => [$config->get('main_image_id')],
      '#upload_location' => 'public://custom-images/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('png gif jpg jpeg'),
        'file_validate_size' => array(25600000),
      ),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('custom_json.card_block.settings');

    $config->set('header_text', $values['header_text']);
    $config->set('body_text', $values['body_text']);
    $config->set('footer_text', $values['footer_text']);
    $config->set('shop_link', $values['shop_link']);


    if ($values['main_image_id'][0]) {
      $file = File::load($values['main_image_id'][0]);
      if ($file) {
        $uriFile = $file->getFileUri();
        $file_path = file_url_transform_relative(file_create_url($uriFile));
        $file->setPermanent();
        $file->save();

        $config->set('main_image_id', $values['main_image_id'][0]);
        $config->set('main_image_uri', $file_path);
      }

    }

    $config->save();
  }
}