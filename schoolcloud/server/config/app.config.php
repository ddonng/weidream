<?php
$GLOBALS['config']['site_name'] = 'Education Open Data Platform';
$GLOBALS['config']['site_domain'] = $_SERVER['HTTP_HOST'];
$GLOBALS['config']['site_url'] = 'http://' . $_SERVER['HTTP_HOST'];

$GLOBALS['config']['token_table_name'] = 'odp_teacher';
$GLOBALS['config']['token_account_field'] = 'email';
$GLOBALS['config']['token_password_field'] = 'teacher_pwd';
$GLOBALS['config']['client_name_field'] = 'client_name';
$GLOBALS['config']['teacher_level_table'] = 'odp_level';

$GLOBALS['config']['display_mysql_error'] = TRUE;
