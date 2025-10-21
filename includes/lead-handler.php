<?php
/**
 * Lead Handler for WhatsApp Form Builder Pro
 *
 * Handles saving leads from form submissions and triggering webhooks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create leads table on activation
 */
function wafbp_create_leads_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'wafbp_leads';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        form_id bigint(20) NOT NULL,
        data longtext NOT NULL,
        page_url varchar(500) DEFAULT NULL,
        user_agent varchar(255) DEFAULT NULL,
        ip_address varchar(100) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY form_id (form_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/**
 * Save lead from form submission
 */
function wafbp_save_lead( $form_id, $form_data ) {
    // Check if save_leads is enabled
    $save_leads_enabled = get_option( 'wafbp_save_leads_enabled', false );
    
    if ( ! $save_leads_enabled ) {
        return false;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_leads';

    // Get page URL from referrer or request
    $page_url = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '';
    
    // Get user agent
    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
    
    // Get IP address
    $ip_address = wafbp_get_client_ip();

    // Sanitize form data
    $sanitized_data = array();
    foreach ( $form_data as $key => $value ) {
        $sanitized_data[ sanitize_key( $key ) ] = sanitize_text_field( $value );
    }

    // Insert lead
    $result = $wpdb->insert(
        $table,
        array(
            'form_id' => absint( $form_id ),
            'data' => wp_json_encode( $sanitized_data ),
            'page_url' => $page_url,
            'user_agent' => $user_agent,
            'ip_address' => $ip_address,
        ),
        array( '%d', '%s', '%s', '%s', '%s' )
    );

    if ( $result === false ) {
        return false;
    }

    $lead_id = $wpdb->insert_id;

    // Prepare webhook payload
    $payload = array(
        'lead_id' => $lead_id,
        'form_id' => $form_id,
        'data' => $sanitized_data,
        'page_url' => $page_url,
        'timestamp' => current_time( 'mysql' ),
    );

    // Trigger webhook action
    do_action( 'wafbp_after_save_lead', $lead_id, $payload );

    return $lead_id;
}

/**
 * Get client IP address
 */
function wafbp_get_client_ip() {
    $ip_keys = array(
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    );

    foreach ( $ip_keys as $key ) {
        if ( isset( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
            return sanitize_text_field( $_SERVER[ $key ] );
        }
    }

    return '';
}

/**
 * AJAX handler for saving leads
 */
function wafbp_ajax_save_lead() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wafbp_form_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'whatsapp-form-builder-pro' ) ) );
    }

    // Get form ID
    $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

    if ( empty( $form_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'whatsapp-form-builder-pro' ) ) );
    }

    // Get form data
    $form_data = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : array();

    if ( empty( $form_data ) ) {
        wp_send_json_error( array( 'message' => __( 'No form data provided.', 'whatsapp-form-builder-pro' ) ) );
    }

    // Save lead
    $lead_id = wafbp_save_lead( $form_id, $form_data );

    if ( $lead_id ) {
        wp_send_json_success( array(
            'message' => __( 'Lead saved successfully.', 'whatsapp-form-builder-pro' ),
            'lead_id' => $lead_id,
        ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Failed to save lead.', 'whatsapp-form-builder-pro' ) ) );
    }
}
add_action( 'wp_ajax_wafbp_save_lead', 'wafbp_ajax_save_lead' );
add_action( 'wp_ajax_nopriv_wafbp_save_lead', 'wafbp_ajax_save_lead' );

/**
 * Get leads from database
 */
function wafbp_get_leads( $form_id = null, $limit = 100, $offset = 0 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_leads';

    $sql = "SELECT * FROM $table";
    
    if ( $form_id ) {
        $sql .= $wpdb->prepare( " WHERE form_id = %d", absint( $form_id ) );
    }

    $sql .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $sql = $wpdb->prepare( $sql, absint( $limit ), absint( $offset ) );

    return $wpdb->get_results( $sql );
}

/**
 * Get lead by ID
 */
function wafbp_get_lead( $lead_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_leads';

    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        absint( $lead_id )
    ) );
}

/**
 * Delete lead
 */
function wafbp_delete_lead( $lead_id ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return false;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_leads';

    return $wpdb->delete(
        $table,
        array( 'id' => absint( $lead_id ) ),
        array( '%d' )
    );
}

/**
 * Export leads to CSV
 */
function wafbp_export_leads_csv( $form_id = null ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return false;
    }

    $leads = wafbp_get_leads( $form_id, 10000, 0 );

    if ( empty( $leads ) ) {
        return false;
    }

    // Set headers for CSV download
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=leads-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv' );

    // Open output stream
    $output = fopen( 'php://output', 'w' );

    // Get all possible field keys
    $field_keys = array( 'id', 'form_id', 'created_at', 'page_url' );
    foreach ( $leads as $lead ) {
        $data = json_decode( $lead->data, true );
        if ( $data ) {
            foreach ( array_keys( $data ) as $key ) {
                if ( ! in_array( $key, $field_keys ) ) {
                    $field_keys[] = $key;
                }
            }
        }
    }

    // Write header row
    fputcsv( $output, $field_keys );

    // Write data rows
    foreach ( $leads as $lead ) {
        $data = json_decode( $lead->data, true );
        $row = array();
        
        foreach ( $field_keys as $key ) {
            if ( $key === 'id' ) {
                $row[] = $lead->id;
            } elseif ( $key === 'form_id' ) {
                $row[] = $lead->form_id;
            } elseif ( $key === 'created_at' ) {
                $row[] = $lead->created_at;
            } elseif ( $key === 'page_url' ) {
                $row[] = $lead->page_url;
            } else {
                $row[] = isset( $data[ $key ] ) ? $data[ $key ] : '';
            }
        }
        
        fputcsv( $output, $row );
    }

    fclose( $output );
    exit;
}
