<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package WPBook
 * @since 1.0.0
 */

namespace WPBook\admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Controller {

	/**
	 * Version of the bundled Select2 library, used to bust browser caches.
	 */
	const SELECT2_VERSION = '4.0.6-rc.1';

	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $wp_book    The ID of this plugin.
	 */
	private $wp_book;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The mPDF instance used for the current book.
	 *
	 * @var \Mpdf\Mpdf
	 */
	private $mpdf;

	/**
	 * PDF templates keyed by name, as returned by the view controller.
	 *
	 * @var array
	 */
	private $templates;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $wp_book The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $wp_book, $version ) {

		$this->wp_book = $wp_book;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WPBook_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WPBook_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->wp_book, plugins_url( 'wp-book/assets/css/wpbook.css' ), array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WPBook_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WPBook_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( 'select2', plugins_url( 'wp-book/assets/css/node_modules/select2/dist/css/select2.min.css' ), array(), self::SELECT2_VERSION );

		wp_enqueue_script( 'select2', plugins_url( 'wp-book/assets/js/node_modules/select2/dist/js/select2.min.js' ), array( 'jquery' ), self::SELECT2_VERSION, true );
		wp_enqueue_script( $this->wp_book, plugins_url( 'wp-book/assets/js/wpbook.min.js' ), array( 'jquery', 'select2', 'jquery-ui-sortable', 'jquery-ui-accordion' ), $this->version, true );

		$params = array(
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( $this->wp_book ),
		);
		wp_localize_script( $this->wp_book, 'wp_book_object', $params );
	}

	/**
	 * Add the WP Book screen under Settings.
	 */
	public function wp_book_settings_page() {
		add_options_page(
			__( 'WP Book', 'wp-book' ),
			__( 'WP Book', 'wp-book' ),
			'manage_options',
			$this->wp_book,
			array( $this, 'wp_book_page' )
		);
	}

	/**
	 * Render the WP Book screen.
	 */
	public function wp_book_page() {
		?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Download PDF', 'wp-book' ); ?></h1>

		<div class="wp-book-form-wrapper">
				
				<div class="container-panel">

					<form class="wp-book-form" id="wp-book-form">
		<?php $this->render_load_filters(); ?>
		<?php $this->render_post_container(); ?>
					</form>

				</div>

		</div>
	</div>
		<?php
	}

	/**
	 * Render the post filter and PDF option fields.
	 */
	public function render_load_filters() {
		?>
	<div class="left-panel">

		<h3><?php esc_html_e( 'Post Filters', 'wp-book' ); ?></h3>

		<div class="form-group">
				<label><?php esc_html_e( 'Post Types', 'wp-book' ); ?></label>
				<select class="form-control filter-post-type wp-book-select2" onchange="load_material();">
		<?php foreach ( get_post_types( array( 'public' => true ), 'names' ) as $post_type ) { ?>
					<option <?php selected( $post_type, 'post' ); ?>><?php echo esc_html( $post_type ); ?></option>
			<?php
		}
		?>
				</select>
		</div>

		<div class="form-group">
				<label><?php esc_html_e( 'Number of posts', 'wp-book' ); ?></label>
				<input type="number" value="10" class="form-control filter-post-number" onchange="load_material();" />
		</div>

		<div class="form-group">
				<label><?php esc_html_e( 'Order By', 'wp-book' ); ?></label>
				<select class="form-control filter-post-order wp-book-select2" onchange="load_material();">
					<option selected="selected" value="ASC"><?php esc_html_e( 'ASC', 'wp-book' ); ?></option>
					<option  value="DESC"><?php esc_html_e( 'DESC', 'wp-book' ); ?></option>
				</select>
		</div>

		<div class="form-group">
				<label><?php esc_html_e( 'Sort', 'wp-book' ); ?></label>
				<select class="form-control filter-post-order-by wp-book-select2" onchange="load_material();">
					<option selected="selected" value=""><?php esc_html_e( 'None', 'wp-book' ); ?></option>
					<option value="ID"><?php esc_html_e( 'ID', 'wp-book' ); ?></option>
					<option value="author"><?php esc_html_e( 'Author', 'wp-book' ); ?></option>
					<option value="title"><?php esc_html_e( 'Title', 'wp-book' ); ?></option>
					<option value="name"><?php esc_html_e( 'Name', 'wp-book' ); ?></option>
					<option value="type"><?php esc_html_e( 'Type', 'wp-book' ); ?></option>
					<option value="date"><?php esc_html_e( 'Published Date', 'wp-book' ); ?></option>
					<option value="modified"><?php esc_html_e( 'Modified Time', 'wp-book' ); ?></option>
					<option value="parent"><?php esc_html_e( 'Parent', 'wp-book' ); ?></option>
					<option value="rand"><?php esc_html_e( 'Random', 'wp-book' ); ?></option>
				</select>
		</div>

		<br/>

		<h3><?php esc_html_e( 'PDF Options', 'wp-book' ); ?></h3>

		<div class="form-group">
				<label><?php esc_html_e( 'Page Format', 'wp-book' ); ?></label>
				<?php $page_sizes = $this->get_supported_mpdf_page_sizes(); ?>
				<select class="form-control wp-book-select2" name="page_format">
				<?php foreach ( $page_sizes as $page_size => $page_values ) { ?>
					<option value="<?php echo esc_attr( $page_size ); ?>"><?php echo esc_html( $page_size . ' - ' . $page_values[0] . ' x ' . $page_values[1] ); ?></option>
					<?php
				}
				?>
				</select>
		</div>

		<div class="form-group">
				<label><?php esc_html_e( 'Page Orientation', 'wp-book' ); ?></label>
				<?php $page_orientations = $this->get_supported_mpdf_page_orientation(); ?>
				<select class="form-control wp-book-select2" name="page_orientations">
				<?php foreach ( $page_orientations as $page_orientation => $page_orientation_value ) { ?>
					<option value="<?php echo esc_attr( $page_orientation ); ?>"><?php echo esc_html( $page_orientation_value ); ?></option>
					<?php
				}
				?>
				</select>
		</div>

		<div class="form-group">
				<label><?php esc_html_e( 'Apply page breaks after each posts', 'wp-book' ); ?></label>
				<input type="checkbox" name="page_breaks" checked="checked">
		</div>

		<div class="form-group">
				<label><?php esc_html_e( 'Header', 'wp-book' ); ?></label>
				<textarea class="form-control" name="header"></textarea>
				<small>{PAGENO} , {DATE j-m-Y}</small>
		</div>

		<div class="form-group">
				<label><?php esc_html_e( 'Footer', 'wp-book' ); ?></label>
				<textarea class="form-control" name="footer"></textarea>
				<small>{PAGENO} , {DATE j-m-Y}</small>
		</div>

		<div class="form-group">
				<label><?php esc_html_e( 'Template', 'wp-book' ); ?></label>
				<?php $templates = $this->get_templates(); ?>
				<select class="form-control wp-book-select2" name="page_template">
				<?php foreach ( $templates as $template_name => $template_value ) { ?>
					<option value="<?php echo esc_attr( $template_name ); ?>"><?php echo esc_html( $template_name ); ?></option>
					<?php
				}
				?>
				</select>
		</div>

	</div>
		<?php
	}

	/**
	 * Render the list of posts to include and the action buttons.
	 */
	public function render_post_container() {
		?>
	<div class="right-panel">
		<h3><?php esc_html_e( 'PDF Material', 'wp-book' ); ?></h3>
		<div class="<?php echo esc_attr( $this->wp_book ); ?>-pdf-material-stage"></div>
		<input type="button" class="button button-primary" id="generate_book" value="<?php esc_attr_e( 'Generate Book', 'wp-book' ); ?>" />
		<a class="button button-primary download_book" target="_blank" href="#"><?php esc_html_e( 'Download PDF', 'wp-book' ); ?></a>
	</div>
		<?php
	}

	/**
	 * AJAX: return the posts matching the selected filters.
	 */
	public function load_posts_for_print() {
		check_ajax_referer( $this->wp_book, 'security' );

		$post_type      = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'post';
		$posts_per_page = isset( $_POST['posts_per_page'] ) ? absint( $_POST['posts_per_page'] ) : 10;
		$order          = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'ASC';
		$order_by       = isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : '';

		$args  = array(
			'post_type'      => $post_type,
			'posts_per_page' => $posts_per_page,
			'order'          => $order,
			'orderby'        => $order_by,
		);
		$query = get_posts( $args );
		wp_send_json( $query );
	}

	/**
	 * AJAX: build the PDF from the selected posts and options.
	 */
	public function generate_book() {
		check_ajax_referer( $this->wp_book, 'security' );

		$output = array();
		if ( ! isset( $_POST['material'] ) || ! is_array( $_POST['material'] ) ) {
			wp_send_json(
				array(
					'result'       => 'failed',
					'notice_class' => 'notice-error',
					'message'      => __( 'Nothing to print.', 'wp-book' ),
				)
			);
		}
		$pdf            = $this->pdf_sanitize_array( map_deep( wp_unslash( $_POST['material'] ), 'sanitize_text_field' ) );
		$pdf['options'] = wp_parse_args(
			$pdf['options'],
			array(
				'page_format'       => 'A4',
				'page_orientations' => 'P',
				'page_template'     => 'Default',
				'page_breaks'       => '',
				'header'            => '',
				'footer'            => '',
			)
		);

		$pdf_options = array(
			'format'           => $pdf['options']['page_format'],
			'orientation'      => $pdf['options']['page_orientations'],
			'tempDir'          => $this->get_wp_book_temp_dir(),
			// Pick a bundled font that covers each script (CJK, Arabic, etc.) instead of rendering boxes.
			'autoScriptToLang' => true,
			'autoLangToFont'   => true,
		);

		$upload_dir = $this->get_wp_book_upload_dir();
		$upload_url = $this->get_wp_book_upload_url();

		try {
			$this->mpdf = new \Mpdf\Mpdf( $pdf_options );

			$template = $pdf['options']['page_template'];

			$stylesheet = $this->build_style( $template );
			$this->mpdf->WriteHTML( $stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS );

			if ( '' !== $pdf['options']['header'] ) {
				$this->mpdf->SetHTMLHeader( $pdf['options']['header'] );
			}

			if ( '' !== $pdf['options']['footer'] ) {
				$this->mpdf->SetHTMLFooter( $pdf['options']['footer'] );
			}

			foreach ( $pdf['material'] as $material ) {
				$post = get_post( $material );
				if ( ! $post ) {
					continue;
				}
				$this->mpdf->WriteHTML( '<h2>' . $post->post_title . '</h2>' );
				$this->mpdf->WriteHTML( '<p>' . $post->post_content . '</p>' );

				if ( 'on' === $pdf['options']['page_breaks'] ) {
					$this->mpdf->AddPage();
				}
			}

			$file_name = md5( microtime() ) . '.pdf';

			$this->mpdf->Output( $upload_dir . DIRECTORY_SEPARATOR . $file_name, 'F' );

			$output['file_name']    = $file_name;
			$output['upload_url']   = $upload_url . '/' . $file_name;
			$output['result']       = 'success';
			$output['notice_class'] = 'notice-success';
			$output['message']      = __( 'File Created Successfully', 'wp-book' );

		} catch ( \Mpdf\MpdfException $e ) {
			$output['result']       = 'failed';
			$output['notice_class'] = 'notice-error';
			$output['message']      = $e->getMessage();
		}

		wp_send_json( $output );
	}

	/**
	 * Return the uploads folder where generated PDFs are saved, creating it if needed.
	 *
	 * @return string|null Absolute path, or null when uploads are unavailable.
	 */
	public function get_wp_book_upload_dir() {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['basedir'] ) ) {
			$dirname = $upload_dir['basedir'] . '/' . $this->wp_book;
			if ( ! file_exists( $dirname ) ) {
				wp_mkdir_p( $dirname );
			}
			return $dirname;
		}
	}

	/**
	 * Return a writable folder for mPDF's font and image caches, creating it if needed.
	 *
	 * The bundled vendor/mpdf/mpdf/tmp is often read-only on production hosts.
	 *
	 * @return string Absolute path.
	 */
	public function get_wp_book_temp_dir() {
		$dirname = trailingslashit( get_temp_dir() ) . $this->wp_book;
		if ( ! file_exists( $dirname ) ) {
			wp_mkdir_p( $dirname );
		}
		return $dirname;
	}

	/**
	 * Return the URL of the folder where generated PDFs are saved.
	 *
	 * @return string
	 */
	public function get_wp_book_upload_url() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/' . $this->wp_book;
	}

	/**
	 * Split the serialized form fields into post IDs and PDF options.
	 *
	 * @param array $fields Form fields as name/value pairs from jQuery serializeArray().
	 * @return array {
	 *     @type string[] $material Post IDs, in print order.
	 *     @type string[] $options  PDF options keyed by field name.
	 * }
	 */
	public function pdf_sanitize_array( $fields = array() ) {
		$output = array(
			'material' => array(),
			'options'  => array(),
		);
		if ( is_array( $fields ) ) {
			foreach ( $fields as $value ) {
				if ( ! isset( $value['name'], $value['value'] ) ) {
					continue;
				}
				if ( 'material' === $value['name'] ) {
					$output['material'][] = sanitize_text_field( $value['value'] );
				} else {
					$output['options'][ $value['name'] ] = sanitize_text_field( $value['value'] );
				}
			}
		}
		return $output;
	}

	/**
	 * Set the available PDF templates.
	 *
	 * @param array $templates Templates keyed by name.
	 */
	public function set_templates( $templates ) {
		$this->templates = $templates;
	}

	/**
	 * Get the available PDF templates.
	 *
	 * @return array
	 */
	public function get_templates() {
		return $this->templates;
	}

	/**
	 * Build the CSS for a template.
	 *
	 * @param string $template Template name.
	 * @return string
	 */
	public function build_style( $template ) {
		if ( ! isset( $this->templates[ $template ] ) ) {
			$template = 'Default';
		}
		$styles = isset( $this->templates[ $template ] ) ? $this->templates[ $template ] : array();

		$css = '';
		foreach ( $styles as $style_key => $style_value ) {
			$css .= $style_key . '{';
			foreach ( $style_value as $k => $v ) {
				$css .= $k . ':' . $v . ';';
			}
			$css .= '}';
		}

		return $css;
	}

	/**
	 * Page orientations supported by mPDF.
	 *
	 * @return array Labels keyed by mPDF orientation code.
	 */
	public function get_supported_mpdf_page_orientation() {
		return array(
			'P' => __( 'Portrait', 'wp-book' ),
			'L' => __( 'landscape', 'wp-book' ),
		);
	}

	/**
	 * Page sizes supported by mPDF.
	 *
	 * @return array Width and height in points, keyed by mPDF format name.
	 */
	public function get_supported_mpdf_page_sizes() {
		return array(
			'A4'        => array( 595.28, 841.89 ),
			'4A0'       => array( 4767.87, 6740.79 ),
			'2A0'       => array( 3370.39, 4767.87 ),
			'A0'        => array( 2383.94, 3370.39 ),
			'A1'        => array( 1683.78, 2383.94 ),
			'A2'        => array( 1190.55, 1683.78 ),
			'A3'        => array( 841.89, 1190.55 ),
			'A5'        => array( 419.53, 595.28 ),
			'A6'        => array( 297.64, 419.53 ),
			'A7'        => array( 209.76, 297.64 ),
			'A8'        => array( 147.40, 209.76 ),
			'A9'        => array( 104.88, 147.40 ),
			'A10'       => array( 73.70, 104.88 ),
			'B0'        => array( 2834.65, 4008.19 ),
			'B1'        => array( 2004.09, 2834.65 ),
			'B2'        => array( 1417.32, 2004.09 ),
			'B3'        => array( 1000.63, 1417.32 ),
			'B4'        => array( 708.66, 1000.63 ),
			'B5'        => array( 498.90, 708.66 ),
			'B6'        => array( 354.33, 498.90 ),
			'B7'        => array( 249.45, 354.33 ),
			'B8'        => array( 175.75, 249.45 ),
			'B9'        => array( 124.72, 175.75 ),
			'B10'       => array( 87.87, 124.72 ),
			'C0'        => array( 2599.37, 3676.54 ),
			'C1'        => array( 1836.85, 2599.37 ),
			'C2'        => array( 1298.27, 1836.85 ),
			'C3'        => array( 918.43, 1298.27 ),
			'C4'        => array( 649.13, 918.43 ),
			'C5'        => array( 459.21, 649.13 ),
			'C6'        => array( 323.15, 459.21 ),
			'C7'        => array( 229.61, 323.15 ),
			'C8'        => array( 161.57, 229.61 ),
			'C9'        => array( 113.39, 161.57 ),
			'C10'       => array( 79.37, 113.39 ),
			'RA0'       => array( 2437.80, 3458.27 ),
			'RA1'       => array( 1729.13, 2437.80 ),
			'RA2'       => array( 1218.90, 1729.13 ),
			'RA3'       => array( 864.57, 1218.90 ),
			'RA4'       => array( 609.45, 864.57 ),
			'SRA0'      => array( 2551.18, 3628.35 ),
			'SRA1'      => array( 1814.17, 2551.18 ),
			'SRA2'      => array( 1275.59, 1814.17 ),
			'SRA3'      => array( 907.09, 1275.59 ),
			'SRA4'      => array( 637.80, 907.09 ),
			'LETTER'    => array( 612.00, 792.00 ),
			'LEGAL'     => array( 612.00, 1008.00 ),
			'LEDGER'    => array( 1224.00, 792.00 ),
			'TABLOID'   => array( 792.00, 1224.00 ),
			'EXECUTIVE' => array( 521.86, 756.00 ),
			'FOLIO'     => array( 612.00, 936.00 ),
			'B'         => array( 362.83, 561.26 ), // 'B' format paperback size 128x198mm
			'A'         => array( 314.65, 504.57 ), // 'A' format paperback size 111x178mm
			'DEMY'      => array( 382.68, 612.28 ), // 'Demy' format paperback size 135x216mm
			'ROYAL'     => array( 433.70, 663.30 ), // 'Royal' format paperback size 153x234mm
		);
	}
}
