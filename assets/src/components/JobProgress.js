/**
 * Progress bar for a generation job.
 */
import { __, sprintf } from '@wordpress/i18n';

export default function JobProgress( { job } ) {
	if ( ! job || ! [ 'queued', 'running' ].includes( job.status ) ) {
		return null;
	}
	const percent = job.total
		? Math.round( ( job.done / job.total ) * 100 )
		: 0;
	const label =
		job.status === 'queued'
			? __( 'Waiting to start…', 'wp-book' )
			: sprintf(
					/* translators: 1: chapters done, 2: total chapters */
					__( 'Building chapter %1$d of %2$d…', 'wp-book' ),
					Math.min( job.done + 1, job.total ),
					job.total
			  );
	return (
		<div className="wpbook-progress" role="status" aria-live="polite">
			<div className="wpbook-progress__bar">
				<span
					className={
						job.status === 'queued' ? 'is-indeterminate' : ''
					}
					style={ {
						width:
							job.status === 'queued'
								? '35%'
								: `${ Math.max( 4, percent ) }%`,
					} }
				/>
			</div>
			<span className="wpbook-progress__label">{ label }</span>
		</div>
	);
}
