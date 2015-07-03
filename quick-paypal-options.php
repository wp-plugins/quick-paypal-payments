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
    $qpp = array(
        'sort' => 'field1,field4,field2,field3,field5,field6,field7,field9,field12,field13,field14,field11,field8,field10,field15,field16',
        'title' => 'Payment Form',
        'blurb' => 'Enter the payment details and submit',
        'inputreference' => 'Payment reference',
        'inputamount' => 'Amount to pay',
        'sandbox' =>'',
        'quantitylabel' => 'Quantity',
        'quantity' => '1',
        'stocklabel' => 'Item Number',
        'use_stock' => '',
        'optionlabel' => 'Options',
        'optionvalues' => 'Large,Medium,Small',
        'use_options' => '',
        'use_slider' => '',
        'sliderlabel' => 'Amount to pay',
        'min' => '0',
        'max' => '100',
        'initial' => '50',
        'step' => '10',
        'output-values' => 'checked',
        'shortcodereference' => 'Payment for: ',
        'shortcodeamount' => 'Amount: ',
        'paypal-location' => 'imagebelow',
        'captcha' => '',
        'mathscaption' => 'Spambot blocker question',
        'submitcaption' => 'Make Payment',
        'resetcaption' => 'Reset Form',
        'use_reset' => '',
        'useprocess' => '',
        'processblurb' => 'A processing fee will be added before payment',
        'processref' => 'Processing Fee',
        'processtype' => 'processpercent',
        'processpercent' => '5',
        'processfixed' => '2',
        'usepostage' => '',
        'postageblurb' => 'Post and Packing will be added before payment',
        'postageref' => 'Post and Packing',
        'postagetype' => 'postagefixed',
        'postagepercent' => '5',
        'postagefixed' => '5',
        'usecoupon' => '',
        'useblurb' => '',
        'useemail' => '',
        'extrablurb' => 'Make sure you complete the next field',
        'couponblurb' => 'Enter coupon code',
        'couponref' => 'Coupon Applied',
        'couponbutton' => 'Apply Coupon',
        'termsblurb' => 'I agree to the Terms and Conditions',
        'termsurl' => home_url(),
        'termspage' => 'checked',
        'quantitymaxblurb' => 'maximum of 99',
        'userecurring' => '',
        'recurringblurb' => 'Subscription details:',
        'recurring' => 'M',
        'recurringhowmany' => '24',
        'Dvalue' => '90',
        'Wvalue' => '52',
        'Mvalue' => '24',
        'Yvalue' => '5',
        'Dperiod' => 'days',
        'Wperiod' => 'weeks',
        'Mperiod' => 'months',
        'Yperiod' => 'years',
        'srt' => '12',
        'payments' => 'Number of payments:',
        'every' => 'Payment every',
        'useaddress' => '',
        'addressblurb' => 'Enter your details below',
        'usetotals' => '',
        'totalsblurb' => 'Total:',
        'emailblurb' => 'Your email address',
        'couponapplied' => '',
        'currency_seperator' => 'period',
        'inline_amount' => '',
        'selector' => 'radio',
        'refselector' => 'radio',
        'optionsselector' => 'radio'
    );
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
    $send = array(
        'waiting' => 'Waiting for PayPal...',
        'cancelurl' => '',
        'thanksurl' => '',
        'target' => 'current'
    );
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
    $style = array(
        'font' => 'plugin',
        'font-family' => 'arial, sans-serif',
        'font-size' => '1em',
        'font-colour' => '#465069',
        'header-type' => 'h2',
        'header-size' => '1.6em',
        'header-colour' => '#465069',
        'text-font-family' => 'arial, sans-serif',
        'text-font-size' => '1em',
        'text-font-colour' => '#465069',
        'width' => 280,
        'form-border' => '1px solid #415063',
        'widthtype' => 'pixel',
        'border' => 'plain',
        'input-border' => '1px solid #415063',
        'required-border' => '1px solid #00C618',
        'error-colour' => '#FF0000',
        'bordercolour' => '#415063',
        'background' => 'white',
        'backgroundhex' => '#FFF',
        'corners' => 'corner',
        'submit-colour' => '#FFF',
        'submit-background' => '#343838',
        'submit-button' => '',
        'submit-border' => '1px solid #415063',
        'submitwidth' => 'submitpercent',
        'submitposition' => 'submitleft',
        'coupon-colour' => '#FFF',
        'coupon-background' => '#1f8416',
        'slider-background' => '#CCC',
        'slider-revealed' => '#00ff00',
        'handle-background' => 'white',
        'handle-border' => '#CCC',
        'output-size' => '1.2em',
        'output-colour' => '#465069',
        'styles' => 'plugin',
        'use_custom' => '',
        'custom' => "#qpp-style {\r\n\r\n}",
        'header-type' => 'h2'
    );
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
    $error = array(
        'errortitle' => 'Oops, got a problem here',
        'errorblurb' => 'Please check the payment details'
    );
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
    $address = array(
        'firstname' => 'First Name',
        'lastname' => 'Last Name',
        'email' => 'Your Email Address',
        'address1' => 'Address Line 1',
        'address2' => 'Address Line 2',
        'city' => 'City',
        'state' => 'State',
        'zip' => 'ZIP Code',
        'country' => 'Country',
        'night_phone_b' => 'Phone Number'
    );
    return $address;
}

function qpp_get_stored_autoresponder ($id) {
    $auto = get_option('qpp_autoresponder'.$id);
    if(!is_array($auto)) {
        $send = qpp_get_stored_send($id);
        if ($send['thankyou']) {
            $auto = array(
                'enable' => $send['thankyou'],
                'subject' => 'Thank you for your payment.',
                'whenconfirm' => $send['whenconfirm'],
                'message' => $send['thankyoumessage'],
                'paymentdetails' => 'checked',
                'fromname' => '',
                'fromemail' => '',
            );
            $send['thankyou'] = '';
            update_option( 'qpp_send'.$id, $send );
        } else {
            $auto = array(
                'enable' => '',
                'subject' => 'Thank you for your payment.',
                'whenconfirm' => 'aftersubmission',
                'message' => 'Once payment has been confirmed we will process your order and be in contanct soon.',
                'paymentdetails' => 'checked',
                'fromname' => '',
                'fromemail' => '',
            );
        }
    }
    return $auto;
}