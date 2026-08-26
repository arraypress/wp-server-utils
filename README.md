# WordPress Server Utilities

Answer "will this actually work on this server?" before you try it.

## What it does

Plenty of plugin features depend on the environment rather than on WordPress:
`.htaccess` rules do nothing on nginx, `X-Sendfile` is not always compiled in,
a big export dies on a low memory limit, and a large upload fails on
`post_max_size` rather than anything you control.

This checks those up front, so you can degrade gracefully or tell the user what
to change instead of failing halfway through.

## Features

- Identify the web server, and whether `.htaccess` and rewriting will work
- Check for `mod_rewrite`, gzip and `X-Sendfile` before relying on them
- Read PHP's memory limit, execution time and upload sizes as bytes
- Check for a required PHP version, extension or function, and list what is missing
- Report free disk space, and whether there is enough for a job
- Tell local, staging and production apart

## Installation

```bash
composer require arraypress/wp-server-utils
```

## Quick start

```php
use ArrayPress\ServerUtils\Environment;
use ArrayPress\ServerUtils\PHP;
use ArrayPress\ServerUtils\Server;

// Only write .htaccess protection where it will be honoured.
if ( Server::supports_htaccess() ) {
    write_protection_files();
}

// Do not start an export that cannot finish.
if ( ! PHP::has_sufficient_memory( '256M' ) ) {
    return new WP_Error( 'memory', 'Increase PHP memory to run this export.' );
}

// Check what is missing rather than failing on the first one.
$missing = PHP::get_missing_extensions( [ 'curl', 'mbstring', 'intl' ] );

// Skip the licence call on a developer's machine.
if ( Environment::is_production() ) {
    check_licence();
}
```

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-2.0-or-later
