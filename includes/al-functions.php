<?php
	/**
	 * Get all available actions
	 *
	 * @return array
	 */
	function get_available_actions() {

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
			// array(
			// 	'action_name'        => 'post_published',
			// 	'action_generator'   => 'WordPress',
			// 	'action_title'       => esc_html( __( 'Post published', 'action-logger' ) ),
			// 	'action_description' => esc_html( __( 'Logs when a post is published.', 'action-logger' ) ),
			// 	'default_value'      => 1,
			// ),
			// array(
			// 	'action_name'        => 'post_changed',
			// 	'action_generator'   => 'WordPress',
			// 	'action_title'       => esc_html( __( 'Post changed', 'action-logger' ) ),
			// 	'action_description' => esc_html( __( 'Logs when a post is changed.', 'action-logger' ) ),
			// 	'default_value'      => 1,
			// ),
			// array(
			// 	'action_name'        => 'post_pending',
			// 	'action_generator'   => 'WordPress',
			// 	'action_title'       => esc_html( __( 'Post pending', 'action-logger' ) ),
			// 	'action_description' => esc_html( __( 'Logs when a post is marked as pending review.', 'action-logger' ) ),
			// 	'default_value'      => 1,
			// ),
			// array(
			// 	'action_name'        => 'post_deleted',
			// 	'action_generator'   => 'WordPress',
			// 	'action_title'       => esc_html( __( 'Post deleted', 'action-logger' ) ),
			// 	'action_description' => esc_html( __( 'Logs when a post is deleted.', 'action-logger' ) ),
			// 	'default_value'      => 1,
			// ),
			// array(
			// 	'action_name'        => 'post_republished',
			// 	'action_generator'   => 'WordPress',
			// 	'action_title'       => esc_html( __( 'Post republished', 'action-logger' ) ),
			// 	'action_description' => esc_html( __( 'Logs when a post is published again (from pending review).', 'action-logger' ) ),
			// 	'default_value'      => 1,
			// ),
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

		// add option for IDF rankings importer (NOT IN USE YET)
		$ri_options      = array(
			array(
				'action_name'        => 'ri_data_nuked',
				'action_generator'   => 'Rankings Importer',
				'action_title'       => 'All data nuked',
				'action_description' => esc_html( __( 'Logs when all data is deleted', 'action-logger' ) ),
				'default_value'      => 1,
			),
			array(
				'action_name'        => 'ri_rankings_deleted',
				'action_generator'   => 'Rankings Importer',
				'action_title'       => esc_html( __( 'Ind. rankings deleted', 'action-logger' ) ),
				'action_description' => esc_html( __( 'Logs when individual rankings are deleted.', 'action-logger' ) ),
				'default_value'      => 1,
			),
			array(
				'action_name'        => 'ri_import_raw',
				'action_generator'   => 'Rankings Importer',
				'action_title'       => esc_html( __( 'Raw CSV import', 'action-logger' ) ),
				'action_description' => esc_html( __( 'Logs when raw data is imported', 'action-logger' ) ),
				'default_value'      => 1,
			),
			array(
				'action_name'        => 'ri_data_verified',
				'action_generator'   => 'Rankings Importer',
				'action_title'       => esc_html( __( 'CSV file verified', 'action-logger' ) ),
				'action_description' => esc_html( __( 'Logs when a csv file is succssful verified', 'action-logger' ) ),
				'default_value'      => 1,
			),
			array(
				'action_name'        => 'ri_rankings_imported',
				'action_generator'   => 'Rankings Importer',
				'action_title'       => esc_html( __( 'Rankings imported from file', 'action-logger' ) ),
				'action_description' => esc_html( __( 'Logs when a rankings are imported', 'action-logger' ) ),
				'default_value'      => 1,
			),
			array(
				'action_name'        => 'ri_file_uploaded',
				'action_generator'   => 'Rankings Importer',
				'action_title'       => esc_html( __( 'CSV file uploaded', 'action-logger' ) ),
				'action_description' => esc_html( __( 'Logs when a csv file is uploaded', 'action-logger' ) ),
				'default_value'      => 1,
			),
		);
		// $all_options = array_merge( $all_options, $ri_options );

		return $all_options;

	}

	function al_get_pagination( $get = false, $pages ) {

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
	function al_replace_log_vars( $action_user, $log_message, $post_id ) {
	    
	    if ( false == $log_message ) {
	        return false;
        }
        
        $replaced_description = $log_message;
        if ( strpos( $replaced_description, '#permalink#') !== false ) {
            $replaced_description = str_replace( '#permalink#', get_the_permalink( $post_id ), $replaced_description );
        }
        if ( strpos( $replaced_description, '#post_title#') !== false ) {
            $replaced_description = str_replace( '#post_title#', get_the_title( $post_id ), $replaced_description );
        }
        if ( $action_user > 0 ) {
            $replaced_description = str_replace( '#user#', get_userdata( $action_user )->display_name, $replaced_description );
        } else {
            $replaced_description = str_replace( '#user#', 'A visitor', $replaced_description );
        }
        
        return $replaced_description;
    }
