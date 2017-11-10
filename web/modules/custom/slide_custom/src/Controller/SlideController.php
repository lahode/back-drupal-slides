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
class SlideController extends ControllerBase {

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
  public function render(Request $request) {
    $node = $request->get('book');
    $data = [];

    // Check if node type is book
    if ($node->getType() == 'book') {

      // Get all the slides nids
      $query = $this->database->select('book', 'b');
      $query->fields('b', array('nid'));
      $query->condition('pid', 1);
      $nids = $query->execute()->fetchCol(0);
      if (!empty($nids)) {

        // Load every slides
        $slides = $this->nodeStorage->loadMultiple($nids);
        $i=0;
        foreach ($slides as $slide) {
          $data[$i] = [
            'title' => $slide->getTitle(),
            'components' => []
          ];

          // Get all the components ID (paragraphs)
          $query = $this->database->select('node__field_slide_component', 'f');
          $query->fields('f', array('field_slide_component_target_id'));
          $query->condition('entity_id', $slide->id());
          $query->condition('bundle', 'standard_slide');
          $pids = $query->execute()->fetchCol(0);
          if (!empty($pids)) {

            // Load every components (paragraphs)
            $components = Paragraph::loadMultiple($pids);
            $j = 0;
            foreach ($components as $component) {

              // Loop on each component types
              $bundles = \Drupal::entityManager()->getBundleInfo('paragraph');
              foreach ($bundles as $bundle => $bundle_name) {

                // Loop on each fields
                $fields = $this->entityFieldManager->getFieldDefinitions('paragraph', $bundle);
                foreach ($fields as $field_name => $field_definition) {

                  // Get the value of each fields
                  if (substr($field_name, 0, 6) == 'field_' && $component->{$field_name}) {
                    $value = [];
                    if ($component->{$field_name} && count($component->{$field_name}) > 0) {
                      switch ($component->{$field_name}->getFieldDefinition()->getType()) {
                        case 'image' :
                          foreach ($component->{$field_name} as $listitem) {
                            if ($listitem->entity) {
                              $value[] = file_create_url($listitem->entity->getFileUri());
                            }
                          }
                          break;
                        case 'link' :
                          foreach ($component->{$field_name} as $listitem) {
                            if ($listitem->uri) {
                              $value[] = $listitem->uri;
                            }
                          }
                          break;
                        default :
                          foreach ($component->{$field_name} as $listitem) {
                            if (count($listitem->getValue()) > 0) {
                              $value[] = $listitem->getValue()['value'];
                            }
                          }
                          break; 
                      }
                    }
                    if ($value) {
                      $data[$i]['components'][$j][$bundle][$field_name] = $value;
                    }
                  }
                }
              }
              $j++;
            }
          }
          $i++;
        }
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
