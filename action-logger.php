<?php
    /*
    Plugin Name: IDF : Action logger
    Version: 1.0.0
    Tags: log
    Plugin URI: http://www.berryplasman.com
    Description: This plugin logs several actions which are interesting to log, to know who did what, such as creating/deleting/promoting users.
    Author: Beee
    Author URI: http://berryplasman.com
    Text-domain: action-logger
    License: GPL v2

            http://www.berryplasman.com
               ___  ____ ____ ____
              / _ )/ __/  __/  __/
             / _  / _/   _/   _/
            /____/___/____/____/

    */

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    if ( ! class_exists( 'ActionLogger' ) ) :

        class ActionLogger {
            var $settings;

            /**
             *  A dummy constructor to ensure plugin is only initialized once
             */
            function __construct() {
            }

            function initialize() {
                // vars
                $this->settings = array(
                    'path'    => trailingslashit( dirname( __FILE__ ) ),
                    'version' => '1.0.0',
                );

                // (de)activation hooks
                register_activation_hook( __FILE__,     array( $this, 'al_plugin_activation' ) );
                register_deactivation_hook( __FILE__,   array( $this, 'al_plugin_deactivation' ) );
                // register_uninstall_hook( __FILE__,      array( $this, 'al_plugin_uninstall' ) );

                // actions
                add_action( 'admin_menu',            array( $this, 'al_add_action_logger_dashboard' ) );            // add menu page
                add_action( 'admin_menu',            array( $this, 'al_add_action_logger_settings_page' ) );        // add settings page
                add_action( 'admin_init',            array( $this, 'al_items_overview_functions' ) );               // functions for overview (delete)
                add_action( 'admin_init',            array( $this, 'al_check_log_table' ) );                        // functions for settings page
                add_action( 'admin_init',            array( $this, 'al_drop_log_table' ) );                         // functions for settings page
                add_action( 'admin_init',            array( $this, 'al_admin_page_functions' ) );                   // functions for settings page
                add_action( 'admin_init',            array( $this, 'al_delete_all_logs' ) );                        // functions for settings page
                add_action( 'admin_enqueue_scripts', array( $this, 'al_enqueue_action_logger_css' ) );              // enqueue css for overview

                // log actions
                add_action( 'user_register ',        array( $this, 'al_log_user_create'), 10, 1 );                  // log on user create
                add_action( 'profile_update',        array( $this, 'al_log_user_change'), 10, 2 );                  // log on user change
                add_action( 'delete_user',           array( $this, 'al_log_user_delete'), 10, 1 );                  // log on user delete
                add_action( 'em_bookings_deleted',   array( $this, 'al_log_registration_delete'), 10, 2 );          // log on registration_delete
                add_action( 'em_booking_save',       array( $this, 'al_log_registration_cancel_reject'), 10, 2 );   // log on registration_cancel/reject
                add_shortcode( 'actionlogger',  array( $this, 'al_register_shortcode_logger' ) );              // register shortcode to track pages/posts

                // $this->al_prepare_db();

                // @TODO: remove include
                include( 'get-functions.php' ); // read csv

            }

            // @TODO: check if db exists
            // @TODO: deactivation hook
            // @TODO: uninstall hook
            // @TODO: add log rotation
            // @TODO: select where to log

            // @TODO: log on publish post
            // @TODO: log on edit post
            // @TODO: log on delete post
            // @TODO: EM: add log for registration approved

            // @TODO: S2Member: add log for new registration
            // @TODO: S2Member: add log for demotion
            // @TODO: S2Member: add log for cancel
            // @TODO: S2Member: add log for reject

            // @TODO: Look into BuddyPress
            // @TODO: Look into WPSC

            public function al_plugin_activation() {
                $this->al_prepare_db();
            }

            public function al_plugin_deactivation() {
            }

            public function al_plugin_uninstall() {
                if ( false == get_option( 'al_storage_type' ) ) {
                    // $this->al_drop_log_table();
                } else {
                    delete_option( 'al_storage_type' );
                }
            }

            public function al_add_action_logger_dashboard() {
                add_menu_page( 'Action logger', 'Action logger', 'access_s2member_level4', 'action-logger', 'action_logger_dashboard', 'dashicons-editor-alignleft' );
                include( 'al-dashboard.php' ); // content for the settings page
            }

            public function al_add_action_logger_settings_page() {
                add_submenu_page( 'action-logger', 'Settings', 'Settings', 'manage_options', 'al-settings', 'action_logger_settings_page' );
                include( 'al-settings.php' ); // content for the settings page
            }

            public function al_admin_page_functions() {

                if ( isset( $_POST[ 'save_settings_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'save_settings_nonce' ], 'save-settings-nonce' ) ) {
                        idf_errors()->add( 'error_nonce_no_match', __( 'Something went wrong. Please try again.', 'idf-action-logger' ) );

                        return;
                    } else {

                        $preserve_settings = isset( $_POST[ 'preserve_settings' ] ) ? $_POST[ 'preserve_settings' ] : false;
                        if ( true == $preserve_settings ) {
                            update_option( 'al_preserve_settings', 1 );
                        } elseif ( false == $preserve_settings ) {
                            delete_option( 'al_preserve_settings' );
                        }
                    }
                }

                if ( isset( $_POST[ 'export_csv_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'export_csv_nonce' ], 'export-csv-nonce' ) ) {
                        idf_errors()->add( 'error_nonce_no_match', __( 'Something went wrong. Please try again.', 'idf-action-logger' ) );

                        return;
                    } else {

                        global $wpdb;
                        $items = $wpdb->get_results( "
                            SELECT * FROM " . $wpdb->prefix . "action_logs
                            order by id DESC
                        ");

                        if ( count( $items ) > 0 ) {

                            $array = [];
                            foreach( $items as $item ) {
                                // make array from object
                                $array[] = (array) $item;
                            }

                            $filename  = "export.csv";
                            $delimiter = ",";
                            $test      = 1;

                            $csv_header = array(
                                'id'                 => 'ID',
                                'action_time'        => 'Date/Time',
                                'action_user'        => 'User ID',
                                'action'             => 'Action',
                                'action_generator'   => 'Generated by',
                                'action_description' => 'Description'
                            );

                            header( 'Content-Type: application/csv' );
                            header( 'Content-Disposition: attachment; filename="' . $filename . '";' );

                            // open the "output" stream
                            // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
                            $f = fopen( 'php://output', 'w' );

                            fputcsv( $f, $csv_header, $delimiter );

                            foreach ( $array as $line ) {
                                fputcsv( $f, $line, $delimiter );
                            }
                            exit;
                        }
                    }
                }
            }

            public function al_items_overview_functions() {

                if ( isset( $_POST[ 'delete_action_items_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'delete_action_items_nonce' ], 'delete-actions-items-nonce' ) ) {
                        idf_errors()->add( 'error_nonce_no_match', __( 'Something went wrong. Please try again.', 'idf-action-logger' ) );

                        return;
                    } else {

                        $delete_items = ! empty( $_POST[ 'delete' ] ) ? $_POST[ 'delete' ] : false;
                        if ( isset( $_POST[ 'rows' ] ) ) {

                            if ( $_POST[ 'rows' ] ) {
                                $where = array();
                                global $wpdb;
                                foreach( $_POST[ 'rows' ] as $row_id ) {
                                    $wpdb->delete( $wpdb->prefix . 'action_logs', array( 'ID' => $row_id ) );
                                }

                                idf_errors()->add( 'success_items_deleted', __( 'All selected items are successfully deleted from the database.', 'idf-action-logger' ) );

                                return;
                            }
                        } else {
                            idf_errors()->add( 'error_no_selection', __( 'You didn\'t select any lines. If you did, then something went wrong. You should contact Beee.', 'idf-action-logger' ) );

                            return;
                        }
                    }
                }
            }

            public function al_delete_all_logs() {

                if ( isset( $_POST[ 'delete_all_logs_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'delete_all_logs_nonce' ], 'delete-all-logs-nonce' ) ) {
                        idf_errors()->add( 'error_nonce_no_match', __( 'Something went wrong. Please try again.', 'idf-action-logger' ) );

                        return;
                    } else {

                        $delete_all = ! empty( $_POST[ 'delete_all' ] ) ? $_POST[ 'delete_all' ] : false;

                        if ( false != $delete_all ) {

                            // drop_db
                            $this->al_drop_log_table( true );
                            idf_errors()->add( 'success_logs_deleted', __( 'All logs deleted.', 'idf-action-logger' ) );

                            return;

                        }
                    }
                }
            }

            public static function al_log_user_action( $action = 'none', $action_generator = 'none', $action_description = 'none' ) {


                $timestamp          = strtotime( date( 'Y-m-d  H:i:s', strtotime( '+' . get_option( 'gmt_offset' ) . ' hours' ) ) );
                $action_description = esc_attr( $action_description );

                do_action( 'before_log_user_action' );

                global $wpdb;
                $sql_data = array(
                    'action_time'        => $timestamp,
                    'action_user'        => get_current_user_id(),
                    'action'             => $action,
                    'action_generator'   => $action_generator,
                    'action_description' => $action_description,
                );
                $wpdb->insert( $wpdb->prefix . 'action_logs', $sql_data );

                do_action( 'after_log_user_action' );

            }

            public function al_prepare_db() {

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                ob_start();
                global $wpdb;
                ?>
                DROP TABLE IF EXISTS <?php echo $wpdb->prefix; ?>action_logs;
                CREATE TABLE <?php echo $wpdb->prefix; ?>action_logs (
                id int(5) unsigned NOT NULL auto_increment,
                action_time int(14) unsigned NOT NULL,
                action_user int(5) unsigned NOT NULL,
                action varchar(50) NULL,
                action_generator varchar(50) NULL,
                action_description varchar(100) NOT NULL,
                PRIMARY KEY (id)
                );
                <?php
                $sql = ob_get_clean();
                dbDelta( $sql );

            }

            public function al_check_log_table() {

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                ob_start();
                global $wpdb;
                ?>
                CREATE TABLE IF NOT EXISTS <?php echo $wpdb->prefix; ?>action_logs (
                id int(5) unsigned NOT NULL auto_increment,
                action_time int(14) unsigned NOT NULL,
                action_user int(5) unsigned NOT NULL,
                action varchar(50) NULL,
                action_generator varchar(50) NULL,
                action_description varchar(100) NOT NULL,
                PRIMARY KEY (id)
                );
                <?php
                $sql = ob_get_clean();
                dbDelta( $sql );

            }

            public function al_register_shortcode_logger( $attributes ) {
                $post_title = get_the_title();
                $attributes = shortcode_atts( array(
                    'action'  => 'page_visit',
                    'generator'  => 'shortcode on ' . $post_title . '"',
                    'message' => 'visited "' . $post_title . '"',
                ), $attributes, 'actionlogger' );

                if ( ! is_admin() ) {
                    $this->al_log_user_action( $attributes[ 'action' ], $attributes[ 'generator' ], get_userdata( get_current_user_id() )->display_name . ' ' . $attributes[ 'message' ] );
                }

                return ;
            }

            public function al_drop_log_table( $drop = false ) {

                if ( false != $drop ) {
                    global $wpdb;
                    $prefix = $wpdb->get_blog_prefix();
                    $wpdb->query( 'DROP TABLE ' . $prefix . 'action_logs' );
                }
            }

            /**
             * Enqueue CSS
             */
            public function al_enqueue_action_logger_css() {
                wp_register_style( 'action-logger', plugins_url( 'style.css', __FILE__ ), false, '1.0' );
                wp_enqueue_style( 'action-logger' );
            }

            /**
             * Default Wordpress actions
             * These functions hooks into default WP actions like user register, change and delete
             */

            /**
             * Log user creation
             *
             * @param $user_id
             */
            public function al_log_user_create( $user_id ) {
                if ( class_exists( 'ActionLogger' ) ) {
                    ActionLogger::al_log_user_action( 'user_registered', 'action-logger', 'New user registered: "' . get_userdata( $user_id )->display_name . '".' );
                }
            }

            /**
             * Log user change
             *
             * @param $user_id
             * @param $old_user_data
             */
            public function al_log_user_change( $user_id, $old_user_data ) {
                // don't log when a a user edits his own profile
                if ( $user_id != get_current_user_id() ) {
                    if ( class_exists( 'ActionLogger' ) ) {
                        ActionLogger::al_log_user_action( 'user_changed', 'action-logger', get_userdata( get_current_user_id() )->first_name . ' changed the user of ' . get_userdata( $user_id )->first_name . ' ' . get_userdata( $user_id )->last_name );
                    }
                }
            }

            /**
             * Log user delete
             * @param $user_id
             */
            public function al_log_user_delete( $user_id ) {
                if ( class_exists( 'ActionLogger' ) ) {
                    ActionLogger::al_log_user_action( 'user_deleted', 'action-logger', get_userdata( get_current_user_id() )->first_name . ' deleted the user of ' . get_userdata( $user_id )->first_name . ' ' . get_userdata( $user_id )->last_name );
                }
            }

            /**
             * Events manager actions
             */

            /**
             * Log an action when a registration is deleted
             *
             * @param $result
             * @param $booking_ids
             */
            public function al_log_registration_delete( $result, $booking_ids ) {
                if ( class_exists( 'ActionLogger' ) ) {
                    ActionLogger::al_log_user_action( 'registration_deleted', 'action-logger', get_userdata( get_current_user_id() )->first_name . ' deleted bookings for an event.' );
                }
            }

            /**
             * Log an action when an booking is canceled or rejected
             *
             * @param $EM_Event
             * @param $EM_Booking
             */
            public function al_log_registration_cancel_reject( $EM_Event, $EM_Booking ) {

                if ( true == $EM_Event ) {

                    $booking_id     = $EM_Booking->booking_id;
                    $booking_user   = $EM_Booking->person_id;
                    $booking_status = $EM_Booking->booking_status;
                    if ( 2 == $EM_Booking->booking_status ) {
                        $action = 'registration_reject';
                        $status = 'rejected';
                    } elseif ( 3 == $EM_Booking->booking_status ) {
                        $action = 'registration_cancel';
                        $status = 'canceled';
                    } else {
                        $action = 'registration_unknown';
                        $status= 'unknown';
                    }

                    if ( class_exists( 'ActionLogger' ) ) {
                            ActionLogger::al_log_user_action( $action, 'action-logger', get_userdata( get_current_user_id() )->first_name . ' ' . $status . ' the booking with booking ID: ' . $booking_id );
                    }
                }
            }

        }

        /**
         * The main function responsible for returning the one true ActionLogger instance to functions everywhere.
         *
         * @return \ActionLogger
         */
        function init_action_logger_plugin() {
            global $action_logger_plugin;

            if ( ! isset( $action_logger_plugin ) ) {
                $action_logger_plugin = new ActionLogger();
                $action_logger_plugin->initialize();
            }

            return $action_logger_plugin;
        }

        // initialize
        init_action_logger_plugin();

    endif; // class_exists check
