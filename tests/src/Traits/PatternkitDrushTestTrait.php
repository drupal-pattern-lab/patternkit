<?php

namespace Drupal\Tests\patternkit\Traits;

use Drush\TestTraits\DrushTestTrait;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Added Patternkit functions for testing Drush commands.
 *
 * @see \Drush\TestTraits\DrushTestTrait
 *
 * @phpstan-require-extends \Drupal\Tests\BrowserTestBase
 */
trait PatternkitDrushTestTrait {

  use DrushTestTrait;

  /**
   * Assert all expected lines are contained in the command output.
   *
   * All lines are trimmed before comparison, so leading and trailing whitespace
   * is ignored. Redundant whitespace is also removed to simplify comparison.
   *
   * @param string[] $expectedLines
   *   Lines expected to be included in output.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *
   * @see \Drush\TestTraits\DrushTestTrait::getSimplifiedOutput()
   */
  protected function assertOutputContainsLines(array $expectedLines): void {
    $output = $this->getSimplifiedOutput();

    $missing = [];
    foreach ($expectedLines as $line) {
      try {
        $this->assertStringContainsString($line, $output);
      }
      catch (ExpectationFailedException) {
        $missing[] = $line;
      }
    }

    if (!empty($missing)) {
      print_r($output);
      throw new ExpectationFailedException("Output did not contain the following lines:\n" . implode("\n\t", $missing));
    }
  }

  /**
   * Assert all expected lines are contained in the command error output.
   *
   * All lines are trimmed before comparison, so leading and trailing whitespace
   * is ignored. Redundant whitespace is also removed to simplify comparison.
   *
   * @param string[] $expectedLines
   *   Lines expected to be included in output.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *
   * @see \Drush\TestTraits\DrushTestTrait::getSimplifiedErrorOutput()
   */
  protected function assertErrorOutputContainsLines(array $expectedLines): void {
    $output = $this->getSimplifiedErrorOutput();

    $missing = [];
    foreach ($expectedLines as $line) {
      try {
        $this->assertStringContainsString($line, $output);
      }
      catch (ExpectationFailedException) {
        $missing[] = $line;
      }
    }

    if (!empty($missing)) {
      print_r($output);
      throw new ExpectationFailedException("Error output did not contain the following lines:\n" . implode("\n\t", $missing));
    }
  }

}
