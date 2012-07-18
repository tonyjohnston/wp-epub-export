<?php
/**
 * A fundamental class from which all other classes in the plugin should be derived.
 * The purpose of this class is to hold data useful to all classes.
 */

if ( !defined('EXP_DEV') )
    define('EXP_DEV', FALSE);

abstract class Expub_Epub {
	
	const TEXT_DOMAIN = 'expub-epub';
	const EXP_VERSION = '1';
	const DB_VERSION = 1;
	const PLUGIN_NAME = 'exPub ePub Exporter for Wordpress';
	const DEBUG = EXP_DEV; // Use define( 'EXP_DEV', TRUE/FALSE ) within your wp-config.
	
	/**
	 * A wrapper around WP's __() to add the plugin's text domain
	 *
	 * @param string $string
	 * @return string|void
	 */
	public static function __( $string ) {
		return __(apply_filters( 'exp_string_'.sanitize_title($string), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * A wrapper around WP's _e() to add the plugin's text domain
	 *
	 * @param string $string
	 * @return void
	 */
	public static function _e( $string ) {
		return _e(apply_filters( 'exp_string_'.sanitize_title($string), $string ), self::TEXT_DOMAIN );
	}
}