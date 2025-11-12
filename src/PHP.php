<?php
/**
 * PHP Configuration Utility Class
 *
 * Provides utility functions for checking PHP configuration, extensions,
 * functions, and system requirements for plugin development.
 *
 * @package ArrayPress\ServerUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\ServerUtils;

/**
 * PHP Class
 *
 * Operations for working with PHP configuration and capabilities.
 */
class PHP {

	/**
	 * Get the current PHP version.
	 *
	 * @return string The PHP version.
	 */
	public static function get_version(): string {
		return phpversion();
	}

	/**
	 * Check if PHP meets minimum version requirement.
	 *
	 * @param string $required_version The minimum required PHP version.
	 *
	 * @return bool True if PHP meets the requirement.
	 */
	public static function meets_version_requirement( string $required_version ): bool {
		return version_compare( PHP_VERSION, $required_version, '>=' );
	}

	// ========================================
	// Configuration Values
	// ========================================

	/**
	 * Get PHP memory limit in bytes.
	 *
	 * @return int The memory limit in bytes.
	 */
	public static function get_memory_limit_bytes(): int {
		return wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
	}

	/**
	 * Get maximum execution time.
	 *
	 * @return int The maximum execution time in seconds.
	 */
	public static function get_max_execution_time(): int {
		return (int) ini_get( 'max_execution_time' );
	}

	/**
	 * Get upload maximum filesize in bytes.
	 *
	 * @return int The upload maximum filesize in bytes.
	 */
	public static function get_upload_max_filesize_bytes(): int {
		return wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
	}

	/**
	 * Get post maximum size in bytes.
	 *
	 * @return int The post maximum size in bytes.
	 */
	public static function get_post_max_size_bytes(): int {
		return wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) );
	}

	/**
	 * Get maximum input variables.
	 *
	 * @return int The maximum number of input variables.
	 */
	public static function get_max_input_vars(): int {
		return (int) ini_get( 'max_input_vars' );
	}

	// ========================================
	// Extensions
	// ========================================

	/**
	 * Check if a PHP extension is loaded.
	 *
	 * @param string $extension The extension name.
	 *
	 * @return bool True if the extension is loaded.
	 */
	public static function has_extension( string $extension ): bool {
		return extension_loaded( $extension );
	}

	/**
	 * Get missing extensions from a required list.
	 *
	 * @param array $required_extensions Array of required extension names.
	 *
	 * @return array Array of missing extensions.
	 */
	public static function get_missing_extensions( array $required_extensions ): array {
		$missing = [];
		foreach ( $required_extensions as $extension ) {
			if ( ! self::has_extension( $extension ) ) {
				$missing[] = $extension;
			}
		}

		return $missing;
	}

	// ========================================
	// Functions
	// ========================================

	/**
	 * Check if a function is available and enabled.
	 *
	 * @param string $function The function name.
	 *
	 * @return bool True if the function is available and enabled.
	 */
	public static function has_function( string $function ): bool {
		if ( ! function_exists( $function ) ) {
			return false;
		}

		$disabled_functions = explode( ',', ini_get( 'disable_functions' ) );
		$disabled_functions = array_map( 'trim', $disabled_functions );

		return ! in_array( $function, $disabled_functions, true );
	}

	// ========================================
	// Directives & Settings
	// ========================================

	/**
	 * Check if file uploads are enabled.
	 *
	 * @return bool True if file uploads are enabled.
	 */
	public static function are_uploads_enabled(): bool {
		return filter_var( ini_get( 'file_uploads' ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Check if allow_url_fopen is enabled.
	 *
	 * @return bool True if allow_url_fopen is enabled.
	 */
	public static function is_url_fopen_enabled(): bool {
		return filter_var( ini_get( 'allow_url_fopen' ), FILTER_VALIDATE_BOOLEAN );
	}

	// ========================================
	// Memory & Performance
	// ========================================

	/**
	 * Check if there's sufficient memory available.
	 *
	 * @param string|int $required_memory Required memory (string like '256M' or bytes as int).
	 *
	 * @return bool True if sufficient memory is available.
	 */
	public static function has_sufficient_memory( $required_memory ): bool {
		$memory_limit   = self::get_memory_limit_bytes();
		$required_bytes = is_string( $required_memory ) ?
			wp_convert_hr_to_bytes( $required_memory ) :
			(int) $required_memory;

		// If memory limit is -1, no limit is set
		if ( $memory_limit === - 1 ) {
			return true;
		}

		$current_usage    = memory_get_usage( true );
		$available_memory = $memory_limit - $current_usage;

		return $available_memory >= $required_bytes;
	}

}