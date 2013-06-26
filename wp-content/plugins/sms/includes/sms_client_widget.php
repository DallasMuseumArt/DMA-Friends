<?php
	if ($_POST['sms_send_'.$sms_section_from]){
		$getUniqueSMSID				= @$_COOKIE[$_POST['UniqueFieldID']];
		if ($_POST[$_POST['UniqueFieldID']] == $getUniqueSMSID):
			$toSendSMSNumber			= $_POST['sms_mob_number'];
			$toSendSMSMessage			= urlencode(substr($_POST['sms_mob_msg'],0,120));
			$toSendSMSFooterText		= urlencode(substr(get_option('sms_footer_text'),0,30));
			$SMSAPIKEY					= get_option('sms_api_key');
			$sms_send_response  		= wp_remote_fopen('http://dnesscarkey.com/sms_service/sendsms.php?api_key='.$SMSAPIKEY.'&to='.$toSendSMSNumber.'&msg='.$toSendSMSMessage.'&footer_text='.$toSendSMSFooterText);
			
			$sms_send_response = json_decode($sms_send_response);
			$sms_show_msg	=	 $sms_send_response->msg;
			$sms_show_class	=	 $sms_send_response->status;
			
			if ($sms_send_response->update_sms_credit == 'yes'):
				update_option('rem_sms_credit', $sms_send_response->rem_sms_credit);
			endif;
			
			if ($sms_send_response->status == 'ok'):
				
				$allTimeMsgSend =	get_option('all_msg_send');
				if (empty($allTimeMsgSend)):
					$allTimeMsgSend = 0;
				endif;
				$allTimeMsgSend++;
				update_option('all_msg_send', $allTimeMsgSend);
				
				$day_msg_stat =	get_option('day_msg_stat');
				if (empty($day_msg_stat)):
					$day_msg_stat	= array();
				else:
					$day_msg_stat	= json_decode($day_msg_stat, true);
				endif;
				
				if (array_key_exists(date('Y-m-d'),$day_msg_stat)){
					$day_msg_stat[date('Y-m-d')]++;
				} else {
					$day_msg_stat[date('Y-m-d')] = 1;	
				}		
				update_option('day_msg_stat', json_encode($day_msg_stat));
			endif;
			echo "<script type='text/javascript'>jQuery.removeCookie('".$_POST['UniqueFieldID']."', { path: '/' });</script>";
		else:
			$sms_show_msg	=	 "You are trying to submit the same form twice or no COOKIE enabled.";
			$sms_show_class	=	 'err';
		endif;
	}
	$UniqueFieldID								=	date('ymdhmis');
	$UniqueSMSID								=	smsCreateRandomCode();
?>
<script type="text/javascript">
	jQuery.cookie('<?php echo $UniqueFieldID; ?>', '<?php echo $UniqueSMSID; ?>', { expires: 7, path: '/' });
</script>

<div id="send_sms">
	<?php if (!empty($sms_show_msg)): ?>
    	<div id="sms_msg_box" class="<?php echo $sms_show_class; ?>">
        	<?php 
				echo $sms_show_msg;
				$sms_show_msg = '';
			?>
        </div>
	<?php endif; ?>
    <form action="" method="post" id="send_sms_form">
    <ul class="sms_form">
    	<li>
        	<label>Mobile Number</label>
            <input type="text" class="required number" value="00" name="sms_mob_number" />
            <em>Format : 00##########</em>
        </li>
        
        <li>
        	<label>Message</label>
            <textarea class="required" name="sms_mob_msg" maxlength="120"></textarea>
            <em>120 Characters Allowed</em>
        </li>
        
        <li>
        	<input type="hidden" name="UniqueFieldID" value="<?php echo $UniqueFieldID; ?>" />
            <input type="hidden" name="<?php echo $UniqueFieldID; ?>" value="<?php echo $UniqueSMSID; ?>" />
            <input type="submit" name="sms_send_<?php echo $sms_section_from ?>" value="Send" />
        </li>        
    </ul>
    </form>
</div>
<script>
	jQuery('#send_sms_form').validate();
</script>