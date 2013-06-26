<?php
/**
 * Template Name: Archives by Category
 *
 * The categories template is a page template that lists your categories along with a link 
 * to the each category's RSS feed and post count.
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

					<?php $categories = get_categories();

					foreach( $categories as $cat ) {

						query_posts( array( 'cat' => $cat->term_id ) );

						if ( have_posts() ) : ?>

							<h2><?php echo $cat->name; ?></h2>
							<ul class="xoxo category-archives"><?php
								while ( have_posts() ) : the_post(); ?>
									<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> <span class="comments-number">(<?php comments_number( '0', '1', '%' ); ?>)</span></li> <?php
								endwhile;
								wp_reset_query(); ?>
							</ul><?php

						endif;
					} ?>

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
