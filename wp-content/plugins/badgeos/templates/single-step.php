<?php
/**
 * The Template for displaying all single posts.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
get_header(); ?>

	<div id="primary" class="site-content">
		<div id="content" role="main">

			<?php while ( have_posts() ) : the_post(); ?>
				<?php if ( badgestack_user_has_access_to_achievement() ) : ?>
					<?php get_template_part( 'content', 'badge' ); ?>

					<!-- TODO: FIGURE OUT PAGINATION BETWEEN BADGES / STEPS -->
					<nav class="nav-single">
						<h3 class="assistive-text"><?php // _e( 'Post navigation', 'twentytwelve' ); ?></h3>
						<span class="nav-previous"><?php // previous_post_link( '%link', __( '<span class="meta-nav">&larr;</span>', 'twentytwelve' ) . ' %title' ); ?></span>
						<span class="nav-next"><?php // next_post_link( '%link', '%title ' . __( '<span class="meta-nav">&rarr;</span>', 'twentytwelve' ) ); ?></span>
					</nav><!-- .nav-single -->
					<?php 
					//load badge unlock options
					$badgestack_unlock_options = get_post_meta( absint( $post->ID ), '_badgestack_step_unlock_options', true );
					//check if step unlock option is set to submission review
					get_currentuserinfo();
					if ( badgestack_save_submission_data() ) { ?>
						you got it
					<?php }
					//check if user already has a submission for this achievement type
					if ( ! badgestack_check_if_user_has_submission( $current_user->ID, $post->ID ) ) {
						// Step Description metadata
						if ( $step_description = get_post_meta( $post->ID, '_badgestack_step_description', true ) ) { ?>
							<p><strong>Step Description</strong><br />
							<?php echo $step_description; ?>
							</p> 
						<?php } ?>
						
						
						<form method="post">
							<p><?php __( 'Submission form', 'badgestack' ); ?></p>
							<?php wp_nonce_field( 'badgestack_submission_form' ); ?>
							<textarea name="badgestack_submission_content"></textarea>
							<?php do_action('submission_form', $post->ID ); ?>
							<input type="submit" name="badgestack_submission_submit" value="<?php _e( 'Submit', 'badgestack' ); ?>" />
						</form>
						<?php //load submission form
					} else {
						//user has an active submission, so show content and comments
						
						$args = array(
							'post_type'		=>	'submission',
							'author'		=>	$current_user->ID,
							'post_status'	=>	'publish',
							'meta_key'		=>	'_badgestack_submission_achievement_id',
							'meta_value'	=>	absint( $post->ID ),
						);

						$submissions = get_posts( $args );
						
						foreach( $submissions as $post ) :	setup_postdata( $post ); ?>
							<p>
								<strong><?php _e( 'Original Submission', 'badgestack' ); ?>:</strong><br />
								<?php the_content(); ?><br />
								<strong><?php _e( 'Date', 'badgestack' ); ?>:</strong>&nbsp;&nbsp;
								<?php the_date(); ?><br />
								<strong><?php _e( 'Status', 'badgestack' ); ?>:</strong>&nbsp;&nbsp;
								<?php echo get_post_meta( get_the_ID(), '_badgestack_submission_status', true ); ?>
							</p>
							<p>
								<strong>Submission Comments</strong>
							<?php badgestack_save_comment_data(); // save submitted comment ?>
							<?php badgestack_get_comments_for_submission( $post->ID ); ?>
							<?php echo badgestack_get_comment_form( $post->ID ); ?>
							</p>
						<?php endforeach; ?>
					<?php } ?>
				<?php else : ?>
				you need another achievement first doof.
				<?php endif; ?>

			<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
