<?php
/**
 * BadgeStack Admin Meta Boxes
 *
 * @package BadgeStack
 */

add_action( 'admin_init', 'badgestack_register_meta_boxes' );

function badgestack_register_meta_boxes() {
	// Steps meta box attached to Badges
	add_meta_box( 'badgestack_badge_type_meta_box', __( 'Badge Options', 'badgestack' ), 'badgestack_badge_type_meta_box', 'badge', 'side', 'low' );

	// Steps metadata meta box attached to Steps
	add_meta_box( 'badgestack_step_details', __( 'Step Details', 'badgestack' ), 'badgestack_step_details_meta_box', 'step', 'normal', 'core' );
	
	//Triggers meta box attached to all achievement types
	add_meta_box( 'badgestack_triggers_meta_box' , __( 'Triggers', 'badgestack' ), 'badgestack_triggers_meta_box', 'step', 'normal', 'low' );
	
	//Submissions meta box to approve/deny submissions
	add_meta_box( 'badgestack_submissions_meta_box', __( 'Submissions Moderation', 'badgestack' ), 'badgestack_submission_meta_box', 'submission', 'side', 'low' );
	
	//Nominations meta box to approve/deny submissions
	add_meta_box( 'badgestack_nominations_meta_box', __( 'Nominations Moderation', 'badgestack' ), 'badgestack_submission_meta_box', 'nomination', 'side', 'low' );
	
	//Save meta box data
	add_action( 'save_post', 'badgestack_save_meta_box_data' );
}

function badgestack_badge_type_meta_box( $post ) {
	
	//load metadata setting values
	$badgestack_unlock_options = get_post_meta( absint( $post->ID ), '_badgestack_badge_unlock_options', true );
	$badgestack_sequential = get_post_meta( absint( $post->ID ), '_badgestack_badge_sequential', true );
	
	$badgestack_point_value = get_post_meta( absint( $post->ID ), '_badgestack_point_value', true );
	$badgestack_point_value = ( $badgestack_point_value ) ? $badgestack_point_value : 0;
	
	$badgestack_sequential = get_post_meta( absint( $post->ID ), '_badgestack_badge_sequential', true );
	$badgestack_sequential = ( $badgestack_sequential ) ? $badgestack_sequential : 'non-sequential';
	
	//nonce for security
	wp_nonce_field( 'badgestack_update_meta_data', 'badgestack_security_nonce' );

	?>
	<p>
		<?php _e( 'How do you want to earn this Badge?', 'badgestack' ); ?>
		<select name="badge_unlock_options">
			<option value="earning_achievements" <?php selected( $badgestack_unlock_options, 'earning_achievements' ); ?>><?php _e( 'Completing Achievements', 'badgestack' ); ?></option>
			<option value="giving" <?php selected( $badgestack_unlock_options, 'giving' ); ?>><?php _e( 'Reward by Giving / Nomination', 'badgestack' ); ?></option>
		</select>
	</p>
	<p>
		<?php _e( 'Require Sequential?', 'badgestack' ); ?>
		<select name="badge_sequential_option">
			<option value="non-sequential" <?php selected( $badgestack_sequential, 'non-sequential' ); ?>><?php _e( 'Non-Sequential', 'badgestack' ); ?></option>
			<option value="sequential" <?php selected( $badgestack_sequential, 'sequential' ); ?>><?php _e( 'Sequential', 'badgestack' ); ?></option>
		</select>
	</p>
	<p>
		<strong><?php _e( 'Point Value', 'badgestack' ); ?>: </strong>
		<input type="text" name="badgestack_point_value" value="<?php echo absint( $badgestack_point_value ); ?>" />
	</p>
	<?php
}

