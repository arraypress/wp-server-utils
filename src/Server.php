<?php
/**
 * Server Utility Class
 *
 * Provides utility functions for detecting web server software and capabilities.
 * Focuses on server type detection and basic server information.
 *
 * @package ArrayPress\ServerUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\ServerUtils;

/**
 * Server Class
 *
 * Core operations for web server detection and capabilities.
 */
class Server {

	/**
	 * Cached server software information.
	 *
	 * @var string|null
	 */
	private static ?string $server_software = null;

	/**
	 * Get the server software information.
	 *
	 * @return string The server software information or empty string if not available.
	 */
	public static function get_software(): string {
		if ( self::$server_software === null ) {
			self::$server_software = isset( $_SERVER['SERVER_SOFTWARE'] )
				? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) )
				: '';
		}

		return self::$server_software;
	}

	/**
	 * Get the server's IP address.
	 *
	 * @return string The server's IP address.
	 */
	public static function get_ip(): string {
		return $_SERVER['SERVER_ADDR'] ?? gethostbyname( gethostname() ?: 'localhost' );
	}

	/**
	 * Get server port.
	 *
	 * @return int The server port.
	 */
	public static function get_port(): int {
		return isset( $_SERVER['SERVER_PORT'] ) ? (int) $_SERVER['SERVER_PORT'] : 80;
	}

	// ========================================
	// Server Type Detection
	// ========================================

	/**
	 * Check if the server is running on Apache.
	 *
	 * @return bool Whether it is running on Apache.
	 */
	public static function is_apache(): bool {
		return stripos( self::get_software(), 'apache' ) !== false;
	}

	/**
	 * Check if the server is running on Nginx.
	 *
	 * @return bool Whether it is running on Nginx.
	 */
	public static function is_nginx(): bool {
		$software = self::get_software();

		return stripos( $software, 'nginx' ) !== false ||
		       stripos( $software, 'flywheel' ) !== false;
	}

	/**
	 * Check if the server is running on LiteSpeed.
	 *
	 * @return bool Whether it is running on LiteSpeed.
	 */
	public static function is_litespeed(): bool {
		return stripos( self::get_software(), 'litespeed' ) !== false;
	}

	/**
	 * Check if the server is running on IIS.
	 *
	 * @return bool Whether it is running on IIS.
	 */
	public static function is_iis(): bool {
		return stripos( self::get_software(), 'microsoft-iis' ) !== false;
	}

	/**
	 * Check if the server is running on Cloudflare.
	 *
	 * @return bool Whether requests are coming through Cloudflare.
	 */
	public static function is_cloudflare(): bool {
		return isset( $_SERVER['HTTP_CF_RAY'] ) || isset( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	}

	// ========================================
	// Server Capabilities
	// ========================================

	/**
	 * Check if the server supports .htaccess files.
	 *
	 * @return bool True if .htaccess is supported.
	 */
	public static function supports_htaccess(): bool {
		return self::is_apache() && self::has_mod_rewrite();
	}

	/**
	 * Check if Apache mod_rewrite is available.
	 *
	 * @return bool True if mod_rewrite is available.
	 */
	public static function has_mod_rewrite(): bool {
		if ( ! self::is_apache() ) {
			return false;
		}

		if ( function_exists( 'apache_get_modules' ) ) {
			return in_array( 'mod_rewrite', apache_get_modules(), true );
		}

		// Fallback check
		return getenv( 'HTTP_MOD_REWRITE' ) === 'On';
	}

	/**
	 * Check if the server supports URL rewriting.
	 *
	 * @return bool True if URL rewriting is supported.
	 */
	public static function supports_url_rewriting(): bool {
		if ( self::is_apache() ) {
			return self::has_mod_rewrite();
		}

		if ( self::is_nginx() || self::is_litespeed() ) {
			return true; // Usually supports rewriting
		}

		if ( self::is_iis() ) {
			return isset( $_SERVER['IIS_UrlRewriteModule'] );
		}

		return false;
	}

	/**
	 * Check if the server supports gzip compression.
	 *
	 * @return bool True if gzip is supported.
	 */
	public static function supports_gzip(): bool {
		return function_exists( 'gzencode' ) || function_exists( 'gzcompress' );
	}

	/**
	 * Check if server supports X-Sendfile for optimized file delivery.
	 *
	 * @return bool True if X-Sendfile is supported.
	 */
	public static function has_xsendfile(): bool {
		// Apache mod_xsendfile
		if ( self::is_apache() && function_exists( 'apache_get_modules' ) ) {
			return in_array( 'mod_xsendfile', apache_get_modules(), true );
		}

		// LiteSpeed supports X-Sendfile natively
		if ( self::is_litespeed() ) {
			return true;
		}

		// Nginx requires manual configuration, check via filter
		if ( self::is_nginx() ) {
			return apply_filters( 'server_nginx_xsendfile', false );
		}

		return false;
	}

	/**
	 * Get Apache modules if available.
	 *
	 * @return array|null Array of module names or null if not Apache/not available.
	 */
	public static function get_apache_modules(): ?array {
		if ( ! self::is_apache() || ! function_exists( 'apache_get_modules' ) ) {
			return null;
		}

		return apache_get_modules();
	}

	/**
	 * Check if a specific Apache module is loaded.
	 *
	 * @param string $module Module name (e.g., 'mod_rewrite', 'mod_headers').
	 *
	 * @return bool True if module is loaded.
	 */
	public static function has_apache_module( string $module ): bool {
		$modules = self::get_apache_modules();

		return $modules !== null && in_array( $module, $modules, true );
	}

}