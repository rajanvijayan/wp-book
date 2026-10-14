/**
 * Thin wrappers around the wp-book/v2 REST API.
 */
import apiFetch from '@wordpress/api-fetch';

const NS = '/wp-book/v2';

export const getMeta = () => apiFetch( { path: `${ NS }/meta` } );

export const listBooks = ( { page = 1, search = '' } = {} ) =>
	apiFetch( {
		path: `${ NS }/books?page=${ page }&per_page=50&search=${ encodeURIComponent(
			search
		) }`,
		parse: false,
	} ).then( async ( response ) => ( {
		items: await response.json(),
		total: Number( response.headers.get( 'X-WP-Total' ) || 0 ),
	} ) );

export const getBook = ( id ) => apiFetch( { path: `${ NS }/books/${ id }` } );

export const createBook = ( title, config = {} ) =>
	apiFetch( {
		path: `${ NS }/books`,
		method: 'POST',
		data: { title, config },
	} );

export const updateBook = ( id, data ) =>
	apiFetch( { path: `${ NS }/books/${ id }`, method: 'POST', data } );

export const deleteBook = ( id ) =>
	apiFetch( { path: `${ NS }/books/${ id }`, method: 'DELETE' } );

export const duplicateBook = ( id ) =>
	apiFetch( { path: `${ NS }/books/${ id }/duplicate`, method: 'POST' } );

export const generateBook = ( id ) =>
	apiFetch( { path: `${ NS }/books/${ id }/generate`, method: 'POST' } );

export const getJob = ( id ) =>
	apiFetch( { path: `${ NS }/books/${ id }/job` } );

export const deleteFile = ( id, fileId ) =>
	apiFetch( {
		path: `${ NS }/books/${ id }/files/${ fileId }`,
		method: 'DELETE',
	} );

export const getAutoCover = ( id ) =>
	apiFetch( { path: `${ NS }/books/${ id }/auto-cover` } );

export const searchContent = ( params ) => {
	const query = new URLSearchParams();
	Object.entries( params ).forEach( ( [ key, value ] ) => {
		if ( Array.isArray( value ) ) {
			value.forEach( ( v ) => query.append( `${ key }[]`, v ) );
		} else if ( value !== '' && value !== undefined && value !== null ) {
			query.append( key, value );
		}
	} );
	return apiFetch( {
		path: `${ NS }/content?${ query.toString() }`,
		parse: false,
	} ).then( async ( response ) => ( {
		items: await response.json(),
		total: Number( response.headers.get( 'X-WP-Total' ) || 0 ),
		pages: Number( response.headers.get( 'X-WP-TotalPages' ) || 0 ),
	} ) );
};

/**
 * Render a preview PDF (cover only or a sample) and return an object URL for an iframe.
 *
 * @param {number}      id     Book ID.
 * @param {string}      mode   'cover' or 'sample'.
 * @param {Object}      config Unsaved settings to apply.
 * @param {string}      title  Unsaved title.
 * @param {AbortSignal} signal Abort signal.
 * @return {Promise<string>} Object URL.
 */
export const previewBook = ( id, mode, config, title, signal ) =>
	apiFetch( {
		path: `${ NS }/books/${ id }/preview`,
		method: 'POST',
		data: { mode, config, title },
		parse: false,
		signal,
	} ).then( async ( response ) =>
		URL.createObjectURL( await response.blob() )
	);

/**
 * Turn an apiFetch error into a readable message.
 *
 * @param {*} error Error thrown by apiFetch.
 * @return {string} Message.
 */
export const errorMessage = ( error ) =>
	( error && error.message ) || String( error || '' );

export const getFontPacks = () => apiFetch( { path: `${ NS }/fonts` } );

export const installFontFile = ( pack ) =>
	apiFetch( { path: `${ NS }/fonts/${ pack }`, method: 'POST' } );

export const removeFontPack = ( pack ) =>
	apiFetch( { path: `${ NS }/fonts/${ pack }`, method: 'DELETE' } );
