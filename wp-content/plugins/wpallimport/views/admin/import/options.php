<?php

	if (!function_exists('reverse_taxonomies_html')) {
		function reverse_taxonomies_html($post_taxonomies, $item_id, &$i){
			$childs = array();
			foreach ($post_taxonomies as $j => $cat) if ($cat->parent_id == $item_id) { $childs[] = $cat; }

			if (!empty($childs)){
				?>
				<ol>
				<?php
				foreach ($childs as $child_cat){
					$i++;
					?>
		            <li id="item_<?php echo $i; ?>">
		            	<div class="drag-element"><input type="checkbox" class="assign_post" <?php if ($child_cat->assign): ?>checked="checked"<?php endif; ?>/><input class="widefat" type="text" value="<?php echo $child_cat->xpath; ?>"/></div><a href="javascript:void(0);" class="icon-item remove-ico"></a>
		            	<?php echo reverse_taxonomies_html($post_taxonomies, $child_cat->item_id, $i); ?>
		            </li>
					<?php
				}
				?>
				</ol>
				<?php
			}
		}
	}
?>
<?php $custom_types = get_post_types(array('_builtin' => false), 'objects'); ?>
<input type="hidden" id="selected_post_type" value="<?php echo (!empty($post['custom_type'])) ? $post['custom_type'] : '';?>">
<input type="hidden" id="selected_type" value="<?php echo (!empty($post['type'])) ? $post['type'] : '';?>">
<h2>
	<?php if ($this->isWizard): ?>
		<?php _e('Import XML/CSV - Step 4: Options', 'pmxi_plugin') ?>
	<?php else: ?>
		<?php _e('Edit Import Options', 'pmxi_plugin') ?>
	<?php endif ?>
</h2>
<h3>Click the appropriate tab to choose the type of posts to create.</h3>

<?php if ($this->errors->get_error_codes()): ?>
	<?php $this->error() ?>
