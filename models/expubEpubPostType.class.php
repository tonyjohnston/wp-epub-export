<?php

abstract class Expub_Epub_Post_Type extends Group_Buying_Model {
	private static $post_types_to_register = array();
	private static $taxonomies_to_register = array();

	protected $ID;
	protected $post;
	protected $post_meta = array();



	/* =============================================================
	 * Class methods
	 * ============================================================= */

 	/**
	 * Tracks all the post types registered by sub-classes, and hooks into WP to register them
	 *
	 * @static
	 * @param string $post_type
	 * @param string $singular
	 * @param string $plural
	 * @param array $args
	 * @return void
	 */
	protected static function register_post_type( $post_type, $singular = '', $plural = '', $args = array() ) {
		self::add_register_post_types_hooks();

		if ( !$singular ) {
			$singular = $post_type;
		}
		if ( !$plural ) {
			$plural = $singular.'s';
		}
		$defaults = array(
			'show_ui' => true,
			'public' => true,
			'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions'),
			'label' => self::__($plural),
			'labels' => self::post_type_labels($singular, $plural),
		);
		$args = wp_parse_args($args, $defaults);
		if ( isset(self::$post_types_to_register[$post_type]) ) {
			if ( self::DEBUG ) {
				error_log(self::__('Attempting to re-register post type: '.$post_type));
			}
			return;
		}
		self::$post_types_to_register[$post_type] = $args;
	}

	/**
	 * Generate a set of labels for a post type
	 *
	 * @static
	 * @param string $singular
	 * @param string $plural
	 * @return array All the labels for the post type
	 */
	private static function post_type_labels($singular, $plural) {
		return array(
			'name' => self::__($plural),
			'singular_name' => self::__($singular),
			'add_new' => self::__('Add ' . $singular),
			'add_new_item' => self::__('Add New ' . $singular),
			'edit_item' => self::__('Edit ' . $singular),
			'new_item' => self::__('New ' . $singular),
		    'all_items' => self::__($plural),
			'view_item' => self::__('View ' . $singular),
			'search_items' => self::__('Search ' . $plural),
			'not_found' => self::__('No ' . $plural . ' found'),
			'not_found_in_trash' => self::__('No ' . $plural . ' found in Trash'),
			'menu_name' => self::__($plural)
		);
	}

	/**
	 * Add the hooks necessary to register post types at the right time
	 * @return void
	 */
	private static function add_register_post_types_hooks() {
		static $registered = FALSE; // only do it once
		if ( !$registered ) {
			$registered = TRUE;
			add_action( 'init', array(get_class(), 'register_post_types'));
			add_action( 'template_redirect', array(get_class(), 'context_fixer') );
			add_filter( 'body_class', array(get_class(), 'body_classes') );
		}
	}

	/**
	 * Register each queued up post type
	 * @return void
	 */
	public static function register_post_types() {
		foreach ( self::$post_types_to_register as $post_type => $args ) {
			$args = apply_filters('gbs_register_post_type_args-'.$post_type, $args);
			$args = apply_filters('gbs_register_post_type_args', $args, $post_type);
			register_post_type($post_type, $args);
		}
	}

	/**
	 * is_home should be false if on a managed post type
	 * @return void
	 */
	public static function context_fixer() {
		if ( in_array(get_query_var( 'post_type' ), array_keys(self::$post_types_to_register)) ) {
			global $wp_query;
			$wp_query->is_home = false;
		}
	}

	/**
	 * If a managed post type is queried, add the post type to body classes
	 * @param array $c
	 * @return array
	 */
	public static function body_classes( $c ) {
		$query_post_type = get_query_var('post_type');
		if ( in_array($query_post_type, array_keys(self::$post_types_to_register)) ) {
			$c[] = $query_post_type;
			$c[] = 'type-' . $query_post_type;
		}
		return $c;
	}



 	/**
	 * Tracks all the taxonomies registered by sub-classes, and hooks into WP to register them
	 *
	 * @static
	 * @param string $post_type
	 * @param string $singular
	 * @param string $plural
	 * @param array $args
	 * @return void
	 */
	protected static function register_taxonomy( $taxonomy, $post_types, $singular = '', $plural = '', $args = array() ) {
		self::add_register_taxonomies_hooks();

		if ( !$singular ) {
			$singular = $taxonomy;
		}
		if ( !$plural ) {
			$plural = $singular.'s';
		}
		$defaults = array(
			'hierarchical' => TRUE,
			'labels' => self::taxonomy_labels($singular, $plural),
			'show_ui' => TRUE,
			'query_var' => TRUE,
		);
		$args = wp_parse_args($args, $defaults);
		$args = apply_filters('gb_register_taxonomy_args-'.$taxonomy, $args, $post_types, $singular, $plural );
		$args = apply_filters('gb_register_taxonomy_args', $args, $taxonomy, $post_types, $singular, $plural );
		if ( isset(self::$taxonomies_to_register[$taxonomy]) ) {
			if ( self::DEBUG ) {
				error_log(self::__('Attempting to re-register taxonomy: '.$taxonomy));
			}
			return;
		}
		self::$taxonomies_to_register[$taxonomy] = array(
			'post_types' => $post_types,
			'args' => $args
		);
	}

	private static function taxonomy_labels($singular, $plural) {
		return array(
			'name' => self::__($plural),
			'singular_name' => self::__($singular),
			'search_items' => self::__('Search '.$plural),
			'popular_items' => self::__('Popular '.$plural),
			'all_items' => self::__('All '.$plural),
			'parent_item' => self::__('Parent '.$singular),
			'parent_item_colon' => self::__('Parent '.$singular.':'),
			'edit_item' => self::__('Edit '.$singular),
			'update_item' => self::__('Update '.$singular),
			'add_new_item' => self::__('Add New '.$singular),
			'new_item_name' => self::__('New '.$singular.' Name'),
			'menu_name' => self::__($plural),
		);
	}

	/**
	 * Add the hooks necessary to register post types at the right time
	 * @return void
	 */
	private static function add_register_taxonomies_hooks() {
		static $registered = FALSE; // only do it once
		if ( !$registered ) {
			$registered = TRUE;
			add_action( 'init', array(get_class(), 'register_taxonomies'));
		}
	}

	/**
	 * Register each queued up taxonomy
	 *
	 * @static
	 * @return void
	 */
	public static function register_taxonomies() {
		foreach ( self::$taxonomies_to_register as $taxonomy => $data ) {
			register_taxonomy($taxonomy, $data['post_types'], $data['args']);
		}
	}


	/* =============================================================
	 * Instance methods
	 * ============================================================= */
	/*
	 * Multiton Design Pattern
	 * ------------------------------------------------------------- */
	final protected function __clone() {
		// cannot be cloned
		trigger_error(__CLASS__.' may not be cloned', E_USER_ERROR);
	}

	final protected function __sleep() {
		// cannot be serialized
		trigger_error(__CLASS__.' may not be serialized', E_USER_ERROR);
	}

	/**
	 * @static
	 * @abstract
	 * @param int $id
	 * @return Expub_Epub_Post_Type|NULL
	 */
	public static abstract function get_instance( $id = 0 );

 	/**
	 * @param int $id The ID of the post
	 */
	protected function __construct( $id ) {
		$this->ID = $id;
		$this->refresh();
		$this->register_update_hooks();
	}

	public function get_id() {
		return $this->ID;
	}

	public function __destruct() {
		$this->unregister_update_hooks();
	}

	/**
	 * Update with fresh data from the database
	 * @return void
	 */
	protected function refresh() {
		$this->load_post();
		$this->load_post_meta();
	}

	/**
	 * Update the post
	 *
	 * @return void
	 */
	protected function load_post() {
		$this->post = get_post($this->ID);
	}

	protected function save_post() {
		wp_update_post($this->post);
	}

	/**
	 * Update post meta
	 * @return void
	 */
	protected function load_post_meta() {
		$meta = get_post_custom($this->ID);
		// get_post_meta unserializes, but get_post_custom does not
		foreach ( $meta as $key => $value ) {
			$this->post_meta[$key] = array_map('maybe_unserialize', $value);
		}
	}

	/**
	 * Watch for updates to the post or its meta
	 *
	 * @return void
	 */
	protected function register_update_hooks() {
		add_action('save_post', array($this, 'post_updated'), 1000, 2);
		add_action('updated_post_meta', array($this, 'post_meta_updated'), 1000, 4);
		add_action('added_post_meta', array($this, 'post_meta_updated'), 1000, 4);
	}

	/**
	 * I'm dying, don't talk to me.
	 *
	 * @return void
	 */
	protected function unregister_update_hooks() {
		remove_action('save_post', array($this, 'post_updated'), 1000, 2);
		remove_action('updated_post_meta', array($this, 'post_meta_updated'), 1000, 4);
		remove_action('added_post_meta', array($this, 'post_meta_updated'), 1000, 4);
	}

	/**
	 * A post was updated. Refresh if necessary.
	 *
	 * @param int $post_id The ID of the post that was updated
	 * @param object $post
	 * @return void
	 */
	public function post_updated( $post_id, $post ) {
		if ( $post_id == $this->ID ) {
			$this->refresh();
		}
	}

	/**
	 * A post's meta was updated. Refresh if necessary.
	 *
	 * @param int $meta_id
	 * @param int $post_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 * @return void
	 */
	public function post_meta_updated( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( $post_id == $this->ID ) {
			$this->refresh();
		}
	}

	/**
	 * Get the post object
	 *
	 * @return object
	 */
	public function get_post() {
		return $this->post;
	}


	/**
	 * Set the title of the post and save
	 *
	 * @param string $title
	 * @return void
	 */
	public function set_title( $title ) {
		$this->post->post_title = $title;
		$this->save_post();
	}

	/**
	 * Saves the given meta key/value pairs to the post.
	 *
	 * By default, keys will be unique per post. Override in a child class to change this.
	 *
	 * @param array $meta An associative array of meta keys and their values to save
	 * @return void
	 */
	public function save_post_meta( $meta = array() ) {
		foreach ( $meta as $key => $value ) {
			update_post_meta($this->ID, $key, $value);
			// $this->post_meta should automatically refresh at this point
		}
	}

	public function add_post_meta( $meta = array(), $unique = FALSE ) {
		foreach ( $meta as $key => $value ) {
			add_post_meta($this->ID, $key, $value, $unique);
			// $this->post_meta should automatically refresh at this point
		}
	}

	public function delete_post_meta( $meta ) {
		foreach ( $meta as $key => $value ) {
			delete_post_meta( $this->ID, $key, $value );
		}
	}

	/**
	 * Returns post meta about the post
	 *
	 * @param string|NULL $meta_key A string indicating which meta key to retrieve, or NULL to return all keys
	 * @param bool $single TRUE to return the first value, FALSE to return an array of values
	 * @return string|array
	 */
	public function get_post_meta(  $meta_key = NULL, $single = TRUE ) {
		if ( $meta_key !== NULL ) { // get a single field
			if ( isset( $this->post_meta[$meta_key] ) ) {
				if ( $single ) {
					return $this->post_meta[$meta_key][0];
				} else {
					return $this->post_meta[$meta_key];
				}
			} else {
				return '';
			}
		} else {
			return $this->post_meta;
		}
	}

	public static function find_by_meta( $post_type, $meta = array() ) {
		global $wpdb;
		$sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p ";
		$args = array();

		$meta_count = 1;
		$join = '';
		foreach ( $meta as $key => $value ) {
			$join .= "INNER JOIN {$wpdb->postmeta} pmeta%d ON p.ID = pmeta%d.post_id ";
			$args[] = $meta_count;
			$args[] = $meta_count;
			$meta_count++;
		}

		$where = 'WHERE p.post_type = %s ';
		$args[] = $post_type;

		$meta_count = 1;
		foreach ( $meta as $key => $value ) {
			$where .= 'AND pmeta%d.meta_key = %s AND pmeta%d.meta_value = %s ';
			$args[] = $meta_count;
			$args[] = $key;
			$args[] = $meta_count;
			$args[] = $value;
			$meta_count++;
		}

		$query = $wpdb->prepare( $sql.$join.$where, $args );
		$column = $wpdb->get_col( $query );
		return $column;
	}
}
