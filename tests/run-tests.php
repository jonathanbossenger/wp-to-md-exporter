<?php
/**
 * Test runner for WordPress to Markdown Exporter.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load test class.
require_once __DIR__ . '/class-wp-to-md-test.php';

// Ensure only administrators can run tests.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to run tests.', 'wp-to-md' ) );
}

// Add admin header.
require_once ABSPATH . 'wp-admin/admin-header.php';
?>

<div class="wrap">
	<h1><?php esc_html_e( 'WordPress to Markdown Exporter - Tests', 'wp-to-md' ); ?></h1>

	<div class="notice notice-info">
		<p><?php esc_html_e( 'Running tests...', 'wp-to-md' ); ?></p>
	</div>

	<?php
	// Run tests.
	$test_runner = new WP_To_MD_Test();
	$test_runner->run_tests();
	?>

	<div class="notice notice-info">
		<p><?php esc_html_e( 'Tests completed.', 'wp-to-md' ); ?></p>
	</div>
</div>

<?php
// Add admin footer.
require_once ABSPATH . 'wp-admin/admin-footer.php'; 
