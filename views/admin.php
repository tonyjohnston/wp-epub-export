<div class="wrap">
	<div id="icon-tools" class="icon32" ><br/></div>
	<h2>ePub Export</h2>
	
<?php 

	// If the page has just been updated, assume that the data in forms is correct and try to export an ebook.
	if($_GET['settings-updated']==true){
		echo '<div id="message" class="updated fade">Your file is ready. <a href="?page=expub_fields&send_expub_epub=true">Get it now</a>.</div>'; 
	}
?>
	<form method="post" action="options.php">
	<?php settings_fields('expub_fields_optiongroup'); ?>
		<input type ="hidden" name="send_expub" value="true" />
	<table class="form-table">			
		<tr valign="top">
			<td colspan="2">
			<h2>eBook Meta Data: information to be embedded in the eBook file</h2>
			<hr/>
			</td>
		</tr>
		<tr valign="top">
			<td>
			<p>eBook Title</p>
			</td>
			<td>
				<p><input type="text" name="expub_title" value=" <?php echo get_option('expub_title'); ?>" /></p>
			</td>
		</tr>			
		<tr valign="top">
			<td>
			<p>eBook Author</p>
			</td>
			<td>
				<p><input type="text" name="expub_author" value=" <?php echo get_option('expub_author'); ?>" /></p>
			</td>
		</tr>
		<tr valign="top">
			<td>
			<p>eBook Cover Date</p>
			</td>
			<td>
				<p><input type="text" name="expub_coverdate" value=" <?php echo get_option('expub_coverdate'); ?>" /></p>
			</td>
		</tr>			<tr valign="top">
			<td>
			<p>Extra text for cover page</p>
			</td>
			<td>
				<p><textarea cols="50" rows="5" name="expub_covertext"><?php echo (get_option('expub_covertext')); ?></textarea></p>
			</td>
		</tr>			
		<tr valign="top">
			<td valign="top">Cover image</td>
			<td valign="top"><p>
				<select name="expub_cover_image">
					<option value="" disabled="disabled">- Select Image -
					<?php
					
						$dirPath = dir(ABSPATH.'wp-content/plugins/expub-epub-export/views/images/covers-s');
						while (($file = $dirPath->read()) !== false){
							if(!is_dir($file)){
								echo "<option value=\"" . trim($file) . "\"".(get_option('expub_cover_image')==trim($file)?' selected="selected"':'').">" . $file . "</option>\n";
							}
						}
						$dirPath->close();
					?>
				</select>
					&nbsp;Cover size &nbsp;
				<select name="expub_cover_size">
					<option value="l"<?php if(get_option('expub_cover_size') == 'l') echo ' selected="selected"';?>>Large (5-10mb)</option>
					<option value="m"<?php if(get_option('expub_cover_size') == 'm') echo ' selected="selected"';?>>Medium (1-2mb)</option>
					<option value="s"<?php if(get_option('expub_cover_size') == 's') echo ' selected="selected"';?>>Small (50-100kb)</option>
				</select>
				<input type="hidden" name="expub_cover_path" value="<?=__DIR__.'/coverimages/'?>" />
			</td>
		</tr>
		<tr valign="top">
			<td>
			<p>Publisher name</p>
			</td>
			<td>
				<p><input type="text" name="expub_publisher" value=" <?php echo get_option('expub_publisher'); ?>" /></p>
			</td>
		</tr>			
		<tr valign="top">
			<td>
			<p>Publisher URL</p>
			</td>
			<td>
				<p><input type="text" name="expub_publisher" value=" <?php echo get_option('expub_publisher'); ?>" /></p>
			</td>
		</tr>
		<tr valign="top">
			<td>
			<p>eBook copyright information</p>
			</td>
			<td>
				<p><textarea cols="50" rows="5" name="expub_copyright_info"><?php echo (get_option('expub_copyright_info')); ?></textarea></p>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2">
			<h2>Include articles according to the following criteria:</h2>
			<hr />
			</td>
		</tr>			
		<tr valign="top">
			<td valign="top">Author(s)</td>
		<td valign="top">
			<?php wp_dropdown_users($authArgs); ?>
		</td>
		</tr>
		<tr valign="top">
			<td valign="top">Categor(y/ies)</td>
		<td valign="top">
			<?php wp_dropdown_categories($catsArgs); ?>
		</td>
		</tr>
		<tr valign="top">
			<td>
			<p>Start Date</p>
			</td>
			<td>
				<p><input type="text" name="expub_start_date" value=" <?php echo get_option('expub_start_date'); ?>" /></p>
			</td>
		</tr>
		<tr valign="top">
			<td>
			<p>End Date</p>
			</td>
			<td>
				<p><input type="text" name="expub_end_date" value=" <?php echo get_option('expub_end_date'); ?>" /></p>
			</td>
		</tr>	
	</table>

	<p class="submit">
		<input type="submit" class="button-primary" value="Save Changes" />
	</p>
	</form>
</div>

