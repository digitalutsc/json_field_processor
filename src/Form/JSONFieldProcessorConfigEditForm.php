<?php

namespace Drupal\json_field_processor\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class JSONFieldProcessorConfigEditForm.
 *
 * Provides the edit form for the json_field_processor_config entity.
 *
 * @ingroup json_field_processor
 */
class JSONFieldProcessorConfigEditForm extends JSONFieldProcessorConfigFormBase
{

    /**
     * Returns the actions provided by this form.
     *
     * For the edit form, we only need to change the text of the submit button.
     *
     * @param array                                $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   An associative array containing the current state of the form.
     *
     * @return array
     *   An array of supported actions for the current entity form.
     */
    protected function actions(array $form, FormStateInterface $form_state)
    {
        // Get the default actions from the base class.
        $actions = parent::actions($form, $form_state);

        // Change the submit button text to reflect the "Update Configuration" action.
        $actions['submit']['#value'] = $this->t('Update Configuration');

        // Return the modified actions array.
        return $actions;
    }

}
