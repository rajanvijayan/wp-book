/**
 * Grid of cover designs. Uses the rendered thumbnail when available, else a CSS rendition.
 */
import { __ } from '@wordpress/i18n';
import CoverThumb from '../CoverThumb';

export default function CoverGallery( {
	covers,
	value,
	onChange,
	title,
	author,
} ) {
	return (
		<div
			className="wpbook-gallery is-covers"
			role="radiogroup"
			aria-label={ __( 'Cover designs', 'wp-book' ) }
		>
			{ covers.map( ( cover ) => {
				const selected = cover.key === value;
				return (
					<button
						type="button"
						key={ cover.key }
						role="radio"
						aria-checked={ selected }
						className={ `wpbook-gallery__item ${
							selected ? 'is-selected' : ''
						}` }
						onClick={ () => onChange( cover.key, cover ) }
					>
						{ cover.thumb ? (
							<img
								className="wpbook-gallery__thumb"
								src={ cover.thumb }
								alt=""
								loading="lazy"
							/>
						) : (
							<CoverThumb
								cover={ {
									template: cover.key,
									primary: cover.defaults.primary,
									accent: cover.defaults.accent,
									text: cover.defaults.text_color,
									title: title || cover.name,
									author: author || '',
								} }
							/>
						) }
						<span className="wpbook-gallery__name">
							{ cover.name }
						</span>
						<span className="wpbook-gallery__desc">
							{ cover.description }
						</span>
						{ cover.uses_image && (
							<span className="wpbook-badge is-subtle">
								{ __( 'Photo', 'wp-book' ) }
							</span>
						) }
					</button>
				);
			} ) }
		</div>
	);
}
