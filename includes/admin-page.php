<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add admin menu.
 */
function vte_add_admin_menu() {
    $hook = add_menu_page(
        __( 'Transport Estimator', 'vehicle-transport-estimator' ),
        __( 'Transport Estimator', 'vehicle-transport-estimator' ),
        'manage_options',
        'vte-admin',
        'vte_render_admin_page',
        'dashicons-car',
        30
    );

    // Enqueue admin styles
    add_action( 'load-' . $hook, 'vte_enqueue_admin_assets' );
}
add_action( 'admin_menu', 'vte_add_admin_menu' );

/**
 * Enqueue admin assets.
 */
function vte_enqueue_admin_assets() {
    wp_enqueue_style( 'vte-admin', VTE_URL . 'assets/css/admin.css', array(), VTE_VERSION );
}

/**
 * Render admin page with tabs.
 */
function vte_render_admin_page() {
    // Handle delete action
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && isset( $_GET['_wpnonce'] ) ) {
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'vte_delete_submission_' . $_GET['id'] ) ) {
            vte_delete_submission( $_GET['id'] );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Submission deleted successfully.', 'vehicle-transport-estimator' ) . '</p></div>';
        }
    }

    $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'submissions';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
            <a href="?page=vte-admin&tab=submissions" class="nav-tab <?php echo $current_tab === 'submissions' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Submissions', 'vehicle-transport-estimator' ); ?>
            </a>
            <a href="?page=vte-admin&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Settings', 'vehicle-transport-estimator' ); ?>
            </a>
        </nav>

        <div class="tab-content">
            <?php
            if ( $current_tab === 'submissions' ) {
                vte_render_submissions_tab();
            } elseif ( $current_tab === 'settings' ) {
                vte_render_settings_tab();
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Render submissions tab (CRM).
 */
function vte_render_submissions_tab() {
    $stats = vte_get_stats();

    // Get search query
    $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
    $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

    $data = vte_get_submissions( array(
        'page' => $paged,
        'per_page' => 20,
        'search' => $search,
    ) );

    ?>
    <div class="vte-submissions">
        <!-- Statistics Cards -->
        <div class="vte-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="vte-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; color: #646970; margin-bottom: 5px;"><?php esc_html_e( 'Total Submissions', 'vehicle-transport-estimator' ); ?></div>
                <div style="font-size: 32px; font-weight: 600; color: #1d2327;"><?php echo esc_html( $stats['total'] ); ?></div>
            </div>
            <div class="vte-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; color: #646970; margin-bottom: 5px;"><?php esc_html_e( 'Today', 'vehicle-transport-estimator' ); ?></div>
                <div style="font-size: 32px; font-weight: 600; color: #1d2327;"><?php echo esc_html( $stats['today'] ); ?></div>
            </div>
            <div class="vte-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; color: #646970; margin-bottom: 5px;"><?php esc_html_e( 'This Month', 'vehicle-transport-estimator' ); ?></div>
                <div style="font-size: 32px; font-weight: 600; color: #1d2327;"><?php echo esc_html( $stats['this_month'] ); ?></div>
            </div>
        </div>

        <!-- Search Form -->
        <form method="get" action="" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="vte-admin">
            <input type="hidden" name="tab" value="submissions">
            <p class="search-box">
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search submissions...', 'vehicle-transport-estimator' ); ?>" style="width: 300px;">
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'vehicle-transport-estimator' ); ?></button>
            </p>
        </form>

        <!-- Submissions Table -->
        <?php if ( ! empty( $data['submissions'] ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'vehicle-transport-estimator' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'vehicle-transport-estimator' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'vehicle-transport-estimator' ); ?></th>
                        <th><?php esc_html_e( 'Phone', 'vehicle-transport-estimator' ); ?></th>
                        <th><?php esc_html_e( 'Route', 'vehicle-transport-estimator' ); ?></th>
                        <th><?php esc_html_e( 'Price', 'vehicle-transport-estimator' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'vehicle-transport-estimator' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $data['submissions'] as $submission ) : ?>
                        <tr>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission->submitted_at ) ) ); ?></td>
                            <td><strong><?php echo esc_html( $submission->fullname ); ?></strong></td>
                            <td><a href="mailto:<?php echo esc_attr( $submission->email ); ?>"><?php echo esc_html( $submission->email ); ?></a></td>
                            <td><a href="tel:<?php echo esc_attr( $submission->phone ); ?>"><?php echo esc_html( $submission->phone ); ?></a></td>
                            <td><?php echo esc_html( $submission->pickup_state . ' → ' . $submission->dropoff_state ); ?></td>
                            <td><?php echo esc_html( $submission->price ); ?><br><small style="color: #646970;"><?php echo esc_html( $submission->distance . ' • ' . $submission->transit_time ); ?></small></td>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $submission->id ) ), 'vte_delete_submission_' . $submission->id ) ); ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this submission?', 'vehicle-transport-estimator' ); ?>');">
                                    <?php esc_html_e( 'Delete', 'vehicle-transport-estimator' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ( $data['total_pages'] > 1 ) : ?>
                <div class="tablenav bottom" style="margin-top: 20px;">
                    <div class="tablenav-pages">
                        <?php
                        $pagination_args = array(
                            'base' => add_query_arg( 'paged', '%#%' ),
                            'format' => '',
                            'current' => $data['current_page'],
                            'total' => $data['total_pages'],
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        );
                        echo paginate_links( $pagination_args );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="notice notice-info" style="margin-top: 20px;">
                <p><?php esc_html_e( 'No submissions found.', 'vehicle-transport-estimator' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render settings tab.
 */
function vte_render_settings_tab() {
    ?>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'vte_settings_group' );
        do_settings_sections( 'vte_settings' );
        submit_button();
        ?>
    </form>
    <?php
}
