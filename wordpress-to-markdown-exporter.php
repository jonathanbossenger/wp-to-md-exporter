<?php
/**
 * Plugin Name: WordPress to Markdown Exporter
 * Plugin URI: 
 * Description: Export WordPress posts and custom post types to Markdown format
 * Version: 1.0.0
 * Author: 
 * Author URI: 
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-to-md
 * Domain Path: /languages
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Main plugin class
 */
class WordPress_To_Markdown_Exporter {
    /**
     * Single instance of this class
     *
     * @var WordPress_To_Markdown_Exporter
     */
    private static $instance = null;

    /**
     * Get single instance of this class
     *
     * @return WordPress_To_Markdown_Exporter
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        define( 'WP_TO_MD_VERSION', '1.0.0' );
        define( 'WP_TO_MD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'WP_TO_MD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Load admin class
        if ( is_admin() ) {
            require_once WP_TO_MD_PLUGIN_DIR . 'admin/class-admin-page.php';
            new WP_To_MD_Admin_Page();
        }
    }

    /**
     * Activation hook callback
     */
    public static function activate() {
        // Future activation code will go here
    }

    /**
     * Deactivation hook callback
     */
    public static function deactivate() {
        // Future cleanup code will go here
    }
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, array( 'WordPress_To_Markdown_Exporter', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WordPress_To_Markdown_Exporter', 'deactivate' ) );

// Initialize the plugin
WordPress_To_Markdown_Exporter::get_instance(); 
