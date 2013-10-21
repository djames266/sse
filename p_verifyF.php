<?php
/***************************************************************************\
*  p_verifyF.php script
*  Denison Parreno (2013)
*  functions used by p_verify.php script
***************************************************************************/
include ('psendverifyCF.php');

//Procedural Script Function - calls other functions to perform verification
function p_verify($arr)
{
	//Acquire the private key conifguration data
	require_once 'key-cfg.php';
	include('p_init.php'); //initialised variables
	
	//split_array contains the following:
	//[0] => body; [1] => signed To field; [2] => signed From field; [3] => signed message; [4] => signed subject; [5] => instruction [6] => empty string
	$split_array = p_splitMessage($arr);
	if(isset($split_array[1])&&isset($split_array[2])&&isset($split_array[3])&&isset($split_array[4])&&isset($split_array[5])&&isset($split_array[6]))
	{
		$body=$split_array[0];
		$sigT=$split_array[1];
		$sigF=$split_array[2];
		$sigM=$split_array[3];
		$sigS=$split_array[4];
		
		
		$message_tobe_hashed = prepare_message($body);
		
		//Hash the received inputs 
		$hBody = p_hashBody($message_tobe_hashed,'sha256');
		$hTo = p_hashBody($arr['to'],'sha256');
		$hFrom = p_hashBody($arr['from'],'sha256');
		$hSub = p_hashBody($arr['subject'], 'sha256');

		//Verify message using the public key to decrypt signature and compare resulting hash with received hash
		if(isset($p_pub_key))
		{
			echo "<br>Message: ";
			p_verifyHash($sigM,$p_pub_key,$hBody);
			
			if ($arr['to']!=NULL)
			{
				echo "<br>To: ";
				p_verifyHash($sigT,$p_pub_key,$hTo);
			}
			
			if ($arr['from']!=NULL)
			{
				echo "<br>From: ";
				p_verifyHash($sigF,$p_pub_key,$hFrom);
			}
			
			if ($arr['subject']!=NULL)
			{
				echo "<br>Subject: ";
				p_verifyHash($sigS,$p_pub_key,$hSub);
			}
			
			//Check that inputs match those in the database
			p_search_DB($arr['to'],$arr['from'],$arr['subject'],$sigM,$hBody,date("Y-m-d H:i:s"));
		}
		
		else
		{
			$p_errors = add_error("p_verify: Public key is not defined. Cannot decrypt signature.");
		}
	}
	
	else
	{
		echo "Please check that the codes between the markers (---++++++---) and the markers themselves are copied together with the message.";
		$p_errors = add_error("p_verify: Email message has incorrect format. Ensure delimiters are present.");
		//var_dump($p_errors);
	}
	
	
}

/** FUNCTIONS **/

//p_splitMessage takes the message value in the array and splits it using the delimiter
function p_splitMessage($arr)
{
	$delim="---++++++---";
	$pieces=explode($delim, $arr['message']);
	$tPieces = array_map('ltrim', $pieces);
	$tPieces = array_map('rtrim', $tPieces);
	return $tPieces;
}

function p_verifyHash($s,$pk, $h)
{
	//Decrypt data using public key
	$key = sprintf("-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----", wordwrap($pk, 64, "\n", true));
	
	if((openssl_verify($h, base64_decode($s), $pk)))
	{
		echo "Pass";
	}
	
	else
	{
		echo "Fail";
	}
	
	return;
}
	
function p_search_DB($t,$f,$s,$c,$b,$d)
{
	include('p_init.php'); //initialised variables
	
	//$t - to field
	//$f - from field
	//$s - subject field
	//$c - message signature
	//$b - body hash
	//$d - date/time message was accessed in DB to be verified
	
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
			
		//If email with this vkey (signature) is not found in database - add_error
		if (!($stmt->rowCount())) 
		{
			$p_errors = add_error('p_verify: Warning, email message not found in the database');
		}
		
		//Record was found in database - add date accessed for verification
		else
		{
			$stmt = $dbConnection->prepare("UPDATE email_history SET date_accessed='$d' WHERE vkey='$c'");
			$stmt->execute();
			//Optional print statement
		}
		
		$dbConnection = null; //close database connection
	}
	
	catch(PDOException $e)
	{
		$p_errors = add_error($e->getMessage()."<br/>");
	}
}
?>
