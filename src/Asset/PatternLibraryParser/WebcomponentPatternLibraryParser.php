<?php

namespace Drupal\patternkit\Asset\PatternLibraryParser;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternLibrary;

/**
 * A library parser plugin implementation for loading web component patterns.
 */
class WebcomponentPatternLibraryParser extends PatternLibraryParserBase {

  /**
   * Fetch and cache assets to render this pattern.
   *
   * @param string $subtype
   *   Pattern machine name.
   * @param object $config
   *   Configuration object for this instance of the pattern.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The patternkit object representing the pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo Finish implementation.
   */
  public static function fetchAssets(string $subtype, $config): PatternInterface {
    $patternkit_host = 'http://localhost:9001';

    $url = $patternkit_host . '/api/render/webcomponent';
    $client = \Drupal::httpClient();
    $request = $client->get(
      $url,
      [
        'headers'  => ['Content-Type' => 'application/json'],
        'jsondata' => $config->rawJSON,
        // 'timeout' => 10,.
        'method'   => 'POST',
      ]
    );

    // @todo Request failure handling.
    // Create the stub object.
    $pk_obj = (object) [
      'PatternkitPattern' => $subtype,
      'attachments' => [],
      'body'        => 'fragment.html',
    ];

    $dir = "public://patternkit/$subtype/{$config->instance_id}";
    if (!\Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY)) {
      \Drupal::service('messenger')->addMessage(
        t(
          'Unable to create folder or save metadata/assets for plugin @plugin',
          ['@plugin' => $subtype]
        ));
      \Drupal::logger('patternkit')->error(
        'Unable to create folder or save metadata/assets for plugin @plugin',
        ['@plugin' => $subtype]);
    }

    // Fetch the body html artifact.
    $save_result = \Drupal::service('file_system')->saveData(
      $request->getBody()->getContents(),
      "$dir/body.html",
      FileSystemInterface::EXISTS_REPLACE
    );

    // Convert stream wrapper to relative path, if appropriate.
    $scheme = StreamWrapperManagerInterface::getScheme($save_result);
    if ($scheme && \Drupal::service('stream_wrapper_manager')->isValidScheme($scheme)) {
      $wrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme($scheme);
      $save_result = $wrapper->getDirectoryPath() . "/" . \Drupal::service('stream_wrapper_manager')::getTarget(
        $save_result);
    }

    $pk_obj->body = $save_result;

    $pk_obj->attachments['js']['https://cdnjs.cloudflare.com/ajax/libs/webcomponentsjs/0.7.24/webcomponents.min.js'] = [
      'type'   => 'external',
      'scope'  => 'header',
      'group'  => JS_DEFAULT,
      'weight' => 0,
    ];

    // Add the header link rel import.
    $pk_obj->attachments['drupal_add_html_head_link'][] = [
      [
        'rel'  => 'import',
        'href' => $pk_obj->body,
      ],
    ];

    if (!$save_result) {
      \Drupal::service('messenger')->addMessage(
        t('Unable to save metadata/assets for plugin @plugin',
          ['@plugin' => $subtype]));
      \Drupal::logger('patternkit')->error(
        'Unable to save metadata/assets for plugin @plugin',
        ['@plugin' => $subtype]);
      // @todo Handle the failure gracefully.
    }

    return Pattern::create((array) $pk_obj);
  }

  /**
   * {@inheritdoc}
   */
  public function parsePatternLibraryInfo(PatternLibrary $library, $path): array {
    // @todo Implement parsePatternLibraryInfo() method.
    return [];
  }

}
