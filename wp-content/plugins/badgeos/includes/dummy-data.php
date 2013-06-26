<?php
/**
 * BadgeStack Dummy Data
 *
 * @package BadgeStack
 */


add_action( 'admin_init', 'badgestack_dummy_data_process' );

function badgestack_dummy_data_process() {

	if ( isset( $_POST['create_dummy_data'] ) ) {
		
		//create dummy user
		$username = 'michaelmyers-' .rand();
		$user_id = username_exists( $username );
		
		if ( !$user_id ) {
			
			//generate random password
			$random_password = wp_generate_password( 12, false );
			
			//create the user
			$new_user_id = wp_create_user( $username, $random_password, 'bwar'.rand().'@doesnotexist.com' );

			//set user role to Author
			$user = new WP_User( $new_user_id );
			$user->set_role( 'author');
			
			//add user meta to track as a dummy account
			add_user_meta( $new_user_id, '_badgestack_dummy_account', 'true' );

		}
		
		if ( $new_user_id > 0 ) {
			
			$random_number = rand();
			
			//create 10 dummy badges
			$badges = array(
				'Halloween Badge ' .$random_number,
				'New User Badge ' .$random_number,
				'First Comment Badge ' .$random_number,
				'Marathon Badge ' .$random_number,
				'Rockstar Badge ' .$random_number,
				'Eric Badge ' .$random_number,
				'Brad Badge ' .$random_number,
				'Brian Badge ' .$random_number,
				'Costume Badge ' .$random_number,
				'Late Night Badge ' .$random_number
			);
			
			foreach ( $badges as $badge ) {
				
				//create dummy data
				$badge_data = array(
					'post_title'	=>	sanitize_text_field( $badge ),
					'post_status'	=>	'publish',
					'post_type'		=>	'badge',
					'post_author'	=>	absint( $new_user_id )
				);
				
				$new_post_id = wp_insert_post( $badge_data );
				
				add_post_meta( $new_post_id, '_badgestack_dummy_data', 'true' );
				
				//set post IDs for specific badges to test
				if ( $badge == 'Halloween Badge ' .$random_number ) {
					$halloween_id = $new_post_id;
				}elseif( $badge == 'Costume Badge ' .$random_number ) {
					$costume_id = $new_post_id;
				}elseif( $badge == 'Late Night Badge ' .$random_number ) {
					$late_night_id = $new_post_id;
				}elseif( $badge == 'Rockstar Badge ' .$random_number ) {
					$rockstar_id = $new_post_id;
				}elseif( $badge == 'New User Badge ' .$random_number ) {
					$new_user_badge_id = $new_post_id;
				}elseif( $badge == 'First Comment Badge ' .$random_number ) {
					$first_comment_id = $new_post_id;
				}elseif( $badge == 'Marathon Badge ' .$random_number ) {
					$marathon_badge_id = $new_post_id;
				}
				
				
			}
			
			//create sample badge to badge requirements
			//Halloween badge requires costume and late night badges
			p2p_type( 'badge_to_badge' )->connect( $costume_id, $halloween_id, array( 'date' => current_time('mysql') ) );
			p2p_type( 'badge_to_badge' )->connect( $late_night_id, $halloween_id, array( 'date' => current_time('mysql') ) );
			
			//create 10 dummy steps
			$steps = array(
				'Play Guitar Step ' .$random_number,
				'Buy a Bus Step ' .$random_number,
				'Website Login Step ' .$random_number,
				'Add Comment Step ' .$random_number,
				'Run 1 Mile Step ' .$random_number,
				'Run 10k Step ' .$random_number,
				'Run 26.2 Miles Step ' .$random_number,
				'WDS Badge ' .$random_number,
				'Epic Badge ' .$random_number
			);
			
			foreach ( $steps as $step ) {
				
				//create dummy data
				$step_data = array(
					'post_title'	=>	sanitize_text_field( $step ),
					'post_status'	=>	'publish',
					'post_type'		=>	'step',
					'post_author'	=>	absint( $new_user_id )
				);
				
				$new_post_id = wp_insert_post( $step_data );
				
				add_post_meta( $new_post_id, '_badgestack_dummy_data', 'true' );
			
				//set post IDs for specific steps to test
				if ( $step == 'Play Guitar Step ' .$random_number ) {
					$guitar_id = $new_post_id;
				}elseif( $step == 'Buy a Bus Step ' .$random_number ) {
					$bus_id = $new_post_id;
				}elseif( $step == 'Website Login Step ' .$random_number ) {
					$login_step_id = $new_post_id;
				}elseif( $step == 'Add Comment Step ' .$random_number ) {
					$add_comment_step_id = $new_post_id;
				}elseif( $step == 'Run 1 Mile Step ' .$random_number ) {
					$onemile_step_id = $new_post_id;
				}elseif( $step == 'Run 10k Step ' .$random_number ) {
					$tenk_step_id = $new_post_id;
				}elseif( $step == 'Run 26.2 Miles Step ' .$random_number ) {
					$marathon_step_id = $new_post_id;
				}
				
			}
			
			//create sample step to badge requirements
			
			//Rockstar badge requires play guitar and buy a bus steps
			p2p_type( 'step_to_badge' )->connect( $guitar_id, $rockstar_id, array( 'date' => current_time('mysql') ) );
			p2p_type( 'step_to_badge' )->connect( $bus_id, $rockstar_id, array( 'date' => current_time('mysql') ) );
			
			//New User badge requires User login step/trigger
			p2p_type( 'step_to_badge' )->connect( $login_step_id, $new_user_badge_id, array( 'date' => current_time('mysql') ) );
			add_post_meta( $login_step_id, '_badgestack_trigger_wp_login', '1' );
			
			//First Comment Badge requires Add Comment step/trigger
			p2p_type( 'step_to_badge' )->connect( $add_comment_step_id, $first_comment_id, array( 'date' => current_time('mysql') ) );
			add_post_meta( $add_comment_step_id, '_badgestack_trigger_comment_post', '1' );
			
			//Marathon badge requires three steps in sequential order
			p2p_create_connection( 'step_to_badge', array(
				'from'	=> $onemile_step_id,
				'to'	=> $marathon_badge_id,
				'meta'	=> array(
					'date'	=>	current_time('mysql'),
					'order'	=>	'1',
					'require' => 'Required',
				)
			) );
			p2p_create_connection( 'step_to_badge', array(
				'from'	=> $tenk_step_id,
				'to'	=> $marathon_badge_id,
				'meta'	=> array(
					'date'	=>	current_time('mysql'),
					'order'	=>	'2',
					'require' => 'Required',
				)
			) );
			p2p_create_connection( 'step_to_badge', array(
				'from'	=> $marathon_step_id,
				'to'	=> $marathon_badge_id,
				'meta'	=> array(
					'date'	=>	current_time('mysql'),
					'order'	=>	'3',
					'require' => 'Required',
				)
			) );
			add_post_meta( $marathon_badge_id, '_badgestack_badge_sequential', 'sequential' );
			
		}
		
	}elseif ( isset( $_POST['delete_dummy_data'] ) ) {
		
		//delete dummy data
		$badge_data = array(
			'post_type'			=>	'badge',
			'meta_key'			=>	'_badgestack_dummy_data',
			'meta_value'		=>	'true',
			'posts_per_page'	=>	'-1'
		);
		
		$badges = get_posts( $badge_data );

		foreach ( $badges as $post ) :
			
			//delete the dummy data entries
			wp_delete_post( absint( $post->ID ), true );
			
		endforeach;
		
		//delete dummy users based on user meta value set
		$dummy_users = get_users( array( 'meta_key' => '_badgestack_dummy_account', 'meta_value' => 'true' ) );
		
		foreach ( $dummy_users as $user ) {
			
			//delete the user
			wp_delete_user( $user->ID );
			
		}
		
	}
	
}

add_action( 'badgestack_settings', 'badgestack_dummy_data_settings' );

function badgestack_dummy_data_settings() {
	//add dummy data options to BadgeStack settings page
	?>
	<tr>
		<td colspan="2"><hr/></td>
	</tr>
	<tr valign="top"><th scope="row"><?php _e( 'Dummy Data', 'badgestack' ); ?></th>
		<td>
			<input type="submit" name="create_dummy_data" value="Create Dummy Data" />&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="submit" name="delete_dummy_data" value="Delete Dummy Data" />
		</td>
	</tr>
	<?php
}
