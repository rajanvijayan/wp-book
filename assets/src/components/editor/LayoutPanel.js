/**
 * Layout step: page size, margins, headers and footers.
 */
import {
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { NumberField } from './fields';

export default function LayoutPanel( { meta, config, setSection } ) {
	const layout = config.layout;
	const set = ( values ) => setSection( 'layout', values );
	const groups = [
		[ 'book', __( 'Book sizes', 'wp-book' ) ],
		[ 'paper', __( 'Paper sizes', 'wp-book' ) ],
	];

	return (
		<div className="wpbook-panel">
			<h2>{ __( 'Page', 'wp-book' ) }</h2>
			<div className="wpbook-fields is-2col">
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Page size', 'wp-book' ) }
					value={ layout.format }
					onChange={ ( format ) => set( { format } ) }
				>
					{ groups.map( ( [ group, label ] ) => (
						<optgroup key={ group } label={ label }>
							{ meta.formats
								.filter( ( f ) => f.group === group )
								.map( ( f ) => (
									<option key={ f.key } value={ f.key }>
										{ `${ f.label } — ${ f.width } × ${ f.height } mm` }
									</option>
								) ) }
						</optgroup>
					) ) }
				</SelectControl>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Orientation', 'wp-book' ) }
					value={ layout.orientation }
					options={ [
						{ value: 'P', label: __( 'Portrait', 'wp-book' ) },
						{ value: 'L', label: __( 'Landscape', 'wp-book' ) },
					] }
					onChange={ ( orientation ) => set( { orientation } ) }
				/>
			</div>

			<h3 className="wpbook-section-title">
				{ __( 'Margins', 'wp-book' ) }
			</h3>
			<div className="wpbook-fields is-4col">
				<NumberField
					label={ __( 'Top', 'wp-book' ) }
					unit="mm"
					value={ layout.margin_top }
					min={ 5 }
					max={ 60 }
					onChange={ ( value ) => set( { margin_top: value } ) }
				/>
				<NumberField
					label={ __( 'Bottom', 'wp-book' ) }
					unit="mm"
					value={ layout.margin_bottom }
					min={ 5 }
					max={ 60 }
					onChange={ ( value ) => set( { margin_bottom: value } ) }
				/>
				<NumberField
					label={
						layout.mirror
							? __( 'Inside', 'wp-book' )
							: __( 'Left', 'wp-book' )
					}
					unit="mm"
					value={ layout.margin_inner }
					min={ 5 }
					max={ 60 }
					onChange={ ( value ) => set( { margin_inner: value } ) }
				/>
				<NumberField
					label={
						layout.mirror
							? __( 'Outside', 'wp-book' )
							: __( 'Right', 'wp-book' )
					}
					unit="mm"
					value={ layout.margin_outer }
					min={ 5 }
					max={ 60 }
					onChange={ ( value ) => set( { margin_outer: value } ) }
				/>
			</div>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Mirror margins for printing (left and right pages)',
					'wp-book'
				) }
				help={ __(
					'Adds a blank page where needed so chapters and the back cover fall on the right side.',
					'wp-book'
				) }
				checked={ layout.mirror }
				onChange={ ( mirror ) => set( { mirror } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Start chapters on a right-hand page', 'wp-book' ) }
				checked={ layout.chapter_recto }
				onChange={ ( value ) => set( { chapter_recto: value } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Columns', 'wp-book' ) }
				value={ String( layout.columns ) }
				options={ [ '1', '2', '3' ].map( ( n ) => ( {
					value: n,
					label: n,
				} ) ) }
				onChange={ ( v ) => set( { columns: Number( v ) } ) }
			/>

			<h3 className="wpbook-section-title">
				{ __( 'Header and footer', 'wp-book' ) }
			</h3>
			<div className="wpbook-fields is-2col">
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Running header with chapter titles',
						'wp-book'
					) }
					checked={ layout.running_header }
					onChange={ ( value ) => set( { running_header: value } ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Page numbers', 'wp-book' ) }
					value={ layout.page_numbers }
					options={ [
						{
							value: 'bottom-center',
							label: __( 'Bottom centre', 'wp-book' ),
						},
						{
							value: 'bottom-outer',
							label: __( 'Bottom outside corner', 'wp-book' ),
						},
						{
							value: 'top-outer',
							label: __( 'Top outside corner', 'wp-book' ),
						},
						{ value: 'none', label: __( 'None', 'wp-book' ) },
					] }
					onChange={ ( value ) => set( { page_numbers: value } ) }
				/>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Header text', 'wp-book' ) }
					help={ __(
						'Shown opposite the chapter title. Defaults to the book title.',
						'wp-book'
					) }
					value={ layout.header_text }
					onChange={ ( value ) => set( { header_text: value } ) }
				/>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Footer text', 'wp-book' ) }
					value={ layout.footer_text }
					onChange={ ( value ) => set( { footer_text: value } ) }
				/>
			</div>
		</div>
	);
}
