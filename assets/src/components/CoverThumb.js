/**
 * A small CSS rendition of a book's cover, built from its colours and title.
 */
const PHOTO = [ 'photo', 'photo-frame', 'split', 'magazine' ];

export default function CoverThumb( { cover, size = 'medium' } ) {
	const { template = 'aurora', primary, accent, text, title, author } = cover;
	const style = {
		'--wpbook-primary': primary,
		'--wpbook-accent': accent,
		'--wpbook-text': text,
	};
	return (
		<div
			className={ `wpbook-cover-thumb is-${ size } is-${ template } ${
				PHOTO.includes( template ) ? 'is-photo' : ''
			}` }
			style={ style }
			aria-hidden="true"
		>
			<span className="wpbook-cover-thumb__title">{ title }</span>
			<span className="wpbook-cover-thumb__author">{ author }</span>
		</div>
	);
}
