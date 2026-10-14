<?php
/**
 * Turns a book into an ordered list of sections and a stylesheet, independent of the PDF engine.
 *
 * @package WPBook
 */

namespace WPBook\Render;

use WPBook\Books\Book;
use WPBook\Covers\Cover_Renderer;
use WPBook\Templates\Template_Registry;

/**
 * Produces sections (cover, front matter, parts, chapters, back matter) one at a time so long books stream.
 *
 * Section shape: [ 'type' => cover|title-page|copyright|dedication|preface|toc|part|chapter|about-author|back-cover,
 *                  'html' => string, 'title' => string, 'number' => int, 'headings' => array ].
 */
final class Book_Renderer {

	const MODE_FULL   = 'full';
	const MODE_COVER  = 'cover';
	const MODE_SAMPLE = 'sample';

	/**
	 * The book.
	 *
	 * @var Book
	 */
	private $book;

	/**
	 * Render mode.
	 *
	 * @var string
	 */
	private $mode;

	/**
	 * Content filter.
	 *
	 * @var Content_Filter
	 */
	private $filter;

	/**
	 * Constructor.
	 *
	 * @param Book   $book Book.
	 * @param string $mode One of the MODE_* constants.
	 */
	public function __construct( Book $book, $mode = self::MODE_FULL ) {
		$this->book   = $book;
		$this->mode   = $mode;
		$this->filter = new Content_Filter( (bool) $book->config['interior']['images'] );
	}

	/**
	 * The book being rendered.
	 *
	 * @return Book
	 */
	public function book() {
		return $this->book;
	}

	/**
	 * Render mode.
	 *
	 * @return string
	 */
	public function mode() {
		return $this->mode;
	}

	/**
	 * Number of chapters that will be rendered (for progress reporting).
	 *
	 * @return int
	 */
	public function chapter_total() {
		$total = count( $this->book->post_ids() );
		return self::MODE_SAMPLE === $this->mode ? min( 2, $total ) : ( self::MODE_COVER === $this->mode ? 0 : $total );
	}

