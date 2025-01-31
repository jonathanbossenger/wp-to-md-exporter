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
	 * Convert HTML content to Markdown.
	 *
	 * @param string $content The HTML content to convert.
	 * @return string The converted Markdown content.
	 */
	public function convert( $content ) {
		// Clean the content first.
		$content = $this->clean_content( $content );

		// Convert blocks to markdown.
		$content = $this->handle_blocks( $content );

		return $content;
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
		$content = preg_replace(
			'/<h([1-6])[^>]*>(.*?)<\/h\1>/i',
			function ( $matches ) {
				$level = $matches[1];
				$text = trim( strip_tags( $matches[2] ) );
				return str_repeat( '#', $level ) . ' ' . $text . "\n\n";
			},
			$content
		);

		// Convert paragraphs.
		$content = preg_replace(
			'/<p[^>]*>(.*?)<\/p>/is',
			function ( $matches ) {
				$text = trim( strip_tags( $matches[1] ) );
				return $text . "\n\n";
			},
			$content
		);

		// Convert unordered lists.
		$content = preg_replace(
			'/<ul[^>]*>(.*?)<\/ul>/is',
			function ( $matches ) {
				$list_items = preg_replace( '/<li[^>]*>(.*?)<\/li>/i', '* $1', $matches[1] );
				return $list_items . "\n";
			},
			$content
		);

		// Convert ordered lists.
		$content = preg_replace(
			'/<ol[^>]*>(.*?)<\/ol>/is',
			function ( $matches ) {
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
			},
			$content
		);

		// Convert links.
		$content = preg_replace(
			'/<a[^>]+href=([\'"])(.*?)\1[^>]*>(.*?)<\/a>/i',
			function ( $matches ) {
				$url = $matches[2];
				$text = trim( strip_tags( $matches[3] ) );
				return '[' . $text . '](' . $url . ')';
			},
			$content
		);

		// Convert images.
		$content = preg_replace(
			'/<img[^>]+src=([\'"])(.*?)\1[^>]*>/i',
			function ( $matches ) {
				$url = $matches[2];
				$alt = '';
				if ( preg_match( '/alt=([\'"])(.*?)\1/i', $matches[0], $alt_matches ) ) {
					$alt = $alt_matches[2];
				}
				return '![' . $alt . '](' . $url . ')';
			},
			$content
		);

		// Convert blockquotes.
		$content = preg_replace(
			'/<blockquote[^>]*>(.*?)<\/blockquote>/is',
			function ( $matches ) {
				$lines = explode( "\n", trim( strip_tags( $matches[1] ) ) );
				$markdown = '';
				foreach ( $lines as $line ) {
					$markdown .= '> ' . trim( $line ) . "\n";
				}
				return $markdown . "\n";
			},
			$content
		);

		// Convert code blocks.
		$content = preg_replace(
			'/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is',
			function ( $matches ) {
				$code = trim( $matches[1] );
				return "```\n" . $code . "\n```\n\n";
			},
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
