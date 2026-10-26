<?php
/**
 * Shared helpers: URLs, settings, permissions, formatting and template rendering.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- URLs ---------- */

function ffp_url( $path = '', $args = array() ) {
	$path = trim( (string) $path, '/' );
	$url  = home_url( '/' . FFP_SLUG . '/' . ( '' !== $path ? $path . '/' : '' ) );
	return $args ? add_query_arg( $args, $url ) : $url;
}

function ffp_project_url( $project_id, $tab = '', $args = array() ) {
	$path = 'project/' . (int) $project_id;
	if ( $tab && 'overview' !== $tab ) {
		$path .= '/' . $tab;
	}
	return ffp_url( $path, $args );
}

function ffp_file_url( $file_id, $size = '', $download = false ) {
	$args = array();
	if ( $size ) {
		$args['size'] = $size;
	}
	if ( $download ) {
		$args['download'] = 1;
	}
	return ffp_url( 'file/' . (int) $file_id, $args );
}

function ffp_export_url( $project_id, $what ) {
	return home_url( '/' . FFP_SLUG . '/project/' . (int) $project_id . '/export/' . $what . '.csv' );
}

/** The URL of the current request (forms post back to the page they are on). */
function ffp_current_url() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	return ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri;
}

function ffp_logout_url() {
	return wp_nonce_url( ffp_url( 'logout' ), 'ffp_logout' );
}

/* ---------- Settings ---------- */

function ffp_default_log_fields() {
	return array(
		array( 'key' => 'crew', 'label' => 'Crew on site', 'type' => 'text', 'options' => '', 'staff_only' => 0 ),
		array( 'key' => 'hours', 'label' => 'Hours on site', 'type' => 'number', 'options' => '', 'staff_only' => 0 ),
		array( 'key' => 'weather', 'label' => 'Weather', 'type' => 'select', 'options' => 'Clear, Partly cloudy, Overcast, Rain, Snow, Hot, Cold, Windy', 'staff_only' => 0 ),
		array( 'key' => 'work_completed', 'label' => 'Work completed today', 'type' => 'textarea', 'options' => '', 'staff_only' => 0 ),
		array( 'key' => 'next_steps', 'label' => 'Next steps', 'type' => 'textarea', 'options' => '', 'staff_only' => 0 ),
		array( 'key' => 'progress', 'label' => 'Overall progress', 'type' => 'percent', 'options' => '', 'staff_only' => 0 ),
		array( 'key' => 'materials', 'label' => 'Materials delivered or used', 'type' => 'textarea', 'options' => '', 'staff_only' => 0 ),
		array( 'key' => 'inspections', 'label' => 'Inspections & permits', 'type' => 'text', 'options' => '', 'staff_only' => 0 ),
		array( 'key' => 'issues', 'label' => 'Delays or issues', 'type' => 'textarea', 'options' => '', 'staff_only' => 0 ),
		array( 'key' => 'internal_notes', 'label' => 'Internal notes', 'type' => 'textarea', 'options' => '', 'staff_only' => 1 ),
	);
}

function ffp_default_spec_categories() {
	return array(
		'Paint', 'Stain & finishes', 'Flooring', 'Tile & stone', 'Cabinetry', 'Countertops',
		'Plumbing fixtures', 'Lighting & electrical', 'Hardware', 'Appliances', 'Doors & windows',
		'Trim & millwork', 'Drywall & insulation', 'Roofing', 'Siding & exterior', 'Decking & railings',
		'Concrete & masonry', 'Other',
	);
}

function ffp_default_settings() {
	return array(
		'company_name'    => 'Fox & Fern Property Services',
		'company_phone'   => '(610) 890-3537',
		'staff_emails'    => get_option( 'admin_email' ),
		'logo_url'        => '',
		'welcome_message' => "Welcome to your project portal. Daily updates, photos, your selections and warranty paperwork all live here. Have a question or want to change something? Send us a message and your project manager will get back to you.",
		'max_upload_mb'   => 25,
		'log_fields'      => ffp_default_log_fields(),
		'spec_categories' => ffp_default_spec_categories(),
	);
}

