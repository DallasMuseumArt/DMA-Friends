<?php
/**
 * BadgeStack Submission Actions
 *
 * @package BadgeStack
 */

add_filter( 'post_row_actions', 'badgestack_hide_quick_edit' );

//hide action links on the submissions edit listing screen
function badgestack_hide_quick_edit( $actions ) {
	global $post;

	if ( get_post_type( $post ) == 'submission' || get_post_type( $post ) == 'nomination' ) {
		//hide action links
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['trash'] );
		unset( $actions['view'] );
	}

	return $actions;

}

//add columns to the Submissions and Nominations edit screen
add_filter( 'manage_edit-submission_columns', 'badgestack_add_submission_columns', 10, 1 );
add_filter( 'manage_edit-nomination_columns', 'badgestack_add_nomination_columns', 10, 1 );

function badgestack_add_submission_columns( $columns ) {
	
	$column_content = array( 'content' => __( 'Content', 'badgestack' ) );
 	$column_action = array( 'action' => __( 'Action', 'badgestack' ) );
	$column_status = array( 'status' => __( 'Status', 'badgestack' ) );
	
	$columns = array_slice( $columns, 0, 2, true ) + $column_content + array_slice( $columns, 2, NULL, true );
	$columns = array_slice( $columns, 0, 3, true ) + $column_action + array_slice( $columns, 2, NULL, true );
	$columns = array_slice( $columns, 0, 4, true ) + $column_status + array_slice( $columns, 2, NULL, true );
	
	unset( $columns['comments'] );

	return $columns;

}

function badgestack_add_nomination_columns( $columns ) {

	$column_content = array( 'content' => __( 'Content', 'badgestack' ) );
	$column_userid = array( 'user' => __( 'User', 'badgestack' ) );
 	$column_action = array( 'action' => __( 'Action', 'badgestack' ) );
	$column_status = array( 'status' => __( 'Status', 'badgestack' ) );
	
	$columns = array_slice( $columns, 0, 2, true ) + $column_content + array_slice( $columns, 2, NULL, true );
	$columns = array_slice( $columns, 0, 3, true ) + $column_userid + array_slice( $columns, 2, NULL, true );
	$columns = array_slice( $columns, 0, 4, true ) + $column_action + array_slice( $columns, 2, NULL, true );
	$columns = array_slice( $columns, 0, 5, true ) + $column_status + array_slice( $columns, 2, NULL, true );
	
	unset( $columns['comments'] );

	return $columns;

}

add_action( 'manage_posts_custom_column', 'badgestack_submission_column_action', 10, 1 );

function badgestack_submission_column_action( $column ) {
	global $post, $badgestack;
	
	switch ( $column ) {
		case 'action':
			
			//if submission use the post Author ID, if nomination use the user ID meta value
			$user_id = ( $_GET['post_type'] == 'submission' ) ? $post->post_author : get_post_meta( $post->ID, '_badgestack_submission_user_id', true );
			
			echo '<a class="button-secondary" href="'.wp_nonce_url( add_query_arg( array( 'badgestack_status' => 'approve', 'post_id' => absint( $post->ID ), 'user_id' => absint( $user_id ) ) ), 'badgestack_status_action' ).'">'.__( 'Approve', 'badgestack' ).'</a>&nbsp;&nbsp;';
			echo '<a class="button-secondary" href="'.wp_nonce_url( add_query_arg( array( 'badgestack_status' => 'deny', 'post_id' => absint( $post->ID ), 'user_id' => absint( $user_id ) ) ), 'badgestack_status_action' ).'">'.__( 'Deny', 'badgestack' ).'</a>';
			break;
		
		case 'content':
			
			echo substr( $post->post_content, 0, 250 ) .'...';
			break;
		
		case 'status':
			
			$status = get_post_meta( $post->ID, '_badgestack_submission_status', true );
			$status = ( $status ) ? $status : __( 'pending', 'badgestack' );
			echo $status;
			break;
		
		case 'user':
			
			$user_id = get_post_meta( $post->ID, '_badgestack_submission_user_id', true );
			
			if ( is_numeric( $user_id ) ) {
				$user_info = get_userdata( absint( $user_id ) );
				echo $user_info->display_name;
				break;
			}
	}
}

add_action( 'restrict_manage_posts', 'badgestack_add_submission_dropdown_filters' );

//add dropdown on submissions edit screen to filter by status
function badgestack_add_submission_dropdown_filters() {
    global $typenow, $wpdb;

	if ( $typenow == 'submission' ) {
        //array of current status values available
        $submission_statuses = array( __( 'Approve', 'badgestack' ), __( 'Deny', 'badgestack' ), __( 'Pending', 'badgestack' ) );

		$current_status = ( isset( $_GET['badgestack_submission_status'] ) ) ? $_GET['badgestack_submission_status'] : '';
		
		//output html for status dropdown filter
		echo "<select name='badgestack_submission_status' id='badgestack_submission_status' class='postform'>";
		echo "<option value=''>" .__( 'Show All Statuses', 'badgestack' ).'</option>';
		foreach ( $submission_statuses as $status ) {
			echo '<option value="'.strtolower( $status ).'"  '.selected( $current_status, strtolower( $status ) ).'>' .$status .'</option>';
		}
		echo '</select>';
	}
}

add_filter( 'pre_get_posts', 'badgestack_submission_status_filter' );

//filter the query to show submission statuses
function badgestack_submission_status_filter( $query ) {
	global $pagenow;
	
	if ( $query->is_admin && ( 'edit.php' == $pagenow ) ) { 
		$metavalue = ( isset($_GET['badgestack_submission_status']) && $_GET['badgestack_submission_status'] != '' ) ? $_GET['badgestack_submission_status'] : '';

		if ( '' != $metavalue ) {
			$query->set( 'orderby' , 'meta_value' );
			$query->set( 'meta_key' , '_badgestack_submission_status' );
			$query->set( 'meta_value', esc_html( $metavalue ) );
		}
	}

	return $query;
}

add_action( 'admin_head', 'badgestack_process_data' );

//process submission approval data
function badgestack_process_data() {
	
	if ( isset( $_GET['badgestack_status'] ) && isset( $_GET['post_id'] ) ) {
		//nonce for security
		check_admin_referer( 'badgestack_status_action' );
		
		$submission_status = $_GET['badgestack_status'];
		$submission_id = $_GET['post_id'];
		$user_id = $_GET['user_id'];
		
		//update the submission entry status
		update_post_meta( absint( $submission_id ), '_badgestack_submission_status', strip_tags( $submission_status ) );
		
		if ( $submission_status == 'approve' ) {
			//reward achievement to user
			
			$achievement_id = get_post_meta( absint( $submission_id ), '_badgestack_submission_achievement_id', true );
			
			//give achievement to the user
			badgestack_award_achievement_to_user( absint( $user_id ), absint( $achievement_id ) );	
		}
	}
	
}
