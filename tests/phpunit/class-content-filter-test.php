<?php
/**
 * Content filtering for PDFs.
 *
 * @package WPBook
 */

use WPBook\Render\Content_Filter;

/**
 * @covers \WPBook\Render\Content_Filter
 */
class Content_Filter_Test extends WPBook_TestCase {

	public function test_scripts_events_and_remote_images_are_removed() {
		$filter = new Content_Filter( true );
		$html   = $filter->clean( '<p onclick="x()">Hi <a href="javascript:alert(1)">link</a></p><script>alert(1)</script><img src="https://evil.example/x.png"><style>p{}</style><form><input></form>', 'h' );
		$this->assertStringNotContainsString( 'script', $html );
		$this->assertStringNotContainsString( 'onclick', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringNotContainsString( 'evil.example', $html );
		$this->assertStringNotContainsString( '<form', $html );
		$this->assertStringContainsString( 'Hi', $html );
	}

	public function test_embeds_become_links() {
		$html = ( new Content_Filter() )->clean( '<iframe src="https://www.youtube.com/embed/abc"></iframe>', 'h' );
		$this->assertStringContainsString( '<a href="https://www.youtube.com/embed/abc"', $html );
		$this->assertStringNotContainsString( '<iframe', $html );
	}

	public function test_headings_shift_down_and_are_recorded() {
		$filter = new Content_Filter();
		$html   = $filter->clean( '<h1>Top</h1><h2>Section</h2><h3>Sub</h3><h4>Deep</h4>', 'wpb-ch1' );
		$this->assertStringContainsString( '<h2 id="wpb-ch1-1">Top</h2>', $html );
		$this->assertStringContainsString( '<h3 id="wpb-ch1-2">Section</h3>', $html );
		$this->assertStringContainsString( '<h5>Deep</h5>', $html );
		$this->assertSame(
			array( array( 2, 'Top', 'wpb-ch1-1' ), array( 2, 'Section', 'wpb-ch1-2' ), array( 3, 'Sub', 'wpb-ch1-3' ) ),
			$filter->headings
		);
	}

	public function test_only_files_inside_uploads_are_allowed() {
		$uploads = wp_upload_dir();
		$file    = $uploads['path'] . '/wpbook-test.png';
		file_put_contents( $file, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.DiscouragedPHPFunctions -- test fixture.
		$this->assertSame( realpath( $file ), Content_Filter::local_image_path( $uploads['url'] . '/wpbook-test.png' ) );
		$this->assertSame( '', Content_Filter::local_image_path( $uploads['baseurl'] . '/../../wp-config.php' ) );
		$this->assertSame( '', Content_Filter::local_image_path( 'https://evil.example/x.png' ) );
		$this->assertSame( '', Content_Filter::local_image_path( 'http://169.254.169.254/latest/meta-data' ) );
		unlink( $file );
	}

	public function test_password_protected_posts_are_not_printed_for_others() {
		$post = self::factory()->post->create_and_get(
			array(
				'post_password' => 'secret',
				'post_content'  => 'Hidden text',
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$html = ( new Content_Filter() )->post_html( $post, 'p' );
		$this->assertStringNotContainsString( 'Hidden text', $html );
	}
}
