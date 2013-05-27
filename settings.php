<?php

add_action('admin_menu', 'qpp_init');
add_action('admin_notices', 'qpp_admin_notice' );

$settingsurl = plugins_url('settings.css', __FILE__);
wp_register_style('qpp_settings', $settingsurl);
wp_enqueue_style('qpp_settings');

register_uninstall_hook(__FILE__, 'qpp_delete_options');

function qpp_init() {
	add_options_page('Paypal Payments', 'Paypal Payments', 'manage_options', __FILE__, 'qpp_tabbed_page');
	}
function qpp_delete_options() {
	delete_option('qpp_options');
	delete_option('qpp_style');
	delete_option('qpp_error');
	delete_option('qpp_setup');
	delete_option('qpp_send');
	delete_option('qpp_upgrade');
	}
function qpp_admin_tabs($current = 'settings') { 
	$tabs = array( 'setup' => 'Setup' , 'settings' => 'Form Settings', 'send' => 'Submit Options','styles' => 'Styling' , 'error' => 'Error Messages' , 'help' => 'Help' , ); 
	$links = array();  
	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper">';
	foreach( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='?page=quick-paypal-payments/settings.php&tab=$tab'>$name</a>";
		}
	echo '</h2>';
	}
function qpp_tabbed_page() {
	qpp_use_custom_css();
	echo '<div class="wrap">';
	echo '<h1>Quick Contact Form</h1>';
	if ( isset ($_GET['tab'])) {qpp_admin_tabs($_GET['tab']); $tab = $_GET['tab'];} else {qpp_admin_tabs('setup'); $tab = 'setup';}
	switch ($tab) {
		case 'setup' : qpp_setup(); break;
		case 'settings' : qpp_form_options(); break;
		case 'send' : qpp_send_options(); break;		
		case 'styles' : qpp_styles(); break;
		case 'error' : qpp_error_page (); break;
		case 'help' : qpp_help (); break;
		}
	echo '</div>';
	}

function qpp_setup () {
	if( isset( $_POST['qpp_submit'])) {
		$qpp_setup['email'] = $_POST['email'];
		$qpp_setup['currency'] = $_POST['currency'];
		update_option( 'qpp_setup', $qpp_setup);
		qpp_admin_notice("The settings have been updated.");
		}
	$qpp_setup = qpp_get_stored_setup();
	$options = qpp_get_stored_options();
	$upgrade = get_option('qpp_upgrade');
	$content .= '<div class="qpp-options">
	<h2 style="color:#B52C00">Paypal Payments Setup</h2>
	<form action="" method="POST">
	<p><span style="color:red; font-weight: bold; margin-right: 3px">Important!</span> Enter your PAYPAL email address and currency code below and save the changes.</p>
	<h3>Email address</h3>
	<input type="text" style="width:90%" label="Email" name="email" value="' . $qpp_setup['email'] . '" /></p>
	<h3>Currency code</h3>
	<input type="text" style="width:6em" label="Currency" name="currency" value="' . $qpp_setup['currency'] . '" /> (For example: GBP, USD, EUR)</p>
	<p>Currency codes are given <a href="http://en.wikipedia.org/wiki/ISO_4217" target="blank">here</a>.</p>
	<input type="submit" name="qpp_submit" class="button-primary" style="color: #FFF" value="Save Changes" />
	</form>
	</div>
	<div class="qpp-options">
	<h2 style="color:#B52C00">Using the Plugin</h2>
	<p>To add the paypal payment to your posts and pages use the shortcode <code>[qpp]</code>.</p>
	<p>To add a payment form to a sidebar use the <a href="' . get_admin_url() . '/wp-admin/widgets.php">widget manager</a>.</p>
	<p>The sortcode supports two parameters: <em>id</em> and <em>amount</em>. For a full explantion on how these work see the <a href= "?page=quick-paypal-payments/settings.php&tab=help">help</a> page.</p>
	<p>You can only have one payment form per page. I&#146;ve tried to get multiple forms to work but failed miserably. Hopefully in the future I&#146;ll get it to work properly.</p>
	</div>';
	echo $content;
	}

