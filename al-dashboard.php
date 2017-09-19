<?php

    /**
     * Content for the 'dashboard page'
     */
    function action_logger_dashboard() {

        if ( ! current_user_can( 'edit_users' ) ) {
            wp_die( esc_html( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) ) );
        }
        ?>

        <div class="wrap">

        <h1>Action Logger overview</h1>

        <?php al_show_admin_notices(); ?>

        <?php do_action( 'al_before_overview', '' ); ?>

        <div id="action-logger" class="">

            <?php echo al_admin_menu(); ?>

            <?php
                // get results from db
                global $wpdb;
                $items = array();
                $items = $wpdb->get_results( "
                    SELECT * FROM " . $wpdb->prefix . "action_logs
                    order by id DESC
                ");
            ?>

            <?php if ( count( $items ) == 0 ) { ?>
                <p><?php esc_html_e( 'No logs (yet)...', 'action-logger' ); ?></p>
            <?php } elseif ( count( $items ) > 0 ) { ?>
                <?php rsort( $items ); ?>
                <p><?php esc_html_e( 'This page shows a log of all actions done by users, which are "interesting" to log.', 'action-logger' ); ?></p>
                <h2><?php esc_html_e( 'Logs', 'action-logger' ); ?></h2>
                <p><small><?php esc_html_e( 'Log items are sorted, newest to oldest.', 'action-logger' ); ?></small></p>
                <form name="logs-form" action="" method="post">
                    <input name="delete_action_items_nonce" type="hidden" value="<?php echo wp_create_nonce( 'delete-actions-items-nonce' ); ?>" />
                    <table>
                        <thead>
                        <tr class="row">
                            <th class="datetime"><?php esc_html_e( 'Date/time', 'action-logger' ); ?></th>
                            <?php if ( current_user_can( 'manage_options' ) ) { ?>
                                <th class="action"><?php esc_html_e( 'Action', 'action-logger' ); ?></th>
                            <?php } ?>
                            <th class="generator"><?php esc_html_e( 'Generated by', 'action-logger' ); ?></th>
                            <th class="description"><?php esc_html_e( 'Description', 'action-logger' ); ?></th>
                            <?php if ( current_user_can( 'manage_options' ) ) { ?>
                                <th class=""><?php esc_html_e( 'Delete', 'action-logger' ); ?></th>
                            <?php } ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach( $items as $item ) { ?>
                            <tr class="row">
                                <td class="datetime"><?php echo date( 'M j @ H:i:s', $item->action_time ); ?> (+<?php echo get_option( 'gmt_offset' ); ?>)</td>
                                <?php if ( current_user_can( 'manage_options' ) ) { ?>
                                    <td class="action"><?php echo $item->action; ?></td>
                                <?php } ?>
                                <td class="generator"><?php echo $item->action_generator; ?></td>
                                <td class="description"><?php esc_html_e( $item->action_description, 'action-logger' ); ?></td>
                                <?php if ( current_user_can( 'manage_options' ) ) { ?>
                                    <td class="checkbox">
                                        <label for="rows" class="screen-reader-text">
                                            <?php esc_html_e( 'Delete', 'action-logger' ); ?>
                                        </label>
                                        <input name="rows[]" id="rows" type="checkbox" value="<?php echo $item->id; ?>" />
                                    </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                <?php if ( current_user_can( 'manage_options' ) ) { ?>
                    <input name="delete" type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Delete selected items', 'action-logger' ); ?>" />
                <?php } ?>
            </form>

                <br />
                <h2><?php esc_html_e( 'Nuke \'em all', 'action-logger' ); ?></h2>
                <p><?php esc_html_e( 'Delete all items. Watch out, there\'s no confirmation. Delete = delete !', 'action-logger' ); ?></p>
                <form name="delete-logs" action="" method="post">
                    <input name="delete_all_logs_nonce" type="hidden" value="<?php echo wp_create_nonce( 'delete-all-logs-nonce' ); ?>" />
                    <label for="delete-all" class="screen-reader-text"><?php esc_html_e( 'Delete', 'action-logger' ); ?></label>
                    <input name="delete_all" id="delete-all" type="checkbox" value="1" /> &nbsp;&nbsp;
                    <input name="delete" type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Delete all', 'action-logger' ); ?>" />
                </form>
            <?php } ?>

        </div><!-- end #action-logger -->

        <?php do_action( 'al_after_overview' ); ?>

        </div><!-- end .wrap -->
<?php } ?>
