<?php
    /*
    Plugin Name: Action logger
    Version: 0.1 beta
    Tags: log
    Plugin URI: https://github.com/Beee4life/action-logger
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
                register_activation_hook( __FILE__,    array( $this, 'al_plugin_activation' ) );
                register_deactivation_hook( __FILE__,  array( $this, 'al_plugin_deactivation' ) );

                // add settings link to plugin
                add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'al_plugin_link' ) );

                // actions
                add_action( 'admin_menu',                   array( $this, 'al_add_action_logger_dashboard' ) );
                add_action( 'admin_menu',                   array( $this, 'al_add_action_logger_settings_page' ) );
                add_action( 'admin_menu',                   array( $this, 'al_add_action_logger_support_page' ) );
                add_action( 'admin_init',                   array( $this, 'al_items_overview_functions' ) );
                add_action( 'admin_init',                   array( $this, 'al_admin_page_functions' ) );
                add_action( 'admin_init',                   array( $this, 'al_delete_all_logs' ) );
                add_action( 'admin_init',                   array( $this, 'al_check_log_table' ) );
                add_action( 'admin_init',                   array( $this, 'al_admin_menu' ) );
                add_action( 'admin_init',                   array( $this, 'al_errors' ) );
                add_action( 'plugins_loaded',               array( $this, 'al_load_plugin_textdomain' ) );
                add_action( 'admin_enqueue_scripts',        array( $this, 'al_enqueue_action_logger_css' ) );

                // WP Core actions
                add_action( 'user_register ',               array( $this, 'al_log_user_create' ), 10, 1 );
                add_action( 'profile_update',               array( $this, 'al_log_user_change' ), 10, 2 );
	            add_action( 'delete_user',                  array( $this, 'al_log_user_delete' ), 10, 1 );
	            add_action( 'publish_post',                 array( $this, 'al_log_post_publish' ), 10, 1 );
	            add_action( 'change_post',                  array( $this, 'al_log_post_change' ), 10, 1 );
	            add_action( 'deleted_post',                 array( $this, 'al_log_post_delete' ), 10, 1 );

	            // CSV Importer actions
	            add_action( 'csvi_successful_csv_upload',   array( $this, 'al_csvi_file_upload' ) );
	            add_action( 'csvi_successful_csv_validate', array( $this, 'al_csvi_file_validate' ) );
	            add_action( 'csvi_successful_csv_import',   array( $this, 'al_csvi_file_import' ) );

	            // EM actions
	            add_action( 'em_bookings_deleted',          array( $this, 'al_log_registration_delete' ), 10, 2 );
                add_action( 'em_booking_save',              array( $this, 'al_log_registration_cancel_reject' ), 10, 2 );

                // Shortcode
                add_shortcode( 'actionlogger',         array( $this, 'al_register_shortcode_logger' ) );

	            // $this->al_set_default_values();

            }

            // @TODO: add log rotation
            // @TODO: add IF for older php versions

            // @TODO: S2Member: add log for new (paid) registration
            // @TODO: S2Member: add log for demotion
            // @TODO: S2Member: add log for cancel
            // @TODO: S2Member: add log for reject

            /**
             * Function which runs upon plugin activation
             */
            public function al_plugin_activation() {
                $this->al_prepare_log_table();
                $this->al_set_default_values();
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
                if ( false != $available_options ) {
                    foreach( $available_options as $option ) {
                        delete_option( 'al_' . $option[ 'action_name' ] );
                    }
                }
                delete_option( 'al_available_log_actions' );
                delete_option( 'al_log_user_role' );

            }

            public function al_plugin_link( $links ) {
                $add_this = array(
                    '<a href="' . admin_url( 'admin.php?page=al-settings' ) . '">Settings</a>',
                );
                return array_merge( $links, $add_this );
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
            public function al_set_default_values() {

                $available_options = get_option( 'al_available_log_actions' );
                // $available_options = false;
                if ( false == $available_options ) {
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
	                    array(
		                    'action_name'        => 'post_published',
		                    'action_generator'   => 'WordPress',
		                    'action_title'       => esc_html( __( 'Post published', 'action-logger' ) ),
		                    'action_description' => esc_html( __( 'Logs when a post is published.', 'action-logger' ) ),
		                    'default_value'      => 1,
	                    ),
	                    array(
		                    'action_name'        => 'post_changed',
		                    'action_generator'   => 'WordPress',
		                    'action_title'       => esc_html( __( 'Post changed', 'action-logger' ) ),
		                    'action_description' => esc_html( __( 'Logs when a post is changed.', 'action-logger' ) ),
		                    'default_value'      => 1,
	                    ),
	                    array(
		                    'action_name'        => 'post_deleted',
		                    'action_generator'   => 'WordPress',
		                    'action_title'       => esc_html( __( 'Post deleted', 'action-logger' ) ),
		                    'action_description' => esc_html( __( 'Logs when a post is deleted.', 'action-logger' ) ),
		                    'default_value'      => 1,
	                    ),
                    );

                    // add option for events manager
                    $em_options        = array(
                        array(
                            'action_name'        => 'em_booking_approved',
                            'action_generator'   => 'Events Manager',
                            'action_title'       => esc_html( __( 'Booking approved', 'action-logger' ) ),
                            'action_description' => esc_html( __( 'Logs when a booking is approved.', 'action-logger' ) ),
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
                            'action_name'        => 'em_booking_rejected',
                            'action_generator'   => 'Events Manager',
                            'action_title'       => esc_html( __( 'Booking rejected', 'action-logger' ) ),
                            'action_description' => esc_html( __( 'Logs when a booking is rejected.', 'action-logger' ) ),
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
                    $all_options = array_merge( $wp_options, $em_options );

                    // csvi options
                    $csvi_options      = array(
                        array(
                            'action_name'        => 'csvi_file_uploaded',
                            'action_generator'   => 'CSV Importer',
                            'action_title'       => 'CSV file uploaded',
                            'action_description' => esc_html( __( 'Logs when a csv file is uploaded.', 'action-logger' ) ),
                            'default_value'      => 0,
                        ),
                    );
                    $all_options = array_merge( $all_options, $csvi_options );

                    // add option for IDF rankings importer
                    $ri_options      = array(
                        array(
                            'action_name'        => 'ri_file_uploaded',
                            'action_generator'   => 'Rankings Importer',
                            'action_title'       => 'CSV file uploaded',
                            'action_description' => esc_html( __( 'Logs when a csv file is uploaded', 'action-logger' ) ),
                            'default_value'      => 0,
                        ),
                    );
                    $all_options = array_merge( $all_options, $ri_options );

                    foreach ( $all_options as $option ) {
                        update_option( 'al_' . $option[ 'action_name' ], $option[ 'default_value' ] );
                    }
                    update_option( 'al_available_log_actions', $all_options );
                    update_option( 'al_log_user_role', 'manage_options' );
                }
            }

            /**
             * Adds a page to admin sidebar menu
             */
            public function al_add_action_logger_dashboard() {
                add_menu_page( 'Action Logger', 'Action Logger', get_option( 'al_log_user_role' ), 'action-logger', 'action_logger_dashboard', 'dashicons-editor-alignleft' );
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
                add_submenu_page( NULL, 'Support', 'Support', 'manage_options', 'al-misc', 'action_logger_misc_page' );
                include( 'al-misc.php' ); // content for the settings page
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

                /**
                 *
                 */
                if ( isset( $_POST[ 'active_logs_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'active_logs_nonce' ], 'active-logs-nonce' ) ) {
                        $this->al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        $get_available_actions = get_option( 'al_available_log_actions' );
                        if ( false == $get_available_actions ) {
                            $this->al_set_default_values();
                            $get_available_actions = get_option( 'al_available_log_actions' );
                        }
                        foreach( $get_available_actions as $action ) {
                            if ( isset( $_POST[ $action[ 'action_name' ] ] ) ) {
                                update_option( 'al_' . $action[ 'action_name' ], 1 );
                            }
                        }

                        update_option( 'al_log_user_role', $_POST[ 'select_cap' ] );

                        $this->al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );
                    }
                }

                /**
                 *
                 */
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
                if ( current_user_can( get_option( 'al_log_user_role' ) ) ) {
                    return '<p><a href="' . site_url() . '/wp-admin/admin.php?page=action-logger">' . esc_html( __( 'Logs', 'action-logger' ) ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-settings">' . esc_html( __( 'Settings', 'action-logger' ) ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-misc">' . esc_html( __( 'Misc', 'action-logger' ) ) . '</a></p>';
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
                    $this->al_log_user_action( 'user_registered', 'Action Logger', sprintf( esc_html( __( 'New user registered: "<a href="%s">%s</a>".', 'sexdates' ) ), get_author_posts_url( $user_id ), get_userdata( $user_id )->display_name ) );
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
                        $this->al_log_user_action( 'user_changed', 'Action Logger', sprintf( esc_html( __( '%s changed the user of %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->first_name, get_userdata( $user_id )->display_name ) );
                    }
                }
            }

	        /**
	         * Log user delete
	         *
	         * @param $user_id
	         */
	        public function al_log_user_delete( $user_id ) {
		        if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_wp_user_delete' ) ) {
			        $this->al_log_user_action( 'user_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted the user of %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->first_name, get_userdata( $user_id )->display_name ) );
		        }
	        }

	        /**
	         * Log post publish
	         *
	         * @param $user_id
	         */
	        public function al_log_post_publish( $user_id ) {
		        if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_post_published' ) ) {
			        $this->al_log_user_action( 'post_published', 'Action Logger', sprintf( esc_html( __( '%s published a post.', 'action-logger' ) ), get_userdata( get_current_user_id() )->first_name ) );
		        }
	        }

	        /**
	         * Log post change
	         *
	         * @param $user_id
	         */
	        public function al_log_post_change( $user_id ) {
		        if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_post_changed' ) ) {
			        $this->al_log_user_action( 'post_changed', 'Action Logger', sprintf( esc_html( __( '%s changed a post.', 'action-logger' ) ), get_userdata( get_current_user_id() )->first_name ) );
		        }
	        }

	        /**
	         * Log post delete
	         *
	         * @param $user_id
	         */
	        public function al_log_post_delete( $user_id ) {
		        if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_post_deleted' ) ) {
			        $this->al_log_user_action( 'post_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted a post.', 'action-logger' ) ), get_userdata( get_current_user_id() )->first_name ) );
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
                if ( false != get_option( 'al_em_booking_delete' ) ) {
                    $this->al_log_user_action( 'registration_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted bookings for an event.', 'action-logger' ) ), get_userdata( get_current_user_id() )->first_name ) );
                }
            }

            public function test_change( $EM_Event, $EM_Booking ) {
                $this->al_log_user_action( 'registration_changed', 'Action Logger' );
            }

            /**
             * Log an action when an booking is canceled or rejected
             *
             * @param $EM_Event
             * @param $EM_Booking
             */
            public function al_log_registration_change( $EM_Event, $EM_Booking ) {

                $log            = false;
                $booking_id     = $EM_Booking->booking_id;

                if ( 1 == $EM_Booking->booking_status ) {
                    $action = 'registration_approved';
                    $status = 'approved';
                    if ( 1 == get_option( 'al_em_booking_approved' ) ) {
                        $log = true;
                    }
                } elseif ( 2 == $EM_Booking->booking_status ) {
                    $action = 'registration_reject';
                    $status = 'rejected';
                    if ( 1 == get_option( 'al_em_booking_rejected' ) ) {
                        $log = true;
                    }
                } elseif ( 3 == $EM_Booking->booking_status ) {
                    $action = 'registration_cancel';
                    $status = 'canceled';
                    if ( 1 == get_option( 'al_em_booking_canceled' ) ) {
                        $log = true;
                    }
                } else {
                    $action = 'registration_unknown';
                    $status = 'unknown';
                    $log   = true;
                }

                if ( $log == true ) {
                    $this->al_log_user_action( $action, 'Action Logger', get_userdata( get_current_user_id() )->first_name . ' ' . $status . ' ' . esc_html( __( 'the booking with booking ID:', 'action-logger' ) )  . ' ' . $booking_id . '.' );
                }

            }

	        /**
	         * Log successful file upload from CSV Importer
	         * Log file upload from CSV Importer
	         *
	         * @param $user_id
	         */
	        public function al_csvi_file_upload() {
		        $user_name = get_userdata( get_current_user_id() )->first_name;
		        if ( false == $user_name ) {
			        $user_name = get_userdata( get_current_user_id() )->display_name;
		        }
		        if ( class_exists( 'CSV_Importer' ) && false != get_option( 'al_csvi_file_upload' ) ) {
			        $this->al_log_user_action( 'csv_upload', 'Action Logger', sprintf( esc_html( __( '%s successfully uploaded the file: "%s".', 'action-logger' ) ), $user_name, $_FILES[ 'csv_upload' ][ 'name' ] ) );
		        }
	        }

	        /**
	         * Log successful csv validate from CSV Importer
	         * @param $user_id
	         */
	        public function al_csvi_file_validate() {
		        $user_name = get_userdata( get_current_user_id() )->first_name;
		        if ( false == $user_name ) {
			        $user_name = get_userdata( get_current_user_id() )->display_name;
		        }
		        if ( class_exists( 'CSV_Importer' ) && false != get_option( 'al_csvi_file_validate' ) ) {
			        $this->al_log_user_action( 'csv_validate', 'Action Logger', sprintf( esc_html( __( '%s successfully validated the file: "%s".', 'action-logger' ) ), $user_name, $_FILES[ 'csv_upload' ][ 'name' ] ) );
		        }
	        }

	        /**
	         * Log successful csv import from CSV Importer
	         * @param $user_id
	         */
	        public function al_csvi_file_import() {
		        $user_name = get_userdata( get_current_user_id() )->first_name;
		        if ( false == $user_name ) {
			        $user_name = get_userdata( get_current_user_id() )->display_name;
		        }
		        if ( class_exists( 'CSV_Importer' ) && false != get_option( 'al_csvi_file_import' ) ) {
			        $this->al_log_user_action( 'csv_imported', 'Action Logger', sprintf( esc_html( __( '%s successfully imported %d lines from file.', 'action-logger' ) ), $user_name, $line_number ) );
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
