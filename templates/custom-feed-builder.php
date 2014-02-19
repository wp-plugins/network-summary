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

			<p><?php _e( 'Build your own custom feed for this network of blogs by checking
			the sites you wish to be included in the feed.', 'network-summary' ); ?></p>

			<div id="custom-feed-form">
				<?php
				global $network_summary;
				$categories = $network_summary->get_all_categories();
				foreach ( $categories as $category_id => $category ) : ?>
					<div id="category-<?php echo $category_id; ?>" class="category">
						<input type="hidden" class="category-id" value="<?php echo $category_id; ?>"/>

						<h3><?php echo esc_html( $category->name ); ?></h3>

						<p><?php echo esc_html( $category->description ); ?></p>

						<div class="rss-builder-form">
							<div>
								<a class="select-all"><?php _e( 'Select All', 'network-summary' ); ?></a>
								<a class="deselect-all"><?php _e( 'Deselect All', 'network-summary' ); ?></a>
							</div>

							<?php
							$sites = $network_summary->get_sites_per_category( $category_id );

							foreach ( $sites as $site_id ) : ?>
								<label class="site-checkbox-label">
									<input type="hidden" class="site-id" value="<?php echo $site_id; ?>"/>
									<input type="checkbox" class="site-checkbox">
									<?php echo get_blog_option( $site_id, 'blogname' ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div id="custom-feed-result">
				<p><?php _e( 'Below you can find your custom feed url.', 'network-summary' ); ?></p>
				<textarea id="custom-feed-input" onclick="jQuery(this).select();"
						  readonly="readonly"><?php echo get_feed_link( 'rss2-network' ); ?></textarea>
			</div>
		</div>
	</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>