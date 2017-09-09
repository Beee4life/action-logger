<?php

    /**
     * Content for the 'dashboard page'
     */
    function action_logger_dashboard() {

        if ( ! current_user_can( 'access_s2member_level4' ) ) {
            wp_die( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) );
        }

        echo '<div class="wrap">';

        echo "<h1>Action Logger overview</h1>";

        idf_show_error_messages();
        do_action('al_before_overview', '' );

        echo '<div id="action-logger" class="">';

        // get results from db
        global $wpdb;
        $items = array();
        $items = $wpdb->get_results( "
                SELECT * FROM " . $wpdb->prefix . "action_logs
                order by id DESC
            ");

        if ( count( $items ) == 0 ) {
            ?>
            <p><?php echo __( 'This page will show a log of all actions done by IDF board members/volunteers, which are "interesting" to log (if there are any).', 'action-logger' ); ?></p>
            <?php
        } elseif ( count( $items ) > 0 ) {
            rsort( $items );
            ?>
            <p><?php echo __( 'This page shows a log of all actions done by IDF board members/volunteers, which are "interesting" to log.', 'action-logger' ); ?></p>
            <?php if ( current_user_can( 'manage_options' ) ) { ?>
                <p>Overview | <a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=al-settings">Settings</a></p>
            <?php } ?>
            <h2><?php echo __( 'Logs', 'action-logger' ); ?></h2>
            <p><small><?php echo __( 'Log items are sorted, newest to oldest.', 'action-logger' ); ?></small></p>
            <form name="logs-form" action="" method="post">
                <input name="delete_action_items_nonce" type="hidden" value="<?php echo wp_create_nonce( 'delete-actions-items-nonce' ); ?>" />
                <table>
                    <thead>
                    <tr class="row">
                        <th class=""><?php echo __( 'Date/time', 'action-logger' ); ?></th>
                        <?php if ( current_user_can( 'manage_options' ) ) { ?>
                            <th class=""><?php echo __( 'Action', 'action-logger' ); ?></th>
                        <?php } ?>
                        <th class="hidden"><?php echo __( 'By', 'action-logger' ); ?></th>
                        <?php if ( 27 == get_current_user_id() ) { ?>
                            <th class=""><?php echo __( 'Generated by', 'action-logger' ); ?></th>
                        <?php } ?>
                        <th class=""><?php echo __( 'Description', 'action-logger' ); ?></th>
                        <?php if ( current_user_can( 'manage_options' ) ) { ?>
                            <th class=""><?php echo __( 'Delete', 'action-logger' ); ?></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        foreach( $items as $item ) {
                            ?>
                            <tr class="row">
                                <td class=""><?php echo date( 'M j @ H:i:s', $item->action_time ); ?> (+<?php echo get_option( 'gmt_offset' ); ?>)</td>
                                <?php if ( current_user_can( 'manage_options' ) ) { ?>
                                    <td class=""><?php echo $item->action; ?></td>
                                <?php } ?>
                                <?php if ( 27 == get_current_user_id() ) { ?>
                                    <td class=""><?php echo $item->action_generator; ?></td>
                                <?php } ?>
                                <td class=""><?php echo $item->action_description; ?></td>
                                <?php if ( current_user_can( 'manage_options' ) ) { ?>
                                    <td class="checkbox">
                                        <label for="rows" class="screen-reader-text">
                                            <?php echo __( 'Delete', 'action-logger' ); ?>
                                        </label>
                                        <input name="rows[]" id="rows" type="checkbox" value="<?php echo $item->id; ?>" />
                                    </td>
                                <?php } ?>
                            </tr>
                            <?php
                        }
                    ?>
                    </tbody>
                </table>
                <?php if ( current_user_can( 'manage_options' ) ) { ?>
                    <br />
                    <input name="delete" type="submit" class="admin-button admin-button-small" value="<?php echo __( 'Delete selected items', 'action-logger' ); ?>" />
                <?php } ?>
            </form>
            <?php
        }

        echo '</div>'; // end #action-logger

        do_action('al_after_overview' );

        echo '</div><!-- end .wrap -->';
    }
