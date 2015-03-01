<?php

require_once dirname( __FILE__ ) . '/class-network-summary-shortcode.php';

class Network_Single_Shortcode extends Network_Summary_Shortcode {
	/**
	 * Displays one specific site in a featured way with an image.
	 *
	 * @param $atts array with a required id of the site to display and a url to a image for the site. Also accepts a
	 * numposts parameter for the number of recent posts displayed. Can be 0 and defaults to 2.
	 *
	 * @return string html content for single view.
	 */
	public static function render( $atts ) {
		global $network_summary;
		$code = new Network_Single_Shortcode( $network_summary, $atts, false );

		return $code->output();
	}

	protected function get_transient_handle() {
		return 'netview_single_';
	}

	protected function generate_output() {
		$dateFormat = get_option( 'date_format' );

		switch_to_blog( $this->args['id'] );

		$result = sprintf( '<div class="netview-single"><h2 class="site-title"><a href="%s">%s</a></h2><div class="site-description">',
			site_url(), get_bloginfo()
		);

		if ( ! empty( $this->args['img'] ) ) {
			$result .= sprintf( '<a href="%s"><img class="featured-img" src="%s"></a>', site_url(), $this->args['img'] );
		}
		$result .= wpautop( do_shortcode( site_description() ) );
		if ( $this->args['numposts'] > 0 ) {
			$result .= '<h4>Recent Posts</h4>';
			// TODO integrate post type parameter here as well
			$result .= $this->get_recent_posts( $this->args['numposts'], array('post'), $dateFormat );
		}
		$result .= '</div></div>';

		restore_current_blog();

		return $result;
	}

	protected function parse_args( $atts ) {
		$this->args = shortcode_atts( array(
			'id'       => 'error',
			'img'      => '',
			'numposts' => 2
		), $atts, 'netview-single' );
	}

	protected function validate_args() {
		$this->errors = array();
		if ( ! is_numeric( $this->args['id'] ) ) {
			array_push( $this->errors, 'No valid parameter for <code>id</code>.' );
		} elseif ( ! in_array( $this->args['id'], $this->plugin->get_shared_sites() ) ) {
			array_push( $this->errors, 'Blog does not exist or sharing has been disabled.' );
		}
		$this->args['img'] = sanitize_text_field( $this->args['img'] );
		if ( ! is_numeric( $this->args['numposts'] ) ) {
			array_push( $this->errors, 'Illegal parameter <code>numposts</code> (must be integer value greater or equal than 0).' );
		}
	}
}