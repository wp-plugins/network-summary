<?php

require_once dirname( __FILE__ ) . '/class-network-summary-list-table.php';

class Site_Category_List_Table extends Network_Summary_List_Table {
	private $site_category_repository;

	public function __construct( Site_Category_Repository $site_category_repository ) {
		$this->site_category_repository = $site_category_repository;
	}

	protected function get_items() {
		return $this->site_category_repository->get_all();
	}

	protected function get_all_columns() {
		return array(
			'id'          => __( 'ID', 'network-summary' ),
			'name'        => __( 'Name', 'network-summary' ),
			'description' => __( 'Description', 'network-summary' )
		);
	}

	protected function get_hidden_columns() {
		return array();
	}

	protected function get_column_widths() {
		return array( 'id' => '5%', 'name' => '20%' );
	}

	protected function column_name( $item ) {
		$edit_url = add_query_arg(
			array(
				'page'   => 'network-summary-categories',
				'action' => 'edit',
				'id'     => $item->id
			),
			network_admin_url( 'settings.php' ) );

		$title      = sprintf( '<strong><a href="%1$s">%2$s</a></strong>',
			$edit_url,
			$item->name
		);
		$delete_url = add_query_arg(
			array(
				'page'     => 'network-summary-categories',
				'action'   => 'delete',
				'id'       => $item->id,
				'_wpnonce' => wp_create_nonce( 'delete_site_category' )
			),
			network_admin_url( 'settings.php' ) );

		$row_actions = sprintf( '<div class="row-actions">
		<span class="edit"><a href="%1$s">%2$s</a></span> |
		<span class="delete"><a href="%3$s">%4$s</a></span>',
			$edit_url,
			__( 'Edit' ),
			$delete_url,
			__( 'Delete' ) );

		return $title . $row_actions;
	}
}