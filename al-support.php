<?php

    /**
     * Content for the settings page
     */
    function action_logger_support_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) );
        }
        ?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>

            <h1>Action Logger support</h1>

            <?php
                ActionLogger::al_show_admin_notices();
                // hook before settings page
                do_action('al_before_settings' );
            ?>

            <div id="action-logger" class="">

                <?php echo ActionLogger::al_admin_menu(); ?>

                <h2><?php esc_html_e( 'Support', 'action-logger' ); ?></h2>
                <p><?php echo sprintf( __( 'If you know about this plugin, you probably know me and know where to reach me. If not, please report it on GitHub in the %s.', 'action-logger' ), '<a href="https://github.com/Beee4life/action-logger/issues">issues section</a>' ); ?></p>

            </div><!-- end #action-logger -->

        <?php
            // hook after settings page
            do_action('al_after_settings' );
        ?>
        </div><!-- end .wrap -->
<?php
    }
