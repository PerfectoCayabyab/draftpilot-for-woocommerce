<?php
/**
 * Builds prompts from product data and turns Gemini output into drafts.
 *
 * @package CopyPilot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Copy generation for a single product.
 */
class CopyPilot_Generator {

	/**
	 * Generate the requested fields for a product and store them as pending drafts.
	 *
	 * @param int      $product_id Product ID.
	 * @param string[] $fields     Subset of CopyPilot_Drafts::fields() keys.
	 * @param string   $tone       Tone preset key.
	 * @return array|WP_Error Array of created draft rows.
	 */
	public static function generate( $product_id, $fields, $tone ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'copypilot_no_product', __( 'Product not found.', 'copypilot-for-woocommerce' ) );
		}

		$valid_fields = array_keys( CopyPilot_Drafts::fields() );
		$fields       = array_values( array_intersect( $fields, $valid_fields ) );
		if ( empty( $fields ) ) {
			return new WP_Error( 'copypilot_no_fields', __( 'No valid fields requested.', 'copypilot-for-woocommerce' ) );
		}

		$schema = self::response_schema( $fields );
		$prompt = self::build_prompt( $product, $fields, $tone );

		$json = CopyPilot_Gemini_Client::generate_json( $prompt, $schema );
		if ( is_wp_error( $json ) ) {
			return $json;
		}

		$drafts = array();
		foreach ( $fields as $field ) {
			if ( empty( $json[ $field ] ) || ! is_string( $json[ $field ] ) ) {
				continue;
			}

			$proposed = 'long_description' === $field || 'short_description' === $field
				? wp_kses_post( $json[ $field ] )
				: sanitize_text_field( $json[ $field ] );

			$draft_id = CopyPilot_Drafts::create(
				$product_id,
				$field,
				CopyPilot_Drafts::current_value( $product, $field ),
				$proposed,
				$tone
			);
			$drafts[] = CopyPilot_Drafts::get( $draft_id );
		}

		if ( empty( $drafts ) ) {
			return new WP_Error( 'copypilot_empty', __( 'The model returned no usable copy. Please try again.', 'copypilot-for-woocommerce' ) );
		}

		return $drafts;
	}

	/**
	 * JSON schema Gemini must return, restricted to the requested fields.
	 *
	 * @param string[] $fields Field keys.
	 * @return array
	 */
	private static function response_schema( $fields ) {
		$properties = array(
			'long_description'  => array(
				'type'        => 'string',
				'description' => 'Full product description, 2-4 short paragraphs of HTML using only <p>, <ul>, <li>, <strong> tags. 120-220 words.',
			),
			'short_description' => array(
				'type'        => 'string',
				'description' => 'One punchy plain-text paragraph, 25-45 words, no HTML.',
			),
			'seo_title'         => array(
				'type'        => 'string',
				'description' => 'SEO page title, max 60 characters, plain text.',
			),
			'meta_description'  => array(
				'type'        => 'string',
				'description' => 'SEO meta description, 120-155 characters, plain text, ends with a subtle call to action.',
			),
		);

		return array(
			'type'       => 'object',
			'properties' => array_intersect_key( $properties, array_flip( $fields ) ),
			'required'   => $fields,
		);
	}

	/**
	 * Compose the prompt from live product data + settings.
	 *
	 * @param WC_Product $product Product.
	 * @param string[]   $fields  Field keys.
	 * @param string     $tone    Tone preset key.
	 * @return string
	 */
	private static function build_prompt( $product, $fields, $tone ) {
		$tones     = CopyPilot_Settings::tones();
		$tone_name = isset( $tones[ $tone ] ) ? $tones[ $tone ] : 'Professional';

		$categories = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ', ' ) );
		$tags       = wp_strip_all_tags( wc_get_product_tag_list( $product->get_id(), ', ' ) );

		$attributes = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( $attribute instanceof WC_Product_Attribute ) {
				$name         = wc_attribute_label( $attribute->get_name() );
				$values       = $attribute->is_taxonomy()
					? wp_list_pluck( $attribute->get_terms() ? $attribute->get_terms() : array(), 'name' )
					: $attribute->get_options();
				$attributes[] = $name . ': ' . implode( ', ', $values );
			}
		}

		$facts = array(
			'Product name: ' . $product->get_name(),
			'Price: ' . html_entity_decode( wp_strip_all_tags( wc_price( (float) $product->get_price() ) ), ENT_QUOTES, 'UTF-8' ),
		);
		if ( $categories ) {
			$facts[] = 'Categories: ' . $categories;
		}
		if ( $tags ) {
			$facts[] = 'Tags: ' . $tags;
		}
		if ( ! empty( $attributes ) ) {
			$facts[] = 'Attributes: ' . implode( ' | ', $attributes );
		}
		if ( $product->get_sku() ) {
			$facts[] = 'SKU: ' . $product->get_sku();
		}

		$existing = $product->get_description() ? $product->get_description() : $product->get_short_description();
		if ( $existing ) {
			$facts[] = 'Existing copy (for factual reference only, do not copy phrasing): ' . wp_strip_all_tags( $existing );
		}

		$brand_voice = (string) CopyPilot_Settings::get( 'brand_voice' );
		$language    = (string) CopyPilot_Settings::get( 'language' );

		$field_labels = array_intersect_key( CopyPilot_Drafts::fields(), array_flip( $fields ) );

		$prompt  = "You are an expert e-commerce copywriter. Write product copy for the following WooCommerce product.\n\n";
		$prompt .= implode( "\n", $facts ) . "\n\n";
		$prompt .= 'Tone: ' . $tone_name . ".\n";
		$prompt .= 'Language: ' . $language . ".\n";
		if ( $brand_voice ) {
			$prompt .= 'Brand voice notes: ' . $brand_voice . "\n";
		}
		$prompt .= "\nWrite the following fields: " . implode( ', ', array_values( $field_labels ) ) . ".\n";
		$prompt .= 'Ground every claim in the facts above — never invent specifications, materials, or guarantees that are not listed. Return JSON matching the schema.';

		return $prompt;
	}
}
