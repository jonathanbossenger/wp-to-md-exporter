<?php
/**
 * Exporter class.
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
	 * Initialize the class.
	 */
	public function __construct() {
		$this->converter = new WP_To_MD_Converter();
		$this->file_handler = new WP_To_MD_File_Handler();
	}

	/**
	 * Export posts to Markdown.
	 *
	 * @param string $post_type Post type to export.
	 * @param bool   $date_prefix Whether to add date prefix to filenames.
	 * @return array|WP_Error Array of results on success, WP_Error on failure.
	 */
	public function export( $post_type, $date_prefix = false ) {
		// Create export directory.
		$export_dir = $this->file_handler->create_export_directory();
		if ( is_wp_error( $export_dir ) ) {
			return $export_dir;
		}

		// Get posts.
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new WP_Query( $args );
		$posts = $query->posts;

		if ( empty( $posts ) ) {
			return new WP_Error( 'no_posts', __( 'No posts found to export.', 'wp-to-md' ) );
		}

		$results = array(
			'success' => array(),
			'failed'  => array(),
		);

		// Process each post.
		foreach ( $posts as $post ) {
			$result = $this->process_post( $post, $export_dir, $date_prefix );
			if ( is_wp_error( $result ) ) {
				$results['failed'][] = array(
					'id'      => $post->ID,
					'title'   => $post->post_title,
					'message' => $result->get_error_message(),
				);
			} else {
				$results['success'][] = array(
					'id'       => $post->ID,
					'title'    => $post->post_title,
					'filename' => $result,
				);
			}
		}

		// Create zip file.
		$zip_filename = 'wp-to-md-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.zip';
		$zip_file = trailingslashit( dirname( $export_dir ) ) . $zip_filename;
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

		// Get the URL for the zip file.
		$upload_dir = wp_upload_dir();
		$zip_url = $this->file_handler->get_download_url( str_replace( $upload_dir['basedir'], '', $zip_file ) );

		$results['zip_file'] = $zip_url;
		return $results;
	}

	/**
	 * Process a single post.
	 *
	 * @param WP_Post $post The post to process.
	 * @param string  $export_dir The export directory path.
	 * @param bool    $date_prefix Whether to add date prefix to filename.
	 * @return string|WP_Error Filename on success, WP_Error on failure.
	 */
	private function process_post( $post, $export_dir, $date_prefix ) {
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
		$filename = $this->converter->generate_filename( $post, $date_prefix );
		$file_path = trailingslashit( $export_dir ) . $filename;

		// Save file.
		$saved = file_put_contents( $file_path, $markdown );
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
}
