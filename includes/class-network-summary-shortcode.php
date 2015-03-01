<?php

abstract class Network_Summary_Shortcode {
	protected $args;
	protected $args_hash;
	protected $cached;
	protected $output;
	protected $errors;
	protected $plugin;
	protected $caching;

	public function __construct( Network_Summary $network_summary, $atts, $caching = true ) {
		$this->plugin = $network_summary;

		$this->parse_and_validate_args( $atts );

		$this->caching   = $caching;
		$this->args_hash = $this->get_hash();
		if ( $caching AND $cache = get_transient( $this->get_transient_handle() . $this->args_hash ) ) {
			$this->cached = true;
			$this->output = $cache;
		} else {
			$this->cached = false;
		}
	}

	protected function parse_and_validate_args( $atts ) {
		$this->parse_args( $atts );
		$this->validate_args();
	}

	protected abstract function parse_args( $atts );

	protected abstract function validate_args();

	protected function get_hash() {
		return md5( serialize( $this->args ) );
	}

	protected abstract function get_transient_handle();

	public function output() {
		$this->enqueue_styles();
		if ( empty( $this->errors ) ) {
			if ( ! $this->cached ) {
				$this->output = $this->generate_output();
			}
		} else {
			$this->output = '<ul class="error">';
			foreach ( $this->errors as $error ) {
				$this->output .= '<li>' . $error . '</li>';
			}
			$this->output .= '</ul>';
		}
		if ( $this->caching ) {
			set_transient( 'netview_overview_' . $this->args_hash, $this->output, 7200 );
		}

		return $this->output;
	}

	protected function enqueue_styles() {
		wp_enqueue_style( 'network_summary' );
	}

	protected abstract function generate_output();

	protected function extract_boolVal( $var ) {
		switch ( $var ) {
			case $var === true:
			case $var == 1:
			case strtolower( $var ) == 'true':
			case strtolower( $var ) == 'on':
			case strtolower( $var ) == 'yes':
			case strtolower( $var ) == 'y':
				$out = true;
				break;
			default:
				$out = false;
		}

		return $out;
	}

	protected function get_recent_posts( $number_of_posts, $post_types, $dateFormat ) {
		$result = '<ul class="site-recent-post">';

		$recent_posts = array();
		foreach($post_types as $post_type) {
			$recent_posts = array_merge($recent_posts, wp_get_recent_posts( array(
				'numberposts' => $number_of_posts,
				'post_type' => $post_type,
				'post_status' => 'publish' ) )
			);
		}

		foreach ( $recent_posts as $post ) {
			$result .= '<li><a href="' . get_permalink( $post["ID"] ) . '" title="Read ' . $post["post_title"] . '.">'
			           . $post["post_title"] . '</a><span class="netview-date">'
			           . date_i18n( $dateFormat, strtotime( $post["post_date"] ) )
			           . '</span></li>';
		}
		$result .= '</ul>';

		return $result;
	}

	protected function sort_sites_by_name( $site_a, $site_b ) {
		return strcmp( get_blog_option( $site_b, 'blogname' ), get_blog_option( $site_a, 'blogname' ) );
	}
}