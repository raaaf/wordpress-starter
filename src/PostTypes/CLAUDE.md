# CLAUDE.md (src/PostTypes)

Rules for custom post types.

## Custom Post Types

Use the abstract base class for consistent CPT registration:

```php
<?php

namespace WordpressStarter\PostTypes;

class Service extends AbstractPostType
{
    protected static string $postType = 'service';
    protected static string $singular = 'Leistung';
    protected static string $plural = 'Leistungen';
    protected static string $genus = 'f'; // 'm', 'f' or 'n': drives Neuer/Neue/Neues in the labels
    protected static string $menuName = ''; // optional sidebar label, defaults to $plural
    protected static string $menuIcon = 'dashicons-admin-generic';

    public static function registerFields(): void
    {
        // Register ACF fields for this CPT
    }
}
```

Add the class to `PostTypeServiceProvider::$postTypes`; `boot()` calls its `register()`.
