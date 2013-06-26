<?php 
$server_status 	= get_option('sms_server_status');
if ($_POST['test_server'] || empty($server_status)){
		$test_code	= date('ymdhis');
		$response	= wp_remote_fopen('http://dnesscarkey.com/sms_service/server/check.php?test_code='.$test_code);
		if ($response == $test_code){
			$server_err_stat	= 'test_successfull';
			$server_err_msg		= '';
		} else {
			$server_err_stat	= 'test_error';
			$server_err_msg 	= '<strong>Error</strong>: Sorry couldnot get response back from the server.';	
		}
		update_option('sms_server_status', $server_err_stat);
		update_option('sms_server_msg', $server_err_msg);
}
$server_status 	= get_option('sms_server_status');
$server_message = get_option('sms_server_msg');
?>
        <br/>
        <table class="wp-list-table widefat fixed bookmarks">
            	<thead>
                <tr>
                	<th>Instruction</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                	<td>
                    	<ol>
                        	<li>Get API key from <a href="http://dnesscarkey.com/sms_service/api/" target="_blank">here</a>. You can select the SMS package or get the Free Test API Key.
                            </li>
                            
                            <li>Goto Apperance > Wigets and drag and drop SMS widget to your sidebar. OR you can also use shortcode [SMS] to keep in post and pages.</li>
                            
                            <li>Keep Footer Text to be send in every SMS. <br/>
                            <em><strong>Note : </strong>It doesnot work for Free Test API key.</em></li>                            
                        </ol>
                    </td>
                </tr>
                </tbody>
            </table>
        
        </td>
        <td width="15">&nbsp;</td>
        <td width="250" valign="top">
        	<table class="wp-list-table widefat fixed bookmarks">
            	<thead>
                <tr>
                	<th>Server Connectivity Test</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                	<td>
                    	<div id="server_status" class="<?php echo $server_status; ?>">
                        	<?php echo str_replace('_',' ',$server_status); ?>
                        </div>						
                        
                        <?php if ($server_status == 'test_error'): ?>
						<div class="sms_test_msg"><?php echo $server_message; ?></div>
                        <?php endif; ?>
                        
                        
                        <form action="options-general.php?page=sms/plugin_interface.php" method="post">
                        	<p align="center">
                            <input type="submit" value="Test Again" class="button-primary" name="test_server" />
                            </p>
                        </form>
                    </td>
                </tr>
                </tbody>
            </table>
            <br/>
            <table class="wp-list-table widefat fixed bookmarks">
            	<thead>
                <tr>
                	<th>Quick Links</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                	<td>
                    <ul class="sms_list">
                    	<li><a href="http://dineshkarki.com.np/forums/forum/sms-service" target="_blank">Visit Support Forum</a></li>
                        <li><a href="http://dineshkarki.com.np/sms-plugin/add-more-sms-credits" target="_blank">Add SMS Credit</a></li>
                        
                        <li><a href="http://dineshkarki.com.np/contact" target="_blank">Contact us</a></li>
                    </ul>
                    </td>
                </tr>
                </tbody>
            </table>
            <br/>
            <table class="wp-list-table widefat fixed bookmarks">
            	<thead>
                <tr>
                	<th>Plugins You May Like</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                	<td>
                    	<ul class="sms_list">
                        	<li><a href="http://wordpress.org/extend/plugins/use-any-font/" target="_blank">Use Any Font</a></li>
                            <li><a href="http://wordpress.org/extend/plugins/any-mobile-theme-switcher/" target="_blank">Any Mobile Theme Switcher</a></li>
                            <li><a href="http://wordpress.org/extend/plugins/add-tags-and-category-to-page/" target="_blank">Add Tags And Category To Page</a></li>
                            <li><a href="http://wordpress.org/extend/plugins/block-specific-plugin-updates/" target="_blank">Block Specific Plugin Updates</a></li>
                            <li><a href="http://wordpress.org/extend/plugins/featured-image-in-rss-feed/" target="_blank">Featured Image In RSS Feed</a></li>
                            <li><a href="http://dineshkarki.com.np/jquery-validation-for-contact-form-7" target="_blank">Jquery Validation For Contact Form 7</a></li>
                            <li><a href="http://wordpress.org/extend/plugins/remove-admin-bar-for-client/" target="_blank">Remove Admin Bar</a></li>
                            <li><a href="http://wordpress.org/extend/plugins/html-in-category-and-pages/" target="_blank">.html in category and page url</a></li>
                        </ul>
                    </td>
                </tr>
                </tbody>
            </table>
            <br/>
            <table class="wp-list-table widefat fixed bookmarks">
            	<thead>
                <tr>
                	<th>Facebook</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                	<td><iframe src="//www.facebook.com/plugins/likebox.php?href=https%3A%2F%2Fwww.facebook.com%2Fpages%2FDnessCarKey%2F77553779916&amp;width=240&amp;height=260&amp;show_faces=true&amp;colorscheme=light&amp;stream=false&amp;border_color=%23f9f9f9&amp;header=false&amp;appId=215419415167468" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:240px; height:260px;" allowTransparency="true"></iframe>
                    </td>
                </tr>
                </tbody>
            </table>
            <br/>
            
        </td>
    </tr>
</table>
</div>
