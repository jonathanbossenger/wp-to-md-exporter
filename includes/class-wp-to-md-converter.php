<?php
/**
 * HTML to Markdown converter class.
 *
 * @package WordPress_To_Markdown_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WP_To_MD_Converter
 */
class WP_To_MD_Converter {
	/**
	 * Convert HTML to Markdown.
	 *
	 * @param string $html The HTML content to convert.
	 * @return string The converted Markdown content.
	 */
	public function convert( $html ) {
		// Clean the content first.
		$html = $this->clean_content( $html );

		// Convert blocks to markdown.
		$markdown = $this->handle_blocks( $html );

		return $markdown;
	}

	/**
	 * Clean the content before conversion.
	 *
	 * @param string $content The content to clean.
	 * @return string The cleaned content.
	 */
	private function clean_content( $content ) {
		// Remove HTML comments.
		$content = preg_replace( '/<!--(.|\s)*?-->/', '', $content );

		// Remove embedded content (iframes).
		$content = preg_replace( '/<iframe.*?\/iframe>/i', '', $content );

		// Remove script tags.
		$content = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $content );

		// Remove style tags.
		$content = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $content );

		return $content;
	}

	/**
	 * Handle WordPress blocks conversion.
	 *
	 * @param string $content The content containing blocks.
	 * @return string The content with blocks converted to Markdown.
	 */
	private function handle_blocks( $content ) {
		// Convert headings.
		$content = preg_replace_callback(
			'/<h([1-6])[^>]*>(.*?)<\/h\1>/i',
			array( $this, 'convert_heading' ),
			$content
		);

		// Convert paragraphs.
		$content = preg_replace_callback(
			'/<p[^>]*>(.*?)<\/p>/is',
			array( $this, 'convert_paragraph' ),
			$content
		);

		// Convert unordered lists.
		$content = preg_replace_callback(
			'/<ul[^>]*>(.*?)<\/ul>/is',
			array( $this, 'convert_unordered_list' ),
			$content
		);

		// Convert ordered lists.
		$content = preg_replace_callback(
			'/<ol[^>]*>(.*?)<\/ol>/is',
			array( $this, 'convert_ordered_list' ),
			$content
		);

		// Convert links.
		$content = preg_replace_callback(
			'/<a[^>]+href=([\'"])(.*?)\1[^>]*>(.*?)<\/a>/i',
			array( $this, 'convert_link' ),
			$content
		);

		// Convert images.
		$content = preg_replace_callback(
			'/<img[^>]+src=([\'"])(.*?)\1[^>]*>/i',
			array( $this, 'convert_image' ),
			$content
		);

		// Convert blockquotes.
		$content = preg_replace_callback(
			'/<blockquote[^>]*>(.*?)<\/blockquote>/is',
			array( $this, 'convert_blockquote' ),
			$content
		);

		// Convert code blocks.
		$content = preg_replace_callback(
			'/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is',
			array( $this, 'convert_code_block' ),
			$content
		);

		// Convert inline code.
		$content = preg_replace( '/<code[^>]*>(.*?)<\/code>/i', '`$1`', $content );

		// Convert emphasis (italic).
		$content = preg_replace( '/<em[^>]*>(.*?)<\/em>/i', '*$1*', $content );
		$content = preg_replace( '/<i[^>]*>(.*?)<\/i>/i', '*$1*', $content );

		// Convert strong (bold).
		$content = preg_replace( '/<strong[^>]*>(.*?)<\/strong>/i', '**$1**', $content );
		$content = preg_replace( '/<b[^>]*>(.*?)<\/b>/i', '**$1**', $content );

		// Convert horizontal rules.
		$content = preg_replace( '/<hr[^>]*>/i', "\n---\n\n", $content );

		// Clean up multiple newlines.
		$content = preg_replace( "/\n{3,}/", "\n\n", $content );

		return trim( $content );
	}

	/**
	 * Convert heading to Markdown.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted heading.
	 */
	private function convert_heading( $matches ) {
		$level = $matches[1];
		$text  = trim( wp_strip_all_tags( $matches[2] ) );
		return str_repeat( '#', $level ) . ' ' . $text . "\n\n";
	}

	/**
	 * Convert paragraph to Markdown.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted paragraph.
	 */
	private function convert_paragraph( $matches ) {
		$text = trim( wp_strip_all_tags( $matches[1] ) );
		return $text . "\n\n";
	}

	/**
	 * Convert unordered list to Markdown, including nested lists.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted list.
	 */
	private function convert_unordered_list( $matches ) {
		return $this->process_list( $matches[1], '*' );
	}

	/**
	 * Convert ordered list to Markdown, including nested lists.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted list.
	 */
	private function convert_ordered_list( $matches ) {
		return $this->process_list( $matches[1], '1.' );
	}

	/**
	 * Process a list (unordered or ordered) and convert it to Markdown.
	 *
	 * @param string $content The HTML content of the list.
	 * @param string $marker  The marker for the list (* for unordered, 1. for ordered).
	 * @param int    $level   The current nesting level (used for indentation).
	 * @return string The converted Markdown list.
	 */
	private function process_list( $content, $marker, $level = 0 ) {
		$indent  = str_repeat( '  ', $level );
		$pattern = '/<li[^>]*>(.*?)<\/li>/is';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $marker, $level, $indent ) {
				$item_content = $matches[1];

				// Check for nested lists.
				$item_content = preg_replace_callback(
					'/<(ul|ol)[^>]*>(.*?)<\/\1>/is',
					function ( $nested_matches ) use ( $level ) {
						$nested_marker = 'ul' === $nested_matches[1] ? '*' : '1.';
						return "\n" . $this->process_list( $nested_matches[2], $nested_marker, $level + 1 );
					},
					$item_content
				);

				// Convert the current list item.
				$item_content = trim( wp_strip_all_tags( $item_content ) );
				return $indent . $marker . ' ' . $item_content;
			},
			$content
		);
	}

	/**
	 * Convert link to Markdown.
	 *
	 * @param array $matches The regex matches.
	 * @return string The converted link.
	 */
	private function convert_link( $matches ) {
		$url  = $matches[2];
		$text = trim( wp_strip_all_tags( $matches[3] ) );
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
		$lines    = explode( "\n", trim( wp_strip_all_tags( $matches[1] ) ) );
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
			$date     = gmdate( 'Y-m-d', strtotime( $post->post_date_gmt ) );
			$filename = $date . '-' . $filename;
		}

		return $filename . '.md';
	}
}