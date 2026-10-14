<?php
/**
 * Front cover designs.
 *
 * @package WPBook
 */

namespace WPBook\Covers;

/**
 * Lists the cover designs. Each design lives in templates/covers/{key}/ with cover.php, cover.css and thumb.png;
 * a theme can override any file by copying it to {theme}/bookpress/covers/{key}/.
 */
final class Cover_Registry {

	/**
	 * All cover designs keyed by slug.
	 *
	 * @return array<string, array{name: string, description: string, tags: string[], uses_image: bool, defaults: array}>
	 */
	public static function all() {
		$covers = array(
			'aurora'        => array(
				'name'        => __( 'Aurora', 'wp-book' ),
				'description' => __( 'Soft two-colour gradient with glowing shapes.', 'wp-book' ),
				'tags'        => array( 'gradient', 'modern' ),
				'uses_image'  => false,
				'defaults'    => array(
					'primary'    => '#3b1f6e',
					'accent'     => '#ff7a59',
					'text_color' => '#ffffff',
					'title_font' => 'playfair',
					'align'      => 'center',
				),
			),
			'minimal'       => array(
				'name'        => __( 'Minimal', 'wp-book' ),
				'description' => __( 'Plenty of white space and a single accent rule.', 'wp-book' ),
				'tags'        => array( 'clean', 'business' ),
				'uses_image'  => false,
				'defaults'    => array(
					'primary'    => '#ffffff',
					'accent'     => '#e4572e',
					'text_color' => '#1d1d1f',
					'title_font' => 'inter',
					'align'      => 'left',
				),
			),
			'classic-novel' => array(
				'name'        => __( 'Classic Novel', 'wp-book' ),
				'description' => __( 'Framed, centred serif title in the style of a clothbound book.', 'wp-book' ),
				'tags'        => array( 'fiction', 'elegant' ),
				'uses_image'  => false,
				'defaults'    => array(
					'primary'    => '#14342b',
					'accent'     => '#c9a227',
					'text_color' => '#f4ecd8',
					'title_font' => 'playfair',
					'align'      => 'center',
				),
			),
			'bold-type'     => array(
				'name'        => __( 'Bold Type', 'wp-book' ),
				'description' => __( 'Huge stacked title that fills the page.', 'wp-book' ),
				'tags'        => array( 'typographic', 'statement' ),
				'uses_image'  => false,
				'defaults'    => array(
					'primary'    => '#ffde59',
					'accent'     => '#111111',
					'text_color' => '#111111',
					'title_font' => 'bebas',
					'align'      => 'left',
				),
			),
			'geometric'     => array(
				'name'        => __( 'Geometric', 'wp-book' ),
				'description' => __( 'Overlapping circles and triangles in your brand colours.', 'wp-book' ),
				'tags'        => array( 'shapes', 'modern' ),
				'uses_image'  => false,
				'defaults'    => array(
					'primary'    => '#0f4c5c',
					'accent'     => '#e36414',
					'text_color' => '#ffffff',
					'title_font' => 'montserrat',
					'align'      => 'left',
				),
			),
			'photo'         => array(
				'name'        => __( 'Full-bleed Photo', 'wp-book' ),
				'description' => __( 'Your image edge to edge with a dark gradient behind the title.', 'wp-book' ),
				'tags'        => array( 'photo', 'travel' ),
				'uses_image'  => true,
				'defaults'    => array(
					'primary'    => '#000000',
					'accent'     => '#ffffff',
					'text_color' => '#ffffff',
					'title_font' => 'playfair',
					'align'      => 'left',
				),
			),
			'photo-frame'   => array(
				'name'        => __( 'Photo Frame', 'wp-book' ),
				'description' => __( 'A framed photo above a clean title block.', 'wp-book' ),
				'tags'        => array( 'photo', 'memoir' ),
				'uses_image'  => true,
				'defaults'    => array(
					'primary'    => '#f5efe6',
					'accent'     => '#8c5e3c',
					'text_color' => '#2b2118',
					'title_font' => 'lora',
					'align'      => 'center',
				),
			),
			'split'         => array(
				'name'        => __( 'Split', 'wp-book' ),
				'description' => __( 'Image on top, solid colour title band below.', 'wp-book' ),
				'tags'        => array( 'photo', 'business' ),
				'uses_image'  => true,
				'defaults'    => array(
					'primary'    => '#1b263b',
					'accent'     => '#4cc9f0',
					'text_color' => '#ffffff',
					'title_font' => 'montserrat',
					'align'      => 'left',
				),
			),
			'magazine'      => array(
				'name'        => __( 'Magazine', 'wp-book' ),
				'description' => __( 'Masthead title, cover lines and an issue badge.', 'wp-book' ),
				'tags'        => array( 'photo', 'editorial' ),
				'uses_image'  => true,
				'defaults'    => array(
					'primary'    => '#d62828',
					'accent'     => '#fcbf49',
					'text_color' => '#ffffff',
					'title_font' => 'bebas',
					'align'      => 'left',
				),
			),
			'tech'          => array(
				'name'        => __( 'Tech Guide', 'wp-book' ),
				'description' => __( 'Dark background with a code-style grid, for manuals and docs.', 'wp-book' ),
				'tags'        => array( 'documentation', 'technical' ),
				'uses_image'  => false,
				'defaults'    => array(
					'primary'    => '#0d1117',
					'accent'     => '#3fb950',
					'text_color' => '#e6edf3',
					'title_font' => 'mono',
					'align'      => 'left',
				),
			),
			'botanical'     => array(
				'name'        => __( 'Botanical', 'wp-book' ),
				'description' => __( 'Hand-drawn leaves framing a serif title.', 'wp-book' ),
				'tags'        => array( 'nature', 'lifestyle' ),
				'uses_image'  => false,
				'defaults'    => array(
					'primary'    => '#f1f5ec',
					'accent'     => '#3a7d44',
					'text_color' => '#1f3d24',
					'title_font' => 'lora',
					'align'      => 'center',
				),
			),
			'playful'       => array(
				'name'        => __( 'Playful', 'wp-book' ),
				'description' => __( 'Bright confetti dots and rounded type for kids and fun guides.', 'wp-book' ),
				'tags'        => array( 'kids', 'colourful' ),
				'uses_image'  => false,
				'defaults'    => array(
					'primary'    => '#ffcad4',
					'accent'     => '#3a86ff',
					'text_color' => '#22223b',
					'title_font' => 'montserrat',
					'align'      => 'left',
				),
			),
		);

		/**
		 * Filters the available cover designs.
		 *
		 * @param array $covers Designs keyed by slug.
		 */
		return apply_filters( 'wpbook_covers', $covers );
	}
}
