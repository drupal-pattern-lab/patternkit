<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\State\StateInterface;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorInterface;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\PatternkitRefProvider;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\patternkit\Schema\SchemaWalkerFactory;
use Drupal\patternkit\Utility\PatternValidationHelper;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount as InvokedCountMatcher;
use Psr\Log\LoggerInterface;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\SchemaContract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test functionality for the Pattern Field Processor Plugin Manager.
 *
 * @group patternkit
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
 * @uses \Drupal\patternkit\Schema\SchemaFactory
 * @uses \Drupal\patternkit\Schema\SchemaHelper
 * @uses \Drupal\patternkit\Schema\SchemaIterator
 * @uses \Drupal\patternkit\Schema\SchemaWalker
 * @coversDefaultClass \Drupal\patternkit\PatternFieldProcessorPluginManager
 */
class PatternFieldProcessorPluginManagerTest extends UnitTestCase {

  use JsonDecodeTrait;

  /**
   * The mocked pattern library plugin manager.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager&\PHPUnit\Framework\MockObject\MockObject
   */
  protected PatternLibraryPluginManager&MockObject $patternLibraryPluginManager;

  /**
   * The pattern field processor plugin manager for testing.
   *
   * @var \Drupal\patternkit\PatternFieldProcessorPluginManager
   */
  protected PatternFieldProcessorPluginManager $pluginManager;

  /**
   * A mocked pattern library plugin.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected PatternLibraryPluginInterface&MockObject $libraryPlugin;

  /**
   * Mocked cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected CacheBackendInterface&MockObject $cacheDiscovery;

  /**
   * Mocked module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected ModuleHandlerInterface&MockObject $moduleHandler;

  /**
   * JSON serializer service for decoding JSON strings.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected Json $serializer;

  /**
   * The test service for resolving schema references.
   *
   * @var \Drupal\patternkit\Schema\PatternkitRefProvider&\PHPUnit\Framework\MockObject\MockObject
   */
  protected PatternkitRefProvider&MockObject $refProvider;

  /**
   * A data preprocessor to register for schema operations.
   *
   * @var \Swaggest\JsonSchema\DataPreProcessor
   */
  protected DataPreProcessor $dataPreProcessor;

  /**
   * The factory service for creating new schema instances.
   *
   * @var \Drupal\patternkit\Schema\SchemaFactory
   */
  protected SchemaFactory $schemaFactory;

  /**
   * The factory service for creating new SchemaWalker instances.
   *
   * @var \Drupal\patternkit\Schema\SchemaWalkerFactory
   */
  protected SchemaWalkerFactory $schemaWalkerFactory;

  /**
   * A mocked logger channel.
   *
   * @var \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * A mocked state service.
   *
   * @var \Drupal\Core\State\StateInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected StateInterface&MockObject $state;

  /**
   * The validation helper service.
   *
   * @var \Drupal\patternkit\Utility\PatternValidationHelper
   */
  protected PatternValidationHelper $validationHelper;

  /**
   * The Drupal container to configure for dependency services.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * An iterator argument for the plugin manager.
   *
   * @var \ArrayIterator
   */
  protected \ArrayIterator $containerNamespaces;

  /**
   * The mocked context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder&\PHPUnit\Framework\MockObject\MockObject
   */
  protected ContextBuilder&MockObject $contextBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock a library plugin to use for rendering.
    $this->libraryPlugin = $this->createMock(PatternLibraryPluginInterface::class);

    // Mock pattern library plugin manager to return our mocked plugin instance.
    $this->patternLibraryPluginManager = $this->createMock(PatternLibraryPluginManager::class);
    $this->patternLibraryPluginManager->method('createInstance')
      ->willReturn($this->libraryPlugin);

