<?php
/*
Plugin Name: Quick Paypal Payments
Plugin URI: http://quick-plugins.com/quick-paypal-payments/
Description: Accept any amount or payment ID before submitting to paypal.
Version: 3.11
Author: fisicx
Author URI: http://quick-plugins.com/
*/

add_shortcode('qpp', 'qpp_loop');
add_shortcode('qppreport', 'qpp_report');
add_filter('plugin_action_links', 'qpp_plugin_action_links', 10, 2 );
add_action('init', 'qpp_init');
add_action('wp_enqueue_scripts','qpp_enqueue_scripts');

if (is_admin()) require_once( plugin_dir_path( __FILE__ ) . '/settings.php' );

function qpp_init() {
	qpp_create_css_file ('');
	}

function qpp_enqueue_scripts() {
	wp_enqueue_script( 'qpp_script',plugins_url('quick-paypal-payments.js', __FILE__));
	wp_enqueue_style( 'qpp_style',plugins_url('quick-paypal-payments.css', __FILE__));
	wp_enqueue_style( 'qpp_custom',plugins_url('quick-paypal-payments-custom.css', __FILE__));
	}

function qpp_create_css_file ($update) {
	if (function_exists('file_put_contents')) {
		$css_dir = plugin_dir_path( __FILE__ ) . '/quick-paypal-payments-custom.css' ;
		$filename = plugin_dir_path( __FILE__ );
		if (is_writable($filename) && (!file_exists($css_dir) || !empty($update))) {
			$data = qpp_generate_css();
			file_put_contents($css_dir, $data, LOCK_EX);
			}
		}
	else add_action('wp_head', 'qpp_head_css');
	}

function qpp_plugin_action_links($links, $file ) {
	if ( $file == plugin_basename( __FILE__ ) ) {
		$qpp_links = '<a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/settings.php">'.__('Settings').'</a>';
		array_unshift( $links, $qpp_links );
		}
	return $links;
	}

function qpp_verify_form(&$formvalues,&$errors,$form) {
    $qpp = qpp_get_stored_options($form);
    $check = preg_replace ( '/[^.,0-9]/', '', $formvalues['amount']);
    $arr = array('amount','reference','quantity','stock');
    foreach ($arr as $item) $formvalues[$item] = filter_var($formvalues[$item], FILTER_SANITIZE_STRING);
    if (!$formvalues['setpay']) if ($formvalues['amount'] == $qpp['inputamount'] || empty($formvalues['amount'])) $errors['amount'] = 'error';
        if ($qpp['allow_amount'] || $formvalues['combine']) $errors['amount'] = '';
    if (!$formvalues['setref']) if ($formvalues['reference'] == $qpp['inputreference'] || empty($formvalues['reference'])) $errors['reference'] = 'error';
    if ($qpp['use_quantity'] && $formvalues['quantity'] < 1) $errors['quantity'] = 'error';
    $max = preg_replace ( '/[^0-9]/', '', $qpp['quantitymaxblurb']);
    if ($qpp['use_quantity'] && $qpp['quantitymax']&& $formvalues['quantity'] > $max) $errors['quantity'] = 'error';
    if($qpp['captcha'] == 'checked') {
        $formvalues['maths'] = strip_tags($formvalues['maths']); 
        if($formvalues['maths']<>$formvalues['answer']) $errors['captcha'] = 'error';
		if(empty($formvalues['maths'])) $errors['captcha'] = 'error'; 
		}
    if($qpp['useterms'] && !$formvalues['termschecked']) $errors['useterms'] = 'error';
    $errors = array_filter($errors);
    return (count($errors) == 0);
	}

