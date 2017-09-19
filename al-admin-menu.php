<?php
	function al_admin_menu() {
        if ( current_user_can( get_option( 'al_log_user_role' ) ) ) {
            return '<p><a href="' . site_url() . '/wp-admin/admin.php?page=action-logger">' . esc_html__( 'Logs', 'action-logger' ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-log-actions">' . esc_html__( 'Log actions', 'action-logger' ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-settings">' . esc_html__( 'Misc', 'action-logger' ) . '</a></p>';
        }
    }
