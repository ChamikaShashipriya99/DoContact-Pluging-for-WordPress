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

    private $service_options = array(
        'general'   => 'General Inquiry',
        'web_dev'   => 'Web Development',
        'seo'       => 'SEO',
        'support'   => 'Support',
        'other'     => 'Other',
    );

    public function __construct() {
        // Register shortcode tag.
        add_shortcode( 'docontact_form', array( $this, 'render' ) );
    }

    /**
     * Render the contact form
     */
    public function render( $atts = array() ) {
        // Enqueue assets
        wp_enqueue_style( 'docontact-form' );
        wp_enqueue_script( 'docontact-form' );

        ob_start();
        ?>
        <div class="docontact-wrap">
            <form id="docontact-form" method="post" novalidate>
                <div id="docontact-messages" aria-live="polite"></div>

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
                    <select id="doc_service" name="service">
                        <?php foreach ( $this->service_options as $key => $label ): ?>
                            <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
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
