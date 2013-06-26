<?php
/**
 * Template Name: Archives
 *
 * @package Nifty
 * @subpackage Template
 */

get_header(); ?>

	<?php do_action( 'nifty_before_content' ); ?>

	<div id="content">

		<?php do_action( 'nifty_open_content' ); ?>

		<div class="hfeed">

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

			<?php do_action( 'nifty_before_entry' ); ?>

			<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<?php do_action( 'nifty_open_entry' ); ?>

				<div class="entry-content">
					<?php the_content(); ?>

					<h2><?php _e( 'Blog Posts', 'nifty' ); ?></h2>
					<ul class="xoxo post-archives">
						<?php wp_get_archives( array( 'type' => 'postbypost' ) ); ?>
					</ul> <!-- .xoxo .post-archives -->

					<h2><?php _e( 'Categories', 'nifty' ); ?></h2>
					<ul class="xoxo category-archives">
						<?php wp_list_categories( array( 'show_count' => true, 'use_desc_for_title' => false, 'title_li' => '' ) ); ?>
					</ul> <!-- .xoxo .category-archives -->

					<h2><?php _e( 'Monthly Archives', 'nifty' ); ?></h2>
					<ul class="xoxo monthly-archives">
						<?php wp_get_archives( array( 'type' => 'monthly', 'show_post_count' => true ) ); ?>
					</ul> <!-- .xoxo .monthly-archives -->

				</div><!-- .entry-content -->

				<?php do_action( 'nifty_close_entry' ); ?>

			</div><!-- .hentry -->

			<?php do_action( 'nifty_after_entry' ); ?>

			<?php do_action( 'nifty_after_singular' ); ?>

<?php endwhile; // End the loop. Whew. ?>

		</div> <!-- .hfeed -->

		<?php do_action( 'nifty_close_content' ); ?>

	</div><!-- #content -->

	<?php do_action( 'nifty_after_content' ); ?>

<?php get_footer(); ?>
