<?php
    
    // If uninstall.php is not called by WordPress, die
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        die;
    }
    
    // Remove Action Logger options
    $options   = get_option( 'al_available_log_actions' );
    $options[] = 'available_log_actions';
    foreach ( $options as $key ) {
        delete_option( 'al_' . $key );
    }
    
    // If preserve settings is false
    if ( false == get_option( 'al_preserve_settings' ) ) {
        /* Delete Action Logger table */
        global $wpdb;
        $wpdb->query( "DROP TABLE `" . $wpdb->prefix . "action_logs`" );
    } else {
        delete_option( 'al_preserve_settings' );
    }
