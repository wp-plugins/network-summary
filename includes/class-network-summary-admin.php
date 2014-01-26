<?php

require_once dirname( __FILE__ ) . '/class-site-category-list-table.php';
require_once dirname( __FILE__ ) . '/class-sites-list-table.php';

class Network_Summary_Admin
{
	private $network_summary;
	private $site_categories;

	public function __construct( Network_Summary $network_summary, Site_Category_Repository $site_categories ) {
		$this->network_summary = $network_summary;
		$this->site_categories = $site_categories;

		add_action( 'network_admin_menu', array($this, 'add_network_admin_page') );
		add_action( 'admin_post_update_network_summary_network_settings', array($this, 'update_network_settings') );

		add_action( 'admin_post_add_site_category', array($this, 'add_site_category') );
		add_action( 'admin_post_edit_site_category', array($this, 'edit_site_category') );

		add_action( 'admin_init', array($this, 'init_settings') );
		add_action( 'admin_init', array($this, 'register_resources') );
	}

	/**
	 * Adds a setting for the network whether site admins are allowed to opt out on their own. Also adds the individual
	 * settings for each site whether they should be displayed in the overview or not.
	 */
	public function init_settings() {
		define('PAGE', 'reading');
		define('SECTION', 'share_site_settings');

		add_settings_section(
			SECTION,
			'Network Summary',
			array($this, 'render_sharing_settings_section'),
			PAGE
		);

		add_settings_field(
			'share_site',
			'Show your content',
			array($this, 'render_share_site_setting'),
			PAGE,
			SECTION,
			array('current' => $this->network_summary->get_option( 'share_site' ))
		);

		add_settings_field(
			'site_categories',
			'Site Categories',
			array($this, 'render_site_categories_setting'),
			PAGE,
			SECTION,
			array('current' => get_site_categories())
		);

		add_settings_field(
			'site_description',
			'Site Description',
			array($this, 'render_description_setting'),
			PAGE,
			SECTION,
			array('current' => $this->network_summary->get_option( 'site_description' ))
		);

		register_setting( PAGE, NETWORK_SUMMARY_OPTIONS, array($this, 'validate_update_site_settings') );

		register_setting( 'network_summary_settings', NETWORK_SUMMARY_OPTIONS );
	}

	public function register_resources() {
		wp_register_style(
			'jquery.chosen',
			NETWORK_SUMMARY_URL . 'css/chosen.css',
			array(),
			'1.0'
		);

		wp_register_style(
			'network_summary_admin',
			NETWORK_SUMMARY_URL . 'css/network-summary-admin.css',
			array('jquery.chosen'),
			NETWORK_SUMMARY_VERSION
		);

		wp_register_script(
			'jquery.chosen',
			NETWORK_SUMMARY_URL . 'js/chosen.jquery.js',
			array('jquery'),
			'1.0'
		);

		wp_register_script(
			'network_summary_admin',
			NETWORK_SUMMARY_URL . 'js/network-summary-admin.js',
			array('jquery.chosen'),
			NETWORK_SUMMARY_VERSION
		);
	}

	public function add_network_admin_page() {
		add_submenu_page( 'settings.php', 'Network Summary Settings', 'Network Summary', 'manage_network',
			'network-summary-settings', array($this, 'network_settings_page') );
		add_submenu_page( 'settings.php', 'Site Categories', 'Site Categories', 'manage_network',
			'network-summary-categories', array($this, 'site_categories_page') );
	}

	public function update_network_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		check_admin_referer( 'network_summary_settings' );
		if ( isset($_POST['deciding_role']) && in_array( $_POST['deciding_role'], array('network_admin', 'network_admin') ) ) {
			$option = get_site_option( NETWORK_SUMMARY_OPTIONS );
			$option['deciding_role'] = $_POST['deciding_role'];
			update_site_option( NETWORK_SUMMARY_OPTIONS, $option );
		}

		$site_options = $_POST['site_setting'];
		if ( is_array( $site_options ) ) {
			foreach ( $site_options as $site_id => $settings ) {
				$site_option = get_blog_option( $site_id, NETWORK_SUMMARY_OPTIONS );
				$site_option['share_site'] = $settings['share_site'] == 1 ? '1' : '0';
				update_blog_option( $site_id, NETWORK_SUMMARY_OPTIONS, $site_option );

				if ( isset($settings['categories']) ) {
					$this->update_categories( $site_id, $settings['categories'] );
				} else {
					$this->update_categories( $site_id, array() );
				}
			}
		}

		wp_redirect( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php?page=network-summary-settings' ) ) );
	}

	public function add_site_category() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		check_admin_referer( 'add-site-category', '_wpnonce_add_site_category' );

