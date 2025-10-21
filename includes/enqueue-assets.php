<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register and enqueue admin and frontend assets.
 */
function wafbp_register_assets() {
    // Admin
    wp_register_style( 'wafbp-admin-style', WAFBP_URL . 'assets/css/admin-style.css', array(), WAFBP_VERSION );
    wp_register_script( 'wafbp-admin-script', WAFBP_URL . 'assets/js/admin-script.js', array( 'jquery', 'wp-color-picker' ), WAFBP_VERSION, true );

    // Frontend
    wp_register_style( 'wafbp-frontend-style', WAFBP_URL . 'assets/css/style.css', array(), WAFBP_VERSION );
    wp_register_script( 'wafbp-frontend', WAFBP_URL . 'assets/js/frontend.js', array(), WAFBP_VERSION, true );
}
add_action( 'init', 'wafbp_register_assets' );

/**
 * Enqueue admin assets on plugin pages only.
 */
function wafbp_admin_enqueue( $hook ) {
    // Basic check: only on plugin pages under 'wafbp'
    if ( false === strpos( $hook, 'wafbp' ) && false === strpos( $hook, 'toplevel_page_wafbp-forms' ) ) {
        return;
    }
    wp_enqueue_style( 'wafbp-admin-style' );
    wp_enqueue_script( 'wafbp-admin-script' );
    wp_enqueue_style( 'wp-color-picker' );

    // Pass localized strings
    wp_localize_script( 'wafbp-admin-script', 'wafbpAdmin', array(
        'copy_message' => __( 'Shortcode copied to clipboard', 'whatsapp-form-builder-pro' ),
        'confirm_delete' => __( 'Are you sure you want to delete this form?', 'whatsapp-form-builder-pro' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'wafbp_admin_enqueue' );

/**
 * Enqueue frontend style if shortcode used - handled inside shortcode via enqueue.
 */