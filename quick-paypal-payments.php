<?php
/*
Plugin Name: Quick Paypal Payments
Plugin URI: http://quick-plugins.com/quick-paypal-payments/
Description: Accept any amount or payment ID before submitting to paypal 
ersion: 2.1.1
Author: fisicx
Author URI: http://quick-plugins.com/
*/

add_shortcode('qpp', 'qpp_loop');
add_action( 'widgets_init', create_function('', 'return register_widget("qpp_payment_widget");') );
add_action('wp_head', 'qpp_use_custom_css');
add_filter( 'plugin_action_links', 'qpp_plugin_action_links', 10, 2 );

if (is_admin()) require_once( plugin_dir_path( __FILE__ ) . '/settings.php' );

$scripturl = plugins_url('quick-paypal-payments-javascript.js', __FILE__);
	wp_register_script('qpp_script', $scripturl);
	wp_enqueue_script( 'qpp_script');

$styleurl = plugins_url('quick-paypal-payments-style.css', __FILE__);
	wp_register_style('qpp_style', $styleurl);
	wp_enqueue_style( 'qpp_style');

function qpp_plugin_action_links($links, $file ) {
	if ( $file == plugin_basename( __FILE__ ) ) {
		$qpp_links = '<a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/settings.php">'.__('Settings').'</a>';
		array_unshift( $links, $qpp_links );
		}
	return $links;
	}
class qpp_payment_widget extends WP_Widget {
	function qpp_payment_widget() {
		$widget_ops = array('classname' => 'qpp_payment_widget', 'description' => 'Add a payment form to your sidebar');
		$this->WP_Widget('qpp_payment_widget', 'Payment Form', $widget_ops);
		}
	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'amount' => '' , 'id' => '' ) );
		$id = $instance['id'];
		$amount = $instance['amount'];
		?>
		<p><label for="<?php echo $this->get_field_id('id'); ?>">Payment Reference: <input class="widefat" id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" type="text" value="<?php echo attribute_escape($id); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id('amount'); ?>">Amount: <input class="widefat" id="<?php echo $this->get_field_id('amount'); ?>" name="<?php echo $this->get_field_name('amount'); ?>" type="text" value="<?php echo attribute_escape($amount); ?>" /></label></p>
		<p>Leave blank to use the default settings.</p>
		<p>To configure the payment form use the <a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/quick-paypal-payments.php">Settings</a> page</p>
		<?php
		}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['id'] = $new_instance['id'];
		$instance['amount'] = $new_instance['amount'];
		return $instance;
		}
	function widget($args, $instance) {
 	   	extract($args, EXTR_SKIP);
		$id=$instance['id'];
		$amount=$instance['amount'];
		echo qpp_loop($instance);
		}
	}
function qpp_display_form($values,$error) {
	$qpp = qpp_get_stored_options();
	$style = qpp_get_stored_style();
	if (!empty($qpp['title'])) $qpp['title'] = '<h2>' . $qpp['title'] . '</h2>';
	if (!empty($qpp['blurb'])) $qpp['blurb'] = '<p>' . $qpp['blurb'] . '</p>';
	$content = "<div id='qpp-style'>\r\t";
	$content .= "<div id='" . $style['border'] . "'>\r\t";
	if ($error)
		$content .= "<h2>" . $error['errortitle'] . "</h2>\r\t<p class='error'>" . $error['errorblurb'] . "</p>\r\t";
	else
		$content .= $qpp['title'] . "\r\t" . $qpp['blurb'] . "\r\t";
	$content .= '<form id="frmPayment" name="frmPayment" method="post" action="" onsubmit="return validatePayment();">';
	if (empty($values['id'])) $content .= '<p><input type="text" label="Reference" name="reference" value="' . $values['reference'] . '" onfocus="clickclear(this, \'' . $values['reference'] . '\')" onblur="clickrecall(this, \'' . $values['reference'] . '\')"/></p>';
	else {
		if ($values['explode']) {
			$checked = 'checked';$ref = explode(",",$values['reference']);
			$content .= '<p class="payment" >'.$qpp['shortcodereference'].'<br>';
			foreach ($ref as $item) { $content .=  '<label><input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label><br>';$checked='';}
			$content .= '</p>';}
		else $content .= '<p class="payment" >'.$values['reference'].'</p>';
		}
	if (empty($values['pay'])) $content .= '<p><input type="text" label="Amount" name="amount" value="' . $values['amount'] . '" onfocus="clickclear(this, \'' . $values['amount'] . '\')" onblur="clickrecall(this, \'' . $values['amount'] . '\')"/></p>';
	else $content .= '<p class="payment" >' . $values['amount'] . '</p>';	
	$caption = $qpp['submitcaption'];
	if ($style['submit-button']) $content .= '<p><input type="image" value="' . $caption . '" style="border:none;" src="'.$style['submit-button'].'" name="PaymentSubmit" /></p>';
	else $content .= '<p><input type="submit" value="' . $caption . '" id="submit" name="PaymentSubmit" /></p>';
	$content .= '</form>
	</div></div>';
	echo $content;	
	}