		if ( isset($_POST['site-category']) ) {
			$new_category = $_POST['site-category'];

			$this->site_categories->add( array(
				'name' => sanitize_text_field( $new_category['name'] ),
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
		if ( isset($_POST['site-category']) ) {
			$new_category = $_POST['site-category'];

			$this->site_categories->update( $new_category['id'], array(
				'name' => sanitize_text_field( $new_category['name'] ),
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

		$sites_table = new Sites_List_Table($this->network_summary, $this);
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
				if ( isset($_GET['id']) ) {
					$category = $this->site_categories->get_by_id( $_GET['id'] );
					require_once NETWORK_SUMMARY_DIR . '/templates/site-category-edit.php';
				}
				break;
			case 'delete':
				check_admin_referer( 'delete_site_category', '_wpnonce' );
				if ( isset($_GET['id']) ) {
					$this->site_categories->delete( $_GET['id'] );
				}
				wp_redirect( add_query_arg( 'deleted', 'true', network_admin_url( 'settings.php?page=network-summary-categories' ) ) );
				break;
			default:
				$categories_table = new Site_Category_List_Table($this->site_categories);
				$categories_table->prepare_items();
				require_once NETWORK_SUMMARY_DIR . '/templates/site-categories.php';
		}
	}

	private function current_action() {
		if ( isset($_REQUEST['action']) && - 1 != $_REQUEST['action'] )
			return $_REQUEST['action'];
		return false;
	}

	public function render_sharing_settings_section() {
		if ( $this->is_user_allowed_to_edit_site_settings() )
			printf( '<p>%s</p>', __( 'There may be times when the network wants to list all sites, including their recent
			posts. Would you like your site to be shown in these lists? (Note that the network administrators may override
			your setting.)', 'network-summary' ) );
		else
			printf( '<p>%s <a href="%s">%s</a>%s</p>',
				__( 'There may be times when the network wants to list all sites, including their recent posts. Whether
				your site is shown in these lists or not is decided by your network administrators.', 'network-summary' ),
				get_site_option( 'admin_email' ),
				__( ' them, if you would like to change this setting.', 'network-summary' )
			);
	}

	public function render_share_site_setting( $args ) {
		$option = $args['current'];

		if ( $this->is_user_allowed_to_edit_site_settings() ) {
			?>
			<label for="share_site_include">
				<input id="share_site_include" type="radio"
				       name="<?php echo NETWORK_SUMMARY_OPTIONS ?>[share_site]"
				       value="1" <?php checked( $option, '1' ) ?>
				"> <?php _e( 'Yes, show it!', 'network-summary' ); ?>
			</label>
			<br>
			<label for="share_site_exclude">
				<input id="share_site_exclude" type="radio"
				       name="<?php echo NETWORK_SUMMARY_OPTIONS ?>[share_site]"
				       value="0" <?php echo checked( $option, '0' ) ?>
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
			echo $this->get_multi_select_categories( NETWORK_SUMMARY_OPTIONS . '[categories]', $args['current'] );
		} else {
			if ( empty($args['current']) ) {
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

	public function render_description_setting( $args ) {
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
		wp_editor( $args['current'], NETWORK_SUMMARY_OPTIONS . '[site_description]', $editor_settings );
	}

	/**
	 * Validation of the site settings input.
	 * @param $input $_POST parameters from the reading settings section.
	 * @return mixed|void if permissions are okay it returns the updated settings. Otherwise it exists WordPress.
	 */
	public function validate_update_site_settings( $input ) {
		$output = get_option( NETWORK_SUMMARY_OPTIONS );
		if ( $input != null ) {
			if ( isset($input['share_site']) ) {
				if ( $this->is_user_allowed_to_edit_site_settings() ) {
					$output['share_site'] = $input['share_site'] === '1' ? '1' : '0';

					if ( isset($input['categories']) ) {
						$this->update_categories( get_current_blog_id(), $input['categories'] );
					} else {
						$this->update_categories( get_current_blog_id(), array() );
					}
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
	 * Checks whether the user has the appropriate role to update the site settings. This depends on the role of the user
	 * and the settings of the plugin.
	 *
	 * @return bool true if the settings allow the current user to update the site settings.
	 */
	private function is_user_allowed_to_edit_site_settings() {
		return (
			$this->network_summary->get_option( 'deciding_role', true ) == 'site_admin' AND
			current_user_can( 'manage_options' )) OR current_user_can( 'manage_networks'
		);
	}

	private function update_categories( $blog_id, $categories ) {
		$current_categories = get_site_categories( $blog_id );
		foreach ( $categories as $category ) {
			set_site_category( $blog_id, $category );
			if ( array_key_exists( $category, $current_categories ) ) {
				unset($current_categories[$category]);
			}
		}
		foreach ( $current_categories as $cat_to_delete ) {
			remove_site_category( $blog_id, $cat_to_delete->id );
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
		return sprintf( '<select name="%s[]" class="category-select" multiple>%s</select>',
			$name,
			implode( $categories ) );
	}
}
 