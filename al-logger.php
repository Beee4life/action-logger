<?php

	/**
	 * This is the actual logger function, which is called at the place where you want to log something.
	 *
	 * @param string $action
	 * @param string $action_generator
	 * @param string $action_description
	 */
	function al_log_user_action( $action = false, $action_generator = false, $action_description = false ) {

        if ( false != $action_description ) {
            global $wpdb;
            $sql_data = array(
                'action_time'        => strtotime( date( 'Y-m-d  H:i:s', strtotime( '+' . get_option( 'gmt_offset' ) . ' hours' ) ) ),
                'action_user'        => get_current_user_id(),
                'action'             => $action,
                'action_generator'   => $action_generator,
                'action_description' => $action_description,
            );
            $db_status = $wpdb->insert( $wpdb->prefix . 'action_logs', $sql_data );
        }

    }

