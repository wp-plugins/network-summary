<?php
if ( ! current_user_can( 'manage_network' ) ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

if ( isset( $categories_table ) ) :

	if ( isset( $_GET['updated'] ) ) : ?>
		<div id="message" class="updated"><p><strong><?php _e( 'Category updated.', 'network-summary' ) ?></strong></p>
		</div>
	<?php endif;
	if ( isset( $_GET['error'] ) ) : ?>
		<div id="message" class="error"><p><strong><?php _e( 'There was an error.', 'network-summary' ) ?></strong></p>
		</div>
	<?php endif;
	if ( isset( $_GET['deleted'] ) ) : ?>
		<div id="message" class="updated"><p><strong><?php _e( 'Category deleted.', 'network-summary' ) ?></strong></p>
		</div>
	<?php endif;
	if ( isset( $_GET['added'] ) ) : ?>
		<div id="message" class="updated"><p><strong><?php _e( 'Category added.', 'network-summary' ) ?></strong></p>
		</div>
	<?php endif; ?>

	<div class="wrap">
		<h2><?php _e( 'Site Categories', 'network-summary' ) ?></h2>


		<div id="col-container">
			<div id="col-right">
				<div class="col-wrap">
					<?php $categories_table->display(); ?>
				</div>
			</div>
			<!-- col right -->
			<div id="col-left">
				<div class="col-wrap">
					<div class="form-wrap">
						<h3><?php _e( 'Add new site category', 'network-summary' ) ?></h3>

						<form id="add-site-category" method="post"
						      action="<?php echo admin_url( 'admin-post.php' ); ?>"
						      class="validate">
							<input type="hidden" name="action" value="add_site_category"/>
							<?php wp_nonce_field( 'add-site-category', '_wpnonce_add_site_category' ); ?>

							<div class="form-field form-required">
								<label for="category-name"><?php _e( 'Name', 'network-summary' ); ?></label>
								<input name="site-category[name]" id="site-category-name" type="text" value="" size="40"
								       required="true"/>

								<p class="description"><?php _e( 'The name is how it appears on your site.', 'network-summary' ); ?></p>
							</div>

							<div class="form-field">
								<label
									for="category-description"><?php _e( 'Description', 'network-summary' ); ?></label>
								<textarea name="site-category[description]" id="category-description" rows="5"
								          cols="40"></textarea>

								<p><?php _e( 'The description can be shown by some shortcodes.', 'network-summary' ); ?></p>
							</div>
							<?php submit_button( __( 'Add New Category', 'network-summary' ) ); ?>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php endif;