# Action Logger

Welcome to the Action Logger plugin for [Wordpress](http://wordpress.org). 

Do you run a website with more than 1 user and do you want to keep tabs on what is happening within your WordPress wesite ? Then this plugin might be something for you.

## Description 

This plugin gives you the option to log various (user) actions on your website. Default there are a few actions which can be tracked from within WordPress' core and some from plugins I like/use.

### Loggable actions

#### WP core
* user registration
* user change
* user delete
* post/page visit (through a shortcode)
* post published (every post type)
* post changed (every post type)
* post deleted (every post type)
* post set to 'pending review' (every post type)

## Impact

The overall impact is minimal.

* Upon activation a new database table named `{your table prefix}_action_logs` is created.
* Upon activation a handful of default settings (true/false) are stored in the database which was just created.
* Every action is logged real-time, which is 1 row being stored in the database.
* Upon plugin deactivation all settings are dropped to keep the database clean (except the preserve data option).
* Upon plugin deletion (through WP admin) the database table `{your table prefix}_action_logs` is dropped (unless preserve data is selected in the options panel).
* Upon plugin deletion (through WP admin) the `preserve data` option is deleted.

## Installation

Uploading a zip file
1. Go to your WordPress Admin plugins page.
1. Click on the "Upload" link near the top of the page and browse for the Action Logger zip file
1. Activate the plugin by clicking `Activate` after installation.

Uploading the extracted zip by FTP
1. Extract the Action Logger zip file.
1. Upload them by FTP to your plugins directory (mostly wp-content/plugins).
1. Go to your WordPress Admin plugins page.
1. Activate the plugin by clicking `Activate` after installation.


## Usage

You can use this plugin in 2 ways:
1. by including a piece of code in your template/plugin (see FAQ below)
1. by using the shortcode in your posts/pages

The first option is where the strength of this plugin lies. It offers a lot more logging possibilities, but requires php knowledge.
Both options are explained in the F.A.Q.

## FAQ

= Can I disable the logs I'm not interested in =

Yes. This can be done on the settings page.

= Which plugins/actions are default included in the plugin =

### WP core
* user registration
* user change
* user delete
* post published
* post changed
* post deleted
* post/page visitor visit (through shortcode)
* post/page registered user visit (through shortcode)

= Can I use this plugin to track visit to posts/pages like registration confirmations or so ? =

Yes. You can do this by inserting a simple shortcode to the page you want to track. Insert the following shortcode at the end of your post/page:
    
    [actionlogger]

This will create a default log entry with the following description:

If a user is logged in, it will create the following log entry:

    #user# visited #title#

If it's a visitor (not logged in), it will create the following log entry:

    A visitor visited #title#

* \#user# generates `display_name` from the user who does the action
* \#title# will be taken from the post/page where the shortcode is inserted

The following values are stored:
1. action time - when was the action done (unix timestamp)
2. action user - who did the action (integer)
3. action - what was the action (string)
4. action generator - which 'piece of code' generated the log message (string)
5. action description - generated message of what happened (string)
6. post_id - post id where the action occured, if appliccable (integer)
 
The default action is `{post_type}_visit` , where `post_type` will be replaced with the post type involved.

The default value for generator is `Shortcode`. 

You can override the default message with a variable. 

This is defined as this:

    [actionlogger message="did something on the website"]
   

This will trigger a log entry with the following description:

    #user# did something on the website

= Can I log my own custom action actions ? =

Of course, that's the whole reason I wrote this plugin; 'to be able to log custom actions'. To use the logger, you need to add a piece of code on the place where you want the tracking to occur. This can be in a plugin or a theme.

    ActionLogger::al_log_user_action();

To make sure your site won't break if you deactivate the plugin, wrap it in a `class_exists()` as follows:     

    if ( class_exists( 'ActionLogger' ) ) {
        ActionLogger::al_log_user_action();
    }

The function can contain 4 variables which are default all set to false. Use them in this order:

* $action
* $action_generator
* $action description
* post_id

This is defined as:

    if ( class_exists( 'ActionLogger' ) ) {
        ActionLogger::al_log_user_action( $action, $generator, $message, $post_id );
    }

= Can I export my logs to CSV ? =

Yes, check the Misc page (in the plugin).

= Why are some variables not stored as a value but as a variable ? =

The values are 'generated' upon display/export. This way you always see the current name/title of a user/post, which makes searching easier. For example if a user changes his name 5 times, it's harder to track who it was. The same goes for a post title. Now the current state/value is always shown.

= Which plugins do you plan to include in the plugin ? =

* [Events Manager](http://wp-events-plugin.com/)
* [S2Member](http://www.s2member.com/)
* [WooCommerce](https://woocommerce.com/)
* [WP e-Commerce](https://wpecommerce.org/)
* [bbPress](https://bbpress.org/)


### Misc

If you're a plugin author and you would like to see your hooks logged in this plugin, please contact me @ http://berryplasman.com.  
