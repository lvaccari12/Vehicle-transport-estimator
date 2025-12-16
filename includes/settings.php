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
            echo '<p>' . esc_html__( 'Configure phone number and next-step URL used by the estimator.', 'vehicle-transport-estimator' ) . '</p>';
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

function vte_sanitize_settings( $input ) {
    $out = array();
    if ( isset( $input['phone'] ) ) {
        $out['phone'] = sanitize_text_field( $input['phone'] );
    }
    if ( isset( $input['next_step_url'] ) ) {
        $out['next_step_url'] = esc_url_raw( $input['next_step_url'] );
    }
    return $out;
}

function vte_add_settings_page() {
    add_options_page(
        __( 'Transport Estimator', 'vehicle-transport-estimator' ),
        __( 'Transport Estimator', 'vehicle-transport-estimator' ),
        'manage_options',
        'vte_settings',
        'vte_render_settings_page'
    );
}
add_action( 'admin_menu', 'vte_add_settings_page' );

function vte_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( __( 'Transport Estimator Settings', 'vehicle-transport-estimator' ) ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'vte_settings_group' );
            do_settings_sections( 'vte_settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
