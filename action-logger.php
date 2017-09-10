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

                // actions
                add_action( 'admin_menu',            array( $this, 'al_add_action_logger_dashboard' ) );
                add_action( 'admin_menu',            array( $this, 'al_add_action_logger_settings_page' ) );
                // add_action( 'admin_menu',            array( $this, 'al_add_action_logger_support_page' ) );
                add_action( 'admin_init',            array( $this, 'al_items_overview_functions' ) );
                add_action( 'admin_init',            array( $this, 'al_admin_page_functions' ) );
                add_action( 'admin_init',            array( $this, 'al_delete_all_logs' ) );
                add_action( 'admin_init',            array( $this, 'al_check_log_table' ) );
                add_action( 'admin_init',            array( $this, 'al_admin_menu' ) );
                add_action( 'admin_init',            array( $this, 'al_errors' ) );
                add_action( 'plugins_loaded',        array( $this, 'al_load_plugin_textdomain' ) );
                add_action( 'admin_enqueue_scripts', array( $this, 'al_enqueue_action_logger_css' ) );

                // log actions
                add_action( 'user_register ',        array( $this, 'al_log_user_create'), 10, 1 );
                add_action( 'profile_update',        array( $this, 'al_log_user_change'), 10, 2 );
                add_action( 'delete_user',           array( $this, 'al_log_user_delete'), 10, 1 );
                add_action( 'em_bookings_deleted',   array( $this, 'al_log_registration_delete'), 10, 2 );
                add_action( 'em_booking_save',       array( $this, 'al_log_registration_cancel_reject'), 10, 2 );
                add_shortcode( 'actionlogger',  array( $this, 'al_register_shortcode_logger' ) );
                
            }
    
            // @TODO: change s2member role
            // @TODO: restrict to roles
            // @TODO: add log rotation
    
            // @TODO: log on publish post
            // @TODO: log on edit post
            // @TODO: log on delete post
            
            // @TODO: S2Member: add log for new registration
            // @TODO: S2Member: add log for demotion
            // @TODO: S2Member: add log for cancel
            // @TODO: S2Member: add log for reject
    
            /**
             * Function which runs upon plugin deactivation
             */
            public function al_plugin_activation() {
                $this->al_prepare_log_table();
                $this->al_store_available_actions();
            }
    
            /**
             * Function which runs upon plugin deactivation
             *
             * Stored options and option statuses are deleted on plugin deactivate to keep the database clean,
             * because several people delete plugins by ftp instead of uninstalling them.
             * These values will be re-initiated upon plugin activation.
             */
            public function al_plugin_deactivation() {
    
                $available_options = get_option( 'al_available_log_actions' );
                foreach( $available_options as $option ) {
                    delete_option( 'al_' . $option[ 'action_name' ] );
                }
                delete_option( 'al_available_log_actions' );
                
            }
    
            /**
             * Function which runs on plugin deletion through the admin panel.
             *
             * The only thing left to do is to nuke the database, unless the preserve option is checked, because
             * all stored values are already deleted upon plugin deactivation.
             */
    
            /**
             * Drop table if exists ( upon plugin activation).
             * Then create a new empty table.
             */
            public function al_prepare_log_table() {
        
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
    
            /**
             * This runs on each page load, to make sure the database table exists.
             * This because the database can be deleted manually.
             */
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
    
            /**
             * Load language files
             */
            public function al_load_plugin_textdomain() {
                load_plugin_textdomain( 'action-logger', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
            }
    
            /**
             * Here we built a simple array of available log actions and store them in an option value.
             */
            public function al_store_available_actions() {
    
                $available_options = get_option( 'al_available_log_actions' );
                // $available_options = false;
                if ( false == $available_options ) {
                    $available_options = array(
                        array(
                            'action_name'        => 'wp_user_create',
                            'action_generator'   => 'WordPress',
                            'action_title'       => 'Create user',
                            'action_description' => esc_html( __( 'Logs when a new user is created in WordPress.', 'action-logger' ) ),
                        ),
                        array(
                            'action_name'        => 'wp_user_change',
                            'action_generator'   => 'WordPress',
                            'action_title'       => 'Change user',
                            'action_description' => esc_html( __( 'Logs when a user is changed (by another user) in WordPress.', 'action-logger' ) ),
                        ),
                        array(
                            'action_name'        => 'wp_user_delete',
                            'action_generator'   => 'WordPress',
                            'action_title'       => 'Delete user',
                            'action_description' => esc_html( __( 'Logs when a user is deleted in WordPress', 'action-logger' ) ),
                        ),
                    );
                    $user_options      = array(
                        array(
                            'action_name'        => 'user_visit_visitor',
                            'action_generator'   => 'WordPress',
                            'action_title'       => 'User visit (visitor)',
                            'action_description' => esc_html( __( 'Logs when a registered user visits a post/page with the shortcode on it.', 'action-logger' ) ),
                        ),
                        array(
                            'action_name'        => 'user_visit_registered',
                            'action_generator'   => 'WordPress',
                            'action_title'       => 'User visit (registered)',
                            'action_description' => esc_html( __( 'Logs when a visitor visits a post/page with the shortcode on it.', 'action-logger' ) ),
                        ),
                    );
                    $available_options = array_merge( $available_options, $user_options );
                    if ( class_exists( 'EM_Events' ) ) {
                        $em_options        = array(
                            array(
                                'action_name'        => 'em_booking_delete',
                                'action_generator'   => 'Events Manager',
                                'action_title'       => 'Booking delete',
                                'action_description' => esc_html( __( 'Logs when a booking is deleted', 'action-logger' ) ),
                            ),
                            array(
                                'action_name'        => 'em_booking_cancel_reject',
                                'action_generator'   => 'Events Manager',
                                'action_title'       => 'Booking canceled/rejected',
                                'action_description' => esc_html( __( 'Logs when a booking is canceled or rejected', 'action-logger' ) ),
                            ),
                        );
                        $available_options = array_merge( $available_options, $em_options );
                    }
                    foreach ( $available_options as $option ) {
                        update_option( 'al_' . $option[ 'action_name' ], true );
                    }
                    update_option( 'al_available_log_actions', $available_options );
                }
            }
    
            /**
             * Adds a page to admin sidebar menu
             */
            public function al_add_action_logger_dashboard() {
                add_menu_page( 'Action logger', 'Action Logger', 'edit_users', 'action-logger', 'action_logger_dashboard', 'dashicons-editor-alignleft' );
                include( 'al-dashboard.php' ); // content for the settings page
            }
    
            /**
             * Adds a (hidden) settings page, only through the menu on top of the pages.
             */
            public function al_add_action_logger_settings_page() {
                add_submenu_page( NULL, 'Settings', 'Settings', 'manage_options', 'al-settings', 'action_logger_settings_page' );
                include( 'al-settings.php' ); // content for the settings page
            }
    
            /**
             * Adds a (hidden) settings page, only through the menu on top of the pages.
             */
            public function al_add_action_logger_support_page() {
                add_submenu_page( NULL, 'Support', 'Support', 'manage_options', 'al-support', 'action_logger_support_page' );
                include( 'al-support.php' ); // content for the settings page
            }
    
            /**
             * @return WP_Error
             */
            public static function al_errors() {
                static $wp_error; // Will hold global variable safely
                return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error( null, null, null ) );
            }
    
            /**
             * Displays error messages from form submissions
             */
            public static function al_show_admin_notices() {
                if ( $codes = ActionLogger::al_errors()->get_error_codes() ) {
                    if ( is_wp_error( ActionLogger::al_errors() ) ) {
        
                        // Loop error codes and display errors
                        $error      = false;
                        $span_class = false;
                        $prefix     = false;
                        foreach ( $codes as $code ) {
                            if ( strpos( $code, 'success' ) !== false ) {
                                $span_class = 'notice-success ';
                                $prefix     = false;
                            } elseif ( strpos( $code, 'warning' ) !== false ) {
                                $span_class = 'notice-warning ';
                                $prefix     = esc_html( __( 'Warning', 'action-logger' ) );
                            } elseif ( strpos( $code, 'info' ) !== false ) {
                                $span_class = 'notice-info ';
                                $prefix     = false;
                            } else {
                                $error  = true;
                                $prefix = esc_html( __( 'Error', 'action-logger' ) );
                            }
                        }
                        echo '<div class="notice ' . $span_class . 'is-dismissible">';
                        foreach( $codes as $code ) {
                            $message = ActionLogger::al_errors()->get_error_message( $code );
                            echo '<div class="">';
                            if ( true == $prefix ) {
                                echo '<strong>' . $prefix . ':</strong> ';
                            }
                            echo $message;
                            echo '</div>';
                            echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html( __( 'Dismiss this notice', 'action-logger' ) ) . '</span></button>';
                        }
                        echo '</div>';
                    }
                }
            }
    
            /**
             * All form action for the settings page, except the nuke database action
             */
            public function al_admin_page_functions() {
        
                if ( isset( $_POST[ 'active_logs_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'active_logs_nonce' ], 'active-logs-nonce' ) ) {
                        $this->al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );
                
                        return;
                    } else {
                
                        $get_available_actions = get_option( 'al_available_log_actions' );
                        if ( false == $get_available_actions ) {
                            $this->al_store_available_actions();
                            $get_available_actions = get_option( 'al_available_log_actions' );
                        }
                        foreach( $get_available_actions as $action ) {
                            if ( ! isset( $_POST[ $action[ 'action_name' ] ] ) ) {
                                delete_option( 'al_' . $action[ 'action_name' ] );
                            } else {
                                update_option( 'al_' . $action[ 'action_name' ], 1 );
                            }
                        }
                        $this->al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );
                    }
                }
        
                if ( isset( $_POST[ 'preserve_settings_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'preserve_settings_nonce' ], 'preserve-settings-nonce' ) ) {
                        $this->al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );
                
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
        
                /**
                 * Export data to CSV
                 */
                if ( isset( $_POST[ 'export_csv_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'export_csv_nonce' ], 'export-csv-nonce' ) ) {
                        $this->al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );
                
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
    
            /**
             * Function for the overview page. Right now only delete rows is available (for admins only)
             */
            public function al_items_overview_functions() {
        
                if ( isset( $_POST[ 'delete_action_items_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'delete_action_items_nonce' ], 'delete-actions-items-nonce' ) ) {
                        $this->al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );
                
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
                        
                                $this->al_errors()->add( 'success_items_deleted', esc_html( __( 'All selected items are successfully deleted from the database.', 'action-logger' ) ) );
                        
                                return;
                            }
                        } else {
                            $this->al_errors()->add( 'error_no_selection', esc_html( __( 'You didn\'t select any lines. If you did, then something went wrong.', 'action-logger' ) ) );
                    
                            return;
                        }
                    }
                }
            }
    
            /**
             * Delete all logs (truncate the table)
             */
            public function al_delete_all_logs() {
        
                if ( isset( $_POST[ 'delete_all_logs_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'delete_all_logs_nonce' ], 'delete-all-logs-nonce' ) ) {
                        $this->al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );
                
                        return;
                    } else {
                
                        $delete_all = $_POST[ 'delete_all' ];
                        if ( false != $delete_all ) {
                            // truncate table
                            $this->al_truncate_log_table( true );
                            $this->al_errors()->add( 'success_logs_deleted', esc_html( __( 'All logs deleted.', 'action-logger' ) ) );
                    
                            return;
                        }
                    }
                }
            }
    
            /**
             * This is the actual logger function, which is called at the place where you want to log something.
             *
             * @param string $action
             * @param string $action_generator
             * @param string $action_description
             */
            public static function al_log_user_action( $action = false, $action_generator = false, $action_description = false ) {
        
                do_action( 'before_log_user_action' );
        
                if ( false != $action_description ) {
                    global $wpdb;
                    $sql_data = array(
                        'action_time'        => strtotime( date( 'Y-m-d  H:i:s', strtotime( '+' . get_option( 'gmt_offset' ) . ' hours' ) ) ),
                        'action_user'        => get_current_user_id(),
                        'action'             => esc_html( $action ),
                        'action_generator'   => esc_html( $action_generator ),
                        'action_description' => esc_html( $action_description ),
                    );
                    $db_status = $wpdb->insert( $wpdb->prefix . 'action_logs', $sql_data );
                    if ( false == $db_status ) {
                        if ( get_current_user_id() == 27 ) {
                            die('Log wasn\'t stored');
                        }
                    }
                }
        
                do_action( 'after_log_user_action' );
        
            }
    
            public function al_register_shortcode_logger( $attributes ) {
    
                $post_title                = get_the_title();
                $post_type                 = get_post_type();
                $log_loggedin              = get_option( 'al_user_visit_registered' );
                $log_visitor               = get_option( 'al_user_visit_visitor' );
                $log_it                    = true;
                $attributes                = shortcode_atts( array(
                    'message' => ' visited ' . $post_title,
                ), $attributes, 'actionlogger' );

                if ( is_user_logged_in() ) {
                    $user = get_userdata( get_current_user_id() )->display_name;
                    if ( false == $log_loggedin ) {
                        $log_it = false;
                    }
                } else {
                    $user = 'A visitor';
                    if ( false == $log_visitor ) {
                        $log_it = false;
                    }
                }
        
                if ( ! is_admin() && true == $log_it ) {
                    $this->al_log_user_action( $post_type . '_visit', 'Shortcode', $user . $attributes[ 'message' ] );
                }
        
                return;
            }
    
            public function al_truncate_log_table( $truncate = false ) {
        
                if ( false != $truncate ) {
                    global $wpdb;
                    $prefix = $wpdb->get_blog_prefix();
                    $wpdb->query( 'TRUNCATE TABLE ' . $prefix . 'action_logs' );
                }
            }
    
            /**
             * Enqueue CSS
             */
            public function al_enqueue_action_logger_css() {
                wp_register_style( 'action-logger', plugins_url( 'style.css', __FILE__ ), false, '1.0' );
                wp_enqueue_style( 'action-logger' );
            }
    
            public static function al_admin_menu() {
                if ( current_user_can( 'manage_options' ) ) {
                    return '<p><a href="' . site_url() . '/wp-admin/admin.php?page=action-logger">' . esc_html( __( 'Logs', 'action-logger' ) ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-settings">' . esc_html( __( 'Settings', 'action-logger' ) ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-support">' . esc_html( __( 'Support', 'action-logger' ) ) . '</a></p>';
                }
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
                if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_wp_user_create' ) ) {
                    $this->al_log_user_action( 'user_registered', 'Action Logger', 'New user registered: "' . get_userdata( $user_id )->display_name . '".' );
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
                    if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_wp_user_change' ) ) {
                        $this->al_log_user_action( 'user_changed', 'Action Logger', get_userdata( get_current_user_id() )->first_name . ' ' . esc_html( __( 'changed the user of', 'action-logger' ) ) . ' ' . get_userdata( $user_id )->first_name . ' ' . get_userdata( $user_id )->last_name . '.' );
                    }
                }
            }
    
            /**
             * Log user delete
             * @param $user_id
             */
            public function al_log_user_delete( $user_id ) {
                if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_wp_user_delete' ) ) {
                    $this->al_log_user_action( 'user_deleted', 'Action Logger', get_userdata( get_current_user_id() )->first_name . ' ' . esc_html( __( 'deleted the user of', 'action-logger' ) ) . ' ' . get_userdata( $user_id )->first_name . ' ' . get_userdata( $user_id )->last_name . '.' );
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
                if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_wp_em_booking_delete' ) ) {
                    $this->al_log_user_action( 'registration_deleted', 'Action Logger', get_userdata( get_current_user_id() )->first_name . ' ' . esc_html( __( 'deleted bookings for an event.', 'action-logger' ) ) );
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
            
                    if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_wp_em_booking_cancel_reject' ) ) {
                        $this->al_log_user_action( $action, 'Action Logger', get_userdata( get_current_user_id() )->first_name . ' ' . $status . ' ' . esc_html( __( 'the booking with booking ID:', 'action-logger' ) )  . ' ' . $booking_id . '.' );
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
