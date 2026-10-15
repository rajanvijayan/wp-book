/**
 * App shell: loads editor metadata once, then routes between screens.
 */
import { Notice, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { errorMessage, getMeta } from '../api';
import { useRoute } from '../router';
import BookList from './BookList';
import BookEditor from './editor/BookEditor';

export default function App() {
	const route = useRoute();
	const [ meta, setMeta ] = useState( null );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		getMeta()
			.then( setMeta )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [] );

	if ( error ) {
		return (
			<div className="wpbook-app">
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			</div>
		);
	}
	if ( ! meta ) {
		return (
			<div className="wpbook-app wpbook-loading">
				<Spinner />
				<span>{ __( 'Loading BookPress…', 'wp-book' ) }</span>
			</div>
		);
	}
	return (
		<div className="wpbook-app">
			{ route.name === 'book' && route.id ? (
				<BookEditor
					key={ route.id }
					bookId={ route.id }
					meta={ meta }
				/>
			) : (
				<BookList meta={ meta } startNew={ route.name === 'new' } />
			) }
		</div>
	);
}
