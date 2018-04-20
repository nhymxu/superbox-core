<?php
//================================================================================
/*
 * Cron cleanup oldate coupons
 */
// Custom Cron Recurrences
function nhymxu_weekly_cron_job_recurrence( $schedules ) {
	$schedules['weekly'] = array(
		'display' => 'weekly',
		'interval' => 604800,
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'nhymxu_weekly_cron_job_recurrence' );

// Schedule Cron Job Event
function nhymxu_weekly_cron_job() {
	if ( ! wp_next_scheduled( '' ) ) {
		wp_schedule_event( time(), 'weekly', '' );
	}
}
add_action( 'wp', 'nhymxu_weekly_cron_job' );

if( !file_exists( WP_CONTENT_DIR . '/coupons_cron_installed' ) ) {
    if (! wp_next_scheduled ( 'nhymxu_coupon_weekly_event' )) {
		wp_schedule_event(time(), 'weekly', 'nhymxu_coupon_weekly_event');
    }

    file_put_contents( WP_CONTENT_DIR . '/coupons_cron_installed', time() );
}

function do_nhymxu_coupon_weekly() {
	global $wpdb;

	date_default_timezone_set('Asia/Ho_Chi_Minh');

	$today = date('Y-m-d');

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}coupons WHERE exp < %s", $today ) );
}
add_action('nhymxu_coupon_weekly_event', 'do_nhymxu_coupon_weekly');
