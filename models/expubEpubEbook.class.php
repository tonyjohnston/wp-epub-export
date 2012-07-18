<?php
 
class Group_Buying_Deal extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_deal';
	const REWRITE_SLUG = 'deals';
	const LOCATION_TAXONOMY = 'gb_location';
	const CAT_TAXONOMY = 'gb_category';
	const TAG_TAXONOMY = 'gb_tag';

	const DEAL_STATUS_OPEN = 'open';
	const DEAL_STATUS_PENDING = 'pending';
	const DEAL_STATUS_CLOSED = 'closed';
	const DEAL_STATUS_UNKNOWN = 'unknown';

	const NO_EXPIRATION_DATE = -1;
	const NO_MAXIMUM = -1;
	const MAX_LOCATIONS = 5;

	private static $instances = array();

	private static $meta_keys = array(
		'amount_saved' => '_amount_saved', // string
		'capture_before_expiration' => '_capture_before_expiration', // bool
		'dynamic_price' => '_dynamic_price', // array
		'expiration_date' => '_expiration_date', // int
		'fine_print' => '_fine_print', // string
		'highlights' => '_highlights', // string
		'max_purchases' => '_max_purchases', // int
		'max_purchases_per_user' => '_max_purchases_per_user', // int
		'merchant_id' => '_merchant_id', // int
		'min_purchases' => '_min_purchases', // int
		'number_of_purchases' => '_number_of_purchases', // int
		'price' => '_base_price', // float
		'tax' => '_tax', // float
		'taxable' => '_taxable', // bool
		'taxmode' => '_taxmode', // string
		'shipping' => '_shipping', // float
		'shippable' => '_shippable', // string
		'shipping_dyn_price' => '_shipping_dyn_price', // array
		'shipping_mode' => '_shipping_mode', // string
		'rss_excerpt' => '_rss_excerpt', // string
		'used_voucher_serials' => '_used_voucher_serials', // array
		'value' => '_value', // string
		'voucher_expiration_date' => '_voucher_expiration_date', // string
		'voucher_how_to_use' => '_voucher_how_to_use', // string
		'voucher_id_prefix' => '_voucher_id_prefix', //string
		'voucher_locations' => '_voucher_locations', // array
		'voucher_logo' => '_voucher_logo', // int
		'voucher_map' => '_voucher_map', // string
		'voucher_serial_numbers' => '_voucher_serial_numbers', // array
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

	
	public static function init() {
		// register Deal post type
		$post_type_args = array(
			'has_archive' => TRUE,
			'menu_position' => 4,
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => FALSE,
			),
			'supports' => array('title', 'editor', 'thumbnail', 'comments', 'custom-fields', 'revisions', 'post-formats'),
			'menu_icon' => GB_URL . '/resources/img/deals.png',
			'hierarchical' => TRUE,
		);
		self::register_post_type(self::POST_TYPE, 'Deal', 'Deals', $post_type_args);

		// register Locations taxonomy
		$singular = 'Location';
		$plural = 'Locations';
		$taxonomy_args = array(
			'rewrite' => array(
				'slug' => 'locations',
				'with_front' => TRUE,
				'hierarchical' => TRUE,
			),
		);
		self::register_taxonomy(self::LOCATION_TAXONOMY, array(self::POST_TYPE), $singular, $plural, $taxonomy_args);
		
		// register Locations taxonomy
		$singular = 'Category';
		$plural = 'Categories';
		$taxonomy_args = array(
			'rewrite' => array(
				'slug' => 'deal-categories',
				'with_front' => TRUE,
				'hierarchical' => TRUE,
			),
		);
		self::register_taxonomy(self::CAT_TAXONOMY, array(self::POST_TYPE), $singular, $plural, $taxonomy_args);
		
		// register Locations taxonomy
		$singular = 'Tag';
		$plural = 'Tags';
		$taxonomy_args = array(
			'hierarchical' => FALSE,
			'rewrite' => array(
				'slug' => 'deal-tags',
				'with_front' => TRUE,
				'hierarchical' => FALSE,
			),
		);
		self::register_taxonomy(self::TAG_TAXONOMY, array(self::POST_TYPE), $singular, $plural, $taxonomy_args);
	}

	protected function __construct( $id ) {
		parent::__construct($id);
	}

	/**
	 * @static
	 * @param int $id
	 * @return Group_Buying_Deal
	 */
	public static function get_instance( $id = 0 ) {
		if ( !$id ) {
			return NULL;
		}
		if ( !isset(self::$instances[$id]) || !self::$instances[$id] instanceof self ) {
			self::$instances[$id] = new self($id);
		}
		if ( self::$instances[$id]->post->post_type != self::POST_TYPE ) {
			return NULL;
		}
		return self::$instances[$id];
	}

	/**
	 * @static
	 * @return bool Whether the current query is for the deal post type
	 */
	public static function is_deal_query() {
		$post_type = get_query_var('post_type');
		if ( $post_type == self::POST_TYPE ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * @static
	 * @return bool Whether the current query is for the merchant post type
	 */
	public static function is_deal_tax_query() {
		$taxonomy = get_query_var('taxonomy');
		if ( $taxonomy == self::LOCATION_TAXONOMY || $taxonomy == self::CAT_TAXONOMY || $taxonomy == self::TAG_TAXONOMY ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Get the post object associated with this deal
	 *
	 * @return object
	 */
	public function get_deal() {
		return $this->get_post();
	}

	/**
	 * Get the current price of the deal, which may be influenced by
	 * the number of successful purchases to date
	 *
	 * @param int $qty Determine the price per unit based on $qty number of purchases. NULL to use current number.
	 * @return int|float The current price
	 */
	public function get_price( $qty = NULL, $data = array() ) {
		if ( is_null( $qty ) ) {
			$qty = $this->get_number_of_purchases();
		}

		$price = apply_filters('gb_get_deal_price_meta', $this->get_post_meta( self::$meta_keys['price'] ), $this, $qty, $data );
		if ( 0 == $qty ) {
			$price = apply_filters('gb_deal_price', $price, $this, $qty, $data);
			return $price;
		}
		
		$dynamic_price = $this->get_dynamic_price();
		$max_qty_found = 0;
		if (!empty($dynamic_price)) {
			foreach ( $dynamic_price as $qty_required => $new_price ) {
				if ( $qty >= $qty_required && $qty_required > $max_qty_found ) {
					$price = $new_price;
					$max_qty_found = $qty_required;
				}
			}
		}

		return apply_filters('gb_deal_get_price', $price, $this, $qty, $data );
	}

	public function get_dynamic_price() {
		$dynamic_price = $this->get_post_meta( self::$meta_keys['dynamic_price'] );
		if ( empty($dynamic_price) ) return;
		return (array)$dynamic_price;
	}

	/**
	 * @return bool Whether the deal is using dynamic pricing
	 */
	public function has_dynamic_price() {
		$dynamic_price = $this->get_dynamic_price();
		return count($dynamic_price) > 0;
	}

	public function set_prices( $prices ) {
		$base = 0;
		$dynamic = array();
		foreach ( $prices as $qty => $price ) {
			if ( $qty == 0 ) {
				$base = $price;
			} else {
				$dynamic[$qty] = $price;
			}
		}
		$this->save_post_meta(array(
			self::$meta_keys['price'] => $base,
			self::$meta_keys['dynamic_price'] => $dynamic,
		));
	}

	/**
	 * Get a list of every successful Purchase of this deal
	 *
	 * @return array An array of all Purchases
	 */
	public function get_purchases() {
		$purchase_ids = Group_Buying_Purchase::get_purchases(array('deal' => $this->ID ));

		$purchases = array();
		foreach ( $purchase_ids as $purchase_id ) {
			$purchases[] = Group_Buying_Purchase::get_instance( $purchase_id );
		}

		return $purchases;
	}

	/**
	 * Get a list of successful Purchases by a given account
	 *
	 * @param int $user_id
	 * @return array An array of Purchases
	 */
	public function get_purchases_by_account($account_id) {
		$purchase_ids = Group_Buying_Purchase::get_purchases( array('deal' => $this->ID, 'account' => $account_id) );

		$purchases = array();
		foreach ( $purchase_ids as $purchase_id ) {
			$purchase = Group_Buying_Purchase::get_instance($purchase_id);
			$purchases[] = $purchase->get_ID();
		}

		return $purchases;
	}

	/**
	 * @return int The number of successful purchases of this deal
	 */
	public function get_number_of_purchases( $recalculate = false, $count_pending = true ) {
		$number_of_purchases = $this->get_post_meta( self::$meta_keys['number_of_purchases'] );
		if ( $recalculate || empty( $number_of_purchases ) ) {
			$purchases = $this->get_purchases();
			$number_of_purchases = 0;
			foreach ( $purchases as $purchase ) {
				if ( $purchase->is_complete() || ( $count_pending && $purchase->is_pending() ) ) {
					$purchase_quantity = $purchase->get_product_quantity( $this->ID );
					$number_of_purchases += $purchase_quantity;
				}
			}

			$number_of_purchases = apply_filters('gb_deal_number_of_purchases', $number_of_purchases, $this);
			$this->save_post_meta(array(
				self::$meta_keys['number_of_purchases'] => $number_of_purchases
			));
		}
		return (int)$number_of_purchases;
	}

	/**
	 * Get the unix timestamp indicating when the deal expires
	 *
	 * @return int The current timestamp
	 */
	public function get_expiration_date() {
		$date = (int)$this->get_post_meta(self::$meta_keys['expiration_date']);
		if ( $date == 0 ) { // a new, unsaved post
			$date = current_time('timestamp') + 24*60*60;
		}
		return $date;
	}

	/**
	 * @return bool Whether this deal has expired
	 */
	public function is_expired() {
		if ( $this->never_expires() ) {
			return FALSE;
		}
		if ( current_time('timestamp') > $this->get_expiration_date() ) {
			return TRUE;
		}
		return FALSE;
	}

	public function never_expires() {
		return $this->get_expiration_date() == self::NO_EXPIRATION_DATE;
	}

	/**
	 * Get the list of deals that expired since $timestamp
	 * @static
	 * @param int $timestamp
	 * @return array The IDs of the recently expired deals
	 */
	public static function get_expired_deals( $timestamp = 0 ) {
		global $wpdb;
		$sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value>%d AND meta_value<%d";
		$query = $wpdb->prepare($sql, self::$meta_keys['expiration_date'], $timestamp, current_time('timestamp'));
		return (array)$wpdb->get_col($query);
	}

	/**
	 * Determine the status of the deal
	 *
	 * @return string The current deal status
	 */
	public function get_status() {
		if ( $this->is_closed() ) {
			return self::DEAL_STATUS_CLOSED;
		} elseif ( $this->is_pending() ) {
			return self::DEAL_STATUS_PENDING;
		} elseif ( $this->is_open() ) {
			return self::DEAL_STATUS_OPEN;
		} else {
			return self::DEAL_STATUS_UNKNOWN;
		}
	}

	/**
	 * @return bool Whether the deal is currently open
	 */
	public function is_open() {
		if ( $this->post->post_status == 'publish' && !$this->is_closed() ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * @return bool Whether the deal is pending
	 */
	public function is_pending() {
		if ( $this->post->post_status == 'pending' ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * @return bool Whether the deal is closed
	 */
	public function is_closed() {
		if ( $this->is_expired() ) {
			return TRUE;
		} elseif ( $this->is_sold_out() ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function set_shipping( $shipping ) {
		$this->save_post_meta(array(
			self::$meta_keys['shipping'] => $shipping,
		));
		return $shipping;
	}

	public function get_shipping_meta() {
		$shipping = $this->get_post_meta( self::$meta_keys['shipping'] );
		return $shipping;
	}

	public function get_shipping( $qty = NULL, $local = NULL ) {
		$shipping = $this->get_post_meta( self::$meta_keys['shipping'] );
		// Filtered for the shipping class to do the heavy lifting.
		return apply_filters('gb_deal_get_shipping',$shipping,$this,$qty,$local);
	}
	
	public function set_shippable( $shippable ) {
		$this->save_post_meta(array(
			self::$meta_keys['shippable'] => $shippable,
		));
		return $shippable;
	}

	public function get_shippable() {
		$shippable = $this->get_post_meta( self::$meta_keys['shippable'] );
		return $shippable;
	}

	public function set_shipping_dyn_price( $shipping_dyn_price ) {
		$this->save_post_meta(array(
			self::$meta_keys['shipping_dyn_price'] => $shipping_dyn_price,
		));
		return $shipping_dyn_price;
	}

	public function get_shipping_dyn_price() {
		$shipping_dyn_price = $this->get_post_meta( self::$meta_keys['shipping_dyn_price'] );
		return $shipping_dyn_price;
	}

	public function set_shipping_mode( $shipping_mode ) {
		$this->save_post_meta(array(
			self::$meta_keys['shipping_mode'] => $shipping_mode,
		));
		return $shipping_mode;
	}

	public function get_shipping_mode( $local = NULL ) {
		$shipping_mode = $this->get_post_meta( self::$meta_keys['shipping_mode'] );
		return $shipping_mode;
	}

	public function set_tax( $tax ) {
		/*
		 * 3.3.4 - stores mode and not int
		 */
		if ( is_numeric($tax) && $tax >= 1 ) {
			$tax = $tax/100;
		}
		$this->save_post_meta(array(
			self::$meta_keys['tax'] => $tax,
		));
		return $tax;
	}
	
	/**
	 * To be depracate in favor of get_tax_rate (or get_tax_mode for the meta)
	 */
	public function get_tax($local = NULL) { 
		$mode = $this->get_tax_mode();
		if ( is_int($mode) ) { 
			return $mode; // returned before the is_taxable check < 3.3.4 tax.
		}
		return Group_Buying_Core_Tax::get_rate( $mode, $local );
	}

	public function get_tax_mode($local = NULL) {
		$tax = $this->get_post_meta( self::$meta_keys['tax'] );
		if ( is_numeric($tax) ) { 
			return (int)$tax; // < 3.3.4 tax.
		}
		return $tax;
	}

	public function set_taxable( $bool ) {
		$this->save_post_meta(array(
			self::$meta_keys['taxable'] => $bool,
		));
		return $bool;
	}

	public function get_taxable($local = NULL) {
		$tax = $this->get_post_meta( self::$meta_keys['taxable'] );
		return $tax;
	}


	public function get_calc_tax( $qty = NULL, $item_data = NULL, $local = NULL ) {
		$tax = Group_Buying_Core_Tax::get_calc_tax( $this, $qty, $item_data, $local );
		return $tax;
	}

	/**
	 * Set the unix timestamp indicating when the deal expires
	 *
	 * @param int The new timestamp
	 * @return int The new timestamp
	 */
	public function set_expiration_date( $timestamp ) {
		$this->save_post_meta(array(
			self::$meta_keys['expiration_date'] => $timestamp,
		));
		return $timestamp;
	}

	/**
	 * @return int The maximum number of purchases allowed for this deal
	 */
	public function get_max_purchases() {
		return $this->get_post_meta(self::$meta_keys['max_purchases']);
	}

	/**
	 * Set a new value for the maximum number of purchases
	 *
	 * @param int $qty The new value
	 * @return int The maximum number of purchases allowed for this deal
	 */
	public function set_max_purchases( $qty ) {
		$this->save_post_meta(array(
			self::$meta_keys['max_purchases'] => $qty,
		));
		return $qty;
	}

	/**
	 * @return int The number of allowed purchase remaining
	 */
	public function get_remaining_allowed_purchases() {
		$max = $this->get_max_purchases();
		if ( $max == self::NO_MAXIMUM ) {
			return self::NO_MAXIMUM;
		}
		return $max - $this->get_number_of_purchases();
	}

	/**
	 * @return bool Whether this deal is sold out
	 */
	public function is_sold_out() {
		if ( $this->get_max_purchases() == self::NO_MAXIMUM ) {
			return FALSE;
		}
		return $this->get_remaining_allowed_purchases() < 1;
	}

	/**
	 * @return int The minimum number of purchases for this to be a successful deal
	 */
	public function get_min_purchases() {
		return $this->get_post_meta(self::$meta_keys['min_purchases']);
	}

	/**
	 * Set a new value for the minimum number of purchases
	 *
	 * @param int $qty The new value
	 * @return int The minimum number of purchases for this to be a successful deal
	 */
	public function set_min_purchases( $qty ) {
		$this->save_post_meta(array(
			self::$meta_keys['min_purchases'] => $qty,
		));
		return $qty;
	}

	/**
	 * @return int The number of purchases still needed for a successful deal
	 */
	public function get_remaining_required_purchases() {
		return max( 0, $this->get_min_purchases() - $this->get_number_of_purchases() );
	}

	public function is_successful() {
		if ( !$this->capture_before_expiration() && !$this->is_expired() ) {
			$bool = FALSE;
		}
		$remaining = $this->get_remaining_required_purchases();
		if ( $remaining < 1 ) {
			$bool = TRUE;
		}
		return apply_filters( 'gb_deal_is_successful', $bool, $this );
	}

	/**
	 * @return int The maximum quantity of this deal that a single user can Purchase
	 */
	public function get_max_purchases_per_user() {
		return $this->get_post_meta( self::$meta_keys['max_purchases_per_user'] );
	}
	/**
	 * Set a new value for the maximum number of purchases per user
	 *
	 * @param int $qty The new value
	 * @return int The maximum quantity of this deal that a single user can Purchase
	 */
	public function set_max_purchases_per_user( $qty ) {
		$this->save_post_meta( array(
			self::$meta_keys['max_purchases_per_user'] => $qty
		));
		return $qty;
	}

	/**
	 * @return int The ID of the merchant associated with this deal
	 */
	public function get_merchant_id() {
		return $this->get_post_meta( self::$meta_keys['merchant_id'] );
	}

	/**
	 * Set a new value for the merchant ID
	 *
	 * @param int $id
	 * @return int The ID of the merchant associated with this deal
	 */
	public function set_merchant_id( $id ) {
		$this->save_post_meta( array(
			self::$meta_keys['merchant_id'] => $id
		));
		return $id;
	}

	/**
	 * @return Group_Buying_Merchant The merchant associated with this deal
	 */
	public function get_merchant() {
		$id = $this->get_merchant_id();
		return Group_Buying_Merchant::get_instance($id);
	}

	public function get_title( $data = array() ) {
		return apply_filters('gb_deal_title', get_the_title($this->ID), $data);
	}

	public function get_slug() {
		$post = get_post($this->ID);
		return $post->post_name;
	}

	public function get_value() {
		$value = $this->get_post_meta( self::$meta_keys['value'] );
		return $value;
	}

	public function set_value( $value ) {
		$this->save_post_meta(array(
			self::$meta_keys['value'] => $value
		));
		return $value;
	}

	public function get_amount_saved() {
		$amount_saved = $this->get_post_meta( self::$meta_keys['amount_saved'] );
		return $amount_saved;
	}

	public function set_amount_saved( $amount_saved ) {
		$this->save_post_meta(array(
			self::$meta_keys['amount_saved'] => $amount_saved
		));
		return $amount_saved;
	}

	public function get_highlights() {
		$highlights = $this->get_post_meta( self::$meta_keys['highlights'] );
		return $highlights;
	}

	public function set_highlights( $highlights ) {
		$this->save_post_meta(array(
			self::$meta_keys['highlights'] => $highlights
		));
		return $highlights;
	}

	public function get_fine_print() {
		$fine_print = $this->get_post_meta( self::$meta_keys['fine_print'] );
		return $fine_print;
	}

	public function set_fine_print( $fine_print ) {
		$this->save_post_meta(array(
			self::$meta_keys['fine_print'] => $fine_print
		));
		return $fine_print;
	}

	public function get_rss_excerpt() {
		$rss_excerpt = $this->get_post_meta( self::$meta_keys['rss_excerpt'] );
		return $rss_excerpt;
	}

	public function set_rss_excerpt( $rss_excerpt ) {
		$this->save_post_meta(array(
			self::$meta_keys['rss_excerpt'] => $rss_excerpt
		));
		return $rss_excerpt;
	}

	/**
	 * @return string Expiration date for this deal's voucher
	 */
	public function get_voucher_expiration_date() {
		$voucher_expiration_date = $this->get_post_meta( self::$meta_keys['voucher_expiration_date'] );
		return $voucher_expiration_date;
	}

	/**
	 * @param string $date The expiration date for this deal's voucher
	 * @return The new expiration date for this deal's voucher
	 */
	public function set_voucher_expiration_date( $date ) {
		$this->save_post_meta(array(
			self::$meta_keys['voucher_expiration_date'] => strtotime($date)
		));
		return $date;
	}

	/**
	 * @return string Instructions on how to use this deal's voucher
	 */
	public function get_voucher_how_to_use() {
		$voucher_how_to_use = $this->get_post_meta( self::$meta_keys['voucher_how_to_use'] );
		return $voucher_how_to_use;
	}

	/**
	 * @param string $instructions The instructions for how to use this deal's voucher
	 * @return string The instructions for how to use this deal's voucher
	 */
	public function set_voucher_how_to_use( $instructions ) {
		$this->save_post_meta(array(
			self::$meta_keys['voucher_how_to_use'] => $instructions
		));
		return $instructions;
	}

	/**
	 * @return string Prefix for this deal's voucher ID
	 */
	public function get_voucher_id_prefix( $fallback = FALSE ) {
		$voucher_id_prefix = $this->get_post_meta( self::$meta_keys['voucher_id_prefix'] );
		if ( $fallback && !$voucher_id_prefix ) {
			$voucher_id_prefix = Group_Buying_Vouchers::get_voucher_prefix();
		}
		return $voucher_id_prefix;
	}

	/**
	 * @param string $prefix The string with which to prefix this deal's voucher IDs
	 * @return string The string with which to prefix this deal's voucher IDs
	 */
	public function set_voucher_id_prefix( $prefix ) {
		$this->save_post_meta(array(
			self::$meta_keys['voucher_id_prefix'] => $prefix
		));
		return $prefix;
	}

	/**
	 * @return array Locations where this deal's voucher may be used
	 */
	public function get_voucher_locations() {
		$voucher_locations = $this->get_post_meta( self::$meta_keys['voucher_locations'] );
		if ( is_null( $voucher_locations ) ) {
			// Initialize with empty locations
			$voucher_locations = array();
			while ( count( $voucher_locations ) < self::MAX_LOCATIONS ) {
				$voucher_locations[] = '';
			}
		}
		return (array)$voucher_locations;
	}

	/**
	 * @param array $locations Locations where this deal's voucher may be used
	 * @return array Locations where this deal's voucher may be used
	 */
	public function set_voucher_locations( $locations ) {
		$this->save_post_meta(array(
			self::$meta_keys['voucher_locations'] => $locations
		));
		return $locations;
	}

	/**
	 * @return string Location of the logo for this deal's voucher
	 */
	public function get_voucher_logo() {
		$voucher_logo = $this->get_post_meta( self::$meta_keys['voucher_logo'] );
		return $voucher_logo;
	}

	/**
	 * @param string $path Location of the logo for this deal's voucher
	 * @return string Location of the logo for this deal's voucher
	 */
	public function set_voucher_logo( $path ) {
		$this->save_post_meta(array(
			self::$meta_keys['voucher_logo'] => $path
		));
		return $path;
	}

	/**
	 * @return string Google maps iframe code for this deal's voucher
	 */
	public function get_voucher_map() {
		$voucher_map = $this->get_post_meta( self::$meta_keys['voucher_map'] );
		return $voucher_map;
	}

	/**
	 * @param string $map Google maps iframe code for this deal's voucher
	 * @return string Google maps iframe code for this deal's voucher
	 */
	public function set_voucher_map( $map ) {
		$this->save_post_meta(array(
			self::$meta_keys['voucher_map'] => $map
		));
		return $map;
	}

	/**
	 * @return string Comma separated list serial numbers for this deal's voucher
	 */
	public function get_voucher_serial_numbers() {
		$voucher_serial_numbers = (array)$this->get_post_meta( self::$meta_keys['voucher_serial_numbers'] );
		return $voucher_serial_numbers;
	}

	/**
	 * @param array $numbers List serial numbers for this deal's voucher
	 * @return array List of serial numbers for this deal's voucher
	 */
	public function set_voucher_serial_numbers( array $numbers ) {
		$this->save_post_meta(array(
			self::$meta_keys['voucher_serial_numbers'] => $numbers
		));
		return $numbers;
	}

	public function get_next_serial( $used = 0 ) {
		$serials = $this->get_voucher_serial_numbers();
		if ( !$serials ) {
			return FALSE;
		}
		$used = $this->get_post_meta(self::$meta_keys['used_voucher_serials']);
		$number_used = ( empty($used) ) ? '0' : count($used);
		if ( $number_used > count($serials) || !isset($serials[$number_used]) || !(trim($serials[$number_used])) ) {
			return FALSE;
		}
		return $serials[$number_used];
	}

	public function mark_serial_used( $new ) {
		$serials = $this->get_post_meta(self::$meta_keys['used_voucher_serials']);
		if ( !is_array($serials) ) {
			$serials = array();
		}
		$serials[] = $new;
		$this->save_post_meta(array(
			self::$meta_keys['used_voucher_serials'] => $serials
		));
	}
	
	/**
	 * Add a file as a post attachment.
	 */
	public function set_attachement( $files ) {
		if (!function_exists('wp_generate_attachment_metadata')){
			require_once(ABSPATH . 'wp-admin' . '/includes/image.php');
			require_once(ABSPATH . 'wp-admin' . '/includes/file.php');
			require_once(ABSPATH . 'wp-admin' . '/includes/media.php');
		}

		foreach ($files as $file => $array) {
			if ($files[$file]['error'] !== UPLOAD_ERR_OK) {
				// Group_Buying_Controller::set_message('upload error : ' . $files[$file]['error']);
			}
			$attach_id = media_handle_upload( $file, $this->ID );
		}
		// Make it a thumbnail while we're at it.
		if ($attach_id > 0){
			update_post_meta($this->ID,'_thumbnail_id',$attach_id);
		}
		return $attach_id;
	}
	
	/**
	 *
	 * @param int $merchant_id The merchant to look for
	 * @return array List of IDs for deals created by this merchant
	 */
	public static function get_deals_by_merchant( $merchant_id ) {
		$deal_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['merchant_id'] => $merchant_id ) );
		return $deal_ids;
	}

	/**
	 * @static
	 * @return bool TRUE if payments will be captured before deal expiration
	 */
	public function capture_before_expiration() {
		return (bool)$this->get_post_meta(self::$meta_keys['capture_before_expiration']);
	}

	public function set_capture_before_expiration( $status ) {
		$this->save_post_meta(array(
			self::$meta_keys['capture_before_expiration'] => (bool)$status,
		));
	}
}
