<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed for all visible sites in the network.
 *
 */

global $network_summary;

if ( isset( $_GET['sites'] ) ) {
	$sites = $_GET['sites'];
} elseif ( isset( $_GET['category'] ) ) {
	if ( is_array( $_GET['category'] ) ) {
		$sites = array();
		foreach ( $_GET['category'] as $category ) {
			$sites = array_merge( $sites, $network_summary->get_sites_per_category( $category ) );
		}
	} else {
		$sites = $network_summary->get_sites_per_category( $_GET['category'] );
	}
} else {
	$sites = $network_summary->get_sites();
}

header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );
$more = 1;

if ( ! function_exists( 'siteinfo_rss' ) ) {
	function siteinfo_rss( $show = '' ) {
		echo convert_chars( strip_tags( get_site_option( $show ) ) );
	}
}

echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>'; ?>

<rss version="2.0"
	 xmlns:content="http://purl.org/rss/1.0/modules/content/"
	 xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	 xmlns:dc="http://purl.org/dc/elements/1.1/"
	 xmlns:atom="http://www.w3.org/2005/Atom"
	 xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	 xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php
	/**
	 * Fires at the end of the RSS root to add namespaces.
	 *
	 * @since 2.0.0
	 */
	do_action( 'rss2_ns' );
	?>
	>

	<channel>
		<title><?php siteinfo_rss( 'site_name' ); ?></title>
		<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml"/>
		<link><?php siteinfo_rss( 'siteurl' ); ?></link>
		<lastBuildDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ); ?></lastBuildDate>
		<language><?php bloginfo_rss( 'language' ); ?></language>
		<?php
		/**
		 * Filter how often to update the RSS feed.
		 *
		 * @since 2.1.0
		 *
		 * @param string $duration The update period.
		 *                         Default 'hourly'. Accepts 'hourly', 'daily', 'weekly', 'monthly', 'yearly'.
		 */
		?>
		<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
		<?php
		/**
		 * Filter the RSS update frequency.
		 *
		 * @since 2.1.0
		 *
		 * @param string $frequency An integer passed as a string representing the frequency
		 *                          of RSS updates within the update period. Default '1'.
		 */
		?>
		<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
		<description><?php _e( sprintf( 'Aggregated RSS Feed for %s.', get_site_option( 'site_name' ) ), 'network-summary' ); ?></description>
		<?php
		/**
		 * Fires at the end of the RSS2 Feed Header.
		 *
		 * @since 2.0.0
		 */
		do_action( 'rss2_head' );

		/*
		 * Fetches all the posts.
		 */
		$posts = array();
		foreach ( $sites as $site_id ) {
			if ( share_site( $site_id ) ) {
				switch_to_blog( $site_id );
				$query = new WP_Query();
				foreach ( $query->get_posts() as $post ) {
					$post->site_id = $site_id;
					array_push( $posts, $post );
				}
				restore_current_blog();
			}
		}

		/*
		 * Sort the posts.
		 */
		function sort_by_post_date( $a, $b ) {
			return strtotime( $b->post_date_gmt ) - strtotime( $a->post_date_gmt );
		}

		usort( $posts, 'sort_by_post_date' );

		$limit = get_site_option( Network_Summary::network_option );
		$limit = $limit['rss_limit'];
		$posts = array_slice( $posts, 0, $limit );

		/*
		 * Outputs all the posts.
		 */
		foreach ( $posts as $post ) :
			switch_to_blog( $post->site_id );
			?>
			<item>
				<title><?php the_title_rss() ?></title>
				<link><?php the_permalink_rss() ?></link>
				<comments><?php comments_link_feed(); ?></comments>
				<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>
				<dc:creator><![CDATA[<?php bloginfo( 'name' ); ?>]]></dc:creator>
				<?php the_category_rss( 'rss2' ) ?>

				<guid isPermaLink="false"><?php the_guid(); ?></guid>
				<?php if ( get_option( 'rss_use_excerpt' ) ) : ?>
					<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
				<?php else : ?>
					<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
					<?php $content = get_the_content_feed( 'rss2' ); ?>
					<?php if ( strlen( $content ) > 0 ) : ?>
						<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
					<?php else : ?>
						<content:encoded><![CDATA[<?php the_excerpt_rss(); ?>]]></content:encoded>
					<?php endif; ?>
				<?php endif; ?>
				<wfw:commentRss><?php echo esc_url( untrailingslashit( get_permalink() ) . get_post_comments_feed_link( null, 'rss2' ) ); ?></wfw:commentRss>
				<slash:comments><?php echo get_comments_number(); ?></slash:comments>
				<?php rss_enclosure(); ?>
				<?php
				/**
				 * Fires at the end of each RSS2 feed item.
				 *
				 * @since 2.0.0
				 */
				do_action( 'rss2_item' );
				?>
			</item>
			<?php
			restore_current_blog();
		endforeach;

		?>
	</channel>
</rss>
