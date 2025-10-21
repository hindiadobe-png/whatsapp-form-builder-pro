<?php
/**
 * Admin Webhooks UI for WhatsApp Form Builder Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add webhooks submenu
 */
function wafbp_add_webhooks_menu() {
    add_submenu_page(
        'wafbp-forms',
        __( 'Webhooks', 'whatsapp-form-builder-pro' ),
        __( 'Webhooks', 'whatsapp-form-builder-pro' ),
        'manage_options',
        'wafbp-webhooks',
        'wafbp_webhooks_page'
    );

    add_submenu_page(
        'wafbp-forms',
        __( 'Leads', 'whatsapp-form-builder-pro' ),
        __( 'Leads', 'whatsapp-form-builder-pro' ),
        'manage_options',
        'wafbp-leads',
        'wafbp_leads_page'
    );
}
add_action( 'admin_menu', 'wafbp_add_webhooks_menu', 20 );

/**
 * Webhooks page
 */
function wafbp_webhooks_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'whatsapp-form-builder-pro' ) );
    }

    // Handle actions
    $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
    $webhook_id = isset( $_GET['webhook_id'] ) ? absint( $_GET['webhook_id'] ) : 0;

    // Handle form submissions
    if ( isset( $_POST['wafbp_webhook_submit'] ) ) {
        check_admin_referer( 'wafbp_webhook_action' );

        $url = isset( $_POST['webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['webhook_url'] ) ) : '';
        $secret = isset( $_POST['webhook_secret'] ) ? wp_unslash( $_POST['webhook_secret'] ) : '';
        $form_id = isset( $_POST['webhook_form_id'] ) && $_POST['webhook_form_id'] !== '' ? absint( $_POST['webhook_form_id'] ) : null;
        $active = isset( $_POST['webhook_active'] ) ? true : false;

        if ( $webhook_id ) {
            // Update existing webhook
            $result = wafbp_update_webhook( $webhook_id, $url, $secret, $form_id, $active );
        } else {
            // Add new webhook
            $result = wafbp_add_webhook( $url, $secret, $form_id, $active );
        }

        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Webhook saved successfully.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            $action = 'list';
            $webhook_id = 0;
        }
    }

    // Handle delete
    if ( $action === 'delete' && $webhook_id ) {
        check_admin_referer( 'wafbp_delete_webhook_' . $webhook_id );
        $result = wafbp_delete_webhook( $webhook_id );
        
        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Webhook deleted successfully.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        }
        
        $action = 'list';
    }

    // Handle test
    if ( $action === 'test' && $webhook_id ) {
        check_admin_referer( 'wafbp_test_webhook_' . $webhook_id );
        $result = wafbp_send_test_webhook( $webhook_id );
        
        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Test webhook sent successfully.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        }
        
        $action = 'list';
    }

    // Handle replay
    if ( $action === 'replay' && $webhook_id && isset( $_GET['lead_id'] ) ) {
        $lead_id = absint( $_GET['lead_id'] );
        check_admin_referer( 'wafbp_replay_webhook_' . $webhook_id . '_' . $lead_id );
        $result = wafbp_replay_webhook( $webhook_id, $lead_id );
        
        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Webhook queued for replay.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        }
        
        $action = 'logs';
    }

    // Display appropriate view
    if ( $action === 'add' || $action === 'edit' ) {
        wafbp_webhooks_edit_form( $webhook_id );
    } elseif ( $action === 'logs' ) {
        wafbp_webhooks_logs_view( $webhook_id );
    } else {
        wafbp_webhooks_list_view();
    }
}

/**
 * Webhooks list view
 */
