<?php

class wp_css_js_Admin {

	protected static $instance = null;
	protected $plugin_screen_hook_suffix = null;
	public $updatePending = false;
	private $enabeled = false;

	private function __construct() {

		$plugin = wp_css_js::get_instance();
		$this->version = wp_css_js::VERSION;
		$this->plugin_slug = $plugin->get_plugin_slug();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );


		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );

	}

	function enqueue_styles(){
		//wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'includes/admin.css', __FILE__ ), array(), $this->version );
		wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'includes/admin.css', __FILE__ ), array(), $this->version );
		wp_enqueue_style( 'codemirror', plugins_url( 'includes/codemirror/lib/codemirror.css', __FILE__ ), array(), $this->version );
		wp_enqueue_style( 'codemirror-foldgutter', plugins_url( 'includes/codemirror/addon/fold/foldgutter.css', __FILE__ ), array('codemirror'), $this->version );
		wp_enqueue_style( 'codemirror-lint', plugins_url( 'includes/codemirror/addon/lint/lint.css', __FILE__ ), array('codemirror'), $this->version );
		wp_enqueue_style( 'codemirror-monokai', plugins_url( 'includes/codemirror/theme/monokai.css', __FILE__ ), array('codemirror'), $this->version );
	}

	function enqueue_scripts(){
		
		wp_enqueue_media();

		//wp_enqueue_script( $this->plugin_slug . '-admin-styles', plugins_url( 'includes/admin.js', __FILE__ ), array('jquery','jquery-ui-datepicker'), $this->version, true);		
		wp_register_script( 'codemirror', plugins_url( 'includes/codemirror/lib/codemirror.js', __FILE__ ), false, $this->version, true);		

		//js styleing
		wp_register_script( 'codemirror-javascript', plugins_url( 'includes/codemirror/mode/javascript/javascript.js', __FILE__ ), array("codemirror"), $this->version, true);		
		
		//css styleing
		wp_register_script( 'codemirror-css', plugins_url( 'includes/codemirror/mode/css/css.js', __FILE__ ), array("codemirror"), $this->version, true);		

		//helpers
		wp_register_script( 'codemirror-active-line', plugins_url( 'includes/codemirror/addon/selection/active-line.js', __FILE__ ), array("codemirror"), $this->version, true);		
		wp_register_script( 'codemirror-matchbrackets', plugins_url( 'includes/codemirror/addon/edit/matchbrackets.js', __FILE__ ), array("codemirror"), $this->version, true);		
		
		//linter
		wp_register_script( 'codemirror-lint', plugins_url( 'includes/codemirror/addon/lint/lint.js', __FILE__ ), array("codemirror"), $this->version, true);		

		//js linting
		wp_register_script( 'jshint', '//ajax.aspnetcdn.com/ajax/jshint/r07/jshint.js', array("codemirror-lint"), $this->version, true);		
		wp_register_script( 'codemirror-lint-javascript', plugins_url( 'includes/codemirror/addon/lint/javascript-lint.js', __FILE__ ), array("codemirror-lint", "jshint"), $this->version, true);		

		//css linting
		wp_register_script( 'csslint', '//rawgit.com/stubbornella/csslint/master/release/csslint.js', array("codemirror-lint"), $this->version, true);		
		wp_register_script( 'codemirror-lint-css', plugins_url( 'includes/codemirror/addon/lint/css-lint.js', __FILE__ ), array("codemirror-lint", "csslint"), $this->version, true);		

		//code folding 
		wp_register_script( 'codemirror-foldcode', plugins_url( 'includes/codemirror/addon/fold/foldcode.js', __FILE__ ), array("codemirror-lint"), $this->version, true);		
		wp_register_script( 'codemirror-foldgutter', plugins_url( 'includes/codemirror/addon/fold/foldgutter.js', __FILE__ ), array("codemirror-lint", "codemirror-foldcode"), $this->version, true);		
		wp_register_script( 'codemirror-brace-fold', plugins_url( 'includes/codemirror/addon/fold/brace-fold.js', __FILE__ ), array("codemirror-lint", "codemirror-foldcode"), $this->version, true);		
		wp_register_script( 'codemirror-comment-fold', plugins_url( 'includes/codemirror/addon/fold/comment-fold.js', __FILE__ ), array("codemirror-lint", "codemirror-foldcode"), $this->version, true);		
		

		wp_register_script( 
			$this->plugin_slug . '-admin-script', 
			plugins_url( 'includes/admin.js', __FILE__ ), 
			array(
				"codemirror",
				"codemirror-javascript",
				"codemirror-css",
				"codemirror-javascript",
				"codemirror-matchbrackets",
				"codemirror-lint-javascript",
				"codemirror-lint-css",
				"codemirror-foldgutter",
				"codemirror-brace-fold",
				"codemirror-comment-fold",
				"underscore"
			), 
			$this->version, 
			true
		);		

	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Adds the meta box container.
	 */
	public function add_meta_box( $post_type ) {
        $dont_include = array('revision', 'attachment', 'nav_menu_item');
        if(!in_array($post_type, $dont_include)){
			add_meta_box(
				$this->plugin_slug.'editor',
				'Custom CSS & JS',
				array( $this, 'render_meta_box_content' ),
				$post_type,
				'normal',
				'default'
			);
        }
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {
	
		// Check if our nonce is set.
		if ( ! isset( $_POST[$this->plugin_slug . 'meta_box_nonce'] ) )
			return $post_id;

		$nonce = $_POST[$this->plugin_slug . 'meta_box_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, $this->plugin_slug . 'meta_box' ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// CSS
		update_post_meta( $post_id, '_' . $this->plugin_slug . '-css', $_POST[ $this->plugin_slug . '-css']);

		// css Files
		update_post_meta( $post_id, '_' . $this->plugin_slug . '-cssfiles', ( isset(  $_POST[ $this->plugin_slug . '-cssfiles'] ) )? $_POST[ $this->plugin_slug . '-cssfiles']: false );

		// Javascript
		update_post_meta( $post_id, '_' . $this->plugin_slug . '-javascript', $_POST[ $this->plugin_slug . '-javascript'] );

		// Javascript Files
		update_post_meta( $post_id, '_' . $this->plugin_slug . '-javascriptfiles', ( isset(  $_POST[ $this->plugin_slug . '-javascriptfiles'] ) )? $_POST[ $this->plugin_slug . '-javascriptfiles']: false );		
	}


	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box_content( $post ) {

	    wp_enqueue_script( $this->plugin_slug . '-admin-script' );

		// ajax nonce
		$ajax_nonce = wp_create_nonce( $this->plugin_slug . 'ajax_nonce' );
	
		// Add an nonce field so we can check for it later.
		wp_nonce_field( $this->plugin_slug . 'meta_box', $this->plugin_slug . 'meta_box_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$css = get_post_meta( $post->ID, '_'.$this->plugin_slug . '-css', true );
		$cssfiles = get_post_meta( $post->ID, '_'.$this->plugin_slug . '-cssfiles', true );

		$javascript = get_post_meta( $post->ID, '_'.$this->plugin_slug . '-javascript', true );
		$javascriptfiles = get_post_meta( $post->ID, '_'.$this->plugin_slug . '-javascriptfiles', true );

		// Display the form, using the current value.
		echo '<p><strong>CSS</strong> (appended to the end of &lt;head&gt; in the same order as on this page)</p> ';

		echo '<ul class="' . $this->plugin_slug . '-cssfiles" id="' . $this->plugin_slug . '-cssfiles">';
			if($cssfiles){
				foreach($cssfiles as $file){
					$file_data = wp_prepare_attachment_for_js($file);
					echo "<li  title='" . $file_data["url"] . "' id='" . $file . "' >" . $file_data["filename"] . " (" . $file_data["filesizeHumanReadable"] . ")" . "<input type='hidden' name='wp-css-js-cssfiles[]' value='". $file ."'/><button class='button deletemeta button-small'>Remove</button></li>";
				}
			}
		echo '</ul>';

		echo '<p class="hide-if-no-js"><a title="Add CSS File" href="javascript:;" id="add-css">Add CSS File</a></p>';

		echo '<label for="' . $this->plugin_slug . '-css">Additional CSS</label> ';
		echo '<textarea id="' . $this->plugin_slug . '-css" name="' . $this->plugin_slug . '-css" >';
			echo esc_attr( $css );
		echo '</textarea>';

		echo '<p><strong>Javascript</strong> (appended at the end of &lt;body&gt; in the same order as on this page)</p>';

		echo '<ul class="' . $this->plugin_slug . '-javascriptfiles" id="' . $this->plugin_slug . '-javascriptfiles">';
			if($javascriptfiles){
				foreach($javascriptfiles as $file){
					$file_data = wp_prepare_attachment_for_js($file);
					echo "<li  title='" . $file_data["url"] . "' id='" . $file . "' >" . $file_data["filename"] . " (" . $file_data["filesizeHumanReadable"] . ")" . "<input type='hidden' name='wp-css-js-javascriptfiles[]' value='". $file ."'/><button class='button deletemeta button-small'>Remove</button></li>";
				}
			}
		echo '</ul>';

		echo '<p class="hide-if-no-js"><a title="Add Javascript File" href="javascript:;" id="add-javascript">Add Javascript File</a></p>';

		echo '<label for="' . $this->plugin_slug . '-javascript">Additional Javascript</label> ';
		echo '<textarea id="' . $this->plugin_slug . '-javascript" name="' . $this->plugin_slug . '-javascript" >';
			echo esc_attr( $javascript );
		echo '</textarea>';

		echo '<button id="' . $this->plugin_slug . '-update" data-nonce="'. $ajax_nonce .'" data-postid="'. $post->ID .'" class="button updatemeta button-small">Save</button>';


	}

}