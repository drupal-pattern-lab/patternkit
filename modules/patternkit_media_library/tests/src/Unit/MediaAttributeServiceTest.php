<?php

namespace Drupal\Tests\patternkit_media_library\Unit;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\patternkit_media_library\MediaAttributeService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for the MediaAttributeService class.
 *
 * @group patternkit
 * @group patternkit_media_library
 * @coversDefaultClass \Drupal\patternkit_media_library\MediaAttributeService
 */
class MediaAttributeServiceTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The entity type manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityTypeManagerInterface>
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * The file URL generator service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\File\FileUrlGeneratorInterface>
   */
  protected ObjectProphecy $fileUrlGenerator;

  /**
   * The entity type bundle info service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityTypeBundleInfoInterface>
   */
  protected ObjectProphecy $bundleInfo;

  /**
   * The request stack service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Symfony\Component\HttpFoundation\RequestStack>
   */
  protected ObjectProphecy $requestStack;

  /**
   * The logger service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\patternkit_media_library\MediaAttributeService
   */
  protected MediaAttributeService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->fileUrlGenerator = $this->prophesize(FileUrlGeneratorInterface::class);
    $this->bundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->logger = $this->prophesize(LoggerInterface::class);

    $this->service = new MediaAttributeService(
      $this->entityTypeManager->reveal(),
      $this->fileUrlGenerator->reveal(),
      $this->bundleInfo->reveal(),
      $this->requestStack->reveal(),
    );
    $this->service->setLogger($this->logger->reveal());
  }

  /**
   * Set up a standard HTTP request for testing.
   *
   * @param string $scheme
   *   The scheme (http or https).
   * @param string $host
   *   The hostname.
   * @param int|null $port
   *   The port number (NULL for standard port).
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The configured request object.
   */
  protected function setUpRequest(string $scheme = 'http', string $host = 'example.com', ?int $port = 80): Request {
    $actual_port = $port ?? ($scheme === 'https' ? 443 : 80);

    // Build the full URL for Request::create() to ensure proper hostname
    // handling.
    $url = $scheme . '://' . $host;
    if ($actual_port && !in_array($actual_port, [80, 443])) {
      $url .= ':' . $actual_port;
    }

    // Create request with proper server parameters and headers.
    $server_params = [
      'HTTPS' => $scheme === 'https' ? 'on' : 'off',
      'SERVER_NAME' => $host,
      'SERVER_PORT' => (string) $actual_port,
      'HTTP_HOST' => $actual_port && !in_array($actual_port, [80, 443]) ? $host . ':' . $actual_port : $host,
    ];

    $request = Request::create($url, 'GET', [], [], [], $server_params);
    $this->requestStack->getCurrentRequest()->willReturn($request);
    return $request;
  }

  /**
   * Set up a mock file entity.
   *
   * @param int $file_id
   *   The file ID.
   * @param string $uri
   *   The file URI.
   * @param string $generated_url
   *   The generated relative URL.
   * @param string|null $absolute_url
   *   Optional absolute URL for canonical link.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\file\FileInterface>
   *   The mocked file entity.
   */
  protected function setUpFileEntity(int $file_id = 1, string $uri = 'public://image.jpg', string $generated_url = '/sites/default/files/image.jpg', ?string $absolute_url = NULL): ObjectProphecy {
    $file = $this->prophesize(FileInterface::class);
    $file->id()->willReturn($file_id);
    $file->getFileUri()->willReturn($uri);
    $file->hasLinkTemplate('canonical')->willReturn($absolute_url !== NULL);

    $this->fileUrlGenerator->generateString($uri)
      ->willReturn($generated_url);

    if ($absolute_url !== NULL) {
      $file_url = $this->getMockBuilder(Url::class)
        ->disableOriginalConstructor()
        ->getMock();
      $file_url->method('setAbsolute')->willReturnSelf();
      $file_url->method('toString')->willReturn($absolute_url);
      // Support both toUrl() and toUrl('canonical') calls.
      $file->toUrl()->willReturn($file_url);
      $file->toUrl('canonical')->willReturn($file_url);
    }

    return $file;
  }

  /**
   * Set up file storage with query.
   *
   * @param array<int>|null $file_ids
   *   The file IDs to return from the query (NULL for no results).
   * @param \Drupal\file\FileInterface|null $file
   *   The file entity to return when loading (optional).
   */
  protected function setUpFileStorage(?array $file_ids = [1], ?FileInterface $file = NULL): void {
    $file_storage = $this->prophesize(EntityStorageInterface::class);
    $file_query = $this->getMockBuilder(QueryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $file_query->method('accessCheck')->willReturnSelf();
    $file_query->method('condition')->willReturnSelf();
    $file_query->method('range')->willReturnSelf();
    $file_query->method('execute')->willReturn($file_ids ?? []);
    $file_storage->getQuery()->willReturn($file_query);

    if ($file !== NULL && !empty($file_ids)) {
      $file_storage->load(reset($file_ids))->willReturn($file);
    }

    $this->entityTypeManager->getStorage('file')
      ->willReturn($file_storage->reveal());
  }

  /**
   * Set up media type configuration.
   *
   * @param string $bundle
   *   The media type bundle name.
   * @param string $source_field
   *   The source field name.
   */
  protected function setUpMediaType(string $bundle = 'image', string $source_field = 'field_media_image'): void {
    $media_type = $this->prophesize(MediaTypeInterface::class);
    $media_type_source = $this->prophesize(MediaSourceInterface::class);
    $media_type_source->getConfiguration()->willReturn([
      'source_field' => $source_field,
    ]);
    $media_type->getSource()->willReturn($media_type_source->reveal());

    $media_type_storage = $this->prophesize(EntityStorageInterface::class);
    $media_type_storage->load($bundle)->willReturn($media_type->reveal());
    $this->entityTypeManager->getStorage('media_type')
      ->willReturn($media_type_storage->reveal());

    $this->bundleInfo->getBundleInfo('media')->willReturn([
      $bundle => ['label' => ucfirst($bundle)],
    ]);
  }

  /**
   * Set up a mock media entity with attributes.
   *
   * @param array<string, mixed> $attributes
   *   The media attributes (alt, width, height, title, etc.).
   * @param string $source_field
   *   The source field name.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\media\MediaInterface>
   *   The mocked media entity.
   */
  protected function setUpMediaEntity(array $attributes = ['alt' => 'Test alt text'], string $source_field = 'field_media_image'): ObjectProphecy {
    $media = $this->prophesize(MediaInterface::class);
    $media_source = $this->prophesize(MediaSourceInterface::class);
    $media_source->getConfiguration()->willReturn([
      'source_field' => $source_field,
    ]);
    $media->getSource()->willReturn($media_source->reveal());

    $field_item = $this->prophesize(FieldItemInterface::class);
    $field_item->getValue()->willReturn(array_merge(['target_id' => 1], $attributes));

    $field_list = $this->prophesize(FieldItemListInterface::class);
    $field_list->first()->willReturn($field_item->reveal());
    $media->get($source_field)->willReturn($field_list->reveal());

    return $media;
  }

  /**
   * Set up media storage with query.
   *
   * @param array<int>|null $media_ids
   *   The media IDs to return from the query (NULL for no results).
   * @param \Drupal\media\MediaInterface|null $media
   *   The media entity to return when loading (optional).
   */
  protected function setUpMediaStorage(?array $media_ids = [1], ?MediaInterface $media = NULL): void {
    $media_storage = $this->prophesize(EntityStorageInterface::class);
    $media_query = $this->getMockBuilder(QueryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $media_query->method('accessCheck')->willReturnSelf();
    $media_query->method('condition')->willReturnSelf();
    $media_query->method('range')->willReturnSelf();
    $media_query->method('execute')->willReturn($media_ids ?? []);
    $media_storage->getQuery()->willReturn($media_query);

    if ($media !== NULL && !empty($media_ids)) {
      $media_storage->load(reset($media_ids))->willReturn($media);
    }

    $this->entityTypeManager->getStorage('media')
      ->willReturn($media_storage->reveal());
  }

  /**
   * Test URL normalization for relative URLs.
   *
   * @param string $input_url
   *   The input URL to normalize.
   * @param string $expected
   *   The expected normalized URL.
   *
   * @covers ::normalizeUrl
   * @dataProvider providerNormalizeUrlRelative
   */
  public function testNormalizeUrlRelative(string $input_url, string $expected): void {
    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('normalizeUrl');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $input_url);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testNormalizeUrlRelative().
   *
   * @return array
   *   An array of test cases, each containing:
   *   - input_url: The URL to normalize.
   *   - expected: The expected normalized URL.
   */
  public static function providerNormalizeUrlRelative(): array {
    return [
      'relative URL without query/fragment' => [
        '/sites/default/files/image.jpg',
        '/sites/default/files/image.jpg',
      ],
      'relative URL with query parameters' => [
        '/sites/default/files/image.jpg?w=100&h=200',
        '/sites/default/files/image.jpg',
      ],
      'relative URL with fragment' => [
        '/sites/default/files/image.jpg#section',
        '/sites/default/files/image.jpg',
      ],
      'relative URL with trailing slash' => [
        '/sites/default/files/image.jpg/',
        '/sites/default/files/image.jpg',
      ],
      'relative URL with both query and fragment' => [
        '/sites/default/files/image.jpg?w=100#section',
        '/sites/default/files/image.jpg',
      ],
      'relative URL with multiple trailing slashes' => [
        '/sites/default/files/image.jpg///',
        '/sites/default/files/image.jpg',
      ],
    ];
  }

  /**
   * Test URL normalization for absolute URLs matching local site.
   *
   * @covers ::normalizeUrl
   * @covers ::stripLocalBaseUrl
   */
  public function testNormalizeUrlAbsoluteLocalSite(): void {
    // Mock request with standard HTTP port.
    $request = new Request();
    $request->server->set('HTTPS', 'off');
    $request->server->set('SERVER_NAME', 'example.com');
    $request->server->set('SERVER_PORT', '80');
    $this->requestStack->getCurrentRequest()->willReturn($request);

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $method = $reflection->getMethod('normalizeUrl');
    $method->setAccessible(TRUE);

    // Test absolute URL matching local site (HTTP, standard port).
    $result = $method->invoke($this->service, 'http://example.com/sites/default/files/image.jpg');
    $this->assertEquals('/sites/default/files/image.jpg', $result);

    // Test absolute URL with standard port explicitly included.
    $result = $method->invoke($this->service, 'http://example.com:80/sites/default/files/image.jpg');
    $this->assertEquals('/sites/default/files/image.jpg', $result);
  }

  /**
   * Test URL normalization for absolute URLs with HTTPS.
   *
   * @covers ::normalizeUrl
   * @covers ::stripLocalBaseUrl
   */
  public function testNormalizeUrlAbsoluteLocalSiteHttps(): void {
    // Mock request with HTTPS.
    $request = new Request();
    $request->server->set('HTTPS', 'on');
    $request->server->set('SERVER_NAME', 'example.com');
    $request->server->set('SERVER_PORT', '443');
    $this->requestStack->getCurrentRequest()->willReturn($request);

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('normalizeUrl');
    $method->setAccessible(TRUE);

    // Test absolute HTTPS URL matching local site.
    $result = $method->invoke($this->service, 'https://example.com/sites/default/files/image.jpg');
    $this->assertEquals('/sites/default/files/image.jpg', $result);

    // Test absolute HTTPS URL with standard port explicitly included.
    $result = $method->invoke($this->service, 'https://example.com:443/sites/default/files/image.jpg');
    $this->assertEquals('/sites/default/files/image.jpg', $result);
  }

  /**
   * Test URL normalization for absolute URLs with non-standard port.
   *
   * @covers ::normalizeUrl
   * @covers ::stripLocalBaseUrl
   */
  public function testNormalizeUrlAbsoluteLocalSiteNonStandardPort(): void {
    // Mock request with non-standard port.
    $this->setUpRequest('http', 'example.com', 8080);

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('normalizeUrl');
    $method->setAccessible(TRUE);

    // Test absolute URL with non-standard port matching local site.
    $result = $method->invoke($this->service, 'http://example.com:8080/sites/default/files/image.jpg');
    $this->assertEquals('/sites/default/files/image.jpg', $result);
  }

  /**
   * Test URL normalization for external URLs.
   *
   * @covers ::normalizeUrl
   * @covers ::stripLocalBaseUrl
   */
  public function testNormalizeUrlExternal(): void {
    // Mock request.
    $this->setUpRequest();

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('normalizeUrl');
    $method->setAccessible(TRUE);

    // Test external URL (different host) - should not strip base URL.
    $result = $method->invoke($this->service, 'http://external.com/sites/default/files/image.jpg');
    $this->assertEquals('http://external.com/sites/default/files/image.jpg', $result);

    // Test external URL (different scheme).
    $this->setUpRequest('http');
    $result = $method->invoke($this->service, 'https://example.com/sites/default/files/image.jpg');
    $this->assertEquals('https://example.com/sites/default/files/image.jpg', $result);
  }

  /**
   * Test URL normalization when no request is available.
   *
   * @covers ::normalizeUrl
   * @covers ::stripLocalBaseUrl
   */
  public function testNormalizeUrlNoRequest(): void {
    // Mock request stack returning NULL (CLI/Drush context).
    $this->requestStack->getCurrentRequest()->willReturn(NULL);

    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('normalizeUrl');
    $method->setAccessible(TRUE);

    // Should fall back to normal normalization without base URL stripping.
    $result = $method->invoke($this->service, 'http://example.com/sites/default/files/image.jpg');
    $this->assertEquals('http://example.com/sites/default/files/image.jpg', $result);
  }

  /**
   * Test getAttributesByUrl with successful file and media lookup.
   *
   * @covers ::getAttributesByUrl
   * @covers ::findFileByUrl
   * @covers ::findMediaByFile
   * @covers ::extractMediaAttributes
   */
  public function testGetAttributesByUrlSuccess(): void {
    // Setup: Mock request for base URL normalization.
    $this->setUpRequest();

    // Setup: Mock file entity and storage.
    $file = $this->setUpFileEntity(
      1,
      'public://image.jpg',
      '/sites/default/files/image.jpg',
      'http://example.com/sites/default/files/image.jpg'
    );
    $this->setUpFileStorage([1], $file->reveal());

    // Setup: Mock media type configuration.
    $this->setUpMediaType();

    // Setup: Mock media entity with attributes.
    $media = $this->setUpMediaEntity([
      'alt' => 'Test alt text',
      'width' => 800,
      'height' => 600,
      'title' => 'Test title',
    ]);
    $this->setUpMediaStorage([1], $media->reveal());

    // Execute: Get attributes by URL.
    $result = $this->service->getAttributesByUrl('/sites/default/files/image.jpg');

    // Assert: Should return attributes.
    $this->assertIsArray($result);
    $this->assertEquals('Test alt text', $result['alt']);
    $this->assertEquals(800, $result['width']);
    $this->assertEquals(600, $result['height']);
    $this->assertEquals('Test title', $result['title']);
    $this->assertArrayNotHasKey('target_id', $result);
  }

  /**
   * Test getAttributesByUrl with absolute URL matching local site.
   *
   * @covers ::getAttributesByUrl
   * @covers ::normalizeUrl
   * @covers ::stripLocalBaseUrl
   */
  public function testGetAttributesByUrlAbsoluteLocalSite(): void {
    // Setup: Mock request for base URL normalization.
    $this->setUpRequest();

    // Setup: Mock file entity and storage.
    $file = $this->setUpFileEntity(
      1,
      'public://image.jpg',
      '/sites/default/files/image.jpg',
      'http://example.com/sites/default/files/image.jpg'
    );
    $this->setUpFileStorage([1], $file->reveal());

    // Setup: Mock media type configuration.
    $this->setUpMediaType();

    // Setup: Mock media entity with attributes.
    $media = $this->setUpMediaEntity(['alt' => 'Test alt text']);
    $this->setUpMediaStorage([1], $media->reveal());

    // Execute: Get attributes by absolute URL matching local site.
    $result = $this->service->getAttributesByUrl('http://example.com/sites/default/files/image.jpg');

    // Assert: Should return attributes (URL was normalized to relative path).
    $this->assertIsArray($result);
    $this->assertEquals('Test alt text', $result['alt']);
  }

  /**
   * Test getAttributesByUrl when file is not found.
   *
   * @covers ::getAttributesByUrl
   * @covers ::findFileByUrl
   */
  public function testGetAttributesByUrlFileNotFound(): void {
    // Setup: Mock request.
    $this->setUpRequest();

    // Setup: Mock file storage returning no results.
    // Need to set up both initial query (empty) and fallback query (empty).
    $this->setUpFileStorage([]);

    // Setup: Mock media type configuration (needed for fallback query path).
    $this->setUpMediaType();
    $this->setUpMediaStorage([]);

    // Execute: Get attributes by URL.
    $result = $this->service->getAttributesByUrl('/sites/default/files/nonexistent.jpg');

    // Assert: Should return empty array.
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Test getAttributesByUrl when media is not found.
   *
   * @covers ::getAttributesByUrl
   * @covers ::findFileByUrl
   * @covers ::findMediaByFile
   */
  public function testGetAttributesByUrlMediaNotFound(): void {
    // Setup: Mock request.
    $this->setUpRequest();

    // Setup: Mock file entity and storage.
    $file = $this->setUpFileEntity(
      1,
      'public://image.jpg',
      '/sites/default/files/image.jpg',
      'http://example.com/sites/default/files/image.jpg'
    );
    $this->setUpFileStorage([1], $file->reveal());

    // Setup: Mock media type configuration.
    $this->setUpMediaType();

    // Setup: Mock media storage returning no results.
    $this->setUpMediaStorage([]);

    // Execute: Get attributes by URL.
    $result = $this->service->getAttributesByUrl('/sites/default/files/image.jpg');

    // Assert: Should return empty array.
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Test getAttributesByUrl with exception handling.
   *
   * @covers ::getAttributesByUrl
   */
  public function testGetAttributesByUrlExceptionHandling(): void {
    // Setup: Mock request.
    $this->setUpRequest();

    // Setup: Mock entity type manager to throw exception.
    $this->entityTypeManager->getStorage('file')
      ->willThrow(new \Exception('Database error'));

    // Setup: Expect error log.
    $this->logger->error(
      'Failed to get media attributes for URL @url: @message',
      Argument::withEntry('@url', '/sites/default/files/image.jpg'),
    )->shouldBeCalled();

    // Execute: Get attributes by URL.
    $result = $this->service->getAttributesByUrl('/sites/default/files/image.jpg');

    // Assert: Should return empty array on exception.
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
