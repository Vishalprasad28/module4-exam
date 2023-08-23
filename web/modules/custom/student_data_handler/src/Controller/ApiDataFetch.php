<?php

namespace Drupal\student_data_handler\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses of API for Student Data Handler routes.
 */
class ApiDataFetch extends ControllerBase {

  /**
   * Contains the current user account information.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Contains the Config Facory object to get the configuraion data.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Takes the httpClient object for api handling.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Contains the current user account object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Contains the config factory dependency.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $client
   *   Contains the HTTPClient Object for API data handling.
   */
  public function __construct(
    AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    ClientInterface $client) {
    $this->user = $current_user;
    $this->configFactory = $config_factory;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Builds the response.
   *
   * @return array
   *   Returns the render array.
   */
  public function invoke() {
    $headers = [
      'id' => '74',
      'key' => '8n7qjk4ilgryfa0t',
    ];
    $query = [
      'stream' => 'cse5',
      'year' => 2023,
    ];
    $options = [
      'headers' => $headers,
      // 'query' => $query,
    ];
    $request = $this->client->request('GET', 'http://mod4.com/student/api/20', $options);
    $result = $request->getBody()->getContents();
    $result = json_decode($result, TRUE);

    return new JsonResponse($result);
  }

}
