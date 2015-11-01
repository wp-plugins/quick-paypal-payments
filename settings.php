<?php
add_action('init', 'qpp_settings_init');
add_action('admin_menu', 'qpp_page_init');
add_action('admin_notices', 'qpp_admin_notice' );
add_action( 'admin_menu', 'qpp_admin_pages' );
add_action('plugin_row_meta', 'qpp_plugin_row_meta', 10, 2 );

function qpp_admin_tabs($current = 'settings') {
    $tabs = array(
        'setup' => 'Setup',
        'settings' => 'Form Settings',
        'styles' => 'Styling',
        'send' => 'Send Options',
        'autoresponce' => 'Auto Responder',
        'ipn' => 'IPN',
        'error' => 'Error Messages'
    ); 
    $links = array();  
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ) {
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=quick-paypal-payments/settings.php&tab=$tab'>$name</a>";
    }
    echo '</h2>';
}

function qpp_tabbed_page() {
    $qpp_setup = qpp_get_stored_setup();
    $id=$qpp_setup['current'];
    echo '<h1>Quick Paypal Payments</h1>';
    if ( isset ($_GET['tab'])) {qpp_admin_tabs($_GET['tab']); $tab = $_GET['tab'];} else {qpp_admin_tabs('setup'); $tab = 'setup';}
    switch ($tab) {
        case 'setup' : qpp_setup($id); break;
        case 'settings' : qpp_form_options($id); break;
        case 'styles' : qpp_styles($id); break;
        case 'send' : qpp_send_page($id); break;
        case 'error' : qpp_error_page ($id); break;
        case 'address' : qpp_address ($id); break;
        case 'shortcodes' : qpp_shortcodes (); break;
        case 'reset' : qpp_reset_page($id); break;
        case 'coupon' : qpp_coupon_codes($id); break;
        case 'ipn' : qpp_ipn_page(); break;
        case 'donate' : qpp_donate_page(); break;
        case 'autoresponce' : qpp_autoresponce_page($id); break;
    }
    echo '';
}

function qpp_setup ($id) {
    $qpp_setup = qpp_get_stored_setup();
    $new_curr= '';
    if( isset( $_POST['Submit']) && check_admin_referer("save_qpp")) {
        
        $qpp_setup['alternative'] = filter_var($_POST['alternative'],FILTER_SANITIZE_STRING);
        $qpp_setup['email'] = filter_var($_POST['email'],FILTER_SANITIZE_STRING);
        
        if (!empty($_POST['new_form'])) {
            $qpp_setup['current'] = stripslashes($_POST['new_form']);
            $qpp_setup['current'] = filter_var($qpp_setup['current'],FILTER_SANITIZE_STRING);
            $qpp_setup['current'] = preg_replace("/[^A-Za-z]/",'',$qpp_setup['current']);
            $qpp_setup['alternative'] = $qpp_setup['current'].','.$qpp_setup['alternative'];
        }
        else {
            $qpp_setup['current'] = filter_var($_POST['current'],FILTER_SANITIZE_STRING);
        }
        
        if (empty($qpp_setup['current'])) {
            $qpp_setup['current'] = '';
        }
        
        $arr = explode(",",$qpp_setup['alternative']);
        foreach ($arr as $item) {
            $qpp_curr[$item] = stripslashes($_POST['qpp_curr'.$item]);
            $qpp_curr[$item] = filter_var($qpp_curr[$item],FILTER_SANITIZE_STRING);
            $qpp_email[$item] = stripslashes($_POST['qpp_email'.$item]);
            $qpp_email[$item] = filter_var($qpp_email[$item],FILTER_SANITIZE_STRING);
        }
        
        if (!empty($_POST['new_form'])) {
            $email = $qpp_setup['current'];
            $qpp_curr[$email] = stripslashes($_POST['new_curr']);
            $qpp_curr[$email] = filter_var($qpp_curr[$email],FILTER_SANITIZE_STRING);
        }
        
        $qpp_setup['sandbox'] = $_POST['sandbox'];
        update_option( 'qpp_curr', $qpp_curr);
        update_option( 'qpp_email', $qpp_email);
        update_option( 'qpp_setup', $qpp_setup);
        qpp_create_css_file ('update');
        qpp_admin_notice("The forms have been updated.");
        if ($_POST['qpp_clone'] && !empty($_POST['new_form'])) qpp_clone($qpp_setup['current'],$_POST['qpp_clone']);

    }

    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        qpp_delete_everything();
        qpp_create_css_file ('');
        qpp_admin_notice("Everything has been reset.");
        $qpp_setup = qpp_get_stored_setup();
    }

    $arr = explode(",",$qpp_setup['alternative']);
    foreach ($arr as $item) if (isset($_POST['deleteform'.$item]) && $_POST['deleteform'.$item] == $item && isset($_POST['delete'.$item]) && $item != '') {
        $forms = $qpp_setup['alternative'];
        qpp_delete_things($_POST['deleteform'.$item]);
        $qpp_setup['alternative'] = str_replace($_POST['deleteform'.$item].',','',$forms); 
        $qpp_setup['current'] = '';
        $qpp_setup['email'] = $_POST['email'];
        update_option('qpp_setup', $qpp_setup);
        qpp_create_css_file ('update');
        qpp_admin_notice("<b>The form named ".$item." has been deleted.</b>");
        $id = '';
        break;
    }

    $qpp_curr = qpp_get_stored_curr();
    $qpp_email = qpp_get_stored_email();
    if (!$new_curr) $new_curr = $qpp_curr[''];
    $content ='<div class="qpp-settings"><div class="qpp-options">
    <form method="post" action="">
    <h2>Account Email</h2>
    <p><span style="color:red; font-weight: bold; margin-right: 3px">Important!</span> Enter your PAYPAL email address</p>
    <input type="text" label="Email" name="email" value="' . $qpp_setup['email'] . '" /></p>
    <h2>Existing Forms</h2>
    <table>
    <tr>
    <td><b>Form name&nbsp;&nbsp;</b></td>
    <td><b>Currency</b></td>
    <td><b>Shortcode</b></td>
    </tr>';
    $arr = explode(",",$qpp_setup['alternative']);
    sort($arr);
    foreach ($arr as $item) {
        if ($qpp_setup['current'] == $item) $checked = 'checked'; else $checked = '';
        if (!$qpp_email[$item]) $qpp_email[$item] = $qpp_setup['email'];
        if ($item == '') $formname = 'default'; else $formname = $item;
        $content .='<tr>
        <td><input style="margin:0; padding:0; border:none" type="radio" name="current" value="' .$item . '" ' .$checked . ' /> '.$formname.'</td>
        <td><input type="text" style="width:3em;padding:1px;" name="qpp_curr'.$item.'" value="' . $qpp_curr[$item].'" /></td>';
        if ($item) $shortcode = ' form="'.$item.'"'; else $shortcode='';
        $content .= '<td><code>[qpp'.$shortcode.']</code></td><td>';
        if ($item) $content .= '<input type="hidden" name="deleteform'.$item.'" value="'.$item.'"><input type="submit" name="delete'.$item.'" class="button-secondary" value="delete" onclick="return window.confirm( \'Are you sure you want to delete '.$item.'?\' );" />';
        $content .= '</td></tr>';
    }
    $content .= '</table>
    <h2>Create New Form</h2>
    <p>Enter form name (letters only - no numbers, spaces or punctuation marks)</p>
    <p><input type="text" label="new_Form" name="new_form" value="" /></p>
    <p>Enter currency code: <input type="text" style="width:3em" label="new_curr" name="new_curr" value="'.$new_curr.'" />&nbsp;(For example: GBP, USD, EUR)</p>
    <p>Allowed Paypal Currency codes are given <a href="https://developer.paypal.com/webapps/developer/docs/classic/api/currency_codes/" target="blank">here</a>.</p>
    <p><span style="color:red; font-weight: bold; margin-right: 3px">Important!</span> If your currency is not listed the plugin will work but paypal will not accept the payment.</p>
    <input type="hidden" name="alternative" value="' . $qpp_setup['alternative'] . '" />
    <p>Copy settings from an exisiting form.</p>
    <select name="qpp_clone"><option>Do not copy settings</option>';
    foreach ($arr as $item) {
        if ($item == '') $item = 'default';
        $content .= '<option value="'.$item.'">'.$item.'</option>';
    }
    $content .= '</select>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Update Settings" /> <input type="submit" name="Reset" class="button-secondary" value="Reset Everything" onclick="return window.confirm( \'This will delete all your forms and settings.\nAre you sure you want to reset everything?\' );"/></p>
    <p><input type="checkbox" style="margin:0; padding: 0; border: none" name="sandbox" ' . $qpp_setup['sandbox'] . ' value="checked" /> Use Paypal sandbox (developer use only)</p>';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>';
    $content .= donate_loop();
    $content .= '</div>
    <div class="qpp-options" style="float:right"> 
    <h2>Adding the payment form to your site</h2>
    <p>To add the basic payment form to your posts or pages use the shortcode: <code>[qpp]</code>. Shortcodes for named forms are given on the left.</p>
    <p>There is also a widget called "Quick Paypal Payments" you can drag and drop into a sidebar.</p>
    <p>That\'s it. The payment form is ready to use.</p>
    <h2>Shortcodes and Examples</h2>
    <p>All the shortcodes are given <a href="http://quick-plugins.com/quick-paypal-payments/paypal-payments-shortcodes/" target="_blank">on this page</a>.</p>
    <p>There are examples of payment forms <a href="http://quick-plugins.com/quick-paypal-payments/paypal-examples/" target="_blank">on this page</a>.</p>
    <h2>Options and Settings</h2>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments/settings.php&tab=settings">Form Settings.</a></span> Change the layout of the form, add or remove fields and the order they appear and edit the labels and captions.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments/settings.php&tab=styles">Styling.</a></span> Change fonts, colours, borders, images and submit button.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments/settings.php&tab=reply">Send Options.</a></span> Change how the form is sent.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments/settings.php&tab=autoreponce">Auto Responder.</a></span> Set up a thank you message.</p>
