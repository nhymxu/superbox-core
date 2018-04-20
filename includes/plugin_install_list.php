<?php
add_action( 'tgmpa_register', 'nhymxu_mars_plugin_install_list' );
function nhymxu_mars_plugin_install_list() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$default_plugins = [
        [
            'name'      => 'Yoast SEO',
            'slug'      => 'wordpress-seo',
        ],
        [
            'name'      => 'Google XML Sitemaps',
            'slug'      => 'google-sitemap-generator',
        ],
        [
            'name'      => 'Easy WP SMTP',
            'slug'      => 'easy-wp-smtp',
        ],
        [
            'name'      => 'Contact Form 7',
            'slug'      => 'contact-form-7',
        ],
    ];

    if ( WP_DEBUG || false === ( $superbox_plugins = get_transient( 'superbox_plugins' ) ) ) {
        // It wasn't there, so regenerate the data and save the transient
        $result = wp_remote_get( 'http://sv.isvn.space/wp-update/superbox-plugins.json' );
        $superbox_plugins = '[]';
        // Check for error
        if ( !is_wp_error( $result ) ) {
            $remote_data = wp_remote_retrieve_body( $result );
            if ( !is_wp_error( $remote_data ) ) {
                $superbox_plugins = $remote_data;
                set_transient( 'superbox_plugins', $remote_data, DAY_IN_SECONDS * 2 );
            }
        }
    }
    $superbox_plugins = json_decode( $superbox_plugins, true );
    
    $plugins = array_merge( $default_plugins, $superbox_plugins );

    $config = [
		'id'           => 'tgmpa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'nhymxu-install-plugins', // Menu slug.
		'parent_slug'  => 'plugins.php',            // Parent menu slug.
		'capability'   => 'activate_plugins',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => false,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '<p><button id="btn_reload_superbox_plugins" class="button">Tải lại danh sách plugin ngay</button> (tự động làm mới danh sách mỗi 2 ngày)</p>',// Message to output right before the plugins table.
        
        /*'strings'      => [
            'page_title'                      => 'Install Recommended Plugins',
            'menu_title'                      => 'Install Plugins',
            // <snip>...</snip>
            'nag_type'                        => 'updated', // Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
        ]*/
    ];

    tgmpa( $plugins, $config );
}

function nhymxu_tgmpa_table_columns_filter( $columns ) {
    unset( $columns['source'] );
    return $columns;
}
add_filter( 'tgmpa_table_columns', 'nhymxu_tgmpa_table_columns_filter' );

function nhymxu_ajax_clear_superbox_plugins_cache() {
    delete_transient( 'superbox_plugins' );
    wp_die();
}
add_action( 'wp_ajax_clear_superbox_plugins_cache', 'nhymxu_ajax_clear_superbox_plugins_cache' );

function nhymxu_tgmpa_admin_inline_js() {
    global $pagenow;
    if ( $pagenow=='plugins.php' ):
        ?>
        <script type="text/javascript">
        jQuery( document ).ready(function() {
            jQuery('#btn_reload_superbox_plugins').click(function() {
                jQuery.ajax({
                    url : "<?=admin_url( 'admin-ajax.php' );?>",
                    type : "post",
                    data : {
                        action : "clear_superbox_plugins_cache"
                    },
                    success : function( response ) {
                        window.location.reload(true);
                    }
                });
            })
        });
        </script>
        <?php   
    endif;
}
add_action( 'admin_footer', 'nhymxu_tgmpa_admin_inline_js' );