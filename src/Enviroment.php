<?php
/**
 * Environment Utility Class
 *
 * Provides utility functions for detecting server environments and hosting platforms.
 * Focuses on environment type detection (localhost, staging, production) and hosting platform identification.
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
			'/dev\.|\.dev/',
			'/test\.|\.test/',
			'/beta\.|\.beta/',
			'/demo\.|\.demo/',
		];

		foreach ( $staging_patterns as $pattern ) {
			if ( preg_match( $pattern, $url ) ) {
				return true;
			}
		}

		// Check environment variables
		$env_indicators = [
			'WP_ENV'        => [ 'staging', 'development' ],
			'ENVIRONMENT'   => [ 'staging', 'development' ],
			'APP_ENV'       => [ 'staging', 'development' ],
			'WORDPRESS_ENV' => [ 'staging', 'development' ],
		];

		foreach ( $env_indicators as $var => $values ) {
			$env_value = getenv( $var ) ?: ( defined( $var ) ? constant( $var ) : null );
			if ( $env_value && in_array( strtolower( $env_value ), $values, true ) ) {
				return true;
			}
		}

		// Check for staging constants
		if ( defined( 'WP_STAGE' ) && WP_STAGE === 'staging' ) {
			return true;
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

	// ========================================
	// Hosting Platform Detection
	// ========================================

	/**
	 * Check if running on WordPress.com.
	 *
	 * @return bool True if WordPress.com hosting.
	 */
	public static function is_wordpress_com(): bool {
		return defined( 'IS_WPCOM' ) && IS_WPCOM;
	}

	/**
	 * Check if running on WP Engine.
	 *
	 * @return bool True if WP Engine hosting.
	 */
	public static function is_wp_engine(): bool {
		return defined( 'WPE_APIKEY' ) ||
		       class_exists( 'WpeCommon' ) ||
		       isset( $_SERVER['IS_WPE'] );
	}

	/**
	 * Check if running on Kinsta.
	 *
	 * @return bool True if Kinsta hosting.
	 */
	public static function is_kinsta(): bool {
		return isset( $_SERVER['KINSTA_CACHE_ZONE'] ) ||
		       defined( 'KINSTAMU_VERSION' );
	}

	/**
	 * Check if running on SiteGround.
	 *
	 * @return bool True if SiteGround hosting.
	 */
	public static function is_siteground(): bool {
		return isset( $_SERVER['SG_CACHEPRESS_SUPERCACHER'] ) ||
		       function_exists( 'sg_cachepress_purge_cache' );
	}

	/**
	 * Check if running on Cloudways.
	 *
	 * @return bool True if Cloudways hosting.
	 */
	public static function is_cloudways(): bool {
		return isset( $_SERVER['cw_allowed_ip'] ) ||
		       strpos( gethostname() ?: '', 'cloudways' ) !== false;
	}

	/**
	 * Check if running on Pantheon.
	 *
	 * @return bool True if Pantheon hosting.
	 */
	public static function is_pantheon(): bool {
		return isset( $_ENV['PANTHEON_ENVIRONMENT'] ) ||
		       defined( 'PANTHEON_ENVIRONMENT' );
	}

	/**
	 * Check if running on Flywheel.
	 *
	 * @return bool True if Flywheel hosting.
	 */
	public static function is_flywheel(): bool {
		return defined( 'FLYWHEEL_CONFIG_DIR' ) ||
		       strpos( $_SERVER['SERVER_SOFTWARE'] ?? '', 'Flywheel' ) !== false;
	}

	/**
	 * Detect hosting platform.
	 *
	 * @return string|null The hosting platform name or null if unknown.
	 */
	public static function get_hosting_platform(): ?string {
		$platforms = [
			'WordPress.com' => 'is_wordpress_com',
			'WP Engine'     => 'is_wp_engine',
			'Kinsta'        => 'is_kinsta',
			'SiteGround'    => 'is_siteground',
			'Cloudways'     => 'is_cloudways',
			'Pantheon'      => 'is_pantheon',
			'Flywheel'      => 'is_flywheel',
		];

		foreach ( $platforms as $platform => $method ) {
			if ( self::$method() ) {
				return $platform;
			}
		}

		return null;
	}

	// ========================================
	// Container & Virtualization Detection
	// ========================================

	/**
	 * Check if running in a Docker container.
	 *
	 * @return bool True if running in Docker.
	 */
	public static function is_docker(): bool {
		return file_exists( '/.dockerenv' ) ||
		       ( file_exists( '/proc/1/cgroup' ) &&
		         strpos( file_get_contents( '/proc/1/cgroup' ), 'docker' ) !== false );
	}

	/**
	 * Check if running in a virtual machine.
	 *
	 * @return bool True if running in a VM.
	 */
	public static function is_virtual_machine(): bool {
		// Check for common VM indicators
		$vm_indicators = [
			'/proc/cpuinfo'   => [ 'hypervisor', 'vmware', 'virtualbox', 'kvm' ],
			'/proc/scsi/scsi' => [ 'vmware', 'vbox' ],
		];

		foreach ( $vm_indicators as $file => $patterns ) {
			if ( file_exists( $file ) ) {
				$content = strtolower( file_get_contents( $file ) );
				foreach ( $patterns as $pattern ) {
					if ( strpos( $content, $pattern ) !== false ) {
						return true;
					}
				}
			}
		}

		return false;
	}

}