function qpp_display_form( $values, $errors, $id ) {
    $qpp_form = qpp_get_stored_setup();
    $qpp = qpp_get_stored_options($id);
    $error = qpp_get_stored_error($id);
    $coupon = qpp_get_stored_coupon($id);
    $send = qpp_get_stored_send($id);
    $style = qpp_get_stored_style($id);
    $currency = qpp_get_stored_curr();
    $address = qpp_get_stored_address($id);
    $curr = ($currency[$id] == '' ? 'USD' : $currency[$id]);
    $check = preg_replace ( '/[^.0-9]/', '', $values['amount']);
    $decimal = array('HKD','JPY','MYR','TWD');$d='2';
    foreach ($decimal as $item) if ($item == $currency[$id]) $d ='0';
    $values['producttotal'] = $values['quantity'] * $check;
    $c = qpp_currency ($id);
    $p = qpp_postage($qpp,$values['producttotal'],'1');
    $h = qpp_handling($qpp,$values['producttotal'],'1');
    $t = $id;if ($t=='') $t='default';
    $values['producttotal'] = $values['producttotal'] + $p +$h;
    $values['producttotal'] = number_format($values['producttotal'], $d,'.','');
    global $_GET;
    if(isset($_GET["coupon"])) {$values['couponblurb'] = $_GET["coupon"];$values['couponget']=$coupon['couponget'];}
	if ($id) $formstyle=$id; else $formstyle='default';
	if (!empty($qpp['title'])) $qpp['title'] = '<h2>' . $qpp['title'] . '</h2>';
	if (!empty($qpp['blurb'])) $qpp['blurb'] = '<p>' . $qpp['blurb'] . '</p>';
    $content = '<div class="qpp-style '.$formstyle.'"><div id="'.$style['border'].'">';
    if (count($errors) > 0) {
        $content .= "<h2 style='color:red'>" . $error['errortitle'] . "</h2><p>" . $error['errorblurb'] . "</p>";
        $arr = array('amount','reference','quantity','stock','answer','useterms','quantity');
        foreach ($arr as $item) if ($errors[$item] == 'error') $errors[$item] = ' style="border:1px solid red;" ';
        if ($errors['captcha']) $errors['captcha'] = 'border:1px solid red;';
        if ($errors['quantity']) $errors['quantity'] = 'border:1px solid red;';
    } else {
		$content .= $qpp['title'];
		if ($qpp['paypal-url'] && $qpp['paypal-location'] == 'imageabove') $content .= "<img src='".$qpp['paypal-url']."' />";
		$content .=  $qpp['blurb'];
    }
    if ($qpp['usetotals']) {
        $content .= '<script>
    window.onload = function() {
        var calctotal = function calctotal() {
            var c = b.value;var n = Number(c.replace(/[^0-9\.]+/g,""));var m = a.value * n;var h = 0;var p = 0;
            if ("'.$qpp['useprocess'].'" == "checked") {
                if ("'.$qpp['processtype'].'" == "processpercent"){
                    var h = "'.$qpp['processpercent'].'".replace( /^\D+/g, "");
                    var h = (m * h / 100);
                } else {
                    var h = "'.$qpp['processfixed'].'".replace( /^\D+/g, "") * 1;
                }
            }
            if ("'.$qpp['usepostage'].'" == "checked") {
                if ("'.$qpp['postagetype'].'" == "postagepercent"){
                    var p = "'.$qpp['postagepercent'].'".replace( /^\D+/g, "");
                    var p = (m * p / 100);
                } else {
                    var p = "'.$qpp['postagefixed'].'".replace( /^\D+/g, "") * 1;
                }
            }
        producttotal.value = (h + p + m).toFixed(2);
        };
    var producttotal = document.getElementById("total_price'.$t.'");
    var a = document.getElementById("qppquantity'.$t.'");
    var b = document.getElementById("qppamount'.$t.'");
    a.onkeyup = calctotal;
    b.onkeyup = calctotal;
    };
    </script>';}
    $content .= '<form id="frmPayment" name="frmPayment" method="post" action="">';
	foreach (explode( ',',$qpp['sort']) as $name) {
        switch ( $name ) {
            case 'field1':
            if (!$values['setref']) {
                $content .= '<p>
                <input type="text" label="Reference" '.$errors['reference'].'name="reference" value="' . $values['reference'] . '" onfocus="qppclear(this, \'' . $values['reference'] . '\')" onblur="qpprecall(this, \'' . $values['reference'] . '\')"/>
                </p>';
            } else {
                if ($values['combine']) {
                    $checked = 'checked';
                    $ret = array_map ('explode_by_semicolon', explode (',', $qpp['inputreference']));
                    $content .= '<p class="payment" >'.$qpp['shortcodereference'].'<br>';
                    foreach ($ret as $item) {
                        
if (strrpos($values['reference'],$item[0]) !==false && $values['combine'] != 'initial') $checked = 'checked';
                        $content .=  '<label><input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="' .  $item[0].'&'.$item[1] . '" ' . $checked . '> ' .  $item[0].' '.$item[1] . '</label><br>';$checked='';}
                    $content .= '</p>
                    <input type="hidden" name="combine" value="checked" />';
                } elseif ($values['explode']) {
                    $checked = 'checked';
                    $ref = explode(",",$values['reference']);
                    $content .= '<p class="payment" >'.$qpp['shortcodereference'].'<br>';
                    foreach ($ref as $item)
                        $content .=  '<label>
                        <input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label><br>';
                    $checked='';
                    $content .= '</p>';
                } else {
                    $content .= '<p class="input" >'.$values['reference'].'</p><input type="hidden" name="setref" value="' . $values['setref'] . '" />';
                }
            }
            break;	
            case 'field2':
            if ($qpp['use_stock']) {
                $content .= '<p>
                <input type="text" label="stock" name="stock" value="' . $values['stock'] . '" onfocus="qppclear(this, \'' . $values['stock'] . '\')" onblur="qpprecall(this, \'' . $values['stock'] . '\')"/>
                </p>';
            }
            break;	
            case 'field3':
            if ($qpp['use_quantity']){
                $content .= '<p>
                <span class="input">'.$qpp['quantitylabel'].'</span>
                <input type="text" style=" '.$errors['quantity'].'width:3em;margin-left:5px" id="qppquantity'.$t.'" label="quantity" name="quantity" value="' . $values['quantity'] . '" onfocus="qppclear(this, \'' . $values['quantity'] . '\')" onblur="qpprecall(this, \'' . $values['quantity'] . '\')" />';
                if ($qpp['quantitymax']) $content .= '&nbsp;'.$qpp['quantitymaxblurb'];
                $content .= '</p>';
            } else { $content .='<input type ="hidden" id="qppquantity'.$t.'" name="quantity" value="1">';}
            break;
            case 'field4':
            if (!$values['combine']) {
                if (!$values['setpay']){
                    $content .= '<p>
                    <input type="text" '.$errors['amount'].' id="qppamount'.$t.'" label="Amount" name="amount" value="' . $values['amount'] . '" onfocus="qppclear(this, \'' . $values['amount'] . '\')" onblur="qpprecall(this, \'' . $values['amount'] . '\' )" />
                    </p>';
                } else {
                    if ($values['explodepay']) {
                        $ref = explode(",",$values['amount']);
                        $content .= '<p class="payment" >'.$qpp['shortcodeamount'].'<br>';
                        if($qpp['selector'] == 'dropdown') {
                            $content .= '<select name="amount">';
                            foreach ($ref as $item) {
                                $selected = '';
                                if ($values['amount'] == $item) $selected = 'selected';
                                $content .= '<option value="' .  $item . '" ' . $selected .'>' .  $item . '</option>'."\r\t";
                            }
                            $content .= '</select>'."\r\t";
                        } else {
                            $checked = 'checked';
                            foreach ($ref as $item) {
                                $content .=  '<label><input type="radio" id="qpptiddles" style="margin:0; padding: 0; border:none;width:auto;" name="amount" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label><br>';
                            $checked='';
                            }
                        $content .= '</p>';
                        }
                    }
                    else $content .= '<p class="input" >' . $values['amount'] . '</p><input type="hidden" id="qppamount'.$id.'" name="amount" value="'.$values['fixedamount'].'" />';
                }
            }
            break;
            case 'field5':
            if ($qpp['use_options']){
                $content .= '<p class="input">' . $qpp['optionlabel'] . '</p><p>';
                $arr = explode(",",$qpp['optionvalues']);
                foreach ($arr as $item) {
                    $checked = '';
                    if ($values['option1'] == $item) $checked = 'checked';
                    if ($item === reset($arr)) $content .= '<input type="radio" style="margin:0; padding: 0; border: none" name="option1" value="' .  $item . '" id="' .  $item . '" checked><label for="' .  $item . '"> ' .  $item . '</label><br>';
                    else $content .=  '<input type="radio" style="margin:0; padding: 0; border: none" name="option1" value="' .  $item . '" id="' .  $item . '" ' . $checked . '><label for="' .  $item . '"> ' .  $item . '</label><br>';
                }
                $content .= '</p>';
            }
            break;
            case 'field6':
            if ($qpp['usepostage']) $content .= '<p class="input" >'.$qpp['postageblurb'].'</p>';
            break;
            case 'field7':
            if ($qpp['useprocess']) $content .= '<p class="input" >'.$qpp['processblurb'].'</p>';
            break;
            case 'field8':
            if ($qpp['captcha']) {
                if (!empty($qpp['mathscaption'])) $content .= '<p class="input">' . $qpp['mathscaption'] . '</p>';
                $content .= '<p>' . strip_tags($values['thesum']) . ' = <input type="text" style="width:3em;font-size:100%;'.$errors['captcha'].'" label="Sum" name="maths"  value="' . $values['maths'] . '"></p> 
                <input type="hidden" name="answer" value="' . strip_tags($values['answer']) . '" />
                <input type="hidden" name="thesum" value="' . strip_tags($values['thesum']) . '" />';
            }
            break;
            case 'field9':
            $content .= '<input type="hidden" name="couponapplied" value="'.$values['couponapplied'].'" />';
            if ($qpp['usecoupon'] && $values['couponapplied']) $content .= '<p>'.$qpp['couponref'].'</p>
            <input type="hidden" name="couponblurb" value="'.$values['couponblurb'].'" />';
            if ($qpp['usecoupon'] && !$values['couponapplied']){
                $content .= '<p>'.$values['couponget'].'
                <input type="text" label="coupon" name="couponblurb" value="' . $values['couponblurb'] . '" onfocus="qppclear(this, \'' . $values['couponblurb'] . '\')" onblur="qpprecall(this, \'' . $values['couponblurb'] . '\')"/>
                </p>
                <p>
                <input type="submit" value="'.$qpp['couponbutton'].'" id="couponsubmit" name="qppapply'.$id.'" />
                </p>';
            }
            break;
            case 'field10':
            if ($qpp['useterms']) {
                if ($qpp['termspage']) $target = ' target="blank" ';
                $content .= '<p class="input" '.$errors['useterms'].'>
                <input type="checkbox" style="margin:0; padding: 0; border:none;width:auto;'.$errors['useterms'].'" name="termschecked" value="checked" ' . $values['termschecked'] . '>
                &nbsp;
                <a href="'.$qpp['termsurl'].'"'.$target.'>'.$qpp['termsblurb'].'</a></p>';
            }
            break;
            case 'field11':
            if ($qpp['useblurb']) $content .= '<p>' . $qpp['extrablurb'] . '</p>';
            break;
            case 'field12':
            if ($qpp['userecurring']) {
                $content .= '<p>' . $qpp['recurringblurb']. '<br>
                '.$qpp['payments'].' '.$qpp['srt'].'<br>
                '.$qpp['every'].' ' . $qpp['recurringhowmany'].' ' . $qpp['recurringperiod'] . '</p>';
                $checked = 'checked';
                $ref = explode(",",$values['recurring']);
            }
            break;
            case 'field13':
            if ($qpp['useaddress']) {
                $content .= '<p>' . $qpp['addressblurb'] . '</p>';
                $arr = array('email','firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
                foreach($arr as $item)
                    if ($values[$item])
                    $content .='<input type="text" name="'.$item.'" value="'.$values[$item].'" 
                    onfocus="qppclear(this, \'' . $values[$item] . '\')" 
                    onblur="qpprecall(this, \'' . $values[$item] . '\')"/>';
            }
            break;
            case 'field14':
            if ($qpp['usetotals']) {
                $content .= '<p style="text-align:right;font-weight:strong;">Total: '.$c['b'].'<input name="producttotal" style="width:5em" type="text" value="'.$values['producttotal'].'" id="total_price'.$t.'" readonly/>'.$c['a'].'</p>';
            }
            break;
        }
    }
	$caption = $qpp['submitcaption'];
    if ($style['submit-button']) $content .= '<p><input type="image" value="' . $caption . '" style="border:none;width:100%;height:auto;overflow:hidden;" src="'.$style['submit-button'].'" name="qppsubmit'.$id.'" onClick="replaceContentInContainer(\'place'.$id.'\', \'rep_place'.$id.'\')"/>';
	else $content .= '<p><input type="submit" value="' . $caption . '" id="submit" name="qppsubmit'.$id.'" /></p>';
	$content .= '</form>'."\r\t";
	if ($qpp['paypal-url'] && $qpp['paypal-location'] == 'imagebelow') $content .= '<img src="'.$qpp['paypal-url'].'" />';
	$content .= '<div style="clear:both;"></div></div></div>'."\r\t";
	echo $content;
}

function explode_by_semicolon ($_) {return explode (';', $_);}

function qpp_handling ($qpp,$check,$quantity){
    if ($qpp['useprocess'] && $qpp['processtype'] == 'processpercent') {
		$percent = preg_replace ( '/[^.,0-9]/', '', $qpp['processpercent']) / 100;
		$handling = $check * $quantity * $percent;}
	if ($qpp['useprocess'] && $qpp['processtype'] == 'processfixed') {
		$handling = preg_replace ( '/[^.,0-9]/', '', $qpp['processfixed']);}
    else $handling = '';
    return $handling;
}

function qpp_postage ($qpp,$check,$quantity){
    $packing='';
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagepercent') {
		$percent = preg_replace ( '/[^.,0-9]/', '', $qpp['postagepercent']) / 100;
		$packing = $check * $quantity * $percent;}
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagefixed') {
		$packing = preg_replace ( '/[^.,0-9]/', '', $qpp['postagefixed']);}
    else $packing='';
    return $packing;
}

