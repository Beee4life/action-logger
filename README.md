# Action Logger

Welcome to the Action Logger plugin for [Wordpress](http://wordpress.org). 

This plugin gives you the option to log various actions on your website. Default there are a few actions which are tracked from within WordPress' core and some plugins I like/use.

## Loggable actions

### WP core
* user registration
* user change
* user delete

### Events manager
Next to that we included a few logging options for one of our favourite plugins: [Events Manager](http://wp-events-plugin.com/) + [Pro](https://eventsmanagerpro.com/). Right now you can track the following actions but more expected to follow:
* registration canceled
* registration rejected
* registration deleted

## Impact

The overall impact is minimal.

* Upon activation a new database table named `wp_action_logs` is created.
* Every action is logged real-time, which is 1 row being stored in the database
* Upon uninstallation the database table `wp_action_logs` is dropped (unless preserve data is selected in the options panel).

## Usage

You can use this plugin in 2 ways:
1. by including a piece of code in your template/plugin
1. by using the shortcode in your posts/pages

## FAQ

= Can I disable the logs I'm not interested in =

No, not yet. This is in the works though.

= Which plugins/actions are included in the plugin =

### WP core
* user registration
* user change
* user delete

### Events manager
* event registration canceled
* event registration rejected
* event registration deleted
* event registration approved (in progress)
* more actions to follow...

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

Yes. You can do this by inserting a simple shortcode to the page you want to track. Insert the followoing shortcode into your post/page:
    
    [actionlogger]

This will trigger a default log entry with the following description:

    'user->display_name' has visited 'post title'

* user->display_name will be taken from the user who triggers the action
* post title will be taken from the post/page where the shortcode is inserted.

Next to that 2 other values are stored:
1. action
2. generator

The default action is `page_visit`, since the message is generated from within the shortcode and is mostly used on pages.

The default value for generator is `shortcode on {page title}`. 

You can override the default values with 3 variables.

- message
- action
- generator

There's only 1 exception. You can't use a " (double quotes) in your messages, because a shortcode variable is contained within double quotes.

This is defined as this:

    [actionlogger message="did something bad" action="register" generator="something"]
   

This will trigger a log entry with the following description:

    'user->display_name' did something bad

Register and something are stored in their own columns in the overview.

= Which plugins do you plan to include in the plugin ? =

* [S2Member](http://www.s2member.com/)
* [WooCommerce](https://woocommerce.com/)
* [WP e-Commerce](https://wpecommerce.org/)
* [BuddyPress](https://buddypress.org/)
* [Mailchimp for Wordpress](https://mc4wp.com/)


### Misc

If you're a plugin author and you would like to see your hooks logged in this plugin, please contact me @ http://berryplasman.com.  

### To Do
* [X] - Add shortcode to track thank you pages and other status pages
* [ ] - Add panel to select what to track
* [ ] - Add WP errors
* [ ] - Add option 'keep logs for X days'
* [ ] - Add auto-purge logs after x days
* [ ] - Add EM registration approve
* [ ] - Add EM hooks
* [ ] - Add S2Member hooks
* [ ] - Add WPSC hooks
* [ ] - Add BuddyPress hooks
* [ ] - Add WP4MC hooks

## Changelog

**1.0**

Initial release
