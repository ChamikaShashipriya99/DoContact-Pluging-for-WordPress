<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Validation utilities: backend and small helpers.
 *
 * Responsibilities:
 * - Validate form submissions on the server.
 * - Provide a reusable phone validation helper.
 *
 * Notes:
 * - Ensures name is letters/spaces, email is valid format, phone is 10 digits.
 */
class DoContact_Validator {

    /**
     * Validate a submission server-side.
     *
     * @param string $full_name
     * @param string $email
     * @param string $phone
     * @return array List of error messages (empty if ok)
     */
    public function validate_submission( $full_name, $email, $phone ) {
        $errors = array();

        if ( empty( $full_name ) ) {
            $errors[] = __( 'Full name is required.', 'docontact' );
        } else {
            // Only letters and spaces allowed
            if ( ! preg_match( '/^[A-Za-z\s]+$/', $full_name ) ) {
                $errors[] = __( 'Full name can only contain letters and spaces.', 'docontact' );
            }
        }

        if ( empty( $email ) ) {
            $errors[] = __( 'Email is required.', 'docontact' );
        } else {
            // sanitize_email already applied by caller, but validate format strictly
            if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                $errors[] = __( 'Email format is invalid.', 'docontact' );
            }
        }

        if ( empty( $phone ) ) {
            $errors[] = __( 'Phone number is required.', 'docontact' );
        } else {
            if ( ! $this->is_valid_phone( $phone ) ) {
                $errors[] = __( 'Phone number must be exactly 10 digits.', 'docontact' );
            }
        }

        return $errors;
    }

    /**
     * Phone validation: exactly 10 digits, numbers only.
     *
     * @param string $phone
     * @return bool
     */
    public function is_valid_phone( $phone ) {
        if ( ! is_string( $phone ) ) {
            return false;
        }

        // Remove any non-digit characters and check if exactly 10 digits
        $digits = preg_replace( '/[^\d]/', '', $phone );
        if ( strlen( $digits ) !== 10 ) {
            return false;
        }

        // Only allow digits in the original string
        if ( preg_match( '/^\d+$/', $phone ) ) {
            return true;
        }
        return false;
    }
}