<p><span style="font-weight:bold"><a href="?page=quick-paypal-payments/settings.php&tab=ipn">Instant Payment Notification.</a></span> Keep track of completed payments.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments/settings.php&tab=error">Error Messages.</a></span> Change the error message.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments/quick-paypal-messages.php">Payment Records.</a></span> See all the payment records. Or click on the <b>Payments</b> link in the dashboard menu.</p>
    <h2>Support</h2>
    <p>If you have any questions visit the <a href="http://quick-plugins.com/quick-paypal-payments/">plugin support page</a> or email me at <a href="mailto:mail@quick-plugins.com">mail@quick-plugins.com</a>.</p>
    </div>
    </div>';
    echo $content;
}

function qpp_clone ($id,$clone) {
    if ($clone == 'default') $clone = '';
    $update = qpp_get_stored_options ($clone);update_option( 'qpp_options'.$id, $update );
    $update = qpp_get_stored_send ($clone);update_option( 'qpp_send'.$id, $update );
    $update = qpp_get_stored_style ($clone);update_option( 'qpp_style'.$id, $update );
    $update = qpp_get_stored_coupon ($clone);update_option( 'qpp_coupon'.$id, $update );
    $update = qpp_get_stored_error ($clone);update_option( 'qpp_error'.$id, $update );
    $update = qpp_get_stored_address ($clone);update_option( 'qpp_address'.$id, $update );
    qpp_create_css_file ('update');
}


