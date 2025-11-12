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
	 * Check if directory has sufficient disk space.
	 *
	 * @param string|int $required_space Required space (string like '1G' or bytes as int).
	 * @param string     $directory      Directory to check.
	 *
	 * @return bool True if sufficient space is available.
	 */
	public static function has_sufficient_disk_space( $required_space, string $directory = ABSPATH ): bool {
		$free = disk_free_space( $directory );
		if ( $free === false ) {
			return false;
		}

		$required_bytes = is_string( $required_space ) ?
			wp_convert_hr_to_bytes( $required_space ) :
			(int) $required_space;

		return $free >= $required_bytes;
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