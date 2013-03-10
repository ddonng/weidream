<?php
if( !defined('IN') ) die('bad request');

$GLOBALS['config']['site_name'] = '质量工程信息系统';
$GLOBALS['config']['site_domain'] = 'lazyphp3.sinaapp.com';
$GLOBALS['config']['on_sae']=true;

//系统代号 xm，提供区别用户在不同系统中的级别level
$GLOBALS['config']['client_name'] = 'xm';

$GLOBALS['config']['client_id'] = '1111';

$GLOBALS['config']['client_secret'] = '1111';

$GLOBALS['config']['redirect_uri'] = "http://cqtbi.sinaapp.com/oauth";

//不需用户确定finishauthorization，需要则用authorize
$GLOBALS['config']['authorizeURL'] = "http://schoolcloud.sinaapp.com/oauth/authorize";

$GLOBALS['config']['tokenURL'] = "http://schoolcloud.sinaapp.com/oauth/token";
//encrypt and decrypt
$GLOBALS['config']['key'] ='CqD204';

//SMTP Mail
$GLOBALS['config']['smtp_server']="smtp.exmail.qq.com";
$GLOBALS['config']['smtp_account']="xmk@cqtbi.esch.cn";
$GLOBALS['config']['smtp_password']="cqtbid204";