function qpp_process_form($values,$id) {
    $currency = qpp_get_stored_curr();
    $qpp = qpp_get_stored_options($id);
    $send = qpp_get_stored_send($id);
    $coupon = qpp_get_stored_coupon($id);
    $address = qpp_get_stored_address($id);
    $style = qpp_get_stored_style($id);
    $qpp_setup = qpp_get_stored_setup();
    $page_url = qpp_current_page_url();
    $paypalurl = 'https://www.paypal.com/cgi-bin/webscr';
    if ($send['customurl']) $paypalurl = $send['customurl'];
	if ($qpp_setup['sandbox']) $paypalurl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	if (empty($send['thanksurl'])) $send['thanksurl'] = $page_url;
	if (empty($send['cancelurl'])) $send['cancelurl'] = $page_url;
	if ($send['target'] == 'newpage') $target = ' target="_blank" ';
    if ($qpp['fixedreference']) $reference = $values['reference'];
    else $reference = $qpp['inputreference'] . ': ' . strip_tags($values['reference']);
    if ($values['combine']) {
        $arr = explode('&',$values['reference']);
        $values['reference'] = $arr[0];
        $values['amount'] = $arr[1];
    }
    $curr = ($currency[$id] == '' ? 'USD' : $currency[$id]);
    $decimal = array('HKD','JPY','MYR','TWD');$d='2';
    foreach ($decimal as $item) if ($item == $currency[$id]) $d ='0';
        if ($qpp['currency_seperator'] == 'comma') {
        $check = preg_replace ( '/[^,0-9]/', '', $values['amount']);
        $check = str_replace(',','.',$check);
        $check = number_format($check, $d,'.','');
    } else {
        $check = preg_replace ( '/[^.0-9]/', '', $values['amount']);
        $check = number_format($check, $d,'.','');
    }
        $quantity =($values['quantity'] < 1 ? '1' : strip_tags($values['quantity']));
   	if ($qpp['useprocess'] && $qpp['processtype'] == 'processpercent') {
		$percent = preg_replace ( '/[^.,0-9]/', '', $qpp['processpercent']) / 100;
		$handling = $check * $quantity * $percent;}
	if ($qpp['useprocess'] && $qpp['processtype'] == 'processfixed') {
		$handling = preg_replace ( '/[^.,0-9]/', '', $qpp['processfixed']);}
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagepercent') {
		$percent = preg_replace ( '/[^.,0-9]/', '', $qpp['postagepercent']) / 100;
		$packing = $check * $quantity * $percent;}
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagefixed') {
		$packing = preg_replace ( '/[^.,0-9]/', '', $qpp['postagefixed']);}	
	$handling = number_format($handling,$d);
	$packing = number_format($packing,$d);
    $qpp_messages = get_option('qpp_messages'.$id);
   	if(!is_array($qpp_messages)) $qpp_messages = array();
	$sentdate = date_i18n('d M Y');
    $amounttopay = $check * $quantity + $handling + $packing;
    if ($qpp['stock'] == $values['stock']) $values['stock'] ='';
    $arr = array(
        'email',
        'firstname',
        'lastname',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'night_phone_b'
    );
