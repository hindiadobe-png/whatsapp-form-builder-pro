<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Form editor page
 */

function wafbp_form_editor_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'whatsapp-form-builder-pro' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wafbp_forms';
    $form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
    $is_editing = $form_id > 0;

    $form_name = '';
    $form_settings = wafbp_get_default_form_settings();

    if ( $is_editing ) {
        $existing_form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $form_id ) );
        if ( $existing_form ) {
            $form_name = $existing_form->form_name;
            $saved_settings = json_decode( $existing_form->settings, true );
            $form_settings = array_merge( wafbp_get_default_form_settings(), (array) $saved_settings );
        } else {
            $is_editing = false;
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Form not found. Creating a new form.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        }
    }

    // Handle POST actions
    if ( isset( $_POST['wafbp_save_form'] ) ) {
        if ( ! check_admin_referer( 'wafbp_save_form_nonce' ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            return;
        }

        $raw_post = wp_unslash( $_POST );
        $form_name = isset( $raw_post['wafbp_form_name'] ) ? sanitize_text_field( $raw_post['wafbp_form_name'] ) : '';
        $submitted_settings = wafbp_sanitize_form_settings( $raw_post );

        $data = array(
            'form_name' => $form_name,
            'settings'  => wp_json_encode( $submitted_settings ),
        );

        if ( $is_editing ) {
            $updated = $wpdb->update( $table_name, $data, array( 'id' => $form_id ), array( '%s', '%s' ), array( '%d' ) );
            if ( $updated !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Form updated successfully!', 'whatsapp-form-builder-pro' ) . '</strong></p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error updating form or no changes made.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            }
            $form_settings = $submitted_settings;
        } else {
            $inserted = $wpdb->insert( $table_name, $data, array( '%s', '%s' ) );
            $form_id = $wpdb->insert_id;
            if ( $form_id ) {
                $redirect_url = add_query_arg(
                    array(
                        'page'    => 'wafbp-forms',
                        'status'  => 'created',
                        'form_id' => $form_id,
                    ),
                    admin_url( 'admin.php' )
                );
                wp_safe_redirect( $redirect_url );
                exit;
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error creating form.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            }
        }
    } elseif ( isset( $_POST['wafbp_reset_form_settings'] ) ) {
        if ( ! check_admin_referer( 'wafbp_save_form_nonce' ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            return;
        }
        $form_settings = wafbp_get_default_form_settings();
        if ( $is_editing ) {
            $data = array(
                'form_name' => $form_name,
                'settings'  => wp_json_encode( $form_settings ),
            );
            $wpdb->update( $table_name, $data, array( 'id' => $form_id ), array( '%s', '%s' ), array( '%d' ) );
            echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Form settings reset to default!', 'whatsapp-form-builder-pro' ) . '</strong></p></div>';
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Form settings reset to default for this new form.', 'whatsapp-form-builder-pro' ) . '</strong></p></div>';
        }
    }

    $font_families = wafbp_get_google_fonts();

    ?>
    <div class="wrap">
        <h1><?php echo $is_editing ? esc_html__( 'Edit WhatsApp Form', 'whatsapp-form-builder-pro' ) : esc_html__( 'Add New WhatsApp Form', 'whatsapp-form-builder-pro' ); ?></h1>

        <div class="wafbp-admin-notice">
            <p><strong><?php echo esc_html__( 'Important Note:', 'whatsapp-form-builder-pro' ); ?></strong> <?php echo esc_html__( 'For the WhatsApp chat to open correctly, please ensure the phone number is entered in the full international format (e.g., 962791234567 for Jordan, 12025550100 for USA) without any plus (+) signs or leading zeros.', 'whatsapp-form-builder-pro' ); ?></p>
        </div>

        <form method="post" id="wafbp-form-editor">
            <?php wp_nonce_field( 'wafbp_save_form_nonce' ); ?>
            <?php if ( $is_editing ) : ?>
                <p><strong><?php echo esc_html__( 'Shortcode for this form:', 'whatsapp-form-builder-pro' ); ?></strong> <code>[whatsapp_form_builder id="<?php echo esc_attr( $form_id ); ?>"]</code> <button type="button" class="button-secondary wafbp-copy-shortcode" data-shortcode="<?php echo esc_attr( '[whatsapp_form_builder id="' . $form_id . '"]' ); ?>"><?php echo esc_html__( 'Copy Shortcode', 'whatsapp-form-builder-pro' ); ?></button></p>
            <?php endif; ?>

            <h2 class="title"><?php echo esc_html__( 'Form Identification', 'whatsapp-form-builder-pro' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wafbp_form_name"><?php echo esc_html__( 'Form Name', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td>
                        <input type="text" name="wafbp_form_name" id="wafbp_form_name" value="<?php echo esc_attr( $form_name ); ?>" class="regular-text" required placeholder="<?php echo esc_attr__( 'e.g., Main Contact Form', 'whatsapp-form-builder-pro' ); ?>">
                        <p class="description"><?php echo esc_html__( 'This name is for your internal reference only.', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php echo esc_html__( 'General Settings', 'whatsapp-form-builder-pro' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wa_phone"><?php echo esc_html__( 'WhatsApp Phone Number', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td>
                        <input type="text" name="wa_phone" value="<?php echo esc_attr( $form_settings['wa_phone'] ); ?>" class="regular-text" placeholder="e.g., 9627xxxxxxxxx">
                        <p class="description"><?php echo esc_html__( 'Enter the WhatsApp phone number in full international format (digits only).', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="form_title"><?php echo esc_html__( 'Form Title', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="form_title" value="<?php echo esc_attr( $form_settings['form_title'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="button_text"><?php echo esc_html__( 'Button Text', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="button_text" value="<?php echo esc_attr( $form_settings['button_text'] ); ?>" class="regular-text"></td>
                </tr>
            </table>

            <h2 class="title"><?php echo esc_html__( 'Styling Options', 'whatsapp-form-builder-pro' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="form_max_width"><?php echo esc_html__( 'Form Max Width (px)', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="number" name="form_max_width" value="<?php echo esc_attr( $form_settings['form_max_width'] ); ?>" class="small-text" min="200" max="1200"> px</td>
                </tr>
                <tr>
                    <th scope="row"><label for="font_family"><?php echo esc_html__( 'Font Family', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td>
                        <select name="font_family" id="font_family">
                            <?php foreach ( $font_families as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $form_settings['font_family'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html__( 'Google Fonts will be automatically enqueued. "Web Safe" fonts are standard browser fonts.', 'whatsapp-form-builder-pro' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="font_size"><?php echo esc_html__( 'Base Font Size (px)', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="number" name="font_size" value="<?php echo esc_attr( $form_settings['font_size'] ); ?>" class="small-text" min="10" max="24"> px</td>
                </tr>
                <tr>
                    <th scope="row"><label for="border_radius"><?php echo esc_html__( 'Border Radius (px)', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="number" name="border_radius" value="<?php echo esc_attr( $form_settings['border_radius'] ); ?>" class="small-text" min="0" max="50"> px</td>
                </tr>
                <tr>
                    <th scope="row"><label for="form_bg_color"><?php echo esc_html__( 'Form Background Color', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="form_bg_color" value="<?php echo esc_attr( $form_settings['form_bg_color'] ); ?>" class="wafbp-color-field"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="form_border_color"><?php echo esc_html__( 'Form Border Color', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="form_border_color" value="<?php echo esc_attr( $form_settings['form_border_color'] ); ?>" class="wafbp-color-field"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="input_text_color"><?php echo esc_html__( 'Input Text & Title Color', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="input_text_color" value="<?php echo esc_attr( $form_settings['input_text_color'] ); ?>" class="wafbp-color-field"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="input_bg_color"><?php echo esc_html__( 'Input Background Color', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="input_bg_color" value="<?php echo esc_attr( $form_settings['input_bg_color'] ); ?>" class="wafbp-color-field"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="btn_bg_color"><?php echo esc_html__( 'Button Background Color', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="btn_bg_color" value="<?php echo esc_attr( $form_settings['btn_bg_color'] ); ?>" class="wafbp-color-field"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="btn_text_color"><?php echo esc_html__( 'Button Text Color', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="btn_text_color" value="<?php echo esc_attr( $form_settings['btn_text_color'] ); ?>" class="wafbp-color-field"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="btn_hover_bg_color"><?php echo esc_html__( 'Button Hover Background Color', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="btn_hover_bg_color" value="<?php echo esc_attr( $form_settings['btn_hover_bg_color'] ); ?>" class="wafbp-color-field"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="btn_hover_text_color"><?php echo esc_html__( 'Button Hover Text Color', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="btn_hover_text_color" value="<?php echo esc_attr( $form_settings['btn_hover_text_color'] ); ?>" class="wafbp-color-field"></td>
                </tr>
            </table>

            <h2 class="title"><?php echo esc_html__( 'Field Settings', 'whatsapp-form-builder-pro' ); ?></h2>
            <p class="description"><?php echo esc_html__( 'Customize each form field: placeholder text, visibility, and if it\'s required.', 'whatsapp-form-builder-pro' ); ?></p>

            <!-- Name Field -->
            <h3><?php echo esc_html__( 'Name Field', 'whatsapp-form-builder-pro' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="field_name_text"><?php echo esc_html__( 'Placeholder Text', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="field_name_text" value="<?php echo esc_attr( $form_settings['field_name_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Visibility', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_name_show" value="yes" <?php checked( $form_settings['field_name_show'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Show Field', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Required', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_name_required" value="yes" <?php checked( $form_settings['field_name_required'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Make Field Required', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <!-- Phone Field -->
            <h3><?php echo esc_html__( 'Phone Number Field', 'whatsapp-form-builder-pro' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="field_phone_text"><?php echo esc_html__( 'Placeholder Text', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="field_phone_text" value="<?php echo esc_attr( $form_settings['field_phone_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Visibility', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_phone_show" value="yes" <?php checked( $form_settings['field_phone_show'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Show Field', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Required', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_phone_required" value="yes" <?php checked( $form_settings['field_phone_required'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Make Field Required', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <!-- City, Subject, Message fields follow same pattern -->
            <h3><?php echo esc_html__( 'City Field', 'whatsapp-form-builder-pro' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="field_city_text"><?php echo esc_html__( 'Placeholder Text', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="field_city_text" value="<?php echo esc_attr( $form_settings['field_city_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Visibility', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_city_show" value="yes" <?php checked( $form_settings['field_city_show'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Show Field', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Required', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_city_required" value="yes" <?php checked( $form_settings['field_city_required'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Make Field Required', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h3><?php echo esc_html__( 'Subject Field', 'whatsapp-form-builder-pro' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="field_subject_text"><?php echo esc_html__( 'Placeholder Text', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="field_subject_text" value="<?php echo esc_attr( $form_settings['field_subject_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Visibility', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_subject_show" value="yes" <?php checked( $form_settings['field_subject_show'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Show Field', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Required', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_subject_required" value="yes" <?php checked( $form_settings['field_subject_required'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Make Field Required', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h3><?php echo esc_html__( 'Message Field', 'whatsapp-form-builder-pro' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="field_message_text"><?php echo esc_html__( 'Placeholder Text', 'whatsapp-form-builder-pro' ); ?></label></th>
                    <td><input type="text" name="field_message_text" value="<?php echo esc_attr( $form_settings['field_message_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Visibility', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_message_show" value="yes" <?php checked( $form_settings['field_message_show'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Show Field', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Required', 'whatsapp-form-builder-pro' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="field_message_required" value="yes" <?php checked( $form_settings['field_message_required'], 'yes' ); ?>>
                            <?php echo esc_html__( 'Make Field Required', 'whatsapp-form-builder-pro' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p>
                <input type="submit" name="wafbp_save_form" class="button button-primary" value="<?php echo esc_attr__( 'Save Form', 'whatsapp-form-builder-pro' ); ?>">
                <input type="submit" name="wafbp_reset_form_settings" class="button button-secondary" value="<?php echo esc_attr__( 'Reset to Default', 'whatsapp-form-builder-pro' ); ?>" onclick="return confirm('<?php echo esc_js( __( "Are you sure you want to reset this form's settings to their default values?", 'whatsapp-form-builder-pro' ) ); ?>');">
            </p>
        </form>
    </div>
    <?php
}