<?php

require_once dirname( __FILE__ ) . '/class-network-summary-shortcode.php';

class Network_Overview_Shortcode extends Network_Summary_Shortcode {
	private $sites;

	public function __construct( Network_Summary $network_summary, $atts, $cache = true ) {
		parent::__construct( $network_summary, $atts, $cache );
		$this->sort_sites();
	}

	private function sort_sites() {
		$sorting = $this->args['sort'];
		if ( 'abc' == $sorting ) {
			@usort( $this->sites, array( $this, 'sort_sites_by_name' ) );
		} else if ( 'posts' == $sorting ) {
			@usort( $this->sites, array( $this, 'sort_sites_by_recent_post' ) );
		}
	}

	/**
	 * Displays a overview of all sites in a two column display in alphabetic order.
	 *
	 * @param $atts array with a include list and a exclude list. If include is empty, all sites are included,
	 * except the ones in the exclude list. This is always overridden by the sharing setting of the individual blog.
	 * Also accepts a numposts parameter for the number of recent posts displayed. Can be 0 and defaults to 2. The sort
	 * parameter can be either 'abc' or 'posts'. 'abc' is the default value and sorts the posts alphabetically. 'posts'
	 * sorts the sites so that the one with the most recent posts are listed first. Finally it takes a layout parameter
	 * which takes either 'grid' (default) or 'table' and displays the sites accordingly.
	 *
	 * @return string the html content for the overview view.
	 */
	public static function render( $atts ) {
		global $network_summary;
		$code = new Network_Overview_Shortcode( $network_summary, $atts, false );

		return $code->output();
	}

	protected function get_transient_handle() {
		return 'netview_overview_';
	}

	protected function parse_args( $atts ) {
		$args = shortcode_atts( array(
			'category'  => array(),
			'include'   => array(),
			'exclude'   => array(),
			'numposts'  => 2,
			'sort'      => 'abc',
			'layout'    => 'table',
			'images'    => 'true',
			'rss'       => 'true',
			'minposts'  => 0,
			'post_types' => array()
		), $atts, 'netview' );

		if ( !is_array( $args['category'] ) ) {
			$args['category'] = explode( ',', $args['category'] );
		}
		if ( !is_array( $args['include'] ) ) {
			$args['include'] = explode( ',', $args['include'] );
		}
		if ( !is_array( $args['exclude'] ) ) {
			$args['exclude'] = explode( ',', $args['exclude'] );
		}
		if ( !is_array( $args['post_types'] ) ) {
			$args['post_types'] = explode( ',', $args['post_types'] );
		}
		array_push( $args['post_types'], 'post' );

		$this->args = $args;
	}

	protected function validate_args() {
		$this->errors = array();

		foreach ( $this->args['category'] as $id ) {
			if ( !is_numeric( $id ) ) {
				array_push(
					$errors,
					'Invalid parameter for <code>include</code>. Must be comma-separated list of integers.'
				);
			}
		}

		foreach ( $this->args['include'] as $id ) {
			if ( !is_numeric( $id ) ) {
				array_push(
					$errors,
					'Invalid parameter for <code>include</code>. Must be comma-separated list of integers.'
				);
			}
		}

		foreach ( $this->args['exclude'] as $id ) {
			if ( !is_numeric( $id ) ) {
				array_push(
					$this->errors,
					'Invalid parameter for <code>exclude</code>. Must be comma-separated list of integers.'
				);
			}
		}

		if ( !is_numeric( $this->args['numposts'] ) || $this->args['numposts'] < 0 ) {
			array_push(
				$this->errors,
				'Illegal parameter <code>numposts</code> (must be integer value greater or equal than 0).'
			);
		}

		if ( !in_array( $this->args['sort'] = strtolower( $this->args['sort'] ), array( 'abc', 'posts' ) ) ) {
			array_push(
				$this->errors,
				'Illegal parameter <code>sort</code> (must be <code>abc</code> or <code>posts</code>).'
			);
		}

		if ( !in_array( $this->args['layout'] = strtolower( $this->args['layout'] ), array( 'grid', 'table' ) ) ) {
			array_push(
				$this->errors,
				'Illegal parameter <code>layout</code> (must be <code>grid</code> or <code>table</code>).'
			);
		}

		$this->args['images'] = $this->extract_boolVal( $this->args['images'] );
		$this->args['rss'] = $this->extract_boolVal( $this->args['rss'] );

		if ( !is_numeric( $this->args['minposts'] ) || $this->args['minposts'] < 0 ) {
			array_push(
				$this->errors,
				'Illegal parameter <code>minposts</code> (must be integer value greater or equal than 0).'
			);
		}

		$this->sites = array();

		if ( !empty( $this->args['category'] ) ) {
			foreach ( $this->args['category'] as $cat ) {
				$this->sites = array_merge( $this->sites, $this->plugin->get_sites_per_category( $cat ) );
			}
		}

		if ( !empty( $this->args['include'] ) ) {
			$this->sites = array_merge( $this->sites, $this->args['include'] );
		}

		global $network_summary;
		if ( empty( $this->args['category'] ) && empty( $this->args['include'] ) ) {
			$this->sites = $network_summary->get_shared_sites( $this->args['minposts'] );
		} else {
			$this->sites = array_intersect( $this->sites, $network_summary->get_shared_sites( $this->args['minposts'] ) );
		}

		$this->sites = array_diff( $this->sites, $this->args['exclude'] );
	}