    // Mock service dependencies for the plugin manager.
    $this->containerNamespaces = new \ArrayIterator([]);
    $this->cacheDiscovery = $this->createMock(CacheBackendInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->serializer = new Json();
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->state = $this->createMock(StateInterface::class);

    // Expect no errors to be logged unless overridden in a specific test.
    $this->logger->expects($this->never())->method('error');

    $this->contextBuilder = $this->createMock(ContextBuilder::class);

    // Create a SchemaWalkerFactory with a mocked ref provider for loading
    // references within schemas.
    $this->refProvider = $this->createMock(PatternkitRefProvider::class);
    $this->dataPreProcessor = new ObjectCoercionDataPreProcessor();
    $this->schemaFactory = new SchemaFactory($this->contextBuilder);
    $this->schemaWalkerFactory = new SchemaWalkerFactory($this->schemaFactory);
    $this->validationHelper = new PatternValidationHelper(
      $this->contextBuilder,
      $this->schemaFactory,
    );

    $defaultContext = new Context($this->refProvider);
    $defaultContext->setDataPreProcessor($this->dataPreProcessor);
    $this->contextBuilder->method('getDefaultContext')
      ->willReturn($defaultContext);

    // Instantiate the plugin manager for testing with our mocked services.
    $this->pluginManager = new PatternFieldProcessorPluginManager(
      $this->containerNamespaces,
      $this->cacheDiscovery,
      $this->moduleHandler,
      $this->serializer,
      $this->schemaWalkerFactory,
      $this->state,
      $this->validationHelper,
    );
    $this->pluginManager->setLogger($this->logger);

    $this->container = new ContainerBuilder();
    $this->container->set('patternkit.schema.schema_factory', $this->schemaFactory);
    $this->container->set('patternkit.schema.context_builder', $this->contextBuilder);

    \Drupal::setContainer($this->container);
  }

  /**
   * Test error-handling for the processSchemaValues method.
   */
  public function testProcessSchemaValuesWithErrors() {
    // Prepare the schema for the pattern being processed.
    $schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "category": "atom",
        "title": "Example",
        "type": "object",
        "format": "grid",
        "properties": {
          "text": {
            "title": "Text",
            "type": "string",
            "options": {
              "grid_columns": 4
            }
          },
          "unknown_reference": {
            "\$ref": "/unknown/reference/value"
          },
          "hidden": {
            "title": "hidden",
            "type": "string"
          }
        }
      }
      JSON;

    // Mock the pattern to be processed and return our schema.
    $pattern = $this->createMock(Pattern::class);
    $pattern->expects($this->atLeastOnce())
      ->method('getSchema')
      ->willReturn($schema_json);

    $metadata = new BubbleableMetadata();

    // Prepare values for processing.
    $values = [
      'text' => $this->getRandomGenerator()->string(),
      'unknown_reference' => [
        'value' => $this->getRandomGenerator()->string(),
      ],
      'hidden' => $this->getRandomGenerator()->string(),
    ];

    // Expect errors to be logged. We have to recreate the logger mock and the
    // plugin manager for testing since the setup method sets the expectation
    // that no errors should be logged in most cases.
    // @todo Refactor once https://www.drupal.org/project/drupal/issues/2903456 is completed.
    /** @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $errorLogger */
    $errorLogger = $this->createMock(LoggerInterface::class);
    $errorLogger->expects($this->exactly(1))
      ->method('error')
      ->with(
        $this->stringContains("Error while processing schema values"),
        $this->callback(function ($context) {
          // Confirm the error message parameter will contain the failed ref.
          return strpos($context['@msg'], '/unknown/reference/value') !== FALSE;
        })
      );

    // Instantiate a new plugin manager instance to use the mocked logger.
    $pluginManager = new PatternFieldProcessorPluginManager(
      $this->containerNamespaces,
      $this->cacheDiscovery,
      $this->moduleHandler,
      $this->serializer,
      $this->schemaWalkerFactory,
      $this->state,
      $this->validationHelper,
    );
    $pluginManager->setLogger($errorLogger);

