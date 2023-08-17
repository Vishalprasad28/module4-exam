<?php

namespace Drupal\user_role_assigner\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure User Role Assigner settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_role_assigner_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['user_role_assigner.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [
      'auto_assign' => $this->t('Auto Assign role'),
      'admin_assigns' => $this->t('Admin assigns the role'),
    ];
    $roles = user_roles();
    unset($roles['anonymous']);
    unset($roles['authenticated']);
    $roles_options = [];
    foreach ($roles as $key => $value) {
      $roles_options[$key] = $key;
    }

    $form['roles_to_assign'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the roles to assign'),
      '#options' => $roles_options,
      '#default_value' => $this->config('user_role_assigner.settings')->get('roles_to_assign'),
    ];
    $form['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select the method'),
      '#options' => $options,
      '#default_value' => $this->config('user_role_assigner.settings')->get('method'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [];
    $roles = $form_state->getValue('roles_to_assign');
    foreach ($roles as $role => $is_selected) {
      if ($is_selected) {
        $data[] = $is_selected;
      }
    }
    $this->config('user_role_assigner.settings')
      ->set('roles_to_assign', $data)
      ->set('method', $form_state->getValue('method'))
      ->save();
  }

}
