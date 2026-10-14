/**
 * Create a book: title plus a cover design and interior template from the galleries.
 */
import { Button, Modal, Notice, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { createBook, errorMessage } from '../api';
import { navigate } from '../router';
import CoverGallery from './gallery/CoverGallery';
import TemplateGallery from './gallery/TemplateGallery';

export default function NewBookModal( { meta, onClose } ) {
	const [ title, setTitle ] = useState( '' );
	const [ cover, setCover ] = useState( meta.defaults.cover.template );
	const [ template, setTemplate ] = useState(
		meta.defaults.interior.template
	);
	const [ step, setStep ] = useState( 1 );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );

	const create = () => {
		setSaving( true );
		const design = meta.covers.find( ( c ) => c.key === cover );
		createBook( title.trim(), {
			cover: { template: cover, ...( design ? design.defaults : {} ) },
			interior: { template },
		} )
			.then( ( book ) => navigate( `book/${ book.id }` ) )
			.catch( ( e ) => {
				setSaving( false );
				setError( errorMessage( e ) );
			} );
	};

	return (
		<Modal
			title={ __( 'New book', 'wp-book' ) }
			onRequestClose={ onClose }
			className="wpbook-new-modal"
			size="large"
		>
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<ol className="wpbook-steps">
				<li className={ step === 1 ? 'is-active' : '' }>
					{ __( 'Title & cover', 'wp-book' ) }
				</li>
				<li className={ step === 2 ? 'is-active' : '' }>
					{ __( 'Interior', 'wp-book' ) }
				</li>
			</ol>
			{ step === 1 && (
				<>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Book title', 'wp-book' ) }
						value={ title }
						onChange={ setTitle }
						placeholder={ __(
							'e.g. The Best of Our Blog 2026',
							'wp-book'
						) }
					/>
					<h3 className="wpbook-section-title">
						{ __( 'Choose a cover', 'wp-book' ) }
					</h3>
					<CoverGallery
						covers={ meta.covers }
						value={ cover }
						onChange={ setCover }
						title={ title }
					/>
				</>
			) }
			{ step === 2 && (
				<>
					<h3 className="wpbook-section-title">
						{ __( 'Choose an interior template', 'wp-book' ) }
					</h3>
					<TemplateGallery
						templates={ meta.templates }
						value={ template }
						onChange={ setTemplate }
					/>
				</>
			) }
			<div className="wpbook-modal-actions">
				{ step === 2 && (
					<Button variant="tertiary" onClick={ () => setStep( 1 ) }>
						{ __( 'Back', 'wp-book' ) }
					</Button>
				) }
				{ step === 1 ? (
					<Button
						variant="primary"
						disabled={ ! title.trim() }
						onClick={ () => setStep( 2 ) }
					>
						{ __( 'Next', 'wp-book' ) }
					</Button>
				) : (
					<Button
						variant="primary"
						isBusy={ saving }
						disabled={ saving }
						onClick={ create }
					>
						{ __( 'Create book', 'wp-book' ) }
					</Button>
				) }
			</div>
		</Modal>
	);
}
