<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

function ss( $key )
{
	return isset($_SESSION[$key])?$_SESSION[$key]:false;
}

function ss_set( $key , $value )
{
	return $_SESSION[$key] = $value;
}

function teacher_ss( $key )
{
	return isset($_SESSION['teacher'][$key])?$_SESSION['teacher'][$key]:false;
}

function teacher_ss_set( $key , $value )
{
	return $_SESSION['teacher'][$key] = $value;
}

function is_login()
{
	if( isset( $_COOKIE['PHPSESSID'] ) )
	{
		session_start();
		return teacher_ss('teacher_id') > 0;
	}
	
	return false;
}

function is_admin()
{
	return teacher_ss('level') > 5 ;
}

function rjoin(  $sp , $str , $array )
{
	$ret = array();
	foreach( $array as $key => $value )
	{
		$ret[] = $str.trim($value , $str ).$str;
	}
	
	return join( $sp , $ret );
}

function has_saekv()
{
	if( defined('SAE_ACCESSKEY') && substr( SAE_ACCESSKEY , 0 , 4 ) == 'kapp' ) return false;
 	return in_array( 'SaeKV' , get_declared_classes() );
	//return false;
}

if( !has_saekv() ) @mkdir( AROOT. '__lr3_kv');

function kget( $key )
{
	if( has_saekv() )
	{
		$kv = new SaeKV();$kv->init();
		$value = $kv->get( $key );
		if(is_NULL($value) || $value == "")
			$value=temp_kget_out( $key );
		return $value;
	}
	else
	{
		$keyfile = AROOT. '__lr3_kv' . DS . 'kv-'.md5($key);
		return @unserialize( @file_get_contents($keyfile) );
	}
}

function kset( $key , $value , $backup = TRUE)
{
	if( has_saekv() )
	{
		$kv = new SaeKV();$kv->init();
		//写一份到__meta_setting表中
		if ($backup){
			if( temp_kset_into( $key , $value ) ) return $kv->set( $key , $value );
		}else{
			return $kv->set( $key , $value );
		}
	}
	else
	{
		$keyfile = AROOT. '__lr3_kv' . DS . 'kv-'.md5($key);
		return @file_put_contents($keyfile , serialize( $value )  );
	}
}

/**
Validate an email address.
Provide email address (raw input)
Returns true if the email address has the email 
address format and the domain exists.
URL: http://developer.51cto.com/art/200810/92652_3.htm
*/
function validEmail($email)
{

   $isValid = true;
   $atIndex = strrpos($email, "@");

   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

//Encrypt and Decrypt

function encrypt($string,$key)
{
	$plain_text = trim($string);
	$iv = substr( md5($key),0,mcrypt_get_iv_size(MCRYPT_CAST_256,MCRYPT_MODE_CFB) );
	$c_t = mcrypt_cfb(MCRYPT_CAST_256,$key,$plain_text,MCRYPT_ENCRYPT,$iv);
	return trim(chop(base64_encode($c_t)));
}

function decrypt($string,$key)
{
	$string=trim(chop(base64_decode($string)));
	$iv = substr( md5($key),0,mcrypt_get_iv_size(MCRYPT_CAST_256,MCRYPT_MODE_CFB) );
	$c_t = mcrypt_cfb(MCRYPT_CAST_256,$key,$string,MCRYPT_DECRYPT,$iv);
	return trim(chop($c_t));
}

//Random String
function generate_rand_str($len)
{
	$c="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	srand((double)microtime()*1000000);
	for($i=0;$i<$len;$i++)
	{
		$rand .=$c[rand()%strlen($c)];
	}
	return $rand;
}

function dohash($str1, $str2) {
	return hash('sha256', $str1 . $str2);
}

function send_mail( $email , $subject , $content )
{
	if( c('on_sae') )
	{
		$m = new SaeMail();
				
		$m->quickSend( $email , $subject , $content , c('smtp_account') , c('smtp_password') , c('smtp_server') , c('smtp_port') );
		/*
		$m->setOpt( array(
				 'from' => '重庆工商职业学院教务处项目科 <'.c('smtp_account').'>',
				 'to' => $email,
				 'smtp_host' => c('smtp_server'), 
				 'smtp_username' => c('smtp_account'),  
				 'smtp_password' => c('smtp_password'),  
				 'subject' => $subject,  
				 'content' => $content, 
				 'content_type' => "HTML"
		) ); 
		$ret = $m->send();
		*/
		return $m->errno();
	}
	else
	{
		return @mail( $email , $subject , $content );
	}
}
