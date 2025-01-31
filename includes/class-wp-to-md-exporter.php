<?php
/**
 * Export handler class.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WP_To_MD_Exporter
 */
class WP_To_MD_Exporter {

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
	 * The batch size for processing posts.
	 *
	 * @var int
	 */
	private $batch_size = 10;

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->converter = new WP_To_MD_Converter();
		$this->file_handler = new WP_To_MD_File_Handler();
	}

	/**
	 * Export posts to Markdown.
	 *
	 * @param string $post_type The post type to export.
	 * @param bool   $add_date  Whether to add date prefix to filenames.
	 * @return array|WP_Error Array of results on success, WP_Error on failure.
	 */
	public function export( $post_type, $add_date = false ) {
		// Create export directory.
		$export_dir = $this->file_handler->create_export_directory();
		if ( is_wp_error( $export_dir ) ) {
			return $export_dir;
		}

		// Get WordPress filesystem.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Initialize log file.
		$log_file = trailingslashit( $export_dir ) . 'export-log.txt';
		$wp_filesystem->put_contents( $log_file, "Export Log\n==========\n\n" );

		// Get total number of posts.
		$total_posts = wp_count_posts( $post_type )->publish;
		if ( $total_posts < 1 ) {
			return new WP_Error( 'no_posts', __( 'No published posts found for the selected post type.', 'wp-to-md' ) );
		}

		$offset = 0;
		$processed = 0;
		$success_count = 0;
		$failed_count = 0;
		$results = array(
			'success' => array(),
			'failed'  => array(),
		);

		// Process posts in batches.
		while ( $offset < $total_posts ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => $this->batch_size,
					'offset'         => $offset,
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);

			foreach ( $posts as $post ) {
				$result = $this->process_post( $post, $export_dir, $add_date );
				if ( is_wp_error( $result ) ) {
					$failed_count++;
					$results['failed'][] = array(
						'id'      => $post->ID,
						'title'   => $post->post_title,
						'message' => $result->get_error_message(),
					);
					$this->log_error( $log_file, $post, $result->get_error_message() );
				} else {
					$success_count++;
					$results['success'][] = array(
						'id'       => $post->ID,
						'title'    => $post->post_title,
						'filename' => $result,
					);
					$this->log_success( $log_file, $post, $result );
				}

				$processed++;
				$progress = round( ( $processed / $total_posts ) * 100 );
				$this->update_progress( $progress );
			}

			$offset += $this->batch_size;
		}

		// Create zip file.
		$zip_result = $this->create_zip_archive( $export_dir );
		if ( is_wp_error( $zip_result ) ) {
			return $zip_result;
		}

		$results['summary'] = array(
			'total'    => $total_posts,
			'success'  => $success_count,
			'failed'   => $failed_count,
			'zip_file' => $zip_result,
		);

		return $results;
	}

	/**
	 * Process a single post.
	 *
	 * @param WP_Post $post       The post to process.
	 * @param string  $export_dir The export directory path.
	 * @param bool    $add_date   Whether to add date prefix to filename.
	 * @return string|WP_Error Filename on success, WP_Error on failure.
	 */
	private function process_post( $post, $export_dir, $add_date ) {
		global $wp_filesystem;

		// Convert content to Markdown.
		$markdown = $this->converter->convert( $post->post_content );
		if ( empty( $markdown ) ) {
			return new WP_Error(
				'empty_content',
				sprintf(
					/* translators: %s: Post title */
					__( 'No content to convert for post: %s', 'wp-to-md' ),
					$post->post_title
				)
			);
		}

		// Generate filename.
		$filename = $this->converter->generate_filename( $post, $add_date );
		$file_path = trailingslashit( $export_dir ) . $filename;

		// Save file.
		$saved = $wp_filesystem->put_contents( $file_path, $markdown );
		if ( false === $saved ) {
			return new WP_Error(
				'save_failed',
				sprintf(
					/* translators: %s: Post title */
					__( 'Failed to save Markdown file for post: %s', 'wp-to-md' ),
					$post->post_title
				)
			);
		}

		return $filename;
	}

	/**
	 * Create a zip archive of the exported files.
	 *
	 * @param string $export_dir The export directory path.
	 * @return string|WP_Error Path to zip file on success, WP_Error on failure.
	 */
	private function create_zip_archive( $export_dir ) {
		$zip_file = trailingslashit( dirname( $export_dir ) ) . 'export.zip';
		$zip = new ZipArchive();

		if ( true !== $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return new WP_Error( 'zip_creation_failed', __( 'Failed to create zip archive.', 'wp-to-md' ) );
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $export_dir ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $file ) {
			if ( ! $file->isDir() ) {
				$file_path = $file->getRealPath();
				$relative_path = substr( $file_path, strlen( $export_dir ) + 1 );

				$zip->addFile( $file_path, $relative_path );
			}
		}

		$zip->close();

		if ( ! file_exists( $zip_file ) ) {
			return new WP_Error( 'zip_not_created', __( 'Zip archive was not created.', 'wp-to-md' ) );
		}

		return $zip_file;
	}

	/**
	 * Log an error to the log file.
	 *
	 * @param string  $log_file The log file path.
	 * @param WP_Post $post     The post that failed.
	 * @param string  $message  The error message.
	 */
	private function log_error( $log_file, $post, $message ) {
		global $wp_filesystem;
		$log_entry = sprintf(
			"[ERROR] Post ID: %d, Title: %s\nMessage: %s\n\n",
			$post->ID,
			$post->post_title,
			$message
		);
		$wp_filesystem->put_contents( $log_file, $log_entry, FILE_APPEND );
	}

	/**
	 * Log a success to the log file.
	 *
	 * @param string  $log_file The log file path.
	 * @param WP_Post $post     The post that was processed.
	 * @param string  $filename The generated filename.
	 */
	private function log_success( $log_file, $post, $filename ) {
		global $wp_filesystem;
		$log_entry = sprintf(
			"[SUCCESS] Post ID: %d, Title: %s\nFilename: %s\n\n",
			$post->ID,
			$post->post_title,
			$filename
		);
		$wp_filesystem->put_contents( $log_file, $log_entry, FILE_APPEND );
	}

	/**
	 * Update the export progress.
	 *
	 * @param int $progress The progress percentage.
	 */
	private function update_progress( $progress ) {
		update_option( 'wp_to_md_export_progress', $progress );
	}
} 
