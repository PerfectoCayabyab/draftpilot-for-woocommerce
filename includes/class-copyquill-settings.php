<?php
/**
 * Plugin settings stored in a single option.
 *
 * @package Copyquill
 */

defined( 'ABSPATH' ) || exit;

/**
 * Typed access to the copyquill_settings option.
 */
class Copyquill_Settings {

	const OPTION = 'copyquill_settings';

	/**
	 * Tone presets offered in the UI.
	 *
	 * @return array<string,string>
	 */
	public static function tones() {
		return array(
			'professional' => __( 'Professional', 'copyquill-for-woocommerce' ),
			'friendly'     => __( 'Friendly & warm', 'copyquill-for-woocommerce' ),
			'premium'      => __( 'Premium & luxurious', 'copyquill-for-woocommerce' ),
			'playful'      => __( 'Playful', 'copyquill-for-woocommerce' ),
			'minimal'      => __( 'Minimal & direct', 'copyquill-for-woocommerce' ),
		);
	}

	/**
	 * Defaults merged under saved values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'api_key'        => '',
			'model'          => 'gemini-3.5-flash',
			'default_tone'   => 'professional',
			'brand_voice'    => '',
			'language'       => 'English',
		);
	}

	/**
	 * All settings.
	 *
	 * @return array
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Sanitize + persist a partial update.
	 *
	 * @param array $input Raw input.
	 * @return array Saved settings.
	 */
	public static function update( $input ) {
		$current = self::all();

		if ( array_key_exists( 'api_key', $input ) ) {
			$key = sanitize_text_field( (string) $input['api_key'] );
			// The UI sends a masked placeholder when the key is unchanged.
			if ( '' === $key || false === strpos( $key, '•' ) ) {
				$current['api_key'] = $key;
			}
		}
		if ( array_key_exists( 'model', $input ) ) {
			$current['model'] = sanitize_text_field( (string) $input['model'] );
		}
		if ( array_key_exists( 'default_tone', $input ) ) {
			$tone                    = sanitize_key( (string) $input['default_tone'] );
			$current['default_tone'] = array_key_exists( $tone, self::tones() ) ? $tone : 'professional';
		}
		if ( array_key_exists( 'brand_voice', $input ) ) {
			$current['brand_voice'] = sanitize_textarea_field( (string) $input['brand_voice'] );
		}
		if ( array_key_exists( 'language', $input ) ) {
			$current['language'] = sanitize_text_field( (string) $input['language'] );
		}

		update_option( self::OPTION, $current, false );
		return $current;
	}

	/**
	 * Settings safe to expose to the admin UI (API key masked).
	 *
	 * @return array
	 */
	public static function for_ui() {
		$all = self::all();
		$key = (string) $all['api_key'];
		if ( strlen( $key ) > 4 ) {
			$all['api_key'] = str_repeat( '•', 12 ) . substr( $key, -4 );
		}
		$all['has_api_key'] = '' !== $key;
		return $all;
	}
}