function qpp_form_options() {
	if( isset( $_POST['qpp_submit'])) {
		$options = array('title','blurb','inputreference','inputamount','shortcodereference','shortcodeamount','shortcode_labels','submitcaption','cancelurl,','thanksurl','target');
		foreach ($options as $item) $qpp[$item] = stripslashes( $_POST[$item]);
		update_option('qpp_options', $qpp);
		qpp_admin_notice("The form and submission settings have been updated.");
		}
	if( isset( $_POST['Reset'])) {
		delete_option('qpp_options');
		qpp_admin_notice("The form and submission settings have been reset.");
		}
	$qpp = qpp_get_stored_options();
	$$qpp['target'] = 'checked';
	$content = '
	<div class="qpp-options">
	<h2 style="color:#B52C00">Payment Form Settings</h2>
	<h2>Form Title and Introductory Blurb</h2>
	<form action="" method="POST">
	<p>Paypal form heading (optional)</p>
	<input type="text" style="width:90%" name="title" value="' . $qpp['title'] . '" />
	<p>This is the blurb that will appear below the heading and above the form (optional):</p>
	<input type="text" style="width:90%" name="blurb" value="' . $qpp['blurb'] . '" />
	<h2>Payment labels</h2>
	<p>Label for the payment Reference/ID/Number:</p>
	<input type="text" style="width:90%" name="inputreference" value="' . $qpp['inputreference'] . '" />
	<p>Label for the amount field:</p>
	<input type="text" style="width:90%" name="inputamount" value="' . $qpp['inputamount'] . '" />
	<h2>Shortcode labels</h2>
	<p>These are the labels that will display if you are using <a href="?page=quick-paypal-payments/settings.php&tab=help">shortcode attributes</a>.</p>
	<p>Label for the payment Reference/ID/Number:</p>
	<input type="text" style="width:90%" name="shortcodereference" value="' . $qpp['shortcodereference'] . '" />
	<p>Label for the amount field:</p>
	<input type="text" style="width:90%" name="shortcodeamount" value="' . $qpp['shortcodeamount'] . '" />
	<h2>Submit button caption</h2>
	<input type="text" style="width:90%" name="submitcaption" value="' . $qpp['submitcaption'] . '" />
	<p><input type="submit" name="qpp_submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form settings?\' );"/></p>
	</form>
	</div>
	<div class="qpp-options">
	<h2 style="color:#B52C00">Form Preview</h2>
	<p>Note: The preview form uses the wordpress admin styles. Your form will use the theme styles so won\'t look exactly like the one below.</p>';
	$content .= qpp_loop('','');
	$content .= '</div>';
	echo $content;
	}

function qpp_send_options() {
	if( isset( $_POST['Submit'])) {
		$options = array('waiting','cancelurl,','thanksurl','target');
		foreach ($options as $item) $send[$item] = stripslashes( $_POST[$item]);
		update_option('qpp_send', $send);
		qpp_admin_notice("The submission settings have been updated.");
		}
	if( isset( $_POST['Reset'])) {
		delete_option('qpp_send');
		qpp_admin_notice("The submission settings have been reset.");
		}
	$send = qpp_get_stored_send();
	$$send['target'] = 'checked';
	$content = '
	<div class="qpp-options">
	<h2 style="color:#B52C00">Submission Settings</h2>
	<form action="" method="POST">
	<h2>Submission Message</h2>
	<p>This is what the visitor sees while the paypal page loads</p>
	<input type="text" style="width:90%" name="waiting" value="' . $send['waiting'] . '" />
	<h2>Cancel and Thank you pages</h2>
	<p>If you leave these blank paypal will return the user to the current page.</p>
	<h3>URL of cancellation page</h3>
	<input type="text" style="width:90%" name="cancelurl" value="' . $send['cancelurl'] . '" />
	<h3>URL of thank you page</h3>
	<input type="text" style="width:90%" name="thanksurl" value="' . $send['thanksurl'] . '" />
	<h2>Paypal Link</h2>
	<p><input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="target" value="newpage" ' . $newpage . ' /> Open link in new page/tab<br>
	<input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="target" value="current" ' . $current . ' /> Open in existing page</p>
	<p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form settings?\' );"/></p>
	</form>
	</div>
	<div class="qpp-options">
	<h2 style="color:#B52C00">Form Preview</h2>
	<p>Note: The preview form uses the wordpress admin styles. Your form will use the theme styles so won\'t look exactly like the one below.</p>';
	$content .= qpp_loop('','');
	$content .= '</div>';
	echo $content;
	}

