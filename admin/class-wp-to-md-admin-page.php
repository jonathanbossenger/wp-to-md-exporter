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
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_wp_to_md_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_wp_to_md_download', array( $this, 'handle_download' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		// Add main menu item.
		$hook = add_menu_page(
			__( 'Export to Markdown', 'wp-to-md' ),
			__( 'Export to Markdown', 'wp-to-md' ),
			'manage_options',
			'wp-to-md',
			array( $this, 'render_admin_page' ),
			'dashicons-download'
		);

		// Add submenu for tests (only in debug mode).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_submenu_page(
				'wp-to-md',
				__( 'Run Tests', 'wp-to-md' ),
				__( 'Run Tests', 'wp-to-md' ),
				'manage_options',
				'wp-to-md-tests',
				array( $this, 'render_test_page' )
			);
		}
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
		$date_prefix = isset( $_POST['date_prefix'] ) && '1' === $_POST['date_prefix'];

		// Initialize exporter.
		$exporter = new WP_To_MD_Exporter();
		$result = $exporter->export( $post_type, $date_prefix );

		if ( is_wp_error( $result ) ) {
			$redirect_url = add_query_arg(
				array(
					'page'   => 'wp-to-md',
					'error'  => urlencode( $result->get_error_message() ),
				),
				admin_url( 'admin.php' )
			);
		} else {
			$success_count = count( $result['success'] );
			$failed_count = count( $result['failed'] );
			$redirect_url = add_query_arg(
				array(
					'page'     => 'wp-to-md',
					'success'  => $success_count,
					'failed'   => $failed_count,
					'zip_file' => urlencode( $result['zip_file'] ),
				),
				admin_url( 'admin.php' )
			);
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle file download request.
	 */
	public function handle_download() {
		if ( ! isset( $_GET['file'], $_GET['nonce'] ) ) {
			wp_die( esc_html__( 'Invalid download request.', 'wp-to-md' ) );
		}

		$file = sanitize_text_field( wp_unslash( $_GET['file'] ) );
		$file_path = sanitize_text_field( base64_decode( $file ) );
		$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'wp_to_md_download_' . $file_path ) ) {
			wp_die( esc_html__( 'Invalid download nonce.', 'wp-to-md' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to download this file.', 'wp-to-md' ) );
		}

		$upload_dir = wp_upload_dir();
		$file = $upload_dir['basedir'] . $file_path;

		if ( ! file_exists( $file ) ) {
			wp_die( esc_html__( 'File not found.', 'wp-to-md' ) );
		}

		$filename = basename( $file );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'Pragma: public' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Expires: 0' );

		readfile( $file );
		exit;
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		require_once WP_TO_MD_PLUGIN_DIR . 'admin/views/admin-page.php';
	}

	/**
	 * Render the test page.
	 */
	public function render_test_page() {
		require_once WP_TO_MD_PLUGIN_DIR . 'tests/run-tests.php';
	}
} 
