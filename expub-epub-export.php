<?php
/* 
	Plugin Name: exPub: ePub Export
	Plugin URI: http://www.expub.org
	Description: Extract series of posts as formatted, chaptered, indexed, and bound ebooks in the ePub format. Compatible with most mobile platforms and ebookstores, including Apple's iBooks, Google eBooks for Android, and Amazon's Kindle Store.
	Author: Tony Johnston
	Version: .1
	Author URI: http://www.expub.org/author
 * 
 */
define ('EXP_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
define ('EXP_URL', plugins_url( '', __FILE__) );
define ('EXP_RESOURCES', plugins_url( 'resources/', __FILE__) );
define ('EXP_LIBRARIES', EXP_PATH.'/lib/' );

define( 'EXP_SUPPORTED_WP_VERSION', version_compare(get_bloginfo('version'), '3.0', '>=') );
define( 'EXP_SUPPORTED_PHP_VERSION', version_compare( phpversion(), '5.0', '>=') );

// PHP 5 compat check + WordPress 3.0 check
if ( EXP_SUPPORTED_WP_VERSION && EXP_SUPPORTED_PHP_VERSION ) {
	expub_epub_load();
	do_action('expub_epub_load');
} else {
	add_action('admin_head', 'expub_fail_notices');
	function expub_fail_notices() {
		if ( !EXP_SUPPORTED_WP_VERSION ) {
			echo '<div class="error"><p><strong>exPub ePub Export for Wordpress Plugin</strong> requires WordPress 3.0 or higher. Please upgrade WordPress or deactivate the exPub Plugin.</p></div>';
		}
		if ( !GBS_SUPPORTED_PHP_VERSION ) {
			echo '<div class="error"><p><strong>exPub ePub Export for Wordpress Plugin</strong> requires PHP 5.0 or higher. Talk to your Web host about not living in the past.</p></div>';
		}
	}
}

function expub_epub_load() {
	if ( class_exists('Expub_Epub') ) {
		return; // already loaded, or a name collision
	}
	
	require_once(EXP_LIBRARIES.'/htmLawed.php');
	require_once(EXP_LIBRARIES.'/EPub.php');
	require_once(EXP_LIBRARIES.'/class.expub-post2ebook.php');
	
	// payment processors
	foreach (glob(EXP_PATH.'/controllers/payment_processors/*.class.php') as $file_path)
	{
		require_once($file_path);
	}
	do_action('exp_register_processors');

	/**
	* WordPress hooks
	*/
	add_action('init', 'expub_custom_post_type_init');
	add_action('admin_init', 'expub_admin_init');
	add_action('init', 'expub_epub_send');
	add_action('manage_posts_custom_column',  'expub_custom_columns');
	add_action('save_post', 'expub_save_details');

	// Filters
	add_filter('post_updated_messages', 'expub_post_updated_messages');
	add_filter('manage_edit-expub_ebook_columns', 'expub_edit_columns');
	add_filter('the_content','expub_download_link');

	register_activation_hook( __FILE__, 'expub_rewrite_flush' );
}
	

/**
 * Register a custom post type to store eBook export details 
 */
function expub_custom_post_type_init() {
	$labels = array(
		'name' => __( 'eBooks' ),
		'singular_name' => __( 'eBook' ),
		'add_new' => __('Define New eBook','expub_ebook'),
		'add_new_item' => __('Add new eBook'),
		'edit_item' => __('Edit eBook Definition'),
		'new_item' => __('New eBook'),
		'view_item' => __('View eBook Page'),
		'search_items' => __('Search eBook Definitions'),
		'not_found' => __('No matching definitions found'),
		'not_found_in_trash' => __('No eBooks found in trash'),
		'parent_item_colon',''
	);
	
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => true,
		'has_archive' => true,
		'description' => 'Define an eBook using date ranges, authors, categories, or a combination of all, then export it as an eBook for mobile devices and mobile eBook stores such as Apple\'s iBooks',
		'rewrite' => array('slug' => 'examples','with_front' => true),
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'supports' => array('title', 'editor','thumbnail')
	);
	
	register_post_type( 'expub_ebook',$args);
	
	// Register "taxonomy" which is a fancy word for category.
	register_taxonomy("eBook Type", 
			array("expub_ebook"), 
			array("hierarchical" => true, "label" => "eBook Types", "singular_label" => "eBook Type", "rewrite" => true));
	
	// Register a taxonomy for individual books
	$taxonomy	= 'expub_ebook_titles';
	$object_type	= array('post','page');
	
	$args = array(
		'label'	=> 'eBook Title',
		'labels'	=> array(
			'name' 					=> __('eBook Titles'),				
			'singular_name' 				=> __('eBook Title'),				
			'search_items'				=> __('Search Titles'),				
			'popular_items' 				=> __('Popular Titles'),				
			'all_items'					=> __('All Titles'), 					
			'parent_item'				=> __('Parent Title'),				
			'parent_item_colon'			=> __('Parent Title:'),				
			'edit_item'					=> __('Edit Title'),					
			'update_item'				=> __('Update Title'),				
			'add_new_item'				=> __('Add new Title'),				
			'new_item_name'			=> __('New Title text'),				
			'separate_items_with_commas'	=> __('Separate Titles with commas'),	
			'add_or_remove_items'		=> __('Add or remove Titles'),			
			'choose_from_most_used'		=> __('Choose from most used Titles'),	
			'menu_name'				=> __('eBook Titles')			
		),	
		'public'				=> true,	
		'show_in_nav_menus'		=> true,	
		'show_ui'				=> true,	
		'show_tagcloud'			=> false,	
		'hierarchical'			=> true,	
		'update_count_callback'	=> null,
		'query_var'				=> null,
		'rewrite'				=> array(	
				'slug'			=> __('ebook'),	
				'with_front'		=> true,			
				'hierarchical'	=> true
		),		
		'capabilities'			=> array( 		
			'manage_terms'		=> true,		
			'edit_terms'		=> true,		
			'delete_terms'		=> true,		
			'assign_terms'		=> true		
		),
	);
	
	register_taxonomy($taxonomy, $object_type, $args);
}

