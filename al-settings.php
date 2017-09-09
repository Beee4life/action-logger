<?php

    /**
     * Content for the settings page
     */
    function action_logger_settings_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) );
        }

        echo '<div class="wrap">';
        echo '<div id="icon-options-general" class="icon32"><br /></div>';

        echo "<h1>Action Logger settings</h1>";

        idf_show_error_messages();
        do_action('al_before_settings' );

        echo '<div id="action-logger" class="">';
        ?>

        <p><?php echo __( 'Here you can export the data to csv and set some values for this plugin.', 'action-logger' ); ?></p>
        <?php if ( current_user_can( 'manage_options' ) ) { ?>
            <p><a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=action-logger">Overview</a> | Settings</p>
        <?php } ?>

        <h2><?php echo __( 'Export data to CSV', 'action-logger' ); ?></h2>
        <p><?php echo __( 'By clicking this button you will trigger a download for a CSV (comma separated value) file.', 'action-logger' ); ?></p>
        <form name="settings-form" action="" method="post">
            <input name="export_csv" type="hidden" value="1" />
            <input name="export_csv_nonce" type="hidden" value="<?php echo wp_create_nonce( 'export-csv-nonce' ); ?>"/>
            <input name="" type="submit" class="admin-button admin-button-small" value="Export to CSV"/>
        </form>

        <h2><?php echo __( 'Preserve data when uninstalling', 'action-logger' ); ?></h2>
        <?php $checked = get_option( 'al_preserve_settings' ); ?>
        <form name="settings-form" action="" method="post">
            <input name="settings_form" type="hidden" value="1" />
            <input name="save_settings_nonce" type="hidden" value="<?php echo wp_create_nonce( 'save-settings-nonce' ); ?>"/>
            <label for="preserve-settings" class="screen-reader-text">Preserve settings</label>
            <input name="preserve_settings" id="preserve-settings" type="checkbox" value="1" <?php if ( false != $checked ) { echo 'checked '; } ?>/> <?php echo __( 'If you uninstall the plugin, all data is removed as well. If you check this box, your data won\'t be deleted upon uninstall.', 'action-logger' ); ?>
            <br />
            <br />
            <input name="save_settings" type="submit" class="admin-button admin-button-small" value="Save settings"/>
        </form>

        <h2><?php echo __( 'Nuke \'em all', 'action-logger' ); ?></h2>
        <p><?php echo __( 'Delete all items. Watch out, there\'s no confirmation. Delete = delete !', 'action-logger' ); ?></p>
        <form name="delete-logs" action="" method="post">
            <input name="delete_all_logs_nonce" type="hidden" value="<?php echo wp_create_nonce( 'delete-all-logs-nonce' ); ?>" />
            <label for="delete-all" class="screen-reader-text">Delete</label>
            <input name="delete_all" id="delete-all" type="checkbox" value="1" /> &nbsp;&nbsp;
            <input name="delete" type="submit" class="admin-button admin-button-small" value="Delete all" />
        </form>

        <?php

        echo '</div>'; // end #action-logger

        do_action('al_after_settings' );

        echo '</div><!-- end .wrap -->';
    }
