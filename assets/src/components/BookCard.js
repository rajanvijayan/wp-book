/**
 * One book in the list: cover, stats, generate and download.
 */
import {
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	copy,
	download,
	edit,
	moreVertical,
	trash,
	update,
} from '@wordpress/icons';
import { navigate } from '../router';
import CoverThumb from './CoverThumb';
import { formatDate, formatSize } from './FileList';
import JobProgress from './JobProgress';
import useJob from './useJob';

export default function BookCard( { book, onDelete, onDuplicate } ) {
	const [ latest, setLatest ] = useState( book.latest_file );
	const { job, error, start, busy } = useJob( book.id, book.job, ( done ) => {
		if ( done.file ) {
			setLatest( done.file );
		}
	} );

	return (
		<article className="wpbook-card">
			<button
				type="button"
				className="wpbook-card__cover"
				onClick={ () => navigate( `book/${ book.id }` ) }
				aria-label={ sprintf(
					/* translators: %s: book title */
					__( 'Edit %s', 'wp-book' ),
					book.title
				) }
			>
				<CoverThumb cover={ book.cover } />
			</button>
			<div className="wpbook-card__body">
				<h2 className="wpbook-card__title">
					<a href={ `#/book/${ book.id }` }>{ book.title }</a>
				</h2>
				<p className="wpbook-card__meta">
					{ sprintf(
						/* translators: %d: number of chapters */
						_n(
							'%d chapter',
							'%d chapters',
							book.chapters,
							'wp-book'
						),
						book.chapters
					) }
					{ latest &&
						' · ' +
							sprintf(
								/* translators: %d: number of pages */
								_n(
									'%d page',
									'%d pages',
									latest.pages,
									'wp-book'
								),
								latest.pages
							) }
				</p>
				<JobProgress job={ job } />
				{ error && <p className="wpbook-error">{ error }</p> }
				{ job.status === 'failed' && (
					<p className="wpbook-error">{ job.message }</p>
				) }
				{ ! busy && latest && (
					<p className="wpbook-card__file">
						{ sprintf(
							/* translators: 1: date, 2: file size */
							__( 'Last built %1$s · %2$s', 'wp-book' ),
							formatDate( latest.created ),
							formatSize( latest.size )
						) }
					</p>
				) }
				<div className="wpbook-card__actions">
					{ latest && ! busy && (
						<Button
							variant="primary"
							icon={ download }
							href={ latest.download }
							download={ latest.name }
						>
							{ __( 'Download', 'wp-book' ) }
						</Button>
					) }
					<Button
						variant={ latest ? 'secondary' : 'primary' }
						icon={ update }
						onClick={ start }
						isBusy={ busy }
						disabled={ busy || ! book.chapters }
					>
						{ latest
							? __( 'Rebuild', 'wp-book' )
							: __( 'Generate', 'wp-book' ) }
					</Button>
					<DropdownMenu
						icon={ moreVertical }
						label={ __( 'More actions', 'wp-book' ) }
					>
						{ ( { onClose } ) => (
							<MenuGroup>
								<MenuItem
									icon={ edit }
									onClick={ () =>
										navigate( `book/${ book.id }` )
									}
								>
									{ __( 'Edit', 'wp-book' ) }
								</MenuItem>
								<MenuItem
									icon={ copy }
									onClick={ () => {
										onDuplicate();
										onClose();
									} }
								>
									{ __( 'Duplicate', 'wp-book' ) }
								</MenuItem>
								<MenuItem
									icon={ trash }
									isDestructive
									onClick={ () => {
										onDelete();
										onClose();
									} }
								>
									{ __( 'Delete', 'wp-book' ) }
								</MenuItem>
							</MenuGroup>
						) }
					</DropdownMenu>
				</div>
			</div>
		</article>
	);
}