function qpp_form_options($id) {
    qpp_change_form_update($id);
    if( isset( $_POST['qpp_submit']) && check_admin_referer("save_qpp")) {
        $options = array(
            'title',
            'blurb',
            'sort',
            'inputreference',
            'inputamount',
            'allow_amount',
            'shortcodereference',
            'use_quantity',
            'quantitylabel',
            'use_stock',
            'ruse_stock',
            'fixedstock',
            'stocklabel',
            'use_options',
            'optionlabel',
            'optionvalues',
            'shortcodeamount',
            'shortcode_labels',
            'submitcaption',
            'cancelurl,',
            'thanksurl',
            'target',
            'paypal-url',
            'paypal-location',
            'useprocess',
            'processblurb',
            'processref',
            'processtype',
            'processpercent',
            'processfixed',
            'usepostage',
            'postageblurb',
            'postageref',
            'postagetype',
            'postagepercent',
            'postagefixed',
            'usecoupon',
            'couponblurb',
            'couponref',
            'couponbutton',
            'captcha',
            'mathscaption',
            'fixedreference',
            'fixedamount',
            'useterms',
            'useblurb',
            'extrablurb',
            'userecurring',
            'recurringblurb',
            'recurring',
            'Dvalue',
            'Wvalue',
            'Mvalue',
            'Yvalue',
            'srt',
            'payments',
            'every',
            'termsblurb',
            'termsurl',
            'termspage',
            'quantitymax',
            'quantitymaxblurb',
            'combine',
            'usetotals',
            'totalsblurb',
            'useaddress',
            'addressblurb',
            'currency_seperator',
            'selector',
            'refselector',
            'optionselector',
            'use_reset',
            'resetcaption',
            'use_slider',
            'sliderlabel',
            'min',
            'max',
            'initial',
            'step',
            'inline_amount',
            'useemail',
            'emailblurb' 
        );
        foreach ($options as $item) {
            $qpp[$item] = stripslashes( $_POST[$item]);
            $qpp[$item] = filter_var($qpp[$item],FILTER_SANITIZE_STRING);
        }
        $ref = $qpp['recurring'].'value';
        $qpp['recurringhowmany'] = filter_var($_POST[$ref],FILTER_SANITIZE_STRING);
        $ref = $qpp['recurring'].'period';
        $qpp['recurringperiod'] = filter_var($_POST[$ref],FILTER_SANITIZE_STRING);
        if ($qpp['userecurring']) {
            $qpp['use_quantity']=$qpp['usepostage']=$qpp['']=$qpp['useprocess']=$qpp['use_stock']='';
        }
        update_option('qpp_options'.$id, $qpp);
        qpp_admin_notice("The form and submission settings have been updated.");
    }
    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        delete_option('qpp_options'.$id);
        qpp_admin_notice("The form and submission settings have been reset.");
    }
    $qpp_setup = qpp_get_stored_setup();
    $id=$qpp_setup['current'];
    $currency = qpp_get_stored_curr();
    $refradio=$refdropdown=$radio=$dropdown=$optionsradio=$optionsdropdown=$processpercent=$postagepercent=$comma=$D=$W=$Y=$imageabove='';
    $qpp = qpp_get_stored_options($id);
    $$qpp['paypal-location'] = 'checked';
    $$qpp['processtype'] = 'checked';
    $$qpp['postagetype'] = 'checked';
    $$qpp['coupontype'] = 'checked';
    $$qpp['recurring'] = 'checked';
    $$qpp['currency_seperator'] = 'checked';
    $$qpp['refselector'] = 'checked';
    $$qpp['optionselector'] = 'checked';
    $$qpp['selector'] = 'checked';
    $content = '<script>
    jQuery(function() {var qpp_sort = jQuery( "#qpp_sort" ).sortable({ axis: "y" ,
    update:function(e,ui) {
    var order = qpp_sort.sortable("toArray").join();
    jQuery("#qpp_settings_sort").val(order);}
    });});
    </script>';
    $content .='<div class="qpp-settings"><div class="qpp-options">';
    if ($id) $content .='<h2>Form settings for ' . $id . '</h2>';
    else $content .='<h2>Default form settings</h2>';
    $content .= qpp_change_form($qpp_setup);
    $content .= '<form action="" method="POST">
    <p>Paypal form heading (optional)</p>
    <input type="text" style="width:100%" name="title" value="' . $qpp['title'] . '" />
    <p>This is the blurb that will appear below the heading and above the form (optional):</p>
    <input type="text" style="width:100%" name="blurb" value="' . $qpp['blurb'] . '" />
    <h2>Form Fields</h2>
    <p>Drag and drop to change order of the fields</p>
    <div style="margin-left:7px;font-weight:bold;"><div style="float:left; width:30%;">Form Fields</div><div style="float:left; width:30%;">Labels and Options</div></div>
    <div style="clear:left"></div>
    <ul id="qpp_sort">';
    foreach (explode( ',',$qpp['sort']) as $name) {
        switch ( $name ) {
            case 'field1':
            $check = '&nbsp;';
            $type = 'Reference';
            $input = 'inputreference';
            $checked = 'checked';
            $options = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="fixedreference" ' . $qpp['fixedreference'] . ' value="checked" /> Display as a pre-set reference<br><span class="description">Use commas to seperate options: Red,Green, Blue<br>Use semi-colons to combine with amount: Red;$5,Green;$10,Blue;£20</span><br>
            Options Selector: <input style="margin:0; padding:0; border:none;" type="radio" name="refselector" value="refradio" ' . $refradio . ' /> Radio <input style="margin:0; padding:0; border:none;" type="radio" name="refselector" value="refdropdown" ' . $refdropdown . ' /> Dropdown</br>';
            break;
            case 'field2': 
            $check = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="use_stock" ' . $qpp['use_stock'] . ' value="checked" />';
            $type = 'Use Item Number';
            $input = 'stocklabel';
            $checked = $qpp['use_stock'];
            $options = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="fixedstock" ' . $qpp['fixedstock'] . ' value="checked" /> Display as a pre-set item number<br><input type="checkbox" style="margin:0; padding: 0; border: none" name="ruse_stock" ' . $qpp['ruse_stock'] . ' value="checked" /> Required Field';
            break;
            case 'field3': 
            $check = ($qpp['userecurring'] ? '&nbsp;' :'<input type="checkbox"  style="margin:0; padding: 0; border: none" name="use_quantity" ' . $qpp['use_quantity'] . ' value="checked" />');
            $type = 'Quantity';
            $input = 'quantitylabel';
            $checked = $qpp['use_quantity'];
            $options = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="quantitymax" ' . $qpp['quantitymax'] . ' value="checked" /> Display and validate a maximum quantity<br><span class="description">Message that will display on the form:</span><br>
            <input type="text" name="quantitymaxblurb" value="' . $qpp['quantitymaxblurb'] . '" />';
            break;
            case 'field4': 
            $check = '&nbsp;';
            $type = 'Amount';
            $input = 'inputamount';
            $checked = 'checked';
            $options = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="allow_amount" ' . $qpp['allow_amount'] . ' value="checked" /> Do not validate (use default amount value)<br>
            <input type="checkbox" style="margin:0; padding: 0; border: none" name="fixedamount" ' . $qpp['fixedamount'] . ' value="checked" /> Display as a pre-set amount<br><span class="description">Use commas to create an options list</span><br>
            Options Selector: <input style="margin:0; padding:0; border:none;" type="radio" name="selector" value="radio" ' . $radio . ' /> Radio <input style="margin:0; padding:0; border:none;" type="radio" name="selector" value="dropdown" ' . $dropdown . ' /> Dropdown<br>
            <input type="checkbox" style="margin:0; padding: 0; border: none" name="inline_amount" ' . $qpp['inline_amount'] . ' value="checked" />&nbsp;Display inline radio fields<br>
            Symbol separating decimal part of the amount: <input style="margin:0; padding:0; border:none;" type="radio" name="currency_seperator" value="period" ' . $period . ' /> Decimal Point <input style="margin:0; padding:0; border:none;" type="radio" name="currency_seperator" value="comma" ' . $comma . ' /> Comma<br>
            ';
            break;
            case 'field5': 
            $check = $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox"  style="margin:0; padding: 0; border: none" name="use_options" ' . $qpp['use_options'] . ' value="checked" />';
            $type = 'Options';
            $input = 'optionlabel';
            $checked = $qpp['use_options'];
            $options = '<span class="description">Options (separate with a comma):</span><br><textarea  name="optionvalues" label="Radio" rows="2">' . $qpp['optionvalues'] . '</textarea><br>
            Options Selector: <input style="margin:0; padding:0; border:none;" type="radio" name="optionselector" value="optionsradio" ' . $optionsradio . ' /> Radio <input style="margin:0; padding:0; border:none;" type="radio" name="optionselector" value="optionsdropdown" ' . $optionsdropdown . ' /> Dropdown<br>'; 
            break;
            case 'field6': 
            $check = $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox" style="margin:0; padding: 0; border: none" name="usepostage" ' . $qpp['usepostage'] . ' value="checked" />';
            $type = 'Postal charge';
            $input = 'postageblurb';
            $checked = $qpp['usepostage'];
            $options = '<span class="description">Post and Packing charge type:</span><br>
            <input style="margin:0; padding:0; border:none;" type="radio" name="postagetype" value="postagepercent" ' . $postagepercent . ' /> Percentage of the total: <input type="text" style="width:4em;padding:2px" label="postagepercent" name="postagepercent" value="' . $qpp['postagepercent'] . '" /> %<br>
            <input style="margin:0; padding:0; border:none;" type="radio" name="postagetype" value="postagefixed" ' . $postagefixed . ' /> Fixed amount: <input type="text" style="width:4em;padding:2px" label="postagefixed" name="postagefixed" value="' . $qpp['postagefixed'] . '" /> '.$currency[$id]; 
            break;
            case 'field7': 
            $check = $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox" style="margin:0; padding: 0; border: none" name="useprocess" ' . $qpp['useprocess'] . ' value="checked" />';
            $type = 'Processing Charge';
            $input = 'processblurb';
            $checked = $qpp['useprocess'];
            $options = '<span class="description">Payment charge type:</span><br>
            <input style="margin:0; padding:0; border:none;" type="radio" name="processtype" value="processpercent" ' . $processpercent . ' /> Percentage of the total: <input type="text" style="width:4em;padding:2px" label="processpercent" name="processpercent" value="' . $qpp['processpercent'] . '" /> %<br>
            <input style="margin:0; padding:0; border:none;" type="radio" name="processtype" value="processfixed" ' . $processfixed . ' /> Fixed amount: <input type="text" style="width:4em;padding:2px" label="processfixed" name="processfixed" value="' . $qpp['processfixed'] . '" /> '.$currency[$id]; 
            break;
            case 'field8': 
            $check = '<input type="checkbox"  style="margin:0; padding: 0; border: none" name="captcha" ' . $qpp['captcha'] . ' value="checked" />';
            $type = 'Maths Captcha';
            $input = 'mathscaption';
            $checked = $qpp['captcha'];
            $options = '<span class="description">Add a maths checker to the form to (hopefully) block most of the spambots.</span>';
            break;
            case 'field9': 
            $check = $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox" style="margin:0; padding: 0; border: none" name="usecoupon" ' . $qpp['usecoupon'] . ' value="checked" />';
            $type = 'Coupon Code';
            $input = 'couponblurb';$checked = $qpp['usecoupon'];
            $options = '<span class="description">Button label:</span><br>
            <input type="text" name="couponbutton" value="' . $qpp['couponbutton'] . '" /><br>
            <span class="description">Coupon applied message:</span><br>
            <input type="text" name="couponref" value="' . $qpp['couponref'] . '" /><br>
            <a href="?page=quick-paypal-payments/settings.php&tab=coupon">Set coupon codes</a>'; 
            break;
            case 'field10': 
            $check = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="useterms" ' . $qpp['useterms'] . ' value="checked" />';
            $type = 'Terms and Conditions';
            $input = 'termsblurb';$checked = $qpp['termsblurb'];
            $options = '<span class="description">URL of Terms and Conditions:</span><br>
            <input type="text" name="termsurl" value="' . $qpp['termsurl'] . '" /><br>
            <input type="checkbox" style="margin:0; padding: 0; border: none" name="termspage" ' . $qpp['termspage'] . ' value="checked" /> Open link in a new page';
            break;
            case 'field11': 
            $check = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="useblurb" ' . $qpp['useblurb'] . ' value="checked" />';
            $type = 'Additional Information';
            $input = 'extrablurb';$checked = $qpp['useblurb'];
            $options = '<span class="description">Add additional information to your form</span>';
            break;
            case 'field12': 
            $check = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="userecurring" ' . $qpp['userecurring'] . ' value="checked" />';
            $type = 'Recurring Payments';
            $input = 'recurringblurb';
            $checked = $qpp['userecurring'];
            $options = '<p>
            <input type="text" style="width:14em;padding:2px"  name="payments" value="' . $qpp['payments'] . '" /> <input type="text" style="width:3em;padding:2px" name="srt" value="' . $qpp['srt'] . '" /> (max 52)<br> 
            <input type="text" style="width:10em;padding:2px"  name="every" value="' . $qpp['every'] . '" /> <span class="description">Select period below</span></p>
            <table>
            <tr>
            <td><input type="radio" style="margin:0; padding: 0; border: none" name="recurring" value="D" '.$D.' /></td>
            <td><input type="text" style="width:2em;padding:2px" name="Dvalue" value="' . $qpp['Dvalue'] . '" /></td>
            <td><input type="text" style="width:6em;padding:2px" name="Dperiod" value="' . $qpp['Dperiod'] . '" /></td>
            <td>max 90 days</td><td>
            </tr>
            <tr>
            <td><input type="radio" style="margin:0; padding: 0; border: none" name="recurring" value="W" '.$W.' /></td>
            <td><input type="text" style="width:2em;padding:2px" name="Wvalue" value="' . $qpp['Wvalue'] . '" /></td>
            <td><input type="text" style="width:6em;padding:2px" name="Wperiod" value="' . $qpp['Wperiod'] . '" /></td>
            <td>max 52 weeks</td><td>
            </tr>
            <tr>
                <td><input type="radio" style="margin:0; padding: 0; border: none" name="recurring" value="M" '.$M.' /></td><td><input type="text" style="width:2em;padding:2px" name="Mvalue" value="' . $qpp['Mvalue'] . '" /></td>
                <td><input type="text" style="width:6em;padding:2px" name="Mperiod" value="' . $qpp['Mperiod'] . '" /></td><td>max 24 months</td><td>
            </tr>
            <tr>
            <td><input type="radio" style="margin:0; padding: 0; border: none" name="recurring" value="Y" '.$Y.' /></td>
            <td><input type="text" style="width:2em;padding:2px" name="Yvalue" value="' . $qpp['Yvalue'] . '" /></td>
            <td><input type="text" style="width:6em;padding:2px" name="Yperiod" value="' . $qpp['Yperiod'] . '" /></td>
            <td>max 5 years</td><td>
            </tr>
            </table>
            <p><span style="color:red">WARNING!</span> Recurring payments only work if you have a Business or Premier account.<br>Using recurring payments will disable some form fields.</p>';
            break;
            case 'field13': 
            $check = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="useaddress" ' . $qpp['useaddress'] . ' value="checked" />';
            $type = 'Personal Details';
            $input = 'addressblurb';$checked = $qpp['useaddress'];
            $options = '<p><a href="?page=quick-paypal-payments/settings.php&tab=address">Personal details Settings</a></p>';
            break;
            case 'field14':
            $check = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="usetotals" ' . $qpp['usetotals'] . ' value="checked" />';
            $type = 'Show totals';
            $input = 'totalsblurb';$checked = $qpp['usetotals'];
            $options = '<span class="description">Show live totals on your form. Warning: Only works if you have one form on the page and you aren\'t using multiple amounts</span>';
            break;
            case 'field15';
            $check = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="use_slider" ' . $qpp['use_slider'] . ' value="checked" />';
            $type = 'Range slider';
            $input = 'sliderlabel';
            $checked = $qpp['use_slider'];
            $options = 'The range slider replaces the amount field.<br>
            <input type="text" style="border:1px solid #415063; width:3em;" name="min" . value ="' . $qpp['min'] . '" />&nbsp;Minimum value<br>
            <input type="text" style="border:1px solid #415063; width:3em;" name="max" . value ="' . $qpp['max'] . '" />&nbsp;Maximum value<br>
            <input type="text" style="border:1px solid #415063; width:3em;" name="initial" . value ="' . $qpp['initial'] . '" />&nbsp;Initial value<br>
            <input type="text" style="border:1px solid #415063; width:3em;" name="step" . value ="' . $qpp['step'] . '" />&nbspStep';
            break;
            case 'field16': 
            $check = '<input type="checkbox" style="margin:0; padding: 0; border: none" name="useemail" ' . $qpp['useemail'] . ' value="checked" />';


            $type = 'Email Address';
            $input = 'emailblurb';$checked = $qpp['useemail'];
            $options = '<span class="description">Use this to collect the Payees email address.</span>';
            break;
        }
        $li_class = ($checked) ? 'button_active' : 'button_inactive';	
        $content .='<li class="'.$li_class.'" id="'.$name.'">
        <div style="float:left; width:5%;">'.$check.'</div>
        <div style="float:left; width:25%;">'.$type.'</div>
        <div style="float:left; width:65%;"><input type="text" id="'.$name.'" name="'.$input.'" value="' . $qpp[$input] . '" />';
        if ($options) $content .='<br>'.$options;
        $content .='</div>
        <div style="clear:left"></div></li>';
    }
    $content .='</ul>
    <h2>Fixed payment and shortcode labels</h2>
    <p>These are the labels that will display if you are using a fixed reference or amount or shortcode attributes</a>. All the shortcodes are given <a href="http://quick-plugins.com/quick-paypal-payments/paypal-payments-shortcodes/" target="_blank">on this page</a>.</p>
    <p>Label for the payment Reference/ID/Number:</p>
    <input type="text" name="shortcodereference" value="' . $qpp['shortcodereference'] . '" />
    <p>Label for the amount field:</p>
    <input type="text" name="shortcodeamount" value="' . $qpp['shortcodeamount'] . '" />
    <h2>Submit button caption</h2>
    <input type="text" name="submitcaption" value="' . $qpp['submitcaption'] . '" />
    <h2>Reset button</h2>
    <p><input type="checkbox" style="margin:0; padding: 0; border: none" name="use_reset" ' . $qpp['use_reset'] . ' value="checked" /> Show Reset Button</p>
    <input type="text" name="resetcaption" value="' . $qpp['resetcaption'] . '" />
    <h2>PayPal Image</h2>
    <p>Upload an image and select where you want it to display (Leave blank if you don\'t want to use an image).</p>
    <p>Below form title: <input type="radio" label="paypal-location" name="paypal-location" value="imageabove" ' . $imageabove . ' /> Below Submit Button: <input type="radio" label="paypal-location" name="paypal-location" value="imagebelow" ' . $imagebelow . ' /></p>
    <p>
    <input id="qpp_upload_image" type="text" name="paypal-url" value="' . $qpp['paypal-url'] . '" />
    <input id="qpp_upload_media_button" class="button" type="button" value="Upload Image" />
    </p>
    <p><input type="submit" name="qpp_submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form settings?\' );"/></p>
    <input type="hidden" id="qpp_settings_sort" name="sort" value="'.$qpp['sort'].'" />';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>Form Preview</h2>
    <p>Note: The preview form uses the wordpress admin styles. Your form will use the theme styles so won\'t look exactly like the one below.</p>';
    if ($id) $form=' form="'.$id.'"';
    $args = array('form' => $id, 'id' => '', 'amount' => '');
    $content .= qpp_loop($args);
    $content .='<p>There are some more examples of payment forms <a href="http://quick-plugins.com/quick-paypal-payments/paypal-examples/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="http://quick-plugins.com/quick-paypal-payments/paypal-payments-shortcodes/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo $content;
}

