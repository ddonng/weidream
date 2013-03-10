<?php
/*
$kv = new SaeKV();
$kv->init();
$kv->set( 'ddo-nng_@163.com.cn' , 'ddonng' );
echo $kv->get('ddo-nng_@163.com.cn');
*/
/*
$a['public']=1;
$a['on']=0;
$kv->set( 'aa' , serialize($a));
echo serialize($a).'   ';
echo $kv->get( 'aa').'   ';
print_r($a);
*/
/*
$requesturl='http://cqdd.sinaapp.com/api/odp_nation/insert/nation_name=214';

$ch=curl_init($requesturl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$cexecute=curl_exec($ch);
curl_close($ch);

$result = json_decode($cexecute,true);
print_r($result);
//echo $result['data'][0]->nation_id;
echo $result['err_msg'];
echo "<br>nation_name: ".$result['data'][0]['nation_name'];
*/

/*
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
echo time();
$string="13610000000";
$key="CqtbiD204";
$encode_str=encrypt($string,$key);
$decode_str=decrypt($encode_str,$key);
$debase64 = chop(base64_decode($encode_str));

echo "string:".$string."<br>"."key:".$key."<br>"."  encode string: ".$encode_str."<br>"."  decode_string:  ".$decode_str."  debase64 :  ".$debase64;
*/


//print_r(unserialize('a:21:{s:10:"teacher_id";s:1:"1";s:9:"school_id";s:5:"13967";s:12:"deparment_id";s:1:"1";s:8:"staff_id";s:7:"0000168";s:12:"teacher_name";s:3:"sss";s:11:"teacher_pwd";s:32:"5f9a9917d364bdb3fa7f61a5a719b694";s:11:"teacher_sex";s:1:"0";s:16:"is_inside_school";s:1:"1";s:10:"is_working";s:1:"1";s:9:"nation_id";s:1:"1";s:15:"native_place_id";s:1:"2";s:15:"start_work_time";s:10:"1390181313";s:8:"birthday";s:10:"1986-12-22";s:5:"email";s:14:"ddonng@163.com";s:12:"office_phone";s:12:"023-42861081";s:12:"moblie_phone";s:11:"13637977217";s:22:"email_verificaion_code";s:17:"qwfwg33545shkdsjf";s:17:"is_email_verified";s:1:"0";s:13:"register_time";s:1:"0";s:15:"last_login_time";s:1:"0";s:5:"level";s:1:"9";}'));

/*
echo strtotime('1986-12-22');
echo "<br>";
echo date('Y-m-d',strtotime('1986-12-22'));
*/
s

