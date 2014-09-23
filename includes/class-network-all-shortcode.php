<?php

require_once dirname( __FILE__ ) . '/class-network-summary-shortcode.php';

class Network_All_Shortcode extends Network_Summary_Shortcode {

	/**
	 * Displays a index of all available sites in an alphabetic order.
	 *
	 * @param $atts array with a title attribute displayed before the list.
	 *
	 * @return string html content
	 */
	public static function render( $atts ) {
		global $network_summary;
		$code = new Network_All_Shortcode( $network_summary, $atts, false );

		return $code->output();
	}

	protected function get_transient_handle() {
		return 'netview_all_';
	}

	protected function generate_output() {
		$title = sprintf( '<div class="netview-all"><h2>%s</h2>', $this->args['title'] );

		$sites = $this->plugin->get_shared_sites();

		if ( empty( $sites ) ) {
			return '<p><b>No sites to display.</b></p>';
		}

		usort( $sites, array( $this, 'sort_sites_by_name' ) );

		$result = '<ul>';
		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );
			$result .= sprintf( '<li><a href="%s">%s</a></li>', site_url(), get_bloginfo() );
			restore_current_blog();
		}
		$result .= '</ul></div>';

		return $title . $result;
	}

	protected function parse_args( $atts ) {
		$this->args = shortcode_atts( array(
			'title' => "All Sites"
		), $atts, 'netview-all' );
	}

	protected function validate_args() {
		$this->args['title'] = sanitize_text_field( $this->args['title'] );
	}
}