<?php
/**
 * System Resources Utility Class
 *
 * Provides utility functions for basic system information and OS detection.
 * Focuses on essential system details needed for plugin development.
 *
 * @package ArrayPress\ServerUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\ServerUtils;

/**
 * System Class
 *
 * Operations for basic system information and OS detection.
 */
class System {

	/**
	 * Get operating system information.
	 *
	 * @return string The operating system information.
	 */
	public static function get_os_info(): string {
		return php_uname();
	}

	/**
	 * Get operating system name.
	 *
	 * @return string The operating system name.
	 */
	public static function get_os_name(): string {
		return php_uname( 's' );
	}

	/**
	 * Get operating system version.
	 *
	 * @return string The operating system version.
	 */
	public static function get_os_version(): string {
		return php_uname( 'r' );
	}

	/**
	 * Get machine architecture.
	 *
	 * @return string The machine architecture.
	 */
	public static function get_architecture(): string {
		return php_uname( 'm' );
	}

	/**
	 * Check if the server is running on Windows.
	 *
	 * @return bool True if running on Windows.
	 */
	public static function is_windows(): bool {
		return stripos( PHP_OS, 'WIN' ) === 0;
	}

	/**
	 * Check if the server is running on Linux.
	 *
	 * @return bool True if running on Linux.
	 */
	public static function is_linux(): bool {
		return stripos( PHP_OS, 'LINUX' ) === 0;
	}

	/**
	 * Check if the server is running on macOS.
	 *
	 * @return bool True if running on macOS.
	 */
	public static function is_macos(): bool {
		return stripos( PHP_OS, 'DARWIN' ) === 0;
	}

	// ========================================
	// Disk Space Information
	// ========================================

	/**
	 * Get disk space information for a directory.
	 *
	 * @param string $directory The directory to check (default: WordPress root).
	 *
	 * @return array|null Disk space information or null if not available.
	 */
	public static function get_disk_space( string $directory = ABSPATH ): ?array {
		if ( ! is_dir( $directory ) ) {
			return null;
		}

		$total = disk_total_space( $directory );
		$free  = disk_free_space( $directory );

		if ( $total === false || $free === false ) {
			return null;
		}

		return [
			'total'   => $total,
			'free'    => $free,
			'used'    => $total - $free,
			'percent' => round( ( ( $total - $free ) / $total ) * 100, 2 ),
		];
	}

	/**
	 * Get available disk space in bytes.
	 *
	 * @param string $directory The directory to check.
	 *
	 * @return int|float|null Available disk space in bytes or null if not available.
	 */
	public static function get_available_disk_space( string $directory = ABSPATH ) {
		$space = disk_free_space( $directory );

		return $space !== false ? $space : null;
	}

	/**
	 * Check if directory has sufficient disk space.
	 *
	 * @param string|int $required_space Required space (string like '1G' or bytes as int).
	 * @param string     $directory      Directory to check.
	 *
	 * @return bool True if sufficient space is available.
	 */
	public static function has_sufficient_disk_space( $required_space, string $directory = ABSPATH ): bool {
		$available = self::get_available_disk_space( $directory );
		if ( $available === null ) {
			return false;
		}

		$required_bytes = is_string( $required_space ) ?
			wp_convert_hr_to_bytes( $required_space ) :
			(int) $required_space;

		return $available >= $required_bytes;
	}

	// ========================================
	// System Load Information
	// ========================================

	/**
	 * Get system load average.
	 *
	 * @return array|null Load average array [1min, 5min, 15min] or null if not available.
	 */
	public static function get_load_average(): ?array {
		if ( function_exists( 'sys_getloadavg' ) ) {
			$load = sys_getloadavg();

			return $load !== false ? $load : null;
		}

		// Try to read from /proc/loadavg on Linux
		if ( self::is_linux() && file_exists( '/proc/loadavg' ) ) {
			$load_string = file_get_contents( '/proc/loadavg' );
			if ( $load_string !== false ) {
				$load_parts = explode( ' ', trim( $load_string ) );

				return [
					(float) $load_parts[0],
					(float) $load_parts[1],
					(float) $load_parts[2],
				];
			}
		}

		return null;
	}

	/**
	 * Check if system load is high.
	 *
	 * @param float $threshold Load threshold (default: 2.0).
	 *
	 * @return bool True if load is above threshold.
	 */
	public static function is_high_load( float $threshold = 2.0 ): bool {
		$load = self::get_load_average();
		if ( $load === null ) {
			return false;
		}

		return $load[0] > $threshold; // Check 1-minute load average
	}

	/**
	 * Get server time in specified format.
	 *
	 * @param string $format The format of the time. Default is 'Y-m-d H:i:s'.
	 *
	 * @return string The formatted server time.
	 */
	public static function get_server_time( string $format = 'Y-m-d H:i:s' ): string {
		return date( $format );
	}

	/**
	 * Get the server's temporary directory path.
	 *
	 * @return string The path to the temporary directory.
	 */
	public static function get_temp_dir(): string {
		return sys_get_temp_dir();
	}

}