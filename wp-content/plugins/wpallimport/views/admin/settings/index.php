<form class="settings" method="post" action="<?php echo $this->baseUrl ?>" enctype="multipart/form-data">

<h2><?php _e('WP All Import Settings', 'pmxi_plugin') ?></h2>
<hr />
<?php if ($this->errors->get_error_codes()): ?>
	<?php $this->error() ?>
<?php endif ?>
	
<h3><?php _e('Saved Templates', 'pmxi_plugin') ?></h3>
<?php $templates = new PMXI_Template_List(); $templates->getBy()->convertRecords() ?>
<?php if ($templates->total()): ?>
	<table>
		<?php foreach ($templates as $t): ?>
			<tr>
				<td><input id="template-<?php echo $t->id ?>" type="checkbox" name="templates[]" value="<?php echo $t->id ?>" /></td>
				<td><label for="template-<?php echo $t->id ?>"><?php echo $t->name ?></label></td>
			</tr>
		<?php endforeach ?>
	</table>
	<p class="submit-buttons">
		<?php wp_nonce_field('delete-templates', '_wpnonce_delete-templates') ?>		
		<input type="submit" class="button-primary" name="delete_templates" value="<?php _e('Delete Selected', 'pmxi_plugin') ?>" />
		<input type="submit" class="button-primary" name="export_templates" value="<?php _e('Export Selected', 'pmxi_plugin') ?>" />
	</p>	
<?php else: ?>
	<em><?php _e('There are no templates saved', 'pmxi_plugin') ?></em>
<?php endif ?>
	<p>
		<input type="hidden" name="is_templates_submitted" value="1" />
		<input type="file" name="template_file"/>
		<input type="submit" class="button-primary" name="import_templates" value="<?php _e('Import Templates', 'pmxi_plugin') ?>" />
	</p>
</form>
<br />

<form name="settings" method="post" action="<?php echo $this->baseUrl ?>">
<h3><?php _e('History', 'pmxi_plugin') ?></h3>
<div><?php printf(__('Store maximum of %s of the most recent files imported. 0 = unlimited', 'pmxi_plugin'), '<input class="small-text" type="text" name="history_file_count" value="' . esc_attr($post['history_file_count']) . '" />') ?></div>
<div><?php printf(__('Store imported file history for a maximum of %s of days. 0 = unlimited', 'pmxi_plugin'), '<input class="small-text" type="text" name="history_file_age" value="' . esc_attr($post['history_file_age']) . '" />') ?></div>
<h3><?php _e('Your server setting', 'pmxi_plugin') ?></h3>
<div><?php printf(__('upload_max_filesize %s', 'pmxi_plugin'), ini_get('upload_max_filesize')) ?></div>
<div><?php printf(__('post_max_size %s', 'pmxi_plugin'), ini_get('post_max_size')) ?></div>
<div><?php printf(__('max_execution_time %s', 'pmxi_plugin'), ini_get('max_execution_time')) ?></div>
<div><?php printf(__('max_input_time %s', 'pmxi_plugin'), ini_get('max_input_time')) ?></div>

<h3><?php _e('Recurring & Scheduled Imports', 'pmxi_plugin') ?></h3>
<div><?php printf(__('Cron Job Secret Key %s', 'pmxi_plugin'), '<input type="text" name="cron_job_key" value="' . esc_attr($post['cron_job_key']) . '"/>') ?></div>
<div class="note"><?php printf(__('Consider this option if you want specified import task to be run automatically on regular basis. Usage example:')) ?></div>
<div class="note"><?php printf(__('create a cron job that runs processor every two minutes <strong>wget "'.home_url().'?import_key='. esc_attr($post['cron_job_key']) .'&import_id=11&action=processing"</strong>', 'pmxi_plugin')) ?></div>
<div class="note"><?php printf(__('run trigger in scheduling period, for example every 24 hours <strong>wget "'.home_url().'?import_key='. esc_attr($post['cron_job_key']) .'&import_id=11&action=trigger"</strong>', 'pmxi_plugin')) ?></div>
<h3><?php _e('Import Settings', 'pmxi_plugin') ?></h3>
<div><?php printf(__('Chunk maximum size %s (Kb)', 'pmxi_plugin'), '<input type="text" name="chunk_size" value="' . esc_attr($post['chunk_size']) . '"/>') ?></div>
<p>
	<input type="hidden" name="legacy_special_character_handling" value="0"/>
	<?php printf(__('%s <label for="legacy_special_character_handling">Use legacy special character handling</label>', 'pmxi_plugin'), '<input type="checkbox" name="legacy_special_character_handling" id="legacy_special_character_handling" value="1"  style="position:relative; top:-2px;" '. (($post['legacy_special_character_handling']) ? 'checked="checked"' : '') .'/>') ?>
	<a href="#help" class="help" title="<?php _e('By default wpallimport uses htmlspecialchars() to encode html tags in csv feeds. If this option is enabled that wpallimport will use htmlentities() function.', 'pmxi_plugin') ?>">?</a>
</p>
<p>
	<input type="hidden" name="case_sensitive" value="0"/>
	<?php printf(__('%s <label for="case_sensitive">Enable case-sensitivity mode</label>', 'pmxi_plugin'), '<input type="checkbox" name="case_sensitive" id="case_sensitive" value="1"  style="position:relative; top:-2px;" '. (($post['case_sensitive']) ? 'checked="checked"' : '') .'/>') ?>
	<a href="#help" class="help" title="<?php _e('', 'pmxi_plugin') ?>">?</a>
</p>
<p>
	<input type="hidden" name="pingbacks" value="0"/>
	<?php printf(__('%s <label for="pingbacks">Enable WP_IMPORTING</label>', 'pmxi_plugin'), '<input type="checkbox" name="pingbacks" id="pingbacks" value="1"  style="position:relative; top:-2px;" '. (($post['pingbacks']) ? 'checked="checked"' : '') .'/>') ?>
	<a href="#help" class="help" title="<?php _e('Avoid triggering pingback.', 'pmxi_plugin') ?>">?</a>
</p>
<p>
	<?php printf(__('%s <label for="session_mode_default">Session Mode (default)</label>', 'pmxi_plugin'), '<input type="radio" name="session_mode" id="session_mode_default" value="default"  style="position:relative; top:-2px;" '. (($post['session_mode'] == 'default') ? 'checked="checked"' : '') .'/>') ?> <br>
	<?php printf(__('%s <label for="session_mode_files">Session Mode (files)</label>', 'pmxi_plugin'), '<input type="radio" name="session_mode" id="session_mode_files" value="files"  style="position:relative; top:-2px;" '. (($post['session_mode'] == 'files') ? 'checked="checked"' : '') .'/>') ?> <br>
	<?php printf(__('%s <label for="session_mode_database">Session Mode (database)</label>', 'pmxi_plugin'), '<input type="radio" name="session_mode" id="session_mode_database" value="database"  style="position:relative; top:-2px;" '. (($post['session_mode'] == 'database') ? 'checked="checked"' : '') .'/>') ?>		
</p>
<p class="submit-buttons">
	<?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
	<input type="hidden" name="is_settings_submitted" value="1" />
	<input type="submit" class="button-primary" value="Save Settings" />
</p>

</form>