function ffp_settings( $fresh = false ) {
	static $settings = null;
	if ( null === $settings || $fresh ) {
		$saved    = get_option( 'ffp_settings', array() );
		$settings = wp_parse_args( is_array( $saved ) ? $saved : array(), ffp_default_settings() );
		if ( empty( $settings['log_fields'] ) || ! is_array( $settings['log_fields'] ) ) {
			$settings['log_fields'] = ffp_default_log_fields();
		}
		if ( empty( $settings['spec_categories'] ) || ! is_array( $settings['spec_categories'] ) ) {
			$settings['spec_categories'] = ffp_default_spec_categories();
		}
	}
	return $settings;
}

function ffp_update_settings( $changes ) {
	update_option( 'ffp_settings', array_merge( ffp_settings(), $changes ), false );
	ffp_settings( true );
}

/* ---------- Vocabularies ---------- */

function ffp_project_statuses() {
	return array(
		'planning'    => 'Planning',
		'scheduled'   => 'Scheduled',
		'in_progress' => 'In progress',
		'on_hold'     => 'On hold',
		'punch_list'  => 'Punch list',
		'completed'   => 'Completed',
		'warranty'    => 'Under warranty',
	);
}

function ffp_field_types() {
	return array(
		'text'     => 'Short text',
		'textarea' => 'Paragraph',
		'number'   => 'Number',
		'percent'  => 'Progress (%)',
		'select'   => 'Dropdown',
		'checkbox' => 'Yes / No',
		'date'     => 'Date',
	);
}

function ffp_doc_categories() {
	return array(
		'warranty'     => 'Warranty certificate',
		'contract'     => 'Contract',
		'estimate'     => 'Estimate / proposal',
		'change_order' => 'Change order',
		'invoice'      => 'Invoice / receipt',
		'permit'       => 'Permit / inspection',
		'manual'       => 'Product manual / data sheet',
		'lien_waiver'  => 'Lien waiver',
		'other'        => 'Other',
	);
}

function ffp_photo_tags() {
	return array(
		'before'   => 'Before',
		'progress' => 'Progress',
		'after'    => 'After',
		'other'    => 'Other',
	);
}

function ffp_comment_types() {
	return array(
		'comment'        => 'Comment',
		'question'       => 'Question',
		'change_request' => 'Change request',
	);
}

function ffp_change_statuses() {
	return array(
		'submitted' => 'Submitted',
		'reviewing' => 'Under review',
		'approved'  => 'Approved',
		'declined'  => 'Declined',
		'completed' => 'Completed',
	);
}

function ffp_milestone_statuses() {
	return array(
		'upcoming' => 'Upcoming',
		'active'   => 'In progress',
		'done'     => 'Done',
	);
}

/* ---------- Permissions ---------- */

function ffp_is_staff( $user_id = null ) {
	return $user_id ? user_can( $user_id, 'ffp_manage_projects' ) : current_user_can( 'ffp_manage_projects' );
}

function ffp_is_admin() {
	return current_user_can( 'ffp_manage_settings' );
}

function ffp_can_view_project( $project_id, $user_id = null ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	if ( ffp_is_staff( $user_id ) ) {
		return true;
	}
	if ( get_user_meta( $user_id, 'ffp_disabled', true ) ) {
		return false;
	}
	return FFP_Data::is_member( $project_id, $user_id );
}

/* ---------- Daily log fields ---------- */

function ffp_log_fields( $include_staff_only = true ) {
	$fields = ffp_settings()['log_fields'];
	if ( ! $include_staff_only ) {
		$fields = array_values( array_filter( $fields, fn( $f ) => empty( $f['staff_only'] ) ) );
	}
	return $fields;
}

function ffp_field_options( $field ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) ( $field['options'] ?? '' ) ) ), 'strlen' ) );
}

