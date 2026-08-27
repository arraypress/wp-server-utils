<?php
/**
 * Which web server this is.
 *
 * @package ArrayPress\ServerUtils
 */

declare( strict_types=1 );

namespace ArrayPress\ServerUtils\Tests;

use ArrayPress\ServerUtils\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What answers here decides whether a plugin writes .htaccess rules that
 * nginx will never read, or tells somebody to add rules by hand on a server
 * that would have accepted the file.
 */
final class ServerTest extends TestCase {

	protected function setUp(): void {
		$this->server( '' );
	}

	protected function tearDown(): void {
		$this->server( '' );
		unset( $_SERVER['HTTP_CF_RAY'], $_SERVER['HTTP_CF_CONNECTING_IP'] );
	}

	/**
	 * Set the server software, clearing the static cache behind it.
	 *
	 * The value is read once and kept, which is right in a request and wrong
	 * across tests: without this the first test decides the answer for all
	 * the rest.
	 *
	 * @param string $software The SERVER_SOFTWARE header.
	 */
	private function server( string $software ): void {
		$_SERVER['SERVER_SOFTWARE'] = $software;

		( new \ReflectionProperty( Server::class, 'server_software' ) )->setValue( null, null );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function software(): array {
		return [
			'apache'    => [ 'Apache/2.4.62 (Unix)', 'is_apache' ],
			'nginx'     => [ 'nginx/1.27.0', 'is_nginx' ],
			'litespeed' => [ 'LiteSpeed', 'is_litespeed' ],
			'iis'       => [ 'Microsoft-IIS/10.0', 'is_iis' ],
		];
	}

	/**
	 * @param string $software The header.
	 * @param string $method   The check that should answer true.
	 */
	#[DataProvider( 'software' )]
	public function test_it_recognises_the_server( string $software, string $method ): void {
		$this->server( $software );

		$this->assertTrue( Server::$method(), $software . ' was not recognised' );
		$this->assertSame( $software, Server::get_software() );
	}

	/**
	 * Local runs Flywheel, which is nginx wearing a different name.
	 *
	 * Worth keeping: it is what every one of these libraries is developed
	 * against, so a rule written only for Apache is a rule nobody sees fail.
	 */
	public function test_flywheel_is_nginx(): void {
		$this->server( 'Flywheel/5.0' );

		$this->assertTrue( Server::is_nginx() );
		$this->assertFalse( Server::is_apache() );
	}

	/**
	 * Nothing but Apache is asked to read an .htaccess.
	 *
	 * Apache alone is not enough either: supports_htaccess() also wants
	 * mod_rewrite, which is only visible through apache_get_modules() --
	 * absent under PHP-FPM and under CLI. So on a perfectly capable
	 * Apache/FPM host this answers false, and a caller that treats that as
	 * "cannot protect a folder" gives up too early. It is a conservative
	 * answer by design; worth knowing it is conservative.
	 */
	public function test_nothing_but_apache_supports_htaccess(): void {
		$this->server( 'nginx/1.27.0' );
		$this->assertFalse( Server::supports_htaccess() );

		$this->server( 'Apache/2.4.62 (Unix)' );
		$this->assertFalse(
			Server::has_mod_rewrite(),
			'apache_get_modules() is not available here, so this cannot be true.'
		);
	}

	/**
	 * nginx and LiteSpeed rewrite without being asked about a module.
	 */
	public function test_rewriting_is_assumed_where_it_is_configured_elsewhere(): void {
		$this->server( 'nginx/1.27.0' );
		$this->assertTrue( Server::supports_url_rewriting() );

		$this->server( 'LiteSpeed' );
		$this->assertTrue( Server::supports_url_rewriting() );
	}

	/**
	 * An unknown server claims nothing.
	 */
	public function test_an_unknown_server_answers_no_to_everything(): void {
		$this->server( 'Caddy/2.8.4' );

		foreach ( [ 'is_apache', 'is_nginx', 'is_litespeed', 'is_iis' ] as $check ) {
			$this->assertFalse( Server::$check(), $check . ' claimed Caddy' );
		}
	}

	/**
	 * Cloudflare is a header on the request, not a server name.
	 */
	public function test_cloudflare_is_detected_from_its_headers(): void {
		$this->assertFalse( Server::is_cloudflare() );

		$_SERVER['HTTP_CF_RAY'] = '8a1b2c3d4e5f6789-LHR';

		$this->assertTrue( Server::is_cloudflare() );
	}
}
