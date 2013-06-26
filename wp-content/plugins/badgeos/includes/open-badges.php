<?php
/**
 * Mozilla OpenBadges BackPack Integration for the BadgeStack WordPress Plugin
 *
 * @package
 */

add_action( 'init', 'test_obi' );
function test_obi(){
	if ( isset( $_GET['test'] ) ) {
		global $blog_id, $user_ID;
		//local
		/*badgestack_revoke_achievement_from_user( 269, $user_ID );
		badgestack_award_achievement_to_user( 269, $user_ID );
		badgestack_revoke_achievement_from_user( 921, $user_ID );
		badgestack_award_achievement_to_user( 921, $user_ID );*/
		
		
		//dev
		
		//badgestack_revoke_achievement_from_user( 250, $user_ID );
		//badgestack_award_achievement_to_user( 250, $user_ID );
		//badgestack_revoke_achievement_from_user( 255, $user_ID );
		//badgestack_award_achievement_to_user( 255, $user_ID );
		
	}
}


/**
 * Set Mozilla OpenBadges BackPack global to decide if should use the old API or the new API
 *
 * 
 */
 add_action( 'init', 'badgestack_openbadges_api_globals', 0);
 function badgestack_openbadges_api_globals(){
	 global $badgestack_backpack_api;
	 $badgestack_backpack_api = 1;
	 if ( $badgestack_backpack_api == 2 ) {
		add_action( 'wp_enqueue_scripts', 'badgestack_openbadges_enqueue' ); 
	 }
 }
 


//enqueue openbadges api called from init if v2 od backpack api
function badgestack_openbadges_enqueue() {
   wp_enqueue_script( 'openbadges', 'http://beta.openbadges.org/issuer.js', array(), null );       
} 


/**
 * Creates array of post_ids on user meta to send to Open Badges BackPack when a badge is unlocked by a user (BackPack Queue).
 *
 *
 *	
 */
add_action( 'badgestack_after_award_achievement_action', 'badgestack_obi_add_backpack_queue', 10, 2 );
function badgestack_obi_add_backpack_queue( $user_id, $post_id ){
	global $blog_id, $badgestack_backpack_api;
	if ( $badgestack_backpack_api == 1 ) {
		badgestack_old_backpack_push( $user_id, $post_id );
	} elseif ( $badgestack_backpack_api == 2 ) {
		//get array from backpack queue
		$badges = get_user_meta( $user_id, '_badgestack_obi_queue_' . $blog_id, true );
		//check if array
		if ( is_array( $badges ) ) {
			//make sure post_id not already in array
			if ( !in_array( $post_id, $badges ) ) {
				$badges = array_merge( $badges, array( $post_id ) );
				update_user_meta( $user_id, '_badgestack_obi_queue_' . $blog_id, $badges );
			}
		} else {
			update_user_meta( $user_id, '_badgestack_obi_queue_' . $blog_id, array( $post_id ) );
		}
	}
}

/**
 * If a user gets an achievement revoked before they can push to OBI then remove badge from backpack queue.
 * Only for v2 of Mozilla obi api
 *
 *	
 */
add_action( 'badgestack_after_revoke_achievement_action', 'badgestack_obi_remove_backpack_queue', 10, 2 );
function badgestack_obi_remove_backpack_queue( $user_id, $post_id ){
	global $blog_id, $badgestack_backpack_api;
	if ( $badgestack_backpack_api == 2 ) {
		//get backpack queue array
		$badges = get_user_meta( $user_id, '_badgestack_obi_queue_' . $blog_id, true );
		if ( is_array( $badges ) ) {
			//verify achievement ID exist in array and get the key
			if ( ( $key = array_search( $post_id, $badges ) ) !== false ) {
				// remove achievement ID from array	
				unset( $badges[$key] );
				if ( $badges[0] ) {
					  update_user_meta( $user_id, '_badgestack_obi_queue_' . $blog_id, $badges );
				} else {
					  delete_user_meta( $user_id, '_badgestack_obi_queue_' . $blog_id );	  
				}
			}
		}
	}
}


/**
 * Builds $backpack_badges_queue global and ajax javascript for OBI response
 * Only for v2 of Mozilla obi api
 *
 *	
 */
