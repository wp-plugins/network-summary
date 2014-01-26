<?php

require_once plugin_dir_path( __FILE__ ) . 'class-site-description-field-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'class-network-summary-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'class-network-overview-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'class-network-single-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'class-network-all-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'class-site-category-repository.php';

class Network_Summary
{
	private $admin;
	private $site_categories;

	/**
	 * Construct the plugin object and registers actions and shortcodes.
	 */
	public function __construct() {
		define('NETWORK_SUMMARY_DIR', plugin_dir_path( __DIR__ ));
		define('NETWORK_SUMMARY_URL', plugin_dir_url( __DIR__ ));
		define('NETWORK_SUMMARY_OPTIONS', 'network_summary');
		define('NETWORK_SUMMARY_VERSION', '2.0');

		add_action( 'admin_init', array($this, 'maybe_update') );

		add_shortcode( 'netview', array('Network_Overview_Shortcode', 'render') );
		add_shortcode( 'netview-single', array('Network_Single_Shortcode', 'render') );
		add_shortcode( 'netview-all', array('Network_All_Shortcode', 'render') );

		$this->site_categories = new Site_Category_Repository();
		$this->admin = new Network_Summary_Admin($this, $this->site_categories);

		add_action( 'wpmu_new_blog', array($this, 'add_new_site') );

		add_action( 'widgets_init', array($this, 'register_widgets') );

		add_action( 'init', array($this, 'init') );
	}

	/**
	 * Performs the required setup on activation. Setting default values for the settings.
	 */
	public function activate() {
		foreach ( $this->get_sites() as $site_id ) {
			add_blog_option( $site_id, NETWORK_SUMMARY_OPTIONS, array(
					'share_site' => '0',
					'site_description' => '')
			);
		}
		add_site_option( NETWORK_SUMMARY_OPTIONS, array('deciding_role' => 'network_admin') );
		add_site_option( 'network_summary_version', NETWORK_SUMMARY_VERSION );

		$this->site_categories->create_table();
	}

	/**
	 * Tear down on deactivation. Deletes all the settings.
	 */
	public function deactivate() {
		foreach ( $this->get_sites() as $site_id ) {
			delete_blog_option( $site_id, NETWORK_SUMMARY_OPTIONS );
		}
		delete_site_option( NETWORK_SUMMARY_OPTIONS );
		delete_site_option( 'network_summary_version' );
	}

	/**
	 * Checks whether the database has to get updated. If so executes the appropriate scripts.
	 */
	public function maybe_update() {
		$old_version = get_site_option( 'network_summary_version' );
		if ( $old_version === null ) {
			// Then we are 1.1.0 or earlier
			add_site_option( NETWORK_SUMMARY_OPTIONS, get_site_option( 'multisite_overview_network' ) );
			delete_site_option( 'multisite_overview_network' );
			foreach ( $this->get_sites() as $site_id ) {
				add_blog_option( $site_id, NETWORK_SUMMARY_OPTIONS, get_blog_option( $site_id,
					'multisite_overview' ) );
				delete_blog_option( $site_id, 'multisite_overview' );
			}
		}

		if ( '1.1.5' == $old_version ) {
			$this->site_categories->create_table();
		}

		if ( $old_version !== NETWORK_SUMMARY_VERSION ) {
			update_site_option( 'network_summary_version', NETWORK_SUMMARY_VERSION );
		}
	}

	/**
	 * Hook executed when a new site is created.
	 * @param $site_id int id of the new site.
	 */
	public function add_new_site( $site_id ) {
		add_blog_option( $site_id, NETWORK_SUMMARY_OPTIONS, array(
				'share_site' => '0',
				'site_description' => '')
		);
	}

	/**
	 * Hook into WP's init action hook.
	 */
	public function init() {
		add_feed( 'rss2-network', array($this, 'get_rss2_feed') );
		wp_register_style(
			'network_summary',
			NETWORK_SUMMARY_URL . 'css/network-summary.css',
			array(),
			NETWORK_SUMMARY_VERSION
		);
	}

	/**
	 * Hook for registering the widgets.
	 */
	public function register_widgets() {
		register_widget( 'Site_Description_Field_Widget' );
	}


	public function get_rss2_feed() {
		$rss_template = plugin_dir_path( __FILE__ ) . '/templates/feed-rss2-network.php';
		if ( file_exists( $rss_template ) ) {
			load_template( $rss_template );
		}
	}

	/**
	 * Returns a list of all sites as an array of blog ids. Saves it as a transient.
	 *
	 * @param int $expires Time until expiration of transient in seconds, default 7200
	 * @return array list of blog ids
	 */
	public function get_sites( $expires = 7200 ) {
		if ( false === ($site_list = get_transient( 'network_summary_site_list' )) ) {
			global $wpdb;
			$site_list = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id ASC" );
			set_site_transient( 'network_summary_site_list', $site_list, $expires );
		}
		return $site_list;
	}

	public function get_sites_per_category( $category_id ) {
		global $wpdb;
		return $wpdb->get_col(
			"SELECT blogs.blog_id
			 FROM $wpdb->blogs blogs
			 JOIN $wpdb->site_categories_relationships rel ON blogs.blog_id = rel.blog_id
			 WHERE rel.category_id = $category_id
			 ORDER BY blog_id ASC" );
	}

	public function get_shared_sites() {
		$shared_sites = array();
		$site_list = $this->get_sites();
		foreach ( $site_list as $site_id ) {
			if ( '1' === $this->get_option( 'share_site', false, $site_id ) ) {
				array_push( $shared_sites, $site_id );
			}
		}
		return $shared_sites;
	}

	public function get_rss2_url( array $sites ) {
		return get_bloginfo( 'url' ) . '?' . http_build_query( array('feed' => 'rss2-network', 'sites' => $sites) );
	}

	public function set_option( $tag, $value, $network = false, $blog_id = null ) {
		$option = $this->get_option( $tag, $network, $blog_id );
		if ( isset($option) ) {
			$option[$tag] = $value;
			if ( $network ) {
				return update_site_option( NETWORK_SUMMARY_OPTIONS, $option );
			} elseif ( isset($blog_id) ) {
				return update_blog_option( $blog_id, NETWORK_SUMMARY_OPTIONS, $option );
			} else {
				return update_option( NETWORK_SUMMARY_OPTIONS, $option );
			}
		} else {
			return false;
		}
	}

	public function get_option( $tag, $network = false, $blog_id = null ) {
		if ( $network ) {
			$option = get_site_option( NETWORK_SUMMARY_OPTIONS );
		} else if ( isset($blog_id) ) {
			$option = get_blog_option( $blog_id, NETWORK_SUMMARY_OPTIONS );
		} else {
			$option = get_option( NETWORK_SUMMARY_OPTIONS );
		}
		if ( isset($tag) ) {
			if ( isset($option[$tag]) ) {
				return $option[$tag];
			} else {
				return null;
			}
		} else {
			return $option;
		}
	}

	public function get_all_categories() {
		return $this->site_categories->get_all();
	}

	public function get_site_categories( $id ) {
		return $this->site_categories->get_by_site( $id );
	}

	public function set_site_category( $site, $category ) {
		$this->site_categories->set_site_category( $site, $category );
	}

	public function remove_site_category( $site, $category ) {
		$this->site_categories->remove_site_category( $site, $category );
	}
}