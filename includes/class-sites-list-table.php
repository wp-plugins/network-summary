<?php

require_once dirname( __FILE__ ) . '/class-network-summary-list-table.php';

class Sites_List_Table extends Network_Summary_List_Table
{
	private $network_summary;
	private $admin;

	public function __construct( Network_Summary $network_Summary, Network_Summary_Admin $admin ) {
		$this->network_summary = $network_Summary;
		$this->admin = $admin;
	}

	protected function get_items() {
		$items = array();
		foreach ( $this->network_summary->get_sites() as $site_id ) {
			switch_to_blog( $site_id );
			$option = get_option( Network_Summary::site_option );
			array_push( $items, array(
				'id' => $site_id,
				'name' => get_bloginfo( 'bloginfo' ),
				'categories' => get_site_categories( $site_id ),
				'sharing' => $option['share_site'],
				'description' => $option['site_description'],
				'no_posts' => wp_count_posts()->publish,
				'no_pages' => wp_count_posts( 'page' )->publish,
				'last_post' => wp_get_recent_posts( array(
					'numberposts' => 1,
					'post_status' => 'publish'
				), 'OBJECT' )
			) );
			restore_current_blog();
		}
		function sort_by_name( $a, $b ) {
			return strcmp( $b['name'], $a['name'] );
		}

		usort( $items, 'sort_by_name' );

		return $items;
	}

	protected function get_all_columns() {
		return array(
			'id' => __( 'ID', 'network-summary' ),
			'name' => __( 'Site Name', 'network-summary' ),
			'categories' => __( 'Categories', 'network-summary' ),
			'sharing' => __( 'Sharing', 'network-summary' ),
			'description' => __( 'Site Description', 'network-summary' ),
			'no_posts' => __( '# Posts', 'network-summary' ),
			'no_pages' => __( '# Pages', 'network-summary' ),
			'last_post' => __( 'Last Post', 'network-summary' )
		);
	}

	protected function get_column_widths() {
		return array(
			'id' => '2%',
			'name' => '15%',
			'categories' => '15%',
			'description' => '25%',
			'last_post' => '25%'
		);
	}

	protected function column_name( $item ) {
		return sprintf( '<a href="%s">%s</a>', get_admin_url( $item['id'], 'options-reading.php' ), $item['name'] );
	}

	protected function column_categories( $item ) {
		return $this->admin->get_multi_select_categories( 'site_setting[' . $item['id'] . '][categories]', $item['categories'] );
	}

	protected function column_sharing( $item ) {
		return
			sprintf( '<label><input type="radio" name="site_setting[%s][share_site]" value="1" %s />%s</label>',
				$item['id'],
				checked( $item['sharing'], true, false ),
				__( 'Share', 'network-summary' )
			) .
			sprintf( '<label><input type="radio" name="site_setting[%s][share_site]" value="0" %s />%s</label>',
				$item['id'],
				checked( $item['sharing'], false, false ),
				__( 'Hide', 'network-summary' )
			);
	}

	protected function column_description( $item ) {
		return empty( $item['description'] ) ? __( 'No description yet.', 'network-summary' ) : $item['description'];
	}

	protected function column_last_post( $item ) {
		if ( ! empty( $item['last_post'] ) ) {
			$post = $item['last_post'][0];
			return sprintf( '<a href="%s">%s</a><br> on %s by %s',
				get_permalink( $post->ID ),
				$post->post_title,
				date_i18n( 'Y/m/d', strtotime( $post->post_date ) ),
				get_userdata( $post->post_author )->display_name
			);
		} else {
			return __( 'No posts yet.', 'network-summary' );
		}
	}
}