add_action( 'wp_head', 'badgestack_obi_ajax_js' );
function badgestack_obi_ajax_js(){
	global $user_ID, $blog_id, $backpack_badges_queue, $badgestack_backpack_api;
	if ( $badgestack_backpack_api == 2 ) {
		$backpack_badges_queue = get_user_meta( $user_ID, '_badgestack_obi_queue_' . $blog_id, true );
		if ( is_array( $backpack_badges_queue ) ) {
		  wp_print_scripts( array( 'sack' ) );?>
		  <script type="text/javascript">
				function badgestack_obi_js(response,args){//called from js in footer
					var nutsack = new sack("<?php echo site_url();?>/wp-admin/admin-ajax.php");
					nutsack.execute = 1;
					nutsack.method = 'POST';
					nutsack.setVar( "action", "badgestack_obi_ajax" );
					nutsack.setVar( "response", response );
					if ( response == "reject") {
					  nutsack.setVar( "count", args.length );
					  nutsack.setVar( "reason", args[0].reason );
					  for (var i = 0; i < args.length; i++) {
						  nutsack.setVar( "url_"+i, args[i].url );
					  }
					}
					nutsack.setVar( "args", args );
					nutsack.runAJAX();
					return true;
				}
		  </script>
		  <?php
		}
	}
}


/**
 * Ajax for OBI "reject" response
 *
 *
 *	
 */
add_action( 'wp_ajax_badgestack_obi_ajax', 'badgestack_obi_ajax' );
add_action( 'wp_ajax_nopriv_badgestack_obi_ajax', 'badgestack_obi_ajax' );
function badgestack_obi_ajax(){
	$args = $_POST['args'];
	$response = $_POST['response'];
	if ( $response == 'reject' ) {
	  	$count = $_POST['count'];
		$response_value = 0; 
	} elseif ( $response == 'accept' ) {
		$args_arr = explode( ',', $args );
		$count = count( $args_arr );
		$response_value = 1;
	}
	if ( $count ) {
	  for ( $i = 0; $i < $count; $i++ ) {
		  if ( $response == 'reject' ) {
		  	$badge_url = $_POST['url_'.$i];
		  } elseif ( $response == 'accept' ) {
			$badge_url = $args_arr[$i];
		  }
		  $post_id = explode( 'obi_json_post_id', $badge_url );
		  $post_id = explode( '&', $post_id[1] );
		  $post_id = trim( str_replace( '=', '', $post_id[0] ) );
		  $post_ids[] = $post_id;
	  }
	}
	if ( is_array( $post_ids ) ) {
		badgestack_obi_log_response( $post_ids, $response_value );
	}
	ob_start();
	echo $response.' post_ids: ';
	print_r( $post_ids );
	$return = ob_get_clean();
	$return = mysql_real_escape_string( $return );
	die("alert('$return')");
}


/**
 * Logs OBI response on the orginal user log entry that was created when the user unlocked an achievement id(s)
 * Deletes user meta key _badgestack_obi_queue_ value of achievement ids to send to OBI BackPack (BackPack Queue)
 *
 *	
 */
function badgestack_obi_log_response( $post_ids, $response ){
	global $user_ID, $blog_id;
	if ( is_array( $post_ids ) ) {
		$log_ids = badgestack_get_userlog_ids( $post_ids, $user_ID );
		foreach ( $log_ids as $log_id ) {
			add_post_meta( $log_id, '_badgestack_log_obi_backpack', $response );
		}
		//wipe out backpack queue
		delete_user_meta( $user_ID, '_badgestack_obi_queue_' . $blog_id );
	}
}


/**
 * Checks user backpack queue and if it has badges then send them to openbadges backpack and store response against the activity log.
 *
 *
 *	
 */
add_action( 'wp_footer', 'badgestack_send_2_obi_backpack' );
function badgestack_send_2_obi_backpack(){
	global $blog_id,$user_ID,$backpack_badges_queue, $badgestack_backpack_api;
	if ( $badgestack_backpack_api == 2 ) {
		if ( is_array( $backpack_badges_queue ) ) {
		  foreach ( $backpack_badges_queue as $post_id ) {
			  $url[] = site_url().'?obi_json_post_id='.$post_id.'&obi_json_user_id='.$user_ID;
		  }
		  $assertionUrl = '"'.implode( '", "', $url ).'"';
		  ?>
		  <script type="text/javascript">
			  OpenBadges.issue([<?php echo $assertionUrl; ?>], function(errors, successes) {					
				  if (successes.length > 0) {
						  //alert(successes);
						  badgestack_obi_js('accept',successes);
						  //call ajax to wipe out user obi queue array
				  }
				  if (errors.length > 0) {
						  //alert(errors[0].url);
						  badgestack_obi_js('reject',errors);
						  //console.log(errors[0]);
						  //call ajax to wipe out user obi queue array and update journey
				  }
			  });
		  </script>
		  <?php
		}
	}
}


/**
 * Builds JSON to send to OBI
 *
 *
 *	
 */
