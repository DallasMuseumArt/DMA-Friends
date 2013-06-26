<form class="template <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
	<h2>
		<?php  if ($this->isWizard): ?>
			<?php _e('Import XML/CSV - Step 3: Template Builder', 'pmxi_plugin') ?>
		<?php else: ?>
			<?php _e('Edit Import Template', 'pmxi_plugin') ?>
		<?php endif ?>
	</h2>

	<?php if ($this->errors->get_error_codes()): ?>
		<?php $this->error() ?>
	<?php endif ?>

	<h3>Drag-and-drop an element from the right to the left to build your template</h3>

	<table class="layout">
		<tr>
			<td class="left">
				<h3>Post Title</h3>
				<div style="width:100%">
					<input id="title" class="widefat" type="text" name="title" value="<?php echo esc_attr($post['title']) ?>" />
				</div>

				<h3>
					Post Content
				</h3>
				<div id="poststuff">
					<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">

						<?php the_editor($post['content']) ?>
						<table id="post-status-info" cellspacing="0">
							<tbody>
							<tr>
								<td id="wp-word-count"></td>
								<td class="autosave-info">
									<span id="autosave">&nbsp;</span>
								</td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>
				<p>
					<span class="header-option">
						<input type="hidden" name="is_keep_linebreaks" value="0" />
						<input type="checkbox" id="is_keep_linebreaks" name="is_keep_linebreaks" value="1" <?php echo $post['is_keep_linebreaks'] ? 'checked="checked"' : '' ?> style="position:relative; top:-3px;"/>
						<label for="is_keep_linebreaks"><?php _e('Keep line breaks from XML', 'pmxi_plugin') ?></label> <br>
						<input type="hidden" name="is_leave_html" value="0" />
						<input type="checkbox" id="is_leave_html" name="is_leave_html" value="1" <?php echo $post['is_leave_html'] ? 'checked="checked"' : '' ?> style="position:relative; top:-3px;"/>
						<label for="is_leave_html"><?php _e('Decode HTML entities with <b>html_entity_decode</b>', 'pmxi_plugin') ?></label><a class="help" href="#help" original-title="If HTML code is showing up in your posts, use this option. You can also use <br /><br /><i>[html_entity_decode({my/xpath})]</i><br /><br /> or <br /><br /><i>[htmlentities({my/xpath})]</i><br /><br /> to decode or encode HTML in your file.">?</a>
					</span>
				</p>
				<hr>
				<p style="clear:both;">
					<?php wp_nonce_field('template', '_wpnonce_template'); ?>
					<input type="hidden" name="is_submitted" value="1" />
					<div class="input">
						<input type="checkbox" id="save_template_as" name="save_template_as" value="1" <?php echo $post['save_template_as'] ? 'checked="checked"' : '' ?> style="position:relative; top:-2px;"/> <label for="save_template_as">Save template as:</label> &nbsp;<input type="text" name="name" title="<?php _e('Save Template As...', 'pmxi_plugin') ?>" style="vertical-align:middle; font-size:13px;" value="<?php echo esc_attr($post['name']) ?>" />
					</div>
				</p>

				<?php $templates = new PMXI_Template_List() ?>
				<div class="load-template">
					<span>Load existing template: </span>
					<select name="load_template">
						<option value=""><?php _e('Load Template...', 'pmxi_plugin') ?></option>
						<?php foreach ($templates->getBy()->convertRecords() as $t): ?>
							<option value="<?php echo $t->id ?>"><?php echo $t->name ?></option>
						<?php endforeach ?>
					</select>
				</div>

				<p>
					<span class="submit-buttons" style="float:right;">
						<?php if ($this->isWizard):?>
							<a href="<?php echo add_query_arg('action', 'element', $this->baseUrl) ?>" class="back"><?php _e('Back', 'pmxi_plugin') ?></a>
						<?php else: ?>
							<a href="<?php echo remove_query_arg('id', remove_query_arg('action', $this->baseUrl)); ?>" class="back"><?php _e('Back', 'pmxi_plugin') ?></a>
						<?php endif; ?>
						<a href="#preview" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only large_button preview" title="<?php _e('Preview Post', 'pmxi_plugin') ?>"><?php _e('Preview', 'pmxi_plugin') ?></a>
						<input type="submit" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only large_button" value="<?php _e( ($this->isWizard) ? 'Next' : 'Update', 'pmxi_plugin') ?>" />
					</span>
				</p>
			</td>
			<?php if ($this->isWizard or $this->isTemplateEdit): ?>
				<td class="right template-sidebar">
					<?php $this->tag() ?>
				</td>
			<?php endif ?>
		</tr>
	</table>
</form>
