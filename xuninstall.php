<?php
    
    // if uninstall.php is not called by WordPress, die
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        die;
    }
    
    /* Remove WP e-Commerce options */
    $keys = array(
        'al_available_log_actions'
    );
    foreach ( $keys as $key ) {
        delete_option( $key );
    }
    
    global $wpdb;
    
    /* Delete Action Logger table */
    $wpdb->query( "DROP TABLE `" . $wpdb->prefix . "action_logs`" );
