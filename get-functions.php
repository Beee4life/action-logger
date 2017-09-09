<?php

    /**
     * Read CSV
     *
     * @param bool $file name of file to read
     * @return array|bool array of 'read' items
     */
    function read_csv_logs( $file = false ) {

        $items    = false;
        $log_path = plugin_dir_path( __FILE__ ) . 'logs/';
        if ( ( $handle = fopen( $log_path . $file, "r" ) ) !== false ) {
            $line_number = 0;
            $items       = array();
            while ( ( $csv_line = fgetcsv( $handle, 1000, "," ) ) !== false ) {
                $line_number++;

                // $items[] = $csv_line;

                $unix_date   = $csv_line[ 0 ];
                $user_id     = $csv_line[ 1 ];
                $action      = $csv_line[ 2 ];
                $generator   = $csv_line[ 3 ];
                $description = $csv_line[ 4 ];
                $tag         = $csv_line[ 5 ];
                $new_array   = array(
                    'date'        => $unix_date,
                    'action'      => $action,
                    'user_id'     => $user_id,
                    'generator'   => $generator,
                    'description' => $description,
                    'tag'         => $tag,
                );
                $items[]     = $new_array;

            }
            fclose( $handle );
        }

        return $items;

    }