	protected function generate_output() {
		if ( empty( $this->sites ) ) {
			return '<p><b>No sites to display.</b></p>';
		}

		$result = '<div class="netview">';

		extract( $this->args );
		if ( isset( $layout ) && isset( $numposts ) && isset( $images ) && isset( $rss ) ) {

			if ( $rss ) {
				$result .= '<div><a class="network-feed" href="' . $this->get_rss2_url() . '">RSS Feed</a></div>';
			}
			$i = 0;
			if ( 'table' == $layout ) {
				$result .= '<table class="netview-site"><tbody>';
			}
			foreach ( $this->sites as $site_id ) {
				$date_format = get_option( 'date_format' );
				switch_to_blog( $site_id );
				$name = '<h2 class="site-title"><a href="' . site_url() . '">' . get_bloginfo() . '</a></h2>';
				$description = wpautop( do_shortcode( site_description() ) );
				if ( $images && get_header_image() ) {
					$picture = '<a href="' . site_url() . '"><img src="' . get_header_image() . '"></a>';
				} else {
					$picture = '';
				}
				if ( $numposts > 0 ) {
					$recent_posts = $this->get_recent_posts( $this->args['numposts'], $this->args['post_types'], $date_format );
				} else {
					$recent_posts = '';
				}

				if ( 'grid' == $layout ) {
					$result .= '<div class="netview-site ' . ( ( $i++ % 2 == 0 ) ? 'even' : 'odd' ) . '">';
					$result .= $name;
					if ( $images && get_header_image() ) {
						$result .= '<span class="header-image">' . $picture . '</span>';
					}
					$result .= $description . $recent_posts;
					$result .= '</div>';
				} else if ( 'table' == $layout ) {
					if ( $this->args['images'] && get_header_image() ) {
						$result .= '<tr class="header-image"><td colspan="2">' . $picture . '</td>';
					}
					$result .= '<tr class="site-info"><td>';
					$result .= $name . $description;
					$result .= '</td><td>' . $recent_posts . '</td></tr>';
				}

				restore_current_blog();
			}
			if ( 'table' == $layout ) {
				$result .= '</tbody></table>';
			}


		}
		$result .= '</div>';

		return $result;
	}

	private function get_rss2_url() {
		$query = array();
		if ( !empty( $this->args['category'] ) && empty( $this->args['include'] ) && empty( $this->args['exclude'] ) ) {
			if ( count( $this->args['category'] ) == 1 ) {
				$query['category'] = $this->args['category'][0];
			} else {
				$query['category'] = $this->args['category'];
			}
		} elseif ( !empty( $this->args['include'] ) || !empty( $this->args['exclude'] ) ) {
			$query['sites'] = $this->sites;
		}
		$url = trailingslashit( get_feed_link( 'rss2-network' ) );

		return empty( $query ) ? $url : $url . '?' . http_build_query( $query );
	}

	private function sort_sites_by_recent_post( $site_a, $site_b ) {
		$post_a = $this->get_most_recent_post( $site_a );
		$post_b = $this->get_most_recent_post( $site_b );

		return strcmp( $post_b['post_date'], $post_a['post_date'] );
	}

	private function get_most_recent_post( $blog_id ) {
		$most_recent = null;
		switch_to_blog( $blog_id );

		foreach ( $this->args['post_types'] as $post_type ) {
			$recent = wp_get_recent_posts( array(
				'numberposts' => 1,
				'post_type' => $post_type,
				'post_status' => 'publish' ) );
			if ( $most_recent == null && !empty( $recent ) ) {
				$most_recent = $recent[0];
			} else if ( $most_recent != null && !empty( $recent ) ) {
				$cmp = strcmp( $recent[0]->post_date, $most_recent->post_date );
				if ( $cmp > 0 ) {
					$most_recent = $recent;
				}
			}
		}

		restore_current_blog();

		return $most_recent;
	}
}