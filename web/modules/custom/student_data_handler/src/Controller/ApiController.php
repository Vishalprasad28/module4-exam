<?php

namespace Drupal\student_data_handler\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Hello World routes.
 */
class ApiController implements ContainerInjectionInterface {

  /**
   * Contains the entity Manager object.
   * 
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * Conatisn the Config Factory object.
   * 
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Contains the Current Logged In User Accountobject.
   * 
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs the required dependencies for the route..
   * 
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_manager
   *   Contains the entityTypeManager instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *   Containsthe Config Factory Object.
   * @param \Drupal\Core\Session\AccountInterface
   *   Comntains the Current use object.
   */
  public function __construct(EntityTypeManager $entity_manager,
    ConfigFactoryInterface $config_factory,
    AccountInterface $user) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * Builds the Json response.
   * 
   * @param int $limit
   *   Takes the limitf data to be displayed.
   * 
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function invoke(int $limit) {
    $user_ids = $this->entityManager->getStorage('user')->getQuery()
      ->condition('roles', 'students')
      ->accessCheck(FALSE)
      ->execute();
    $key = $_GET['key'];
    $id = $this->user->id();
    if (!isset($key)) {
      return new JsonResponse(['nothing to display']);
    }
    else if (isset($this->configFactory->getEditable('student_data_handler.settings')->get('api_keys')[$id]) &&
      $key != $this->configFactory->getEditable('student_data_handler.settings')->get('api_keys')[$id]) {
      return new JsonResponse(['nothing to display']);
    }

    return new JsonResponse([
      'data' => $this->fetchUserData($user_ids, $limit),
    ]);
  }

  /**
   * Function to fetch the user's data.
   * 
   * @param array $user_ids
   *   Takes the user ids to load.
   * 
   * @param int $limit
   *   Takes the limit of data to be fetched.
   * 
   * @return JsonResponse
   *   returns the json response.
   */
  public function fetchUserData(array $user_ids, int $limit) {
    if ($user_ids) {
      $user_ids = array_slice($user_ids, 0, $limit);
      $users = $this->entityManager->getStorage('user')->loadMultiple($user_ids);

      $data = [];
      // Storing the data of each user in a array.
      foreach ($users as $user) {
        $temp['full_name'] = $user->get('field_full_name')->value;
        $temp['user_name'] = $user->getAccountName();
        $temp['user_email'] = $user->getEmail();
        $temp['stream'] = $this->entityManager->getStorage('taxonomy_term')
          ->load($users[3]->get('field_stream')
          ->getValue()[0]['target_id'])->getName();
        $temp['phone'] = $user->get('field_phone_number')->value;
        $temp['field_passing_year'] = date_format(date_create($user->get('field_passing_year')->value), "Y");
        array_push($data, $temp);
      }
    }

    return $data;
  }

  /**
   * Function to check access of user for the route.
   * 
   * @return AccessResult
   *   Returns the access result.
   */
  public function access() {
    $id = $this->user->id();
    // Checking if the user has permission to view the page
    if (isset($this->configFactory->getEditable('student_data_handler.settings')->get('api_keys')[$id])) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
