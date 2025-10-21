<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Settings page for leads management
 */

/**
 * Register settings submenu
 */
function wafbp_register_settings_menu() {
    add_submenu_page(
        'wafbp-forms',
        __( 'Settings', 'whatsapp-form-builder-pro' ),
        __( 'Settings', 'whatsapp-form-builder-pro' ),
        'manage_options',
        'wafbp-settings',
        'wafbp_settings_page'
    );
}
add_action( 'admin_menu', 'wafbp_register_settings_menu' );

/**
 * Get default settings
 */
function wafbp_get_default_settings() {
    return array(
        'save_leads' => false,
        'save_ip' => false,
        'opt_in_label' => __( 'I agree to save my data for follow up.', 'whatsapp-form-builder-pro' ),
        'retention_days' => 0,
        'delete_data_on_uninstall' => false,
    );
}

/**
 * Get current settings (merged with defaults)
 */
function wafbp_get_settings() {
    $defaults = wafbp_get_default_settings();
    $saved = get_option( 'wafbp_settings', array() );
    return wp_parse_args( $saved, $defaults );
}

/**
 * Settings page output
 */
function wafbp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'whatsapp-form-builder-pro' ) );
    }

    // Handle form submission
    if ( isset( $_POST['wafbp_save_settings'] ) ) {
        if ( ! check_admin_referer( 'wafbp_settings_nonce' ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        } else {
            $settings = array(
                'save_leads' => isset( $_POST['save_leads'] ) ? true : false,
                'save_ip' => isset( $_POST['save_ip'] ) ? true : false,
                'opt_in_label' => isset( $_POST['opt_in_label'] ) ? sanitize_text_field( wp_unslash( $_POST['opt_in_label'] ) ) : wafbp_get_default_settings()['opt_in_label'],
                'retention_days' => isset( $_POST['retention_days'] ) ? absint( $_POST['retention_days'] ) : 0,
                'delete_data_on_uninstall' => isset( $_POST['delete_data_on_uninstall'] ) ? true : false,
            );
            update_option( 'wafbp_settings', $settings );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        }
    }

    $settings = wafbp_get_settings();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'WhatsApp Form Builder Pro - Settings', 'whatsapp-form-builder-pro' ); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field( 'wafbp_settings_nonce' ); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="save_leads"><?php echo esc_html__( 'Save Leads', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="save_leads" id="save_leads" value="1" <?php checked( $settings['save_leads'], true ); ?>>
                        <p class="description"><?php echo esc_html__( 'Enable saving form submissions to the database.', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="save_ip"><?php echo esc_html__( 'Save IP Address', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="save_ip" id="save_ip" value="1" <?php checked( $settings['save_ip'], true ); ?>>
                        <p class="description"><?php echo esc_html__( 'Save submitter IP address (disabled by default for privacy).', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="opt_in_label"><?php echo esc_html__( 'Opt-in Label', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="opt_in_label" id="opt_in_label" value="<?php echo esc_attr( $settings['opt_in_label'] ); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__( 'Text shown next to the consent checkbox (when Save Leads is enabled).', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="retention_days"><?php echo esc_html__( 'Data Retention (Days)', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="retention_days" id="retention_days" value="<?php echo esc_attr( $settings['retention_days'] ); ?>" min="0" class="small-text">
                        <p class="description"><?php echo esc_html__( 'Automatically delete leads older than this many days. Set to 0 to keep indefinitely.', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="delete_data_on_uninstall"><?php echo esc_html__( 'Delete Data on Uninstall', 'whatsapp-form-builder-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="delete_data_on_uninstall" id="delete_data_on_uninstall" value="1" <?php checked( $settings['delete_data_on_uninstall'], true ); ?>>
                        <p class="description"><?php echo esc_html__( 'Remove all leads data when the plugin is uninstalled (cannot be undone).', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="wafbp_save_settings" class="button button-primary" value="<?php echo esc_attr__( 'Save Settings', 'whatsapp-form-builder-pro' ); ?>">
            </p>
        </form>
    </div>
    <?php
}
