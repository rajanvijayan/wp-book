<?php
/**
 * Lays out rendered sections with mPDF.
 *
 * @package WPBook
 */

namespace WPBook\Render;

use WPBook\Books\Book;
use WPBook\Books\Page_Sizes;
use WPBook\Templates\Template_Registry;
use WPBook\Vendor\Mpdf\HTMLParserMode;
use WPBook\Vendor\Mpdf\Mpdf;
use WPBook\Vendor\Mpdf\Output\Destination;

/**
 * PDF engine based on mPDF 8.
 *
 * Page model:
 * - Covers use the named page "wpbcover" (no margins, no header/footer).
 * - Front matter has no header or footer.
 * - The TOC (if any) gets roman page numbers; the first chapter restarts at 1.
 * - Each chapter defines its own running header, hidden on the chapter's opening page.
 */
final class Mpdf_Engine implements Pdf_Engine {

	/**
	 * The mPDF instance.
	 *
	 * @var Mpdf
	 */
	private $mpdf;

	/**
	 * Book being rendered.
	 *
	 * @var Book
	 */
	private $book;

	/**
	 * Whether the main matter (numbered pages) has started.
	 *
	 * @var bool
	 */
	private $main_started = false;

	/**
	 * Main section counter; section k uses the named page wpbm{k}.
	 *
	 * @var int
	 */
	private $main_index = 0;

	/**
	 * Index of a main section whose first page was already opened by the TOC page break.
	 *
	 * @var int
	 */
	private $opened_by_toc = 0;

	/**
	 * TOC level of chapters (0, or 1 when parts exist).
	 *
	 * @var int
	 */
	private $chapter_level = 0;

	/**
	 * Render a book to a PDF file.
	 *
	 * @param Book_Renderer $renderer Renderer.
	 * @param string        $path     Destination file.
	 * @param callable|null $progress Progress callback (done, total).
	 * @return array{pages: int}
	 */
	public function render( Book_Renderer $renderer, $path, $progress = null ) {
		$this->book          = $renderer->book();
		$this->chapter_level = $renderer->has_parts() ? 1 : 0;
		$this->mpdf          = new Mpdf( $this->config() );
		$this->set_metadata();
		$this->mpdf->WriteHTML( $this->base_css() . $renderer->stylesheet(), HTMLParserMode::HEADER_CSS );

		// One-section lookahead: the TOC page break opens the first page of the section after it.
		$current = null;
		foreach ( $renderer->sections( $progress ) as $section ) {
			if ( null !== $current ) {
				$this->write_section( $current, $section );
			}
			$current = $section;
		}
		if ( null !== $current ) {
			$this->write_section( $current, null );
		}

		if ( 0 === $this->mpdf->page ) {
			$this->mpdf->WriteHTML( '<p></p>' );
		}
		$this->mpdf->Output( $path, Destination::FILE );
		return array( 'pages' => (int) $this->mpdf->page );
	}

	/**
	 * Build the mPDF configuration from the book layout.
	 *
	 * @return array
	 */
	private function config() {
		$layout = $this->book->config['layout'];
		$size   = Page_Sizes::for_layout( $layout );
		$config = array(
			'mode'                       => 'utf-8',
			'format'                     => $size,
			'orientation'                => 'P',
			'tempDir'                    => self::temp_dir(),
			'margin_left'                => $layout['margin_inner'],
			'margin_right'               => $layout['margin_outer'],
			'margin_top'                 => $layout['margin_top'],
			'margin_bottom'              => $layout['margin_bottom'],
			'margin_header'              => max( 4, $layout['margin_top'] / 2 - 2 ),
			'margin_footer'              => max( 4, $layout['margin_bottom'] / 2 - 3 ),
			'mirrorMargins'              => $layout['mirror'] ? 1 : 0,
			'autoScriptToLang'           => true,
			'autoLangToFont'             => true,
			'useSubstitutions'           => false,
			'default_font'               => 'dejavuserif',
			'img_dpi'                    => 150,
			// Only local files; Content_Filter already maps images to paths in the uploads folder.
			'whitelistStreamWrappers'    => array( 'file' ),
			'curlAllowUnsafeSslRequests' => false,
		);
		return Font_Manager::mpdf_config( $config );
	}

