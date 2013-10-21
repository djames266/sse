<?php
/***************************************************************************\
*  p_verify.php script
*  Denison Parreno (2013)
*  Description: Verifies a received email message (can also verify to, from 
*  and subject headers) by copying the message into an html form
*  How it works:
*  v_array stores inputs from an html form. These inputs are
*  to address, from address, subject and message.
*  After the inputs pass a security check (sanitisation), p_verify() is called 
*  which verifies whether the message content (including to, from and subject headers) 
*  is the same as it was originally sent(i.e. not tampered with during transit).
***************************************************************************/
include ('p_verifyF.php');

//Email headers
$v_array = array("to" => $_POST['to'],"subject" => $_POST['subject'], "message" => $_POST['message'], "from" => $_POST['from']);

//Validate inputs before verifying mail
$clean_v_array = p_check_security($v_array);

p_verify($clean_v_array);

?>
