<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\TestTools\Random;
use Drupal\Tests\UnitTestCase;
use Drupal\patternkit\PatternkitEnvironment;

/**
 * Test the PatternkitEnvironment class.
 *
 * @coversDefaultClass \Drupal\patternkit\PatternkitEnvironment
 * @group patternkit
 */
class PatternkitEnvironmentTest extends UnitTestCase {

  /**
   * Test debug flag behavior.
   *
   * @covers ::__construct
   * @covers ::isDebug
   */
  public function testIsDebug() {
    // Test default instantiation.
    $environment = new PatternkitEnvironment();
    $this->assertFalse($environment->isDebug());

    // Test instantiation with debug explicitly disabled.
    $environment = new PatternkitEnvironment([
      'debug' => FALSE,
    ]);
    $this->assertFalse($environment->isDebug());

    // Test instantiation with debug explicitly enabled.
    $environment = new PatternkitEnvironment([
      'debug' => TRUE,
    ]);
    $this->assertTrue($environment->isDebug());
  }

  /**
   * @covers ::__construct
   * @covers ::getFeatureOption
   * @dataProvider providerGetFeatureOption
   */
  public function testGetFeatureOption(array $config, string $feature_name, $default, $expected) {
    // Test default instantiation.
    $environment = new PatternkitEnvironment($config);

    $this->assertEquals($expected, $environment->getFeatureOption($feature_name, $default));
  }

  /**
   * Data provider for testGetFeatureOption.
   */
  public static function providerGetFeatureOption() {
    $cases = [];

    $cases['no config boolean option'] = [
      [],
      'my_feature',
      FALSE,
      FALSE,
    ];

    $cases['no config complex option'] = [
      [],
      'my_feature',
      $default = (array) Random::object(),
      $default,
    ];

    $cases['configured option'] = [
      ['features' => ['my_feature' => TRUE]],
      'my_feature',
      FALSE,
      TRUE,
    ];

    $cases['unconfigured option with defined features'] = [
      ['features' => ['my_feature' => TRUE]],
      'undefined_feature',
      'some default',
      'some default',
    ];

    return $cases;
  }

}
