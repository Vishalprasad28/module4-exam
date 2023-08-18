<?php

namespace Drupal\old_data_remover\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to delete the old notice data using batch.
 */
class NoticeRemover extends FormBase {

  /**
   * Contains the entity manager service object.
   * 
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Contains the database connection object.
   * 
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs the required dependencies required by the <form action="
   * 
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Takes the entity manager pbject.
   * @param \Drupal\Core\Database\Connection $connection
   *   Contains the Connection object.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, Connection $connection) {
    $this->entityManager = $entity_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'old_data_remover_notice_remover';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Remove Notices'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $query = $this->connection->select('node', 'n');
    $query->addField('n.nid', 'nid');
    $query->innerJoin('node__field_event_date', 'event_date', 'n.nid = event_date.entity_id');
    $query->condition('event_date.field_event_date_value' ,\Drupal::time()->getRequestTime(), '<');
    $ids = $query->execute()->fetchAllAssoc(\PDO::FETCH_ASSOC);

    $operations = [];
    foreach (array_chunk($ids, 50) as $smaller_batch_data) {
      $operations[] = ['\Drupal\old_data_remover\Form\NoticeRemover::batchDelete', [$smaller_batch_data]];
    }

    // Setup and define batch informations.
    $batch = array(
      'title' => t('Deleting nodes in batch...'),
      'operations' => $operations,
      'finished' => '\Drupal\old_data_remover\Form\NoticeRemover::batchFinished',
    );
    batch_set($batch);
  }

  // Implement the operation method.
  public function batchDelete($smaller_batch_data, &$context) {
      // Deleting nodes.
      $storage_handler = $this->entityManager->getStorage('node');
      $entities = $storage_handler->loadMultiple($smaller_batch_data);
      $storage_handler->delete($entities);

      // Display data while running batch.
      $batch_size=sizeof($smaller_batch_data);
      $batch_number=sizeof($context['results'])+1;
      $context['message'] = sprintf("Deleting %s nodes per batch. Batch #%s" ,$batch_size, $batch_number);
      $context['results'][] = sizeof($smaller_batch_data);
  }

  /**
   * Displayes the message based on success or failure of the batch process.
   * 
   * @param 
   */
  public function batchFinished($success, $results, $operations) {
    if ($success)
      $message = count($results). ' batches processed.';
    else
      $message = 'Finished with an error.';
  }

}