function wafbp_webhooks_list_view() {
    $webhooks = wafbp_get_webhooks();
    ?>
    <div class="wrap">
        <h1>
            <?php echo esc_html__( 'Webhooks', 'whatsapp-form-builder-pro' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-webhooks&action=add' ) ); ?>" class="page-title-action">
                <?php echo esc_html__( 'Add New', 'whatsapp-form-builder-pro' ); ?>
            </a>
        </h1>

        <?php if ( empty( $webhooks ) ) : ?>
            <p><?php echo esc_html__( 'No webhooks configured. Click "Add New" to create your first webhook!', 'whatsapp-form-builder-pro' ); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__( 'URL', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Form', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Status', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Last Delivery', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Actions', 'whatsapp-form-builder-pro' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $webhooks as $webhook ) :
                        $last_log = wafbp_get_webhook_last_log( $webhook->id );
                        $form_name = wafbp_get_form_name( $webhook->form_id );
                        ?>
                        <tr>
                            <td><code><?php echo esc_html( $webhook->url ); ?></code></td>
                            <td><?php echo $form_name ? esc_html( $form_name ) : '<em>' . esc_html__( 'All Forms', 'whatsapp-form-builder-pro' ) . '</em>'; ?></td>
                            <td>
                                <?php if ( $webhook->active ) : ?>
                                    <span style="color: green;">●</span> <?php echo esc_html__( 'Active', 'whatsapp-form-builder-pro' ); ?>
                                <?php else : ?>
                                    <span style="color: red;">●</span> <?php echo esc_html__( 'Inactive', 'whatsapp-form-builder-pro' ); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ( $last_log ) {
                                    $status_color = ( $last_log->status_code >= 200 && $last_log->status_code < 300 ) ? 'green' : 'red';
                                    echo '<span style="color: ' . esc_attr( $status_color ) . ';">' . esc_html( $last_log->status_code ) . '</span> - ';
                                    echo esc_html( human_time_diff( strtotime( $last_log->created_at ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'whatsapp-form-builder-pro' );
                                } else {
                                    echo '<em>' . esc_html__( 'Never', 'whatsapp-form-builder-pro' ) . '</em>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-webhooks&action=edit&webhook_id=' . $webhook->id ) ); ?>" class="button-secondary button-small">
                                    <?php echo esc_html__( 'Edit', 'whatsapp-form-builder-pro' ); ?>
                                </a>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wafbp-webhooks&action=test&webhook_id=' . $webhook->id ), 'wafbp_test_webhook_' . $webhook->id ) ); ?>" class="button-secondary button-small">
                                    <?php echo esc_html__( 'Test', 'whatsapp-form-builder-pro' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-webhooks&action=logs&webhook_id=' . $webhook->id ) ); ?>" class="button-secondary button-small">
                                    <?php echo esc_html__( 'Logs', 'whatsapp-form-builder-pro' ); ?>
                                </a>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wafbp-webhooks&action=delete&webhook_id=' . $webhook->id ), 'wafbp_delete_webhook_' . $webhook->id ) ); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this webhook?', 'whatsapp-form-builder-pro' ) ); ?>');">
                                    <?php echo esc_html__( 'Delete', 'whatsapp-form-builder-pro' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2><?php echo esc_html__( 'Webhook Testing', 'whatsapp-form-builder-pro' ); ?></h2>
        <p><?php echo esc_html__( 'To test a webhook endpoint manually, use the following curl command:', 'whatsapp-form-builder-pro' ); ?></p>
        <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;">
curl -X POST https://your-webhook-url.com/endpoint \
  -H "Content-Type: application/json" \
  -H "X-WAFBP-Signature: sha256=your_signature_here" \
  -d '{
    "lead_id": 1,
    "form_id": 1,
    "data": {
      "name": "Test User",
      "phone": "1234567890",
      "message": "Test message"
    },
    "page_url": "https://example.com",
    "timestamp": "2024-01-01 12:00:00"
  }'
        </pre>
    </div>
    <?php
}

/**
 * Webhook edit form
 */
function wafbp_webhooks_edit_form( $webhook_id ) {
    $webhook = null;
    if ( $webhook_id ) {
        $webhook = wafbp_get_webhook( $webhook_id );
        if ( ! $webhook ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Webhook not found.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            return;
        }
    }

    // Get forms for dropdown
    global $wpdb;
    $forms_table = $wpdb->prefix . 'wafbp_forms';
    $forms = $wpdb->get_results( "SELECT id, form_name FROM $forms_table ORDER BY form_name" );

    $is_edit = (bool) $webhook;
    ?>
    <div class="wrap">
        <h1><?php echo $is_edit ? esc_html__( 'Edit Webhook', 'whatsapp-form-builder-pro' ) : esc_html__( 'Add New Webhook', 'whatsapp-form-builder-pro' ); ?></h1>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-webhooks' . ( $is_edit ? '&action=edit&webhook_id=' . $webhook_id : '&action=add' ) ) ); ?>">
            <?php wp_nonce_field( 'wafbp_webhook_action' ); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="webhook_url"><?php echo esc_html__( 'Webhook URL', 'whatsapp-form-builder-pro' ); ?> <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <input type="url" name="webhook_url" id="webhook_url" class="regular-text" value="<?php echo $webhook ? esc_attr( $webhook->url ) : ''; ?>" required>
                        <p class="description"><?php echo esc_html__( 'The URL to send webhook payloads to. Must be a valid HTTP or HTTPS URL.', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="webhook_secret"><?php echo esc_html__( 'Secret Key', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="webhook_secret" id="webhook_secret" class="regular-text" placeholder="<?php echo $is_edit ? esc_attr__( 'Leave empty to keep existing secret', 'whatsapp-form-builder-pro' ) : ''; ?>" <?php echo $is_edit ? '' : 'required'; ?>>
                        <p class="description">
                            <?php echo esc_html__( 'Secret key used to sign webhook payloads with HMAC-SHA256. The signature will be sent in the X-WAFBP-Signature header.', 'whatsapp-form-builder-pro' ); ?>
                            <?php if ( $is_edit ) : ?>
                                <br><strong><?php echo esc_html__( 'Leave empty to keep the existing secret.', 'whatsapp-form-builder-pro' ); ?></strong>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="webhook_form_id"><?php echo esc_html__( 'Target Form', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <select name="webhook_form_id" id="webhook_form_id">
                            <option value=""><?php echo esc_html__( 'All Forms', 'whatsapp-form-builder-pro' ); ?></option>
                            <?php foreach ( $forms as $form ) : ?>
                                <option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $webhook && $webhook->form_id == $form->id ); ?>>
                                    <?php echo esc_html( $form->form_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html__( 'Choose a specific form or "All Forms" to receive webhooks from all forms.', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php echo esc_html__( 'Status', 'whatsapp-form-builder-pro' ); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="webhook_active" value="1" <?php checked( ! $webhook || $webhook->active ); ?>>
                            <?php echo esc_html__( 'Active', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                        <p class="description"><?php echo esc_html__( 'Only active webhooks will receive payloads.', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="wafbp_webhook_submit" class="button-primary" value="<?php echo $is_edit ? esc_attr__( 'Update Webhook', 'whatsapp-form-builder-pro' ) : esc_attr__( 'Add Webhook', 'whatsapp-form-builder-pro' ); ?>">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-webhooks' ) ); ?>" class="button">
                    <?php echo esc_html__( 'Cancel', 'whatsapp-form-builder-pro' ); ?>
                </a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Webhook logs view
 */
function wafbp_webhooks_logs_view( $webhook_id ) {
    $webhook = wafbp_get_webhook( $webhook_id );
    
    if ( ! $webhook ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Webhook not found.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        return;
    }

    $logs = wafbp_get_webhook_logs( $webhook_id );
    ?>
    <div class="wrap">
        <h1>
            <?php echo esc_html__( 'Webhook Logs', 'whatsapp-form-builder-pro' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-webhooks' ) ); ?>" class="page-title-action">
                <?php echo esc_html__( 'Back to Webhooks', 'whatsapp-form-builder-pro' ); ?>
            </a>
        </h1>

        <p><strong><?php echo esc_html__( 'Webhook URL:', 'whatsapp-form-builder-pro' ); ?></strong> <code><?php echo esc_html( $webhook->url ); ?></code></p>

        <?php if ( empty( $logs ) ) : ?>
            <p><?php echo esc_html__( 'No logs found for this webhook.', 'whatsapp-form-builder-pro' ); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__( 'Lead ID', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Status Code', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Attempt', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Response', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Time', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Actions', 'whatsapp-form-builder-pro' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $logs as $log ) :
                        $is_success = $log->status_code >= 200 && $log->status_code < 300;
                        ?>
                        <tr>
                            <td><?php echo esc_html( $log->lead_id ); ?></td>
                            <td>
                                <span style="color: <?php echo $is_success ? 'green' : 'red'; ?>;">
                                    <?php echo $log->status_code ? esc_html( $log->status_code ) : '<em>' . esc_html__( 'Error', 'whatsapp-form-builder-pro' ) . '</em>'; ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $log->attempt_number ); ?></td>
                            <td>
                                <details>
                                    <summary style="cursor: pointer;"><?php echo esc_html__( 'View Response', 'whatsapp-form-builder-pro' ); ?></summary>
                                    <pre style="max-width: 400px; overflow: auto; background: #f5f5f5; padding: 10px; margin-top: 5px;"><?php echo esc_html( $log->response ); ?></pre>
                                </details>
                            </td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
                            <td>
                                <?php if ( ! $is_success ) : ?>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wafbp-webhooks&action=replay&webhook_id=' . $webhook_id . '&lead_id=' . $log->lead_id ), 'wafbp_replay_webhook_' . $webhook_id . '_' . $log->lead_id ) ); ?>" class="button-secondary button-small">
                                        <?php echo esc_html__( 'Replay', 'whatsapp-form-builder-pro' ); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Get last log for a webhook
 */
function wafbp_get_webhook_last_log( $webhook_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_webhook_logs';

    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE webhook_id = %d ORDER BY created_at DESC LIMIT 1",
        absint( $webhook_id )
    ) );
}

/**
 * Get form name by ID
 */
function wafbp_get_form_name( $form_id ) {
    if ( ! $form_id ) {
        return null;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wafbp_forms';

    $form = $wpdb->get_row( $wpdb->prepare(
        "SELECT form_name FROM $table WHERE id = %d",
        absint( $form_id )
    ) );

    return $form ? $form->form_name : null;
}

/**
 * Leads page
 */
function wafbp_leads_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'whatsapp-form-builder-pro' ) );
    }

    // Handle export
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'export' ) {
        check_admin_referer( 'wafbp_export_leads' );
        $form_id = isset( $_GET['form_id'] ) && $_GET['form_id'] !== '' ? absint( $_GET['form_id'] ) : null;
        wafbp_export_leads_csv( $form_id );
        exit;
    }

    // Handle delete
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['lead_id'] ) ) {
        $lead_id = absint( $_GET['lead_id'] );
        check_admin_referer( 'wafbp_delete_lead_' . $lead_id );
        
        if ( wafbp_delete_lead( $lead_id ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Lead deleted successfully.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to delete lead.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        }
    }

    // Get filter
    $filter_form_id = isset( $_GET['filter_form'] ) && $_GET['filter_form'] !== '' ? absint( $_GET['filter_form'] ) : null;

    // Get forms for filter
    global $wpdb;
    $forms_table = $wpdb->prefix . 'wafbp_forms';
    $forms = $wpdb->get_results( "SELECT id, form_name FROM $forms_table ORDER BY form_name" );

    // Get leads
    $leads = wafbp_get_leads( $filter_form_id, 100 );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Leads', 'whatsapp-form-builder-pro' ); ?></h1>

        <div style="margin: 20px 0;">
            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: inline-block;">
                <input type="hidden" name="page" value="wafbp-leads">
                <select name="filter_form" onchange="this.form.submit()">
                    <option value=""><?php echo esc_html__( 'All Forms', 'whatsapp-form-builder-pro' ); ?></option>
                    <?php foreach ( $forms as $form ) : ?>
                        <option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $filter_form_id, $form->id ); ?>>
                            <?php echo esc_html( $form->form_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wafbp-leads&action=export' . ( $filter_form_id ? '&form_id=' . $filter_form_id : '' ) ), 'wafbp_export_leads' ) ); ?>" class="button">
                <?php echo esc_html__( 'Export to CSV', 'whatsapp-form-builder-pro' ); ?>
            </a>
        </div>

        <?php if ( empty( $leads ) ) : ?>
            <p><?php echo esc_html__( 'No leads found.', 'whatsapp-form-builder-pro' ); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__( 'ID', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Form', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Data', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Page URL', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Date', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Actions', 'whatsapp-form-builder-pro' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $leads as $lead ) :
                        $lead_data = json_decode( $lead->data, true );
                        $form_name = wafbp_get_form_name( $lead->form_id );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $lead->id ); ?></td>
                            <td><?php echo $form_name ? esc_html( $form_name ) : esc_html( $lead->form_id ); ?></td>
                            <td>
                                <?php if ( $lead_data ) : ?>
                                    <?php foreach ( $lead_data as $key => $value ) : ?>
                                        <strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?>:</strong> <?php echo esc_html( $value ); ?><br>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $lead->page_url ) : ?>
                                    <a href="<?php echo esc_url( $lead->page_url ); ?>" target="_blank"><?php echo esc_html( wp_parse_url( $lead->page_url, PHP_URL_PATH ) ); ?></a>
                                <?php else : ?>
                                    <em><?php echo esc_html__( 'N/A', 'whatsapp-form-builder-pro' ); ?></em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lead->created_at ) ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wafbp-leads&action=delete&lead_id=' . $lead->id ), 'wafbp_delete_lead_' . $lead->id ) ); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this lead?', 'whatsapp-form-builder-pro' ) ); ?>');">
                                    <?php echo esc_html__( 'Delete', 'whatsapp-form-builder-pro' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