function qpp_styles($id) {
    qpp_change_form_update();
    if( isset( $_POST['Submit']) && check_admin_referer("save_qpp")) {
        $options = array(
            'font',
            'font-family',
            'font-size',
            'font-colour',
            'text-font-family',
            'text-font-size',
            'text-font-colour',
            'form-border',
            'input-border',
            'required-border',
            'error-colour',
            'border',
            'width',
            'widthtype',
            'background',
            'backgroundhex',
            'backgroundimage',
            'corners',
            'custom',
            'use_custom',
            'usetheme',
            'styles',
            'submit-colour',
            'submit-background',
            'submit-button',
            'submit-border',
            'submitwidth',
            'submitwidthset',
            'submitposition',
            'coupon-colour',
            'coupon-background',
            'header-type',
            'header-size',
            'header-colour',
            'slider-background',
            'slider-revealed',
            'handle-background',
            'handle-border',
            'output-size',
            'output-colour'
        );
        foreach ( $options as $item) {
            $style[$item] = stripslashes($_POST[$item]);
            $style[$item] = filter_var($style[$item],FILTER_SANITIZE_STRING);
        }
        update_option( 'qpp_style'.$id, $style);
        qpp_create_css_file ('update');
        qpp_admin_notice("The form styles have been updated.");
    }
    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        delete_option('qpp_style'.$id);
        qpp_create_css_file ('update');
        qpp_admin_notice("The form styles have been reset.");
    }
    $percent=$pixel=$none=$plain=$shadow=$roundshadow=$round=$white=$square=$theme=$submitrandom=$submitpixel=$submitright='';    
    $qpp_setup = qpp_get_stored_setup();
    $id=$qpp_setup['current'];
    $style = qpp_get_stored_style($id);
    $$style['font'] = 'checked';
    $$style['widthtype'] = 'checked';
    $$style['submitwidth'] = 'checked';
    $$style['submitposition'] = 'checked';
    $$style['border'] = 'checked';
    $$style['background'] = 'checked';
    $$style['corners'] = 'checked';
    $$style['styles'] = 'checked';
    $$style['header-type'] = 'checked';

    $content ='<div class="qpp-settings"><div class="qpp-options">';
    if ($id) $content .='<h2>Style options for ' . $id . '</h2>';
    else $content .='<h2>Default form style options</h2>';
    $content .= qpp_change_form($qpp_setup);
	$content .= '
    <form method="post" action=""> 
    <p<span<b>Note:</b> Leave fields blank if you don\'t want to use them</span></p>
    <table>
    <tr>
    <td colspan="2"><h2>Form Width</h2></td>
    </tr>
    <tr>
    <td width="30%"></td>
    <td><input style="margin:0; padding:0; border:none;" type="radio" name="widthtype" value="percent" ' . $percent . ' /> 100% (fill the available space)<br />
    <input style="margin:0; padding:0; border:none;" type="radio" name="widthtype" value="pixel" ' . $pixel . ' /> Pixel (fixed): <input type="text" style="width:4em" label="width" name="width" value="' . $style['width'] . '" /> use px, em or %. Default is px.</td>
    </tr>
    <tr>
    <td colspan="2"><h2>Form Border</h2>
    <p>Note: The rounded corners and shadows only work on CSS3 supported browsers and even then not in IE8. Don\'t blame me, blame Microsoft.</p></td
    </tr>
    <tr>
    <td>Type:</td>
    <td><input type="radio" name="border" value="none" ' . $none . ' /> No border<br />
    <input type="radio" name="border" value="plain" ' . $plain . ' /> Plain Border<br />
    <input type="radio" name="border" value="rounded" ' . $rounded . ' /> Round Corners (Not IE8)<br />
    <input type="radio" name="border" value="shadow" ' . $shadow . ' /> Shadowed Border(Not IE8)<br />
    <input type="radio" name="border" value="roundshadow" ' . $roundshadow . ' /> Rounded Shadowed Border (Not IE8)</td>
    </tr>
    <tr>
    <td>Style:</td>
    <td><input type="text" label="form-border" name="form-border" value="' . $style['form-border'] . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>Background</h2></td>
    </tr>
    <tr>
    <td>Colour:</td>
    <td><input type="radio" name="background" value="white" ' . $white . ' /> White<br />
    <input type="radio" name="background" value="theme" ' . $theme . ' /> Use theme colours<br />
    <input style="margin-bottom:5px;" type="radio" name="background" value="color" ' . $color . ' />
    <input type="text" class="qpp-color" label="background" name="backgroundhex" value="' . $style['backgroundhex'] . '" /></td>
    </tr>
    <tr><td>Background<br>Image:</td>
    <td>
    <input id="qpp_background_image" type="text" name="backgroundimage" value="' . $style['backgroundimage'] . '" />
    <input id="qpp_upload_background_image" class="button" type="button" value="Upload Image" /></td>
    </tr>
    <tr><td colspan="2"><h2>Font Styles</h2></td>
    </tr>
    <tr>
    <td></td>
    <td><input style="margin:0; padding:0; border:none" type="radio" name="font" value="theme" ' . $theme . ' /> Use theme font styles<br />
    <input style="margin:0; padding:0; border:none" type="radio" name="font" value="plugin" ' . $plugin . ' /> Use Plugin font styles (enter font family and size below)
    </td>
    </tr>
    <tr>
    <td colspan="2"><h2>Form Header</h2></td>
    </tr>
    <tr>
    <td style="vertical-align:top;">'.__('Header', 'quick-event-manager').'</td>
    <td><input style="margin:0; padding:0; border:none;" type="radio" name="header-type" value="h2" ' . $h2 . ' /> H2 <input style="margin:0; padding:0; border:none;" type="radio" name="header-type" value="h3" ' . $h3 . ' /> H3 <input style="margin:0; padding:0; border:none;" type="radio" name="header-type" value="h4" ' . $h4 . ' /> H4</td>
    </tr>
    <tr>
    <td>Header Size:</td>
    <td><input type="text" style="width:6em" label="header-size" name="header-size" value="' . $style['header-size'] . '" /></td>
    </tr>
    <tr><td>Header Colour:</td>
    <td><input type="text" class="qpp-color" label="header-colour" name="header-colour" value="' . $style['header-colour'] . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>Input fields</h2></td>
    </tr>
    <tr>
    <td>Font Family: </td>
    <td><input type="text" label="font-family" name="font-family" value="' . $style['font-family'] . '" /></td>
    </tr>
    <tr>
    <td>Font Size: </td>
    <td><input type="text" label="font-size" name="font-size" value="' . $style['font-size'] . '" /></td>
    </tr>
    <tr>
    <td>Font Colour: </td>
    <td><input type="text" class="qpp-color" label="font-colour" name="font-colour" value="' . $style['font-colour'] . '" /></td
    </tr>
    <tr>
    <td>Normal Border: </td>
    <td><input type="text" label="input-border" name="input-border" value="' . $style['input-border'] . '" /></td>
    </tr>
    <tr>
    <td>Required Border: </td>
    <td><input type="text" name="required-border" value="' . $style['required-border'] . '" /></td>
    </tr>
    <tr>
    <td>Error Colour: </td>
    <td><input type="text" class="qpp-color" name="error-colour" value="' . $style['error-colour'] . '" /></td>
    </tr>
    <tr>
    <td>Corners: </td>
    <td><input style="margin:0; padding:0; border:none;" type="radio" name="corners" value="corner" ' . $corner . ' /> Use theme settings<br />
    <input style="margin:0; padding:0; border:none;" type="radio" name="corners" value="square" ' . $square . ' /> Square corners<br />
    <input style="margin:0; padding:0; border:none;" type="radio" name="corners" value="round" ' . $round . ' /> 5px rounded corners</td></tr>
    <tr>
    <td colspan="2"><h2>Apply Coupon Button</h2></td>
    </tr>
    <tr>
    <td>Font Colour: </td>
    <td><input type="text" class="qpp-color" label="coupon-colour" name="coupon-colour" value="' . $style['coupon-colour'] . '" /></td>
    </tr>
    <tr>
    <td>Background: </td>
    <td><input type="text" class="qpp-color" label="coupon-background" name="coupon-background" value="' . $style['coupon-background'] . '" /><br>Other settings are the same as the Submit Button</td>
    </tr>		
    <tr>
    <td colspan="2"><h2>Other text content</h2></td>
    </tr>
    <tr>
    <td>Font Family: </td>
    <td><input type="text" label="text-font-family" name="text-font-family" value="' . $style['text-font-family'] . '" /></td>
    </tr>
    <tr>
    <td>Font Size: </td>
    <td><input type="text" style="width:6em" label="text-font-size" name="text-font-size" value="' . $style['text-font-size'] . '" /></td>
    </tr>
    <tr>
    <td>Font Colour: </td>
    <td><input type="text" class="qpp-color" label="text-font-colour" name="text-font-colour" value="' . $style['text-font-colour'] . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>Submit Button</h2></td>
    </tr>
    <tr>
    <td>Font Colour:</td>
    <td><input type="text" class="qpp-color" label="submit-colour" name="submit-colour" value="' . $style['submit-colour'] . '" /></td></tr>
    <tr>
    <td>Background:</td>
    <td><input type="text" class="qpp-color" label="submit-background" name="submit-background" value="' . $style['submit-background'] . '" /></td></tr>
    <tr>
    <td>Border:</td>
    <td><input type="text" label="submit-border" name="submit-border" value="' . $style['submit-border'] . '" /></td></tr>
    <tr>
    <td>Size:</td>
    <td><input style="margin:0; padding:0; border:none;" type="radio" name="submitwidth" value="submitpercent" ' . $submitpercent . ' /> Same width as the form<br />
    <input style="margin:0; padding:0; border:none;" type="radio" name="submitwidth" value="submitrandom" ' . $submitrandom . ' /> Same width as the button text<br />
    <input style="margin:0; padding:0; border:none;" type="radio" name="submitwidth" value="submitpixel" ' . $submitpixel . ' /> Set your own width: <input type="text" style="width:5em" label="submitwidthset" name="submitwidthset" value="' . $style['submitwidthset'] . '" /> (px, % or em)</td></tr>
    <tr>
    <td>Position:</td>
    <td><input style="margin:0; padding:0; border:none;" type="radio" name="submitposition" value="submitleft" ' . $submitleft . ' /> Left <input style="margin:0; padding:0; border:none;" type="radio" name="submitposition" value="submitright" ' . $submitright . ' /> Right</td>
    </tr>
    <tr>
    <td>Button Image: </td><td>
    <input id="qpp_submit_button" type="text" name="submit-button" value="' . $style['submit-button'] . '" />
    <input id="qpp_upload_submit_button" class="button-secondary" type="button" value="Upload Image" /></td></tr>
    <tr>
    <td colspan="2"><h2>Slider</h2></td>
    </tr>
    <tr>
    <td>Normal Background</td>
    <td><input type="text" class="qpp-color" label="input-border" name="slider-background" value="' . $style['slider-background'] . '" /></td>
    </tr>
    <tr>
    <td>Revealed Background</td>
    <td><input type="text" class="qpp-color" label="input-border" name="slider-revealed" value="' . $style['slider-revealed'] . '" /></td>
    </tr>
    <tr>
    <td>Handle Background</td>
    <td><input type="text" class="qpp-color" label="input-border" name="handle-background" value="' . $style['handle-background'] . '" /></td>
    </tr>
    <tr>
    <td>Handle Border</td>
    <td><input type="text" class="qpp-color" label="input-border" name="handle-border" value="' . $style['handle-border'] . '" /></td>
    </tr>
    <tr>
    <td>Output Size</td>
    <td><input type="text" style="width:5em" label="input-border" name="output-size" value="' . $style['output-size'] . '" /></td>
    </tr>
    <tr>
    <td>Output Colour</td>
    <td><input type="text" class="qpp-color" label="input-border" name="output-colour" value="' . $style['output-colour'] . '" /></td>
    </tr>
    </table>

    <h2>Custom CSS</h2>
    <p><input type="checkbox" style="margin:0; padding: 0; border: nocapne" name="use_custom" ' . $style['use_custom'] . ' value="checked" /> Use Custom CSS</p>
    <p><textarea style="width:100%; height: 200px" name="custom">' . $style['custom'] . '</textarea></p>
    <p>To see all the styling use the <a href="'.get_admin_url().'plugin-editor.php?file=quick-paypal-payments/quick-paypal-payments.css">CSS editor</a>.</p>
    <p>The main style wrapper is the <code>.qpp-style</code> id.</p>
    <p>The form borders are: #none, #plain, #rounded, #shadow, #roundshadow.</p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form styles?\' );"/></p>';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;"> <h2>Test Form</h2>
    <p>Not all of your style selections will display here (because of how WordPress works). So check the form on your site.</p>';
    if ($id) $form=' form="'.$id.'"';
    $args = array('form' => $id, 'id' => '', 'amount' => '');
