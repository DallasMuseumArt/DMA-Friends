<?php
/**
 * @package BadgeStack
 *
 */

/**
 * Check if nomination form was submitted and create a new nomination
 *
 *
 */
function badgestack_save_nomination_data() {
	global $current_user, $post;
	// If the form hasn't been submitted, bail.
	if ( ! isset( $_POST['badgestack_submission_submit'] ) )
		return false;

	//nonce check for security
	check_admin_referer( 'badgestack_submission_form' );

	get_currentuserinfo();
	$submission_content = $_POST['badgestack_submission_content'];
	$submission_user_id = $_POST['badgestack_submission_user_id'];
	$submission_type = get_post_type( absint( $post->ID ) );

	return badgestack_create_nomination(
		$post->ID,
		$submission_type . ':' . get_the_title( absint( $post->ID ) ),
		sanitize_text_field( $submission_content ),
		$submission_user_id,
		absint( $current_user->ID )
	);
}

function badgestack_create_nomination( $achievement_id, $title, $content, $user_nominated, $user_nominating ) {
	$submission_data = array(
		'post_title'	=>	$title,
		'post_content'	=>	$content,
		'post_status'	=>	'publish',
		'post_author'	=>	$user_nominated,
		'post_type'		=>	'nomination',
	);

	//insert the post into the database
	if ( $new_post_id = wp_insert_post( $submission_data ) ) {
		//save the submission status metadata
		add_post_meta( $new_post_id, '_badgestack_submission_status', 'pending' );

		//save the achievement id metadata
		add_post_meta( $new_post_id, '_badgestack_submission_achievement_id', $achievement_id );

		//save user ID if it exists
		if ( is_numeric( $user_nominated ) )
			add_post_meta( $new_post_id, '_badgestack_submission_user_id', absint( $user_nominated ) );
		do_action( 'save_nomination', $new_post_id );
		return true;
	} else {
		return false;
	}
}

/**
 * Check if nomination form has been submitted and save data
 *
 *
 */
function badgestack_save_submission_data() {
	global $current_user, $post;

	// If form items don't exist, bail.
	if ( ! isset( $_POST['badgestack_submission_submit'] ) || ! isset( $_POST['badgestack_submission_content'] ) )
		return;
	//nonce check for security
	check_admin_referer( 'badgestack_submission_form' );
	get_currentuserinfo();

	$submission_content = $_POST['badgestack_submission_content'];
	$submission_type = get_post_type( absint( $post->ID ) );

	return badgestack_create_submission(
		$post->ID,
		$submission_type . ':' . get_the_title( absint( $post->ID ) ),
		sanitize_text_field( $submission_content ),
		absint( $current_user->ID )
	);
}

function badgestack_create_submission( $achievement_id, $title, $content, $user_ID ) {
	$submission_data = array(
		'post_title'	=>	$title,
		'post_content'	=>	$content,
		'post_status'	=>	'publish',
		'post_author'	=>	$user_ID,
		'post_type'		=>	'submission',
	);
	//insert the post into the database
	if ( $new_post_id = wp_insert_post( $submission_data ) ) {
		// Set the submission approval status
		add_post_meta( $new_post_id, '_badgestack_submission_status', 'pending' );
		// save the achievement ID related to the submission
		add_post_meta( $new_post_id, '_badgestack_submission_achievement_id', $achievement_id );
		do_action( 'save_submission', $new_post_id );
		return true;
	} else {
		return false;
	}
}

function badgestack_get_unlock_options() {
	global $post;
	return $badgestack_unlock_options = get_post_meta( absint( $post->ID ), '_badgestack_badge_unlock_options', true );
}
/**
 * Returns the comment form for Submissions
 *
 *
 */
function badgestack_get_comment_form( $post_id = 0 ) {
	$sub_form = '<form method="post">';
	$sub_form .= '<p>' .__( 'Add Comment', 'badgestack' ) .'</p>';
	$sub_form .= wp_nonce_field( 'badgestack_comment_form' );
	$sub_form .=	'<textarea name="badgestack_comment"></textarea>';
	$sub_form .=	'<input type="submit" name="badgestack_comment_submit" value="' .__( 'Submit Comment', 'badgestack' ) .'" />';
	$sub_form .= '</form>';

	return $sub_form;
}

/**
 * Save submission comment data
 *
 *
 */
