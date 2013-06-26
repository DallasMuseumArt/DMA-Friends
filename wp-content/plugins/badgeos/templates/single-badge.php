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

				<?php get_template_part( 'content', 'badge' ); ?>

				<!-- TODO: FIGURE OUT PAGINATION BETWEEN BADGES / STEPS -->
				<nav class="nav-single">
					<h3 class="assistive-text"><?php // _e( 'Post navigation', 'twentytwelve' ); ?></h3>
					<span class="nav-previous"><?php // previous_post_link( '%link', __( '<span class="meta-nav">&larr;</span>', 'twentytwelve' ) . ' %title' ); ?></span>
					<span class="nav-next"><?php // next_post_link( '%link', '%title ' . __( '<span class="meta-nav">&rarr;</span>', 'twentytwelve' ) ); ?></span>
				</nav><!-- .nav-single -->

				<?php if ( badgestack_method_of_earning_achievement() == 'giving' ) { ?>
				<?php if ( badgestack_save_nomination_data() ) { ?> 
					Your nomination was submitted.
				<?php } ?>
				<?php $type = 'nomination';?>

				<form method="post">
					<p><?php _e( 'Nomination form', 'badgestack' ); ?></p>
					<?php wp_nonce_field( 'badgestack_nomination_form' ); ?>
					<?php var_dump(badgestack_method_of_earning_achievement()); ?>
						//load all user in WP 
						$args = array( 'orderby' => 'display_name' );
						$wp_users = new WP_User_Query( $args );
						$wp_all_users = $wp_users->get_results(); ?>
						<?php _e( 'User to nominate', 'badgestack' ); ?>: <select name="badgestack_nomination_user_id">
						
						<?php foreach ( $wp_all_users as $wp_user ) { ?>
							<option value="<?php echo $wp_user->ID; ?>"><?php echo $wp_user->display_name; ?></option>
						<?php } ?>
						</select> <br />
					
					<textarea name="badgestack_nomination_content"></textarea><br />
					<input type="submit" name="badgestack_nomination_submit" value="<?php _e( 'Submit', 'badgestack' ); ?>" />
				</form>
				<?php
					// If comments are open or we have at least one comment, load up the comment template
					if ( comments_open() || '0' != get_comments_number() )
						comments_template( '', true );
				?>
				<?php } // badgestack_get_unlock_options() == 'giving' ?>
				<?php if ( 'earning_achievements' == badgestack_method_of_earning_achievement() ) { ?>
					<h3>Required Achievements</h3>
					<?php $required_achievements = badgestack_get_required_achievements_for_achievement(); ?>
					<?php foreach ( $required_achievements as $required_achievement ) { ?>
						<a href="<?php echo get_permalink( $required_achievement->ID ); ?>"><?php echo $required_achievement->post_title?></a><br />
					<?php } ?>
				<?php } ?>
			<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
