<?php

namespace Drupal\json_field_processor\Plugin\search_api\processor;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use JmesPath\Env as JmesPath;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the item's view count to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "json_field_processor",
 *   label = @Translation("Json Field Data"),
 *   description = @Translation("Add index for JSON data of a node or media item"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   hidden = false,
 * )
 */
class JSONFieldProcessor extends ProcessorPluginBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Theme settings config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * {@inheritdoc}
   */

  /**
   * Class property to store the results in a key-value array.
   */
  protected $json_field_configurations = [];

  protected $json_field_name = [];

  /**
   * Load the relationship types from the database.
   * Populates $json_field_configurations with field_name => json_path pairs.
   */
  protected function loadJSONFieldConfigurations() {
    // Fetch the results from the database.
    $json_field_processor_config = \Drupal::entityTypeManager()->getStorage('json_field_processor_config')->loadMultiple();
    foreach ($json_field_processor_config as $submission) {
      $label = $submission->label();
      $field_name = $submission->field_name;
      $json_path = $submission->json_path;
      // Process each submission as needed.
      $this->json_field_configurations[$field_name] = $json_path;
      $this->json_field_name[$field_name] = $label;
    }
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->httpClient = $container->get('http_client');
    $processor->configFactory = $container->get('config.factory');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    $this->loadJSONFieldConfigurations();

    if (!$datasource) {
      foreach ($this->json_field_configurations as $field_name => $json_path) {
        $definition = [
          'label' => $this->t('Field: @label', ['@label' => $json_path]),
          'description' => $this->t('Json Data of Islandora Site to be indexed to Solr'),
          'type' => 'string',
          'processor_id' => $this->getPluginId(),
          'is_list' => TRUE,
        ];
        $properties['json_field_processor_' . $field_name] = new ProcessorProperty($definition);
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $datasourceId = $item->getDatasourceId();

    // Handle node entities.
    foreach ($this->json_field_configurations as $field_name => $json_path) {
      $json_field = $this->json_field_name[$field_name];
      if ($datasourceId == 'entity:node') {
        $node = $item->getOriginalObject()->getValue();
        if ($node instanceof Node && $node->hasField($json_field)) {
          $json_data = $node->get($json_field)->value;
          $data = json_decode($json_data, TRUE);
          $result = JmesPath::search($json_path, $data);
          if ($result !== NULL) {
            $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'json_field_processor_' . $field_name);
            foreach ($fields as $field) {
              $field->addValue($result);
            }
          }
        }
      }
      // Handle media entities.
      elseif ($datasourceId == 'entity:media') {
        \Drupal::logger('json_field_processor')->notice('Media entity detected');
        $media = $item->getOriginalObject()->getValue();
        if ($media instanceof Media && $media->hasField($json_field)) {
          $json_data = $media->get($json_field)->value;
          $data = json_decode($json_data, TRUE);
          $result = JmesPath::search($json_path, $data);
          if ($result !== NULL) {
            $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'json_field_processor_' . $field_name);
            foreach ($fields as $field) {
              $field->addValue($result);
            }
          }
        }
      }
    }
  }

}