function badgestack_save_comment_data() {
	global $current_user;

	if ( ! isset( $_POST['badgestack_comment_submit'] ) || ! isset( $_POST['badgestack_comment'] ) )
		return;
	//process comment data

	//nonce check for security
	check_admin_referer( 'badgestack_comment_form' );

	get_currentuserinfo();

	$comment_data = array(
		'comment_post_ID' => absint( $post_id ),
		'comment_content' => sanitize_text_field( $_POST['badgestack_comment'] ),
		'user_id' => $current_user->ID,
	);

	wp_insert_comment( $comment_data );

	echo 'Comment saved!';
}

/**
 * Returns all comments for a Submission entry
 *
 *
 */
function badgestack_get_comments_for_submission( $post_id = 0 ) {

	$comments = get_comments( 'post_id=' .absint( $post_id ) );

	echo '<hr />';

	foreach( $comments as $comment ) :

		//get comment author data
		$user_data = get_userdata( $comment->user_id );

		//display comment data
		echo '<p>';
		echo __( 'Comment by', 'badgestack' ) .': ' .$user_data->display_name .'<br />';
		echo __( 'Comment date', 'badgestack' ) .': ' .$comment->comment_date .'<br />';
		echo __( 'Comment', 'badgestack' ) .': ' .$comment->comment_content .'<br />';
		echo '</p>';
		echo '<hr />';

	endforeach;

}

/**
 * Returns an array of achievement IDs for a specific user
 *
 *
 */
function badgestack_get_achievements_for_user( $user_id ) {
	if ( is_numeric( $user_id ) ) {
		$achievements = get_user_meta( $user_id, '_badgestack_achievements', true );
		if ( is_array( $achievements ) )
			return $achievements;
	}
	return false;
}

/**
 * Posts a log entry when a user unlocks any achievement post
 *
 * @since  1.0
 * @param  integer $post_id    The post id of the activity we're logging
 * @param  integer $user_id    The user ID
 * @param  string  $action     The action word to be used for the generated title
 * @param  string  $title      An optional default title for the log post
 * @return integer             The post ID of the newly created log entry
 */
function badgestack_post_log_entry( $post_id, $user_id = 0, $action = 'unlocked', $title = '' ) {
	global $user_ID;
	if ( $user_id == 0 ) {
		$user_id = $user_ID;
	}
 
	$user              = get_userdata( $user_id );
	$achievement       = get_post( $post_id );
	$achievement_types = badgestack_get_achievement_types();
	$default_title     = ( !empty( $title ) ? $title : "{$user->user_login} " . __( $action, 'badgestack' ) . " the {$achievement->post_title} " . $achievement_types[$achievement->post_type]['single_name'] );
	$title             = apply_filters( 'badgestack_log_entry_title', $default_title, $achievement_id, $user_id, $action, $achievement, $achievement_types );
 
	$args = array(
		'post_title'  => $title,
		'post_status' => 'publish',
		'post_author' => absint( $user_id ),
		'post_type'   => 'badgestack-log-entry',
	);
 
	if ( $log_post_id = wp_insert_post( $args ) )
		add_post_meta( $log_post_id, '_badgestack_log_achievement_id', $post_id );
 
	do_action( 'badgestack_create_log_entry', $log_post_id, $post_id, $user_id, $action );
 
	return $log_post_id;
}


/**
 * Returns array of user log ids (log/journey cpt) from post ids array and a user id.
 *
 *
 *
 */
function badgestack_get_userlog_ids( $post_ids, $user_id ){
	global $wpdb;
	if ( is_array( $post_ids ) ) {
		$post_ids = implode( ',', $post_ids );
		$sql = "SELECT a.ID FROM $wpdb->posts a, $wpdb->postmeta b WHERE a.ID = b.post_id AND a.post_author = ".$user_id." AND a.post_status = 'publish' AND b.meta_key = '_badgestack_log_achievement_id' and b.meta_value in ( ".$post_ids." )";
		$rs = $wpdb->get_results( $sql );
		foreach ( $rs as $post ) {
			$log_ids[] = $post->ID;
		}
		return $log_ids;
	}
}

/**
 * Check if debug mode is enabled
 *
 *
 */
function badgestack_is_debug_mode() {

	//get setting for debug mode
	$badgestack_settings = get_option( 'badgestack_settings' );
	$debug_mode = ( !empty( $badgestack_settings['debug_mode'] ) ) ? $badgestack_settings['debug_mode'] : 'disabled';

	if ( $debug_mode == 'enabled' ) {
		return true;
	}

	return false;

}

