<?php
if ( ! current_user_can( 'manage_network' ) ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

if ( isset( $sites_table ) ) :
	global $network_summary;
	$option = get_site_option( Network_Summary::network_option );

	if ( isset( $_GET['updated'] ) ) {
		?>
		<div id="message" class="updated"><p><strong><?php _e( 'Options saved.' ) ?></strong></p></div><?php
	}
	?>

	<div class="wrap">
		<h2><?php _e( 'Network Summary Settings', 'network-summary' ) ?></h2>

		<p><?php _e( 'The following setting allow you to define whether each site administrator is able to decide on their own,
			whether their contents are visible to other sites or only network administrators should be able to decide this.
			Changing this setting has no influence on the current settings of each site.', 'network-summary' ); ?></p>
	</div>
	<form method="post"
	      action="<?php echo admin_url( 'admin-post.php?action=update_network_summary_network_settings' ); ?>">
		<?php wp_nonce_field( 'network_summary_settings' ); ?>
		<table class="form-table">
			<tbody>
			<tr>
				<th class="label-row-deciding-role"><label
						for="network_option[deciding_role]"><?php _e( 'Deciding Role on Sharing Content', 'network-summary' ); ?></label>
				</th>
				<td>
					<select name="network_option[deciding_role]" required>
						<option <?php selected( $option['deciding_role'], 'site_admin', true ) ?>
							value="site_admin">
							<?php _e( 'Site Admin', 'network-summary' ); ?>
						</option>
						<option <?php selected( $option['deciding_role'], 'network_admin', true ) ?>
							value="network_admin"><?php _e( 'Network Admin', 'network-summary' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th class="label-rss-limit"><label
						for="network_option[rss_limit]"><?php _e( 'RSS Feed Limit', 'network-summary' ); ?></label></th>
				<td>
					<input name="network_option[rss_limit]" type="number" min="1" max="1000" required
					       value="<?php echo $option['rss_limit']; ?>"/>
				</td>
			</tr>
			</tbody>
		</table>

		<h3><?php echo __( 'Site Sharing Settings' ) ?></h3>
		<?php $sites_table->display(); ?>
		<?php submit_button(); ?>
	</form>
<?php endif;