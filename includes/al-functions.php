<?php
    /**
     * Get all available actions
     *
     * @return array
     */
    function al_get_available_actions() {

        $wp_options = array(
            array(
                'action_name'        => 'wp_user_create',
                'action_generator'   => 'WordPress',
                'action_title'       => esc_html( __( 'Create user', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a new user is created in WordPress.', 'action-logger' ) ),
                'default_value'      => 1,
            ),
            array(
                'action_name'        => 'wp_user_change',
                'action_generator'   => 'WordPress',
                'action_title'       => esc_html( __( 'Change user', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a user is changed (by another user) in WordPress.', 'action-logger' ) ),
                'default_value'      => 1,
            ),
            array(
                'action_name'        => 'wp_user_delete',
                'action_generator'   => 'WordPress',
                'action_title'       => esc_html( __( 'Delete user', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a user is deleted in WordPress.', 'action-logger' ) ),
                'default_value'      => 1,
            ),
            array(
                'action_name'        => 'user_visit_visitor',
                'action_generator'   => 'WordPress',
                'action_title'       => esc_html( __( 'User visit (visitor)', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a registered user visits a post/page with the shortcode on it.', 'action-logger' ) ),
                'default_value'      => 1,
            ),
            array(
                'action_name'        => 'user_visit_registered',
                'action_generator'   => 'WordPress',
                'action_title'       => esc_html( __( 'User visit (registered)', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a visitor visits a post/page with the shortcode on it.', 'action-logger' ) ),
                'default_value'      => 1,
            ),
        );
        $all_options = $wp_options;

        // add option for events manager (NOT IN USE YET)
        $em_options        = array(
            array(
                'action_name'        => 'em_booking_pending',
                'action_generator'   => 'Events Manager',
                'action_title'       => esc_html( __( 'Booking pending', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a booking is pending.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
            array(
                'action_name'        => 'em_booking_approved',
                'action_generator'   => 'Events Manager',
                'action_title'       => esc_html( __( 'Booking approved', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a booking is approved.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
            array(
                'action_name'        => 'em_booking_rejected',
                'action_generator'   => 'Events Manager',
                'action_title'       => esc_html( __( 'Booking rejected', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a booking is rejected.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
            array(
                'action_name'        => 'em_booking_canceled',
                'action_generator'   => 'Events Manager',
                'action_title'       => esc_html( __( 'Booking canceled', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a booking is canceled.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
            array(
                'action_name'        => 'em_booking_aw_onl_payment',
                'action_generator'   => 'Events Manager',
                'action_title'       => esc_html( __( 'Booking awaiting online payment', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a booking is awaiting online payment.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
            array(
                'action_name'        => 'em_booking_aw_ofl_payment',
                'action_generator'   => 'Events Manager',
                'action_title'       => esc_html( __( 'Booking awaiting offline payment', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a booking is awaiting offline payment.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
            array(
                'action_name'        => 'em_booking_deleted',
                'action_generator'   => 'Events Manager',
                'action_title'       => esc_html( __( 'Booking deleted', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a booking is deleted.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
        );
        // $all_options = array_merge( $wp_options, $em_options );

        // csvi options (NOT IN USE YET)
        $csvi_options      = array(
            array(
                'action_name'        => 'csvi_file_uploaded',
                'action_generator'   => 'CSV Importer',
                'action_title'       => esc_html( __( 'CSV file uploaded', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a csv file is uploaded.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
            array(
                'action_name'        => 'csvi_file_validated',
                'action_generator'   => 'CSV Importer',
                'action_title'       => esc_html( __( 'CSV file validated', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when a csv file is validated.', 'action-logger' ) ),
                'default_value'      => 0,
            ),
            array(
                'action_name'        => 'csvi_file_imported',
                'action_generator'   => 'CSV Importer',
                'action_title'       => esc_html( __( 'CSV data imported', 'action-logger' ) ),
                'action_description' => esc_html( __( 'Logs when csv data is imported', 'action-logger' ) ),
                'default_value'      => 0,
            ),
        );
        // $all_options = array_merge( $all_options, $csvi_options );

        return $all_options;

    }

    function al_get_pagination( $get, $pages ) {

        if ( $get == false || $pages == 1 ) {
            return false;
        }

        $big = 999999999; // need an unlikely integer
        if ( isset( $get[ 'paged' ] ) ) {
            $page_number = $get[ 'paged' ];
        } else {
            $page_number = 1;
        }
        $pagination_args = array(
            'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'    => '/page/%#%',
            'total'     => $pages,
            'current'   => max(1, $page_number),
            'show_all'  => false,
            'end_size'  => 3,
            'mid_size'  => 2,
            'prev_next' => false,
            'prev_text' => __( '&laquo; Previous', 'action-logger' ),
            'next_text' => __( 'Next &raquo;', 'action-logger' ),
            'type'      => 'list',
        );
        $pagination = sprintf( '<div class="paginator">%s</div>', paginate_links( $pagination_args ) );

        return $pagination;

    }

    /**
     * Replace vars in log message
     *
     * @param $action_user
     * @param $log_message
     * @param $post_id
     *
     * @return bool|mixed
     */
    function al_replace_log_vars( $user_id, $log_message, $post_id ) {

        if ( false == $log_message ) {
            return false;
        }

        if ( strpos( $log_message, '#user#' ) !== false ) {
            $user_data   = get_userdata( $user_id );
            $user_name   = $user_data->display_name;
            $log_message = str_replace( '#user#', $user_name, $log_message );
        }
        if ( strpos( $log_message, '#permalink#' ) !== false ) {
            $log_message = str_replace( '#permalink#', get_the_permalink( $post_id ), $log_message );
        }

        // this code exists for a custom plugin I am using
        if ( strpos( $log_message, '#orderlink#' ) !== false ) {
            $log_message = str_replace( '#orderlink#', site_url() . '/wp-admin/admin.php?page=sd8-orders', $log_message );
        }

        return $log_message;
    }
