/**
 * Books screen: every saved book with quick generate and download.
 */
import { Button, Notice, SearchControl, Spinner } from '@wordpress/components';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { deleteBook, duplicateBook, errorMessage, listBooks } from '../api';
import { navigate } from '../router';
import BookCard from './BookCard';
import NewBookModal from './NewBookModal';

export default function BookList( { meta, startNew } ) {
	const [ books, setBooks ] = useState( null );
	const [ search, setSearch ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ notice, setNotice ] = useState( '' );

	const load = useCallback( () => {
		listBooks( { search } )
			.then( ( result ) => setBooks( result.items ) )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [ search ] );

	useEffect( () => {
		const t = setTimeout( load, search ? 300 : 0 );
		return () => clearTimeout( t );
	}, [ load, search ] );

	const remove = ( book ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				__(
					'Delete this book and all of its PDFs? This cannot be undone.',
					'wp-book'
				)
			)
		) {
			return;
		}
		deleteBook( book.id )
			.then( () => {
				setNotice( __( 'Book deleted.', 'wp-book' ) );
				load();
			} )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	};

	const duplicate = ( book ) =>
		duplicateBook( book.id )
			.then( () => {
				setNotice( __( 'Book duplicated.', 'wp-book' ) );
				load();
			} )
			.catch( ( e ) => setError( errorMessage( e ) ) );

	return (
		<div className="wpbook-books">
			<header className="wpbook-header">
				<div>
					<h1 className="wpbook-title">
						{ __( 'BookPress', 'wp-book' ) }
					</h1>
					<p className="wpbook-subtitle">
						{ __(
							'Turn your posts into beautiful PDF eBooks.',
							'wp-book'
						) }
					</p>
				</div>
				<div className="wpbook-header__actions">
					<SearchControl
						__nextHasNoMarginBottom
						value={ search }
						onChange={ setSearch }
						placeholder={ __( 'Search books', 'wp-book' ) }
					/>
					<Button
						variant="primary"
						icon={ plus }
						onClick={ () => navigate( 'new' ) }
					>
						{ __( 'New book', 'wp-book' ) }
					</Button>
				</div>
			</header>

			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			{ notice && (
				<Notice status="success" onRemove={ () => setNotice( '' ) }>
					{ notice }
				</Notice>
			) }

			{ books === null && <Spinner /> }

			{ books && books.length === 0 && ! search && (
				<div className="wpbook-empty">
					<div className="wpbook-empty__art" aria-hidden="true">
						<span />
						<span />
						<span />
					</div>
					<h2>{ __( 'Create your first book', 'wp-book' ) }</h2>
					<p>
						{ __(
							'Pick a cover, choose your posts, and download a polished PDF in minutes.',
							'wp-book'
						) }
					</p>
					<Button
						variant="primary"
						icon={ plus }
						onClick={ () => navigate( 'new' ) }
					>
						{ __( 'New book', 'wp-book' ) }
					</Button>
				</div>
			) }

			{ books && books.length === 0 && search && (
				<p className="wpbook-muted">
					{ __( 'No books match your search.', 'wp-book' ) }
				</p>
			) }

			{ books && books.length > 0 && (
				<div className="wpbook-grid">
					{ books.map( ( book ) => (
						<BookCard
							key={ book.id }
							book={ book }
							onDelete={ () => remove( book ) }
							onDuplicate={ () => duplicate( book ) }
						/>
					) ) }
				</div>
			) }

			{ startNew && (
				<NewBookModal meta={ meta } onClose={ () => navigate( '' ) } />
			) }
		</div>
	);
}
