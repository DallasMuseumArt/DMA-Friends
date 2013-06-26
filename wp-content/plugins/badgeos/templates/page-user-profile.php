<?php
global $current_user;
get_currentuserinfo();
$achievement_ids = get_user_meta( $current_user->ID, '_badgestack_achievements', true );
if ( is_array( $achievement_ids ) ) {
	foreach ( $achievement_ids as $achievement_id ) {
		$achievement = get_post( $achievement_id );
		var_dump( $achievement_id );
		echo $achievement->post_title . '<BR />';
	}
}
