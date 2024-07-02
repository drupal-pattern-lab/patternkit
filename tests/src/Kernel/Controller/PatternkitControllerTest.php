<?php

namespace Drupal\Tests\patternkit\Kernel\Controller;

use Drupal\patternkit\Controller\PatternkitController;
use Drupal\patternkit\Plugin\Derivative\PatternkitBlock;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\patternkit_example\Traits\PatternkitExampleLibraryAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test integrated usage of the PatternkitController class.
 *
 * @coversDefaultClass \Drupal\patternkit\Controller\PatternkitController
 * @group patternkit
 */
class PatternkitControllerTest extends PatternkitKernelTestBase {

  use PatternkitExampleLibraryAwareTrait;
  use JsonDecodeTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

  /**
   * The controller being tested.
   *
   * @var \Drupal\patternkit\Controller\PatternkitController
   */
  protected PatternkitController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->controller = PatternkitController::create($this->container);
  }

  /**
   * @covers ::add
   * @uses \Drupal\patternkit\Plugin\Derivative\PatternkitBlock::assetToDerivativeId()
   */
  public function testAdd(): void {
    $patterns = $this->getPatternNames();
    $ids = array_map([PatternkitBlock::class, 'assetToDerivativeId'], $patterns);

    $actual = $this->controller->add();
    $this->assertArrayHasKey('#content', $actual);
    $this->assertArrayHasKey('types', $actual['#content']);
    $this->assertEqualsCanonicalizing($ids, array_keys($actual['#content']['types']));
  }

  /**
   * Test a successful response from the apiPattern method.
   *
   * @covers ::apiPattern
   *
   * @todo Test content of the pattern object more completely.
   */
  public function testApiPattern(): void {
    $pattern_id = 'patternkit/atoms/example/src/example';
    $request = new Request(['pattern' => $pattern_id]);

    $response = $this->controller->apiPattern($request);
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals('200', $response->getStatusCode());

    $content = static::decodeJson($response->getContent());
    $this->assertFalse(isset($content->error));
  }

  /**
   * Test a failed lookup response from the apiPattern method.
   *
   * @covers ::apiPattern
   */
  public function testApiPatternUnknownResult(): void {
    $pattern_id = 'unknown/pattern';
    $request = new Request(['pattern' => $pattern_id]);

    $response = $this->controller->apiPattern($request);
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals('404', $response->getStatusCode());

    $content = static::decodeJson($response->getContent());
    $this->assertTrue(isset($content->error));
    $this->assertEquals('No known pattern definition for "@unknown/pattern".', $content->error);
  }

  /**
   * Test a successful response from the apiPatternSchema method.
   *
   * @covers ::apiPattern
   * @covers ::apiPatternSchema
   */
  public function testApiPatternSchema(): void {
    $pattern_id = 'patternkit/atoms/example/src/example';
    $schema_path = $this->getModulePath('patternkit_example') . '/lib/patternkit/src/atoms/example/src/example.json';
    $request = new Request([
      'pattern' => $pattern_id,
      'asset' => 'schema',
    ]);

    $response = $this->controller->apiPattern($request);
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals('200', $response->getStatusCode());

    $content = static::decodeJson($response->getContent());
    $this->assertFalse(isset($content->error));
    $this->assertJsonStringEqualsJsonFile($schema_path, $response->getContent());
  }

  /**
   * Test a failed lookup response from the apiPatternSchema method.
   *
   * @covers ::apiPattern
   * @covers ::apiPatternSchema
   */
  public function testApiPatternSchemaUnknownResult(): void {
    $pattern_id = 'unknown/pattern';
    $request = new Request([
      'pattern' => $pattern_id,
      'asset' => 'schema',
    ]);
    $response = $this->controller->apiPattern($request);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals('404', $response->getStatusCode());

    $content = static::decodeJson($response->getContent());
    $this->assertTrue(isset($content->error));
    $this->assertEquals('No known pattern definition for "@unknown/pattern".', $content->error);
  }

}