function qpp_verify_form ($formvalues,$values) {
		$errors = '';
		$qpp = qpp_get_stored_options();
		$check = preg_replace ( '/[^.,0-9]/', '', $formvalues['amount']);
		if (!$values['id']) if ($formvalues['amount'] == $qpp['inputamount'] || empty($formvalues['amount'])) $errors = 'error';
		if (!$values['pay']) if ($formvalues['reference'] == $qpp['inputreference'] || empty($formvalues['reference'])) $errors	= 'error';
		return $errors;
		}
function qpp_process_form($values) {
	$qpp = qpp_get_stored_options();
	$qpp_setup = qpp_get_stored_setup();
	$send = qpp_get_stored_send();
	$page_url = qpp_current_page_url();
	if (empty($send['thanksurl'])) $send['thanksurl'] = $page_url;
	if (empty($send['cancelurl'])) $send['cancelurl'] = $page_url;
	if ($send['target'] == 'newpage') $target = ' target="_blank" ';
	$check = preg_replace ( '/[^.,0-9]/', '', $values['amount']);
	$content = '<h2>'.$send['waiting'].'</h2><form action="https://www.paypal.com/cgi-bin/webscr" method="post" name="frmCart" id="frmCart" ' . $target . '>
	<input type="hidden" name="cmd" value="_xclick">
	<input type="hidden" name="business" value="' . $qpp_setup['email'] . '">
	<input type="hidden" name="return" value="' .  $send['thanksurl'] . '">
	<input type="hidden" name="cancel_return" value="' .  $send['cancelurl'] . '">
	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="currency_code" value="' .  $qpp_setup['currency'] . '">
	<input type="hidden" name="item_number" value="">
	<input type="hidden" name="item_name" value="' .  $qpp['inputreference'] . ': ' . $values['reference'] . '">
	<input type="hidden" name="amount" value="' . $check . '">
	</form>
	<script language="JavaScript">
	document.getElementById("frmCart").submit();
	</script>';
	echo $content;
	}