$content .= qpp_loop($args);
    $content .='<p>There are some more examples of payment forms <a href="http://quick-plugins.com/quick-paypal-payments/paypal-examples/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="http://quick-plugins.com/quick-paypal-payments/paypal-payments-shortcodes/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo $content;
}

function qpp_send_page($id) {
    qpp_change_form_update();
    if( isset( $_POST['Submit']) && check_admin_referer("save_qpp")) {
        $options = array(
            'waiting',
            'use_lc',
            'lc',
            'customurl',
            'cancelurl',
            'thanksurl',
            'target',
            'email',
            'donate',
            'combine',
            'confirmmessage',
            'google_onclick'
        );
        foreach ($options as $item) {
            $send[$item] = stripslashes( $_POST[$item]);
            $send[$item] = filter_var($send[$item],FILTER_SANITIZE_STRING);
        }
        update_option('qpp_send'.$id, $send);
        qpp_admin_notice("The submission settings have been updated.");
    }
    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        delete_option('qpp_send'.$id);
        qpp_admin_notice("The submission settings have been reset.");
    }
    $qpp_setup = qpp_get_stored_setup();
    $id=$qpp_setup['current'];
    $newpage=$customurl='';
    $send = qpp_get_stored_send($id);
    $$send['target'] = 'checked';
    $$send['lc'] = 'selected';
    qpp_create_css_file ('update');
    $content ='<div class="qpp-settings"><div class="qpp-options">';
    if ($id) $content .='<h2>Send settings for ' . $id . '</h2>';
    else $content .='<h2>Default form send options</h2>';
    $content .= qpp_change_form($qpp_setup);
    $content .= '
    <form action="" method="POST">
    <h2>Submission Message</h2>
    <p>This is what the visitor sees while the paypal page loads</p>
    <input type="text" style="width:100%" name="waiting" value="' . $send['waiting'] . '" />
    <h2>Force Locale</h2>
    <p clsss="description">This may or may not work, Paypal has some very strange rule regarding language</p>
    <p><input type="checkbox" style="margin:0; padding: 0; border: none" name="use_lc" ' . $send['use_lc'] . ' value="checked" /> Use Locale</p>
    <select name="lc">
    <option value="AU" '.$AU.'>Australia</option>
    <option value="AT" '.$AT.'>Austria</option>
    <option value="BE" '.$BE.'>Belgium</option>
    <option value="BR" '.$BR.'>Brazil</option>
    <option value="pt_BR" '.$pt_BR.'>Brazilian Portuguese (for Portugal and Brazil only)</option>
    <option value="CA" '.$CA.'>Canada</option>
    <option value="CH" '.$CH.'>Switzerland</option>
    <option value="CN" '.$CN.'>China</option>
    <option value="da_DK" '.$da_DK.'>Danish (for Denmark only)</option>
    <option value="FR" '.$FR.'>France</option>
    <option value="DE" '.$DE.'>Germany</option>
    <option value="he_IL" '.$he_IL.'>Hebrew (all)</option>
    <option value="id_ID" '.$id_ID.'>Indonesian (for Indonesia only)</option>
    <option value="IT" '.$IT.'>Italy</option>
    <option value="ja_JP" '.$ja_JP.'>Japanese (for Japan only)</option>
    <option value="NL" '.$NL.'>Netherlands</option>
    <option value="no_NO" '.$no_NO.'>Norwegian (for Norway only)</option>
    <option value="PL" '.$PL.'>Poland</option>
    <option value="PT" '.$PT.'>Portugal</option>
    <option value="RU" '.$RU.'>Russia</option>
    <option value="ru_RU" '.$ru_RU.'>Russian (for Lithuania, Latvia, and Ukraine only)</option>
    <option value="zh_CN" '.$zh_CN.'>Simplified Chinese (for China only)</option>
    <option value="zh_HK" '.$zh_HK.'>Traditional Chinese (for Hong Kong only)</option>
    <option value="zh_TW" '.$zh_TW.'>Traditional Chinese (for Taiwan only)</option>
    <option value="ES" '.$ES.'>Spain</option>
    <option value="sv_SE" '.$sv_SE.'>Swedish (for Sweden only)</option>
    <option value="th_TH" '.$th_TH.'>Thai (for Thailand only)</option>
    <option value="tr_TR" '.$tr_TR.'>Turkish (for Turkey only)</option>
    <option value="GB" '.$GB.'>United Kingdom</option>
    <option value="US" '.$US.'>United States</option>
    </select>
    <h2>Cancel and Thank you pages</h2>
    <p>If you leave these blank paypal will return the user to the current page.</p>
    <p>URL of cancellation page</p>
    <input type="text" style="width:100%" name="cancelurl" value="' . $send['cancelurl'] . '" />
    <p>URL of thank you page</p>
    <input type="text" style="width:100%" name="thanksurl" value="' . $send['thanksurl'] . '" />
    <h2>Confirmation Message</h2>
    <p><input type="checkbox" style="margin:0; padding: 0; border: none" name="confirmmessage" ' . $send['confirmmessage'] . ' value="checked" /> Send yourself a copy of the payment details.</p>
    <p>You can sen the payee a confirmation message using the <a href="?page=quick-paypal-payments/settings.php&tab=autoresponce">Auto Responder</a> options.</p>
    <h2>Custom Paypal Settings</h2>
    <p><input type="checkbox" style="margin:0; padding: 0; border: none" name="donate" ' . $send['donate'] . ' value="checked" /> Form is for donations only</p>
    <p><input type="checkbox" style="margin:0; padding: 0; border: none" name="combine" ' . $send['combine'] . ' value="checked" /> Include Postage and Processing in the amount to pay.</p>
    <p>If you have a custom PayPal page enter the URL here. Leave blank to use the standard PayPal payment page</p>
    <p><input type="text" style="width:100%" name="customurl" value="' . $send['customurl'] . '" /></p>
    <p>Alternate PayPal email address:</p>
    <p><input type="text" style="width:100%" name="email" value="' . $send['email'] . '" /></p>
    <p><input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="target" value="current" ' . $current . ' /> Open in existing page<br>
    <input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="target" value="newpage" ' . $newpage . ' /> Open link in new page/tab <span class="description">This is very browser dependant. Use with caution!</span></p>
    <h2>Google onClick Event</h2>
    <p><input type="text" style="width:100%" name="google_onclick" value="' . $send['google_onclick'] . '" /></p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form settings?\' );"/></p>';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;"> <h2>Form Preview</h2>
    <p>Note: The preview form uses the wordpress admin styles. Your form will use the theme styles so won\'t look exactly like the one below.</p>';
    if ($id) $form=' form="'.$id.'"';
        $args = array('form' => $id, 'id' => '', 'amount' => '');
    $content .= qpp_loop($args);
    $content .='<p>There are some more examples of payment forms <a href="http://quick-plugins.com/quick-paypal-payments/paypal-examples/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="http://quick-plugins.com/quick-paypal-payments/paypal-payments-shortcodes/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo $content;
}