<?php endif ?>
<table class="layout">
<tr>
	<td class="left">
		<?php if ($this->isWizard): ?>
			<?php if ($is_loaded_template && !$load_options): ?>
				<form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
					<span class="load-options">
						Load Options...&nbsp;<input type="checkbox" name="load_options" /><a class="help" href="#help" original-title="Load options from selected template.">?</a>
					</span>
				</form>
				<?php elseif ($is_loaded_template): ?>
				<form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
					<span class="load-options">
						Reset Options...&nbsp;<input type="checkbox" name="reset_options" /><a class="help" href="#help" original-title="Reset options.">?</a>
					</span>
				</form>
			<?php endif; ?>
		<?php endif; ?>
		<div id="pmxi_tabs">
		    <ul>
		        <li><a href="#tabs-1">Posts</a></li>
		        <li><a href="#tabs-2">Pages</a></li>
				<?php if (count($custom_types)): ?>
					<?php foreach ($custom_types as $key => $ct): ?>
						<li><a href="#tabs-<?php echo $key; ?>"><?php echo $ct->labels->name ?></a></li>
					<?php endforeach ?>
				<?php endif ?>
				<!-- WooCommerce Add-On -->
				<?php
					if (class_exists('PMWI_Plugin')):
					?>
						<li><a href="#tabs-woo-product">WooCommerce Add-On</a></li>
					<?php
					endif;
				?>
		    </ul>

		    <!-- Post Options -->

		    <div id="tabs-1"> <!-- Basic -->
			    <form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
			    	<input type="hidden" name="type" value="post"/>
			    	<input type="hidden" name="custom_type" value=""/>
					<div class="post-type-options">
						<table class="form-table" style="max-width:none;">
							<?php
								$post_type = 'post';
								$entry = 'post';
								include( 'options/_main_options_template.php' );
								include( 'options/_taxonomies_template.php' );
								include( 'options/_categories_template.php' );
								include( 'options/_custom_fields_template.php' );
								include( 'options/_featured_template.php' );
								include( 'options/_author_template.php' );
								include( 'options/_reimport_template.php' );
								include( 'options/_scheduling_template.php' );
							?>
						</table>
					</div>

					<?php include( 'options/_buttons_template.php' ); ?>

				</form>
			</div>

			<!-- Page Options -->

			<div id="tabs-2">
				<form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
					<input type="hidden" name="type" value="page"/>
					<input type="hidden" name="custom_type" value=""/>
					<div class="post-type-options">
						<table class="form-table" style="max-width:none;">

							<?php include( 'options/_main_options_template.php' ); ?>

							<tr>
								<td align="center" width="33%">
									<label><?php _e('Page Template', 'pmxi_plugin') ?></label> <br>
									<select name="page_template" id="page_template">
										<option value='default'><?php _e('Default', 'pmxi_plugin') ?></option>
										<?php page_template_dropdown($post['page_template']); ?>
									</select>
								</td>
								<td align="center" width="33%">
									<label><?php _e('Parent Page', 'pmxi_plugin') ?></label> <br>
									<?php wp_dropdown_pages(array('post_type' => 'page', 'selected' => $post['parent'], 'name' => 'parent', 'show_option_none' => __('(no parent)', 'pmxi_plugin'), 'sort_column'=> 'menu_order, post_title',)) ?>
								</td>
								<td align="center" width="33%">
									<label><?php _e('Order', 'pmxi_plugin') ?></label> <br>
									<input type="text" class="" name="order" value="<?php echo esc_attr($post['order']) ?>" />
								</td>
							</tr>
							<?php
								$post_type = 'post';
								$entry = 'page';
								include( 'options/_taxonomies_template.php' );
								include( 'options/_featured_template.php' );
								include( 'options/_reimport_template.php' );
								include( 'options/_scheduling_template.php' );
							?>
						</table>
					</div>

					<?php include( 'options/_buttons_template.php' ); ?>

				</form>
			</div>

			<!-- Custom Post Types -->

			<?php if (count($custom_types)): ?>
				<?php foreach ($custom_types as $key => $ct): ?>
					<div id="tabs-<?php echo $key;?>">
						<form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
							<input type="hidden" name="custom_type" value="<?php echo $key; ?>"/>
							<input type="hidden" name="type" value="post"/>
							<div class="post-type-options">
								<table class="form-table" style="max-width:none;">
									<?php
										$post_type = $entry = $key;
										include( 'options/_main_options_template.php' );
										include( 'options/_taxonomies_template.php' );
										include( 'options/_categories_template.php' );
										include( 'options/_custom_fields_template.php' );
										include( 'options/_featured_template.php' );
										include( 'options/_author_template.php' );
										include( 'options/_reimport_template.php' );
										include( 'options/_scheduling_template.php' );
									?>
								</table>
							</div>

							<?php include( 'options/_buttons_template.php' ); ?>

						</form>
					</div>
				<?php endforeach ?>
			<?php endif ?>

			<!-- WooCommerce Add-On -->
			<?php
				if (class_exists('PMWI_Plugin')):
				?>
					<div id="tabs-woo-product">
						<form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
							<input type="hidden" name="custom_type" value="woo_product"/>
							<input type="hidden" name="type" value="post"/>
							<div class="post-type-options">
								<table class="form-table" style="max-width:none;">
									<?php
																				
										$post_type = $entry = 'woo_product';		

										$woo_controller = new PMWI_Admin_Import();										
										$woo_controller->index();
									
										include( 'options/_taxonomies_template.php' );
										include( 'options/_categories_template.php' );
										include( 'options/_custom_fields_template.php' );
										include( 'options/_featured_template.php' );
										include( 'options/_author_template.php' );
										include( 'options/_reimport_template.php' );
										include( 'options/_scheduling_template.php' );

									?>
								</table>
							</div>

							<?php include( 'options/_buttons_template.php' ); ?>

						</form>
					</div>					
				<?php
				endif;
			?>

		</div>
	</td>
	<?php if ($this->isWizard or $this->isTemplateEdit): ?>
		<td class="right options">
			<?php $this->tag() ?>
		</td>
	<?php endif ?>
</tr>
</table>