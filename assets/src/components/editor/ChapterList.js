/**
 * Sortable list of chapters and part dividers.
 */
import {
	closestCenter,
	DndContext,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	arrayMove,
	SortableContext,
	sortableKeyboardCoordinates,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
	Button,
	CheckboxControl,
	Dropdown,
	TextControl,
} from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { close, dragHandle, plus, settings } from '@wordpress/icons';

const keyOf = ( chapter, index ) =>
	chapter.type === 'post' ? `p${ chapter.id }` : `part${ index }`;

export default function ChapterList( {
	chapters,
	setChapters,
	posts,
	onAddPart,
} ) {
	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 4 } } ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);
	const ids = useMemo( () => chapters.map( keyOf ), [ chapters ] );

	const onDragEnd = ( { active, over } ) => {
		if ( over && active.id !== over.id ) {
			setChapters(
				arrayMove(
					chapters,
					ids.indexOf( active.id ),
					ids.indexOf( over.id )
				)
			);
		}
	};
	const update = ( index, values ) =>
		setChapters(
			chapters.map( ( c, i ) =>
				i === index ? { ...c, ...values } : c
			)
		);
	const remove = ( index ) =>
		setChapters( chapters.filter( ( c, i ) => i !== index ) );

	let number = 0;
	return (
		<div className="wpbook-chapters">
			{ chapters.length === 0 && (
				<div className="wpbook-chapters__empty">
					{ __(
						'No chapters yet. Pick posts on the right to start your book.',
						'wp-book'
					) }
				</div>
			) }
			<DndContext
				sensors={ sensors }
				collisionDetection={ closestCenter }
				onDragEnd={ onDragEnd }
			>
				<SortableContext
					items={ ids }
					strategy={ verticalListSortingStrategy }
				>
					<ol className="wpbook-chapters__list">
						{ chapters.map( ( chapter, index ) => {
							if ( chapter.type === 'post' ) {
								number++;
							}
							return (
								<SortableRow
									key={ ids[ index ] }
									id={ ids[ index ] }
									chapter={ chapter }
									number={ number }
									post={
										chapter.type === 'post'
											? posts[ chapter.id ]
											: null
									}
									onChange={ ( values ) =>
										update( index, values )
									}
									onRemove={ () => remove( index ) }
								/>
							);
						} ) }
					</ol>
				</SortableContext>
			</DndContext>
			<Button variant="secondary" icon={ plus } onClick={ onAddPart }>
				{ __( 'Add part divider', 'wp-book' ) }
			</Button>
		</div>
	);
}

function SortableRow( { id, chapter, number, post, onChange, onRemove } ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id } );
	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
	};

	if ( chapter.type === 'part' ) {
		return (
			<li
				ref={ setNodeRef }
				style={ style }
				className={ `wpbook-chapter is-part ${
					isDragging ? 'is-dragging' : ''
				}` }
			>
				<span
					className="wpbook-chapter__handle"
					{ ...attributes }
					{ ...listeners }
					aria-label={ __( 'Drag to reorder', 'wp-book' ) }
				>
					{ dragHandle }
				</span>
				<div className="wpbook-chapter__part-fields">
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Part title', 'wp-book' ) }
						hideLabelFromVision
						value={ chapter.title }
						onChange={ ( title ) => onChange( { title } ) }
						placeholder={ __( 'Part title', 'wp-book' ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Part subtitle', 'wp-book' ) }
						hideLabelFromVision
						value={ chapter.subtitle }
						onChange={ ( subtitle ) => onChange( { subtitle } ) }
						placeholder={ __( 'Subtitle (optional)', 'wp-book' ) }
					/>
				</div>
				<Button
					icon={ close }
					label={ __( 'Remove part', 'wp-book' ) }
					onClick={ onRemove }
					size="small"
				/>
			</li>
		);
	}

	const missing = ! post || post.missing || post.forbidden;
	const title =
		chapter.title ||
		( post && post.title ) ||
		sprintf(
			/* translators: %d: post ID */ __( 'Post #%d', 'wp-book' ),
			chapter.id
		);
	return (
		<li
			ref={ setNodeRef }
			style={ style }
			className={ `wpbook-chapter ${ isDragging ? 'is-dragging' : '' } ${
				missing ? 'is-missing' : ''
			}` }
		>
			<span
				className="wpbook-chapter__handle"
				{ ...attributes }
				{ ...listeners }
				aria-label={ __( 'Drag to reorder', 'wp-book' ) }
			>
				{ dragHandle }
			</span>
			<span className="wpbook-chapter__number">{ number }</span>
			<div className="wpbook-chapter__main">
				<span className="wpbook-chapter__title">{ title }</span>
				<span className="wpbook-chapter__meta">
					{ missing
						? __(
								'This post no longer exists or you cannot read it; it will be skipped.',
								'wp-book'
						  )
						: [
								post.type_name,
								post.status !== 'publish' ? post.status : '',
								post.words
									? sprintf(
											/* translators: %d: word count */ __(
												'%d words',
												'wp-book'
											),
											post.words
									  )
									: '',
						  ]
								.filter( Boolean )
								.join( ' · ' ) }
				</span>
			</div>
			<Dropdown
				popoverProps={ { placement: 'left-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						icon={ settings }
						label={ __( 'Chapter options', 'wp-book' ) }
						onClick={ onToggle }
						aria-expanded={ isOpen }
						size="small"
					/>
				) }
				renderContent={ () => (
					<div className="wpbook-chapter__options">
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Chapter title', 'wp-book' ) }
							help={ __(
								'Leave empty to use the post title.',
								'wp-book'
							) }
							value={ chapter.title }
							placeholder={ post && post.title }
							onChange={ ( value ) =>
								onChange( { title: value } )
							}
						/>
						{ [
							[
								'featured_image',
								__( 'Featured image', 'wp-book' ),
							],
							[ 'excerpt', __( 'Excerpt', 'wp-book' ) ],
							[ 'author', __( 'Author', 'wp-book' ) ],
							[ 'date', __( 'Date', 'wp-book' ) ],
						].map( ( [ key, label ] ) => (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={ key }
								label={ label }
								checked={ chapter.include[ key ] }
								onChange={ ( checked ) =>
									onChange( {
										include: {
											...chapter.include,
											[ key ]: checked,
										},
									} )
								}
							/>
						) ) }
					</div>
				) }
			/>
			<Button
				icon={ close }
				label={ __( 'Remove chapter', 'wp-book' ) }
				onClick={ onRemove }
				size="small"
			/>
		</li>
	);
}
