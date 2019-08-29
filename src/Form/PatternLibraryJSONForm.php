<?php

namespace Drupal\patternkit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for configuring the JSON Library Plugin.
 */
class PatternLibraryJSONForm extends ConfigFormBase {

  /**
   * Settings identifier.
   *
   * @var string
   */
  public const SETTINGS = 'patternkit.settings';

  /**
   * Array of themes supported by the JSON Schema Editor.
   *
   * @var array
   *
   * @see https://github.com/json-editor/json-editor
   *
   * @todo Move to yml config.
   *
   * @todo Materialize doesn't support the Shadow dom: figure out encapsulation.
   */
  public const THEMES = [
    'barebones' => [],
    'html' => [],
    'jqueryui' => [
      'css' => 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
    ],
    'bootstrap2' => [
      'css' => 'https://netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css',
    ],
    'bootstrap3' => [
      'css' => 'https://netdna.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
    ],
    'bootstrap4' => [
      'css' => 'library://patternkit/patternkit.jsoneditor.theme.bootstrap4/css',
    ],
    'foundation3' => [
      'css' => 'https://cdnjs.cloudflare.com/ajax/libs/foundation/3.2.5/stylesheets/foundation.css',
    ],
    'foundation4' => [
      'css' => 'https://cdnjs.cloudflare.com/ajax/libs/foundation/4.3.2/css/foundation.min.css',
    ],
    'foundation5' => [
      'css' => 'https://cdnjs.cloudflare.com/ajax/libs/foundation/5.5.3/css/foundation.min.css',
    ],
    'foundation6' => [
      'css' => 'https://cdnjs.cloudflare.com/ajax/libs/foundation/6.2.4/foundation.min.css',
    ],
  ];

  /**
   * Array of icons supported by the JSON Schema Editor.
   *
   * @var array
   *
   * @see https://github.com/json-editor/json-editor
   *
   * @todo Move to yml config.
   */
  public const ICONS = [
    'none' => '',
    'jqueryui' => '',
    'bootstrap2' => '',
    'bootstrap3' => '',
    'foundation2' => 'https://cdnjs.cloudflare.com/ajax/libs/foundicons/2.0/stylesheets/general_foundicons.css',
    'foundation3' => 'https://cdnjs.cloudflare.com/ajax/libs/foundicons/3.0.0/foundation-icons.css',
    'fontawesome3' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.2.1/css/font-awesome.css',
    'fontawesome4' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.css',
    'fontawesome5' => 'https://use.fontawesome.com/releases/v5.6.1/css/all.css',
  ];

  /**
   * Implements buildForm().
   *
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) :array {
    $config = $this->config(static::SETTINGS);

    $themes = array_keys(static::THEMES);
    $form['patternkit_json_editor_theme'] = array(
      '#type' => 'select',
      '#title' => t('Select the JSON Editor Theme'),
      '#options' => array_combine($themes, $themes),
      '#default_value' => $config->get('patternkit_json_editor_theme'),
    );

    $icons = array_keys(static::ICONS);
    $form['patternkit_json_editor_icons'] = array(
      '#type' => 'select',
      '#title' => t('Select the icons to be used with the editor'),
      '#options' => array_combine($icons, $icons),
      '#default_value' => $config->get('patternkit_json_editor_icons'),
    );

    $form['patternkit_json_editor_css'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to the editor theme CSS'),
      '#description' => t('Enter a comma-separated list of additional CSS to include.'),
      '#default_value' => $config->get('patternkit_json_editor_css'),
    );

    $form['patternkit_json_editor_js'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to the editor theme JS'),
      '#description' => t('Entter a comma-separated list of additional JS to include.'),
      '#default_value' => $config->get('patternkit_json_editor_js'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() :array {
    return [static::SETTINGS];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() :string {
    return 'patternkit_json_editor_config';
  }

}
