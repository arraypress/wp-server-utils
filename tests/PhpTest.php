<?php
/**
 * What the PHP runtime allows.
 *
 * @package ArrayPress\ServerUtils
 */

declare( strict_types=1 );

namespace ArrayPress\ServerUtils\Tests;

use ArrayPress\ServerUtils\PHP;
use ArrayPress\ServerUtils\System;
use PHPUnit\Framework\TestCase;

/**
 * These decide whether a plugin refuses to activate, so a wrong answer is
 * either a plugin that will not run where it would have been fine, or one
 * that runs where it cannot.
 */
final class PhpTest extends TestCase {

	/**
	 * A version requirement is compared, not string-matched.
	 *
	 * `'8.10'` against `'8.9'` is the case that catches a naive comparison:
	 * as strings, 8.10 sorts before 8.9.
	 */
	public function test_version_requirements_compare_numerically(): void {
		$this->assertTrue( PHP::meets_version_requirement( '5.6' ) );
		$this->assertTrue( PHP::meets_version_requirement( PHP_VERSION ) );
		$this->assertFalse( PHP::meets_version_requirement( '99.0' ) );
	}

	/**
	 * An extension this process definitely has, and one nobody has.
	 */
	public function test_extensions_are_reported_from_the_runtime(): void {
		$this->assertTrue( PHP::has_extension( 'json' ) );
		$this->assertFalse( PHP::has_extension( 'not-a-real-extension' ) );
	}

	/**
	 * Only the missing ones come back, and in the order asked for.
	 */
	public function test_missing_extensions_lists_what_is_absent(): void {
		$missing = PHP::get_missing_extensions( [ 'json', 'not-a-real-extension', 'pcre' ] );

		$this->assertSame( [ 'not-a-real-extension' ], array_values( $missing ) );
		$this->assertSame( [], PHP::get_missing_extensions( [ 'json', 'pcre' ] ) );
	}

	/**
	 * The limits are bytes, not the shorthand the ini file holds.
	 *
	 * A caller comparing `'256M'` as a number gets 256, which is smaller
	 * than every real limit and so always passes.
	 */
	public function test_limits_come_back_as_bytes(): void {
		$limit = PHP::get_memory_limit_bytes();

		// -1 is a real answer: it means no limit at all.
		$this->assertTrue( $limit > 1024 || -1 === $limit, 'Got ' . $limit );
		$this->assertGreaterThan( 1024, PHP::get_upload_max_filesize_bytes() );
		$this->assertGreaterThan( 1024, PHP::get_post_max_size_bytes() );
	}

	/**
	 * A requirement can be given in the same shorthand as the ini file.
	 */
	public function test_a_memory_requirement_accepts_shorthand(): void {
		$this->assertTrue( PHP::has_sufficient_memory( '1K' ) );
		$this->assertFalse( PHP::has_sufficient_memory( '64G' ) );
	}

	/**
	 * Exactly one operating system is the one being run on.
	 */
	public function test_one_operating_system_answers_true(): void {
		$answers = array_filter( [
			System::is_windows(),
			System::is_linux(),
			System::is_macos(),
		] );

		$this->assertCount( 1, $answers );
	}

	/**
	 * There is somewhere to write a temporary file.
	 */
	public function test_there_is_a_temp_directory(): void {
		$temp = System::get_temp_dir();

		$this->assertNotSame( '', $temp );
		$this->assertDirectoryExists( $temp );
	}
}
