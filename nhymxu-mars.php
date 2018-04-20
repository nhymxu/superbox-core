<?php
/*
Plugin Name: Mars system
Plugin URI: https://dungnt.net/
Description: Mars core plugin
Version: 2.9.0
Author: Dũng Nguyễn (nhymxu)
Author URI: https://dungnt.net/
License: me
*/

define('NHYMXU_MARS_DIR', __DIR__ . DIRECTORY_SEPARATOR );
define('NHYMXU_MARS_VERSION', 20900);

/**
 * Auto generate deeplink from accesstrade_userid
 */
function nhymxu_generate_deeplink( $url ) {
	$at_userid = get_option('accesstrade_userid');

	if( !$at_userid )
		return $url;

	$utm_source = '&utm_source=superbox';

	return 'https://pub.accesstrade.vn/deep_link/'. $at_userid .'?url=' . rawurlencode( $url ) . $utm_source;
}

require NHYMXU_MARS_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';

require_once( NHYMXU_MARS_DIR . 'includes/coupon.php' );
require_once( NHYMXU_MARS_DIR . 'includes/product.php' );
require_once( NHYMXU_MARS_DIR . 'includes/tweak.php' );
require_once( NHYMXU_MARS_DIR . 'includes/api.php' );
require_once( NHYMXU_MARS_DIR . 'includes/remote-notice.php' );
require_once( NHYMXU_MARS_DIR . 'includes/class-tgm-plugin-activation.php' );
require_once( NHYMXU_MARS_DIR . 'includes/plugin_install_list.php' );
require_once( NHYMXU_MARS_DIR . 'includes/deeplink_shortcode.php' );

function nhymxu_at_deeplink_shortcode( $atts, $content = null ) {
	$a = shortcode_atts( ['url' => ''], $atts );

	if( $a['url'] == '' && filter_var($content, FILTER_VALIDATE_URL) ) {
		return '<a href="'.nhymxu_generate_deeplink( $content ).'" target="_blank">' . $content . '</a>';
	} else if( $content != null && $content != '' ) {
		return '<a href="'.nhymxu_generate_deeplink( $a['url'] ).'" target="_blank">' . do_shortcode($content) . '</a>';
	}
}
add_shortcode( 'at', 'nhymxu_at_deeplink_shortcode' );
add_shortcode( 'deeplink', 'nhymxu_at_deeplink_shortcode' );

$nhymxu_update_checker = Puc_v4_Factory::buildUpdateChecker(
	'http://sv.isvn.space/wp-update/plugin-mars-core.json', //Metadata URL.
	__FILE__, //Full path to the main plugin file.
	'nhymxu-mars' //Plugin slug. Usually it's the same as the name of the directory.
);

function nhymxu_mars_plugin_update() {
	$prev_version = get_option( 'nhymxu_mars_version' );
	if( !$prev_version ) {
		$prev_version = 0;
	}

    if ( $prev_version != NHYMXU_MARS_VERSION ) {
        nhymxu_mars_do_plugin_update( $prev_version );
    }
}
add_action( 'plugins_loaded', 'nhymxu_mars_plugin_update' );

function nhymxu_mars_do_plugin_update( $prev_version ) {
    global $wpdb;

	if( $prev_version < 150 ) {
		$wpdb->query("ALTER TABLE {$wpdb->prefix}coupons ADD save VARCHAR(20) NOT NULL DEFAULT '';");
		$wpdb->query("ALTER TABLE {$wpdb->prefix}coupons CHANGE sale code VARCHAR(60) NOT NULL DEFAULT '';");
	}
	if( $prev_version < 196 ) {
		$wpdb->query( "create table {$wpdb->prefix}coupon_categories( id int(10) unsigned auto_increment primary key, name varchar(250) default '' null, slug varchar(100) default '' not null);" );
		$wpdb->query( "create index slug on {$wpdb->prefix}coupon_categories (slug);" );
		$wpdb->query( "create table {$wpdb->prefix}coupon_category_rel( coupon_id int not null, category_id int not null, constraint coupon_id unique (coupon_id, category_id));" );
	}
	if( $prev_version < 20500 ) {
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}coupons CHANGE `url` `url` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;" );
	}

	if( $prev_version <= 20801 ) {
		$wpdb->query('ALTER TABLE '. $wpdb->prefix .'coupons CHANGE type type VARCHAR(100);');
		$wpdb->query('ALTER TABLE '. $wpdb->prefix .'coupons CHANGE code code VARCHAR(100);');
		$wpdb->query('ALTER TABLE '. $wpdb->prefix .'coupons CHANGE save save VARCHAR(100);');
	}

	if( $prev_version < 20803 ) {
		$option = ['uid' => '', 'accesskey' => '', 'utmsource' => ''];
		$uid = get_option('accesstrade_userid');
		$option['uid'] = $uid;
		update_option('nhymxu_at_coupon', $option);
	}

	update_option( 'nhymxu_mars_version', NHYMXU_MARS_VERSION );
}

function nhymxu_mars_auto_update_specific_plugins( $update, $item ) {
    // Array of plugin slugs to always auto-update
    $plugins = [
		'nhymxu-mars'
	];
    if ( in_array( $item->slug, $plugins ) ) {
        return true; // Always update plugins in this array
    } else {
        return $update; // Else, use the normal API response to decide whether to update or not
    }
}
add_filter( 'auto_update_plugin', 'nhymxu_mars_auto_update_specific_plugins', 10, 2 );

function nhymxu_hot_update() {
	require_once( NHYMXU_MARS_DIR . 'includes/Rest_Upgrader_Skin.php' );
	require_once( NHYMXU_MARS_DIR . 'includes/Rest_Update.php' );
	$rest_update = new Nhymxu_Rest_Update();
	$rest_update->process_request();
}
add_action( 'wp_ajax_nhymxu-hot-update', 'nhymxu_hot_update' );
add_action( 'wp_ajax_nopriv_nhymxu-hot-update', 'nhymxu_hot_update' );
