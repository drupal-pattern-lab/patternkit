<?php

namespace Drupal\Tests\patternkit_media_library\Unit;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\patternkit_media_library\JsonEditorFormElementAlterHandler;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Unit tests for the JsonEditorFormElementAlterHandler class.
 *
 * @coversDefaultClass \Drupal\patternkit_media_library\JsonEditorFormElementAlterHandler
 * @group patternkit
 * @group patternkit_media_library
 */
class JsonEditorFormElementAlterHandlerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * A mocked URL generator service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Routing\UrlGeneratorInterface>
   */
  protected ObjectProphecy $urlGenerator;

  /**
   * An assembled service container for testing.
   *
   * @var \Drupal\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * A mocked config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigFactoryInterface|MockObject $configFactory;

  /**
   * Default configuration values to use for testing.
   *
   * @var array
   */
  protected array $config = [
    'patternkit_media_library.settings' => [
      'use_styles' => TRUE,
      'media_mapping' => [
        'image_types' => [
          0 => 'image',
        ],
        'video_types' => [
          0 => 'remote_video',
          1 => 'video',
        ],
      ],
      'media_display_mode' => 'patternkit',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->getConfigFactoryStub($this->config);
    $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);

    $this->contextManager = $this->prophesize(CacheContextsManager::class);
    $this->contextManager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $this->container = new ContainerBuilder();

    $this->container->set('url_generator', $this->urlGenerator->reveal());
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('cache_contexts_manager', $this->contextManager->reveal());

    \Drupal::setContainer($this->container);
  }

  /**
   * @covers ::attachMediaLibrarySupport
   * @uses \Drupal::config()
   * @uses \Drupal::urlGenerator()
   * @uses \Drupal\Core\Render\BubbleableMetadata
   * @uses \Drupal\Core\GeneratedUrl
   */
  public function testAttachMediaLibrarySupport(): void {
    // Mock the route lookup.
    $route = 'patternkit.media_library';
    $media_url = '/patternkit_media_library';
    $generated_url = new GeneratedUrl();
    $generated_url->setGeneratedUrl($media_url);
    $generated_url->setCacheMaxAge(-1);
    $this->urlGenerator->generateFromRoute($route, [], [], TRUE)
      ->willReturn($generated_url);

    // Prepare cache metadata lookups for the config object.
    /** @var \Drupal\Core\Config\ImmutableConfig&\PHPUnit\Framework\MockObject\MockObject $config */
    $config = $this->configFactory->get('patternkit_media_library.settings');
    $config->method('getCacheContexts')
      ->willReturn(['languages:language_interface']);
    $config->method('getCacheTags')
      ->willReturn(['config:patternkit_media_library.settings']);
    $config->method('getCacheMaxAge')->willReturn(-1);

    // Define the expected metadata to be added to the final element.
    $expected_metadata = [
      '#cache' => [
        'contexts' => [
          'languages:language_interface',
        ],
        'tags' => [
          'config:patternkit_media_library.settings',
        ],
        'max-age' => -1,
      ],
      '#attached' => [
        'drupalSettings' => [
          'patternkitEditor' => [
            'imageUrl' => '/patternkit_media_library',
            'videoUrl' => '/patternkit_media_library',
            'mediaMapping' => [
              'image_types' => [
                'image',
              ],
              'video_types' => [
                'remote_video',
                'video',
              ],
            ],
          ],
        ],
        'library' => [
          'patternkit_media_library/patternkit.jsoneditor.media_library',
          'patternkit_media_library/media_library_modal',
        ],
      ],
    ];

    // Create the initial element to work from.
    $element = [
      '#type' => 'jsoneditor_form',

      // Add additional pre-existing metadata to ensure it isn't lost.
      '#cache' => [
        'contexts' => [
          'user',
        ],
      ],
      '#attached' => [
        'library' => [
          'patternkit/patternkit.jsoneditor',
        ],
      ],
    ];

    $result = JsonEditorFormElementAlterHandler::attachMediaLibrarySupport($element);

    // Assemble the expected metadata for the final element including the
    // original values.
    // Note: This simple recursive merge will only work as long as no complex
    // resolution is involved for simplifying cache metadata or deduping
    // attachments.
    $expected = array_merge_recursive($element, $expected_metadata);

    $this->assertEquals($expected, $result);
  }

}
