=== OpenHook ===
Contributors: BrazenlyGeek
Donate link: https://www.paypal.com/donate/?hosted_button_id=DXYAEEXMSK3DG
Tags:  "custom code", Astra, Flat, Headway, K2, Thesis, "Theme Hook Alliance", admin, shortcode, HTML, PHP, hooks, customization, phpinfo, ClassicPress
Requires at least: 4.1
Tested up to: 6.3.1
Stable tag: 4.3.1
License: GPLv3 or later

Add customizations to any hook in WordPress and any hook-enabled theme or plugin from within your admin panel! Wanna play hooky?

== Description ==

If you aren't altogether comfortable with editing PHP files to customize your site, OpenHook is for you! An increasing number of themes & plugins come equipped with a myriad of _hooks_ — points within their code which can receive user customizations, known as _actions_ — which can be customized from within your WordPress admin panel using OpenHook!

OpenHook brings the world of hooks & actions to the mainstream, providing an easy to use admin interface in which you can customize your site without limit, whether you're using HTML/CSS/JavaScript or PHP!

***Features***

* Customize the hooks present in your favorite themes!
* Define any hook you want within OpenHook and add an action to it! In addition to the themes which OpenHook supports explicitly, you can now use OpenHook to customize ANY hook (even something as arcane as `theme_hook_before_meta_987`) in ANY theme or plugin that has ANY hooks, from WordPress' bare minimum hooks to hooks that are dynamically created and are as infinitely diverse as your site can be!
* Predefined hooks for Astra, the world's most popular non-default WordPress theme!
* Also included are hooks for legacy themes Headway, Thesis 1.8.x, Flat, and K2.
* OpenBox, a PHP-friendly "box" for Thesis 2
* Quick access to the header & footer hooks of WordPress
* All hooks can be customized with text, HTML/CSS/JavaScript, PHP, or shortcodes
* All actions can be selectively disabled
* A variety of actions already present in Flat, Thesis 1.8.x, and WordPress can be selectively disabled
* Hook visualization allows you to see exactly where each hook is fired on the front-end of your site
* Shortcodes
    * [email], for masking email addresses from some spam robots
    * [global], which makes use of custom fields on a draft page in order to provide a library of reusable strings
    * [php], an admin-only shortcode for including PHP code within posts
    * [snap], an easy way to include (nearly) always up-to-date screenshots of websites within your posts
* Ability to disable _all_ shortcodes
* Display of `phpinfo()` in the admin panel
* Options management, including tools to upgrade from OpenHook 2 and to uninstall (delete) all OpenHook options
* Only users with the `edit_themes` permission may access OpenHook or its features. If enabled by such a user, other users may use `[email]` or `[global]` shortcodes in their entries as well.
* Compatible with ClassicPress!

== Changelog ==

= 4.3.1 =
* [added] Show notice if DISALLOW_FILE_EDIT = true
* [changed] Removed call to deprecated screen icon function
* [changed] Removed Dreamhost affiliate link, Facebook buttons, and Twitter link
* [changed] Updated some verbiage and sidebar links
* [changed] Tested up to WordPress 6.3.1
* [changed] Tested up to ClassicPress 1.7.0
* [changed] Removed closing slashes from self-closing HTML tags
* [fix] Server Info page works again
* [security] Prevent access to using [php] shortcode in ajax

= 4.3.0 =
* [added] Support for Astra, the world's most popular non-default WordPress theme
* [added] Introduced new Snap shortcode for including easy website screenshots within posts
* [changed] Minor cleanup of obsolete links and other items throughout
* [changed] Slight refinement of admin panel layout
* [changed] Some localization strings have changed

