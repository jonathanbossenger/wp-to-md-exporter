<?php
/**
 * File Handler class.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WP_To_MD_File_Handler
 */
class WP_To_MD_File_Handler {

	/**
	 * The uploads base directory for our exports.
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * WordPress Filesystem API instance.
	 *
	 * @var WP_Filesystem_Base
	 */
	private $filesystem;

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->base_dir = trailingslashit( $upload_dir['basedir'] ) . 'wp-to-md-exports';
		$this->init_filesystem();
	}

	/**
	 * Initialize WordPress Filesystem API.
	 *
	 * @return bool True if filesystem initialized successfully.
	 */
	private function init_filesystem() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			return false;
		}

		global $wp_filesystem;
		$this->filesystem = $wp_filesystem;

		return true;
	}

	/**
	 * Create the exports directory if it doesn't exist.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function create_exports_directory() {
		if ( ! $this->filesystem ) {
			return new WP_Error( 'filesystem_error', __( 'WordPress Filesystem API not initialized.', 'wp-to-md' ) );
		}

		// First check if uploads directory exists and is writable.
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'uploads_dir_error', $upload_dir['error'] );
		}

		if ( ! $this->filesystem->is_writable( $upload_dir['basedir'] ) ) {
			return new WP_Error(
				'uploads_not_writable',
				sprintf(
					/* translators: %s: Directory path */
					__( 'Uploads directory is not writable: %s', 'wp-to-md' ),
					$upload_dir['basedir']
				)
			);
		}

		// Create base exports directory if it doesn't exist.
		if ( ! $this->filesystem->is_dir( $this->base_dir ) ) {
			if ( ! wp_mkdir_p( $this->base_dir ) ) {
				return new WP_Error(
					'directory_creation_failed',
					sprintf(
						/* translators: %s: Directory path */
						__( 'Failed to create directory: %s', 'wp-to-md' ),
						$this->base_dir
					)
				);
			}

			// Create an index.php file to prevent directory listing.
			$this->filesystem->put_contents(
				trailingslashit( $this->base_dir ) . 'index.php',
				'<?php // Silence is golden.'
			);
		}

		return true;
	}

	/**
	 * Create a new export directory for the current export.
	 *
	 * @return string|WP_Error Directory path on success, WP_Error on failure.
	 */
	public function create_export_directory() {
		// First ensure base exports directory exists.
		$result = $this->create_exports_directory();
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$date_dir = gmdate( 'Y/m/d' );
		$export_dir = trailingslashit( $this->base_dir ) . $date_dir;

		if ( ! $this->filesystem->is_dir( $export_dir ) ) {
			if ( ! wp_mkdir_p( $export_dir ) ) {
				return new WP_Error(
					'export_directory_creation_failed',
					sprintf(
						/* translators: %s: Directory path */
						__( 'Failed to create export directory: %s', 'wp-to-md' ),
						$export_dir
					)
				);
			}
		}

		return $export_dir;
	}

	/**
	 * List all available exports.
	 *
	 * @return array|WP_Error Array of export details on success, WP_Error on failure.
	 */
	public function list_exports() {
		if ( ! $this->filesystem->is_dir( $this->base_dir ) ) {
			return array();
		}

		$exports = array();
		$year_dirs = $this->filesystem->dirlist( $this->base_dir );

		if ( ! $year_dirs ) {
			return array();
		}

		foreach ( $year_dirs as $year => $year_data ) {
			if ( 'd' !== $year_data['type'] || 'index.php' === $year ) {
				continue;
			}

			$year_path = trailingslashit( $this->base_dir ) . $year;
			$month_dirs = $this->filesystem->dirlist( $year_path );

			if ( ! $month_dirs ) {
				continue;
			}

			foreach ( $month_dirs as $month => $month_data ) {
				if ( 'd' !== $month_data['type'] ) {
					continue;
				}

				$month_path = trailingslashit( $year_path ) . $month;
				$day_dirs = $this->filesystem->dirlist( $month_path );

				if ( ! $day_dirs ) {
					continue;
				}

				foreach ( $day_dirs as $day => $day_data ) {
					if ( 'd' !== $day_data['type'] ) {
						continue;
					}

					$day_path = trailingslashit( $month_path ) . $day;
					$zip_file = dirname( $day_path ) . '/export.zip';

					if ( $this->filesystem->exists( $zip_file ) ) {
						$date = "$year-$month-$day";
						$exports[] = array(
							'date'      => $date,
							'timestamp' => strtotime( $date ),
							'size'      => $this->filesystem->size( $zip_file ),
							'path'      => $zip_file,
							'url'       => $this->get_download_url( $zip_file ),
						);
					}
				}
			}
		}

		// Sort exports by date, newest first.
		usort(
			$exports,
			function ( $a, $b ) {
				return $b['timestamp'] - $a['timestamp'];
			}
		);

		return $exports;
	}

	/**
	 * Get the download URL for an export file.
	 *
	 * @param string $file_path The file path relative to the uploads directory.
	 * @return string The download URL.
	 */
	public function get_download_url( $file_path ) {
		$upload_dir = wp_upload_dir();
		$relative_path = str_replace( $upload_dir['basedir'], '', $file_path );
		return add_query_arg(
			array(
				'action'   => 'wp_to_md_download',
				'file'     => base64_encode( $relative_path ),
				'nonce'    => wp_create_nonce( 'wp_to_md_download_' . $relative_path ),
			),
			admin_url( 'admin-post.php' )
		);
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

		if ( ! $this->filesystem->exists( $file ) ) {
			wp_die( esc_html__( 'File not found.', 'wp-to-md' ) );
		}

		$filename = basename( $file );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . $this->filesystem->size( $file ) );
		header( 'Pragma: public' );

		$this->filesystem->get_contents( $file );
		exit;
	}

	/**
	 * Clean up old export files.
	 *
	 * @param int $days Number of days to keep files (default: 30).
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function cleanup_old_files( $days = 30 ) {
		if ( ! $this->filesystem->is_dir( $this->base_dir ) ) {
			return true;
		}

		$cutoff = time() - ( $days * DAY_IN_SECONDS );
		$year_dirs = $this->filesystem->dirlist( $this->base_dir );

		if ( ! $year_dirs ) {
			return true;
		}

		foreach ( $year_dirs as $year => $year_data ) {
			if ( 'd' !== $year_data['type'] || 'index.php' === $year ) {
				continue;
			}

			$year_path = trailingslashit( $this->base_dir ) . $year;
			$month_dirs = $this->filesystem->dirlist( $year_path );

			if ( ! $month_dirs ) {
				continue;
			}

			foreach ( $month_dirs as $month => $month_data ) {
				if ( 'd' !== $month_data['type'] ) {
					continue;
				}

				$month_path = trailingslashit( $year_path ) . $month;
				$day_dirs = $this->filesystem->dirlist( $month_path );

				if ( ! $day_dirs ) {
					continue;
				}

				foreach ( $day_dirs as $day => $day_data ) {
					if ( 'd' !== $day_data['type'] ) {
						continue;
					}

					$day_path = trailingslashit( $month_path ) . $day;
					$timestamp = strtotime( "$year-$month-$day" );

					if ( $timestamp && $timestamp < $cutoff ) {
						$this->filesystem->delete( $day_path, true );
					}
				}

				// Clean up empty month directories.
				if ( ! $this->filesystem->dirlist( $month_path ) ) {
					$this->filesystem->delete( $month_path, true );
				}
			}

			// Clean up empty year directories.
			if ( ! $this->filesystem->dirlist( $year_path ) ) {
				$this->filesystem->delete( $year_path, true );
			}
		}

		return true;
	}

	/**
	 * Get the base directory path.
	 *
	 * @return string
	 */
	public function get_base_dir() {
		return $this->base_dir;
	}
}
