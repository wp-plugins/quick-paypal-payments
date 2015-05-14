<?php
/*
Plugin Name: Quick Paypal Payments
Plugin URI: http://quick-plugins.com/quick-paypal-payments/
Description: Accept any amount or payment ID before submitting to paypal.
Version: 3.14
Author: fisicx
Author URI: http://quick-plugins.com/
*/

add_shortcode('qpp', 'qpp_loop');
add_shortcode('qppreport', 'qpp_report');
add_filter('plugin_action_links', 'qpp_plugin_action_links', 10, 2 );
add_action('init', 'qpp_init');
add_action('wp_enqueue_scripts','qpp_enqueue_scripts');
add_action( 'template_redirect', 'qpp_ipn' );

require_once( plugin_dir_path( __FILE__ ) . '/quick-paypal-options.php' );
if (is_admin()) require_once( plugin_dir_path( __FILE__ ) . '/settings.php' );

function qpp_init() {
    qpp_create_css_file ('');
}

function qpp_enqueue_scripts() {
    wp_enqueue_script( 'qpp_script',plugins_url('quick-paypal-payments.js', __FILE__));
    wp_enqueue_style( 'qpp_style',plugins_url('quick-paypal-payments.css', __FILE__));
    wp_enqueue_style( 'qpp_custom',plugins_url('quick-paypal-payments-custom.css', __FILE__));
    wp_enqueue_script("jquery-effects-core");
    wp_enqueue_script('qpp_slider', plugins_url('quick-paypal-slider.js', __FILE__ ), array( 'jquery' ), false, true );
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
    foreach($arr as $item) $v[$item] = $address[$item];
    $v['quantity'] = 1;
    $v['stock'] = $qpp['stocklabel'];
    $v['couponblurb'] = $qpp['couponblurb'];
    $v['email'] = $qpp['emailblurb'];
    $v['combine'] = $v['couponapplied'] = $v['couponget'] =$v['maths'] = $v['explodepay'] =  $v['explode'] = $v['recurring'] = '';
    if (strrpos($qpp['inputreference'],';') || strrpos($id,';')) $v['combine'] = 'initial';
    if (!$labels) {
        $shortcodereference = $qpp['shortcodereference'].' ';
        $shortcodeamount = $qpp['shortcodeamount'].' ';
        }
    
    if ($id) {
        $v['setref'] = 'checked';
        if (strrpos($id,',')) {
            $v['reference'] = $id;
            if (!$v['combine']) $v['explode'] = 'checked';
        } else {
            $v['reference'] = $shortcodereference.$id;
        }
    } else {
        $v['reference'] = $qpp['inputreference'];
        $v['setref'] = '';
    }
    
    if ($qpp['fixedreference'] && !$id) {
        if (strrpos($qpp['inputreference'],',')) {
            $v['reference'] = $qpp['inputreference'];
            if (!$v['combine']) $v['explode'] = 'checked';
            $v['setref'] = 'checked';
        } else {
        $v['reference'] = $shortcodereference.$qpp['inputreference'];
        $v['setref'] = 'checked';
        }
    }
    
    if ($amount) {
        $v['setpay'] = 'checked';
        if (strrpos($amount,',')) {
            $v['amount'] = $amount;
            $v['explodepay'] = 'checked';
            $v['fixedamount'] = $amount;
        } else {
            $v['amount'] = $shortcodeamount.$amount;
            $v['fixedamount'] = $amount;
        } 
    } else {
        $v['amount'] = $qpp['inputamount'];
        $v['setpay'] = '';
    }
    
    if ($qpp['fixedamount'] && !$amount) {
        if (strrpos($qpp['inputamount'],',')) {
            $v['amount'] = $qpp['inputamount'];
            $v['explodepay'] = 'checked';
            $v['setpay'] = 'checked';
            $a = explode(",",$qpp['inputamount']);
            $v['fixedamount'] = $a[0];
        } else {
            $v['amount'] = $shortcodeamount.$qpp['inputamount'];
            $v['fixedamount'] = $qpp['inputamount'];
            $v['setpay'] = 'checked';
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
        foreach($arr as $item) if (isset($_POST[$item])) $v[$item] = $_POST[$item];
    }
    
    if (isset($_POST['qppapply'.$form])) {
        if ($v['combine']) {
            $arr = explode('&',$v['reference']);
            $v['reference'] = $arr[0];
            $v['amount'] = $arr[1];
        }
        $check = qpp_format_amount($currency[$form],$qpp,$v['amount']);
        $coupon = qpp_get_stored_coupon($form);
        $c = qpp_currency ($form);
        for ($i=1; $i<=$coupon['couponnumber']; $i++) {
            if ($v['couponblurb'] == $coupon['code'.$i]) {
                if ($coupon['coupontype'.$i] == 'percent'.$i) $check = $check - ($check * $coupon['couponpercent'.$i]/100);
                if ($coupon['coupontype'.$i] == 'fixed'.$i) $check = $check - $coupon['couponfixed'.$i];
                if ($check > 0) {
                    $check = number_format($check, 2,'.','');
                    $v['couponapplied'] = 'checked';
                    $v['setpay'] = 'checked';
                    $v['amount'] = $shortcodeamount.$c['b'].$check.$c['a'];
                    $v['fixedamount'] = $check;
                    $v['explodepay'] = $v['combine'] ='';
                } else {
                   $v['couponblurb'] = $qpp['couponblurb'];
                }
            }
        }
        if (!$v['couponapplied']) $v['couponerror'] = $coupon['couponerror'];
    }
    
    if (isset($_POST['qppsubmit'.$form]) || isset($_POST['qppsubmit'.$form.'_x'])) {
        $formerrors = array();
        if (!qpp_verify_form($v,$formerrors,$form)) {
            qpp_display_form($v,$formerrors,$form);
        } else {
            if ($amount) $v['amount'] = $amount;
            if ($id) $v['reference'] = $id;
            qpp_process_form($v,$form);
            if(function_exists(qem_qpp_places)) qem_qpp_places();
        }
    } else {
        $digit1 = mt_rand(1,10);
        $digit2 = mt_rand(1,10);
        if( $digit2 >= $digit1 ) {
            $v['thesum'] = "$digit1 + $digit2";
            $v['answer'] = $digit1 + $digit2;
        } else {
            $v['thesum'] = "$digit1 - $digit2";
            $v['answer'] = $digit1 - $digit2;
        }
        if ($qpp['use_slider']) $v['amount'] = $qpp['initial'];
        qpp_display_form($v,null,$form);
    }
    $output_string=ob_get_contents();
    ob_end_clean();
    return $output_string;
}

function qpp_dropdown($arr,$values,$name,$blurb) {
    $content='';
    if ($blurb) $content = '<p class="payment" >'.$blurb.'</p>';
    $content .= '<select name="'.$name.'">';
    if(!$values['combine']) {
        foreach ($arr as $item) {
            $selected = '';
            if ($values[$name] == $item) $selected = 'selected';
            $content .= '<option value="' .  $item . '" ' . $selected .'>' .  $item . '</option>'."\r\t";
        }
    } else {
        foreach ($arr as $item) {
            $selected = (strrpos($values['reference'],$item[0]) !==false && $values['combine'] != 'initial' ? 'selected' : '');
            $content .=  '<option value="' .  $item[0].'&'.$item[1] . '" ' . $selected . '> ' .  $item[0].' '.$item[1] . '</option>';$selected='';
        }
    }
    $content .= '</select>'."\r\t";
    return $content;
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
    $hd = $style['header-type'];
    $values['producttotal'] = $values['producttotal'] + $p +$h;
    $values['producttotal'] = number_format($values['producttotal'], $d,'.','');
    global $_GET;
    if(isset($_GET["coupon"])) {$values['couponblurb'] = $_GET["coupon"];$values['couponget']=$coupon['couponget'];}
	if ($id) $formstyle=$id; else $formstyle='default';
	if (!empty($qpp['title'])) $qpp['title'] = '<'.$hd.'>' . $qpp['title'] . '</'.$hd.'>';
	if (!empty($qpp['blurb'])) $qpp['blurb'] = '<p>' . $qpp['blurb'] . '</p>';
    $content = '<div class="qpp-style '.$formstyle.'"><div id="'.$style['border'].'">';

    if (count($errors) > 0) {
        $content .= "<".$hd." id='qpp_reload' style='color:red'>" . $error['errortitle'] . "</".$hd.">
        <script type='text/javascript' language='javascript'>document.querySelector('#qpp_reload').scrollIntoView();</script>
        <p>" . $error['errorblurb'] . "</p>";
        $arr = array('amount','reference','quantity','stock','answer','useterms','quantity');
        foreach ($arr as $item) if ($errors[$item] == 'error') $errors[$item] = ' style="border:1px solid red;" ';
        if ($errors['captcha']) $errors['captcha'] = 'border:1px solid red;';
        if ($errors['quantity']) $errors['quantity'] = 'border:1px solid red;';
    } else {
        $content .= $qpp['title'];
        if ($qpp['paypal-url'] && $qpp['paypal-location'] == 'imageabove') $content .= "<img src='".$qpp['paypal-url']."' />";
        $content .=  $qpp['blurb'];
    }
    
    $content .= '<form id="frmPayment'.$t.'" name="frmPayment'.$t.'" method="post" action="">';
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
                    $ret = array_map ('explode_by_semicolon', explode (',', $values['reference']));
                    if ($qpp['refselector'] == 'refdropdown') {
                        $content .= qpp_dropdown($ret,$values,'reference',$qpp['shortcodereference']);
                    } else {
                        $content .= '<p class="payment" >'.$qpp['shortcodereference'].'<br>';
                        $content .= '<input type="hidden" name="combined_radio_amount" value="0.00" />';
                        foreach ($ret as $item) {
                            if (strrpos($values['reference'],$item[0]) !==false && $values['combine'] != 'initial') 
                                $checked = 'checked';
                            $content .=  '<label><input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="' .  $item[0].'&'.$item[1] . '" ' . $checked . '> ' .  $item[0].' '.$item[1] . '</label><br>';$checked='';
                        }
                        $content .= '</p>';
                    }
                    $content .= '<input type="hidden" name="combine" value="checked" />';
                } elseif ($values['explode']) {
                    $checked = 'checked';
                    $ref = explode(",",$values['reference']);
                    if ($qpp['refselector'] == 'refdropdown') {
                        $content .= qpp_dropdown($ref,$values,'reference',$qpp['shortcodereference']);
                    } else {
                        $content .= '<p class="payment" >'.$qpp['shortcodereference'].'<br>';
                        foreach ($ref as $item)
                            $content .=  '<label>
                            <input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label><br>';
                        $checked='';
                        $content .= '</p>';
                    }
                } else {
                    $content .= '<p class="input" >'.$values['reference'].'</p><input type="hidden" name="reference" value="' . $values['reference'] . '" /><input type="hidden" name="setref" value="' . $values['setref'] . '" />';
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
            if ($qpp['use_slider']) {
                $content .='<p>'.$qpp['sliderlabel'].'</p>
                <input type="range" id="qppamount'.$t.'" name="amount" min="'.$qpp['min'].'" max="'.$qpp['max'].'" value="'.$values['amount'].'" step="'.$qpp['step'].'" data-rangeslider>
                <div class="qpp-slideroutput">';
                if ($qpp['output-values']) {
                    $content.= '<span class="qpp-sliderleft">'.$qpp['min'].'</span>
                    <span class="qpp-slidercenter"><output></output></span>
                    <span class="qpp-sliderright">'.$qpp['max'].'</span>';
                } else {
                    $content.= '<span class="qpp-outputcenter"><output></output></span>';
                }
                $content.= '</div><div style="clear: both;"></div>';
            } else {
                if (!$values['combine']) {
                    if (!$values['setpay']){
                        $content .= '<p>
                        <input type="text" '.$errors['amount'].' id="qppamount'.$t.'" label="Amount" name="amount" value="' . $values['amount'] . '" onfocus="qppclear(this, \'' . $values['amount'] . '\')" onblur="qpprecall(this, \'' . $values['amount'] . '\' )" />
                        </p>';
                    } else {
                        if ($values['explodepay']) {
                            $ref = explode(",",$values['amount']);
                            if($qpp['selector'] == 'dropdown') {
                                $content .= qpp_dropdown($ref,$values,'amount',$qpp['shortcodeamount']);
                            } else {
                                $checked = 'checked';
                                $br = ($qpp['inline_amount'] ? '&nbsp;' : '<br>');
                                $content .= '<p class="payment" >'.$qpp['shortcodeamount'].'<br>';
                                foreach ($ref as $item) {
                                    $content .=  '<label><input type="radio" id="qpptiddles" style="margin:0; padding: 0; border:none;width:auto;" name="amount" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label>'.$br;
                                $checked='';
                                }
                            $content .= '</p>';
                            }
                        }
                        else $content .= '<p class="input" >' . $values['amount'] . '</p><input type="hidden" id="qppamount'.$t.'" name="amount" value="'.$values['fixedamount'].'" />';
                    }
                    $content .= '<input type="hidden" name="radio_amount" value="0.00" />';
                }
            }
            break;
            
            case 'field5':
            if ($qpp['use_options']){
                $content .= '<p class="input">' . $qpp['optionlabel'] . '</p><p>';
                $arr = explode(",",$qpp['optionvalues']);
                if ($qpp['optionselector'] == 'optionsdropdown') {
                    $content .= qpp_dropdown($arr,$values,'option1','');
                } else {
                    foreach ($arr as $item) {
                        $checked = '';
                        if ($values['option1'] == $item) $checked = 'checked';
                        if ($item === reset($arr)) $content .= '<input type="radio" style="margin:0; padding: 0; border: none" name="option1" value="' .  $item . '" id="' .  $item . '" checked><label for="' .  $item . '"> ' .  $item . '</label><br>';
                        else $content .=  '<input type="radio" style="margin:0; padding: 0; border: none" name="option1" value="' .  $item . '" id="' .  $item . '" ' . $checked . '><label for="' .  $item . '"> ' .  $item . '</label><br>';
                    }
                    $content .= '</p>';
                }
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
            if ($qpp['usecoupon'] && $values['couponapplied']) 
                $content .= '<p>'.$qpp['couponref'].'</p>
                <input type="hidden" name="couponblurb" value="'.$values['couponblurb'].'" />';
            if ($qpp['usecoupon'] && !$values['couponapplied']){
                if ($values['couponerror']) $content .= '<p style="color:red">'.$values['couponerror'].'</p>';
                $content .= '<p>'.$values['couponget'].'</p>';
                $content .= '<p><input type="text" label="coupon" name="couponblurb" value="' . $values['couponblurb'] . '" onfocus="qppclear(this, \'' . $values['couponblurb'] . '\')" onblur="qpprecall(this, \'' . $values['couponblurb'] . '\')"/>
                </p>
                <p class="submit">
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
                    $content .='<input type="text" name="'.$item.'" value="'.$values[$item].'" onfocus="qppclear(this, \'' . $values[$item] . '\')" onblur="qpprecall(this, \'' . $values[$item] . '\')"/>';
            }
            break;
            
            case 'field14':
            if ($qpp['usetotals']) {
                $content .= '<p style="font-weight:bold;">Total: '.$c['b'].'<input type="text" id="qpptotal" name="total" value="0.00" readonly="readonly" />'.$c['a'].'</p>';
            } else {
             $content .= '<input type="hidden" id="qpptotal" name="total"  />';   
            }
            break;
            
            case 'field16':
            if ($qpp['useemail']) {
                $content .= '<input type="text" label="stock" name="email" value="' . $values['email'] . '" onfocus="qppclear(this, \'' . $values['email'] . '\')" onblur="qpprecall(this, \'' . $values['email'] . '\')"/>';
            }
            break;	
        }
    }
    $caption = $qpp['submitcaption'];
    if ($style['submit-button']) {
        $content .= '<p class="submit"><input type="image" id="submitimage" value="' . $caption . '" src="'.$style['submit-button'].'" name="qppsubmit'.$id.'" onClick="replaceContentInContainer(\'place'.$id.'\', \'rep_place'.$id.'\')"/></p>';
    } else {
        $content .= '<p class="submit"><input type="submit" value="' . $caption . '" id="submit" name="qppsubmit'.$id.'" /></p>';
    }
    if ($qpp['use_reset']) $content .= '<p><input type="reset" value="'.$qpp['resetcaption'] . '" /></p>';
    $content .= '</form>'."\r\t";
    if ($qpp['paypal-url'] && $qpp['paypal-location'] == 'imagebelow') $content .= '<img src="'.$qpp['paypal-url'].'" />';
    if ($qpp['usetotals'] || $qpp['use_slider']) 
        $content .='<script type="text/javascript">
        (function() {
            function formatDecimal(val, n) {
                n = n || 2;
                var str = "" + Math.round ( parseFloat(val) * Math.pow(10, n) );
                while (str.length <= n) {
                    str = "0" + str;
                }
                var pt = str.length - n;
                return str.slice(0,pt) + "." + str.slice(pt);
            }
            function getRadioVal(form, name) {
                var radios = form.elements[name];
                var val;
                for (var i=0, len=radios.length; i<len; i++) {
                    if ( radios[i].checked == true ) {
                        val = radios[i].value;
                        break;
                    }
                }
                return val;
            }
            function getSizePrice(e) {
                this.form.elements["radio_amount"].value = this.value;
                updateTotal'.$t.'(this.form);
            }
            function getquantity() {
                this.form.elements["qppquantity'.$t.'"].value = this.value;
                updateTotal'.$t.'(this.form);
            }
            function getamount() {
                this.form.elements["qppamount'.$t.'"].value = this.value;
                updateTotal'.$t.'(this.form);
            }
            function updateTotal'.$t.'(form, explicitAmount) {
                
                if (typeof optionalArg === "undefined"){
                    if(form.elements["quantity"]) {
                        var qty = form.elements["quantity"].value;
                    } else {
                        var qty = parseFloat( form.elements["qppquantity'.$t.'"].value );
                    }
                    qty = qty || 1;
                    if ("'.$values['explodepay'].'"=="checked" && form.elements["radio_amount"]) {
                        var radio_amount = form.elements["radio_amount"].value;
                        var radio_amount = Number(radio_amount.replace(/[^0-9\.]+/g,""));
                        var radio_amount = parseFloat(radio_amount);
                    } else if (form.elements["combined_radio_amount"] ) {
                        var radio_amount = form.elements["combined_radio_amount"].value;
                    } else {
                        var amount = form.elements["qppamount'.$t.'"].value;
                        var amount = Number(amount.replace(/[^0-9\.]+/g,""));
                        var amount = parseFloat(amount);
                    }
                    
                    amount = amount || 0;
                    radio_amount = radio_amount || 0;
                    var m = qty * (amount + radio_amount);
                } else {
                    var m = explicitAmount;
                }
                var h = 0;
                var p = 0;
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
                //console.log("m"+m + " p"+p + " h"+h);
                form.elements["total"].value = formatDecimal(m + p + h);
            }
            var form = document.getElementById("frmPayment'.$t.'");
            if ("'.$values['explodepay'].'"=="checked") {
                var sz = form.elements["amount"];
                for (var i=0, len=sz.length; i<len; i++) sz[i].onclick = getSizePrice;
                form.elements["radio_amount"].value = Number( getRadioVal(form, "amount").replace(/[^0-9\.]+/g,""));
                updateTotal'.$t.'(form);
            } else if(document.getElementById("qppamount'.$t.'")) {
                var bx = document.getElementById("qppamount'.$t.'");
                bx.onkeyup = getamount;
                getamount.call(bx);
            }
            var ax = document.getElementById("qppquantity'.$t.'");
            ax.onkeyup = getquantity;
            getquantity.call(ax);
            jQuery(document).ready(function($) {
                $(function() {                    
                    var $document = $(document),selector = "[data-rangeslider]",$inputRange = $(selector);function valueOutput(element) {
                        var value = element.value,output = element.parentNode.getElementsByTagName("output")[0];
                        output.innerHTML = value;
                    }
                    for (var i = $inputRange.length - 1; i >= 0; i--) {
                        valueOutput($inputRange[i]);
                    };
                    $document.on("change", selector, function(e) {
                        valueOutput(e.target);
                    });
                    $inputRange.rangeslider({
                        polyfill: false,onSlide: function(position, value) {
                            updateTotal'.$t.'(document.getElementById("frmPayment'.$t.'"), value);
                        }
                    });
                });
            });
            jQuery(document).ready(function($) {
                combined = [];
                initiate = [];
                numeric_regex = /[+\-0-9\.\,]+/;
                jQuery("input[type=radio]").each(function(index){
                    if($(this).attr("value").match(/\&/) && $(this).attr("name").match(/reference/)) {
                        combined.push($(this));
                        if($(this).attr("checked")) {
                            initiate.push($(this));
                        }
                    }
                })
                
                $.each(combined, function(key, val){
                
                    val.change(function(){
                        v = val.val();
                        split = v.split("&");
                        numeric = split[1].match(numeric_regex);
                        //console.log("combined "+numeric[0]);
                        formid = $(this).closest("form").attr("id");
                        $("#"+ formid + " input[name=combined_radio_amount]").val(numeric[0]);
                        updateTotal'.$t.'(document.getElementById(formid));
                    });
                });
                $.each(initiate, function(key, val){
                    val.change();
                });
            });
    }());
</script>';
    $content .= '<script>jQuery("select option:selected").click(); //force calculation by clicking on default values</script>';
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

function qpp_format_amount($currency,$qpp,$amount){
    $curr = ($currency == '' ? 'USD' : $currency);
    $decimal = array('HKD','JPY','MYR','TWD');$d='2';
    foreach ($decimal as $item) {
        if ($item == $curr) $d = '';
        break;
    }
    if (!$d) {
        $check = preg_replace ( '/[^.0-9]/', '', $amount);
        $check = intval($check);
    } elseif ($qpp['currency_seperator'] == 'comma' && strpos($amount,',')) {
        $check = preg_replace ( '/[^,0-9]/', '', $amount);
        $check = str_replace(',','.',$check);
        $check = number_format($check, $d,'.','');
    } else {
        $check = preg_replace ( '/[^.0-9]/', '', $amount);
        $check = number_format($check, $d,'.','');
    }
    return $check;
}

function qpp_verify_form(&$v,&$errors,$form) {
    $qpp = qpp_get_stored_options($form);
    $check = preg_replace ( '/[^.,0-9]/', '', $v['amount']);
    $arr = array('amount','reference','quantity','stock','email');
    foreach ($arr as $item) $v[$item] = filter_var($v[$item], FILTER_SANITIZE_STRING);
    if (!$v['setpay']) if ($v['amount'] == $qpp['inputamount'] || empty($v['amount'])) $errors['amount'] = 'error';
        if ($qpp['allow_amount'] || $v['combine']) $errors['amount'] = '';
    if (!$v['setref']) if ($v['reference'] == $qpp['inputreference'] || empty($v['reference'])) 
        $errors['reference'] = 'error';
    if ($qpp['use_quantity'] && $v['quantity'] < 1) 
        $errors['quantity'] = 'error';
    $max = preg_replace ( '/[^0-9]/', '', $qpp['quantitymaxblurb']);
    if ($qpp['use_quantity'] && $qpp['quantitymax']&& $v['quantity'] > $max) $errors['quantity'] = 'error';
    if($qpp['captcha'] == 'checked') {
        $v['maths'] = strip_tags($v['maths']); 
        if($v['maths']<>$v['answer']) $errors['captcha'] = 'error';
        if(empty($v['maths'])) $errors['captcha'] = 'error'; 
    }
    if($qpp['useterms'] && !$v['termschecked']) $errors['useterms'] = 'error';
    $errors = array_filter($errors);
    return (count($errors) == 0);
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
    $custom = ($qpp['custom'] ? $qpp['custom'] : md5(mt_rand()) );
    $email = ($send['email'] ? $send['email'] : $qpp_setup['email']);
    if ($values['combine'] && $values['combine'] != 'initial') {
        $arr = explode('&',$values['reference']);
        $values['reference'] = $arr[0];
        $values['amount'] = $arr[1];
    }
    $check = qpp_format_amount($currency[$id],$qpp,$values['amount']);
    $quantity =($values['quantity'] < 1 ? '1' : strip_tags($values['quantity']));
   	if ($qpp['useprocess'] && $qpp['processtype'] == 'processpercent') {
        $percent = preg_replace ( '/[^.,0-9]/', '', $qpp['processpercent']) / 100;
        $handling = $check * $quantity * $percent;
        $handling = qpp_format_amount($currency[$id],$qpp,$handling);
    }
	if ($qpp['useprocess'] && $qpp['processtype'] == 'processfixed') {
        $handling = preg_replace ( '/[^.,0-9]/', '', $qpp['processfixed']);
        $handling = qpp_format_amount($currency[$id],$qpp,$handling);
    }
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagepercent') {
        $percent = preg_replace ( '/[^.,0-9]/', '', $qpp['postagepercent']) / 100;
        $packing = $check * $quantity * $percent;
        $packing = qpp_format_amount($currency[$id],$qpp,$packing);
    }
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagefixed') {
        $packing = preg_replace ( '/[^.,0-9]/', '', $qpp['postagefixed']);
        $packing = qpp_format_amount($currency[$id],$qpp,$packing);
    }
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
    foreach ($arr as $item)
        if ($address[$item] == $values[$item]) $values[$item] = '';
        $custom = md5(mt_rand());
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
        'field17' => $values['night_phone_b'],
        'field18' => $custom
    );
    update_option('qpp_messages'.$id,$qpp_messages);
    $content = '<h2 id="qpp_reload">'.$send['waiting'].'</h2>
    <script type="text/javascript" language="javascript">
    document.querySelector("#qpp_reload").scrollIntoView();
    </script>
    <form action="'.$paypalurl.'" method="post" name="frmCart" id="frmCart" ' . $target . '>
    <input type="hidden" name="item_name" value="' .strip_tags($values['reference']). '"/>
    <input type="hidden" name="custom" value="' .$custom. '"/>
    <input type="hidden" name="upload" value="1">
    <input type="hidden" name="business" value="'.$email.'">
    <input type="hidden" name="return" value="'.$send['thanksurl'].'">
    <input type="hidden" name="cancel_return" value="'.$send['cancelurl'].'">
    <input type="hidden" name="currency_code" value="'.$currency[$id].'">
    <input type="hidden" name="custom" value="'.$custom.'">
    ';
    
    if ($qpp['userecurring']) {
        $content .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
    } elseif ($send['donate']) {
        $content .= '<input type="hidden" name="cmd" value="_donations">';
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
    
    if ($send['thankyou'] && $values['email']) {
        $headers = "From: <{$qpp_setup['email']}>\r\n"
. "MIME-Version: 1.0\r\n"
. "Content-Type: text/html; charset=\"utf-8\"\r\n";	
        $subject = $values['reference'];
        $content = '<p>' . $send['thankyoumessage'] . '</p>
<table>
<tr>
<td>'.$qpp['inputreference'].': </td>
<td>'.$values['reference'].'</td>
</tr>
<tr>
<td>'.$qpp['quantitylabel'].': </td>
<td>'.$quantity.'</td>
</tr>';
if ($qpp['use_stock']) $content .= '<tr>
<td>'.$qpp['stocklabel'].': </td>
<td>' . strip_tags($values['stock']) . '</td>
</tr>';
if ($qpp['use_options']) $content .= '<tr>
<td>'.$qpp['optionlabel'].': </td>
<td>' . strip_tags($values['option1']) . '</td>
</tr>';
$content .= '<tr>
<td>'.$qpp['inputamount'].': </td>
<td>'.$check.' '.$currency[$id].'</td>
</tr>
</table>';
        wp_mail($values['email'], $subject, $content, $headers);
    }      
}

