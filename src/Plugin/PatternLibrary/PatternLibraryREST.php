<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\patternkit\Annotation\PatternLibrary;
use Drupal\patternkit\Pattern;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibraryParserInterface;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @PatternLibrary(
 *   id = "rest",
 * )
 */
class PatternLibraryREST extends PatternLibraryPluginDefault implements ContainerFactoryPluginInterface {

  /** @var \Drupal\Core\Config\ConfigFactoryInterface */
  protected $configFactory;

  /** @var \Drupal\Component\Serialization\SerializationInterface */
  protected $serializer;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a PatternRESTCollectionRenderer.
   *
   * @param mixed $root
   *   The application root path(s).
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The Drupal JSON Serializer.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\patternkit\PatternLibraryParserInterface $parser
   *   The REST Pattern Library Parser.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A Drupal Config Factory.
   * @param array $configuration
   *   Additional library configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * {@inheritDoc}
   */
  public function __construct(
    $root,
    SerializationInterface $serializer,
    StateInterface $state,
    PatternLibraryParserInterface $parser,
    ConfigFactoryInterface $config_factory,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {

    parent::__construct(
      $root,
      $parser,
      $config_factory,
      $configuration,
      $plugin_id,
      $plugin_definition);

    $this->configFactory = $config_factory;
    $this->serializer = $serializer;
    $this->state = $state;
  }

  /**
   * Creates a new PatternLibraryRest instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface
   *   A Symfony dependency injection container.
   * @param array $configuration
   *   Additional library configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryREST
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PatternLibraryPluginInterface {
    $root = $container->get('app.root');
    /** @var \Drupal\Component\Serialization\SerializationInterface $serializer */
    $serializer = $container->get('serialization.json');
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $container->get('state');
    /** @var \Drupal\patternkit\PatternLibraryParserInterface $rest_parser */
    $rest_parser = $container->get('patternkit.library.discovery.parser.rest');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    return new static($root, $serializer, $state, $rest_parser, $config_factory, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function getEditor(Pattern $pattern = NULL,
    PatternEditorConfig $config = NULL) {
    if ($pattern === NULL) {
      return '';
    }
    $patternkit_host = $this->configFactory->get('patternkit')->get('patternkit_pl_host') ?: 'http://localhost:9001';
    $url = $patternkit_host . '/schema/editor/' . substr($pattern->category, 3);

    if ($config !== NULL) {
      $url .= !empty($config->lzstring) ? '?data=' . $config->lzstring : '';
    }
    // @todo Move to external template.
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
   * {@inheritDoc}
   */
  public function getMetadata($extension, $library, $path): array {
    $patternkit_host = $this->configFactory->get('patternkit')->get('patternkit_pl_host') ?: 'http://localhost:9001';

    $patterns = \Drupal::httpClient()->request(
      'GET',
      $patternkit_host . '/api/patterns',
      [
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 10,
      ]
    );
    if ($patterns === NULL
      || !empty($patterns->error)
      || (int) $patterns->code !== 200) {
      \Drupal::logger('patternkit')->error(
        'Patternkit failed to load metadata from service (%service_uri): %error',
        [
          '%service_uri' => $patternkit_host . '/api/patterns',
          '%error'       => !empty($patterns->error) ? $patterns->error : $patterns->code,
        ]
      );
      return [];
    }
    $metadata = (array) $this->serializer::decode($patterns->data);
    foreach ($metadata as $subtype => $pattern) {
      $pattern->library = &$this;
      $metadata[$subtype] = $pattern;
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   *
   * This class evaluates the aggregation enabled/disabled condition on a group
   * by group basis by testing whether an aggregate file has been made for the
   * group rather than by testing the site-wide aggregation setting. This allows
   * this class to work correctly even if modules have implemented custom
   * logic for grouping and aggregating files.
   *
   * @throws \Exception
   */
  public function render(array $assets): array {
    $elements = [];
    /** @var Pattern $pattern */
    foreach ($assets as $pattern) {
      $config = $pattern->config ?? [];
      if (empty($config['presentation_style']) || empty($config['instance_id'])) {
        return [];
      }
      $pattern_name = $pattern->name;
      if ($config['presentation_style'] === 'webcomponent') {
        $elements[] = "<$pattern_name-pattern></$pattern_name-pattern>";
      }
      else {
        $filename = "public://patternkit/$pattern_name/{$config['instance_id']}/body.html";
        $elements[] = file_get_contents($filename);
      }
    }
    return $elements;
  }

}