/**
 *  Add custom post type meta boxes
 */
function expub_admin_init(){
	add_meta_box('expub_ebook_info', __('eBook Details'), 'expub_ebook_info', 'expub_ebook', 'normal', 'default');
	add_meta_box('expub_ebook_query', __('Define eBook Critera'), 'expub_ebook_query', 'expub_ebook', 'side', 'low');
	add_action('add_meta_boxes', 'expub_rename_featured_image', 10, 2);
}

/*
 *  Rename Featured Image metabox.
 */
function expub_rename_featured_image($post_type, $post) {
	global $wp_meta_boxes; 
	
	$wp_meta_boxes['expub_ebook']['side']['low']['postimagediv']['title'] = 'Custom Cover Image';
}

/**
 * If the post is being edited by an admin, show the exPub meta box. 
 */
if ( is_admin() ){
	add_action('load-post.php', 'call_expub2post' );
}

/*
 *  Meta box for ebook info
 */
function expub_ebook_info(){
	require_once(ABSPATH.'wp-content/plugins/expub-epub-export/views/metabox-ebookinfo.php');
}

/*
 * Meta box for ebook query fields
 */
function expub_ebook_query() {
	require_once(ABSPATH.'wp-content/plugins/expub-epub-export/views/metabox-ebookquery.php');
}

/*
 *  Hook into the save post action to save our custom fields
 */
