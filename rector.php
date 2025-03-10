<?php
// @phpcs:ignoreFile

declare(strict_types=1);

use DrupalRector\Set\Drupal10SetList;
use DrupalRector\Set\Drupal8SetList;
use DrupalRector\Set\Drupal9SetList;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\PHPUnit\Set\PHPUnitSetList;

return static function (RectorConfig $rectorConfig): void {
  // Adjust the set lists to be more granular to your Drupal requirements.
  // @todo find out how to only load the relevant rector rules.
  //   Should we try and load \Drupal::VERSION and check?
  $rectorConfig->sets([
    Drupal8SetList::DRUPAL_8,
    Drupal9SetList::DRUPAL_9,
    Drupal10SetList::DRUPAL_10,
    PHPUnitSetList::PHPUNIT_80,
    PHPUnitSetList::PHPUNIT_90,
    PHPUnitSetList::PHPUNIT_100,
    PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    // PHPUnitSetList::PHPUNIT_CODE_QUALITY,
  ]);

  $drupalRoot = '/var/www/html/web';
  $rectorConfig->autoloadPaths([
    $drupalRoot . '/core',
    $drupalRoot . '/modules',
    $drupalRoot . '/profiles',
    $drupalRoot . '/themes',
  ]);

  $rectorConfig->skip(['*/upgrade_status/tests/modules/*']);
  $rectorConfig->fileExtensions([
    'php',
    'module',
    'theme',
    'install',
    'profile',
    'inc',
    'engine',
  ]);
  $rectorConfig->importNames(TRUE, FALSE);
  $rectorConfig->importShortClasses(FALSE);

  $rectorConfig->paths([
    '/var/www/html/web/modules/custom/patternkit/**/*',
  ]);

//  $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
//  $rectorConfig->ruleWithConfiguration(ClassPropertyAssignToConstructorPromotionRector::class, [
//    ClassPropertyAssignToConstructorPromotionRector::INLINE_PUBLIC => FALSE,
//    ClassPropertyAssignToConstructorPromotionRector::RENAME_PROPERTY => TRUE,
//  ]);
};