function qpp_error_page($id) {
    qpp_change_form_update();
    if( isset( $_POST['Submit']) && check_admin_referer("save_qpp")) {
        $options = array('errortitle','errorblurb');
        foreach ( $options as $item) {
            $error[$item] = stripslashes($_POST[$item]);
            $error[$item] = filter_var($error[$item],FILTER_SANITIZE_STRING);
        }
        update_option( 'qpp_error'.$id, $error );
        qpp_admin_notice("The error settings have been updated.");
    }
    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        delete_option('qpp_error'.$id);
        qpp_admin_notice("The error messages have been reset.");
    }
    $qpp_setup = qpp_get_stored_setup();
    $id=$qpp_setup['current'];
    $error = qpp_get_stored_error($id);
    qpp_create_css_file ('update');
    $content ='<div class="qpp-settings"><div class="qpp-options">';
    if ($id) $content .='<h2>Eror message settings for ' . $id . '</h2>';
    else $content .='<h2>Default form error message</h2>';
    $content .= qpp_change_form($qpp_setup);
    $content .= '<form method="post" action="">
    <p<span<b>Note:</b> Leave fields blank if you don\'t want to use them</span></p>
    <table>
    <tr>
    <td>Error header</td>
    <td><input type="text"  style="width:100%" name="errortitle" value="' . $error['errortitle'] . '" /></td>
    </tr>
    <tr>
    <td>Error message</td>
    <td><input type="text" style="width:100%" name="errorblurb" value="' . $error['errorblurb'] . '" /></td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the error message?\' );"/></p>';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>Error Checker</h2>
    <p>Try sending a blank form to test your error messages.</p>';
    if ($id) $form=' form="'.$id.'"';
        $args = array('form' => $id, 'id' => '', 'amount' => '');
    $content .= qpp_loop($args);
    $content .='<p>There are some more examples of payment forms <a href="http://quick-plugins.com/quick-paypal-payments/paypal-examples/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="http://quick-plugins.com/quick-paypal-payments/paypal-payments-shortcodes/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo $content;
}

function qpp_ipn_page() {
    if( isset( $_POST['Submit']) && check_admin_referer("save_qpp")) {
        $options = array('ipn','paid','title');
        foreach ( $options as $item) {
            $ipn[$item] = stripslashes($_POST[$item]);
            $ipn[$item] = filter_var($ipn[$item],FILTER_SANITIZE_STRING);
        }
        update_option( 'qpp_ipn', $ipn );
        qpp_admin_notice("The IPN settings have been updated.");
    }
    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        delete_option('qpp_ipn');
        qpp_admin_notice("The IPN settings have been reset.");
    }
    $ipn = qpp_get_stored_ipn();
    $content ='<div class="qpp-settings"><div class="qpp-options">
	<h2>Instant Payment Notifications</h2>
	<form method="post" action="">
    <p>IPN only works if you have a PayPal Business or Premier account and IPN has been set up on that account.</p>
    <p>See the <a href="https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNSetup/">PayPal IPN Integration Guide</a> for more information on how to set up IPN.</p>
    <p>The IPN listener URL you will need is:<pre>'.site_url('/?qpp_ipn').'</pre></p>
    <p>To check completed payments click on the <b>Payments</b> link in your dashboard menu or <a href="?page=quick-paypal-payments/quick-paypal-messages.php">click here</a>.</p>
    <table>
    <tr>
    <td><input type="checkbox" style="margin:0; padding: 0; border: none" name="ipn" ' . $ipn['ipn'] . ' value="checked" /></td>
    <td colspan="2"> Enable IPN.</td>
    </tr>
    <tr>
    <td></td>
    <td>Payment Report Column header:</td>
    <td><input type="text"  style="width:100%" name="title" value="' . $ipn['title'] . '" /></td>
    </tr>
    <tr>
    <td></td>
    <td>Payment Complete Label:</td>
    <td><input type="text"  style="width:100%" name="paid" value="' . $ipn['paid'] . '" /></td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the IPN settings?\' );"/></p>';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>IPN Simulation</h2>
    <p>IPN can be blocked or resticted by your server settings, theme or other plugins. The good news is you can simulate the notifications to check if all is working.</p>
    <p>To carry out a simulation:</p>
    <ol>
    <li>Enable the PayPal Sandbox on the <a href="?page=quick-paypal-payments/settings.php&tab=setup">plugin setup page</a></li>
    <li>Fill in and send your payment form (you do not need to make an actual payment)</li>
    <li>Go to the <a href="?page=quick-paypal-payments/quick-paypal-messages.php">Payments Report</a> and copy the long number in the last column from the payment you have just made</li>
    <li>Go to the IPN simulation page: <a href="https://developer.paypal.com/developer/ipnSimulator" target="_blank">https://developer.paypal.com/developer/ipnSimulator</a></li>
    <li>Login and enter the IPN listener URL</li>
    <li>Select \'Express Checkout\' from the drop down</li>
    <li>Scroll to the bottom of the page and enter the long number you copied at step 3 into the \'Custom\' field</li>
    <li>Click \'Send IPN\'. Scroll up the page and you should see an \'IPN Verified\' message.</li>
    <li>Go back to your Payments Report and refresh, you should now see the payment completed message</li>
    </ol>
    </div>
    </div>';
	echo $content;
}

function qpp_autoresponce_page($id) {
    qpp_change_form_update();
    if( isset( $_POST['Submit']) && check_admin_referer("save_qpp")) {
        $options = array(
            'enable',
            'whenconfirm',
            'fromname',
            'fromemail',
            'subject',
            'message',
            'paymentdetails'
        );
        foreach ( $options as $item) {
            $auto[$item] = stripslashes($_POST[$item]);
        }
        update_option( 'qpp_autoresponder'.$id, $auto );
        if ($id) qpp_admin_notice("The autoresponder settings for " . $id . " have been updated.");
        else qpp_admin_notice("The default form autoresponder settings have been updated.");
    }
    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        delete_option('qpp_autoresponder'.$id);
        qpp_admin_notice("The autoresponder settings for the form called ".$id. " have been reset.");
    }
	
    $qpp_setup = qpp_get_stored_setup();
    $id=$qpp_setup['current'];
    $qpp = qpp_get_stored_options($id);
    $auto = qpp_get_stored_autoresponder($id);
    $$auto['whenconfirm'] = 'checked';
    $message = $auto['message'];
    $content ='<div class="qpp-settings"><div class="qpp-options" style="width:90%;">';
    if ($id) $content .='<h2 style="color:#B52C00">Autoresponse settings for ' . $id . '</h2>';
    else $content .='<h2 style="color:#B52C00">Default form autoresponse settings</h2>';
    $content .= qpp_change_form($qpp_setup);
    $content .='<p>The auto responder sends a confirmation message to the Payee. Use the editor below to send links, images and anything else you normally add to a post or page.</p>
    <p class="description">Note that the autoresponder only works if you collect an email address on the <a href="?page=quick-paypal-payments/settings.php&tab=settings">Form Settings</a>.</p>
    <form method="post" action="">
    <p><input type="checkbox" style="margin: 0; padding: 0; border: none;" name="enable"' . $auto['enable'] . ' value="checked" /> Enable Auto Responder</p> 
    <p><input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="whenconfirm" value="aftersubmission" ' . $aftersubmission . ' /> After submission to PayPal<br>
    <input style="width:20px; margin: 0; padding: 0; border: none;" type="radio" name="whenconfirm" value="afterpayment" ' . $afterpayment . ' /> After payment (only works if <a href="?page=quick-paypal-payments/settings.php&tab=ipn">IPN</a> is active)</span></p>
    <p>From Name (<span class="description">Defaults to your <a href="'. get_admin_url().'options-general.php">Site Title</a> if left blank.</span>):<br>
    <input type="text" style="width:50%" name="fromname" value="' . $auto['fromname'] . '" /></p>
    <p>From Email (<span class="description">Defaults to the your <a href="?page=quick-paypal-payments/settings.php&tab=setup">PayPal email address</a> if left blank.</span>):<br>
    <input type="text" style="width:50%" name="fromemail" value="' . $auto['fromemail'] . '" /></p>
    <p>Subject</p>
    <input style="width:100%" type="text" name="subject" value="' . $auto['subject'] . '"/><br>
    <p>Message Content</p>';
    echo $content;
    wp_editor($message, 'message', $settings = array('textarea_rows' => '20','wpautop'=>false));
    $content = '<p>You can use the following shortcodes in the message body:</p>
    <table>
    <tr>
    <th>Shortcode</th>
    <th>Replacement Text</th>
    </tr>
    <tr>
    <td>[firstname]</td>
    <td>The registrants first name if you are using the <a href="?page=quick-paypal-payments/settings.php&tab=address">personal details</a> option.</td>
    </tr>
    <tr>
    <td>[name]</td>
    <td>The registrants first and last name if you are using the <a href="?page=quick-paypal-payments/settings.php&tab=address">personal details</a> option.</td>
    </tr>
    <tr>
    <td>[reference]</td>
    <td>The name of the item being purchased</td>
    </tr>
    <tr>
    <td>[amount]</td>
    <td>The total amount to be paid without the currency symbol</td>
    </tr>
    <tr>
    <td>[fullamount]</td>
    <td>The total amount to be paid with currency symbol</td>
    </tr>
    <tr>
    <td>[quantity]</td>
    <td>The number of items purchased</td>
    </tr>
    <tr>
    <td>[option]</td>
    <td>The option selected</td>
    </tr>
    <tr>
    <td>[stock]</td>
    <td>The stock, SKU or item number</td>
    </tr>
    <tr>
    <td>[details]</td>
    <td>The payment information (reference, quantity, options, stock number, amount)</td>
    </tr>
    </table>
    <p><input type="checkbox" style="margin: 0; padding: 0; border: none;" name="paymentdetails"' . $auto['paymentdetails'] . ' value="checked" /> Add payment details to the message</p> 
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the error settings for '.$id.'?\' );"/></p>';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>
    </div>
    </div>';
    echo $content;
}

