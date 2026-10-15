/**
 * Small reusable controls for the editor panels.
 */
import {
	BaseControl,
	Button,
	ColorIndicator,
	ColorPicker,
	Dropdown,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

// Colour swatch that opens a picker with the theme palette.
export function ColorField( { label, value, onChange, palette = [] } ) {
	const id = useInstanceId( ColorField, 'wpbook-color' );
	return (
		<BaseControl
			id={ id }
			label={ label }
			className="wpbook-color-field"
			__nextHasNoMarginBottom
		>
			<Dropdown
				popoverProps={ { placement: 'bottom-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						onClick={ onToggle }
						aria-expanded={ isOpen }
						className="wpbook-color-field__toggle"
					>
						<ColorIndicator colorValue={ value } />
						<code>{ value }</code>
					</Button>
				) }
				renderContent={ () => (
					<div className="wpbook-color-field__popover">
						{ palette.length > 0 && (
							<div className="wpbook-swatches">
								{ palette.map( ( color ) => (
									<button
										type="button"
										key={ color }
										style={ { background: color } }
										aria-label={ color }
										onClick={ () => onChange( color ) }
									/>
								) ) }
							</div>
						) }
						<ColorPicker
							color={ value }
							onChange={ onChange }
							enableAlpha={ false }
						/>
					</div>
				) }
			/>
		</BaseControl>
	);
}

// Image picker using the WordPress media library.
export function MediaField( { label, value, onChange, help } ) {
	const id = useInstanceId( MediaField, 'wpbook-media' );
	const open = () => {
		const frame = window.wp.media( {
			title: label,
			library: { type: 'image' },
			multiple: false,
			button: { text: __( 'Use this image', 'wp-book' ) },
		} );
		frame.on( 'select', () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			onChange( attachment.id, attachment );
		} );
		frame.open();
	};
	return (
		<BaseControl
			id={ id }
			label={ label }
			help={ help }
			__nextHasNoMarginBottom
		>
			<div className="wpbook-media-field">
				<Button variant="secondary" onClick={ open }>
					{ value
						? __( 'Replace image', 'wp-book' )
						: __( 'Choose image', 'wp-book' ) }
				</Button>
				{ value > 0 && (
					<Button
						variant="tertiary"
						isDestructive
						onClick={ () => onChange( 0 ) }
					>
						{ __( 'Remove', 'wp-book' ) }
					</Button>
				) }
			</div>
		</BaseControl>
	);
}

// Numeric input with a unit suffix.
export function NumberField( {
	label,
	value,
	onChange,
	min,
	max,
	step = 1,
	unit,
} ) {
	return (
		<TextControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			type="number"
			label={ unit ? `${ label } (${ unit })` : label }
			value={ value }
			min={ min }
			max={ max }
			step={ step }
			onChange={ ( v ) => onChange( v === '' ? min : Number( v ) ) }
		/>
	);
}

// Font select built from the server's font list.
export function FontField( {
	label,
	value,
	onChange,
	fonts,
	allowDefault = false,
} ) {
	const options = fonts.map( ( f ) => ( { value: f.key, label: f.name } ) );
	if ( allowDefault ) {
		options.unshift( {
			value: '',
			label: __( 'Template default', 'wp-book' ),
		} );
	}
	return (
		<SelectControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ label }
			value={ value }
			options={ options }
			onChange={ onChange }
		/>
	);
}
