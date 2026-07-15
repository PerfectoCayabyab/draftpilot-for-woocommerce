import { useState } from '@wordpress/element';
import {
	Button,
	TextControl,
	TextareaControl,
	SelectControl,
	Notice,
	ExternalLink,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const SettingsTab = ( { config, onSaved } ) => {
	const [ values, setValues ] = useState( config.settings );
	const [ saving, setSaving ] = useState( false );
	const [ saved, setSaved ] = useState( false );

	const set = ( key ) => ( value ) => {
		setSaved( false );
		setValues( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const save = async () => {
		setSaving( true );
		const res = await apiFetch( {
			path: '/draftpilot/v1/settings',
			method: 'POST',
			data: values,
		} );
		onSaved( { ...config, settings: res.settings } );
		setValues( res.settings );
		setSaving( false );
		setSaved( true );
	};

	return (
		<div className="draftpilot-settings">
			{ saved && (
				<Notice status="success" onRemove={ () => setSaved( false ) }>
					{ __( 'Settings saved.', 'draftpilot-for-woocommerce' ) }
				</Notice>
			) }

			<TextControl
				label={ __( 'Gemini API key', 'draftpilot-for-woocommerce' ) }
				type="password"
				value={ values.api_key }
				onChange={ set( 'api_key' ) }
				help={
					<>
						{ __( 'Get a free key at ', 'draftpilot-for-woocommerce' ) }
						<ExternalLink href="https://aistudio.google.com/apikey">
							Google AI Studio
						</ExternalLink>
					</>
				}
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>

			<TextControl
				label={ __( 'Model', 'draftpilot-for-woocommerce' ) }
				value={ values.model }
				onChange={ set( 'model' ) }
				help={ __( 'Gemini model ID, e.g. gemini-3.5-flash.', 'draftpilot-for-woocommerce' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>

			<SelectControl
				label={ __( 'Default tone', 'draftpilot-for-woocommerce' ) }
				value={ values.default_tone }
				options={ Object.entries( config.tones ).map(
					( [ value, label ] ) => ( { value, label } )
				) }
				onChange={ set( 'default_tone' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>

			<TextareaControl
				label={ __( 'Brand voice notes', 'draftpilot-for-woocommerce' ) }
				value={ values.brand_voice }
				onChange={ set( 'brand_voice' ) }
				help={ __(
					'Optional. E.g. "We are a family-run outdoor gear shop; avoid hype, mention durability."',
					'draftpilot-for-woocommerce'
				) }
				rows={ 4 }
				__nextHasNoMarginBottom
			/>

			<TextControl
				label={ __( 'Output language', 'draftpilot-for-woocommerce' ) }
				value={ values.language }
				onChange={ set( 'language' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>

			<Button
				variant="primary"
				isBusy={ saving }
				disabled={ saving }
				onClick={ save }
			>
				{ __( 'Save settings', 'draftpilot-for-woocommerce' ) }
			</Button>
		</div>
	);
};

export default SettingsTab;
