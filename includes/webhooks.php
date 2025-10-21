<?php
/**
 * Webhooks Manager for WhatsApp Form Builder Pro
 *
 * Handles webhook registration, delivery, retries, and logging.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create webhooks tables on activation
 */
function wafbp_create_webhook_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Webhooks table
    $webhooks_table = $wpdb->prefix . 'wafbp_webhooks';
    $webhooks_sql = "CREATE TABLE $webhooks_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        form_id bigint(20) DEFAULT NULL,
        url varchar(500) NOT NULL,
        secret varchar(255) NOT NULL,
        active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY form_id (form_id),
        KEY active (active)
    ) $charset_collate;";

    // Webhook logs table
    $logs_table = $wpdb->prefix . 'wafbp_webhook_logs';
    $logs_sql = "CREATE TABLE $logs_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        webhook_id bigint(20) NOT NULL,
        lead_id bigint(20) NOT NULL,
        status_code int(11) DEFAULT NULL,
        response text,
        attempt_number int(11) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY webhook_id (webhook_id),
        KEY lead_id (lead_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $webhooks_sql );
    dbDelta( $logs_sql );

    // Initialize encryption key if not exists
    if ( ! get_option( 'wafbp_webhook_encryption_key' ) ) {
        $key = wp_generate_password( 64, true, true );
        add_option( 'wafbp_webhook_encryption_key', $key, '', 'no' );
    }
}

/**
 * Get encryption key for webhook secrets
 */
function wafbp_get_encryption_key() {
    $key = get_option( 'wafbp_webhook_encryption_key' );
    if ( ! $key ) {
        // Fallback to AUTH_KEY if available
        if ( defined( 'AUTH_KEY' ) && AUTH_KEY ) {
            $key = substr( AUTH_KEY, 0, 64 );
        } else {
            $key = wp_generate_password( 64, true, true );
            update_option( 'wafbp_webhook_encryption_key', $key );
        }
    }
    return $key;
}

/**
 * Encrypt webhook secret
 */
function wafbp_encrypt_secret( $secret ) {
    if ( function_exists( 'openssl_encrypt' ) ) {
        $key = wafbp_get_encryption_key();
        $iv = openssl_random_pseudo_bytes( 16 );
        $encrypted = openssl_encrypt( $secret, 'AES-256-CBC', $key, 0, $iv );
        return base64_encode( $iv . $encrypted );
    }
    // Fallback: store with wp_hash_password (one-way, cannot decrypt)
    return wp_hash_password( $secret );
}

/**
 * Decrypt webhook secret
 */
function wafbp_decrypt_secret( $encrypted_secret ) {
    if ( function_exists( 'openssl_decrypt' ) ) {
        $key = wafbp_get_encryption_key();
        $decoded = base64_decode( $encrypted_secret );
        $iv = substr( $decoded, 0, 16 );
        $encrypted = substr( $decoded, 16 );
        $decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
        return $decrypted !== false ? $decrypted : '';
    }
    // Cannot decrypt if using wp_hash_password
    return '';
}

/**
 * Add a new webhook
 */
function wafbp_add_webhook( $url, $secret, $form_id = null, $active = true ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'permission_denied', __( 'You do not have permission to add webhooks.', 'whatsapp-form-builder-pro' ) );
    }

    // Validate URL
    $url = esc_url_raw( $url, array( 'http', 'https' ) );
    if ( empty( $url ) ) {
        return new WP_Error( 'invalid_url', __( 'Invalid webhook URL.', 'whatsapp-form-builder-pro' ) );
    }

    // Encrypt secret
    $encrypted_secret = wafbp_encrypt_secret( $secret );

    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_webhooks';

    $result = $wpdb->insert(
        $table,
        array(
            'form_id' => $form_id ? absint( $form_id ) : null,
            'url' => $url,
            'secret' => $encrypted_secret,
            'active' => $active ? 1 : 0,
        ),
        array( '%d', '%s', '%s', '%d' )
    );

    if ( $result === false ) {
        return new WP_Error( 'db_error', __( 'Failed to add webhook.', 'whatsapp-form-builder-pro' ) );
    }

    return $wpdb->insert_id;
}

