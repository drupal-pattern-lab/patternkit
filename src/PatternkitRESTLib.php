<?php

/**
 * Class RESTLib.
 */
class PatternkitRESTLib extends PatternkitDrupalCachedLib {

  /**
   * Fetches all assets for a pattern.
   *
   * @param \PatternkitPattern $pattern
   *   The pattern to use for asset retrieval.
   * @param \PatternkitEditorConfig $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return \PatternkitPattern
   *   The pattern parameter with updated asset references.
   */
  public function fetchPatternAssets(PatternkitPattern $pattern,
    \PatternkitEditorConfig $config) {

    $patternkit_host = variable_get(
      'patternkit_pl_host',
      'http://localhost:9001'
    );

    $url = $patternkit_host . '/api/render/json';
    $result = drupal_http_request(
      $url,
      array(
        'headers' => array('Content-Type' => 'application/json'),
        'data'    => $config->rawJSON,
        'timeout' => 10,
        'method'  => 'POST',
      )
    );

    // @TODO: Request failure handling.

    $pk_obj = json_decode($result->data);
    $subtype = $pattern;
    $dir = "public://patternkit/$subtype/{$config->instance_id}";
    if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
      // @TODO: Failure handling.
      _patternkit_show_error(
        "Unable to create folder ($dir) to contain the pklugins artifacts."
      );
    }

    // Fetch the body html artifact.
    $save_result = file_unmanaged_save_data(
      $pk_obj->body,
      "$dir/manifest.json",
      FILE_EXISTS_REPLACE
    );
    $pk_obj->body = $save_result;

    if ($save_result === FALSE) {
      // @TODO: Failure handling.
      _patternkit_show_error(
        "Unable to create static archive of the JSON pklugins artifact for $subtype."
      );
    }

    $assets = array();

    // Normalize the object for easier processing.
    if (!empty($pk_obj->assets)) {
      $pk_obj->assets->js->early;
      $pk_obj->assets->js->deferred;
      $pk_obj->assets->css->list;
      $pk_obj->assets->css->shared;
    }
    else {
      $pk_obj->assets->js->early = $pk_obj->global_assets->js;
      $pk_obj->assets->js->deferred = $pk_obj->global_assets->footer_js;
      $pk_obj->assets->css->list = $pk_obj->global_assets->css;
      $pk_obj->assets->css->shared = array();
    }

    if (!empty($pk_obj->assets)) {
      $assets['js'] = array_merge(
        $pk_obj->assets->js->early,
        $pk_obj->assets->js->deferred
      );

      $assets['css'] = $pk_obj->assets->css->list;
      // Fetch and update the assets in the shared dependency list.
      if (!empty($pk_obj->assets->css->shared)) {
        foreach ($pk_obj->assets->css->shared as $asset) {
          $assets['css'][] = $asset->src;
        }
      }
    }
    else {
      $assets['js'] = array_merge(
        $pk_obj->global_assets->js,
        $pk_obj->global_assets->footer_js
      );

      $assets['css'] = $pk_obj->global_assets->css;
    }

    foreach ($assets as $asset_type => $urls) {
      foreach ($urls as $asset_url) {
        // If the asset is being loaded with arbitrary scheme, assume external.
        $pos = strpos($asset_url, '//');
        if ($pos !== FALSE && $pos <= 11) {
          continue;
        }

        $save_result = _patternkit_fetch_single_asset($dir, $pk_obj->path, $asset_url);
        $pk_obj->raw_assets[$asset_url] = $save_result;
      }
    }

    // Depending on the type of presentation, we want to fetch the data
    // differently.
    switch ($config->presentation_style) {
      case 'webcomponent':
        _patternkit_fetch_webcomponent_assets($subtype, $config, $pk_obj);
        break;

      case 'html':
        _patternkit_fetch_fragment_assets($subtype, $config, $pk_obj);
        break;

      case 'json':
        break;
    }

    return $pk_obj;
  }

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
   * Returns rendered markup for a provided pattern.
   *
   * @param \PatternkitPattern $pattern
   *   The pattern to render.
   * @param \PatternkitEditorConfig $config
   *   The editor configuration for the pattern.
   *
   * @return string
   *   The rendered pattern HTML.
   */
  public function getRenderedPatternMarkup(PatternkitPattern $pattern,
    PatternkitEditorConfig $config) {
    $subtype = $pattern->subtype;
    if (empty($config['presentation_style']) || empty($config['instance_id'])) {
      return '';
    }
    if ($config['presentation_style'] === 'webcomponent') {
      $subtype = substr($subtype, 3);
      $body = "<$subtype-pattern></$subtype-pattern>";
    }
    else {
      $filename = "public://patternkit/$subtype/{$config['instance_id']}/body.html";
      $body = file_get_contents($filename);
    }
    return $body;
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
  public function getEditor($subtype = NULL, PatternkitEditorConfig $config = NULL) {
    $patternkit_host = variable_get(
      'patternkit_pl_host',
      'http://localhost:9001'
    );
    $url = $patternkit_host . '/schema/editor/' . substr($subtype, 3);

    if ($config !== NULL) {
      $url .= !empty($config->lzstring) ? '?data=' . $config->lzstring : '';
    }
    // @todo Move to external phptemplate.
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
