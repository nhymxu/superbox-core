<?php

function get_at_notice() {
    if ( WP_DEBUG || false === ( $html = get_transient( 'at_thongbao' ) ) ) {
        // It wasn't there, so regenerate the data and save the transient
        $results = wp_remote_get( 'http://sv.isvn.space/api/v1/mars/thongbao' );
        $html = wp_remote_retrieve_body( $results );
        set_transient( 'at_thongbao', $html, DAY_IN_SECONDS / 4 );
    }
   
    return $html;
}