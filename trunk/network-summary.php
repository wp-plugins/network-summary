<?php
/*
Plugin Name: Network Summary
Plugin URI: http://www.aleaiactaest.ch/network-summary
Description: Allows the display of content from other sites within the same network.
Version: 1.1.2
Network: True
Author: Joel Krebs
Author URI: http://www.aleaiactaest.ch
License: GPL2
*/

include_once dirname( __FILE__ ) . '/class-network-summary.php';
include_once dirname( __FILE__ ) . '/class-site-description-field-widget.php';

if ( class_exists( 'Network_Summary' ) ) {
	register_activation_hook( __FILE__, array('Network_Summary', 'activate') );
	register_deactivation_hook( __FILE__, array('Network_Summary', 'deactivate') );

	$network_overview = new Network_Summary();
}