=== Quick Paypal Payments ===

Contributors: 
Tags: paypal payment form
Requires at least: 2.7
Tested up to: 4.2
Stable tag: trunk

Zero to PayPal with just one shortcode. Jam packed with features and options with easy to use custom settings.

== Description ==

Taking PayPal payments just got easier, one shortcode to collect any amount from anywhere on your site

= Features =

*	Accepts all PayPal approved currencies
*	Fixed or variable payment amounts
*	Easy to use range of shortcode options
*	Fully editable
*	Loads of styling options
*	Multi-language
*	Add custom forms anywhere on your site
*	Downloadable payment records
*   Instant Payment Notifications
*   Fully editable autoresponder

= Developers plugin page =

[quick paypal payments plugin](http://quick-plugins.com/quick-paypal-payments/).

== Screenshots ==
1. This is the main admin screen.
2. An example form.
3. The payment record

More [example forms](http://quick-plugins.com/quick-paypal-payments/paypal-examples/).

== Installation ==

1.	Login to your wordpress dashboard.
2.	Go to 'Plugins', 'Add New' then search for 'Quick Paypal Payments'.
3.	Follow the on screen instructions.
4.	Activate the plugin.
5.	Go to the plugin 'Settings' page to add your paypal email address and currency
6.	Edit any of the form settings if you wish.
7.	Use the shortcode `[qpp]` in your posts or page or even your sidebar.
8.	To use the form in your theme files use the code `<?php echo do_shortcode('[qpp]'); ?>`.

== Frequently Asked Questions ==

= How do I change the labels and captions? =
Go to your plugin list and scroll down until you see 'Quick Paypal Payments' and click on 'Settings'.

= What's the shortcode? =
[qpp]

= How do I change the styles and colours? =
Use the plugin settings style page.

= Can I have more than one payment form on a page? =
Yes. But they have to have different names. Create the forms on the setup page.

= Where can I see all the payments? =
At the bottom of the dashboard is a link called 'Payments'.

= It's all gone wrong! =
If it all goes wrong, just reinstall the plugin and start again. If you need help then [contact me](http://quick-plugins.com/quick-paypal-payments/).

== Changelog ==

= 3.17 =
*   Autoresponder Shortcodes

= 3.16 =
*   All new Autoresponder
*   Minor bug fixes
*   Moved scripts to footer
*   Option to combine shipping and processing with total

= 3.15 =
*   Improved error checking
*   Required field selectors
*   Border styles for required and normal fields
*   Confirmation email after payment (IPN only)
*   Clone settings option
*   Color picker bug fix

= 3.14 =
*   Instant Payment Notifications
*   Option to display inline radio buttons
*   Updated Pre-population settings
*   Notification emails
*   Improvements to totals field
*   Styling bug fixes
*   Header size selection

= 3.13 =
*   Dropdown option on selectable fields
*   Bug fix for the $1 problem
*   Improved styling on submit button images

= 3.12.2 =
*   Bug fix number_format calculation

= 3.12.1 =
*   Bug fix for range slider
*   Bug fix for the postage and handling fee calculation

= 3.12 =
*   Added option for form Reset
*   Added message for invalid coupon code
*   New range slider field
*   Bug fixes for the Payment Lists
*   Improved total calculation, it now works with radio fields.

= 3.11 =
*	Currency option to selected decimal points or comma
*   When using multiple amounts you can now have radio or dropdown selectors
*   Bug fix to the 'item number' field.

= 3.10 =
*	Umpteen bug fixes in the payment report/CSV download
*   Added option to send payment lists as an email

= 3.9 =
*	Bug fix for recurring payments
*   New field to add extra information to the form
*   Live totals option
*   Option to collect personal information
*   Product options now display as a new line on the PayPal page
*   Shipping and handling now display properly on the PayPal page

= 3.8 =
*	Added field for recurring payment
*   Added code to allow multiple products/prices
*   More shortcode options

= 3.7 =
*	Better error reporting
*	Option to link to Terms and Conditions
*	Set maximum quantity
*	CSS fix to overide theme settings for line heights

= 3.6.4 =
*	Add as many coupons as you like
*	Duplicate coupons across multiple forms
*	Set reference and amount without using shortcodes
*	Pass form variables using queries

= 3.6.3 =
*	Bug fix, amounts over 1000 now work properly
*	Added URL queries for reference, amount and coupon

= 3.6.2 =
*	Improved coupon support
*	Option to change PayPal locale
*	Payment records now download properly

= 3.6 =
*	You can now add coupon codes to the form
*	Updated payments reports

= 3.5.3 =
*	Fixed error when using % postage or processing fees
*	Fixed bug in image image uploader

= 3.5.1 =
*	Bug fix to clear illegal offset warning

= 3.5 =
*	Added captcha option
*   More forms fields
*   Drag and drop ordering
*   Loads of new styling options

= 3.4 =
*	Options to add postage and admin charges
*	Option to add the item number to the order.
*	More Wordpress 3.8 tweaks

= 3.3 =
*	Updated to support Wordpress 3.8

= 3.2 =
*	Added the option to display a PayPal logo on the form

= 3.1 =
*	Closed an XSS security hole
*	Fixed the incorrect shortcode description

= 3.0 =
*	Multiple form support
*	Dashboard link to display and download payment records
*	Add options to your forms using shortcodes

= 2.1.1 =
*	Bug fix: Custom CSS option wasn't saving properly

= 2.1 =
*	Added styling options for Submit button
*	Improved form validation and error checking

= 2.0.4 =
*	Bug fix: fixed duplicate function name error

= 2.0.2 =
*	Bug fix: error in sidebar widget

= 2.0.1 =
*	Bug fix: selected currency asn't being passed to paypal

= 2.0 =
*	Major upgrade to the admin pages
*	Added loads of styling options
*	Custom error messages and improved validation
*	Faster paypal processing

= 1.5 =
*	Error checking went wonky. It's fixed now

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