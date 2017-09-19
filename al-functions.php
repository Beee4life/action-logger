<?php
    function al_check_php_version() {
        $php_version = intval( phpversion() );
        if ( $php_version < 5.4 ) {
            // return '<p>' . esc_html( __( 'Your PHP version it too low. You should consider updating.', 'action-logger' ) ) . '</p>';
        } else {
            return false;
        }
    }
