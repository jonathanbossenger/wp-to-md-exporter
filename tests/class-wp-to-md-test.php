<?php
/**
 * Test class for WordPress to Markdown Exporter.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WP_To_MD_Test
 */
class WP_To_MD_Test {

	/**
	 * The converter instance.
	 *
	 * @var WP_To_MD_Converter
	 */
	private $converter;

	/**
	 * The file handler instance.
	 *
	 * @var WP_To_MD_File_Handler
	 */
	private $file_handler;

	/**
	 * The exporter instance.
	 *
	 * @var WP_To_MD_Exporter
	 */
	private $exporter;

	/**
	 * Initialize the test class.
	 */
	public function __construct() {
		$this->converter = new WP_To_MD_Converter();
		$this->file_handler = new WP_To_MD_File_Handler();
		$this->exporter = new WP_To_MD_Exporter();
	}

	/**
	 * Run all tests.
	 */
	public function run_tests() {
		$this->test_post_type_handling();
		$this->test_file_system();
		$this->test_conversion();
		$this->test_export_process();
		$this->test_file_management();
		$this->test_cleanup();
	}

	/**
	 * Test post type handling.
	 */
	private function test_post_type_handling() {
		// Test post type access.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		if ( empty( $post_types ) ) {
			$this->log_error( 'No public post types found.' );
			return;
		}

		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content with some <strong>HTML</strong>.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			$this->log_error( 'Failed to create test post: ' . $post_id->get_error_message() );
			return;
		}

		$this->log_success( 'Post type handling tests passed.' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test file system operations.
	 */
	private function test_file_system() {
		// Test directory creation.
		$result = $this->file_handler->create_exports_directory();
		if ( is_wp_error( $result ) ) {
			$this->log_error( 'Failed to create exports directory: ' . $result->get_error_message() );
			return;
		}

		// Test export directory creation.
		$export_dir = $this->file_handler->create_export_directory();
		if ( is_wp_error( $export_dir ) ) {
			$this->log_error( 'Failed to create export directory: ' . $export_dir->get_error_message() );
			return;
		}

		// Test file permissions.
		if ( ! is_writable( $export_dir ) ) {
			$this->log_error( 'Export directory is not writable: ' . $export_dir );
			return;
		}

		$this->log_success( 'File system tests passed.' );
	}

	/**
	 * Test conversion functionality.
	 */
	private function test_conversion() {
		$test_cases = array(
			'<h1>Test Heading</h1>'                => '# Test Heading',
			'<p>Test paragraph</p>'                => 'Test paragraph',
			'<strong>Bold text</strong>'           => '**Bold text**',
			'<em>Italic text</em>'                => '*Italic text*',
			'<a href="test.com">Link</a>'         => '[Link](test.com)',
			'<img src="test.jpg" alt="Test">'     => '![Test](test.jpg)',
			'<code>Test code</code>'              => '`Test code`',
			'<ul><li>Item 1</li><li>Item 2</li></ul>' => '* Item 1\n* Item 2',
		);

		foreach ( $test_cases as $html => $expected ) {
			$result = trim( $this->converter->convert( $html ) );
			$expected = trim( $expected );

			if ( $result !== $expected ) {
				$this->log_error(
					sprintf(
						'Conversion test failed. Expected: "%s", Got: "%s"',
						$expected,
						$result
					)
				);
				return;
			}
		}

		$this->log_success( 'Conversion tests passed.' );
	}

	/**
	 * Test export process.
	 */
	private function test_export_process() {
		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Export Test Post',
				'post_content' => 'Test content for export.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			$this->log_error( 'Failed to create test post for export: ' . $post_id->get_error_message() );
			return;
		}

		// Test export.
		$result = $this->exporter->export( 'post', true );
		if ( is_wp_error( $result ) ) {
			$this->log_error( 'Export test failed: ' . $result->get_error_message() );
			wp_delete_post( $post_id, true );
			return;
		}

		// Verify export results.
		if ( empty( $result['success'] ) ) {
			$this->log_error( 'Export test failed: No successful exports.' );
			wp_delete_post( $post_id, true );
			return;
		}

		$this->log_success( 'Export process tests passed.' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test file management.
	 */
	private function test_file_management() {
		// Test listing exports.
		$exports = $this->file_handler->list_exports();
		if ( ! is_array( $exports ) ) {
			$this->log_error( 'Failed to list exports.' );
			return;
		}

		// Test file download URL generation.
		if ( ! empty( $exports ) ) {
			$first_export = reset( $exports );
			$download_url = $this->file_handler->get_download_url( $first_export['path'] );
			if ( empty( $download_url ) ) {
				$this->log_error( 'Failed to generate download URL.' );
				return;
			}
		}

		$this->log_success( 'File management tests passed.' );
	}

	/**
	 * Test cleanup functionality.
	 */
	private function test_cleanup() {
		// Test cleanup of old files.
		$result = $this->file_handler->cleanup_old_files( 0 ); // Clean all files for testing.
		if ( is_wp_error( $result ) ) {
			$this->log_error( 'Cleanup test failed: ' . $result->get_error_message() );
			return;
		}

		// Verify cleanup.
		$exports = $this->file_handler->list_exports();
		if ( ! empty( $exports ) ) {
			$this->log_error( 'Cleanup test failed: Files still exist after cleanup.' );
			return;
		}

		$this->log_success( 'Cleanup tests passed.' );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message The error message.
	 */
	private function log_error( $message ) {
		error_log( '[WP-TO-MD-TEST] ERROR: ' . $message );
		echo '<div class="notice notice-error"><p>Test Error: ' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Log a success message.
	 *
	 * @param string $message The success message.
	 */
	private function log_success( $message ) {
		error_log( '[WP-TO-MD-TEST] SUCCESS: ' . $message );
		echo '<div class="notice notice-success"><p>Test Success: ' . esc_html( $message ) . '</p></div>';
	}
} 
