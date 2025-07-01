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
	 * Get PHP memory limit.
	 *
	 * @return string The memory limit.
	 */
	public static function get_memory_limit(): string {
		return ini_get( 'memory_limit' );
	}

	/**
	 * Get PHP memory limit in bytes.
	 *
	 * @return int The memory limit in bytes.
	 */
	public static function get_memory_limit_bytes(): int {
		return wp_convert_hr_to_bytes( self::get_memory_limit() );
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
	 * Get upload maximum filesize.
	 *
	 * @return string The upload maximum filesize.
	 */
	public static function get_upload_max_filesize(): string {
		return ini_get( 'upload_max_filesize' );
	}

	/**
	 * Get upload maximum filesize in bytes.
	 *
	 * @return int The upload maximum filesize in bytes.
	 */
	public static function get_upload_max_filesize_bytes(): int {
		return wp_convert_hr_to_bytes( self::get_upload_max_filesize() );
	}

	/**
	 * Get post maximum size.
	 *
	 * @return string The post maximum size.
	 */
	public static function get_post_max_size(): string {
		return ini_get( 'post_max_size' );
	}

	/**
	 * Get post maximum size in bytes.
	 *
	 * @return int The post maximum size in bytes.
	 */
	public static function get_post_max_size_bytes(): int {
		return wp_convert_hr_to_bytes( self::get_post_max_size() );
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
	 * Get all loaded PHP extensions.
	 *
	 * @param bool $zend_only Whether to get only Zend extensions.
	 *
	 * @return array Array of loaded extensions.
	 */
	public static function get_loaded_extensions( bool $zend_only = false ): array {
		return $zend_only ? get_loaded_extensions( true ) : get_loaded_extensions();
	}

	/**
	 * Get extension version.
	 *
	 * @param string $extension The extension name.
	 *
	 * @return string|null The extension version or null if not found.
	 */
	public static function get_extension_version( string $extension ): ?string {
		if ( ! self::has_extension( $extension ) ) {
			return null;
		}

		return phpversion( $extension ) ?: null;
	}

	/**
	 * Check multiple extensions at once.
	 *
	 * @param array $extensions Array of extension names.
	 *
	 * @return array Array with extension names as keys and boolean values.
	 */
	public static function check_extensions( array $extensions ): array {
		$results = [];
		foreach ( $extensions as $extension ) {
			$results[ $extension ] = self::has_extension( $extension );
		}

		return $results;
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
	 * @param string $function       The function name.
	 * @param bool   $check_disabled Whether to check if function is disabled.
	 *
	 * @return bool True if the function is available and enabled.
	 */
	public static function has_function( string $function, bool $check_disabled = true ): bool {
		if ( ! function_exists( $function ) ) {
			return false;
		}

		if ( $check_disabled ) {
			return ! self::is_function_disabled( $function );
		}

		return true;
	}

	/**
	 * Check if a function is disabled.
	 *
	 * @param string $function The function name.
	 *
	 * @return bool True if the function is disabled.
	 */
	public static function is_function_disabled( string $function ): bool {
		$disabled_functions = explode( ',', ini_get( 'disable_functions' ) );
		$disabled_functions = array_map( 'trim', $disabled_functions );

		return in_array( $function, $disabled_functions, true );
	}

	/**
	 * Get list of disabled functions.
	 *
	 * @return array Array of disabled function names.
	 */
	public static function get_disabled_functions(): array {
		$disabled = ini_get( 'disable_functions' );
		if ( empty( $disabled ) ) {
			return [];
		}

		return array_map( 'trim', explode( ',', $disabled ) );
	}

	/**
	 * Check multiple functions at once.
	 *
	 * @param array $functions Array of function names.
	 *
	 * @return array Array with function names as keys and boolean values.
	 */
	public static function check_functions( array $functions ): array {
		$results = [];
		foreach ( $functions as $function ) {
			$results[ $function ] = self::has_function( $function );
		}

		return $results;
	}

	// ========================================
	// Directives & Settings
	// ========================================

	/**
	 * Check if a PHP directive is enabled.
	 *
	 * @param string $directive The directive name.
	 *
	 * @return bool True if the directive is enabled.
	 */
	public static function is_directive_enabled( string $directive ): bool {
		return filter_var( ini_get( $directive ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Check if file uploads are enabled.
	 *
	 * @return bool True if file uploads are enabled.
	 */
	public static function are_uploads_enabled(): bool {
		return self::is_directive_enabled( 'file_uploads' );
	}

	/**
	 * Check if allow_url_fopen is enabled.
	 *
	 * @return bool True if allow_url_fopen is enabled.
	 */
	public static function is_url_fopen_enabled(): bool {
		return self::is_directive_enabled( 'allow_url_fopen' );
	}

	// ========================================
	// Memory & Performance
	// ========================================

	/**
	 * Get current memory usage.
	 *
	 * @param bool $real_usage Whether to get real memory usage.
	 *
	 * @return int Memory usage in bytes.
	 */
	public static function get_memory_usage( bool $real_usage = false ): int {
		return memory_get_usage( $real_usage );
	}

	/**
	 * Get peak memory usage.
	 *
	 * @param bool $real_usage Whether to get real memory usage.
	 *
	 * @return int Peak memory usage in bytes.
	 */
	public static function get_peak_memory_usage( bool $real_usage = false ): int {
		return memory_get_peak_usage( $real_usage );
	}

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

		$current_usage    = self::get_memory_usage( true );
		$available_memory = $memory_limit - $current_usage;

		return $available_memory >= $required_bytes;
	}

}