<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin menu and forms list
 */

function wafbp_add_admin_menu() {
    add_menu_page(
        __( 'WhatsApp Forms', 'whatsapp-form-builder-pro' ),
        __( 'WhatsApp Forms', 'whatsapp-form-builder-pro' ),
        'manage_options',
        'wafbp-forms',
        'wafbp_forms_list_page',
        'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.25 4.74 1.25 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.72 14.59l-1.42 1.41c-.19.19-.4.29-.64.29-.24 0|.46.09.64.29.35.35.7.7 1.05 1.05.19.19.29.4.29.64 0 .24-.09.46.29.64l-.99.99c-.19.19-.29.4-.29.64s.09.46.29.64c.95.95 2.05 1.86 3.19 2.9.19.19.4.29.64.29.24 0 .46-.09.64-.29l.99-.99c.19-.19.29-.4.29-.64s-.09-.46-.29-.64c-.35-.35-.7-.7-1.05-1.05-.19-.19-.4-.29-.64-.29s-.46.09-.64.29l-1.41 1.42z"/></svg>' ),
        60
    );

    add_submenu_page(
        'wafbp-forms',
        __( 'Add New Form', 'whatsapp-form-builder-pro' ),
        __( 'Add New', 'whatsapp-form-builder-pro' ),
        'manage_options',
        'wafbp-new-form',
        'wafbp_form_editor_page'
    );
}
add_action( 'admin_menu', 'wafbp_add_admin_menu' );

function wafbp_forms_list_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'whatsapp-form-builder-pro' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wafbp_forms';

    // Handle delete action
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['form_id'] ) && is_numeric( $_GET['form_id'] ) ) {
        $form_id = absint( $_GET['form_id'] );
        if ( check_admin_referer( 'delete_form_' . $form_id ) ) {
            if ( $wpdb->delete( $table_name, array( 'id' => $form_id ), array( '%d' ) ) !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Form deleted successfully.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error deleting form.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        }
    }

    // Success notice after creation
    if ( isset( $_GET['status'] ) && $_GET['status'] === 'created' && isset( $_GET['form_id'] ) ) {
        $created_form_id = absint( $_GET['form_id'] );
        $shortcode_to_display = '[whatsapp_form_builder id="' . esc_attr( $created_form_id ) . '"]';
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Form created successfully!', 'whatsapp-form-builder-pro' ) . '</strong> ' . esc_html__( 'Use this shortcode:', 'whatsapp-form-builder-pro' ) . ' <code>' . esc_html( $shortcode_to_display ) . '</code> <button type="button" class="button-secondary wafbp-copy-shortcode-btn" data-shortcode="' . esc_attr( $shortcode_to_display ) . '">' . esc_html__( 'Copy', 'whatsapp-form-builder-pro' ) . '</button></p></div>';
    }

    $forms = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'WhatsApp Forms', 'whatsapp-form-builder-pro' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-new-form' ) ); ?>" class="page-title-action"><?php echo esc_html__( 'Add New', 'whatsapp-form-builder-pro' ); ?></a></h1>

        <?php if ( empty( $forms ) ) : ?>
            <p><?php echo esc_html__( 'No WhatsApp forms found. Click "Add New" to create your first form!', 'whatsapp-form-builder-pro' ); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__( 'Form Name', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Shortcode', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'WhatsApp Number', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Created On', 'whatsapp-form-builder-pro' ); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php echo esc_html__( 'Actions', 'whatsapp-form-builder-pro' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $forms as $form ) : 
                        $settings = json_decode( $form->settings, true );
                        $shortcode = '[whatsapp_form_builder id="' . esc_attr( $form->id ) . '"]';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $form->form_name ); ?></strong></td>
                            <td>
                                <code><?php echo esc_html( $shortcode ); ?></code>
                                <button type="button" class="button-secondary wafbp-copy-shortcode-btn" data-shortcode="<?php echo esc_attr( $shortcode ); ?>"><?php echo esc_html__( 'Copy', 'whatsapp-form-builder-pro' ); ?></button>
                            </td>
                            <td><?php echo esc_html( $settings['wa_phone'] ?? __( 'N/A', 'whatsapp-form-builder-pro' ) ); ?></td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $form->created_at ) ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-new-form&form_id=' . $form->id ) ); ?>" class="button-primary button-small"><?php echo esc_html__( 'Edit', 'whatsapp-form-builder-pro' ); ?></a>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wafbp-forms&action=delete&form_id=' . $form->id ), 'delete_form_' . $form->id ) ); ?>" class="button button-danger button-small wafbp-delete-form-btn"><?php echo esc_html__( 'Delete', 'whatsapp-form-builder-pro' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}