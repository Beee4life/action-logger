# Action Logger

Welcome to the Action Logger plugin for [Wordpress](http://wordpress.org). I built this initially for the IDF (International Downhill Federation), to keep tabs on actions from other users, but along the way I though many more users would find this interesting to use so that's why it's here now :)

## Description

## Installation

This plugin gives you the option to log various actions on your website. Default there are a few actions which can be tracked from within WordPress' core and some from plugins I like/use.

1. Install the plugin through WordPress' admin panel at Plugins > Add New
2. Activate the plugin through the 'Plugins' menu in WordPress

Once activated you will find a "Settings" link next to the deactivate button.

## Usage

You can use this plugin in 2 ways:
1. by including a piece of code in your template/plugin (see FAQ below)
1. by using the shortcode in your posts/pages

The first option offers a lot more logging possibilities, but requires php knowledge.
How to do either is explained in the FAQ.

## FAQ

= Can I disable the logs I'm not interested in =

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

If it's a visitor (not logged in), it will trigger the following log entry:
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
[actionlogger message="did something on the website"]


This will trigger a log entry with the following description:
{user->display_name} did something on the website

= Can I log my own custom action actions ? =

Of course, that's the whole reason I wrote this plugin; 'to be able to log custom actions'. To use the logger, you need to add a piece of code on the place where you want the tracking to occur. This can be in a plugin or a theme.
ActionLogger::al_log_user_action();

To make sure your site won't break if you deactivate the plugin, wrap it in a `class_exists()` as follows:
if ( class_exists( 'ActionLogger' ) ) {
    ActionLogger::al_log_user_action();
}

The function can contain 3 variables which are default all set to false. Use them in this order:

* $action
* $action_generator
* $action description

This is defined as:
if ( class_exists( 'ActionLogger' ) ) {
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


### Misc

If you're a plugin author and you would like to see your hooks logged in this plugin, please contact me @ http://berryplasman.com.

### To Do
* [X] - Add shortcode to track thank you pages and other status pages
* [X] - Add panel to select what to track
* [X] - Add WP errors
* [X] - Add option to select which user roles can see the logger
* [ ] - Add pagination in overview
* [ ] - Add filters in overview to filter certain actions/generators
* [ ] - Add auto-purge logs after x days
* [ ] - Add option to 'keep logs for X days'
* [ ] - Add EM registration approve
* [ ] - Add more EM hooks
* [ ] - Add S2Member hooks
* [ ] - Add WooCommerce hooks
* [ ] - Add BuddyPress hooks
* [ ] - Add WP4MC hooks

## Changelog

** 0.1 beta **
Initial release