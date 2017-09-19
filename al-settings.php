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

            <h1>Action Logger settings page</h1>
    
            <?php ActionLogger::al_show_admin_notices(); ?>

            <?php do_action('al_before_settings' ); ?>

            <div id="action-logger" class="">

                <?php echo al_admin_menu(); ?>
    
                <?php echo al_check_php_version(); ?>

                <form name="settings-form" action="" method="post">
                    <input name="settings_page_nonce" type="hidden" value="<?php echo wp_create_nonce( 'settings-page-nonce' ); ?>"/>
                    <h2><?php esc_html_e( 'Who can do what', 'action-logger' ); ?></h2>
                    <p>
                        <?php esc_html_e( 'Here you can select what capability a user needs to see the logs. The default setting is "manage_options" which belongs to administrator.', 'action-logger' ); ?>
                        <?php esc_html_e( 'The reason why it\'s set per capability instead of per user is because two users with the same role can have different capabilities.', 'action-logger' ); ?>
                    </p>
                    <p>
                        <?php
                            $all_capabilities = get_role( 'administrator' )->capabilities;
                            $logs_user_role   = get_option( 'al_log_user_role' );
                            ksort( $all_capabilities );
                        ?>
                        <label for="select_cap" class="screen-reader-text"></label>
                        <select name="select_cap" id="select_cap">
                            <?php foreach ( $all_capabilities as $key => $value ) { ?>
                                <option value="<?php echo $key; ?>"<?php echo ( $logs_user_role == $key ? ' selected' : '' ); ?>><?php echo $key; ?></option>';
                            <?php } ?>
                        </select>
                    </p>
                    <input type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Save settings', 'action-logger' ); ?>" />
                </form>

                <h2><?php esc_html_e( 'Preserve data when uninstalling', 'action-logger' ); ?></h2>
                <?php $checked = get_option( 'al_preserve_settings' ); ?>
                <div>
                    <form name="preserve-form" action="" method="post">
                        <input name="preserve_settings_nonce" type="hidden" value="<?php echo wp_create_nonce( 'preserve-settings-nonce' ); ?>"/>
                        <label for="preserve-settings" class="screen-reader-text">Preserve settings</label>
                        <p>
                            <input name="preserve_settings" id="preserve-settings" type="checkbox" value="1" <?php if ( false != $checked ) { echo 'checked '; } ?>/> <span class="checkbox"><?php esc_html_e( 'If you uninstall the plugin, all data is removed as well. If you check this box, your logs won\'t be deleted upon uninstall.', 'action-logger' ); ?></span>
                        </p>
                        <input name="" type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Save settings', 'action-logger' ); ?>" />
                    </form>
                </div>

            </div><!-- end #action-logger -->

        <?php do_action('al_after_settings' ); ?>
        </div><!-- end .wrap -->
<?php
    }
