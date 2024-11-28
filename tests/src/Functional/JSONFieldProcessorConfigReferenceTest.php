<?php

namespace Drupal\Tests\json_field_processor\Functional;

use Drupal\Core\Url;
use Drupal\json_field_processor\Entity\JSONFieldProcessorConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensure Config entities can be used in entity_reference fields.
 *
 * @group config_entity_example
 * @group examples
 *
 * @ingroup config_entity_example
 */
class JSONFieldProcessorConfigReferenceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['config_entity_example', 'node', 'field_ui'];

  /**
   * {@inheritdoc}
   *
   * We use the minimal profile because otherwise local actions aren't placed in
   * a block anywhere.
   */
  protected $profile = 'minimal';

  /**
   * Ensure we can use json_field_processor_config entities as reference fields.
   */
  public function testEntityReference() {
    $assert = $this->assertSession();

    // Create a new content type for testing.
    $type = $this->createContentType();

    // Log in as a user with permissions to create content and administer fields.
    $this->drupalLogin($this->createUser([
      'create ' . $type->id() . ' content',
      'administer node fields',
    ]));

    // Go to the "manage fields" section of a content entity.
    $this->drupalGet('admin/structure/types/manage/' . $type->id() . '/fields');
    $assert->statusCodeEquals(200);

    // Click on the "add field" button.
    $this->clickLink('Create a new field');

    // Select "Reference" as the field type and choose "other".
    // Add a label and click continue.
    $this->submitForm([
      'new_storage_type' => 'reference',
    ], 'Continue');
    $this->submitForm([
      'entity_reference' => 'entity_reference',
      'label' => 'json_field_processor_config Reference',
    ], 'Continue');
    // Get the current page HTML.
    $html = $this->getSession()->getPage()->getHtml();

    // Print the page HTML to debug what is rendered.
    print('<pre>' . htmlspecialchars($html) . '</pre>');
    $this->submitForm([
      'settings-handler-settings-target-bundles-islandora-object' => $type->id(),
    ], "Save settings");

    $assert->statusCodeEquals(200);

    // Select "json_field_processor_config" as the target type.
    $this->submitForm([
      'settings[target_type]' => 'json_field_processor_config',
    ], 'Save field settings');
    $assert->statusCodeEquals(200);

    // Load a json_field_processor_config entity to reference.
    $json_field_processor_config = JSONFieldProcessorConfig::loadMultiple();
    // Get the first available config entity.
    $json_field_processor_config = reset($json_field_processor_config);

    // Create a new node of the created content type.
    $this->drupalGet(Url::fromRoute('node.add', ['node_type' => $type->id()]));
    $this->submitForm([
      'title[0][value]' => 'Test Node Title',
      'field_json_field_processor_config_reference[0][target_id]' => $json_field_processor_config->id(),
    // Ensure this button is correctly targeted.
    ], 'Save');
    $assert->statusCodeEquals(200);

    // Verify that the json_field_processor_config label is present in the node.
    $assert->pageTextContains($json_field_processor_config->label());
  }

}
