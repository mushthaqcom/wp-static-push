<?php
/**
 * Plugin Name: WP Static Push
 * Plugin URI:  https://mushthaq.com
 * Description: Generate a fully static version of your WordPress site and push it to GitHub or download as ZIP. SEO-friendly with sitemap, robots.txt, and 404 page support.
 * Version:     1.1.0
 * Author:      Mushthaq
 * Author URI:  https://mushthaq.com
 * License:     GPL-2.0+
 * Text Domain: wp-static-push
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPSP_VERSION',    '1.1.0' );
define( 'WPSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSP_OUTPUT_DIR', WP_CONTENT_DIR . '/wp-static-push-output' );

require_once WPSP_PLUGIN_DIR . 'includes/class-wpsp-settings.php';
require_once WPSP_PLUGIN_DIR . 'includes/class-wpsp-crawler.php';
require_once WPSP_PLUGIN_DIR . 'includes/class-wpsp-seo.php';
require_once WPSP_PLUGIN_DIR . 'includes/class-wpsp-zip.php';
require_once WPSP_PLUGIN_DIR . 'includes/class-wpsp-github.php';
require_once WPSP_PLUGIN_DIR . 'includes/class-wpsp-admin.php';
require_once WPSP_PLUGIN_DIR . 'includes/class-wpsp-ajax.php';

register_activation_hook( __FILE__, 'wpsp_activate' );
register_deactivation_hook( __FILE__, 'wpsp_deactivate' );

function wpsp_activate() {
    if ( ! file_exists( WPSP_OUTPUT_DIR ) ) {
        wp_mkdir_p( WPSP_OUTPUT_DIR );
    }
    // Protect output dir from direct access
    file_put_contents( WPSP_OUTPUT_DIR . '/.htaccess', 'Deny from all' );
    WPSP_Settings::install();
}

function wpsp_deactivate() {
    // Optional: clean up output dir on deactivation
}

function wpsp_init() {
    new WPSP_Admin();
    new WPSP_Ajax();
}
add_action( 'plugins_loaded', 'wpsp_init' );
