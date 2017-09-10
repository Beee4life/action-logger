<?php

    /**
     * Content for the settings page
     */
    function action_logger_settings_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) );
        }
        ?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>

            <h1>Action Logger settings</h1>

            <?php
                ActionLogger::al_show_admin_notices();
                // hook before settings page
                do_action('al_before_settings' );
            ?>

            <div id="action-logger" class="">

                <?php echo ActionLogger::al_admin_menu(); ?>

                <p><?php esc_html_e( 'Here you can export the data to csv and set some values for this plugin.', 'action-logger' ); ?></p>
                <h2><?php esc_html_e( 'Select what to log', 'action-logger' ); ?></h2>
                <p><?php esc_html_e( 'Here you can select which actions you want to log/ignore.', 'action-logger' ); ?></p>
                <?php
                    // get all log actions
                    $available_log_actions = get_option( 'al_available_log_actions' );
                    // loop through log actions
                    if ( $available_log_actions ) {
                        ?>
                        <form name="settings-form" action="" method="post">
                            <input name="active_logs_nonce" type="hidden" value="<?php echo wp_create_nonce( 'active-logs-nonce' ); ?>"/>
                            <table>
                                <thead>
                                    <tr>
                                        <th class="hidden">Action name</th>
                                        <th class="">Action</th>
                                        <th class="">Generator</th>
                                        <th class="">Description</th>
                                        <th class="checkbox">Active</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $action_counter = 0; ?>
                                    <?php foreach( $available_log_actions as $action ) { ?>
                                        <?php $action_counter++; ?>
                                    <tr>
                                        <td class="hidden"><?php echo $action[ 'action_name' ]; ?></td>
                                        <td class=""><?php echo $action[ 'action_title' ]; ?></td>
                                        <td class=""><?php echo $action[ 'action_generator' ]; ?></td>
                                        <td class=""><?php echo $action[ 'action_description' ]; ?></td>
                                        <td class="checkbox">
                                            <?php
                                                $active = 0;
                                                $checked = false;
                                                $is_active = get_option( 'al_' . $action[ 'action_name' ] );
                                                if ( $is_active ) {
                                                    $checked = 'checked';
                                                    $active = 1;
                                                }
                                            ?>
                                            <label for="action-status" class="screen-reader-text">
                                                <?php esc_html_e( 'Active', 'action-logger' ); ?>
                                            </label>
                                            <input name="<?php echo $action[ 'action_name' ]; ?>" id="action-status" type="checkbox" value="<?php echo $active; ?>" <?php echo $checked; ?>/>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <br />
                            <input name="" type="submit" class="admin-button admin-button-small" value="Save settings" />
                        </form>
                <?php } ?>

                <h2><?php esc_html_e( 'Export data to CSV', 'action-logger' ); ?></h2>
                <p><?php esc_html_e( 'By clicking this button you will trigger a download for a CSV (comma separated value) file.', 'action-logger' ); ?></p>
                <form name="export-form" action="" method="post">
                    <input name="export_csv" type="hidden" value="1" />
                    <input name="export_csv_nonce" type="hidden" value="<?php echo wp_create_nonce( 'export-csv-nonce' ); ?>"/>
                    <input name="" type="submit" class="admin-button admin-button-small" value="Export to CSV"/>
                </form>

                <h2><?php esc_html_e( 'Preserve data when uninstalling', 'action-logger' ); ?></h2>
                <?php $checked = get_option( 'al_preserve_settings' ); ?>
                <form name="preserve-form" action="" method="post">
                    <input name="preserve_settings_nonce" type="hidden" value="<?php echo wp_create_nonce( 'preserve-settings-nonce' ); ?>"/>
                    <label for="preserve-settings" class="screen-reader-text">Preserve settings</label>
                    <input name="preserve_settings" id="preserve-settings" type="checkbox" value="1" <?php if ( false != $checked ) { echo 'checked '; } ?>/> <?php esc_html_e( 'If you uninstall the plugin, all data is removed as well. If you check this box, your data won\'t be deleted upon uninstall.', 'action-logger' ); ?>
                    <br />
                    <br />
                    <input name="" type="submit" class="admin-button admin-button-small" value="Save settings"/>
                </form>

                <h2><?php esc_html_e( 'Nuke \'em all', 'action-logger' ); ?></h2>
                <p><?php esc_html_e( 'Delete all items. Watch out, there\'s no confirmation. Delete = delete !', 'action-logger' ); ?></p>
                <form name="delete-logs" action="" method="post">
                    <input name="delete_all_logs_nonce" type="hidden" value="<?php echo wp_create_nonce( 'delete-all-logs-nonce' ); ?>" />
                    <label for="delete-all" class="screen-reader-text">Delete</label>
                    <input name="delete_all" id="delete-all" type="checkbox" value="1" /> &nbsp;&nbsp;
                    <input name="delete" type="submit" class="admin-button admin-button-small" value="Delete all" />
                </form>

            </div><!-- end #action-logger -->

        <?php
            // hook after settings page
            do_action('al_after_settings' );
        ?>
        </div><!-- end .wrap -->
<?php
    }