	/**
	 * Writable folder for mPDF caches.
	 *
	 * @return string
	 */
	public static function temp_dir() {
		$dir = trailingslashit( get_temp_dir() ) . 'wp-book-mpdf';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return $dir;
	}

	/**
	 * Create an empty temporary file for a PDF. wp_tempnam() is not available in REST and cron requests.
	 *
	 * @param string $prefix File name prefix.
	 * @return string Path.
	 */
	public static function temp_file( $prefix = 'wpbook' ) {
		$path = tempnam( self::temp_dir(), $prefix );
		if ( false === $path ) {
			$path = trailingslashit( self::temp_dir() ) . $prefix . '-' . wp_generate_password( 12, false ) . '.pdf';
		}
		return $path;
	}

	/**
	 * Title, author, watermark and password.
	 */
	private function set_metadata() {
		$output = $this->book->config['output'];
		$this->mpdf->SetTitle( $this->book->cover_title() );
		$this->mpdf->SetAuthor( $this->book->author_name() );
		$this->mpdf->SetCreator( 'BookPress' );
		if ( '' !== $output['subject'] ) {
			$this->mpdf->SetSubject( $output['subject'] );
		}
		if ( '' !== $output['keywords'] ) {
			$this->mpdf->SetKeywords( $output['keywords'] );
		}
		if ( '' !== $output['watermark'] ) {
			$this->mpdf->SetWatermarkText( $output['watermark'], 0.07 );
			$this->mpdf->showWatermarkText = true;
		}
		if ( '' !== $output['password'] ) {
			$this->mpdf->SetProtection( array( 'print', 'print-highres', 'copy' ), $output['password'], wp_generate_password( 32, true, true ) );
		}
	}

	/**
	 * Dispatch a section to its writer.
	 *
	 * @param array      $section Section.
	 * @param array|null $next    Following section, if any.
	 */
	private function write_section( array $section, $next ) {
		switch ( $section['type'] ) {
			case 'cover':
			case 'back-cover':
				$this->section_cover( $section );
				break;
			case 'toc':
				$this->section_toc( $section, $next );
				break;
			case 'part':
			case 'chapter':
			case 'about-author':
				$this->section_main( $section );
				break;
			default:
				$this->section_front( $section );
		}
	}

	/**
	 * Page rules shared by every template, plus blank and footer page regions.
	 *
	 * @return string
	 */
	private function base_css() {
		return '@page wpbcover { margin: 0; margin-header: 0; margin-footer: 0; odd-header-name: html_wpbblank; even-header-name: html_wpbblank; odd-footer-name: html_wpbblank; even-footer-name: html_wpbblank; }'
			. '@page wpbfront { odd-header-name: html_wpbblank; even-header-name: html_wpbblank; odd-footer-name: html_wpbblank; even-footer-name: html_wpbblank;' . $this->page_background() . ' }';
	}

	/**
	 * Background colour declaration for content pages, from the interior template.
	 *
	 * @return string
	 */
	private function page_background() {
		$template = Template_Registry::get( $this->book->config['interior']['template'] );
		return '' !== $template['page_background'] ? ' background-color: ' . $template['page_background'] . ';' : '';
	}

