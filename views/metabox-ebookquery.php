<?php
global $post;

$custom	= get_post_custom($post->ID);

$author	= $custom["expub_authors"][0];
$category	= $custom["expub_category"][0];
$startdate	= $custom["expub_startdate"][0];
$enddate	= $custom["expub_enddate"][0];
$postids	= $custom["expub_postids"][0];

$authArgs = array(
	'show_option_all'         => 'Any Author', // string
	'hide_if_only_one_author' => false, // string
	'orderby'                 => 'display_name',
	'order'                   => 'ASC',
	'include'                 => null, // string
	'exclude'                 => null, // string
	'multi'                   => false,
	'show'                    => 'display_name',
	'echo'                    => false,
	'selected'                => $author,
	'include_selected'        => false,
	'name'                    => 'expub_authors', // string
	'id'                      => null, // integer
	'class'                   => null, // string 
	'blog_id'                 => $GLOBALS['blog_id'],
	'who'                     => 'authors' // string)
);

$authdropdown = wp_dropdown_users($authArgs);

$catsArgs = array(
	'show_option_all'    => 'All categories',
	'orderby'            => 'ID', 
	'order'              => 'ASC',
	'show_count'         => 0,
	'hide_empty'         => 0, 
	'child_of'           => 0,
	'exclude'            => NULL,
	'echo'               => 0,
	'selected'           => $category,
	'hierarchical'       => 0, 
	'name'               => 'expub_category',
	'id'                 => NULL,
	'class'              => 'postform',
	'depth'              => 0,
	'tab_index'          => 0,
	'taxonomy'           => 'category',
	'hide_if_empty'      => false ); 

$catdropdown = wp_dropdown_categories($catsArgs);
?>

<p><label>Author:</label><br/>
	<?php echo $authdropdown; ?></p>

<p><label>Category:</label><br/>
	<?php echo $catdropdown; ?></p>

<p><label>Start Date:</label><br/>
	<input type="text" name="expub_start_date" value="<?php echo $startdate; ?>" /></p>

<p><label>End Date:</label><br/>
	<input type="text" name="expub_end_date" value="<?php echo $enddate; ?>" /></p>

<p><label>Post IDs (comma separated):</label><br/>
	<input type="text" name="expub_post_ids" value="<?php echo $postids; ?>" /></p>