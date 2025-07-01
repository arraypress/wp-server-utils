# WordPress Server Utilities

A lightweight WordPress library focused on server environment detection and basic system information for plugin developers. Complements WordPress core functions without duplication.

## Features

* 🖥️ **Server Detection**: Apache, Nginx, LiteSpeed, IIS identification
* 🔍 **Environment Detection**: Localhost, staging, production detection
* 🏢 **Hosting Platform Detection**: WP Engine, Kinsta, SiteGround, etc.
* 📊 **PHP Information**: Version, extensions, memory, configuration checks
* 💻 **System Basics**: OS detection, disk space, load averages
* 🎯 **Plugin-Focused**: Essential functionality plugin developers need

## Requirements

* PHP 7.4 or later
* WordPress 5.0 or later

## Installation

```bash
composer require arraypress/wp-server-utils
```

## Usage

### Server Detection

```php
use ArrayPress\ServerUtils\Server;

// Check server type
if ( Server::is_apache() ) {
	// Apache-specific code
}

if ( Server::is_nginx() ) {
	// Nginx-specific code  
}

// Get server info
$info = Server::get_info();
// Returns: ['type' => 'Apache', 'software' => 'Apache/2.4.41']

// Check capabilities
if ( Server::supports_htaccess() ) {
	// Can use .htaccess files
}

if ( Server::has_mod_rewrite() ) {
	// mod_rewrite available
}
```

### Environment Detection

```php
use ArrayPress\ServerUtils\Environment;

// Environment checks
if ( Environment::is_localhost() ) {
	// Local development
}

if ( Environment::is_staging() ) {
	// Staging environment
}

if ( Environment::is_production() ) {
	// Production environment
}

// Get environment type
$env = Environment::get_type(); // 'localhost', 'staging', 'production'

// Hosting platform detection
$platform = Environment::get_hosting_platform(); // 'WP Engine', 'Kinsta', etc.

// Container detection
if ( Environment::is_docker() ) {
	// Running in Docker
}
```

### PHP Configuration

```php
use ArrayPress\ServerUtils\PHP;

// Version checks
if ( PHP::meets_version_requirement( '8.0' ) ) {
	// PHP 8.0+
}

// Extension checks  
if ( PHP::has_extension( 'curl' ) ) {
	// cURL available
}

// Memory checks
if ( PHP::has_sufficient_memory( '256M' ) ) {
	// Enough memory
}

// Get values
$memory_limit  = PHP::get_memory_limit();
$max_execution = PHP::get_max_execution_time();
$upload_max    = PHP::get_upload_max_filesize();

// Function availability
if ( PHP::has_function( 'exec' ) ) {
	// exec() available and not disabled
}

// Use WordPress core functions for size conversion
$bytes    = wp_convert_hr_to_bytes( '256M' ); // WordPress core
$readable = size_format( 268435456 ); // WordPress core
```

### System Information

```php
use ArrayPress\ServerUtils\System;

// OS detection
if ( System::is_linux() ) {
	// Linux-specific code
}

if ( System::is_windows() ) {
	// Windows-specific code
}

// Disk space
$disk = System::get_disk_space();
// Returns: ['total' => ..., 'free' => ..., 'used' => ..., 'percent' => ...]

if ( System::has_sufficient_disk_space( '1G' ) ) {
	// At least 1GB available
}

// System load (Unix systems)
$load = System::get_load_average(); // [1min, 5min, 15min]

if ( System::is_high_load( 2.0 ) ) {
	// Load above 2.0
}
```

## What This Library Provides vs WordPress Core

**WordPress Core Has:**
- `is_ssl()` - SSL detection
- `wp_get_server_protocol()` - HTTP protocol
- `wp_is_mobile()` - Mobile detection
- `wp_is_json_request()` - JSON request detection
- `wp_convert_hr_to_bytes()` - Size string to bytes conversion
- `size_format()` - Bytes to human readable conversion

**This Library Adds:**
- Server type detection (Apache, Nginx, etc.)
- Environment detection (localhost, staging, production)
- Hosting platform identification
- PHP requirement validation
- Basic system information

## Key Features

- **Lean & Focused**: Only essential server detection functionality
- **Plugin-Oriented**: Built for plugin developers' common needs
- **WordPress Compatible**: Works alongside core functions
- **Environment Aware**: Different behaviors for different environments
- **Zero Bloat**: No unnecessary features or complex monitoring

## Requirements

- PHP 7.4+
- WordPress 5.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-server-utils)
- [Issue Tracker](https://github.com/arraypress/wp-server-utils/issues)