<?php
/**
 * Security and error handling class.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WP_To_MD_Security
 */
class WP_To_MD_Security {

	/**
	 * Error messages container.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Verify user capabilities.
	 *
	 * @param string $capability The capability to check for (default: manage_options).
	 * @return bool True if user has capability, false otherwise.
	 */
	public function verify_capability( $capability = 'manage_options' ) {
		if ( ! current_user_can( $capability ) ) {
			$this->add_error(
				'insufficient_permissions',
				__( 'You do not have sufficient permissions to perform this action.', 'wp-to-md' )
			);
			return false;
		}
		return true;
	}

	/**
	 * Verify nonce.
	 *
	 * @param string $nonce    The nonce to verify.
	 * @param string $action   The nonce action.
	 * @param string $method   Request method (GET/POST).
	 * @param bool   $die      Whether to die on failure.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public function verify_nonce( $nonce, $action, $method = 'POST', $die = true ) {
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			$this->add_error(
				'invalid_nonce',
				__( 'Security check failed. Please try again.', 'wp-to-md' )
			);
			if ( $die ) {
				wp_die( esc_html__( 'Security check failed.', 'wp-to-md' ) );
			}
			return false;
		}
		return true;
	}

	/**
	 * Verify request method.
	 *
	 * @param string $method The expected request method.
	 * @return bool True if method matches, false otherwise.
	 */
	public function verify_request_method( $method = 'POST' ) {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( $method ) !== $_SERVER['REQUEST_METHOD'] ) {
			$this->add_error(
				'invalid_request_method',
				__( 'Invalid request method.', 'wp-to-md' )
			);
			return false;
		}
		return true;
	}

	/**
	 * Verify file path is within allowed directory.
	 *
	 * @param string $file_path The file path to verify.
	 * @param string $base_dir  The allowed base directory.
	 * @return bool True if path is valid, false otherwise.
	 */
	public function verify_file_path( $file_path, $base_dir ) {
		$real_file_path = realpath( $file_path );
		$real_base_dir = realpath( $base_dir );

		if ( false === $real_file_path || false === $real_base_dir ) {
			$this->add_error(
				'invalid_path',
				__( 'Invalid file path.', 'wp-to-md' )
			);
			return false;
		}

		if ( 0 !== strpos( $real_file_path, $real_base_dir ) ) {
			$this->add_error(
				'path_traversal',
				__( 'Invalid file path: Path traversal detected.', 'wp-to-md' )
			);
			return false;
		}

		return true;
	}

	/**
	 * Prevent timeout for long-running processes.
	 */
	public function prevent_timeout() {
		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
		}

		// Increase memory limit if possible.
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
	}

	/**
	 * Add an error message.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 */
	public function add_error( $code, $message ) {
		$this->errors[ $code ] = $message;
	}

	/**
	 * Get all error messages.
	 *
	 * @return array Array of error messages.
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Check if there are any errors.
	 *
	 * @return bool True if there are errors, false otherwise.
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}

	/**
	 * Display admin notices for errors.
	 */
	public function display_admin_notices() {
		foreach ( $this->errors as $code => $message ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( $message )
			);
		}
	}

	/**
	 * Log error message.
	 *
	 * @param string $message Error message to log.
	 * @param string $level   Error level (error, warning, info).
	 */
	public function log_error( $message, $level = 'error' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = sprintf(
			'[%s] [%s] %s',
			current_time( 'mysql' ),
			strtoupper( $level ),
			$message
		);

		error_log( $log_message );
	}

	/**
	 * Handle fatal errors.
	 */
	public function handle_fatal_error() {
		$error = error_get_last();
		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR ), true ) ) {
			$this->log_error(
				sprintf(
					'Fatal Error: %s in %s on line %d',
					$error['message'],
					$error['file'],
					$error['line']
				)
			);

			if ( wp_doing_ajax() ) {
				wp_send_json_error(
					array(
						'message' => __( 'A fatal error occurred.', 'wp-to-md' ),
					)
				);
			} else {
				wp_die(
					esc_html__( 'A fatal error occurred.', 'wp-to-md' ),
					esc_html__( 'Error', 'wp-to-md' ),
					array(
						'response'  => 500,
						'back_link' => true,
					)
				);
			}
		}
	}

	/**
	 * Register error handlers.
	 */
	public function register_error_handlers() {
		register_shutdown_function( array( $this, 'handle_fatal_error' ) );
		set_error_handler( array( $this, 'handle_error' ) );
		set_exception_handler( array( $this, 'handle_exception' ) );
	}

	/**
	 * Handle PHP errors.
	 *
	 * @param int    $errno   Error level.
	 * @param string $errstr  Error message.
	 * @param string $errfile File where the error occurred.
	 * @param int    $errline Line number where the error occurred.
	 * @return bool
	 */
	public function handle_error( $errno, $errstr, $errfile, $errline ) {
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		$this->log_error(
			sprintf(
				'PHP Error (%s): %s in %s on line %d',
				$this->get_error_type( $errno ),
				$errstr,
				$errfile,
				$errline
			)
		);

		return true;
	}

	/**
	 * Handle exceptions.
	 *
	 * @param Exception $exception The exception object.
	 */
	public function handle_exception( $exception ) {
		$this->log_error(
			sprintf(
				'Exception: %s in %s on line %d',
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine()
			)
		);

		if ( wp_doing_ajax() ) {
			wp_send_json_error(
				array(
					'message' => __( 'An error occurred.', 'wp-to-md' ),
				)
			);
		} else {
			wp_die(
				esc_html__( 'An error occurred.', 'wp-to-md' ),
				esc_html__( 'Error', 'wp-to-md' ),
				array(
					'response'  => 500,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Get error type string.
	 *
	 * @param int $type Error type constant.
	 * @return string
	 */
	private function get_error_type( $type ) {
		switch ( $type ) {
			case E_ERROR:
				return 'E_ERROR';
			case E_WARNING:
				return 'E_WARNING';
			case E_PARSE:
				return 'E_PARSE';
			case E_NOTICE:
				return 'E_NOTICE';
			case E_CORE_ERROR:
				return 'E_CORE_ERROR';
			case E_CORE_WARNING:
				return 'E_CORE_WARNING';
			case E_COMPILE_ERROR:
				return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING:
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR:
				return 'E_USER_ERROR';
			case E_USER_WARNING:
				return 'E_USER_WARNING';
			case E_USER_NOTICE:
				return 'E_USER_NOTICE';
			case E_STRICT:
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR:
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED:
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED:
				return 'E_USER_DEPRECATED';
			default:
				return 'UNKNOWN';
		}
	}
} 
