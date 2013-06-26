<?php 
if ($_POST['sms_footer_text_submit']){
	update_option('sms_footer_text',$_POST['sms_footer_text']);
	$sms_footer_update_msg 	= 'Footer Text has been updated.';
}
$footer_sms_text	=	get_option('sms_footer_text');
?>

<?php if (!empty($sms_footer_update_msg)):?>
	<div class="updated" id="message"><p><?php echo $sms_footer_update_msg ?></p></div>
<?php endif; ?>

<table class="wp-list-table widefat fixed bookmarks">
                <thead>
                    <tr>
                        <th>SMS Footer Text</th>
                    </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                    	<form action="options-general.php?page=sms/plugin_interface.php" method="post" >
                       Footer Text :
                    	<input name="sms_footer_text" type="text" style="width:350px; margin-left:50px;" maxlength="30" value="<?php echo $footer_sms_text; ?>" />
                        <input type="submit" name="sms_footer_text_submit" class="button-primary" value="Submit" style="padding:2px;" />
                        </form>
                        <br/>                        
                        <strong>Note</strong> : SMS footer text are attached in every SMS that are send from your site. It doesn't works for Free Test API Key.
                        <br/><br/>
                   	</td>
                    
                </tr>
                </tbody>
            </table>
            <br/>