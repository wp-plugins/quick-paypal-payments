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
sort($tabs);
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
    $qpp = qpp_get_stored_options($id);
    qpp_generate_csv();
	
    if( isset($_POST['qpp_emaillist'])) {
        $message = get_option('qpp_messages'.$id);
        $messageoptions = qpp_get_stored_msg();
        $content = qpp_messagetable ($id,'checked');
        $title = $id; if ($id == '') $title = 'Default';
        $title = 'Payment List for '.$title.' as at '.date('j M Y'); 
        global $current_user;
        get_currentuserinfo();
        $qpp_email = $current_user->user_email;
        $headers = "From: {<{$qpp_email}>\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=\"utf-8\"\r\n";	
        wp_mail($qpp_email, $title, $content, $headers);
        qpp_admin_notice('Message list has been sent to '.$qpp_email.'.');
    }
    
    if (isset($_POST['qpp_reset_message'.$id])) delete_option('qpp_messages'.$id);
	
    if( isset( $_POST['Submit'])) {
        $options = array( 'messageqty','messageorder','showaddress');
        foreach ( $options as $item) $messageoptions[$item] = stripslashes($_POST[$item]);
        update_option( 'qpp_messageoptions', $messageoptions );
        qpp_admin_notice("The message options have been updated.");
    }
	
    if( isset($_POST['qpp_delete_selected'])) {
        $id = $_POST['formname'];
        $message = get_option('qpp_messages'.$id);
        $count = count($message);
        for($i = 0; $i <= $count; $i++) {
            if ($_POST[$i] == 'checked') {
                unset($message[$i]);
            }
        }
        $message = array_values($message);
        update_option('qpp_messages'.$id, $message ); 
        qpp_admin_notice('Selected payments have been deleted.');
    }
    
    $messageoptions = qpp_get_stored_msg();
    $fifty = $hundred = $all = $oldest = $newest = '';
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
    &nbsp;&nbsp;
    <input style="margin:0; padding:0; border:none;" type="checkbox" name="showaddress" value="checked" ' . $messageoptions['showaddress'] . ' /> Show Addresses
    &nbsp;&nbsp;
    <input type="submit" name="Submit" class="button-secondary" value="Update options" />
    </form></p>';
    $dashboard .='<form method="post" id="download_form" action="">';
    $dashboard .= qpp_messagetable($id,'');
    $dashboard .='<input type="hidden" name="formname" value = "'.$id.'" />
    <input type="submit" name="download_qpp_csv" class="button-primary" value="Export to CSV" />
    <input type="submit" name="qpp_emaillist" class="button-primary" value="Email List" />
    <input type="submit" name="qpp_reset_message" class="button-secondary" value="Delete All" onclick="return window.confirm( \'Are you sure you want to delete all the payment details?\' );"/>
    <input type="submit" name="qpp_delete_selected" class="button-secondary" value="Delete Selected" onclick="return window.confirm( \'Are you sure you want to delete the selected payment details?\' );"/>
    </form></div></div>';
    echo $dashboard;
}