<?php
/**
 * Plugin Name:       Fox & Fern Customer Portal
 * Description:       Private project portal for Fox & Fern customers: daily progress updates, photos, selections & spec sheets, warranty documents and messages / change requests.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Fox & Fern Property Services
 * License:           GPL-2.0-or-later
 * Text Domain:       ffp
 */

defined( 'ABSPATH' ) || exit;

define( 'FFP_VERSION', '1.0.0' );
define( 'FFP_DB_VERSION', '1' );
define( 'FFP_FILE', __FILE__ );
define( 'FFP_DIR', plugin_dir_path( __FILE__ ) );
define( 'FFP_URL', plugin_dir_url( __FILE__ ) );

// The portal lives at /portal/. Define FFP_SLUG in wp-config.php to move it.
if ( ! defined( 'FFP_SLUG' ) ) {
	define( 'FFP_SLUG', 'portal' );
}

require FFP_DIR . 'includes/helpers.php';
require FFP_DIR . 'includes/class-ffp-install.php';
require FFP_DIR . 'includes/class-ffp-data.php';
require FFP_DIR . 'includes/class-ffp-files.php';
require FFP_DIR . 'includes/class-ffp-auth.php';
require FFP_DIR . 'includes/class-ffp-notify.php';
require FFP_DIR . 'includes/class-ffp-csv.php';
require FFP_DIR . 'includes/class-ffp-actions.php';
require FFP_DIR . 'includes/class-ffp-router.php';

register_activation_hook( __FILE__, array( 'FFP_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'FFP_Install', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'FFP_Install', 'maybe_upgrade' ) );
FFP_Auth::init();
FFP_Router::init();
