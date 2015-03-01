<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

require_once dirname( __FILE__ ) . '/includes/class-network-summary.php';

global $wpdb;
$site_list = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id ASC" );

foreach ( $site_list as $site_id ) {
	delete_blog_option( $site_id, Network_Summary::site_option );
}
delete_site_option( Network_Summary::network_option );
delete_site_option( Network_Summary::version_option );

$table = $wpdb->base_prefix . 'site_categories';
$wpdb->query( "DROP TABLE IF EXISTS $table" );
$table = $wpdb->base_prefix . 'site_categories_relationships';
$wpdb->query( "DROP TABLE IF EXISTS $table" );