function qpp_styles() {
	if( isset( $_POST['Submit'])) {
		$options = array( 'font','font-family','font-size','border','width','widthtype','background','backgroundhex','corners','styles','use_custom','custom');
		foreach ( $options as $item) $style[$item] = stripslashes($_POST[$item]);
		update_option( 'qpp_style', $style);
		qpp_admin_notice("The form styles have been updated.");
		}
	if( isset( $_POST['Reset'])) {
		delete_option('qpp_style');
		qpp_admin_notice("The form styles have been reset.");
		}
	$style = qpp_get_stored_style();
	$$style['font'] = 'checked';
	$$style['widthtype'] = 'checked';
	$$style['border'] = 'checked';
	$$style['background'] = 'checked';
	$$style['corners'] = 'checked';
	$$style['styles'] = 'checked';
	qpp_use_custom_css();
	$content ='<div class="qpp-options">
	<h2 style="color:#B52C00">Form Styles</h2>
	<form method="post" action=""> 
	<h2>Form Width</h2>
	<p>
		<input style="margin:0; padding:0; border:none;" type="radio" name="widthtype" value="percent" ' . $percent . ' /> 100% (fill the available space)<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="widthtype" value="pixel" ' . $pixel . ' /> Pixel (fixed)</p>
	<p>Enter the width of the form in pixels. Just enter the value, no need to add \'px\'. The current width is as you see it here.</p>
	<p><input type="text" style="width:4em" label="width" name="width" value="' . $style['width'] . '" /> px</p>
	<h2>Font Options</h2>
	<p>
		<input style="margin:0; padding:0; border:none" type="radio" name="font" value="theme" ' . $theme . ' /> Use your theme font styles<br />
		<input style="margin:0; padding:0; border:none" type="radio" name="font" value="plugin" ' . $plugin . ' /> Use Plugin font styles (enter font family and size below)
	</p>
	<p>Font Family: <input type="text" style="width:15em" label="font-family" name="font-family" value="' . $style['font-family'] . '" /></p>
	<p>Font Size: <input type="text" style="width:6em" label="font-size" name="font-size" value="' . $style['font-size'] . '" /></p>
	<h2>Form Border</h2>
	<p>Note: The rounded corners and shadows only work on CSS3 supported browsers and even then not in IE8. Don\'t blame me, blame Microsoft.</p>
	<p>
		<input style="margin:0; padding:0; border:none;" type="radio" name="border" value="none" ' . $none . ' /> No border<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="border" value="plain" ' . $plain . ' /> Plain Border<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="border" value="rounded" ' . $rounded . ' /> Round Corners (Not IE8)<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="border" value="shadow" ' . $shadow . ' /> Shadowed Border(Not IE8)<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="border" value="roundshadow" ' . $roundshadow . ' /> Rounded Shadowed Border (Not IE8)</p>
	<h2>Background colour</h2>
	<p>
		<input style="margin:0; padding:0; border:none;" type="radio" name="background" value="white" ' . $white . ' /> White<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="background" value="theme" ' . $theme . ' /> Use theme colours<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="background" value="color" ' . $color . ' /> Set your own (enter HEX code or color name below)</p>
	<p><input type="text" style="width:7em" label="background" name="backgroundhex" value="' . $style['backgroundhex'] . '" /></p>
	<h2>Input field corners</h2>
	<p>
		<input style="margin:0; padding:0; border:none;" type="radio" name="corners" value="corner" ' . $corner . ' /> Use theme settings<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="corners" value="square" ' . $square . ' /> Square corners<br />
		<input style="margin:0; padding:0; border:none;" type="radio" name="corners" value="round" ' . $round . ' /> 5px rounded corners
	</p>
	<p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /></p>
	<h2>Custom CSS</h2>
	<p><input type="checkbox" style="margin:0; padding: 0; border: none" name="custom"' . $style['use_custom'] . ' value="checked" /> Use Custom CSS</p>
	<p><textarea style="width:100%; height: 200px" name="styles">' . $style['custom'] . '</textarea></p>
	<p>To see all the styling use the <a href="'.get_admin_url().'plugin-editor.php?file=quick-contact-form/quick-contact-form-style.css">CSS editor</a>.</p>
	<p>The main style wrapper is the <code>#qpp-style</code> id.</p>
	<p>The form borders are: #none, #plain, #rounded, #shadow, #roundshadow.</p>
	<p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form styles?\' );"/></p>
	</form>
	</div>
	<div class="qpp-options">
	<h2 style="color:#B52C00">Test Form</h2>
	<p>Not all of your style selections will display here (because of how WordPress works). So check the form on your site.</p>';
	$content .= qpp_loop('','');
	$content .= '</div>';
	echo $content;
	}
