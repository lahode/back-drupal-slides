<?php

namespace Drupal\slide_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\NodeStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Database\Connection;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Entity\EntityFieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the Slide module.
 */
class SlideshowController extends ControllerBase {

  /**
   * The node Storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

 /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * DB Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(NodeStorageInterface $node_storage, EntityFieldManager $entity_field_manager, Connection $database) {
    $this->nodeStorage = $node_storage;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('node'),
      $container->get('entity_field.manager'),
      $container->get('database')
    );
  }

  /* 
    - TODO 
    - Parcourir tous les bundles de paragraphs avec getAllBundleInfo();
    - Récupérer les autres valeurs que value
  */

  /**
   * Returns the information of a slideshow.
   *
   * @return array
   *   A simple renderable array.
   */
  public function render() {
    $data = [];

    // Get all the slides nids
    $query = $this->database->select('book', 'b');
    $query->fields('b', array('nid'));
    $query->condition('pid', 0);
    $query->orderby('weight', 'ASC');
    $nids = $query->execute()->fetchCol(0);
    if (!empty($nids)) {
      $nodes = $this->nodeStorage->loadMultiple($nids);
      foreach ($nodes as $node) {
        $data[] = [
          'id' => $node->id(),
          'title' => $node->getTitle(),
          'body' => count($node->get('body')->getValue()) > 0 ? $node->get('body')->getValue()[0]['value'] : '',
          'logo' => $node->field_logo[0]->entity ? file_create_url($node->field_logo[0]->entity->getFileUri()) : ''
        ];
      }
    }

    $response = new Response();
    $response->setContent(\Drupal::service('serializer')->serialize($data, 'json'));
    $response->headers->set('Content-Type', 'application/json');
    $response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept');
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET');
    return $response;
  }

}
