<?php
    
    function my_admin_help( $old_help, $screen_id, $screen ) {
    
        // echo '<pre>'; var_dump($screen_id); echo '</pre>'; exit;
    
        // Not our screen, exit earlier
        if ( 'toplevel_page_action-logger' != $screen_id ) {
            return;
        }
    
        // echo '<pre>'; var_dump('HIT'); echo '</pre>'; exit;

        // Add one help tab
        $screen->add_help_tab( array(
            'id'      => 'my-admin-help',
            'title'   => esc_html__( 'My Help Tab', 'action-logger' ),
            'content' => '<p>' . esc_html__( 'Descriptive content that will show in My Help Tab-body goes here.', 'action-logger' ) . '</p>',
            // Use 'callback' to use callback function to display tab content
        ) );
        
        // This sets the sidebar for help screen, if required
        get_current_screen()->set_help_sidebar(
            '<p><strong>' . esc_html__( 'For more information:', 'action-logger' ) . '</strong></p>' .
            '<p><a href="https://wordpress.org/">WordPress</a></p>' .
            '<p><a href="https://wordpress.org/support/" target="_blank">' . esc_html__( 'Support Forums', 'action-logger' ) . '</a></p>'
        );
    
        // echo '<pre>'; var_dump($screen); echo '</pre>'; exit;
    
        return $old_help;
    }
    add_filter( 'contextual_help', 'my_admin_help', 5, 3 );
