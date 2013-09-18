<?php

class Network_Summary
{
	const SITE_SETTING_NAME = 'network_summary';
	const NETWORK_SETTING_NAME = 'network_summary';
	const CURRENT_VERSION = '1.1.0';

	/**
	 * Construct the plugin object by registering actions and shortcodes.
	 */
	public function __construct() {
		add_action( 'admin_init', array(&$this, 'admin_init') );
		add_action( 'admin_init', array(&$this, 'maybe_update') );
		add_action( 'network_admin_menu', array(&$this, 'add_network_admin_page') );

		add_shortcode( 'netview', array(&$this, 'netview_overview') );
		add_shortcode( 'netview-single', array(&$this, 'netview_single') );
		add_shortcode( 'netview-all', array(&$this, 'netview_all') );

		add_action( 'wpmu_new_blog', array(&$this, 'add_new_site') );

		add_action( 'admin_post_update_network_summary_network_settings',
			array(&$this, 'update_network_settings') );

		add_action( 'widgets_init', function () {
			register_widget( 'Site_Description_Field_Widget' );
		} );
	}

	/**
	 * Performs the required setup on activation. Setting default values for the settings.
	 */
	public static function activate() {
		$site_list = Network_Summary::list_sites();
		foreach ( $site_list as $site_id ) {
			update_blog_option( $site_id, Network_Summary::SITE_SETTING_NAME, array(
					'share_site' => '0',
					'site_description' => '')
			);
		}
		update_site_option( Network_Summary::NETWORK_SETTING_NAME, array('deciding_role' => 'network_admin') );
		update_site_option( 'network_summary_version', Network_Summary::CURRENT_VERSION );
	}

	/**
	 * Tear down on deactivation. Deletes all the settings.
	 */
	public static function deactivate() {
		foreach ( Network_Summary::list_sites() as $site_id ) {
			delete_blog_option( $site_id, Network_Summary::SITE_SETTING_NAME );
		}
		delete_site_option( Network_Summary::NETWORK_SETTING_NAME );
	}

	/**
	 * Checks whether the database has to get updated. If so executes the appropriate scripts.
	 */
	public function maybe_update() {
		$old_version = get_site_option( 'network_summary_version' );
		if ( $old_version === null ) {
			// Then we are 1.1.0 or earlier
			update_site_option( Network_Summary::NETWORK_SETTING_NAME, get_site_option( 'multisite_overview_network' ) );
			delete_site_option( 'multisite_overview_network' );
			foreach ( $this->list_sites() as $site_id ) {
				update_blog_option( $site_id, Network_Summary::NETWORK_SETTING_NAME, get_blog_option( $site_id,
					'multisite_overview' ) );
				delete_blog_option( $site_id, 'multisite_overview' );
			}
			update_site_option( 'network_summary_version', Network_Summary::CURRENT_VERSION );
		}
	}

	/**
	 * Hook executed when a new site is created.
	 * @param $site_id int id of the new site.
	 */
	public function add_new_site( $site_id ) {
		update_blog_option( $site_id, Network_Summary::SITE_SETTING_NAME, array(
				'share_site' => '0',
				'site_description' => '')
		);
	}

	/**
	 * Hook into WP's admin_init action hook.
	 */
	public function admin_init() {
		$this->init_settings();
	}

	/**
	 * Hook into WP's network_admin_menu action hook.
	 */
	public function add_network_admin_page() {
		add_submenu_page( 'settings.php', 'Network Summary Settings', 'Network Summary', 'manage_network',
			'network-summary-settings', array(&$this, 'network_settings_page') );
	}

	/**
	 * Callback for the settings page for the network admin.
	 */
	public function network_settings_page() {
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		wp_register_style( 'network_summary_admin', plugins_url( 'network-summary-admin.css', __FILE__ ) );
		wp_enqueue_style( 'network_summary_admin' );

		include dirname( __FILE__ ) . '/templates/network-settings.php';
	}

