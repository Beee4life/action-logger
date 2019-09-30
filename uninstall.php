<?php

    // If uninstall.php is not called by WordPress, die
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        die;
    }

    // If preserve settings is false
    if ( false == get_option( 'al_preserve_settings' ) ) {

        /* Delete Action Logger table */
        global $wpdb;
        $wpdb->query( "DROP TABLE `" . $wpdb->prefix . "action_logs`" );

        $options   = get_option( 'al_available_log_actions' );
        $actions   = array();
        foreach ( $options as $key => $value ) {
            $actions[] = $value['action_name'];
        }
        $actions[] = 'al_available_log_actions';
        $actions[] = 'al_log_user_role';
        $actions[] = 'al_posts_per_page';
        foreach ( $actions as $action ) {
            delete_option( 'al_' . $action );
        }

    }
