<?php
	/**
	 * @return WP_Error
	 */
    function al_errors() {
        $wp_error; // Will hold global variable safely
        return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error( null, null, null ) );
    }

	/**
	 * Displays error messages from form submissions
	 */
	function al_show_admin_notices() {
        if ( $codes = al_errors()->get_error_codes() ) {
            if ( is_wp_error( al_errors() ) ) {

                // Loop error codes and display errors
                $error      = false;
                $span_class = false;
                $prefix     = false;
                foreach ( $codes as $code ) {
                    if ( strpos( $code, 'success' ) !== false ) {
                        $span_class = 'notice-success ';
                        $prefix     = false;
                    } elseif ( strpos( $code, 'warning' ) !== false ) {
                        $span_class = 'notice-warning ';
                        $prefix     = esc_html( __( 'Warning', 'action-logger' ) );
                    } elseif ( strpos( $code, 'info' ) !== false ) {
                        $span_class = 'notice-info ';
                        $prefix     = false;
                    } else {
                        $error  = true;
                        $prefix = esc_html( __( 'Error', 'action-logger' ) );
                    }
                }
                echo '<div class="notice ' . $span_class . 'is-dismissible">';
                foreach( $codes as $code ) {
                    $message = al_errors()->get_error_message( $code );
                    echo '<div class="">';
                    if ( true == $prefix ) {
                        echo '<strong>' . $prefix . ':</strong> ';
                    }
                    echo $message;
                    echo '</div>';
                    echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html( __( 'Dismiss this notice', 'action-logger' ) ) . '</span></button>';
                }
                echo '</div>';
            }
        }
    }
