<?php
	function al_admin_menu() {

		$string = false;

		if ( current_user_can( 'manage_options' ) ) {
			$string = '<p><a href="' . site_url() . '/wp-admin/admin.php?page=action-logger">' . esc_html__( 'Logs', 'action-logger' ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-log-actions">' . esc_html__( 'Log actions', 'action-logger' ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-settings">' . esc_html__( 'Settings', 'action-logger' ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-misc">' . esc_html__( 'Misc', 'action-logger' ) . '</a></p>';
		}

		return $string;
	}
