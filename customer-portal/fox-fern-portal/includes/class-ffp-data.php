<?php
/**
 * Database access for projects, members, daily logs, files, specs, comments and milestones.
 */

defined( 'ABSPATH' ) || exit;

class FFP_Data {

	public static function t( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'ffp_' . $name;
	}

	private static function now() {
		return current_time( 'mysql' );
	}

	private static function date_or_null( $value ) {
		$value = trim( (string) $value );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : null;
	}

	/* ---------- Projects ---------- */

	public static function project( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'projects' ) . ' WHERE id = %d', $id ) );
	}

	public static function projects_for( $user_id, $archived = false ) {
		global $wpdb;
		$p = self::t( 'projects' );
		if ( ffp_is_staff( $user_id ) ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $p WHERE archived = %d ORDER BY updated_at DESC", $archived ? 1 : 0 ) );
		}
		$m = self::t( 'members' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.* FROM $p p INNER JOIN $m m ON m.project_id = p.id WHERE m.user_id = %d AND p.archived = %d ORDER BY p.updated_at DESC",
				$user_id,
				$archived ? 1 : 0
			)
		);
	}

	public static function save_project( $data, $id = 0 ) {
		global $wpdb;
		$row = array(
			'title'           => $data['title'],
			'address'         => $data['address'],
			'status'          => $data['status'],
			'description'     => $data['description'],
			'start_date'      => self::date_or_null( $data['start_date'] ?? '' ),
			'target_date'     => self::date_or_null( $data['target_date'] ?? '' ),
			'manager_id'      => (int) $data['manager_id'],
			'notify_customer' => empty( $data['notify_customer'] ) ? 0 : 1,
			'updated_at'      => self::now(),
		);
		if ( $id ) {
			$wpdb->update( self::t( 'projects' ), $row, array( 'id' => $id ) );
			return (int) $id;
		}
		$row['created_by'] = get_current_user_id();
		$row['created_at'] = self::now();
		$wpdb->insert( self::t( 'projects' ), $row );
		return (int) $wpdb->insert_id;
	}

	public static function update_project_fields( $id, $fields ) {
		global $wpdb;
		$fields['updated_at'] = self::now();
		$wpdb->update( self::t( 'projects' ), $fields, array( 'id' => $id ) );
	}

	public static function touch_project( $id ) {
		self::update_project_fields( $id, array() );
	}

	public static function delete_project( $id ) {
		global $wpdb;
		foreach ( self::files( $id ) as $file ) {
			FFP_Files::delete( $file );
		}
		foreach ( array( 'logs', 'specs', 'comments', 'milestones', 'members' ) as $table ) {
			$wpdb->delete( self::t( $table ), array( 'project_id' => $id ) );
		}
		$wpdb->delete( self::t( 'projects' ), array( 'id' => $id ) );
		@rmdir( FFP_Files::base_dir() . '/' . (int) $id ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	/* ---------- Members ---------- */

	public static function is_member( $project_id, $user_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . self::t( 'members' ) . ' WHERE project_id = %d AND user_id = %d', $project_id, $user_id ) );
	}

	/** @return WP_User[] */
	public static function members( $project_id ) {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT user_id FROM ' . self::t( 'members' ) . ' WHERE project_id = %d ORDER BY added_at', $project_id ) );
		return array_values( array_filter( array_map( 'get_userdata', array_map( 'intval', $ids ) ) ) );
	}

	public static function add_member( $project_id, $user_id ) {
		global $wpdb;
		if ( self::is_member( $project_id, $user_id ) ) {
			return;
		}
		$wpdb->insert(
			self::t( 'members' ),
			array( 'project_id' => $project_id, 'user_id' => $user_id, 'added_at' => self::now() )
		);
	}

	public static function remove_member( $project_id, $user_id ) {
		global $wpdb;
		$wpdb->delete( self::t( 'members' ), array( 'project_id' => $project_id, 'user_id' => $user_id ) );
	}

	public static function member_projects( $user_id ) {
		global $wpdb;
		$p = self::t( 'projects' );
		$m = self::t( 'members' );
		return $wpdb->get_results( $wpdb->prepare( "SELECT p.id, p.title, p.archived FROM $p p INNER JOIN $m m ON m.project_id = p.id WHERE m.user_id = %d ORDER BY p.title", $user_id ) );
	}

	/* ---------- Daily logs ---------- */

	public static function logs( $project_id, $staff, $limit = 0, $offset = 0 ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . self::t( 'logs' ) . ' WHERE project_id = %d' . ( $staff ? '' : ' AND visible = 1' ) . ' ORDER BY log_date DESC, id DESC';
		if ( $limit ) {
			$sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
		}
		return array_map( array( __CLASS__, 'decode_log' ), $wpdb->get_results( $wpdb->prepare( $sql, $project_id ) ) );
	}

	public static function count_logs( $project_id, $staff ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t( 'logs' ) . ' WHERE project_id = %d' . ( $staff ? '' : ' AND visible = 1' ), $project_id ) );
	}

	public static function log( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'logs' ) . ' WHERE id = %d', $id ) );
		return $row ? self::decode_log( $row ) : null;
	}

	private static function decode_log( $row ) {
		$row->values = json_decode( (string) $row->data, true ) ?: array();
		return $row;
	}

	public static function save_log( $project_id, $date, $values, $visible, $id = 0 ) {
		global $wpdb;
		$row = array(
			'log_date'   => self::date_or_null( $date ) ?? current_time( 'Y-m-d' ),
			'data'       => wp_json_encode( $values ),
			'visible'    => $visible ? 1 : 0,
			'updated_at' => self::now(),
		);
		if ( $id ) {
			$wpdb->update( self::t( 'logs' ), $row, array( 'id' => $id ) );
		} else {
			$row['project_id'] = $project_id;
			$row['author_id']  = get_current_user_id();
			$row['created_at'] = self::now();
			$wpdb->insert( self::t( 'logs' ), $row );
			$id = (int) $wpdb->insert_id;
		}
		self::touch_project( $project_id );
		return (int) $id;
	}

	public static function mark_log_notified( $id ) {
		global $wpdb;
		$wpdb->update( self::t( 'logs' ), array( 'notified' => 1 ), array( 'id' => $id ) );
	}

	public static function delete_log( $log ) {
		global $wpdb;
		foreach ( self::files( $log->project_id, 'log', $log->id ) as $file ) {
			FFP_Files::delete( $file );
		}
		$wpdb->delete( self::t( 'logs' ), array( 'id' => $log->id ) );
	}

	/** Latest value of the first "Progress (%)" field, newest log first. Null when none recorded. */
	public static function progress( $project_id, $staff ) {
		$keys = array();
		foreach ( ffp_log_fields( $staff ) as $field ) {
			if ( 'percent' === $field['type'] ) {
				$keys[] = $field['key'];
			}
		}
		if ( ! $keys ) {
			return null;
		}
		foreach ( self::logs( $project_id, $staff, 60 ) as $log ) {
			foreach ( $keys as $key ) {
				if ( isset( $log->values[ $key ] ) && '' !== $log->values[ $key ] ) {
					return (int) $log->values[ $key ];
				}
			}
		}
		return null;
	}

	/* ---------- Files ---------- */

	public static function files( $project_id, $owner_type = null, $owner_id = null, $visible_only = false ) {
		global $wpdb;
		$sql  = 'SELECT * FROM ' . self::t( 'files' ) . ' WHERE project_id = %d';
		$args = array( $project_id );
		if ( null !== $owner_type ) {
			$types = (array) $owner_type;
			$sql  .= ' AND owner_type IN (' . implode( ',', array_fill( 0, count( $types ), '%s' ) ) . ')';
			$args  = array_merge( $args, $types );
		}
		if ( null !== $owner_id ) {
			$sql   .= ' AND owner_id = %d';
			$args[] = $owner_id;
		}
		if ( $visible_only ) {
			$sql .= ' AND visible = 1';
		}
		$sql .= ' ORDER BY created_at DESC, id DESC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	/** Gallery and daily-update photos. Customers only see visible photos from shared updates. */
	public static function photos( $project_id, $staff, $category = '' ) {
		global $wpdb;
		$f    = self::t( 'files' );
		$l    = self::t( 'logs' );
		$sql  = "SELECT f.*, l.log_date FROM $f f LEFT JOIN $l l ON f.owner_type = 'log' AND l.id = f.owner_id WHERE f.project_id = %d AND f.owner_type IN ('log','gallery')";
		$args = array( $project_id );
		if ( ! $staff ) {
			$sql .= " AND f.visible = 1 AND (f.owner_type = 'gallery' OR l.visible = 1)";
		}
		if ( $category ) {
			$sql   .= ' AND f.category = %s';
			$args[] = $category;
		}
		$sql .= ' ORDER BY COALESCE(l.log_date, f.created_at) DESC, f.id DESC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	/** Files grouped by owner id, for attaching to logs, specs or messages in one query. */
	public static function files_by_owner( $project_id, $owner_type, $visible_only ) {
		$grouped = array();
		foreach ( array_reverse( self::files( $project_id, $owner_type, null, $visible_only ) ) as $file ) {
			$grouped[ (int) $file->owner_id ][] = $file;
		}
		return $grouped;
	}

	public static function file( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'files' ) . ' WHERE id = %d', $id ) );
	}

	public static function file_meta( $file ) {
		return json_decode( (string) $file->meta, true ) ?: array();
	}

	public static function update_file( $id, $fields ) {
		global $wpdb;
		$wpdb->update( self::t( 'files' ), $fields, array( 'id' => $id ) );
	}

	/* ---------- Specs ---------- */

	public static function spec_columns() {
		return array( 'category', 'location', 'product', 'brand', 'color', 'code', 'finish', 'quantity', 'supplier', 'purpose', 'installed_on', 'notes' );
	}

	public static function specs( $project_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'specs' ) . ' WHERE project_id = %d ORDER BY category, location, sort, id', $project_id ) );
	}

	public static function spec( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'specs' ) . ' WHERE id = %d', $id ) );
	}

	public static function save_spec( $project_id, $data, $id = 0 ) {
		global $wpdb;
		$row = array();
		foreach ( self::spec_columns() as $col ) {
			$row[ $col ] = $data[ $col ] ?? '';
		}
		$row['installed_on'] = self::date_or_null( $row['installed_on'] );
		$row['updated_at']   = self::now();
		if ( $id ) {
			$wpdb->update( self::t( 'specs' ), $row, array( 'id' => $id ) );
		} else {
			$row['project_id'] = $project_id;
			$row['created_by'] = get_current_user_id();
			$row['created_at'] = self::now();
			$wpdb->insert( self::t( 'specs' ), $row );
			$id = (int) $wpdb->insert_id;
		}
		self::touch_project( $project_id );
		return (int) $id;
	}

	public static function delete_spec( $spec ) {
		global $wpdb;
		foreach ( self::files( $spec->project_id, 'spec', $spec->id ) as $file ) {
			FFP_Files::delete( $file );
		}
		$wpdb->delete( self::t( 'specs' ), array( 'id' => $spec->id ) );
	}

	/* ---------- Comments / change requests ---------- */

	/** Top-level threads newest first, each with ->replies oldest first. */
	public static function threads( $project_id, $type = '' ) {
		global $wpdb;
		$rows    = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'comments' ) . ' WHERE project_id = %d ORDER BY created_at ASC, id ASC', $project_id ) );
		$threads = array();
		$replies = array();
		foreach ( $rows as $row ) {
			$row->replies = array();
			if ( (int) $row->parent_id ) {
				$replies[] = $row;
			} else {
				$threads[ (int) $row->id ] = $row;
			}
		}
		foreach ( $replies as $reply ) {
			if ( isset( $threads[ (int) $reply->parent_id ] ) ) {
				$threads[ (int) $reply->parent_id ]->replies[] = $reply;
			}
		}
		if ( $type ) {
			$threads = array_filter( $threads, fn( $t ) => $t->type === $type );
		}
		return array_reverse( array_values( $threads ) );
	}

	public static function comment( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'comments' ) . ' WHERE id = %d', $id ) );
	}

	public static function add_comment( $project_id, $parent_id, $type, $body ) {
		global $wpdb;
		$wpdb->insert(
			self::t( 'comments' ),
			array(
				'project_id' => $project_id,
				'parent_id'  => $parent_id,
				'user_id'    => get_current_user_id(),
				'type'       => $parent_id ? 'comment' : $type,
				'body'       => $body,
				'status'     => ( ! $parent_id && 'change_request' === $type ) ? 'submitted' : '',
				'created_at' => self::now(),
				'updated_at' => self::now(),
			)
		);
		self::touch_project( $project_id );
		return (int) $wpdb->insert_id;
	}

	public static function update_comment( $id, $fields ) {
		global $wpdb;
		$fields['updated_at'] = self::now();
		$wpdb->update( self::t( 'comments' ), $fields, array( 'id' => $id ) );
	}

	public static function delete_comment( $comment ) {
		global $wpdb;
		$ids = array_merge(
			array( (int) $comment->id ),
			array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . self::t( 'comments' ) . ' WHERE parent_id = %d', $comment->id ) ) )
		);
		foreach ( $ids as $cid ) {
			foreach ( self::files( $comment->project_id, 'message', $cid ) as $file ) {
				FFP_Files::delete( $file );
			}
			$wpdb->delete( self::t( 'comments' ), array( 'id' => $cid ) );
		}
	}

	public static function open_change_requests( $project_id ) {
		return array_values(
			array_filter(
				self::threads( $project_id, 'change_request' ),
				fn( $t ) => in_array( $t->status, array( 'submitted', 'reviewing', 'approved' ), true )
			)
		);
	}

	/* ---------- Milestones ---------- */

	public static function milestones( $project_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'milestones' ) . ' WHERE project_id = %d ORDER BY (target_date IS NULL), target_date, sort, id', $project_id ) );
	}

	public static function milestone( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'milestones' ) . ' WHERE id = %d', $id ) );
	}

	public static function save_milestone( $project_id, $title, $date, $status, $id = 0 ) {
		global $wpdb;
		$row = array(
			'title'        => $title,
			'target_date'  => self::date_or_null( $date ),
			'status'       => $status,
			'completed_at' => 'done' === $status ? self::now() : null,
		);
		if ( $id ) {
			$wpdb->update( self::t( 'milestones' ), $row, array( 'id' => $id ) );
		} else {
			$row['project_id'] = $project_id;
			$wpdb->insert( self::t( 'milestones' ), $row );
			$id = (int) $wpdb->insert_id;
		}
		self::touch_project( $project_id );
		return (int) $id;
	}

	public static function delete_milestone( $id ) {
		global $wpdb;
		$wpdb->delete( self::t( 'milestones' ), array( 'id' => $id ) );
	}

	/* ---------- "New since your last visit" counts ---------- */

	public static function new_counts( $project_id, $staff ) {
		global $wpdb;
		$uid    = get_current_user_id();
		$vis    = $staff ? '' : ' AND visible = 1';
		$counts = array();

		$counts['updates'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t( 'logs' ) . " WHERE project_id = %d AND author_id <> %d AND created_at > %s$vis", $project_id, $uid, ffp_seen( $project_id, 'updates' ) )
		);
		$counts['photos'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t( 'files' ) . " WHERE project_id = %d AND owner_type IN ('log','gallery') AND uploaded_by <> %d AND created_at > %s$vis", $project_id, $uid, ffp_seen( $project_id, 'photos' ) )
		);
		$counts['documents'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t( 'files' ) . " WHERE project_id = %d AND owner_type = 'document' AND uploaded_by <> %d AND created_at > %s$vis", $project_id, $uid, ffp_seen( $project_id, 'documents' ) )
		);
		$counts['messages'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t( 'comments' ) . ' WHERE project_id = %d AND user_id <> %d AND updated_at > %s', $project_id, $uid, ffp_seen( $project_id, 'messages' ) )
		);
		return $counts;
	}
}
