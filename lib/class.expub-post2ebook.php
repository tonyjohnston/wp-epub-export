<?php
/** 
 * Allows any post to be designated a downloadable eBook
 */
class expub_post2ebook{
	const LANG = 'en';

	public function __construct(){
		add_action('add_meta_boxes', array( &$this, 'add_expub_metabox' ) );
		add_action('save_post', array(&$this, 'save_details'));
	}

	/**
	* Adds the meta box container
	*/
	public function add_expub_metabox(){
		add_meta_box( 
			'expub_metabox',
			__( 'exPub eBook Export', self::LANG ),
			array( &$this, 'render_meta_box_content' ),
			'post' ,
			'side',
			'default'
		);
	}
	
	/**
	* Render Meta Box content
	*/
	public function render_meta_box_content() {
		global $post;

		if($post->post_type == 'post' || $post->post_type == 'page'){
			// Main ebook
			include_once(WP_PLUGIN_DIR.'/expub-epub-export/views/metabox-ebookpost.php');
		}
	}

	/**
	* Saves custom field values 
	*/
	public function save_details(){
		if($_POST['expub_update_post_values']==1){
			global $post;
			update_post_meta($post->ID,'expub_postisebook',$_POST['expub_post_is_ebook']);
			update_post_meta($post->ID,'expub_mustlogin',$_POST['expub_reader_must_login']);
		}
	}
	
	/**
	 * Add download link to post 
	 */
	public function add_download_link($content){
		global $post;
		
		$permalink	= add_query_arg( 'expub_epub_send', $post->ID, get_permalink($post->ID));
		$imgpath		= WP_PLUGIN_URL.'/expub-epub-export/views/images/expub-button128x46.png';
		
		$ebooklink = '<div style="float:right;margin:0 0 15px 15px;"><a href="'.$permalink.'">
			<img src="'.$imgpath.'" alt="Download this post as an ebook" height="46" width="128" /></a></div>';
		
		$custom = get_post_custom($post->ID);
		
		if(is_single() && ($custom['expub_postisebook'] || $post->post_type == 'expub_ebook')){
			if(is_user_logged_in() || (!$custom['expub_usermustlogin'])){
				$content = $ebooklink.$content;
			}			
		}
		return $content;
	}
}
?>