function expub_save_details(){
	global $post;
	$titleTaxonomy = 'expub_ebook_titles';

	if($post->post_type == 'expub_ebook' && $_POST['update_expub_custom'] == 1){
		// eBook info
		update_post_meta($post->ID, "expub_covertitle",		$_POST["expub_title"]);
		update_post_meta($post->ID, "expub_coverauthor",		$_POST["expub_author"]);
		update_post_meta($post->ID, "expub_coverdate",		$_POST["expub_coverdate"]);
		update_post_meta($post->ID, "expub_coverimage",		$_POST["expub_cover_image"]);
		update_post_meta($post->ID, "expub_coversize",		$_POST["expub_cover_size"]);
		update_post_meta($post->ID, "expub_covertext",		$_POST["expub_cover_text"]);
		update_post_meta($post->ID, "expub_coverpublisher",	$_POST["expub_publisher"]);
		update_post_meta($post->ID, "expub_coverpublisherurl",	$_POST["expub_publisher_url"]);
		update_post_meta($post->ID, "expub_covercopyright",	$_POST["expub_copyright_info"]);
		update_post_meta($post->ID, "expub_customizetitlepage",	$_POST["expub_customize_title_page"]);

		// eBook query
		update_post_meta($post->ID, "expub_authors",		$_POST["expub_authors"]);
		update_post_meta($post->ID, "expub_category",		$_POST["expub_category"]);
		update_post_meta($post->ID, "expub_startdate",	$_POST["expub_start_date"]);
		update_post_meta($post->ID, "expub_enddate",		$_POST["expub_end_date"]);
		update_post_meta($post->ID, "expub_postids",		$_POST["expub_post_ids"]);

		// Update an existing term or insert it if it doesn't already exist
		$term = wp_insert_term( $_POST['expub_title'], $titleTaxonomy, $args = array() );

		// Insert or overwrite the term relationship
		if (!is_wp_error($term)){
			wp_set_object_terms( $post->ID, $term->ID, $titleTaxonomy, false );
		}
	}
  
}

/*
 *  Flush rewrite rules to handle newly activated plugin
 */
function expub_rewrite_flush() 
{
    expub_custom_post_type_init();
    flush_rewrite_rules();
}

