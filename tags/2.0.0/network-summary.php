<?php
/*
Plugin Name: Network Summary
Plugin URI: http://www.aleaiactaest.ch/network-summary
Description: Allows the display of content from other sites within the same network.
Version: 2.0.0
Network: True
Author: Joel Krebs
Author URI: http://www.aleaiactaest.ch
License: GPL2
*/

include_once dirname( __FILE__ ) . '/includes/class-network-summary.php';

if ( class_exists( 'Network_Summary' ) ) {
	$network_summary = new Network_Summary();

	register_activation_hook( __FILE__, array($network_summary, 'activate') );
	register_deactivation_hook( __FILE__, array($network_summary, 'deactivate') );

	function get_site_categories( $id = null ) {
		if ( ! isset($id) ) {
			$id = get_current_blog_id();
		}
		global $network_summary;
		return $network_summary->get_site_categories( $id );
	}

	function set_site_category( $id, $category ) {
		global $network_summary;
		$network_summary->set_site_category( $id, $category );
	}

	function remove_site_category( $id, $category ) {
		global $network_summary;
		$network_summary->remove_site_category( $id, $category );
	}
}