<?php
/**
 * BadgeStack Content Filters
 *
 * @package BadgeStack
 */

// add_filter( 'the_content', 'badgestack_steps_single' );

// Step single page filter
function badgestack_steps_single( $content ) {
	global $post, $current_user;
	
	if ( get_post_type( $post ) == 'step' && is_single() ) {
		//load badge unlock options
		$badgestack_unlock_options = get_post_meta( absint( $post->ID ), '_badgestack_step_unlock_options', true );
		
		//check if step unlock option is set to submission review
		if ( $badgestack_unlock_options == 'submission-review' ) {
			get_currentuserinfo();
			
			//check if user already has a submission for this achievement type
			if ( !badgestack_check_if_user_has_submission( $current_user->ID, $post->ID ) ) {
				//load step metadata for single step pages
				$badgestack_step_description = get_post_meta( $post->ID, '_badgestack_step_description', true );
				$badgestack_completing_step_means  = get_post_meta( $post->ID, '_badgestack_completing_step_means', true );
				$badgestack_submission_instructions = get_post_meta( $post->ID, '_badgestack_submission_instructions', true );
				$badgestack_discuss_after = get_post_meta( $post->ID, '_badgestack_discuss_after', true );
				$badgestack_discussforum_prompt     = get_post_meta( $post->ID, '_badgestack_discussforum_prompt', true );
				$badgestack_learn_even_more = get_post_meta( $post->ID, '_badgestack_learn_even_more', true );

				$badgestack_step_color = get_post_meta( $post->ID, '_badgestack_step_color', true );
				$badgestack_step_color = ( $badgestack_step_color ) ? $badgestack_step_color : '#';

				$new_content = null;

				// Step Description metadata
				if ( $badgestack_step_description ) {
					$new_content .= '<p><strong>Step Description</strong><br />';
					$new_content .= $badgestack_step_description;
					$new_content .= '</p>';
				}

				//load submission form
				$submission_form = badgestack_get_submission_form( $post->ID );
				$new_content    .= $new_content .$submission_form;

				$content = $content . $new_content;
			} else {
				//user has an active submission, so show content and comments
				
				$args = array(
					'post_type'			=>	'submission',
					'author'			=>	$current_user->ID,
					'post_status'	=>	'publish',
					'meta_key'		=>	'_badgestack_submission_achievement_id',
					'meta_value'	=>	absint( $post->ID ),
				);

				$submissions = get_posts( $args );
				
				foreach( $submissions as $post ) :	setup_postdata( $post );
					
					echo '<p>';
					
					echo '<strong>' .__( 'Original Submission', 'badgestack' ). ':</strong><br />';
					echo get_the_content() .'<br />';
					
					echo '<strong>' .__( 'Date', 'badgestack' ). ':</strong>&nbsp;&nbsp;';
					echo get_the_date() .'<br />';
					
					echo '<strong>' .__( 'Status', 'badgestack' ). ':</strong>&nbsp;&nbsp;';
					echo get_post_meta( get_the_ID(), '_badgestack_submission_status', true );
					
					echo '</p>';
					
					echo '<p>';
					echo '<strong>Submission Comments</strong>';
					
					//display any comments that exist
					badgestack_get_comments( $post->ID );
					
					//display a form to add new comments
					echo badgestack_get_comment_form( $post->ID );
					
					echo '</p>';
					
				endforeach;
			}
		}
	} elseif ( get_post_type( $post ) == 'badge' && is_single() ) {
		$new_content = null;
		
		//load badge unlock options
		$badgestack_unlock_options = get_post_meta( absint( $post->ID ), '_badgestack_badge_unlock_options', true );
		
		//check if badge unlock option is set to reward/nomination
		if ( $badgestack_unlock_options == 'giving' ) {
			//load nomination form
			$submission_form = badgestack_get_submission_form( $post->ID, 'nomination' );
			$new_content .= $new_content . $submission_form;
			$content     = $content . $new_content;
		}
	}
	
	return $content;
	
}