//add filter to ensure the text Book, or book, is displayed when user updates a book 
function expub_post_updated_messages( $messages ) {
  global $post, $post_ID;

  $messages['expub'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('eBook updated. <a href="%s">View eBook</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('eBook updated.'),
    5 => isset($_GET['revision']) ? sprintf( __('eBook restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('eBook published. <a href="%s">View eBook</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('eBook saved.'),
    8 => sprintf( __('eBook submitted. <a target="_blank" href="%s">Preview eBook</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('eBook scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview eBook</a>'),
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Book draft updated. <a target="_blank" href="%s">Preview eBook</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );

  return $messages;
}

/*
 *  Add admin menu for creating one-off ebooks based on specific criteria
 */
if (is_admin()) {
	add_action('admin_menu', 'expub_fields_menu');
	add_action('admin_init', 'expub_fields_register');
}

/*
 *  Admin menu page details --
 */
function expub_fields_menu() {
	add_management_page('ePub Export', 'ePub Export', 8, 'expub_fields', 'expub_fields_options');
}

/*
 *  Add options
 */
function expub_fields_register() {
	// eBook details
	register_setting('expub_fields_optiongroup', 'expub_title');
	register_setting('expub_fields_optiongroup', 'expub_author');
	register_setting('expub_fields_optiongroup', 'expub_coverdate');
	register_setting('expub_fields_optiongroup', 'expub_covertext');
	register_setting('expub_fields_optiongroup', 'expub_cover_image');
	register_setting('expub_fields_optiongroup', 'expub_cover_size');
	register_setting('expub_fields_optiongroup', 'expub_publisher');
	register_setting('expub_fields_optiongroup', 'expub_publisher_url');
	register_setting('expub_fields_optiongroup', 'expub_copyright_info');
	register_setting('expub_fields_optiongroup', 'expub_customize_title');
	// eBook query fields
	register_setting('expub_fields_optiongroup', 'expub_authors');
	register_setting('expub_fields_optiongroup', 'expub_category');
	register_setting('expub_fields_optiongroup', 'expub_start_date');
	register_setting('expub_fields_optiongroup', 'expub_end_date');
	register_setting('expub_fields_optiongroup', 'expub_post_ids');
}

/*
 *  Add menu page
 */
function expub_fields_options() { 

	$authArgs = array(
		'show_option_all'         => true, // string
		'show_option_none'        => null, // string
		'hide_if_only_one_author' => false, // string
		'orderby'                 => 'display_name',
		'order'                   => 'ASC',
		'include'                 => null, // string
		'exclude'                 => null, // string
		'multi'                   => false,
		'show'                    => 'display_name',
		'echo'                    => true,
		'selected'                => get_option('expub_authors'),
		'include_selected'        => false,
		'name'                    => 'expub_authors', // string
		'id'                      => null, // integer
		'class'                   => null, // string 
		'blog_id'                 => $GLOBALS['blog_id'],
		'who'                     => 'authors' // string)
	);
			
	$catsArgs = array(
		'show_option_all'    => true,
		'show_option_none'   => false,
		'orderby'            => 'ID', 
		'order'              => 'ASC',
		'show_count'         => 0,
		'hide_empty'         => 1, 
		'child_of'           => 0,
		'exclude'            => NULL,
		'echo'               => 1,
		'selected'           => get_option('expub_category'),
		'hierarchical'       => 0, 
		'name'               => 'expub_category',
		'id'                 => NULL,
		'class'              => 'postform',
		'depth'              => 0,
		'tab_index'          => 0,
		'taxonomy'           => 'category',
		'hide_if_empty'      => false ); 
		
	require_once('views/admin.php');
}


function expub_epub_send(){
	if(!empty($_GET['expub_epub_send'])){
		expub_epub_create($_GET['expub_epub_send']);
	}
}

/**
 * Create the array of columns
 * 
 * @param array $columns
 * @return string 
 */
function expub_edit_columns($columns){
	$columns = array(
		"cb" => "<input type=\"checkbox\" />",
		"title" => "eBook Title",
		"authors" => "Author(s)",
		"category" => "Category",
		"start" => "Start date",
		"end" => "End date",
		"postids" => "Post IDs"
	);
 
	return $columns;
}

/**
 * Echo the column values 
 * 
 * @param array $column 
 */
function expub_custom_columns($column){
	
	$custom = get_post_custom();

	switch ($column) {
		case "authors":
			echo $custom["expub_authors"][0];
			break;
		case "category":
			echo $custom["expub_category"][0];
			break;
		case "start":
			echo $custom['expub_startdate'][0];
			break;
		case "end":
			echo $custom['expub_enddate'][0];
			break;
		case "postids":
			echo $custom['expub_postids'][0];
			break;
  }
}

/**
 * Create the eBook
 * 
 * @global type $wpdb
 * @global type $current_user
 * @param type $postID
 * @return boolean 
 */

function expub_epub_create($postID){
	global $wpdb;
	
	// Get all post data
	$post	= wp_get_single_post($postID);
	$custom	= get_post_custom($post->ID);
	
	// Check to see if this post is allowed to be exported as an eBook
	if($custom['expub_postisebook'] || $post->post_type == 'expub_ebook'){
		if($custom['expub_mustlogin']){
			if(!is_user_logged_in()){
				// User must be lgged in to download
				return FALSE;
			}
		}
		
	}else{
		// Not designated as an ebook by author
		return FALSE;
	}
	
	if($post->post_type == 'post' || $post->post_type == 'page'){
		
		// Use defaults if this is not a post_type of eBook
		$coverTitle		= $post->post_title;
		$coverAuthor		= get_author_name($post->post_author);
		$coverDate		= $post->post_date;
		$coverText		= get_option('expub_covertext');
		
		// Publisher info from plugin settings
		$coverPublisher		= get_option('expub_publisher');
		$coverPublisherUrl	= get_option('expub_publisher_url');
		$coverCopyright		= get_option('expub_copyright_info');
		$coverCustomize	= get_option('expub_customize_title');
		
		// Set query condition
		$queryPostIDs	= $post->ID;
		$queryLimit	= "\n LIMIT 1";
		
	}elseif($post->post_type == 'expub_ebook'){
		
		// Title page info
		$coverTitle		= $custom["expub_covertitle"][0];
		$coverAuthor		= $custom["expub_coverauthor"][0];
		$coverDate		= $custom["expub_coverdate"][0];
		$coverText		= $custom["expub_covertext"][0];
		$coverPublisher		= $custom["expub_coverpublisher"][0];
		$coverPublisherUrl	= $custom["expub_coverpublisherurl"][0];
		$coverCopyright		= $custom["expub_covercopyright"][0];
		$coverCustomize	= $custom["expub_customizetitlepage"][0];
		
		// Query for multi-article ebooks
		$queryAuthor	= $custom['expub_authors'][0];
		$queryCategory	= $custom['expub_category'][0];
		$queryStartDate	= $custom['expub_startdate'][0];
		$queryEndDate	= $custom['expub_enddate'][0];
		$queryPostIds	= $custom['expub_postids'][0];
	}else{
		//This is not an eBook
		return FALSE;
	}
	
	// Cover image
	if(has_post_thumbnail($post->ID)){
		$coverPath = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'large');
		$coverPath = (substr($coverPath[0],strpos($coverPath[0],'/',7)+1));
	}else{
		// If the post has a coverimage designated from the built-in options
		if(!empty($custom["expub_coverimage"][0]) &! empty($custom["expub_coversize"][0])){
			$coverImage	= $custom['expub_coverimage'][0];
			$coverSize	= $custom["expub_coversize"][0];
		}else{
			// Or user the default as set in plugin settings
			$coverImage	= get_option('expub_cover_image');
			$coverSize	= get_option('expub_cover_size');

			// If it's still empty, use some hard coded values
			if(empty($coverImage) || empty($coverSize)){
				$coverImage = 'Leather.png';
				$coverSize	= 'm';
			}
		}
		// Build the cover path
		$coverPath		= WP_PLUGIN_DIR.'/expub-epub-export/views/images/covers-'.$coverSize.'/'.$coverImage;
	}
	
	
	
	/**
	 * Set up WHERE clause for retreiving matching posts, even if it's just 
	 * a single post from the $_GET 
	 */
	
	// If filtering by Author
	if(!empty($queryAuthor)){
		$qAuth = "\nAND p.post_author = ".mysql_real_escape_string($queryAuthor)."\n";
	}
	
	// If filtering by date
	if(!empty($queryStartDate)){
		
		$queryStartDate = date('Y-m-d',strtotime($queryStartDate));
		
		if(empty($queryEndDate)){
			$queryEndDate = date('Y-m-d');
		}else{
			$queryEndDate = date('Y-m-d', strtotime($queryEndDate));
		}
		
		if($queryStartDate && $queryEndDate){
			$qDates = "\nAND p.post_date BETWEEN '".mysql_real_escape_string($queryStartDate)." AND ".mysql_real_escape_string($queryEndDate)."'\n";
		}
	}
	
	// If filtering by category
	if(!empty($queryCategory)){
		$qCat = "\nAND tt.taxonomy = 'category' and t.term_id=".mysql_real_escape_string($queryCategory)."\n";
	}
	
	// if filtering by Post IDs
	if(!empty($queryPostIDs)){
		$qPosts = "\nAND p.ID IN (".mysql_real_escape_string($queryPostIDs).")\n";
	}
	
	// Query DB for all matching records:
	$query = 'SELECT p.ID,  
				p.guid as url, 
				p.post_name as basedir,
				p.post_author as authorid,
				p.post_title as title, 
				t.name as category,
				p.post_excerpt as excerpt, 
				p.post_content as body, 
				DATE_FORMAT(p.post_date, "%W %M %Y") as pubdate
		FROM '.$wpdb->posts.' p
			LEFT JOIN wp_term_relationships tr ON p.ID = tr.object_id 
			LEFT JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			LEFT JOIN wp_terms t ON t.term_id = tt.term_id
		WHERE 1 '.$qAuth.$qDates.$qCat.$qPosts.'			
		ORDER BY p.post_date ASC'.$queryLimit;

	$series = $wpdb->get_results($query);
	
	// Now create the book
	$book = new EPub();
	
	// Establish ebook headers
	$content_start = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
			"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n".
			"\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n".
			"<html xmlns=\"http://www.w3.org/1999/xhtml\">\n".
			"<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n".
			"<link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\" />\n";
	$content_epub .= '<title>'.$coverTitle."</title>\n</head>\n<body>\n";
	
	if($coverCustomize){
		global $current_user;
		get_currentuserinfo();
		$madefor  = "<p style=\"text-align:center\">This eBook publication has been specially prepared for:</p>";
		$madefor .= "<p style=\"text-align:center\">$current_user->user_firstname $current_user->user_lastname<br />$current_user->display_name $current_user->user_email</p><p align=\"center\">by</p>";
	}
		
	// Create the cover page
	$thisebook = $content_start.$content_epub . '<h2 style="text-align:center">'.$coverTitle."</h2>\n".
			$madefor."<h3 style=\"text-align:center\">".get_bloginfo('name')."<p style=\"text-align:center\">".$coverDate."</p>".
			"</h3>\n<p style=\"font-size:small;color:#666;text-align:center;\">$coverText <a href=\"".
			get_bloginfo('url')."\"><br />Visit us online for more eBooks like this one".
			"</a></p><p style=\"text-align:center;font-style:italic;font-size:x-small;color:#666;\">Published by $coverPublisher<br/>$coverPublisherUrl</p></body>\n</html>\n";
	$book->addChapter("Title Page", "thisebook.html", $thisebook);
	
	// Add chapters
	foreach($series as $article){	

		// Clean up HTML unless inside cdata
		$config["valid_xhtml"] = 1; 
		$config["cdata"] = 3;
		
		// Combine the raw html of the content with predefined headers
		$title = '<title>'.$article->title."</title>\n</head>\n<body>\n<h1 class=\"title_page\"><br/>".$article->title."</h1><hr />";
		// $article->body = $content_start.$title.htmLawed($article->body,$config).'</body></html>';
		$article->body = $content_start.$title.wpautop($article->body).'</body></html>';

		$book->addChapter($article->title, $article->basedir.'.html', $article->body, false, EPub::EXTERNAL_REF_ADD, $article->basdir);
	}	
	
	// Settings from the series
	$book->setTitle($coverTitle);
	$book->setIdentifier(get_bloginfo(url)."/?expub_epub_send=".$post->ID, EPub::IDENTIFIER_URI);
	$book->setDescription($article->excerpt);
	$book->setDate(time($coverDate));
	$book->setSourceURL(get_bloginfo(url)."/?expub_epub_send=".$post->ID);

	// Move all of these into the plugin
	$book->setLanguage("en");
	$book->setAuthor($coverAuthor, get_bloginfo('name')); 
	$book->setPublisher($coverPublisher,$coverPublisherUrl);
	$book->setRights($coverCopyright);

	// Cover image to use on the book
	$book->setCoverImage($coverPath);
	
	// CSS hard coded. Should use user settings 
	$css = file_get_contents(ABSPATH.'wp-content/plugins/expub-epub-export/views/styles.css');

	// Name of the css file that will reside inside the final .epub file
	$book->addCSSFile("style.css", "css1", $css);	
	
	// Finalize the book (no futher changes allowed) and use http headers to send a zipped epub file to the client
	$book->finalize();
	$book->sendBook($coverTitle);
}

/**
 * Calls the class on the post edit screen
 */
function call_expub2post() 
{
    return new expub_post2ebook();
}

/**
 * Calls the method inside expub_post2ebook to display a link to the ebook download.
 * 
 * @param string $content
 * @return string $content
 */
function expub_download_link($content){
	$expub = new expub_post2ebook();
	return $expub->add_download_link($content);
}

 


?>