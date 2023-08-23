<?php

namespace Drupal\student_data_handler\services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;

/**
 * Conatains the methods to handle the students data.
 */
class StudentServiceClass {

  /**
   * Contains the DateTime object.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Conatins the current user data object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $user;

  /**
   * Contains the Database Connection Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs the Required dependencies fo the service to work.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Takes the current user account object.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Contains the TimeInteface object for date and time handling.
   * @param \Drupal\Core\Database\Connection $connection
   *   Takes the database connection object.
   */
  public function __construct(AccountInterface $user, TimeInterface $time, Connection $connection) {
    $this->user = $user;
    $this->time = $time;
    $this->connection = $connection;
  }

  /**
   * Fetch the Top Active students.
   *
   * @return array|bool
   *   returns the array of data being fetched.
   */
  public function getTopActiveStudents() {
    try {
      $uids = $this->connection
        ->query('SELECT uid FROM sessions WHERE uid != 0 AND `timestamp` >=:time',
       [':time' => ($this->time->getCurrentTime() - 3600)])->fetchCol();
    }
    catch (\Exception $e) {
      return FALSE;
    }
    return $uids;
  }

}
