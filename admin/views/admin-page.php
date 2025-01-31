<?php
/**
 * Admin page template.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Export to Markdown', 'wp-to-md' ); ?></h1>

	<div class="wp-to-md-container">
		<form method="post" action="">
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
							foreach ( $available_post_types as $post_type_object ) {
								printf(
									'<option value="%s">%s</option>',
									esc_attr( $post_type_object->name ),
									esc_html( $post_type_object->labels->name )
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
			<p class="description"><?php esc_html_e( 'List of previous exports will appear here.', 'wp-to-md' ); ?></p>
		</div>
	</div>
</div> 
