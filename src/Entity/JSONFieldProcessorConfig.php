<?php

namespace Drupal\json_field_processor\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\search_api\Entity\Index;

/**
 * Defines the json_field_processor_config entity for JSON field processor.
 *
 * This configuration entity stores information about JSON field processor configurations.
 *
 * @ConfigEntityType(
 *   id = "json_field_processor_config",
 *   label = @Translation("JSON Field Processor Configuration"),
 *   admin_permission = "administer json field processor",
 *   handlers = {
 *     "access" = "Drupal\json_field_processor\JSONFieldProcessorConfigAccessController",
 *     "list_builder" = "Drupal\json_field_processor\Controller\JSONFieldProcessorConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\json_field_processor\Form\JSONFieldProcessorConfigAddForm",
 *       "edit" = "Drupal\json_field_processor\Form\JSONFieldProcessorConfigEditForm",
 *       "delete" = "Drupal\json_field_processor\Form\JSONFieldProcessorConfigDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/json_field_processor/manage/{json_field_processor_config}",
 *     "delete-form" = "/admin/config/json_field_processor/manage/{json_field_processor_config}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "field_name",
 *     "json_path",
 *   }
 * )
 */
class JSONFieldProcessorConfig extends ConfigEntityBase {

  /**
   * The json_field_processor_config ID.
   *
   * @var string
   */
  public $id;

  /**
   * The json_field_processor_config UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The json_field_processor_config label.
   *
   * @var string
   */
  public $label;

  /**
   * The name of the field submitted.
   *
   * @var string
   */
  public $field_name;

  /**
   * The JSON path associated with the json_field_processor_config.
   *
   * @var string
   */
  public $json_path;

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Custom logic before deletion.
    $field_name = 'json_field_processor_' . $this->get('field_name');

    // Load the search index.
    $indexes = Index::loadMultiple();
    foreach ($indexes as $index) {
      $fields = $index->getFields();
      if (isset($fields[$field_name])) {
        // Remove the field from the index.
        $index->removeField($field_name);
        $index->save();
      }
    }

    // Call parent delete to perform the actual deletion.
    parent::delete();
  }

}
