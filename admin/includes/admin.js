//var jQuery, CodeMirror, ajaxurl, console, wp, _;

var WPCSSJS = function(){
	"use strict";


	/**
	 * Callback function for the 'click' event of the 'Set Footer Image'
	 * anchor in its meta box.
	 *
	 * Displays the media uploader for selecting an image.
	 *
	 * @since 0.1.0
	 */
	function renderMediaUploader(mediaType) {
	
		var file_frame, file_data;
	
		/**
		 * If an instance of file_frame already exists, then we can open it
		 * rather than creating a new instance.
		 */
		if ( undefined !== file_frame ) {
			file_frame.options.library.mediaType = mediaType;
			file_frame.open();
			return;
	 
		}
	 
		/**
		 * If we're this far, then an instance does not exist, so we need to
		 * create our own.
		 *
		 * Here, use the wp.media library to define the settings of the Media
		 * Uploader. We're opting to use the 'post' frame which is a template
		 * defined in WordPress core and are initializing the file frame
		 * with the 'insert' state.
		 *
		 * We're also not allowing the user to select more than one image.
		 */
		file_frame = wp.media.frames.file_frame = wp.media({
			frame:    "post",
			state:    "insert",
			multiple: false,
			library: {
				type: mediaType
			},
		});
	 
		console.log(file_frame);

		/**
		 * Setup an event handler for what to do when an image has been
		 * selected.
		 *
		 * Since we're using the 'view' state when initializing
		 * the file_frame, we need to make sure that the handler is attached
		 * to the insert event.
		 */
		file_frame.on( "insert", function() {

			// Read the JSON data returned from the Media Uploader
			file_data = file_frame.state().get( "selection" ).first().toJSON();
			
			if(file_data.mime == "application/javascript"){
				jQuery("#wp-css-js-javascriptfiles").append(
					"<li id='" + file_data.id + "' title='" + file_data.url + "'>" + file_data.filename + " (" + file_data.filesizeHumanReadable + ")" + "<input type='hidden' name='wp-css-js-javascriptfiles[]' value='"+ file_data.id +"'/></li>"
				);
			}

			if(file_data.mime == "text/css"){
				jQuery("#wp-css-js-cssfiles").append(
					"<li id='" + file_data.id + "' title='" + file_data.url + "'>" + file_data.filename + " (" + file_data.filesizeHumanReadable + ")" + "<input type='hidden' name='wp-css-js-cssfiles[]' value='"+ file_data.id +"'/></li>"
				);
			}

			save();
	
		});
	
		// Now display the actual file_frame
		file_frame.open();
	
	}

	jQuery( "#add-javascript" ).on( "click", function( evt ) {

		// Stop the anchor's default behavior
		evt.preventDefault();

		// Display the media uploader
		renderMediaUploader("application/javascript");
	});

	jQuery( "#add-css" ).on( "click", function( evt ) {

		// Stop the anchor's default behavior
		evt.preventDefault();

		// Display the media uploader
		renderMediaUploader("text/css");
	});


	// set up the CSS code editor
	var $cssholder = jQuery("#wp-css-js-css");
	var css = CodeMirror.fromTextArea( $cssholder[0], {
		mode: "text/css",
		//theme: "monokai",
		lineNumbers: true,
		styleActiveLine: true,
		matchBrackets: true,
		gutters: ["CodeMirror-lint-markers", "CodeMirror-linenumbers", "CodeMirror-foldgutter"],
		lint: true,
		foldGutter: true,			
	});

	// set up the Javascript editor
	var $javascriptholder = jQuery("#wp-css-js-javascript");
	var javascript = CodeMirror.fromTextArea( $javascriptholder[0], {
		mode: "javascript",
		//theme: "monokai",
		lineNumbers: true,
		styleActiveLine: true,
		matchBrackets: true,
		gutters: ["CodeMirror-lint-markers", "CodeMirror-linenumbers", "CodeMirror-foldgutter"],
		lint: true,
		foldGutter: true,
	});

	// save on ctrl-s or cmd-s
	CodeMirror.commands.save = function(){
		save();
	};
	// save on button click
	jQuery("#wp-css-js-update").click( save );

	//save back to a custom field
	function save(){
		css.save();
		javascript.save();

		var jsfiles = jQuery("#wp-css-js-javascriptfiles input").serializeArray();
		var cssfiles = jQuery("#wp-css-js-cssfiles input").serializeArray();

		var data = {
			action: "wpcssjssave",
			security: jQuery("#wp-css-js-update")[0].dataset.nonce,
			postid: jQuery("#wp-css-js-update")[0].dataset.postid,
			
			"wp-css-js-css": jQuery("#wp-css-js-css").val(),
			"wp-css-js-cssfiles": _.map(cssfiles, function(value, key, list){
				return parseInt(value.value);
			}),

			"wp-css-js-javascript": jQuery("#wp-css-js-javascript").val(),
			"wp-css-js-javascriptfiles": _.map(jsfiles, function(value, key, list){
				return parseInt(value.value);
			})
		};

		jQuery.post(ajaxurl, data, function() {
			jQuery("#wp-css-jseditor").addClass("saved");
			window.setTimeout(function(){
				jQuery("#wp-css-jseditor").removeClass("saved");
			}, 250);
		});

		return false;
	}


	jQuery("#wp-css-js-javascriptfiles button").click( deletefile );
	jQuery("#wp-css-js-cssfiles button").click( deletefile );

	function deletefile(evt){
		// Stop the anchor's default behavior
		evt.preventDefault();

		jQuery(this).parent().remove();

		console.log(evt);

		save();

		return false;

	}

};
WPCSSJS();