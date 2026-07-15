<?php
/**
 * Remove all DraftPilot data when the plugin is deleted.
 *
 * @package DraftPilot
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}draftpilot_drafts" );

delete_option( 'draftpilot_settings' );
delete_option( 'draftpilot_db_version' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( '_draftpilot_seo_title', '_draftpilot_seo_description' )" );
