<?php
/*
Plugin Name: WhatsApp Form Builder Pro
Description: Create multiple, highly customizable WhatsApp contact forms with unique shortcodes. Supports RTL, Google Fonts, and comprehensive styling options.
Version: 2.0
Author: Your Name (Oceanhub Agency)
Author URI: https://example.com
Text Domain: whatsapp-form-builder-pro
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! defined( 'WAFBP_PATH' ) ) {
    define( 'WAFBP_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WAFBP_URL' ) ) {
    define( 'WAFBP_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WAFBP_VERSION' ) ) {
    define( 'WAFBP_VERSION', '2.0' );
}
if ( ! defined( 'WAFBP_DB_VERSION' ) ) {
    define( 'WAFBP_DB_VERSION', '1.0' );
}

// Include files if they exist
$includes = array(
    'includes/helpers.php',
    'includes/admin-menu.php',
    'includes/form-editor.php',
    'includes/shortcode.php',
    'includes/enqueue-assets.php',
    'includes/style.php', // optional wrapper (present for compatibility)
);

foreach ( $includes as $inc ) {
    $path = WAFBP_PATH . $inc;
    if ( file_exists( $path ) ) {
        require_once $path;
    } else {
        // Optional debug: error_log( "WAFBP: missing include $path" );
    }
}

/**
 * Activation hook: create table for forms
 */
function wafbp_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wafbp_forms';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_name varchar(255) NOT NULL,
        settings longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option( 'wafbp_db_version', WAFBP_DB_VERSION );
}
register_activation_hook( __FILE__, 'wafbp_activate' );

/**
 * Deactivation hook (no destructive action by default)
 */
function wafbp_deactivate() {
    // Intentionally empty to preserve data by default.
}
register_deactivation_hook( __FILE__, 'wafbp_deactivate' );

/**
 * Load plugin textdomain
 */
function wafbp_load_textdomain() {
    load_plugin_textdomain( 'whatsapp-form-builder-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'wafbp_load_textdomain' );