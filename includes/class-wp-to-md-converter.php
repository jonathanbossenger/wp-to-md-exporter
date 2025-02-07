<?php
/**
 * HTML to Markdown converter class
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class WP_To_MD_Converter
 */
class WP_To_MD_Converter {

	/**
	 * Convert post content to Markdown with metadata
	 *
	 * @param WP_Post $post The post object to convert.
	 * @return string The converted markdown content with metadata
	 */
	public function convert_post_to_markdown( $post ) {
		// Get post metadata
		$metadata = $this->get_post_metadata( $post );

		// Convert the post content to Markdown
		$markdown_content = $this->convert_content_to_markdown( $post->post_content );

		// Combine metadata and content
		return $this->combine_metadata_and_content( $metadata, $markdown_content );
	}

	/**
	 * Add metadata to converted markdown content
	 *
	 * @param WP_Post $post The post object.
	 * @param string  $markdown_content The converted markdown content.
	 * @return string The markdown content with metadata
	 */
	public function add_post_metadata( $post, $markdown_content ) {
		// Get post metadata
		$metadata = $this->get_post_metadata( $post );

		// Combine metadata and content
		return $this->combine_metadata_and_content( $metadata, $markdown_content );
	}
	/**
	 * Get post metadata formatted for YAML front matter
	 *
	 * @param WP_Post $post The post object.
	 * @return array The post metadata
	 */
	private function get_post_metadata( $post ) {
		// Get the author
		$author = get_user_by( 'ID', $post->post_author );

		// Get featured image
		$featured_image = '';
		if ( has_post_thumbnail( $post ) ) {
			$featured_image = get_the_post_thumbnail_url( $post, 'full' );
		}

		// Get categories
		$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
		$categories_string = ! empty( $categories ) ? implode( ', ', $categories ) : '';

		return array(
			'title'           => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'publish_date'    => get_the_date( 'Y-m-d H:i:s', $post ),
			'author'          => $author ? $author->display_name : '',
			'featured_image'  => $featured_image,
			'categories'      => $categories_string,
		);
	}

	/**
	 * Convert HTML content to Markdown
	 *
	 * @param string $content The HTML content to convert.
	 * @return string The converted markdown content
	 */
	private function convert_content_to_markdown( $content ) {
		// TODO: Implement actual HTML to Markdown conversion
		// For now, return stripped content as placeholder
		return wp_strip_all_tags( $content );
	}
	/**
	 * Combine metadata and content into final Markdown format
	 *
	 * @param array  $metadata The post metadata.
	 * @param string $content The markdown content.
	 * @return string The combined markdown with metadata
	 */
	private function combine_metadata_and_content( $metadata, $content ) {
		$yaml = "---\n";

		foreach ( $metadata as $key => $value ) {
			// Skip empty values
			if ( empty( $value ) ) {
				continue;
			}

			// Properly escape string values
			if ( is_string( $value ) ) {
				$value = str_replace( '"', '\"', $value );
				$value = '"' . $value . '"';
			}
			$yaml .= sprintf( "%s: %s\n", $key, $value );
		}

		$yaml .= "---\n\n";

		return $yaml . $content;
	}

	/**
	 * Convert ordered list to Markdown.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted list.
	 */
	private function convert_ordered_list( $matches ) {
		$items = preg_split( '/<li[^>]*>/i', $matches[1] );
		$markdown = '';
		$counter = 1;
		foreach ( $items as $item ) {
			if ( empty( trim( $item ) ) ) {
				continue;
		}
			$item = preg_replace( '/<\/li>/', '', $item );
			$markdown .= $counter . '. ' . trim( strip_tags( $item ) ) . "\n";
			$counter++;
	}
		return $markdown . "\n";
	}

	/**
	 * Convert link to Markdown.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted link.
	 */
	private function convert_link( $matches ) {
		$url = $matches[2];
		$text = trim( strip_tags( $matches[3] ) );
		return '[' . $text . '](' . $url . ')';
	}

	/**
	 * Convert image to Markdown.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted image.
	 */
	private function convert_image( $matches ) {
		$url = $matches[2];
		$alt = '';
		if ( preg_match( '/alt=([\'"])(.*?)\1/i', $matches[0], $alt_matches ) ) {
			$alt = $alt_matches[2];
		}
		return '![' . $alt . '](' . $url . ')';
	}

	/**
	 * Convert blockquote to Markdown.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted blockquote.
	 */
	private function convert_blockquote( $matches ) {
		$lines = explode( "\n", trim( strip_tags( $matches[1] ) ) );
		$markdown = '';
		foreach ( $lines as $line ) {
			$markdown .= '> ' . trim( $line ) . "\n";
		}
		return $markdown . "\n";
	}

	/**
	 * Convert code block to Markdown.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted code block.
	 */
	private function convert_code_block( $matches ) {
		$code = trim( $matches[1] );
		return "```\n" . $code . "\n```\n\n";
	}

	/**
	 * Generate a filename for the markdown file.
	 *
	 * @param WP_Post $post     The post object.
	 * @param bool    $add_date Whether to add date prefix.
	 * @return string The generated filename.
	 */
	public function generate_filename( $post, $add_date = false ) {
		$filename = sanitize_title( $post->post_title );

		if ( $add_date ) {
			$date = gmdate( 'Y-m-d', strtotime( $post->post_date_gmt ) );
			$filename = $date . '-' . $filename;
		}

		return $filename . '.md';
	}
}
