/**
 * Generated files of a book with download and delete actions.
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { download, trash } from '@wordpress/icons';

export const formatSize = ( bytes ) =>
	bytes > 1048576
		? `${ ( bytes / 1048576 ).toFixed( 1 ) } MB`
		: `${ Math.max( 1, Math.round( bytes / 1024 ) ) } KB`;

export const formatDate = ( iso ) =>
	new Date( iso ).toLocaleString( undefined, {
		dateStyle: 'medium',
		timeStyle: 'short',
	} );

export default function FileList( { files, onDelete } ) {
	if ( ! files.length ) {
		return (
			<p className="wpbook-muted">
				{ __(
					'No PDFs yet. Generate the book to create one.',
					'wp-book'
				) }
			</p>
		);
	}
	return (
		<table className="wpbook-files widefat striped">
			<thead>
				<tr>
					<th>{ __( 'Created', 'wp-book' ) }</th>
					<th>{ __( 'Pages', 'wp-book' ) }</th>
					<th>{ __( 'Size', 'wp-book' ) }</th>
					<th className="wpbook-files__actions">
						<span className="screen-reader-text">
							{ __( 'Actions', 'wp-book' ) }
						</span>
					</th>
				</tr>
			</thead>
			<tbody>
				{ files.map( ( file, index ) => (
					<tr key={ file.id }>
						<td>
							{ formatDate( file.created ) }
							{ index === 0 && (
								<span className="wpbook-badge">
									{ __( 'Latest', 'wp-book' ) }
								</span>
							) }
						</td>
						<td>{ file.pages }</td>
						<td>{ formatSize( file.size ) }</td>
						<td className="wpbook-files__actions">
							<Button
								variant={
									index === 0 ? 'primary' : 'secondary'
								}
								size="compact"
								icon={ download }
								href={ file.download }
								download={ file.name }
							>
								{ __( 'Download', 'wp-book' ) }
							</Button>
							{ onDelete && (
								<Button
									size="compact"
									icon={ trash }
									isDestructive
									label={ sprintf(
										/* translators: %s: date */
										__(
											'Delete the PDF from %s',
											'wp-book'
										),
										formatDate( file.created )
									) }
									onClick={ () => onDelete( file ) }
								/>
							) }
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
}
