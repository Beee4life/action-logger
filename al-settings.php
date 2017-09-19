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

            <?php do_action('al_before_settings' ); ?>

            <div id="action-logger" class="">

                <?php echo al_admin_menu(); ?>
    
                <?php echo al_check_php_version(); ?>

                <h2><?php esc_html_e( 'Select what to log', 'action-logger' ); ?></h2>
                <p><?php esc_html_e( 'Here you can select which actions you want to log/ignore.', 'action-logger' ); ?></p>
                <?php
                    $available_log_actions = get_option( 'al_available_log_actions' );
                    if ( $available_log_actions ) {
                        ?>
                        <form name="settings-form" id="settings-form" action="" method="post">
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
                                        <?php
                                            $show = false;
                                            if ( class_exists( 'EM_Events' ) && 'Events Manager' == $action[ 'action_generator' ] ) {
                                                $show = true;
                                            } elseif ( class_exists( 'CSV_Importer' ) && 'CSV Importer' == $action[ 'action_generator' ] ) {
                                                // $show = true;
                                            } elseif ( class_exists( 'RankingsImport' ) && 'Rankings Importer' == $action[ 'action_generator' ] ) {
                                                $show = true;
                                            } elseif ( class_exists( 'S2Member' ) && 'S2Member' == $action[ 'action_generator' ] ) {
                                                // $show = true;
                                            } elseif ( 'WordPress' == $action[ 'action_generator' ] ) {
                                                $show = true;
                                            } else {
                                                $show = false;
                                            }

                                            if ( $show == true ) {
                                            ?>

                                            <tr>
                                                <td class="hidden"><?php echo $action[ 'action_name' ]; ?></td>
                                                <td class=""><?php esc_html_e( $action[ 'action_title' ], 'action-logger' ); ?></td>
                                                <td class=""><?php echo $action[ 'action_generator' ]; ?></td>
                                                <td class=""><?php esc_html_e( $action[ 'action_description' ], 'action-logger' ); ?></td>
                                                <td class="checkbox">
                                                    <?php
                                                        $active    = 0;
                                                        $checked   = false;
                                                        $is_active = get_option( 'al_' . $action[ 'action_name' ] );
                                                        if ( $is_active ) {
                                                            $checked = 'checked';
                                                            $active  = 1;
                                                        }
                                                    ?>
                                                    <label for="action-status" class="screen-reader-text">
                                                        <?php esc_html_e( 'Active', 'action-logger' ); ?>
                                                    </label>
                                                    <input name="<?php echo $action[ 'action_name' ]; ?>" id="action-status" type="checkbox" value="<?php echo $active; ?>" <?php echo $checked; ?>/>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </form>
                <?php } ?>

            </div><!-- end #action-logger -->

        <?php do_action('al_after_settings' ); ?>
        </div><!-- end .wrap -->
<?php
    }
