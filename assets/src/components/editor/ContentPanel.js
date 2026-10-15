/**
 * Content step: ordered chapter list plus a picker to add posts and part dividers.
 */
import { __ } from '@wordpress/i18n';
import ChapterList from './ChapterList';
import ContentPicker from './ContentPicker';

export default function ContentPanel( {
	meta,
	chapters,
	setChapters,
	posts,
	setPosts,
} ) {
	const inBook = new Set(
		chapters.filter( ( c ) => c.type === 'post' ).map( ( c ) => c.id )
	);

	const addPosts = ( items ) => {
		const fresh = items.filter( ( item ) => ! inBook.has( item.id ) );
		if ( ! fresh.length ) {
			return;
		}
		setPosts( ( map ) => {
			const next = { ...map };
			fresh.forEach( ( item ) => ( next[ item.id ] = item ) );
			return next;
		} );
		setChapters( [
			...chapters,
			...fresh.map( ( item ) => ( {
				type: 'post',
				id: item.id,
				title: '',
				include: {
					featured_image: true,
					excerpt: false,
					author: false,
					date: false,
				},
			} ) ),
		] );
	};

	const addPart = () =>
		setChapters( [
			...chapters,
			{ type: 'part', title: __( 'New part', 'wp-book' ), subtitle: '' },
		] );

	return (
		<div className="wpbook-panel wpbook-content">
			<div className="wpbook-content__chapters">
				<h2>{ __( 'Chapters', 'wp-book' ) }</h2>
				<p className="wpbook-muted">
					{ __(
						'Drag to reorder. Each post becomes a chapter; parts group chapters together.',
						'wp-book'
					) }
				</p>
				<ChapterList
					chapters={ chapters }
					setChapters={ setChapters }
					posts={ posts }
					onAddPart={ addPart }
				/>
			</div>
			<div className="wpbook-content__picker">
				<h2>{ __( 'Add content', 'wp-book' ) }</h2>
				<ContentPicker
					meta={ meta }
					inBook={ inBook }
					onAdd={ addPosts }
				/>
			</div>
		</div>
	);
}
