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
    Domain Path: /languages
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

	            // filters
	            add_filter( 'set-screen-option',            array( $this, 'al_set_screen_option' ), 10, 3 );
	            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'al_plugin_link' ) );

                // actions
	            add_action( 'admin_menu',                   array( $this, 'al_add_admin_pages' ) );
                add_action( 'admin_init',                   array( $this, 'al_delete_selected_items' ) );
                add_action( 'admin_init',                   array( $this, 'al_delete_all_logs' ) );
                add_action( 'admin_init',                   array( $this, 'al_log_actions_functions' ) );
                add_action( 'admin_init',                   array( $this, 'al_settings_page_functions' ) );
                add_action( 'plugins_loaded',               array( $this, 'al_load_plugin_textdomain' ) );
                add_action( 'admin_enqueue_scripts',        array( $this, 'al_enqueue_action_logger_css' ) );

	            // Shortcode
	            add_shortcode( 'actionlogger',         array( $this, 'al_register_shortcode_logger' ) );

	            // WP Core actions
                add_action( 'user_register',                array( $this, 'al_log_user_create' ), 10, 1 );
                add_action( 'profile_update',               array( $this, 'al_log_user_change' ), 10, 2 );
	            add_action( 'delete_user',                  array( $this, 'al_log_user_delete' ), 10, 1 );
                add_action( 'transition_post_status',       array( $this, 'al_post_status_transitions'), 10, 3 );

	            // EM actions
	            add_action( 'em_bookings_deleted',          array( $this, 'al_log_registration_delete' ), 10, 2 );
	            add_action( 'em_booking_save',              array( $this, 'al_log_registration_change' ), 10, 2 );

                // CSV Importer actions
	            add_action( 'csv2wp_successful_csv_upload',   array( $this, 'al_csvi_file_upload' ) );
	            add_action( 'csv2wp_successful_csv_validate', array( $this, 'al_csvi_file_validate' ) );
	            add_action( 'csv2wp_successful_csv_import',   array( $this, 'al_csvi_file_import' ) );

                // Rankings Importer actions
                add_action( 'ri_all_data_nuked',            array( $this, 'al_ri_all_nuked' ) );
                add_action( 'ri_delete_user_rankings',      array( $this, 'al_ri_user_rankings_delete' ) );
                add_action( 'ri_import_raw',                array( $this, 'al_ri_import_raw_data' ) );
                add_action( 'ri_verify_csv',                array( $this, 'al_ri_verify_csv' ) );
                add_action( 'ri_rankings_imported',         array( $this, 'al_ri_rankings_imported' ) );
                add_action( 'ri_csv_file_upload',           array( $this, 'al_ri_csv_uploaded' ) );

	            // load on each page load
	            $this->al_load_includes();
	            $this->al_log_user_action();
	            $this->al_check_log_table();

	            $this->al_set_default_values(); // check if there are default settings
	            $this->check_this();            // for testing stuff

            }

	        public function check_this() {
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

	            if ( false == get_option( 'al_preserve_settings' ) ) {
		            $this->al_check_log_table();
	            }

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
                CREATE TABLE <?php echo $wpdb->prefix; ?>action_logs (
                    id int(6) unsigned NOT NULL auto_increment,
                    action_time int(14) unsigned NOT NULL,
                    action_user int(6) unsigned NOT NULL,
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
	                update_option( 'al_posts_per_page', 100 );
	                update_option( 'al_purge_logs', 30 );
                }
            }

	        /**
	         * Adds a page to admin sidebar menu
	         */
	        public function al_add_admin_pages() {
		        global $my_plugin_hook;
		        $capability = get_option( 'al_log_user_role' );
		        $my_plugin_hook = add_menu_page( 'Action Logger', 'Action Logger', $capability, 'action-logger', 'action_logger_dashboard', 'dashicons-editor-alignleft' );
		        add_action( "load-$my_plugin_hook", array( $this, 'al_add_screen_options' ) );
		        include( 'al-dashboard.php' ); // content for the settings page

		        add_submenu_page( NULL, 'Log actions', 'Log actions', 'manage_options', 'al-log-actions', 'action_logger_actions_page' );
		        include( 'al-log-actions.php' ); // content for the settings page

		        add_submenu_page( NULL, 'Log actions', 'Log actions', 'manage_options', 'al-settings', 'action_logger_settings_page' );
		        include( 'al-settings.php' ); // content for the settings page

		        add_submenu_page( NULL, 'Misc', 'Misc', 'manage_options', 'al-misc', 'action_logger_misc_page' );
		        include( 'al-misc.php' ); // content for the settings page
	        }

	        /**
	         * Load included files
	         */
	        public function al_load_includes() {
		        include( 'al-errors.php' );
		        include( 'al-help-tabs.php' );
		        include( 'al-functions.php' );
		        include( 'al-admin-menu.php' );
	        }

	        /**
             * All form action for the settings page, except the nuke database action
             */
            public function al_log_actions_functions() {

                if ( isset( $_POST[ 'active_logs_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'active_logs_nonce' ], 'active-logs-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

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
                        al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );

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
	            if ( isset( $_POST[ 'purge_logs_nonce' ] ) ) {
		            if ( ! wp_verify_nonce( $_POST[ 'purge_logs_nonce' ], 'purge-logs-nonce' ) ) {
			            al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

			            return;
		            } else {

			            if ( isset( $_POST[ 'purge_logs' ] ) ) {
				            update_option( 'al_purge_logs', $_POST[ 'purge_logs' ] );
			            }
			            al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );

		            }
	            }

	            /**
	             * Update who can manage
	             */
	            if ( isset( $_POST[ 'settings_page_nonce' ] ) ) {
		            if ( ! wp_verify_nonce( $_POST[ 'settings_page_nonce' ], 'settings-page-nonce' ) ) {
			            al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

			            return;
		            } else {

			            if ( isset( $_POST[ 'select_cap' ] ) ) {
				            update_option( 'al_log_user_role', $_POST[ 'select_cap' ] );
			            }
			            al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );

		            }
	            }

	            /**
                 * Export data to CSV
                 */
                if ( isset( $_POST[ 'export_csv_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'export_csv_nonce' ], 'export-csv-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

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
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        $preserve_settings = isset( $_POST[ 'preserve_settings' ] ) ? $_POST[ 'preserve_settings' ] : false;
                        if ( true == $preserve_settings ) {
                            update_option( 'al_preserve_settings', 1 );
                        } elseif ( false == $preserve_settings ) {
                            delete_option( 'al_preserve_settings' );
                        }
	                    al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );
                    }
                }

            }

            /**
             * Function for the overview page.
             */
            public function al_delete_selected_items() {

                if ( isset( $_POST[ 'delete_action_items_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'delete_action_items_nonce' ], 'delete-actions-items-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

	                    // delete all
                        if ( isset( $_POST['delete_all'] ) ) {
		                    $this->al_truncate_log_table( true );
		                    al_errors()->add( 'success_logs_deleted', esc_html( __( 'All logs deleted.', 'action-logger' ) ) );

		                    return;
	                    } elseif ( isset( $_POST['rows'] ) ) {

		                    // delete rows
                            if ( $_POST['rows'] ) {
			                    $where = array();
			                    global $wpdb;
			                    foreach ( $_POST['rows'] as $row_id ) {
				                    $wpdb->delete( $wpdb->prefix . 'action_logs', array( 'ID' => $row_id ) );
			                    }

			                    al_errors()->add( 'success_items_deleted', esc_html( __( 'All selected items are successfully deleted from the database.', 'action-logger' ) ) );

			                    return;
		                    }
	                    } else {
		                    al_errors()->add( 'error_no_selection', esc_html( __( 'You didn\'t select any lines. If you did, then something went wrong.', 'action-logger' ) ) );

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
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        if ( isset( $_POST[ 'delete_all' ] ) ) {
                            // truncate table
                            $this->al_truncate_log_table( true );
                            al_errors()->add( 'success_logs_deleted', esc_html( __( 'All logs deleted.', 'action-logger' ) ) );

                            return;
                        }
                    }
                }
            }

	        /**
	         * Empty log table
             *
             * @param bool $truncate
	         */
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
                wp_register_style( 'action-logger', plugins_url( 'assets/css/style.css', __FILE__ ), false, '1.0' );
                wp_enqueue_style( 'action-logger' );
            }


	        /**
             * Create admin menu
             *
	         * @return string
	         */
            public function al_admin_menu() {
                if ( current_user_can( 'manage_options' ) ) {
                    return '<p><a href="' . site_url() . '/wp-admin/admin.php?page=action-logger">' . esc_html( __( 'Logs', 'action-logger' ) ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-settings">' . esc_html( __( 'Settings', 'action-logger' ) ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-misc">' . esc_html( __( 'Misc', 'action-logger' ) ) . '</a></p>';
                }
            }


	        /**
	         * Add screen options
             *
             * @return bool
	         */
	        public function al_add_screen_options() {

		        $screen = get_current_screen();
		        // echo '<pre>'; var_dump($screen); echo '</pre>'; exit;

		        if ( 'toplevel_page_action-logger' != $screen->id ) {
			        return false;
		        }

		        $option   = $screen->get_option( 'al_ppp', 'option' );
		        $per_page = get_user_meta( get_current_user_id(), $option, true );
		        if ( empty ( $per_page ) || $per_page < 1 ) {
			        $per_page = $screen->get_option( 'per_page', 'default' );
		        }

		        add_screen_option(
			        'per_page',
			        array(
				        'label'   => sprintf( __( 'Log entries (%d max)', 'action-logger' ), 200 ),
				        'default' => get_option( 'al_posts_per_page' ),
				        'option'  => 'al_ppp'
			        )
		        );

	        }


	        /**
             * Set sceen options
             *
	         * @param $status
	         * @param $option
	         * @param $value
	         *
	         * @return mixed
	         */
	        public function al_set_screen_option( $status, $option, $value ) {

		        if ( 'al_ppp' == $option ) {
			        update_user_meta( get_current_user_id(), $option, $value );
			        return $value;
		        }

		        return $status;

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

	        /**
	         * @param $attributes
             * return void
	         */
            public function al_register_shortcode_logger( $attributes ) {

                $post_title   = get_the_title();
                $post_link    = get_permalink();
                // $post_link    = '#';
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
	                $this->al_log_user_action( $post_type . '_visit', 'Shortcode', $user . ' ' . $attributes[ 'message' ] );
	                // $this->al_log_user_action( $post_type . '_visit', 'Shortcode', $user . ' did something on' );
	                return;
                }

                return;
            }

            /**
             * Default Wordpress actions
             * These functions hooks into default WP actions like user register, change and delete
             */

	        /**
	         * Function to check post transitions and log those
	         *
	         * @param $new_status string
	         * @param $old_status string
	         * @param $post       object
	         */
	        public function al_post_status_transitions( $new_status, $old_status, $post ) {
		        $post_type = $post->post_type;
		        $post_link = '<a href="' . esc_url( get_permalink( $post->ID ) ) . '">' . $post->post_title . '</a>';
		        $user_name = get_userdata( get_current_user_id() )->display_name;
		        if ( $old_status == 'draft' && $new_status == 'publish' ) {

                    // draft > publish
			        $this->al_log_user_action( $post_type . '_published', 'Action Logger', sprintf( esc_html( __( '%s published %s.', 'action-logger' ) ), $user_name, $post_link ) );

		        } elseif ( $old_status == 'pending' && $new_status == 'publish' ) {

		            // pending > publish
			        $this->al_log_user_action( $post_type . '_republished', 'Action Logger', sprintf( esc_html( __( '%s re-published %s.', 'action-logger' ) ), $user_name, $post_link ) );

		        } elseif ( $old_status == 'publish' && $new_status == 'publish' ) {

		            // publish > publish
			        $this->al_log_user_action( $post_type . '_changed', 'Action Logger', sprintf( esc_html__( '%s edited published post %s.', 'action-logger' ), $user_name, $post_link ) );

		        } elseif ( $old_status == 'publish' && $new_status != 'publish' ) {

		            // publish > !publish
			        if ( $old_status == 'publish' && $new_status == 'trash' ) {

                        // publish > trash
				        $this->al_log_user_action( $post_type . '_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted %s.', 'action-logger' ) ), $user_name, get_the_title() ) );

			        } elseif ( $old_status == 'publish' && $new_status == 'pending' ) {

                        // publish > pending
				        $this->al_log_user_action( $post_type . '_pending', 'Action Logger', sprintf( esc_html( __( '%s marked %s as pending review.', 'action-logger' ) ), $user_name, get_the_title() ) );
			        }
		        }
	        }


            /**
             * Log user creation
             *
             * @param $user_id
             */
            public function al_log_user_create( $user_id ) {
                // echo '<pre>'; var_dump( $user_id ); echo '</pre>'; exit;
                if ( false != get_option( 'al_wp_user_create' ) ) {
	                $this->al_log_user_action( 'user_registered', 'Action Logger', sprintf( esc_html( __( 'New user registered: %s.', 'action-logger' ) ), get_userdata( $user_id )->display_name ) );
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
                    if ( false != get_option( 'al_wp_user_change' ) ) {
                        $this->al_log_user_action( 'user_changed', 'Action Logger', sprintf( esc_html( __( '%s changed the user of %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, get_userdata( $user_id )->display_name ) );
                    }
                }
            }


	        /**
	         * Log user delete
	         *
	         * @param $user_id int
	         */
	        public function al_log_user_delete( $user_id ) {
		        if ( false != get_option( 'al_wp_user_delete' ) ) {
			        $this->al_log_user_action( 'user_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted the user of %s.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name, get_userdata( $user_id )->display_name ) );
		        }
	        }


	        /**
             * Events manager actions
             */

            /**
             * Should log an action when an booking is changed (doesn't do jack right now)
             *
             * @param $EM_Event
             * @param $EM_Booking
             */
            public function al_log_registration_change( $EM_Event, $EM_Booking ) {


	            $log        = false;
	            $booking_id = $EM_Booking->booking_id;
	            $user       = is_user_logged_in() ? get_userdata( get_current_user_id() )->display_name : 'A visitor';

                if ( 0 == $EM_Booking->booking_status ) {
	                $action = 'registration_pending';
	                $status = 'pending';
	                if ( 1 == get_option( 'al_em_booking_pending' ) ) {
		                $log = true;
	                }
                } elseif ( 1 == $EM_Booking->booking_status ) {
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
                } elseif ( 4 == $EM_Booking->booking_status ) {
	                $action = 'registration_aw_onl_payment';
	                $status = 'registered';
	                if ( 1 == get_option( 'al_em_booking_aw_onl_payment' ) ) {
		                $log = true;
	                }
                } elseif ( 5 == $EM_Booking->booking_status ) {
	                $action = 'registration_aw_ofl_payment';
	                $status = 'registered';
	                if ( 1 == get_option( 'al_em_booking_aw_ofl_payment' ) ) {
		                $log = true;
	                }
                } else {
                    $action = 'registration_unknown';
                    $status = 'unknown';
                    $log   = true;
                }

                if ( $log == true ) {
                    $this->al_log_user_action( $action, 'Action Logger', $user . ' ' . $status . ' ' . esc_html( __( 'the booking with booking ID:', 'action-logger' ) )  . ' ' . $booking_id . '.' );
                }

            }

            /**
             * Log an action when a registration is deleted
             *
             * @param $result
             * @param $booking_ids
             */
            public function al_log_registration_delete( $result, $booking_ids ) {
                if ( false != get_option( 'al_em_booking_deleted' ) ) {
                    $this->al_log_user_action( 'registration_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted bookings for an event.', 'action-logger' ) ), get_userdata( get_current_user_id() )->display_name ) );
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
		        if ( class_exists( 'CSV_WP' ) && false != get_option( 'al_csvi_file_uploaded' ) ) {
			        $this->al_log_user_action( 'csv_upload', 'Action Logger', sprintf( esc_html( __( '%s successfully uploaded the file: "%s".', 'action-logger' ) ), $user_name, $_FILES[ 'csv_upload' ][ 'name' ] ) );
		        }
	        }

	        /**
	         * Log successful csv validate from CSV Importer
	         * @param $user_id
	         */
	        public function al_csvi_file_validate( $file_name ) {
		        $user_name = get_userdata( get_current_user_id() )->display_name;
		        if ( class_exists( 'CSV_WP' ) && false != get_option( 'al_csvi_file_validated' ) ) {
		            $this->al_log_user_action( 'csv_validate', 'Action Logger', sprintf( esc_html( __( '%s successfully validated the file: "%s".', 'action-logger' ) ), $user_name, $file_name ) );
		        }
	        }

	        /**
	         * Log successful csv import from CSV Importer
	         * @param $user_id
	         */
	        public function al_csvi_file_import( $line_number ) {
		        $user_name = get_userdata( get_current_user_id() )->display_name;
		        if ( class_exists( 'CSV_WP' ) && false != get_option( 'al_csvi_file_imported' ) ) {
			        $this->al_log_user_action( 'csv_imported', 'Action Logger', sprintf( esc_html( __( '%s successfully imported %d lines from file.', 'action-logger' ) ), $user_name, $line_number ) );
		        }
	        }

	        public function al_ri_all_nuked() {
		        if ( false != get_option( 'al_ri_data_nuked' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        $this->al_log_user_action( 'nuke_all', 'Rankings Importer', sprintf( esc_html__( '%s nuked all rankings.', 'action-logger' ), $user ) );
		        }
	        }

	        public function al_ri_user_rankings_delete( $user_id, $count ) {
		        if ( false != get_option( 'al_ri_rankings_deleted' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        $this->al_log_user_action( 'individual_ranking_deleted', 'Rankings Importer', sprintf( esc_html__( '%s deleted %d individual rankings for %s.', 'action-logger' ), $user, $count, $user_id ) );
		        }
	        }

	        public function al_ri_import_raw_data( $count ) {
		        if ( false != get_option( 'al_ri_import_raw' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        $this->al_log_user_action( 'import_raw', 'Rankings Importer', $user . ' uploaded ' . $count . ' lines through raw import' );
		        }
	        }

	        public function al_ri_verify_csv() {
		        if ( false != get_option( 'al_ri_data_verified' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        $this->al_log_user_action( 'upload_rankings_csv', 'Rankings Importer', sprintf( esc_html( __( '%s successfully verified %s.', 'action-logger' ) ), $user, $_POST[ 'file_name' ][0] ) );
		        }
	        }

	        public function al_ri_rankings_imported( $line_number = false ) {
		        if ( false != get_option( 'al_ri_rankings_imported' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        $this->al_log_user_action( 'rankings_imported', 'Rankings Importer', sprintf( esc_html( __( '%s successfully imported %d lines from file.', 'action-logger' ) ), $user, $line_number ) );
		        }
	        }

	        public function al_ri_csv_uploaded() {
		        if ( false != get_option( 'al_ri_file_uploaded' ) ) {
			        $user = get_userdata( get_current_user_id() )->display_name;
			        $this->al_log_user_action( 'upload_rankings_csv', 'Rankings Importer', sprintf( esc_html( __( '%s successfully uploaded the file: "%s".', 'action-logger' ) ), $user, $_FILES[ 'csv_upload' ][ 'name' ] ) );
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
