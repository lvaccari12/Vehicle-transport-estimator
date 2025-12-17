<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function vte_register_settings() {
    register_setting( 'vte_settings_group', 'vte_settings', 'vte_sanitize_settings' );

    add_settings_section(
        'vte_main_section',
        __( 'Transport Estimator Settings', 'vehicle-transport-estimator' ),
        function() {
            echo '<p>' . esc_html__( 'Configure phone number, next-step URL, and webhook integration.', 'vehicle-transport-estimator' ) . '</p>';
        },
        'vte_settings'
    );

    add_settings_field(
        'vte_phone',
        __( 'Phone Number', 'vehicle-transport-estimator' ),
        'vte_phone_field_cb',
        'vte_settings',
        'vte_main_section'
    );

    add_settings_field(
        'vte_next_url',
        __( 'Next Step URL', 'vehicle-transport-estimator' ),
        'vte_next_url_field_cb',
        'vte_settings',
        'vte_main_section'
    );

    add_settings_field(
        'vte_webhook_url',
        __( 'Webhook URL', 'vehicle-transport-estimator' ),
        'vte_webhook_url_field_cb',
        'vte_settings',
        'vte_main_section'
    );
}
add_action( 'admin_init', 'vte_register_settings' );

function vte_phone_field_cb() {
    $opts = get_option( 'vte_settings', array() );
    $val = isset( $opts['phone'] ) ? $opts['phone'] : '+18005551234';
    printf( '<input type="text" id="vte_phone" name="vte_settings[phone]" value="%s" class="regular-text">', esc_attr( $val ) );
}

function vte_next_url_field_cb() {
    $opts = get_option( 'vte_settings', array() );
    $val = isset( $opts['next_step_url'] ) ? $opts['next_step_url'] : '/quote/step-2';
    printf( '<input type="url" id="vte_next_url" name="vte_settings[next_step_url]" value="%s" class="regular-text">', esc_attr( $val ) );
}

function vte_webhook_url_field_cb() {
    $opts = get_option( 'vte_settings', array() );
    $val = isset( $opts['webhook_url'] ) ? $opts['webhook_url'] : '';
    printf( '<input type="url" id="vte_webhook_url" name="vte_settings[webhook_url]" value="%s" class="regular-text" placeholder="https://example.com/webhook">' . '<p class="description">%s</p>', esc_attr( $val ), esc_html__( 'Enter the webhook URL where form submissions will be sent.', 'vehicle-transport-estimator' ) );
}

function vte_sanitize_settings( $input ) {
    $out = array();
    if ( isset( $input['phone'] ) ) {
        $out['phone'] = sanitize_text_field( $input['phone'] );
    }
    if ( isset( $input['next_step_url'] ) ) {
        $out['next_step_url'] = esc_url_raw( $input['next_step_url'] );
    }
    if ( isset( $input['webhook_url'] ) ) {
        $out['webhook_url'] = esc_url_raw( $input['webhook_url'] );
    }
    return $out;
}

// Settings page rendering is now handled in admin-page.php
