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

		// Initialize the tag processor.
		$processor = new WP_HTML_Tag_Processor( $html );
		$markdown = $html;

		// Convert headings.
		while ( $processor->next_tag( array( 'tag_name' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ) ) ) ) {
			$tag = $processor->get_tag();
			$level = substr( $tag, 1, 1 );
			$text = $processor->get_modifiable_text();
			$markdown = str_replace( $processor->get_modifiable_text(), str_repeat( '#', $level ) . ' ' . $text . "\n\n", $markdown );
		}

		// Convert paragraphs.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'p' ) ) ) {
			$text = $processor->get_modifiable_text();
			$markdown = str_replace( $processor->get_modifiable_text(), $text . "\n\n", $markdown );
		}

		// Convert unordered lists.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'ul' ) ) ) {
			$list_items = array();
			$list_processor = new WP_HTML_Tag_Processor( $processor->get_modifiable_text() );
			while ( $list_processor->next_tag( array( 'tag_name' => 'li' ) ) ) {
				$list_items[] = '* ' . trim( $list_processor->get_modifiable_text() );
			}
			$markdown = str_replace( $processor->get_modifiable_text(), implode( "\n", $list_items ), $markdown );
		}

		// Convert ordered lists.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'ol' ) ) ) {
			$list_items = array();
			$counter = 1;
			$list_processor = new WP_HTML_Tag_Processor( $processor->get_modifiable_text() );
			while ( $list_processor->next_tag( array( 'tag_name' => 'li' ) ) ) {
				$list_items[] = $counter . '. ' . trim( $list_processor->get_modifiable_text() );
				$counter++;
			}
			$markdown = str_replace( $processor->get_modifiable_text(), implode( "\n", $list_items ), $markdown );
		}

		// Convert links.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'a' ) ) ) {
			$url = $processor->get_attribute( 'href' );
			$text = $processor->get_modifiable_text();
			$markdown = str_replace( $processor->get_modifiable_text(), '[' . $text . '](' . $url . ')', $markdown );
		}

		// Convert images.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'img' ) ) ) {
			$src = $processor->get_attribute( 'src' );
			$alt = $processor->get_attribute( 'alt' ) ?? '';
			$markdown = str_replace( $processor->get_modifiable_text(), '![' . $alt . '](' . $src . ')', $markdown );
		}

		// Convert blockquotes.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'blockquote' ) ) ) {
			$lines = explode( "\n", trim( $processor->get_modifiable_text() ) );
			$quoted_lines = array_map( function( $line ) {
				return '> ' . trim( $line );
			}, $lines );
			$markdown = str_replace( $processor->get_modifiable_text(), implode( "\n", $quoted_lines ) . "\n\n", $markdown );
		}

		// Convert code blocks.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'pre' ) ) ) {
			$code_processor = new WP_HTML_Tag_Processor( $processor->get_modifiable_text() );
			if ( $code_processor->next_tag( array( 'tag_name' => 'code' ) ) ) {
				$code = trim( $code_processor->get_modifiable_text() );
				$markdown = str_replace( $processor->get_modifiable_text(), "```\n" . $code . "\n```\n\n", $markdown );
			}
		}

		// Convert inline code.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'code' ) ) ) {
			$code = $processor->get_modifiable_text();
			$markdown = str_replace( $processor->get_modifiable_text(), '`' . $code . '`', $markdown );
		}

		// Convert emphasis (italic).
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => array( 'em', 'i' ) ) ) ) {
			$text = $processor->get_modifiable_text();
			$markdown = str_replace( $processor->get_modifiable_text(), '*' . $text . '*', $markdown );
		}

		// Convert strong (bold).
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => array( 'strong', 'b' ) ) ) ) {
			$text = $processor->get_modifiable_text();
			$markdown = str_replace( $processor->get_modifiable_text(), '**' . $text . '**', $markdown );
		}

		// Convert horizontal rules.
		$processor = new WP_HTML_Tag_Processor( $markdown );
		while ( $processor->next_tag( array( 'tag_name' => 'hr' ) ) ) {
			$markdown = str_replace( $processor->get_modifiable_text(), "\n---\n\n", $markdown );
		}

		// Clean up multiple newlines.
		$markdown = preg_replace( "/\n{3,}/", "\n\n", $markdown );

		return trim( $markdown );
	}

	/**
	 * Clean the content before conversion.
	 *
	 * @param string $content The content to clean.
	 * @return string The cleaned content.
	 */
	private function clean_content( $content ) {
		$processor = new WP_HTML_Tag_Processor( $content );

		// Remove HTML comments.
		while ( $processor->next_token() ) {
			if ( '#comment' === $processor->get_token_type() ) {
				$content = str_replace( $processor->get_modifiable_text(), '', $content );
			}
		}

		// Remove embedded content (iframes).
		$processor = new WP_HTML_Tag_Processor( $content );
		while ( $processor->next_tag( array( 'tag_name' => 'iframe' ) ) ) {
			$content = str_replace( $processor->get_modifiable_text(), '', $content );
		}

		// Remove script tags.
		$processor = new WP_HTML_Tag_Processor( $content );
		while ( $processor->next_tag( array( 'tag_name' => 'script' ) ) ) {
			$content = str_replace( $processor->get_modifiable_text(), '', $content );
		}

		// Remove style tags.
		$processor = new WP_HTML_Tag_Processor( $content );
		while ( $processor->next_tag( array( 'tag_name' => 'style' ) ) ) {
			$content = str_replace( $processor->get_modifiable_text(), '', $content );
		}

		return $content;
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
