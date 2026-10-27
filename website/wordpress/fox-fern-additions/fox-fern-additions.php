<?php
/**
 * Plugin Name: Fox & Fern Additions
 * Description: Adds the Bathroom Remodeling in Trappe, PA page (with its Markdown copy and share image) on top of the Fox & Fern Site plugin, links to it from Residential Remodeling and Service Areas, adds it to the sitemaps and AI content map, and fixes the header menu on phones turned sideways. A layer over the Fox & Fern Site plugin's output; deactivate to undo instantly. Retire it once the Fox & Fern Site plugin source carries these changes.
 * Version: 1.0.0
 * Author: Fox & Fern Property Services
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

defined( 'ABSPATH' ) || exit;

/*
 * Everything runs as this file loads, before the Fox & Fern Site plugin (plugins load in alphabetical order),
 * so new pages are answered first and the edits wrap the site plugin's whole response.
 *
 * - routes.json: new URLs and the file each one serves.
 * - edits.json:  exact text edits per URL (applied only when the anchor text is found once and the page doesn't
 *                already link the new page), plus the menu CSS added to every page that has the site header.
 */

const FFA_DIR = __DIR__ . '/';

function ffa_request_path() {
	$path = parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH );
	return is_string( $path ) ? rawurldecode( $path ) : '/';
}

function ffa_is_front_end_get() {
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';
	if ( 'GET' !== $method && 'HEAD' !== $method ) {
		return false;
	}
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || isset( $_GET['rest_route'] ) ) {
		return false;
	}
	return ! preg_match( '#^/(wp-admin|wp-json|wp-content|wp-includes|wp-login\.php|xmlrpc\.php|wp-cron\.php)#', ffa_request_path() );
}

function ffa_json( $name ) {
	static $cache = array();
	if ( ! isset( $cache[ $name ] ) ) {
		$data             = json_decode( (string) @file_get_contents( FFA_DIR . $name ), true );
		$cache[ $name ] = is_array( $data ) ? $data : array();
	}
	return $cache[ $name ];
}

function ffa_http_date( $time ) {
	return gmdate( 'D, d M Y H:i:s', $time ) . ' GMT';
}

/** Answers one of the new URLs and stops, or returns if this request isn't one of them. */
function ffa_serve_routes() {
	$routes = ffa_json( 'routes.json' );
	$path   = ffa_request_path();
	$query  = isset( $_SERVER['QUERY_STRING'] ) && '' !== $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';

	if ( isset( $routes['redirects'][ $path ] ) ) {
		header( 'Location: ' . $routes['redirects'][ $path ] . $query, true, 301 );
		exit;
	}
	if ( ! isset( $routes['files'][ $path ] ) ) {
		return;
	}
	$route = $routes['files'][ $path ];
	$file  = FFA_DIR . $route['file'];
	if ( ! is_readable( $file ) ) {
		return; // let the site plugin answer (its normal 404)
	}
	$modified     = (int) $routes['built'];
	$since        = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) : false;
	$not_modified = $since && $since >= $modified;

	status_header( $not_modified ? 304 : 200 );
	if ( get_option( 'ffa_purge_pending' ) ) {
		// backup for the purge on activation: this URL is always answered by PHP, so its response can clear the cache
		header( 'X-LiteSpeed-Purge: *' );
		delete_option( 'ffa_purge_pending' );
	}
	header( 'Content-Type: ' . $route['type'] );
	header( 'Cache-Control: public, max-age=600' );
	header( 'Last-Modified: ' . ffa_http_date( $modified ) );
	header( 'X-Content-Type-Options: nosniff' );
	if ( ! $not_modified && 'HEAD' !== $_SERVER['REQUEST_METHOD'] ) {
		readfile( $file );
	}
	exit;
}

/** Applies the edits for this URL to the site plugin's response. */
function ffa_apply_edits( $body ) {
	$edits = ffa_json( 'edits.json' );
	$path  = preg_replace( '#/index\.html$#', '/', ffa_request_path() );

	if ( isset( $edits['pages'][ $path ] ) ) {
		$page = $edits['pages'][ $path ];
		if ( false === strpos( $body, $page['skip_if_found'] ) ) {
			foreach ( $page['replace'] as $pair ) {
				if ( 1 === substr_count( $body, $pair[0] ) ) {
					$body = str_replace( $pair[0], $pair[1], $body );
				}
			}
		}
	}

	// header menu fix, on every page that has the site header and doesn't have the fix yet
	$menu = isset( $edits['menu_css'] ) ? $edits['menu_css'] : null;
	if ( $menu && false === strpos( $body, $menu['marker'] ) ) {
		$at  = strpos( $body, $menu['anchor'] );
		$end = false === $at ? false : strpos( $body, '</style>', $at );
		if ( false !== $end ) {
			$body = substr_replace( $body, $menu['css'], $end, 0 );
		}
	}
	return $body;
}

/** Buffers the response so the edits can be applied once the site plugin has written it. */
function ffa_start_layer() {
	$flushed = false;
	ob_start(
		function ( $buffer, $phase ) use ( &$flushed ) {
			if ( $phase & PHP_OUTPUT_HANDLER_CLEAN ) {
				return '';
			}
			if ( ! ( $phase & PHP_OUTPUT_HANDLER_FINAL ) ) {
				$flushed = true; // part of the page already went out: leave the rest as it is
				return $buffer;
			}
			if ( $flushed || '' === $buffer ) {
				return $buffer;
			}
			$edited = ffa_apply_edits( $buffer );
			if ( $edited !== $buffer && ! headers_sent() ) {
				// the page changed, so its length, ETag and date from the site plugin no longer apply
				$modified = (int) ffa_json( 'routes.json' )['built'];
				foreach ( headers_list() as $line ) {
					if ( 0 === stripos( $line, 'Last-Modified:' ) ) {
						$modified = max( $modified, (int) strtotime( substr( $line, 14 ) ) );
					}
				}
				header_remove( 'Content-Length' );
				header_remove( 'ETag' );
				header( 'Last-Modified: ' . ffa_http_date( $modified ) );
			}
			return $edited;
		}
	);
}

function ffa_purge_cache() {
	do_action( 'litespeed_purge_all' ); // LiteSpeed Cache: serve the edited pages right away
}
register_activation_hook(
	__FILE__,
	function () {
		update_option( 'ffa_purge_pending', 1, false );
		ffa_purge_cache();
	}
);
register_deactivation_hook(
	__FILE__,
	function () {
		delete_option( 'ffa_purge_pending' );
		ffa_purge_cache();
	}
);

if ( ffa_is_front_end_get() ) {
	ffa_serve_routes();
	ffa_start_layer();
}
