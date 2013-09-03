<tr>
	<td colspan="3" style="border-bottom:1px solid #ccc;">
		<fieldset class="optionsset" style="text-align:center;">
			<legend>Custom Fields</legend>													
			<table class="form-table custom-params" style="max-width:none; border:none;">
			<thead>
				<tr>
					<td><?php _e('Name', 'pmxi_plugin') ?></td>
					<td><?php _e('Value', 'pmxi_plugin') ?></td>					
				</tr>
			</thead>
			<tbody>				
				<?php if (!empty($post['custom_name'])):?>
					<?php foreach ($post['custom_name'] as $i => $name): ?>
						<tr class="form-field">
							<td><input type="text" name="custom_name[]"  value="<?php echo esc_attr($name) ?>" /></td>
							<td class="action remove">
								<textarea name="custom_value[]"><?php echo esc_html($post['custom_value'][$i]) ?></textarea>
								<a href="#remove"></a>
							</td>														
						</tr>
					<?php endforeach ?>
				<?php else: ?>
					<tr class="form-field">
						<td><input type="text" name="custom_name[]"  value="" /></td>
						<td class="action remove">
							<textarea name="custom_value[]"></textarea>
							<a href="#remove"></a>
						</td>													
					</tr>
				<?php endif;?>
				<tr class="form-field template">
					<td><input type="text" name="custom_name[]" value="" /></td>
					<td class="action remove">
						<textarea name="custom_value[]"></textarea>
						<a href="#remove"></a>
					</td>												
				</tr>
				<tr>
					<td colspan="2"><a href="#add" title="<?php _e('add', 'pmxi_plugin')?>" class="action add-new-custom"><?php _e('Add more', 'pmxi_plugin') ?></a></td>												
				</tr>
			</tbody>
			</table>
			<select class="existing_meta_keys">
				<option value=""><?php _e('Existing Custom Fields...','pmxi_plugin');?></option>
				<?php				
				$hide_fields = array('_wp_page_template', '_edit_lock', '_edit_last', '_wp_trash_meta_status', '_wp_trash_meta_time');
				if (!empty($meta_keys) and $meta_keys->count()):
					foreach ($meta_keys as $meta_key) { if (in_array($meta_key['meta_key'], $hide_fields) or strpos($meta_key['meta_key'], '_wp') === 0) continue;
						?>
						<option value="<?php echo $meta_key['meta_key'];?>"><?php echo $meta_key['meta_key'];?></option>
						<?php
					}
				endif;
				?>
			</select>
			<br/>			
		</fieldset>
		<br>
	</td>
</tr>