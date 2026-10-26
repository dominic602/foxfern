<?php
/**
 * Private file storage. Uploads live in wp-content/uploads/ffp-private/{project}/ under random
 * names, are blocked from direct web access, and are only served through /portal/file/{id}/
 * after a permission check.
 */

defined( 'ABSPATH' ) || exit;

class FFP_Files {

	const IMAGE_EXT = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
	const DOC_EXT   = array( 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp' );
	const MAX_DIM   = 2400;
	const THUMB_DIM = 640;

	public static function base_dir() {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . 'ffp-private';
	}

	public static function ensure_private_dir() {
		$dir = self::base_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions
				$dir . '/.htaccess',
				"# Fox & Fern portal files are only served through the portal.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n"
			);
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return true;
	}

	/** Normalizes $_FILES[$field] (single or multiple) into a list of file arrays. */
	public static function collect( $field ) {
		if ( empty( $_FILES[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return array();
		}
		$f   = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$out = array();
		if ( is_array( $f['name'] ) ) {
			foreach ( $f['name'] as $i => $name ) {
				if ( UPLOAD_ERR_NO_FILE === (int) $f['error'][ $i ] ) {
					continue;
				}
				$out[] = array(
					'name'     => $name,
					'tmp_name' => $f['tmp_name'][ $i ],
					'error'    => (int) $f['error'][ $i ],
					'size'     => (int) $f['size'][ $i ],
				);
			}
		} elseif ( UPLOAD_ERR_NO_FILE !== (int) $f['error'] ) {
			$out[] = array(
				'name'     => $f['name'],
				'tmp_name' => $f['tmp_name'],
				'error'    => (int) $f['error'],
				'size'     => (int) $f['size'],
			);
		}
		return $out;
	}

	/**
	 * Validates and stores every file posted in $field.
	 *
	 * @param array $args owner_type, owner_id, category, title, caption, meta, visible, images_only.
	 * @return array [ int[] $ids, string[] $errors ]
	 */
	public static function store_uploads( $field, $project_id, $args ) {
		$args   = wp_parse_args(
			$args,
			array( 'owner_type' => '', 'owner_id' => 0, 'category' => '', 'title' => '', 'caption' => '', 'meta' => array(), 'visible' => 1, 'images_only' => false )
		);
		$files  = self::collect( $field );
		$max_mb = max( 1, (int) ffp_settings()['max_upload_mb'] );
		$ids    = array();
		$errors = array();

		foreach ( $files as $file ) {
			$name = sanitize_file_name( wp_unslash( $file['name'] ) );
			if ( UPLOAD_ERR_OK !== $file['error'] ) {
				$errors[] = in_array( $file['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true )
					? sprintf( '%s is larger than the server allows.', $name )
					: sprintf( '%s could not be uploaded (error %d).', $name, $file['error'] );
				continue;
			}
			if ( $file['size'] > $max_mb * MB_IN_BYTES ) {
				$errors[] = sprintf( '%s is larger than the %d MB limit.', $name, $max_mb );
				continue;
			}
			if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
				$errors[] = sprintf( '%s could not be uploaded.', $name );
				continue;
			}
			$check   = wp_check_filetype_and_ext( $file['tmp_name'], $name );
			$ext     = strtolower( (string) $check['ext'] );
			$allowed = $args['images_only'] ? self::IMAGE_EXT : self::DOC_EXT;
			if ( ! $ext || ! in_array( $ext, $allowed, true ) ) {
				$errors[] = $args['images_only']
					? sprintf( '%s is not a photo we can use (JPG, PNG, WebP or GIF).', $name )
					: sprintf( '%s is not an allowed file type (PDF, Word, Excel, CSV, text or photos).', $name );
				continue;
			}
			$file_args          = $args;
			$file_args['title'] = ( '' !== $args['title'] && 1 === count( $files ) ) ? $args['title'] : self::pretty_name( $name );
			$id                 = self::store( $file['tmp_name'], $name, $ext, (string) $check['type'], $project_id, $file_args );
			if ( is_wp_error( $id ) ) {
				$errors[] = $name . ': ' . $id->get_error_message();
			} else {
				$ids[] = $id;
			}
		}
		return array( $ids, $errors );
	}

	private static function pretty_name( $filename ) {
		$base = pathinfo( $filename, PATHINFO_FILENAME );
		return ucfirst( trim( preg_replace( '/[-_]+/', ' ', $base ) ) );
	}

	/** Saves one file (resizing photos and making a thumbnail) and records it. */
	public static function store( $tmp, $orig_name, $ext, $mime, $project_id, $args ) {
		if ( ! self::ensure_private_dir() ) {
			return new WP_Error( 'ffp_dir', 'The upload folder is not writable.' );
		}
		$rel_dir = (string) (int) $project_id;
		$dir     = self::base_dir() . '/' . $rel_dir;
		wp_mkdir_p( $dir );

		$token     = strtolower( wp_generate_password( 24, false ) );
		$rel       = $rel_dir . '/' . $token . '.' . $ext;
		$dest      = self::base_dir() . '/' . $rel;
		$thumb_rel = '';
		$width     = 0;
		$height    = 0;

		if ( in_array( $ext, self::IMAGE_EXT, true ) && 'gif' !== $ext ) {
			$editor = wp_get_image_editor( $tmp );
			if ( ! is_wp_error( $editor ) ) {
				$editor->maybe_exif_rotate();
				$editor->set_quality( 82 );
				$size = $editor->get_size();
				if ( $size['width'] > self::MAX_DIM || $size['height'] > self::MAX_DIM ) {
					$editor->resize( self::MAX_DIM, self::MAX_DIM, false );
				}
				$saved = $editor->save( $dest );
				if ( ! is_wp_error( $saved ) ) {
					$dest   = $saved['path'];
					$rel    = $rel_dir . '/' . basename( $saved['path'] );
					$width  = (int) $saved['width'];
					$height = (int) $saved['height'];
					if ( $width > self::THUMB_DIM || $height > self::THUMB_DIM ) {
						$editor->resize( self::THUMB_DIM, self::THUMB_DIM, false );
						$thumb = $editor->save( $dir . '/' . $token . '-thumb.' . $ext );
						if ( ! is_wp_error( $thumb ) ) {
							$thumb_rel = $rel_dir . '/' . basename( $thumb['path'] );
						}
					}
				}
			}
		}

		if ( ! file_exists( $dest ) && ! move_uploaded_file( $tmp, $dest ) ) {
			return new WP_Error( 'ffp_move', 'The file could not be saved.' );
		}

		if ( ! $width && in_array( $ext, self::IMAGE_EXT, true ) ) {
			$dims = @getimagesize( $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			if ( $dims ) {
				$width  = (int) $dims[0];
				$height = (int) $dims[1];
			}
		}

		global $wpdb;
		$meta                  = (array) $args['meta'];
		$meta['original_name'] = $orig_name;
		$wpdb->insert(
			FFP_Data::t( 'files' ),
			array(
				'project_id'  => $project_id,
				'owner_type'  => $args['owner_type'],
				'owner_id'    => (int) $args['owner_id'],
				'category'    => $args['category'],
				'title'       => mb_substr( $args['title'], 0, 200 ),
				'caption'     => $args['caption'],
				'meta'        => wp_json_encode( $meta ),
				'path'        => $rel,
				'thumb_path'  => $thumb_rel,
				'mime'        => $mime ? $mime : 'application/octet-stream',
				'size'        => (int) filesize( $dest ),
				'width'       => $width,
				'height'      => $height,
				'visible'     => $args['visible'] ? 1 : 0,
				'uploaded_by' => get_current_user_id(),
				'created_at'  => current_time( 'mysql' ),
			)
		);
		FFP_Data::touch_project( $project_id );
		return (int) $wpdb->insert_id;
	}

	public static function is_image( $file ) {
		return str_starts_with( (string) $file->mime, 'image/' );
	}

	/** Streams a file to the browser. Permission checks happen in the router before this. */
	public static function serve( $file, $size = '', $download = false ) {
		$rel  = ( 'thumb' === $size && $file->thumb_path ) ? $file->thumb_path : $file->path;
		$base = realpath( self::base_dir() );
		$path = realpath( self::base_dir() . '/' . $rel );
		if ( ! $rel || ! $base || ! $path || ! str_starts_with( $path, $base . DIRECTORY_SEPARATOR ) || ! is_file( $path ) ) {
			status_header( 404 );
			exit( 'File not found.' );
		}
		$meta   = FFP_Data::file_meta( $file );
		$name   = $meta['original_name'] ?? basename( $path );
		$name   = str_replace( array( '"', "\r", "\n", '\\' ), '', $name );
		$inline = ! $download && ( self::is_image( $file ) || 'application/pdf' === $file->mime );

		while ( ob_get_level() ) {
			ob_end_clean();
		}
		header( 'Content-Type: ' . $file->mime );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode( $name ) );
		header( 'Cache-Control: private, max-age=86400' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	public static function delete( $file ) {
		global $wpdb;
		foreach ( array( $file->path, $file->thumb_path ) as $rel ) {
			if ( $rel ) {
				$path = self::base_dir() . '/' . $rel;
				if ( is_file( $path ) ) {
					wp_delete_file( $path );
				}
			}
		}
		$wpdb->delete( FFP_Data::t( 'files' ), array( 'id' => $file->id ) );
		$wpdb->update( FFP_Data::t( 'projects' ), array( 'cover_file_id' => 0 ), array( 'cover_file_id' => $file->id ) );
	}
}
