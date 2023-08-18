<?php

namespace Drupal\student_data_handler\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form Class to generate the API key for each user.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Contains the user's Account Object.
   * 
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs the dependency for the user accont <object data="
   * 
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Contains the User's Account Data.
   */
  public function __construct(AccountInterface $user) {
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'student_data_handler_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['student_data_handler.settings'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $id = $this->user->id();
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => isset($this->config('student_data_handler.settings')->get('api_keys')[$id]) ? $this->config('student_data_handler.settings')->get('api_keys')[$id] : '',
      '#attributes' => [
        'id' => 'api-value',
      ],
    ];
    $form['button'] = [
      '#type' => 'button',
      '#value' => $this->t('Generate'),
      '#ajax' => [
        'callback' => '::getApiKey',
        'wrapper' => 'api-value',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Generates the API Key for the user.
   * 
   * @param array $form
   *   Takes the form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Takes the formStateInterface Instance of the form.
   * 
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns the ajax response.
   */
  public function getApiKey($form, FormStateInterface $form_state) {
    $string = '0123456789abcdefghijklmnopqrstuvwxyz';
    // Generating a random API Key specific to the user.
    $key = str_shuffle($string);
    $key = substr($key, 0, 16);
    $data = $this->config('student_data_handler.settings')->get('api_keys');
    if (!isset($data[$this->user->id()])) {
      $data[$this->user->id()] = $key;
      $this->config('student_data_handler.settings')->set('api_keys', $data)->save();
    }
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('#api-value', 'val',
      [$this->config('student_data_handler.settings')->get('api_keys')[$this->user->id()]]));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
