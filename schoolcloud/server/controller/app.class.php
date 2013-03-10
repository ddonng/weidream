<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'lib'.DS.'oauth'.DS.'OAuth2Storage.php');

class appController extends coreController
{
	//Oauth2对象实例
	private static $oauth = NULL;

	//Oauth2StoragePDO对象实例
	private static $oauth2Storage =NULL;

	function __construct()
	{
		// 载入默认的
		parent::__construct();
		session_start();
	}

	// login check or something


	//返回Oauth2对象
	//在appController中实现是因为apiController中也要使用

	public static function getOauth() {
        if (is_null(self::$oauth) || !isset(self::$oauth)) {
			
            self::$oauth = 	new OAuth2( self::getOauth2Storage() );
        }
        return self::$oauth;
	} 
	
	public static function getOauth2Storage()
	{
		if (is_null(self::$oauth2Storage) || !isset(self::$oauth2Storage)) {
			
			//在CROOT中core.function.php加载了db.sae.function.php与db.funcion.php两个数据库函数
            self::$oauth2Storage = new OAuth2Storage( db() );
        }
        return self::$oauth2Storage;
	
	}





}

