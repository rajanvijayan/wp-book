/**
 * Output step: file name, metadata, bookmarks, watermark, password and history.
 */
import {
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { NumberField } from './fields';

export default function OutputPanel( { config, setSection, pages } ) {
	const output = config.output;
	const set = ( values ) => setSection( 'output', values );
	return (
		<div className="wpbook-panel">
			<h2>{ __( 'PDF file', 'wp-book' ) }</h2>
			<div className="wpbook-fields is-2col">
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'File name', 'wp-book' ) }
					help={ __( 'Defaults to the book title.', 'wp-book' ) }
					value={ output.filename }
					onChange={ ( filename ) => set( { filename } ) }
				/>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Subject', 'wp-book' ) }
					value={ output.subject }
					onChange={ ( subject ) => set( { subject } ) }
				/>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Keywords', 'wp-book' ) }
					help={ __( 'Separate with commas.', 'wp-book' ) }
					value={ output.keywords }
					onChange={ ( keywords ) => set( { keywords } ) }
				/>
				<NumberField
					label={ __( 'PDFs to keep', 'wp-book' ) }
					value={ output.keep }
					min={ 1 }
					max={ 50 }
					onChange={ ( keep ) => set( { keep } ) }
				/>
			</div>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Add bookmarks (PDF outline) for chapters and headings',
					'wp-book'
				) }
				checked={ output.bookmarks }
				onChange={ ( bookmarks ) => set( { bookmarks } ) }
			/>

			<h3 className="wpbook-section-title">
				{ __( 'Protection', 'wp-book' ) }
			</h3>
			<div className="wpbook-fields is-2col">
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Watermark text', 'wp-book' ) }
					help={ __(
						'Printed faintly across every page, e.g. “Draft” or “Review copy”.',
						'wp-book'
					) }
					value={ output.watermark }
					onChange={ ( watermark ) => set( { watermark } ) }
				/>
				<TextControl
					__nextHasNoMarginBottom
					type="password"
					autoComplete="new-password"
					label={ __( 'Password to open the PDF', 'wp-book' ) }
					help={ __(
						'Leave empty for no password. Previews are never protected.',
						'wp-book'
					) }
					value={ output.password }
					onChange={ ( password ) => set( { password } ) }
				/>
			</div>

			<h3 className="wpbook-section-title">
				{ __( 'Print', 'wp-book' ) }
			</h3>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Also create a print cover (back, spine and front on one sheet)',
					'wp-book'
				) }
				help={ __(
					'For print-on-demand services. Includes 3.175 mm (0.125 in) bleed; the spine width follows the page count.',
					'wp-book'
				) }
				checked={ output.print_cover }
				onChange={ ( value ) => set( { print_cover: value } ) }
			/>
			{ output.print_cover && (
				<div className="wpbook-fields is-2col">
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Paper', 'wp-book' ) }
						value={ output.paper }
						options={ [
							{
								value: 'white',
								label: __(
									'White (0.0572 mm per page)',
									'wp-book'
								),
							},
							{
								value: 'cream',
								label: __(
									'Cream (0.0635 mm per page)',
									'wp-book'
								),
							},
						] }
						onChange={ ( value ) => set( { paper: value } ) }
					/>
					<p className="wpbook-muted">
						{ pages
							? sprintf(
									/* translators: 1: spine width in mm, 2: page count */
									__(
										'Spine: about %1$s mm for %2$d pages (from the last build).',
										'wp-book'
									),
									(
										pages *
										( output.paper === 'cream'
											? 0.0635
											: 0.0572 )
									).toFixed( 1 ),
									pages
							  )
							: __(
									'The spine width is calculated when the book is generated.',
									'wp-book'
							  ) }
					</p>
				</div>
			) }
		</div>
	);
}
