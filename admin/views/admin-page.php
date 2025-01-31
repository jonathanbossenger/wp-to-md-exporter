<?php
/**
 * Admin page template.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Display any error messages.
if ( isset( $_GET['error'] ) ) {
	$error_message = sanitize_text_field( wp_unslash( $_GET['error'] ) );
	?>
	<div class="notice notice-error">
		<p><?php echo esc_html( $error_message ); ?></p>
	</div>
	<?php
}

// Display success messages.
if ( isset( $_GET['success'] ) ) {
	$success_count = absint( $_GET['success'] );
	$failed_count = isset( $_GET['failed'] ) ? absint( $_GET['failed'] ) : 0;
	$zip_file = isset( $_GET['zip_file'] ) ? sanitize_text_field( wp_unslash( $_GET['zip_file'] ) ) : '';
	?>
	<div class="notice notice-success">
		<p>
			<?php
			printf(
				/* translators: 1: Number of successful exports, 2: Number of failed exports */
				esc_html__( 'Export completed. Successfully exported %1$d posts. Failed to export %2$d posts.', 'wp-to-md' ),
				esc_html( $success_count ),
				esc_html( $failed_count )
			);
			?>
		</p>
		<?php if ( ! empty( $zip_file ) ) : ?>
			<p>
				<a href="<?php echo esc_url( $zip_file ); ?>" class="button">
					<?php esc_html_e( 'Download Export', 'wp-to-md' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Export to Markdown', 'wp-to-md' ); ?></h1>

	<div class="wp-to-md-container">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wp_to_md_export">
			<?php wp_nonce_field( 'wp_to_md_export', 'wp_to_md_nonce' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="post_type"><?php esc_html_e( 'Post Type', 'wp-to-md' ); ?></label>
					</th>
					<td>
						<select name="post_type" id="post_type">
							<?php
							$available_post_types = get_post_types( array( 'public' => true ), 'objects' );
							foreach ( $available_post_types as $type_key => $type_object ) {
								printf(
									'<option value="%s">%s</option>',
									esc_attr( $type_key ),
									esc_html( $type_object->labels->name )
								);
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="date_prefix"><?php esc_html_e( 'Add Date Prefix', 'wp-to-md' ); ?></label>
					</th>
					<td>
						<input type="checkbox" name="date_prefix" id="date_prefix" value="1">
						<span class="description">
							<?php esc_html_e( 'Add YYYY-MM-DD date prefix to filenames', 'wp-to-md' ); ?>
						</span>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="export" class="button button-primary" value="<?php esc_attr_e( 'Export to Markdown', 'wp-to-md' ); ?>">
			</p>
		</form>

		<div class="wp-to-md-previous-exports">
			<h2><?php esc_html_e( 'Previous Exports', 'wp-to-md' ); ?></h2>
			<?php
			$file_handler = new WP_To_MD_File_Handler();
			$exports = $file_handler->list_exports();
			if ( empty( $exports ) ) {
				?>
				<p class="description"><?php esc_html_e( 'No previous exports found.', 'wp-to-md' ); ?></p>
				<?php
			} else {
				?>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'wp-to-md' ); ?></th>
							<th><?php esc_html_e( 'Size', 'wp-to-md' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wp-to-md' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $exports as $export ) : ?>
							<tr>
								<td><?php echo esc_html( $export['date'] ); ?></td>
								<td><?php echo esc_html( size_format( $export['size'] ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( $export['url'] ); ?>" class="button button-small">
										<?php esc_html_e( 'Download', 'wp-to-md' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
			}
			?>
		</div>
	</div>
</div> 
