<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Database functions for leads table
 */

/**
 * Create leads table on activation
 */
function wafbp_create_leads_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wafbp_leads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        form_id mediumint(9) NOT NULL,
        name varchar(255) DEFAULT '' NOT NULL,
        phone varchar(50) DEFAULT '' NOT NULL,
        city varchar(255) DEFAULT '',
        subject varchar(255) DEFAULT '',
        message text,
        page_url varchar(500) DEFAULT '',
        ip_address varchar(100) DEFAULT NULL,
        user_agent varchar(500) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY form_id (form_id),
        KEY created_at (created_at)
    ) $charset_collate ENGINE=InnoDB;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/**
 * Drop leads table (used during uninstall if enabled)
 */
function wafbp_drop_leads_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wafbp_leads';
    $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}
