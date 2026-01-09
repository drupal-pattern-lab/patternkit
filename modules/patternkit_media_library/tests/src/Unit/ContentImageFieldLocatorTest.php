<?php

namespace Drupal\Tests\patternkit_media_library\Unit;

use Drupal\patternkit\Schema\SchemaWalkerFactory;
use Drupal\patternkit_media_library\ContentImageFieldLocator;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the ContentImageFieldLocator class.
 *
 * @group patternkit
 * @group patternkit_media_library
 * @coversDefaultClass \Drupal\patternkit_media_library\ContentImageFieldLocator
 */
class ContentImageFieldLocatorTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The schema walker factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\SchemaWalkerFactory>
   */
  protected ObjectProphecy $schemaWalkerFactory;

  /**
   * The logger service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\patternkit_media_library\ContentImageFieldLocator
   */
  protected ContentImageFieldLocator $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->schemaWalkerFactory = $this->prophesize(SchemaWalkerFactory::class);
    $this->logger = $this->prophesize(LoggerInterface::class);

    $this->service = new ContentImageFieldLocator(
      $this->schemaWalkerFactory->reveal(),
    );
    $this->service->setLogger($this->logger->reveal());
  }

  /**
   * Test getValueByPath with simple path.
   *
   * @covers ::getValueByPath
   */
  public function testGetValueByPathSimple(): void {
    $array = [
      'image' => [
        'src' => '/path/to/image.jpg',
        'alt' => 'Test alt text',
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getValueByPath');
    $method->setAccessible(TRUE);

    // Test getting src value.
    $result = $method->invoke($this->service, $array, 'image.src');
    $this->assertEquals('/path/to/image.jpg', $result);

    // Test getting alt value.
    $result = $method->invoke($this->service, $array, 'image.alt');
    $this->assertEquals('Test alt text', $result);
  }

  /**
   * Test getValueByPath with nested path.
   *
   * @covers ::getValueByPath
   */
  public function testGetValueByPathNested(): void {
    $array = [
      'hero' => [
        'image' => [
          'src' => '/path/to/hero.jpg',
          'alt' => 'Hero image',
        ],
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getValueByPath');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $array, 'hero.image.src');
    $this->assertEquals('/path/to/hero.jpg', $result);
  }

  /**
   * Test getValueByPath with non-existent path.
   *
   * @covers ::getValueByPath
   */
  public function testGetValueByPathNonExistent(): void {
    $array = [
      'image' => [
        'src' => '/path/to/image.jpg',
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getValueByPath');
    $method->setAccessible(TRUE);

    // Test getting non-existent alt value.
    $result = $method->invoke($this->service, $array, 'image.alt');
    $this->assertNull($result);

    // Test getting completely non-existent path.
    $result = $method->invoke($this->service, $array, 'hero.image.src');
    $this->assertNull($result);
  }

  /**
   * Test getValueByPath with NULL value vs missing key.
   *
   * @covers ::getValueByPath
   */
  public function testGetValueByPathNullValue(): void {
    $array = [
      'image' => [
        'src' => '/path/to/image.jpg',
        'alt' => NULL,
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getValueByPath');
    $method->setAccessible(TRUE);

    // Test getting NULL value (key exists with NULL).
    $result = $method->invoke($this->service, $array, 'image.alt');
    $this->assertNull($result);

    // Test getting missing key (should also return NULL).
    $result = $method->invoke($this->service, $array, 'image.title');
    $this->assertNull($result);
  }

  /**
   * Test setValueByPath with simple path.
   *
   * @covers ::setValueByPath
   */
  public function testSetValueByPathSimple(): void {
    $array = [
      'image' => [
        'src' => '/path/to/image.jpg',
      ],
    ];

    $this->service->setValueByPath($array, 'image.alt', 'New alt text');

    $this->assertEquals('New alt text', $array['image']['alt']);
    $this->assertEquals('/path/to/image.jpg', $array['image']['src']);
  }

  /**
   * Test setValueByPath with nested path.
   *
   * @covers ::setValueByPath
   */
  public function testSetValueByPathNested(): void {
    $array = [
      'hero' => [
        'image' => [
          'src' => '/path/to/hero.jpg',
        ],
      ],
    ];

    $this->service->setValueByPath($array, 'hero.image.alt', 'Hero alt text');

    $this->assertEquals('Hero alt text', $array['hero']['image']['alt']);
  }

  /**
   * Test setValueByPath creates missing intermediate keys.
   *
   * @covers ::setValueByPath
   */
  public function testSetValueByPathCreatesIntermediateKeys(): void {
    $array = [];

    $this->service->setValueByPath($array, 'hero.image.alt', 'Hero alt text');

    $this->assertArrayHasKey('hero', $array);
    $this->assertArrayHasKey('image', $array['hero']);
    $this->assertArrayHasKey('alt', $array['hero']['image']);
    $this->assertEquals('Hero alt text', $array['hero']['image']['alt']);
  }

  /**
   * Test setValueByPath overwrites existing value.
   *
   * @covers ::setValueByPath
   */
  public function testSetValueByPathOverwrites(): void {
    $array = [
      'image' => [
        'alt' => 'Old alt text',
      ],
    ];

    $this->service->setValueByPath($array, 'image.alt', 'New alt text');

    $this->assertEquals('New alt text', $array['image']['alt']);
  }

  /**
   * Test getAltPath with standard .src suffix.
   *
   * @covers ::getAltPath
   */
  public function testGetAltPathWithSrcSuffix(): void {
    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getAltPath');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 'image.src');
    $this->assertEquals('image.alt', $result);

    $result = $method->invoke($this->service, 'hero.image.src');
    $this->assertEquals('hero.image.alt', $result);
  }

  /**
   * Test getAltPath with root-level src.
   *
   * @covers ::getAltPath
   */
  public function testGetAltPathRootLevel(): void {
    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getAltPath');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 'src');
    $this->assertEquals('alt', $result);
  }

  /**
   * Test getAltPath with non-src path.
   *
   * @covers ::getAltPath
   */
  public function testGetAltPathNonSrcPath(): void {
    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getAltPath');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 'image.url');
    $this->assertNull($result);

    $result = $method->invoke($this->service, 'hero.image.source');
    $this->assertNull($result);
  }

  /**
   * Test getBasePath with .src suffix.
   *
   * @covers ::getBasePath
   */
  public function testGetBasePathWithSrcSuffix(): void {
    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getBasePath');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 'image.src');
    $this->assertEquals('image', $result);

    $result = $method->invoke($this->service, 'hero.image.src');
    $this->assertEquals('hero.image', $result);
  }

  /**
   * Test getBasePath without .src suffix.
   *
   * @covers ::getBasePath
   */
  public function testGetBasePathWithoutSrcSuffix(): void {
    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getBasePath');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 'image.url');
    $this->assertEquals('image.url', $result);
  }

  /**
   * Test isImageField with format: "image" schema.
   *
   * @covers ::isImageField
   */
  public function testIsImageFieldWithFormatImage(): void {
    // Mock a property schema with format: "image".
    $propertySchema = new \stdClass();
    $propertySchema->type = 'string';
    $propertySchema->format = 'image';

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isImageField');
    $method->setAccessible(TRUE);

    // Test with valid URL starting with /.
    $result = $method->invoke($this->service, $propertySchema, 'image_url', '/path/to/image.jpg');
    $this->assertTrue($result);

    // Test with valid URL starting with http.
    $result = $method->invoke($this->service, $propertySchema, 'image_url', 'http://example.com/image.jpg');
    $this->assertTrue($result);
  }

  /**
   * Test isImageField with src field name.
   *
   * @covers ::isImageField
   */
  public function testIsImageFieldWithSrcName(): void {
    // Mock a property schema without format: "image".
    $propertySchema = new \stdClass();
    $propertySchema->type = 'string';

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isImageField');
    $method->setAccessible(TRUE);

    // Test with field name "src" and valid URL.
    $result = $method->invoke($this->service, $propertySchema, 'src', '/path/to/image.jpg');
    $this->assertTrue($result);
  }

  /**
   * Test isImageField with non-URL value.
   *
   * @covers ::isImageField
   */
  public function testIsImageFieldNonUrl(): void {
    // Mock a property schema with format: "image".
    $propertySchema = new \stdClass();
    $propertySchema->type = 'string';
    $propertySchema->format = 'image';

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isImageField');
    $method->setAccessible(TRUE);

    // Test with non-URL value.
    $result = $method->invoke($this->service, $propertySchema, 'image_url', 'not-a-url');
    $this->assertFalse($result);

    // Test with empty value.
    $result = $method->invoke($this->service, $propertySchema, 'image_url', '');
    $this->assertFalse($result);
  }

  /**
   * Test isImageField with non-string value.
   *
   * @covers ::isImageField
   */
  public function testIsImageFieldNonString(): void {
    // Mock a property schema with format: "image".
    $propertySchema = new \stdClass();
    $propertySchema->type = 'string';
    $propertySchema->format = 'image';

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isImageField');
    $method->setAccessible(TRUE);

    // Test with integer value.
    $result = $method->invoke($this->service, $propertySchema, 'image_url', 123);
    $this->assertFalse($result);

    // Test with array value.
    $result = $method->invoke($this->service, $propertySchema, 'image_url', []);
    $this->assertFalse($result);

    // Test with NULL value.
    $result = $method->invoke($this->service, $propertySchema, 'image_url', NULL);
    $this->assertFalse($result);
  }

  /**
   * Test isMissingAltText with missing alt field.
   *
   * @covers ::isMissingAltText
   * @covers ::getAltPath
   * @covers ::getValueByPath
   */
  public function testIsMissingAltTextMissing(): void {
    $content = [
      'image' => [
        'src' => '/path/to/image.jpg',
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isMissingAltText');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $content, 'image.src');
    $this->assertTrue($result);
  }

  /**
   * Test isMissingAltText with empty alt field.
   *
   * @covers ::isMissingAltText
   * @covers ::getAltPath
   * @covers ::getValueByPath
   */
  public function testIsMissingAltTextEmpty(): void {
    $content = [
      'image' => [
        'src' => '/path/to/image.jpg',
        'alt' => '',
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isMissingAltText');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $content, 'image.src');
    $this->assertTrue($result);
  }

  /**
   * Test isMissingAltText with NULL alt field.
   *
   * @covers ::isMissingAltText
   * @covers ::getAltPath
   * @covers ::getValueByPath
   */
  public function testIsMissingAltTextNull(): void {
    $content = [
      'image' => [
        'src' => '/path/to/image.jpg',
        'alt' => NULL,
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isMissingAltText');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $content, 'image.src');
    $this->assertTrue($result);
  }

  /**
   * Test isMissingAltText with present alt field.
   *
   * @covers ::isMissingAltText
   * @covers ::getAltPath
   * @covers ::getValueByPath
   */
  public function testIsMissingAltTextPresent(): void {
    $content = [
      'image' => [
        'src' => '/path/to/image.jpg',
        'alt' => 'Image description',
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isMissingAltText');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $content, 'image.src');
    $this->assertFalse($result);
  }

  /**
   * Test isMissingAltText with non-src path.
   *
   * @covers ::isMissingAltText
   * @covers ::getAltPath
   */
  public function testIsMissingAltTextNonSrcPath(): void {
    $content = [
      'image' => [
        'url' => '/path/to/image.jpg',
      ],
    ];

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isMissingAltText');
    $method->setAccessible(TRUE);

    // Should return FALSE because path doesn't end with .src.
    $result = $method->invoke($this->service, $content, 'image.url');
    $this->assertFalse($result);
  }

}
