<?php

namespace Drupal\student_data_handler\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\student_data_handler\services\StudentServiceClass;

/**
 * Provides an active students block.
 *
 * @Block(
 *   id = "active_students",
 *   admin_label = @Translation("Active students"),
 *   category = @Translation("Custom"),
 * )
 */
class ActiveStudentsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Contains the students handler service object.
   *
   * @var \Drupal\student_data_handler\services\StudentServiceClass
   */
  protected $studentHandler;

  /**
   * Takes the entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    StudentServiceClass $students_handler,
    EntityTypeManagerInterface $entity_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->studentHandler = $students_handler;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('students.handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'limit' => 5,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit of students'),
      '#default_value' => $this->configuration['limit'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['limit'] = $form_state->getValue('limit');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $data = $this->constructUserData();
    $build['content'] = [
      '#type' => 'theme',
      '#theme' => 'active_student_list',
      '#data' => $data,
      '#cache' => [
        'tags' => ['active:users'],
      ],
    ];

    return $build;
  }

  /**
   * Function to constructs the users data from their ids.
   *
   * @return array
   *   Returns tha array of user names.
   */
  private function constructUserData() {
    $uids = $this->studentHandler->getTopActiveStudents();

    if ($uids) {
      $users = $this->entityManager->getStorage('user')->loadMultiple($uids);
    }
    $data = [];
    if (isset($users)) {
      foreach ($users as $user) {
        if (in_array('students', $user->getRoles())) {
          $data[] = $user->get('field_full_name')->value;
        }
        if (count($data) >= $this->configuration['limit']) {
          break;
        }
      }
    }
    else {
      $data[] = 'No Users are active.';
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIf(TRUE);
  }

}
