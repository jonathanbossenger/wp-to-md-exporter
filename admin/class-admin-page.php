<?php
/**
 * Admin page handler class.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class to handle the admin interface.
 */
class WP_To_MD_Admin_Page {

	/**
	 * Initialize the admin page.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add menu item to WordPress admin.
	 */
	public function add_admin_menu() {
		add_management_page(
			__( 'Export to Markdown', 'wp-to-md' ),
			__( 'Export to Markdown', 'wp-to-md' ),
			'manage_options',
			'wp-to-md',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'tools_page_wp-to-md' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wp-to-md-admin',
			WP_TO_MD_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			WP_TO_MD_VERSION
		);
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		require_once WP_TO_MD_PLUGIN_DIR . 'admin/views/admin-page.php';
	}
} 
