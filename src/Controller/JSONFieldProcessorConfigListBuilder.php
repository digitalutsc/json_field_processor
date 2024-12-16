<?php

namespace Drupal\json_field_processor\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of configuration entities for JSON Field Processor.
 *
 * This controller lists `json_field_processor_config` entities in a tabular form.
 * We override `buildHeader()` and `buildRow()` to control the columns and rows
 * of the table display.
 *
 * Drupal locates this list controller using the "list" entry in the entity
 * type's annotation. The route for this listing is defined in
 * json_field_processor.routing.yml, where "_entity_list" points to this entity type ID.
 *
 * @ingroup json_field_processor
 */
class JSONFieldProcessorConfigListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    protected function getModuleName()
    {
        return 'json_field_processor';
    }

    /**
     * Builds the header row for the entity listing.
     *
     * @return array
     *   A render array structure of header strings.
     */
    public function buildHeader()
    {
        $header['label'] = $this->t('JSON Field Processor Configuration');
        $header['field_name'] = $this->t('Field Name');
        $header['json_path'] = $this->t('JSON Path');
        $header['json_field_name'] = $this->t('JSON Field Name');
        return $header + parent::buildHeader();
    }

    /**
     * Builds a row for an entity in the entity listing.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity for which to build the row.
     *
     * @return array
     *   A render array of the table row for displaying the entity.
     */
    public function buildRow(EntityInterface $entity)
    {
        $row['label'] = $entity->label();
        $row['field_name'] = $entity->get('field_name');
        $row['json_path'] = $entity->get('json_path');
        $row['json_field_name'] = $entity->get('label');

        return $row + parent::buildRow($entity);
    }

    /**
     * Adds a descriptive message above the entity list.
     *
     * Typically, there's no need to override render(). We override here
     * to add introductory markup before the table listing.
     *
     * @return array
     *   Renderable array including description and table listing.
     */
    public function render()
    {
        $build = [
        '#markup' => $this->t('Manage and view JSON Field Processor Configurations.'),
        ];
        $build[] = parent::render();
        return $build;
    }

}
