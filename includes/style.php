<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Optional loader for frontend styles.
 * This file acts as a compatibility wrapper so the plugin tree contains includes/style.php
 * It ensures the frontend CSS is enqueued via the existing enqueue-assets registration.
 */

function wafbp_enqueue_frontend_style_wrapper() {
    // If the frontend style is not registered by enqueue-assets.php, register a fallback.
    if ( ! wp_style_is( 'wafbp-frontend-style', 'registered' ) ) {
        wp_register_style(
            'wafbp-frontend-style',
            WAFBP_URL . 'assets/css/style.css',
            array(),
            WAFBP_VERSION
        );
    }
    wp_enqueue_style( 'wafbp-frontend-style' );
}
add_action( 'wp_enqueue_scripts', 'wafbp_enqueue_frontend_style_wrapper' );