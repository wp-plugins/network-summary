<?php

/**
 * Class to render the custom feed builder page. Rewrite mechanisms heavily based on work by Tom J Nowell (www.tomjn.com).
 *
 * @License: GPL 2+
 */
class Custom_Feed_Builder_Page {
	private $url;
	private $pagename;
	private $template;

	public function __construct( $url, $pagename, $template ) {
		$this->url      = $url;
		$this->pagename = $pagename;
		$this->template = $template;

		add_filter( 'generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'page_query_vars' ) );
		add_action( 'template_include', array( $this, 'redirect' ) );
	}

	public function generate_rewrite_rules( $wp_rewrite ) {
		$wp_rewrite->rules = array( $this->url => 'index.php?custom_page=' . $this->pagename ) + $wp_rewrite->rules;
	}

	public function page_query_vars( $qvars ) {
		$qvars[] = 'custom_page';

		return $qvars;
	}

	public function redirect( $template ) {
		global $wp_query;
		if ( isset( $wp_query->query_vars['custom_page'] ) ) {
			$page = $wp_query->query_vars['custom_page'];
			if ( $page == $this->pagename ) {
				return $this->template;
			}
		}

		return $template;
	}

}