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
        // Only enqueue on our page - check both hook and screen ID
        $is_our_page = false;
        
        // Check by hook parameter
        if ( $hook === 'toplevel_page_docontact_submissions' ) {
            $is_our_page = true;
        }
        
        // Also check by screen ID for better compatibility
        $screen = get_current_screen();
        if ( $screen && isset( $screen->id ) && false !== strpos( $screen->id, 'docontact_submissions' ) ) {
            $is_our_page = true;
        }
        
        if ( $is_our_page ) {
            // Ensure script is registered first (should already be, but double-check)
            if ( ! wp_script_is( 'docontact-admin', 'registered' ) ) {
                wp_register_script( 'docontact-admin', DOCONTACT_URL . 'assets/js/admin.js', array( 'jquery' ), DOCONTACT_VERSION, true );
            }
            
            wp_enqueue_style( 'docontact-admin' );
            wp_enqueue_script( 'docontact-admin' );
            
            // Create nonce for delete action - must be created when user is logged in
            $nonce = wp_create_nonce( 'docontact_delete_nonce' );
            
            // Localize script with AJAX data and nonce
            // wp_localize_script can be called before or after wp_enqueue_script
            wp_localize_script( 'docontact-admin', 'DoContactAdmin', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => $nonce,
                'confirm'  => __( 'Are you sure you want to delete this submission?', 'docontact' ),
            ) );
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
        
        // Output localized script data directly in the page to ensure it's available
        // This is a fallback in case wp_localize_script doesn't work properly
        $nonce = wp_create_nonce( 'docontact_delete_nonce' );
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        if (typeof DoContactAdmin === 'undefined') {
            var DoContactAdmin = {
                ajax_url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
                nonce: '<?php echo esc_js( $nonce ); ?>',
                confirm: '<?php echo esc_js( __( 'Are you sure you want to delete this submission?', 'docontact' ) ); ?>'
            };
        }
        /* ]]> */
        </script>
        <div class="wrap">
            <h1><?php esc_html_e( 'DoContact Submissions', 'docontact' ); ?></h1>
            <p><?php printf( esc_html__( 'Total submissions: %d', 'docontact' ), intval( $total ) ); ?></p>

            <?php if ( ! empty( $submissions ) ): ?>
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'docontact' ); ?></label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php esc_html_e( 'Bulk Actions', 'docontact' ); ?></option>
                        <option value="delete"><?php esc_html_e( 'Delete', 'docontact' ); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'docontact' ); ?>">
                </div>
            </div>
            <?php endif; ?>

            <form id="docontact-bulk-form" method="post">
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input id="cb-select-all" type="checkbox">
                        </td>
                        <th width="60"><?php esc_html_e( 'ID', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Full Name', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Phone', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Service', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'IP', 'docontact' ); ?></th>
                        <th><?php esc_html_e( 'Submitted (UTC)', 'docontact' ); ?></th>
                        <th width="100"><?php esc_html_e( 'Actions', 'docontact' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $submissions ) ): ?>
                        <?php foreach ( $submissions as $row ): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="submission_ids[]" value="<?php echo esc_attr( $row['id'] ); ?>" class="docontact-checkbox">
                                </th>
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
                                <td>
                                    <button type="button" class="button button-small docontact-delete-btn" data-id="<?php echo esc_attr( $row['id'] ); ?>" data-name="<?php echo esc_attr( $row['full_name'] ); ?>">
                                        <?php esc_html_e( 'Delete', 'docontact' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10"><?php esc_html_e( 'No submissions found.', 'docontact' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </form>

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
