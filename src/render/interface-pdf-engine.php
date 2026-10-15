<?php
/**
 * PDF engine contract.
 *
 * @package WPBook
 */

namespace WPBook\Render;

/**
 * Lays out the sections produced by Book_Renderer into a document.
 */
interface Pdf_Engine {

	/**
	 * Render a book to a file.
	 *
	 * @param Book_Renderer $renderer Renderer.
	 * @param string        $path     Destination file.
	 * @param callable|null $progress Progress callback (done, total).
	 * @return array{pages: int}
	 */
	public function render( Book_Renderer $renderer, $path, $progress = null );
}
