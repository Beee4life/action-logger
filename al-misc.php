<?php

    /**
     * Content for the settings page
     */
    function action_logger_misc_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) );
        }
        ?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>

            <h1>Action Logger misc settings</h1>
    
            <?php ActionLogger::al_show_admin_notices(); ?>
    
            <?php do_action('al_before_settings' ); ?>

            <div id="action-logger" class="">

                <?php echo ActionLogger::al_admin_menu(); ?>

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
                    <input name="preserve_settings" id="preserve-settings" type="checkbox" value="1" <?php if ( false != $checked ) { echo 'checked '; } ?>/> <?php esc_html_e( 'If you uninstall the plugin, all data is removed as well. If you check this box, your logs won\'t be deleted upon uninstall.', 'action-logger' ); ?>
                    <br />
                    <br />
                    <input name="" type="submit" class="admin-button admin-button-small" value="Save settings"/>
                </form>

                <h2><?php esc_html_e( 'Support', 'action-logger' ); ?></h2>
                <p><?php echo sprintf( __( 'If you know about this plugin, you probably know me and know where to reach me. If not, please report it on GitHub in the %s.', 'action-logger' ), '<a href="https://github.com/Beee4life/action-logger/issues">issues section</a>' ); ?></p>
                <p><?php esc_html_e( 'Find more info about the plugin on', 'action-logger' ); ?> <a href="https://github.com/Beee4life/action-logger/">GitHub</a>.</p>

                <h2><?php esc_html_e( 'About the author', 'action-logger' ); ?></h2>
                <p>This plugin is created by Beee, a Wordpress developer from Amsterdam.</p>

            </div><!-- end #action-logger -->

        <?php do_action('al_after_settings' ); ?>
        </div><!-- end .wrap -->
<?php
    }