function qpp_address($id) {
    qpp_change_form_update();
    if( isset( $_POST['Submit']) && check_admin_referer("save_qpp")) {
        $options = array(
            'useaddress',
            'firstname',
            'lastname',
            'email',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b',
            'rfirstname',
            'rlastname',
            'remail',
            'raddress1',
            'raddress2',
            'rcity',
            'rstate',
            'rzip',
            'rcountry',
            'rnight_phone_b'
        );
        foreach ( $options as $item) {
            $address[$item] = stripslashes($_POST[$item]);
            $address[$item] = filter_var($address[$item],FILTER_SANITIZE_STRING);
        }
        update_option( 'qpp_address'.$id, $address );
        qpp_admin_notice("The form settings have been updated.");
    }
    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        delete_option('qpp_error'.$id);
        qpp_admin_notice("The form settings have been reset.");
    }
    $qpp_setup = qpp_get_stored_setup();
    $id=$qpp_setup['current'];
    $address = qpp_get_stored_address($id);
    $content ='<div class="qpp-settings"><div class="qpp-options">';
    if ($id) $content .='<h2>Personal Information Fields for ' . $id . '</h2>';
    else $content .='<h2>Personal Information Fields</h2>';
    $content .= qpp_change_form($qpp_setup);
    $content .= '<form method="post" action="">
    <p class="description">Note: The information will be collected and saved and passed to PayPal but usage is dependant on browser and user settings. Which means they may have to fill in the information again when they get to PayPal</p>
    <p>1. Delete labels for fields you do not want to use.</p>
    <p>2. Check the <b>R</b> box for madatory/required fields.</p>
    <table>
    <tr>
    
    <th>Field</th>
    <th>Label</th>
    <th>R</th>
    </tr>
    <tr>
    
    <td width="20%">First Name</td>
    <td><input type="text"  style="width:100%" name="firstname" value="' . $address['firstname'] . '" /></td>
    <td width="5%"><input type="checkbox" name="rfirstname" ' . $address['rfirstname'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Last Name</td>
    <td><input type="text"  style="width:100%" name="lastname" value="' . $address['lastname'] . '" /></td>
    <td><input type="checkbox" name="rlastname" ' . $address['rlastname'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Email</td>
    <td><input type="text" style="width:100%" name="email" value="' . $address['email'] . '" /></td>
    <td><input type="checkbox" name="remail" ' . $address['remail'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Address Line 1</td>
    <td><input type="text" style="width:100%" name="address1" value="' . $address['address1'] . '" /></td>
    <td><input type="checkbox" name="raddress1" ' . $address['raddress1'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Address Line 2</td>
    <td><input type="text" style="width:100%" name="address2" value="' . $address['address2'] . '" /></td>
    <td><input type="checkbox" name="raddress2" ' . $address['raddress2'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>City</td>
    <td><input type="text" style="width:100%" name="city" value="' . $address['city'] . '" /></td>
    <td><input type="checkbox" name="rcity" ' . $address['rcity'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>State</td>
    <td><input type="text" style="width:100%" name="state" value="' . $address['state'] . '" /></td>
    <td><input type="checkbox" name="rstate" ' . $address['rstate'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Zip</td>
    <td><input type="text" style="width:100%" name="zip" value="' . $address['zip'] . '" /></td>
    <td><input type="checkbox" name="rzip" ' . $address['rzip'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Country</td>
    <td><input type="text" style="width:100%" name="country" value="' . $address['country'] . '" /></td>
    <td><input type="checkbox" name="rcountry" ' . $address['rcountry'] . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Phone</td>
    <td><input type="text" style="width:100%" name="night_phone_b" value="' . $address['night_phone_b'] . '" /></td>
    <td><input type="checkbox" name="rnight_phone_b" ' . $address['rnight_phone_b'] . ' value="checked" /></td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the error message?\' );"/></p>';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>Example Form</h2>';
    if ($id) $form=' form="'.$id.'"';
        $args = array('form' => $id, 'id' => '', 'amount' => '');
    $content .= qpp_loop($args);
    $content .='<p>There are some more examples of payment forms <a href="http://quick-plugins.com/quick-paypal-payments/paypal-examples/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="http://quick-plugins.com/quick-paypal-payments/paypal-payments-shortcodes/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo $content;
}

function qpp_coupon_codes($id) {
    qpp_change_form_update();
    if( isset( $_POST['Submit']) && check_admin_referer("save_qpp")) {
        $arr = array('couponnumber','couponget','duplicate','couponerror');
        foreach ($arr as $item) {
            $coupon[$item] = stripslashes($_POST[$item]);
            $coupon[$item] = filter_var($coupon[$item],FILTER_SANITIZE_STRING);
        }
        $options = array('code','coupontype','couponpercent','couponfixed');
        if ($coupon['couponnumber'] < 1) $coupon['couponnumber'] = 1;
        for ($i=1; $i<=$coupon['couponnumber']; $i++) {
            foreach ( $options as $item) $coupon[$item.$i] = stripslashes($_POST[$item.$i]);
            if (!$coupon['coupontype'.$i]) $coupon['coupontype'.$i] = 'percent'.$i;
            if (!$coupon['couponpercent'.$i]) $coupon['couponpercent'.$i] = '10';
            if (!$coupon['couponfixed'.$i]) $coupon['couponfixed'.$i] = '5';
        }
        update_option( 'qpp_coupon'.$id, $coupon );
        if ($coupon['duplicate']) {
            $qpp_setup = qpp_get_stored_setup();
            $arr = explode(",",$qpp_setup['alternative']);
            foreach ($arr as $item) update_option( 'qpp_coupon'.$item, $coupon );
        }
        qpp_admin_notice("The coupon settings have been updated.");
    }
    if( isset( $_POST['Reset']) && check_admin_referer("save_qpp")) {
        delete_option('qpp_coupon'.$id);
        qpp_admin_notice("The coupon settings have been reset.");
    }
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $currency = qpp_get_stored_curr();
    $before = array(
        'USD'=>'&#x24;',
        'CDN'=>'&#x24;',
        'EUR'=>'&euro;',
        'GBP'=>'&pound;',
        'JPY'=>'&yen;',
        'AUD'=>'&#x24;',
        'BRL'=>'R&#x24;',
        'HKD'=>'&#x24;',
        'ILS'=>'&#x20aa;',
        'MXN'=>'&#x24;',
        'NZD'=>'&#x24;',
        'PHP'=>'&#8369;',
        'SGD'=>'&#x24;',
        'TWD'=>'NT&#x24;',
        'TRY'=>'&pound;');
    $after = array(
        'CZK'=>'K&#269;',
        'DKK'=>'Kr',
        'HUF'=>'Ft',
        'MYR'=>'RM',
        'NOK'=>'kr',
        'PLN'=>'z&#322',
        'RUB'=>'&#1056;&#1091;&#1073;',
        'SEK'=>'kr',
        'CHF'=>'CHF',
        'THB'=>'&#3647;');
    foreach($before as $item=>$key) {if ($item == $currency[$id]) $b = $key;}
    foreach($after as $item=>$key) {if ($item == $currency[$id]) $a = $key;}
    $coupon = qpp_get_stored_coupon($id);
    $content ='<div class="qpp-settings"><div class="qpp-options">';
    if ($id) $content .='<h2>Coupons codes for ' . $id . '</h2>';
    else $content .='<h2>Default form coupons codes</h2>';
    $content .= qpp_change_form($qpp_setup);
    $content .= '<form method="post" action="">
    <p<span<b>Note:</b> Leave fields blank if you don\'t want to use them</span></p>
    <p>Number of Coupons: <input type="text" name="couponnumber" value="'.$coupon['couponnumber'].'" style="width:4em"></p>
    <table>
    <tr><td>Coupon Code</td><td>Percentage</td><td>Fixed Amount</td></tr>';
    for ($i=1; $i<=$coupon['couponnumber']; $i++) {
        $percent = ($coupon['coupontype'.$i] == 'percent'.$i ? 'checked' : '');
        $fixed = ($coupon['coupontype'.$i] == 'fixed'.$i ? 'checked' : ''); 
        $content .= '<tr><td><input type="text" name="code'.$i.'" value="' . $coupon['code'.$i] . '" /></td>
        <td><input style="margin:0; padding:0; border:none;" type="radio" name="coupontype'.$i.'" value="percent'.$i.'" ' . $percent . ' /> <input type="text" style="width:4em;padding:2px" label="couponpercent'.$i.'" name="couponpercent'.$i.'" value="' . $coupon['couponpercent'.$i] . '" /> %</td>
        <td><input style="margin:0; padding:0; border:none;" type="radio" name="coupontype'.$i.'" value="fixed'.$i.'" ' . $fixed.' />&nbsp;'.$b.'&nbsp;<input type="text" style="width:4em;padding:2px" label="couponfixed'.$i.'" name="couponfixed'.$i.'" value="' . $coupon['couponfixed'.$i] . '" /> '.$a.'</td></tr>';
    }
    $content .= '</table>
    <h2>Invalid Coupon Code Message</h2>
    <input id="couponerror" type="text" name="couponerror" value="' . $coupon['couponerror'] . '" /></p>
    <h2>Coupon Code Autofill</h2>
    <p>You can add coupon codes to URLs which will autofill the field. The URL format is: mysite.com/mypaymentpage/?coupon=code. The code you set will appear on the form with the following caption:<br>
    <input id="couponget" type="text" name="couponget" value="' . $coupon['couponget'] . '" /></p>
    <h2>Clone Coupon Settings</h2>
    <p><input type="checkbox" style="margin:0; padding: 0; border: none" name="duplicate" ' . $coupon['duplicate'] . ' value="checked" /> Duplicate coupon codes across all forms</p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the coupon codes?\' );"/></p>';
    $content .= wp_nonce_field("save_qpp");
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>Coupon Check</h2>
    <p>Test your coupon codes.</p>';
    if ($id) $form=' form="'.$id.'"';
        $args = array('form' => $id, 'id' => '', 'amount' => '');
    $content .= qpp_loop($args);
    $content .='<p>There are some more examples of payment forms <a href="http://quick-plugins.com/quick-paypal-payments/paypal-examples/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="http://quick-plugins.com/quick-paypal-payments/paypal-payments-shortcodes/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo $content;
}

function qpp_delete_everything() {
    $qpp_setup = qpp_get_stored_setup();
    $arr = explode(",",$qpp_setup['alternative']);
    foreach ($arr as $item) qpp_delete_things($item);
    qpp_delete_things('');
    delete_option('qpp_setup');
    delete_option('qpp_curr');
    delete_option('qpp_message');
}

function qpp_delete_things($id) {
    delete_option('qpp_options'.$id);
    delete_option('qpp_send'.$id);
    delete_option('qpp_error'.$id);
    delete_option('qpp_style'.$id);
}

