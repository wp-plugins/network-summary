<?php

require_once dirname( __FILE__ ) . '/class-site-category-list-table.php';
require_once dirname( __FILE__ ) . '/class-sites-list-table.php';

class Network_Summary_Admin {
	private $network_summary;
	private $site_categories;

	public function __construct( Network_Summary $network_summary, Site_Category_Repository $site_categories ) {
		$this->network_summary = $network_summary;
		$this->site_categories = $site_categories;

		add_action( 'network_admin_menu', array( $this, 'add_network_admin_page' ) );
		add_action( 'admin_post_update_network_summary_network_settings', array( $this, 'update_network_settings' ) );

		add_action( 'admin_post_add_site_category', array( $this, 'add_site_category' ) );
		add_action( 'admin_post_edit_site_category', array( $this, 'edit_site_category' ) );

		add_action( 'admin_init', array( $this, 'init_settings' ) );
		add_action( 'admin_init', array( $this, 'register_resources' ) );
	}

	/**
	 * Adds a setting for the network whether site admins are allowed to opt out on their own. Also adds the individual
	 * settings for each site whether they should be displayed in the overview or not.
	 */
	public function init_settings() {
		$page    = 'reading';
		$section = 'share_site_settings';

		$current_settings = get_option( Network_Summary::site_option );
		add_settings_section(
			$section,
			'Network Summary',
			array( $this, 'render_sharing_settings_section' ),
			$page
		);

		add_settings_field(
			'share_site',
			'Show your content',
			array( $this, 'render_share_site_setting' ),
			$page,
			$section,
			array( 'current' => $current_settings['share_site'] )
		);

		add_settings_field(
			'site_categories',
			'Site Categories',
			array( $this, 'render_site_categories_setting' ),
			$page,
			$section,
			array( 'current' => get_site_categories() )
		);

		add_settings_field(
			'site_description',
			'Site Description',
			array( $this, 'render_description_setting' ),
			$page,
			$section,
			array( 'current' => $current_settings['site_description'] )
		);

		register_setting( $page, Network_Summary::site_option, array( $this, 'validate_site_settings' ) );

		add_filter( 'sanitize_option_' . Network_Summary::network_option, array( $this, 'validate_network_settings' ) );
		add_filter( 'sanitize_option_' . Network_Summary::site_option, array( $this, 'validate_site_settings' ) );
	}

	public function register_resources() {
		wp_register_style(
			'network_summary_admin',
			NETWORK_SUMMARY_URL . 'css/network-summary-admin.css',
			array(),
			Network_Summary::version
		);

		wp_register_script(
			'network_summary_admin',
			NETWORK_SUMMARY_URL . 'js/network-summary-admin.js',
			array(),
			Network_Summary::version
		);
	}

	public function add_network_admin_page() {
		add_submenu_page( 'settings.php', 'Network Summary Settings', 'Network Summary', 'manage_network',
			'network-summary-settings', array( $this, 'network_settings_page' ) );
		add_submenu_page( 'settings.php', 'Site Categories', 'Site Categories', 'manage_network',
			'network-summary-categories', array( $this, 'site_categories_page' ) );
	}

	public function update_network_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		check_admin_referer( 'network_summary_settings' );

		if ( isset( $_POST['network_option'] ) ) {
			update_site_option( Network_Summary::network_option,
				array_merge( get_site_option( Network_Summary::network_option ), $_POST['network_option'] ) );
		}

		if ( is_array( $site_options = $_POST['site_setting'] ) ) {
			foreach ( $site_options as $site_id => $settings ) {
				$site_option               = get_blog_option( $site_id, Network_Summary::site_option );
				$site_option['share_site'] = $settings['share_site'] == 1 ? '1' : '0';

				update_blog_option( $site_id, Network_Summary::site_option, $site_option );

				if ( isset( $settings['categories'] ) ) {
					$this->update_categories( $site_id, $settings['categories'] );
				} else {
					$this->update_categories( $site_id, array() );
				}
			}
		}

		wp_redirect( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php?page=network-summary-settings' ) ) );
	}

	private function update_categories( $blog_id, $categories ) {
		$current_categories = get_site_categories( $blog_id );
		foreach ( $categories as $category ) {
			set_site_category( $blog_id, $category );
			if ( array_key_exists( $category, $current_categories ) ) {
				unset( $current_categories[ $category ] );
			}
		}
		foreach ( $current_categories as $cat_to_delete ) {
			remove_site_category( $blog_id, $cat_to_delete->id );
		}
	}