= 4.2.1 =
* [fixed] `load_plugin_textdomain()` changed to properly reference the `/languages` directory (thanks pm1932)
* [changed] Localization namespace is now `thesis-openhook` to conform to WordPress' guideline that this namespace match OpenHook's slug in the plugins repository (see https://make.wordpress.org/plugins/2015/09/01/plugin-translations-on-wordpress-org/)
* [changed] Author links updated to reflect current domain and Twitter account

= 4.2.0 =
* [added] Ability to define custom hooks and to then add actions to them, effectively opening OpenHook support to *all* hooks across *all* WordPress themes!
* [added] New themes: K2
* [added] Ability to specify priorities on actions
* [added] Access to the WordPress hook `comment_form`
* [added] Include FB like button in admin panel sidebar
* [fixed] Contact form link now works
* [fixed] Asterisks denoting that a hook has customizations added to it are now more accurate
* [fixed] OpenHook version and the Flat & THA hook options are now properly deleted when deleting OpenHook options
* [fixed] `OpenHook::upgrade()`'s "is an upgraded needed?" checks now actually work, preventing upgrade code from processing on every page load
* [fixed] More `WP_DEBUG` notices taken care of
* [changed] OpenHook admin panels are more responsive on smaller screens
* [changed] Only one theme's actions may be enabled at one time, saving processor power & preventing hook name conflicts
* [changed] Explicitly adhere to semantic versioning going forward
* [changed] OpenHook CSS is now minified
* [changed] File structure organized
* [changed] Simplified admin panel tabs to only highlight active action groups

= 4.1.0 =
* [added] Flat hooks
* [added] Theme Hook Alliance hooks
* [fixed] OpenBox is now compatible with Thesis 2.1.x
* [changed] Various text & links throughout OpenHook admin panel
* [changed] Optimized various bits of code, bringing it in line with WP coding standards
* [changed] Admin sidebar no longer appears on the server info page

= 4.0.1 =
* [fixed] Fixed broken class calls in options management functions

= 4.0.0 =
* [added] Now supporting Headway theme hooks!
* [added] Shortcodes manager introduced!
* [added] Users can now choose whether all hook panels are displayed or just one at a time
* [added] PHP shortcode - Arbitrary PHP code in your posts! (Admin users only.)
* [added] Email shortcode - Encodes email addresses for use in posts to thwart harvesters
* [added] Global shortcode - Take advantage of custom fields on a draft post to create a library of strings which may be used in any post
* [changed] Various code optimizations

= 3.4.0 =
* [added] OpenBox - a box added to Thesis 2's box management, allowing for arbitrary code in Thesis 2's skin editor
* [changed] OpenHook is now programmed as a class to allow its code to be self-contained. More code refinements will be coming
* [changed] Plugin is now named simply "OpenHook." Viva la simplicity!

= 3.3.1 =
* [fixed] thesis_hook_after_post_box restored. Hat tip: Doug Foster

= 3.3.0 =
* [added] Hook visualization (Based upon http://headwaythemes.com/headway-hooks-visualized/)
* [changed] Improved handling of the options management functions (upgrade/delete options)
* [changed] When action groups are disabled, the hook pages now include a nag stating as much
* [fixed] Warnings about empty arrays when activating action groups

= 3.2.1 =
* [fixed] Bumped to make the repository update

= 3.2.0 =
* [added] Hooks with customizations are marked with asterisks in the dropdown select box
* [changed] Add link to phpinfo() under Tools menu
* [changed] Verbiage for unhooking updated
* [fixed] Many undefined variable errors
* [fixed] Slashes are now properly stripped when upgrading from 2.x.x
* [fixed] Default Thesis 404 content can now be properly removed

= 3.1.0 =
* [fixed] Rare issue where the general settings panel doesn't fully appear

= 3.0.0 =
* [changed] Total rewrite of the plugin
* [added] phpinfo() panel
* [added] per-hook disabling of custom actions
* [added] option to process shortcodes on custom actions
* [added] ability to choose which actions to process (WordPress' or Thesis' or both's)
* [added] ability to remove all OpenHook options
* [removed] several deprecated options

= 2.3.2 =
* [fixed] Remember the typos fixed in 2.3.1? There were others I should have caught then. I'm a terrible proofreader, but thanks, Dean (http://www.doublejoggingstrollershq.com/), for catching them!

= 2.3.1 =
* [fixed] Fixed two stupid typos that killed everything that was right with the world. Well, they broke the plugin anyway. Thanks, Jim (http://doggybytes.ca/), for reporting so quickly!

= 2.3.0 =
* [added] Thesis 1.7's four new hooks are now included.
* [removed] OpenHook's file editing panels have been removed -- Thesis has these by default now.
* [changed] Readme.txt updated.

= 2.2.5 =
* [fixed] Reverted change introduced in 2.2.3 regarding stripping of slashes

= 2.2.4 =
* [fixed] Fixed a syntax error, reported by multiple users.

= 2.2.3 =
* [fixed] Fixed a bug which prevented the After Teasers Box hook from saving properly. Thanks, Michael Curving.
* [fixed] Fixed an issue where the file editors would strip slashes unnecessarily. Thanks, Kristarella.

== Installation ==

After you have downloaded the file and extracted the `thesis-openhook/` directory from the archive...

1. Upload the entire `thesis-openhook/` directory to the `wp-content/plugins/` directory.
1. Activate the plugin through the Plugins menu in WordPress.
1. Visit Settings -> OpenHook and customize to your heart's content!

Alternatively, you can use WordPress' automatic plugin installer. Go ahead, it's easier!

== Upgrade Notice ==

= 4.2.0 =
If you are using OpenHook 2 or 3, you are strongly encouraged to update to take advantage of all of OpenHook's new features!

= 4.1.0 =
This long-overdue update adds support for the Flat theme as well as any theme supporting Theme Hook Alliance hooks while also fixing a few bugs and cleaning things up a little! For Thesis 2 users, OpenBox should now function properly!

= 4.0.0 =
Now providing support for the theme Headway as well as adding several custom shortcodes! Numerous more minor changes have been made, so dive in and check 'em out!

= 3.0.0 =
OpenHook 3 provides a leaner, cleaner interface for managing your customizations. If you are upgrading from 2.x.x, be sure to FIRST upgrade your options via Settings -> OpenHook; otherwise, you may find your customizations have vanished!

== Screenshots ==

1. An example of one of the many hooks to which OpenHook provides access. This is one of WordPress'.
2. How the Headway theme page of OpenHook looks. There is a toggle on the General page to choose to view all pages at once.
3. A snapshot of the shortcodes page.
4. How OpenBox appears on Thesis' box management screen.
5. An example of OpenBox in action in Thesis' skin editor.

== Frequently Asked Questions ==

= How does OpenBox work in Thesis 2.1.x? =

First, ensure you have enabled OpenBox in the Thesis 2.1.x "boxes" screen (OpenBox should be checkmarked and you'll need to press the save button).

Second, visit the Thesis skin editor. Add instances of OpenBox from the boxes dropdown menu, and drag them into your skin template wherever you would like them to be. I recommend opening each instance of OpenBox (via the gear icon) and giving it a unique name so that they are not all "OpenBox."

Finally, once you have saved your template, return to the regular admin panel and visit Thesis' skin content page. On this page,  you'll be shown a list of all of the custom boxes, including OpenBoxes, you've added to your template, at which point you can edit them to your liking.

Yes, this is convoluted, and for that I apologize; fortunately, it isn't my fault.

= I upgraded from OpenHook 2.x.x; where did all of my customizations go? =

OpenHook 3 and newer does not automatically import pre-existing customizations. You will need to visit the OpenHook settings page accessible at Settings -> OpenHook; once there, you can use the "Upgrade from OpenHook 2" button to import your pre-existing customizations to the new schema. You'll then need to activate the Thesis & WordPress action groups as needed from the same settings page.

= I don't use one of the supported themes; can I still use this plugin? =

Of course! However, what you are able to do with OpenHook will be limited. Still, you will have access to WordPress' few public-facing hooks, the new shortcodes, and the `phpinfo()` panel.

= Where can I get the supported themes? =

* Astra can be downloaded at [the WordPress theme repository](https://wordpress.org/themes/astra/).
* Headway can be purchased at [Headway Themes](http://headwaythemes.com/).
* Themes which support the Theme Hook Alliance hooks are [listed at GitHub](https://github.com/zamoose/themehookalliance).
* Flat is no longer available.
* K2 is no longer available.
* Thesis is no longer available.

= What about the code in my theme's custom functions file? =

If you have already modified your theme's installation via `functions.php`, `custom_functions.php`, or some other similar file, you are welcome to port those changes into OpenHook to manage all of your changes in one place.

Note that your blog will use both your theme's custom functions and OpenHook, so the two are complementary.

Likewise, your theme's custom functions file will be processed *after* OpenHook, so you can override OpenHook via the custom functions file, if you need to.

= Why can only certain users on my site access OpenHook? =

Due to the powerful nature of OpenHook, access is restricted only to the highest level of users (i.e., those with the `edit_themes` permission).

= What are the security risks involved in using OpenHook? =

OpenHook is a powerful tool for customizing your site; however, with great power comes, ahem, great responsibility. You are able to use any (ANY!) PHP code within your OpenHook-managed customizations; any other administrators on your site with access to OpenHook can do the same. The freedom allowed means that database credentials could be displayed, your database could be deleted, or your entire site could be defaced. Therefore, while OpenHook certainly can be dangerous, if you have only trusted administrators on your site, you have nothing to worry about.

= Why is K2 included? =

K2 is a pretty old WordPress theme -- an abandoned one, for all I can tell. However, it was the first theme that ever had a "hooks" plugin made for it -- K2 Hook Up, which the first version of OpenHook was based upon. The K2 theme is included to honor its place in WordPress history. OpenHook now allows access to all of its hooks, including one which K2 Hook Up didn't! I would also love to see a theme developer pick up K2 and update it for today's users. It's a great theme that shouldn't fade away completely.