/**
 * Update a webhook
 */
function wafbp_update_webhook( $webhook_id, $url, $secret, $form_id = null, $active = true ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'permission_denied', __( 'You do not have permission to update webhooks.', 'whatsapp-form-builder-pro' ) );
    }

    $webhook_id = absint( $webhook_id );
    if ( empty( $webhook_id ) ) {
        return new WP_Error( 'invalid_id', __( 'Invalid webhook ID.', 'whatsapp-form-builder-pro' ) );
    }

    // Validate URL
    $url = esc_url_raw( $url, array( 'http', 'https' ) );
    if ( empty( $url ) ) {
        return new WP_Error( 'invalid_url', __( 'Invalid webhook URL.', 'whatsapp-form-builder-pro' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_webhooks';

    $data = array(
        'form_id' => $form_id ? absint( $form_id ) : null,
        'url' => $url,
        'active' => $active ? 1 : 0,
    );
    $format = array( '%d', '%s', '%d' );

    // Update secret only if provided
    if ( ! empty( $secret ) ) {
        $data['secret'] = wafbp_encrypt_secret( $secret );
        $format[] = '%s';
    }

    $result = $wpdb->update(
        $table,
        $data,
        array( 'id' => $webhook_id ),
        $format,
        array( '%d' )
    );

    if ( $result === false ) {
        return new WP_Error( 'db_error', __( 'Failed to update webhook.', 'whatsapp-form-builder-pro' ) );
    }

    return true;
}

/**
 * Delete a webhook
 */
function wafbp_delete_webhook( $webhook_id ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'permission_denied', __( 'You do not have permission to delete webhooks.', 'whatsapp-form-builder-pro' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_webhooks';

    $result = $wpdb->delete(
        $table,
        array( 'id' => absint( $webhook_id ) ),
        array( '%d' )
    );

    if ( $result === false ) {
        return new WP_Error( 'db_error', __( 'Failed to delete webhook.', 'whatsapp-form-builder-pro' ) );
    }

    return true;
}

/**
 * Get webhook by ID
 */
function wafbp_get_webhook( $webhook_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_webhooks';

    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        absint( $webhook_id )
    ) );
}

/**
 * Get all webhooks, optionally filtered by form_id
 */
function wafbp_get_webhooks( $form_id = null, $active_only = false ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_webhooks';

    $where = array();
    $values = array();

    if ( $form_id !== null ) {
        $where[] = '(form_id = %d OR form_id IS NULL)';
        $values[] = absint( $form_id );
    }

    if ( $active_only ) {
        $where[] = 'active = 1';
    }

    $sql = "SELECT * FROM $table";
    if ( ! empty( $where ) ) {
        $sql .= ' WHERE ' . implode( ' AND ', $where );
    }
    $sql .= ' ORDER BY created_at DESC';

    if ( ! empty( $values ) ) {
        $sql = $wpdb->prepare( $sql, $values );
    }

    return $wpdb->get_results( $sql );
}

/**
 * Get webhooks for a specific form (including global webhooks)
 */
function wafbp_get_webhooks_for_form( $form_id ) {
    $webhooks = wafbp_get_webhooks( $form_id, true );
    
    // Apply filter to allow extensions
    return apply_filters( 'wafbp_webhook_endpoints_for_form', $webhooks, $form_id );
}

/**
 * Send webhooks for a lead
 * 
 * Hooked to wafbp_after_save_lead action
 */
function wafbp_send_webhooks( $lead_id, $payload ) {
    // Get form_id from payload
    $form_id = isset( $payload['form_id'] ) ? absint( $payload['form_id'] ) : 0;

    // Apply filter to payload
    $payload = apply_filters( 'wafbp_save_lead_payload', $payload, $form_id );

    // Get webhooks for this form
    $webhooks = wafbp_get_webhooks_for_form( $form_id );

    if ( empty( $webhooks ) ) {
        return;
    }

    // Check if async sending is enabled
    $async_enabled = get_option( 'wafbp_webhook_async_enabled', true );

    foreach ( $webhooks as $webhook ) {
        if ( $async_enabled ) {
            // Schedule async delivery
            wafbp_schedule_webhook_delivery( $webhook->id, $lead_id, $payload, 1 );
        } else {
            // Send immediately
            wafbp_deliver_webhook( $webhook->id, $lead_id, $payload, 1 );
        }
    }
}
add_action( 'wafbp_after_save_lead', 'wafbp_send_webhooks', 10, 2 );

/**
 * Schedule webhook delivery
 */
function wafbp_schedule_webhook_delivery( $webhook_id, $lead_id, $payload, $attempt = 1 ) {
    // Store in transient for processing
    $queue_key = 'wafbp_webhook_queue_' . $webhook_id . '_' . $lead_id;
    
    $queue_data = array(
        'webhook_id' => $webhook_id,
        'lead_id' => $lead_id,
        'payload' => $payload,
        'attempt' => $attempt,
        'scheduled_at' => time(),
    );

    set_transient( $queue_key, $queue_data, DAY_IN_SECONDS );

    // Schedule single event to process this webhook
    if ( ! wp_next_scheduled( 'wafbp_process_webhook_queue', array( $queue_key ) ) ) {
        wp_schedule_single_event( time() + 5, 'wafbp_process_webhook_queue', array( $queue_key ) );
    }
}

/**
 * Deliver a webhook
 */
function wafbp_deliver_webhook( $webhook_id, $lead_id, $payload, $attempt = 1 ) {
    $webhook = wafbp_get_webhook( $webhook_id );

    if ( ! $webhook || ! $webhook->active ) {
        return;
    }

    // Prepare body
    $body = wp_json_encode( $payload );

    // Decrypt secret for HMAC signing
    $secret = wafbp_decrypt_secret( $webhook->secret );
    
    // Generate HMAC signature
    $signature = 'sha256=' . hash_hmac( 'sha256', $body, $secret );

    // Send request
    $response = wp_remote_post( $webhook->url, array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-WAFBP-Signature' => $signature,
        ),
        'body' => $body,
    ) );

    // Process response
    $status_code = null;
    $response_body = '';

    if ( is_wp_error( $response ) ) {
        $response_body = $response->get_error_message();
        $success = false;
    } else {
        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $success = $status_code >= 200 && $status_code < 300;
    }

    // Log delivery
    wafbp_log_webhook_delivery( $webhook_id, $lead_id, $status_code, $response_body, $attempt );

    // Fire hooks
    if ( $success ) {
        do_action( 'wafbp_webhook_sent', $webhook_id, $lead_id, $response, $attempt );
    } else {
        do_action( 'wafbp_webhook_failed', $webhook_id, $lead_id, is_wp_error( $response ) ? $response : $status_code, $attempt );

        // Schedule retry if not exceeded max attempts
        $max_attempts = get_option( 'wafbp_webhook_max_attempts', 4 );
        if ( $attempt < $max_attempts ) {
            wafbp_schedule_webhook_retry( $webhook_id, $lead_id, $payload, $attempt + 1 );
        }
    }

    return $success;
}

