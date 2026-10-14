/**
 * Book editor: steps on the left, the current panel in the middle, live preview on the right.
 */
import { Button, Notice, Spinner } from '@wordpress/components';
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { arrowLeft, download } from '@wordpress/icons';
import { deleteFile, errorMessage, getBook, updateBook } from '../../api';
import { navigate } from '../../router';
import FileList from '../FileList';
import JobProgress from '../JobProgress';
import useJob from '../useJob';
import ContentPanel from './ContentPanel';
import CoverPanel from './CoverPanel';
import InteriorPanel from './InteriorPanel';
import LayoutPanel from './LayoutPanel';
import OutputPanel from './OutputPanel';
import PreviewPane from './PreviewPane';

const STEPS = [
	{
		key: 'content',
		label: __( 'Content', 'wp-book' ),
		hint: __( 'Chapters and parts', 'wp-book' ),
	},
	{
		key: 'cover',
		label: __( 'Cover', 'wp-book' ),
		hint: __( 'Front and back', 'wp-book' ),
	},
	{
		key: 'interior',
		label: __( 'Interior', 'wp-book' ),
		hint: __( 'Template and typography', 'wp-book' ),
	},
	{
		key: 'layout',
		label: __( 'Layout', 'wp-book' ),
		hint: __( 'Page size and margins', 'wp-book' ),
	},
	{
		key: 'output',
		label: __( 'Output', 'wp-book' ),
		hint: __( 'File, watermark, password', 'wp-book' ),
	},
	{
		key: 'downloads',
		label: __( 'Downloads', 'wp-book' ),
		hint: __( 'Generated PDFs', 'wp-book' ),
	},
];

export default function BookEditor( { bookId, meta } ) {
	const [ saved, setSaved ] = useState( null );
	const [ draft, setDraft ] = useState( null );
	const [ posts, setPosts ] = useState( {} );
	const [ files, setFiles ] = useState( [] );
	const [ step, setStep ] = useState( 'content' );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ showPreview, setShowPreview ] = useState( true );
	const pendingGenerate = useRef( false );
	const [ initialJob, setInitialJob ] = useState( undefined );

	const load = useCallback( () => {
		getBook( bookId )
			.then( ( book ) => {
				setSaved( { title: book.title, config: book.config } );
				setDraft( { title: book.title, config: book.config } );
				setPosts( book.posts || {} );
				setFiles( book.files || [] );
				setInitialJob( book.job );
			} )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [ bookId ] );

	useEffect( load, [ load ] );

	const dirty = useMemo(
		() =>
			saved &&
			draft &&
			JSON.stringify( saved ) !== JSON.stringify( draft ),
		[ saved, draft ]
	);

	// Warn before leaving with unsaved changes.
	useEffect( () => {
		const onBeforeUnload = ( event ) => {
			if ( dirty ) {
				event.preventDefault();
				event.returnValue = '';
			}
		};
		window.addEventListener( 'beforeunload', onBeforeUnload );
		return () =>
			window.removeEventListener( 'beforeunload', onBeforeUnload );
	}, [ dirty ] );

	const save = useCallback( () => {
		setSaving( true );
		setError( '' );
		return updateBook( bookId, {
			title: draft.title,
			config: draft.config,
		} )
			.then( ( book ) => {
				setSaved( { title: book.title, config: book.config } );
				setDraft( { title: book.title, config: book.config } );
				setPosts( book.posts || {} );
				setSaving( false );
				return book;
			} )
			.catch( ( e ) => {
				setSaving( false );
				setError( errorMessage( e ) );
				throw e;
			} );
	}, [ bookId, draft ] );

	const setTitle = ( title ) => setDraft( ( d ) => ( { ...d, title } ) );
	const setSection = ( section, values ) =>
		setDraft( ( d ) => ( {
			...d,
			config: {
				...d.config,
				[ section ]: { ...d.config[ section ], ...values },
			},
		} ) );
	const setChapters = ( chapters ) =>
		setDraft( ( d ) => ( {
			...d,
			config: { ...d.config, content: { chapters } },
		} ) );

	if ( error && ! draft ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}
	if ( ! draft || initialJob === undefined ) {
		return <Spinner />;
	}

	return (
		<EditorBody
			{ ...{
				bookId,
				meta,
				draft,
				posts,
				setPosts,
				files,
				setFiles,
				step,
				setStep,
				saving,
				dirty,
				save,
				error,
				setError,
				setTitle,
				setSection,
				setChapters,
				showPreview,
				setShowPreview,
				initialJob,
				pendingGenerate,
			} }
		/>
	);
}

