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
$forms_table = $wpdb->prefix . 'wafbp_forms';
$leads_table = $wpdb->prefix . 'wafbp_leads';

// DROP forms TABLE (this is destructive — kept because plugin stores forms)
// If you prefer to keep data, comment out the DROP below.
$wpdb->query( "DROP TABLE IF EXISTS {$forms_table}" );

// Remove DB version option
delete_option( 'wafbp_db_version' );

// Optionally drop leads table if setting enabled
$settings = get_option( 'wafbp_settings', array() );
if ( isset( $settings['delete_data_on_uninstall'] ) && $settings['delete_data_on_uninstall'] === true ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$leads_table}" );
}

// Remove settings
delete_option( 'wafbp_settings' );
