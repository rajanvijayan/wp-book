/**
 * Minimal hash router: #/ (books), #/new, #/book/12.
 */
import { useEffect, useState } from '@wordpress/element';

const parse = () => {
	const hash = window.location.hash.replace( /^#\/?/, '' );
	const [ name = '', id = '' ] = hash.split( '/' );
	return { name: name || 'books', id: id ? Number( id ) : 0 };
};

export const useRoute = () => {
	const [ route, setRoute ] = useState( parse );
	useEffect( () => {
		const onChange = () => setRoute( parse() );
		window.addEventListener( 'hashchange', onChange );
		return () => window.removeEventListener( 'hashchange', onChange );
	}, [] );
	return route;
};

export const navigate = ( path ) => {
	window.location.hash = '#/' + path.replace( /^\//, '' );
};