function qpp_current_page_url() {
	$pageURL = 'http';
	if( isset($_SERVER["HTTPS"]) ) { if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";} }
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	return $pageURL;
	}
function qpp_loop($atts) {
	ob_start();
	extract(shortcode_atts(array( 'amount' => '' , 'id' => '' ), $atts));
	$qpp = qpp_get_stored_options();
	$errors = qpp_get_stored_error();
	if ($id) {
		$formvalues['id'] = 'checked';
		if (strrpos($id,',')) {$formvalues['reference'] = $id;$formvalues['explode'] = 'checked';}
		else $formvalues['reference'] = $qpp['shortcodereference'].' '.$id;
		}
	else {$formvalues['reference'] = $qpp['inputreference'];$formvalues['id'] = '';}
	if ($amount) {$formvalues['amount'] = $qpp['shortcodeamount'].' '.$amount;$formvalues['pay'] = 'checked';}
	else {$formvalues['amount'] = $qpp['inputamount'];$formvalues['pay'] = '';}
	if (isset($_POST['PaymentSubmit']) || isset($_POST['PaymentSubmit_x'])) {
		if (isset($_POST['reference'])) {$formvalues['reference'] = $_POST['reference'];$id = $_POST['reference'];}
		if (isset($_POST['amount'])) $formvalues['amount'] = $_POST['amount'];
		if (qpp_verify_form($formvalues,$values)) qpp_display_form($formvalues,$errors);
   		else {
			if ($amount) $formvalues['amount'] = $amount;
			if ($id) $formvalues['reference'] = $id;
			qpp_process_form($formvalues);
			}
		}
	else qpp_display_form($formvalues,'');
	$output_string=ob_get_contents();
	ob_end_clean();
	return $output_string;
	}
function qpp_use_custom_css () {
	$style = qpp_get_stored_style();
	if ($style['font'] == 'plugin') {
			$font = "font-family: ".$style['font-family']."; font-size: ".$style['font-size'].";color: ".$style['font-colour'].";";			
			}
	$input = "#qpp-style input[type=text] {border: ".$style['input-border'].";".$font."}\r\n";
	
	if ($style['background'] == 'white') $background = "#qpp-style div {background:#FFF;}\r\n";
	if ($style['background'] == 'color') $background = "#qpp-style div {background:".$style['backgroundhex'].";}\r\n";
	if ($style['widthtype'] == 'pixel') $width = preg_replace("/[^0-9]/", "", $style['width']) . 'px';
	else $width = '100%';
	if ($style['corners'] == 'round') $corner = '5px'; else $corner = '0';
	$corners = "#qpp-style input[type=text], #qpp-style #submit {border-radius:".$corner.";}\r\n";
	$submit = "#qpp-style #submit{color:".$style['submit-colour'].";background:".$style['submit-background'].";}\r\n";
	if ($style['corners'] == 'theme') $corners = '';
	$code .= "<style type=\"text/css\" media=\"screen\">\r\n#qpp-style {width:".$width.";}\r\n".$corners.$input.$background.$submit;
	if ($style['use_custom'] == 'checked') $code .= $style['custom'] . "\r\n";
	$code .= "</style>\r\n";
	echo $code;
	}
function qpp_get_stored_setup () {
	$qpp_setup = get_option('qpp_setup');
	if(!is_array($qpp_setup)) $qpp_setup = array();
	$option_default = qpp_get_default_setup();
	$qpp_setup = array_merge($option_default, $qpp_setup);
	return $qpp_setup;
	}
function qpp_get_default_setup() {
	$qpp_setup = array();
	$qpp_setup['email'] = '';
	$qpp_setup['currency'] = 'GBP';
	return $qpp_setup;
	}
function qpp_upgrade ($options){
	$upgrade = get_option('qpp_upgrade');
	if (empty($upgrade)) {
		$qpp_setup = array();
		$qpp_setup['email'] = $options['email'];
		$qpp_setup['currency'] = $options['currency'];
		update_option('qpp_setup', $qpp_setup);
		$send = array();
		$send['cancelurl'] = $options['cancelurl'];
		$send['thanksurl'] = $options['thanksurl'];
		$send['target'] = $options['target'];
		update_option('qpp_send', $send);
		$style = array();
		$style['styles'] = $options['styles'];
		$style['custom'] = $options['custom'];
		$style['width'] = $options['width'];
		$style['widthtype'] = $options['widthtype'];
		update_option('qpp_style', $style);
		$upgrade = 'complete';
		update_option('qpp_upgrade', $upgrade);
		}
	}

function qpp_get_stored_options () {
	$options = get_option('qpp_options');
	if(!is_array($options)) $options = array();
	else qpp_upgrade($options);
	$default = qpp_get_default_options();
	$options = array_merge($default, $options);
	return $options;
	}
function qpp_get_default_options () {
	$qpp = array();
	$qpp['title'] = 'Payment Form';
	$qpp['blurb'] = 'Enter the required imformation and submit';
	$qpp['inputreference'] = 'Payment reference';
	$qpp['inputamount'] = 'Amount to pay';
	$qpp['shortcodereference'] = 'Payment for: ';
	$qpp['shortcodeamount'] = 'Amount: ';
	$qpp['submitcaption'] = 'Make Payment';
	return $qpp;
	}
function qpp_get_stored_send() {
	$send = get_option('qpp_send');
	if(!is_array($send)) $send = array();
	$default = qpp_get_default_send();
	$send = array_merge($default, $send);
	return $send;
	}
function qpp_get_default_send() {
	$send['waiting'] = 'Waiting for PayPal...';
	$send['cancelurl'] = '';
	$send['thanksurl'] = '';
	$send['target'] = 'current';
	return $send;
	}
function qpp_get_stored_style() {
	$style = get_option('qpp_style');
	if(!is_array($style)) $style = qpp_get_stored_options();
	$default = qpp_get_default_style();
	$style = array_merge($default, $style);
	return $style;
	}
function qpp_get_default_style() {
	$style['font'] = 'plugin';
	$style['font-family'] = 'arial, sans-serif';
	$style['font-size'] = '1.2em';
	$style['font-colour'] = '#465069';
	$style['width'] = 280;
	$style['widthtype'] = 'pixel';
	$style['border'] = 'plain';
	$style['input-border'] = '1px solid #415063';
	$style['input-required'] = '1px solid #00C618';
	$style['bordercolour'] = '#415063';
	$style['background'] = 'white';
	$style['backgroundhex'] = '#FFF';
	$style['corners'] = 'corner';
	$style['submit-colour'] = '#FFF';
	$style['submit-background'] = '#343838';
	$style['submit-button'] = '';
	$style['styles'] = 'plugin';
	$style['use_custom'] = '';
	$style['custom'] = "#qpp-style {\r\n\r\n}";
	return $style;
	}
function qpp_get_stored_error () {
	$error = get_option('qpp_error');
	if(!is_array($error)) $error = qpp_get_stored_options();
	$default = qpp_get_default_error();
	$error = array_merge($default, $error);
	return $error;
	}
function qpp_get_default_error () {
	$error['errortitle'] = 'Oops, got a problem here';
	$error['errorblurb'] = 'Please check the payment details';
	return $error;
	}