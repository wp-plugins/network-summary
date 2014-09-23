<?php

if ( isset( $category ) ) : ?>
	<div class="wrap">
		<h2><?php _e( 'Edit Site Category', 'network-summary' ) ?></h2>

		<form name="edit-site-category" id="edit-site-category" method="post"
		      action="<?php echo admin_url( 'admin-post.php' ) ?>" class="validate">
			<input type="hidden" name="action" value="edit_site_category"/>
			<input type="hidden" name="site-category[id]" value="<?php echo esc_attr( $category->id ); ?>"/>
			<?php wp_nonce_field( 'edit-site-category', '_wpnonce_edit_site_category' ); ?>
			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="category-name"><?php _e( 'Name', 'network-summary' ); ?></label></th>
					<td><input name="site-category[name]" id="site-category-name" type="text"
					           value="<?php echo $category->name; ?>" size="40"
					           required="true"/>

						<p class="description"><?php _e( 'The name is how it appears on your site.', 'network-summary' ); ?></p>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label
							for="category-description"><?php _e( 'Description', 'network-summary' ); ?></label></th>
					<td><textarea name="site-category[description]" id="category-description" rows="5"
					              cols="40"><?php echo $category->description; ?></textarea>

						<p><?php _e( 'The description can be shown by some shortcodes.', 'network-summary' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Update Category', 'network-summary' ) ); ?>
		</form>
	</div>
<?php endif;
 