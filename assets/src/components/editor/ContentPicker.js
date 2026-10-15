/**
 * Search and filter the site's posts and add them to the book.
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	CheckboxControl,
	SearchControl,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { errorMessage, searchContent } from '../../api';

export default function ContentPicker( { meta, inBook, onAdd } ) {
	const [ postType, setPostType ] = useState(
		meta.post_types[ 0 ] ? meta.post_types[ 0 ].slug : 'post'
	);
	const [ search, setSearch ] = useState( '' );
	const [ status, setStatus ] = useState( 'publish' );
	const [ order, setOrder ] = useState( 'date:asc' );
	const [ taxonomy, setTaxonomy ] = useState( '' );
	const [ term, setTerm ] = useState( '' );
	const [ terms, setTerms ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ result, setResult ] = useState( null );
	const [ selected, setSelected ] = useState( {} );
	const [ error, setError ] = useState( '' );

	const type = meta.post_types.find( ( t ) => t.slug === postType ) || {
		taxonomies: [],
	};

	// Load the terms of the chosen taxonomy (core REST API).
	useEffect( () => {
		setTerm( '' );
		setTerms( [] );
		const tax = type.taxonomies.find( ( t ) => t.slug === taxonomy );
		if ( ! tax ) {
			return;
		}
		apiFetch( {
			path: `/${ tax.rest_ns }/${ tax.rest_base }?per_page=100&orderby=count&order=desc&_fields=id,name,count`,
		} )
			.then( setTerms )
			.catch( () => setTerms( [] ) );
	}, [ taxonomy, postType ] ); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => {
		setResult( null );
		const [ orderby, dir ] = order.split( ':' );
		const t = setTimeout(
			() => {
				searchContent( {
					post_type: postType,
					search,
					status,
					orderby,
					order: dir,
					taxonomy: term ? taxonomy : '',
					terms: term ? [ term ] : [],
					page,
					per_page: 30,
				} )
					.then( setResult )
					.catch( ( e ) => setError( errorMessage( e ) ) );
			},
			search ? 300 : 0
		);
		return () => clearTimeout( t );
	}, [ postType, search, status, order, taxonomy, term, page ] );

	const items = result ? result.items : [];
	const chosen = items.filter( ( item ) => selected[ item.id ] );
	const addable = items.filter( ( item ) => ! inBook.has( item.id ) );

	const add = ( list ) => {
		onAdd( list );
		setSelected( {} );
	};

	return (
		<div className="wpbook-picker">
			<div className="wpbook-picker__filters">
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Type', 'wp-book' ) }
					value={ postType }
					options={ meta.post_types.map( ( t ) => ( {
						value: t.slug,
						label: t.label,
					} ) ) }
					onChange={ ( v ) => {
						setPostType( v );
						setTaxonomy( '' );
						setPage( 1 );
					} }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Status', 'wp-book' ) }
					value={ status }
					options={ [
						{
							value: 'publish',
							label: __( 'Published', 'wp-book' ),
						},
						{ value: 'private', label: __( 'Private', 'wp-book' ) },
						{ value: 'draft', label: __( 'Drafts', 'wp-book' ) },
						{ value: 'any', label: __( 'Any', 'wp-book' ) },
					] }
					onChange={ ( v ) => {
						setStatus( v );
						setPage( 1 );
					} }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Order', 'wp-book' ) }
					value={ order }
					options={ [
						{
							value: 'date:asc',
							label: __( 'Oldest first', 'wp-book' ),
						},
						{
							value: 'date:desc',
							label: __( 'Newest first', 'wp-book' ),
						},
						{
							value: 'title:asc',
							label: __( 'Title A–Z', 'wp-book' ),
						},
						{
							value: 'menu_order:asc',
							label: __( 'Menu order', 'wp-book' ),
						},
					] }
					onChange={ setOrder }
				/>
				{ type.taxonomies.length > 0 && (
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Filter by', 'wp-book' ) }
						value={ taxonomy }
						options={ [
							{ value: '', label: __( 'Everything', 'wp-book' ) },
						].concat(
							type.taxonomies.map( ( t ) => ( {
								value: t.slug,
								label: t.label,
							} ) )
						) }
						onChange={ ( v ) => {
							setTaxonomy( v );
							setPage( 1 );
						} }
					/>
				) }
				{ taxonomy && (
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Term', 'wp-book' ) }
						value={ term }
						options={ [
							{ value: '', label: __( 'Choose…', 'wp-book' ) },
						].concat(
							terms.map( ( t ) => ( {
								value: String( t.id ),
								label: `${ t.name } (${ t.count })`,
							} ) )
						) }
						onChange={ ( v ) => {
							setTerm( v );
							setPage( 1 );
						} }
					/>
				) }
			</div>
			<SearchControl
				__nextHasNoMarginBottom
				value={ search }
				onChange={ ( v ) => {
					setSearch( v );
					setPage( 1 );
				} }
				placeholder={ __( 'Search posts', 'wp-book' ) }
			/>
			{ error && <p className="wpbook-error">{ error }</p> }
			{ ! result && <Spinner /> }
			{ result && (
				<>
					<div className="wpbook-picker__toolbar">
						<span className="wpbook-muted">
							{ sprintf(
								/* translators: %d: number of posts found */
								__( '%d found', 'wp-book' ),
								result.total
							) }
						</span>
						<Button
							variant="secondary"
							size="small"
							disabled={ ! chosen.length }
							onClick={ () => add( chosen ) }
						>
							{ sprintf(
								/* translators: %d: number of selected posts */
								__( 'Add selected (%d)', 'wp-book' ),
								chosen.length
							) }
						</Button>
						<Button
							variant="tertiary"
							size="small"
							disabled={ ! addable.length }
							onClick={ () => add( addable ) }
						>
							{ __( 'Add all shown', 'wp-book' ) }
						</Button>
					</div>
					<ul className="wpbook-picker__list">
						{ items.map( ( item ) => {
							const added = inBook.has( item.id );
							return (
								<li
									key={ item.id }
									className={ added ? 'is-added' : '' }
								>
									<CheckboxControl
										__nextHasNoMarginBottom
										checked={
											added || !! selected[ item.id ]
										}
										disabled={ added }
										onChange={ ( checked ) =>
											setSelected( {
												...selected,
												[ item.id ]: checked,
											} )
										}
										label={
											item.title ||
											__( '(no title)', 'wp-book' )
										}
									/>
									<span className="wpbook-picker__meta">
										{ new Date(
											item.date
										).toLocaleDateString() }
										{ item.status !== 'publish' &&
											` · ${ item.status }` }
									</span>
									{ ! added && (
										<Button
											icon={ plus }
											size="small"
											label={ __( 'Add', 'wp-book' ) }
											onClick={ () => add( [ item ] ) }
										/>
									) }
								</li>
							);
						} ) }
					</ul>
					{ result.pages > 1 && (
						<div className="wpbook-picker__pages">
							<Button
								size="small"
								disabled={ page <= 1 }
								onClick={ () => setPage( page - 1 ) }
							>
								{ __( 'Previous', 'wp-book' ) }
							</Button>
							<span>
								{ sprintf(
									/* translators: 1: page, 2: total pages */
									__( 'Page %1$d of %2$d', 'wp-book' ),
									page,
									result.pages
								) }
							</span>
							<Button
								size="small"
								disabled={ page >= result.pages }
								onClick={ () => setPage( page + 1 ) }
							>
								{ __( 'Next', 'wp-book' ) }
							</Button>
						</div>
					) }
				</>
			) }
		</div>
	);
}
