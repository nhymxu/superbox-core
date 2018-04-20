<?php
/*
Some wp-admin tweak
Version: 1.0.0
*/

/*
 * Hide super admin account in users list
 */
function nhymxu_pre_user_query( $user_search ) {
	$user = wp_get_current_user();
	if ( $user->ID != 1 ) { // Is not super administrator, remove super administrator
		global $wpdb;
		$user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1 AND {$wpdb->users}.ID<>1", $user_search->query_where );
	}
}

/*function nhymxu_pre_user_query( $user_search ) {
	$user = wp_get_current_user();
	if( $user->user_login !== 'admin' ) { // Is not super administrator, remove super administrator
		global $wpdb;
		$user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1 AND {$wpdb->users}.user_login != 'admin'", $user_search->query_where );
	}
}*/
add_action('pre_user_query','nhymxu_pre_user_query');

function nhymxu_user_list_table_views($views){
   $users = count_users();
   $admins_num = $users['avail_roles']['administrator'] - 1;
   $all_num = $users['total_users'] - 1;
   $class_adm = ( strpos($views['administrator'], 'current') === false ) ? "" : "current";
   $class_all = ( strpos($views['all'], 'current') === false ) ? "" : "current";
   $views['administrator'] = '<a href="users.php?role=administrator" class="' . $class_adm . '">' . translate_user_role('Administrator') . ' <span class="count">(' . $admins_num . ')</span></a>';
   $views['all'] = '<a href="users.php" class="' . $class_all . '">' . __('All') . ' <span class="count">(' . $all_num . ')</span></a>';
   return $views;
}
add_filter("views_users", "nhymxu_user_list_table_views");

/**
 * Add All Custom Post Types to search
 *
 * Returns the main $query.
 *
 * @access      public
 * @since       1.0
 * @return      $query
*/
function nhymxu_mars_add_cpts_to_search($query) {
    // Check to verify it's search page
    if( is_search() ) {
        // Get post types
        $post_types = get_post_types(array('public' => true, 'exclude_from_search' => false), 'objects');
        $searchable_types = array();
        // Add available post types
        if( $post_types ) {
            foreach( $post_types as $type) {
                $searchable_types[] = $type->name;
            }
        }
        $query->set( 'post_type', $searchable_types );
    }
    return $query;
}
add_action( 'pre_get_posts', 'nhymxu_mars_add_cpts_to_search' );

/*
 * Disable WordPress import/export tool
 */
function nhymxu_mars_remove_ietool_menu()
{
    $user = wp_get_current_user();

    if ( $user->ID != 1 ) {
        remove_submenu_page( 'tools.php', 'export.php' );
        remove_submenu_page( 'tools.php', 'import.php' );
        remove_submenu_page( 'plugins.php', 'plugin-install.php' );
    }
}
add_action( 'admin_menu', 'nhymxu_mars_remove_ietool_menu' );

function nhymxu_prevent_url_iotool_access()
{
    $user = wp_get_current_user();

    if ( $user->ID != 1 ) {
        exit;
    }
}
add_action( 'admin_head-export.php', 'nhymxu_prevent_url_iotool_access' );
add_action( 'admin_head-import.php', 'nhymxu_prevent_url_iotool_access' );
add_action( 'admin_head-plugin-install.php', 'nhymxu_prevent_url_iotool_access' );

/*
 * Force enable maintenance
 * Using when need suspend website
 */
add_action( 'init', function() {
    if( file_exists( WP_CONTENT_DIR . '/suspend.php' ) ) {
        header( 'HTTP/1.1 Service Unavailable', true, 503 );
        header( 'Content-Type: text/html; charset=utf-8' );

        require_once( WP_CONTENT_DIR . '/suspend.php' );

        die();
    }
});

/*
 * Prevent user change domain in admin
 */
add_action( 'admin_head-options-general.php', function() {
    $domain = get_option( 'siteurl' );
    define('WP_HOME', $domain);
    define('WP_SITEURL', $domain);
});

add_action( 'admin_head-options-reading.php', function() {
    echo '<style>#front-static-pages{ display: none; }</style>';
});

add_action( 'admin_head-plugins.php', function() {
    echo '<style>div.wrap a.page-title-action{ display: none; }</style>';
});

function nhymxu_remove_customize_sfp( $wp_customize ) {
    //All our sections, settings, and controls will be added here
    $wp_customize->remove_section( 'static_front_page');

}
add_action( 'customize_register', 'nhymxu_remove_customize_sfp', 50 );

function nhymxu_prevent_delete_specialpage($allcaps, $caps, $args) {
    $page_front = get_option('page_on_front');
    $page_blog = get_option('page_for_posts');
    if ( isset( $args[0], $args[2] ) && ($args[2] == $page_front || $args[2] == $page_blog) && ($args[0] == 'delete_post' || $args[0] == 'trash_post') ) {
        $allcaps[ $caps[0] ] = false;
    }
    return $allcaps;
}
add_filter ('user_has_cap', 'nhymxu_prevent_delete_specialpage', 10, 3);

/**
 * Add Smart Tag to header
 */
function nhymxu_mars_smarttag_enable() {
    $at_userid = get_option('accesstrade_userid');

    if( !$at_userid )
        return '';

    $utm_source = 'superbox';
    if( file_exists( WP_CONTENT_DIR . '/svecom.txt' ) ) {
        $utm_source = 'svecom';
    }
    ?>
    <script type="text/javascript">
    var __atsmarttag = { pub_id: '<?=$at_userid;?>', utm_source: '<?=$utm_source;?>' };
    (function () {
        var script = document.createElement('script');
        script.src = '//static.accesstrade.vn/js/atsmarttag.min.js?v=1.1.0';
        script.type = 'text/javascript';
        script.async = true;
        (document.getElementsByTagName('head')[0]||document.getElementsByTagName('body')[0]).appendChild(script);
    })();
    </script>
    <?php
}
add_action('wp_head', 'nhymxu_mars_smarttag_enable');
