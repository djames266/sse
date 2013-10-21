<?php
/***************************************************************************\
*  p_mailF.php script
*  Denison Parreno (2013)
*  Description: Signs an email and adds the verification signatures and instruction lines to the email message body
*  How it works: 
*  $arr is an array of inputs which are
*  to address, from address, subject and message (and headers if present).
*  After the inputs pass a security check (sanitisation), p_mail() adds the signatures to the original message body
*  p_mail() can handle sending multipart content emails (html and text)
*  Important Notes:
*  Create an array of header inputs e.g. $m_array = array("to" => $_POST['to'],"subject" => $_POST['subject'], 
*  "message" => $_POST['message'], "from" => $_POST['from'], "headers" => "")
*  Replace PHP's mail() function with p_mail() and use $m_array as the input to p_mail that is, p_mail($m_array).
***************************************************************************/
require_once('psendverifyCF.php'); //functions common to sending and verifying mail

//p_mail is the procedural script function which adds the digital signatures to the email message
//p_mail also stores sent emails into a database

function p_mail($arr)
{	
	require('key-cfg.php'); //key configurations
	include('p_init.php'); //initialised variables
	
	$arr = p_check_security($arr); //Last 3 headers in array MUST be FROM, MIME, Content-Type (in that sequence)
	
	//Define delimiter 
	$p_delim="---++++++---";
	
	//Create boundary to separate plaintext from html message
	$p_boundary = uniqid('np');
	
	//Check if fields are not null
	if ($arr['to']!=NULL && $arr['from']!=NULL && $arr['message']!=NULL)
	{	
		//Check if inputs contain delimiter - if so, replace them with space - else verifier will break
		$arr['message'] = trim(str_replace($p_delim, "", $arr['message']));
		
		//Check if arr['headers'] is already defined - to be fixed - to send html/text simultaneously, headers must be From, MIME-Version,Content-Type
		if($arr['headers']!=NULL)
		{
			$p_headers = $arr['headers'];

			//Format : FROM, MIME, Content-Type (last three headers)
			//Replace content-type with defined boundaries - used to display multipart email (html or text dependent on recipient's email client)
			$p_headers = preg_replace('/Content-Type:.*/',"Content-Type: multipart/alternative;boundary=" . $p_boundary . "\r\n", $p_headers);
		}
		
		//Build default header
		else
		{			
			//Build the header using to and from fields, and boundary
			$p_headers = p_buildHeader($arr['to'],$arr['from'],$p_boundary);
		}
		
		$message_tobe_hashed = prepare_message($arr['message']);
		
		//replace carriage returns with breaks for html display
		$arr['message'] = str_replace("\r", "<br>", $arr['message']);
		
		//For each line in the message - trim surrounding whitespace
		$arr['message'] = p_trim_line($arr['message']);

		//Hash headers and message using sha256
		$hMessage = p_hashBody($message_tobe_hashed,'sha256'); //hash message after removing empty white lines
		$hTo = p_hashBody($arr['to'],'sha256');
		$hFrom = p_hashBody($arr['from'],'sha256');
		$hSub = p_hashBody($arr['subject'],'sha256');
		
		//Sign hashes using private key
		if (isset($p_priv_key))
		{
			$sigM = p_sign($hMessage,$p_priv_key);
			$sigT = p_sign($hTo,$p_priv_key);
			$sigF = p_sign($hFrom,$p_priv_key);
			$sigS = p_sign($hSub,$p_priv_key);
			
			//Build new message with signatures
			$messageNew = p_buildMessage($p_delim,$sigT, $sigF, $sigM, $p_instruction, /*$p_stripped_message,*/ $arr['message'], $p_boundary, $sigS);

			//Send email
			$mail_result = mail($arr['to'],$arr['subject'],$messageNew,$p_headers);

			//Add sent email record to database
			p_addto_DB($arr['to'],$arr['from'],$arr['subject'],$hMessage,$sigM,date("Y-m-d H:i:s"));
			print_r($p_errors);
			return true;
		}
		
		else
		{
			$p_errors = add_error("p_mail: Cannot sign. Private key not defined");
			print_r($p_errors);
			return false;
		}
		
	}
	
	else
	{
		//Add to p_errors array for later printing
		$p_errors = add_error("p_mail: Please fill in the 'To' AND 'From' AND 'Message' fields");
		print_r($p_errors);
		return false;
	}
	
	

}

