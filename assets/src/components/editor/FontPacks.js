/**
 * Font packs for non-Latin scripts: status, download with progress, and removal.
 */
import { Button, Notice, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { getFontPacks, installFontFile, removeFontPack } from '../../api';
import { formatSize } from '../FileList';

export default function FontPacks() {
	const [ packs, setPacks ] = useState( null );
	const [ canInstall, setCanInstall ] = useState( false );
	const [ working, setWorking ] = useState( '' );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		getFontPacks()
			.then( ( data ) => {
				setPacks( data.packs );
				setCanInstall( data.can_install );
			} )
			.catch( ( e ) => setError( e.message ) );
	}, [] );

	const update = ( pack ) =>
		setPacks( ( list ) =>
			list.map( ( p ) => ( p.key === pack.key ? pack : p ) )
		);

	// One file per request, so no request has to fetch the whole pack.
	const install = async ( key ) => {
		setWorking( key );
		setError( '' );
		try {
			let pack;
			do {
				pack = await installFontFile( key );
				update( pack );
			} while ( ! pack.complete );
		} catch ( e ) {
			setError( e.message );
		}
		setWorking( '' );
	};

	const remove = async ( key ) => {
		setWorking( key );
		setError( '' );
		try {
			update( await removeFontPack( key ) );
		} catch ( e ) {
			setError( e.message );
		}
		setWorking( '' );
	};

	if ( ! packs ) {
		return error ? (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		) : (
			<Spinner />
		);
	}

	return (
		<div className="wpbook-font-packs">
			<p className="wpbook-muted">
				{ __(
					'Latin, Greek and Cyrillic text works out of the box. For other scripts, download a font pack. The fonts come from the mPDF project on GitHub and are stored in your uploads folder.',
					'wp-book'
				) }
			</p>
			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			{ packs.map( ( pack ) => (
				<div className="wpbook-font-pack" key={ pack.key }>
					<div className="wpbook-font-pack__text">
						<strong>{ pack.name }</strong>
						<span className="wpbook-font-pack__desc">
							{ pack.description }
						</span>
						{ working === pack.key && ! pack.complete && (
							<div
								className="wpbook-progress"
								role="status"
								aria-live="polite"
							>
								<div className="wpbook-progress__bar">
									<span
										style={ {
											width: `${ Math.max(
												4,
												Math.round(
													( pack.installed /
														pack.files ) *
														100
												)
											) }%`,
										} }
									/>
								</div>
								<span className="wpbook-progress__label">
									{ sprintf(
										/* translators: 1: files downloaded, 2: total files */
										__(
											'Downloaded %1$d of %2$d files…',
											'wp-book'
										),
										pack.installed,
										pack.files
									) }
								</span>
							</div>
						) }
					</div>
					<div className="wpbook-font-pack__action">
						{ pack.complete && (
							<span className="wpbook-badge">
								{ __( 'Installed', 'wp-book' ) }
							</span>
						) }
						{ canInstall && ! pack.complete && (
							<Button
								variant="secondary"
								isBusy={ working === pack.key }
								disabled={ !! working }
								onClick={ () => install( pack.key ) }
							>
								{ sprintf(
									/* translators: %s: download size */
									__( 'Download (%s)', 'wp-book' ),
									formatSize( pack.size )
								) }
							</Button>
						) }
						{ canInstall && pack.complete && (
							<Button
								variant="tertiary"
								isDestructive
								disabled={ !! working }
								onClick={ () => remove( pack.key ) }
							>
								{ __( 'Remove', 'wp-book' ) }
							</Button>
						) }
						{ ! canInstall && ! pack.complete && (
							<span className="wpbook-muted">
								{ __(
									'Ask an administrator to install it.',
									'wp-book'
								) }
							</span>
						) }
					</div>
				</div>
			) ) }
		</div>
	);
}
