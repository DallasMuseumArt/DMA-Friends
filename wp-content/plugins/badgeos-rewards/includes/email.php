<?php

add_action( 'dma_user_claimed_reward', 'dma_send_reward_email', 1, 2 );

/**
* Sends email to user who redeemed a Reward
*
* @param  string $user_id		ID of the user
* @param  string $reward_id		ID of the reward
*/
function dma_send_reward_email( $user_id = 0, $reward_id = 0 ) {
	
	
	$reward_email_enabled = get_post_meta( $reward_id, '_dma_reward_enable_email', true );
	$reward_email_text = get_post_meta( $reward_id, '_dma_reward_redemption_email', true );
	
	if ( $reward_email_enabled != 'no' ) {	
	
		//get reward data
		$reward_name = get_the_title( absint( $reward_id ) );
		
		//get user email
		$user_info = get_userdata( absint( $user_id ) );
		$user_email = $user_info->user_email;

		$subject = 'Reward Details: ' .$reward_name;
		
		$headers[] = 'From: DMA Friends <rewards@dma.org>';
		$headers[] = 'Bcc: DMA Friends <rewards@dma.org>';
		
		if ( is_email( $user_email ) ) {

			//send the email
			wp_mail( $user_email, $subject, $reward_email_text, $headers );
			
		}
		
	}
	
}
