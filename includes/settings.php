<?php
/**
 * Settings page for WhatsApp Form Builder Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add settings submenu
 */
function wafbp_add_settings_menu() {
    add_submenu_page(
        'wafbp-forms',
        __( 'Settings', 'whatsapp-form-builder-pro' ),
        __( 'Settings', 'whatsapp-form-builder-pro' ),
        'manage_options',
        'wafbp-settings',
        'wafbp_settings_page'
    );
}
add_action( 'admin_menu', 'wafbp_add_settings_menu', 30 );

/**
 * Register settings
 */
function wafbp_register_settings() {
    register_setting( 'wafbp_settings_group', 'wafbp_save_leads_enabled' );
    register_setting( 'wafbp_settings_group', 'wafbp_webhook_async_enabled' );
    register_setting( 'wafbp_settings_group', 'wafbp_webhook_max_attempts' );
    register_setting( 'wafbp_settings_group', 'wafbp_webhook_retry_schedule' );
}
add_action( 'admin_init', 'wafbp_register_settings' );

/**
 * Settings page
 */
function wafbp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'whatsapp-form-builder-pro' ) );
    }

    // Handle form submission
    if ( isset( $_POST['wafbp_settings_submit'] ) ) {
        check_admin_referer( 'wafbp_settings_action' );

        // Save leads enabled
        $save_leads = isset( $_POST['wafbp_save_leads_enabled'] ) ? true : false;
        update_option( 'wafbp_save_leads_enabled', $save_leads );

        // Webhook async enabled
        $async_enabled = isset( $_POST['wafbp_webhook_async_enabled'] ) ? true : false;
        update_option( 'wafbp_webhook_async_enabled', $async_enabled );

        // Max attempts
        $max_attempts = isset( $_POST['wafbp_webhook_max_attempts'] ) ? absint( $_POST['wafbp_webhook_max_attempts'] ) : 4;
        update_option( 'wafbp_webhook_max_attempts', $max_attempts );

        // Retry schedule
        $retry_schedule = isset( $_POST['wafbp_webhook_retry_schedule'] ) ? sanitize_text_field( wp_unslash( $_POST['wafbp_webhook_retry_schedule'] ) ) : '60,300,1800,7200';
        $retry_array = array_map( 'absint', explode( ',', $retry_schedule ) );
        update_option( 'wafbp_webhook_retry_schedule', $retry_array );

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'whatsapp-form-builder-pro' ) . '</p></div>';
    }

    // Get current settings
    $save_leads_enabled = get_option( 'wafbp_save_leads_enabled', false );
    $async_enabled = get_option( 'wafbp_webhook_async_enabled', true );
    $max_attempts = get_option( 'wafbp_webhook_max_attempts', 4 );
    $retry_schedule = get_option( 'wafbp_webhook_retry_schedule', array( 60, 300, 1800, 7200 ) );
    
    if ( is_array( $retry_schedule ) ) {
        $retry_schedule_str = implode( ',', $retry_schedule );
    } else {
        $retry_schedule_str = '60,300,1800,7200';
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'WhatsApp Form Builder Pro Settings', 'whatsapp-form-builder-pro' ); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'wafbp_settings_action' ); ?>

            <h2><?php echo esc_html__( 'Lead Storage', 'whatsapp-form-builder-pro' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Save Leads', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wafbp_save_leads_enabled" value="1" <?php checked( $save_leads_enabled ); ?>>
                            <?php echo esc_html__( 'Enable lead storage in database', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                        <p class="description">
                            <?php echo esc_html__( 'When enabled, form submissions will be saved to the database and can be viewed in the Leads page. This is required for webhooks to function.', 'whatsapp-form-builder-pro' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h2><?php echo esc_html__( 'Webhook Settings', 'whatsapp-form-builder-pro' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Async Delivery', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wafbp_webhook_async_enabled" value="1" <?php checked( $async_enabled ); ?>>
                            <?php echo esc_html__( 'Enable asynchronous webhook delivery', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                        <p class="description">
                            <?php echo esc_html__( 'When enabled, webhooks are sent in the background via WordPress cron. Recommended for better performance with multiple webhooks.', 'whatsapp-form-builder-pro' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wafbp_webhook_max_attempts"><?php echo esc_html__( 'Max Retry Attempts', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="wafbp_webhook_max_attempts" id="wafbp_webhook_max_attempts" min="1" max="10" value="<?php echo esc_attr( $max_attempts ); ?>" class="small-text">
                        <p class="description">
                            <?php echo esc_html__( 'Maximum number of retry attempts for failed webhooks. Default: 4', 'whatsapp-form-builder-pro' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wafbp_webhook_retry_schedule"><?php echo esc_html__( 'Retry Schedule (seconds)', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wafbp_webhook_retry_schedule" id="wafbp_webhook_retry_schedule" value="<?php echo esc_attr( $retry_schedule_str ); ?>" class="regular-text">
                        <p class="description">
                            <?php echo esc_html__( 'Comma-separated list of delays (in seconds) between retry attempts. Default: 60,300,1800,7200 (1min, 5min, 30min, 2hr)', 'whatsapp-form-builder-pro' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="wafbp_settings_submit" class="button-primary" value="<?php echo esc_attr__( 'Save Settings', 'whatsapp-form-builder-pro' ); ?>">
            </p>
        </form>

        <h2><?php echo esc_html__( 'About Webhook Security', 'whatsapp-form-builder-pro' ); ?></h2>
        <p><?php echo esc_html__( 'Webhooks are signed using HMAC-SHA256 with your secret key. The signature is sent in the X-WAFBP-Signature header as "sha256=<hex_signature>".', 'whatsapp-form-builder-pro' ); ?></p>
        
        <h3><?php echo esc_html__( 'Verifying Webhook Signatures', 'whatsapp-form-builder-pro' ); ?></h3>
        <p><?php echo esc_html__( 'To verify webhook signatures in your application:', 'whatsapp-form-builder-pro' ); ?></p>
        
        <h4><?php echo esc_html__( 'PHP Example:', 'whatsapp-form-builder-pro' ); ?></h4>
        <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;">
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WAFBP_SIGNATURE'] ?? '';
$secret = 'your_webhook_secret';

$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected_signature, $signature)) {
    // Signature is valid
    $data = json_decode($payload, true);
    // Process webhook...
} else {
    // Invalid signature
    http_response_code(401);
}
        </pre>

        <h4><?php echo esc_html__( 'Node.js Example:', 'whatsapp-form-builder-pro' ); ?></h4>
        <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;">
const crypto = require('crypto');

app.post('/webhook', (req, res) => {
    const signature = req.headers['x-wafbp-signature'] || '';
    const secret = 'your_webhook_secret';
    const payload = JSON.stringify(req.body);
    
    const expectedSignature = 'sha256=' + 
        crypto.createHmac('sha256', secret)
              .update(payload)
              .digest('hex');
    
    if (crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expectedSignature)
    )) {
        // Signature is valid
        // Process webhook...
        res.status(200).send('OK');
    } else {
        // Invalid signature
        res.status(401).send('Unauthorized');
    }
});
        </pre>

        <h2><?php echo esc_html__( 'Webhook Payload Format', 'whatsapp-form-builder-pro' ); ?></h2>
        <p><?php echo esc_html__( 'Webhooks send JSON payloads in the following format:', 'whatsapp-form-builder-pro' ); ?></p>
        <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;">
{
    "lead_id": 123,
    "form_id": 1,
    "data": {
        "name": "John Doe",
        "phone": "1234567890",
        "message": "Hello, I'm interested in your services"
    },
    "page_url": "https://example.com/contact",
    "timestamp": "2024-01-01 12:00:00"
}
        </pre>
    </div>
    <?php
}
