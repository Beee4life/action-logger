<?php

    /**
     * Content for the settings page
     */
    function action_logger_misc_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
	        wp_die( esc_html( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) ) );
        }
        ?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>

            <h1>Action Logger misc settings</h1>

            <?php al_show_admin_notices(); ?>

            <?php do_action('al_before_settings' ); ?>

            <div id="action-logger" class="">

                <?php echo al_admin_menu(); ?>

                <form name="capabilities-form" action="" method="post">
                    <input name="active_logs_nonce" type="hidden" value="<?php echo wp_create_nonce( 'active-logs-nonce' ); ?>"/>
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
                    <br />
                    <input type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Save settings', 'action-logger' ); ?>" />
                </form>

                <h2><?php esc_html_e( 'Export data to CSV', 'action-logger' ); ?></h2>
                <p><?php esc_html_e( 'By clicking this button you will trigger a download for a CSV (comma separated value) file.', 'action-logger' ); ?></p>
                <form name="export-form" action="" method="post">
                    <input name="export_csv" type="hidden" value="1" />
                    <input name="export_csv_nonce" type="hidden" value="<?php echo wp_create_nonce( 'export-csv-nonce' ); ?>"/>
                    <input name="" type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Export to CSV', 'action-logger' ); ?>"/>
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
                <p><?php echo sprintf( __( 'If you know about this plugin, you probably know me and know where to reach me. If not, please report it on GitHub in the <a href="%s">issues section</a>.', 'action-logger' ), esc_url( 'https://github.com/Beee4life/action-logger/issues' ) ); ?></p>
                <p><?php esc_html_e( 'Find more info about the plugin on', 'action-logger' ); ?> <a href="https://github.com/Beee4life/action-logger/">GitHub</a>.</p>

                <h2><?php esc_html_e( 'About the author', 'action-logger' ); ?></h2>
                <p><?php echo sprintf( __( 'This plugin is created by %s, a Wordpress developer from Amsterdam.', 'action-logger' ), '<a href="' . esc_url( 'http://www.berryplasman.com' ) . '">Beee</a>' ); ?></p>

                <h2>Donate</h2>
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="donation">
                    <div>
                        <p>This plugin is free to use and if you like it, I would appreciate a small donation, so I can continue to develop this in my free time.</p>

                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="business" value="info@berryplasman.com">
                        <input type="hidden" name="item_name" value="ActionLogger">
                        <input type="hidden" name="buyer_credit_promo_code" value="">
                        <input type="hidden" name="buyer_credit_product_category" value="">
                        <input type="hidden" name="buyer_credit_shipping_method" value="">
                        <input type="hidden" name="buyer_credit_user_address_change" value="">
                        <input type="hidden" name="no_shipping" value="1">
                        <input type="hidden" name="return" value="<?php echo site_url(); ?>/wp-admin/admin.php?page=al-misc">
                        <input type="hidden" name="no_note" value="1">
                        <input type="hidden" name="currency_code" value="USD">
                        <input type="hidden" name="tax" value="0">
                        <input type="hidden" name="lc" value="US">
                        <input type="hidden" name="bn" value="PP-DonationsBF">
                        <div class="donation-amount">$
                            <label for="donate-amount" class="screen-reader-text">
                                Donate amount
                            </label>
                            <input type="number" id="donate-amount" min="1" name="amount" value="10">
                            <input type="submit" class="button-primary" value="Donate 💰">
                        </div>
                    </div>
                </form>

            </div><!-- end #action-logger -->

        <?php do_action('al_after_settings' ); ?>
        </div><!-- end .wrap -->
<?php
    }
