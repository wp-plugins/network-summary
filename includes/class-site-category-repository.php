<?php

class Site_Category_Repository {
	public function __construct() {
		global $wpdb;
		$table_name                          = $wpdb->base_prefix . 'site_categories';
		$wpdb->site_categories               = $table_name;
		$table_name                          = $wpdb->base_prefix . 'site_categories_relationships';
		$wpdb->site_categories_relationships = $table_name;
	}

	public function create_table() {
		global $wpdb;

		$engine = $wpdb->get_row( "SELECT ENGINE FROM information_schema.TABLES where TABLE_NAME = '$wpdb->blogs'", ARRAY_A );
		$engine = $engine['ENGINE'];

		$sql = "CREATE TABLE $wpdb->site_categories (
			id BIGINT NOT NULL AUTO_INCREMENT,
			created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			name VARCHAR(55) NOT NULL,
			description VARCHAR(255) DEFAULT NULL,
			PRIMARY KEY id (id)
		)
		ENGINE $engine,
		DEFAULT COLLATE utf8_general_ci;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbdelta( $sql );

		$sql = "CREATE TABLE $wpdb->site_categories_relationships (
			blog_id BIGINT NOT NULL,
			category_id BIGINT NOT NULL,
			PRIMARY KEY id (blog_id, category_id),
			INDEX blog_ind (blog_id),
			INDEX category_ind (category_id),
			FOREIGN KEY (blog_id)
				REFERENCES $wpdb->blogs(blog_id),
			FOREIGN KEY (category_id)
				REFERENCES $wpdb->site_categories(id)
		)
		ENGINE $engine,
		DEFAULT COLLATE utf8_general_ci;";

		dbdelta( $sql );
	}

	public function get_all() {
		global $wpdb;

		return $wpdb->get_results( "SELECT id, name, description FROM $wpdb->site_categories", OBJECT_K );
	}

	public function add( array $category ) {
		global $wpdb;

		return $wpdb->insert( $wpdb->site_categories, $category );
	}

	public function delete( $id ) {
		global $wpdb;

		return $wpdb->delete( $wpdb->site_categories, array( 'id' => $id ) );
	}

	public function get_by_id( $id ) {
		global $wpdb;

		return $wpdb->get_row( "SELECT id, name, description FROM $wpdb->site_categories where id = $id", OBJECT );
	}

	public function update( $id, array $category ) {
		global $wpdb;

		return $wpdb->update( $wpdb->site_categories, $category, array( 'id' => $id ) );
	}

	public function get_by_site( $site_id ) {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT cat.id, cat.name, cat.description
			 FROM $wpdb->site_categories cat
			 JOIN $wpdb->site_categories_relationships rel ON cat.id = rel.category_id
			 WHERE rel.blog_id = $site_id", OBJECT_K
		);
	}

	public function set_site_category( $site, $category ) {
		global $wpdb;

		return $wpdb->replace( $wpdb->site_categories_relationships, array( 'blog_id'     => $site,
		                                                                    'category_id' => $category
			) );
	}

	public function remove_site_category( $site, $category ) {
		global $wpdb;

		return $wpdb->delete( $wpdb->site_categories_relationships, array( 'blog_id'     => $site,
		                                                                   'category_id' => $category
			) );
	}
}