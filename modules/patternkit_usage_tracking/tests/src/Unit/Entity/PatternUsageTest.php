<?php

namespace Drupal\Tests\patternkit_usage_tracking\Unit\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\patternkit_usage_tracking\Entity\PatternUsage;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the PatternUsage entity type.
 */
#[Group('patternkit')]
#[Group('patternkit_usage_tracking')]
#[CoversClass(PatternUsage::class)]
class PatternUsageTest extends UnitTestCase {

  /**
   * Test the parseUsagePath() method.
   *
   * @param string $path
   *   The usage path to be parsed.
   * @param array{section: int, uuid: string, content_path: string, top_pattern: string, nested_pattern?: string} $expected
   *   The expected parsed output for the usage path.
   */
  #[DataProvider('providerParseUsagePath')]
  public function testParseUsagePath(string $path, array $expected): void {
    $stub = $this->getMockBuilder(PatternUsage::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $stub->method('get')
      ->with('path')
      ->willReturn((object) ['value' => $path]);

    $actual = $stub->parseUsagePath();
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testParseUsagePath().
   */
  public static function providerParseUsagePath(): array {
    $cases = [];

    $cases['without nested reference'] = [
      '1.b9a66bc2-018a-11ee-be56-0242ac120002:@patternkit/atoms/example_ref/src/example_ref',
      [
        'section' => 1,
        'uuid' => 'b9a66bc2-018a-11ee-be56-0242ac120002',
        'content_path' => '@patternkit/atoms/example_ref/src/example_ref',
        'top_pattern' => '@patternkit/atoms/example_ref/src/example_ref',
      ],
    ];

    $cases['with nested reference'] = [
      '2.409d6716-018b-11ee-be56-0242ac120002:@patternkit/atoms/example_ref/src/example_ref=>@patternkit/atoms/example/src/example',
      [
        'section' => 2,
        'uuid' => '409d6716-018b-11ee-be56-0242ac120002',
        'content_path' => '@patternkit/atoms/example_ref/src/example_ref=>@patternkit/atoms/example/src/example',
        'top_pattern' => '@patternkit/atoms/example_ref/src/example_ref',
        'nested_pattern' => '@patternkit/atoms/example/src/example',
      ],
    ];

    return $cases;
  }

  #[DataProvider('providerIsNested')]
  public function testIsNested(string $path, bool $expected): void {
    $stub = $this->getMockBuilder(PatternUsage::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $stub->method('get')
      ->with('path')
      ->willReturn((object) ['value' => $path]);

    $this->assertEquals($expected, $stub->isNested());
  }

  /**
   * Data provider for testParseUsagePath().
   */
  public static function providerIsNested(): array {
    return [
      [
        '1.5137f6a8-018c-11ee-be56-0242ac120002:@patternkit/atoms/example_ref/src/example_ref',
        FALSE,
      ],
      [
        '2.5dcc2a9c-018c-11ee-be56-0242ac120002:@patternkit/atoms/example_ref/src/example_ref=>@patternkit/atoms/example/src/example',
        TRUE,
      ],
    ];
  }

  #[DataProvider('providerGetLayoutPath')]
  public function testGetLayoutPath(string $path, string $expected): void {
    $stub = $this->getMockBuilder(PatternUsage::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $stub->method('get')
      ->with('path')
      ->willReturn((object) ['value' => $path]);

    $this->assertEquals($expected, $stub->getLayoutPath());
  }

  /**
   * Data provider for testParseUsagePath().
   */
  public static function providerGetLayoutPath(): array {
    $cases = [];

    $cases[] = [
      '1.5137f6a8-018c-11ee-be56-0242ac120002:@patternkit/atoms/example_ref/src/example_ref',
      '1.5137f6a8-018c-11ee-be56-0242ac120002',
    ];
    $cases[] = [
      '2.5dcc2a9c-018c-11ee-be56-0242ac120002:@patternkit/atoms/example_ref/src/example_ref=>@patternkit/atoms/example/src/example',
      '2.5dcc2a9c-018c-11ee-be56-0242ac120002',
    ];

    return $cases;
  }

  public function testSetPath(): void {
    // Define the record's current parent UUID to test against.
    $current_uuid = '5137f6a8-018c-11ee-be56-0242ac120002';

    // Define a valid and an invalid path to test assignments.
    $valid_path = "0.$current_uuid:@patternkit/atoms/example/src/example";
    $invalid_path = "0.a-different-uuid:@patternkit/atoms/example/src/example";

    $stub = $this->getMockBuilder(PatternUsage::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get', 'set'])
      ->getMock();

    // Return the current UUID when requested.
    $stub->method('get')
      ->with('component_uuid')
      ->willReturn((object) ['value' => $current_uuid]);

    // Expect the path to only get set once.
    $stub->expects($this->once())
      ->method('set')
      ->with('path', $valid_path);

    // Expect success the first time.
    $stub->setPath($valid_path);

    // Expect an assertion failure with the invalid path.
    $this->expectExceptionMessage('The UUID of a component should not be changed.');
    $stub->setPath($invalid_path);
  }

  #[DataProvider('providerGetContentPath')]
  public function testGetContentPath(string $path, ?string $expected): void {
    $stub = $this->getMockBuilder(PatternUsage::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $stub->method('get')
      ->with('path')
      ->willReturn((object) ['value' => $path]);

    $this->assertEquals($expected, $stub->getContentPath());
  }

  /**
   * Data provider for testGetContentPath().
   */
  public static function providerGetContentPath(): array {
    $cases = [];

    $cases[] = [
      '1.5137f6a8-018c-11ee-be56-0242ac120002:@patternkit/atoms/example_ref/src/example_ref',
      '@patternkit/atoms/example_ref/src/example_ref',
    ];
    $cases[] = [
      '2.5dcc2a9c-018c-11ee-be56-0242ac120002:@patternkit/atoms/example_ref/src/example_ref=>@patternkit/atoms/example/src/example',
      '@patternkit/atoms/example_ref/src/example_ref=>@patternkit/atoms/example/src/example',
    ];

    return $cases;
  }

  #[DataProvider('providerGetComponentUuidDefaultValue')]
  public function testGetComponentUuidDefaultValue(string $path, string $expected): void {
    $stub = $this->getMockBuilder(PatternUsage::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getLayoutPath'])
      ->getMock();
    $stub->method('getLayoutPath')
      ->willReturn($path);

    $base_field_definition = $this->createMock(BaseFieldDefinition::class);

    $actual = PatternUsage::getComponentUuidDefaultValue($stub, $base_field_definition);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testGetComponentUuidDefaultValue().
   */
  public static function providerGetComponentUuidDefaultValue(): array {
    $cases = [];

    $cases[] = [
      '1.5137f6a8-018c-11ee-be56-0242ac120002',
      '5137f6a8-018c-11ee-be56-0242ac120002',
    ];
    $cases[] = [
      '0.5dcc2a9c-018c-11ee-be56-0242ac120002',
      '5dcc2a9c-018c-11ee-be56-0242ac120002',
    ];

    return $cases;
  }

}
