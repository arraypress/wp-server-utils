<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ArrayPress\ServerUtils
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/*
 * What the library calls out to. Deliberately small: everything here reads
 * $_SERVER, an ini value or a site URL, so the stubs only have to let the
 * tests control those.
 */
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( (string) $url, $component );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'wp_convert_hr_to_bytes' ) ) {
	/**
	 * Core's own, because the byte parsing is what several of these return.
	 */
	function wp_convert_hr_to_bytes( $value ) {
		$value = strtolower( trim( (string) $value ) );
		$bytes = (int) $value;

		if ( str_contains( $value, 'g' ) ) {
			$bytes *= 1024 * 1024 * 1024;
		} elseif ( str_contains( $value, 'm' ) ) {
			$bytes *= 1024 * 1024;
		} elseif ( str_contains( $value, 'k' ) ) {
			$bytes *= 1024;
		}

		return min( $bytes, PHP_INT_MAX );
	}
}

/**
 * The site URL the environment tests are pointing at.
 *
 * @var string
 */
$GLOBALS['su_site_url'] = 'https://example.com';

if ( ! function_exists( 'get_site_url' ) ) {
	function get_site_url( $blog_id = null, $path = '', $scheme = null ) {
		return $GLOBALS['su_site_url'];
	}
}

if ( ! function_exists( 'get_home_url' ) ) {
	function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
		return $GLOBALS['su_site_url'];
	}
}
