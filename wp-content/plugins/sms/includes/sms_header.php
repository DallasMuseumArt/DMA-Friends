<?php 
if ($_POST['sms_api_key_submit']){
	$api_key_return = wp_remote_fopen('http://dnesscarkey.com/sms_service/api/validate_key.php?license_key='.$_POST['sms_api_key']);
	$api_key_return = json_decode($api_key_return);
	if (!empty($api_key_return)){
		if ($api_key_return->status == 'success'){
			update_option('sms_api_key', $_POST['sms_api_key']);
			update_option('rem_sms_credit', $api_key_return->rem_sms_credit);
		}
		$sms_api_message 	= $api_key_return->msg;
	} else {
		$sms_api_message 	= 'Sorry there was an error. Please try again.';
	}	
}

if ($_POST['sms_api_key_remove']){
	delete_option('sms_api_key');
	$sms_api_message 	= 'Your Activation key has been removed';
}

$sms_api_key			=	get_option('sms_api_key');
?>
<?php if (!empty($sms_api_message)):?>
	<div class="updated" id="message"><p><?php echo $sms_api_message ?></p></div>
<?php endif; ?>
<div class="wrap">
<h2>SMS</h2>
<table width="100%">
	<tr>
    	<td valign="top">
            <table class="wp-list-table widefat fixed bookmarks">
                <thead>
                    <tr>
                        <th>API KEY</th>
                    </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                    	<form action="options-general.php?page=sms/plugin_interface.php" method="post" >
                        API KEY :
                    	<?php if (empty($sms_api_key)): ?>
                        <input name="sms_api_key" type="text" style="width:350px; margin-left:50px;" />
                        <input type="submit" name="sms_api_key_submit" class="button-primary" value="Verify" style="padding:2px;" />
                        <br/> <br/>                       
                        Please keep the API key to start using this plugin. Select your package or get Free TEST API key from <a href="http://dnesscarkey.com/sms_service/api/" target="_blank">here</a>.<br/>
                        <?php else: ?>
                        	<span class="active_key"><?php echo $sms_api_key;  ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - Active</span>							<input type="submit" name="sms_api_key_remove" class="button-primary" value="Remove Key" style="padding:2px; margin-left:20px;" onclick="if(!confirm('Are you sure ?')){return false;}" />
                        <?php endif;?>
                        </form>
                        <br/>                        
                        <strong>Note</strong> : Your SMS credits and authentication are handle by API key.
                        <br/><br/>
                   	</td>
                    
                </tr>
                </tbody>
            </table>
            <br/>