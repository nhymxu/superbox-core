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

/**
 * Add Smart Tag to header
 */
function nhymxu_mars_smarttag_enable() {
    $at_userid = get_option('accesstrade_userid');

    if( !$at_userid )
        return '';

    $utm_source = 'superbox';
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
