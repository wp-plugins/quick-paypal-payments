<?php
$qpp_setup = qpp_get_stored_setup();
$tabs = explode(",",$qpp_setup['alternative']);
$firsttab = reset($tabs);
echo '<div class="wrap">';
echo '<h1>Quick Paypal Payments</h1>';
if ( isset ($_GET['tab'])) {qpp_messages_admin_tabs($_GET['tab']); $tab = $_GET['tab'];} else {qpp_messages_admin_tabs($firsttab); $tab = $firsttab;}
qpp_show_messages($tab);
echo '</div>';

function qpp_messages_admin_tabs($current = 'default') { 
	$qpp_setup = qpp_get_stored_setup();
	$tabs = explode(",",$qpp_setup['alternative']);
	array_push($tabs, 'default');
	$message = get_option( 'qpp_message' );
	echo '<h2 class="nav-tab-wrapper">';
	foreach( $tabs as $tab ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		if ($tab) echo "<a class='nav-tab$class' href='?page=quick-paypal-payments/quick-paypal-messages.php&tab=".$tab."'>$tab</a>";
		}
	echo '</h2>';
	}
function qpp_show_messages($id) {
	if ($id == 'default') $id='';
	$qpp_setup = qpp_get_stored_setup();
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
        'NZD'=>'&#x24;','PHP'=>'&#8369;',
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
    $qpp = qpp_get_stored_options($id);
	qpp_generate_csv();
	if (isset($_POST['qpp_reset_message'.$id])) delete_option('qpp_messages'.$id);
	if( isset( $_POST['Submit'])) {
		$options = array( 'messageqty','messageorder');
		foreach ( $options as $item) $messageoptions[$item] = stripslashes($_POST[$item]);
		update_option( 'qpp_messageoptions', $messageoptions );
		qpp_admin_notice("The message options have been updated.");
		}
	$messageoptions = qpp_get_stored_msg();
	$showthismany = '9999';
	if ($messageoptions['messageqty'] == 'fifty') $showthismany = '50';
	if ($messageoptions['messageqty'] == 'hundred') $showthismany = '100';
	$$messageoptions['messageqty'] = "checked";
	$$messageoptions['messageorder'] = "checked";
	$dashboard = '<form method="post" action="">
	<p><b>Show</b> <input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="fifty" ' . $fifty . ' /> 50 
	<input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="hundred" ' . $hundred . ' /> 100 
	<input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="all" ' . $all . ' /> all messages.&nbsp;&nbsp;
	<b>List</b> <input style="margin:0; padding:0; border:none;" type="radio" name="messageorder" value="oldest" ' . $oldest . ' /> oldest first 
	<input style="margin:0; padding:0; border:none;" type="radio" name="messageorder" value="newest" ' . $newest . ' /> newest first
	&nbsp;&nbsp;<input type="submit" name="Submit" class="button-secondary" value="Update options" />
	</form></p>';
	$options = qpp_get_stored_options ($id);
	$message = get_option('qpp_messages'.$id);
	if(!is_array($message)) $message = array();
	$title = $id; if ($id == '') $title = 'Default';
	$dashboard .= '<div class="wrap"><div id="qpp-widget">';
	$dashboard .= '<table cellspacing="0"><tr><th>Date Sent</th>';
	foreach (explode( ',',$options['sort']) as $name) {
        $title='';
		switch ( $name ) {
			case 'field1': $title=$options['inputreference'];break;
			case 'field2': $title=$options['quantitylabel'];break;
			case 'field3': $title=$options['inputamount'];break;
			case 'field4': if ($options['usestock']) $title=$options['stock'];break;
			case 'field5': if ($options['use_options']) $title=$options['optionlabel'];break;
			case 'field6': if ($options['usecoupon']) $title=$options['couponblurb'];break;
			}
        $dashboard .= '<th>'.$title.'</th>';
        }	
    $dashboard .= '</tr>';
	if ($messageoptions['messageorder'] == 'newest') {
        foreach(array_reverse( $message ) as $value) {
            if ($count < $showthismany ) {
                if ($value['date']) $report = 'messages';
                $content .= '<tr><td>'.strip_tags($value['field0']).'</td>';
                foreach (explode( ',',$options['sort']) as $name) {
                    $title='';
$amount = preg_replace ( '/[^.,0-9]/', '', $value['field3']);

                    
switch ( $name ) {
                        case 'field1': $title=$value['field1'];break;
                        case 'field2': $title=$value['field2'];break;
                        case 'field3': $title=$b.$amount.$a;break;
                        case 'field4': if ($options['usestock']) $title=$value['field4'];break;
                        case 'field5': if ($options['use_options']) $title=$value['field5'];break;
                        case 'field6': if ($options['usecoupon']) $title=$value['field6'];break;
                        }
                    $content .= '<td>'.$title.'</td>';
                    }
                $content .='</tr>';
                $count = $count+1;
                }
            }
		}
	else {
	   foreach($message as $value) {
           if ($count < $showthismany ) {
               if ($value['date']) $report = 'messages';
               $content .= '<tr><td>'.strip_tags($value['field0']).'</td>';
               foreach (explode( ',',$options['sort']) as $name) {
                   $title='';
$amount = preg_replace ( '/[^.,0-9]/', '', $value['field3']);

                   
switch ( $name ) {
                       case 'field1': $title=$value['field1'];break;
                       case 'field2': $title=$value['field2'];break;
                       case 'field3': $title=$b.$amount.$a;break;
                       case 'field4': if ($options['usestock']) $title=$value['field4'];break;
                       case 'field5': if ($options['use_options']) $title=$value['field5'];break;
                       case 'field6': if ($options['usecoupon']) $title=$value['field6'];break;
                        }
                   $content .= '<td>'.$title.'</td>';
                    }
               $content .='</tr>';
               $count = $count+1;
            }
			}
		}	
	if ($report) $dashboard .= $content.'</table>';
	else $dashboard .= '</table><p>No messages found</p>';
	$dashboard .='<form method="post" id="download_form" action=""><input type="hidden" name="formname" value = "'.$id.'" /><input type="submit" name="download_qpp_csv" class="button-primary" value="Export to CSV" /> <input type="submit" name="qpp_reset_message'.$id.'" class="button-primary" value="Delete Messages" onclick="return window.confirm( \'Are you sure you want to delete the messages for '.$title.'?\' );"/></form></div></div>';		
	echo $dashboard;
	}