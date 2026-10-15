/**
 * Grid of interior templates with a two-page spread thumbnail.
 */
import { __ } from '@wordpress/i18n';

export default function TemplateGallery( { templates, value, onChange } ) {
	return (
		<div
			className="wpbook-gallery is-templates"
			role="radiogroup"
			aria-label={ __( 'Interior templates', 'wp-book' ) }
		>
			{ templates.map( ( template ) => {
				const selected = template.key === value;
				return (
					<button
						type="button"
						key={ template.key }
						role="radio"
						aria-checked={ selected }
						className={ `wpbook-gallery__item ${
							selected ? 'is-selected' : ''
						}` }
						onClick={ () => onChange( template.key ) }
					>
						{ template.thumb ? (
							<img
								className="wpbook-gallery__thumb is-spread"
								src={ template.thumb }
								alt=""
								loading="lazy"
							/>
						) : (
							<div
								className={ `wpbook-spread is-${ template.key }` }
								aria-hidden="true"
							>
								<span className="wpbook-spread__page">
									<i className="is-heading" />
									<i />
									<i />
									<i className="is-short" />
								</span>
								<span className="wpbook-spread__page">
									<i />
									<i />
									<i />
									<i className="is-short" />
								</span>
							</div>
						) }
						<span className="wpbook-gallery__name">
							{ template.name }
						</span>
						<span className="wpbook-gallery__desc">
							{ template.description }
						</span>
					</button>
				);
			} ) }
		</div>
	);
}
