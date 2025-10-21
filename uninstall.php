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
$webhooks_table = $wpdb->prefix . 'wafbp_webhooks';
$webhook_logs_table = $wpdb->prefix . 'wafbp_webhook_logs';
$leads_table = $wpdb->prefix . 'wafbp_leads';

// DROP TABLE (this is destructive — kept because plugin stores forms)
// If you prefer to keep data, comment out the DROP below.
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$webhooks_table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$webhook_logs_table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$leads_table}" );

// Remove DB version option
delete_option( 'wafbp_db_version' );

// Remove webhook options
delete_option( 'wafbp_webhook_encryption_key' );
delete_option( 'wafbp_save_leads_enabled' );
delete_option( 'wafbp_webhook_async_enabled' );
delete_option( 'wafbp_webhook_max_attempts' );
delete_option( 'wafbp_webhook_retry_schedule' );

// Clean up transients (webhook queue and locks)
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wafbp_webhook_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wafbp_webhook_%'" );