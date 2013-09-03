<?php 

function pmxi_admin_notices() {
	// notify user if history folder is not writable
	$uploads = wp_upload_dir();

	if ( ! @is_dir($uploads['basedir'] . '/wpallimport_history') or ! @is_writable($uploads['basedir'] . '/wpallimport_history')) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: History folder %s must be writable for the plugin to function properly. Please deactivate the plugin, set proper permissions to the folder and activate the plugin again.', 'pmxi_plugin'),
					PMXI_Plugin::getInstance()->getName(),
					$uploads['basedir'] . '/wpallimport_history'
			) ?>
		</p></div>
		<?php
	}

	// notify user
	if (!PMXI_Plugin::getInstance()->getOption('dismiss') and strpos($_SERVER['REQUEST_URI'], 'pmxi-admin') !== false) {
		?>
		<div class="updated"><p>
			<?php printf(
					__('Welcome to WP All Import. We hope you like it. Please send all support requests and feedback to <a href="mailto:support@soflyy.com">support@soflyy.com</a>.<br/><br/><a href="javascript:void(0);" id="dismiss">dismiss</a>', 'pmxi_plugin')										
			) ?>
		</p></div>
		<?php
	}

	$input = new PMXI_Input();
	$messages = $input->get('pmxi_nt', array());
	if ($messages) {
		is_array($messages) or $messages = array($messages);
		foreach ($messages as $type => $m) {
			in_array((string)$type, array('updated', 'error')) or $type = 'updated';
			?>
			<div class="<?php echo $type ?>"><p><?php echo $m ?></p></div>
			<?php 
		}
	}
}