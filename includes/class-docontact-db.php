<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DB wrapper for DoContact submissions.
 *
 * Responsibilities:
 * - Provide simple helpers to insert and read submissions.
 * - Hide raw table name handling and $wpdb usage behind methods.
 *
 * Notes:
 * - Table name is prefixed with the site's $wpdb->prefix.
 * - Uses prepared statements for pagination query.
 * - Returns associative arrays for reads.
 */
class DoContact_DB {

    /** @var string */
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'docontact_submissions';
    }

    /**
     * Insert a submission.
     *
     * @param array $data Keys: full_name, email, phone, service, message, ip_address, created_at
     * @return int|false Insert ID on success, false on failure.
     */
    public function insert_submission( $data ) {
        global $wpdb;

        // Column formats map to the insert array order.
        $format   = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
        $inserted = $wpdb->insert( $this->table, $data, $format );

        if ( $inserted ) {
            return (int) $wpdb->insert_id;
        }
        return false;
    }

    /**
     * Get submissions with pagination.
     *
     * @param int $per_page
     * @param int $offset
     * @return array
     */
    public function get_submissions( $per_page = 25, $offset = 0 ) {
        global $wpdb;

        // Use prepare to safely set limits
        $sql = $wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset );
        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Count all submissions.
     *
     * @return int
     */
    public function count_submissions() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
    }

    /**
     * Delete a submission by ID.
     *
     * @param int $id Submission ID
     * @return bool True on success, false on failure.
     */
    public function delete_submission( $id ) {
        global $wpdb;

        $id = absint( $id );
        if ( $id <= 0 ) {
            return false;
        }

        $deleted = $wpdb->delete(
            $this->table,
            array( 'id' => $id ),
            array( '%d' )
        );

        return $deleted !== false;
    }
}
