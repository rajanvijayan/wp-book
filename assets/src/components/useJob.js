/**
 * Start and follow a book generation job.
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { errorMessage, generateBook, getJob } from '../api';

const ACTIVE = [ 'queued', 'running' ];

export default function useJob( bookId, initialJob, onDone ) {
	const [ job, setJob ] = useState( initialJob || { status: 'idle' } );
	const [ error, setError ] = useState( '' );
	const timer = useRef();
	const doneRef = useRef( onDone );
	doneRef.current = onDone;

	const poll = useCallback( () => {
		clearTimeout( timer.current );
		timer.current = setTimeout( () => {
			getJob( bookId )
				.then( ( next ) => {
					setJob( next );
					if ( ACTIVE.includes( next.status ) ) {
						poll();
					} else if ( next.status === 'done' && doneRef.current ) {
						doneRef.current( next );
					}
				} )
				.catch( ( e ) => setError( errorMessage( e ) ) );
		}, 1500 );
	}, [ bookId ] );

	useEffect( () => {
		if ( initialJob && ACTIVE.includes( initialJob.status ) ) {
			poll();
		}
		return () => clearTimeout( timer.current );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	const start = useCallback( () => {
		setError( '' );
		setJob( { status: 'queued', done: 0, total: 0 } );
		generateBook( bookId )
			.then( ( next ) => {
				setJob( next );
				poll();
			} )
			.catch( ( e ) => {
				setJob( { status: 'idle' } );
				setError( errorMessage( e ) );
			} );
	}, [ bookId, poll ] );

	return {
		job,
		error,
		start,
		busy: ACTIVE.includes( job.status ),
	};
}
