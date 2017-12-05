<?php

    /**
     * Content for the 'dashboard page'
     */
    function action_logger_dashboard() {

        if ( ! current_user_can( get_option( 'al_log_user_role' ) ) ) {
            wp_die( esc_html( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) ) );
        }
        ?>

        <div class="wrap">

            <h1>Action Logger</h1>

            <?php al_show_admin_notices(); ?>

            <div id="action-logger" class="">

                <?php echo ActionLogger::al_admin_menu(); ?>

                <?php
                    // get results from db
                    global $wpdb;
                    $ppp       = ( false != get_user_meta( get_current_user_id(), 'al_ppp', true ) ) ? get_user_meta( get_current_user_id(), 'al_ppp', true ) : get_option( 'al_posts_per_page' );
                    $all_items = array();
                    $items     = array();

                    $all_items = $wpdb->get_results( "
                        SELECT *
                        FROM " . $wpdb->prefix . "action_logs
                    ");

                    if ( ! isset( $_GET['paged'] ) || $_GET['paged'] == 1 ) {
                        $page_number = 1;
                        $offset      = false;
                    } else {
                        $page_number = $_GET['paged'];
                        $offset      = ' OFFSET ' . ( $page_number - 1 ) * $ppp;
                    }

                    $items = $wpdb->get_results( "
                        SELECT *
                        FROM " . $wpdb->prefix . "action_logs
                        ORDER BY id DESC
                        LIMIT " . $ppp . $offset . "
                    ");
                ?>

                <?php if ( count( $items ) == 0 ) { ?>
                    <p><?php esc_html_e( 'No logs (yet)...', 'action-logger' ); ?></p>
                <?php } elseif ( count( $items ) > 0 ) { ?>

                    <?php rsort( $items ); ?>
                    <?php $pages = ceil( ( count( $all_items ) / $ppp ) ); ?>
                    <?php $item_count = 0; ?>
                    <?php //echo $pages; ?>

                    <p><?php esc_html_e( 'This page shows a log of all actions done by users, which are "interesting" to log.', 'action-logger' ); ?></p>
                    <p>
                        <?php echo sprintf( esc_html__( 'You have %d log items.', 'action-logger' ), count( $all_items ) ); ?>
                        <br />
                        <small><?php esc_html_e( 'Log items are sorted, newest to oldest.', 'action-logger' ); ?></small>
                    </p>

                    <?php echo al_get_pagination( $_GET, $pages ); ?>

                    <form name="logs-form" action="" method="post">
                        <input name="delete_action_items_nonce" type="hidden" value="<?php echo wp_create_nonce( 'delete-actions-items-nonce' ); ?>" />
                        <table class="action-logs">
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
                                <?php //if ( ! isset( $_GET[ 'paged' ] ) ) { $item_count++; } ?>
                                <tr class="row">
                                    <td class="datetime"><?php echo date( 'M j @ H:i:s', $item->action_time ); ?> (+<?php echo get_option( 'gmt_offset' ); ?>)</td>
                                    <?php if ( current_user_can( 'manage_options' ) ) { ?>
                                        <td class="action"><?php echo $item->action; ?></td>
                                    <?php } ?>
                                    <td class="generator"><?php echo $item->action_generator; ?></td>
                                    <td class="description">
                                        <?php
                                            // echo al_replace_log_vars( $item->action_user, $item->action_description, $item->post_id );
                                            if ( 'Shortcode' == $item->action_generator ) {
                                                echo $item->action_description. ' <a href="' . get_the_permalink( $item->post_id ) . '">' . get_the_title( $item->post_id ) . '</a>';
                                            } else {
	                                            echo $item->action_description;
                                            }
                                        ?>
                                    </td>
                                    <?php if ( current_user_can( 'manage_options' ) ) { ?>
                                        <td class="checkbox">
                                            <label for="rows" class="screen-reader-text">
                                                <?php esc_html_e( 'Delete', 'action-logger' ); ?>
                                            </label>
                                            <input name="rows[]" id="rows" type="checkbox" value="<?php echo $item->id; ?>" />
                                        </td>
                                    <?php } ?>
                                </tr>
                                <?php if ( $item_count == $ppp ) { break; } ?>
                            <?php } ?>
                            </tbody>
                        </table>
                        <?php echo al_get_pagination( $_GET, $pages ); ?>
                        <?php if ( current_user_can( 'manage_options' ) ) { ?>
                            <input name="delete_selected" type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Delete selected items', 'action-logger' ); ?>" />
                            <input name="delete_all" type="submit" class="admin-button admin-button-small" onclick="return confirm( 'Are you sure you want to delete all logs ?' )" value="<?php esc_html_e( 'Delete all', 'action-logger' ); ?>" />
                        <?php } ?>
                    </form>

                <?php } ?>

            </div><!-- end #action-logger -->

        </div><!-- end .wrap -->
<?php } ?>