foreach ($arr as $item) if ($address[$item] == $values[$item]) $values[$item] = '';
	$qpp_messages[] = array(
		'field0' => $sentdate,
		'field1' => $values['reference'] ,
		'field2' => $values['quantity'],
		'field3' => $amounttopay,
		'field4' => $values['stock'],
		'field5' => $values['option1'],
		'field6' => $values['couponblurb'],
        'field8' => $values['email'],
        'field9' => $values['firstname'],
        'field10' => $values['lastname'],
        'field11' => $values['address1'],
        'field12' => $values['address2'],
        'field13' => $values['city'],
        'field14' => $values['state'],
        'field15' => $values['zip'],
        'field16' => $values['country'],
        'field17' => $values['night_phone_b']
    );
	update_option('qpp_messages'.$id,$qpp_messages);
    
    $content = '<h2 id="qpp_reload">'.$send['waiting'].'</h2>
    <script type="text/javascript" language="javascript">
    document.querySelector("#qpp_reload").scrollIntoView();
    </script>
    <form action="'.$paypalurl.'" method="post" name="frmCart" id="frmCart" ' . $target . '>
    <input type="hidden" name="item_name" value="' .strip_tags($values['reference']). '"/>
    <input type="hidden" name="upload" value="1">
    <input type="hidden" name="business" value="' . $qpp_setup['email'] . '">
    <input type="hidden" name="bn" value="AngellEYE_SP_Quick_PayPal_Payments" />
    <input type="hidden" name="return" value="' .  $send['thanksurl'] . '">
    <input type="hidden" name="cancel_return" value="' .  $send['cancelurl'] . '">
    <input type="hidden" name="currency_code" value="' .  $curr . '">';
    
    if ($qpp['userecurring']) {
        $content .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
    } else {
        $content .= '<input type="hidden" name="cmd" value="_xclick">';
    }
    
    if ($qpp['use_stock']) {
        $content .= '<input type="hidden" name="item_number" value="' . strip_tags($values['stock']) . '">';
    }
    
	if ($qpp['userecurring']) {
        $content .= '<input type="hidden" name="a3" value="' . $check . '">
        <input type="hidden" name="p3" value="' .$qpp['recurringhowmany'] . '">
        <input type="hidden" name="t3" value="' .$qpp['recurring'] . '">
        <input type="hidden" name="src" value="1">
        <input type="hidden" name="srt" value="' .$qpp['srt'] . '">';
    } else {
        $content .= '<input type="hidden" name="quantity" value="' . $quantity . '">
		<input type="hidden" name="amount" value="' . $check . '">';
        if ($qpp['use_options']) {
            $content .= '<input type="hidden" name="on0" value="'.$qpp['optionlabel'].'" />
            <input type="hidden" name="os0" value="'.$values['option1'].'" />';
        }
        if ($qpp['useprocess']) {
            $content .='<input type="hidden" name="handling" value="' . $handling . '">';
        }
        if ($qpp['usepostage']) {
            $content .='
            <input type="hidden" name="shipping" value="' . $packing . '">';
        }
    }
    
    if ($send['use_lc']) {
        $content .= '<input type="hidden" name="lc" value="' . $send['lc'] . '">
        <input type="hidden" name="country" value="' . $send['lc'] . '">';
    }
    
    if ($qpp['useaddress']) {
        $arr = array('email','firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
        foreach($arr as $item) if ($address[$item] == $values[$item]) {
            $values[$item] = '';
            $content .= '<input type="hidden" name="'.$item.'" value="' . strip_tags($values[$item]) . '">';
        }
    }
    $content .='</form>
    <script language="JavaScript">document.getElementById("frmCart").submit();</script>';
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

function qpp_currency ($id) {
    $currency = qpp_get_stored_curr();
    $c = array();
    $c['a'] = $c['b'] = '';
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
    foreach($before as $item=>$key) {if ($item == $currency[$id]) $c['b'] = $key;}
    foreach($after as $item=>$key) {if ($item == $currency[$id]) $c['a'] = $key;}
    return $c;
    }

function qpp_loop($atts) {
	ob_start();
	extract(shortcode_atts(array( 'form' =>'','amount' => '' , 'id' => '','stock' => '', 'labels' => ''), $atts));
	$qpp = qpp_get_stored_options($form);
    $address = qpp_get_stored_address($form);
    global $_GET;
    if(isset($_GET["reference"])) {$id = $_GET["reference"];}
    if(isset($_GET["amount"])) {$amount = $_GET["amount"];}
    $arr = array('email','firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
    foreach($arr as $item) $formvalues[$item] = $address[$item];
    $formvalues['quantity'] = 1;
	$formvalues['stock'] = $qpp['stocklabel'];
    $formvalues['couponblurb'] = $qpp['couponblurb'];
    $formvalues['emailblurb'] = $qpp['emailblurb'];
    $formvalues['combine'] = $formvalues['couponapplied'] = $formvalues['couponget'] =$formvalues['maths'] = $formvalues['explodepay'] =  $formvalues['explode'] = $formvalues['recurring'] = '';
    if (strrpos($qpp['inputreference'],';') || strrpos($id,';')) $formvalues['combine'] = 'initial';

    if (!$labels) {
        $shortcodereference = $qpp['shortcodereference'].' ';
        $shortcodeamount = $qpp['shortcodeamount'].' ';
        }
    
    if ($id) {
        $formvalues['setref'] = 'checked';
		if (strrpos($id,',')) {
            $formvalues['reference'] = $id;
            if (!$formvalues['combine']) $formvalues['explode'] = 'checked';
        } else {
            $formvalues['reference'] = $shortcodereference.$id;
        }
    } else {
        $formvalues['reference'] = $qpp['inputreference'];
        $formvalues['setref'] = '';
    }
    if ($qpp['fixedreference'] && !$id) {
        if (strrpos($qpp['inputreference'],',')) {
            $formvalues['reference'] = $qpp['inputreference'];
            if (!$formvalues['combine']) $formvalues['explode'] = 'checked';
            $formvalues['setref'] = 'checked';
        } else {
        $formvalues['reference'] = $shortcodereference.$qpp['inputreference'];
        $formvalues['setref'] = 'checked';
        }
    }
    
    if ($amount) {
        $formvalues['setpay'] = 'checked';
        if (strrpos($amount,',')) {
            $formvalues['amount'] = $amount;
            $formvalues['explodepay'] = 'checked';
            $formvalues['fixedamount'] = $amount;
        } else {
            $formvalues['amount'] = $shortcodeamount.$amount;
            $formvalues['fixedamount'] = $amount;
        } 
    } else {
        $formvalues['amount'] = $qpp['inputamount'];
        $formvalues['setpay'] = '';
    }
    
    if ($qpp['fixedamount'] && !$amount) {
        if (strrpos($qpp['inputamount'],',')) {
            $formvalues['amount'] = $qpp['inputamount'];
            $formvalues['explodepay'] = 'checked';
            $formvalues['setpay'] = 'checked';
            $a = explode(",",$qpp['inputamount']);
            $formvalues['fixedamount'] = $a[0];
        } else {
            $formvalues['amount'] = $shortcodeamount.$qpp['inputamount'];
            $formvalues['fixedamount'] = $qpp['inputamount'];
            $formvalues['setpay'] = 'checked';
        }
    }
    
    if (isset($_POST['qppapply'.$form]) || isset($_POST['qppsubmit'.$form]) || isset($_POST['qppsubmit'.$form.'_x'])) {
        $_POST = qpp_sanitize($_POST);
        if (isset($_POST['reference'])) $id = $_POST['reference'];
        if (isset($_POST['amount'])) $amount = $_POST['amount'];
        $arr = array(
            'reference',
            'amount',
            'stock',
            'quantity',
            'option1',
            'couponblurb',
            'maths',
            'thesum',
            'answer',
            'termschecked',
            'email',
            'firstname',
            'lastname',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b','combine'
        );
        foreach($arr as $item) if (isset($_POST[$item])) $formvalues[$item] = $_POST[$item];
        }
    
    if (isset($_POST['qppapply'.$form])) {
    	$check = preg_replace ( '/[^.,0-9]/', '', $formvalues['amount']); 
        $coupon = qpp_get_stored_coupon($form);
        $c = qpp_currency ($form);
        for ($i=1; $i<=$coupon['couponnumber']; $i++) {
            if ($formvalues['couponblurb'] == $coupon['code'.$i]) {
                if ($coupon['coupontype'.$i] == 'percent'.$i) $check = $check - ($check * $coupon['couponpercent'.$i]/100);
                if ($coupon['coupontype'.$i] == 'fixed'.$i) $check = $check - $coupon['couponfixed'.$i];
                if ($check > 0) {
                    $formvalues['couponapplied'] = 'checked';
                    $formvalues['setpay'] = 'checked';
                    $formvalues['amount'] = $shortcodeamount.$c['b'].$check.$c['a'];
                    $formvalues['fixedamount'] = $check;
                } else $formvalues['couponblurb'] = $qpp['couponblurb'];
            }
        }
    }
    
    if (isset($_POST['qppsubmit'.$form]) || isset($_POST['qppsubmit'.$form.'_x'])) {
        $formerrors = array();
		if (!qpp_verify_form($formvalues,$formerrors,$form)) qpp_display_form($formvalues,$formerrors,$form);
   		else {
			if ($amount) $formvalues['amount'] = $amount;
			if ($id) $formvalues['reference'] = $id;
			qpp_process_form($formvalues,$form);
            if(function_exists(qem_qpp_places)) qem_qpp_places();
			}
		}
	 
    else {	$digit1 = mt_rand(1,10);
		$digit2 = mt_rand(1,10);
		if( $digit2 >= $digit1 ) {
		$formvalues['thesum'] = "$digit1 + $digit2";
		$formvalues['answer'] = $digit1 + $digit2;
		} else {
		$formvalues['thesum'] = "$digit1 - $digit2";
		$formvalues['answer'] = $digit1 - $digit2;
        }
          qpp_display_form($formvalues,null,$form);
		}
	$output_string=ob_get_contents();
	ob_end_clean();
	return $output_string;
	}

function qpp_sanitize($input) {
    if (is_array($input)) foreach($input as $var=>$val) $output[$var] = filter_var($val, FILTER_SANITIZE_STRING);
    return $output;
    }

function register_qpp_widget() {register_widget( 'qpp_Widget' );}

add_action( 'widgets_init', 'register_qpp_widget' );

class qpp_widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'qpp_widget', // Base ID
			'Paypal Payments', // Name
			array( 'description' => __( 'Paypal Payments', 'Add paypal payment form to your sidebar' ), ) // Args
		);
	   }
	public function widget( $args, $instance ) {
		extract($args, EXTR_SKIP);
		$id=$instance['id'];
		$amount=$instance['amount'];
        $form=$instance['form'];
		echo qpp_loop($instance);
	   }
 	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['id'] = $new_instance['id'];
		$instance['amount'] = $new_instance['amount'];
        		$instance['form'] = $new_instance['form'];
		return $instance;
		}
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'amount' => '' , 'id' => '','form' => '' ) );
		$id = $instance['id'];
		$amount = $instance['amount'];
		$form=$instance['form'];
		$qpp_setup = qpp_get_stored_setup();
		?>
		<h3>Select Form:</h3>
		<select class="widefat" name="<?php echo $this->get_field_name('form'); ?>">
		<?php
		$arr = explode(",",$qpp_setup['alternative']);
		foreach ($arr as $item) {
			if ($item == '') {$showname = 'default'; $item='';} else $showname = $item;
			if ($showname == $form || $form == '') $selected = 'selected'; else $selected = '';
			?><option value="<?php echo $item; ?>" id="<?php echo $this->get_field_id('form'); ?>" <?php echo $selected; ?>><?php echo $showname; ?></option><?php } ?>
		</select>
		<h3>Presets:</h3>
		<p><label for="<?php echo $this->get_field_id('id'); ?>">Payment Reference: <input class="widefat" id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" type="text" value="<?php echo attribute_escape($id); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id('amount'); ?>">Amount: <input class="widefat" id="<?php echo $this->get_field_id('amount'); ?>" name="<?php echo $this->get_field_name('amount'); ?>" type="text" value="<?php echo attribute_escape($amount); ?>" /></label></p>
		<p>Leave blank to use the default settings.</p>
		<p>To configure the payment form use the <a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/quick-paypal-payments.php">Settings</a> page</p>
		<?php
		}
	}
