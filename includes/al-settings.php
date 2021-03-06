<?php

    /**
     * Content for the settings page
     */
    function action_logger_settings_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) ) );
        }
        ?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>

            <h1>Action Logger settings</h1>

            <?php al_show_admin_notices(); ?>

            <div id="action-logger" class="">

                <?php echo ActionLogger::al_admin_menu(); ?>

                <form name="purge-logs-form" action="" method="post">
                    <input name="al_purge_logs_nonce" type="hidden" value="<?php echo wp_create_nonce( 'al-purge-logs-nonce' ); ?>"/>
                    <h2><?php esc_html_e( 'Purge logs', 'action-logger' ); ?></h2>
                    <p>
                        <?php esc_html_e( 'Select how long you want to keep the logs. Default is Forever.', 'action-logger' ); ?>
                    </p>
                    <p>
                        <?php
                            $log_rotation = array(
                                array(
                                    'key'   => 1,
                                    'label' => '1 day',
                                ),
                                array(
                                    'key'   => 7,
                                    'label' => '1 week',
                                ),
                                array(
                                    'key'   => 14,
                                    'label' => '2 weeks',
                                ),
                                array(
                                    'key'   => 30,
                                    'label' => '1 month',
                                ),
                                array(
                                    'key'   => 60,
                                    'label' => '2 months',
                                ),
                                array(
                                    'key'   => 365,
                                    'label' => '1 year',
                                ),
                                array(
                                    'key'   => 0,
                                    'label' => 'Forever',
                                ),
                            );
                            $purge_logs_after = ( false != get_option( 'al_purge_logs' ) ) ? get_option( 'al_purge_logs' ) : false;
                        ?>
                        <label for="purge_logs" class="screen-reader-text"></label>
                        <select name="al_purge_logs" id="purge_logs">
                            <?php foreach ( $log_rotation as $log ) { ?>
                                <option value="<?php echo $log[ 'key' ]; ?>"<?php echo( $purge_logs_after == $log[ 'key' ] ? ' selected' : '' ); ?>><?php echo $log[ 'label' ]; ?></option>';
                            <?php } ?>
                        </select>
                    </p>
                    <input type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Save settings', 'action-logger' ); ?>" />
                </form>

                <form name="settings-form" action="" method="post">
                    <input name="al_settings_page_nonce" type="hidden" value="<?php echo wp_create_nonce( 'al-settings-page-nonce' ); ?>"/>
                    <h2><?php esc_html_e( 'Who can do what', 'action-logger' ); ?></h2>
                    <p>
                        <?php esc_html_e( 'Here you can select what capability a user needs to see the logs. The default setting is "manage_options" which belongs to administrator.', 'action-logger' ); ?>
                        <?php esc_html_e( 'The reason why it\'s set per capability instead of per user is because two users with the same role can have different capabilities.', 'action-logger' ); ?>
                    </p>
                    <p>
                        <?php
                            $all_capabilities = get_role( 'administrator' )->capabilities;
                            $logs_user_role   = get_option( 'al_log_user_role' );
                            unset( $all_capabilities[ 'manage_links' ] ); // @TODO: why again
                            ksort( $all_capabilities );
                        ?>
                        <label for="select_cap" class="screen-reader-text"></label>
                        <select name="al_select_cap" id="select_cap">
                            <?php foreach ( $all_capabilities as $key => $value ) { ?>
                                <?php // Exclude deprecated capabilities ?>
                                <?php if ( substr( $key, 0, 6 ) != 'level_' ) { ?>
                                    <option value="<?php echo $key; ?>"<?php echo ( $logs_user_role == $key ? ' selected' : '' ); ?>><?php echo $key; ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </p>
                    <input type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Save settings', 'action-logger' ); ?>" />
                </form>

                <h2><?php esc_html_e( 'Preserve data when uninstalling', 'action-logger' ); ?></h2>
                <?php $checked = get_option( 'al_preserve_settings' ); ?>
                <div>
                    <form name="preserve-form" action="" method="post">
                        <input name="al_preserve_settings_nonce" type="hidden" value="<?php echo wp_create_nonce( 'al-preserve-settings-nonce' ); ?>"/>
                        <label for="preserve-settings" class="screen-reader-text">Preserve settings</label>
                        <p>
                            <input name="al_preserve_settings" id="preserve-settings" type="checkbox" value="1" <?php if ( false != $checked ) { echo 'checked '; } ?>/> <span class="checkbox"><?php esc_html_e( "If you uninstall the plugin, all data and settings are removed as well. If you check this box, your logs and settings won't be deleted upon uninstall.", 'action-logger' ); ?></span>
                        </p>
                        <input type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Save settings', 'action-logger' ); ?>" />
                    </form>
                </div>

                <h2><?php esc_html_e( 'Reset to default settings', 'action-logger' ); ?></h2>
                <p><?php esc_html_e( 'This deletes all logs and stored values.', 'action-logger' ); ?></p>
                <form name="reset" action="" method="post">
                    <input name="al_reset_all_nonce" type="hidden" value="<?php echo wp_create_nonce( 'al-reset-all-nonce' ); ?>" />
                    <input name="al_reset_all" type="submit" class="admin-button admin-button-small" onclick="return confirm( 'Are you sure you want to reset everything ?' )" value="<?php esc_html_e( 'Reset', 'action-logger' ); ?>" />
                </form>

            </div><!-- end #action-logger -->

        </div><!-- end .wrap -->
<?php
    }
