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
	 * Get server type and software information.
	 *
	 * @return array An array containing 'type' and 'software' keys.
	 */
	public static function get_info(): array {
		$software = self::get_software();
		$type     = self::detect_type();

		return [
			'type'     => $type,
			'software' => $software,
		];
	}

	/**
	 * Get the server hostname.
	 *
	 * @return string|null The server hostname or null if not available.
	 */
	public static function get_hostname(): ?string {
		$hostname = gethostname();

		return $hostname !== false ? $hostname : null;
	}

	/**
	 * Get the server's IP address.
	 *
	 * @return string The server's IP address.
	 */
	public static function get_ip(): string {
		return $_SERVER['SERVER_ADDR'] ?? gethostbyname( self::get_hostname() ?? 'localhost' );
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

	/**
	 * Detect the server type.
	 *
	 * @return string The detected server type.
	 */
	private static function detect_type(): string {
		if ( self::is_apache() ) {
			return 'Apache';
		}

		if ( self::is_nginx() ) {
			return 'Nginx';
		}

		if ( self::is_litespeed() ) {
			return 'LiteSpeed';
		}

		if ( self::is_iis() ) {
			return 'IIS';
		}

		return 'Unknown';
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
	 * Check if server supports brotli compression.
	 *
	 * @return bool True if brotli is supported.
	 */
	public static function supports_brotli(): bool {
		return function_exists( 'brotli_compress' );
	}

}