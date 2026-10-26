<?php
/**
 * Activation, database tables, roles and upgrades.
 */

defined( 'ABSPATH' ) || exit;

class FFP_Install {

	public static function activate() {
		self::create_tables();
		self::add_roles();
		FFP_Files::ensure_private_dir();
		if ( false === get_option( 'ffp_settings' ) ) {
			add_option( 'ffp_settings', ffp_default_settings(), '', false );
		}
		FFP_Router::rewrites();
		flush_rewrite_rules();
		update_option( 'ffp_rewrite_version', FFP_VERSION );
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( get_option( 'ffp_db_version' ) !== FFP_DB_VERSION ) {
			self::create_tables();
			self::add_roles();
			FFP_Files::ensure_private_dir();
		}
		if ( get_option( 'ffp_rewrite_version' ) !== FFP_VERSION ) {
			add_action(
				'init',
				function () {
					flush_rewrite_rules();
					update_option( 'ffp_rewrite_version', FFP_VERSION );
				},
				99
			);
		}
	}

	public static function add_roles() {
		if ( ! get_role( 'ffp_customer' ) ) {
			add_role( 'ffp_customer', 'Portal Customer', array( 'read' => true ) );
		}
		if ( ! get_role( 'ffp_staff' ) ) {
			add_role( 'ffp_staff', 'Portal Staff', array( 'read' => true, 'ffp_manage_projects' => true ) );
		}
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'ffp_manage_projects' );
			$admin->add_cap( 'ffp_manage_settings' );
		}
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'ffp_';

		dbDelta(
			"CREATE TABLE {$p}projects (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(200) NOT NULL DEFAULT '',
			address varchar(255) NOT NULL DEFAULT '',
			status varchar(30) NOT NULL DEFAULT 'planning',
			description text NULL,
			start_date date NULL,
			target_date date NULL,
			manager_id bigint(20) unsigned NOT NULL DEFAULT 0,
			cover_file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			notify_customer tinyint(1) NOT NULL DEFAULT 1,
			archived tinyint(1) NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY archived (archived)
			) $c;
			CREATE TABLE {$p}members (
			project_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			added_at datetime NOT NULL,
			PRIMARY KEY  (project_id,user_id),
			KEY user_id (user_id)
			) $c;
			CREATE TABLE {$p}logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL,
			log_date date NOT NULL,
			data longtext NULL,
			visible tinyint(1) NOT NULL DEFAULT 1,
			notified tinyint(1) NOT NULL DEFAULT 0,
			author_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY project_date (project_id,log_date)
			) $c;
			CREATE TABLE {$p}files (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL,
			owner_type varchar(20) NOT NULL DEFAULT '',
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			category varchar(40) NOT NULL DEFAULT '',
			title varchar(200) NOT NULL DEFAULT '',
			caption text NULL,
			meta longtext NULL,
			path varchar(255) NOT NULL DEFAULT '',
			thumb_path varchar(255) NOT NULL DEFAULT '',
			mime varchar(100) NOT NULL DEFAULT '',
			size bigint(20) unsigned NOT NULL DEFAULT 0,
			width int(11) NOT NULL DEFAULT 0,
			height int(11) NOT NULL DEFAULT 0,
			visible tinyint(1) NOT NULL DEFAULT 1,
			uploaded_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY project_owner (project_id,owner_type,owner_id)
			) $c;
			CREATE TABLE {$p}specs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL,
			category varchar(80) NOT NULL DEFAULT '',
			location varchar(150) NOT NULL DEFAULT '',
			product varchar(200) NOT NULL DEFAULT '',
			brand varchar(150) NOT NULL DEFAULT '',
			color varchar(150) NOT NULL DEFAULT '',
			code varchar(150) NOT NULL DEFAULT '',
			finish varchar(150) NOT NULL DEFAULT '',
			quantity varchar(80) NOT NULL DEFAULT '',
			supplier varchar(200) NOT NULL DEFAULT '',
			purpose text NULL,
			installed_on date NULL,
			notes text NULL,
			sort int(11) NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY project_id (project_id)
			) $c;
			CREATE TABLE {$p}comments (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			type varchar(20) NOT NULL DEFAULT 'comment',
			body text NULL,
			status varchar(20) NOT NULL DEFAULT '',
			response text NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY project_parent (project_id,parent_id)
			) $c;
			CREATE TABLE {$p}milestones (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL,
			title varchar(200) NOT NULL DEFAULT '',
			target_date date NULL,
			status varchar(20) NOT NULL DEFAULT 'upcoming',
			sort int(11) NOT NULL DEFAULT 0,
			completed_at datetime NULL,
			PRIMARY KEY  (id),
			KEY project_id (project_id)
			) $c;"
		);

		update_option( 'ffp_db_version', FFP_DB_VERSION );
	}
}
