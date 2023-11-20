# Patternkit Usage Tracking

The Patternkit Usage Tracking module (`patternkit_usage_tracking`) is a
submodule of the Patternkit Drupal module. The intent of it is to provide
tracking and insight into where patterns are used throughout the content of
a site where this was previously inaccessible.

## Goals

The primary goals of the Patternkit Usage Tracking module and its provided
functionality and reports are to answer the following questions:

- What patterns are used within the content of a site?
- Where are specific patterns used within the content of a site?
- When using patterns that may reference and nest other patterns within
  their content, what patterns are referenced and where?

## Installation

### Module and dependency installation

First, the module itself will need to be enabled.

```bash
drush en -y patternkit patternkit_usage_tracking
```

### Indexing

Once the module is installed, existing content will need to be indexed to
track pattern usages across the site. This may be performed via batch processing
using Drush. (An administrative UI for this operation is intended for addition
soon.)

```bash
drush patternkit:usage:scan
```

This process should only be necessary once. After the module has been
installed, **pattern usage data should be recorded moving forward whenever a
layout-enabled entity is created, updated, or deleted**.

### Review

Once some usage data has been recorded, reports of discovered usages may be
found within the site reports section: Reports > Pattern usage
(`/admin/reports/pattern-usage`).

Currently, these reports only show usage tracking for patterns placed on
Node layouts. These reports are built using Views, however, so tracking on
additional entities may be exposed by adding additional Views displays.

## Assumptions

* Only Patternkit blocks placed within overridden or default layouts for
  entities are captured.
* Existing reporting views only display records on Node content.
* Existing Views integration only provides built-in relationships for Node
  content, but additional relationship data may be added by altering Views data.
