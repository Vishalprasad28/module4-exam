<?php

namespace Drupal\student_data_handler\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for the API Calls..
 */
class ApiController implements ContainerInjectionInterface {

  /**
   * Contains the entity Manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * Conatins the Config Factory object.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Containsthe Config Factory Object.
   * @param \Drupal\Core\Session\AccountInterface $user
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
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('current_user'),
    );
  }

  /**
   * Builds the Json response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Takes the Requesr object to fetch the query and headers.
   * @param int $limit
   *   Takes the limitf data to be displayed.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns the Json Response.
   */
  public function invoke(Request $request, int $limit) {
    // Authenticating the API Call.
    if ($this->authenticateApiCall($request)) {
      $query = $this->entityManager
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', 'students');

      // Fetching the term ids by their name.
      $terms = array_keys($this->entityManager
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'vid' => 'stream',
          'name' => $request->query->get('stream') ? $request->query->get('stream') : '',
        ]));

      if ($terms) {
        $query->condition('field_stream', $terms);
      }
      else if ($request->query->get('stream')) {
        $query->condition('field_stream', $request->query->get('stream'));
      }
      if ($request->query->get('year')) {
        $query->condition('field_passing_year', $request->query->get('year') . '-01-01', '>=')
          ->condition('field_passing_year', ($request->query->get('year') + 1) . '-01-01', '<');
      }

      $result = $query
        ->range(0, $limit)
        ->execute();
      return new JsonResponse(
        $this->fetchUserData($result),
      );
    }

    return new JsonResponse(
      'Access Denied'
    );
  }

  /**
   * Function to authenticate the API call being made.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Takes the Request object to fetch the headers and query.
   *
   * @return bool
   *   Returns bool based on authentication result.
   */
  public function authenticateApiCall(Request $request) {
    if ($request->headers->get('key') == NULL || $request->headers->get('id') == NULL || !is_numeric($request->headers->get('id'))) {
      return FALSE;
    }
    elseif (!isset($this->configFactory->getEditable('student_data_handler.settings')->get('api_keys')[$request->headers->get('id')]) ||
      $request->headers->get('key') != $this->configFactory->getEditable('student_data_handler.settings')
        ->get('api_keys')[$request->headers->get('id')]) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Function to fetch the user's data.
   *
   * @param array $user_ids
   *   Takes the user ids to load.
   *
   * @return array
   *   returns the array of data.
   */
  public function fetchUserData(array $user_ids) {
    if ($user_ids) {
      $users = $this->entityManager->getStorage('user')->loadMultiple(array_values($user_ids));
      $data = [];
      // Storing the data of each user in a array.
      foreach ($users as $user) {
        $temp['full_name'] = $user->get('field_full_name')->value;
        $temp['user_name'] = $user->getAccountName();
        $temp['user_email'] = $user->getEmail();
        $temp['stream'] = $this->entityManager->getStorage('taxonomy_term')
          ->load($user->get('field_stream')
            ->getValue()[0]['target_id'])->getName();
        $temp['phone'] = $user->get('field_phone_number')->value;
        $temp['field_passing_year'] = date_format(date_create($user->get('field_passing_year')->value), "Y");
        array_push($data, $temp);
      }
    }

    return $data;
  }

}
