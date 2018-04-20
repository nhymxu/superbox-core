<?php

class nhymxu_at_deeplink {
	public function __construct() {
		add_action("admin_print_footer_scripts", [$this, 'shortcode_button_script']);	
		add_action( 'init', [$this,'tinymce_new_button'] );			
	}

	function shortcode_button_script() {
		if(wp_script_is("quicktags")):
			?>
			<script type="text/javascript">
			//this function is used to retrieve the selected text from the text editor
			function getSel()
			{
				var txtarea = document.getElementById("content");
				var start = txtarea.selectionStart;
				var finish = txtarea.selectionEnd;
				return txtarea.value.substring(start, finish);
			}

			QTags.addButton( 
				"at_shortcode", 
				"AT Deeplink", 
				callback
			);

			function callback()
			{
				var selected_text = getSel();
				if( selected_text == '' ) {
					selected_text = 'dien_ten_san_pham';
				}
				QTags.insertContent('[at url="dien_link_san_pham"]' +  selected_text + '[/at]');
			}
			</script>
			<?php
		endif;
	}

	function tinymce_new_button() {
		add_filter("mce_external_plugins", [$this,'tinymce_add_button']);
		add_filter("mce_buttons", [$this,'tinymce_register_button']);	
	}

	function tinymce_add_button($plugin_array) {
		//enqueue TinyMCE plugin script with its ID.
		$plugin_array["at_deeplink_button"] =  plugin_dir_url(__FILE__) . "visual-editor-button.js";
		return $plugin_array;
	}

	function tinymce_register_button($buttons) {
		//register buttons with their id.
		array_push($buttons, "at_deeplink_button");
		return $buttons;
	}
}

new nhymxu_at_deeplink();