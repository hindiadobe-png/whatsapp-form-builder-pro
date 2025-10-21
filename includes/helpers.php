<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return default form settings array.
 */
function wafbp_get_default_form_settings() {
    return array(
        'wa_phone'             => '',
        'form_title'           => '',
        'button_text'          => __( 'Send via WhatsApp', 'whatsapp-form-builder-pro' ),
        'form_max_width'       => 400,
        'font_family'          => 'Arial (Web Safe)',
        'font_size'            => 14,
        'border_radius'        => 6,
        'form_bg_color'        => '#ffffff',
        'form_border_color'    => '#e5e5e5',
        'input_text_color'     => '#333333',
        'input_bg_color'       => '#ffffff',
        'btn_bg_color'         => '#25D366',
        'btn_text_color'       => '#ffffff',
        'btn_hover_bg_color'   => '#1da851',
        'btn_hover_text_color' => '#ffffff',
        // Fields default visibility/required
        'field_name_show'      => 'yes',
        'field_name_required'  => 'no',
        'field_name_text'      => __( 'Your name', 'whatsapp-form-builder-pro' ),
        'field_phone_show'     => 'yes',
        'field_phone_required' => 'yes',
        'field_phone_text'     => __( 'Phone number', 'whatsapp-form-builder-pro' ),
        'field_city_show'      => 'no',
        'field_city_required'  => 'no',
        'field_city_text'      => __( 'City', 'whatsapp-form-builder-pro' ),
        'field_subject_show'   => 'no',
        'field_subject_required'=> 'no',
        'field_subject_text'   => __( 'Subject', 'whatsapp-form-builder-pro' ),
        'field_message_show'   => 'yes',
        'field_message_required'=> 'yes',
        'field_message_text'   => __( 'Message', 'whatsapp-form-builder-pro' ),
    );
}

/**
 * Sanitize form settings coming from POST.
 * Expect $raw array (wp_unslash($_POST)) or similar.
 */
