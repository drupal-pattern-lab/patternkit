# pkplugins

Panels based plugin that wraps a pattern-builder/patternkit library of components. 
This code will hit the patternkit RESTful endpoints to generate a list of "content types" in panels that can be drag/dropped into panel variants. The configuration screen is simply an iframe wrapper of a simplified version of the patternkit schema builder.

When pattern configurations are saved, the template is downloaded locally (to mitigate origin failures and lock in version at time of configuration.)

Rendered twigs may contain drupal tokens, which are then processed in context.

## Important Variables

* pkplugins_cache_enabled - Whether or not the metadata and render cache are enabled. (Disable during development)
* pkplugins_pk_host - The scheme://hostname:port/ of the patternkit library host.
* pkplugins_default_module_ttl - How long the rendered pattern should be cached.
* pkplugins_show_errors - Whether or not to display messages on the site.
* pkplugins_log_errors - Whether or not to log errors to php error log. 

## TODOs

* Error handling.
* Better information about available tokens.
* More investigation into the appropriate handling of web component configuration.
* Finalize the CSS/JS management strategy.
More documentation will be added.
