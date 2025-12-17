<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create submissions table on plugin activation.
 */
function vte_create_submissions_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'vte_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        fullname varchar(255) NOT NULL,
        phone varchar(50) NOT NULL,
        email varchar(255) NOT NULL,
        pickup_state varchar(100) NOT NULL,
        dropoff_state varchar(100) NOT NULL,
        price varchar(100) DEFAULT NULL,
        distance varchar(100) DEFAULT NULL,
        transit_time varchar(100) DEFAULT NULL,
        submitted_at datetime NOT NULL,
        ip_address varchar(100) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        PRIMARY KEY (id),
        KEY submitted_at (submitted_at),
        KEY email (email)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Store database version
    add_option( 'vte_db_version', '1.0.0' );
}

/**
 * Check if submissions table exists.
 */
function vte_table_exists() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vte_submissions';
    $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
    return $wpdb->get_var( $query ) === $table_name;
}

/**
 * Save submission to database.
 */
function vte_save_submission( $data ) {
    global $wpdb;

    // Check if table exists, create if it doesn't
    if ( ! vte_table_exists() ) {
        vte_create_submissions_table();
    }

    $table_name = $wpdb->prefix . 'vte_submissions';

    $insert_data = array(
        'fullname' => sanitize_text_field( $data['fullname'] ),
        'phone' => sanitize_text_field( $data['phone'] ),
        'email' => sanitize_email( $data['email'] ),
        'pickup_state' => sanitize_text_field( $data['pickup'] ),
        'dropoff_state' => sanitize_text_field( $data['dropoff'] ),
        'price' => sanitize_text_field( $data['price'] ),
        'distance' => sanitize_text_field( $data['distance'] ),
        'transit_time' => sanitize_text_field( $data['transit'] ),
        'submitted_at' => current_time( 'mysql' ),
        'ip_address' => vte_get_client_ip(),
        'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
    );

    $result = $wpdb->insert( $table_name, $insert_data );

    if ( $result === false ) {
        // Log the error for debugging
        error_log( 'VTE Database Error: ' . $wpdb->last_error );
        return false;
    }

    return $wpdb->insert_id;
}

/**
 * Get client IP address.
 */
function vte_get_client_ip() {
    $ip = '';

    if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return sanitize_text_field( $ip );
}

/**
 * Get all submissions with pagination.
 */
function vte_get_submissions( $args = array() ) {
    global $wpdb;

    $defaults = array(
        'per_page' => 20,
        'page' => 1,
        'orderby' => 'submitted_at',
        'order' => 'DESC',
        'search' => '',
    );

    $args = wp_parse_args( $args, $defaults );
    $table_name = $wpdb->prefix . 'vte_submissions';

    $where = '1=1';

    // Search functionality
    if ( ! empty( $args['search'] ) ) {
        $search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        $where .= $wpdb->prepare(
            ' AND (fullname LIKE %s OR email LIKE %s OR phone LIKE %s OR pickup_state LIKE %s OR dropoff_state LIKE %s)',
            $search, $search, $search, $search, $search
        );
    }

    // Count total
    $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE $where" );

    // Get submissions
    $offset = ( $args['page'] - 1 ) * $args['per_page'];
    $orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

    $submissions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d",
            $args['per_page'],
            $offset
        )
    );

    return array(
        'submissions' => $submissions,
        'total' => $total,
        'per_page' => $args['per_page'],
        'current_page' => $args['page'],
        'total_pages' => ceil( $total / $args['per_page'] ),
    );
}

/**
 * Delete submission by ID.
 */
function vte_delete_submission( $id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'vte_submissions';

    return $wpdb->delete( $table_name, array( 'id' => absint( $id ) ), array( '%d' ) );
}

/**
 * Get submission statistics.
 */
function vte_get_stats() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'vte_submissions';

    $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    $today = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE DATE(submitted_at) = %s",
        current_time( 'Y-m-d' )
    ) );
    $this_month = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE MONTH(submitted_at) = %d AND YEAR(submitted_at) = %d",
        date( 'n' ),
        date( 'Y' )
    ) );

    return array(
        'total' => $total,
        'today' => $today,
        'this_month' => $this_month,
    );
}
