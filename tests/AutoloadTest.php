<?php
/**
 * Autoloading tests.
 *
 * @package ArrayPress\ServerUtils
 */

declare( strict_types=1 );

namespace ArrayPress\ServerUtils\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Every class this library declares can actually be loaded.
 *
 * `Environment` lived in `Enviroment.php` — one letter — so PSR-4 looked for
 * a file that was not there and anything calling it got a fatal "class not
 * found". It worked on an install built with an optimized autoloader, where
 * Composer builds a class map by reading the files rather than by their
 * names, and failed on one built without: right in production, wrong in
 * development, or the other way round depending on how it was installed.
 *
 * So this asserts the names against the files rather than trusting either.
 */
final class AutoloadTest extends TestCase {

	/**
	 * A file's name matches the class it declares.
	 *
	 * Which is what PSR-4 means, and the only thing that makes a plain
	 * autoloader find it.
	 */
	public function test_every_file_is_named_for_its_class(): void {
		$files = (array) glob( dirname( __DIR__ ) . '/src/*.php' );

		$this->assertNotEmpty( $files );

		foreach ( $files as $file ) {
			$source = (string) file_get_contents( (string) $file );

			$this->assertSame(
				1,
				preg_match( '/^(?:final\s+)?(?:abstract\s+)?class\s+(\w+)/m', $source, $found ),
				sprintf( '%s declares no class.', basename( (string) $file ) )
			);

			$this->assertSame(
				$found[1] . '.php',
				basename( (string) $file ),
				sprintf( 'Class %s is in %s, so PSR-4 cannot find it.', $found[1], basename( (string) $file ) )
			);
		}
	}

	/**
	 * And every one of them loads through the autoloader.
	 */
	public function test_every_class_loads(): void {
		foreach ( (array) glob( dirname( __DIR__ ) . '/src/*.php' ) as $file ) {
			$class = 'ArrayPress\\ServerUtils\\' . basename( (string) $file, '.php' );

			$this->assertTrue( class_exists( $class ), sprintf( '%s does not load.', $class ) );
		}
	}
}
