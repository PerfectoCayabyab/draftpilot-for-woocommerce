<?php
/**
 * Thin client for the Google Gemini generateContent API.
 *
 * @package Copyquill
 */

defined( 'ABSPATH' ) || exit;

/**
 * Calls Gemini with a JSON response schema and returns decoded JSON.
 */
class Copyquill_Gemini_Client {

	const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

	/**
	 * Generate structured JSON from a prompt.
	 *
	 * @param string $prompt          User prompt.
	 * @param array  $response_schema JSON Schema the model must follow.
	 * @return array|WP_Error Decoded JSON on success.
	 */
	public static function generate_json( $prompt, $response_schema ) {
		$api_key = (string) Copyquill_Settings::get( 'api_key' );
		if ( '' === $api_key ) {
			return new WP_Error(
				'copyquill_no_key',
				__( 'No Gemini API key configured. Add one under Copyquill → Settings.', 'copyquill-for-woocommerce' )
			);
		}

		$model = (string) Copyquill_Settings::get( 'model' );
		$url   = sprintf( self::ENDPOINT, rawurlencode( $model ) );

		$body = array(
			'contents'         => array(
				array(
					'role'  => 'user',
					'parts' => array( array( 'text' => $prompt ) ),
				),
			),
			'generationConfig' => array(
				'responseMimeType' => 'application/json',
				'responseSchema'   => $response_schema,
				'temperature'      => 0.7,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $api_key,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error.', 'copyquill-for-woocommerce' );
			return new WP_Error(
				'copyquill_api_error',
				/* translators: 1: HTTP status code, 2: API error message. */
				sprintf( __( 'Gemini API error (HTTP %1$d): %2$s', 'copyquill-for-woocommerce' ), $code, $message )
			);
		}

		$text = isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ? $data['candidates'][0]['content']['parts'][0]['text'] : '';
		$json = json_decode( $text, true );

		if ( ! is_array( $json ) ) {
			return new WP_Error( 'copyquill_bad_json', __( 'The model returned malformed JSON. Please try again.', 'copyquill-for-woocommerce' ) );
		}

		return $json;
	}
}
