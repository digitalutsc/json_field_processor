<?php

namespace Drupal\Tests\json_field_processor\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the Config Entity Example module.
 *
 * @group config_entity_example
 * @group examples
 *
 * @ingroup config_entity_example
 */
class JSONFieldProcessorConfigEntityTest extends BrowserTestBase
{

    /**
     * {@inheritdoc}
     */
    protected $defaultTheme = 'stark';

    /**
     * Modules to enable.
     *
     * @var array
     */
    protected static $modules = ['json_field_processor'];

    /**
     * The installation profile to use with this test.
     *
     * We need the 'minimal' profile in order to make sure the Tool block is
     * available.
     *
     * @var string
     */
    protected $profile = 'minimal';

    /**
     * Various functional tests of the Config Entity Example module.
     *
     * 1) Verify that the default json_field_processor_config entity was created when the module was installed.
     *
     * 2) Verify that permissions are applied to the various defined paths.
     *
     * 3) Verify that we can manage entities through the user interface.
     *
     * 4) Verify that the entity we add can be re-edited.
     *
     * 5) Verify that the label is shown in the list.
     */
    public function testConfigEntityExample()
    {
        $assert = $this->assertSession();

        // 2) Verify that permissions are applied to the various defined paths.
        // Define some paths. Since the default json_field_processor_config entity is defined, we can use it
        // in our management paths.
        $forbidden_paths = [
        '/admin/json-field-processor/json_field_processor_config',
        '/admin/json-field-processor/json_field_processor_config/add',
        ];
        // Check each of the paths to make sure we don't have access. At this point
        // we haven't logged in any users, so the client is anonymous.
        foreach ($forbidden_paths as $path) {
            $this->drupalGet($path);
            $assert->statusCodeEquals(403);
        }

        // Create a user with no permissions.
        $without_perms_user = $this->drupalCreateUser();
        $this->drupalLogin($without_perms_user);
        // Should be the same result for forbidden paths, since the user needs
        // special permissions for these paths.
        foreach ($forbidden_paths as $path) {
            $this->drupalGet($path);
            // Again 404?
            $assert->statusCodeEquals(403);
        }

        // Create a user who can administer json_field_processor_configs.
        $admin_user = $this->drupalCreateUser(['administer json field processor']);
        $this->drupalLogin($admin_user);
        // Forbidden paths are no longer forbidden.
        foreach ($forbidden_paths as $path) {
            $this->drupalGet($path);
            $assert->statusCodeEquals(200);
        }

        // Now that we have the admin user logged in, check the menu links.
        // $this->drupalGet('/admin/config');
        // $assert->linkByHrefExists('/admin/json-field-processor/json_field_processor_config');.
        // 3) Verify that we can manage entities through the user interface.
        // We still have the admin user logged in, so we'll create, update, and
        // delete an entity.
        // Go to the list page.
        $this->drupalGet('/admin/json-field-processor/json_field_processor_config');
        $this->clickLink('Add JSON Field Processor Configuration');
        $json_field_processor_config_machine_name = 'json_field_processor_config_01';
        $form_values = [
        'field_name' => 'Test Field Name',
        'id' => $json_field_processor_config_machine_name,
        'json_path' => 'data.test',
        'label' => 'Test Label',
        ];

        // Submit the form with the required values.
        $this->submitForm($form_values, 'Create JSON Field Processor Configuration');
        // 4) Verify that our json_field_processor_config appears when we edit it.
        // Step 4: Verify that the config entity appears when we edit it.
        $this->drupalGet('/admin/json-field-processor/json_field_processor_config/' . $json_field_processor_config_machine_name . '/edit');
        // Ensure label is pre-filled correctly.
        $assert->fieldValueEquals('label', 'Test Label');
        // Verify field_name value.
        $assert->fieldValueEquals('field_name', 'Test Field Name');
        // Verify JSON path value.
        $assert->fieldValueEquals('json_path', 'data.test');
        // Check that the edit button exists.
        $assert->buttonExists('Update Configuration');

        // Step 5: Verify that the label and machine name appear in the list.
        $this->drupalGet('/admin/json-field-processor/json_field_processor_config');
        // Ensure label is shown.
        $assert->pageTextContains('Test Label');
        // Ensure json path is shown.
        $assert->pageTextContains('data.test');
        // Ensure field name is shown.
        $assert->pageTextContains('Test Field Name');

        // Add another configuration.
        $this->clickLink('Add JSON Field Processor Configuration');
        $robby_machine_name = 'robby_json_field_processor_config';
        $robby_label = 'Robby JSON Field Processor Configuration Label';
        $form_values = [
        'field_name' => 'Robby Field Name',
        'id' => $robby_machine_name,
        'json_path' => 'data.robby',
        'label' => $robby_label,
        ];
        $this->submitForm($form_values, 'Create JSON Field Processor Configuration');
        $this->drupalGet('/admin/json-field-processor/json_field_processor_config');
        $assert->pageTextContains('Robby Field Name');
        $assert->pageTextContains('data.robby');
        $assert->pageTextContains($robby_label);

        // Step 6: Verify links on the listing page.
        $this->drupalGet(Url::fromRoute('entity.json_field_processor_config.list'));
        $assert->linkByHrefExists('/admin/json-field-processor/json_field_processor_config/add');
        $assert->linkByHrefExists('/admin/json-field-processor/json_field_processor_config/' . $robby_machine_name);
        $assert->linkByHrefExists('/admin/json-field-processor/json_field_processor_config/' . $robby_machine_name . '/delete');

        // Verify links on the Add JSON Field Processor Configuration page.
        $this->drupalGet('/admin/json-field-processor/json_field_processor_config/add');
        // Verify links on the Edit JSON Field Processor Configuration page.
        $this->drupalGet('/admin/json-field-processor/json_field_processor_config/' . $robby_machine_name . '/edit');
        $assert->linkByHrefExists('/admin/json-field-processor/json_field_processor_config/' . $robby_machine_name . '/delete');

        // Step 7: Verify deletion workflow.
        $this->drupalGet('/admin/json-field-processor/json_field_processor_config/' . $robby_machine_name . '/delete');
        // Verify the delete button exists.
        $assert->buttonExists('Delete');

        // Step 8: Verify the cancel button on the delete page redirects to the list page.
        $cancel_button = $this->xpath(
            '//a[@id="edit-cancel" and contains(@href, :path)]',
            [':path' => '/admin/json-field-processor/json_field_processor_config']
        );
        $this->assertEquals(count($cancel_button), 1, 'Found cancel button linking to list page.');

        // Step 9: Reserved keyword test.
        //  $this->drupalGet(Url::fromRoute('entity.json_field_processor_config.add_form'));
        //  $this->submitForm([
        //      'field_name' => 'Custom Field Name',
        //      'id' => 'custom',
        //      'json_path' => 'data.custom',
        //      'label' => 'Custom',
        //  ], 'Create JSON Field Processor Configuration');
        //  $assert->pageTextContains('Additionally, it cannot be the reserved word "custom".');.
    }

}