function ffp_sanitize_field_value( $field, $raw ) {
	$raw = is_scalar( $raw ) ? (string) $raw : '';
	switch ( $field['type'] ) {
		case 'textarea':
			return sanitize_textarea_field( $raw );
		case 'number':
			$raw = str_replace( ',', '', trim( $raw ) );
			return is_numeric( $raw ) ? (string) ( 0 + $raw ) : '';
		case 'percent':
			$raw = trim( str_replace( '%', '', $raw ) );
			return is_numeric( $raw ) ? (string) max( 0, min( 100, (int) round( (float) $raw ) ) ) : '';
		case 'checkbox':
			return in_array( strtolower( trim( $raw ) ), array( '1', 'yes', 'y', 'true', 'x' ), true ) ? '1' : '0';
		case 'date':
			$ts = strtotime( $raw );
			return ( '' !== trim( $raw ) && $ts ) ? gmdate( 'Y-m-d', $ts ) : '';
		default:
			return sanitize_text_field( $raw );
	}
}

/** Renders a stored value for display. Returns HTML, or '' when there is nothing to show. */
function ffp_field_display( $field, $value ) {
	if ( null === $value || '' === $value ) {
		return '';
	}
	switch ( $field['type'] ) {
		case 'textarea':
			return ffp_text( $value );
		case 'percent':
			$v = (int) $value;
			return '<span class="meter" role="img" aria-label="' . esc_attr( $v . '% complete' ) . '"><span style="width:' . $v . '%"></span></span><strong>' . $v . '%</strong>';
		case 'checkbox':
			return '1' === (string) $value ? 'Yes' : 'No';
		case 'date':
			return esc_html( ffp_date( $value ) );
		default:
			return esc_html( $value );
	}
}

