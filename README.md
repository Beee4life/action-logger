# Action Logger

Welcome to the Action Logger plugin for [Wordpress](http://wordpress.org). 


## Description

## Features

This plugin gives you the option to log various actions on your website. Default there are 3 core Wordpress actions which can be tracked:
1. User registration
1. User change
1. User delete

Next to that we included a few loggin options for one of our favourite plugins: [Events Manager](http://wp-events-plugin.com/) + [Pro](https://eventsmanagerpro.com/). You can track the following actions:
* registration approved (tbc)
* registration cancelled
* registration rejected
* registration deleted

## Usage

To use the logger, you need to add piece of code on the place where you want the tracking to occur.

    ActionLogger::log_user_action( $action, $action_generator, $action_description );

To make sure your site won't break if you deactivate the plugin, wrap it in a `class_exists()` as follows:     

    if ( class_exists( 'ActionLogger' ) ) {
        ActionLogger::log_user_action( 'user_registered', 'action-logger', 'New user registered: "' . get_userdata( $user_id )->display_name . '".' );
    }
    
The code consists can contain 3 variables:
* $action (string)
* $action_generator (string)
* $action description

## FAQ

### To Do
* [X] - create shortcode for easier logging

### Misc

Author: [Beee](http://www.berryplasman.nl)

## Changelog


**1.0**

Initial release
