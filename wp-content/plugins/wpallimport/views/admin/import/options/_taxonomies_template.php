<?php
	$post_taxonomies = array_diff_key(get_taxonomies_by_object_type(array($post_type), 'object'), array_flip(array('category', 'post_tag', 'post_format')));
	if ( ! empty($post_taxonomies)): ?>
	<tr>
		<td colspan="3">
			<fieldset class="optionsset">
				<legend>Custom Taxonomies</legend>
				<?php foreach ($post_taxonomies as $ctx): ?>
				<table>
					<tr>
						<td>
							<div class="post_taxonomy">
								<div class="col2" style="width:35%;">
									<nobr><?php echo $ctx->labels->name ?></nobr>
								</div>												
								<div class="col2" style="width:65%;">
									<ol class="sortable no-margin">
										<?php if (!empty($post['post_taxonomies'][$ctx->name])):												
												$taxonomies_hierarchy = json_decode($post['post_taxonomies'][$ctx->name]);												
												if (!empty($taxonomies_hierarchy) and is_array($taxonomies_hierarchy)): $i = 0; foreach ($taxonomies_hierarchy as $cat) { $i++;
													if (is_null($cat->parent_id) or empty($cat->parent_id))
													{
														?>
														<li id="item_<?php echo $i; ?>">
															<div class="drag-element"><input type="checkbox" class="assign_post" <?php if ($cat->assign): ?>checked="checked"<?php endif; ?>/><input type="text" class="widefat" value="<?php echo $cat->xpath; ?>"/></div><?php if ($i>1):?><a href="javascript:void(0);" class="icon-item remove-ico"></a><?php endif;?>
															<?php echo reverse_taxonomies_html($taxonomies_hierarchy, $cat->item_id, $i); ?>
														</li>								    
														<?php
													}
												}; else:?>
												<li id="item_1"><div class="drag-element"><input type="checkbox" class="assign_post" checked="checked"/><input type="text" class="widefat" value=""/></div></li>								    
												<?php endif;
											  else: ?>
									    <li id="item_1"><div class="drag-element"><input type="checkbox" class="assign_post" checked="checked"/><input type="text" class="widefat" value=""/></div></li>								    
										<?php endif; ?>
									</ol>								
									<input type="hidden" class="hierarhy-output" name="post_taxonomies[<?php echo $ctx->name ?>]" value="<?php echo esc_attr($post['post_taxonomies'][$ctx->name]) ?>"/>									
									<div class="delim">		
										<label><?php _e('Separated by', 'pmxi_plugin'); ?></label>
										<input type="text" class="small tax_delim" maxlength="1" value="<?php echo (!empty($ctx->delim)) ? $ctx->delim : ',' ?>" />							
										<a href="javascript:void(0);" class="icon-item add-new-ico">Add more</a>														
									</div>
								</div>												
							</div>
						</td>
					</tr>
				</table>
				<?php endforeach; ?>										
			</fieldset>
		</td>
	</tr>
	<?php endif;
?>