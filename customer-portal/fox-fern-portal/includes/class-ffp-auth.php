<?php
/**
 * Sign-in, password reset, customer invites, sign-in throttling and keeping customers out of wp-admin.
 */

defined( 'ABSPATH' ) || exit;

class FFP_Auth {

	const MAX_FAILS   = 5;
	const LOCK_WINDOW = 15 * MINUTE_IN_SECONDS;

	public static function init() {
		add_filter( 'wp_authenticate_user', array( __CLASS__, 'block_disabled' ), 10, 1 );
		add_action( 'admin_init', array( __CLASS__, 'keep_customers_out_of_admin' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
		add_action( 'wp_login', array( __CLASS__, 'record_login' ), 10, 2 );
	}

	public static function is_customer_only( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		return $user instanceof WP_User && $user->exists()
			&& in_array( 'ffp_customer', (array) $user->roles, true )
			&& ! user_can( $user, 'edit_posts' )
			&& ! user_can( $user, 'ffp_manage_projects' );
	}

	public static function block_disabled( $user ) {
		if ( $user instanceof WP_User && get_user_meta( $user->ID, 'ffp_disabled', true ) ) {
			return new WP_Error( 'ffp_disabled', 'Portal access for this account is turned off. Please call us if you need access.' );
		}
		return $user;
	}

	public static function keep_customers_out_of_admin() {
		if ( wp_doing_ajax() || ! self::is_customer_only() ) {
			return;
		}
		wp_safe_redirect( ffp_url() );
		exit;
	}

	public static function admin_bar( $show ) {
		return self::is_customer_only() ? false : $show;
	}

	public static function login_redirect( $redirect_to, $requested, $user ) {
		return ( $user instanceof WP_User && self::is_customer_only( $user ) ) ? ffp_url() : $redirect_to;
	}

	public static function record_login( $login, $user ) {
		update_user_meta( $user->ID, 'ffp_last_login', current_time( 'mysql' ) );
	}

	private static function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/** Signs a user in, locking out a login/IP pair after repeated failures. */
	public static function sign_in( $login, $password, $remember ) {
		$key   = 'ffp_lf_' . md5( strtolower( trim( $login ) ) . '|' . self::client_ip() );
		$fails = (int) get_transient( $key );
		if ( $fails >= self::MAX_FAILS ) {
			return new WP_Error( 'ffp_locked', 'Too many sign-in attempts. Please wait 15 minutes, or reset your password below.' );
		}
		$user = wp_signon(
			array( 'user_login' => $login, 'user_password' => $password, 'remember' => $remember ),
			is_ssl()
		);
		if ( is_wp_error( $user ) ) {
			if ( 'ffp_disabled' === $user->get_error_code() ) {
				return $user;
			}
			set_transient( $key, $fails + 1, self::LOCK_WINDOW );
			return new WP_Error( 'ffp_bad_login', "That email and password don't match our records." );
		}
		delete_transient( $key );
		return $user;
	}

	public static function find_user( $login ) {
		$login = trim( (string) $login );
		if ( '' === $login ) {
			return false;
		}
		return is_email( $login ) ? get_user_by( 'email', $login ) : get_user_by( 'login', $login );
	}

	/** Builds a set-password link for invites and resets. */
	public static function password_link( $user ) {
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		return ffp_url( 'reset', array( 'key' => $key, 'login' => rawurlencode( $user->user_login ) ) );
	}

	/** Validates a reset key. Invite links stay valid for 7 days; reset links for WordPress's default 24 hours. */
	public static function check_key( $key, $login ) {
		$user = get_user_by( 'login', $login );
		if ( ! $user ) {
			return new WP_Error( 'ffp_bad_key', 'This link is no longer valid.' );
		}
		$extend = function () {
			return 7 * DAY_IN_SECONDS;
		};
		if ( get_user_meta( $user->ID, 'ffp_invite_pending', true ) ) {
			add_filter( 'password_reset_expiration', $extend );
		}
		$result = check_password_reset_key( $key, $login );
		remove_filter( 'password_reset_expiration', $extend );
		return is_wp_error( $result ) ? new WP_Error( 'ffp_bad_key', 'This link has expired or was already used.' ) : $result;
	}

	/** Creates a portal customer, or returns the existing account with that email. */
	public static function create_customer( $name, $email, $phone = '' ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'ffp_email', 'Please enter a valid email address.' );
		}
		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			if ( ! self::is_customer_only( $existing ) && ( ffp_is_staff( $existing->ID ) || user_can( $existing, 'edit_posts' ) ) ) {
				return new WP_Error( 'ffp_team_email', 'That email belongs to a team or website account, so it can’t be used for a customer login.' );
			}
			if ( ! in_array( 'ffp_customer', (array) $existing->roles, true ) ) {
				$existing->add_role( 'ffp_customer' );
			}
			if ( $phone && ! get_user_meta( $existing->ID, 'ffp_phone', true ) ) {
				update_user_meta( $existing->ID, 'ffp_phone', $phone );
			}
			return $existing;
		}

		$login = sanitize_user( strtolower( $email ), true );
		$base  = $login;
		$n     = 2;
		while ( username_exists( $login ) ) {
			$login = $base . $n++;
		}
		$parts = preg_split( '/\s+/', trim( $name ), 2 );
		$id    = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => $name ? $name : $email,
				'first_name'   => $parts[0] ?? '',
				'last_name'    => $parts[1] ?? '',
				'role'         => 'ffp_customer',
			)
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_user_meta( $id, 'ffp_phone', $phone );
		update_user_meta( $id, 'ffp_invite_pending', 1 );
		return get_userdata( $id );
	}

	/** Emails a customer their set-password link. Returns the link so staff can also text it. */
	public static function send_invite( $user, $project = null ) {
		$link = self::password_link( $user );
		if ( is_wp_error( $link ) ) {
			return $link;
		}
		update_user_meta( $user->ID, 'ffp_invited_at', current_time( 'mysql' ) );
		FFP_Notify::invite( $user, $link, $project );
		return $link;
	}

	public static function customer_state( $user ) {
		if ( get_user_meta( $user->ID, 'ffp_disabled', true ) ) {
			return array( 'disabled', 'Access off' );
		}
		if ( get_user_meta( $user->ID, 'ffp_invite_pending', true ) ) {
			$sent = get_user_meta( $user->ID, 'ffp_invited_at', true );
			return array( 'pending', $sent ? 'Invited ' . ffp_date( $sent ) : 'Not invited yet' );
		}
		$last = get_user_meta( $user->ID, 'ffp_last_login', true );
		return array( 'active', $last ? 'Last signed in ' . ffp_ago( $last ) : 'Active' );
	}
}
