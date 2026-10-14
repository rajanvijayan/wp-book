<?php
/**
 * Font packs: detection, permissions, verified downloads and mPDF font registration.
 *
 * @package WPBook
 */

use WPBook\Render\Font_Manager;
use WPBook\Render\Font_Packs;

/**
 * @covers \WPBook\Render\Font_Packs
 * @covers \WPBook\Rest\Fonts_Controller
 * @covers \WPBook\Render\Font_Manager
 */
class Font_Packs_Test extends WPBook_TestCase {

	public function tear_down() {
		foreach ( array( 'cjk', 'world' ) as $key ) {
			Font_Packs::remove( $key );
		}
		parent::tear_down();
	}

	public function test_manifest_lists_both_packs_with_hashes() {
		$manifest = Font_Packs::manifest();
		$this->assertSame( 'https://raw.githubusercontent.com/mpdf/mpdf/' . $manifest['mpdf'] . '/ttfonts/', Font_Packs::source() );
		$this->assertArrayHasKey( 'Sun-ExtA.ttf', $manifest['packs']['cjk'] );
		$this->assertArrayHasKey( 'XB Riyaz.ttf', $manifest['packs']['world'] );
		foreach ( $manifest['packs'] as $files ) {
			foreach ( $files as $file ) {
				$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $file['sha256'] );
			}
		}
	}

	public function test_detects_scripts_that_need_a_pack() {
		$this->assertSame( array(), Font_Packs::missing_for( '<p>Plain English, café, Ελληνικά, русский.</p>' ) );
		$this->assertSame( array( 'cjk' ), Font_Packs::missing_for( '<p>日本語のテキスト</p>' ) );
		$this->assertSame( array( 'world' ), Font_Packs::missing_for( '<p>مرحبا بالعالم</p>' ) );
		$this->assertSame( array( 'cjk', 'world' ), Font_Packs::missing_for( '<p>한국어 और हिन्दी</p>' ) );
		// Markup is not text.
		$this->assertSame( array(), Font_Packs::missing_for( '<p title="日本">Hello</p>' ) );
	}

	public function test_only_administrators_install_packs() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 200, rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp-book/v2/fonts' ) )->get_status() );
		$this->assertSame( 403, rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/wp-book/v2/fonts/cjk' ) )->get_status() );
		$this->assertSame( 403, rest_get_server()->dispatch( new WP_REST_Request( 'DELETE', '/wp-book/v2/fonts/cjk' ) )->get_status() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( 403, rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp-book/v2/fonts' ) )->get_status() );
	}

	public function test_unknown_pack_is_rejected() {
		$this->assertWPError( Font_Packs::install_next( 'nope' ) );
	}

	public function test_download_that_fails_the_checksum_is_discarded() {
		$requested = '';
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( &$requested ) {
				$requested = $url;
				file_put_contents( $args['filename'], 'not a font' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				return array(
					'headers'  => array(),
					'body'     => '',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => $args['filename'],
				);
			},
			10,
			3
		);

		$result = Font_Packs::install_next( 'cjk' );
		$this->assertWPError( $result );
		$this->assertSame( 'wpbook_font_hash', $result->get_error_code() );
		$this->assertSame( Font_Packs::source() . 'Sun-ExtA.ttf', $requested );
		$this->assertFileDoesNotExist( Font_Packs::dir() . '/Sun-ExtA.ttf' );
		$this->assertFalse( Font_Packs::is_installed( 'cjk' ) );
	}

	public function test_mpdf_config_registers_only_installed_fonts() {
		$config = Font_Manager::mpdf_config( array() );
		$this->assertArrayHasKey( 'dejavuserif', $config['fontdata'] );
		$this->assertContains( Font_Packs::dir(), $config['fontDir'] );

		$prefixed = dirname( __DIR__, 2 ) . '/vendor-prefixed/mpdf/mpdf/ttfonts/Sun-ExtA.ttf';
		if ( ! file_exists( $prefixed ) ) {
			// Trimmed build: the CJK font is only available once the pack is installed.
			$this->assertArrayNotHasKey( 'sun-exta', $config['fontdata'] );
		}
	}
}
