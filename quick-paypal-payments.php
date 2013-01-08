<?php
/*
Plugin Name: Quick Paypal Payments
Plugin URI: http://quick-plugins.com/quick-paypal-payments/
Description: Accept any amount or payment ID before submitting to paypal 
Version: 1.5
Author: fisicx
Author URI: http://quick-plugins.com/
*/

add_shortcode('qpp', 'qpp_payment');
add_action('admin_menu', 'qpp_page_init');
add_action( 'admin_notices', 'qpp_admin_notice' );
add_action( 'widgets_init', create_function('', 'return register_widget("qpp_payment_widget");') );
add_action('wp_head', 'qpp_use_custom_css');
add_filter( 'plugin_action_links', 'qpp_plugin_action_links', 10, 2 );

/* register_deactivation_hook( __FILE__, 'qpp_delete_options' ); */
register_uninstall_hook(__FILE__, 'qpp_delete_options');

$scripturl = plugins_url('quick-paypal-payments-javascript.js', __FILE__);
	wp_register_script('qpp_script', $scripturl);
	wp_enqueue_script( 'qpp_script');

$qpp = qpp_get_stored_options();
if ( $qpp['styles'] == 'plugin'  || $qpp['styles'] == 'custom') {
	$styleurl = plugins_url('quick-paypal-payments-style.css', __FILE__);
	wp_register_style('qpp_style', $styleurl);
	wp_enqueue_style( 'qpp_style');
	}

if (is_admin() ) {
$adminurl = plugins_url('quick-paypal-payments-admin.css', __FILE__);
	wp_register_style('qpp_admin', $adminurl);
	wp_enqueue_style( 'qpp_admin');
	}

function qpp_page_init() {
	add_options_page('Quick Paypal', 'Quick Paypal', 'manage_options', __FILE__, 'qpp_settings');
	}

function qpp_plugin_action_links($links, $file ) {
	if ( $file == plugin_basename( __FILE__ ) )
		{
		$qpp_links = '<a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/quick-paypal-payments.php">'.__('Settings').'</a>';
		array_unshift( $links, $qpp_links );
		}
	return $links;
	}

function qpp_delete_options() {
	delete_option('qpp_options');
	}

function qpp_admin_notice( $message) {
	if (!empty( $message)) echo '<div class="updated"><p>'.$message.'</p></div>';
	}