function qpp_current_page_url() {
	$pageURL = 'http';
	if( isset($_SERVER["HTTPS"]) ) {
        if ($_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        } 
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") 
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    else 
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
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
        $corners=$input=$background=$paragraph=$submit='';
        $style = qpp_get_stored_style($item);
        if ($item !='') $id = '.'.$item; else $id = '.default';
        if ($style['font'] == 'plugin') {
            $font = "font-family: ".$style['text-font-family']."; font-size: ".$style['text-font-size'].";color: ".$style['text-font-colour'].";line-height:100%;";
            $inputfont = "font-family: ".$style['font-family']."; font-size: ".$style['font-size']."; color: ".$style['font-colour'].";";
            $submitfont = "font-family: ".$style['font-family'];
            if ($style['header-size'] || $style['header-colour']) $header = ".qpp-style".$id." ".$style['header-type']." {font-size: ".$style['header-size']."; color: ".$style['header-colour'].";}";
        }
        $input = ".qpp-style".$id." input[type=text], .qpp-style".$id." select {border: ".$style['input-border'].";".$inputfont.";font-size: inherit;height:auto;line-height:normal;}\r\n";
        $paragraph = ".qpp-style".$id." p{".$font.";}\r\n";
        if ($style['submitwidth'] == 'submitpercent') $submitwidth = 'width:100%;';
        if ($style['submitwidth'] == 'submitrandom') $submitwidth = 'width:auto;';
        if ($style['submitwidth'] == 'submitpixel') $submitwidth = 'width:'.$style['submitwidthset'].';';
        if ($style['submitposition'] == 'submitleft') $submitposition = 'text-align:left;'; else $submitposition = 'text-align:right;';
        $submitbutton = ".qpp-style".$id." p.submit {".$submitposition."}
.qpp-style".$id." #submitimage, .qpp-style".$id." #submitimage:hover {".$submitwidth."height:auto;overflow:hidden;}
.qpp-style".$id." #submit, .qpp-style".$id." #submit:hover {".$submitwidth."color:".$style['submit-colour'].";background:".$style['submit-background'].";border:".$style['submit-border'].";".$submitfont.";font-size: inherit;text-align:center;}\r\n";
        $couponbutton = ".qpp-style".$id." #couponsubmit, .qpp-style".$id." #couponsubmit:hover{".$submitwidth."color:".$style['coupon-colour'].";background:".$style['coupon-background'].";border:".$style['submit-border'].";".$submitfont.";font-size: inherit;margin: 3px 0px 7px;
padding: 6px;text-align:center;}\r\n";
        if ($style['border']<>'none') $border =".qpp-style".$id." #".$style['border']." {border:".$style['form-border'].";}\r\n";
        if ($style['background'] == 'white') {$bg = "background:#FFF";$background = ".qpp-style".$id." div {background:#FFF;}\r\n";}
        if ($style['background'] == 'color') {$background = ".qpp-style".$id." div {background:".$style['backgroundhex'].";}\r\n";$bg = "background:".$style['backgroundhex'].";";}
        if ($style['backgroundimage']) $background = ".qpp-style".$id." #".$style['border']." {background: url('".$style['backgroundimage']."');}\r\n";
        $formwidth = preg_split('#(?<=\d)(?=[a-z%])#i', $style['width']);
        if (!$formwidth[1]) $formwidth[1] = 'px';
        if ($style['widthtype'] == 'pixel') $width = $formwidth[0].$formwidth[1];
        else $width = '100%';
        if ($style['corners'] == 'round') $corner = '5px'; else $corner = '0';
        $corners = ".qpp-style".$id." input[type=text], .qpp-style".$id." textarea, .qpp-style".$id." select, .qpp-style".$id." #submit {border-radius:".$corner.";}\r\n";
        if ($style['corners'] == 'theme') $corners = '';
        $slider = '.qpp-style'.$id.' div.rangeslider, .qpp-style'.$id.' div.rangeslider__fill {background: '.$style['slider-background'].';}
.qpp-style'.$id.' div.rangeslider__fill {background: '.$style['slider-revealed'].';}
.qpp-style'.$id.' div.rangeslider__handle {background: '.$style['handle-background'].';border: 1px solid '.$style['handle-border'].';}
.qpp-style'.$id.' div.qpp-slideroutput{font-size:'.$style['output-size'].';color:'.$style['output-colour'].';}'."\r";
$code .= ".qpp-style".$id." {width:".$width.";}\r\n".$border.$corners.$header.$paragraph.$input.$background.$submitbutton.$couponbutton.$slider;
$code  .= '.qpp-style'.$id.' input#qpptotal {font-weight:bold;padding: 0;margin-left:3px;border:none;'.$bg.'}'."\r\n\r\n";
        if ($style['use_custom'] == 'checked') $code .= $style['styles'] . "\r\n";
    }
    return $code;
}

