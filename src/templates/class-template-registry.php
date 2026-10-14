<?php
/**
 * Interior (page) templates.
 *
 * @package WPBook
 */

namespace WPBook\Templates;

/**
 * Lists the interior templates. Each lives in templates/interior/{key}/ with book.css and thumb.png.
 */
final class Template_Registry {

	/**
	 * All interior templates keyed by slug.
	 *
	 * @return array<string, array{name: string, description: string, font: string, heading_font: string}>
	 */
	public static function all() {
		$templates = array(
			'classic'  => array(
				'name'         => __( 'Classic Novel', 'wp-book' ),
				'description'  => __( 'Serif text, centred chapter openings and drop caps, like a printed novel.', 'wp-book' ),
				'font'         => 'lora',
				'heading_font' => 'playfair',
			),
			'modern'   => array(
				'name'         => __( 'Modern', 'wp-book' ),
				'description'  => __( 'Clean sans-serif with bold left-aligned headings and an accent colour.', 'wp-book' ),
				'font'         => 'inter',
				'heading_font' => 'montserrat',
			),
			'magazine' => array(
				'name'         => __( 'Magazine', 'wp-book' ),
				'description'  => __( 'Two columns, large headlines and pull-quote styling.', 'wp-book' ),
				'font'         => 'lora',
				'heading_font' => 'bebas',
			),
			'manual'   => array(
				'name'         => __( 'Manual', 'wp-book' ),
				'description'  => __( 'Numbered headings, code blocks and callouts for guides and documentation.', 'wp-book' ),
				'font'         => 'inter',
				'heading_font' => 'inter',
			),
			'minimal'  => array(
				'name'         => __( 'Minimal', 'wp-book' ),
				'description'  => __( 'Generous margins and quiet typography that lets the words breathe.', 'wp-book' ),
				'font'         => 'inter',
				'heading_font' => 'inter',
			),
			'dark'     => array(
				'name'         => __( 'Dark Reader', 'wp-book' ),
				'description'  => __( 'Light text on a dark page for comfortable reading on screens.', 'wp-book' ),
				'font'         => 'inter',
				'heading_font' => 'montserrat',
			),
		);

		/**
		 * Filters the available interior templates.
		 *
		 * @param array $templates Templates keyed by slug.
		 */
		return apply_filters( 'wpbook_templates', $templates );
	}
}