	/**
	 * Callback for the new action hook to update the network settings.
	 */
	public function update_network_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		check_admin_referer( 'network_summary_settings' );

		$new_option = $_POST[Network_Summary::NETWORK_SETTING_NAME];
		if ( $new_option['deciding_role'] === 'site_admin' || $new_option['deciding_role'] === 'network_admin' ) {
			$option = get_site_option( Network_Summary::NETWORK_SETTING_NAME );
			$option['deciding_role'] = $new_option['deciding_role'];
			update_site_option( Network_Summary::NETWORK_SETTING_NAME, $option );
		}

		$site_options = $_POST['share_site'];
		if ( is_array( $site_options ) ) {
			foreach ( $site_options as $site_id => $new_site_option ) {
				$site_option = get_blog_option( $site_id, Network_Summary::SITE_SETTING_NAME );
				$site_option['share_site'] = $new_site_option;
				update_blog_option( $site_id, Network_Summary::SITE_SETTING_NAME, $site_option );
			}
		}

		wp_redirect( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php?page=network-summary-settings' ) ) );
	}

	/**
	 * Adds a setting for the network whether site admins are allowed to opt out on their own. Also adds the individual
	 * settings for each site whether they should be displayed in the overview or not.
	 */
	public function init_settings() {
		define('PAGE', 'reading');
		define('SECTION', 'share_site_settings');

		add_settings_section( SECTION, 'Network Summary', array(&$this, 'render_sharing_settings_section'), PAGE );
		add_settings_field( 'share_site', 'Show your content', array(&$this, 'render_share_site_setting'), PAGE, SECTION );
		add_settings_field( 'site_description', 'Site Description', array(&$this, 'render_description_setting'), PAGE, SECTION );
		register_setting( PAGE, Network_Summary::SITE_SETTING_NAME, array(&$this, 'validate_update_site_settings') );

		register_setting( 'network_summary_settings', Network_Summary::NETWORK_SETTING_NAME );
	}

	public function render_sharing_settings_section() {
		if ( $this->is_user_allowed_to_edit_site_settings() )
			echo '<p>There may be times when the network wants to list all sites, including their recent posts. Would you like
 			your site to be shown in these lists?  (Note that the network administrators may override your setting.)</p>';
		else
			echo '<p>There may be times when the network wants to list all sites, including their recent posts. Whether
			your site is shown in these lists or not is decided by your network administrators. <a href="mailto:' .
				get_site_option( 'admin_email' ) . '">Contact</a> them, if you would like to change this setting.';
	}

	public function render_share_site_setting() {
		$option = $this->get_plugin_option( 'share_site' );

		if ( $this->is_user_allowed_to_edit_site_settings() ) {
			?>
			<label for="share_site_include">
				<input id="share_site_include" type="radio"
				       name="<?php echo Network_Summary::SITE_SETTING_NAME ?>[share_site]"
				       value="1" <?php checked( $option, '1' ) ?>
				"> Yes, show it!
			</label>
			<br>
			<label for="share_site_exclude">
				<input id="share_site_exclude" type="radio"
				       name="<?php echo Network_Summary::SITE_SETTING_NAME ?>[share_site]"
				       value="0" <?php echo checked( $option, '0' ) ?>
				"> No, do not show it at this time.
			</label>
		<?php
		} else {
			if ( $option === '0' )
				echo 'Your content is currently not shown.';
			else
				echo 'Your content is shown!';
		}
	}

	public function render_description_setting() {
		$option = $this->get_plugin_option( 'site_description' );

		$editor_settings = array(
			'wpautop' => true,
			'media_buttons' => false,
			'textarea_rows' => 10,
			'teeny' => true,
			'tinymce' => array(
				'theme_advanced_buttons1' => 'bold,italic,|,link,unlink'
			)
		);

		echo '<p class="description">This text is used whenever a summary of your blog is needed. Keep it brief but informative.</p>';
		wp_editor( $option, Network_Summary::SITE_SETTING_NAME . '[site_description]', $editor_settings );
	}