/**
 * Schedule webhook retry with exponential backoff
 */
function wafbp_schedule_webhook_retry( $webhook_id, $lead_id, $payload, $attempt ) {
    // Get retry schedule (in seconds)
    $retry_schedule = get_option( 'wafbp_webhook_retry_schedule', array( 60, 300, 1800, 7200 ) );
    
    if ( ! is_array( $retry_schedule ) ) {
        $retry_schedule = array( 60, 300, 1800, 7200 );
    }

    // Get delay for this attempt (use last value if attempt exceeds schedule)
    $delay_index = min( $attempt - 1, count( $retry_schedule ) - 1 );
    $delay = isset( $retry_schedule[ $delay_index ] ) ? $retry_schedule[ $delay_index ] : 7200;

    // Schedule retry
    wafbp_schedule_webhook_delivery( $webhook_id, $lead_id, $payload, $attempt );
}

/**
 * Log webhook delivery
 */
function wafbp_log_webhook_delivery( $webhook_id, $lead_id, $status_code, $response, $attempt ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_webhook_logs';

    // Truncate response if too long
    if ( strlen( $response ) > 5000 ) {
        $response = substr( $response, 0, 5000 ) . '... (truncated)';
    }

    $wpdb->insert(
        $table,
        array(
            'webhook_id' => absint( $webhook_id ),
            'lead_id' => absint( $lead_id ),
            'status_code' => $status_code ? absint( $status_code ) : null,
            'response' => sanitize_textarea_field( $response ),
            'attempt_number' => absint( $attempt ),
        ),
        array( '%d', '%d', '%d', '%s', '%d' )
    );
}

