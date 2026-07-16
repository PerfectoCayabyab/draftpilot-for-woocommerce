<?php
/**
 * Remove all Copyquill data when the plugin is deleted.
 *
 * @package Copyquill
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}copyquill_drafts" );

delete_option( 'copyquill_settings' );
delete_option( 'copyquill_db_version' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( '_copyquill_seo_title', '_copyquill_seo_description' )" );
