<?php

namespace Drupal\old_data_remover\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Service class to handle the node delete operations.
 */
class DataremovalService {
  /**
   * Contains the database manager object.
   *
   * @var \Drupal\Core\Database\Driver\corefake\Connection
   */
  protected $connection;

  /**
   * Constructs a new DataremovalService object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Contains the connection object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Deletes the notices whose dates are expired.
   *
   * @return void
   */
  public function deleteOldNotices() {
    $ids = $this->getNoticeNodeIds();
    $this->setUpBatch($ids, 'node');
  }

  /**
   * Deletes the students data who have been graduated.
   *
   * @return void
   */
  public function deleteGraduatedStudents() {
    $ids = $this->getStudentIds();
    $this->setUpBatch($ids, 'user');
  }

  /**
   * Function to fetch the user ids of students to be deleted.
   *
   * @return array
   *   Returns the array of user ids.
   */
  public function getStudentIds() {
    $currentDateTime = new DrupalDateTime('-6 months');
    $date = $currentDateTime->format('Y-m-d');

    $query = $this->connection->select('users_field_data', 'u');
    $query->innerJoin('user__field_passing_year', 'year', 'u.uid = year.entity_id');
    $query->addField('u', 'uid');
    $query->condition('year.field_passing_year_value', $date, '<=');
    $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $ids = [];
    foreach ($result as $id) {
      $ids[] = $id['uid'];
    }

    return $ids;
  }

  /**
   * Function to fetch the node ids of node to be deleted.
   *
   * @return array
   *   Returns the array of node ids.
   */
  public function getNoticeNodeIds() {
    $currentDateTime = new DrupalDateTime();
    $now = $currentDateTime->format('Y-m-d');

    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node__field_event_date', 'event_date', 'n.nid = event_date.entity_id');
    $query->addField('n', 'nid');
    $query->condition('event_date.field_event_date_value', $now, '<');
    $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $ids = [];
    foreach ($result as $id) {
      $ids[] = $id['nid'];
    }

    return $ids;
  }

  /**
   * Performs the batch delete operations.
   *
   * @param mixed $smaller_batch_data
   *   Takes the smaller batch data to perform operation upn.
   * @param mixed $context
   *   Takes the context of data.
   *
   * @return void
   */
  public static function batchDeleteUser($smaller_batch_data, &$context) {
    // Deleting nodes.
    $storage_handler = \Drupal::entityTypeManager()->getStorage('user');
    $entities = $storage_handler->loadMultiple($smaller_batch_data);
    $storage_handler->delete($entities);

    // Display data while running batch.
    $batch_size = count($smaller_batch_data);
    $batch_number = count($context['results']) + 1;
    $context['message'] = sprintf("Deleting %s nodes per batch. Batch #%s", $batch_size, $batch_number);
    $context['results'][] = count($smaller_batch_data);
  }

  /**
   * Performs the batch delete operations.
   *
   * @param mixed $smaller_batch_data
   *   Takes the smaller batch data to perform operation upn.
   * @param mixed $context
   *   Takes the context of data.
   *
   * @return void
   */
  public static function batchDeleteNode($smaller_batch_data, &$context) {
    // Deleting nodes.
    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');
    $entities = $storage_handler->loadMultiple($smaller_batch_data);
    $storage_handler->delete($entities);

    // Display data while running batch.
    $batch_size = count($smaller_batch_data);
    $batch_number = count($context['results']) + 1;
    $context['message'] = sprintf("Deleting %s nodes per batch. Batch #%s", $batch_size, $batch_number);
    $context['results'][] = count($smaller_batch_data);
  }

  /**
   * Displayes the message based on success or failure of the batch process.
   *
   * @param mixed $success
   *   Takes the success value for the batch process.
   * @param mixed $results
   *   Takes the result of the batch process.
   * @param mixed $operations
   *   Takes the operations value for the batch.
   *
   * @return void
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      $message = count($results) . ' batches processed.';
    }
    else {
      $message = 'Finished with an error.';
    }

    \Drupal::messenger()->addMessage(t($message));
  }

  /**
   * Constructs the batch data to perform operations upon.
   *
   * @param array $ids
   *   Takes the array of node ids.
   * @param string $entity_type
   *   Takes the entity type id.
   *
   * @return array
   *   Returns the array of operation data.
   */
  public function setUpBatch(array $ids, string $entity_type) {
    $operations = [];
    if ($entity_type == 'node') {
      foreach (array_chunk($ids, 50) as $smaller_batch_data) {
        $operations[] = ['\Drupal\old_data_remover\Services\DataremovalService::batchDeleteNode', [$smaller_batch_data]];
      }
    }
    elseif ($entity_type == 'user') {
      foreach (array_chunk($ids, 50) as $smaller_batch_data) {
        $operations[] = ['\Drupal\old_data_remover\Services\DataremovalService::batchDeleteUser', [$smaller_batch_data]];
      }
    }

    // Setup and define batch informations.
    $batch = [
      'title' => t('Deleting nodes in batch...'),
      'operations' => $operations,
      'finished' => '\Drupal\old_data_remover\Services\DataremovalService::batchFinished',
    ];
    batch_set($batch);
  }

}
