<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Lead handling and AJAX submission
 */

/**
 * Save a lead to the database
 * 
 * @param int $form_id Form ID
 * @param array $data Lead data (name, phone, city, subject, message, page_url)
 * @return int|WP_Error Lead ID on success, WP_Error on failure
 */
function wafbp_save_lead( $form_id, $data ) {
    global $wpdb;
    
    // Validate form_id
    $form_id = absint( $form_id );
    if ( empty( $form_id ) ) {
        return new WP_Error( 'invalid_form_id', __( 'Invalid form ID.', 'whatsapp-form-builder-pro' ) );
    }
    
    // Validate phone (digits only)
    $phone = isset( $data['phone'] ) ? preg_replace( '/\D+/', '', $data['phone'] ) : '';
    if ( empty( $phone ) ) {
        return new WP_Error( 'invalid_phone', __( 'Phone number is required and must contain only digits.', 'whatsapp-form-builder-pro' ) );
    }
    
    // Sanitize all inputs
    $name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
    $city = isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : '';
    $subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : '';
    $message = isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : '';
    $page_url = isset( $data['page_url'] ) ? esc_url_raw( $data['page_url'] ) : '';
    
    // Get settings
    $settings = wafbp_get_settings();
    
    // Optionally capture IP and user agent
    $ip_address = null;
    $user_agent = null;
    
    if ( apply_filters( 'wafbp_should_save_ip', $settings['save_ip'], $form_id ) ) {
        $ip_address = wafbp_get_client_ip();
    }
    
    if ( $settings['save_ip'] ) {
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null;
    }
    
    // Build payload
    $payload = array(
        'form_id' => $form_id,
        'name' => $name,
        'phone' => $phone,
        'city' => $city,
        'subject' => $subject,
        'message' => $message,
        'page_url' => $page_url,
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'created_at' => current_time( 'mysql' ),
    );
    
    // Allow filtering
    $payload = apply_filters( 'wafbp_save_lead_payload', $payload, $form_id );
    
    // Insert into database
    $table_name = $wpdb->prefix . 'wafbp_leads';
    $result = $wpdb->insert(
        $table_name,
        $payload,
        array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
    );
    
    if ( $result === false ) {
        return new WP_Error( 'db_error', __( 'Failed to save lead to database.', 'whatsapp-form-builder-pro' ) );
    }
    
    $lead_id = $wpdb->insert_id;
    
    // Trigger action hook
    do_action( 'wafbp_after_save_lead', $lead_id, $payload );
    
    return $lead_id;
}

/**
 * Get client IP address
 * 
 * @return string|null IP address or null
 */
function wafbp_get_client_ip() {
    $ip = null;
    
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Sanitize IP
    if ( $ip ) {
        $ip = sanitize_text_field( wp_unslash( $ip ) );
        // Validate IP format
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $ip = null;
        }
    }
    
    return $ip;
}

/**
 * Check rate limiting per IP
 * 
 * @param string $ip IP address
 * @return bool True if allowed, false if rate limited
 */
function wafbp_check_rate_limit( $ip ) {
    $transient_key = 'wafbp_rate_limit_' . md5( $ip );
    $attempts = get_transient( $transient_key );
    
    if ( $attempts === false ) {
        // First attempt
        set_transient( $transient_key, 1, 60 ); // 1 minute
        return true;
    }
    
    if ( $attempts >= 5 ) {
        // Too many attempts
        return false;
    }
    
    // Increment attempts
    set_transient( $transient_key, $attempts + 1, 60 );
    return true;
}

/**
 * AJAX handler for saving leads (for logged-in users)
 */
function wafbp_ajax_save_lead() {
    wafbp_ajax_save_lead_handler();
}
add_action( 'wp_ajax_wafbp_save_lead', 'wafbp_ajax_save_lead' );

/**
 * AJAX handler for saving leads (for non-logged-in users)
 */
function wafbp_ajax_save_lead_nopriv() {
    wafbp_ajax_save_lead_handler();
}
add_action( 'wp_ajax_nopriv_wafbp_save_lead', 'wafbp_ajax_save_lead_nopriv' );

/**
 * Common AJAX handler for both logged-in and non-logged-in users
 */
function wafbp_ajax_save_lead_handler() {
    // Check nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wafbp_frontend_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'whatsapp-form-builder-pro' ) ) );
        return;
    }
    
    // Check if save_leads is enabled
    $settings = wafbp_get_settings();
    if ( ! $settings['save_leads'] ) {
        wp_send_json_success( array( 'message' => __( 'Lead saving is disabled.', 'whatsapp-form-builder-pro' ) ) );
        return;
    }
    
    // Check opt-in consent
    $opt_in = isset( $_POST['opt_in'] ) ? sanitize_text_field( wp_unslash( $_POST['opt_in'] ) ) : '';
    if ( $opt_in !== '1' ) {
        wp_send_json_success( array( 'message' => __( 'Opt-in not provided.', 'whatsapp-form-builder-pro' ) ) );
        return;
    }
    
    // Rate limiting
    $ip = wafbp_get_client_ip();
    if ( $ip && ! wafbp_check_rate_limit( $ip ) ) {
        wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'whatsapp-form-builder-pro' ) ) );
        return;
    }
    
    // Get form data
    $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
    $data = array(
        'name' => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
        'phone' => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
        'city' => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
        'subject' => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
        'message' => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
        'page_url' => isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '',
    );
    
    // Save lead
    $result = wafbp_save_lead( $form_id, $data );
    
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        return;
    }
    
    wp_send_json_success( array(
        'message' => __( 'Lead saved successfully.', 'whatsapp-form-builder-pro' ),
        'lead_id' => $result,
    ) );
}
