<?php

    /**
     * Add help tabs
     *
     * @param $old_help  string
     * @param $screen_id int
     * @param $screen    object
     */
    function al_help_tabs( $old_help, $screen_id, $screen ) {

        $screen_array = array(
            'toplevel_page_action-logger',
            'admin_page_al-log-actions',
            'admin_page_al-settings',
            'admin_page_al-misc',
        );
        if ( ! in_array( $screen_id, $screen_array ) ) {
            return false;
        }

        if ( 'toplevel_page_action-logger' == $screen_id ) {
            $screen->add_help_tab( array(
                'id'      => 'logs-overview',
                'title'   => esc_html__( 'Dashboard', 'action-logger' ),
                'content' => '<h5>All log entries</h5><p>' . esc_html__( 'This page will show all your logged entries.', 'action-logger' ) . '</p>' .
                    '<p>' . esc_html__( 'You can delete individual logs by checking the checkbox and click "Delete selected items" or delete them all at once.', 'action-logger' ) . '</p>'
            ) );

        }

        if ( 'admin_page_al-log-actions' == $screen_id ) {
            $screen->add_help_tab( array(
                'id'      => 'log-actions',
                'title'   => esc_html__( 'Log actions', 'action-logger' ),
                'content' => '<h4>Log actions</h4><p>' . esc_html__( 'If you activate a plugin for which we have incorporated the actions, then the loggable actions will show on this page, when the plugin is activated.', 'action-logger' ) . '</p>' .
                    '<p>' . esc_html__( 'You can enable/disbale each individual action. Just (de)select it and click "Save settings".', 'action-logger' ) . '</p>',
            ) );
        }

        if ( 'admin_page_al-settings' == $screen_id ) {
            $screen->add_help_tab( array(
                'id'      => 'log-settings',
                'title'   => esc_html__( 'Log settings', 'action-logger' ),
                'content' => '<h4>Log sttings</h4><p>' . esc_html__( 'On this page you can:', 'action-logger' ) . '</p>' .
                '<ul>
                    <li>' . esc_html__( 'select how long to keep the logs', 'action-logger' ) . '</li>
                    <li>' . esc_html__( 'select who can see the logs', 'action-logger' ) . '</li>
                    <li>' . esc_html__( 'select to preserve the data when uninstalling', 'action-logger' ) . '</li>
                </ul>',
            ) );
        }

        get_current_screen()->set_help_sidebar(
            '<p><strong>' . esc_html__( 'Author\'s website', 'action-logger' ) . '</strong></p>' .
            '<p><a href="https://berryplasman.com?utm_source=' . $_SERVER[ 'SERVER_NAME' ] . '&utm_medium=plugin_admin&utm_campaign=free_promo">berryplasman.com</a></p>'
        );

        return $old_help;
    }
    add_filter( 'contextual_help', 'al_help_tabs', 5, 3 );
