<?php

namespace Drupal\json_field_processor\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JSONFieldProcessorConfigFormBase.
 *
 * Typically, we need to build the same form for both adding a new entity,
 * and editing an existing entity. Instead of duplicating our form code,
 * we create a base class. Drupal never routes to this class directly,
 * but instead through the child classes of JSONFieldProcessorConfigAddForm and JSONFieldProcessorConfigEditForm.
 *
 * @ingroup json_field_processor
 */
class JSONFieldProcessorConfigFormBase extends EntityForm {

  /**
   * An entity query factory for the json_field_processor_config entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Construct the JSONFieldProcessorConfigFormBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   An entity query factory for the json_field_processor_config entity type.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * Factory method for JSONFieldProcessorConfigFormBase.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return \Drupal\json_field_processor\Form\JSONFieldProcessorConfigFormBase
   *   The form object.
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('entity_type.manager')->getStorage('json_field_processor_config'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the json_field_processor_config add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    // Drupal provides the entity to us as a class variable. If this is an
    // existing entity, it will be populated with existing values as class
    // variables. If this is a new entity, it will be a new object with the
    // class of our entity. Drupal knows which class to call from the
    // annotation on our JSONFieldProcessorConfig class.
    $json_field_processor_config = $this->entity;

    // Build the form.
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Name'),
      '#maxlength' => 255,
      '#default_value' => $json_field_processor_config->field_name,
      '#required' => TRUE,
    ];
    // Ensure the machine name depends on the field name.
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#maxlength' => 128,
      '#default_value' => $json_field_processor_config->id(),
      '#machine_name' => [
        // Use 'field_name' as the source for the machine name.
        'source' => ['field_name'],
        // Uniqueness check.
        'exists' => [$this, 'exists'],
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores.',
      ],
      // Disable editing for existing configurations.
      '#disabled' => !$json_field_processor_config->isNew(),
    ];
    $form['json_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JSON Path'),
      '#maxlength' => 255,
      '#default_value' => $json_field_processor_config->json_path,
      '#required' => TRUE,
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Json Field Machine Name'),
      '#maxlength' => 255,
      '#default_value' => $json_field_processor_config->label(),
      '#required' => TRUE,
    ];

    // Return the form.
    return $form;
  }

  /**
   * Checks for an existing json_field_processor_config.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new json_field_processor_config entity query.
    $query = $this->entityStorage->getQuery();

    // Query the entity ID to see if it's in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->accessCheck()
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * To set the submit button text, we need to override actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actions from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Add code here to validate your config entity's form elements.
    // Nothing to do here.
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * Saves the entity. This is called after submit() has built the entity from
   * the form values. Do not override submit() as save() is the preferred
   * method for entity form controllers.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    // EntityForm provides us with the entity we're working on.
    $json_field_processor_config = $this->getEntity();

    // Drupal already populated the form values in the entity object. Each
    // form field was saved as a public variable in the entity class. PHP
    // allows Drupal to do this even if the method is not defined ahead of
    // time.
    $status = $json_field_processor_config->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $json_field_processor_config->toUrl();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addMessage($this->t('JSON Field Processor Configuration %label has been updated.', ['%label' => $json_field_processor_config->label()]));
      $this->logger('contact')->notice('JSON Field Processor Configuration %label has been updated.', ['%label' => $json_field_processor_config->label(), 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('JSON Field Processor Configuration %label has been added.', ['%label' => $json_field_processor_config->label()]));
      $this->logger('contact')->notice('JSON Field Processor Configuration %label has been added.', ['%label' => $json_field_processor_config->label(), 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.json_field_processor_config.list');

    return $status;
  }

}
