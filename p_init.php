<?php
/***************************************************************************\
*  p_init.php script
*  Denison Parreno (2013)
*  Description: Initialises the variables to be used in the simple secure email 
*  solution.
***************************************************************************/

$p_instruction="Verify this message by copying whole message (including codes) and pasting online at http://www.myfinder.com.au/functions/pemail/p_verify_page.html"; //Set the instruction you want to show in the message
$date_time = date_default_timezone_set('Australia/Sydney'); //Set timezone to Australia/Sydney
$p_errors = array(); //array to store errors - do not configure

//Database settings
$db_server = "127.0.0.1";
$db_user = "root";
$db_pwd = "";
$db_name = "mail_storage";

?>
