<?php
/**
 * Template Name: Authors
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

					<?php foreach( get_users() as $author ) : ?>

						<?php $user = new WP_User( $author->ID ); ?>

						<?php if ( $user->has_cap( 'publish_posts' ) || $user->has_cap( 'edit_posts' ) || $user->has_cap( 'publish_pages' ) || $user->has_cap( 'edit_pages' ) ) : ?>

							<div id="hcard-<?php echo str_replace( ' ', '-', get_the_author_meta( 'user_nicename', $author->ID ) ); ?>" class="author-profile vcard clear">

								<a href="<?php echo get_author_posts_url( $author->ID ); ?>" title="<?php the_author_meta( 'display_name', $author->ID ); ?>">
								<?php echo get_avatar( get_the_author_meta( 'user_email', $author->ID ), '100', '', get_the_author_meta( 'display_name', $author->ID ) ); ?>
								</a>

								<h2 class="author-name fn n">
								<a href="<?php echo get_author_posts_url( $author->ID ); ?>" title="<?php the_author_meta( 'display_name', $author->ID ); ?>"><?php the_author_meta( 'display_name', $author->ID ); ?></a>
								</h2>

								<div class="author-bio">
								<?php the_author_meta( 'description', $author->ID ); ?>
								</div><!-- .author-bio -->

							</div><!-- .author-profile .vcard -->

						<?php endif; ?>

					<?php endforeach; ?>

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
