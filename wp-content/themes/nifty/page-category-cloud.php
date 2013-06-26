<?php
/**
 * Template Name: Category Cloud
 *
 * Shows a category cloud bellow the content.
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

					<p class="post_tag-cloud tag-cloud">
						<?php wp_tag_cloud( array( 'taxonomy' => 'category', 'number' => 0 ) ); ?>
					</p><!-- .post_tag-cloud .tag-cloud -->

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