    // Execute processing of the properties.
    $this->expectException(SchemaReferenceException::class);
    $pluginManager->processSchemaValues(
      $pattern,
      $values,
      [],
      $metadata
    );
  }

  /**
   * Test schema traversal to ensure the callback receives expected arguments.
   *
   * @dataProvider providerTraverseSchema
   */
  public function testTraverseSchema(string $schema_json, $values, callable $expectationsCallback) {
    // Mock the callback for confirming it's called with expected properties.
    $processorMock = $this->createMock(PatternFieldProcessorInterface::class);

    // Mock the additional parameters for the apply function.
    $context = [$this->getRandomGenerator()->name() => $this->getRandomGenerator()->object()];
    $metadata = new BubbleableMetadata();

    // Prepare case-specific expectations.
    $expectationsCallback($processorMock, $context, $metadata);

    // Execute the traversal.
    $this->pluginManager->traverseSchema(
      $schema_json,
      $values,
      [$this->pluginManager, 'applyProcessors'],
      [$processorMock],
      $context,
      $metadata,
    );
  }

  /**
   * Data provider for testTraverseSchema.
   *
   * @todo Add test cases for exceptions.
   * @todo Add test cases for array items.
   * @todo Add test cases for anyOf properties.
   */
  public static function providerTraverseSchema(): array {
    $cases = [];

    $flat_schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "category": "atom",
        "title": "Example",
        "type": "object",
        "format": "grid",
        "properties": {
          "text": {
            "title": "Text",
            "type": "string",
            "options": {
              "grid_columns": 4
            }
          },
          "formatted_text": {
            "title": "Formatted Text",
            "type": "string",
            "format": "html",
            "options": {
              "wysiwyg": true
            }
          },
          "image": {
            "title": "Image Object",
            "type": "object",
            "properties": {
              "image_url": {
                "title": "Image URL",
                "type": "string",
                "format": "image",
                "options": {
                  "grid_columns": 6
                }
              }
            }
          },
          "hidden": {
            "title": "hidden",
            "type": "string"
          },
          "breakpoints": {
            "title": "Breakpoints",
            "type": "array",
            "items": {
              "anyOf": [
                {
                  "title": "",
                  "type": "string",
                  "enum": [
                    "",
                    "xxs",
                    "xs",
                    "sm",
                    "md",
                    "lg"
                  ]
                }
              ]
            }
          }
        }
      }
      JSON;

    $values = [
      'text' => 'test',
      'image' => [
        'image_url' => 'abc123',
      ],
      'breakpoints' => [
        'xs',
        'lg',
      ],
    ];
    $expectationsCallback = function (MockObject $processor, $context, $metadata) {
      // Set expectations for how and with what arguments the applies method
      // will be called.
      $processor->expects(new InvokedCountMatcher(6))
        ->method('applies')
        ->willReturn(TRUE);

      $expected_args = [
        [
          static::isInstanceOf(SchemaContract::class),
          'test',
          $context,
          $metadata,
        ],
        [
          static::isInstanceOf(SchemaContract::class),
          ['image_url' => 'abc123'],
          $context,
          $metadata,
        ],
        [
          static::isInstanceOf(SchemaContract::class),
          'abc123',
          $context,
          $metadata,
        ],
        [
          static::isInstanceOf(SchemaContract::class),
          static::arrayHasKey(0),
          $context,
          $metadata,
        ],
        [
          static::isInstanceOf(SchemaContract::class),
          'xs',
          $context,
          $metadata,
        ],
        [
          static::isInstanceOf(SchemaContract::class),
          'lg',
          $context,
          $metadata,
        ],
      ];

      // Set expectations for how and with what the apply method will be called.
      $processor->expects(new InvokedCountMatcher(6))
        ->method('apply')
        ->willReturnCallback(function () use (&$expected_args) {
          $expected = array_shift($expected_args);
          $args = func_get_args();

          // Test each received argument against expectations.
          foreach ($expected as $index => $expected_value) {
            if ($expected_value instanceof Constraint) {
              static::assertThat($args[$index], $expected_value);
            }
            else {
              static::assertEquals($expected_value, $args[$index]);
            }
          }

          // Always return the first argument.
          return $args[1];
        });
    };

    $cases['flat_array'] = [$flat_schema_json, $values, $expectationsCallback];

    return $cases;
  }

  /**
   * Test schema traversal to confirm value changes are applied.
   */
  public function testTraverseSchemaProcessing() {
    // Prepare the schema for the pattern being processed.
    $schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "category": "atom",
        "title": "Example",
        "type": "object",
        "format": "grid",
        "properties": {
          "text": {
            "title": "Text",
            "type": "string",
            "options": {
              "grid_columns": 4
            }
          },
          "formatted_text": {
            "title": "Formatted Text",
            "type": "string",
            "format": "html",
            "options": {
              "wysiwyg": true
            }
          },
          "image_url": {
            "title": "Image URL",
            "type": "string",
            "format": "image",
            "options": {
              "grid_columns": 6
            }
          },
          "hidden": {
            "title": "hidden",
            "type": "string"
          }
        }
      }
      JSON;

    // Prepare test and config values for the pattern being processed.
    $text_value = $this->getRandomGenerator()->sentences(3);
    $formatted_text_value = $this->getRandomGenerator()->paragraphs(1);
    $hidden_value = $this->getRandomGenerator()->sentences(3);
    $config_values = [
      'text' => $text_value,
      'formatted_text' => $formatted_text_value,
      'hidden' => $hidden_value,
    ];

    // Clone the config values for comparison after processing.
    $original_config_values = $config_values;

    // Prepare context values for validation.
    $context_id = $this->getRandomGenerator()->name();
    $context_object = $this->getRandomGenerator()->object();
    $context_values = [
      $context_id => $context_object,
    ];

    // Stub the metadata expected to be passed through all processing.
    $metadata = new BubbleableMetadata();

    // Mock a processor to observe what values are passed in.
    $processor = $this->getAppendingProcessorMock();

    // Execute processing of the properties.
    $processed_values = $this->pluginManager->traverseSchema(
      $schema_json,
      $config_values,
      [$this->pluginManager, 'applyProcessors'],
      [$processor],
      $context_values,
      $metadata
    );

    // Confirm expected results on config values.
    $this->assertNotEquals($original_config_values, $processed_values);
    $this->assertEquals([
      'text' => $original_config_values['text'] . ' PROCESSED',
      'formatted_text' => $original_config_values['formatted_text'] . ' PROCESSED',
      'hidden' => $original_config_values['hidden'] . ' PROCESSED',
    ], $processed_values, 'Processed config values did not include processed value changes.');
  }

  public function testTraverseSchemaWithErrors() {
    // Prepare the schema for the pattern being processed.
    $schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "category": "atom",
        "title": "Example",
        "type": "object",
        "format": "grid",
        "properties": {
          "text": {
            "title": "Text",
            "type": "string",
            "options": {
              "grid_columns": 4
            }
          },
          "unknown_reference": {
            "\$ref": "/unknown/reference/value"
          },
          "hidden": {
            "title": "hidden",
            "type": "string"
          }
        }
      }
      JSON;

    // Mock a processor to observe what values are passed in.
    $processor = $this->createMock(PatternFieldProcessorInterface::class);

    // Expect applies to be called for successful properties.
    $processor->expects($this->never())
      ->method('applies')
      ->willReturn(FALSE);

    $metadata = new BubbleableMetadata();

    // Prepare values for processing.
    $values = [
      'text' => $this->getRandomGenerator()->string(),
      'unknown_reference' => [
        'value' => $this->getRandomGenerator()->string(),
      ],
      'hidden' => $this->getRandomGenerator()->string(),
    ];

    // Expect an exception to be thrown.
    $this->expectException(SchemaReferenceException::class);

    // Execute processing of the properties.
    $this->pluginManager->traverseSchema(
      $schema_json,
      $values,
      [$this->pluginManager, 'applyProcessors'],
      [$processor],
      [],
      $metadata
    );
  }

  /**
   * Test property traversal with array properties.
   */
  public function testTraverseSchemaWithArrays() {
    // Prepare the schema for the pattern being processed.
    $schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "category": "atom",
        "title": "Array Example",
        "type": "object",
        "format": "grid",
        "properties": {
          "breakpoints": {
            "title": "Breakpoints",
            "type": "array",
            "items": {
              "anyOf": [
                {
                  "title": "",
                  "type": "string",
                  "enum": [
                    "",
                    "xxs",
                    "xs",
                    "sm",
                    "md",
                    "lg"
                  ]
                }
              ]
            }
          }
        }
      }
      JSON;

    $values = [
      'breakpoints' => [
        'xxs',
        'sm',
        'lg',
      ],
    ];
    $original_values = $values;

    $context_values = [
      'node' => $this->getRandomGenerator()->object(),
    ];
    $metadata = new BubbleableMetadata();

    // Mock a processor to observe what values are passed in.
    $processor = $this->createMock(PatternFieldProcessorInterface::class);

    // Expect applies to be called for each array item.
    $expected_applies_args = [
      $values['breakpoints'],
      $values['breakpoints'][0],
      $values['breakpoints'][1],
      $values['breakpoints'][2],
    ];
    $processor->expects($this->exactly(count($values['breakpoints']) + 1))
      ->method('applies')
      ->willReturnCallback(function (SchemaContract $propertySchema, $value) use (&$expected_applies_args): bool {
        $this->assertInstanceOf(SchemaContract::class, $propertySchema);
        $this->assertEquals(array_shift($expected_applies_args), $value);

        return is_string($value);
      });

    // Expect consecutive calls to apply() for each property with a value.
    $expected_apply_args = [
      [
        $this->isInstanceOf(SchemaContract::class),
        $values['breakpoints'][0],
        $context_values,
        $metadata,
      ],
      [
        $this->isInstanceOf(SchemaContract::class),
        $values['breakpoints'][1],
        $context_values,
        $metadata,
      ],
      [
        $this->isInstanceOf(SchemaContract::class),
        $values['breakpoints'][2],
        $context_values,
        $metadata,
      ],
    ];
    $processor->expects($this->exactly(count($values['breakpoints'])))
      ->method('apply')
      ->willReturnCallback(function () use (&$expected_apply_args) {
        $expected = array_shift($expected_apply_args);
        $args = func_get_args();

        // Test each received argument against expectations.
        foreach ($expected as $index => $expected_value) {
          if ($expected_value instanceof Constraint) {
            static::assertThat($args[$index], $expected_value);
          }
          else {
            static::assertEquals($expected_value, $args[$index]);
          }
        }

        // Return a consistent value indicating processing occurred.
        return 'PROCESSED';
      });

    // Execute processing of the properties.
    $processed_values = $this->pluginManager->traverseSchema(
      $schema_json,
      $values,
      [$this->pluginManager, 'applyProcessors'],
      [$processor],
      $context_values,
      $metadata
    );

    // Confirm structure of the resulting values after processing.
    $this->assertNotEquals($original_values, $processed_values);
    $this->assertCount(count($original_values['breakpoints']), $processed_values['breakpoints']);

    // Confirm all item values were processed.
    foreach ($processed_values['breakpoints'] as $index => $item) {
      $this->assertEquals('PROCESSED', $item, "The item at index $index was not processed.");
    }
  }

  /**
   * Ensure invalid values fail validation before processing.
   */
  public function testProcessSchemaValuesWithInvalidData(): void {
    // Define a simple schema requiring a string "text" field.
    $schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "category": "atom",
        "title": "Example",
        "type": "object",
        "properties": {
          "text": {
            "title": "Text",
            "type": "string"
          }
        },
        "required": ["text"]
      }
      JSON;

    // Mock the pattern to supply schema and an asset id for logging context.
    $pattern = $this->createMock(Pattern::class);
    $pattern->expects($this->atLeastOnce())
      ->method('getSchema')
      ->willReturn($schema_json);
    $pattern->method('getAssetId')
      ->willReturn('@patternkit/example');

    // Prepare invalid values and metadata container for processing.
    $metadata = new BubbleableMetadata();
    $values = [
      'text' => ['invalid'],
    ];

    // Expect a warning on soft pre-validation and an error on hard failure.
    $errorLogger = $this->createMock(LoggerInterface::class);
    $errorLogger->expects($this->once())
      ->method('warning');
    $errorLogger->expects($this->once())
      ->method('error');

    // Build the plugin manager with the mocked logger to capture failures.
    $pluginManager = new PatternFieldProcessorPluginManager(
      $this->containerNamespaces,
      $this->cacheDiscovery,
      $this->moduleHandler,
      $this->serializer,
      $this->schemaWalkerFactory,
      $this->state,
      $this->validationHelper,
    );
    $pluginManager->setLogger($errorLogger);

    // A processor should never be invoked when validation fails up front.
    $processor = $this->createMock(PatternFieldProcessorInterface::class);
    $processor->expects($this->never())->method('applies');

    // The hard post-processing validation should surface a schema exception.
    $this->expectException(SchemaValidationException::class);
    $pluginManager->processSchemaValues(
      $pattern,
      $values,
      [],
      $metadata
    );
  }

  /**
   * Test property traversal with array properties containing references.
   */
  public function testTraverseSchemaWithArraysWithRefs() {
    // Prepare the schema for the pattern being processed.
    $schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "category": "atom",
        "title": "Array Example With References",
        "type": "object",
        "properties": {
          "references": {
            "title": "Example reference items",
            "type": "array",
            "items": {
              "anyOf": [
                { "\$ref": "/api/patternkit/patternkit/refs/text?asset=schema" },
                { "\$ref": "/api/patternkit/patternkit/refs/number?asset=schema" },
                { "\$ref": "/api/patternkit/patternkit/refs/object?asset=schema" }
              ]
            }
          }
        }
      }
      JSON;

    // Mock referenced schemas.
    $text_schema = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "title": "Text property",
        "type": "string"
      }
      JSON;
    $number_schema = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "title": "Number property",
        "type": "number"
      }
      JSON;
    $object_schema = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "title": "Object property",
        "type": "object",
        "properties": {
          "text": {
            "type": "string"
          }
        }
      }
      JSON;

    $this->refProvider->method('getSchemaData')
      ->willReturnMap([
        [
          '/api/patternkit/patternkit/refs/text?asset=schema',
          static::decodeJson($text_schema),
        ],
        [
          '/api/patternkit/patternkit/refs/number?asset=schema',
          static::decodeJson($number_schema),
        ],
        [
          '/api/patternkit/patternkit/refs/object?asset=schema',
          static::decodeJson($object_schema),
        ],
      ]);

    // Prepare the configuration values for testing.
    $values = [
      'references' => [
        'my string value',
        '8675309',
        [
          'text' => 'nested string value',
        ],
      ],
    ];
    $original_values = $values;

    $context_values = [
      'node' => $this->getRandomGenerator()->object(),
    ];
    $metadata = new BubbleableMetadata();

    // Mock a processor to observe what values are passed in.
    $processor = $this->createMock(PatternFieldProcessorInterface::class);

    // Expect applies to be called for each array item.
    $expected_applies_args = [
      $values['references'],
      $values['references'][0],
      $values['references'][1],
      $values['references'][2],
      $values['references'][2]['text'],
    ];
    $processor->expects($this->exactly(5))
      ->method('applies')
      ->willReturnCallback(function (SchemaContract $propertySchema, $value) use (&$expected_applies_args): bool {
        $this->assertInstanceOf(SchemaContract::class, $propertySchema);
        $this->assertEquals(array_shift($expected_applies_args), $value);

        return is_string($value);
      });

    // Expect consecutive calls to apply() for each property with a value.
    $expected_apply_args = [
      [
        $this->isInstanceOf(SchemaContract::class),
        $values['references'][0],
        $context_values,
        $metadata,
      ],
      [
        $this->isInstanceOf(SchemaContract::class),
        $values['references'][1],
        $context_values,
        $metadata,
      ],
      [
        $this->isInstanceOf(SchemaContract::class),
        $values['references'][2]['text'],
        $context_values,
        $metadata,
      ],
    ];
    $processor->expects($this->exactly(3))
      ->method('apply')
      ->willReturnCallback(function () use (&$expected_apply_args) {
        $expected = array_shift($expected_apply_args);
        $args = func_get_args();

        // Test each received argument against expectations.
        foreach ($expected as $index => $expected_value) {
          if ($expected_value instanceof Constraint) {
            static::assertThat($args[$index], $expected_value);
          }
          else {
            static::assertEquals($expected_value, $args[$index]);
          }
        }

        // Return a consistent value indicating processing occurred.
        return 'PROCESSED';
      });

    // Execute processing of the properties.
    $processed_values = $this->pluginManager->traverseSchema(
      $schema_json,
      $values,
      [$this->pluginManager, 'applyProcessors'],
      [$processor],
      $context_values,
      $metadata
    );

    // Confirm structure of the resulting values after processing.
    $this->assertNotEquals($original_values, $processed_values);

    // Confirm all item values were processed.
    $this->assertEquals([
      'references' => [
        'PROCESSED',
        'PROCESSED',
        [
          'text' => 'PROCESSED',
        ],
      ],
    ], $processed_values);
  }

  /**
   * Get a mocked field processor that appends text to text values.
   *
   * This processor only applies to text values and returns the value with the
   * text " PROCESSED" appended to it.
   *
   * @return \Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorInterface|\PHPUnit\Framework\MockObject\MockObject
   *   A mocked PatternFieldProcessor plugin that appends the string
   *   ' PROCESSED' to all text string property values.
   */
  protected function getAppendingProcessorMock() {
    // Mock a processor to observe what values are passed in.
    $processor = $this->createMock(PatternFieldProcessorInterface::class);

    // Expect to be checked against various properties, but only apply to
    // properties with string values.
    $processor->expects($this->atLeastOnce())
      ->method('applies')
      ->willReturnCallback(function (SchemaContract $propertySchema, $value) {
        return is_string($value);
      });

    // Append text to the given value for all string values.
    // Only string values should make it past the applies() method.
    $processor->method('apply')
      ->with(
        $this->isInstanceOf(SchemaContract::class),
        method_exists($this, 'isString') ? $this->isString() : $this->isType('string'),
      )
      ->willReturnCallback(function ($schema, $value) {
        return $value . ' PROCESSED';
      });

    return $processor;
  }

}