	public function validate_update_site_settings( $input ) {
		$output = get_option( Network_Summary::SITE_SETTING_NAME );
		if ( $input != null ) {
			if ( isset($input['share_site']) ) {
				if ( $this->is_user_allowed_to_edit_site_settings() ) {
					$output['share_site'] = $input['share_site'] === '1' ? '1' : '0';
				} else {
					wp_die( 'You do not have sufficient permissions to modify this setting.', E_USER_ERROR );
				}
			}
			if ( isset($input['site_description']) ) {
				$allowed_html = array(
					'a' => array(
						'href' => array(),
						'title' => array(),
						'target' => array()
					),
					'br' => array(),
					'em' => array(),
					'strong' => array(),
					'p' => array()
				);
				$output['site_description'] = wp_kses( $input['site_description'], $allowed_html );
			}
		}
		return $output;
	}


	/**
	 * Displays a overview of all sites in a two column display in alphabetic order.
	 *
	 * @param $atts array with a include list and a exclude list. If include is empty, all sites are included,
	 * except the ones in the exclude list. This is always overridden by the sharing setting of the individual blog.
	 * Also accepts a numposts parameter for the number of recent posts displayed. Can be 0 and defaults to 2. The sort
	 * parameter can be either 'abc' or 'posts'. 'abc' is the default value and sorts the posts alphabetically. 'posts'
	 * sorts the sites so that the one with the most recents posts are listed first. Finally it takes a layout parameter
	 * which takes either 'grid' (default) or 'table' and displays the sites accordingly.
	 */
	public function netview_overview( $atts ) {
		$params = shortcode_atts( array(
			'include' => $this->get_shared_sites(),
			'exclude' => array(),
			'numposts' => 2,
			'sort' => 'abc',
			'layout' => 'grid'
		), $atts, 'netview' );

		$param_hash = md5( serialize( $params ) );

		wp_register_style( 'network_summary', plugins_url( 'network-summary.css', __FILE__ ) );
		wp_enqueue_style( 'network_summary' );

		if ( false === ($result = get_transient( 'netview_overview_' . $param_hash )) ) {
			if ( ! is_array( $params['include'] ) ) {
				$params['include'] = explode( ',', $params['include'] );
			}
			if ( ! is_array( $params['exclude'] ) ) {
				$params['exclude'] = explode( ',', $params['exclude'] );
			}

			if ( ! is_numeric( $params['numposts'] ) || $params['numposts'] < 0 ) {
				return '<p><b>Illegal parameter <code>numposts</code> (must be integer value greater or equal than 0).</b></p>';
			}

			if ( strcmp( $params['sort'], 'abc' ) !== 0 && strcmp( $params['sort'], 'posts' ) !== 0 ) {
				return '<p><b>Illegal parameter <code>sort</code> (must be <code>abc</code> or <code>posts</code>).</b></p>';
			}

			if ( strcmp( $params['layout'], 'grid' ) !== 0 && strcmp( $params['layout'], 'table' ) !== 0 ) {
				return '<p><b>Illegal parameter <code>layout</code> (must be <code>grid</code> or <code>table</code>).</b></p>';
			}

			$params['include'] = array_intersect( $params['include'], $this->get_shared_sites() );
			$sites = array_diff( $params['include'], $params['exclude'] );

			if ( empty($sites) ) {
				return '<p><b>No sites to display.</b></p>';
			}

			$this->sort_sites( $sites, $params['sort'] );

			$result = '<div class="netview">';
			$i = 0;
			if ( 0 === strcmp( $params['layout'], 'table' ) ) {
				$result .= '<table class="netview-site"><tbody>';
			}
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				$name = '<h2 class="site-title"><a href="' . site_url() . '">' . get_bloginfo() . '</a></h2>';
				$description = wpautop( do_shortcode( $this->get_plugin_option( 'site_description' ) ) );
				if ( $params['numposts'] > 0 ) {
					$recent_posts = $this->get_recent_posts( $params['numposts'] );
				} else {
					$recent_posts = '';
				}

				if ( 0 === strcmp( $params['layout'], 'grid' ) ) {
					$result .= '<div class="netview-site ' . (($i ++ % 2 == 0) ? 'even' : 'odd') . '">';
					$result .= $name . $description . $recent_posts;
					$result .= '</div>';
				} else if ( 0 === strcmp( $params['layout'], 'table' ) ) {
					$result .= '<tr><td>';
					$result .= $name . $description;
					$result .= '</td><td>' . $recent_posts . '</td></tr>';
				}

				restore_current_blog();
			}
			if ( 0 === strcmp( $params['layout'], 'table' ) ) {
				$result .= '</tbody></table>';
			}

			$result .= '</div>';
			//set_transient( 'netview_overview_' . $param_hash, $result, 7200 );
		}
		return $result;
	}

	/**
	 * Displays one specific site in a featured way with an image.
	 *
	 * @param $atts array with a required id of the site to display and a url to a image for the site. Also accepts a
	 * numposts parameter for the number of recent posts displayed. Can be 0 and defaults to 2.
	 */
	public function netview_single( $atts ) {
		$params = shortcode_atts( array(
			'id' => "error",
			'img' => "",
			'numposts' => 2
		), $atts, 'netview-single' );

		$param_hash = md5( serialize( $params ) );

		wp_register_style( 'network_summary', plugins_url( 'network-summary.css', __FILE__ ) );
		wp_enqueue_style( 'network_summary' );

		if ( false === ($result = get_transient( 'netview_single_' . $param_hash )) ) {
			if ( ! in_array( $params['id'], $this->get_shared_sites() ) ) {
				return '<p><b>Not a valid blog id or sharing has been disabled for this blog.</b></p>';
			}

			if ( ! is_numeric( $params['numposts'] ) || $params['numposts'] < 0 ) {
				return '<p><b>Illegal parameter <code>numposts</code> (must be integer value greater or equal than 0).</b></p>';
			}

			switch_to_blog( $params['id'] );

			$result = '<div class="netview-single"><h2 class="site-title"><a href="' . site_url() . '">' .
				get_bloginfo() . '</a></h2><div class="site-description">';
			if ( ! empty($params['img']) ) {
				$result .= '<a href="' . site_url() . '"><img class="featured-img" src="' . $params['img'] . '"></a>';
			}
			$result .= wpautop( do_shortcode( $this->get_plugin_option( 'site_description' ) ) );
			if ( $params['numposts'] > 0 ) {
				$result .= $this->get_recent_posts( $params['numposts'] );
			}
			$result .= '</div></div>';

			restore_current_blog();

			set_transient( 'netview_single_' . $param_hash, $result, 7200 );
		}
		return $result;
	}

	/**
	 * Displays a index of all available sites in an alphabetic order.
	 *
	 * @param $atts array with a title attribute displayed before the list.
	 */
	public function netview_all( $atts ) {
		$params = shortcode_atts( array(
			'title' => "All Sites"
		), $atts, 'netview-all' );

		wp_register_style( 'network_summary', plugins_url( 'network-summary.css', __FILE__ ) );
		wp_enqueue_style( 'network_summary' );

		$title = '<div class="netview-all"><h2>' . $params['title'] . '</h2>';

		if ( false === ($result = get_transient( 'netview_all' )) ) {
			$sites = $this->get_shared_sites();

			if ( empty($sites) ) {
				return '<p><b>No sites to display.</b></p>';
			}

			$this->sort_sites( $sites );

			$result .= '<ul>';
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				$result .= '<li><a href="' . site_url() . '">' . get_bloginfo() . '</a></li>';
				restore_current_blog();
			}
			$result .= '</ul></div>';
		}
		set_transient( 'netview_all', $result, 7200 );
		return $title . $result;
	}

	/**
	 * Build a list of all sites in a network.
	 */
	public static function list_sites( $expires = 7200 ) {
		if ( ! is_multisite() ) return false;
		if ( false === ($site_list = get_transient( 'network_summary_site_list' )) ) {
			global $wpdb;
			$site_list = $wpdb->get_col( "SELECT * FROM $wpdb->blogs ORDER BY blog_id ASC" );
			set_site_transient( 'network_summary_site_list', $site_list, $expires );
		}
		return $site_list;
	}

	/**
	 * Checks whether the user has the appropriate role to update the site settings. This depends on the role of the user
	 * and the settings of the plugin.
	 *
	 * @return bool true if the settings allow the current user to update the site settings.
	 */
	private function is_user_allowed_to_edit_site_settings() {
		return ($this->get_plugin_option( 'deciding_role', $network = true ) == 'site_admin' AND
			current_user_can( 'manage_options' )) OR
		current_user_can( 'manage_networks' );
	}

	private function get_shared_sites() {
		$shared_sites = array();
		$site_list = $this->list_sites();
		foreach ( $site_list as $site_id ) {
			if ( '1' === $this->get_plugin_option( 'share_site', $site_id ) ) {
				array_push( $shared_sites, $site_id );
			}
		}
		return $shared_sites;
	}

	private function get_recent_posts( $number_of_posts ) {
		$result = '<h4>Most recent posts</h4>
		<ul class="site-recent-post">';

		$recent_posts = wp_get_recent_posts( array('numberposts' => $number_of_posts, 'post_status' => 'publish') );
		foreach ( $recent_posts as $post ) {
			$result .= '<li><a href="' . get_permalink( $post["ID"] ) . '" title="Read ' . $post["post_title"] . '.">'
				. $post["post_title"] . '</a><span class="date">'
				. date_i18n( get_option( 'date_format' ), strtotime( $post["post_date"] ) )
				. '</span></li>';
		}
		$result .= '</ul>';
		return $result;
	}

	private function sort_sites( &$sites, $sorting = 'abc' ) {
		if ( strcmp( $sorting, 'abc' ) === 0 ) {
			usort( $sites, function ( $site_a, $site_b ) {
				return strcmp( get_blog_option( $site_a, 'blogname' ), get_blog_option( $site_b, 'blogname' ) );
			} );
		} else if ( strcmp( $sorting, 'posts' ) === 0 ) {
			usort( $sites, function ( $site_a, $site_b ) {
				switch_to_blog( $site_a );
				$recent = wp_get_recent_posts( array('numberposts' => 1, 'post_status' => 'publish') );
				if ( empty ($recent) ) {
					return - 1000;
				} else {
					$recent = array_pop( $recent );
				}
				$date_recent_post_a = $recent['post_date'];
				restore_current_blog();
				switch_to_blog( $site_b );
				$recent = wp_get_recent_posts( array('numberposts' => 1, 'post_status' => 'publish') );
				if ( empty ($recent) ) {
					return 1000;
				} else {
					$recent = array_pop( $recent );
				}
				$date_recent_post_b = $recent['post_date'];
				restore_current_blog();
				return strcmp( $date_recent_post_b, $date_recent_post_a );
			} );
		}

	}

	public static function get_plugin_option( $tag, $blog_id = null, $network = false ) {
		if ( $network ) {
			$option = get_site_option( Network_Summary::NETWORK_SETTING_NAME );
		} else if ( $blog_id != null ) {
			$option = get_blog_option( $blog_id, Network_Summary::SITE_SETTING_NAME );
		} else {
			$option = get_option( Network_Summary::SITE_SETTING_NAME );
		}
		if ( isset($option[$tag]) ) {
			return $option[$tag];
		} else {
			return null;
		}
	}
}