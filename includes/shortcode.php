<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shortcode output for [whatsapp_form_builder id="X"]
 */

function wafbp_shortcode_output( $atts ) {
    $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'whatsapp_form_builder' );
    $form_id = absint( $atts['id'] );

    if ( empty( $form_id ) ) {
        return '<p style="color: red;">' . esc_html__( 'Error: WhatsApp Form ID is missing from shortcode. Please use [whatsapp_form_builder id="YOUR_FORM_ID"].', 'whatsapp-form-builder-pro' ) . '</p>';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wafbp_forms';
    $form_data = $wpdb->get_row( $wpdb->prepare( "SELECT settings FROM $table_name WHERE id = %d", $form_id ) );

    if ( ! $form_data ) {
        return '<p style="color: red;">' . sprintf( esc_html__( 'Error: WhatsApp Form with ID "%d" not found.', 'whatsapp-form-builder-pro' ), $form_id ) . '</p>';
    }

    $saved_settings = json_decode( $form_data->settings, true );
    $options = array_merge( wafbp_get_default_form_settings(), (array) $saved_settings );

    // sanitize/normalize
    $wa_phone = isset( $options['wa_phone'] ) ? preg_replace( '/\D+/', '', $options['wa_phone'] ) : '';
    $form_title = isset( $options['form_title'] ) ? $options['form_title'] : '';
    $button_text = isset( $options['button_text'] ) ? $options['button_text'] : __( 'Send via WhatsApp', 'whatsapp-form-builder-pro' );

    $form_max_width = isset( $options['form_max_width'] ) ? absint( $options['form_max_width'] ) : 400;
    $font_family = isset( $options['font_family'] ) ? sanitize_text_field( $options['font_family'] ) : '';
    $font_size = isset( $options['font_size'] ) ? absint( $options['font_size'] ) : 14;
    $border_radius = isset( $options['border_radius'] ) ? absint( $options['border_radius'] ) : 6;

    // colours
    $form_bg_color = isset( $options['form_bg_color'] ) ? sanitize_hex_color( $options['form_bg_color'] ) : '#ffffff';
    $form_border_color = isset( $options['form_border_color'] ) ? sanitize_hex_color( $options['form_border_color'] ) : '#e5e5e5';
    $input_text_color = isset( $options['input_text_color'] ) ? sanitize_hex_color( $options['input_text_color'] ) : '#333333';
    $input_bg_color = isset( $options['input_bg_color'] ) ? sanitize_hex_color( $options['input_bg_color'] ) : '#ffffff';
    $btn_bg_color = isset( $options['btn_bg_color'] ) ? sanitize_hex_color( $options['btn_bg_color'] ) : '#25D366';
    $btn_text_color = isset( $options['btn_text_color'] ) ? sanitize_hex_color( $options['btn_text_color'] ) : '#ffffff';
    $btn_hover_bg_color = isset( $options['btn_hover_bg_color'] ) ? sanitize_hex_color( $options['btn_hover_bg_color'] ) : $btn_bg_color;
    $btn_hover_text_color = isset( $options['btn_hover_text_color'] ) ? sanitize_hex_color( $options['btn_hover_text_color'] ) : $btn_text_color;

    $is_rtl = is_rtl();
    $dir_attribute = $is_rtl ? 'dir="rtl"' : 'dir="ltr"';

    // Enqueue frontend assets and pass the options to the JS initializer
    wp_enqueue_style( 'wafbp-frontend-style' );
    wp_enqueue_script( 'wafbp-frontend' );

    // Build a minimal options array to pass to JS (sanitized)
    $js_options = array(
        'form_id' => $form_id,
        'wa_phone' => $wa_phone,
        'button_text' => wp_strip_all_tags( $button_text ),
        'fields' => array(
            'name' => array( 'show' => $options['field_name_show'] === 'yes', 'required' => $options['field_name_required'] === 'yes', 'placeholder' => $options['field_name_text'] ),
            'phone' => array( 'show' => $options['field_phone_show'] === 'yes', 'required' => $options['field_phone_required'] === 'yes', 'placeholder' => $options['field_phone_text'] ),
            'city' => array( 'show' => $options['field_city_show'] === 'yes', 'required' => $options['field_city_required'] === 'yes', 'placeholder' => $options['field_city_text'] ),
            'subject' => array( 'show' => $options['field_subject_show'] === 'yes', 'required' => $options['field_subject_required'] === 'yes', 'placeholder' => $options['field_subject_text'] ),
            'message' => array( 'show' => $options['field_message_show'] === 'yes', 'required' => $options['field_message_required'] === 'yes', 'placeholder' => $options['field_message_text'] ),
        ),
        'invalid_number_msg' => __( 'WhatsApp number is not configured or invalid. Please contact the website administrator.', 'whatsapp-form-builder-pro' ),
    );

    // Pass options to JS for this single form call
    $inline = 'if (typeof wafbp_init_form === "function") wafbp_init_form(' . json_encode( $form_id ) . ', ' . wp_json_encode( $js_options ) . ');';
    wp_add_inline_script( 'wafbp-frontend', $inline, 'after' );

    // Optionally enqueue Google Font if it exists in whitelist
    $google_fonts = wafbp_get_google_fonts();
    $enqueue_font = false;
    if ( $font_family && array_key_exists( $font_family, $google_fonts ) && strpos( $font_family, '(Web Safe)' ) === false ) {
        $enqueue_font = true;
        wp_enqueue_style( 'wafbp-google-font-' . sanitize_title( $font_family ), 'https://fonts.googleapis.com/css2?family=' . rawurlencode( $font_family ) . ':wght@400;700&display=swap', array(), null );
        $font_family_css = "'" . esc_attr( $font_family ) . "', sans-serif";
    } else {
        $font_family_css = esc_attr( $font_family );
    }

    // Output markup (values escaped)
    ob_start();
    ?>
    <div class="wafbp-form-wrapper form-id-<?php echo esc_attr( $form_id ); ?>" <?php echo $dir_attribute; ?> style="max-width: <?php echo esc_attr( $form_max_width ); ?>px; background-color: <?php echo esc_attr( $form_bg_color ); ?>; border: 1px solid <?php echo esc_attr( $form_border_color ); ?>; border-radius: <?php echo esc_attr( $border_radius ); ?>px; font-family: <?php echo esc_attr( $font_family_css ); ?>; font-size: <?php echo esc_attr( $font_size ); ?>px; box-sizing: border-box; padding: 20px; margin: 20px auto;">
        <?php if ( ! empty( $form_title ) ) : ?>
            <h2 style="text-align:center; color: <?php echo esc_attr( $input_text_color ); ?>; margin-top:0;"><?php echo esc_html( $form_title ); ?></h2>
        <?php endif; ?>

        <form class="wafbp-form" id="wafbpForm-<?php echo esc_attr( $form_id ); ?>">
            <?php if ( $options['field_name_show'] === 'yes' ) : ?>
                <div class="wafbp-field-group">
                    <input type="text" name="wafbp_name" placeholder="<?php echo esc_attr( $options['field_name_text'] ); ?>" <?php echo $options['field_name_required'] === 'yes' ? 'required' : ''; ?> style="width:100%; padding:10px; box-sizing:border-box; margin-bottom:10px; color:<?php echo esc_attr( $input_text_color ); ?>; background:<?php echo esc_attr( $input_bg_color ); ?>; border-radius:<?php echo esc_attr( $border_radius ); ?>px;">
                </div>
            <?php endif; ?>

            <?php if ( $options['field_phone_show'] === 'yes' ) : ?>
                <div class="wafbp-field-group">
                    <input type="tel" name="wafbp_phone" placeholder="<?php echo esc_attr( $options['field_phone_text'] ); ?>" <?php echo $options['field_phone_required'] === 'yes' ? 'required' : ''; ?> style="width:100%; padding:10px; box-sizing:border-box; margin-bottom:10px; color:<?php echo esc_attr( $input_text_color ); ?>; background:<?php echo esc_attr( $input_bg_color ); ?>; border-radius:<?php echo esc_attr( $border_radius ); ?>px;">
                </div>
            <?php endif; ?>

            <?php if ( $options['field_city_show'] === 'yes' ) : ?>
                <div class="wafbp-field-group">
                    <input type="text" name="wafbp_city" placeholder="<?php echo esc_attr( $options['field_city_text'] ); ?>" <?php echo $options['field_city_required'] === 'yes' ? 'required' : ''; ?> style="width:100%; padding:10px; box-sizing:border-box; margin-bottom:10px; color:<?php echo esc_attr( $input_text_color ); ?>; background:<?php echo esc_attr( $input_bg_color ); ?>; border-radius:<?php echo esc_attr( $border_radius ); ?>px;">
                </div>
            <?php endif; ?>

            <?php if ( $options['field_subject_show'] === 'yes' ) : ?>
                <div class="wafbp-field-group">
                    <input type="text" name="wafbp_subject" placeholder="<?php echo esc_attr( $options['field_subject_text'] ); ?>" <?php echo $options['field_subject_required'] === 'yes' ? 'required' : ''; ?> style="width:100%; padding:10px; box-sizing:border-box; margin-bottom:10px; color:<?php echo esc_attr( $input_text_color ); ?>; background:<?php echo esc_attr( $input_bg_color ); ?>; border-radius:<?php echo esc_attr( $border_radius ); ?>px;">
                </div>
            <?php endif; ?>

            <?php if ( $options['field_message_show'] === 'yes' ) : ?>
                <div class="wafbp-field-group">
                    <textarea name="wafbp_message" placeholder="<?php echo esc_attr( $options['field_message_text'] ); ?>" <?php echo $options['field_message_required'] === 'yes' ? 'required' : ''; ?> style="width:100%; padding:10px; box-sizing:border-box; margin-bottom:10px; color:<?php echo esc_attr( $input_text_color ); ?>; background:<?php echo esc_attr( $input_bg_color ); ?>; border-radius:<?php echo esc_attr( $border_radius ); ?>px; min-height:80px;"></textarea>
                </div>
            <?php endif; ?>

            <button type="submit" style="width:100%; padding:12px; background:<?php echo esc_attr( $btn_bg_color ); ?>; color:<?php echo esc_attr( $btn_text_color ); ?>; border:none; border-radius:<?php echo esc_attr( $border_radius ); ?>px; font-weight:600;"><?php echo esc_html( $button_text ); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'whatsapp_form_builder', 'wafbp_shortcode_output' );