<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

global $wpdb;
$site_list = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id ASC" );

foreach ( $site_list as $site_id ) {
    delete_blog_option( $site_id, 'network_summary' );
}
delete_site_option( 'network_summary' );
delete_site_option( 'network_summary_version' );

$table = $wpdb->base_prefix . 'site_categories';
$wpdb->query("DROP TABLE IF EXISTS $table");
$table = $wpdb->base_prefix . 'site_categories_relationships';
$wpdb->query("DROP TABLE IF EXISTS $table");