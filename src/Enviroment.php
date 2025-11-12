<?php
/**
 * Environment Utility Class
 *
 * Provides utility functions for detecting server environments.
 * Focuses on environment type detection (localhost, staging, production).
 *
 * @package ArrayPress\ServerUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\ServerUtils;

/**
 * Environment Class
 *
 * Operations for detecting and working with server environments.
 */
class Environment {

	/**
	 * Environment types.
	 */
	public const TYPE_LOCALHOST = 'localhost';
	public const TYPE_STAGING = 'staging';
	public const TYPE_PRODUCTION = 'production';
	public const TYPE_DEVELOPMENT = 'development';

	/**
	 * Get the current environment type.
	 *
	 * @return string The environment type (localhost, staging, development, production).
	 */
	public static function get_type(): string {
		if ( self::is_localhost() ) {
			return self::TYPE_LOCALHOST;
		}

		if ( self::is_staging() ) {
			return self::TYPE_STAGING;
		}

		if ( self::is_development() ) {
			return self::TYPE_DEVELOPMENT;
		}

		return self::TYPE_PRODUCTION;
	}

	/**
	 * Check if running on localhost.
	 *
	 * @return bool True if localhost environment.
	 */
	public static function is_localhost(): bool {
		$domains_to_check = array_unique( [
			wp_parse_url( get_site_url(), PHP_URL_HOST ),
			wp_parse_url( get_home_url(), PHP_URL_HOST ),
		] );

		$localhost_indicators = [
			'localhost',
			'localhost.localdomain',
			'127.0.0.1',
			'::1',
			'local.wordpress.test',
			'local.wordpress-trunk.test',
			'src.wordpress-develop.test',
			'build.wordpress-develop.test',
		];

		foreach ( $domains_to_check as $domain ) {
			if ( ! $domain ) {
				return true;
			}

			// Check explicit localhost domains
			if ( in_array( $domain, $localhost_indicators, true ) ) {
				return true;
			}

			// Check for .test, .local, .dev domains
			if ( preg_match( '/\.(test|local|dev)$/i', $domain ) ) {
				return true;
			}

			// Check for IP addresses in private ranges
			if ( filter_var( $domain, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if running on staging environment.
	 *
	 * @return bool True if staging environment.
	 */
	public static function is_staging(): bool {
		// Check for staging indicators in URL
		$url              = get_site_url();
		$staging_patterns = [
			'/staging\.|\.staging/',
			'/stage\.|\.stage/',
			'/test\.|\.test/',
			'/demo\.|\.demo/',
		];

		foreach ( $staging_patterns as $pattern ) {
			if ( preg_match( $pattern, $url ) ) {
				return true;
			}
		}

		// Check environment variables
		$env_indicators = [
			'WP_ENV'      => [ 'staging', 'stage' ],
			'ENVIRONMENT' => [ 'staging', 'stage' ],
			'APP_ENV'     => [ 'staging', 'stage' ],
		];

		foreach ( $env_indicators as $var => $values ) {
			$env_value = getenv( $var ) ?: ( defined( $var ) ? constant( $var ) : null );
			if ( $env_value && in_array( strtolower( $env_value ), $values, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if running on development environment.
	 *
	 * @return bool True if development environment.
	 */
	public static function is_development(): bool {
		// Check WordPress debug constants
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		// Check environment variables
		$env_value = getenv( 'WP_ENV' ) ?: ( defined( 'WP_ENV' ) ? WP_ENV : null );
		if ( $env_value && strtolower( $env_value ) === 'development' ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if running on production environment.
	 *
	 * @return bool True if production environment.
	 */
	public static function is_production(): bool {
		return ! self::is_localhost() && ! self::is_staging() && ! self::is_development();
	}

}