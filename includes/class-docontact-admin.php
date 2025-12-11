<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin page for viewing submissions.
 *
 * Responsibilities:
 * - Register the DoContact top-level menu page.
 * - Enqueue admin assets only when that page is loaded.
 * - Fetch paginated submissions from the DB wrapper.
 * - Render a table of submissions with basic pagination.
 *
 * Security:
 * - Requires 'manage_options' capability.
 * - Escapes all output before rendering.
 */
class DoContact_Admin {

    /** @var DoContact_DB */
    private $db;

    public function __construct( DoContact_DB $db ) {
        $this->db = $db;

        // Hook to register admin menu and enqueue assets.
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_menu() {
        // Adds a top-level menu item.
        add_menu_page(
            __( 'DoContact Submissions', 'docontact' ),
            __( 'DoContact', 'docontact' ),
            'manage_options',
            'docontact_submissions',
            array( $this, 'render_page' ),
            'dashicons-email-alt',
            26
        );
    }

    public function enqueue_assets( $hook ) {
        // Only enqueue on our page
        $screen = get_current_screen();
        if ( $screen && isset( $screen->id ) && false !== strpos( $screen->id, 'docontact_submissions' ) ) {
            wp_enqueue_style( 'docontact-admin' );
            wp_enqueue_script( 'docontact-admin' );
        }
    }

    public function render_page() {
        // Capability check guards the page.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions', 'docontact' ) );
        }

        // Pagination inputs.
        $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $per_page = 25;
        $offset = ( $paged - 1 ) * $per_page;

        // Fetch data.
        $total = $this->db->count_submissions();
        $submissions = $this->db->get_submissions( $per_page, $offset );
        $total_pages = ( $total > 0 ) ? ceil( $total / $per_page ) : 1;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'DoContact Submissions', 'docontact' ); ?></h1>
            <p><?php printf( esc_html__( 'Total submissions: %d', 'docontact' ), intval( $total ) ); ?></p>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th width="60"><?php esc_html_e( 'ID', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Full Name', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Phone', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Service', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'IP', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Submitted (UTC)', 'docontact' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $submissions ) ): ?>
                        <?php foreach ( $submissions as $row ): ?>
                            <tr>
                                <td><?php echo esc_html( $row['id'] ); ?></td>
                                <td><?php echo esc_html( $row['full_name'] ); ?></td>
                                <td><a href="mailto:<?php echo esc_attr( $row['email'] ); ?>"><?php echo esc_html( $row['email'] ); ?></a></td>
                                <td><?php echo esc_html( $row['phone'] ); ?></td>
                                <td><?php
                                    $service_raw = isset( $row['service'] ) ? $row['service'] : '';
                                    $svc = '';
                                    if ( is_numeric( $service_raw ) ) {
                                        $service_id = absint( $service_raw );
                                        if ( $service_id > 0 ) {
                                            $service_post = get_post( $service_id );
                                            if ( $service_post && in_array( $service_post->post_type, array( 'services', 'service' ), true ) ) {
                                                $svc = $service_post->post_title;
                                            }
                                        }
                                    } else {
                                        // Already stored as a readable title
                                        $svc = $service_raw;
                                    }
                                    // Fallback to stored value if post not found
                                    if ( empty( $svc ) && ! empty( $service_raw ) ) {
                                        $svc = $service_raw;
                                    }
                                    echo esc_html( $svc );
                                ?></td>
                                <td style="max-width:400px;"><div style="white-space:pre-wrap;"><?php echo esc_html( $row['message'] ); ?></div></td>
                                <td><?php echo esc_html( $row['ip_address'] ); ?></td>
                                <td><?php echo esc_html( $row['created_at'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8"><?php esc_html_e( 'No submissions found.', 'docontact' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        $base = remove_query_arg( array( 'paged' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
                        for ( $i = 1; $i <= $total_pages; $i++ ) {
                            if ( $i === $paged ) {
                                echo '<span class="page-numbers current">' . esc_html( $i ) . '</span> ';
                            } else {
                                $url = add_query_arg( 'paged', $i, $base );
                                echo '<a class="page-numbers" href="' . esc_url( $url ) . '">' . esc_html( $i ) . '</a> ';
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }
}