function qpp_generate_css() {
	$qpp_form = qpp_get_stored_setup();
	$arr = explode(",",$qpp_form['alternative']);
	foreach ($arr as $item) {
		$corners='';$input='';$background='';$paragraph='';$submit='';
		$style = qpp_get_stored_style($item);
		if ($item !='') $id = '.'.$item; else $id = '.default';
		if ($style['font'] == 'plugin') {
            $font = "font-family: ".$style['text-font-family']."; font-size: ".$style['text-font-size'].";color: ".$style['text-font-colour'].";line-height:100%;";
			$inputfont = "font-family: ".$style['font-family']."; font-size: ".$style['font-size']."; color: ".$style['font-colour'].";";
			$submitfont = "font-family: ".$style['font-family'];
			if ($style['header']) $header = ".qpp-style".$id." h2 {font-size: ".$style['header-size']."; color: ".$style['header-colour'].";}";
			}
		$input = ".qpp-style".$id." input[type=text]{border: ".$style['input-border'].";".$inputfont.";font-size: inherit;height:auto;line-height:normal;}\r\n";
		$paragraph = ".qpp-style".$id." p{".$font.";}\r\n";
		if ($style['submitwidth'] == 'submitpercent') $submitwidth = 'width:100%;';
		if ($style['submitwidth'] == 'submitrandom') $submitwidth = 'width:auto;';
		if ($style['submitwidth'] == 'submitpixel') $submitwidth = 'width:'.$style['submitwidthset'].';';
		if ($style['submitposition'] == 'submitleft') $submitposition = 'float:left;'; else $submitposition = 'float:right;';
		$submitbutton = ".qpp-style".$id." #submit, .qpp-style".$id." #submit:hover {".$submitposition.$submitwidth."color:".$style['submit-colour'].";background:".$style['submit-background'].";border:".$style['submit-border'].";".$submitfont.";font-size: inherit;}\r\n";
        $couponbutton = ".qpp-style".$id." #couponsubmit, .qpp-style".$id." #couponsubmit:hover{".$submitposition.$submitwidth."color:".$style['coupon-colour'].";background:".$style['coupon-background'].";border:".$style['submit-border'].";".$submitfont.";font-size: inherit;margin: 3px 0px 7px;
padding: 6px;}\r\n";
		if ($style['border']<>'none') $border =".qpp-style".$id." #".$style['border']." {border:".$style['form-border'].";}\r\n";
		if ($style['background'] == 'white') $background = ".qpp-style".$id." div {background:#FFF;}\r\n";
		if ($style['background'] == 'color') {$background = ".qpp-style".$id." div {background:".$style['backgroundhex'].";}\r\n";$bg = "background:".$style['backgroundhex'].";";}
		if ($style['backgroundimage']) $background = ".qpp-style".$id." #".$style['border']." {background: url('".$style['backgroundimage']."');}\r\n";
		if ($style['widthtype'] == 'pixel') $width = preg_replace("/[^0-9]/", "", $style['width']) . 'px';
		else $width = '100%';
		if ($style['corners'] == 'round') $corner = '5px'; else $corner = '0';
		$corners = ".qpp-style".$id." input[type=text], .qpp-style".$id." textarea, .qpp-style".$id." select, .qpp-style".$id." #submit {border-radius:".$corner.";}\r\n";
		if ($style['corners'] == 'theme') $corners = '';
        $code .='
        .qpp-style'.$id.' .floatleft{float:left;}
        .qpp-style'.$id.' .floatright {float:right;text-align:right;}
        .qpp-style'.$id.' .floatright input {width:3em;text-align:right;}
        .qpp-style'.$id.' input#total_price, .qpp-style'.$id.' input#total_postage, .qpp-style'.$id.' input#total_process, .qpp-style'.$id.' input#total_products {text-align:right;border:none;'.$bg.'}';
		$code .= ".qpp-style".$id." {width:".$width.";}\r\n".$border.$corners.$header.$paragraph.$input.$background.$submitbutton.$couponbutton;
		if ($style['use_custom'] == 'checked') $code .= $style['styles'] . "\r\n";
		}
	return $code;
}

