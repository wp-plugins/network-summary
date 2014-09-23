<?php
wp_enqueue_style(
	'custom-feed-builder',
	NETWORK_SUMMARY_URL . 'css/custom-feed-builder.css',
	array(),
	Network_Summary::version
);

wp_enqueue_script(
	'custom-feed-builder',
	NETWORK_SUMMARY_URL . 'js/custom-feed-builder.js',
	array( 'jquery' ),
	Network_Summary::version,
	true
);

get_header(); ?>

	<div id="container">
		<div id="content" role="main">
			<h1><?php _e( 'Custom RSS Feed Builder', 'network-summary' ); ?></h1>

			<p><?php _e( 'Build your own custom feed for this network by checking the sites you wish to be included in the feed. After selecting your categories and/or individual sites, your URL for your custom RSS feed will be available at the bottom of this page.', 'network-summary' ); ?></p>

			<p><?php _e( 'You can also paste the URL from a custom feed there in order to modify it.', 'network-summary' ); ?></p>

			<div id="custom-feed-form">
				<?php
				global $network_summary;
				$categories = $network_summary->get_all_categories();
				foreach ( $categories as $category_id => $category ) :
					$sites = $network_summary->get_sites_per_category( $category_id, true );
					if ( ! empty( $sites ) ) : ?>
						<div class="category">
							<h3><?php echo esc_html( $category->name ); ?></h3>

							<p class="category-description"><?php echo esc_html( $category->description ); ?></p>

							<div class="rss-builder-form">
								<div class="select-category">
									<label>
										<input type="checkbox" class="category-checkbox"
										       data-category="<?php echo $category_id; ?>">
										<?php _e( 'Subscribe to all sites in this category (including any new sites)', 'network-summary' ); ?>
									</label>
								</div>
								<div class="sites">
									<?php if ( count( $sites ) > 1 ) : ?>
										<div>
											<a class="select-all">
												<?php _e( 'Select All', 'network-summary' ); ?>
											</a>
											<a class="deselect-all"">
											<?php _e( 'Deselect All', 'network-summary' ); ?>
											</a>
										</div>
									<?php endif; ?>
									<?php
									foreach ( $sites as $site_id ) : ?>
										<label class="site">
											<input type="checkbox" class="site-checkbox"
											       data-site="<?php echo $site_id; ?>"
												/>
											<?php echo get_blog_option( $site_id, 'blogname' ); ?>
											<a href="<?php echo get_blog_option( $site_id, 'siteurl' ); ?>"
											   target="_blank"><?php _e( '[ Visit ]', 'network-summary', 'network-summary' ); ?></a>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					<?php endif;
				endforeach;

				$sites = $network_summary->get_sites_without_category( true );
				if ( ! empty( $sites ) ) : ?>
					<div id="no-category">
						<h3><?php _e( 'Other sites', 'network-summary' ); ?></h3>

						<div class="sites">
							<?php if ( count( $sites ) > 1 ) : ?>
								<div>
									<a class="select-all">
										<?php _e( 'Select All', 'network-summary' ); ?>
									</a>
									<a class="deselect-all">
										<?php _e( 'Deselect All', 'network-summary' ); ?>
									</a>
								</div>
							<?php endif; ?>
							<?php
							foreach ( $sites as $site_id ) : ?>
								<label class="site">
									<input type="checkbox" class="site-checkbox"
									       data-site="<?php echo $site_id; ?>"
										/>
									<?php echo get_blog_option( $site_id, 'blogname' ); ?>
									<a href="<?php echo get_blog_option( $site_id, 'siteurl' ); ?>"
									   target="_blank"><?php _e( '[ Visit ]', 'network-summary', 'network-summary' ); ?></a>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<div id="custom-feed-result">
				<p class="rss-message error"><?php _e( 'Sorry, but the entered rss url is not valid.', 'network-summary' ); ?></p>

				<p class="rss-message valid"><?php _e( 'The entered rss url was successfully validated!', 'network-summary' ); ?></p>

				<label>
					<?php _e( 'Use this url in your rss feed reader:', 'network-summary' ); ?>
					<input type="text" id="custom-feed-input" onclick="jQuery(this).select();"
					       data-base-url="<?php echo get_feed_link( 'rss2-network' ); ?>"
					       value="<?php echo get_feed_link( 'rss2-network' ); ?>"/>
				</label>
				<input id="custom-feed-reset" type="button" value="Reset" class="pure-button"/>
			</div>
		</div>
	</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>