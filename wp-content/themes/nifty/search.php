<?php
/**
 * Search Template
 *
 * @package Nifty
 * @subpackage Template
 */

get_header(); ?>

	<?php do_action( 'nifty_before_content' ); // Before Content hook ?>

	<div id="content">

		<?php do_action( 'nifty_open_content' ); // Open Content hook ?>

		<div class="hfeed">

		<?php if ( have_posts() ) : ?>

			<?php do_action( 'nifty_before_loop' ); ?>

			<?php while ( have_posts() ) : the_post(); ?>

			<?php do_action( 'nifty_before_entry' ); // Before entry content hook ?>

			<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<?php get_template_part( 'loop-thumbnail' ); // Loads loop-thumbnail.php template ?>

				<?php do_action( 'nifty_open_entry' ); // Open entry hook */ ?>

				<div class="entry-summary">

					<?php the_excerpt(); ?>

				</div><!-- .entry-summary -->

				<?php do_action( 'nifty_close_entry' ); // Close entry hook */ ?>

			</div><!-- .hentry -->

			<?php do_action( 'nifty_after_entry' ); // After entry content hook */ ?>

			<?php endwhile; ?>

			<?php do_action( 'nifty_after_loop' ); ?>

		<?php else : ?>

			<p><?php _e( 'Sorry, no posts matched your criteria. Maybe you\'d like to try inputting different search terms.', 'nifty' ); ?></p>

			<?php get_search_form(); ?>

		<?php endif; ?>

		</div> <!-- .hfeed -->

		<?php do_action( 'nifty_close_content' ); // Close Content hook ?>

	</div><!-- #content -->

	<?php do_action( 'nifty_after_content' ); // After Content hook ?>

<?php get_footer(); ?>