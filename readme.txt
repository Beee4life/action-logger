=== Action Logger ===
Contributors: Beee
Tags: log, logging, hooks, filters
License URI: #Requires at least: 3.0
Tested up to: 4.8.1
Requires PHP: 5.4
Donate link: http://
Stable tag: trunk
License: GPL v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin gives you the option to log various actions on your website. Default there are a few actions which can be tracked from within WordPress\' core and some from plugins I like/use.

== Installation ==

1. Install the plugin through WordPress\' admin panel at Plugins > Add New
2. Activate the plugin through the \'Plugins\' menu in WordPress

== Impact ==

* Upon activation a new database table named `{your table prefix}_action_logs` is created.
* Every action is logged real-time, which is 1 row being stored in the database.
* Upon plugin deactivation all settings are dropped to keep the database clean (except the preserve data option).
* Upon plugin deletion (through WP admin) the database table `{your table prefix}_action_logs` is dropped (unless preserve data is selected in the options panel).

== Frequently Asked Questions ==

= Can I disable the logs I\'m not interested in =

Yes. This can be done on the settings page.

= Which plugins/actions are included in the plugin =

### WP core
* user registration
* user change
* user delete
* post/page visitor visit (through shortcode)
* post/page registered user visit (through shortcode)

### Events manager
* event registration canceled
* event registration rejected
* event registration deleted
* event registration approved (in progress)
* more actions to follow...

= Can I use this plugin to track visit to special pages like registration confirmations or so ? =

Yes. You can do this by inserting a simple shortcode to the page you want to track. Insert the following shortcode at the end of your post/page:
[actionlogger]

If a user is logged in, it will trigger the following log entry:
{user->display_name} has visited {post title}

If it\'s a visitor (not logged in), it will trigger the following log entry:
A visitor has visited {post title}

* user->display_name will be taken from the user who triggers the action
* post title will be taken from the post/page where the shortcode is inserted.

Next to that 2 other values are stored:
1. action
2. generator

The default action is `{post_type}_visit`.

The default value for generator is `Shortcode`.

You can override the default message with a variable.

This is defined as this:
[actionlogger message=\"did something on the website\"]

This will trigger a log entry with the following description:
{user->display_name} did something on the website

= Can I log my own custom action actions ? =

Of course, that\'s the whole reason I wrote this plugin; \'to be able to log custom actions\'. To use the logger, you need to add a piece of code on the place where you want the tracking to occur. This can be in a plugin or a theme.
ActionLogger::al_log_user_action();

To make sure your site won\'t break if you deactivate the plugin, wrap it in a `class_exists()` as follows:
if ( class_exists( \'ActionLogger\' ) ) {
    ActionLogger::al_log_user_action();
}

The function can contain 3 variables which are default all set to false. Use them in this order:

* $action
* $action_generator
* $action description

This is defined as:
if ( class_exists( \'ActionLogger\' ) ) {
    ActionLogger::al_log_user_action( $action, $generator, $message );
}

= Can I export my logs to CSV ? =

Yes, check the Misc page.

= Which plugins do you plan to include in the plugin ? =

* S2Member
* WooCommerce
* WP e-Commerce
* BuddyPress
* Mailchimp for Wordpress
