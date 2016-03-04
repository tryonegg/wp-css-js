<?php

class wp_css_js_ajax {

	protected static $instance = null;

	private function __construct() {

		$plugin = wp_css_js::get_instance();
		$this->version = wp_css_js::VERSION;
		$this->plugin_slug = $plugin->get_plugin_slug();

		add_action('wp_ajax_wpcssjssave', array( $this, 'save' ));
	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}	

	public function save( ) {

		check_ajax_referer( $this->plugin_slug . 'ajax_nonce', 'security' );

		// CSS
		$mydata = $_POST[ $this->plugin_slug . '-css' ];
		// Update the meta field.
		$css = update_post_meta( $_POST['postid'], '_' . $this->plugin_slug . '-css', $mydata );

		// CSS files
		$cssfiles = $_POST[ $this->plugin_slug . '-cssfiles' ];
		// Update the meta field.
		if($cssfiles){
			update_post_meta( $_POST['postid'], '_' . $this->plugin_slug . '-cssfiles', $cssfiles );
		} else {
			delete_post_meta( $_POST['postid'], '_' . $this->plugin_slug . '-cssfiles' );
		}

		// Javascript
		$mydata = $_POST[ $this->plugin_slug . '-javascript' ];
		// Update the meta field.
		$javascript = update_post_meta( $_POST['postid'], '_' . $this->plugin_slug . '-javascript', $mydata );

		// CSS files
		$javascriptfiles = $_POST[ $this->plugin_slug . '-javascriptfiles' ];
		// Update the meta field.
		if($javascriptfiles){
			update_post_meta( $_POST['postid'], '_' . $this->plugin_slug . '-javascriptfiles', $javascriptfiles );
		} else {
			delete_post_meta( $_POST['postid'], '_' . $this->plugin_slug . '-javascriptfiles' );
		}
		
		if($javascript && $css){
			die(true);
		} else{
			die(false);
		}
	}

}