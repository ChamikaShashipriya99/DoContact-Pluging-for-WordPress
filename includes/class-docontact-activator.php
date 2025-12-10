<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles plugin activation tasks.
 *
 * Workflow:
 * 1) Determine the submissions table name using the site prefix.
 * 2) Build a CREATE TABLE statement with appropriate column types and indexes.
 * 3) Load wp-admin/upgrade.php for dbDelta support.
 * 4) Run dbDelta() to create or update the table schema safely.
 * 5) Store the plugin DB version in an option for future migrations.
 *
 * Notes:
 * - dbDelta is idempotent; it will create or alter the table to match the SQL.
 * - Charset/collation is pulled from the WordPress DB settings for compatibility.
 */
class DoContact_Activator {

    public static function activate() {
        global $wpdb;

        $table_name     = $wpdb->prefix . 'docontact_submissions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            full_name VARCHAR(191) NOT NULL,
            email VARCHAR(191) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            service VARCHAR(100) DEFAULT NULL,
            message TEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY email_idx (email)
        ) {$charset_collate};";

        // dbDelta lives in this file; required before calling dbDelta().
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Track schema version for potential future migrations.
        add_option( 'docontact_db_version', DOCONTACT_VERSION );
    }
}
