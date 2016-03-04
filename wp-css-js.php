<?php
/*
Plugin Name: WP CSS & JS Editor
Version: 1.0
Plugin URI: 
Description: Adds a css and js editor to each post, page & custom posts
Author: Tryon Eggleston
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


class wp_css_js {
	const VERSION = '1.0';
	protected $plugin_slug = 'wp-css-js';
	protected static $instance = null;

	private function __construct() {

		// add our new peramstructs to the rewrite rules
		//add_filter( 'post_rewrite_rules', array( $this, 'add_permastructs' ) );

		// parse the generated links
		//add_filter( 'post_type_link', array( $this, 'parse_permalinks' ), 10, 4 );

		add_action( 'wp_head', array( $this, 'add_css'), 40);
		add_action( 'wp_footer', array( $this, 'add_js'), 40);

	}

	
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public static function activate( $network_wide ) {
		
	}

	public static function deactivate( $network_wide ) {
		
	}

	public function add_css(){
		global $post;

		if( $page_cssfiles = get_post_meta( $post->ID, '_' . $this->get_plugin_slug() . '-cssfiles', true ) ){
			foreach( $page_cssfiles as $file ){
				$file = wp_prepare_attachment_for_js($file);
				echo "\n<link rel='stylesheet' id='". $file['title'] ."-css'  href='" . $file[url] . "' type='text/css' media='all' />";
			}
		}	

		if( $page_css = get_post_custom_values( '_' . $this->get_plugin_slug() . '-css', $post->ID ) ){
			foreach( $page_css as $css ){
				echo ("\n<style>\n$css\n</style>\n");
			}
		}
	}

	public function add_js($t){
		global $post;

		if( $page_jsfiles = get_post_meta( $post->ID, '_' . $this->get_plugin_slug() . '-javascriptfiles', true ) ){
			foreach( $page_jsfiles as $file ){
				echo ("\n<script src=\"" . wp_get_attachment_url($file) . "\"></script>");
			}
		}		

		if( $page_js = get_post_custom_values( '_' . $this->get_plugin_slug() . '-javascript', $post->ID ) ){
			foreach( $page_js as $script ){
				echo ("\n<script>\n$script\n</script>");
			}
		}
	}
}

register_activation_hook( __FILE__,  array('wp_css_js','activate') );
register_deactivation_hook( __FILE__,  array('wp_css_js','deactivate') );

add_action( 'plugins_loaded', array( 'wp_css_js', 'get_instance' ) );

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wp-css-js-admin.php' );
	add_action( 'plugins_loaded', array( 'wp_css_js_Admin', 'get_instance' ) );
}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wp-css-js-ajax.php' );
	add_action( 'plugins_loaded', array( 'wp_css_js_ajax', 'get_instance' ) );
}