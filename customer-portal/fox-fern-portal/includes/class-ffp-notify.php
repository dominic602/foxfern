<?php
/**
 * Plain-text email notifications. Sent with wp_mail(), so an SMTP plugin on the site controls delivery.
 */

defined( 'ABSPATH' ) || exit;

class FFP_Notify {

	public static function mail( $to, $subject, $lines ) {
		$to = array_values( array_unique( array_filter( (array) $to, 'is_email' ) ) );
		if ( ! $to ) {
			return false;
		}
		$s         = ffp_settings();
		$from_name = $s['company_name'];
		$filter    = function () use ( $from_name ) {
			return $from_name;
		};
		$body = implode( "\n", $lines ) . "\n\n-- \n" . $from_name . ( $s['company_phone'] ? "\n" . $s['company_phone'] : '' ) . "\n";

		add_filter( 'wp_mail_from_name', $filter );
		$sent = true;
		foreach ( $to as $address ) {
			$sent = wp_mail( $address, $subject, $body ) && $sent;
		}
		remove_filter( 'wp_mail_from_name', $filter );
		return $sent;
	}

	public static function customer_emails( $project_id ) {
		$emails = array();
		foreach ( FFP_Data::members( $project_id ) as $user ) {
			if ( ! get_user_meta( $user->ID, 'ffp_disabled', true ) ) {
				$emails[] = $user->user_email;
			}
		}
		return $emails;
	}

	public static function staff_emails( $project ) {
		$emails = preg_split( '/[\s,;]+/', (string) ffp_settings()['staff_emails'] );
		if ( $project->manager_id ) {
			$manager = get_userdata( (int) $project->manager_id );
			if ( $manager ) {
				$emails[] = $manager->user_email;
			}
		}
		return $emails;
	}

	private static function excerpt( $text, $words = 60 ) {
		return wp_trim_words( wp_strip_all_tags( (string) $text ), $words, '…' );
	}

	public static function new_log( $project, $log ) {
		$lines = array(
			'Hi,',
			'',
			sprintf( 'There is a new daily update for %s (%s).', $project->title, ffp_date( $log->log_date, 'l, F j' ) ),
		);
		foreach ( ffp_log_fields( false ) as $field ) {
			if ( 'textarea' === $field['type'] && ! empty( $log->values[ $field['key'] ] ) ) {
				$lines[] = '';
				$lines[] = $field['label'] . ':';
				$lines[] = self::excerpt( $log->values[ $field['key'] ] );
				break;
			}
		}
		$lines[] = '';
		$lines[] = 'See the full update and photos:';
		$lines[] = ffp_project_url( $project->id, 'updates' );
		return self::mail( self::customer_emails( $project->id ), 'Daily update: ' . $project->title, $lines );
	}

	public static function new_message( $project, $comment ) {
		$types     = ffp_comment_types();
		$from      = ffp_user_name( $comment->user_id );
		$from_team = ffp_is_staff( (int) $comment->user_id );
		$label     = $comment->parent_id ? 'reply' : strtolower( $types[ $comment->type ] ?? 'message' );
		$lines     = array(
			sprintf( '%s posted a new %s on %s:', $from, $label, $project->title ),
			'',
			self::excerpt( $comment->body, 120 ),
			'',
			'Read and reply:',
			ffp_project_url( $project->id, 'messages' ),
		);
		$to = $from_team ? self::customer_emails( $project->id ) : self::staff_emails( $project );
		$to = array_diff( $to, array( wp_get_current_user()->user_email ) );
		return self::mail( $to, sprintf( 'New %s: %s', $label, $project->title ), $lines );
	}

	public static function change_status( $project, $comment ) {
		$statuses = ffp_change_statuses();
		$lines    = array(
			'Hi,',
			'',
			sprintf( 'Your change request on %s is now: %s.', $project->title, $statuses[ $comment->status ] ?? $comment->status ),
			'',
			'Your request:',
			self::excerpt( $comment->body, 60 ),
		);
		if ( $comment->response ) {
			$lines[] = '';
			$lines[] = 'Our response:';
			$lines[] = self::excerpt( $comment->response, 120 );
		}
		$lines[] = '';
		$lines[] = ffp_project_url( $project->id, 'messages' );
		return self::mail( self::customer_emails( $project->id ), 'Change request update: ' . $project->title, $lines );
	}

	public static function new_documents( $project, $titles ) {
		$lines = array(
			'Hi,',
			'',
			sprintf( 'We added %s to your project portal for %s:', 1 === count( $titles ) ? 'a document' : count( $titles ) . ' documents', $project->title ),
			'',
		);
		foreach ( $titles as $title ) {
			$lines[] = '- ' . $title;
		}
		$lines[] = '';
		$lines[] = ffp_project_url( $project->id, 'documents' );
		return self::mail( self::customer_emails( $project->id ), 'New documents: ' . $project->title, $lines );
	}

	public static function invite( $user, $link, $project = null ) {
		$s     = ffp_settings();
		$lines = array(
			sprintf( 'Hi %s,', $user->first_name ? $user->first_name : $user->display_name ),
			'',
			$project
				? sprintf( '%s has set up a private online portal for your project, %s.', $s['company_name'], $project->title )
				: sprintf( '%s has set up a private online portal for your projects.', $s['company_name'] ),
			"You'll find daily progress updates, photos, your selections and finishes, and warranty paperwork there, and you can message our team directly.",
			'',
			'Create your password here (this link works for 7 days):',
			$link,
			'',
			'After that, sign in any time at:',
			ffp_url( 'login' ),
			'',
			'Your sign-in email is ' . $user->user_email . '.',
		);
		return self::mail( $user->user_email, 'Your ' . $s['company_name'] . ' project portal', $lines );
	}

	public static function reset( $user, $link ) {
		$lines = array(
			'Hi,',
			'',
			'We received a request to reset your project portal password. Use this link within 24 hours:',
			$link,
			'',
			"If you didn't ask for this, you can ignore this email.",
		);
		return self::mail( $user->user_email, 'Reset your portal password', $lines );
	}
}
