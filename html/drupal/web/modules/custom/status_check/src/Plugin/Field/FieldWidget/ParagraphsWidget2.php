<?php

namespace Drupal\status_check\Plugin\Field\FieldWidget;

use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Plugin implementation of the 'entity_reference_revisions paragraphs' widget.
 *
 * @FieldWidget(
 *   id = "paragraphs2",
 *   label = @Translation("Paragraphs EXPERIMENTAL2"),
 *   description = @Translation("An experimental paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsWidget2 extends ParagraphsWidget {

  /**
   * Builds dropdown button for adding new paragraph.
   *
   * @return array
   *   The form element array.
   */
  protected function buildButtonsAddMode() {
    $options = $this->getAccessibleOptions();
    $add_mode = $this->getSetting('add_mode');
    $paragraphs_type_storage = \Drupal::entityTypeManager()->getStorage('paragraphs_type');

    // Build the buttons.
    $add_more_elements = [];
    foreach ($options as $machine_name => $label) {
      $button_key = 'add_more_button_' . $machine_name;
      $add_more_elements[$button_key] = $this->expandButton([
        '#type' => 'submit',
        '#name' => $this->fieldIdPrefix . '_' . $machine_name . '_add_more',
        '#value' => $add_mode == 'modal' ? $label : $this->t('@type', ['@type' => $label]),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [array_merge($this->fieldParents, [$this->fieldDefinition->getName(), 'add_more'])],
        '#submit' => [[get_class($this), 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'addMoreAjax'],
          'wrapper' => $this->fieldWrapperId,
        ],
        '#bundle_machine_name' => $machine_name,
      ]);

      if ($add_mode === 'modal' && $icon_url = $paragraphs_type_storage->load($machine_name)->getIconUrl()) {
        $add_more_elements[$button_key]['#attributes']['style'] = 'background-image: url(' . $icon_url . ');';
      }
    }

    // Determine if buttons should be rendered as dropbuttons.
    if (count($options) > 1 && $add_mode == 'dropdown') {
      $add_more_elements = $this->buildDropbutton($add_more_elements);
      $add_more_elements['#suffix'] = $this->t('to %type', ['%type' => $this->fieldDefinition->getLabel()]);
    }
    elseif ($add_mode == 'modal') {
      $this->buildModalAddForm($add_more_elements);
      $add_more_elements['add_modal_form_area']['#suffix'] = $this->t('to %type', ['%type' => $this->fieldDefinition->getLabel()]);
    }
    $add_more_elements['#weight'] = 1;

    return $add_more_elements;
  }

}
