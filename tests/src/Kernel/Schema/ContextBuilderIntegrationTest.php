<?php

namespace Drupal\Tests\patternkit\Kernel\Schema;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Swaggest\JsonSchema\Schema;

/**
 * Integration tests to confirm default context behaviors.
 *
 * @group patternkit
 * @coversNothing
 */
class ContextBuilderIntegrationTest extends KernelTestBase {

  use JsonDecodeTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
  ];

  /**
   * The context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder
   */
  protected ContextBuilder $contextBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->contextBuilder = $this->container->get('patternkit.schema.context_builder');
  }

  /**
   * Test schema validation against use cases needing the default preprocessors.
   *
   * @dataProvider providerDefaultContextSchemaValidation
   */
  public function testDefaultContextSchemaValidation(string $schema_json, $config): void {
    $default_context = $this->contextBuilder->getDefaultContext();
    $schema = Schema::import(static::decodeJson($schema_json), $default_context);

    $this->assertNotNull($schema->in($config, $default_context));
  }

  /**
   * Data provider for testDefaultContextSchemaValidation().
   */
  public static function providerDefaultContextSchemaValidation(): array {
    $cases = [];

    // Test object coercion requiring the 'objects' preprocessor.
    $object_json = <<<JSON
      {
        "type": "object",
        "properties": {
          "text": {
            "type": "string"
          }
        },
        "required": ["text"]
      }
      JSON;
    $cases['object coercion'] = [
      $object_json,
      ['text' => 'my text'],
    ];

    // Test 'uri' string format validation requiring the 'tokens' preprocessor.
    $uri_format_json = <<<JSON
      {
        "type": "object",
        "properties": {
          "uri_reference": {
            "type": "string",
            "format": "uri"
          }
        }
      }
      JSON;
    $cases['uri tokens'] = [
      $uri_format_json,
      (object) ['uri_reference' => '[node:token]'],
    ];

    return $cases;
  }

}