add_action( 'init', 'badgestack_obi_json' );
function badgestack_obi_json(){
  if ( isset( $_GET['obi_json_post_id'] ) && isset( $_GET['obi_json_user_id'] ) || isset( $_GET['obi_json_old_api_post_user'] ) ) {
	header( 'Content-Type: application/json' ); 
	global $user_ID, $blog_id; 
	//old version of API
	if ( isset( $_GET['obi_json_old_api_post_user'] ) ) {
		$badgestack_backpack_api = 1;
		$values=explode('_',$_GET['obi_json_old_api_post_user']);
		$post_id = $values[0];
		$user_id = $values[1];
	//newer version of API
	} elseif ( isset( $_GET['obi_json_post_id'] ) && isset( $_GET['obi_json_user_id'] ) ) {
		$badgestack_backpack_api = 2;
		$post_id = $_GET['obi_json_post_id'];	
		$user_id = $_GET['obi_json_user_id'];
	}
	if ( $post_id && $user_id ) {
	  $user = get_userdata( $user_id );
	  $email = $user->user_email;
	  $salt = 'salt'.$email.$post_id.$user_id;
	  $post = get_post( $post_id );
	  $title = $post->post_title;
	  $description = $post->post_content;
	  if ( !$description ) {
		  $description = $title;	
	  }
	  $issued_on = date( 'Y-m-d' );
	  $criteria = get_permalink( $post_id ); 
	  
	  $thumbnail_id = get_post_thumbnail_id( $post_id );
	  $image = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
	  $image_url = $image[0];
	  
	  
	  //get user log entry that was unlocked when achievement was unlocked
	  $log_id = badgestack_get_userlog_ids( array( $post_id ), $user_id );
	  if ( is_array( $log_id ) ) {
		  $evidence = get_permalink( $log_id[0] );
	  }
	  if ( $badgestack_backpack_api == 2 ) {?>
	  {
		"recipient": "sha256$<?php echo hash( 'sha256', ( $email . $salt ) ); ?>",
		"salt": "<?php echo $salt; ?>",
		"evidence": "<?php echo $evidence;?>",
		"issued_on": "<?php echo $issued_on;?>",
		"badge": {
		  "version": "0.5.0",
		  "name": "<?php echo $title;?>",
		  "image": "<?php echo $image_url;?>",
		  "description": "<?php echo $description;?>",
		  "criteria": "<?php echo $criteria;?>",
		  "issuer": {
			"origin": "<?php echo site_url();?>",
			"name": "<?php echo get_bloginfo( 'name' );?>"
		  }
		}
	  }
	  <?php
	  } elseif ( $badgestack_backpack_api == 1 ) {
		  $wds_open_badges_array = array(
			  'recipient' => 'sha256$' . hash( 'sha256', ( $email . $salt ) ), // format recipient as "sha256" + "$" + hashed email
			  'salt' => $salt,
			  'evidence' => $evidence,
			  'expires' => NULL,
			  'issued_on' => $issued_on,
			  'badge' => array( 
				  'version' => '0.5.0',
				  'name' => $title,
				  'image' => $image_url, // PNG URL 
				  'description' => $description,
				  'criteria' => $criteria,
				  'issuer' => array( 'origin' => site_url(),
					  'name' => get_bloginfo( 'name' ),
					  'org' => get_bloginfo( 'name' ),
					  'contact' => 'brian@webdevstudios.com'
				  )
			  )
		  );
		  echo json_encode( $wds_open_badges_array );
	  }
		exit();
	}
  }
}

/**
 * Pushes a badge to a users backpack via the old api method
 *
 *
 *	
 */
function badgestack_old_backpack_push( $user_id, $post_id ) {
	$user_data = get_userdata( $user_id );
	$file_name_external = '?obi_json_old_api_post_user='.$post_id.'_'.$user_id;
	$request = "http://stage.openbadges.org/baker?award=".urlencode($user_data->user_email)."&assertion=" . site_url() . $file_name_external;
    
	$session =  curl_init( $request );
    curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $session );
    $decoded_response = json_decode( $response );
    curl_close( $session );
    //send request also to beta
    $request = "http://beta.openbadges.org/baker?award=".urlencode($user_data->user_email)."&assertion=" . site_url() . $file_name_external;
    $session =  curl_init( $request );
    curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );
    $headers = array( 'Accept: application/json' ); 
    $response = curl_exec( $session );
    $decoded_response = json_decode( $response );
    curl_close( $session );
	//TODO: figure out how to capture a response to update user log so we know if the badge actually got pushed to obi or not, if not add to queue?
	//echo $response."<hr>";
	//echo $decoded_response."<hr>";
	//print_r( $response );
	//print_r( $decoded_response );
}

