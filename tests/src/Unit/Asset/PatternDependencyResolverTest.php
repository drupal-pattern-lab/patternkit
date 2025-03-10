<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use Drupal\patternkit\Asset\PatternDependencyResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the PatternDependencyResolver class.
 */
#[Group('patternkit')]
#[UsesClass('\Drupal\patternkit\Schema\ContextBuilderTrait')]
#[UsesClass('\Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver')]
#[UsesClass('\Drupal\patternkit\Schema\UnresolvedSchema')]
#[UsesClass('\MJS\TopSort\Implementations\FixedArraySort')]
#[CoversClass(PatternDependencyResolver::class)]
class PatternDependencyResolverTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The pattern dependency resolver being tested.
   *
   * @var \Drupal\Tests\patternkit\Unit\Asset\TestPatternDependencyResolver
   */
  protected TestPatternDependencyResolver $resolver;

  /**
   * The pattern discovery service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Asset\PatternDiscoveryInterface>
   */
  protected ObjectProphecy $patternDiscovery;

  /**
   * The entity type manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityTypeManagerInterface>
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * The entity storage handler for the 'patternkit_pattern' entity.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $patternStorage;

  /**
   * The schema data preprocessor factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\DataPreProcessorFactory>
   */
  protected ObjectProphecy $dataPreProcessorFactory;

  /**
   * The context builder service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $contextBuilder;

  /**
   * The patternkit logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * A collection of interrelated patterns and dependencies.
   *
   * @var array
   */
  protected static array $patternDependencies = [
    '@pattern/1' => [],
    '@pattern/2' => ['@pattern/1'],
    '@pattern/3' => ['@pattern/1', '@pattern/2'],
    '@pattern/4' => ['@pattern/4'],
    '@pattern/5' => ['@pattern/4', '@pattern/3'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->patternDiscovery = $this->prophesize(PatternDiscoveryInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);
    $this->dataPreProcessorFactory = $this->prophesize(DataPreProcessorFactory::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->contextBuilder = $this->prophesize(ContextBuilder::class);

    // Create the resolver by default for tests that need no additional
    // configuration.
    $this->resolver = $this->getDependencyResolver();
  }

  /**
   * A utility function to get the dependency resolver with injected mocks.
   *
   * This is a utility function to help defer resolution of mocks to allow
   * individual tests to configure them before instantiation.
   *
   * @return \Drupal\Tests\patternkit\Unit\Asset\TestPatternDependencyResolver
   *   The pattern dependency resolver instance for testing with all configured
   *   mocks injected.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getDependencyResolver(): TestPatternDependencyResolver {
    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    $resolver = new TestPatternDependencyResolver(
      $this->patternDiscovery->reveal(),
      $this->entityTypeManager->reveal(),
      $this->dataPreProcessorFactory->reveal(),
    );

    $resolver->setLogger($this->logger->reveal());
    $resolver->setContextBuilder($this->contextBuilder->reveal());

    return $resolver;
  }

  /**
   * Test recursive pattern dependency resolution.
   */
  #[DataProvider('providerResolvePatternDependencies')]
  public function testResolvePatternDependencies(array $dependencies, string $pattern_id, array $known_dependencies, array $expected) {
    $this->resolver->setDependencies($dependencies);

    $actual = $this->resolver->resolvePatternDependencies($pattern_id, $known_dependencies);

    $this->assertEqualsCanonicalizing($expected, $actual);
  }

  /**
   * Data provider for testResolvePatternDependencies.
   */
  public static function providerResolvePatternDependencies(): array {
    $cases = [];

    $cases['no dependencies'] = [
      static::$patternDependencies,
      '@pattern/1',
      [],
      [],
    ];

    $cases['one dependency'] = [
      static::$patternDependencies,
      '@pattern/2',
      [],
      [
        '@pattern/1',
      ],
    ];

    $cases['one dependency with known dependencies'] = [
      static::$patternDependencies,
      '@pattern/2',
      [
        '@pattern/1',
      ],
      [],
    ];

    $cases['redundant dependencies'] = [
      static::$patternDependencies,
      '@pattern/3',
      [],
      [
        '@pattern/1',
        '@pattern/2',
      ],
    ];

    $cases['circular dependency'] = [
      static::$patternDependencies,
      '@pattern/4',
      [],
      [],
    ];

    $cases['full depth dependencies'] = [
      static::$patternDependencies,
      '@pattern/5',
      [],
      [
        '@pattern/1',
        '@pattern/2',
        '@pattern/3',
        '@pattern/4',
      ],
    ];

    return $cases;
  }

  /**
   * Test loading and ordering of pattern dependencies for loading.
   */
  public function testGetOrderedDependencies() {
    $this->resolver->setDependencies(static::$patternDependencies);

    $ordered_dependencies = $this->resolver->getOrderedDependencies('@pattern/5');

    $this->assertArrayKeyOrder([
      '@pattern/1',
      '@pattern/2',
      '@pattern/3',
      '@pattern/4',
      '@pattern/5',
    ], array_flip($ordered_dependencies));
  }

  public function testGetAllPatternDependencies() {
    $pattern_definitions = [
      '@namespace' => [
        '@namespace/pattern_with_cached_dependencies' => [
          'dependencies' => [
            'first_dependency',
            'second_dependency',
          ],
        ],
      ],
    ];
    $expected_results = [
      '@namespace/pattern_with_cached_dependencies' => [
        'first_dependency',
        'second_dependency',
      ],
    ];

    $this->patternDiscovery->getPatternDefinitions()
      ->willReturn($pattern_definitions);

    // Prepare pattern entity for loading.
    $pattern_definition = $pattern_definitions['@namespace']['@namespace/pattern_with_cached_dependencies'];
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getDependencies()->willReturn($pattern_definition['dependencies']);
    $this->patternStorage->create($pattern_definition)
      ->willReturn($pattern->reveal());

    $dependencies = $this->getDependencyResolver()->getAllPatternDependencies();

    $this->assertEquals($expected_results, $dependencies);
  }

  public function testGetPatternLoadOrderWithoutArguments() {
    // Don't use the class instance variable since the pointers will cause an
    // error.
    $dependencies = [
      '@pattern/1' => [],
      '@pattern/2' => ['@pattern/1'],
      '@pattern/3' => ['@pattern/1', '@pattern/2'],
      '@pattern/4' => ['@pattern/4'],
      '@pattern/5' => ['@pattern/4', '@pattern/3'],
    ];

    $this->resolver->setDependencies($dependencies);

    $order = $this->resolver->getPatternLoadOrder();

    $this->assertArrayKeyOrder([
      '@pattern/1',
      '@pattern/2',
      '@pattern/3',
      '@pattern/4',
      '@pattern/5',
    ], array_flip($order));
  }

  public function testGetPatternLoadOrderWithArguments() {
    // Don't use the class instance variable since the pointers will cause an
    // error.
    $dependencies = [
      '@pattern/1' => [],
      '@pattern/2' => ['@pattern/1'],
      '@pattern/3' => ['@pattern/1', '@pattern/2'],
      '@pattern/4' => ['@pattern/4'],
      '@pattern/5' => ['@pattern/4', '@pattern/3'],
    ];

    $order = $this->resolver->getPatternLoadOrder($dependencies);

    $this->assertArrayKeyOrder([
      '@pattern/1',
      '@pattern/2',
      '@pattern/3',
      '@pattern/4',
      '@pattern/5',
    ], array_flip($order));
  }

  /**
   * Assert the order of appearance of array keys in an array.
   *
   * @param string[] $expected
   *   An array of array keys in the order they're expected to appear.
   * @param array $actual
   *   An associative array to test the sequence of the keys.
   */
  protected function assertArrayKeyOrder(array $expected, array $actual): void {
    $actual_keys = array_keys($actual);

    $indices = [];
    foreach ($expected as $key) {
      $indices[$key] = array_search($key, $actual_keys);
    }

    $last_index = -1;
    $last_key = '';
    foreach ($indices as $key => $index) {
      if ($index === FALSE) {
        $this->fail("Didn't find array key '$key' in array: " .
          implode(', ', $actual_keys));
      }

      $this->assertGreaterThan($last_index, $index,
        sprintf('Expected to find %s before %s.', $key, $last_key));
    }
  }

}