/**
 * Get webhook logs
 */
function wafbp_get_webhook_logs( $webhook_id = null, $limit = 100 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_webhook_logs';

    $sql = "SELECT * FROM $table";
    
    if ( $webhook_id ) {
        $sql .= $wpdb->prepare( " WHERE webhook_id = %d", absint( $webhook_id ) );
    }

    $sql .= " ORDER BY created_at DESC LIMIT " . absint( $limit );

    return $wpdb->get_results( $sql );
}

/**
 * Process webhook queue (cron worker)
 */
function wafbp_process_webhook_queue( $queue_key ) {
    // Get lock to prevent concurrent processing
    $lock_key = 'wafbp_webhook_lock_' . $queue_key;
    
    if ( get_transient( $lock_key ) ) {
        // Already being processed
        return;
    }

    // Set lock (5 minutes)
    set_transient( $lock_key, 1, 300 );

    // Get queue data
    $queue_data = get_transient( $queue_key );

    if ( ! $queue_data ) {
        delete_transient( $lock_key );
        return;
    }

    // Deliver webhook
    $success = wafbp_deliver_webhook(
        $queue_data['webhook_id'],
        $queue_data['lead_id'],
        $queue_data['payload'],
        $queue_data['attempt']
    );

    // Remove from queue if successful
    if ( $success ) {
        delete_transient( $queue_key );
    }

    // Release lock
    delete_transient( $lock_key );
}
add_action( 'wafbp_process_webhook_queue', 'wafbp_process_webhook_queue' );

/**
 * Replay failed webhook
 */
function wafbp_replay_webhook( $webhook_id, $lead_id ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'permission_denied', __( 'You do not have permission to replay webhooks.', 'whatsapp-form-builder-pro' ) );
    }

    // Get the original lead data from logs or recreate payload
    // For now, we'll need to reconstruct from lead data
    global $wpdb;
    $leads_table = $wpdb->prefix . 'wafbp_leads';

    $lead = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $leads_table WHERE id = %d",
        absint( $lead_id )
    ) );

    if ( ! $lead ) {
        return new WP_Error( 'lead_not_found', __( 'Lead not found.', 'whatsapp-form-builder-pro' ) );
    }

    // Reconstruct payload
    $payload = json_decode( $lead->data, true );
    if ( ! $payload ) {
        $payload = array( 'lead_id' => $lead_id );
    }

    // Schedule delivery
    wafbp_schedule_webhook_delivery( $webhook_id, $lead_id, $payload, 1 );

    return true;
}

/**
 * Send test webhook
 */
function wafbp_send_test_webhook( $webhook_id ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'permission_denied', __( 'You do not have permission to send test webhooks.', 'whatsapp-form-builder-pro' ) );
    }

    $webhook = wafbp_get_webhook( $webhook_id );

    if ( ! $webhook ) {
        return new WP_Error( 'webhook_not_found', __( 'Webhook not found.', 'whatsapp-form-builder-pro' ) );
    }

    // Create test payload
    $payload = array(
        'test' => true,
        'form_id' => $webhook->form_id,
        'timestamp' => current_time( 'mysql' ),
        'data' => array(
            'name' => 'Test User',
            'phone' => '1234567890',
            'message' => 'This is a test webhook',
        ),
    );

    // Deliver immediately
    return wafbp_deliver_webhook( $webhook_id, 0, $payload, 1 );
}
