<?php
/**
 * Template Name: Biography
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

					<?php if ( $page != $wp_query->get( 'page' ) ) : ?>
	
						<div id="hcard-<?php the_author_meta( 'user_nicename' ); ?>" class="author-profile vcard">

							<div class="author-bio">

								<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>" title="<?php the_author_meta( 'display_name' ); ?>">
								<?php echo get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'author_about_avatar_size', 80 ), '', get_the_author_meta( 'display_name' ) ); ?>
								</a>

								<?php if ( get_the_author_meta( 'description' ) ) : ?>
									<?php the_author_meta( 'description' ); ?>
								<?php endif; ?>

							</div><!-- .author-bio -->

							<ul class="xoxo clear">

								<?php if ( get_the_author_meta( 'nickname' ) ) : ?>
									<li><strong><?php _e( 'Nickname:', 'nifty' ); ?></strong> <span class="nickname"><?php the_author_meta( 'nickname' ); ?></span></li>
								<?php endif; ?>

								<?php if ( get_the_author_meta( 'user_url' ) ) : ?>
									<li><strong><?php _e( 'Website:', 'nifty' ); ?></strong> <a class="url" href="<?php the_author_meta( 'user_url' ); ?>" title="<?php the_author_meta( 'user_url' ); ?>"><?php the_author_meta( 'user_url' ); ?></a></li>
								<?php endif; ?>

								<?php if ( get_the_author_meta( 'aim' ) ) : ?>
									<li><strong><?php _e( 'AIM:', 'nifty' ); ?></strong> <a class="url" href="aim:goim?screenname=<?php the_author_meta( 'aim' ); ?>" title="<?php printf( __( 'IM with %1$s', 'nifty' ), get_the_author_meta( 'aim' ) ); ?>"><?php the_author_meta( 'aim' ); ?></a></li>
								<?php endif; ?>

								<?php if ( get_the_author_meta( 'jabber' ) ) : ?>
									<li><strong><?php _e( 'Jabber:', 'nifty' ); ?></strong> <a class="url" href="xmpp:<?php the_author_meta( 'jabber' ); ?>@jabberservice.com" title="<?php printf( __( 'IM with %1$s', 'nifty' ), get_the_author_meta( 'jabber' ) ); ?>"><?php the_author_meta( 'jabber' ); ?></a></li>
								<?php endif; ?>

								<?php if ( get_the_author_meta( 'yim' ) ) : ?>
									<li><strong><?php _e( 'Yahoo:', 'nifty' ); ?></strong> <a class="url" href="ymsgr:sendIM?<?php the_author_meta( 'yim' ); ?>" title="<?php printf( __( 'IM with %1$s', 'nifty' ), get_the_author_meta( 'yim' ) ); ?>"><?php the_author_meta( 'yim' ); ?></a></li>
								<?php endif; ?>

							</ul><!-- .xoxo -->

						</div><!-- .author-profile .vcard -->
				
					<?php endif; ?>

					<?php the_content(); ?>

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
