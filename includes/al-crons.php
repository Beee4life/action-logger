<?php
	function al_cron_jobs() {
	$purge_logs_after = get_option( 'al_purge_logs', 0 );
	// only purge when it's not set to forever/0
	if ( 0 != $purge_logs_after ) {
		$now_ts           = current_time( 'timestamp' );
		$purge_range      = $purge_logs_after * 24 * 60 * 60;
		$purge_date       = $now_ts - $purge_range;

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"
                            DELETE FROM {$wpdb->prefix}action_logs
                            WHERE action_time < %d
                            ",
				$now_ts
			)
		);
	}
}
add_action( 'al_cron_purge_logs', 'al_cron_jobs' );
