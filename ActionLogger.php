<?php
    /*
    Plugin Name: Action Logger
    Version: 1.0.0
    Tags: log
    Plugin URI: https://github.com/Beee4life/action-logger
    Description: This plugin logs several actions which are interesting to log, to know who did what, such as creating/deleting/promoting users.
    Author: Beee
    Author URI: https://berryplasman.com
    Text-domain: action-logger
    Domain Path: /languages
    License: GPL v2

    https://www.berryplasman.com
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

                add_action( 'admin_init',                   array( $this, 'al_delete_items' ) );
                add_action( 'admin_init',                   array( $this, 'al_reset_all' ) );
                add_action( 'admin_init',                   array( $this, 'al_log_actions_functions' ) );
                add_action( 'admin_init',                   array( $this, 'al_store_post_types' ) );
                add_action( 'admin_init',                   array( $this, 'al_settings_page_functions' ) );

                add_action( 'admin_init',                   array( $this, 'al_load_includes' ), 1 );
                add_action( 'admin_init',                   array( $this, 'al_log_user_action' ) );
                add_action( 'admin_init',                   array( $this, 'al_check_log_table' ) );

                // Shortcode
                add_shortcode( 'actionlogger',         array( $this, 'al_register_shortcode_logger' ) );

                // WP Core actions
                add_action( 'user_register',                array( $this, 'al_log_user_create' ), 10, 1 );
                add_action( 'profile_update',               array( $this, 'al_log_user_change' ), 10, 2 );
                add_action( 'delete_user',                  array( $this, 'al_log_user_delete' ), 10, 1 );
                add_action( 'transition_post_status',       array( $this, 'al_post_status_transitions'), 10, 3 );
                add_action( 'activated_plugin',             array( $this, 'al_log_plugin_activation' ), 10, 2 );
                add_action( 'deactivated_plugin',           array( $this, 'al_log_plugin_deactivation' ), 10, 2 );
                
                // @TODO: load plugin at end
                
                // EM actions (test)
                // add_action( 'em_bookings_deleted',          array( $this, 'al_log_registration_delete' ), 10, 2 );
                // add_action( 'em_booking_save',              array( $this, 'al_log_registration_change' ), 10, 2 );
    
                // CSV Importer actions
                add_action( 'csv2wp_successful_csv_upload',   array( $this, 'al_csvi_file_upload' ) );
                add_action( 'csv2wp_successful_csv_validate', array( $this, 'al_csvi_file_validate' ) );
                add_action( 'csv2wp_successful_csv_import',   array( $this, 'al_csvi_file_import' ) );
    
                include( 'includes/al-crons.php' );
                include( 'includes/al-functions.php' );

                // @TODO: look into this
                // $this->al_store_post_type_actions();

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
                    id int(8) unsigned NOT NULL auto_increment,
                    action_time int(14) unsigned NOT NULL,
                    action_user int(6) unsigned NOT NULL,
                    action varchar(50) NULL,
                    action_generator varchar(50) NULL,
                    action_description varchar(100) NOT NULL,
                    post_id int(8) NULL,
                    PRIMARY KEY  (id)
                )
                COLLATE <?php echo $wpdb->collate; ?>;
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
                if ( false == $available_options ) {

                    $all_options = al_get_available_actions();
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

                $post_type_args = array(
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
                        $post_types[$post_type][] = 'pending';
                    }
                }
                update_option( 'al_active_post_types', $post_types );

            }


            /**
             * Check if any post types actions have been added/removed
             */
            public function al_store_post_type_actions() {

                $post_type_args = array(
                    'public'             => true,
                    'publicly_queryable' => true,
                );
                $available_post_types = get_post_types( $post_type_args, 'names', 'OR' );
                $stored_post_types    = get_option( 'al_active_post_types' );
                $array_diffs          = array_diff( $stored_post_types, $available_post_types );
                if ( count( $array_diffs ) > 0 ) {
                    // store new post types if there are differences
                    update_option( 'al_active_post_types', $array_diffs );
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
            }


            /**
             * All form action for the settings page, except the nuke database action
             */
            public function al_log_actions_functions() {

                if ( isset( $_POST[ 'al_active_logs_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'al_active_logs_nonce' ], 'al-active-logs-nonce' ) ) {
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
             * ??
             */
            public function al_store_post_types() {

                if ( isset( $_POST[ 'al_post_types_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'al_post_types_nonce' ], 'al-post-types-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        $submitted_post_types = $_POST['al_post_types'];

                        if ( $submitted_post_types ) {
                            foreach( $submitted_post_types as $post_type => $actions ) {
                                if ( ! in_array( 'active', $actions ) ) {
                                    unset( $submitted_post_types[$post_type] );
                                }
                            }
                        }

                        if ( empty( $submitted_post_types ) ) {
                            $submitted_post_types = 0;
                        }

                        update_option( 'al_active_post_types', $submitted_post_types );

                        al_errors()->add( 'success_posttypes_saved', esc_html( __( 'Post type options saved.', 'action-logger' ) ) );

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
                if ( isset( $_POST[ 'al_purge_logs_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'al_purge_logs_nonce' ], 'al-purge-logs-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        if ( isset( $_POST[ 'al_purge_logs' ] ) ) {
                            update_option( 'al_purge_logs', $_POST[ 'al_purge_logs' ] );
                        }
                        al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );

                    }
                }

                /**
                 * Update who can manage
                 */
                if ( isset( $_POST[ 'al_settings_page_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'al_settings_page_nonce' ], 'al-    settings-page-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        if ( isset( $_POST[ 'al_select_cap' ] ) ) {
                            update_option( 'al_log_user_role', $_POST[ 'al_select_cap' ] );
                        }
                        al_errors()->add( 'success_settings_saved', esc_html( __( 'Settings saved.', 'action-logger' ) ) );

                    }
                }

                /**
                 * Export data to CSV
                 */
                if ( isset( $_POST[ 'al_export_csv_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'al_export_csv_nonce' ], 'al-export-csv-nonce' ) ) {
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
                                $array_item[ 'action_description' ] = al_replace_log_vars( get_current_user_id(), $array_item[ 'action_description' ], $array_item[ 'post_id' ] );
                                $csv_array[] = $array_item;
                            }

                            $filename  = "export.csv";
                            $delimiter = ",";

                            $csv_header = array(
                                'id'                 => 'ID',
                                'action_time'        => 'Time',
                                'action_user'        => 'User',
                                'action'             => 'Action',
                                'action_generator'   => 'Generator',
                                'action_description' => 'Description',
                                'post_id'            => 'Post_id',
                            );

                            header( 'Content-Type: application/csv' );
                            header( 'Content-Disposition: attachment; filename="' . $filename . '";' );

                            // open the "output" stream
                            // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
                            $file_contents = fopen( 'php://output', 'w' );

                            // write header row
                            fputcsv( $file_contents, $csv_header, $delimiter );
    
                            // write rows
                            foreach ( $csv_array as $line ) {
                                fputcsv( $file_contents, $line, $delimiter );
                            }
                            exit;
                        }
                    }
                }

                /**
                 * Preserve settings
                 */
                if ( isset( $_POST[ 'al_preserve_settings_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'al_preserve_settings_nonce' ], 'al-preserve-settings-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        $preserve_settings = isset( $_POST[ 'al_preserve_settings' ] ) ? $_POST[ 'al_preserve_settings' ] : false;
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
            public function al_delete_items() {

                if ( isset( $_POST[ 'al_delete_action_items_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'al_delete_action_items_nonce' ], 'al-delete-actions-items-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        // delete all
                        if ( isset( $_POST[ 'al_delete_all' ] ) ) {
                            $this->al_truncate_log_table( true );
                            al_errors()->add( 'success_logs_deleted', esc_html( __( 'All logs deleted.', 'action-logger' ) ) );

                            return;
                        } elseif ( isset( $_POST[ 'rows' ] ) ) {

                            // delete rows
                            if ( $_POST[ 'rows' ] ) {
                                $where = array();
                                global $wpdb;
                                foreach ( $_POST[ 'rows' ] as $row_id ) {
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
             * Reset to factory settings
             */
            public function al_reset_all() {

                if ( isset( $_POST[ 'al_reset_all_nonce' ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ 'al_reset_all_nonce' ], 'al-reset-all-nonce' ) ) {
                        al_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'action-logger' ) ) );

                        return;
                    } else {

                        if ( isset( $_POST[ 'al_reset_all' ] ) ) {

                            global $wpdb;
                            $wpdb->query( "DROP TABLE {$wpdb->prefix}action_logs" );

                            $options   = get_option( 'al_available_log_actions' );
                            $actions   = array();
                            foreach ( $options as $key => $value ) {
                                $actions[] = $value['action_name'];
                            }
                            $actions[] = 'active_post_types';
                            $actions[] = 'available_log_actions';
                            $actions[] = 'log_user_role';
                            $actions[] = 'posts_per_page';
                            $actions[] = 'purge_logs';
                            foreach ( $actions as $action ) {
                                delete_option( 'al_' . $action );
                            }

                            al_errors()->add( 'success_all_reset', esc_html( __( 'All settings are reset to default settings.', 'action-logger' ) ) );

                            return;
                        }
                    }
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
             * @return bool|void
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
                
                return;

            }
    
            /**
             * Admin menu
             *
             * @return bool|string
             */
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
                    $filtered_sql_data = apply_filters( 'al_override_log_data', $sql_data );
                    $wpdb->insert( $wpdb->prefix . 'action_logs', $filtered_sql_data );
                }

            }

            /**
             * @param $attributes
             *
             * @return void
             */
            public function al_register_shortcode_logger( $attributes ) {

                $log_loggedin = get_option( 'al_user_visit_registered' );
                $log_visitor  = get_option( 'al_user_visit_visitor' );
                $who          = 'A visitor';
                $post_id      = get_the_ID();
                $message      = 'visited <a href="#permalink#">' . get_the_title( $post_id ) . '</a>';

                if ( is_user_logged_in() ) {
                    $who = get_userdata( get_current_user_id() )->display_name;
                    if ( false != $log_loggedin ) {
                        $log_it = true;
                    }
                } else {
                    if ( false != $log_visitor ) {
                        $log_it = true;
                    }
                }

                $attributes = shortcode_atts( array(
                    'message' => $message,
                ), $attributes, 'actionlogger' );

                if ( ! is_admin() && true == $log_it ) {
                    $this->al_log_user_action( get_post_type() . '_visit', 'Shortcode', $who . ' ' . $attributes['message'], $post_id );
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

                $post_type         = $post->post_type;
                $post_link         = '<a href="#permalink#">' . $post->post_title . '</a>';
                $user_data         = get_userdata( get_current_user_id() );
                $user_name         = $user_data->display_name;
                $active_post_types = get_option( 'al_active_post_types' );

                // if post type is active in active post types
                if ( array_key_exists( $post_type, $active_post_types ) ) {

                    // X > trash
                    if ( $new_status == 'trash' && in_array( 'delete', $active_post_types[ $post_type ] ) ) {
                        $this->al_log_user_action( $post_type . '_deleted', 'Action Logger', sprintf( esc_html( __( '%s deleted %s %s', 'action-logger' ) ), $user_name, $post_type, $post->post_title ), $post->ID );
                    } elseif ( $old_status == 'publish' ) {
                        if ( $new_status == 'publish' && in_array( 'edit', $active_post_types[ $post_type ] ) ) {
                            $this->al_log_user_action( $post_type . '_changed', 'Action Logger', sprintf( esc_html( __( '%s changed %s %s', 'action-logger' ) ), $user_name, $post_type, $post_link ), $post->ID );
                        } elseif ( $new_status == 'pending' && in_array( 'pending', $active_post_types[ $post_type ] ) ) {
                            $this->al_log_user_action( $post_type . '_pending', 'Action Logger', sprintf( esc_html( __( '%s marked %s %s as pending review', 'action-logger' ) ), $user_name, $post_type, $post_link ), $post->ID );
                        }
                    } elseif ( $old_status != 'publish' ) {
                        if ( $old_status == 'trash' && $new_status == 'publish' && in_array( 'publish', $active_post_types[ $post_type ] ) ) {
                            $this->al_log_user_action( $post_type . '_republished', 'Action Logger', sprintf( esc_html( __( '%s republished %s %s', 'action-logger' ) ), $user_name, $post_type, $post_link ), $post->ID );
                        } elseif ( $new_status == 'publish' && in_array( 'publish', $active_post_types[ $post_type ] ) ) {
                            $this->al_log_user_action( $post_type . '_published', 'Action Logger', sprintf( esc_html( __( '%s published %s %s', 'action-logger' ) ), $user_name, $post_type, $post_link ), $post->ID );
                        }
                    }
                }
            }
    
            public function al_log_plugin_activation( $plugin, $network_activation ) {
                $path          = explode( '/', $plugin );
                $plugin_folder = $path[ 0 ];
                $user_data     = get_userdata( get_current_user_id() );
                $user_name     = $user_data->display_name;
    
                $this->al_log_user_action( 'plugin_activated', 'Action Logger', sprintf( esc_html( __( '%s activated the plugin %s', 'action-logger' ) ), $user_name, $plugin_folder ) );
                
            }
            
            public function al_log_plugin_deactivation( $plugin, $network_activation ) {
                $path          = explode( '/', $plugin );
                $plugin_folder = $path[ 0 ];
                $user_data     = get_userdata( get_current_user_id() );
                $user_name     = $user_data->display_name;
    
                $this->al_log_user_action( 'plugin_deactivated', 'Action Logger', sprintf( esc_html( __( '%s deactivated the plugin %s', 'action-logger' ) ), $user_name, $plugin_folder ) );
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
                    if ( false != get_option( 'al_wp_user_change' ) && get_current_user_id() > 0 ) {
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
             * Log successful file upload from CSV to WP
             * Log file upload from CSV to WP
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
             * Log successful csv validate from CSV to WP
             * @param $user_id
             */
            public function al_csvi_file_validate( $file_name ) {
                $user_name = get_userdata( get_current_user_id() )->display_name;
                if ( class_exists( 'CSV_WP' ) && false != get_option( 'al_csvi_file_validated' ) ) {
                    $this->al_log_user_action( 'csv_validate', 'Action Logger', sprintf( esc_html( __( '%s successfully validated the file: "%s".', 'action-logger' ) ), $user_name, $file_name ) );
                }
            }

            /**
             * Log successful csv import from CSV to WP
             * @param $user_id
             */
            public function al_csvi_file_import( $line_number ) {
                $user_name = get_userdata( get_current_user_id() )->display_name;
                if ( class_exists( 'CSV_WP' ) && false != get_option( 'al_csvi_file_imported' ) ) {
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
