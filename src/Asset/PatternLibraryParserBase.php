<?php

namespace Drupal\patternkit\Asset;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Asset\LibraryDiscoveryParser;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\PatternAssetException;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Webmozart\Assert\Assert;

/**
 * Base functionality for a pattern library parser.
 */
abstract class PatternLibraryParserBase extends LibraryDiscoveryParser implements PatternLibraryParserInterface {

  use StringTranslationTrait;
  use PatternkitEnvironmentAwareTrait;

  /**
   * Serializes and de-serializes data.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected SerializationInterface $serializer;

  /**
   * The entity storage handler for the patternkit_pattern entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * The schema data preprocessor factory service.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessorFactory
   */
  protected DataPreProcessorFactory $dataPreProcessorFactory;

  /**
   * The pattern dependency resolver service.
   *
   * @var \Drupal\patternkit\Asset\PatternDependencyResolver
   */
  protected PatternDependencyResolver $patternDependencyResolver;

  /**
   * The patternkit logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructor for PatternLibraryParser implementations.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serializes and de-serializes data.
   * @param string $root
   *   The application root path.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Allow modules to alter library parsing.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Allow themes to alter library parsing.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper
   *   The stream wrapper manager.
   * @param \Drupal\Core\Asset\LibrariesDirectoryFileFinder $libraries_directory_file_finder
   *   The libraries directory file finder service.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\patternkit\Schema\DataPreProcessorFactory $dataPreProcessorFactory
   *   The schema data preprocessor factory service.
   * @param \Drupal\patternkit\Asset\PatternDependencyResolver $patternDependencyResolver
   *   The pattern dependency resolver service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The patternkit logger channel.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    SerializationInterface $serializer,
    string $root,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager,
    StreamWrapperManagerInterface $stream_wrapper,
    LibrariesDirectoryFileFinder $libraries_directory_file_finder,
    ExtensionPathResolver $extension_path_resolver,
    EntityTypeManagerInterface $entity_type_manager,
    DataPreProcessorFactory $dataPreProcessorFactory,
    PatternDependencyResolver $patternDependencyResolver,
    LoggerChannelInterface $logger
  ) {
    parent::__construct($root, $module_handler, $theme_manager, $stream_wrapper, $libraries_directory_file_finder, $extension_path_resolver);

    $this->serializer = $serializer;
    $this->patternStorage = $entity_type_manager->getStorage('patternkit_pattern');
    $this->dataPreProcessorFactory = $dataPreProcessorFactory;
    $this->patternDependencyResolver = $patternDependencyResolver;
    $this->logger = $logger;
  }

  /**
   * Returns a new Patternkit Pattern.
   *
   * @param string $name
   *   The name of the pattern.
   * @param array|object $data
   *   The optional data for the pattern.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   A fully-instantiated Patternkit Pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createPattern(string $name, $data): PatternInterface {
    // Pattern schemas contain values needed for Pattern fields.
    $values = ['name' => $name];
    return $this->patternStorage->create($values + $data);
  }

  /**
   * Returns an array of file components grouped by file basename and extension.
   *
   * @param string $path
   *   The fully-qualified path to discover component files.
   * @param array $filter
   *   An optional filter of file extensions to search for.
   *
   * @return array
   *   Array of file components.
   *   [basename][extension] = filename.
   */
  public static function discoverComponents(string $path, array $filter = []): array {
    $components = [];
    $rdit = new RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO);
    /** @var \SplFileInfo $file */
    foreach (new \RecursiveIteratorIterator($rdit) as $file) {
      // Skip directories and non-files.
      if (!$file->isFile()) {
        continue;
      }

      // Skip tests folders.
      if (strpos($file->getRelativePath(), '/tests') !== FALSE) {
        continue;
      }

      // Get the file extension for the file.
      $file_ext = $file->getExtension();
      if (!in_array(strtolower($file_ext), $filter, TRUE)) {
        continue;
      }

      // We use file_basename as a unique key, it is required that the
      // JSON and twig file share this basename.
      $file_basename = $file->getBasename('.' . $file_ext);

      // Build an array of all the filenames of interest, keyed by name.
      $file_path = $file->getPath();
      $components[$file_basename][$file_ext] = "$file_path/$file_basename.$file_ext";
    }
    return $components;
  }

  /**
   * Implements fetchAssets().
   *
   * {@inheritdoc}
   *
   * @todo Finish implementation.
   */
  public static function fetchAssets(string $subtype, object $config): PatternInterface {
    $patternkit_host = \Drupal::config('patternkit.settings')
      ->get('patternkit_pl_host') ?: 'http://localhost:9001';

    $url = $patternkit_host . '/api/render/json';
    $result = \Drupal::httpClient()->post($url)
      ->withHeader('Content-Type', 'application/json')
      ->withBody($config->rawJSON);

    // @todo Request failure handling.
    $pk_obj = \Drupal::service('serialization.json')->decode($result->getBody()->getContents());

    $dir = "public://patternkit/$subtype/{$config->instance_id}";
    if (!\Drupal::service('file_system')->prepareDirectory($dir, FileSystem::CREATE_DIRECTORY)) {
      // @todo Failure handling.
      \Drupal::service('messenger')->addMessage(
        t(
        "Unable to create folder (@dir) to contain the pklugins artifacts.",
          ['@dir' => $dir]
      ));
    }

    // Fetch the body html artifact.
    $save_result = \Drupal::service('file_system')->saveData(
      $pk_obj->body,
      "$dir/manifest.json",
      FileSystemInterface::EXISTS_REPLACE
    );
    $pk_obj->body = $save_result;

    if ($save_result === FALSE) {
      // @todo Failure handling.
      \Drupal::service('messenger')->addMessage(
        t(
        "Unable to create static archive of the JSON pklugins artifact for @subtype.",
          ['@subtype' => $subtype]
      ));
    }

    $weight = 100;
    $assets = [];

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
      $pk_obj->assets->css->shared = [];
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

        $save_result = static::fetchSingleAsset($dir, $pk_obj->path, $asset_url);
        $pk_obj->raw_assets[$asset_url] = $save_result;
      }
    }

    return $pk_obj;
  }

  /**
   * Fetches all assets for a pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern to use for asset retrieval.
   * @param \Drupal\patternkit\PatternEditorConfig|null $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return array
   *   An array of loaded patterns assets.
   */
  public function fetchPatternAssets(PatternInterface $pattern, PatternEditorConfig $config = NULL): array {
    // @todo Add support for twig lib attachments such as JS and images.
    $assets = $pattern->getAssets();
    foreach ($assets as $asset => $data) {
      if (empty($data)) {
        continue;
      }
      if (file_exists($data)) {
        $assets[$asset] = file_get_contents($data);
      }
      // $this->fetchSingleAsset();
    }
    return $assets;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Finish implementation.
   */
  public static function fetchSingleAsset(string $dir, string $asset_prefix, string $asset_url) {
    $patternkit_host = \Drupal::config('patternkit.settings')
      ->get('patternkit_pl_host') ?: 'http://localhost:9001';

    // Leading double slashes eliminated above, leaving only relatives.
    $path = "$dir/" . dirname(trim($asset_url, '.'));
    $filename = basename(trim($asset_url, '.'));

    if (!\Drupal::service('file_system')->prepareDirectory($path, FileSystem::CREATE_DIRECTORY)) {
      \Drupal::service('messenger')->addMessage(
        t(
        "Unable to create folder (@path) to contain the pklugins artifacts.",
          ['@path' => $path]
      ));
    }

    // Generate the full path to the source asset.
    $full_asset_url = $patternkit_host
      . preg_replace('/^\\.\\//', $asset_prefix . '/', $asset_url);

    // What follows is for store/cache model.
    $asset_src = \Drupal::httpClient()->get($full_asset_url);
    // May consider some way of doing this
    // $dest_path = "$dir/" . md5($asset_src->data) . ".$asset_type";.
    $dest_path = $path . $filename;

    $save_result = \Drupal::service('file_system')->saveData(
      $asset_src->getBody()->getContents(),
      $dest_path,
      FileSystemInterface::EXISTS_REPLACE
    );

    if ($save_result === FALSE) {
      // @todo handle failure.
      return FALSE;
    }

    // Convert stream wrapper to relative path, if appropriate.
    $scheme = StreamWrapperManagerInterface::getScheme($save_result);
    if ($scheme && \Drupal::service('stream_wrapper_manager')->isValidScheme($scheme)) {
      $wrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme($scheme);
      $save_result = $wrapper->getDirectoryPath()
        . '/'
        . \Drupal::service('stream_wrapper_manager')::getTarget(
      $save_result);
    }
    return $save_result;
  }

  /**
   * Implements fetchFragmentAssets.
   *
   * {@inheritdoc}
   *
   * @todo Finish implementation.
   */
  public function fetchFragmentAssets($subtype, object $config, object &$pk_obj): PatternInterface {
    $patternkit_host = \Drupal::config('patternkit')->get(
      'patternkit_pl_host') ?: 'http://localhost:9001';

    $url = $patternkit_host . '/api/render/html';
    $result = \Drupal::httpClient()->post(
      $url)->withHeader('Content-Type', 'application/json')
      ->withBody($config->rawJSON);

    // @todo Request failure handling.
    $pk_obj->pattern = $subtype;

    $dir = "public://patternkit/$subtype/{$config->instance_id}";
    if (!\Drupal::service('file_system')->prepareDirectory($dir, FileSystem::CREATE_DIRECTORY)) {
      \Drupal::service('messenger')->addMessage(
        $this->t(
          'Unable to create folder or save metadata/assets for plugin @plugin',
          ['@plugin' => $subtype]
        ));
      \Drupal::logger('patternkit')->error(
        'Unable to create folder or save metadata/assets for plugin @plugin',
        ['@plugin' => $subtype]);
    }

    // Fetch the body html artifact.
    $save_result = \Drupal::service('file_system')->saveData(
      $result->getBody()->getContents(),
      "$dir/body.html",
      FileSystemInterface::EXISTS_REPLACE
    );

    // Convert stream wrapper to relative path, if appropriate.
    $scheme = StreamWrapperManagerInterface::getScheme($save_result);
    if ($scheme && \Drupal::service('stream_wrapper_manager')->isValidScheme($scheme)) {
      $wrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme($scheme);
      $save_result = $wrapper->getDirectoryPath() . "/" . \Drupal::service('stream_wrapper_manager')::getTarget(
          $save_result
        );
    }

    $pk_obj->body = $save_result;

    if ($save_result === FALSE) {
      \Drupal::service('messenger')->addMessage(
        $this->t(
          'Unable to save metadata/assets for plugin @plugin',
          ['@plugin' => $subtype]
        ));
      \Drupal::logger('patternkit')->error(
        'Unable to save metadata/assets for plugin @plugin',
        ['@plugin' => $subtype]);
      // @todo Handle this error gracefully.
    }

    foreach (['early', 'deferred'] as $stage) {
      foreach ($pk_obj->assets->js->{$stage} as $asset_fragment) {
        $path = $pk_obj->raw_assets[$asset_fragment];

        if (substr($path, 0, 19) === 'public://patternkit/') {
          $pk_obj->attachments['js'][$path] = [
            'type'   => 'file',
            'scope'  => $stage === 'early' ? 'header' : 'footer',
            'group'  => JS_DEFAULT,
            'weight' => 0,
          ];
        }
        else {
          $pk_obj->attachments['js'][$path] = [
            'type'   => 'external',
            'scope'  => $stage === 'early' ? 'header' : 'footer',
            'group'  => JS_DEFAULT,
            'weight' => 0,
          ];
        }
      }
    }

    foreach ($pk_obj->assets->css->list as $asset_fragment) {
      $path = $pk_obj->raw_assets[$asset_fragment];

      if (substr($path, 0, 19) === 'public://patternkit/') {
        $pk_obj->attachments['css'][$path] = [
          'type'   => 'file',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        ];
      }
      else {
        $pk_obj->attachments['css'][$path] = [
          'type'   => 'external',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        ];
      }
    }

    foreach ($pk_obj->assets->css->shared as $asset_fragment) {
      $path = $pk_obj->raw_assets[$asset_fragment->src];

      if (substr($path, 0, 19) === 'public://patternkit/') {
        $pk_obj->attachments['css'][$path] = [
          'type'   => 'file',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        ];
      }
      else {
        $pk_obj->attachments['css'][$path] = [
          'type'   => 'external',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        ];
      }
    }

    return $this->patternStorage->create((array) $pk_obj);
  }

  /**
   * Merge js dependency arrays.
   *
   * Adds $js2 to $js1 and returns the merged array.
   *
   * @param array $js1
   *   First array.
   * @param array $js2
   *   Second array.
   *
   * @return array
   *   The merged array.
   */
  public function mergeJs(array $js1, array $js2): array {
    $ret = [];

    $x = 0;
    if (!empty($js1)) {
      foreach ($js1 as $key => $val) {
        // If this is a 'setting' or 'inline' it will be numeric.
        if (is_numeric($key)) {
          $ret[$x++] = $val;
        }
        else {
          $ret[$key] = $val;
        }
      }
    }
    if (!empty($js2)) {
      foreach ($js2 as $key => $val) {
        // If this is a 'setting' or 'inline' it will be numeric.
        if (is_numeric($key)) {
          $ret[$x++] = $val;
        }
        else {
          $ret[$key] = $val;
        }
      }
    }
    return $ret;
  }

  /**
   * Log encountered exceptions during library parsing and discovery.
   *
   * @param string $library
   *   The library being processed.
   * @param array $exceptions
   *   An array of exception data keyed by the pattern identifier being
   *   processed while the exception was encountered. Each entry should be an
   *   array of the following values:
   *   - The exception encountered.
   *   - A message for logging.
   *   - Any context values for logging.
   */
  protected function logParserExceptions(string $library, array $exceptions): void {
    Assert::allIsArray($exceptions, 'All exception values are expected to be an array.');
    Assert::allCount($exceptions, 3, 'All exception events are expected to have 3 elements.');

    // Log debug messages if debug mode is enabled. See patternkit.services.yml.
    $debug = $this->isDebug();

    // Log each of the exceptions.
    foreach ($exceptions as [$exception, $message, $context]) {
      switch (true) {
        case $exception instanceof PluginNotFoundException:
        case $exception instanceof PluginException:
        case $exception instanceof SchemaException:
        case $exception instanceof \JsonException:
          $this->logger->warning($message, $context);
          break;

        case $exception instanceof PatternAssetException:
          if ($debug) {
            $this->logger->debug($message, $context);
          }
          break;

        default:
          $this->logger->warning($message, $context);
      }
    }

    if (($count = count($exceptions)) && $debug) {
      $this->logger->debug('Unable to load @count patterns from library @library: <br/>@patterns',
        [
          '@count' => $count,
          '@library' => $library,
          '@patterns' => implode(', ', array_keys($exceptions)),
        ]
      );
    }
  }

}