function qpp_settings()
	{
	$qpp = qpp_get_stored_options();
	if( isset( $_POST['qpp_submit'])) {
		$qpp['email'] = $_POST['qpp_email'];
		$qpp['currency'] = $_POST['qpp_currency'];
		$qpp['title'] = stripslashes( $_POST['qpp_title']);
		$qpp['blurb'] = stripslashes( $_POST['qpp_blurb']);
		$qpp['inputreference'] = stripslashes( $_POST['qpp_inputreference']);
		$qpp['inputamount'] = stripslashes( $_POST['qpp_inputamount']);
		$qpp['submitcaption'] = stripslashes( $_POST['qpp_submitcaption']);
		$qpp['cancelurl'] = stripslashes( $_POST['qpp_cancelurl']);
		$qpp['thanksurl'] = stripslashes( $_POST['qpp_thanksurl']);
		$qpp['target'] = $_POST['qpp_target'];
		$qpp['styles'] = $_POST['qpp_styles'];
		$qpp['custom'] = $_POST['qpp_custom'];
		$qpp['width'] = $_POST['width'];
		$qpp['widthtype'] = $_POST['widthtype'];
		update_option( 'qpp_options', $qpp);
		qpp_admin_notice("The plugin settings have been updated.");
		}
	$$qpp['target'] = 'checked';
	$$qpp['styles'] = 'checked';
	$$qpp['widthtype'] = 'checked';
	$content = '
	<div id="qpp-options"> 
	<div id="qpp-style">
	<h2>Paypal Payments </h2>
	<p>Use the <a href="' . get_admin_url() . '/wp-admin/widgets.php">widget manager</a> to add a payment form to a sidebar or use the shortcode [qpp] in your posts and pages.</p>
	<p>The sortcode supports two parameters: <em>id</em> and <em>amount</em>. There are shortcode examples on the right.</p>
	<p>You can only have one payment form per page. I&#146;ve tried to get multiple forms to work but failed miserably. Hopefully in the future I&#146;ll get it to work properly.</p>
	<form action="" method="POST">
	<p><span style="color:red; font-weight: bold;">Important!</span> Enter your PAYPAL email address and currency code below and save the changes.</p>
	<h3>Email address</h3>
	<input type="text" style="width:90%" label="Email" name="qpp_email" value="' . $qpp['email'] . '" /></p>
	<h3>Currency code</h3>
	<input type="text" style="width:6em" label="Currency" name="qpp_currency" value="' . $qpp['currency'] . '" /> (For example: GBP, USD, EUR)</p>
	<p>Currency codes are given <a href="http://en.wikipedia.org/wiki/ISO_4217" target="blank">here</a>.</p>
	<input type="submit" name="qpp_submit" class="button-primary" style="color: #FFF" value="Save Changes" />
	<h2>Form Title and Introductory Blurb</h2>
	<p>Paypal form heading (optional)</p>
	<input type="text" style="width:90%" name="qpp_title" value="' . $qpp['title'] . '" />
	<p>This is the blurb that will appear below the heading and above the form (optional):</p>
	<input type="text" style="width:90%" name="qpp_blurb" value="' . $qpp['blurb'] . '" />
	<h2>Payment labels</h2>
	<p>Label fo the Reference/ID/Number:</p>
	<input type="text" style="width:90%" name="qpp_inputreference" value="' . $qpp['inputreference'] . '" />
	<p>Label for the amount field:</p>
	<input type="text" style="width:90%" name="qpp_inputamount" value="' . $qpp['inputamount'] . '" />
	<h2>Submit button caption</h2>
	<input type="text" style="width:90%" name="qpp_submitcaption" value="' . $qpp['submitcaption'] . '" />
	<h2>Cancel and Thank you pages</h2>
	<p>If you leave these blank paypal will return the user to the current page.</p>
	<h3>URL of cancellation page</h3>
	<input type="text" style="width:90%" name="qpp_cancelurl" value="' . $qpp['cancelurl'] . '" />
	<h3>URL of thank you page</h3>
	<input type="text" style="width:90%" name="qpp_thanksurl" value="' . $qpp['thanksurl'] . '" />
	<h2>Styles</h2>
	<h3>Form Width</h3>
	<p>
	<input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="widthtype" value="percent" ' . $percent . ' /> 100% (fill the available space)<br />
	<input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="widthtype" value="pixel" ' . $pixel . ' /> Pixel (fixed)</p>
	<p>Enter the width of the form in pixels. Just enter the value, no need to add &#146;px&#146;. The current width is as you see it here.</p>
	<p><input type="text" style="width:4em" label="width" name="width" value="' . $qpp['width'] . '" /> px</p>
	<h3>Style source</h3>
	<p><input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="qpp_styles" value="plugin" ' . $plugin . ' /> Use plugin styles<br>
	<input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="qpp_styles" value="theme" ' . $theme . ' /> Use theme styles<br>
	<input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="qpp_styles" value="custom" ' . $custom . ' /> Use custom styles (add to text editor below)</p>
	<p><textarea style="width:100%; height: 200px" name="qpp_custom">' . $qpp['custom'] . '</textarea></p>
	</p>
	<h2>Paypal Link</h2>
	<p><input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="qpp_target" value="newpage" ' . $newpage . ' /> Open link in new page/tab<br>
	<input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="qpp_target" value="current" ' . $current . ' /> Open in existing page</p>
	<p><input type="submit" name="qpp_submit" class="button-primary" style="color: #FFF;" value="Save Changes" /></p>
	</form>
	</div>
	</div>
	<div id="qpp-options"> 
	<div id="qpp-style">
	<h2>Shortcode Examples</h2>
	<p><strong>[qpp]</strong></p>
	<p><img src="' . plugins_url('/screenshot-1.gif' , __FILE__ ) . '"></p>
	<p><strong>[qpp id=\'room deposit\' amount=\'&pound;30\']</strong></p>
	<p><img src="' . plugins_url('/screenshot-2.gif' , __FILE__ ) . '"></p>
	<p><strong>[qpp amount=\'$40\']</strong></p>
	<p><img src="' . plugins_url('/screenshot-3.gif' , __FILE__ ) . '"></p>
	<p><strong>[qpp id=\'cleaning\']</strong></p>
	<p><img src="' . plugins_url('/screenshot-4.gif' , __FILE__ ) . '"></p>
	</div></div>';
	echo $content;
	}

class qpp_payment_widget extends WP_Widget
	{
	function qpp_payment_widget()
		{
		$widget_ops = array('classname' => 'qpp_payment_widget', 'description' => 'Add a payment form to your sidebar');
		$this->WP_Widget('qpp_payment_widget', 'Payment Form', $widget_ops);
		}
	function form($instance)
		{
		echo '<p>Use the the plugin <a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/quick-paypal-payments.php">Settings</a> page to configure the payment form.</p>';
		}
	function update($new_instance, $old_instance)
		{
		$instance = $old_instance;
		$instance['paypal'] = $new_instance['paypal'];
		return $instance;
		}
 	function widget($args, $instance)
		{
 	   	extract($args, EXTR_SKIP);
		echo qpp_payment('','');
		}
	}

function qpp_payment($atts)
	{
	ob_start();
	extract(shortcode_atts(array( 'amount' => '' , 'id' => '' ), $atts));
	$qpp = qpp_get_stored_options();
	$payment_id = $qpp['inputreference'];
	$payment_amount = $qpp['inputamount'];
	if ($qpp['widthtype'] == 'pixel') {
		$width = ' style="width: ' . preg_replace("/[^0-9]/", "", $qpp['width']) . 'px"';
		}
	else {
		$width = ' style="width: 100%;"';
		$submit = ' style="width: calc(100% - 14px);"';
		}
	if(isset( $_POST['PaymentSubmit']))
		{
		$errors = '';
		if (empty ($id)) $payment_id = $_POST['payment_id'];
		else $payment_id = $id;
		if (empty ($amount)) $payment_amount = $_POST['payment_amount'];
		else $payment_amount = $amount;
		$check = preg_replace ( '/[^.,0-9]/', '', $payment_amount );
		if($payment_id !="" && $payment_id != $qpp['inputreference'] && $check > 0)
			{
			$_SESSION['payment_id']	= $payment_id;
			$_SESSION['payment_amount'] = $check;
			paypal_process();
			}
		else
			{
			$errors	= '<p class="error">Please check the details you entered.</p>';
			}
		}
	$payment_id = $qpp['inputreference'];
	$payment_amount = $qpp['inputamount'];
	if (!empty($qpp['title'])) $qpp['title'] = '<h2>' . $qpp['title'] . '</h2>';
	if (!empty($qpp['blurb'])) $qpp['blurb'] = '<p>' . $qpp['blurb'] . '</p>';
	$content ='
	<div id="qpp-style" ' . $width . '>' . $qpp['title'] . $qpp['blurb'] .
		'<form id="frmPayment" name="frmPayment" method="post" action="" onsubmit="return validatePayment();">';
		$content .= $errors;
		if (empty($id)&& empty($amount)) {
			$content .= '<p><input type="text"  label="Reference" name="payment_id" value="' . $payment_id . '" onfocus="clickclear(this, \'' . $payment_id . '\')" onblur="clickrecall(this, \'' . $payment_id . '\')"/></p>';
			$content .= '<p><input type="text"  label="Amount" name="payment_amount" value="' . $payment_amount . '" onfocus="clickclear(this, \'' . $payment_amount . '\')" onblur="clickrecall(this, \'' . $payment_amount . '\')"/></p>';
			$caption = $qpp['submitcaption'];
			}
		if (empty($id) && !empty($amount)) {
			$content .= '<p><input type="text"  label="Reference" name="payment_id" value="' . $payment_id . '" onfocus="clickclear(this, \'' . $payment_id . '\')" onblur="clickrecall(this, \'' . $payment_id . '\')"/></p>';
			$caption = $payment_amount . ' ' . $amount;
			}
		if (!empty($id) && empty($amount)) {
			$content .= '<p class="payment" >' . $payment_id . ' ' . $id . '</p>';
			$content .= '<p><input type="text"  label="Amount" name="payment_amount" value="' . $payment_amount . '" onfocus="clickclear(this, \'' . $payment_amount . '\')" onblur="clickrecall(this, \'' . $payment_amount . '\')"/></p>';
			$caption = $qpp['submitcaption'];
			}
		if (!empty($id) && !empty($amount)) {
			$content .= '<p class="payment" >' . $payment_id . ' ' . $id . '</p>';
			$content .= '<p class="payment" >' . $payment_amount . ' ' . $amount . '</p>';
			$caption = $qpp['submitcaption'];
			}
		$content .= '<p><input type="submit" value="' . $caption . '" id="submit" name="PaymentSubmit" /></p>
		</form>
		</div>';
	echo $content;	
	$output_string=ob_get_contents();
	ob_end_clean();
	return $output_string;
	}

function paypal_process()
	{
	$qpp = qpp_get_stored_options();
	$page_url = current_page_url();
	if (empty ($qpp['thanksurl'])) $qpp['thanksurl'] = $page_url;
	if (empty ($qpp['cancelurl'])) $qpp['cancelurl'] = $page_url;
	if ($qpp['target'] == 'newpage') $target = ' target="_blank" ';
	$content = '<h2>Waiting for Paypal...</h2>
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" name="frmCart" id="frmCart" ' . $target . '>
	<input type="hidden" name="cmd" value="_xclick">
	<input type="hidden" name="business" value="' . $qpp['email'] . '">
	<input type="hidden" name="return" value="' .  $qpp['thanksurl'] . '">
	<input type="hidden" name="cancel_return" value="' .  $qpp['cancelurl'] . '">
	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="currency_code" value="' .  $qpp['currency'] . '">
	<input type="hidden" name="item_number" value="">
	<input type="hidden" name="item_name" value="' .  $qpp['inputreference'] . ': ' . $_SESSION['payment_id'] . '">
	<input type="hidden" name="amount" value="' . $_SESSION['payment_amount'] . '">
	</form>
	<script language="JavaScript">
	document.getElementById("frmCart").submit();
	</script>';
	echo $content;
	}

function current_page_url() {
	$pageURL = 'http';
	if( isset($_SERVER["HTTPS"]) ) {
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

function qpp_use_custom_css () {
	$qpp = qpp_get_stored_options();
	if ($qpp['styles'] == 'custom') {
		$code = "<style type=\"text/css\" media=\"screen\">\r\n" . $qpp['custom'] . "\r\n</style>\r\n";
		echo $code;
		}
	}

function qpp_get_stored_options () {
	$qpp = get_option('qpp_options');
	if(!is_array($qpp)) $qpp = array();
	$option_default = qpp_get_default_options();
	$qpp = array_merge($option_default, $qpp);
	return $qpp;
	}

function qpp_get_default_options () {
	$qpp = array();
	$qpp['email'] = '';
	$qpp['currency'] = 'GBP';
	$qpp['title'] = 'Payment Form';
	$qpp['blurb'] = 'Enter the required imformation and submit';
	$qpp['inputreference'] = 'Payment for';
	$qpp['inputamount'] = 'Amount to pay';
	$qpp['submitcaption'] = 'Make Payment';
	$qpp['cancelurl'] = '';
	$qpp['thanksurl'] = '';
	$qpp['target'] = 'current';
	$qpp['styles'] = 'plugin';
	$qpp['custom'] = "#qpp-style {\r\n\r\n}";
	$qpp['width'] = '280';
	$qpp['widthtype'] = 'pixel';
	return $qpp;
	}