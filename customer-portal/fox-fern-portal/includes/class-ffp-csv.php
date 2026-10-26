<?php
/**
 * CSV import (daily log from a Google Sheets export) and CSV exports.
 */

defined( 'ABSPATH' ) || exit;

class FFP_CSV {

	const DATE_HEADERS = array( 'date', 'day', 'log date', 'work date' );

	/**
	 * Imports daily-log rows. Column headers are matched to daily-update fields by label or key;
	 * a Date column is required.
	 *
	 * @return array|WP_Error Summary: imported, skipped, unmatched, added.
	 */
	public static function import_logs( $project_id, $path, $visible, $add_missing ) {
		$fh = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $fh ) {
			return new WP_Error( 'ffp_csv', 'The file could not be read.' );
		}
		$headers = fgetcsv( $fh, 0, ',', '"', '\\' );
		if ( ! $headers ) {
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return new WP_Error( 'ffp_csv', 'The file is empty.' );
		}
		$headers[0] = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $headers[0] );
		$headers    = array_map( fn( $h ) => trim( (string) $h ), $headers );

		$fields   = ffp_settings()['log_fields'];
		$by_name  = array();
		foreach ( $fields as $i => $field ) {
			$by_name[ strtolower( $field['label'] ) ] = $i;
			$by_name[ strtolower( $field['key'] ) ]   = $i;
		}

		$date_col  = null;
		$map       = array();
		$unmatched = array();
		foreach ( $headers as $col => $header ) {
			$lower = strtolower( $header );
			if ( '' === $lower ) {
				continue;
			}
			if ( null === $date_col && in_array( $lower, self::DATE_HEADERS, true ) ) {
				$date_col = $col;
			} elseif ( isset( $by_name[ $lower ] ) ) {
				$map[ $col ] = $by_name[ $lower ];
			} else {
				$unmatched[ $col ] = $header;
			}
		}
		if ( null === $date_col ) {
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return new WP_Error( 'ffp_csv', 'We could not find a "Date" column. Make sure the first row of the sheet has column names and one of them is "Date".' );
		}

		$rows = array();
		while ( false !== ( $row = fgetcsv( $fh, 0, ',', '"', '\\' ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			$rows[] = $row;
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$added = array();
		if ( $add_missing && $unmatched ) {
			foreach ( $unmatched as $col => $header ) {
				$long = false;
				foreach ( $rows as $row ) {
					if ( isset( $row[ $col ] ) && strlen( (string) $row[ $col ] ) > 80 ) {
						$long = true;
						break;
					}
				}
				$fields[]    = array( 'key' => self::unique_key( $header, $fields ), 'label' => sanitize_text_field( $header ), 'type' => $long ? 'textarea' : 'text', 'options' => '', 'staff_only' => 0 );
				$map[ $col ] = count( $fields ) - 1;
				$added[]     = $header;
			}
			ffp_update_settings( array( 'log_fields' => $fields ) );
			$unmatched = array();
		}

		$existing = array();
		foreach ( FFP_Data::logs( $project_id, true ) as $log ) {
			$existing[ $log->log_date . '|' . md5( wp_json_encode( $log->values ) ) ] = true;
		}

		$imported = 0;
		$skipped  = 0;
		foreach ( $rows as $row ) {
			$date = self::parse_date( $row[ $date_col ] ?? '' );
			if ( ! $date ) {
				++$skipped;
				continue;
			}
			$values = array();
			foreach ( $map as $col => $field_index ) {
				$field = $fields[ $field_index ];
				$value = ffp_sanitize_field_value( $field, (string) ( $row[ $col ] ?? '' ) );
				if ( '' !== $value && ! ( 'checkbox' === $field['type'] && '' === trim( (string) ( $row[ $col ] ?? '' ) ) ) ) {
					$values[ $field['key'] ] = $value;
				}
			}
			$signature = $date . '|' . md5( wp_json_encode( $values ) );
			if ( ! $values || isset( $existing[ $signature ] ) ) {
				++$skipped;
				continue;
			}
			$id = FFP_Data::save_log( $project_id, $date, $values, $visible );
			FFP_Data::mark_log_notified( $id );
			$existing[ $signature ] = true;
			++$imported;
		}

		return array(
			'imported'  => $imported,
			'skipped'   => $skipped,
			'unmatched' => array_values( $unmatched ),
			'added'     => $added,
		);
	}

	private static function unique_key( $label, $fields ) {
		$base = sanitize_key( str_replace( array( ' ', '-' ), '_', strtolower( $label ) ) );
		$base = $base ? $base : 'field';
		$keys = array_column( $fields, 'key' );
		$key  = $base;
		$n    = 2;
		while ( in_array( $key, $keys, true ) ) {
			$key = $base . '_' . $n++;
		}
		return $key;
	}

	public static function parse_date( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return null;
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}/', $value ) ) {
			return substr( $value, 0, 10 );
		}
		if ( preg_match( '#^(\d{1,2})/(\d{1,2})/(\d{2,4})$#', $value, $m ) ) {
			$year = strlen( $m[3] ) === 2 ? 2000 + (int) $m[3] : (int) $m[3];
			return checkdate( (int) $m[1], (int) $m[2], $year ) ? sprintf( '%04d-%02d-%02d', $year, $m[1], $m[2] ) : null;
		}
		$ts = strtotime( $value );
		return $ts ? gmdate( 'Y-m-d', $ts ) : null;
	}

	/* ---------- Exports ---------- */

	public static function export_logs( $project ) {
		$fields = ffp_log_fields( true );
		$logs   = array_reverse( FFP_Data::logs( $project->id, true ) );
		// Keep data from fields that were later removed in Settings.
		$known = array_column( $fields, 'key' );
		foreach ( $logs as $log ) {
			foreach ( array_keys( $log->values ) as $key ) {
				if ( ! in_array( $key, $known, true ) ) {
					$known[]  = $key;
					$fields[] = array( 'key' => $key, 'label' => $key . ' (removed field)', 'type' => 'text' );
				}
			}
		}
		$rows = array( array_merge( array( 'Date' ), array_column( $fields, 'label' ), array( 'Visible to customer', 'Posted by' ) ) );
		foreach ( $logs as $log ) {
			$row = array( $log->log_date );
			foreach ( $fields as $field ) {
				$value = $log->values[ $field['key'] ] ?? '';
				$row[] = 'checkbox' === $field['type'] && '' !== $value ? ( '1' === $value ? 'Yes' : 'No' ) : $value;
			}
			$row[]  = $log->visible ? 'Yes' : 'No';
			$row[]  = ffp_user_name( $log->author_id );
			$rows[] = $row;
		}
		self::send( sanitize_file_name( $project->title . ' daily log.csv' ), $rows );
	}

	public static function export_specs( $project ) {
		$rows = array( array( 'Category', 'Room / location', 'Product', 'Brand / manufacturer', 'Color', 'Color / product code', 'Finish / sheen', 'Quantity', 'Supplier', 'Used for', 'Installed on', 'Notes' ) );
		foreach ( FFP_Data::specs( $project->id ) as $spec ) {
			$rows[] = array( $spec->category, $spec->location, $spec->product, $spec->brand, $spec->color, $spec->code, $spec->finish, $spec->quantity, $spec->supplier, $spec->purpose, $spec->installed_on, $spec->notes );
		}
		self::send( sanitize_file_name( $project->title . ' selections and specs.csv' ), $rows );
	}

	private static function send( $filename, $rows ) {
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . str_replace( '"', '', $filename ) . '"' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		foreach ( $rows as $row ) {
			// Neutralize spreadsheet formulas so exported text can't execute in Excel or Sheets.
			$row = array_map( fn( $v ) => preg_match( '/^[=+\-@\t\r]/', (string) $v ) ? "'" . $v : $v, $row );
			fputcsv( $out, $row, ',', '"', '\\' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
