<?php
/**
 * Template Name: Thumbnail Archive
 *
 * Lists your post archives in thumbnail format.
 * It also integrates with the Get The Image plugin if activated.
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

					<div class="thumbnail-archive">

						<?php query_posts( array( 'showpost' => 30 ) ); ?>

						<?php while( have_posts() ) : the_post();

							if ( has_post_thumbnail( null ) ) :

								/* Get the Thumbnail. */
								$thumbnail = get_the_post_thumbnail( null, 'thumbnail', array( 'class' => 'thumbnail', 'title' => '' ) );

								/* Link the image to this post. */
								$thumbnail = '<a href="' . get_permalink( null ) . '" title="' . strip_tags( get_the_title( null ) ) . '">' . $thumbnail . '</a>';

								/* Displays the Thumbnail. */
								echo $thumbnail;

							endif; ?>

						<?php endwhile; ?>

						<?php wp_reset_query(); ?>

					</div><!-- .thumbnail-archive -->

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
