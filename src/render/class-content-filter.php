<?php
/**
 * Turns post content into HTML that mPDF renders well and safely.
 *
 * @package WPBook
 */

namespace WPBook\Render;

// The DOM extension's properties (parentNode, nodeName, ...) are camelCase by design.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Renders blocks and shortcodes, removes interactive and remote content, maps images to local files
 * and shifts headings below the chapter title.
 */
final class Content_Filter {

	/**
	 * Elements removed entirely.
	 */
	const REMOVE = array( 'script', 'style', 'noscript', 'form', 'input', 'button', 'select', 'textarea', 'object', 'embed', 'canvas', 'template', 'link', 'meta' );

	/**
	 * Elements replaced by a link to their source (when they have one).
	 */
	const REPLACE_WITH_LINK = array( 'iframe', 'video', 'audio' );

	/**
	 * Headings found while filtering, for the table of contents: [ [toc level, text, anchor], ... ].
	 * Chapters are TOC level 1; a post's h1/h2 become level 2 and its h3 level 3.
	 *
	 * @var array
	 */
	public $headings = array();

	/**
	 * Whether to keep images.
	 *
	 * @var bool
	 */
	private $images;

	/**
	 * Constructor.
	 *
	 * @param bool $images Keep images.
	 */
	public function __construct( $images = true ) {
		$this->images = (bool) $images;
	}

	/**
	 * Filter a post's content.
	 *
	 * @param \WP_Post $post   Post.
	 * @param string   $prefix Anchor prefix for this chapter's headings.
	 * @return string HTML.
	 */
	public function post_html( \WP_Post $post, $prefix ) {
		$this->headings = array();
		if ( post_password_required( $post ) && ! current_user_can( 'edit_post', $post->ID ) ) {
			return '<p>' . esc_html__( 'This content is password protected.', 'wp-book' ) . '</p>';
		}

		$previous_post   = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- blocks and shortcodes read the current post.
		setup_postdata( $post );

		// The core content pipeline without the_content, so themes and plugins cannot inject ads or share buttons.
		$html = do_blocks( $post->post_content );
		$html = wptexturize( $html );
		$html = wpautop( $html );
		$html = shortcode_unautop( $html );
		$html = do_shortcode( $html );
		$html = convert_smilies( $html );

		$GLOBALS['post'] = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restore.
		if ( $previous_post ) {
			setup_postdata( $previous_post );
		} else {
			wp_reset_postdata();
		}

		return $this->clean( $html, $prefix );
	}