function ffp_field_input( $field, $value = '', $prefix = 'fields' ) {
	$name = $prefix . '[' . $field['key'] . ']';
	$id   = 'f-' . $prefix . '-' . $field['key'] . '-' . wp_rand( 100, 999999 );
	$out  = '<div class="field field-' . esc_attr( $field['type'] ) . '">';
	if ( 'checkbox' === $field['type'] ) {
		$out .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
		$out .= '<label class="check"><input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( '1', (string) $value, false ) . '> ' . esc_html( $field['label'] ) . '</label>';
	} else {
		$out .= '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . ( ! empty( $field['staff_only'] ) ? ' <span class="lock">Staff only</span>' : '' ) . '</label>';
		switch ( $field['type'] ) {
			case 'textarea':
				$out .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="3">' . esc_textarea( $value ) . '</textarea>';
				break;
			case 'number':
				$out .= '<input id="' . esc_attr( $id ) . '" type="number" step="any" inputmode="decimal" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
				break;
			case 'percent':
				$out .= '<div class="pct"><input id="' . esc_attr( $id ) . '" type="number" min="0" max="100" step="1" inputmode="numeric" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"><span>%</span></div>';
				break;
			case 'date':
				$out .= '<input id="' . esc_attr( $id ) . '" type="date" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
				break;
			case 'select':
				$opts = ffp_field_options( $field );
				$out .= '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"><option value="">—</option>';
				if ( '' !== (string) $value && ! in_array( $value, $opts, true ) ) {
					$opts[] = $value;
				}
				foreach ( $opts as $opt ) {
					$out .= '<option' . selected( $value, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
				}
				$out .= '</select>';
				break;
			default:
				$out .= '<input id="' . esc_attr( $id ) . '" type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		}
	}
	return $out . '</div>';
}

/* ---------- Formatting ---------- */

/** Plain text to safe HTML with line breaks and clickable links. */
function ffp_text( $text ) {
	return make_clickable( nl2br( esc_html( (string) $text ) ) );
}

function ffp_date( $date, $format = 'M j, Y' ) {
	if ( ! $date || str_starts_with( (string) $date, '0000' ) ) {
		return '';
	}
	$ts = strtotime( $date );
	return $ts ? date_i18n( $format, $ts ) : '';
}

function ffp_ago( $datetime ) {
	$ts = strtotime( (string) $datetime );
	if ( ! $ts ) {
		return '';
	}
	$now = current_time( 'timestamp' );
	if ( $now - $ts > WEEK_IN_SECONDS ) {
		return ffp_date( $datetime, 'M j, Y \a\t g:i a' );
	}
	return sprintf( '%s ago', human_time_diff( $ts, $now ) );
}

function ffp_user_name( $user_id ) {
	$user = get_userdata( (int) $user_id );
	return $user ? $user->display_name : 'Former user';
}

function ffp_initials( $name ) {
	$parts = preg_split( '/\s+/', trim( (string) $name ) );
	$first = $parts[0] ?? '';
	$last  = count( $parts ) > 1 ? end( $parts ) : '';
	return strtoupper( mb_substr( $first, 0, 1 ) . mb_substr( $last, 0, 1 ) );
}

function ffp_badge( $label, $class = '' ) {
	return '<span class="badge ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
}

function ffp_status_badge( $status ) {
	$all = ffp_project_statuses();
	return ffp_badge( $all[ $status ] ?? ucfirst( (string) $status ), 'st-' . sanitize_html_class( $status ) );
}

function ffp_file_icon( $mime ) {
	if ( str_starts_with( (string) $mime, 'image/' ) ) {
		return 'IMG';
	}
	$map = array(
		'application/pdf' => 'PDF',
		'text/csv'        => 'CSV',
		'text/plain'      => 'TXT',
	);
	if ( isset( $map[ $mime ] ) ) {
		return $map[ $mime ];
	}
	if ( str_contains( (string) $mime, 'sheet' ) || str_contains( (string) $mime, 'excel' ) ) {
		return 'XLS';
	}
	if ( str_contains( (string) $mime, 'word' ) ) {
		return 'DOC';
	}
	return 'FILE';
}

/* ---------- Flash messages & "new since last visit" ---------- */

function ffp_flash( $type, $message, $copy = '' ) {
	$key   = 'ffp_flash_' . get_current_user_id();
	$all   = get_transient( $key );
	$all   = is_array( $all ) ? $all : array();
	$all[] = array( 'type' => $type, 'message' => $message, 'copy' => $copy );
	set_transient( $key, $all, 5 * MINUTE_IN_SECONDS );
}

function ffp_take_flash() {
	$key = 'ffp_flash_' . get_current_user_id();
	$all = get_transient( $key );
	delete_transient( $key );
	return is_array( $all ) ? $all : array();
}

function ffp_seen( $project_id, $tab ) {
	$all = get_user_meta( get_current_user_id(), 'ffp_seen', true );
	return ( is_array( $all ) && ! empty( $all[ $project_id ][ $tab ] ) ) ? $all[ $project_id ][ $tab ] : '1970-01-01 00:00:00';
}

function ffp_mark_seen( $project_id, $tab ) {
	$uid = get_current_user_id();
	$all = get_user_meta( $uid, 'ffp_seen', true );
	$all = is_array( $all ) ? $all : array();
	$all[ $project_id ][ $tab ] = current_time( 'mysql' );
	update_user_meta( $uid, 'ffp_seen', $all );
}

/* ---------- Templates ---------- */

function ffp_template( $name, $vars = array() ) {
	extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
	include FFP_DIR . 'templates/' . $name . '.php';
}

/** Renders a full portal page (content wrapped in the layout) and ends the request. */
function ffp_page( $title, $template, $vars = array(), $layout = 'layout' ) {
	ob_start();
	ffp_template( $template, $vars );
	$content = ob_get_clean();
	ffp_template( $layout, array_merge( $vars, array( 'page_title' => $title, 'content' => $content ) ) );
	exit;
}

function ffp_form_open( $action, $args = array() ) {
	$args = wp_parse_args( $args, array( 'files' => false, 'class' => '', 'confirm' => '', 'url' => '' ) );
	$out  = '<form method="post" action="' . esc_url( $args['url'] ) . '"';
	$out .= $args['files'] ? ' enctype="multipart/form-data"' : '';
	$out .= $args['class'] ? ' class="' . esc_attr( $args['class'] ) . '"' : '';
	$out .= $args['confirm'] ? ' data-confirm="' . esc_attr( $args['confirm'] ) . '"' : '';
	$out .= '>';
	$out .= '<input type="hidden" name="ffp_action" value="' . esc_attr( $action ) . '">';
	$out .= wp_nonce_field( 'ffp_' . $action, '_ffp_nonce', false, false );
	return $out;
}
