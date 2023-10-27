<?php
/*
Plugin Name: WP CSS & JS Editor
Version: 1.2
Plugin URI:
Description: Adds a css and js editor to each post, page & custom posts
Author: Tryon Eggleston
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


class wp_css_js {
	const VERSION              = '1.1';
	protected $plugin_slug     = 'wp-css-js';
	protected static $instance = null;

	private function __construct() {

		add_action( 'wp_head', array( $this, 'add_css' ), 40 );
		add_action( 'wp_footer', array( $this, 'add_js' ), 40 );
	}


	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate( $network_wide ) {
	}

	public static function deactivate( $network_wide ) {
	}

	public function add_css() {
		global $post;
		if ( ! isset( $post->ID ) ) {
			return;
		}

		if ( $page_cssfiles = get_post_meta( $post->ID, '_' . $this->get_plugin_slug() . '-cssfiles', true ) ) {
			foreach ( $page_cssfiles as $file ) {
				$file = wp_prepare_attachment_for_js( $file );
				echo "\n<link rel='stylesheet' id='" . $file['title'] . "-css'  href='" . $file[ url ] . "' type='text/css' media='all' />";
			}
		}
		if ( $page_css = get_post_custom_values( '_' . $this->get_plugin_slug() . '-css', $post->ID ) ) {
			if ( sizeof( $page_css ) > 0 ) {
				if ( ! empty( $page_css[0] ) ) {
					$css = self::minifyCss( $page_css[0] );
					echo ( "\n<style id=\"wp-cssjs-css\">\n$css\n</style>\n" );
				}
			}
		}
	}

	public function add_js( $t ) {
		global $post;
		if ( ! $post ) {
			return;
		}

		if ( $page_jsfiles = get_post_meta( $post->ID, '_' . $this->get_plugin_slug() . '-javascriptfiles', true ) ) {
			foreach ( $page_jsfiles as $file ) {
				echo ( "\n<script src=\"" . wp_get_attachment_url( $file ) . '"></script>' );
			}
		}

		if ( $page_js = get_post_custom_values( '_' . $this->get_plugin_slug() . '-javascript', $post->ID ) ) {
			if ( sizeof( $page_js ) > 0 ) {
				if ( ! empty( $page_js[0] ) ) {
					echo ( "\n<script id=\"wp-cssjs-js\">\n" . $page_js[0] . "\n</script>" );
				}
			}
		}
	}

	/**
	 * This function takes a css-string and compresses it, removing
	 * unnecessary whitespace, colons, removing unnecessary px/em
	 * declarations etc.
	 *
	 * @param string $css
	 * @return string compressed css content
	 * @author Steffen Becker
	 */
	public function minifyCss( $css ) {
		// some of the following functions to minimize the css-output are directly taken
		// from the awesome CSS JS Booster: https://github.com/Schepp/CSS-JS-Booster
		// all credits to Christian Schaefer: http://twitter.com/derSchepp

		// remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		// backup values within single or double quotes
		preg_match_all( '/(\'[^\']*?\'|"[^"]*?")/ims', $css, $hit, PREG_PATTERN_ORDER );
		for ( $i = 0; $i < count( $hit[1] ); $i++ ) {
			$css = str_replace( $hit[1][ $i ], '##########' . $i . '##########', $css );
		}

		// remove traling semicolon of selector's last property
		$css = preg_replace( '/;[\s\r\n\t]*?}[\s\r\n\t]*/ims', "}\r\n", $css );
		// remove any whitespace between semicolon and property-name
		$css = preg_replace( '/;[\s\r\n\t]*?([\r\n]?[^\s\r\n\t])/ims', ';$1', $css );
		// remove any whitespace surrounding selector-comma
		$css = preg_replace( '/[\s\r\n\t]*,[\s\r\n\t]*?([^\s\r\n\t])/ims', ',$1', $css );
		// remove any whitespace surrounding opening parenthesis
		$css = preg_replace( '/[\s\r\n\t]*{[\s\r\n\t]*?([^\s\r\n\t])/ims', '{$1', $css );
		// remove any whitespace between numbers and units
		$css = preg_replace( '/([\d\.]+)[\s\r\n\t]+(px|em|pt|%)/ims', '$1$2', $css );
		// constrain multiple whitespaces
		$css = preg_replace( '/\p{Zs}+/ims', ' ', $css );
		// remove newlines
		$css = str_replace( array( "\r\n", "\r", "\n" ), '', $css );

		// Restore backupped values within single or double quotes
		for ( $i = 0; $i < count( $hit[1] ); $i++ ) {
			$css = str_replace( '##########' . $i . '##########', $hit[1][ $i ], $css );
		}

		return $css;
	}
}

add_action( 'plugins_loaded', array( 'wp_css_js', 'get_instance' ) );

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-wp-css-js-admin.php';
	add_action( 'plugins_loaded', array( 'wp_css_js_Admin', 'get_instance' ) );
}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-wp-css-js-ajax.php';
	add_action( 'plugins_loaded', array( 'wp_css_js_ajax', 'get_instance' ) );
}