	/**
	 * Header/footer regions every page can point at; written once before the first page.
	 *
	 * @return string
	 */
	private function base_regions() {
		$layout = $this->book->config['layout'];
		$number = in_array( $layout['page_numbers'], array( 'none', 'top-outer' ), true ) ? '' : '{PAGENO}';
		$text   = esc_html( $layout['footer_text'] );
		if ( '' === $number && '' === $text ) {
			$odd  = '';
			$even = '';
		} elseif ( 'bottom-center' === $layout['page_numbers'] || '' === $number ) {
			$odd  = '<table class="wpb-footer"><tr><td class="wpb-f-center">' . $text . ( '' !== $text && '' !== $number ? ' · ' : '' ) . $number . '</td></tr></table>';
			$even = $odd;
		} else {
			$odd  = '<table class="wpb-footer"><tr><td class="wpb-f-left">' . $text . '</td><td class="wpb-f-right">' . $number . '</td></tr></table>';
			$even = $layout['mirror'] ? '<table class="wpb-footer"><tr><td class="wpb-f-left">' . $number . '</td><td class="wpb-f-right">' . $text . '</td></tr></table>' : $odd;
		}
		return '<htmlpageheader name="wpbblank"><div></div></htmlpageheader>'
			. '<htmlpagefooter name="wpbblank"><div></div></htmlpagefooter>'
			. '<htmlpagefooter name="wpbfo">' . ( '' !== $odd ? $odd : '<div></div>' ) . '</htmlpagefooter>'
			. '<htmlpagefooter name="wpbfe">' . ( '' !== $even ? $even : '<div></div>' ) . '</htmlpagefooter>';
	}

	/**
	 * Full-bleed front or back cover.
	 *
	 * @param array $section Section.
	 */
	private function section_cover( array $section ) {
		$is_back = 'back-cover' === $section['type'];
		if ( 0 === $this->mpdf->page ) {
			$this->mpdf->WriteHTML( $this->base_regions() );
		}
		$this->write_section_css( $section );
		$this->mpdf->AddPageByArray(
			array(
				'type'         => $is_back && $this->book->config['layout']['mirror'] ? 'NEXT-EVEN' : '',
				'pageselector' => 'wpbcover',
			)
		);
		list( , $height ) = Page_Sizes::for_layout( $this->book->config['layout'] );
		// The design is absolutely positioned; this full-height block gives the page real content so mPDF
		// does not skip the following page break.
		$this->mpdf->WriteHTML(
			( $is_back ? '' : $this->bookmark( __( 'Cover', 'wp-book' ), 0 ) )
			. $section['html']
			. '<div style="height: ' . ( $height - 1 ) . 'mm; margin: 0; padding: 0;"></div>'
		);
	}

	/**
	 * Front matter page (title page, copyright, dedication, preface): no header or footer.
	 *
	 * @param array $section Section.
	 */
	private function section_front( array $section ) {
		if ( 0 === $this->mpdf->page ) {
			$this->mpdf->WriteHTML( $this->base_regions() );
		}
		$this->mpdf->AddPageByArray( array( 'pageselector' => 'wpbfront' ) );
		$html = $section['html'];
		if ( ! empty( $section['title'] ) ) {
			$html = $this->bookmark( $section['title'], 0 ) . $html;
		}
		$this->mpdf->WriteHTML( $html );
	}

	/**
	 * Table of contents; mPDF fills it in at the end. Its page break also opens the next section's first page.
	 *
	 * @param array      $section Section.
	 * @param array|null $next    Following section.
	 */
	private function section_toc( array $section, $next ) {
		if ( 0 === $this->mpdf->page ) {
			$this->mpdf->WriteHTML( $this->base_regions() );
		}
		$this->mpdf->WriteHTML( '@page wpbtoc { odd-header-name: html_wpbblank; even-header-name: html_wpbblank; odd-footer-name: html_wpbblank; even-footer-name: html_wpbblank;' . $this->page_background() . ' }', HTMLParserMode::HEADER_CSS );

		$args = array(
			'links'            => 1,
			'toc-preHTML'      => '<h1 class="wpb-toc-title">' . esc_html( $section['title'] ) . '</h1>',
			'toc-bookmarkText' => $this->bookmarks() ? $section['title'] : '',
			'toc-pageselector' => 'wpbtoc',
			'toc-resetpagenum' => 1,
			'toc-pagenumstyle' => 'i',
			'resetpagenum'     => 1,
			'pagenumstyle'     => '1',
			'type'             => $this->book->config['layout']['chapter_recto'] ? 'NEXT-ODD' : '',
		);
		if ( $next && in_array( $next['type'], array( 'part', 'chapter', 'about-author' ), true ) ) {
			$selector             = $this->define_main_section( $next );
			$args['pageselector'] = $selector;
			$this->opened_by_toc  = $this->main_index;
			$this->main_started   = true;
		}
		$this->mpdf->TOCpagebreakByArray( $args );
	}

