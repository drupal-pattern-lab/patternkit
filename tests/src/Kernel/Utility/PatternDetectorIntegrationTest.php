<?php

namespace Drupal\Tests\patternkit\Kernel\Utility;

use Drupal\patternkit\Utility\PatternDetector;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;

/**
 * Test pattern usage discovery using the pattern detector service.
 *
 * @group patternkit
 * @covers \Drupal\patternkit\Schema\ContextBuilder
 * @covers \Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection
 * @covers \Drupal\patternkit\Schema\DataPreProcessorFactory
 * @coversDefaultClass \Drupal\patternkit\Utility\PatternDetector
 * @covers \Drupal\patternkit\PatternRepository
 * @covers \Drupal\patternkit\Schema\SchemaFactory
 * @covers \Drupal\patternkit\Schema\SchemaHelper
 * @covers \Drupal\patternkit\Schema\SchemaRefHandler
 * @covers \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver
 * @uses \Drupal\patternkit\Attribute\PatternLibrary
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor
 * @uses \Drupal\patternkit\Entity\Pattern
 * @uses \Drupal\patternkit\Asset\PatternLibraryParser\JSONPatternLibraryParser
 * @uses \Drupal\patternkit\JsonEditorFormBuilder
 * @uses \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
 * @uses \Drupal\patternkit\Asset\PatternDependencyResolver
 * @uses \Drupal\patternkit\Asset\PatternDiscovery
 * @uses \Drupal\patternkit\Asset\PatternDiscoveryLoader
 * @uses \Drupal\patternkit\PatternLibrary
 * @uses \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryJSON
 * @uses \Drupal\patternkit\Asset\PatternLibraryParserBase
 * @uses \Drupal\patternkit\PatternLibraryPluginDefault
 * @uses \Drupal\patternkit\PatternLibraryPluginManager
 * @uses \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig
 * @uses \Drupal\patternkit\PatternkitEnvironment
 * @uses \Drupal\patternkit\Schema\PatternkitRefProvider
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor
 * @uses \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser
 * @uses \Drupal\patternkit\Schema\UnresolvedSchema
 */
class PatternDetectorIntegrationTest extends PatternkitKernelTestBase {

  use JsonDecodeTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'system',
  ];

  /**
   * The pattern detector service being tested.
   *
   * @var \Drupal\patternkit\Utility\PatternDetector
   */
  protected PatternDetector $detector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->detector = \Drupal::service('patternkit.pattern.detector');
  }

  /**
   * Test pattern detection within various content.
   *
   * @param string $pattern_id
   *   The asset name for the pattern to be tested.
   * @param string $content
   *   A JSON string of the content to be tested against the pattern.
   * @param string[][] $expected
   *   Expected results from the pattern detection.
   *
   * @dataProvider providerPatternDetection
   */
  public function testPatternDetection(string $pattern_id, string $content, array $expected): void {
    $detected = $this->detector->detectInPattern($pattern_id, $content);
    $this->assertEquals($expected, array_keys($detected));
  }

  /**
   * Data provider for testPatternDetection().
   */
  public static function providerPatternDetection(): array {
    $cases = [];

    $cases['simple nested reference'] = [
      // The pattern identifier.
      '@patternkit/atoms/example_ref/src/example_ref',
      // Content to be searched.
      <<<JSON
      {
        "text": "some text value",
        "nested_reference": {
          "text": "another text value"
        }
      }
      JSON,
      // The expected results.
      [
        '@patternkit/atoms/example/src/example',
      ],
    ];

    $cases['nested pattern with anchor'] = [
      // The pattern identifier.
      '@patternkit/atoms/example_ref/src/example_ref',
      // Content to be searched.
      <<<JSON
      {
        "text": "some text value"
      }
      JSON,
      // The expected results.
      [
        '@patternkit/atoms/example/src/example',
      ],
    ];

    $cases['no nested patterns'] = [
      // The pattern identifier.
      '@patternkit/atoms/example/src/example',
      // Content to be searched.
      <<<JSON
      {
        "text": "some text value"
      }
      JSON,
      // The expected results.
      [],
    ];

    $cases['multiple nested patterns'] = [
      // The pattern identifier.
      '@patternkit/components/media_combined_embed/media_combined_embed',
      // Content to be searched.
      <<<JSON
      {
        "name": "@patternkit/components/media_combined_embed/media_combined_embed",
        "image": {
          "name": "@patternkit/components/media_image_embed/media_image_embed",
          "image": "Markup for an embedded image."
        },
        "video": {
          "name": "@patternkit/components/media_video_embed/media_video_embed",
          "video": "Markup for an embedded video."
        }
      }
      JSON,
      // The expected results.
      [
        '@patternkit/components/media_image_embed/media_image_embed',
        '@patternkit/components/media_video_embed/media_video_embed',
      ],
    ];

    $cases['subpattern with anyOf'] = [
      // The pattern identifier.
      '@patternkit/components/media_wrapper/media_wrapper',
      // Content to be searched.
      <<<JSON
      {
        "name": "@patternkit/components/media_wrapper/media_wrapper",
        "media": [
          {
            "name": "@patternkit/components/media_image_embed/media_image_embed",
            "image": "Markup for an embedded image."
          }
        ]
      }
      JSON,
      // The expected results.
      [
        '@patternkit/components/media_image_embed/media_image_embed',
      ],
    ];

    $cases['subpattern with anyOf case 2'] = [
      // The pattern identifier.
      '@patternkit/components/media_wrapper/media_wrapper',
      // Content to be searched.
      <<<JSON
      {
        "name": "@patternkit/components/media_wrapper/media_wrapper",
        "media": [
          {
            "name": "@patternkit/components/media_video_embed/media_video_embed",
            "video": "Markup for an embedded video."
          }
        ]
      }
      JSON,
      // The expected results.
      [
        '@patternkit/components/media_video_embed/media_video_embed',
      ],
    ];

    $cases['subpattern with empty anyOf'] = [
      // The pattern identifier.
      '@patternkit/components/media_wrapper/media_wrapper',
      // Content to be searched.
      <<<JSON
      {
        "name": "@patternkit/components/media_wrapper/media_wrapper",
        "media": []
      }
      JSON,
      // The expected results.
      [],
    ];

    return $cases;
  }

}