function qpp_head_css () {
	$data = '<style type="text/css" media="screen">'.qpp_generate_css().'</style>';
	echo $data;
}

function qpp_report($atts) {
	extract(shortcode_atts(array( 'form' =>''), $atts));
	return qpp_messagetable($form,'');
}

function qpp_messagetable ($id,$email) {
	$options = qpp_get_stored_options ($id);
	$message = get_option('qpp_messages'.$id);
	$messageoptions = qpp_get_stored_msg();
    $address = qpp_get_stored_address($id);
	$c = qpp_currency ($id);
	$showthismany = '9999';
	if ($messageoptions['messageqty'] == 'fifty') $showthismany = '50';
	if ($messageoptions['messageqty'] == 'hundred') $showthismany = '100';
	$$messageoptions['messageqty'] = "checked";
	$$messageoptions['messageorder'] = "checked";
	if(!is_array($message)) $message = array();
	$title = $id; if ($id == '') $title = 'Default';
	if (!$email) $dashboard .= '<div class="wrap"><div id="qpp-widget">';
    else $padding = 'cellpadding="5"';      
	$dashboard .= '<table cellspacing="0" '.$padding.'><tr><th style="text-align:left">Date Sent</th>';
	foreach (explode( ',',$options['sort']) as $name) {
	$title='';
		switch ( $name ) {
			case 'field1': $dashboard .= '<th style="text-align:left">'.$options['inputreference'].'</th>';break;
			case 'field2': $dashboard .= '<th style="text-align:left">'.$options['quantitylabel'].'</th>';break;
			case 'field3': $dashboard .= '<th style="text-align:left">'.$options['inputamount'].'</th>';break;
			case 'field4': if ($options['use_stock']) $dashboard .= '<th style="text-align:left">'.$options['stocklabel'].'</th>';break;
			case 'field5': if ($options['use_options']) $dashboard .= '<th style="text-align:left">'.$options['optionlabel'].'</th>';break;
			case 'field6': if ($options['usecoupon']) $dashboard .= '<th style="text-align:left">'.$options['couponblurb'].'</th>';break;
			}
		}
    if ($messageoptions['showaddress']) {
        $arr = array('email','firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
        foreach ($arr as $item) $dashboard .= '<th style="text-align:left">'.$address[$item].'</th>';
    }
        
	if (!$email) $dashboard .= '<th style="text-align:left">Delete</th></tr>';
	
    if ($messageoptions['messageorder'] == 'newest') {
        $i=count($message) - 1;
        foreach(array_reverse( $message ) as $value) {
            if ($count < $showthismany ) {
                if ($value['date']) $report = 'messages';
                $content .= qpp_messagecontent ($id,$value,$options,$c,$messageoptions,$address,$arr,$i,$email);
                $content .='</tr>';
                $count = $count+1;
                $i--;
            }
        }
	}
	else {
        $i=0;
        foreach($message as $value) {
		if ($count < $showthismany ) {
            if ($value['date']) $report = 'messages';
			$content .= qpp_messagecontent ($id,$value,$options,$c,$messageoptions,$address,$arr,$i,$email);
			$content .='</tr>';
			$count = $count+1;
            $i++;
			}
		}
	}	
	if ($report) $dashboard .= $content.'</table>';
	else $dashboard .= '</table><p>No messages found</p>';
	return $dashboard;
	}

function qpp_messagecontent ($id,$value,$options,$c,$messageoptions,$address,$arr,$i,$email) {
	$content .= '<tr><td>'.strip_tags($value['field0']).'</td>';
	foreach (explode( ',',$options['sort']) as $name) {
		$title='';
		$amount = preg_replace ( '/[^.,0-9]/', '', $value['field3']);                 
		switch ( $name ) {
            case 'field1': $content .= '<td>'.$value['field1'].'</td>';break;
			case 'field2': $content .= '<td>'.$value['field2'].'</td>';break;
			case 'field3': $content .= '<td>'.$c['b'].$amount.$c['a'].'</td>';break;
			case 'field4': if ($options['use_stock']) 
                if ($options['stocklabel'] == $value['field4']) $value['field4']='';
                $content .= '<td>'.$value['field4'].'</td>';break;
			case 'field5': if ($options['use_options'])
            if ($options['optionlabel'] == $value['field5']) $value['field5']='';
                $content .= '<td>'.$value['field5'].'</td>';break;
			case 'field6': if ($options['usecoupon'])
                if ($options['couponblurb'] == $value['field6']) $value['field6']='';
                $content .= '<td>'.$value['field6'].'</td>';break;
			}
		}
    if ($messageoptions['showaddress']) {
        $arr = array('field8','field9','field10','field11','field12','field13','field14','field15','field16','field17');
        foreach ($arr as $item) {
            if ($value[$item] == $address[$item]) $value[$item] = '';
            $content .= '<td>'.$value[$item].'</td>';
        }
    }
    $content .= '<td>';
    if (!$email) $content .= '<input type="checkbox" name="'.$i.'" value="checked" /></td>';
    $content .= '</tr>';
	return $content;	
}

function qpp_get_stored_setup () {
	$qpp_setup = get_option('qpp_setup');
	if(!is_array($qpp_setup)) $qpp_setup = array();
	$default = qpp_get_default_setup();
	$qpp_setup = array_merge($default, $qpp_setup);
	return $qpp_setup;
	}
function qpp_get_default_setup () {
	$qpp_setup = array();
	$qpp_setup['current'] = '';
	$qpp_setup['alternative'] = '';
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
	return $qpp;
	}

function qpp_get_default_options () {
	$qpp = array();
	$qpp['sort'] = 'field1,field4,field2,field3,field5,field6,field7,field9,field12,field13,field14,field11,field8,field10';
    $qpp['title'] = 'Payment Form';
	$qpp['blurb'] = 'Enter the payment details and submit';
	$qpp['inputreference'] = 'Payment reference';
	$qpp['inputamount'] = 'Amount to pay';
	$qpp['quantitylabel'] = 'Quantity';
	$qpp['quantity'] = '1';
	$qpp['stocklabel'] = 'Item Number';
	$qpp['use_stock'] = '';
	$qpp['optionlabel'] = 'Options';
	$qpp['optionvalues'] = 'Large,Medium,Small';
	$qpp['use_options'] = '';
	$qpp['shortcodereference'] = 'Payment for: ';
	$qpp['shortcodeamount'] = 'Amount: ';
	$qpp['paypal-location'] = 'imagebelow';
	$qpp['captcha'] = '';
	$qpp['mathscaption'] = 'Spambot blocker question';
	$qpp['submitcaption'] = 'Make Payment';
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
    $qpp['emailblurb'] = '';
    $qpp['couponapplied'] = '';
    $qpp['currency_seperator'] = 'period';
    $qpp['selector'] = 'radio';
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
	$style['header'] = '';
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
    $coupon['couponnumber'] = '10';
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