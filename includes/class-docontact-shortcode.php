<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode class that renders the form and enqueues frontend assets when needed.
 *
 * Responsibilities:
 * - Register the [docontact_form] shortcode.
 * - Enqueue frontend CSS/JS when the shortcode renders.
 * - Output the contact form markup with placeholders and error spans.
 *
 * Notes:
 * - Service options are defined locally and rendered as a select menu.
 * - Nonce field included for AJAX security.
 */
class DoContact_Shortcode {

    /**
     * Get service options from 'services' CPT
     *
     * @return array Array of post_id => post_title
     */
    private function get_service_options() {
        // Try 'services' first, then 'service' (singular) as fallback
        // TODO: If your CPT has a different name, add it here: array( 'services', 'service', 'your_cpt_name' )
        $post_types_to_try = array( 'services', 'service' );
        $options = array();
        
        foreach ( $post_types_to_try as $post_type ) {
            // Check if post type exists
            if ( ! post_type_exists( $post_type ) ) {
                // Uncomment next line temporarily for debugging:
                // error_log( "DoContact: Post type '{$post_type}' does not exist" );
                continue;
            }
            
            // Try using WP_Query for better compatibility
            $query_args = array(
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
                'suppress_filters' => false,
                'no_found_rows'  => true,
            );
            
            $query = new WP_Query( $query_args );
            
            // Uncomment next line temporarily for debugging:
            // error_log( "DoContact: Query for '{$post_type}' found {$query->found_posts} published posts" );
            
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $options[ get_the_ID() ] = get_the_title();
                }
                wp_reset_postdata();
                break; // Found posts, no need to try other post types
            }
            wp_reset_postdata();
        }
        
        // If no options found and you want to debug, uncomment the line below:
        // error_log( 'DoContact: No services found. Checked post types: ' . implode( ', ', $post_types_to_try ) );
        
        return $options;
    }

    public function __construct() {
        // Register shortcode tag.
        add_shortcode( 'docontact_form', array( $this, 'render' ) );
    }

    /**
     * Render the contact form
     */
    public function render( $atts = array() ) {
        // Enqueue assets
        if ( wp_style_is( 'font-awesome', 'registered' ) ) {
            wp_enqueue_style( 'font-awesome' );
        }
        wp_enqueue_style( 'docontact-form' );
        wp_enqueue_script( 'docontact-form' );

        ob_start();
        ?>
        <div class="docontact-wrap">
            <div id="docontact-messages" aria-live="polite"></div>
            <form id="docontact-form" method="post" novalidate>

                <p>
                    <label for="doc_full_name">Full Name <span class="required">*</span></label><br/>
                    <input type="text" id="doc_full_name" name="full_name" pattern="[A-Za-z\s]+" placeholder="Please enter your full name (Only letters and spaces are allowed)" required />
                    <span class="doc-field-error" id="doc_full_name_error" role="alert"></span>
                </p>

                <p>
                    <label for="doc_email">Email <span class="required">*</span></label><br/>
                    <input type="email" id="doc_email" name="email" placeholder="Please enter your email (example@example.com)" required />
                    <span class="doc-field-error" id="doc_email_error" role="alert"></span>
                </p>

                <p>
                    <label for="doc_phone">Phone Number <span class="required">*</span></label><br/>
                    <input type="tel" id="doc_phone" name="phone" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" placeholder="Please enter your mobile number (10 digits)" required />
                    <span class="doc-field-error" id="doc_phone_error" role="alert"></span>
                </p>

                <p>
                    <label for="doc_service">Service Required</label><br/>
                    <div class="doc-select-wrapper">
                        <select id="doc_service" name="service">
                            <option value=""><?php esc_html_e( '-- Select Service --', 'docontact' ); ?></option>
                            <?php
                            $service_options = $this->get_service_options();
                            if ( empty( $service_options ) ) {
                                // Debug: Show message if no services found (remove in production)
                                // Uncomment the line below temporarily to debug:
                                // echo '<!-- DEBUG: No services found. Check if CPT "services" exists and has published posts. -->';
                            }
                            foreach ( $service_options as $post_id => $title ):
                            ?>
                                <option value="<?php echo esc_attr( $post_id ); ?>"><?php echo esc_html( $title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-angle-down"></i>
                    </div>
                </p>

                <p>
                    <label for="doc_message">Message</label><br/>
                    <textarea id="doc_message" name="message" placeholder="Please enter your message" rows="5"></textarea>
                </p>

                <p>
                    <button type="submit" id="doc_submit" class="doc-button">Submit Message</button>
                    <span class="doc-loading" id="doc-loading" aria-hidden="true" style="display:none;">
                        <span class="doc-spinner" aria-hidden="true"></span>
                    </span>
                </p>

                <?php wp_nonce_field( 'docontact_submit_nonce', 'docontact_nonce' ); ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}
