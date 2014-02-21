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
				foreach ( $categories as $category_id => $category ) :
					$sites = $network_summary->get_sites_per_category( $category_id, true );
					if ( ! empty( $sites ) ) : ?>
						<div class="category">
							<h3><?php echo esc_html( $category->name ); ?></h3>

							<p><?php echo esc_html( $category->description ); ?></p>

							<div class="rss-builder-form">
								<div>
									<a class="select-all" data-category="<?php echo $category_id; ?>">
										<?php _e( 'Select All', 'network-summary' ); ?>
									</a>
									<a class="deselect-all" data-category="<?php echo $category_id; ?>">
										<?php _e( 'Deselect All', 'network-summary' ); ?>
									</a>
								</div>

								<?php
								foreach ( $sites as $site_id ) : ?>
									<label class="site-checkbox-label">
										<input type="checkbox" class="site-checkbox"
											   data-category="<?php echo $category_id; ?>"
											   data-site="<?php echo $site_id; ?>"
											/>
										<?php echo get_blog_option( $site_id, 'blogname' ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif;
				endforeach; ?>
			</div>
			<div id="custom-feed-result">
				<p><?php
					_e( 'Below you can find your custom feed url. Use this in your favorite feed reader.', 'network-summary' );
					echo '<br>';
					_e( 'Alternatively you can copy an existing url in the field to modify it.', 'network-summary' ); ?>
				</p>

				<p class="rss-message error"><?php _e('Sorry, but the entered rss url is not valid.', 'network-summary'); ?></p>
				<p class="rss-message valid"><?php _e('The entered rss url was successfully validated!', 'network-summary'); ?></p>

				<input type="text" id="custom-feed-input" onclick="jQuery(this).select();"
					   data-base-url="<?php echo get_feed_link( 'rss2-network' ); ?>"
					   value="<?php echo get_feed_link( 'rss2-network' ); ?>"/>
				<input id="custom-feed-reset" type="button" value="Reset" class="pure-button"/>
			</div>
		</div>
	</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>