function qpp_change_form($qpp_setup) {
    $content = '';
    if ($qpp_setup['alternative']) {
        $content .= '<form style="margin-top: 8px" method="post" action="" >';
        $arr = explode(",",$qpp_setup['alternative']);
        sort($arr);
        foreach ($arr as $item) {
            if ($qpp_setup['current'] == $item) $checked = 'checked'; else $checked = '';
            if ($item == '') {$formname = 'default'; $item='';} else $formname = $item;
            $content .='<input style="margin:0; padding:0; border:none" type="radio" name="current" value="' .$item . '" ' .$checked . ' /> '.$formname . ' ';
        }
        $content .='<input type="hidden" name="alternative" value = "' . $qpp_setup['alternative'] . '" />
        <input type="hidden" name="email" value = "' . $qpp_setup['email'] . '" />&nbsp;&nbsp;
        <input type="submit" name="Select" class="button-secondary" value="Select Form" />
        </form>';
    }
    return $content;
}

function qpp_change_form_update() {
    if( isset( $_POST['Select'])) {
        $qpp_setup['current'] = $_POST['current'];
        $qpp_setup['alternative'] = $_POST['alternative'];
        $qpp_setup['email'] = $_POST['email'];
        update_option( 'qpp_setup', $qpp_setup);
    }
}

function qpp_generate_csv() {
    $qpp_setup = qpp_get_stored_setup();
    if(isset($_POST['download_qpp_csv'])) {
        $id = $_POST['formname'];
        $filename = urlencode($id.'.csv');
        if ($id == '') $filename = urlencode('default.csv');
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename="'.$filename.'"');
        header( 'Content-Type: text/csv');$outstream = fopen("php://output",'w');
        $message = get_option( 'qpp_messages'.$id );
        $messageoptions = qpp_get_stored_msg();
        if(!is_array($message))$message = array();
        $qpp = qpp_get_stored_options ($id);
        $address = qpp_get_stored_address ($id);
        $headerrow = array();
        array_push($headerrow,'Date Sent');
        array_push($headerrow, $qpp['inputreference']);
        array_push($headerrow, $qpp['quantitylabel']);
        array_push($headerrow, $qpp['inputamount']);
        array_push($headerrow, $qpp['stock']);
        array_push($headerrow, $qpp['optionlabel']);
        array_push($headerrow, $qpp['couponblurb']);
        if ($messageoptions['showaddress']) {
            array_push($headerrow, $address['email']);
            array_push($headerrow, $address['firstname']);
            array_push($headerrow, $address['lastname']);
            array_push($headerrow, $address['address1']);
            array_push($headerrow, $address['address2']);
            array_push($headerrow, $address['city']);
            array_push($headerrow, $address['state']);
            array_push($headerrow, $address['zip']);
            array_push($headerrow, $address['country']);
            array_push($headerrow, $address['night_phone_b']);
        }
        if ($qpp_setup['ipn']) array_push($headerrow, 'Paid');
        fputcsv($outstream,$headerrow, ',', '"');
        foreach(array_reverse( $message ) as $value) {
            $cells = array();
            array_push($cells,$value['field0']);
            array_push($cells,$value['field1']);
            array_push($cells,$value['field2']);
            array_push($cells,$value['field3']);
            $value['field4'] = ($value['field4'] != $value['stocklabel'] ? $value['field4'] : ''); array_push($cells,$value['field4']);
            $value['field5'] = ($value['field5'] != $value['optionlabel'] ? $value['field5'] : ''); array_push($cells,$value['field5']);	
            $value['field6'] = ($value['field6'] != $value['couponblurb'] ? $value['field6'] : ''); array_push($cells,$value['field6']);
            if ($messageoptions['showaddress']) {
                $value['field8'] = ($value['field8'] != $address['email'] ? $value['field8'] : ''); array_push($cells,$value['field8']);
                $value['field9'] = ($value['field9'] != $address['firstname'] ? $value['field9'] : ''); array_push($cells,$value['field9']);
                $value['field10'] = ($value['field10'] != $address['lastname'] ? $value['field10'] : ''); array_push($cells,$value['field10']);
                $value['field11'] = ($value['field11'] != $address['address1'] ? $value['field11'] : ''); array_push($cells,$value['field11']);
                $value['field12'] = ($value['field12'] != $address['address2'] ? $value['field12'] : ''); array_push($cells,$value['field12']);
                $value['field13'] = ($value['field13'] != $address['city'] ? $value['field13'] : ''); array_push($cells,$value['field13']);
                $value['field14'] = ($value['field14'] != $address['state'] ? $value['field14'] : ''); array_push($cells,$value['field14']);
                $value['field15'] = ($value['field15'] != $address['zip'] ? $value['field15'] : ''); array_push($cells,$value['field15']);
                $value['field16'] = ($value['field16'] != $address['country'] ? $value['field16'] : ''); array_push($cells,$value['field16']);
                $value['field17'] = ($value['field17'] != $address['night_phone_b'] ? $value['field17'] : ''); array_push($cells,$value['field17']);
            }
            if ($qpp_setup['ipn']) {
                $paid = ($value['field18'] == 'Paid' ? 'Paid' : '');
                array_push($cells,$paid);
            }
            fputcsv($outstream,$cells, ',', '"');
        }
        fclose($outstream); 
        exit;
    }
}

function qpp_donate_page() {
    $content = '<div class="qpp-settings"><div class="qpp-options">';
    $content .= donate_loop();
    $content .= '</div></div>';
    echo $content;
}

function donate_verify($formvalues) {
    $errors = '';
    if ($formvalues['amount'] == 'Amount' || empty($formvalues['amount'])) $errors = 'first';
    if ($formvalues['yourname'] == 'Your name' || empty($formvalues['yourname'])) $errors = 'second';
    return $errors;
}

function donate_display( $values, $errors ) {
    $content = "<script>\r\t
    function donateclear(thisfield, defaulttext) {if (thisfield.value == defaulttext) {thisfield.value = '';}}\r\t
    function donaterecall(thisfield, defaulttext) {if (thisfield.value == '') {thisfield.value = defaulttext;}}\r\t
    </script>\r\t
    <div class='qpp-style'>\r\t";
    if ($errors)
        $content .= "<h2 class='error'>Feed me...</h2>\r\t<p class='error'>...your donation details</p>\r\t";
    else
        $content .= "<h2 style='color:#B52C00'>Make a donation</h2>\r\t
        <p>Whilst I enjoyed creating this plugin and have had lots of great ideas lots of people, it doesn't pay the bills. So a donation will always be gratefully received.</p>\r\t
        <p>If you have no money then a thank you will suffice.</p>\r\t";
    $content .= '<form method="post" action="" style="width:50%">
    <p><input type="text" label="Your name" name="yourname" value="Your name" onfocus="donateclear(this, \'Your name\')" onblur="donaterecall(this, \'Your name\')"/></p>
    <p><input type="text" label="Amount" name="amount" value="Amount" onfocus="donateclear(this, \'Amount\')" onblur="donaterecall(this, \'Amount\')"/></p>
    <p><input type="submit" value="Donate" id="submit" name="donate" /></p>
    </form></div>';
    echo $content;
}

function donate_process($values) {
    $page_url = donate_page_url();
    $content = '<h2>Waiting for paypal...</h2><form action="https://www.paypal.com/cgi-bin/webscr" method="post" name="frmCart" id="frmCart">
    <input type="hidden" name="cmd" value="_xclick">
    <input type="hidden" name="business" value="graham@aerin.co.uk">
    <input type="hidden" name="bn" value="AngellEYE_SP_Quick_PayPal_Payments" />
    <input type="hidden" name="return" value="' .  $page_url . '">
    <input type="hidden" name="cancel_return" value="' .  $page_url . '">
    <input type="hidden" name="no_shipping" value="1">
    <input type="hidden" name="currency_code" value="">
    <input type="hidden" name="item_number" value="">
    <input type="hidden" name="item_name" value="'.$values['yourname'].'">
    <input type="hidden" name="amount" value="'.preg_replace ( '/[^.,0-9]/', '', $values['amount']).'">
    </form>
    <script language="JavaScript">
    document.getElementById("frmCart").submit();
    </script>';
    echo $content;
}

function donate_page_url() {
    $pageURL = 'http';
    if( isset($_SERVER["HTTPS"]) ) { if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";} }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    return $pageURL;
}

function donate_loop() {
    ob_start();
    if (isset($_POST['donate'])) {
        $formvalues['yourname'] = $_POST['yourname'];
        $formvalues['amount'] = $_POST['amount'];
        if (donate_verify($formvalues)) donate_display($formvalues,'donateerror');
        else donate_process($formvalues,$form);
    }
    else donate_display(null,'');
    $output_string=ob_get_contents();
    ob_end_clean();
    return $output_string;
}

function qpp_settings_init() {
    qpp_generate_csv();
    return;
}

function qpp_scripts_init() {
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'qpp_script',plugins_url('quick-paypal-payments.js', __FILE__));
    wp_enqueue_style( 'qpp_style',plugins_url('quick-paypal-payments.css', __FILE__));
    wp_enqueue_style( 'qpp_custom',plugins_url('quick-paypal-payments-custom.css', __FILE__));
    wp_enqueue_style( 'qpp_settings',plugins_url('settings.css', __FILE__));
    wp_enqueue_media();
    wp_enqueue_script('qpp-media', plugins_url('quick-paypal-media.js', __FILE__ ), array( 'jquery','wp-color-picker' ), false, true );
    wp_enqueue_script('qpp-slider', plugins_url('quick-paypal-slider.js', __FILE__ ), array( 'jquery' ), false, true );
}

add_action('admin_enqueue_scripts', 'qpp_scripts_init');

function qpp_page_init() {
    add_options_page('Paypal Payments', 'Paypal Payments', 'manage_options', __FILE__, 'qpp_tabbed_page');
}

function qpp_admin_notice($message) {
    if (!empty( $message)) echo '<div class="updated"><p>'.$message.'</p></div>';
}

function qpp_admin_pages() {
    add_menu_page('Payments', 'Payments', 'manage_options','quick-paypal-payments/quick-paypal-messages.php','','dashicons-cart');
}

function qpp_plugin_row_meta( $links, $file = '' ){
    if( false !== strpos($file , '/quick-paypal-payments.php') ){
        $new_links = array('<a href="http://quick-plugins.com/quick-paypal-payments/"><strong>Help and Support</strong></a>','<a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/settings.php&tab=donate"><strong>Donate</strong></a>');
$links = array_merge( $links, $new_links );  
} 
    return $links;
}