	/**
	 * Part, chapter or about-the-author: its own named page with running header and footer.
	 *
	 * @param array $section Section.
	 */
	private function section_main( array $section ) {
		if ( $this->opened_by_toc ) {
			// The TOC page break already defined this section and opened its first page.
			$this->opened_by_toc = 0;
		} else {
			$selector = $this->define_main_section( $section );
			$args     = array(
				'type'         => $this->book->config['layout']['chapter_recto'] ? 'NEXT-ODD' : '',
				'pageselector' => $selector,
			);
			if ( ! $this->main_started ) {
				$args['resetpagenum'] = 1;
				$args['pagenumstyle'] = '1';
				$this->main_started   = true;
			}
			$this->mpdf->AddPageByArray( $args );
		}

		$title = wp_strip_all_tags( isset( $section['title'] ) ? $section['title'] : '' );
		if ( 'chapter' === $section['type'] ) {
			$structure = $this->book->config['structure'];
			$entry     = ( $structure['chapter_numbers'] ? $this->chapter_label( $section['number'] ) . ': ' : '' ) . $title;
			$html      = $this->toc_entry( $entry, $this->chapter_level ) . $this->bookmark( $entry, $this->chapter_level );
			$body      = $section['html'];
			if ( $structure['toc'] || $this->bookmarks() ) {
				$body = $this->mark_headings( $body, $section['headings'] );
			}
			$columns = (int) $this->book->config['layout']['columns'];
			if ( $columns > 1 ) {
				// Chapter templates wrap the body in <div class="wpb-chapter-body">; columns start there.
				$body = preg_replace( '/(<div class="wpb-chapter-body"[^>]*>)/', '$1<columns column-count="' . $columns . '" vAlign="J" column-gap="7" />', $body, 1 ) . '<columns column-count="1" />';
			}
			$this->mpdf->WriteHTML( $html . $body );
			return;
		}
		$this->mpdf->WriteHTML( $this->toc_entry( $title, 0 ) . $this->bookmark( $title, 0 ) . $section['html'] );
	}

	/**
	 * Write the header regions and @page rules for the next main section and return its page selector.
	 *
	 * @param array $section Part, chapter or about-the-author section.
	 * @return string
	 */
	private function define_main_section( array $section ) {
		++$this->main_index;
		$k      = $this->main_index;
		$name   = 'wpbm' . $k;
		$header = 'chapter' === $section['type'] ? $this->running_header( $section ) : array( '', '' );
		$odd    = '' !== $header[0] ? 'html_wpbh' . $k . 'o' : 'html_wpbblank';
		$even   = '' !== $header[1] ? 'html_wpbh' . $k . 'e' : 'html_wpbblank';

		$regions = '';
		if ( '' !== $header[0] ) {
			$regions .= '<htmlpageheader name="wpbh' . $k . 'o">' . $header[0] . '</htmlpageheader>';
		}
		if ( '' !== $header[1] ) {
			$regions .= '<htmlpageheader name="wpbh' . $k . 'e">' . $header[1] . '</htmlpageheader>';
		}
		if ( '' !== $regions ) {
			$this->mpdf->WriteHTML( $regions );
		}
		$this->mpdf->WriteHTML(
			"@page {$name} { odd-header-name: {$odd}; even-header-name: {$even}; odd-footer-name: html_wpbfo; even-footer-name: html_wpbfe;" . $this->page_background() . ' }'
			// mPDF merges named :first rules after resolving odd/even names, so the plain "header" property is used here.
			. "@page {$name} :first { header: html_wpbblank; }",
			HTMLParserMode::HEADER_CSS
		);
		return $name;
	}

