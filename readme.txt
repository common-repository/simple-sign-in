=== Simple Sign In ===
Contributors: roamzero
Donate link: http://peopletab.com
Tags: login, authentication, 
Requires at least: 2.3
Tested up to: 2.3
Stable tag: 1.0

A login alternative to OpenID, designed for blogs and anything else you can think of!

== Description ==

SSI is designed to be a simple alternative to authentication systems such as OpenID. 
The plugin is designed to work on most Wordpress installations, and it allows your 
Wordpress installion to be both a provider(allows you to sign in to ssi sites) and a 
consumer(allows others to sign in to yours). 
It also allows you to manage your login information. In addition to that, those that post comments on your site
after signing in also have their avatars displayed and the comment form is autofilled for them.
SSI is designed using [RESTTA](http://peopletab.com/restta.html) , meaning that is a protocol
open to any application that implements it.

== Installation ==

1. Upload `ssi` directory to the `/wp-content/plugins/` directory
2. Activate the plugin and the widget plugin through the 'Plugins' menu in WordPress
3. Edit the restta.xml file in the ssi directory if your wp installation
is in a directory (e.g. mysite.com/wp/). Edit the pathPrefix element to surround the path (e.g. <pathPrefix>/wp</pathPrefix>)
4. Move the restta.xml file to the document root. If there already exists a restta.xml file, do not delete it. Open the existing file and
merge the appClass tag (along with its contents) in the original file with the other appClass tags in the already-existing file.
5. Add the login widget to the sidebar through the widget panel. People wont be able to login unless it's there!

== Frequently Asked Questions ==

= Where do I send my inquiries? =

Please contact me at roamzero[at]gmail.com.


== Screenshots ==

1. The login widget and its tooltip.
2. Auto-filled form and comment posted with avatar displayed (avatars are 20x20).
3. Login manager that you can use to setup multiple logins.



