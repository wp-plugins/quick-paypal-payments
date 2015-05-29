<?php

function qpp_get_stored_setup () {
    $qpp_setup = get_option('qpp_setup');
    if(!is_array($qpp_setup)) $qpp_setup = array();
    $default = qpp_get_default_setup();
    $qpp_setup = array_merge($default, $qpp_setup);
    return $qpp_setup;
}
function qpp_get_default_setup () {
    $qpp_setup = array(
        'current' => '',
        'alternative' => '',
        'sandbox' => '',
        'encryption' => ''
    );
    return $qpp_setup;
}

function qpp_get_stored_curr () {
    $qpp_curr = get_option('qpp_curr');
    if(!is_array($qpp_curr)) $qpp_curr = array();
    $default = qpp_get_default_curr();
    $qpp_curr = array_merge($default, $qpp_curr);
    return $qpp_curr;
}
function qpp_get_default_curr () {
    $qpp_curr = array();
    $qpp_curr[''] = 'USD';
    return $qpp_curr;
}

function qpp_get_stored_email () {
    $qpp_email = get_option('qpp_email');
    if(!is_array($qpp_email)) $qpp_email = array();
    $default = qpp_get_default_email();
    $qpp_email = array_merge($default, $qpp_email);
    return $qpp_email;
}
function qpp_get_default_email () {
    $qpp_setup = qpp_get_stored_setup();
    $qpp_email = array();
    $qpp_email[''] = $qpp_setup['email'];
    return $qpp_email;
}

function qpp_get_stored_msg () {
    $messageoptions = get_option('qpp_messageoptions');
    if(!is_array($messageoptions)) $messageoptions = array();
    $default = qpp_get_default_msg();
    $messageoptions = array_merge($default, $messageoptions);
    return $messageoptions;
}
function qpp_get_default_msg () {
    $messageoptions = array();
    $messageoptions['messageqty'] = 'fifty';
    $messageoptions['messageorder'] = 'newest';
    return $messageoptions;
}

function qpp_get_stored_options ($id) {
    $qpp = get_option('qpp_options'.$id);
    if(!is_array($qpp)) $qpp = array();
    $default = qpp_get_default_options();
    $qpp = array_merge($default, $qpp);
    if (!strpos($qpp['sort'],'13')) {$qpp['sort'] = $qpp['sort'].',field13';update_option('qpp_options'.$id,$qpp);}
    if (!strpos($qpp['sort'],'14')) {$qpp['sort'] = $qpp['sort'].',field14';update_option('qpp_options'.$id,$qpp);}
    if (!strpos($qpp['sort'],'15')) {$qpp['sort'] = $qpp['sort'].',field15';update_option('qpp_options'.$id,$qpp);}
    if (!strpos($qpp['sort'],'16')) {$qpp['sort'] = $qpp['sort'].',field16';update_option('qpp_options'.$id,$qpp);}
    return $qpp;
}

function qpp_get_default_options () {
    $qpp = array();
    $qpp['sort'] = 'field1,field4,field2,field3,field5,field6,field7,field9,field12,field13,field14,field11,field8,field10,field15,field16';
    $qpp['title'] = 'Payment Form';
    $qpp['blurb'] = 'Enter the payment details and submit';
    $qpp['inputreference'] = 'Payment reference';
    $qpp['inputamount'] = 'Amount to pay';
    $qpp['sandbox'] ='';
    $qpp['quantitylabel'] = 'Quantity';
    $qpp['quantity'] = '1';
    $qpp['stocklabel'] = 'Item Number';
    $qpp['use_stock'] = '';
    $qpp['optionlabel'] = 'Options';
    $qpp['optionvalues'] = 'Large,Medium,Small';
    $qpp['use_options'] = '';
    $qpp['use_slider'] = '';
    $qpp['sliderlabel'] = 'Amount to pay';
    $qpp['min'] = '0';
    $qpp['max'] = '100';
    $qpp['initial'] = '50';
    $qpp['step'] = '10';
    $qpp['output-values'] = 'checked';
    $qpp['shortcodereference'] = 'Payment for: ';
    $qpp['shortcodeamount'] = 'Amount: ';
    $qpp['paypal-location'] = 'imagebelow';
    $qpp['captcha'] = '';
    $qpp['mathscaption'] = 'Spambot blocker question';
    $qpp['submitcaption'] = 'Make Payment';
    $qpp['resetcaption'] = 'Reset Form';
    $qpp['use_reset'] = '';
    $qpp['useprocess'] = '';
    $qpp['processblurb'] = 'A processing fee will be added before payment';
    $qpp['processref'] = 'Processing Fee';
    $qpp['processtype'] = 'processpercent';
    $qpp['processpercent'] = '5';
    $qpp['processfixed'] = '2';
    $qpp['usepostage'] = '';
    $qpp['postageblurb'] = 'Post and Packing will be added before payment';
    $qpp['postageref'] = 'Post and Packing';
    $qpp['postagetype'] = 'postagefixed';
    $qpp['postagepercent'] = '5';
    $qpp['postagefixed'] = '5';
    $qpp['usecoupon'] = '';
    $qpp['useblurb'] = '';
    $qpp['useemail'] = '';
    $qpp['extrablurb'] = 'Make sure you complete the next field';
    $qpp['couponblurb'] = 'Enter coupon code';
    $qpp['couponref'] = 'Coupon Applied';
    $qpp['couponbutton'] = 'Apply Coupon';
    $qpp['termsblurb'] = 'I agree to the Terms and Conditions';
    $qpp['termsurl'] = home_url();
    $qpp['termspage'] = 'checked';
    $qpp['quantitymaxblurb'] = 'maximum of 99';
    $qpp['userecurring'] = '';
    $qpp['recurringblurb'] = 'Subscription details:';
    $qpp['recurring'] = 'M';
    $qpp['recurringhowmany'] = '24';
    $qpp['Dvalue'] = '90';
    $qpp['Wvalue'] = '52';
    $qpp['Mvalue'] = '24';
    $qpp['Yvalue'] = '5';
    $qpp['Dperiod'] = 'days';
    $qpp['Wperiod'] = 'weeks';
    $qpp['Mperiod'] = 'months';
    $qpp['Yperiod'] = 'years';
    $qpp['srt'] = '12';
    $qpp['payments'] = 'Number of payments:';
    $qpp['every'] = 'Payment every';
    $qpp['useaddress'] = '';
    $qpp['addressblurb'] = 'Enter your details below';
    $qpp['usetotals'] = '';
    $qpp['totalsblurb'] = 'Total:';
    $qpp['emailblurb'] = 'Your email address';
    $qpp['couponapplied'] = '';
    $qpp['currency_seperator'] = 'period';
    $qpp['inline_amount'] = '';
    $qpp['selector'] = 'radio';
    $qpp['refselector'] = 'radio';
    $qpp['optionsselector'] = 'radio';
    return $qpp;
}

