<?php
/**
 * The Template for displaying all attachment posts.
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

					<?php if ( wp_attachment_is_image( get_the_ID() ) ) : ?>

						<p class="attachment attachment-image">
							<?php echo wp_get_attachment_image( get_the_ID(), 'full', false, array( 'class' => 'aligncenter' ) ); ?>
						</p><!-- .attachment .attachment-image -->

					<?php else : ?>

						<p class="download">
							<a href="<?php echo wp_get_attachment_url(); ?>" rel="enclosure"><?php printf( __( 'Download &quot;%1$s&quot;', 'nifty' ), the_title( '<span class="fn">', '</span>', false ) ); ?></a>
						</p><!-- .download -->

					<?php endif; ?>

					<?php the_content(); ?>

					<?php if ( wp_attachment_is_image( get_the_ID() ) ) echo do_shortcode( sprintf( '[gallery id="%1$s" exclude="%2$s" columns="3" order="RAND"]', $post->post_parent, get_the_ID() ) ); ?>

				</div><!-- .entry-content -->

				<?php do_action( 'nifty_close_entry' ); ?>

			</div><!-- .hentry -->

			<?php do_action( 'nifty_after_entry' ); ?>

			<?php do_action( 'nifty_after_singular' ); ?>

			<?php comments_template( '', true ); ?>

			<?php endwhile; // End the loop. Whew. ?>

		</div> <!-- .hfeed -->

		<?php do_action( 'nifty_close_content' ); ?>

	</div><!-- #content -->

	<?php do_action( 'nifty_after_content' ); ?>

<?php get_footer(); ?>
