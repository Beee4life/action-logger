<?php
	function al_admin_menu() {
        if ( current_user_can( get_option( 'al_log_user_role' ) ) ) {
            return '<p><a href="' . site_url() . '/wp-admin/admin.php?page=action-logger">' . esc_html( __( 'Logs', 'action-logger' ) ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-settings">' . esc_html( __( 'Settings', 'action-logger' ) ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-misc">' . esc_html( __( 'Misc', 'action-logger' ) ) . '</a></p>';
        }
    }