//Meta box for Steps metadata fields
function badgestack_step_details_meta_box( $post ){
	
	$badgestack_step_description = get_post_meta( $post->ID, '_badgestack_step_description', true );
	$badgestack_completing_step_means = get_post_meta( $post->ID, '_badgestack_completing_step_means', true );
	$badgestack_submission_instructions = get_post_meta( $post->ID, '_badgestack_submission_instructions', true );
	$badgestack_discuss_after = get_post_meta( $post->ID, '_badgestack_discuss_after', true );
	$badgestack_discussforum_prompt = get_post_meta( $post->ID, '_badgestack_discussforum_prompt', true );
	$badgestack_learn_even_more = get_post_meta( $post->ID, '_badgestack_learn_even_more', true );
	$badgestack_unlock_options = get_post_meta( $post->ID, '_badgestack_step_unlock_options', true );

	$badgestack_step_color = get_post_meta( $post->ID, '_badgestack_step_color', true );
	$badgestack_step_color = ( $badgestack_step_color ) ? $badgestack_step_color : '#';
	
	//nonce for security
	wp_nonce_field( 'badgestack_update_meta_data', 'badgestack_security_nonce' );
	?>
		
    <style>
	.mf_textarea {
		height: 150px;
		margin-left: 1px;
		width: 95%;
	}
	.mf_caption {
		clear: both;
		color: #999999;
		margin-left: 0 !important;
	}
	</style>
	
	<?php echo '<p>' .__( 'Related Badges', 'badgestack' ) .'</p>'; ?>
	<p>
		<?php _e( 'How do you want to earn this Step?', 'badgestack' ); ?>
		<select name="step_unlock_options">
			<option value="submission-review" <?php selected( $badgestack_unlock_options, 'submission-review' ); ?>><?php _e( 'Submission Review', 'badgestack' ); ?></option>
			<option value="submission-auto" <?php selected( $badgestack_unlock_options, 'submission-auto' ); ?>><?php _e( 'Submission Auto Acceptance', 'badgestack' ); ?></option>
		</select>
	</p>
	<?php
}

//Meta box for Levels metadata fields
function badgestack_level_details_meta_box( $post ) {
	
	$badgestack_level_points = get_post_meta( $post->ID, '_badgestack_level_points', true );
	$badgestack_level_points = ( $badgestack_level_points ) ? $badgestack_level_points : 0;
	$badgestack_required_achievements = get_post_meta( $post->ID, '_badgestack_required_achievements', true );
	$badgestack_level_color = get_post_meta( $post->ID, '_badgestack_level_color', true );
	$badgestack_level_color = ( $badgestack_level_color ) ? $badgestack_level_color : '#';
	
	//nonce for security
	wp_nonce_field( 'badgestack_update_meta_data', 'badgestack_security_nonce' );
	?>
		
    <style>
	.mf_textarea {
		height: 150px;
		margin-left: 1px;
		width: 95%;
	}
	.mf_caption {
		clear: both;
		color: #999999;
		margin-left: 0 !important;
	}
	</style>
	<p>
		<strong><?php _e( 'Level Color', 'badgestack' ); ?>: </strong>
		<input class="color-picker" type="text" size="36" name="badgestack_level_color" value="<?php echo esc_attr( $badgestack_level_color ); ?>" />
	</p>
	<p>
		<strong><?php _e( 'Points Required', 'badgestack' ); ?>: </strong>
		<input class="color-picker" type="text" size="36" name="badgestack_level_points" value="<?php echo absint( $badgestack_level_points ); ?>" />
	</p>
	<?php
		
}

function badgestack_submission_meta_box( $post ) {
	
	echo '<p>' .__( 'Approve or Deny the Submission', 'badgestack' ). '</p>';

	$current_status = get_post_meta( $post->ID, '_badgestack_submission_status', true );
	$current_status = ( $current_status != '' ) ? $current_status : 'none';
	
	echo '<p>' .__( 'Current Status', 'badgestack' ) .': ' .$current_status .'</p>';

	//check if submission user id exists, if so show the user
	$submission_user_id = get_post_meta( $post->ID, '_badgestack_submission_user_id', true );
	
	if ( $submission_user_id ) {
		
		$user_data = get_userdata( $submission_user_id ); 
		
		echo '<p>' .__( 'User Nominated', 'badgestack' ) .': '.$user_data->display_name.'</p>';
	}
	
	$user_id = ( get_post_type( $post ) == 'submission' ) ? $post->post_author : get_post_meta( $post->ID, '_badgestack_submission_user_id', true );

	echo '<a class="button-secondary" href="'.wp_nonce_url( add_query_arg( array( 'badgestack_status' => 'approve', 'post_id' => absint( $post->ID ), 'user_id' => absint( $user_id ) ) ), 'badgestack_status_action' ).'">'.__( 'Approve', 'badgestack' ).'</a>&nbsp;&nbsp;';
	echo '<a class="button-secondary" href="'.wp_nonce_url( add_query_arg( array( 'badgestack_status' => 'deny', 'post_id' => absint( $post->ID ), 'user_id' => absint( $user_id ) ) ), 'badgestack_status_action' ).'">'.__( 'Deny', 'badgestack' ).'</a>';
	
}

/**
 * Save submitted meta box data
 *
 *
 */
