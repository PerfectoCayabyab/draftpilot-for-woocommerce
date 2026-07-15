<?php
/**
 * Draft storage: a custom table holding AI-generated copy awaiting review.
 *
 * @package DraftPilot
 */

defined( 'ABSPATH' ) || exit;

/**
 * CRUD for the draftpilot_drafts table.
 */
class DraftPilot_Drafts {

	const DB_VERSION        = '1.0';
	const DB_VERSION_OPTION = 'draftpilot_db_version';

	/**
	 * Fields DraftPilot can generate, mapped to human labels.
	 *
	 * @return array<string,string>
	 */
	public static function fields() {
		return array(
			'long_description'  => __( 'Description', 'draftpilot-for-woocommerce' ),
			'short_description' => __( 'Short description', 'draftpilot-for-woocommerce' ),
			'seo_title'         => __( 'SEO title', 'draftpilot-for-woocommerce' ),
			'meta_description'  => __( 'Meta description', 'draftpilot-for-woocommerce' ),
		);
	}

	/**
	 * Table name with prefix.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'draftpilot_drafts';
	}

	/**
	 * Create the drafts table on activation.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				product_id BIGINT UNSIGNED NOT NULL,
				field VARCHAR(32) NOT NULL,
				current_value LONGTEXT NULL,
				proposed_value LONGTEXT NOT NULL,
				tone VARCHAR(40) NOT NULL DEFAULT '',
				status VARCHAR(16) NOT NULL DEFAULT 'pending',
				created_at DATETIME NOT NULL,
				decided_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY product_id (product_id),
				KEY status (status)
			) {$charset_collate};"
		);

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Re-run install when the schema version changes.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Insert a pending draft, superseding any older pending draft for the same product+field.
	 *
	 * @param int    $product_id     Product ID.
	 * @param string $field          One of self::fields() keys.
	 * @param string $current_value  Value on the product right now.
	 * @param string $proposed_value AI-proposed value.
	 * @param string $tone           Tone preset used.
	 * @return int Draft ID.
	 */
	public static function create( $product_id, $field, $current_value, $proposed_value, $tone ) {
		global $wpdb;

		// Only one pending draft per product+field: mark older ones superseded.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'status'     => 'superseded',
				'decided_at' => current_time( 'mysql', true ),
			),
			array(
				'product_id' => $product_id,
				'field'      => $field,
				'status'     => 'pending',
			)
		);

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'product_id'     => $product_id,
				'field'          => $field,
				'current_value'  => $current_value,
				'proposed_value' => $proposed_value,
				'tone'           => $tone,
				'status'         => 'pending',
				'created_at'     => current_time( 'mysql', true ),
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Fetch one draft as an object.
	 *
	 * @param int $id Draft ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	/**
	 * List drafts by status, newest first.
	 *
	 * @param string $status Draft status.
	 * @param int    $limit  Max rows.
	 * @return array<object>
	 */
	public static function list_by_status( $status, $limit = 100 ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d", $status, $limit ) );
	}

	/**
	 * Count pending drafts.
	 *
	 * @return int
	 */
	public static function count_pending() {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
	}

	/**
	 * Mark a draft approved/rejected.
	 *
	 * @param int    $id     Draft ID.
	 * @param string $status New status.
	 */
	public static function set_status( $id, $status ) {
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'status'     => $status,
				'decided_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	/**
	 * Apply an approved draft to its product.
	 *
	 * @param object $draft Draft row.
	 * @return true|WP_Error
	 */
	public static function apply( $draft ) {
		$product = wc_get_product( (int) $draft->product_id );
		if ( ! $product ) {
			return new WP_Error( 'draftpilot_no_product', __( 'Product no longer exists.', 'draftpilot-for-woocommerce' ) );
		}

		$value = $draft->proposed_value;

		switch ( $draft->field ) {
			case 'long_description':
				$product->set_description( wp_kses_post( $value ) );
				$product->save();
				break;

			case 'short_description':
				$product->set_short_description( wp_kses_post( $value ) );
				$product->save();
				break;

			case 'seo_title':
				self::save_seo_meta( (int) $draft->product_id, 'title', sanitize_text_field( $value ) );
				break;

			case 'meta_description':
				self::save_seo_meta( (int) $draft->product_id, 'description', sanitize_text_field( $value ) );
				break;

			default:
				return new WP_Error( 'draftpilot_bad_field', __( 'Unknown field.', 'draftpilot-for-woocommerce' ) );
		}

		return true;
	}

	/**
	 * Write SEO meta to whichever SEO plugin is active (Yoast, Rank Math), plus our own key.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $kind       'title' or 'description'.
	 * @param string $value      Sanitized value.
	 */
	private static function save_seo_meta( $product_id, $kind, $value ) {
		update_post_meta( $product_id, '_draftpilot_seo_' . $kind, $value );

		if ( defined( 'WPSEO_VERSION' ) ) {
			$yoast_key = 'title' === $kind ? '_yoast_wpseo_title' : '_yoast_wpseo_metadesc';
			update_post_meta( $product_id, $yoast_key, $value );
		}

		if ( class_exists( 'RankMath' ) ) {
			$rm_key = 'title' === $kind ? 'rank_math_title' : 'rank_math_description';
			update_post_meta( $product_id, $rm_key, $value );
		}
	}

	/**
	 * Current live value of a field on a product (what a draft would replace).
	 *
	 * @param WC_Product $product Product.
	 * @param string     $field   Field key.
	 * @return string
	 */
	public static function current_value( $product, $field ) {
		switch ( $field ) {
			case 'long_description':
				return (string) $product->get_description();
			case 'short_description':
				return (string) $product->get_short_description();
			case 'seo_title':
				return (string) get_post_meta( $product->get_id(), '_draftpilot_seo_title', true );
			case 'meta_description':
				return (string) get_post_meta( $product->get_id(), '_draftpilot_seo_description', true );
		}
		return '';
	}
}