	/**
	 * Running header markup for a chapter: [odd page, even page]; '' when off.
	 *
	 * @param array $section Chapter section.
	 * @return string[]
	 */
	private function running_header( array $section ) {
		$layout = $this->book->config['layout'];
		if ( ! $layout['running_header'] && 'top-outer' !== $layout['page_numbers'] ) {
			return array( '', '' );
		}
		$chapter = $layout['running_header'] ? esc_html( $section['title'] ) : '';
		$book    = $layout['running_header'] ? esc_html( '' !== $layout['header_text'] ? $layout['header_text'] : $this->book->cover_title() ) : '';
		$number  = 'top-outer' === $layout['page_numbers'] ? '{PAGENO}' : '';

		if ( $layout['mirror'] ) {
			$odd  = '<table class="wpb-header"><tr><td class="wpb-h-left">' . $chapter . '</td><td class="wpb-h-right">' . $number . '</td></tr></table>';
			$even = '<table class="wpb-header"><tr><td class="wpb-h-left">' . $number . '</td><td class="wpb-h-right">' . $book . '</td></tr></table>';
		} else {
			$odd  = '<table class="wpb-header"><tr><td class="wpb-h-left">' . $book . '</td><td class="wpb-h-right">' . $chapter . ( '' !== $number ? ' · ' . $number : '' ) . '</td></tr></table>';
			$even = $odd;
		}
		return array( $odd, $even );
	}

	/**
	 * Insert TOC entries and bookmarks before headings recorded by the content filter.
	 *
	 * @param string $html     Chapter HTML.
	 * @param array  $headings [toc level, text, anchor] rows.
	 * @return string
	 */
	private function mark_headings( $html, array $headings ) {
		$depth = (int) $this->book->config['structure']['toc_depth'];
		foreach ( $headings as $heading ) {
			list( $level, $text, $anchor ) = $heading;
			if ( $level > $depth ) {
				continue;
			}
			$mpdf_level = $this->chapter_level + $level - 1;
			$marker     = $this->toc_entry( $text, $mpdf_level ) . $this->bookmark( $text, $mpdf_level );
			$html       = preg_replace( '/(<h\d[^>]*\sid="' . preg_quote( $anchor, '/' ) . '")/', $marker . '$1', $html, 1 );
		}
		return $html;
	}

	/**
	 * Section-specific CSS (covers).
	 *
	 * @param array $section Section.
	 */
	private function write_section_css( array $section ) {
		if ( ! empty( $section['css'] ) ) {
			$this->mpdf->WriteHTML( $section['css'], HTMLParserMode::HEADER_CSS );
		}
	}

	/**
	 * TOC entry tag, or '' when the TOC is off.
	 *
	 * @param string $text  Entry text.
	 * @param int    $level mPDF TOC level.
	 * @return string
	 */
	private function toc_entry( $text, $level ) {
		if ( ! $this->book->config['structure']['toc'] ) {
			return '';
		}
		return '<tocentry content="' . esc_attr( $text ) . '" level="' . (int) $level . '" />';
	}

	/**
	 * Bookmark tag, or '' when bookmarks are off.
	 *
	 * @param string $text  Bookmark text.
	 * @param int    $level Level.
	 * @return string
	 */
	private function bookmark( $text, $level ) {
		if ( ! $this->bookmarks() ) {
			return '';
		}
		return '<bookmark content="' . esc_attr( $text ) . '" level="' . (int) $level . '" />';
	}

	/**
	 * Whether PDF bookmarks are enabled.
	 *
	 * @return bool
	 */
	private function bookmarks() {
		return (bool) $this->book->config['output']['bookmarks'];
	}

	/**
	 * "Chapter 3" in the book's wording.
	 *
	 * @param int $number Chapter number.
	 * @return string
	 */
	private function chapter_label( $number ) {
		$label = $this->book->config['structure']['chapter_label'];
		return ( '' !== $label ? $label : __( 'Chapter', 'wp-book' ) ) . ' ' . (int) $number;
	}
}
