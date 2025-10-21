<?php
/**
 * Uninstall script for WhatsApp Form Builder Pro
 *
 * Drops the plugin table and removes plugin-specific options.
 *
 * Note: This file is executed only when the user triggers uninstall from WP admin
 * or when WP_UNINSTALL_PLUGIN is defined.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'wafbp_forms';

// DROP TABLE (this is destructive — kept because plugin stores forms)
// If you prefer to keep data, comment out the DROP below.
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

// Remove DB version option
delete_option( 'wafbp_db_version' );