function EditorBody( props ) {
	const {
		bookId,
		meta,
		draft,
		posts,
		setPosts,
		files,
		setFiles,
		step,
		setStep,
		saving,
		dirty,
		save,
		error,
		setError,
		setTitle,
		setSection,
		setChapters,
		showPreview,
		setShowPreview,
		initialJob,
	} = props;

	// The "ready" notice is only for a build that finishes while the editor is open.
	const [ justBuilt, setJustBuilt ] = useState( false );
	const {
		job,
		error: jobError,
		start,
		busy,
	} = useJob( bookId, initialJob, ( done ) => {
		setJustBuilt( true );
		if ( done.file ) {
			const fresh = [ done.print_cover, done.file ].filter( Boolean );
			setFiles( ( list ) => [
				...fresh,
				...list.filter(
					( f ) => ! fresh.some( ( n ) => n.id === f.id )
				),
			] );
		}
	} );

	const chapterCount = draft.config.content.chapters.filter(
		( c ) => c.type === 'post'
	).length;
	const latest = files.find( ( f ) => f.kind !== 'print-cover' );

	const generate = () => {
		const run = dirty ? save() : Promise.resolve();
		run.then( start ).catch( () => {} );
	};

	const removeFile = ( file ) =>
		deleteFile( bookId, file.id )
			.then( () =>
				setFiles( ( list ) => list.filter( ( f ) => f.id !== file.id ) )
			)
			.catch( ( e ) => setError( errorMessage( e ) ) );

	const panelProps = { meta, config: draft.config, setSection, bookId };

	return (
		<div
			className={ `wpbook-editor ${ showPreview ? 'has-preview' : '' }` }
		>
			<header className="wpbook-editor__bar">
				<Button
					icon={ arrowLeft }
					label={ __( 'All books', 'wp-book' ) }
					onClick={ () => navigate( '' ) }
				/>
				<input
					className="wpbook-editor__title"
					value={ draft.title }
					onChange={ ( e ) => setTitle( e.target.value ) }
					aria-label={ __( 'Book title', 'wp-book' ) }
				/>
				<span className="wpbook-editor__status">
					{ saving && __( 'Saving…', 'wp-book' ) }
					{ ! saving && dirty && __( 'Unsaved changes', 'wp-book' ) }
					{ ! saving && ! dirty && __( 'Saved', 'wp-book' ) }
				</span>
				<Button
					variant="tertiary"
					onClick={ () => setShowPreview( ! showPreview ) }
				>
					{ showPreview
						? __( 'Hide preview', 'wp-book' )
						: __( 'Show preview', 'wp-book' ) }
				</Button>
				<Button
					variant="secondary"
					onClick={ save }
					disabled={ ! dirty || saving }
					isBusy={ saving }
				>
					{ __( 'Save', 'wp-book' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ generate }
					isBusy={ busy }
					disabled={ busy || ! chapterCount || ! draft.title.trim() }
				>
					{ latest
						? __( 'Rebuild PDF', 'wp-book' )
						: __( 'Generate PDF', 'wp-book' ) }
				</Button>
				{ latest && ! busy && (
					<Button
						variant="primary"
						icon={ download }
						href={ latest.download }
						download={ latest.name }
					>
						{ __( 'Download', 'wp-book' ) }
					</Button>
				) }
			</header>

			{ ( busy || job.status === 'failed' || jobError || error ) && (
				<div className="wpbook-editor__messages">
					<JobProgress job={ job } />
					{ job.status === 'failed' && (
						<Notice status="error" isDismissible={ false }>
							{ sprintf(
								/* translators: %s: error message */
								__(
									'The PDF could not be built: %s',
									'wp-book'
								),
								job.message
							) }
						</Notice>
					) }
					{ ( jobError || error ) && (
						<Notice
							status="error"
							onRemove={ () => setError( '' ) }
						>
							{ jobError || error }
						</Notice>
					) }
				</div>
			) }
			{ justBuilt && job.status === 'done' && job.file && ! busy && (
				<Notice
					status="success"
					isDismissible={ false }
					className="wpbook-editor__done"
				>
					{ sprintf(
						/* translators: %d: number of pages */
						__( 'Your book is ready: %d pages.', 'wp-book' ),
						job.file.pages
					) }{ ' ' }
					<a href={ job.file.download } download={ job.file.name }>
						{ __( 'Download the PDF', 'wp-book' ) }
					</a>
				</Notice>
			) }
			{ justBuilt &&
				job.status === 'done' &&
				! busy &&
				job.missing_fonts?.length > 0 && (
					<Notice
						status="warning"
						isDismissible={ false }
						actions={ [
							{
								label: __( 'Get fonts', 'wp-book' ),
								onClick: () => setStep( 'interior' ),
							},
						] }
					>
						{ sprintf(
							/* translators: %s: font pack names */
							__(
								'Some text needs fonts that are not installed, so it may show as empty boxes: %s. Download them under Interior → Languages, then build again.',
								'wp-book'
							),
							job.missing_fonts
								.map( ( pack ) => pack.name )
								.join( ', ' )
						) }
					</Notice>
				) }

			<nav
				className="wpbook-editor__steps"
				aria-label={ __( 'Book settings', 'wp-book' ) }
			>
				{ STEPS.map( ( s, i ) => (
					<button
						type="button"
						key={ s.key }
						className={ step === s.key ? 'is-active' : '' }
						aria-current={ step === s.key ? 'step' : undefined }
						onClick={ () => setStep( s.key ) }
					>
						<span className="wpbook-step-number">{ i + 1 }</span>
						<span>
							<strong>{ s.label }</strong>
							<small>
								{ s.key === 'content'
									? sprintf(
											/* translators: %d: number of chapters */
											__( '%d chapters', 'wp-book' ),
											chapterCount
									  )
									: s.hint }
							</small>
						</span>
					</button>
				) ) }
			</nav>

			<main className="wpbook-editor__panel">
				{ step === 'content' && (
					<ContentPanel
						{ ...panelProps }
						posts={ posts }
						setPosts={ setPosts }
						chapters={ draft.config.content.chapters }
						setChapters={ setChapters }
					/>
				) }
				{ step === 'cover' && (
					<CoverPanel { ...panelProps } title={ draft.title } />
				) }
				{ step === 'interior' && <InteriorPanel { ...panelProps } /> }
				{ step === 'layout' && <LayoutPanel { ...panelProps } /> }
				{ step === 'output' && (
					<OutputPanel
						{ ...panelProps }
						pages={ latest ? latest.pages : 0 }
					/>
				) }
				{ step === 'downloads' && (
					<div className="wpbook-panel">
						<h2>{ __( 'Downloads', 'wp-book' ) }</h2>
						<p className="wpbook-muted">
							{ sprintf(
								/* translators: %d: number of files kept */
								__(
									'The last %d PDFs are kept. Older ones are deleted automatically.',
									'wp-book'
								),
								draft.config.output.keep
							) }
						</p>
						<FileList files={ files } onDelete={ removeFile } />
					</div>
				) }
			</main>

			{ showPreview && (
				<PreviewPane
					bookId={ bookId }
					draft={ draft }
					hasChapters={ chapterCount > 0 }
				/>
			) }
		</div>
	);
}
