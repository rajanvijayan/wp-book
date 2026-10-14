/**
 * Interior step: template gallery, typography and the book's structure.
 */
import {
	RangeControl,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import TemplateGallery from '../gallery/TemplateGallery';
import { FontField } from './fields';

export default function InteriorPanel( { meta, config, setSection } ) {
	const interior = config.interior;
	const structure = config.structure;
	const set = ( values ) => setSection( 'interior', values );
	const setStructure = ( values ) => setSection( 'structure', values );

	return (
		<div className="wpbook-panel">
			<h2>{ __( 'Interior template', 'wp-book' ) }</h2>
			<TemplateGallery
				templates={ meta.templates }
				value={ interior.template }
				onChange={ ( template ) => {
					set( { template } );
					const picked = meta.templates.find(
						( t ) => t.key === template
					);
					if ( picked && picked.layout_defaults ) {
						setSection( 'layout', picked.layout_defaults );
					}
				} }
			/>

			<h3 className="wpbook-section-title">
				{ __( 'Typography', 'wp-book' ) }
			</h3>
			<div className="wpbook-fields is-2col">
				<FontField
					label={ __( 'Body font', 'wp-book' ) }
					value={ interior.font }
					fonts={ meta.fonts }
					allowDefault
					onChange={ ( font ) => set( { font } ) }
				/>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Font size (pt)', 'wp-book' ) }
					value={ interior.font_size }
					min={ 8 }
					max={ 18 }
					step={ 0.5 }
					onChange={ ( value ) => set( { font_size: value } ) }
				/>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Line height', 'wp-book' ) }
					value={ interior.line_height }
					min={ 1 }
					max={ 2.5 }
					step={ 0.05 }
					onChange={ ( value ) => set( { line_height: value } ) }
				/>
				<div>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Justify text', 'wp-book' ) }
						checked={ interior.justify }
						onChange={ ( justify ) => set( { justify } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Indent paragraphs (book style)',
							'wp-book'
						) }
						checked={ interior.indent }
						onChange={ ( indent ) => set( { indent } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Drop caps at chapter starts', 'wp-book' ) }
						checked={ interior.drop_caps }
						onChange={ ( value ) => set( { drop_caps: value } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Include images', 'wp-book' ) }
						checked={ interior.images }
						onChange={ ( images ) => set( { images } ) }
					/>
				</div>
			</div>

			<h3 className="wpbook-section-title">
				{ __( 'Front and back matter', 'wp-book' ) }
			</h3>
			<div className="wpbook-fields">
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Title page', 'wp-book' ) }
					checked={ structure.title_page }
					onChange={ ( value ) =>
						setStructure( { title_page: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Copyright page', 'wp-book' ) }
					checked={ structure.copyright }
					onChange={ ( copyright ) => setStructure( { copyright } ) }
				/>
				{ structure.copyright && (
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Copyright text', 'wp-book' ) }
						help={ __(
							'Leave empty for a standard “All rights reserved” notice.',
							'wp-book'
						) }
						value={ structure.copyright_text }
						rows={ 3 }
						onChange={ ( value ) =>
							setStructure( { copyright_text: value } )
						}
					/>
				) }
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Dedication', 'wp-book' ) }
					value={ structure.dedication }
					rows={ 2 }
					onChange={ ( dedication ) =>
						setStructure( { dedication } )
					}
				/>
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Preface', 'wp-book' ) }
					help={ __( 'Basic HTML is allowed.', 'wp-book' ) }
					value={ structure.preface }
					rows={ 4 }
					onChange={ ( preface ) => setStructure( { preface } ) }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Table of contents', 'wp-book' ) }
					checked={ structure.toc }
					onChange={ ( toc ) => setStructure( { toc } ) }
				/>
				{ structure.toc && (
					<div className="wpbook-fields is-2col">
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Contents title', 'wp-book' ) }
							value={ structure.toc_title }
							placeholder={ __( 'Contents', 'wp-book' ) }
							onChange={ ( value ) =>
								setStructure( { toc_title: value } )
							}
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Contents depth', 'wp-book' ) }
							value={ String( structure.toc_depth ) }
							options={ [
								{
									value: '1',
									label: __( 'Chapters only', 'wp-book' ),
								},
								{
									value: '2',
									label: __(
										'Chapters and headings',
										'wp-book'
									),
								},
								{
									value: '3',
									label: __(
										'Chapters, headings and subheadings',
										'wp-book'
									),
								},
							] }
							onChange={ ( v ) =>
								setStructure( { toc_depth: Number( v ) } )
							}
						/>
					</div>
				) }
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Number chapters', 'wp-book' ) }
					checked={ structure.chapter_numbers }
					onChange={ ( value ) =>
						setStructure( { chapter_numbers: value } )
					}
				/>
				{ structure.chapter_numbers && (
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Chapter label', 'wp-book' ) }
						value={ structure.chapter_label }
						placeholder={ __( 'Chapter', 'wp-book' ) }
						onChange={ ( value ) =>
							setStructure( { chapter_label: value } )
						}
					/>
				) }
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'About the author page', 'wp-book' ) }
					checked={ structure.about_author }
					onChange={ ( value ) =>
						setStructure( { about_author: value } )
					}
				/>
				{ structure.about_author && (
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'About the author', 'wp-book' ) }
						value={ structure.about_author_text }
						rows={ 4 }
						onChange={ ( value ) =>
							setStructure( { about_author_text: value } )
						}
					/>
				) }
			</div>
		</div>
	);
}
