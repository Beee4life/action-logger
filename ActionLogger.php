<?php
    /*
    Plugin Name: Action Logger
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
                add_action( 'plugins_loaded',               array( $this, 'al_load_plugin_textdomain' ) );
	            add_action( 'admin_enqueue_scripts',        array( $this, 'al_enqueue_action_logger_css' ) );
	            add_action( 'admin_menu',                   array( $this, 'al_add_admin_pages' ) );
                add_action( 'admin_init',                   array( $this, 'al_admin_menu' ) );

                add_action( 'admin_init',                   array( $this, 'al_delete_selected_items' ) );
                add_action( 'admin_init',                   array( $this, 'al_delete_all_logs' ) );
                add_action( 'admin_init',                   array( $this, 'al_log_actions_functions' ) );
                add_action( 'admin_init',                   array( $this, 'al_store_post_types' ) );
                add_action( 'admin_init',                   array( $this, 'al_settings_page_functions' ) );

                add_action( 'admin_init',                   array( $this, 'al_load_includes' ), 1 );
                add_action( 'admin_init',                   array( $this, 'al_set_default_values' ) );
                add_action( 'admin_init',                   array( $this, 'al_log_user_action' ) );
                add_action( 'admin_init',                   array( $this, 'al_check_log_table' ) );
	            add_action( 'al_cron_purge_logs',           array( $this, 'al_cron_jobs' ) );

                // Shortcode
	            add_shortcode( 'actionlogger',         array( $this, 'al_register_shortcode_logger' ) );

	            // WP Core actions
                add_action( 'user_register',                array( $this, 'al_log_user_create' ), 10, 1 );
                add_action( 'profile_update',               array( $this, 'al_log_user_change' ), 10, 2 );
	            add_action( 'delete_user',                  array( $this, 'al_log_user_delete' ), 10, 1 );
                add_action( 'transition_post_status',       array( $this, 'al_post_status_transitions'), 10, 3 );

	            // EM actions
	            // add_action( 'em_bookings_deleted',          array( $this, 'al_log_registration_delete' ), 10, 2 );
	            // add_action( 'em_booking_save',              array( $this, 'al_log_registration_change' ), 10, 2 );

                // CSV Importer actions
                // add_action( 'csv2wp_successful_csv_upload',   array( $this, 'al_csvi_file_upload' ) );
                // add_action( 'csv2wp_successful_csv_validate', array( $this, 'al_csvi_file_validate' ) );
                // add_action( 'csv2wp_successful_csv_import',   array( $this, 'al_csvi_file_import' ) );

                // Rankings Importer actions
                // add_action( 'ri_all_data_nuked',            array( $this, 'al_ri_all_nuked' ) );
                // add_action( 'ri_delete_user_rankings',      array( $this, 'al_ri_user_rankings_delete' ) );
                // add_action( 'ri_import_raw',                array( $this, 'al_ri_import_raw_data' ) );
                // add_action( 'ri_verify_csv',                array( $this, 'al_ri_verify_csv' ) );
                // add_action( 'ri_rankings_imported',         array( $this, 'al_ri_rankings_imported' ) );
                // add_action( 'ri_csv_file_upload',           array( $this, 'al_ri_csv_uploaded' ) );


            }

	        /**
	         * Function which runs upon plugin activation
	         */
	        public function al_plugin_activation() {
		        $this->al_prepare_log_table();
		        $this->al_set_default_values();
		        $this->al_set_post_types();

		        if ( ! wp_next_scheduled( 'al_cron_purge_logs' ) ) {
			        wp_schedule_event( time(), 'daily', 'al_cron_purge_logs' );
		        }
	        }

	        /**
	         * Function which runs upon plugin deactivation
	         */
	        public function al_plugin_deactivation() {
	            $timestamp = wp_next_scheduled( 'al_cron_purge_logs' );
	            wp_unschedule_event( $timestamp, 'al_cron_purge_logs' );
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
                return array_merge( $add_this, $links );
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
                    post_id int(8) NULL,
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
	                update_option( 'al_purge_logs', 0 );
                }

            }

            /**
             * Set post types on plugin activate
             */
            public function al_set_post_types() {

                $available_post_types = get_option( 'al_active_post_types' );
                // $available_post_types = false;
                if ( false == $available_post_types ) {

                    $post_type_args       = array(
                        'public'             => true,
                        'publicly_queryable' => true,
                    );
                    $available_post_types = get_post_types( $post_type_args, 'names', 'OR' );
                    $post_types           = array();
                    foreach ( $available_post_types as $post_type ) {
                        $post_type_array = array();
                        if ( $post_type != 'attachment' ) {
                            $post_types[$post_type][] = 'active';
                            $post_types[$post_type][] = 'publish';
                            $post_types[$post_type][] = 'edit';
                            $post_types[$post_type][] = 'delete';
                        }
                    }
                    update_option( 'al_active_post_types', $post_types );
                }

            }


	        /**
	         * Function which runs when cron job is triggered
	         */
	        public function al_cron_jobs() {
		        $purge_logs_after = ( false != get_option( 'al_purge_logs' ) ) ? intval( get_option( 'al_purge_logs' ) ) : 30;
		        // only purge when it's not set to forever/0
		        if ( 0 !== $purge_logs_after ) {
			        $now_ts           = strtotime( date( 'Y-m-d  H:i:s', strtotime( '+' . get_option( 'gmt_offset' ) . ' hours' ) ) );
			        $purge_range      = $purge_logs_after * 24 * 60 * 60;
			        $purge_date       = $now_ts - $purge_range;

			        global $wpdb;
			        $wpdb->query(
				        $wpdb->prepare(
					        "
                        DELETE FROM {$wpdb->prefix}action_logs
                        WHERE action_time < %d
                        ",
					        $now_ts
				        )
			        );
                }
	        }


	        /**
	         * Adds a page to admin sidebar menu
	         */
	        public function al_add_admin_pages() {
		        global $my_plugin_hook;
		        $capability     = get_option( 'al_log_user_role' );
		        $my_plugin_hook = add_menu_page( 'Action Logger', 'Action Logger', $capability, 'action-logger', 'action_logger_dashboard', 'dashicons-editor-alignleft' );
		        add_action( "load-$my_plugin_hook", array( $this, 'al_add_screen_options' ) );
		        include( 'includes/al-dashboard.php' ); // content for the settings page

		        add_submenu_page( NULL, 'Log actions', 'Log actions', 'manage_options', 'al-log-actions', 'action_logger_actions_page' );
		        include( 'includes/al-log-actions.php' ); // content for the settings page

		        add_submenu_page( NULL, 'Log settings', 'Log settings', 'manage_options', 'al-settings', 'action_logger_settings_page' );
		        include( 'includes/al-settings.php' ); // content for the settings page

		        add_submenu_page( NULL, 'Misc', 'Misc', 'manage_options', 'al-misc', 'action_logger_misc_page' );
		        include( 'includes/al-misc.php' ); // content for the settings page
	        }

	        /**
	         * Load included files
	         */
	        public function al_load_includes() {
		        include( 'includes/al-errors.php' );
		        include( 'includes/al-help-tabs.php' );
		        include( 'includes/al-functions.php' );
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

            public function al_store_post_types() {

                if ( isset( $_POST[ 'post_types_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'post_types_nonce' ], 'post-types-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        $submitted_post_types = $_POST['post_types'];

                        // echo '<pre>'; var_dump($submitted_post_types); echo '</pre>'; exit;

                        if ( $submitted_post_types ) {
                            foreach( $submitted_post_types as $post_type => $actions ) {
                                if ( ! in_array( 'active', $actions ) ) {
                                    unset( $submitted_post_types[$post_type] );
                                }
                            }
                        }

                        // echo '<pre>'; var_dump($submitted_post_types); echo '</pre>'; exit;
                        if ( empty( $submitted_post_types ) ) {
                            // die('XYZ');
                            $submitted_post_types = 0;
                        }

                        update_option( 'al_active_post_types', $submitted_post_types );

                        al_errors()->add( 'success_posttypes_saved', esc_html( __( 'Post types saved.', 'action-logger' ) ) );

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

                            $csv_array = [];
                            foreach( $items as $item ) {
                                // make array from object
                                $array_item = (array) $item;
                                $array_item['action_description'] = al_replace_log_vars( $array_item['action_user'], $array_item['action_description'], $array_item['post_id'] );
                                $csv_array[] = $array_item;
                            }

                            $filename  = "export.csv";
                            $delimiter = ",";

                            $csv_header = array(
                                'id'                 => 'ID',
                                'action_time'        => 'Date/Time',
                                'action_user'        => 'User ID',
                                'action'             => 'Action',
                                'action_generator'   => 'Generated by',
                                'action_description' => 'Description',
                                'post_id'            => 'Post_id',
                            );

                            header( 'Content-Type: application/csv' );
                            header( 'Content-Disposition: attachment; filename="' . $filename . '";' );

                            // open the "output" stream
                            // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
                            $f = fopen( 'php://output', 'w' );

                            fputcsv( $f, $csv_header, $delimiter );

                            foreach ( $csv_array as $line ) {
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
	         * Add screen options
             *
             * @return bool
	         */
	        public function al_add_screen_options() {

		        $screen = get_current_screen();

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

	        public static function al_admin_menu() {

		        $string = false;

		        if ( current_user_can( 'manage_options' ) ) {
			        $string = '<p>';
			        $string .= '<a href="' . site_url() . '/wp-admin/admin.php?page=action-logger">' . esc_html__( 'Logs', 'action-logger' ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-log-actions">' . esc_html__( 'Log actions', 'action-logger' ) . '</a> | <a href="' . site_url() . '/wp-admin/admin.php?page=al-settings">' . esc_html__( 'Settings', 'action-logger' ) . '</a>';
			        $string .= ' | <a href="' . site_url() . '/wp-admin/admin.php?page=al-misc">' . esc_html__( 'Misc', 'action-logger' ) . '</a>';
                    $string .= '</p>';
		        }

		        return $string;
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
	        public static function al_log_user_action( $action = false, $action_generator = false, $action_description = false, $post_id = false ) {

		        if ( false != $action_description ) {
			        global $wpdb;
			        $sql_data = array(
				        'action_time'        => current_time( 'timestamp' ),
				        'action_user'        => get_current_user_id(),
				        'action'             => $action,
				        'action_generator'   => $action_generator,
				        'action_description' => $action_description,
                        'post_id'            => $post_id
			        );
			        $wpdb->insert( $wpdb->prefix . 'action_logs', $sql_data );
		        }

	        }

            /**
             * @param $attributes
             *
             * @return void
             */
	        public function al_register_shortcode_logger( $attributes ) {

                // $post_type    = get_post_type();
                $log_loggedin = get_option( 'al_user_visit_registered' );
                $log_visitor  = get_option( 'al_user_visit_visitor' );
                $log_it       = true;
                $post_id      = false;
                if ( is_singular() ) {
                    $post_id = get_the_ID();
                }
                $attributes = shortcode_atts( array(
                    'message' => 'visited <a href="#permalink#">#post_title#</a>',
                ), $attributes, 'actionlogger' );

                if ( is_user_logged_in() ) {
                    if ( false == $log_loggedin ) {
                        $log_it = false;
                    }
                } else {
                    if ( false == $log_visitor ) {
                        $log_it = false;
                    }
                }

                if ( ! is_admin() && true == $log_it ) {
	                $this->al_log_user_action( get_post_type() . '_visit', 'Shortcode', '#user# ' . $attributes[ 'message' ], $post_id );
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

                $post_type  = $post->post_type;
                $post_link  = '<a href="#permalink#">' . $post->post_title. '</a>';
                $user_data  = get_userdata( get_current_user_id() );
                $user_name  = $user_data->display_name;

                $active_post_types     = get_option( 'al_active_post_types' );
                // if post type is active in active post types
                if ( array_key_exists( $post_type, $active_post_types ) ) {
                    // log it (if actions are active)

                    if ( $old_status != 'publish' && $new_status == 'publish' && in_array( 'publish', $active_post_types[$post_type] ) ) {

                        // draft > publish
                        $this->al_log_user_action( $post_type . '_published', 'Action Logger', sprintf( esc_html( __( '#user# published %s %s.', 'action-logger' ) ), $post_type, $post_link ), $post->ID );

                    } elseif ( $old_status == 'pending' && $new_status == 'publish' && in_array( 'republish', $active_post_types[$post_type] ) ) {

                        // pending > publish
                        $this->al_log_user_action( $post_type . '_republished', 'Action Logger', sprintf( esc_html( __( '#user# re-published %s.', 'action-logger' ) ), $post_link ), $post->ID );

                    } elseif ( $old_status == 'publish' && $new_status == 'publish' && in_array( 'edit', $active_post_types[$post_type] ) ) {

                        // publish > publish
                        $this->al_log_user_action( $post_type . '_changed', 'Action Logger', sprintf( esc_html__( '#user# edited published %s %s.', 'action-logger' ), $post_type, $post_link ), $post->ID );

                    } elseif ( $old_status == 'publish' && $new_status == 'trash' && in_array( 'deleted', $active_post_types[$post_type] ) ) {

                        // publish > trash
                        $this->al_log_user_action( $post_type . '_deleted', 'Action Logger', sprintf( esc_html( __( '#user# deleted %s %s.', 'action-logger' ) ), $post_type, $post_link ), $post->ID );

                    } elseif ( $old_status == 'publish' && $new_status == 'pending' && in_array( 'pending', $active_post_types[$post_type] ) ) {

                        // publish > pending
                        $this->al_log_user_action( $post_type . '_pending', 'Action Logger', sprintf( esc_html( __( '#user# marked %s %s as pending review.', 'action-logger' ) ), $post_type, $post_link ), $post->ID );

                    }
                }
            }


            /**
             * Log user creation
             *
             * @param $user_id
             */
            public function al_log_user_create( $user_id ) {
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
