=== Quick Paypal Payments ===

Contributors: 
Tags: contact form
Requires at least: 2.7
Tested up to: 3.4.2
Stable tag: trunk

Zero to PayPal with just one shortcode

== Description ==

Taking PayPal payments just got easier, one shortcode to collect any amount from anywhere on your site

= Features =

*	Accepts any currency
*	Fixed or variable payment amounts
*	Easy to use shortcode
*	Multi-language

= Developers plugin page =

[quick paypal payments plugin](http://quick-plugins.com/quick-paypal-payments/).

== Screenshots ==
1. This is an example of a simple form usinf the shortcode [qpp].
2. Form using shortcode [qpp id='room deposit' amount='£30'].
3. Form using shortcode [qpp amount='$40'].
4. Form using shortcode [qpp id='cleaning'].

== Installation ==

1.	Download the plugin.
2.	Login to your wordpress dashboard.
3.	Go to 'Plugins', 'Add New' then 'Upload'.
4.	Browse to the downloaded plugin then then 'Install Now'.
5.	Activate the plugin.
6.	Go to the plugin 'Settings' page to add you paypal email address and currency
7.	Edit any of the form settings if you wish.
8.	Use the shortcode `[qpp]` in you posts or page or even your sidebar.
10.	To use the form in your theme files use the code `<?php echo do_shortcode('[qpp]'); ?>`.

== Frequently Asked Questions ==

= How do I change the labels and captions? =
Go to your plugin list and scroll down until you see 'Quick Paypal Payments' and click on 'Settings'.

= What's the shortcode? =
[qpp]

= How do I change the colours? =
Edit the 'quick-paypal-payment-styles.css' or use the custom styles option.

= Can I have more than one payment form on a page? =
No. When you submit the form a function processes the payment info. If you have more than one form it won't know which one to process. I am working on this options but it's proving to be a bit dificult.

= It's all gone wrong! =
If it all goes wrong, just reinstall the plugin and start again. If you need help then [contact me](http://aerin.co.uk/contact-me/).


== Changelog ==

= 1.4 =
*	Now with width options

= 1.3 =
*	Simplified the processing code

= 1.2 =
*	Added custom styles option

= 1.1 =
*	Added choice of plugin or theme styles

= 1.0 =
*	Initial Issue