/**
 * Check if a user has an existing submission for an achievement
 *
 *
 */
function badgestack_check_if_user_has_submission( $user_id, $activity_id ) {
	$args = array(
		'post_type'		=>	'submission',
		'author'		=>	absint( $user_id ),
		'post_status'	=>	'publish',
		'meta_key'		=>	'_badgestack_submission_achievement_id',
		'meta_value'	=>	absint( $activity_id ),
	);

	$submissions = get_posts( $args );

	if ( !empty( $submissions ) ) {
		//user has an active submission for this achievement
		return true;
	}

	//user has no active submission for this achievement
	return false;
}

/**
 * Returns an array of achievements, based on arguments.
 *
 *
 *
 */
function badgestack_get_achievements( $args = array() ) {
	global $badgestack;
	$defaults = array(
		'post_type' => badgestack_get_achievement_types_slugs(),
		'suppress_filters' => false,
		'achievement_relationsihp' => 'any',
	);
	$args = wp_parse_args( $args, $defaults );

	if ( isset( $args['parent_of'] ) && $args['parent_of'] ) {
		// Hook join functions for joining to P2P table to retrieve the parent of an acheivement
		add_filter( 'posts_join', 'badgestack_get_achievements_modify_join_for_parent_of' );
		add_filter( 'posts_where', 'badgestack_get_achievements_modify_where_for_parent_of', 10, 2 );
	}
	if ( isset( $args['children_of'] ) && $args['children_of'] ) {
		// Hook join functions for joining to P2P table to retrieve the children of an acheivement
		add_filter( 'posts_join', 'badgestack_get_achievements_modify_join_for_children_of', 10, 2 );
		add_filter( 'posts_where', 'badgestack_get_achievements_modify_where_for_children_of', 10, 2 );
		add_filter( 'posts_orderby', 'badgestack_get_achievements_modify_orderby_for_children_of' );
	}
	$achievements = get_posts( $args );

	if ( isset( $args['parent_of'] ) && $args['parent_of'] ) {
		// Clean up
		remove_filter( 'posts_join', 'badgestack_get_achievements_modify_join_for_parent_of' );
		remove_filter( 'posts_where', 'badgestack_get_achievements_modify_where_for_parent_of' );
	}
	if ( isset( $args['children_of'] ) && $args['children_of'] ) {
		// Clean up
		remove_filter( 'posts_join', 'badgestack_get_achievements_modify_join_for_children_of' );
		remove_filter( 'posts_where', 'badgestack_get_achievements_modify_where_for_children_of' );
		remove_filter( 'posts_orderby', 'badgestack_get_achievements_modify_orderby_for_children_of' );
	}

	return $achievements;
}

function badgestack_get_achievements_modify_join_for_children_of( $join, $query_object ) {
	global $wpdb;
	$join .= " LEFT JOIN $wpdb->p2p AS p2p ON p2p.p2p_from = $wpdb->posts.ID";
	if ( isset( $query_object->query_vars['achievement_relationship'] ) && $query_object->query_vars['achievement_relationship'] != 'any' )
		$join .= " LEFT JOIN $wpdb->p2pmeta AS p2pm1 ON p2pm1.p2p_id = p2p.p2p_id";
	$join .= " LEFT JOIN $wpdb->p2pmeta AS p2pm2 ON p2pm2.p2p_id = p2p.p2p_id";
	return $join;
}

function badgestack_get_achievements_modify_where_for_children_of( $where, $query_object ) {
	global $wpdb;
	if ( isset( $query_object->query_vars['achievement_relationship'] ) && $query_object->query_vars['achievement_relationship'] == 'required' )
		$where .= " AND p2pm1.meta_key ='Required'";

	if ( isset( $query_object->query_vars['achievement_relationship'] ) && $query_object->query_vars['achievement_relationship'] == 'optional' )
		$where .= " AND p2pm1.meta_key ='Optional'";
	// ^^ TODO, add required and optional. right now just returns all achievemnts.
	$where .= " AND p2pm2.meta_key ='order'";
	$where .= $wpdb->prepare( ' AND p2p.p2p_to = %d', $query_object->query_vars['children_of'] );
	return $where;
}

function badgestack_get_achievements_modify_orderby_for_children_of( $orderby ) {
	return $orderby = 'p2pm2.meta_value ASC';
}

