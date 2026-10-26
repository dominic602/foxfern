<?php
/**
 * /portal/ URLs, access control and page rendering.
 */

defined( 'ABSPATH' ) || exit;

class FFP_Router {

	const PROJECT_TABS = array( 'overview', 'updates', 'photos', 'specs', 'documents', 'messages', 'manage', 'record' );

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrites' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'dispatch' ), 1 );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_shortcode( 'ffp_portal_link', array( __CLASS__, 'shortcode_link' ) );
	}

	public static function rewrites() {
		$s = preg_quote( FFP_SLUG, '#' );
		add_rewrite_rule( "^{$s}/?$", 'index.php?ffp_route=dashboard', 'top' );
		add_rewrite_rule( "^{$s}/(login|logout|forgot|reset|customers|settings|new-project)/?$", 'index.php?ffp_route=$matches[1]', 'top' );
		add_rewrite_rule( "^{$s}/project/([0-9]+)/export/(logs|specs)\.csv$", 'index.php?ffp_route=export&ffp_id=$matches[1]&ffp_tab=$matches[2]', 'top' );
		add_rewrite_rule( "^{$s}/project/([0-9]+)/?$", 'index.php?ffp_route=project&ffp_id=$matches[1]', 'top' );
		add_rewrite_rule( "^{$s}/project/([0-9]+)/([a-z]+)/?$", 'index.php?ffp_route=project&ffp_id=$matches[1]&ffp_tab=$matches[2]', 'top' );
		add_rewrite_rule( "^{$s}/file/([0-9]+)/?$", 'index.php?ffp_route=file&ffp_id=$matches[1]', 'top' );
	}

	public static function query_vars( $vars ) {
		return array_merge( $vars, array( 'ffp_route', 'ffp_id', 'ffp_tab' ) );
	}

	public static function admin_menu() {
		add_menu_page( 'Customer Portal', 'Customer Portal', 'ffp_manage_projects', ffp_url(), '', 'dashicons-portfolio', 3 );
	}

	public static function shortcode_link( $atts ) {
		$atts = shortcode_atts( array( 'label' => 'Client Login' ), $atts );
		return '<a class="ffp-portal-link" href="' . esc_url( is_user_logged_in() ? ffp_url() : ffp_url( 'login' ) ) . '">' . esc_html( $atts['label'] ) . '</a>';
	}

	/** Portal pages are private: never cached by LiteSpeed/CDN, never indexed. */
	private static function private_headers() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		do_action( 'litespeed_control_set_nocache', 'Fox & Fern customer portal' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		nocache_headers();
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: same-origin' );
	}

	public static function dispatch() {
		$route = get_query_var( 'ffp_route' );
		if ( ! $route ) {
			return;
		}
		self::private_headers();

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			// PHP drops the whole form when an upload exceeds post_max_size.
			if ( empty( $_POST ) && ! empty( $_SERVER['CONTENT_LENGTH'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( is_user_logged_in() ) {
					ffp_flash( 'error', sprintf( 'That upload was too large for the server (limit %s). Try fewer photos at a time.', size_format( wp_max_upload_size() ) ) );
				}
				wp_safe_redirect( ffp_current_url() );
				exit;
			}
			if ( isset( $_POST['ffp_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				FFP_Actions::handle();
			}
		}

		$id  = absint( get_query_var( 'ffp_id' ) );
		$tab = sanitize_key( (string) get_query_var( 'ffp_tab' ) );

		switch ( $route ) {
			case 'login':
				self::login();
				return;
			case 'forgot':
				ffp_page( 'Reset your password', 'forgot', array(), 'layout-auth' );
				return;
			case 'reset':
				self::reset();
				return;
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( ffp_url( 'login', array( 'redirect_to' => rawurlencode( ffp_current_url() ) ) ) );
			exit;
		}
		if ( get_user_meta( get_current_user_id(), 'ffp_disabled', true ) ) {
			wp_logout();
			wp_safe_redirect( ffp_url( 'login' ) );
			exit;
		}

		switch ( $route ) {
			case 'logout':
				check_admin_referer( 'ffp_logout' );
				wp_logout();
				wp_safe_redirect( ffp_url( 'login', array( 'signed_out' => 1 ) ) );
				exit;
			case 'dashboard':
				self::dashboard();
				return;
			case 'project':
				self::project( $id, $tab ? $tab : 'overview' );
				return;
			case 'file':
				self::file( $id );
				return;
			case 'export':
				self::export( $id, $tab );
				return;
			case 'new-project':
				self::require_staff();
				ffp_page( 'New project', 'project-new', array( 'nav' => 'projects' ) );
				return;
			case 'customers':
				self::require_staff();
				ffp_page( 'Customers', 'customers', array( 'nav' => 'customers' ) );
				return;
			case 'settings':
				if ( ! ffp_is_admin() ) {
					self::not_found();
				}
				ffp_page( 'Settings', 'settings', array( 'nav' => 'settings' ) );
				return;
		}
		self::not_found();
	}

	private static function require_staff() {
		if ( ! ffp_is_staff() ) {
			self::not_found();
		}
	}

	public static function not_found( $message = "We couldn't find that page, or you don't have access to it." ) {
		status_header( 404 );
		ffp_page( 'Not found', 'not-found', array( 'message' => $message ) );
	}

	private static function login() {
		if ( is_user_logged_in() && ! FFP_Actions::$form_error ) {
			wp_safe_redirect( ffp_url() );
			exit;
		}
		ffp_page( 'Sign in', 'login', array(), 'layout-auth' );
	}

	private static function reset() {
		$key   = isset( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$login = isset( $_REQUEST['login'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$user  = FFP_Auth::check_key( $key, $login );
		$title = ( ! is_wp_error( $user ) && get_user_meta( $user->ID, 'ffp_invite_pending', true ) ) ? 'Create your password' : 'Choose a new password';
		ffp_page( $title, 'reset', array( 'key' => $key, 'login' => $login, 'user' => $user, 'heading' => $title ), 'layout-auth' );
	}

	private static function dashboard() {
		$staff    = ffp_is_staff();
		$archived = $staff && ! empty( $_GET['archived'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$projects = FFP_Data::projects_for( get_current_user_id(), $archived );
		$past     = $staff ? array() : FFP_Data::projects_for( get_current_user_id(), true );
		// A customer with a single project goes straight to it.
		if ( ! $staff && 1 === count( $projects ) && ! $past && empty( $_GET['all'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( ffp_project_url( $projects[0]->id ) );
			exit;
		}
		ffp_page( 'Projects', 'dashboard', compact( 'projects', 'past', 'archived', 'staff' ) + array( 'nav' => 'projects' ) );
	}

	private static function project( $id, $tab ) {
		$project = FFP_Data::project( $id );
		if ( ! $project || ! ffp_can_view_project( $id ) ) {
			self::not_found( "We couldn't find that project, or it isn't shared with your account." );
		}
		$staff = ffp_is_staff();
		if ( ! in_array( $tab, self::PROJECT_TABS, true ) || ( 'manage' === $tab && ! $staff ) ) {
			self::not_found();
		}
		$vars = array(
			'project' => $project,
			'tab'     => $tab,
			'staff'   => $staff,
			'nav'     => 'projects',
		);
		if ( 'record' === $tab ) {
			ffp_page( $project->title . ' home record', 'record', $vars, 'layout-print' );
		}
		$vars['badges']   = FFP_Data::new_counts( $project->id, $staff );
		$vars['progress'] = FFP_Data::progress( $project->id, $staff );
		if ( isset( $vars['badges'][ $tab ] ) ) {
			$vars['badges'][ $tab ] = 0;
			ffp_mark_seen( $project->id, $tab );
		}
		ffp_page( $project->title, 'project', $vars );
	}

	private static function file( $id ) {
		$file = FFP_Data::file( $id );
		if ( ! $file || ! ffp_can_view_project( $file->project_id ) || ( ! $file->visible && ! ffp_is_staff() ) ) {
			status_header( 404 );
			exit( 'File not found.' );
		}
		if ( 'log' === $file->owner_type && ! ffp_is_staff() ) {
			$log = FFP_Data::log( $file->owner_id );
			if ( ! $log || ! $log->visible ) {
				status_header( 404 );
				exit( 'File not found.' );
			}
		}
		$size = isset( $_GET['size'] ) ? sanitize_key( $_GET['size'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		FFP_Files::serve( $file, $size, ! empty( $_GET['download'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	private static function export( $id, $what ) {
		$project = FFP_Data::project( $id );
		if ( ! $project || ! ffp_can_view_project( $id ) ) {
			self::not_found();
		}
		if ( 'specs' === $what ) {
			FFP_CSV::export_specs( $project );
		}
		if ( 'logs' === $what && ffp_is_staff() ) {
			FFP_CSV::export_logs( $project );
		}
		self::not_found();
	}
}