//p_buildHeader builds the header using the to, from and boundary values
function p_buildHeader($t,$f,$bn)
{
	$b_headers = "MIME-Version:1.0\r\n";
	$b_headers .= "From: " . $f . "\r\n";
	$b_headers .= "Content-Type: multipart/alternative;boundary=" . $bn . "\r\n"; //The test site code needs to have this in the header!!!
	return $b_headers;
} 

//p_buildMessage builds the new message in both plaintext and html - separated by boundaries 
//email client will revert to viewing plaintext version if unable to view html version of the message
function p_buildMessage($d, $st, $sf, $s, $i, /*$m,*/ $m_html, $bn, $ss)
{
			//Signature arrangement:
			
			//To signature
			//From signature
			//Message signature
			//Subject signature
			
			//plaintext version
			$msg  = "\r\n\r\n--" . $bn . "\r\n";
			$msg .= "Content-type: text/plain;charset=utf-8\r\n\r\n";
			$msg .= $m_html."\r\n"."\r\n".$d."\r\n".$st."\r\n".$d."\r\n".$sf."\r\n".$d."\r\n".$s."\r\n".$d."\r\n".$ss."\r\n".$d."\r\n".$i."\r\n".$d;
			$msg .= "\r\n\r\n--" . $bn . "\r\n";
			
			//html version
			$msg .= "Content-type: text/html;charset=utf-8\r\n\r\n";
			$msg .= $m_html;
			$msg .= "<br>"."<br>".$d."<br>".$st."<br>".$d."<br>".$sf."<br>".$d."<br>".$s."<br>".$d."<br>".$ss."<br>".$d."<br>".$i."<br>".$d;
			$msg .= "\r\n\r\n--" . $bn . "--"; 	

		return $msg;
}

//p_addto_DB adds sent email to database
function p_addto_DB($t,$f,$s,$m,$c,$d)
{  
	include('p_init.php');
	
	try
	{	
		//Create database connection using PDO - to prevent sql injections
		$dbConnection = new PDO("mysql:dbname=$db_name;host=$db_server;charset=utf8", $db_user, $db_pwd);
		
		//This makes sure the statement and the values aren't parsed by PHP before sending it to the MySQL server (giving a possible attacker no chance to inject malicious SQL).
		$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 
		
		//This makes sure the statement and the values aren't parsed by PHP before sending it to the MySQL server (giving a possible attacker no chance to inject malicious SQL).
		$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //Prevents script from stopping with fatal error - gives an opportunity to catch errors
		
		$stmt = $dbConnection->prepare("SELECT * FROM email_history WHERE vkey='$c'");
		$stmt->execute();
			
		//If email with this vkey (signature) is not found in database - insert it
		if (!($stmt->rowCount())) 
		{
			$preparedStatement = $dbConnection->prepare('INSERT INTO email_history (to_field, from_field, sub_field, body_field, vkey, date_created) VALUES (:t, :f, :s, :m, :c, :d)');
			$preparedStatement->execute(array(':t' => $t, ':f' => $f, ':s' => $s, ':m' => $m, ':c' => $c, ':d' => $d));
		}
			
		else
		{
			$p_errors = add_error("p_mail: Warning, record already exists in database");
		}
		
		$dbConnection = null; //close database connection
	}
	
	catch(PDOException $e)
	{
		$p_errors = add_error($e->getMessage()."<br/>");
	}
}

//Sign data using private key
function p_sign($s,$key) 
{ 
	if (openssl_sign($s, $signature, $key))
	{			
		return base64_encode($signature);
	}
	
	else
	{
		$p_errors = add_error("p_mail: Cannot sign");
	}
}	

?>
