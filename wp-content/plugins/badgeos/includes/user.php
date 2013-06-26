<?php

add_action( 'init', 'badgestack_process_user_data' );

function badgestack_process_user_data() {

	//process awarding achievement to user
	if ( isset( $_GET['award_achievement'] )  && isset( $_GET['achievement_id'] ) ) {

		//check nonce for security
		check_admin_referer( 'badgestack_award_achievement' );

		$achievement_id = $_GET['achievement_id'];

		$user_id = $_GET['user_id'];

		//award achievement to user
		badgestack_award_achievement_to_user( absint( $achievement_id ), absint( $user_id ) );

	}

	//process revoking achievement from a user
	if ( isset( $_GET['user_id'] ) && isset( $_GET['achievement_id'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'revoke' ) {

		check_admin_referer( 'badgestack_revoke_achievement' );

		$user_id = $_GET['user_id'];
		$achievement_id = $_GET['achievement_id'];

		badgestack_revoke_achievement_from_user( absint( $achievement_id ), absint( $user_id ) );

		//redirect page to the same page

		//strip querystring values from the current URL
		$url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		$url = substr( $url, 0, strpos( $url, '?' ) );

		//add user_id value back in querystring
		$url = add_query_arg( 'user_id', absint( $user_id ), $url );

		wp_redirect( esc_url( $url ) );
		exit();

	}


}

add_filter( 'show_user_profile', 'badgestack_user_profile_data' );
add_filter( 'edit_user_profile', 'badgestack_user_profile_data' );

/**
 * Display achievements for a user on their profile screen
 *
 *
 */
function badgestack_user_profile_data( $user ) {


	$achievements = badgestack_get_achievements_for_user( absint( $user->ID ) );

	if ( badgestack_is_debug_mode() ) {

		//debug mode is on so show achievements array
		echo 'DEBUG MODE ENABLED<br />';
		echo 'Metadata value for: _badgestack_achievements<br />';

		var_dump ( $achievements );

	}

	if ( $achievements ) {

		echo '<h2>' .__( 'Achievements', 'badgestack' ). '</h2>';

		foreach ( $achievements as $achievement ) {

			//general remove URL with nonce for security
			$remove_url = wp_nonce_url( add_query_arg( array( 'user_id' => absint( $user->ID ), 'achievement_id' => absint( $achievement->ID ), 'action' => 'revoke' ) ), 'badgestack_revoke_achievement' );

			echo get_the_title( $achievement->ID ) .'  <a href="'.esc_url( $remove_url ).'">Revoke Badge</a><br />';

		}
	}

	if ( function_exists( 'dma_get_user_rewards' ) ) {
		$rewards = dma_get_user_rewards( $user->ID );
	
		if ( is_array( $rewards ) ) {
			
			echo '<h2>' .__( 'Rewards Redeemed', 'badgestack' ). '</h2>';
			
			foreach ( $rewards as $reward ) {
				$reward_data = get_post( $reward->ID );
				echo $reward_data->post_title .'<br />';
			}
			
			echo '<hr />';
		}
		
	}
	
	//reward achievements to a user
	$achievement_types = badgestack_get_achievement_types();

	?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			<?php foreach ( $achievement_types as $achievement_type ) { ?>
			jQuery('#<?php echo $achievement_type['single_name']; ?>').hide();
			<?php } ?>
			jQuery("#thechoices").change(function(){
			if(this.value == 'all')
				{jQuery("#boxes").children().show();}
			else
				{jQuery("#" + this.value).show().siblings().hide();}
			});

			jQuery("#thechoices").change();
		});
	</script>
	<?php

	echo __( 'Select Achievement Type to Give', 'badgestack' );
	echo '<select id="thechoices">';
		echo '<option>--SELECT--</option>';

	foreach ( $achievement_types as $achievement_type ) {

		echo '<option value="'.$achievement_type['single_name'].'">' .$achievement_type['single_name'] .'</option>';

	}

	echo '</select>';

	echo '<div id="boxes">';

	foreach ( $achievement_types as $achievement_type ) {

		echo '<div id="'.esc_attr( $achievement_type['single_name'] ).'">';

		//load achievement type entries
		$args = array(
			'post_type'			=>	$achievement_type['single_name'],
			'posts_per_page'	=>	'-1'
		);

		$the_query = new WP_Query( $args );

		// Loop through achievement entries
		while ( $the_query->have_posts() ) : $the_query->the_post();

			echo '<p>';

			echo  '<strong>' .$achievement_type['single_name'] .'</strong>: ' .get_the_title() .'<br />';

			echo get_the_post_thumbnail( get_the_ID(), 'thumbnail' );

			$args = array(
				'award_achievement'	=>	'true',
				'achievement_id'	=>	absint( get_the_ID() ),
				'user_id'			=>	absint( $user->ID )
			);

			$url = add_query_arg( $args );

			echo '<a href="' .esc_url( wp_nonce_url( $url, 'badgestack_award_achievement' ) ). '">Give '.$achievement_type['single_name'].'</a>';

			echo '</p>';

		endwhile;

		// Reset Post Data
		wp_reset_postdata();

		echo '</div>';

	}

	echo '</div>';


}
