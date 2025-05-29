<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test PatternkitEnvironment behavior with default parameters.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\PatternkitEnvironment
 */
class PatternkitEnvironmentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['patternkit'];

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
   * Test default debug configuration.
   */
  public function testIsDebug() {
    $this->assertFalse($this->environment->isDebug());
  }

  /**
   * Test default feature option behaviors.
   */
  public function testGetFeatureOption() {
    $actual = $this->environment->getFeatureOption('unknown_feature', TRUE);
    $this->assertTrue($actual);

    $default = (array) $this->getRandomGenerator()->object();
    $actual = $this->environment->getFeatureOption('unknown_feature', $default);
    $this->assertEquals($default, $actual);
  }

}
