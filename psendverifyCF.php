<?php
/***************************************************************************\
*  psendverifyCF.php
*  Denison Parreno (2013)
*  Common functions used by p_mailF.php and p_verifyF.php
***************************************************************************/

/** FUNCTIONS **/

//Cleanses the form inputs before processing
function p_check_security($arr)
{
	//Trim surrounding white space of inputs and lower cases all letters
	$arr = p_trim_space($arr); 
	
	//Removes all illegal e-mail characters from 'to' and 'from' inputs (W3 Schools) - consolidate into for loop
	if($arr['to']!=NULL)
	{
		$arr['to'] = filter_var($arr['to'], FILTER_SANITIZE_EMAIL);
		if(!filter_var($arr['to'], FILTER_VALIDATE_EMAIL))
		{
			add_error("Invalid To address");
		}	
	}
	
	if($arr['from']!=NULL)
	{
		$arr['from'] = filter_var($arr['from'], FILTER_SANITIZE_EMAIL);
		
		if(!filter_var($arr['from'], FILTER_VALIDATE_EMAIL))
		{
			add_error("Invalid From address");
		}	
	} 
	
	//Check for no newlines added in to, from, subject header fields
	foreach ($arr as $ind => &$lfield)
	{
		if ($ind == 'to' ||  $ind == 'from' || $ind == 'subject')
		{
			if(preg_match( "/[\r\n]/", $lfield ))
			{
				add_error("Invalid header/s");
			}
		}
	}
	 unset($lfield);
	  
	 //Optional - Restricts allowed email domains to receive/send email
	 //Must create a p_recipient.txt with list of allowable domains e.g. gmail, hotmail
	 /*if($arr['to']!=NULL)
	 {
		//Get list of allowable recipient domains
		$recipient_array = file('p_recipient.txt');
		
		//Trim surrounding whitespaces and lowercase letters
		$recipient_array = p_trim_space($recipient_array);
		
		//split to address using @ as delimiter - domain stored in split_to[1]
		$split_to = explode("@",$arr['to']);
		
		//Variable to indicate whether domain was found 
		$domain_found = 0;
		
		//Check if the To address domain is in the list of recipients
		foreach ($recipient_array as $line_num => $line) 
		{
			if($split_to[1]==$line)
			{
				$domain_found = 1;
				break;
			}
		}
		
		//If to address domain not found in recipient list - print message
		if(!$domain_found)
		{
			die("<br>To field domain is not allowed");
		}
		
	 }*/
	  
	 //Fix XSS + HTTP Header Injection for v1.93
	 foreach ($arr as $ind => &$lfield)
	 {
        //Removes html tags to prevent javascript injections/tricks in headers
		if ($ind == 'to' ||  $ind == 'from' || $ind=='subject')
		{
			$arr[$ind] = strip_tags($lfield);
		}			
      }
	  
	  unset($lfield);
			
	return $arr; 
}	

//trim surrounding spaces of each line  - for string inputs
function p_trim_line($arr)
{
	$lines = explode("<br>", $arr);
	
	foreach ($lines as $ind => &$value)
	{
		$value = trim($value);
	}
	
	$lines = implode("<br>",$lines);
	return $lines;
}

//trim surrounding spaces of each array index (and lower case to and from fields) 
function p_trim_space($arr)
{
	foreach ($arr as $ind => &$value)
	{
		$value = trim($value);
	
		if ($ind=="to" || $ind=="from")
		{
			$value=strtolower($value);
		}
	}
	unset($value);
	return $arr;
}

function p_hashBody($body, $method) 
{
    return base64_encode(pack("H*", hash($method, $body)));     
}

function p_check_emptyField($field)
{
	if ($field==NULL)
	{
		add_error("$field must not be empty.");
		return 0;
	}
	
	else
	{
		return 1;
	}
}

function add_error($s)
{
	require('p_init.php');
	array_push($p_errors,$s);
	return $p_errors;
}

//Prepares message to be signed
function prepare_message($m)
{
		/*CLEAN MESSAGE INPUT - Only want to sign the plain text*/
		//Strip html tags to prepare signing of message content
		$m = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", " ", $m); //Remove empty white lines in message and replace with space.
		
		$m = preg_replace("/&#?[a-z0-9]{2,8};/i","",$m); //Removes special characters such as &nbsp; - http://stackoverflow.com/questions/657643/how-to-remove-html-special-chars
		
		$m = p_trim_line($m); //trim each line using <br> as delimiter
			
		$m = strip_all_tags($m); //remove html tags
		
		$m = trim($m);
		
		$m = preg_replace('!\s+!', ' ', $m); //reduce multiple white space into one white space - Outlook does this when receiving mail - http://stackoverflow.com/questions/2368539/php-replacing-multiple-spaces-with-a-single-space

		return $m;
}
//To remove other tags not accounted for in strip_tags - http://informationideas.com/news/2008/05/19/php-strip_tags-problem/
function strip_all_tags($content)
{
	$content = preg_replace('/\n/',' ',$content);
	$content = preg_replace('/<script.*<\/script>/U',' ',$content);
	$content = preg_replace('/<style.*<\/style>/U',' ',$content);
	$content = strip_tags($content);
	return $content;
}

