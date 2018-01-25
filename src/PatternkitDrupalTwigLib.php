<?php

/**
 * Class PatternkitDrupalTwigLib.
 */
class PatternkitDrupalTwigLib extends PatternkitDrupalCachedLib {

  private $id;

  private $path;

  private $title;

  /**
   * PatternkitDrupalTwigLib constructor.
   *
   * @param string $title
   *   The name of the library.
   * @param string $path
   *   The path to the library.
   */
  public function __construct($title, $path) {
    $this->title = $title;
    $this->id = preg_replace('/[^a-z0-9]+/', '_', strtolower($title));
    $this->path = $path;
  }

  /**
   * Returns the id of the Pattern Library.
   *
   * @return string
   *   The Pattern Library id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Returns the title of the Pattern Library.
   *
   * @return string
   *   The Pattern Library title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Returns renderable data or markup for a pattern editor.
   *
   * @param string|null $subtype
   *   If specified, return an editor customized for this subtype.
   * @param \PatternkitEditorConfig $config
   *   Optional configuration settings for the editor.
   *
   * @return mixed
   *   The renderable pattern editor.
   */
  public function getEditor($subtype = NULL,
    PatternkitEditorConfig $config = NULL) {
    $pattern = $this->getMetadata($subtype);
    if ($subtype === NULL || empty($pattern)) {
      return t('Unable to lookup the schema for this subtype.');
    }

    $schema_json = drupal_json_encode($pattern->schema);
    $starting_json = $config !== NULL ? drupal_json_encode($config->fields)
      : $config;
    // @todo Move to own JS file & Drupal Settings config var.
    $markup = <<<HTML
<div id="editor_holder"></div>
<script type="text/javascript">
  var data = {};
  data.schema = $schema_json;
  data.starting = $starting_json;
  // Enlarge the ctools modal to make it easier to work with the iframe.
  jQuery('.ctools-modal-content').animate({width:'100%', height:'100%'});
  jQuery('#modalContent').animate({'width': '100%', 'left':'0px', 'top':'0px'});
  jQuery('#modal-content').animate({'width': '100%', 'height': '100%'});

  if (data.starting !== null && data.starting.name) {
    JSONEditor.defaults.options.startval = data.starting;
  }
  
  // Initialize the editor with a JSON schema
  var editor = new JSONEditor(
    document.getElementById('editor_holder'), {
      schema:            data.schema,
      theme:             'jqueryui',
      iconlib:           'jqueryui',
      keep_oneof_values: false,
      ajax:              true
    }
  );
  JSONEditor.plugins.sceditor.emoticonsEnabled = false;
  
  editor.on('change', function() {
    var config_string = JSON.stringify(editor.getValue());
    document.getElementById('schema_instance_config').value = config_string;
    
  });
</script>
HTML;

    // @todo Toggle based on developer settings.
    drupal_add_js(drupal_get_path('module', 'patternkit')
      . '/js/jsoneditor.js');

    return $markup;
  }

  /**
   * Utility function to get all Patternkit module metadata.
   *
   * @return array
   *   Array of metadata objects found.
   */
  protected function getRawMetadata() {
    $id = $this->getId();
    $it = new RecursiveDirectoryIterator($this->path);
    $filter = array('json');
    $metadata = array();
    /** @var \SplFileInfo $file */
    foreach (new RecursiveIteratorIterator($it) as $file) {
      if (!$file->isFile()) {
        continue;
      }
      $file_path = $file->getPath();
      $dirs = explode(DIRECTORY_SEPARATOR, $file_path);
      // All JSON schema must be in an 'api' folder at this time.
      // @todo Add support for custom setups.
      $num_dirs = count($dirs);
      if ($num_dirs < 2
        || array_pop($dirs) !== 'api') {
        continue;
      }
      $file_ext = $file->getExtension();
      if (!in_array(strtolower($file_ext), $filter, TRUE)) {
        continue;
      }
      if ($file_contents = file_get_contents($file)) {
        $pattern = new PatternkitPattern(json_decode($file_contents));
        $pattern->library = &$this;
        $file_basename = $file->getBasename('.json');
        $subtype = "pk_$file_basename";
        $pattern->subtype = $subtype;
        $pattern->url = url("patternkit/ajax/$id/$subtype/schema");
        $twig_file = $file_path
          . DIRECTORY_SEPARATOR . $file_basename . '.twig';
        if (file_exists($twig_file)) {
          $pattern->twig = file_get_contents($twig_file);
        }
        $metadata[$file_basename] = $pattern;
      }
    }
    foreach ($metadata as $pattern_type => $pattern) {
      // Replace any $ref links with relative paths.
      if (!isset($pattern->schema->properties)) {
        continue;
      }
      $pattern->schema->properties = _patternkit_schema_ref($pattern->schema->properties,
        $metadata);
      $metadata[$pattern_type] = $pattern;
    }
    return $metadata;
  }

}
