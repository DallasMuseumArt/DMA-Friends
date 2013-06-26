<?php
/**
 * Index Template
 * 
 * @package Nifty
 * @subpackage Template
 */

get_header(); ?>

	<?php do_action( 'nifty_before_content' ); ?>

	<div id="content">

		<?php do_action( 'nifty_open_content' ); ?>

		<div class="hfeed">

		<?php if ( have_posts() ) : ?>

			<?php do_action( 'nifty_before_loop' ); ?>

			<?php while ( have_posts() ) : the_post(); ?>

			<?php do_action( 'nifty_before_entry' ); ?>

			<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<?php get_template_part( 'loop-thumbnail' ); // Loads loop-thumbnail.php template ?>

				<?php do_action( 'nifty_open_entry' ); ?>

				<div class="entry-content">

					<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'nifty' ) ); ?>

				</div><!-- .entry-content -->

				<?php do_action( 'nifty_close_entry' ); ?>

			</div><!-- .hentry -->

			<?php do_action( 'nifty_after_entry' ); ?>

			<?php endwhile; ?>

			<?php do_action( 'nifty_after_loop' ); ?>

		<?php else : ?>

			<?php get_template_part( 'loop', 'error' ); // Loads the loop-error.php template ?>

		<?php endif; ?>

		</div> <!-- .hfeed -->

		<?php do_action( 'nifty_close_content' ); ?>

	</div><!-- #content -->

	<?php do_action( 'nifty_after_content' ); ?>

<?php get_footer(); ?>
