<?php
/*
Plugin Name: DoContact
Description: Collect visitor contact submissions via AJAX, validate email & phone, store in custom DB table and view submissions in admin.
Version:     1.0.0
Author:      Chamika Shashipriya
Text Domain: docontact
*/

// Guard direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Basic constants
 */
define( 'DOCONTACT_VERSION', '1.0.0' );            // Plugin semantic version.
define( 'DOCONTACT_DIR', plugin_dir_path( __FILE__ ) ); // Filesystem path to plugin root.
define( 'DOCONTACT_URL', plugin_dir_url( __FILE__ ) );  // Public URL to plugin root.

// Load core classes.
require_once DOCONTACT_DIR . 'includes/class-docontact-activator.php';
require_once DOCONTACT_DIR . 'includes/class-docontact-db.php';
require_once DOCONTACT_DIR . 'includes/class-docontact-validator.php';
require_once DOCONTACT_DIR . 'includes/class-docontact-ajax.php';
require_once DOCONTACT_DIR . 'includes/class-docontact-shortcode.php';
require_once DOCONTACT_DIR . 'includes/class-docontact-admin.php';

/**
 * Main bootstrap class that wires everything together.
 *
 * Responsibilities:
 * - Define and share core constants (version, paths, URLs).
 * - Load all component classes (DB, validator, AJAX, shortcode, admin).
 * - Register activation hook to create/upgrade the DB table.
 * - Register (but do not immediately enqueue) frontend/admin assets.
 * - Instantiate components and wire WordPress hooks.
 *
 * Flow:
 * 1) On plugin load, this class is instantiated via ::instance().
 * 2) Activation hook points to DoContact_Activator::activate() for table setup.
 * 3) Constructor is private; instance() ensures singleton behavior.
 * 4) init() builds core services: DB, Validator, Ajax, Shortcode, Admin.
 * 5) register_frontend_assets() and register_admin_assets() only register assets;
 *    actual enqueue happens inside Shortcode/Admin when needed.
 */
class DoContact_Plugin_Bootstrap {

    /** @var DoContact_DB DB wrapper for submissions table access. */
    private $db;

    /** @var DoContact_Validator Server-side validation helper. */
    private $validator;

    /** @var DoContact_Ajax AJAX controller for form submissions. */
    private $ajax;

    /** @var DoContact_Shortcode Renders the frontend form via shortcode. */
    private $shortcode;

    /** @var DoContact_Admin Admin UI for viewing submissions. */
    private $admin;

    /** @var DoContact_Plugin_Bootstrap|null Singleton instance holder. */
    private static $instance = null;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function init() {
        // Activation: create/upgrade DB table on plugin activation.
        register_activation_hook( __FILE__, array( 'DoContact_Activator', 'activate' ) );

        // Initialize components.
        $this->db = new DoContact_DB();
        $this->validator = new DoContact_Validator();
        $this->ajax = new DoContact_Ajax( $this->db, $this->validator );
        $this->shortcode = new DoContact_Shortcode();
        $this->admin = new DoContact_Admin( $this->db );

        // Enqueue frontend assets (register only)
        add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );

        // Enqueue admin assets (register only)
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
    }

    public function register_frontend_assets() {
        // Font Awesome (if not already enqueued by theme)
        if ( ! wp_style_is( 'font-awesome', 'enqueued' ) && ! wp_style_is( 'fontawesome', 'enqueued' ) ) {
            wp_register_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
        }
        
        // CSS
        wp_register_style( 'docontact-form', DOCONTACT_URL . 'assets/css/form.css', array(), DOCONTACT_VERSION );
        // JS (depends on jquery)
        wp_register_script( 'docontact-form', DOCONTACT_URL . 'assets/js/form.js', array( 'jquery' ), DOCONTACT_VERSION, true );

        // Localize for AJAX
        wp_localize_script( 'docontact-form', 'DoContactVars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'docontact_submit_nonce' ),
        ) );
    }

    public function register_admin_assets( $hook ) {
        // Only register; admin class will enqueue when showing its page.
        wp_register_style( 'docontact-admin', DOCONTACT_URL . 'assets/css/admin.css', array(), DOCONTACT_VERSION );
        wp_register_script( 'docontact-admin', DOCONTACT_URL . 'assets/js/admin.js', array( 'jquery' ), DOCONTACT_VERSION, true );
    }
}

DoContact_Plugin_Bootstrap::instance();
