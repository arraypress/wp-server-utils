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

			/*
			 * A private or reserved IP -- 192.168.x, 10.x, 127.x.
			 *
			 * Both halves are needed. FILTER_VALIDATE_IP returns false for
			 * anything that is not an IP at all, so testing only the flagged
			 * call answered "localhost" for every ordinary domain name:
			 * example.com is not an IP, so it failed the filter, so it was
			 * read as local. Which made is_localhost() true everywhere,
			 * get_type() always "localhost", and is_production() never true.
			 */
			$is_ip = false !== filter_var( $domain, FILTER_VALIDATE_IP );

			if ( $is_ip && false === filter_var( $domain, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
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
		/*
		 * Matched against the host, and only at a label boundary.
		 *
		 * The patterns used to be run over the whole URL and to accept the
		 * word anywhere in it, so `latest.example.com` was staging -- it
		 * contains "test." -- and so was any path containing the word. A
		 * production site being told it is staging is not a small mistake:
		 * it is what decides whether a licence check, a payment gateway or
		 * an analytics call runs in live mode.
		 */
		$host = (string) wp_parse_url( get_site_url(), PHP_URL_HOST );

		if ( '' !== $host && preg_match( '/(^|\.)(staging|stage|test|demo)(\.|$)/i', $host ) ) {
			return true;
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
