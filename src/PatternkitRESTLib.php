<?php

/**
 * Class RESTLib.
 */
class PatternkitRESTLib extends PatternkitDrupalCachedLib {

  /**
   * Returns the id of the Pattern Library.
   *
   * @return string
   *   The Pattern Library id.
   */
  public function getId() {
    return 'patternkit_rest_api';
  }

  /**
   * Returns the title of the Pattern Library.
   *
   * @return string
   *   The Pattern Library title.
   */
  public function getTitle() {
    return 'Patternkit REST API';
  }

  public function getEditor($subtype = NULL, $config = NULL) {
    $patternkit_host = variable_get(
      'patternkit_pl_host',
      'http://localhost:9001'
    );
    $url = $patternkit_host . '/schema/editor/' . substr($subtype, 3);

    if (!empty($conf['instance_config'])) {
      $config = json_decode($conf['instance_config']);
      $url .= !empty($config->lzstring) ? "?data=" . $config->lzstring : '';
    }
    $markup = <<< HTML
<iframe id='schema-editor-iframe' width='100%' height='1000px' src='$url'></iframe>
<script>
  // Enlarge the ctools modal to make it easier to work with the iframe.
  jQuery('.ctools-modal-content').animate({width:'100%', height:'100%'});
  jQuery('#modalContent').animate({'width': '100%', 'left':'0px', 'top':'0px'});
  jQuery('#modal-content').animate({'width': '100%', 'height': '100%'});
  
  var schemaDataSaved = false;
  // Respond to data events.
  window.addEventListener('message', function(event) {
    if (event.data.name && event.data.name === 'saveData') {
      var configObject = JSON.stringify(event.data);
      document.getElementById('schema_instance_config').value = configObject;
      console.log('config object', configObject);
      schemaDataSaved = true;
      jQuery('#patternkit-patternkit-content-type-edit-form').trigger('submit');
    }
  });
  
  document.getElementById('patternkit-patternkit-content-type-edit-form').onsubmit = function(){
    if (schemaDataSaved === false) {
      var frame = document.getElementById('schema-editor-iframe');
      frame.contentWindow.postMessage('sendSaveData', '*');
      return false;
    }
  };
</script>
HTML;

    return $markup;
  }

  /**
   * Utility function to get all Patternkit module metadata.
   *
   * @return array
   *   Array of metadata objects found.
   */
  protected function getRawMetadata() {
    $patternkit_host = variable_get('patternkit_pl_host', 'http://localhost:9001');

    $patterns = drupal_http_request(
      $patternkit_host . '/api/patterns',
      array(
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 10,
      )
    );
    if (empty($patterns)
      || !empty($patterns->error)
      || (int) $patterns->code !== 200) {
      _patternkit_show_error(
        'Patternkit failed to load metadata from service (%service_uri): %error',
        array(
          '%service_uri' => $patternkit_host . '/api/patterns',
          '%error'       => !empty($patterns->error) ? $patterns->error : $patterns->code,
        )
      );
      return array();
    }
    $metadata = (array) json_decode($patterns->data);
    foreach ($metadata as $subtype => $pattern) {
      $pattern->library = &$this;
      $metadata[$subtype] = $pattern;
    }
    return $metadata;
  }

}
