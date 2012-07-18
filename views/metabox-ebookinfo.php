<?php
global $post;

$custom = get_post_custom($post->ID);

$title			= $custom["expub_covertitle"][0];
$author		= $custom["expub_coverauthor"][0];
$coverdate	= $custom["expub_coverdate"][0];
$covertext		= $custom["expub_covertext"][0];
$coverimage	= $custom["expub_coverimage"][0];
$coversize		= $custom["expub_coversize"][0];
$publisher		= $custom["expub_coverpublisher"][0];
$publisherurl	= $custom["expub_coverpublisherurl"][0];
$copyright		= $custom["expub_covercopyright"][0];
$customize	= $custom["expub_customizetitlepage"][0];

$cust_checked = ($customize == 1 ? 'checked="checked"' : '');
?>

<p><label>eBook Title:</label><br/>
	<input type="hidden" name="update_expub_custom" value="1" />
	<input type="text" name="expub_title" value="<?php echo $title; ?>" /></p>

<p><label>eBook Author:</label><br/>
	<input type="text" name="expub_author" value="<?php echo $author; ?>" /></p>

<p><label>eBook Cover Date:<br/>
	<input type="text" name="expub_coverdate" value="<?php echo $coverdate; ?>" /></p>

<p><label>Customize title page with user info:</label><br/>
	<input type="checkbox" name="expub_customize_title_page" value="1" <?php echo $cust_checked ?> />

<p><label>Extra text for title page:</label><br/>
	<textarea cols="50" rows="5" name="expub_cover_text"><?php echo $covertext; ?></textarea></p>

<p><label>Cover image: </label><br/>
	<select name="expub_cover_image">
		<option value="" disabled="disabled">- Select Image -
		<?php

			$dirPath = dir(ABSPATH.'wp-content/plugins/expub-epub-export/views/images/covers-s');
			while (($file = $dirPath->read()) !== false){
				if(!is_dir($file)){
					echo "<option value=\"" . trim($file) . "\"".($coverimage==trim($file)?' selected="selected"':'').">" . $file . "</option>\n";
				}
			}
			$dirPath->close();
		?>
	</select><br/>You can specify an image from those built in, or you can use the "Custom Cover Image" setting to the right to choose something from the media library or upload a custom file.</p>
	
<p><label>Cover size</label><br/>
	<select name="expub_cover_size">
		<option value="s"<?php if($coversize == 's') echo ' selected="selected"';?>>Small (50-100kb)</option>
		<option value="m"<?php if($coversize == 'm') echo ' selected="selected"';?>>Medium (1-2mb)</option>
		<option value="l"<?php if($coversize == 'l') echo ' selected="selected"';?>>Large (5-10mb)</option>
	</select>

<p><label>Publisher name:</label><br/>
	<input type="text" name="expub_publisher" value="<?php echo trim($publisher); ?>" /></p>

<p><label>Publisher URL:</label><br/>
	<input type="text" name="expub_publisher_url" value="<?php echo trim($publisherurl); ?>" /></p>

<p><label>eBook copyright information:</label><br/>
	<textarea cols="50" rows="5" name="expub_copyright_info"><?php echo trim($copyright); ?></textarea></p>