	/**
	 * Whether the chapter list contains part dividers (shifts TOC levels down by one).
	 *
	 * @return bool
	 */
	public function has_parts() {
		foreach ( $this->book->config['content']['chapters'] as $chapter ) {
			if ( 'part' === $chapter['type'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Interior stylesheet with the book's typography settings applied.
	 *
	 * @return string
	 */
	public function stylesheet() {
		$interior  = $this->book->config['interior'];
		$templates = Template_Registry::all();
		$key       = isset( $templates[ $interior['template'] ] ) ? $interior['template'] : 'classic';
		$template  = $templates[ $key ];
		$body_font = '' !== $interior['font'] ? $interior['font'] : $template['font'];

		$css  = Template_Loader::contents(
			'interior/base.css',
			array(
				'body_font'    => Font_Manager::css_family( $body_font ),
				'heading_font' => Font_Manager::css_family( $template['heading_font'] ),
				'mono_font'    => Font_Manager::css_family( 'mono' ),
				'font_size'    => $interior['font_size'] . 'pt',
				'line_height'  => $interior['line_height'],
				'text_align'   => $interior['justify'] ? 'justify' : 'left',
				'indent'       => $interior['indent'] ? '1.4em' : '0',
				'para_space'   => $interior['indent'] ? '0' : '0.7em',
				'accent'       => $this->book->config['cover']['accent'],
			)
		);
		$css .= Template_Loader::contents(
			'interior/' . $key . '/book.css',
			array(
				'body_font'    => Font_Manager::css_family( $body_font ),
				'heading_font' => Font_Manager::css_family( $template['heading_font'] ),
				'accent'       => $this->book->config['cover']['accent'],
				'primary'      => $this->book->config['cover']['primary'],
			)
		);
		if ( $interior['drop_caps'] ) {
			$css .= '.wpb-chapter-body > p.wpb-first:first-letter { font-size: 3.2em; float: left; line-height: 0.9; margin: 0.05em 0.08em 0 0; font-family: ' . Font_Manager::css_family( $template['heading_font'] ) . '; color: ' . $this->book->config['cover']['accent'] . '; }';
		}
		return $css;
	}

	/**
	 * Sections in reading order.
	 *
	 * @param callable|null $progress Called with (done, total) after each chapter.
	 * @return \Generator
	 */
	public function sections( $progress = null ) {
		$config    = $this->book->config;
		$structure = $config['structure'];

		if ( $config['cover']['enabled'] ) {
			yield array_merge( array( 'type' => 'cover' ), Cover_Renderer::front( $this->book ) );
		}
		if ( self::MODE_COVER === $this->mode ) {
			if ( $config['back_cover']['enabled'] ) {
				yield array_merge( array( 'type' => 'back-cover' ), Cover_Renderer::back( $this->book ) );
			}
			return;
		}

		$vars = array(
			'book'   => $this->book,
			'config' => $config,
		);
		if ( $structure['title_page'] ) {
			yield array(
				'type' => 'title-page',
				'html' => Template_Loader::render( 'front/title-page.php', $vars ),
			);
		}
		if ( $structure['copyright'] ) {
			yield array(
				'type' => 'copyright',
				'html' => Template_Loader::render( 'front/copyright.php', $vars ),
			);
		}
		if ( '' !== trim( $structure['dedication'] ) ) {
			yield array(
				'type' => 'dedication',
				'html' => Template_Loader::render( 'front/dedication.php', $vars ),
			);
		}
		if ( '' !== trim( wp_strip_all_tags( $structure['preface'] ) ) ) {
			yield array(
				'type'  => 'preface',
				'title' => __( 'Preface', 'wp-book' ),
				'html'  => Template_Loader::render( 'front/preface.php', $vars + array( 'preface_html' => $this->filter->clean( $structure['preface'], 'preface' ) ) ),
			);
		}
		if ( $structure['toc'] ) {
			yield array(
				'type'  => 'toc',
				'title' => '' !== $structure['toc_title'] ? $structure['toc_title'] : __( 'Contents', 'wp-book' ),
			);
		}

		$total  = $this->chapter_total();
		$done   = 0;
		$number = 0;
		foreach ( $config['content']['chapters'] as $chapter ) {
			if ( $done >= $total ) {
				break;
			}
			if ( 'part' === $chapter['type'] ) {
				yield array(
					'type'  => 'part',
					'title' => $chapter['title'],
					'html'  => Template_Loader::render( 'parts/part.php', $vars + array( 'part' => $chapter ) ),
				);
				continue;
			}
			$post = get_post( $chapter['id'] );
			// Skip missing posts and posts this user cannot read (the job runs as the user who started it).
			if ( ! $post || ! current_user_can( 'read_post', $post->ID ) ) {
				++$done;
				continue;
			}
			++$number;
			$anchor = 'wpb-ch' . $number;
			$body   = $this->filter->post_html( $post, $anchor );
			$title  = '' !== $chapter['title'] ? $chapter['title'] : get_the_title( $post );
			yield array(
				'type'     => 'chapter',
				'number'   => $number,
				'title'    => wp_strip_all_tags( $title ),
				'anchor'   => $anchor,
				'headings' => $this->filter->headings,
				'html'     => Template_Loader::render(
					'parts/chapter.php',
					$vars + array(
						'post'      => $post,
						'chapter'   => $chapter,
						'number'    => $number,
						'title'     => $title,
						'body_html' => $this->mark_first_paragraph( $body ),
					)
				),
			);
			++$done;
			if ( $progress ) {
				call_user_func( $progress, $done, $total );
			}
		}

		if ( self::MODE_SAMPLE === $this->mode ) {
			return;
		}
		if ( $structure['about_author'] && '' !== trim( wp_strip_all_tags( $structure['about_author_text'] ) ) ) {
			yield array(
				'type'  => 'about-author',
				'title' => __( 'About the Author', 'wp-book' ),
				'html'  => Template_Loader::render( 'back/about-author.php', $vars + array( 'about_html' => $this->filter->clean( $structure['about_author_text'], 'about' ) ) ),
			);
		}
		if ( $config['back_cover']['enabled'] ) {
			yield array_merge( array( 'type' => 'back-cover' ), Cover_Renderer::back( $this->book ) );
		}
	}

	/**
	 * Tag the first paragraph of a chapter (for drop caps and no indent).
	 *
	 * @param string $html Chapter body.
	 * @return string
	 */
	private function mark_first_paragraph( $html ) {
		return preg_replace_callback(
			'/<p(\s[^>]*)?>/',
			static function ( $m ) {
				$attrs = isset( $m[1] ) ? $m[1] : '';
				if ( preg_match( '/\sclass="([^"]*)"/', $attrs ) ) {
					return '<p' . preg_replace( '/\sclass="([^"]*)"/', ' class="$1 wpb-first"', $attrs, 1 ) . '>';
				}
				return '<p class="wpb-first"' . $attrs . '>';
			},
			$html,
			1
		);
	}
}
