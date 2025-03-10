<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\patternkit\PatternkitEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test PatternkitEnvironment behavior with debugging parameters enabled.
 */
#[Group('patternkit')]
#[CoversClass(PatternkitEnvironment::class)]
class PatternkitEnvironmentDebugTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'patternkit_debug_test',
  ];

  /**
   * The patternkit environment service being tested.
   *
   * @var \Drupal\patternkit\PatternkitEnvironment|object|null
   */
  protected $environment;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->environment = $this->container->get('patternkit.environment');
  }

  /**
   * Test debug configuration.
   */
  public function testIsDebug() {
    $this->assertTrue($this->environment->isDebug());
  }

  /**
   * Test feature option behaviors.
   */
  public function testGetFeatureOption() {
    $actual = $this->environment->getFeatureOption('unknown_feature', TRUE);
    $this->assertTrue($actual);

    $default = (array) $this->getRandomGenerator()->object();
    $actual = $this->environment->getFeatureOption('unknown_feature', $default);
    $this->assertEquals($default, $actual);

    $actual = $this->environment->getFeatureOption('bypass_pattern_cache', FALSE);
    $this->assertTrue($actual);
  }

}
