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

    // @TODO: add log rotation

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
                add_action( 'admin_menu',                   array( $this, 'al_add_action_logger_actions_page' ) );
                add_action( 'admin_menu',                   array( $this, 'al_add_settings_page' ) );
                add_action( 'admin_menu',                   array( $this, 'al_add_action_logger_misc_page' ) );
                add_action( 'admin_init',                   array( $this, 'al_delete_selected_items' ) );
                add_action( 'admin_init',                   array( $this, 'al_delete_all_logs' ) );
                add_action( 'admin_init',                   array( $this, 'al_errors' ) );
                add_action( 'admin_init',                   array( $this, 'al_log_actions_functions' ) );
                add_action( 'admin_init',                   array( $this, 'al_settings_page_functions' ) );
                add_action( 'admin_init',                   array( $this, 'al_check_log_table' ) );
                add_action( 'plugins_loaded',               array( $this, 'al_load_plugin_textdomain' ) );
                add_action( 'admin_enqueue_scripts',        array( $this, 'al_enqueue_action_logger_css' ) );

	            // Shortcode
	            add_shortcode( 'actionlogger',         array( $this, 'al_register_shortcode_logger' ) );

	            // WP Core actions
                add_action( 'user_register ',               array( $this, 'al_log_user_create' ), 10, 1 );
                add_action( 'profile_update',               array( $this, 'al_log_user_change' ), 10, 2 );
	            add_action( 'delete_user',                  array( $this, 'al_log_user_delete' ), 10, 1 );
                add_action( 'transition_post_status',       array( $this, 'al_post_status_transitions'), 10, 3 );

                // CSV Importer actions
	            add_action( 'csvi_successful_csv_upload',   array( $this, 'al_csvi_file_upload' ) );
	            add_action( 'csvi_successful_csv_validate', array( $this, 'al_csvi_file_validate' ) );
	            add_action( 'csvi_successful_csv_import',   array( $this, 'al_csvi_file_import' ) );

	            // EM actions
	            add_action( 'em_bookings_deleted',          array( $this, 'al_log_registration_delete' ), 10, 2 );
                // add_action( 'em_booking_save',              array( $this, 'al_log_registration_change' ), 10, 2 );
                // add_action( 'em_booking_save',              array( $this, 'test_change' ), 20, 2 );

                // Rankings Importer actions
                add_action( 'ri_all_data_nuked',            array( $this, 'al_ri_all_nuked' ) );
                add_action( 'ri_delete_user_rankings',      array( $this, 'al_ri_user_rankings_delete' ) );
                add_action( 'ri_import_raw',                array( $this, 'al_ri_import_raw_data' ) );
                add_action( 'ri_verify_csv',                array( $this, 'al_ri_verify_csv' ) );
                add_action( 'ri_rankings_imported',         array( $this, 'al_ri_rankings_imported' ) );
                add_action( 'ri_csv_file_upload',           array( $this, 'al_ri_csv_uploaded' ) );

	            // $this->al_set_default_values();

	            // includes
                include( 'al-admin-menu.php' );
	            include( 'al-functions.php' );
                include( 'al-logger.php' );
                include( 'al-help-tab.php' );
                include( 'al-available-actions.php' );

            }

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
	            if ( false == get_option( 'al_preserve_settings' ) ) {
	                $this->al_truncate_log_table( true );
		            delete_option( 'al_preserve_settings' );
	            }

            }

            /**
             * Adds a link to plugin actions
             * @param $links
             * @return array
             */
            public function al_plugin_link( $links ) {
                $add_this = array(
                    '<a href="' . admin_url( 'admin.php?page=al-settings' ) . '">' . esc_html__( 'Settings', 'action-logger' ) . '</a>',
                );
                return array_merge( $links, $add_this );
            }

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
             * Here we build a simple array of available log actions and store them in an option value.
             */
            public function al_set_default_values() {

                $available_options = get_option( 'al_available_log_actions' );
                // $available_options = false;
                if ( false == $available_options ) {

                    $all_options = get_available_actions();
                    foreach ( $all_options as $option ) {
                        update_option( 'al_' . $option[ 'action_name' ], $option[ 'default_value' ] );
                    }
                    update_option( 'al_available_log_actions', $all_options );
                    update_option( 'al_log_user_role', 'manage_options' );
                }
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
             * Adds a page to admin sidebar menu
             */
            public function al_add_action_logger_dashboard() {
                global $my_plugin_hook;
                $my_plugin_hook = add_menu_page( 'Action Logger', 'Action Logger', 'manage_options', 'action-logger', 'action_logger_dashboard', 'dashicons-editor-alignleft' );
                include( 'al-dashboard.php' ); // content for the settings page
            }

            /**
             * Adds a (hidden) settings page, only through the menu on top of the pages.
             */
            public function al_add_action_logger_actions_page() {
                add_submenu_page( NULL, 'Log actions', 'Log actions', 'manage_options', 'al-log-actions', 'action_logger_actions_page' );
                include( 'al-log-actions.php' ); // content for the settings page
            }

            /**
             * Adds a (hidden) settings page, only through the menu on top of the pages.
             */
            public function al_add_settings_page() {
                add_submenu_page( NULL, 'Log actions', 'Log actions', 'manage_options', 'al-settings', 'action_logger_settings_page' );
                include( 'al-settings.php' ); // content for the settings page
            }

            /**
             * Adds a (hidden) support page, only through the menu on top of the pages.
             */
            public function al_add_action_logger_misc_page() {
                add_submenu_page( NULL, 'Misc', 'Misc', 'manage_options', 'al-misc', 'action_logger_misc_page' );
                include( 'al-misc.php' ); // content for the settings page

            }

            /**
             * All form action for the settings page, except the nuke database action
             */
            public function al_log_actions_functions() {

                /**
                 * Update who can manage
                 */
                if ( isset( $_POST[ 'active_logs_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'active_logs_nonce' ], 'active-logs-nonce' ) ) {
                        ActionLogger::al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

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
                            } else {
                                update_option( 'al_' . $action[ 'action_name' ], 0 );
                            }
                        }
                        ActionLogger::al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );

                    }
                }
            }

            /**
             * All form action for the settings page, except the nuke database action
             */
            public function al_settings_page_functions() {

	            /**
	             * Update who can manage
	             */
                if ( isset( $_POST[ 'settings_page_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'settings_page_nonce' ], 'settings-page-nonce' ) ) {
                        ActionLogger::al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        if ( isset( $_POST[ 'select_cap' ] ) ) {
                            update_option( 'al_log_user_role', $_POST[ 'select_cap' ] );
                        }
                        ActionLogger::al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );

                    }
                }

                /**
                 * Export data to CSV
                 */
                if ( isset( $_POST[ 'export_csv_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'export_csv_nonce' ], 'export-csv-nonce' ) ) {
                        ActionLogger::al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

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

                /**
                 * Preserve settings
                 */
                if ( isset( $_POST[ 'preserve_settings_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'preserve_settings_nonce' ], 'preserve-settings-nonce' ) ) {
                        ActionLogger::al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

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

            }

            /**
             * Function for the overview page. Right now only delete rows is available (for admins only)
             */
            public function al_delete_selected_items() {

                if ( isset( $_POST[ 'delete_action_items_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'delete_action_items_nonce' ], 'delete-actions-items-nonce' ) ) {
                        ActionLogger::al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        $delete_items = ! empty( $_POST[ 'delete_selected' ] ) ? $_POST[ 'delete_selected' ] : false;
                        if ( isset( $_POST[ 'rows' ] ) ) {

                            if ( $_POST[ 'rows' ] ) {
                                $where = array();
                                global $wpdb;
                                foreach( $_POST[ 'rows' ] as $row_id ) {
                                    $wpdb->delete( $wpdb->prefix . 'action_logs', array( 'ID' => $row_id ) );
                                }

                                ActionLogger::al_errors()->add( 'success_items_deleted', esc_html( __( 'All selected items are successfully deleted from the database.', 'action-logger' ) ) );

                                return;
                            }
                        } else {
                            ActionLogger::al_errors()->add( 'error_no_selection', esc_html( __( 'You didn\'t select any lines. If you did, then something went wrong.', 'action-logger' ) ) );

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
                        ActionLogger::al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

	                    $delete_all = ! empty( $_POST[ 'delete_all' ] ) ? $_POST[ 'delete_all' ] : false;
                        if ( false != $delete_all ) {
                            // truncate table
                            $this->al_truncate_log_table( true );
                            ActionLogger::al_errors()->add( 'success_logs_deleted', esc_html( __( 'All logs deleted.', 'action-logger' ) ) );

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
	        public static function xal_log_user_action( $action = false, $action_generator = false, $action_description = false ) {

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

		        $post_title   = get_the_title();
		        $post_link    = get_the_permalink();
		        $post_type    = get_post_type();
		        $log_loggedin = get_option( 'al_user_visit_registered' );
		        $log_visitor  = get_option( 'al_user_visit_visitor' );
		        $log_it       = true;
		        $attributes   = shortcode_atts( array(
	                'message' => 'visited <a href="' . $post_link . '">' . $post_title . '</a>',
                ), $attributes, 'actionlogger' );

                if ( is_user_logged_in() ) {
	                $user = get_userdata( get_current_user_id() )->display_name;
                    if ( false == $log_loggedin ) {
                        $log_it = false;
                    }
                } else {
	                $user = esc_html__( 'A visitor', 'action-logger' );
                    if ( false == $log_visitor ) {
                        $log_it = false;
                    }
                }

                if ( ! is_admin() && true == $log_it ) {
                    al_log_user_action( $post_type . '_visit', 'Shortcode', $user . ' ' . $attributes[ 'message' ] );
	                // al_log_user_action( $post_type . '_visit', 'Shortcode', 'message' );
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

            public function al_admin_menu() {
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
                    al_log_user_action( 'user_registered', 'Action Logger', sprintf( esc_html( __( 'New user registered: "<a href="%s">%s</a>".', 'sexdates' ) ), get_author_posts_url( $user_id ), get_userdata( $user_id )->display_name ) );
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
                        al_log_user_action( 'user_changed', 'Action Logger', sprintf( esc_html( __( '%s changed the user of %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, get_userdata( $user_id )->display_name ) );
                    }
                }
            }

	        /**
	         * Log user delete
	         *
	         * @param $user_id int
	         */
	        public function al_log_user_delete( $user_id ) {
		        if ( class_exists( 'ActionLogger' ) && false != get_option( 'al_wp_user_delete' ) ) {
			        al_log_user_action( 'user_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted the user of %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, get_userdata( $user_id )->display_name ) );
		        }
	        }

            /**
             * Function to check post transitions and log those
             *
             * @param $new_status string
             * @param $old_status string
             * @param $post       object
             */
	        public function al_post_status_transitions( $new_status, $old_status, $post ) {
                if ( class_exists( 'ActionLogger' ) ) {
                    if ( $old_status == 'draft' && $new_status == 'publish' ) {
                        // draft > publish
                        al_log_user_action( 'post_published', 'Action Logger', sprintf( esc_html( __( '%s published %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, '<a href="' . get_the_permalink( $post->ID ) . '">' . $post->post_title . '</a>' ) );
                    } elseif ( $old_status == 'pending' && $new_status == 'publish' ) {
                        // pending > publish
                        al_log_user_action( 'post_published', 'Action Logger', sprintf( esc_html( __( '%s re-published %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, '<a href="' . get_the_permalink( $post->ID ) . '">' . $post->post_title . '</a>' ) );
                    } elseif ( $old_status == 'publish' && $new_status == 'publish' ) {
                        // publish > publish
                        al_log_user_action( 'post_edited', 'Action Logger', sprintf( esc_html( __( '%s edited published post %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, '<a href="' . get_the_permalink( $post->ID ) . '">' . $post->post_title . '</a>' ) );
                    } elseif ( $old_status == 'publish' && $new_status != 'publish' ) {
                        // X > !publish
                        if ( $old_status == 'publish' && $new_status == 'trash' ) {
                            // publish > trash
                            al_log_user_action( 'post_trashed', 'Action Logger', sprintf( esc_html( __( '%s deleted %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, '<a href="' . get_the_permalink( $post->ID ) . '">' . $post->post_title . '</a>' ) );
                        } elseif ( $old_status == 'publish' && $new_status == 'pending' ) {
                            // publish > pending
                            // die('XYZ');
                            al_log_user_action( 'post_pending', 'Action Logger', sprintf( esc_html( __( '%s marked %s as \'pending review\'.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, '<a href="' . get_the_permalink( $post->ID ) . '">' . $post->post_title . '</a>' ) );
                        }
                    }
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
                    al_log_user_action( 'registration_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted bookings for an event.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name ) );
                }
            }

            public function test_change( $EM_Event, $EM_Booking ) {
                // empty
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
                    al_log_user_action( $action, 'Action Logger', get_userdata( get_current_user_id() )->display_name . ' ' . $status . ' ' . esc_html( __( 'the booking with booking ID:', 'action-logger' ) )  . ' ' . $booking_id . '.' );
                }

            }

	        /**
	         * Log successful file upload from CSV Importer
	         * Log file upload from CSV Importer
	         *
	         * @param $user_id
	         */
	        public function al_csvi_file_upload() {
		        $user_name = get_userdata( get_current_user_id() )->display_name;
		        if ( false == $user_name ) {
			        $user_name = get_userdata( get_current_user_id() )->display_name;
		        }
		        if ( class_exists( 'CSV_Importer' ) && false != get_option( 'al_csvi_file_uploaded' ) ) {
			        al_log_user_action( 'csv_upload', 'Action Logger', sprintf( esc_html( __( '%s successfully uploaded the file: "%s".', 'action-logger' ) ), $user_name, $_FILES[ 'csv_upload' ][ 'name' ] ) );
		        }
	        }

	        /**
	         * Log successful csv validate from CSV Importer
	         * @param $user_id
	         */
	        public function al_csvi_file_validate() {
		        $user_name = get_userdata( get_current_user_id() )->display_name;
		        if ( false == $user_name ) {
			        $user_name = get_userdata( get_current_user_id() )->display_name;
		        }
		        if ( class_exists( 'CSV_Importer' ) && false != get_option( 'al_csvi_file_validated' ) ) {
			        al_log_user_action( 'csv_validate', 'Action Logger', sprintf( esc_html( __( '%s successfully validated the file: "%s".', 'action-logger' ) ), $user_name, $_FILES[ 'csv_upload' ][ 'name' ] ) );
		        }
	        }

	        /**
	         * Log successful csv import from CSV Importer
	         * @param $user_id
	         */
	        public function al_csvi_file_import( $line_number ) {
		        $user_name = get_userdata( get_current_user_id() )->display_name;
		        if ( false == $user_name ) {
			        $user_name = get_userdata( get_current_user_id() )->display_name;
		        }
		        if ( class_exists( 'CSV_Importer' ) && false != get_option( 'al_csvi_file_imported' ) ) {
			        al_log_user_action( 'csv_imported', 'Action Logger', sprintf( esc_html( __( '%s successfully imported %d lines from file.', 'action-logger' ) ), $user_name, $line_number ) );
		        }
	        }

	        public function al_ri_all_nuked() {
		        if ( false != get_option( 'al_ri_data_nuked' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        al_log_user_action( 'nuke_all', 'Rankings Importer', sprintf( esc_html__( '%s nuked all rankings.', 'action-logger' ), $user ) );
		        }
	        }

	        public function al_ri_user_rankings_delete( $user_id, $value_array ) {
		        if ( false != get_option( 'al_ri_rankings_deleted' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        al_log_user_action( 'individual_ranking_deleted', 'Rankings Importer', ' deleted ' . count( $value_array ) . ' ranking lines for ' . get_userdata( $user_id )->display_name );
		        }
	        }

	        public function al_ri_import_raw_data( $count ) {
		        if ( false != get_option( 'al_ri_import_raw' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        al_log_user_action( 'import_raw', 'Rankings Importer', ' uploaded ' . $count . ' lines through raw import' );
		        }
	        }

	        public function al_ri_verify_csv() {
		        if ( false != get_option( 'al_ri_data_verified' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        al_log_user_action( 'upload_rankings_csv', 'Rankings Importer', sprintf( esc_html( __( '%s successfully verified %s.', 'action-logger' ) ), $user, $_POST[ 'file_name' ][0] ) );
		        }
	        }

	        public function al_ri_rankings_imported( $line_number = false ) {
		        if ( false != get_option( 'al_ri_rankings_imported' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        al_log_user_action( 'rankings_imported', 'Rankings Importer', sprintf( esc_html( __( '%s successfully imported %d lines from file.', 'action-logger' ) ), $user, $line_number ) );
		        }
	        }

	        public function al_ri_csv_uploaded() {
		        if ( false != get_option( 'al_ri_file_uploaded' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        al_log_user_action( 'upload_rankings_csv', 'Rankings Importer', sprintf( esc_html( __( '%s successfully uploaded the file: "%s".', 'action-logger' ) ), $user, $_FILES[ 'csv_upload' ][ 'name' ] ) );
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
