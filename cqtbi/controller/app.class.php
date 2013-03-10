<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'lib' . DS . 'edudata.class.php' );

class appController extends coreController
{
	private static $edudata = NULL;

	function __construct()
	{
		// 载入默认的
		parent::__construct();
	}

	// login check or something
	
	//返回单例
	public static function getEdudata( $email = NULL,$password = NULL ) {
        if (is_null(self::$edudata) || !isset(self::$edudata)) {
			
            self::$edudata = new edudata( $email,$password );
        }
        return self::$edudata;
	} 

	
}
