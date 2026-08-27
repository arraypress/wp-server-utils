<?php
/**
 * Which kind of site this is.
 *
 * @package ArrayPress\ServerUtils
 */

declare( strict_types=1 );

namespace ArrayPress\ServerUtils\Tests;

use ArrayPress\ServerUtils\Environment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Getting this wrong is not cosmetic. A plugin asks whether it is on
 * production to decide whether to run a licence check, take a real payment
 * or report an analytics event -- so a live site told it is staging quietly
 * stops doing the things it is there to do, and nobody notices for a while.
 */
final class EnvironmentTest extends TestCase {

	/**
	 * Point the stubs at a site.
	 *
	 * @param string $url The site URL.
	 */
	private function site( string $url ): void {
		$GLOBALS['su_site_url'] = $url;
	}

	protected function tearDown(): void {
		$this->site( 'https://example.com' );
	}

	/**
	 * Hosts that are somebody's own machine.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function local_hosts(): array {
		return [
			'localhost'        => [ 'http://localhost' ],
			'loopback'         => [ 'http://127.0.0.1' ],
			'a .test domain'   => [ 'http://mysite.test' ],
			'a .local domain'  => [ 'http://mysite.local' ],
		];
	}

	/**
	 * @param string $url The site URL.
	 */
	#[DataProvider( 'local_hosts' )]
	public function test_a_local_host_is_localhost( string $url ): void {
		$this->site( $url );

		$this->assertTrue( Environment::is_localhost() );
		$this->assertSame( 'localhost', Environment::get_type() );
		$this->assertFalse( Environment::is_production() );
	}

	/**
	 * Hosts that are a staging copy.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function staging_hosts(): array {
		return [
			'a staging subdomain' => [ 'https://staging.example.com' ],
			'a stage subdomain'   => [ 'https://stage.example.com' ],
			'a demo subdomain'    => [ 'https://demo.example.com' ],
			'a staging suffix'    => [ 'https://example.staging' ],
		];
	}

	/**
	 * @param string $url The site URL.
	 */
	#[DataProvider( 'staging_hosts' )]
	public function test_a_staging_host_is_staging( string $url ): void {
		$this->site( $url );

		$this->assertTrue( Environment::is_staging() );
		$this->assertFalse( Environment::is_production() );
	}

	/**
	 * Hosts that merely contain one of those words.
	 *
	 * The patterns used to run over the whole URL and match the word
	 * anywhere in it, so `latest.example.com` was staging -- it contains
	 * "test." -- and so was any site whose path happened to say demo.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function production_hosts(): array {
		return [
			'latest'              => [ 'https://latest.example.com' ],
			'a word ending in it' => [ 'https://protest.example.com' ],
			'a word starting it'  => [ 'https://staging-archive.example.com' ],
			'in the path only'    => [ 'https://example.com/demo/page' ],
			'a plain host'        => [ 'https://example.com' ],
		];
	}

	/**
	 * @param string $url The site URL.
	 */
	#[DataProvider( 'production_hosts' )]
	public function test_a_word_inside_a_label_is_not_staging( string $url ): void {
		$this->site( $url );

		$this->assertFalse(
			Environment::is_staging(),
			$url . ' was read as staging.'
		);
	}

	/**
	 * A private IP is local; a public one is not.
	 *
	 * The check for this tested only the flagged call, which returns false
	 * for anything that is not an IP at all -- so every ordinary domain
	 * failed it and was read as local. is_localhost() was true everywhere,
	 * get_type() was always "localhost", and is_production() was never true.
	 *
	 * @param string $url   The site URL.
	 * @param bool   $local Whether it should be read as local.
	 */
	#[DataProvider( 'addresses' )]
	public function test_only_a_private_address_is_local( string $url, bool $local ): void {
		$this->site( $url );

		$this->assertSame( $local, Environment::is_localhost(), $url );
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function addresses(): array {
		return [
			'a private range'    => [ 'http://192.168.1.10', true ],
			'the 10 range'       => [ 'http://10.0.0.5', true ],
			'loopback'           => [ 'http://127.0.0.1', true ],
			'a public address'   => [ 'http://8.8.8.8', false ],
			'an ordinary domain' => [ 'https://example.com', false ],
		];
	}

	/**
	 * Localhost wins over staging, which wins over production.
	 *
	 * A `.test` host satisfies both of the first two, and the answer has to
	 * be the more specific one.
	 */
	public function test_the_most_specific_answer_wins(): void {
		$this->site( 'http://staging.mysite.test' );

		$this->assertTrue( Environment::is_localhost() );
		$this->assertTrue( Environment::is_staging() );
		$this->assertSame( 'localhost', Environment::get_type() );
	}

	/**
	 * Production is the absence of the others, not a test of its own.
	 */
	public function test_production_is_what_is_left(): void {
		$this->site( 'https://example.com' );

		$this->assertFalse( Environment::is_localhost() );
		$this->assertFalse( Environment::is_staging() );
		$this->assertSame( 'production', Environment::get_type() );
		$this->assertTrue( Environment::is_production() );
	}
}
