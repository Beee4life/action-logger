# Action Logger

Welcome to the Action Logger plugin for [Wordpress](http://wordpress.org). 


## Description

This plugin gives you the option to log various actions on your website. Default there are a few actions which are tracked from within WP core and some plugins I like/use.

## Features

1. User registration
1. User change
1. User delete

Next to that we included a few logging options for one of our favourite plugins: [Events Manager](http://wp-events-plugin.com/) + [Pro](https://eventsmanagerpro.com/). Right now you can track the following actions but more expected to follow:
* registration approved (tbc)
* registration cancelled
* registration rejected
* registration deleted

## Usage

## Impact

* Upon activation a new database table named `wp_action_logs` is created.
* Every action is logged real-time, which is 1 row being stored in the database
* Upon uninstallation the database table `wp_action_logs` is dropped (unless preserve data is selected in the options panel).

## FAQ

= Which plugins/actions are included in the plugin =

### WP core
* user registration
* user change
* user delete

### Events manager
* event booking cancelled
* event booking rejected
* event booking approved (in progress)
* more to follow...

= Can I log my own custom action actions ? =

Of course. To use the logger, you need to add piece of code on the place where you want the tracking to occur. This can be a plugin or a theme.

    ActionLogger::log_user_action( $action, $action_generator, $action_description );

To make sure your site won't break if you deactivate the plugin, wrap it in a `class_exists()` as follows:     

    if ( class_exists( 'ActionLogger' ) ) {
        ActionLogger::log_user_action( 'user_registered', 'action-logger', 'New user registered: "' . get_userdata( $user_id )->display_name . '".' );
    }

The code consists can contain 3 variables:
* $action (string) - default false
* $action_generator (string) - default false
* $action description (escaped string) - default false

= Can I use this plugin to track visit to special pages like registration confirmations or so ? =

Yes. You can do this by inserting a simple shortcode to the page you want to track. Insert the follwoing shortcode into your post/page:
    
    [actionlogger]

This will trigger a log entry as follows:

    'user->display_name' has visited 'post title'

* user->display_name will be taken from the user who triggers the action
* post title will be taken from the post/page where the shortcode is inserted.

You can override the default message with a simple `message=""` variable.

    [actionlogger message="did something bad"]

This will trigger a log entry as follows:

    'user->display_name' did something bad

### Misc

If you're a plugin author and you would like to see your hooks logged in this plugin, please contact me @ http://berryplasman.com.  

### To Do
* [X] - Add shortcode to track thank you pages and other status pages
* [ ] - Add WP errors
* [ ] - Add EM registration approve
* [ ] - Scan S2Member for hooks
* [ ] - Scan WPSC for hooks
* [ ] - Scan BuddyPress for hooks
* [ ] - Scan WP4MC for hooks

## Changelog

**1.0**

Initial release
