<?php
/**
 * REST API for the Copyquill admin app.
 *
 * @package Copyquill
 */

defined( 'ABSPATH' ) || exit;

/**
 * Routes under copyquill/v1. All routes require manage_woocommerce.
 */
class Copyquill_REST_Controller {

	const NAMESPACE = 'copyquill/v1';

	/**
	 * Register all routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/products',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_products' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'   => array(
						'type'    => 'integer',
						'default' => 1,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/generate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'product_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'fields'     => array(
						'type'     => 'array',
						'required' => true,
						'items'    => array( 'type' => 'string' ),
					),
					'tone'       => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/drafts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_drafts' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'status' => array(
						'type'              => 'string',
						'default'           => 'pending',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/drafts/(?P<id>\d+)/(?P<decision>approve|reject)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'decide_draft' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Only shop managers / admins may use Copyquill.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * GET /products — paged product list with copy status.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_products( $request ) {
		$query = new WC_Product_Query(
			array(
				'status'   => array( 'publish', 'draft' ),
				'limit'    => 20,
				'page'     => max( 1, (int) $request['page'] ),
				'paginate' => true,
				's'        => (string) $request['search'],
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
		$results = $query->get_products();

		$items = array();
		foreach ( $results->products as $product ) {
			$image_id = $product->get_image_id();
			$items[]  = array(
				'id'                    => $product->get_id(),
				'name'                  => $product->get_name(),
				'sku'                   => $product->get_sku(),
				'price_html'            => html_entity_decode( wp_strip_all_tags( wc_price( (float) $product->get_price() ) ), ENT_QUOTES, 'UTF-8' ),
				'status'                => $product->get_status(),
				'image'                 => $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '',
				'has_long_description'  => '' !== trim( (string) $product->get_description() ),
				'has_short_description' => '' !== trim( (string) $product->get_short_description() ),
				'edit_link'             => get_edit_post_link( $product->get_id(), 'raw' ),
			);
		}

		return rest_ensure_response(
			array(
				'products'    => $items,
				'total'       => (int) $results->total,
				'total_pages' => (int) $results->max_num_pages,
			)
		);
	}

	/**
	 * POST /generate — generate drafts for one product.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate( $request ) {
		$tone = (string) $request['tone'];
		if ( '' === $tone ) {
			$tone = (string) Copyquill_Settings::get( 'default_tone' );
		}

		$fields = array_map( 'sanitize_key', (array) $request['fields'] );
		$drafts = Copyquill_Generator::generate( (int) $request['product_id'], $fields, $tone );

		if ( is_wp_error( $drafts ) ) {
			$drafts->add_data( array( 'status' => 400 ) );
			return $drafts;
		}

		return rest_ensure_response(
			array(
				'drafts'  => array_map( array( $this, 'format_draft' ), $drafts ),
				'pending' => Copyquill_Drafts::count_pending(),
			)
		);
	}

	/**
	 * GET /drafts — list drafts by status.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_drafts( $request ) {
		$status = (string) $request['status'];
		if ( ! in_array( $status, array( 'pending', 'approved', 'rejected', 'superseded' ), true ) ) {
			$status = 'pending';
		}

		$drafts = Copyquill_Drafts::list_by_status( $status );

		return rest_ensure_response(
			array(
				'drafts'  => array_map( array( $this, 'format_draft' ), $drafts ),
				'pending' => Copyquill_Drafts::count_pending(),
			)
		);
	}

	/**
	 * POST /drafts/{id}/approve|reject.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function decide_draft( $request ) {
		$draft = Copyquill_Drafts::get( (int) $request['id'] );
		if ( ! $draft ) {
			return new WP_Error( 'copyquill_not_found', __( 'Draft not found.', 'copyquill-for-woocommerce' ), array( 'status' => 404 ) );
		}
		if ( 'pending' !== $draft->status ) {
			return new WP_Error( 'copyquill_decided', __( 'This draft has already been decided.', 'copyquill-for-woocommerce' ), array( 'status' => 409 ) );
		}

		if ( 'approve' === $request['decision'] ) {
			$applied = Copyquill_Drafts::apply( $draft );
			if ( is_wp_error( $applied ) ) {
				$applied->add_data( array( 'status' => 400 ) );
				return $applied;
			}
			Copyquill_Drafts::set_status( (int) $draft->id, 'approved' );
		} else {
			Copyquill_Drafts::set_status( (int) $draft->id, 'rejected' );
		}

		return rest_ensure_response(
			array(
				'ok'      => true,
				'pending' => Copyquill_Drafts::count_pending(),
			)
		);
	}

	/**
	 * GET /settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		return rest_ensure_response(
			array(
				'settings' => Copyquill_Settings::for_ui(),
				'tones'    => Copyquill_Settings::tones(),
				'fields'   => Copyquill_Drafts::fields(),
			)
		);
	}

	/**
	 * POST /settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function update_settings( $request ) {
		Copyquill_Settings::update( (array) $request->get_json_params() );
		return rest_ensure_response( array( 'settings' => Copyquill_Settings::for_ui() ) );
	}

	/**
	 * Shape a draft row for the UI.
	 *
	 * @param object $draft Draft row.
	 * @return array
	 */
	private function format_draft( $draft ) {
		$product = wc_get_product( (int) $draft->product_id );
		$fields  = Copyquill_Drafts::fields();

		return array(
			'id'             => (int) $draft->id,
			'product_id'     => (int) $draft->product_id,
			'product_name'   => $product ? $product->get_name() : __( '(deleted product)', 'copyquill-for-woocommerce' ),
			'field'          => $draft->field,
			'field_label'    => isset( $fields[ $draft->field ] ) ? $fields[ $draft->field ] : $draft->field,
			'current_value'  => (string) $draft->current_value,
			'proposed_value' => (string) $draft->proposed_value,
			'tone'           => $draft->tone,
			'status'         => $draft->status,
			'created_at'     => $draft->created_at,
		);
	}
}
