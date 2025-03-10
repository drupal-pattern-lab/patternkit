<?php

namespace Drupal\Tests\patternkit\Kernel\Utility;

use Drupal\patternkit\Asset\LibraryNamespaceResolver;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Asset\PatternDiscovery;
use Drupal\patternkit\Asset\PatternDiscoveryLoader;
use Drupal\patternkit\Asset\PatternLibraryParser\JSONPatternLibraryParser;
use Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser;
use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Drupal\patternkit\Attribute\PatternLibrary as AttributePatternLibrary;
use Drupal\patternkit\Entity\Pattern as EntityPattern;
use Drupal\patternkit\JsonEditorFormBuilder;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryJSON;
use Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver;
use Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\PatternkitRefProvider;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\patternkit\Schema\SchemaHelper;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\patternkit\Schema\UnresolvedSchema;
use Drupal\patternkit\Utility\PatternDetector;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Test pattern usage discovery using the pattern detector service.
 */
#[Group('patternkit')]
#[CoversClass(ContextBuilder::class)]
#[CoversClass(DataPreProcessorCollection::class)]
#[CoversClass(DataPreProcessorFactory::class)]
#[CoversClass(PatternDetector::class)]
#[CoversClass(PatternRepository::class)]
#[CoversClass(SchemaFactory::class)]
#[CoversClass(SchemaHelper::class)]
#[CoversClass(SchemaRefHandler::class)]
#[CoversClass(SchemaRefObserver::class)]
#[UsesClass(AttributePatternLibrary::class)]
#[UsesClass(BundleReferenceDataPreProcessor::class)]
#[UsesClass(EntityPattern::class)]
#[UsesClass(JSONPatternLibraryParser::class)]
#[UsesClass(JsonEditorFormBuilder::class)]
#[UsesClass(LibraryNamespaceResolver::class)]
#[UsesClass(ObjectCoercionDataPreProcessor::class)]
#[UsesClass(PatternDependencyResolver::class)]
#[UsesClass(PatternDiscovery::class)]
#[UsesClass(PatternDiscoveryLoader::class)]
#[UsesClass(PatternLibrary::class)]
#[UsesClass(PatternLibraryJSON::class)]
#[UsesClass(PatternLibraryParserBase::class)]
#[UsesClass(PatternLibraryPluginDefault::class)]
#[UsesClass(PatternLibraryPluginManager::class)]
#[UsesClass(PatternLibraryTwig::class)]
#[UsesClass(PatternkitEnvironment::class)]
#[UsesClass(PatternkitRefProvider::class)]
#[UsesClass(RefNormalizerDataPreProcessor::class)]
#[UsesClass(TokenValidationDataPreProcessor::class)]
#[UsesClass(TwigPatternLibraryParser::class)]
#[UsesClass(UnresolvedSchema::class)]
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
   */
  #[DataProvider('providerPatternDetection')]
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
