<?php
/**
 * Remove all CopyPilot data when the plugin is deleted.
 *
 * @package CopyPilot
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}copypilot_drafts" );

delete_option( 'copypilot_settings' );
delete_option( 'copypilot_db_version' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( '_copypilot_seo_title', '_copypilot_seo_description' )" );