	/**
	 * Clean arbitrary HTML (also used for the preface and author bio).
	 *
	 * @param string $html   HTML.
	 * @param string $prefix Anchor prefix.
	 * @return string
	 */
	public function clean( $html, $prefix = 'h' ) {
		if ( '' === trim( (string) $html ) ) {
			return '';
		}
		$dom      = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8"?><div id="wpbook-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		$xpath = new DOMXPath( $dom );

		foreach ( self::REMOVE as $tag ) {
			foreach ( iterator_to_array( $dom->getElementsByTagName( $tag ) ) as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}
		foreach ( self::REPLACE_WITH_LINK as $tag ) {
			foreach ( iterator_to_array( $dom->getElementsByTagName( $tag ) ) as $node ) {
				$this->replace_with_link( $dom, $node );
			}
		}

		// Strip event handlers and javascript: URLs left by any block or shortcode.
		foreach ( $xpath->query( '//@*' ) as $attr ) {
			$name = strtolower( $attr->nodeName );
			if ( 0 === strpos( $name, 'on' ) || ( in_array( $name, array( 'href', 'src' ), true ) && preg_match( '/^\s*javascript:/i', $attr->nodeValue ) ) ) {
				$attr->ownerElement->removeAttribute( $attr->nodeName );
			}
		}

		foreach ( iterator_to_array( $dom->getElementsByTagName( 'img' ) ) as $img ) {
			$this->filter_image( $img );
		}

		$this->shift_headings( $dom, $prefix );

		$root = $dom->getElementById( 'wpbook-root' );
		$out  = '';
		if ( $root ) {
			foreach ( $root->childNodes as $child ) {
				$out .= $dom->saveHTML( $child );
			}
		}
		return $out;
	}

	/**
	 * Replace an embed with a paragraph linking to its source.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param DOMElement  $node Element.
	 */
	private function replace_with_link( DOMDocument $dom, DOMElement $node ) {
		$src = $node->getAttribute( 'src' );
		if ( '' === $src ) {
			$source = $node->getElementsByTagName( 'source' )->item( 0 );
			$src    = $source ? $source->getAttribute( 'src' ) : '';
		}
		if ( '' === $src || ! wp_http_validate_url( $src ) ) {
			$node->parentNode->removeChild( $node );
			return;
		}
		$p    = $dom->createElement( 'p' );
		$link = $dom->createElement( 'a', esc_html( $src ) );
		$link->setAttribute( 'href', esc_url( $src ) );
		$p->setAttribute( 'class', 'wpbook-embed-link' );
		$p->appendChild( $link );
		$node->parentNode->replaceChild( $p, $node );
	}

	/**
	 * Point images at local files and drop anything remote.
	 *
	 * @param DOMElement $img Image element.
	 */
	private function filter_image( DOMElement $img ) {
		if ( ! $this->images ) {
			$img->parentNode->removeChild( $img );
			return;
		}
		$src = $img->getAttribute( 'src' );
		foreach ( array( 'srcset', 'sizes', 'loading', 'decoding', 'fetchpriority', 'style' ) as $attr ) {
			$img->removeAttribute( $attr );
		}
		$local = self::local_image_path( $src );
		if ( '' === $local ) {
			$img->parentNode->removeChild( $img );
			return;
		}
		$img->setAttribute( 'src', $local );
		$img->removeAttribute( 'width' );
		$img->removeAttribute( 'height' );
	}

	/**
	 * Map an image URL to a readable file in the uploads folder (or a data: URI). Remote URLs are rejected so a
	 * book can never make the server fetch arbitrary addresses.
	 *
	 * @param string $src Image URL.
	 * @return string Local path, data URI, or ''.
	 */
	public static function local_image_path( $src ) {
		$src = html_entity_decode( (string) $src, ENT_QUOTES );
		if ( 0 === strpos( $src, 'data:image/' ) ) {
			return $src;
		}
		$uploads = wp_upload_dir( null, false );
		$base    = set_url_scheme( $uploads['baseurl'], 'http' );
		$url     = set_url_scheme( strtok( $src, '?#' ), 'http' );
		if ( 0 === strpos( $url, '//' ) ) {
			$url = 'http:' . $url;
		}
		if ( 0 !== strpos( $url, $base . '/' ) ) {
			/**
			 * Filters the local path for an image outside the uploads folder. Return a readable local path to allow it.
			 *
			 * @param string $path Default ''.
			 * @param string $src  Image URL.
			 */
			return (string) apply_filters( 'wpbook_external_image_path', '', $src );
		}
		$relative = rawurldecode( substr( $url, strlen( $base ) ) );
		$path     = realpath( $uploads['basedir'] . $relative );
		$root     = realpath( $uploads['basedir'] );
		if ( ! $path || ! $root || 0 !== strpos( $path, $root . DIRECTORY_SEPARATOR ) || ! is_readable( $path ) ) {
			return '';
		}
		return $path;
	}

	/**
	 * Push headings one level down (the chapter title is the only h1) and record h2/h3 for the TOC.
	 *
	 * @param DOMDocument $dom    Document.
	 * @param string      $prefix Anchor prefix.
	 */
	private function shift_headings( DOMDocument $dom, $prefix ) {
		$xpath = new DOMXPath( $dom );
		$count = 0;
		foreach ( iterator_to_array( $xpath->query( '//h1|//h2|//h3|//h4|//h5' ) ) as $heading ) {
			$original  = (int) substr( $heading->nodeName, 1 );
			$level     = min( 6, $original + 1 );
			$toc_level = $original <= 2 ? 2 : ( 3 === $original ? 3 : 0 );
			$new       = $dom->createElement( 'h' . $level );
			foreach ( iterator_to_array( $heading->attributes ) as $attr ) {
				if ( 'class' === $attr->nodeName ) {
					$new->setAttribute( 'class', $attr->nodeValue );
				}
			}
			while ( $heading->firstChild ) {
				$new->appendChild( $heading->firstChild );
			}
			$text = trim( preg_replace( '/\s+/', ' ', $new->textContent ) );
			if ( $toc_level && '' !== $text ) {
				++$count;
				$anchor = $prefix . '-' . $count;
				$new->setAttribute( 'id', $anchor );
				$this->headings[] = array( $toc_level, $text, $anchor );
			}
			$heading->parentNode->replaceChild( $new, $heading );
		}
	}
}
