<?php

    /**
     * Content for the settings page
     */
    function action_logger_actions_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html( __( 'Sorry, you do not have sufficient permissions to access this page.', 'action-logger' ) ) );
        }
        ?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>

            <h1>Action Logger log actions</h1>

            <?php al_show_admin_notices(); ?>

            <div id="action-logger" class="">

                <?php echo ActionLogger::al_admin_menu(); ?>

                <h2><?php esc_html_e( 'Available log actions', 'action-logger' ); ?></h2>
                <p><?php esc_html_e( 'Here you can select which actions you want to log/ignore.', 'action-logger' ); ?></p>
                <?php
                    $post_type_args = array(
                        'public'             => true,
                        'publicly_queryable' => true,
                    );
                    $available_post_types  = get_post_types( $post_type_args, 'names', 'OR' );
                    $available_log_actions = get_option( 'al_available_log_actions');
                    $active_post_types     = get_option( 'al_active_post_types' );
                    if ( 0 == $active_post_types ) {
                        $active_post_types = [];
                    }

                    if ( $available_log_actions ) {
                        ?>
                        <form name="log_actions" id="settings-form" action="" method="post">
                            <input name="active_logs_nonce" type="hidden" value="<?php echo wp_create_nonce( 'active-logs-nonce' ); ?>"/>
                            <table>
                                <thead>
                                    <tr>
                                        <th class=""><?php esc_html_e( 'Action', 'action-logger' ); ?></th>
                                        <th class=""><?php esc_html_e( 'Generator', 'action-logger' ); ?></th>
                                        <th class=""><?php esc_html_e( 'Description', 'action-logger' ); ?></th>
                                        <th class="checkbox"><?php esc_html_e( 'Active', 'action-logger' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $action_counter = 0; ?>
                                    <?php foreach( $available_log_actions as $action ) { ?>
                                        <?php $action_counter++; ?>
                                        <?php
                                            $show = false;
                                            if ( class_exists( 'EM_Events' ) && 'Events Manager' == $action[ 'action_generator' ] ) {
                                                // $show = true;
                                            } elseif ( class_exists( 'CSV_WP' ) && 'CSV Importer' == $action[ 'action_generator' ] ) {
                                                // $show = true;
                                            } elseif ( class_exists( 'RankingsImport' ) && 'Rankings Importer' == $action[ 'action_generator' ] ) {
                                                // $show = true;
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
                            <input type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Save settings', 'action-logger' ); ?>" />
                        </form>

                        <?php if ( $available_post_types ) { ?>
                            <form name="post_types" id="post-types-form" action="" method="post">
                                <input name="post_types_nonce" type="hidden" value="<?php echo wp_create_nonce( 'post-types-nonce' ); ?>"/>
                                <table class="ai_post_types">
                                    <thead>
                                    <tr>
                                        <th>Post types</th>
                                        <th>Log</th>
                                        <th>Publish</th>
                                        <th>Edit</th>
                                        <th>Delete</th>
                                        <th>Pending</th>
                                        <!--<th>Republish</th>-->
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ( $available_post_types as $post_type ) { ?>
                                        <tr>
                                            <td><?php echo $post_type; ?></td>
                                            <td class="checkbox">
                                                <?php
                                                    $checked    = false;
                                                    $key_exists = array_key_exists( $post_type, $active_post_types );
                                                    if ( true == $key_exists ) {
                                                        $in_array = in_array( 'active', $active_post_types[$post_type] );
                                                        if ( true == $in_array ) {
                                                            $checked = 'checked';
                                                        }
                                                    }
                                                ?>
                                                <label for="post-type" class="screen-reader-text"><?php esc_html_e( 'Active', 'action-logger' ); ?></label>
                                                <input name="post_types[<?php echo $post_type; ?>][]" id="post-type" type="checkbox" value="active" <?php echo $checked; ?>/>
                                            </td>

                                            <td class="checkbox">
                                                <?php
                                                    $checked    = false;
                                                    if ( true == $key_exists ) {
                                                        $in_array = in_array( 'publish', $active_post_types[$post_type] );
                                                        if ( true == $in_array ) {
                                                            $checked = 'checked';
                                                        }
                                                    }
                                                ?>
                                                <label for="post-type" class="screen-reader-text"><?php esc_html_e( 'Publish', 'action-logger' ); ?></label>
                                                <input name="post_types[<?php echo $post_type; ?>][]" id="post-type" type="checkbox" value="publish" <?php echo $checked; ?>/>
                                            </td>

                                            <td class="checkbox">
                                                <?php
                                                    $checked    = false;
                                                    if ( true == $key_exists ) {
                                                        $in_array = in_array( 'edit', $active_post_types[$post_type] );
                                                        if ( true == $in_array ) {
                                                            $checked = 'checked';
                                                        }
                                                    }
                                                ?>
                                                <label for="post-type" class="screen-reader-text"><?php esc_html_e( 'Edit', 'action-logger' ); ?></label>
                                                <input name="post_types[<?php echo $post_type; ?>][]" id="post-type" type="checkbox" value="edit" <?php echo $checked; ?>/>
                                            </td>

                                            <td class="checkbox">
                                                <?php
                                                    $checked    = false;
                                                    if ( true == $key_exists ) {
                                                        $in_array = in_array( 'delete', $active_post_types[$post_type] );
                                                        if ( true == $in_array ) {
                                                            $checked = 'checked';
                                                        }
                                                    }
                                                ?>
                                                <label for="post-type" class="screen-reader-text"><?php esc_html_e( 'Delete', 'action-logger' ); ?></label>
                                                <input name="post_types[<?php echo $post_type; ?>][]" id="post-type" type="checkbox" value="delete" <?php echo $checked; ?>/>
                                            </td>

                                            <td class="checkbox">
                                                <?php
                                                    $checked    = false;
                                                    if ( true == $key_exists ) {
                                                        $in_array = in_array( 'pending', $active_post_types[$post_type] );
                                                        if ( true == $in_array ) {
                                                            $checked = 'checked';
                                                        }
                                                    }
                                                ?>
                                                <label for="post-type" class="screen-reader-text"><?php esc_html_e( 'Pending', 'action-logger' ); ?></label>
                                                <input name="post_types[<?php echo $post_type; ?>][]" id="post-type" type="checkbox" value="pending" <?php echo $checked; ?>/>
                                            </td>

                                            <td class="hidden checkbox">
                                                <?php
                                                    // @TODO: look into this
                                                    $checked    = false;
                                                    if ( true == $key_exists ) {
                                                        $in_array = in_array( 'republish', $active_post_types[$post_type] );
                                                        if ( true == $in_array ) {
                                                            $checked = 'checked';
                                                        }
                                                    }
                                                ?>
                                                <label for="post-type" class="screen-reader-text"><?php esc_html_e( 'Republish', 'action-logger' ); ?></label>
                                                <input name="post_types[<?php echo $post_type; ?>][]" id="post-type" type="checkbox" value="republish" <?php echo $checked; ?>/>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                                <input type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Save settings', 'action-logger' ); ?>" />
                            </form>
                        <?php } ?>
                <?php } ?>

            </div><!-- end #action-logger -->

        </div><!-- end .wrap -->
<?php
    }
