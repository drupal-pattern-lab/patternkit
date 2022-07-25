<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorInterface;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\patternkit\Schema\SchemaWalkerFactory;
use Drupal\Tests\patternkit\Unit\Schema\TestPatternkitRefProvider;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\SchemaContract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test functionality for the Pattern Field Processor Plugin Manager.
 *
 * @coversDefaultClass \Drupal\patternkit\PatternFieldProcessorPluginManager
 * @group patternkit
 */
class PatternFieldProcessorPluginManagerTest extends UnitTestCase {

  /**
   * The mocked pattern library plugin manager.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager
   */
  protected PatternLibraryPluginManager $patternLibraryPluginManager;

  /**
   * The pattern field processor plugin manager for testing.
   *
   * @var \Drupal\patternkit\PatternFieldProcessorPluginManager
   */
  protected PatternFieldProcessorPluginManager $pluginManager;

  /**
   * A mocked pattern library plugin.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginInterface
   */
  protected PatternLibraryPluginInterface $libraryPlugin;

  /**
   * Mocked cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheDiscovery;

  /**
   * Mocked module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * JSON serializer service for decoding JSON strings.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected Json $serializer;

  /**
   * The test service for resolving schema references.
   *
   * @var \Drupal\Tests\patternkit\Unit\Schema\TestPatternkitRefProvider
   */
  protected TestPatternkitRefProvider $refProvider;

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
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The Drupal container to configure for dependency services.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

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
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    // Expect no errors to be logged unless overridden in a specific test.
    $this->logger->expects($this->never())->method('error');

    // Create a SchemaWalkerFactory with a mocked ref provider for loading
    // references within schemas.
    $this->refProvider = new TestPatternkitRefProvider();
    $this->dataPreProcessor = new ObjectCoercionDataPreProcessor();
    $this->schemaFactory = new SchemaFactory($this->refProvider, $this->dataPreProcessor);
    $this->schemaWalkerFactory = new SchemaWalkerFactory($this->schemaFactory);

    // Instantiate the plugin manager for testing with our mocked services.
    $this->pluginManager = new PatternFieldProcessorPluginManager(
      $this->containerNamespaces,
      $this->cacheDiscovery,
      $this->moduleHandler,
      $this->serializer,
      $this->schemaWalkerFactory,
      $this->logger,
    );

    $this->container = new ContainerBuilder();
    $this->container->set('patternkit.schema.schema_factory', $this->schemaFactory);

    \Drupal::setContainer($this->container);
  }

  /**
   * Test error-handling for the processSchemaValues method.
   *
   * @covers ::processSchemaValues
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
    $pattern->expects($this->once())
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
    /** @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject errorLogger */
    $errorLogger = $this->createMock(LoggerChannelInterface::class);
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
      $errorLogger,
    );

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
   * @covers ::traverseSchema
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
  public function providerTraverseSchema(): array {
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
      $processor->expects($this->exactly(6))
        ->method('applies')
        ->willReturn(TRUE);

      // Set expectations for how and with what the apply method will be called.
      $processor->expects($this->exactly(6))
        ->method('apply')
        ->withConsecutive(
          [
            $this->isInstanceOf(SchemaContract::class),
            'test',
            $context,
            $metadata,
          ],
          [
            $this->isInstanceOf(SchemaContract::class),
            ['image_url' => 'abc123'],
            $context,
            $metadata,
          ],
          [
            $this->isInstanceOf(SchemaContract::class),
            'abc123',
            $context,
            $metadata,
          ],
          [
            $this->isInstanceOf(SchemaContract::class),
            $this->arrayHasKey(0),
            $context,
            $metadata,
          ],
          [
            $this->isInstanceOf(SchemaContract::class),
            'xs',
            $context,
            $metadata,
          ],
          [
            $this->isInstanceOf(SchemaContract::class),
            'lg',
            $context,
            $metadata,
          ],
        )
        ->willReturnArgument(1);
    };

    $cases['flat_array'] = [$flat_schema_json, $values, $expectationsCallback];

    return $cases;
  }

  /**
   * Test schema traversal to confirm value changes are applied.
   *
   * @covers ::traverseSchema
   * @covers ::applyProcessors
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

  /**
   * @covers ::traverseSchema
   * @covers ::applyProcessors
   */
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
   *
   * @covers ::traverseSchema
   * @covers ::applyProcessors
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
    $processor->expects($this->exactly(count($values['breakpoints']) + 1))
      ->method('applies')
      ->withConsecutive(
        [$this->isInstanceOf(SchemaContract::class), $values['breakpoints']],
        [$this->isInstanceOf(SchemaContract::class), $values['breakpoints'][0]],
        [$this->isInstanceOf(SchemaContract::class), $values['breakpoints'][1]],
        [$this->isInstanceOf(SchemaContract::class), $values['breakpoints'][2]],
      )
      ->willReturnCallback(function (SchemaContract $propertySchema, $value) {
        return is_string($value);
      });

    // Expect consecutive calls to apply() for each property with a value.
    $processor->expects($this->exactly(count($values['breakpoints'])))
      ->method('apply')
      ->withConsecutive(
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
      )
      ->willReturnCallback(function () {
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
   * Test property traversal with array properties containing references.
   *
   * @covers ::traverseSchema
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
    $this->registerRefSchema($text_schema, '@patternkit/refs/text');
    $this->registerRefSchema($number_schema, '@patternkit/refs/number');
    $this->registerRefSchema($object_schema, '@patternkit/refs/object');

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
    $processor->expects($this->exactly(5))
      ->method('applies')
      ->withConsecutive(
        [$this->isInstanceOf(SchemaContract::class), $values['references']],
        [$this->isInstanceOf(SchemaContract::class), $values['references'][0]],
        [$this->isInstanceOf(SchemaContract::class), $values['references'][1]],
        [$this->isInstanceOf(SchemaContract::class), $values['references'][2]],
        [
          $this->isInstanceOf(SchemaContract::class),
          $values['references'][2]['text'],
        ],
      )
      ->willReturnCallback(function (SchemaContract $propertySchema, $value) {
        return is_string($value);
      });

    // Expect consecutive calls to apply() for each property with a value.
    $processor->expects($this->exactly(3))
      ->method('apply')
      ->withConsecutive(
        [$this->isInstanceOf(SchemaContract::class),
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
      )
      ->willReturnCallback(function () {
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
   * Prepare mocking for loading a reference schema.
   *
   * @param string $schema_json
   *   The JSON content to be loaded for the schema reference.
   * @param string $schema_ref
   *   The schema reference identifier that will be looked up.
   */
  protected function registerRefSchema(string $schema_json, string $schema_ref): void {
    $this->refProvider->registerPatternSchema($schema_ref, $schema_json);
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
    $processor->method('apply')
      ->with(
        $this->isInstanceOf(SchemaContract::class),
        // Only string values should make it past the applies() method.
        $this->isType(IsType::TYPE_STRING),
      )
      ->willReturnCallback(function ($schema, $value) {
        return $value . ' PROCESSED';
      });

    return $processor;
  }

}
