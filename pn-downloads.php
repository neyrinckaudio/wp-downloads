<?php
/**
 * Plugin Name: PN Downloads
 * Description: Manage product installer downloads with REST API for version updates.
 * Version: 1.0.0
 * Author: Paul Neyrinck
 * Text Domain: pn-downloads
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PN_DOWNLOADS_VERSION', '1.0.0' );
define( 'PN_DOWNLOADS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PN_DOWNLOADS_URL', plugin_dir_url( __FILE__ ) );

require_once PN_DOWNLOADS_DIR . 'includes/class-cpt.php';
require_once PN_DOWNLOADS_DIR . 'includes/class-admin-settings.php';
require_once PN_DOWNLOADS_DIR . 'includes/class-rest-api.php';
require_once PN_DOWNLOADS_DIR . 'includes/class-tracker.php';
require_once PN_DOWNLOADS_DIR . 'includes/class-shortcodes.php';

function pn_downloads_init() {
    PN_Downloads_CPT::init();
    PN_Downloads_Admin_Settings::init();
    PN_Downloads_REST_API::init();
    PN_Downloads_Tracker::init();
    PN_Downloads_Shortcodes::init();
}
add_action( 'plugins_loaded', 'pn_downloads_init' );

register_activation_hook( __FILE__, 'pn_downloads_activate' );
function pn_downloads_activate() {
    PN_Downloads_CPT::register_post_type();
    flush_rewrite_rules();

    // Generate API key if one doesn't exist.
    if ( ! get_option( 'pn_downloads_api_key' ) ) {
        update_option( 'pn_downloads_api_key', wp_generate_password( 40, false ) );
    }
}

register_deactivation_hook( __FILE__, 'pn_downloads_deactivate' );
function pn_downloads_deactivate() {
    flush_rewrite_rules();
}
