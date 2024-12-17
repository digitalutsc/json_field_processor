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
class JSONFieldProcessor extends ProcessorPluginBase
{

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
    protected function loadJSONFieldConfigurations()
    {
        // Fetch the results from the database.
        $json_field_processor_config = \Drupal::entityTypeManager()->getStorage('json_field_processor_config')->loadMultiple();
        foreach ($json_field_processor_config as $submission) {
            $label = $submission->label();
            $field_name = $submission->id();
            $json_path = $submission->json_path;
            // Process each submission as needed.
            $this->json_field_configurations[$field_name] = $json_path;
            $this->json_field_name[$field_name] = $label;
        }
    }

    /**
     *
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        /**
   * @var static $processor 
*/
        $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
        $processor->httpClient = $container->get('http_client');
        $processor->configFactory = $container->get('config.factory');
        return $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyDefinitions(DatasourceInterface $datasource = null)
    {
        $properties = [];
        $this->loadJSONFieldConfigurations();

        if (!$datasource) {
            foreach ($this->json_field_configurations as $field_name => $json_path) {
                $definition = [
                'label' => $this->t('Field: @label', ['@label' => $json_path]),
                'description' => $this->t('Json Data of Islandora Site to be indexed to Solr'),
                'type' => 'string',
                'processor_id' => $this->getPluginId(),
                'is_list' => true,
                ];
                $properties['json_field_processor_' . $field_name] = new ProcessorProperty($definition);
            }
        }

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function addFieldValues(ItemInterface $item)
    {
        $datasourceId = $item->getDatasourceId();
        $entity = $item->getOriginalObject()->getValue();

        // Process JSON fields for supported entity types.
        if (in_array($datasourceId, ['entity:node', 'entity:media'])) {
            foreach ($this->json_field_configurations as $field_name => $json_path) {
                $this->processJsonField($entity, $item, $field_name, $json_path);
            }
        }
    }

    /**
     * Process JSON field for the given entity and field configuration.
     *
     * @param \Drupal\Core\Entity\EntityInterface   $entity
     *   The entity being processed.
     * @param \Drupal\search_api\Item\ItemInterface $item
     *   The search API item.
     * @param string                                $field_name
     *   The machine name of the field configuration.
     * @param string                                $json_path
     *   The JSON path to process.
     */
    private function processJsonField($entity, ItemInterface $item, $field_name, $json_path)
    {
        $json_field = $this->json_field_name[$field_name];

        if ($entity->hasField($json_field)) {
            $json_data = $entity->get($json_field)->value;
            if ($json_data === null) {
                return;
            }
            $data = json_decode($json_data, true);
            $result = JmesPath::search($json_path, $data);

            if (is_array($result)) {
                $result = $this->flattenJson($result);
            }

            if ($result !== null) {
                $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), null, 'json_field_processor_' . $field_name);
                foreach ($fields as $field) {
                    $field->addValue($result);
                }
            }
        }
    }

    /**
     * Flattens a JSON structure into a "key:value, key:value" string.
     *
     * @param  mixed  $data      The JSON data to flatten.
     * @param  string $parentKey The parent key for nested elements (used recursively).
     * @return string The flattened key:value pairs.
     */
    private function flattenJson($data, $parentKey = '')
    {
        $result = [];

        foreach ($data as $key => $value) {
            // Build the full key for nested elements.
            $fullKey = $parentKey ? $parentKey . '.' . $key : $key;

            if (is_array($value)) {
                // Recursively process arrays and convert to string.
                $result[] = $fullKey . ': [' . $this->flattenJson($value, '') . ']';
            } elseif (is_object($value)) {
                // Recursively process nested objects.
                $result[] = $this->flattenJson($value, $fullKey);
            } else {
                // Handle scalar values (strings, numbers, etc.) and avoid extra escaping.
                if (is_string($value)) {
                    // Ensure no extra escaping by directly adding the value.
                    $result[] = $fullKey . ': "' . addslashes($value) . '"';
                } else {
                    $result[] = $fullKey . ': ' . $value;
                }
            }
        }

        return implode(', ', $result);
    }


}
