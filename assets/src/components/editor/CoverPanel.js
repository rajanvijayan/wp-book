/**
 * Cover step: design gallery, automatic cover, text, colours, image and back cover.
 */
import {
	Button,
	Notice,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { errorMessage, getAutoCover } from '../../api';
import CoverGallery from '../gallery/CoverGallery';
import { ColorField, FontField, MediaField } from './fields';

export default function CoverPanel( {
	meta,
	config,
	setSection,
	bookId,
	title,
} ) {
	const cover = config.cover;
	const back = config.back_cover;
	const set = ( values ) => setSection( 'cover', values );
	const setBack = ( values ) => setSection( 'back_cover', values );
	const [ auto, setAuto ] = useState( null );
	const [ autoBusy, setAutoBusy ] = useState( false );
	const [ error, setError ] = useState( '' );
	const design = meta.covers.find( ( c ) => c.key === cover.template ) || {};

	const applyAuto = () => {
		setAutoBusy( true );
		getAutoCover( bookId )
			.then( ( suggestion ) => {
				set( suggestion.cover );
				setAuto( suggestion.reason );
				setAutoBusy( false );
			} )
			.catch( ( e ) => {
				setAutoBusy( false );
				setError( errorMessage( e ) );
			} );
	};

	return (
		<div className="wpbook-panel">
			<div className="wpbook-panel__head">
				<h2>{ __( 'Front cover', 'wp-book' ) }</h2>
				<Button
					variant="secondary"
					onClick={ applyAuto }
					isBusy={ autoBusy }
					disabled={ autoBusy }
				>
					{ __( '✨ Auto cover', 'wp-book' ) }
				</Button>
			</div>
			{ auto && (
				<Notice status="info" onRemove={ () => setAuto( null ) }>
					{ __( 'Cover created from your site:', 'wp-book' ) }{ ' ' }
					{ auto.join( ' ' ) }
				</Notice>
			) }
			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Include a front cover', 'wp-book' ) }
				checked={ cover.enabled }
				onChange={ ( enabled ) => set( { enabled } ) }
			/>
			{ cover.enabled && (
				<>
					<CoverGallery
						covers={ meta.covers }
						value={ cover.template }
						title={ cover.title || title }
						author={ cover.author }
						onChange={ ( key, picked ) =>
							set( { template: key, ...picked.defaults } )
						}
					/>

					<div className="wpbook-fields is-2col">
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Cover title', 'wp-book' ) }
							value={ cover.title }
							placeholder={ title }
							onChange={ ( v ) => set( { title: v } ) }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Subtitle', 'wp-book' ) }
							value={ cover.subtitle }
							onChange={ ( v ) => set( { subtitle: v } ) }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Author', 'wp-book' ) }
							value={ cover.author }
							placeholder={ window.wpbookSettings.user }
							onChange={ ( v ) => set( { author: v } ) }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Badge or series', 'wp-book' ) }
							value={ cover.badge }
							placeholder={ __( 'e.g. Volume One', 'wp-book' ) }
							onChange={ ( v ) => set( { badge: v } ) }
						/>
					</div>

					<h3 className="wpbook-section-title">
						{ __( 'Colours and fonts', 'wp-book' ) }
					</h3>
					<div className="wpbook-fields is-3col">
						<ColorField
							label={ __( 'Background', 'wp-book' ) }
							value={ cover.primary }
							palette={ meta.site.palette }
							onChange={ ( primary ) => set( { primary } ) }
						/>
						<ColorField
							label={ __( 'Accent', 'wp-book' ) }
							value={ cover.accent }
							palette={ meta.site.palette }
							onChange={ ( accent ) => set( { accent } ) }
						/>
						<ColorField
							label={ __( 'Text', 'wp-book' ) }
							value={ cover.text_color }
							palette={ [ '#ffffff', '#1d1d1f' ].concat(
								meta.site.palette
							) }
							onChange={ ( value ) =>
								set( { text_color: value } )
							}
						/>
						<FontField
							label={ __( 'Title font', 'wp-book' ) }
							value={ cover.title_font }
							fonts={ meta.fonts }
							onChange={ ( value ) =>
								set( { title_font: value } )
							}
						/>
						<FontField
							label={ __( 'Text font', 'wp-book' ) }
							value={ cover.body_font }
							fonts={ meta.fonts }
							onChange={ ( value ) =>
								set( { body_font: value } )
							}
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Text alignment', 'wp-book' ) }
							value={ cover.align }
							options={ [
								{
									value: 'left',
									label: __( 'Left', 'wp-book' ),
								},
								{
									value: 'center',
									label: __( 'Centre', 'wp-book' ),
								},
								{
									value: 'right',
									label: __( 'Right', 'wp-book' ),
								},
							] }
							onChange={ ( align ) => set( { align } ) }
						/>
					</div>

					<h3 className="wpbook-section-title">
						{ __( 'Image and logo', 'wp-book' ) }
					</h3>
					{ design.uses_image &&
						! cover.image_id &&
						! cover.use_featured && (
							<Notice status="warning" isDismissible={ false }>
								{ __(
									'This design is made for a photo. Choose an image or use the first featured image.',
									'wp-book'
								) }
							</Notice>
						) }
					<div className="wpbook-fields is-2col">
						<MediaField
							label={ __( 'Cover image', 'wp-book' ) }
							value={ cover.image_id }
							onChange={ ( value ) => set( { image_id: value } ) }
						/>
						<div>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Use the first featured image when no image is chosen',
									'wp-book'
								) }
								checked={ cover.use_featured }
								onChange={ ( value ) =>
									set( { use_featured: value } )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Show the site logo', 'wp-book' ) }
								checked={ cover.logo }
								onChange={ ( logo ) => set( { logo } ) }
							/>
						</div>
					</div>
				</>
			) }

			<h2 className="wpbook-panel__subhead">
				{ __( 'Back cover', 'wp-book' ) }
			</h2>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Include a back cover', 'wp-book' ) }
				checked={ back.enabled }
				onChange={ ( enabled ) => setBack( { enabled } ) }
			/>
			{ back.enabled && (
				<div className="wpbook-fields">
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Blurb', 'wp-book' ) }
						help={ __(
							'A short description of the book. Leave a blank line between paragraphs.',
							'wp-book'
						) }
						value={ back.blurb }
						rows={ 5 }
						onChange={ ( blurb ) => setBack( { blurb } ) }
					/>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'About the author', 'wp-book' ) }
						value={ back.bio }
						rows={ 3 }
						onChange={ ( bio ) => setBack( { bio } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show the author photo (uploaded avatars only)',
							'wp-book'
						) }
						checked={ back.show_avatar }
						onChange={ ( value ) =>
							setBack( { show_avatar: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Add a QR code', 'wp-book' ) }
						checked={ back.qr }
						onChange={ ( qr ) => setBack( { qr } ) }
					/>
					{ back.qr && (
						<TextControl
							__nextHasNoMarginBottom
							type="url"
							label={ __( 'QR code link', 'wp-book' ) }
							help={ __(
								'Leave empty to link to your site.',
								'wp-book'
							) }
							value={ back.qr_url }
							onChange={ ( value ) =>
								setBack( { qr_url: value } )
							}
						/>
					) }
				</div>
			) }
		</div>
	);
}
