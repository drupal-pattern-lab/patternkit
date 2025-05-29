<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\patternkit\PatternkitEnvironment;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;

/**
 * Test the PatternkitEnvironment class.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\PatternkitEnvironment
 */
class PatternkitEnvironmentTest extends UnitTestCase {

  /**
   * Test debug flag behavior.
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
   * Test getting a feature option.
   *
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

    // cspell:disable-next-line
    $cases['unconfigured option with defined features'] = [
      ['features' => ['my_feature' => TRUE]],
      'undefined_feature',
      'some default',
      'some default',
    ];

    return $cases;
  }

}