function qpp_head_css () {
    $data = '<style type="text/css" media="screen">'.qpp_generate_css().'</style>';
    echo $data;
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

function qpp_report($atts) {
    extract(shortcode_atts(array( 'form' =>''), $atts));
    return qpp_messagetable($form,'');
}

function qpp_messagetable ($id,$email) {
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
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
    $dashboard .= '<table cellspacing="0" '.$padding.'><tr>';
    if (!$email) $dashboard .= '<th></th>';
    $dashboard .= '<th style="text-align:left">Date Sent</th>';
    foreach (explode( ',',$options['sort']) as $name) {
        $title='';
        switch ( $name ) {
            case 'field1': $dashboard .= '<th style="text-align:left">'.$options['inputreference'].'</th>';break;
            case 'field2': $dashboard .= '<th style="text-align:left">'.$options['quantitylabel'].'</th>';break;
            case 'field3': $dashboard .= '<th style="text-align:left">'.$options['inputamount'].'</th>';break;
            case 'field4': if ($options['use_stock']) $dashboard .= '<th style="text-align:left">'.$options['stocklabel'].'</th>';break;
            case 'field5': if ($options['use_options']) $dashboard .= '<th style="text-align:left">'.$options['optionlabel'].'</th>';break;
            case 'field6': if ($options['usecoupon']) $dashboard .= '<th style="text-align:left">'.$options['couponblurb'].'</th>';break;
            case 'field16': if ($options['useemail']) $dashboard .= '<th style="text-align:left">'.$options['emailblurb'].'</th>';break;
        }
    }
    if ($messageoptions['showaddress']) {
        $arr = array('email','firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
        foreach ($arr as $item) $dashboard .= '<th style="text-align:left">'.$address[$item].'</th>';
    }
    if ($qpp_ipn['ipn']) $dashboard .= '<th>'.$qpp_ipn['title'].'</th>';
    $dashboard .= '</tr>';
    if ($messageoptions['messageorder'] == 'newest') {
        $i=count($message) - 1;
        foreach(array_reverse( $message ) as $value) {
            if ($count < $showthismany ) {
                if ($value['field0']) $report = 'messages';
                $content .= qpp_messagecontent ($id,$value,$options,$c,$messageoptions,$address,$arr,$i,$email);
                $count = $count+1;
                $i--;
            }
        }
    } else {
        $i=0;
        foreach($message as $value) {
            if ($count < $showthismany ) {
                if ($value['field0']) $report = 'messages';
                $content .= qpp_messagecontent ($id,$value,$options,$c,$messageoptions,$address,$arr,$i,$email);
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
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
    $content .= '<tr>';
    if (!$email) $content .= '<td><input type="checkbox" name="'.$i.'" value="checked" /></td>';
    $content .= '<td>'.strip_tags($value['field0']).'</td>';
    foreach (explode( ',',$options['sort']) as $name) {
        $title='';
        $amount = preg_replace ( '/[^.,0-9]/', '', $value['field3']);                 
        switch ( $name ) {
            case 'field1': $content .= '<td>'.$value['field1'].'</td>';break;
            case 'field2': $content .= '<td>'.$value['field2'].'</td>';break;
            case 'field3': $content .= '<td>'.$c['b'].$amount.$c['a'].'</td>';break;
            case 'field4': if ($options['use_stock']) {
                if ($options['stocklabel'] == $value['field4']) $value['field4']='';
                $content .= '<td>'.$value['field4'].'</td>';}break;
            case 'field5': if ($options['use_options']) {
                if ($options['optionlabel'] == $value['field5']) $value['field5']='';
                $content .= '<td>'.$value['field5'].'</td>';}break;
            case 'field6': if ($options['usecoupon']) {
                if ($options['couponblurb'] == $value['field6']) $value['field6']='';
                $content .= '<td>'.$value['field6'].'</td>';}break;
            case 'field16': if ($options['useemail']) {
                if ($options['emailblurb'] == $value['field8']) $value['field8']='';
                $content .= '<td>'.$value['field8'].'</td>';}break;
        }
    }
    if ($messageoptions['showaddress']) {
        $arr = array('field8','field9','field10','field11','field12','field13','field14','field15','field16','field17');
        foreach ($arr as $item) {
            if ($value[$item] == $address[$item]) $value[$item] = '';
            $content .= '<td>'.$value[$item].'</td>';
        }
    }
    if ($qpp_ipn['ipn']) {
$ipn = ($qpp_setup['sandbox'] ? $value['field18'] : '');
        $content .= ($value['field18'] == "Paid" ? '<td>'.$qpp_ipn['paid'].'</td>' : '<td>'.$ipn.'</td>');
    }
    $content .= '</tr>';
    return $content;	
}

function qpp_ipn () {
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
    if ( !isset( $_REQUEST['paypal_ipn_result'] ) && !$qpp_ipn['ipn'])
        return;
    define("DEBUG", 1);
    define("LOG_FILE", "./ipn.log");
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    foreach ($raw_post_array as $keyval) {
        $keyval = explode ('=', $keyval);
        if (count($keyval) == 2)
            $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
    $req = 'cmd=_notify-validate';
    if(function_exists('get_magic_quotes_gpc')) {
        $get_magic_quotes_exists = true;
    }
    foreach ($myPost as $key => $value) {
        if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
            $value = urlencode(stripslashes($value));
        } else {
            $value = urlencode($value);
        }
        $req .= "&$key=$value";
    }
    
    if ($qpp_setup['sandbox']) {
        $paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    } else {
        $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
    }
    
    $ch = curl_init($paypal_url);
    if ($ch == FALSE) {
        return FALSE;
    }

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

    if(DEBUG == true) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
    }

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

    $res = curl_exec($ch);
    if (curl_errno($ch) != 0) // cURL error
    {
        if(DEBUG == true) {	
            error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL, 3, LOG_FILE);
        }
        curl_close($ch);
        exit;
    } else {
		if(DEBUG == true) {
			error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" . PHP_EOL, 3, LOG_FILE);
			error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE);
        }
		curl_close($ch);
    }

    $tokens = explode("\r\n\r\n", trim($res));
    $res = trim(end($tokens));

    if (strcmp ($res, "VERIFIED") == 0) {
        $custom = $_POST['custom'];
        $arr = explode(",",$qpp_setup['alternative']);
        foreach ($arr as $item) {
            $message = get_option('qpp_messages'.$item);
            $count = count($message);
            for($i = 0; $i <= $count; $i++) {
                if ($message[$i]['field18'] == $custom) {
                    $message[$i]['field18'] = 'Paid';
                    update_option('qpp_messages'.$item,$message);
                }
            }
        }
        
        if(DEBUG == true) {
            error_log(date('[Y-m-d H:i e] '). "Verified IPN: $req ". PHP_EOL, 3, LOG_FILE);
        }
    
    } else if (strcmp ($res, "INVALID") == 0) {
        if(DEBUG == true) {
            error_log(date('[Y-m-d H:i e] '). "Invalid IPN: $req" . PHP_EOL, 3, LOG_FILE);
        }
    }
}