function qpp_get_stored_send($id) {
    $send = get_option('qpp_send'.$id);
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

function qpp_get_stored_style($id) {
    $style = get_option('qpp_style'.$id);
    if(!is_array($style)) $style = array();
    $default = qpp_get_default_style();
    $style = array_merge($default, $style);
    return $style;
}

function qpp_get_default_style() {
    $style['font'] = 'plugin';
    $style['font-family'] = 'arial, sans-serif';
    $style['font-size'] = '1em';
    $style['font-colour'] = '#465069';
    $style['header-type'] = 'h2';
    $style['header-size'] = '1.6em';
    $style['header-colour'] = '#465069';
    $style['text-font-family'] = 'arial, sans-serif';
    $style['text-font-size'] = '1em';
    $style['text-font-colour'] = '#465069';
    $style['width'] = 280;
    $style['form-border'] = '1px solid #415063';
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
    $style['submit-border'] = '1px solid #415063';
    $style['submitwidth'] = 'submitpercent';
    $style['submitposition'] = 'submitleft';
    $style['coupon-colour'] = '#FFF';
    $style['coupon-background'] = '#1f8416';
    $style['slider-background'] = '#CCC';
    $style['slider-revealed'] = '#00ff00';
    $style['handle-background'] = 'white';
    $style['handle-border'] = '#CCC';
    $style['output-size'] = '1.2em';
    $style['output-colour'] = '#465069';
    $style['styles'] = 'plugin';
    $style['use_custom'] = '';
    $style['custom'] = "#qpp-style {\r\n\r\n}";
    return $style;
}

function qpp_get_stored_error ($id) {
    $error = get_option('qpp_error'.$id);
    if(!is_array($error)) $error = array();
    $default = qpp_get_default_error();
    $error = array_merge($default, $error);
    return $error;
}

function qpp_get_default_error () {
    $error['errortitle'] = 'Oops, got a problem here';
    $error['errorblurb'] = 'Please check the payment details';
    return $error;
}

function qpp_get_stored_ipn () {
    $ipn = get_option('qpp_ipn');
    if(!is_array($ipn)) $ipn = array();
    $default = qpp_get_default_ipn();
    $ipn = array_merge($default, $ipn);
    return $ipn;
}

function qpp_get_default_ipn () {
    $ipn = array(
        'ipn' => '',
        'title' => 'Payment',
        'paid' => 'Complete'
    );
    return $ipn;
}

function qpp_get_stored_coupon ($id) {
    $coupon = get_option('qpp_coupon'.$id);
    if(!is_array($coupon)) $coupon = array();
    $default = qpp_get_default_coupon();
    $coupon = array_merge($default, $coupon);
    return $coupon;
}

function qpp_get_default_coupon () {
    for ($i=1; $i<=10; $i++) {
        $coupon['couponget'] = 'Coupon Code:';
        $coupon['coupontype'.$i] = 'percent'.$i;
        $coupon['couponpercent'.$i] = '10';
        $coupon['couponfixed'.$i] = '5';
    }
    $coupon['couponget'] = 'Coupon Code:';
    $coupon['couponnumber'] = '10';
    $coupon['duplicate'] = '';
    $coupon['couponerror'] = 'Invalid Code';
    return $coupon;
}

function qpp_get_stored_address ($id) {
    $address = get_option('qpp_address'.$id);
    if(!is_array($address)) $address = array();
    $default = qpp_get_default_address();
    $address = array_merge($default, $address);
    return $address;
}

function qpp_get_default_address () {
    $address['firstname']= 'First Name';
    $address['lastname']= 'Last Name';
    $address['email']= 'Email';
    $address['address1']= 'Address Line 1';
    $address['address2']= 'Address Line 2';
    $address['city']= 'City';
    $address['state']= 'State';
    $address['zip']= 'ZIP Code';
    $address['country']= 'Country';
    $address['night_phone_b']= 'Phone Number';
    return $address;
}