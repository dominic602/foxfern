<?php
/**
 * Form handlers. Every portal form posts an ffp_action plus a nonce; each handler checks
 * permissions, saves, sets a flash message and redirects (post/redirect/get).
 */

defined( 'ABSPATH' ) || exit;

class FFP_Actions {

	/** Error shown on the logged-out forms (login, forgot, reset), which re-render instead of redirecting. */
	public static $form_error = '';
	public static $form_done  = false;

	const PUBLIC_ACTIONS = array( 'login', 'forgot', 'reset' );
	const MEMBER_ACTIONS = array( 'add_comment' );
	const ADMIN_ACTIONS  = array( 'save_settings' );

	public static function handle() {
		$action = isset( $_POST['ffp_action'] ) ? sanitize_key( wp_unslash( $_POST['ffp_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$method = 'do_' . $action;
		if ( ! $action || ! method_exists( __CLASS__, $method ) ) {
			wp_die( 'Unknown action.', 'Error', array( 'response' => 400 ) );
		}

		$nonce = isset( $_POST['_ffp_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ffp_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! wp_verify_nonce( $nonce, 'ffp_' . $action ) ) {
			if ( in_array( $action, self::PUBLIC_ACTIONS, true ) ) {
				self::$form_error = 'Your session timed out. Please try again.';
				return;
			}
			ffp_flash( 'error', 'That form expired before it was sent. Please try again.' );
			self::back();
		}

		if ( ! in_array( $action, self::PUBLIC_ACTIONS, true ) ) {
			if ( ! is_user_logged_in() ) {
				wp_safe_redirect( ffp_url( 'login' ) );
				exit;
			}
			$allowed = in_array( $action, self::MEMBER_ACTIONS, true )
				|| ( in_array( $action, self::ADMIN_ACTIONS, true ) ? ffp_is_admin() : ffp_is_staff() );
			if ( ! $allowed ) {
				wp_die( 'You do not have permission to do that.', 'Not allowed', array( 'response' => 403 ) );
			}
		}

		call_user_func( array( __CLASS__, $method ) );
	}

	/* ---------- Small helpers ---------- */

	private static function post( $key, $default = '' ) {
		return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : $default; // phpcs:ignore WordPress.Security
	}

	private static function text( $key ) {
		return sanitize_text_field( (string) self::post( $key ) );
	}

	private static function textarea( $key ) {
		return sanitize_textarea_field( (string) self::post( $key ) );
	}

	private static function int( $key ) {
		return absint( self::post( $key, 0 ) );
	}

	private static function flag( $key ) {
		return ! empty( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/** Redirects after a form post; by default back to the page the form was on. */
	private static function back( $url = '' ) {
		wp_safe_redirect( $url ? $url : ffp_current_url(), 303, 'Fox & Fern Portal' );
		exit;
	}

	/** Loads the posted project and confirms the current user may act on it. */
	private static function project() {
		$project = FFP_Data::project( self::int( 'project_id' ) );
		if ( ! $project || ! ffp_can_view_project( $project->id ) ) {
			wp_die( 'Project not found.', 'Not found', array( 'response' => 404 ) );
		}
		return $project;
	}

	private static function upload_errors( $errors ) {
		foreach ( $errors as $error ) {
			ffp_flash( 'error', $error );
		}
	}

	/* ---------- Sign in / password ---------- */

	private static function do_login() {
		$login = trim( (string) self::post( 'log' ) );
		$user  = FFP_Auth::sign_in( $login, (string) self::post( 'pwd' ), self::flag( 'remember' ) );
		if ( is_wp_error( $user ) ) {
			self::$form_error = $user->get_error_message();
			return;
		}
		$to = wp_validate_redirect( (string) self::post( 'redirect_to' ), ffp_url() );
		wp_safe_redirect( str_starts_with( $to, ffp_url() ) ? $to : ffp_url() );
		exit;
	}

	private static function do_forgot() {
		$throttle = 'ffp_fp_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
		$count    = (int) get_transient( $throttle );
		if ( $count < 10 ) {
			set_transient( $throttle, $count + 1, HOUR_IN_SECONDS );
			$user = FFP_Auth::find_user( self::post( 'login' ) );
			if ( $user && ! get_user_meta( $user->ID, 'ffp_disabled', true ) ) {
				$link = FFP_Auth::password_link( $user );
				if ( ! is_wp_error( $link ) ) {
					FFP_Notify::reset( $user, $link );
				}
			}
		}
		// Same answer whether or not the account exists, so the form can't be used to look up emails.
		self::$form_done = true;
	}

	private static function do_reset() {
		$user = FFP_Auth::check_key( (string) self::post( 'key' ), (string) self::post( 'login' ) );
		if ( is_wp_error( $user ) ) {
			self::$form_error = $user->get_error_message();
			return;
		}
		$pass = (string) self::post( 'pass1' );
		if ( strlen( $pass ) < 10 ) {
			self::$form_error = 'Please use at least 10 characters.';
			return;
		}
		if ( $pass !== (string) self::post( 'pass2' ) ) {
			self::$form_error = "The two passwords don't match.";
			return;
		}
		// Don't email the site admin every time a customer sets up or resets their password.
		remove_action( 'after_password_reset', 'wp_password_change_notification' );
		reset_password( $user, $pass );
		delete_user_meta( $user->ID, 'ffp_invite_pending' );
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, false, is_ssl() );
		do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		ffp_flash( 'success', 'Your password is set. Welcome to your project portal.' );
		wp_safe_redirect( ffp_url() );
		exit;
	}

	/* ---------- Projects ---------- */

	private static function do_save_project() {
		$id    = self::int( 'project_id' );
		$title = self::text( 'title' );
		if ( '' === $title ) {
			ffp_flash( 'error', 'Please give the project a name.' );
			self::back();
		}
		if ( $id && ! FFP_Data::project( $id ) ) {
			self::back( ffp_url() );
		}
		$status = self::text( 'status' );
		$id     = FFP_Data::save_project(
			array(
				'title'           => $title,
				'address'         => self::text( 'address' ),
				'status'          => array_key_exists( $status, ffp_project_statuses() ) ? $status : 'planning',
				'description'     => self::textarea( 'description' ),
				'start_date'      => self::text( 'start_date' ),
				'target_date'     => self::text( 'target_date' ),
				'manager_id'      => ffp_is_staff( self::int( 'manager_id' ) ) ? self::int( 'manager_id' ) : 0,
				'notify_customer' => self::flag( 'notify_customer' ),
			),
			$id
		);
		if ( self::int( 'project_id' ) ) {
			ffp_flash( 'success', 'Project details saved.' );
			self::back();
		}
		ffp_flash( 'success', 'Project created. Next, add your customer so they can sign in.' );
		self::back( ffp_project_url( $id, 'manage' ) );
	}

	private static function do_archive_project() {
		$project = self::project();
		$archive = $project->archived ? 0 : 1;
		FFP_Data::update_project_fields( $project->id, array( 'archived' => $archive ) );
		ffp_flash( 'success', $archive ? 'Project archived. Customers can still see it under "Past projects".' : 'Project restored.' );
		self::back();
	}

	private static function do_delete_project() {
		$project = self::project();
		if ( 'DELETE' !== strtoupper( trim( self::text( 'confirm' ) ) ) ) {
			ffp_flash( 'error', 'Type DELETE to confirm. Nothing was removed.' );
			self::back();
		}
		FFP_Data::delete_project( $project->id );
		ffp_flash( 'success', sprintf( '"%s" and all of its files were deleted.', $project->title ) );
		self::back( ffp_url() );
	}

	/* ---------- Customers ---------- */

	private static function do_invite_customer() {
		$project = self::int( 'project_id' ) ? self::project() : null;
		$user    = FFP_Auth::create_customer( self::text( 'name' ), self::text( 'email' ), self::text( 'phone' ) );
		if ( is_wp_error( $user ) ) {
			ffp_flash( 'error', $user->get_error_message() );
			self::back();
		}
		if ( $project ) {
			FFP_Data::add_member( $project->id, $user->ID );
		}
		if ( self::flag( 'send_invite' ) && get_user_meta( $user->ID, 'ffp_invite_pending', true ) ) {
			$link = FFP_Auth::send_invite( $user, $project );
			if ( is_wp_error( $link ) ) {
				ffp_flash( 'error', 'The account was created, but the invite link could not be made: ' . $link->get_error_message() );
			} else {
				ffp_flash( 'success', sprintf( 'Invite emailed to %s. You can also text them this link (valid 7 days):', $user->user_email ), $link );
			}
		} else {
			ffp_flash( 'success', sprintf( '%s now has access%s.', $user->display_name, $project ? ' to this project' : '' ) );
		}
		self::back();
	}

	/**
	 * Loads the posted customer. Only portal-customer accounts qualify, so staff can never
	 * reset, edit or disable an admin or employee account through these forms.
	 */
	private static function customer_from_post() {
		$user = get_userdata( self::int( 'user_id' ) );
		if ( ! $user || ! FFP_Auth::is_customer_only( $user ) ) {
			ffp_flash( 'error', 'That customer account no longer exists.' );
			self::back();
		}
		return $user;
	}

	private static function do_resend_invite() {
		$user    = self::customer_from_post();
		$project = self::int( 'project_id' ) ? self::project() : null;
		update_user_meta( $user->ID, 'ffp_invite_pending', 1 );
		$link = FFP_Auth::send_invite( $user, $project );
		if ( is_wp_error( $link ) ) {
			ffp_flash( 'error', $link->get_error_message() );
		} else {
			ffp_flash( 'success', sprintf( 'New invite sent to %s. Link to text them (valid 7 days):', $user->user_email ), $link );
		}
		self::back();
	}

	private static function do_add_member() {
		$project = self::project();
		$user    = self::customer_from_post();
		FFP_Data::add_member( $project->id, $user->ID );
		ffp_flash( 'success', sprintf( '%s added to this project.', $user->display_name ) );
		self::back();
	}

	private static function do_remove_member() {
		$project = self::project();
		$user    = self::customer_from_post();
		FFP_Data::remove_member( $project->id, $user->ID );
		ffp_flash( 'success', sprintf( '%s no longer has access to this project.', $user->display_name ) );
		self::back();
	}

	private static function do_toggle_customer() {
		$user = self::customer_from_post();
		if ( get_user_meta( $user->ID, 'ffp_disabled', true ) ) {
			delete_user_meta( $user->ID, 'ffp_disabled' );
			ffp_flash( 'success', sprintf( 'Portal access turned back on for %s.', $user->display_name ) );
		} else {
			update_user_meta( $user->ID, 'ffp_disabled', 1 );
			WP_Session_Tokens::get_instance( $user->ID )->destroy_all();
			ffp_flash( 'success', sprintf( 'Portal access turned off for %s. They have been signed out.', $user->display_name ) );
		}
		self::back();
	}

	private static function do_update_customer() {
		$user  = self::customer_from_post();
		$name  = self::text( 'name' );
		$email = sanitize_email( self::text( 'email' ) );
		if ( ! is_email( $email ) ) {
			ffp_flash( 'error', 'Please enter a valid email address.' );
			self::back();
		}
		$other = get_user_by( 'email', $email );
		if ( $other && (int) $other->ID !== (int) $user->ID ) {
			ffp_flash( 'error', 'Another account already uses that email.' );
			self::back();
		}
		$parts = preg_split( '/\s+/', $name, 2 );
		wp_update_user(
			array(
				'ID'           => $user->ID,
				'display_name' => $name ? $name : $email,
				'first_name'   => $parts[0] ?? '',
				'last_name'    => $parts[1] ?? '',
				'user_email'   => $email,
			)
		);
		update_user_meta( $user->ID, 'ffp_phone', self::text( 'phone' ) );
		ffp_flash( 'success', 'Customer details saved.' );
		self::back();
	}

	/* ---------- Daily updates ---------- */

	private static function do_save_log() {
		$project = self::project();
		$log_id  = self::int( 'log_id' );
		$log     = $log_id ? FFP_Data::log( $log_id ) : null;
		if ( $log_id && ( ! $log || (int) $log->project_id !== (int) $project->id ) ) {
			self::back();
		}

		$raw    = (array) self::post( 'fields', array() );
		$values = $log ? $log->values : array();
		foreach ( ffp_log_fields( true ) as $field ) {
			if ( array_key_exists( $field['key'], $raw ) ) {
				$value = ffp_sanitize_field_value( $field, $raw[ $field['key'] ] );
				if ( '' === $value ) {
					unset( $values[ $field['key'] ] );
				} else {
					$values[ $field['key'] ] = $value;
				}
			}
		}
		$visible = self::flag( 'visible' );
		$id      = FFP_Data::save_log( $project->id, self::text( 'log_date' ), $values, $visible, $log_id );

		foreach ( array_map( 'absint', (array) self::post( 'delete_files', array() ) ) as $file_id ) {
			$file = FFP_Data::file( $file_id );
			if ( $file && 'log' === $file->owner_type && (int) $file->owner_id === $id ) {
				FFP_Files::delete( $file );
			}
		}
		list( $photo_ids, $errors ) = FFP_Files::store_uploads(
			'photos',
			$project->id,
			array( 'owner_type' => 'log', 'owner_id' => $id, 'category' => 'progress', 'visible' => 1, 'images_only' => true )
		);
		self::upload_errors( $errors );

		$saved = FFP_Data::log( $id );
		if ( $visible && ! $saved->notified ) {
			FFP_Data::mark_log_notified( $id );
			if ( self::flag( 'notify' ) ) {
				FFP_Notify::new_log( $project, $saved );
			}
		}

		$msg = $log_id ? 'Daily update saved.' : 'Daily update posted.';
		if ( $photo_ids ) {
			$msg .= sprintf( ' %d photo%s added.', count( $photo_ids ), 1 === count( $photo_ids ) ? '' : 's' );
		}
		if ( ! $visible ) {
			$msg .= ' It is a draft, so the customer cannot see it yet.';
		}
		ffp_flash( 'success', $msg );
		self::back( ffp_project_url( $project->id, 'updates' ) . '#log-' . $id );
	}

	private static function do_publish_log() {
		$project = self::project();
		$log     = FFP_Data::log( self::int( 'log_id' ) );
		if ( ! $log || (int) $log->project_id !== (int) $project->id ) {
			self::back();
		}
		FFP_Data::save_log( $project->id, $log->log_date, $log->values, true, $log->id );
		if ( ! $log->notified ) {
			FFP_Data::mark_log_notified( $log->id );
			if ( $project->notify_customer ) {
				FFP_Notify::new_log( $project, FFP_Data::log( $log->id ) );
			}
		}
		ffp_flash( 'success', 'Update shared with the customer.' );
		self::back();
	}

	private static function do_delete_log() {
		$project = self::project();
		$log     = FFP_Data::log( self::int( 'log_id' ) );
		if ( $log && (int) $log->project_id === (int) $project->id ) {
			FFP_Data::delete_log( $log );
			ffp_flash( 'success', 'Daily update deleted.' );
		}
		self::back( ffp_project_url( $project->id, 'updates' ) );
	}

	private static function do_import_csv() {
		$project = self::project();
		$files   = FFP_Files::collect( 'csv' );
		$file    = $files[0] ?? null;
		if ( ! $file || UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
			ffp_flash( 'error', 'Please choose the .csv file you downloaded from Google Sheets.' );
			self::back();
		}
		if ( 'csv' !== strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) ) ) {
			ffp_flash( 'error', 'That file is not a .csv. In Google Sheets use File > Download > Comma-separated values (.csv).' );
			self::back();
		}
		$result = FFP_CSV::import_logs( $project->id, $file['tmp_name'], self::flag( 'visible' ), self::flag( 'add_missing' ) );
		if ( is_wp_error( $result ) ) {
			ffp_flash( 'error', $result->get_error_message() );
			self::back();
		}
		$msg = sprintf( 'Imported %d daily update%s.', $result['imported'], 1 === $result['imported'] ? '' : 's' );
		if ( $result['skipped'] ) {
			$msg .= sprintf( ' Skipped %d row%s (blank, no date, or already imported).', $result['skipped'], 1 === $result['skipped'] ? '' : 's' );
		}
		if ( $result['added'] ) {
			$msg .= ' New fields added: ' . implode( ', ', $result['added'] ) . '.';
		}
		ffp_flash( 'success', $msg );
		if ( $result['unmatched'] ) {
			ffp_flash( 'warning', 'These columns did not match a daily-update field and were not imported: ' . implode( ', ', $result['unmatched'] ) . '. Add them under Settings, or re-import with "Add unmatched columns as new fields" checked.' );
		}
		self::back( ffp_project_url( $project->id, 'updates' ) );
	}

	/* ---------- Photos & documents ---------- */

	private static function do_upload_photos() {
		$project = self::project();
		$tag     = self::text( 'category' );
		list( $ids, $errors ) = FFP_Files::store_uploads(
			'photos',
			$project->id,
			array(
				'owner_type'  => 'gallery',
				'category'    => array_key_exists( $tag, ffp_photo_tags() ) ? $tag : 'progress',
				'caption'     => self::textarea( 'caption' ),
				'visible'     => self::flag( 'visible' ),
				'images_only' => true,
			)
		);
		self::upload_errors( $errors );
		if ( $ids ) {
			ffp_flash( 'success', sprintf( '%d photo%s uploaded.', count( $ids ), 1 === count( $ids ) ? '' : 's' ) );
		} elseif ( ! $errors ) {
			ffp_flash( 'error', 'Please choose at least one photo.' );
		}
		self::back();
	}

	private static function warranty_meta() {
		return array(
			'provider' => self::text( 'w_provider' ),
			'coverage' => self::textarea( 'w_coverage' ),
			'starts'   => self::text( 'w_starts' ),
			'expires'  => self::text( 'w_expires' ),
		);
	}

	private static function do_upload_document() {
		$project  = self::project();
		$category = self::text( 'category' );
		$category = array_key_exists( $category, ffp_doc_categories() ) ? $category : 'other';
		list( $ids, $errors ) = FFP_Files::store_uploads(
			'documents',
			$project->id,
			array(
				'owner_type' => 'document',
				'category'   => $category,
				'title'      => self::text( 'title' ),
				'caption'    => self::textarea( 'caption' ),
				'meta'       => 'warranty' === $category ? self::warranty_meta() : array(),
				'visible'    => self::flag( 'visible' ),
			)
		);
		self::upload_errors( $errors );
		if ( ! $ids ) {
			if ( ! $errors ) {
				ffp_flash( 'error', 'Please choose a file to upload.' );
			}
			self::back();
		}
		ffp_flash( 'success', sprintf( '%d document%s uploaded.', count( $ids ), 1 === count( $ids ) ? '' : 's' ) );
		if ( self::flag( 'visible' ) && self::flag( 'notify' ) ) {
			$titles = array();
			foreach ( $ids as $id ) {
				$titles[] = FFP_Data::file( $id )->title;
			}
			FFP_Notify::new_documents( $project, $titles );
		}
		self::back();
	}

	private static function owned_file( $project ) {
		$file = FFP_Data::file( self::int( 'file_id' ) );
		if ( ! $file || (int) $file->project_id !== (int) $project->id ) {
			self::back();
		}
		return $file;
	}

	private static function do_update_file() {
		$project = self::project();
		$file    = self::owned_file( $project );
		$fields  = array(
			'caption' => self::textarea( 'caption' ),
			'visible' => self::flag( 'visible' ) ? 1 : 0,
		);
		if ( isset( $_POST['title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$fields['title'] = self::text( 'title' );
		}
		if ( isset( $_POST['category'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$cat     = self::text( 'category' );
			$allowed = 'document' === $file->owner_type ? ffp_doc_categories() : ffp_photo_tags();
			if ( array_key_exists( $cat, $allowed ) ) {
				$fields['category'] = $cat;
			}
		}
		if ( 'document' === $file->owner_type ) {
			$meta = FFP_Data::file_meta( $file );
			if ( 'warranty' === ( $fields['category'] ?? $file->category ) ) {
				$meta = array_merge( $meta, self::warranty_meta() );
			}
			$fields['meta'] = wp_json_encode( $meta );
		}
		FFP_Data::update_file( $file->id, $fields );
		if ( self::flag( 'make_cover' ) && FFP_Files::is_image( $file ) ) {
			FFP_Data::update_project_fields( $project->id, array( 'cover_file_id' => $file->id ) );
		}
		ffp_flash( 'success', 'Saved.' );
		self::back();
	}

	private static function do_delete_file() {
		$project = self::project();
		$file    = self::owned_file( $project );
		FFP_Files::delete( $file );
		ffp_flash( 'success', 'File deleted.' );
		self::back();
	}

	/* ---------- Selections & specs ---------- */

	private static function do_save_spec() {
		$project = self::project();
		$spec_id = self::int( 'spec_id' );
		if ( $spec_id ) {
			$spec = FFP_Data::spec( $spec_id );
			if ( ! $spec || (int) $spec->project_id !== (int) $project->id ) {
				self::back();
			}
		}
		$data = array();
		foreach ( FFP_Data::spec_columns() as $col ) {
			$data[ $col ] = in_array( $col, array( 'purpose', 'notes' ), true ) ? self::textarea( $col ) : self::text( $col );
		}
		if ( '' === $data['product'] && '' === $data['color'] ) {
			ffp_flash( 'error', 'Add at least a product name or a color.' );
			self::back();
		}
		$id = FFP_Data::save_spec( $project->id, $data, $spec_id );
		list( , $errors ) = FFP_Files::store_uploads( 'spec_file', $project->id, array( 'owner_type' => 'spec', 'owner_id' => $id, 'category' => 'spec', 'visible' => 1 ) );
		self::upload_errors( $errors );
		ffp_flash( 'success', $spec_id ? 'Item updated.' : 'Item added to the spec sheet.' );
		self::back( ffp_project_url( $project->id, 'specs' ) . '#spec-' . $id );
	}

	private static function do_delete_spec() {
		$project = self::project();
		$spec    = FFP_Data::spec( self::int( 'spec_id' ) );
		if ( $spec && (int) $spec->project_id === (int) $project->id ) {
			FFP_Data::delete_spec( $spec );
			ffp_flash( 'success', 'Item removed from the spec sheet.' );
		}
		self::back();
	}

	/* ---------- Schedule ---------- */

	private static function do_save_milestone() {
		$project = self::project();
		$id      = self::int( 'milestone_id' );
		if ( $id ) {
			$m = FFP_Data::milestone( $id );
			if ( ! $m || (int) $m->project_id !== (int) $project->id ) {
				self::back();
			}
		}
		$title  = self::text( 'title' );
		$status = self::text( 'status' );
		if ( '' === $title ) {
			ffp_flash( 'error', 'Give the schedule step a name.' );
			self::back();
		}
		FFP_Data::save_milestone( $project->id, $title, self::text( 'target_date' ), array_key_exists( $status, ffp_milestone_statuses() ) ? $status : 'upcoming', $id );
		ffp_flash( 'success', 'Schedule updated.' );
		self::back( ffp_project_url( $project->id ) . '#schedule' );
	}

	private static function do_milestone_status() {
		$project = self::project();
		$m       = FFP_Data::milestone( self::int( 'milestone_id' ) );
		$status  = self::text( 'status' );
		if ( $m && (int) $m->project_id === (int) $project->id && array_key_exists( $status, ffp_milestone_statuses() ) ) {
			FFP_Data::save_milestone( $project->id, $m->title, (string) $m->target_date, $status, $m->id );
		}
		self::back( ffp_project_url( $project->id ) . '#schedule' );
	}

	private static function do_delete_milestone() {
		$project = self::project();
		$m       = FFP_Data::milestone( self::int( 'milestone_id' ) );
		if ( $m && (int) $m->project_id === (int) $project->id ) {
			FFP_Data::delete_milestone( $m->id );
			ffp_flash( 'success', 'Schedule step removed.' );
		}
		self::back( ffp_project_url( $project->id ) . '#schedule' );
	}

	/* ---------- Messages & change requests ---------- */

	private static function do_add_comment() {
		$project   = self::project();
		$parent_id = self::int( 'parent_id' );
		if ( $parent_id ) {
			$parent = FFP_Data::comment( $parent_id );
			if ( ! $parent || (int) $parent->project_id !== (int) $project->id || (int) $parent->parent_id ) {
				self::back();
			}
		}
		$type = self::text( 'type' );
		$type = array_key_exists( $type, ffp_comment_types() ) ? $type : 'comment';
		$body = self::textarea( 'body' );
		if ( '' === trim( $body ) && ! FFP_Files::collect( 'attachments' ) ) {
			ffp_flash( 'error', 'Please write a message before sending.' );
			self::back();
		}
		$id = FFP_Data::add_comment( $project->id, $parent_id, $type, $body );
		list( , $errors ) = FFP_Files::store_uploads( 'attachments', $project->id, array( 'owner_type' => 'message', 'owner_id' => $id, 'category' => 'message', 'visible' => 1 ) );
		self::upload_errors( $errors );
		FFP_Notify::new_message( $project, FFP_Data::comment( $id ) );

		if ( ffp_is_staff() ) {
			ffp_flash( 'success', 'Message posted.' );
		} elseif ( 'change_request' === $type && ! $parent_id ) {
			ffp_flash( 'success', "Change request sent. We'll review it and reply here with any cost or schedule impact before work changes." );
		} else {
			ffp_flash( 'success', 'Message sent. Your project manager has been notified.' );
		}
		self::back( ffp_project_url( $project->id, 'messages' ) . '#c-' . ( $parent_id ? $parent_id : $id ) );
	}

	private static function do_change_status() {
		$project = self::project();
		$comment = FFP_Data::comment( self::int( 'comment_id' ) );
		$status  = self::text( 'status' );
		if ( ! $comment || (int) $comment->project_id !== (int) $project->id || ! array_key_exists( $status, ffp_change_statuses() ) ) {
			self::back();
		}
		FFP_Data::update_comment( $comment->id, array( 'status' => $status, 'response' => self::textarea( 'response' ) ) );
		if ( self::flag( 'notify' ) ) {
			FFP_Notify::change_status( $project, FFP_Data::comment( $comment->id ) );
		}
		ffp_flash( 'success', 'Change request updated.' );
		self::back( ffp_project_url( $project->id, 'messages' ) . '#c-' . $comment->id );
	}

	private static function do_delete_comment() {
		$project = self::project();
		$comment = FFP_Data::comment( self::int( 'comment_id' ) );
		if ( $comment && (int) $comment->project_id === (int) $project->id ) {
			FFP_Data::delete_comment( $comment );
			ffp_flash( 'success', 'Message deleted.' );
		}
		self::back( ffp_project_url( $project->id, 'messages' ) );
	}

	/* ---------- Settings ---------- */

	private static function do_save_settings() {
		$section = self::text( 'section' );

		if ( 'fields' === $section ) {
			$rows   = (array) self::post( 'f', array() );
			$types  = ffp_field_types();
			$fields = array();
			$keys   = array();
			uasort( $rows, fn( $a, $b ) => (int) ( $a['order'] ?? 0 ) <=> (int) ( $b['order'] ?? 0 ) );
			foreach ( $rows as $row ) {
				$label = sanitize_text_field( $row['label'] ?? '' );
				if ( '' === $label || ! empty( $row['remove'] ) ) {
					continue;
				}
				$key = sanitize_key( $row['key'] ?? '' );
				if ( '' === $key ) {
					$key = sanitize_key( str_replace( array( ' ', '-' ), '_', strtolower( $label ) ) );
					$key = $key ? $key : 'field';
				}
				$base = $key;
				$n    = 2;
				while ( in_array( $key, $keys, true ) ) {
					$key = $base . '_' . $n++;
				}
				$keys[]   = $key;
				$type     = sanitize_key( $row['type'] ?? 'text' );
				$fields[] = array(
					'key'        => $key,
					'label'      => $label,
					'type'       => array_key_exists( $type, $types ) ? $type : 'text',
					'options'    => sanitize_text_field( $row['options'] ?? '' ),
					'staff_only' => empty( $row['staff_only'] ) ? 0 : 1,
				);
			}
			if ( ! $fields ) {
				ffp_flash( 'error', 'Keep at least one field in the daily update.' );
				self::back();
			}
			ffp_update_settings( array( 'log_fields' => $fields ) );
			ffp_flash( 'success', 'Daily update fields saved.' );
			self::back();
		}

		$cats = array_values( array_filter( array_map( 'sanitize_text_field', preg_split( '/\r\n|\r|\n/', (string) self::post( 'spec_categories' ) ) ), 'strlen' ) );
		ffp_update_settings(
			array(
				'company_name'    => self::text( 'company_name' ),
				'company_phone'   => self::text( 'company_phone' ),
				'staff_emails'    => implode( ', ', array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', (string) self::post( 'staff_emails' ) ) ), 'is_email' ) ),
				'logo_url'        => esc_url_raw( (string) self::post( 'logo_url' ) ),
				'welcome_message' => self::textarea( 'welcome_message' ),
				'max_upload_mb'   => max( 1, min( 512, self::int( 'max_upload_mb' ) ) ),
				'spec_categories' => $cats ? $cats : ffp_default_spec_categories(),
			)
		);
		ffp_flash( 'success', 'Settings saved.' );
		self::back();
	}
}