function wafbp_sanitize_form_settings( $raw ) {
    $defaults = wafbp_get_default_form_settings();
    $out = $defaults;

    // helper to read checkbox yes/no
    $read_yes_no = function( $k ) use ( $raw ) {
        return ( isset( $raw[ $k ] ) && $raw[ $k ] === 'yes' ) ? 'yes' : 'no';
    };

    // sanitize strings
    $out['form_title'] = isset( $raw['form_title'] ) ? sanitize_text_field( $raw['form_title'] ) : $defaults['form_title'];
    $out['button_text'] = isset( $raw['button_text'] ) ? sanitize_text_field( $raw['button_text'] ) : $defaults['button_text'];

    // phone: digits only
    $out['wa_phone'] = isset( $raw['wa_phone'] ) ? preg_replace( '/\D+/', '', $raw['wa_phone'] ) : '';

    // numbers
    $out['form_max_width'] = isset( $raw['form_max_width'] ) ? absint( $raw['form_max_width'] ) : $defaults['form_max_width'];
    $out['font_size'] = isset( $raw['font_size'] ) ? absint( $raw['font_size'] ) : $defaults['font_size'];
    $out['border_radius'] = isset( $raw['border_radius'] ) ? absint( $raw['border_radius'] ) : $defaults['border_radius'];

    // font family (sanitise text only, but template trusts get_google_fonts list)
    $out['font_family'] = isset( $raw['font_family'] ) ? sanitize_text_field( $raw['font_family'] ) : $defaults['font_family'];

    // colors (use sanitize_hex_color for safety)
    $out['form_bg_color'] = isset( $raw['form_bg_color'] ) ? sanitize_hex_color( $raw['form_bg_color'] ) : $defaults['form_bg_color'];
    $out['form_border_color'] = isset( $raw['form_border_color'] ) ? sanitize_hex_color( $raw['form_border_color'] ) : $defaults['form_border_color'];
    $out['input_text_color'] = isset( $raw['input_text_color'] ) ? sanitize_hex_color( $raw['input_text_color'] ) : $defaults['input_text_color'];
    $out['input_bg_color'] = isset( $raw['input_bg_color'] ) ? sanitize_hex_color( $raw['input_bg_color'] ) : $defaults['input_bg_color'];
    $out['btn_bg_color'] = isset( $raw['btn_bg_color'] ) ? sanitize_hex_color( $raw['btn_bg_color'] ) : $defaults['btn_bg_color'];
    $out['btn_text_color'] = isset( $raw['btn_text_color'] ) ? sanitize_hex_color( $raw['btn_text_color'] ) : $defaults['btn_text_color'];
    $out['btn_hover_bg_color'] = isset( $raw['btn_hover_bg_color'] ) ? sanitize_hex_color( $raw['btn_hover_bg_color'] ) : $out['btn_bg_color'];
    $out['btn_hover_text_color'] = isset( $raw['btn_hover_text_color'] ) ? sanitize_hex_color( $raw['btn_hover_text_color'] ) : $out['btn_text_color'];

    // fields: placeholders and checkboxes
    $out['field_name_text'] = isset( $raw['field_name_text'] ) ? sanitize_text_field( $raw['field_name_text'] ) : $defaults['field_name_text'];
    $out['field_name_show'] = $read_yes_no( 'field_name_show' );
    $out['field_name_required'] = $read_yes_no( 'field_name_required' );

    $out['field_phone_text'] = isset( $raw['field_phone_text'] ) ? sanitize_text_field( $raw['field_phone_text'] ) : $defaults['field_phone_text'];
    $out['field_phone_show'] = $read_yes_no( 'field_phone_show' );
    $out['field_phone_required'] = $read_yes_no( 'field_phone_required' );

    $out['field_city_text'] = isset( $raw['field_city_text'] ) ? sanitize_text_field( $raw['field_city_text'] ) : $defaults['field_city_text'];
    $out['field_city_show'] = $read_yes_no( 'field_city_show' );
    $out['field_city_required'] = $read_yes_no( 'field_city_required' );

    $out['field_subject_text'] = isset( $raw['field_subject_text'] ) ? sanitize_text_field( $raw['field_subject_text'] ) : $defaults['field_subject_text'];
    $out['field_subject_show'] = $read_yes_no( 'field_subject_show' );
    $out['field_subject_required'] = $read_yes_no( 'field_subject_required' );

    $out['field_message_text'] = isset( $raw['field_message_text'] ) ? sanitize_text_field( $raw['field_message_text'] ) : $defaults['field_message_text'];
    $out['field_message_show'] = $read_yes_no( 'field_message_show' );
    $out['field_message_required'] = $read_yes_no( 'field_message_required' );

    return $out;
}

/**
 * Return a whitelist of Google fonts + web-safe labels.
 */
function wafbp_get_google_fonts() {
    // minimal sample; you can expand
    return array(
        'Arial (Web Safe)' => 'Arial (Web Safe)',
        'Helvetica (Web Safe)' => 'Helvetica (Web Safe)',
        'Roboto' => 'Roboto',
        'Open Sans' => 'Open Sans',
        'Noto Sans' => 'Noto Sans',
    );
}

/**
 * Convert hex color to rgb string 'r,g,b' safely.
 */
function wafbp_hex_to_rgb( $hex ) {
    $hex = sanitize_hex_color_no_hash( $hex );
    if ( ! $hex ) {
        return '0,0,0';
    }
    if ( strlen( $hex ) === 3 ) {
        $r = hexdec( str_repeat( substr( $hex, 0, 1 ), 2 ) );
        $g = hexdec( str_repeat( substr( $hex, 1, 1 ), 2 ) );
        $b = hexdec( str_repeat( substr( $hex, 2, 1 ), 2 ) );
    } else {
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
    }
    return sprintf( '%d,%d,%d', $r, $g, $b );
}