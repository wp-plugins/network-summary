<?php

require_once plugin_dir_path( __FILE__ ) . 'class-site-description-field-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'class-network-overview-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'class-network-single-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'class-network-all-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'class-site-category-repository.php';

class Network_Summary
{
	const site_option = 'network_summary';
	const network_option = 'network_summary_network';
	const version_option = 'network_summary_version';
	const version = '2.0.4';

	private $site_categories;

	/**
	 * Construct the plugin object and registers actions and shortcodes.
	 */
	public function __construct() {
		add_shortcode( 'netview', array( 'Network_Overview_Shortcode', 'render' ) );
		add_shortcode( 'netview-single', array( 'Network_Single_Shortcode', 'render' ) );
		add_shortcode( 'netview-all', array( 'Network_All_Shortcode', 'render' ) );

		$this->site_categories = new Site_Category_Repository();
		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-network-summary-admin.php';
			add_action( 'admin_init', array( $this, 'maybe_update' ) );
			new Network_Summary_Admin( $this, $this->site_categories );
		}

		add_action( 'wpmu_new_blog', array( $this, 'add_new_site' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Performs the required setup on activation. Setting default values for the settings.
	 */
	public function activate() {
		foreach ( $this->get_sites() as $site_id ) {
			add_blog_option( $site_id, Network_Summary::site_option, $this->get_default_site_values() );
		}
		add_site_option( Network_Summary::network_option, $this->get_default_network_values() );
		add_site_option( Network_Summary::version_option, Network_Summary::version );
		flush_rewrite_rules();

		$this->site_categories->create_table();
	}

	public function get_default_site_values() {
		return array(
			'share_site' => false,
			'site_description' => ''
		);
	}

	public function get_default_network_values() {
		return array(
			'deciding_role' => 'network_admin',
			'rss_limit' => 50
		);
	}

	/**
	 * Checks whether the database has to get updated. If so executes the appropriate scripts.
	 */
	public function maybe_update() {
		$old_version = get_site_option( Network_Summary::version_option );
		switch ( $old_version ) {
			case null:
				// Then we are 1.1.0 or earlier
				add_site_option( Network_Summary::network_option, array_merge( $this->get_default_network_values(),
					get_site_option( 'multisite_overview_network' ) ) );
				delete_site_option( 'multisite_overview_network' );
				foreach ( $this->get_sites() as $site_id ) {
					add_blog_option( $site_id, Network_Summary::site_option, get_blog_option( $site_id, 'multisite_overview' ) );
					delete_blog_option( $site_id, 'multisite_overview' );
				}
				$this->site_categories->create_table();
				break;
			case '1.1.5':
				$this->site_categories->create_table();
			case '2.0.0':
			case '2.0.1':
			case '2.0.2':
				foreach ( $this->get_sites() as $site_id ) {
					$option = get_blog_option( $site_id, Network_Summary::site_option );
					$option['share_site'] = $option['share_site'] == 1;
					update_blog_option( $site_id, Network_Summary::site_option, $option );
				}
				add_site_option(
					Network_Summary::network_option,
					array_merge( $this->get_default_network_values(), get_site_option( 'network_summary' ) )
				);
				delete_site_option( 'network_summary' );
			case '2.0.3':
				global $wpdb;
				$engine = $wpdb->get_row( "SELECT ENGINE FROM information_schema.TABLES where TABLE_NAME = '$wpdb->blogs'", ARRAY_A );
				$engine = $engine['ENGINE'];
				$wpdb->query( "ALTER TABLE $wpdb->site_categories ENGINE=$engine" );
				$wpdb->query( "ALTER TABLE $wpdb->site_categories_relationships ENGINE=$engine" );
		}

		if ( $old_version !== Network_Summary::version ) {
			update_site_option( Network_Summary::version_option, Network_Summary::version );
		}
	}

	/**
	 * Hook executed when a new site is created.
	 * @param $site_id int id of the new site.
	 */
	public function add_new_site( $site_id ) {
		add_blog_option( $site_id, Network_Summary::site_option, array(
				'share_site' => '0',
				'site_description' => '' )
		);
	}

	/**
	 * Hook into WP's init action hook.
	 */
	public function init() {
		add_feed( 'rss2-network', array( $this, 'get_rss2_feed' ) );
		wp_register_style(
			'network_summary',
			NETWORK_SUMMARY_URL . 'css/network-summary.css',
			array(),
			Network_Summary::version
		);
	}

	/**
	 * Hook for registering the widgets.
	 */
	public function register_widgets() {
		register_widget( 'Site_Description_Field_Widget' );
	}

	public function get_rss2_feed() {
		$rss_template = NETWORK_SUMMARY_DIR . 'templates/feed-rss2-network.php';
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
		if ( false === ( $site_list = get_transient( 'network_summary_site_list' ) ) ) {
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
			if ( share_site( $site_id ) ) {
				array_push( $shared_sites, $site_id );
			}
		}
		return $shared_sites;
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