<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler class.
 *
 * Responsibilities:
 * - Register authenticated and unauthenticated AJAX actions.
 * - Validate and sanitize incoming POST data.
 * - Persist submissions via the DB wrapper.
 * - Return JSON success/error responses.
 *
 * Security:
 * - Verifies request method and nonce.
 * - Sanitizes and validates all inputs.
 */
class DoContact_Ajax {

    /** @var DoContact_DB */
    private $db;

    /** @var DoContact_Validator */
    private $validator;

    public function __construct( DoContact_DB $db, DoContact_Validator $validator ) {
        $this->db = $db;
        $this->validator = $validator;

        // Allow both logged-in and guest submissions.
        add_action( 'wp_ajax_docontact_submit', array( $this, 'handle' ) );
        add_action( 'wp_ajax_nopriv_docontact_submit', array( $this, 'handle' ) );
    }

    /**
     * Handle the AJAX submission
     */
    public function handle() {
        // Basic method guard.
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request method.', 'docontact' ) ), 405 );
        }

        // Nonce verification.
        $nonce = isset( $_POST['docontact_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['docontact_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'docontact_submit_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'docontact' ) ), 403 );
        }

        // Sanitize incoming data
        $full_name = isset( $_POST['full_name'] ) ? trim( wp_strip_all_tags( wp_unslash( $_POST['full_name'] ) ) ) : '';
        $email     = isset( $_POST['email'] ) ? trim( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : '';
        $phone     = isset( $_POST['phone'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : '';
        $service   = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
        $message   = isset( $_POST['message'] ) ? trim( wp_kses_post( wp_unslash( $_POST['message'] ) ) ) : '';

        // Validate backend
        $errors = $this->validator->validate_submission( $full_name, $email, $phone );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 422 );
        }

        // Validate service: accept either 'services' or 'service' CPT IDs
        $service_id      = '';
        $service_value   = '';
        $allowed_service = array( 'services', 'service' );
        if ( ! empty( $service ) ) {
            $service_id = absint( $service );
            if ( $service_id > 0 ) {
                $service_post = get_post( $service_id );
                if ( $service_post && in_array( $service_post->post_type, $allowed_service, true ) && $service_post->post_status === 'publish' ) {
                    $service_value = $service_post->post_title; // store the readable title instead of the ID
                } else {
                    $service_id = '';
                }
            } else {
                $service_id = '';
            }
        }

        $ip_address = $this->get_ip();

        // Persist submission
        $data = array(
            'full_name'  => $full_name,
            'email'      => $email,
            'phone'      => $phone,
            'service'    => $service_value,
            'message'    => $message,
            'ip_address' => $ip_address,
            'created_at' => current_time( 'mysql', 1 ), // store as UTC
        );

        $insert_id = $this->db->insert_submission( $data );

        if ( $insert_id ) {
            wp_send_json_success( array( 'message' => __( 'Thank you — your message has been received.', 'docontact' ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to save submission. Please try again later.', 'docontact' ) ), 500 );
    }

    /**
     * Get client IP
     *
     * @return string
     */
    private function get_ip() {
        // Prefer client IP, fall back to forwarded-for, then remote addr.
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip_list = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            return sanitize_text_field( trim( $ip_list[0] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        return '';
    }
}
