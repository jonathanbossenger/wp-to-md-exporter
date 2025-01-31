<?php
/**
 * Admin page class.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WP_To_MD_Admin_Page
 */
class WP_To_MD_Admin_Page {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_wp_to_md_export', array( $this, 'handle_export' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Export to Markdown', 'wp-to-md' ),
			__( 'Export to Markdown', 'wp-to-md' ),
			'manage_options',
			'wp-to-md',
			array( $this, 'render_admin_page' ),
			'dashicons-download'
		);
	}

	/**
	 * Get available post types.
	 *
	 * @return array Array of post type objects.
	 */
	public function get_available_post_types() {
		return get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);
	}

	/**
	 * Get posts of a specific type.
	 *
	 * @param string $post_type The post type to fetch.
	 * @return array Array of post objects.
	 */
	public function get_posts_by_type( $post_type ) {
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Handle the export process.
	 */
	public function handle_export() {
		if ( ! isset( $_POST['wp_to_md_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_to_md_nonce'] ) ), 'wp_to_md_export' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'wp-to-md' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-to-md' ) );
		}

		$post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'post';
		$posts = $this->get_posts_by_type( $post_type );

		// TODO: Implement the actual export process. This will be implemented in the export handling section.

		wp_safe_redirect( admin_url( 'admin.php?page=wp-to-md' ) );
		exit;
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		require_once WP_TO_MD_PLUGIN_DIR . 'admin/views/admin-page.php';
	}
} 