function qpp_error_page() {
	if( isset( $_POST['Submit'])) {
		$options = array('errortitle','errorblurb');
		foreach ( $options as $item) $error[$item] = stripslashes($_POST[$item]);
		update_option( 'qpp_error', $error );
		qpp_admin_notice("The error settings have been updated.");
		}
	$error = qpp_get_stored_error();
	qpp_use_custom_css();
	$content .='<div class="qpp-options">';
	$content .='<h2 style="color:#B52C00">Error messages settings</h2>';
	$content .='<form method="post" action="">
		<p>Error header (leave blank if you don\'t want a heading):</p>
		<p><input type="text"  style="width:100%" name="errortitle" value="' . $error['errortitle'] . '" /></p>
		<p>This is the blurb that will appear below the error heading and above the actual error messages (leave blank if you don\'t want any blurb):</p>
		<p><input type="text" style="width:100%" name="errorblurb" value="' . $error['errorblurb'] . '" /></p>
		<p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the error message?\' );"/></p>
		</form>
		</div>
		<div class="qpp-options">
		<h2 style="color:#B52C00">Error Checker</h2>
		<p>Try sending a blank form to test your error messages.</p>';
	$content .= qpp_loop('','');
	$content .= '</div>';
	echo $content;
	}
function qpp_help() {
	$content .='<div class="qpp-options">
		<h2 style="color:#B52C00">Getting Started</h2>
		<p>A default form is already installed and ready to use. To add to a page or a post just add the shortcode <code>[qpp]</code>. If you want to add the form to a sidebar use the Paypal Payment widget.</p>
		<p>You can now use the tabbed options on this page to change any of settings. If you haven\'t already, click on the Setup tab and add your email address and currency.</p>
		<h2>Form settings and options</h2>
		<p>The <a href="?page=quick-paypal-payments/settings.php&tab=settings">Form Settings</a> page allows you to change the label of the two fields. You can also change the submit button label and where paypal sends the visitor. When you save the changes the updated form will preview on the right.</p>
		<p>To change the width of the form, border style and background colour use the <a href="?page=quick-paypal-payments/settings.php&tab=styles">styling</a> page. You also have the option to add some custom CSS.</p>
		<p>You can create your own <a href= "?page=quick-paypal-payments/settings.php&tab=error">error messages</a> as well.</p>
		<p>If it all goes a bit pear shaped you can reset everything to the defaults.</p>
		<p>There is some development info on <a href="http://quick-plugins.com/quick-paypal-payments/" target="_blank">my plugin page</a> along with a feedback form. Or you can email me at <a href="mailto:mail@quick-plugins.com">mail@quick-plugins.com</a>.</p>
		</div>
		<div class="qpp-options"> 
		<h2 style="color:#B52C00">Shortcode Options</h2>
		<p>You can preset the ID and amount fields using shortcode attributes. The basic format is: <code>[qpp id="ABC123" amount="$140"]</code>. You can use just one or both as required.</p>
		<p>A label is displayed on the form in front of the attribute. If you dont want them just delete the shortcode lables on the <a href="?page=quick-paypal-payments/settings.php&tab=styles">form setting</a> page. For example:</p>
		<p>The shortcode <code>[qpp id="ABC123"]</code> with labels displays as:</p>
		<p> </p>
		<p><img src="' . plugins_url('/screenshot-3.gif' , __FILE__ ) . '"></p>
		<p>Deleting labels will change the form to:</p>
		<p><img src="' . plugins_url('/screenshot-4.gif' , __FILE__ ) . '"></p>
		</div>';
	echo $content;
	}
function qpp_admin_notice($message) {
	if (!empty( $message)) echo '<div class="updated"><p>'.$message.'</p></div>';
	}