	public function validate_network_settings( $value ) {
		$result = $this->network_summary->get_default_network_values();
		if ( in_array( $value['deciding_role'], array( 'site_admin', 'network_admin' ) ) ) {
			$result['deciding_role'] = $value['deciding_role'];
		}
		if ( is_numeric( $value['rss_limit'] ) && $value['rss_limit'] > 0 && $value['rss_limit'] <= 1000 ) {
			$result['rss_limit'] = $value['rss_limit'];
		}

		return $result;
	}

	public function add_site_category() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		check_admin_referer( 'add-site-category', '_wpnonce_add_site_category' );

		if ( isset( $_POST['site-category'] ) ) {
			$new_category = $_POST['site-category'];

			$this->site_categories->add( array(
				'name'        => sanitize_text_field( $new_category['name'] ),
				'description' => sanitize_text_field( $new_category['description'] )
			) );
			wp_redirect( add_query_arg( 'added', 'true', network_admin_url( 'settings.php?page=network-summary-categories' ) ) );
		} else {
			wp_redirect( add_query_arg( 'error', 'true', network_admin_url( 'settings.php?page=network-summary-categories' ) ) );
		}
	}

	public function edit_site_category() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		check_admin_referer( 'edit-site-category', '_wpnonce_edit_site_category' );
		if ( isset( $_POST['site-category'] ) ) {
			$new_category = $_POST['site-category'];

			$this->site_categories->update( $new_category['id'], array(
				'name'        => sanitize_text_field( $new_category['name'] ),
				'description' => sanitize_text_field( $new_category['description'] )
			) );
			wp_redirect( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php?page=network-summary-categories' ) ) );
		} else {
			wp_redirect( add_query_arg( 'error', 'true', network_admin_url( 'settings.php?page=network-summary-categories' ) ) );
		}
	}

	public function network_settings_page() {
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		wp_enqueue_style( 'network_summary_admin' );

		$sites_table = new Sites_List_Table( $this->network_summary, $this );
		$sites_table->prepare_items();

		require_once NETWORK_SUMMARY_DIR . '/templates/network-settings.php';
	}

	public function site_categories_page() {
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		wp_enqueue_style( 'network_summary_admin' );

		switch ( $this->current_action() ) {
			case 'edit':
				if ( isset( $_GET['id'] ) ) {
					$category = $this->site_categories->get_by_id( $_GET['id'] );
					require_once NETWORK_SUMMARY_DIR . '/templates/site-category-edit.php';
				}
				break;
			case 'delete':
				check_admin_referer( 'delete_site_category', '_wpnonce' );
				if ( isset( $_GET['id'] ) ) {
					$this->site_categories->delete( $_GET['id'] );
				}
				wp_redirect( add_query_arg( 'deleted', 'true', network_admin_url( 'settings.php?page=network-summary-categories' ) ) );
				break;
			default:
				$categories_table = new Site_Category_List_Table( $this->site_categories );
				$categories_table->prepare_items();
				require_once NETWORK_SUMMARY_DIR . '/templates/site-categories.php';
		}
	}

	private function current_action() {
		if ( isset( $_REQUEST['action'] ) && - 1 != $_REQUEST['action'] ) {
			return $_REQUEST['action'];
		}

		return false;
	}

	public function render_sharing_settings_section() {
		if ( $this->is_user_allowed_to_edit_site_settings() ) {
			printf( '<p>%s</p>', __( 'There may be times when the network wants to list all sites, including their recent
			posts. Would you like your site to be shown in these lists? (Note that the network administrators may override
			your setting.)', 'network-summary' ) );
		} else {
			printf( '<p>%s <a href="%s">%s</a>%s</p>',
				__( 'There may be times when the network wants to list all sites, including their recent posts. Whether
				your site is shown in these lists or not is decided by your network administrators.', 'network-summary' ),
				get_site_option( 'admin_email' ),
				__( ' them, if you would like to change this setting.', 'network-summary' )
			);
		}
	}

	/**
	 * Checks whether the user has the appropriate role to update the site settings. This depends on the role of the user
	 * and the settings of the plugin.
	 *
	 * @return bool true if the settings allow the current user to update the site settings.
	 */
	private function is_user_allowed_to_edit_site_settings() {
		$option = get_site_option( Network_Summary::network_option );

		return (
			       $option['deciding_role'] == 'site_admin' AND
			       current_user_can( 'manage_options' ) ) OR current_user_can( 'manage_networks' );
	}

	public function render_share_site_setting( $args ) {
		$option = $args['current'];

		if ( $this->is_user_allowed_to_edit_site_settings() ) {
			?>
			<label for="share_site_include">
				<input id="share_site_include" type="radio"
				       name="<?php echo Network_Summary::site_option ?>[share_site]"
				       value="1" <?php checked( $option ) ?>
				"> <?php _e( 'Yes, show it!', 'network-summary' ); ?>
			</label>
			<br>
			<label for="share_site_exclude">
				<input id="share_site_exclude" type="radio"
				       name="<?php echo Network_Summary::site_option ?>[share_site]"
				       value="0" <?php echo checked( $option, false ) ?>
				"> <?php _e( 'No, do not show it at this time.' ); ?>
			</label>
		<?php
		} else {
			if ( $option ) {
				_e( 'Your content is shown!', 'network-summary' );
			} else {
				_e( 'Your content is currently not shown.', 'network-summary' );
			}
		}
	}

	public function render_site_categories_setting( $args ) {
		if ( $this->is_user_allowed_to_edit_site_settings() ) {
			echo $this->get_multi_select_categories( Network_Summary::site_option . '[categories]', $args['current'] );
		} else {
			if ( empty( $args['current'] ) ) {
				printf( '<p>%s</p>', __( 'Your blog currently has no category.', 'network-summary' ) );
			} else {
				printf( '<p>%s</p>', __( 'Your blog currently has the following categories:', 'network-summary' ) );
				echo '<ul>';
				foreach ( $args['current'] as $cat ) {
					printf( '<li><strong>%s</strong><br>%s</li>', $cat->name, $cat->description );
				}
				echo '<ul>';
			}
		}
	}

	public function get_multi_select_categories( $name, $current ) {
		$categories = $this->network_summary->get_all_categories();
		foreach ( $categories as &$category ) {
			$selected = array_key_exists( $category->id, $current );
			$category = sprintf( '<option value="%d" %s>%s</option>',
				$category->id,
				selected( $selected, true, false ),
				$category->name );
		}
		if ( empty( $categories ) ) {
			return __( 'No categories defined.', 'network-summary' );
		} else {
			return sprintf( '<select name="%s[]" class="category-select" multiple>%s</select>',
				$name,
				implode( $categories ) );
		}
	}

	public function render_description_setting( $args ) {
		$editor_settings = array(
			'wpautop'       => true,
			'media_buttons' => false,
			'textarea_rows' => 10,
			'textarea_name' => Network_Summary::site_option . '[site_description]',
			'teeny'         => true,
			'tinymce'       => array(
				'toolbar1' => 'bold,italic,|,link,unlink'
			)
		);

		echo '<p class="description">This text is used whenever a summary of your blog is needed. Keep it brief but informative.</p>';
		wp_editor( $args['current'], 'site_description', $editor_settings );
	}

	/**
	 * Validation of the site settings input.
	 *
	 * @param $input $_POST parameters from the reading settings section.
	 *
	 * @return mixed|void if permissions are okay it returns the updated settings. Otherwise it exists WordPress.
	 */
	public function validate_site_settings( $input ) {
		$output = get_option( Network_Summary::site_option );
		if ( $input != null ) {
			if ( isset( $input['share_site'] ) ) {
				if ( $this->is_user_allowed_to_edit_site_settings() ) {
					$output['share_site'] = $input['share_site'] === '1';

					if ( isset( $input['categories'] ) ) {
						$this->update_categories( get_current_blog_id(), $input['categories'] );
					} else {
						$this->update_categories( get_current_blog_id(), array() );
					}
				} else {
					wp_die( 'You do not have sufficient permissions to modify this setting.', E_USER_ERROR );
				}
			}
			if ( isset( $input['site_description'] ) ) {
				$allowed_html               = array(
					'a'      => array(
						'href'   => array(),
						'title'  => array(),
						'target' => array()
					),
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
					'p'      => array()
				);
				$output['site_description'] = wp_kses( $input['site_description'], $allowed_html );
			}
		}

		return $output;
	}
}
 