/**
 * BookPress admin app entry point.
 */
import { createRoot, render } from '@wordpress/element';
import App from './components/App';
import './style.scss';

const mount = () => {
	const root = document.getElementById( 'wpbook-root' );
	if ( ! root ) {
		return;
	}
	if ( createRoot ) {
		createRoot( root ).render( <App /> );
	} else {
		render( <App />, root );
	}
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mount );
} else {
	mount();
}
