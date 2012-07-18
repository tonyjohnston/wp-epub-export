<?php
global $post;

$custom = get_post_custom($post->ID);
$checked = ($custom['expub_postisebook'][0] ? 'checked="checked"' : '');
$checked2 = ($custom['expub_mustlogin'][0] ? 'checked="checked"' : '');

?>

<p>Allow readers to download this article as a self-contained eBook in the ePub format.</p>
<p><input id="expub_post_is_ebook" type="checkbox" name="expub_post_is_ebook" value="1" <?php echo $checked ?>/>
	<label for="expub_post_is_ebook"> Allow</label></p>
	<p><input id="expub_reader_must_login" type="checkbox" name="expub_reader_must_login" value="1" <?php echo $checked2 ?>/>
	<label for="expub_reader_must_login"> Logged in readers only</label></p>
	<input type="hidden" name="expub_update_post_values" value="1" />
	<hr/>

<p>This <?php echo $post->post_type ?> appears in the following eBooks:
