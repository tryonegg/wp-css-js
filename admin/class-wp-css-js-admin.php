<?php

class wp_css_js_Admin {

	protected static $instance           = null;
	protected $plugin_screen_hook_suffix = null;
	public $updatePending                = false;
	private $enabeled                    = false;

	private function __construct() {

		$plugin            = wp_css_js::get_instance();
		$this->version     = wp_css_js::VERSION;
		$this->plugin_slug = $plugin->get_plugin_slug();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		add_action( 'init', array( $this, 'init' ), 20 );
		add_action( 'admin_head', array( $this, 'css' ) );

	}

	function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'includes/admin.css', __FILE__ ), array(), $this->version );

		wp_enqueue_style( 'wp-codemirror' );
	}

	function enqueue_scripts() {

		global $pagenow;
		if ( ( $pagenow == 'post.php' ) || ( $pagenow == 'post-new.php' ) || ( get_post_type() == 'post' ) ) {

			wp_enqueue_media();

			wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
			wp_enqueue_code_editor( array( 'type' => 'application/javascript' ) );
			wp_enqueue_script( 'wp-theme-plugin-editor' );

			wp_register_script(
				$this->plugin_slug . '-admin-script',
				plugins_url( 'includes/admin.js', __FILE__ ),
				array(
					'wp-theme-plugin-editor',
					'underscore',
				),
				$this->version,
				true
			);

		}
	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Adds the meta box container.
	 */
	public function add_meta_box( $post_type ) {
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}
		$dont_include = array( 'revision', 'attachment', 'nav_menu_item' );
		if ( ! in_array( $post_type, $dont_include ) ) {
			add_meta_box(
				$this->plugin_slug . 'editor',
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
		if ( ! isset( $_POST[ $this->plugin_slug . 'meta_box_nonce' ] ) ) {
			return $post_id;
		}

		$nonce = $_POST[ $this->plugin_slug . 'meta_box_nonce' ];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, $this->plugin_slug . 'meta_box' ) ) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted,
				// so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {

				return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// CSS
		update_post_meta( $post_id, '_' . $this->plugin_slug . '-css', $_POST[ $this->plugin_slug . '-css' ] );

		// css Files
		update_post_meta( $post_id, '_' . $this->plugin_slug . '-cssfiles', ( isset( $_POST[ $this->plugin_slug . '-cssfiles' ] ) ) ? $_POST[ $this->plugin_slug . '-cssfiles' ] : false );

		// Javascript
		update_post_meta( $post_id, '_' . $this->plugin_slug . '-javascript', $_POST[ $this->plugin_slug . '-javascript' ] );

		// Javascript Files
		update_post_meta( $post_id, '_' . $this->plugin_slug . '-javascriptfiles', ( isset( $_POST[ $this->plugin_slug . '-javascriptfiles' ] ) ) ? $_POST[ $this->plugin_slug . '-javascriptfiles' ] : false );
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
		$css      = get_post_meta( $post->ID, '_' . $this->plugin_slug . '-css', true );
		$cssfiles = get_post_meta( $post->ID, '_' . $this->plugin_slug . '-cssfiles', true );

		$javascript      = get_post_meta( $post->ID, '_' . $this->plugin_slug . '-javascript', true );
		$javascriptfiles = get_post_meta( $post->ID, '_' . $this->plugin_slug . '-javascriptfiles', true );

		// Display the form, using the current value.
		echo '<p><strong>CSS</strong> (appended to the end of &lt;head&gt; in the same order as on this page)</p> ';

		echo '<ul class="' . $this->plugin_slug . '-cssfiles" id="' . $this->plugin_slug . '-cssfiles">';
		if ( $cssfiles ) {
			foreach ( $cssfiles as $file ) {
				$file_data = wp_prepare_attachment_for_js( $file );
				echo "<li  title='" . $file_data['url'] . "' id='" . $file . "' >" . $file_data['filename'] . ' (' . $file_data['filesizeHumanReadable'] . ')' . "<input type='hidden' name='wp-css-js-cssfiles[]' value='" . $file . "'/><button class='button deletemeta button-small'>Remove</button></li>";
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
		if ( $javascriptfiles ) {
			foreach ( $javascriptfiles as $file ) {
				$file_data = wp_prepare_attachment_for_js( $file );
				echo "<li  title='" . $file_data['url'] . "' id='" . $file . "' >" . $file_data['filename'] . ' (' . $file_data['filesizeHumanReadable'] . ')' . "<input type='hidden' name='wp-css-js-javascriptfiles[]' value='" . $file . "'/><button class='button deletemeta button-small'>Remove</button></li>";
			}
		}
		echo '</ul>';

		echo '<p class="hide-if-no-js"><a title="Add Javascript File" href="javascript:;" id="add-javascript">Add Javascript File</a></p>';

		echo '<label for="' . $this->plugin_slug . '-javascript">Additional Javascript</label> ';
		echo '<textarea id="' . $this->plugin_slug . '-javascript" name="' . $this->plugin_slug . '-javascript" >';
			echo esc_attr( $javascript );
		echo '</textarea>';

	}


	// Add a column to any posts column to show if the post has custom css or JS.
	public function init() {

		if ( ! current_user_can( 'manage_plugins' ) ) {
			return;
		}

		$args = array(
			'public' => true,
		);

		$post_types = get_post_types( $args );

		foreach ( $post_types as $type ) {
			add_filter( 'manage_' . $type . 's_columns', array( $this, 'add_column' ), 20 );
			add_action( 'manage_' . $type . 's_custom_column', array( $this, 'column_view' ), 20, 2 );
		}
	}

	public function css() {
		echo '
        <style type="text/css">
            .column-cssjscode{
                width: 80px;
            }
			.column-cssjscode svg{
				width: 26px;
				margin: 5px;
			}
			.column-cssjscode .dashicons.dashicons-update {
				font-size: 3em;
				color: #002855;
				width: 36px;
				height: 36px;
				font-size: 36px;
			}			
        </style>
    	';

		echo '
		<div style="height: 0; width: 0; position: absolute; visibility: hidden">
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
					<symbol id="cssfile" viewBox="0 0 60 60" ><title>CSS File</title><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-720.000000, -330.000000)" fill="#000000"><g transform="translate(720.000000, 330.000000)"><path d="M36,12 L44.586,12 L36,3.414 L36,12 Z M47,14 L35,14 C34.447,14 34,13.552 34,13 L34,1 C34,0.596 34.243,0.231 34.617,0.076 C34.991,-0.078 35.421,0.007 35.707,0.293 L47.707,12.293 C47.993,12.579 48.079,13.009 47.924,13.383 C47.77,13.756 47.404,14 47,14 L47,14 Z"/><path d="M13,46 L58,46 L58,24 L13,24 L13,46 Z M59,48 L12,48 C11.447,48 11,47.552 11,47 L11,23 C11,22.448 11.447,22 12,22 L59,22 C59.553,22 60,22.448 60,23 L60,47 C60,47.552 59.553,48 59,48 L59,48 Z"/><path d="M38,44 C35.243,44 33,41.757 33,39 C33,38.448 33.447,38 34,38 C34.553,38 35,38.448 35,39 C35,40.654 36.346,42 38,42 C39.654,42 41,40.654 41,39 C41,37.346 39.654,36 38,36 C35.243,36 33,33.757 33,31 C33,28.243 35.243,26 38,26 C40.757,26 43,28.243 43,31 C43,31.552 42.553,32 42,32 C41.447,32 41,31.552 41,31 C41,29.346 39.654,28 38,28 C36.346,28 35,29.346 35,31 C35,32.654 36.346,34 38,34 C40.757,34 43,36.243 43,39 C43,41.757 40.757,44 38,44"/><path d="M51,44 C48.243,44 46,41.757 46,39 C46,38.448 46.447,38 47,38 C47.553,38 48,38.448 48,39 C48,40.654 49.346,42 51,42 C52.654,42 54,40.654 54,39 C54,37.346 52.654,36 51,36 C48.243,36 46,33.757 46,31 C46,28.243 48.243,26 51,26 C53.757,26 56,28.243 56,31 C56,31.552 55.553,32 55,32 C54.447,32 54,31.552 54,31 C54,29.346 52.654,28 51,28 C49.346,28 48,29.346 48,31 C48,32.654 49.346,34 51,34 C53.757,34 56,36.243 56,39 C56,41.757 53.757,44 51,44"/><path d="M24,44 C19.037,44 15,39.962 15,35 C15,30.038 19.037,26 24,26 C26.04,26 28.038,26.701 29.626,27.975 C30.057,28.32 30.126,28.95 29.78,29.38 C29.436,29.812 28.807,29.882 28.374,29.535 C27.122,28.531 25.609,28 24,28 C20.141,28 17,31.14 17,35 C17,38.86 20.141,42 24,42 C25.609,42 27.122,41.469 28.376,40.465 C28.809,40.12 29.437,40.19 29.781,40.62 C30.127,41.051 30.058,41.68 29.626,42.025 C28.038,43.299 26.039,44 24,44"/><path d="M2,58 L46,58 L46,48 L12,48 C11.447,48 11,47.552 11,47 L11,23 C11,22.448 11.447,22 12,22 L46,22 L46,14 L35,14 C34.447,14 34,13.552 34,13 L34,2 L2,2 L2,58 Z M47,60 L1,60 C0.447,60 0,59.552 0,59 L0,1 C0,0.448 0.447,0 1,0 L35,0 C35.553,0 36,0.448 36,1 L36,12 L47,12 C47.553,12 48,12.448 48,13 L48,23 C48,23.552 47.553,24 47,24 L13,24 L13,46 L47,46 C47.553,46 48,46.448 48,47 L48,59 C48,59.552 47.553,60 47,60 L47,60 Z"/></g></g></g></symbol>
					<symbol id="cssinline" viewBox="0 0 60 60"><title>CSS Code</title><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-240.000000, -690.000000)" fill="#000000"><g transform="translate(240.000000, 690.000000)"><path d="M2,10 L2,57 C2,57.551 2.449,58 3,58 L57,58 C57.551,58 58,57.551 58,57 L58,10 L2,10 Z M57,60 L3,60 C1.346,60 0,58.654 0,57 L0,9 C0,8.448 0.448,8 1,8 L59,8 C59.552,8 60,8.448 60,9 L60,57 C60,58.654 58.654,60 57,60 L57,60 Z"/><path d="M2,8 L58,8 L58,3 C58,2.449 57.551,2 57,2 L3,2 C2.449,2 2,2.449 2,3 L2,8 Z M59,10 L1,10 C0.448,10 0,9.552 0,9 L0,3 C0,1.346 1.346,0 3,0 L57,0 C58.654,0 60,1.346 60,3 L60,9 C60,9.552 59.552,10 59,10 L59,10 Z"/><path d="M6,5 C6,5.552 5.552,6 5,6 C4.448,6 4,5.552 4,5 C4,4.448 4.448,4 5,4 C5.552,4 6,4.448 6,5"/><path d="M10,5 C10,5.552 9.552,6 9,6 C8.448,6 8,5.552 8,5 C8,4.448 8.448,4 9,4 C9.552,4 10,4.448 10,5"/><path d="M14,5 C14,5.552 13.552,6 13,6 C12.448,6 12,5.552 12,5 C12,4.448 12.448,4 13,4 C13.552,4 14,4.448 14,5"/><path d="M10.001,43 C9.689,43 9.381,42.854 9.186,42.581 L4.186,35.581 C3.938,35.233 3.938,34.767 4.186,34.419 L9.186,27.419 C9.507,26.969 10.132,26.865 10.581,27.186 C11.031,27.507 11.135,28.132 10.814,28.581 L6.229,35 L10.814,41.419 C11.135,41.868 11.031,42.493 10.581,42.814 C10.405,42.939 10.202,43 10.001,43"/><path d="M49.999,43 C49.798,43 49.595,42.939 49.419,42.814 C48.969,42.493 48.865,41.868 49.186,41.419 L53.771,35 L49.186,28.581 C48.865,28.132 48.969,27.507 49.419,27.186 C49.867,26.865 50.492,26.969 50.814,27.419 L55.814,34.419 C56.062,34.767 56.062,35.233 55.814,35.581 L50.814,42.581 C50.619,42.854 50.311,43 49.999,43"/><path d="M20,42 C16.14,42 13,38.86 13,35 C13,31.14 16.14,28 20,28 C21.725,28 23.381,28.633 24.666,29.782 C25.077,30.15 25.112,30.782 24.744,31.193 C24.377,31.605 23.744,31.641 23.333,31.272 C22.416,30.452 21.232,30 20,30 C17.243,30 15,32.243 15,35 C15,37.757 17.243,40 20,40 C21.232,40 22.417,39.548 23.333,38.727 C23.746,38.358 24.378,38.394 24.746,38.805 C25.114,39.217 25.079,39.849 24.667,40.217 C23.383,41.367 21.726,42 20,42"/><path d="M32,42 C29.794,42 28,40.206 28,38 C28,37.448 28.448,37 29,37 C29.552,37 30,37.448 30,38 C30,39.103 30.897,40 32,40 C33.103,40 34,39.103 34,38 C34,36.897 33.103,36 32,36 C29.794,36 28,34.206 28,32 C28,29.794 29.794,28 32,28 C34.206,28 36,29.794 36,32 C36,32.552 35.552,33 35,33 C34.448,33 34,32.552 34,32 C34,30.897 33.103,30 32,30 C30.897,30 30,30.897 30,32 C30,33.103 30.897,34 32,34 C34.206,34 36,35.794 36,38 C36,40.206 34.206,42 32,42"/><path d="M43,42 C40.794,42 39,40.206 39,38 C39,37.448 39.448,37 40,37 C40.552,37 41,37.448 41,38 C41,39.103 41.897,40 43,40 C44.103,40 45,39.103 45,38 C45,36.897 44.103,36 43,36 C40.794,36 39,34.206 39,32 C39,29.794 40.794,28 43,28 C45.206,28 47,29.794 47,32 C47,32.552 46.552,33 46,33 C45.448,33 45,32.552 45,32 C45,30.897 44.103,30 43,30 C41.897,30 41,30.897 41,32 C41,33.103 41.897,34 43,34 C45.206,34 47,35.794 47,38 C47,40.206 45.206,42 43,42"/></g></g></g></symbol>
					<symbol id="jsfile" viewBox="0 0 60 60"><title>JS File</title><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-840.000000, -330.000000)" fill="#000000"><g transform="translate(840.000000, 330.000000)"><path d="M49,44 C46.243,44 44,41.757 44,39 C44,38.448 44.447,38 45,38 C45.553,38 46,38.448 46,39 C46,40.654 47.346,42 49,42 C50.654,42 52,40.654 52,39 C52,37.346 50.654,36 49,36 C46.243,36 44,33.757 44,31 C44,28.243 46.243,26 49,26 C51.757,26 54,28.243 54,31 C54,31.552 53.553,32 53,32 C52.447,32 52,31.552 52,31 C52,29.346 50.654,28 49,28 C47.346,28 46,29.346 46,31 C46,32.654 47.346,34 49,34 C51.757,34 54,36.243 54,39 C54,41.757 51.757,44 49,44"/><path d="M36,12 L44.586,12 L36,3.414 L36,12 Z M47,14 L35,14 C34.447,14 34,13.552 34,13 L34,1 C34,0.596 34.243,0.231 34.617,0.076 C34.992,-0.078 35.422,0.007 35.707,0.293 L47.707,12.293 C47.993,12.579 48.079,13.009 47.924,13.383 C47.77,13.756 47.404,14 47,14 L47,14 Z"/><path d="M28,46 L58,46 L58,24 L28,24 L28,46 Z M59,48 L27,48 C26.447,48 26,47.552 26,47 L26,23 C26,22.448 26.447,22 27,22 L59,22 C59.553,22 60,22.448 60,23 L60,47 C60,47.552 59.553,48 59,48 L59,48 Z"/><path d="M47,60 L1,60 C0.447,60 0,59.552 0,59 L0,1 C0,0.448 0.447,0 1,0 L35,0 C35.553,0 36,0.448 36,1 C36,1.552 35.553,2 35,2 L2,2 L2,58 L46,58 L46,47 C46,46.448 46.447,46 47,46 C47.553,46 48,46.448 48,47 L48,59 C48,59.552 47.553,60 47,60"/><path d="M47,24 C46.447,24 46,23.552 46,23 L46,13 C46,12.448 46.447,12 47,12 C47.553,12 48,12.448 48,13 L48,23 C48,23.552 47.553,24 47,24"/><path d="M36,43 C33.794,43 32,41.206 32,39 C32,38.448 32.447,38 33,38 C33.553,38 34,38.448 34,39 C34,40.103 34.897,41 36,41 C37.103,41 38,40.103 38,39 L38,27 C38,26.448 38.447,26 39,26 C39.553,26 40,26.448 40,27 L40,39 C40,41.206 38.206,43 36,43"/></g></g></g></symbol>
					<symbol id="jsinline" viewBox="0 0 60 60"><title>JS Code</title><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-1200.000000, -810.000000)" fill="#000000"><g transform="translate(1200.000000, 810.000000)"><path d="M2,10 L2,57 C2,57.551 2.448,58 3,58 L57,58 C57.552,58 58,57.551 58,57 L58,10 L2,10 Z M57,60 L3,60 C1.346,60 0,58.654 0,57 L0,9 C0,8.448 0.447,8 1,8 L59,8 C59.553,8 60,8.448 60,9 L60,57 C60,58.654 58.654,60 57,60 L57,60 Z"/><path d="M2,8 L58,8 L58,3 C58,2.449 57.552,2 57,2 L3,2 C2.448,2 2,2.449 2,3 L2,8 Z M59,10 L1,10 C0.447,10 0,9.552 0,9 L0,3 C0,1.346 1.346,0 3,0 L57,0 C58.654,0 60,1.346 60,3 L60,9 C60,9.552 59.553,10 59,10 L59,10 Z"/><path d="M6,5 C6,5.552 5.553,6 5,6 C4.447,6 4,5.552 4,5 C4,4.448 4.447,4 5,4 C5.553,4 6,4.448 6,5"/><path d="M10,5 C10,5.552 9.553,6 9,6 C8.447,6 8,5.552 8,5 C8,4.448 8.447,4 9,4 C9.553,4 10,4.448 10,5"/><path d="M14,5 C14,5.552 13.553,6 13,6 C12.447,6 12,5.552 12,5 C12,4.448 12.447,4 13,4 C13.553,4 14,4.448 14,5"/><path d="M11,33 C8.794,33 7,31.206 7,29 L7,25.414 L5.293,23.707 C4.902,23.316 4.902,22.684 5.293,22.293 L7,20.586 L7,17 C7,14.794 8.794,13 11,13 C11.553,13 12,13.448 12,14 C12,14.552 11.553,15 11,15 C9.897,15 9,15.897 9,17 L9,21 C9,21.265 8.895,21.52 8.707,21.707 L7.414,23 L8.707,24.293 C8.895,24.48 9,24.735 9,25 L9,29 C9,30.103 9.897,31 11,31 C11.553,31 12,31.448 12,32 C12,32.552 11.553,33 11,33"/><path d="M10,56 C9.447,56 9,55.552 9,55 C9,54.448 9.447,54 10,54 C11.103,54 12,53.103 12,52 L12,48 C12,47.735 12.105,47.48 12.293,47.293 L13.586,46 L12.293,44.707 C12.104,44.519 11.999,44.264 12,43.998 L12.008,39.998 C12.008,38.897 11.107,38 10,38 C9.447,38 9,37.552 9,37 C9,36.448 9.447,36 10,36 C12.21,36 14.008,37.794 14.008,40 L14.001,43.586 L15.707,45.293 C16.098,45.684 16.098,46.316 15.707,46.707 L14,48.414 L14,52 C14,54.206 12.206,56 10,56"/><path d="M19.999,33 C19.854,33 19.707,32.969 19.567,32.902 C17.461,31.892 15,28.693 15,23 C15,17.49 17.34,14.282 19.53,13.117 C20.018,12.857 20.623,13.042 20.883,13.53 C21.143,14.018 20.957,14.624 20.47,14.883 C18.869,15.734 17,18.371 17,23 C17,27.458 18.724,30.279 20.433,31.098 C20.931,31.337 21.141,31.934 20.901,32.432 C20.729,32.791 20.372,33 19.999,33"/><path d="M24.001,33 C23.643,33 23.297,32.808 23.117,32.47 C22.857,31.982 23.043,31.376 23.53,31.117 C25.131,30.266 27,27.629 27,23 C27,18.542 25.276,15.721 23.567,14.902 C23.069,14.663 22.859,14.066 23.099,13.568 C23.337,13.07 23.932,12.858 24.433,13.098 C26.539,14.108 29,17.307 29,23 C29,28.51 26.66,31.718 24.47,32.883 C24.32,32.962 24.159,33 24.001,33"/></g></g></g></symbol>
				</svg>
		</div>
		';
	}

	public function add_column( $cols ) {

		if ( current_user_can( 'manage_network' ) ) {
			$cols['cssjscode'] = __( 'CSS/JS' );
		}

		return $cols;
	}

	public function column_view( $column_name, $post_id ) {

		if ( 'cssjscode' == $column_name ) {

			$css      = get_post_meta( $post_id, '_' . $this->plugin_slug . '-css', true );
			$cssfiles = get_post_meta( $post_id, '_' . $this->plugin_slug . '-cssfiles', true );

			$javascript      = get_post_meta( $post_id, '_' . $this->plugin_slug . '-javascript', true );
			$javascriptfiles = get_post_meta( $post_id, '_' . $this->plugin_slug . '-javascriptfiles', true );

			if ( ! empty( $cssfiles ) ) {
				echo '<svg preserveAspectRatio="xMinYMid" viewbox="0 0 60 60"><use xlink:href="#cssfile"/></svg>';
			}
			if ( ! empty( $javascriptfiles ) ) {
				echo '<svg preserveAspectRatio="xMinYMid" viewbox="0 0 60 60"><use xlink:href="#jsfile"/></svg>';
			}
			if ( ! empty( $css ) ) {
				echo '<svg preserveAspectRatio="xMinYMid" viewbox="0 0 60 60"><use xlink:href="#cssinline"/></svg>';
			}
			if ( ! empty( $javascript ) ) {
				echo '<svg preserveAspectRatio="xMinYMid" viewbox="0 0 60 60"><use xlink:href="#jsinline"/></svg>';
			}
		}
	}
}
