/**
 * Live PDF preview of the cover or a sample, including unsaved changes.
 */
import { Button, Spinner } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { update } from '@wordpress/icons';
import { errorMessage, previewBook } from '../../api';

export default function PreviewPane( { bookId, draft, hasChapters } ) {
	const [ mode, setMode ] = useState( 'cover' );
	const [ url, setUrl ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ tick, setTick ] = useState( 0 );
	const abort = useRef();

	const effectiveMode = mode === 'sample' && ! hasChapters ? 'cover' : mode;
	const key = JSON.stringify( [
		effectiveMode,
		draft.title,
		draft.config,
		tick,
	] );

	useEffect( () => {
		const timer = setTimeout( () => {
			if ( abort.current ) {
				abort.current.abort();
			}
			abort.current = new window.AbortController();
			setLoading( true );
			setError( '' );
			previewBook(
				bookId,
				effectiveMode,
				draft.config,
				draft.title,
				abort.current.signal
			)
				.then( ( next ) => {
					setUrl( ( previous ) => {
						if ( previous ) {
							URL.revokeObjectURL( previous );
						}
						return next;
					} );
					setLoading( false );
				} )
				.catch( ( e ) => {
					if ( e && e.name === 'AbortError' ) {
						return;
					}
					setLoading( false );
					setError(
						errorMessage( e ) ||
							__(
								'The preview could not be rendered.',
								'wp-book'
							)
					);
				} );
		}, 1200 );
		return () => clearTimeout( timer );
	}, [ key ] ); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => () => url && URL.revokeObjectURL( url ), [] ); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<aside
			className="wpbook-preview"
			aria-label={ __( 'Preview', 'wp-book' ) }
		>
			<div className="wpbook-preview__bar">
				<div
					className="wpbook-segmented"
					role="group"
					aria-label={ __( 'Preview mode', 'wp-book' ) }
				>
					<button
						type="button"
						className={ mode === 'cover' ? 'is-active' : '' }
						onClick={ () => setMode( 'cover' ) }
					>
						{ __( 'Cover', 'wp-book' ) }
					</button>
					<button
						type="button"
						className={ mode === 'sample' ? 'is-active' : '' }
						onClick={ () => setMode( 'sample' ) }
						disabled={ ! hasChapters }
						title={
							hasChapters
								? ''
								: __(
										'Add chapters to preview the inside of the book.',
										'wp-book'
								  )
						}
					>
						{ __( 'Sample pages', 'wp-book' ) }
					</button>
				</div>
				{ loading && <Spinner /> }
				<Button
					icon={ update }
					label={ __( 'Refresh preview', 'wp-book' ) }
					onClick={ () => setTick( tick + 1 ) }
					size="small"
				/>
			</div>
			{ error && <p className="wpbook-error">{ error }</p> }
			<div className="wpbook-preview__frame">
				{ url ? (
					<iframe
						title={ __( 'Book preview', 'wp-book' ) }
						src={ `${ url }#toolbar=0&navpanes=0&view=FitH` }
					/>
				) : (
					<div className="wpbook-preview__placeholder">
						<Spinner />
					</div>
				) }
			</div>
		</aside>
	);
}