function badgestack_save_meta_box_data( $post_id ) {
	global $badgestack;

	if ( get_post_type( $post_id ) == 'badge' && isset( $_POST['badge_unlock_options'] ) ) {
		//Process Badges CPT meta data and the Steps meta box
		
		//if autosave, skip
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		//check nonce for security
		check_admin_referer( 'badgestack_update_meta_data', 'badgestack_security_nonce' );

		//update badge step array
		if ( isset( $_POST['steplist'] ) && ( $badge_steplist = $_POST['steplist'] ) ) {
			$checkbox = array_map( 'absint', $badge_steplist );
			update_post_meta( $post_id, '_badgestack_steps', $checkbox );
		}

		//update badge options
		if ( isset( $_POST['badge_unlock_options'] ) && ( $badge_unlock_options = $_POST['badge_unlock_options'] ) ) {
			update_post_meta( $post_id, '_badgestack_badge_unlock_options', strip_tags( $badge_unlock_options ) );
		}
		
		if ( isset( $_POST['badge_sequential_option'] ) && ( $badge_sequential_option = $_POST['badge_sequential_option'] ) ) {
			update_post_meta( $post_id, '_badgestack_badge_sequential', strip_tags( $badge_sequential_option ) );
		}
		
		if ( isset( $_POST['badgestack_point_value'] ) && ( $badgestack_point_value = $_POST['badgestack_point_value'] ) ) {
			update_post_meta( $post_id, '_badgestack_point_value', absint( $badgestack_point_value ) );
		}
		
		if ( isset( $_POST['badge_required_achievements'] ) && ( $badge_required_achievements = $_POST['badge_required_achievements'] ) ) {
			update_post_meta( $post_id, '_badgestack_badge_required_achievements', strip_tags( $badge_required_achievements ) );
		}
		
		//update badge step array
		if ( isset( $_POST['required_achievements'] ) && ( $required_achievements = $_POST['required_achievements'] ) ) {
			$checkbox = array_map( 'absint', $required_achievements );
			update_post_meta( $post_id, '_badgestack_required_achievements', $checkbox );
		}
	} elseif ( get_post_type( $post_id ) == 'step' && ( isset( $_POST['step_unlock_options'] ) ) ) {
		//Process Steps CPT meta data from the Badges meta box

		//if autosave, skip
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		//check nonce for security
		check_admin_referer( 'badgestack_update_meta_data', 'badgestack_security_nonce' );

		//update badge step array
		if ( isset( $_POST['badgelist'] ) ) {
			$checkbox = array_map( 'absint', $_POST['badgelist'] );
			update_post_meta( $post_id, '_badgestack_badges', $checkbox );
		}

		//allowed HTML tags for the editor
		$allowedtags = array(
			'a' => array(
				'href' => array(),
				'title' => array()
				),
			'abbr' => array(
				'title' => array()
				),
			'acronym' => array(
				'title' => array()
				),
			'code' => array(),
			'em' => array(),
			'strong' => array(),
			'img'	=> array(
				'src' => array(),
				'alt' => array(),
				'title' => array(),
				'width' => array(),
				'height' => array(),
				'class' => array()
			)
		);

		if ( isset( $_POST['step_unlock_options'] ) && ( $badgestack_step_unlock_options = $_POST['step_unlock_options'] ) ) {
			update_post_meta( $post_id, '_badgestack_step_unlock_options', strip_tags( $badgestack_step_unlock_options ) );
		}
		
		if ( isset( $_POST['badgestack_step_color'] ) && ( $badgestack_step_color = $_POST['badgestack_step_color'] ) ) {
			update_post_meta( $post_id, '_badgestack_step_color', strip_tags( $badgestack_step_color ) );
		}
		
		if ( isset( $_POST['badgestack_step_description'] ) && ( $badgestack_step_description = $_POST['badgestack_step_description'] ) ) {
			update_post_meta( $post_id, '_badgestack_step_description', wp_kses( $badgestack_step_description, $allowedtags ) );
		}
		
		if ( isset( $_POST['badgestack_completing_step_means'] ) && ( $badgestack_completing_step_means = $_POST['badgestack_completing_step_means'] ) ) {
			update_post_meta( $post_id, '_badgestack_completing_step_means', wp_kses( $badgestack_completing_step_means, $allowedtags ) );
		}

		if ( isset( $_POST['badgestack_submission_instructions'] ) && ( $badgestack_submission_instructions = $_POST['badgestack_submission_instructions'] ) ){
			update_post_meta( $post_id, '_badgestack_submission_instructions', wp_kses( $badgestack_submission_instructions, $allowedtags ) );
		}
		
		if ( isset( $_POST['badgestack_discuss_after'] ) && ( $badgestack_discuss_after = $_POST['badgestack_discuss_after'] ) ) {
			update_post_meta( $post_id, '_badgestack_discuss_after', wp_kses( $badgestack_discuss_after, $allowedtags ) );
		}
		
		if ( isset( $_POST['badgestack_discussforum_prompt'] ) && ( $badgestack_discussforum_prompt = $_POST['badgestack_discussforum_prompt'] ) ) {
			update_post_meta( $post_id, '_badgestack_discussforum_prompt', wp_kses( $badgestack_discussforum_prompt, $allowedtags ) );
		}
	}elseif ( get_post_type( $post_id ) == 'level' ) {
		//Process Levels CPT metadata

		//if autosave, skip
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		return;
		
		if ( isset( $_POST['badgestack_level_color'] ) && ( $badgestack_level_color = $_POST['badgestack_level_color'] ) ) {
			update_post_meta( $post_id, '_badgestack_level_color', $badgestack_level_color );
		}
		
		if ( isset( $_POST['badgestack_level_points'] ) && ( $badgestack_level_points = $_POST['badgestack_level_points'] ) ) {
			update_post_meta( $post_id, '_badgestack_level_points', $badgestack_level_points );
		}
		
		if ( isset( $_POST['required_achievements'] ) && ( $badgestack_required_achievements = $_POST['required_achievements'] ) ) {
			$badgestack_required_achievements = array_map( 'absint', $badgestack_required_achievements );
			update_post_meta( $post_id, '_badgestack_required_achievements', $badgestack_required_achievements );
		}
	}

	//get exisitng steps for this badge and wipe out badge id
	$steps = get_post_meta( $post_id, '_badgestack_steps', true );
	if ( is_array( $steps ) && 1 == 2 ) { 
		//disable for now
		foreach ( $steps as $key => $value ) {
			$step_id = $value;
			$badges = get_post_meta( $step_id, '_wds_lt_badges', true );
			if ( is_array( $badges ) ) {
				$key = array_search( $post_ID, $badges );
				unset( $badges[$key] );
			}
			update_post_meta( $step_id, '_wds_lt_badges', $badges );
		}
	}


	//update step badges array
	if ( 1 == 2 ) { 
	//disable for now
		foreach ( $checkbox as $key => $value ) {
			$step_id = $value;
			$badges = get_post_meta( $step_id, '_wds_lt_badges', true );
			if ( ! is_array( $badges ) ) {
				$badges = array( $post_ID );
			} else {
				array_push( $badges, $post_ID );
			}
			update_post_meta( $step_id, '_wds_lt_badges', $badges );
		}
	}
	
	// Save Triggers Meta

	foreach ( $badgestack->activity_triggers as $trigger ) {
		if ( isset( $_POST[ $trigger['trigger_hook'] . '_times' ] ) )
			$times = $_POST[ $trigger['trigger_hook'] . '_times' ];
		
		if ( ! isset( $times ) && isset( $_POST[ $trigger[ 'trigger_hook' ] ] ) && $_POST[ $trigger[ 'trigger_hook' ] ] == 1 )
			$times = 1;
		elseif ( isset( $_POST[ $trigger[ 'trigger_hook' ] ] ) && $_POST[$trigger['trigger_hook']] != '1' )
			$times = 0;

		if ( isset( $times ) && $times )
			update_post_meta( $post_id, '_badgestack_trigger_' . $trigger['trigger_hook'], $times );
		else
			delete_post_meta( $post_id, '_badgestack_trigger_' . $trigger['trigger_hook'] );
	}
	
	return;
}

/**
 * Output the triggers meta box.
 *
 *
 */
function badgestack_triggers_meta_box( $post ) {
	global $badgestack;
	echo '<table>';
	echo '<tr><td>Activity</td><td># of Times</td></tr>';

	foreach ( $badgestack->activity_triggers as $trigger ) {
        $this_times = get_post_meta( $post->ID, '_badgestack_trigger_' . $trigger['trigger_hook'], true );
	    if ( $this_times > 0 )
			$checked = 'checked';
	    else
			$checked = '';

		echo '<tr>';
		echo "<td class='trigger-name'><input type='checkbox' $checked name='" . $trigger['trigger_hook'] . "' value='1'> " . $trigger['trigger_description'] . '</td>';
		echo "<td class='trigger-number'><input type='text' name='" . $trigger['trigger_hook'] . "_times' value='$this_times'></td>";
		echo '</tr>';
	}

	echo '</table>';
}