function badgestack_get_achievements_modify_join_for_parent_of( $join ) {
	global $wpdb;
	$join .= " LEFT JOIN $wpdb->p2p AS p2p ON p2p.p2p_to = $wpdb->posts.ID";
	return $join;
}
function badgestack_get_achievements_modify_where_for_parent_of( $where, $query_object ) {
	global $wpdb;
	$where .= $wpdb->prepare( ' AND p2p.p2p_from = %d', $query_object->query_vars['parent_of'] );
	return $where;
}
/**
 * Returns a multidimensional array of slug, single name and plural name for all achievement types.
 *
 *
 *
 */
function badgestack_get_achievement_types() {
	global $badgestack;
	return $badgestack->achievement_types;
}

/**
 * Returns an array of slugs of all achievement types.
 *
 *
 *
 */
function badgestack_get_achievement_types_slugs() {
	global $badgestack;
	foreach ( $badgestack->achievement_types as $achievement_type_slug => $achievement_type_data )
		$achievement_type_slugs[] = $achievement_type_slug;
	return $achievement_type_slugs;
}

/**
 * Returns the filepath to the BadgeStack plugin directory root.
 *
 *
 *
 */
function badgestack_get_directory_path() {
	global $badgestack;
	return $badgestack->directory_path;
}

/**
 * Returns the URL to the BadgeStack plugin direcoty root.
 *
 *
 *
 */
function badgestack_get_directory() {
	global $badgestack;
	return $badgestack->directory_url;
}

function badgestack_method_of_earning_achievement( $post_id = 0 ) {
	global $post;
	if ( ! $post_id )
		$post_id = $post->ID;
	return get_post_meta( $post_id, '_badgestack_badge_unlock_options', true );
}

/**
 * Check if user may access / earn achievement.
 *
 */
function badgestack_user_has_access_to_achievement( $user_id = 0, $achievement_id = 0 ) {
	global $badgestack;

	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}
	// If there is no parent for the achievement, bail true.
	if ( ! $parent_achievement = badgestack_get_parent_of_achievement( $achievement_id ) )
		return true;
	if ( ! badgestack_user_has_access_to_achievement( $user_id, $parent_achievement->ID ) )
		return false;

	$return = true;

	if ( badgestack_is_achievement_sequential( $parent_achievement->ID ) ) {
		$siblings = badgestack_get_required_children_of_achievement( $parent_achievement->ID );

		foreach ( $siblings as $sibling ) {
			if ( $sibling->ID == $achievement_id ) {
				$return = true;
				break;
			}
			if ( ! badgestack_check_if_user_has_achievement( $sibling->ID ) ) {
				$badgestack->prerequisite_achievement = $sibling;
				$return = false;
				break;
			}
		}
	}
	return apply_filters( 'user_has_access_to_achievement', $return, $user_id, $achievement_id );
}

/**
 * Returns a parent achievement given a child achievement ID
 *
 */
function badgestack_get_parent_of_achievement( $achievement_id = 0 ) {
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}
	$parents = badgestack_get_achievements( array( 'parent_of' => $achievement_id ) );

	if ( empty( $parents ) )
		return false;

	return $parents[0];
}

/**
 * Returns all achievement's children given a parent achievement ID
 *
 */
function badgestack_get_all_children_of_achievement( $achievement_id = 0 ) {
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}
	$children = badgestack_get_achievements( array( 'children_of' => $achievement_id, 'achievement_relationship' => 'any' ) );
	return $children;

}

/**
 * Returns achievement's required children given a parent achievement ID
 *
 */
function badgestack_get_required_children_of_achievement( $achievement_id = 0 ) {
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}
	$children = badgestack_get_achievements( array( 'children_of' => $achievement_id, 'achievement_relationship' => 'required' ) );
	return $children;
}

/**
 * Returns achievement's optional children given a parent achievement ID
 *
 */
function badgestack_get_optional_children_of_achievement( $achievement_id = 0 ) {
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}
	$children = badgestack_get_achievements( array( 'children_of' => $achievement_id, 'achievement_relationship' => 'optional' ) );
	return $children;
}

/**
 * Checks if the achievement's child achievements are requried to be
 * earned sequentially
 */
function badgestack_is_achievement_sequential( $achievement_id ) {
	if ( get_post_meta( $achievement_id, '_badgestack_badge_sequential', true ) == 'sequential' )
		return true;
	else
		return false;
}
