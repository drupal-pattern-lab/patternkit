<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Core\Asset\AssetQueryStringInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\editor\Plugin\EditorManager;
use Drupal\patternkit\JSONSchemaEditorTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for JSONSchemaEditorTrait.
 *
 * @coversDefaultClass \Drupal\patternkit\JSONSchemaEditorTrait
 * @group patternkit
 */
class JSONSchemaEditorTraitTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * A mocked state service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\State\StateInterface>
   */
  protected ObjectProphecy $state;

  /**
   * A mocked file URL generator service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\File\FileUrlGeneratorInterface>
   */
  protected ObjectProphecy $fileUrlGenerator;

  /**
   * A mocked editor plugin manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\editor\Plugin\EditorManager>
   */
  protected ObjectProphecy $editorPluginManager;

  /**
   * A mocked query string service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Asset\AssetQueryStringInterface>
   */
  protected ObjectProphecy $queryString;

  /**
   * A mocked service container.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Symfony\Component\DependencyInjection\ContainerInterface>
   */
  protected ObjectProphecy $container;

  /**
   * The mocked object using the trait being tested.
   *
   * @var \Drupal\Tests\patternkit\Unit\TestJSONSchemaEditorTrait
   */
  protected TestJSONSchemaEditorTrait $mock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->prophesize(StateInterface::class);
    $this->fileUrlGenerator = $this->prophesize(FileUrlGeneratorInterface::class);
    $this->editorPluginManager = $this->prophesize(EditorManager::class);
    $this->queryString = $this->prophesize(AssetQueryStringInterface::class);

    $this->container = $this->prophesize(ContainerInterface::class);
    \Drupal::setContainer($this->container->reveal());

    $this->mock = new TestJSONSchemaEditorTrait(
      $this->state->reveal(),
      $this->fileUrlGenerator->reveal()
    );
  }

  /**
   * Test with Drupal 10.2+.
   *
   * @covers ::getLibraryAssetUrlFromUri
   * @covers ::getQueryString
   */
  public function testGetLibraryAssetUrlFromUriDrupalWithService(): void {
    // Inject the service for testing if available. Skip this test otherwise.
    if (class_exists('\Drupal\Core\Asset\AssetQueryString')) {
      $this->mock->setQueryStringService($this->queryString->reveal());
    }
    else {
      $this->markTestSkipped('Skipped for Drupal < 10.2.');
    }

    $random = $this->getRandomGenerator();
    $uri = 'library://patternkit/cygnet_no_shadow_dom/css';

    // Assemble a fake string to represent the absolute URL and include query
    // parameters already added.
    $absolute_url = $random->string(10) . '?' . $random->string(5);
    $this->fileUrlGenerator->generateAbsoluteString($uri)
      ->willReturn($absolute_url);

    // Prepare a query string snippet to be returned and ensure it isn't already
    // present in the random string for testing later.
    $query_string = $random->string();
    $this->assertStringNotContainsString($query_string, $absolute_url);
    $this->queryString->get()->willReturn($query_string);

    // Return the same string as a relative url for testing.
    $this->fileUrlGenerator->transformRelative($absolute_url)
      ->willReturn($absolute_url);

    // Assemble the expected output for testing.
    $expected = $absolute_url . '&' . $query_string;

    $actual = $this->mock->getLibraryAssetUrlFromUri($uri);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test with Drupal <=10.1.
   *
   * @covers ::getLibraryAssetUrlFromUri
   * @covers ::getQueryString
   */
  public function testGetLibraryAssetUrlFromUriWithoutService(): void {
    $random = $this->getRandomGenerator();
    $uri = 'library://patternkit/cygnet_no_shadow_dom/css';

    // Assemble a fake string to represent the absolute URL and include query
    // parameters already added.
    $absolute_url = $random->string(10) . '?' . $random->string(5);
    $this->fileUrlGenerator->generateAbsoluteString($uri)
      ->willReturn($absolute_url);

    // Prepare a query string snippet to be returned and ensure it isn't already
    // present in the random string for testing later.
    $query_string = $random->string();
    $this->assertStringNotContainsString($query_string, $absolute_url);
    $this->state->get('system.css_js_query_string', 0)
      ->willReturn($query_string);

    // Return the same string as a relative url for testing.
    $this->fileUrlGenerator->transformRelative($absolute_url)
      ->willReturn($absolute_url);

    // Assemble the expected output for testing.
    $expected = $absolute_url . '&' . $query_string;

    $actual = $this->mock->getLibraryAssetUrlFromUri($uri);
    $this->assertEquals($expected, $actual);
  }

}

/**
 * Test class using the trait.
 *
 * @internal
 */
class TestJSONSchemaEditorTrait {
  use JSONSchemaEditorTrait {
    setQueryStringService as public;
  }

  /**
   * Create an instance of the test class.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file url generator service.
   */
  public function __construct(
    StateInterface $state,
    FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    $this->state = $state;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

}
