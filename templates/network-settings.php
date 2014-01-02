<?php
if ( ! current_user_can( 'manage_network' ) ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

$title = __( 'Network Summary Settings' );

$option = get_site_option( Network_Summary::NETWORK_SETTING_NAME );

$sites = $this->list_sites();

if ( isset($_GET['updated']) ) {
	?>
	<div id="message" class="updated"><p><strong><?php _e( 'Options saved.' ) ?></strong></p></div><?php
}
?>

<div class="wrap">
	<h2><?php echo esc_html( $title ); ?></h2>

	<p>The following setting allow you to define whether each site administrator is able to decide on their own, whether
		their contents are visible to other sites or only network administrators should be able to decide this. Changing
		this setting has no influence on the current settings of each site.</p>

	<form method="post"
	      action="<?php echo admin_url( 'admin-post.php?action=update_network_summary_network_settings' ); ?>">
		<?php wp_nonce_field( 'network_summary_settings' ); ?>
		<table class="form-table">
			<tbody>
			<tr>
				<th class="label-row-deciding-role"><label
						for="<?php echo Network_Summary::NETWORK_SETTING_NAME ?>[deciding_role]">Deciding
						Role on Sharing Content</label></th>
				<td>
					<select name="<?php echo Network_Summary::NETWORK_SETTING_NAME ?>[deciding_role]" required>
						<option <?php selected( $option['deciding_role'], 'site_admin', true ) ?> value="site_admin">
							Site Admin
						</option>
						<option <?php selected( $option['deciding_role'], 'network_admin', true ) ?>
							value="network_admin">Network Admin
						</option>
					</select>
				</td>
			</tr>
			</tbody>
		</table>
		<h3><?php echo __( 'Site Sharing Settings' ) ?></h3>
		<table class="form-table network-summary">
			<thead>
			<tr>
				<th>Site</th>
				<th style="width: 10%">Content is visible</th>
				<th style="width: 10%">Content is not visible</th>
				<th>Description</th>
				<th style="width: 5%"># Posts</th>
				<th style="width: 5%"># Pages</th>
				<th>Last Post</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $sites as $site_id ) {
				$option = Network_Summary::get_plugin_option( 'share_site', $site_id );
				$description = Network_Summary::get_plugin_option( 'site_description', $site_id );
				switch_to_blog( $site_id );
				$site = get_option( 'blogname' );
				$post_count = wp_count_posts()->publish;
				$page_count = wp_count_posts( 'page' )->publish;
				$post = wp_get_recent_posts( array(
					'numberposts' => 1,
					'post_status' => 'publish'
				), 'OBJECT' );
				$post = $post[0];
				?>
				<tr>
					<td><a
							href="<?php echo get_admin_url( $site_id, 'options-reading.php' ) ?>"><?php echo $site ?></a>
					</td>
					<td><input type="radio" name="share_site[<?php echo $site_id ?>]"
					           value="1" <?php checked( $option, '1' ) ?> ">
					</td>
					<td><input type="radio" name="share_site[<?php echo $site_id ?>]"
					           value="0" <?php checked( $option, '0' ) ?> ">
					</td>
					<td><?php echo $description ?></td>
					<td><?php echo $post_count ?></td>
					<td><?php echo $page_count ?></td>
					<td>
						<a href="<?php echo get_permalink( $post->ID ) ?>"><?php echo $post->post_title ?></a>
						<br> on <?php echo date_i18n( 'Y/m/d', strtotime( $post->post_date ) ) ?>
						by <?php echo get_userdata( $post->post_author )->display_name ?>
					</td>
				</tr>
				<?php
				restore_current_blog();
			} ?>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>