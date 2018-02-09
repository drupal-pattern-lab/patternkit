# PatternKit
Panels-based plugin that wraps a PatternLab library of components.
 
This code will parse a pattern library (local or through REST endpoints) to generate a list of "content types" in panels that can be drag/dropped into panel variants.

If enabled, the configuration screen is simply an iframe wrapper of a simplified version of the PatternLab schema builder.

When pattern configurations are saved, the template is downloaded locally (to mitigate origin failures and lock in version at time of configuration.)

Rendered twigs may contain drupal tokens, which are then processed in context.

## Installation
Install the patternkit module as usual, and review the important variables below to determine if you would like to change the defaults.

Install the Twig library into /sites/all/libraries/Twig
```
git clone git://github.com/twigphp/Twig.git -b 1.x /tmp/Twig
mv /tmp/Twig/lib/Twig ${DRUPALDIR}/sites/all/libraries/
rm -rf /tmp/Twig
```

The patternkit module by itself only provides the glue for other modules to present components. Define one by implementing ```hook_patternkit_library```

An example implementation follows
```
/**
 * Implements hook_patternkit_library().
 */
function webrh_patternkit_library() {
  $libraries = array();

  $namespaces = array(
    'Web RH Patterns' => 'webrh/src/library',
  );

  $module_path = drupal_get_path('module', 'webrh');
  foreach ($namespaces as $namespace => $path) {
    $lib_path = $module_path . DIRECTORY_SEPARATOR . $path;
    $libraries[] = new PatternkitDrupalTwigLib($namespace, $lib_path);
  }

  return $libraries;
}
```

There are two different plugins currently available, 
* PatternkitRESTLib
* PatternkitDrupalTwigLib

Use the former for dynamic REST based components, and the latter for locally sourced.

## Important Variables
* ```patternkit_cache_enabled``` - Whether or not the metadata and render cache are enabled. (Disable during development)
* ```patternkit_pl_host``` - The scheme://hostname:port/ of the PatternLab library host.
* ```patternkit_default_module_ttl``` - How long the rendered pattern should be cached.
* ```patternkit_show_errors``` - Whether or not to display messages on the site.
* ```patternkit_log_errors``` - Whether or not to log errors to php error log. 

## TODOs
* https://github.com/drupal-pattern-lab/roadmap/issues/8 Solve the problem of mapping Drupal fields to pattern Variables.
* Error handling.
* Better information about available tokens.
* More investigation into the appropriate handling of web component configuration.
* Finalize the CSS/JS management strategy.
More documentation will be added.

## Dependencies
[PatternLab](https://github.com/pattern-lab/starterkit-twig-drupal-minimal)
* Schema display support requires: https://github.com/pattern-lab/patternlab-php-core/issues/117
* 

# Proposed V2 Ecosystem
* Exists to solve this issue: https://github.com/drupal-pattern-lab/roadmap/issues/8
* PatternLAB with Restful Extensions provides library + endpoints (Ported from PatternKit)
* PatternKit provides Drupal endpoint consumer (renamed from PKPlugins)

# Legacy Ecosystem
* PatternKit provides library + RESTful endpoints
* PKPlugins provides Drupal endpoint consumer
