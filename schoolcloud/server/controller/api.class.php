<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );

define( 'LR_API_TOKEN_ERROR' , 10001 );
define( 'LR_API_USER_ERROR' , 10002 );
define( 'LR_API_DB_ERROR' , 10004 );
define( 'LR_API_NOT_IMPLEMENT_YET' , 10005 );
define( 'LR_API_ARGS_ERROR' , 10006 );
define( 'LR_API_VERIFY_ARGS_EMPTY' , 10007 );
define( 'LR_API_DB_EMPTY_RESULT' , 10008 );
define( 'LR_API_VERIFY_NAME_ERROR' , 10009 );
define( 'LR_API_VERIFY_ERROR' , 10010 );

class apiController extends appController
{
	
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		
	}
	
	public function index()
	{
		//print_r( $_REQUEST );
		$table = z(t(v('_table')));
		$action = z(t(v('_interface')));
		
		if( strlen( $table ) < 1 || strlen( $action ) < 1 )
			return $this->send_error( LR_API_ARGS_ERROR , 'BAD ARGS' );
			
			
		// user define code
		//if( $my_code = get_var( "SELECT `code` FROM `__meta_code` WHERE `table` = '" . s( $table ) . "' AND `action` = '" . s($action) . "' LIMIT 1" ) )
		if($my_code=get_my_action_code($table,$action))
		{
			return eval(  $my_code  );
			exit;
		}	

		//接入系统验证，返回teacher_id，level  api/teacher/verify/
		if( ($table == 'teacher') && ($action == 'verify') ) return $this->verify();

		//返回department、nation、native_place  api/general/get/
		if( ($table == 'general') && ($action=='get') ) return $this->get_general();
		
		// check table
		$tables = get_table_list(db());
		
		if( !in_array( $table , $tables ) )
			return $this->send_error( LR_API_ARGS_ERROR , 'TABLE NOT EXISTS' );
			
		if( ($table == c('token_table_name')) && ($action == 'get_token') )	return $this->get_token();

		$fields = get_fields( $table );
		
		$ainfo = unserialize( kget( 'msetting_' . $table . '_' . $action ));
		
		$in_code =   kget( 'iosetting_input_' . $table . '_' . $action  )  ;
		$out_code =   kget( 'iosetting_output_' . $table . '_' . $action  ) ;
		
		// run user defined input fliter
		if( strlen($in_code) > 0 ) eval( $in_code );
		
		
		if( $ainfo['on'] != 1 )
			return $this->send_error( LR_API_ARGS_ERROR , 'API NOT  AVAILABLE' );

		if( $ainfo['public'] != 1 )
			//改用Oauth2，默认检查user_id
			$this->check_access_token(true);
		
		$requires = array();
		$inputs = array();
		$outs = array();
		$likes = array();
		$equal = array();
		
		
		foreach( $fields as $field )
		{
			$finfo = unserialize( kget( 'msetting_' . $table . '_' . $action .  '_' . $field  ) ) ;
			
			
			if( $finfo['required'] == 1 ) $requires[] = $field;
			if( $finfo['input'] == 1 ) $inputs[] = $field;
			if( $finfo['output'] == 1 ) $outputs[] = $field;
			if( $finfo['like'] == 1 ) $likes[] = $field;
			if( $finfo['equal'] == 1 ) $equals[] = $field;	
		}
		
		// check require
		if( count(  $requires ) > 0 )
		{
			foreach( $requires as $require )
			{
				if( strlen( v($require) ) < 1 ) return $this->send_error( LR_API_ARGS_ERROR ,  z(t($require)) .' FIELD REQUIRED' );
			}
		}
		
		// build sql
		
			switch( $action )
			{
				case 'insert':
					
					if( count( $inputs ) < 1 )  $this->send_error( LR_API_ARGS_ERROR , 'INPUT MUST HAS 1 FIELD AT LEAST' );
					if( count( $outputs ) < 1 )  $this->send_error( LR_API_ARGS_ERROR , 'OUTPUT MUST HAS 1 FIELD AT LEAST' );
					
					foreach( $inputs as $input )
					{
						$dsql[] = "'" . s(v($input)) . "'";
					}
					
					$sql = "INSERT INTO `" . s($table) . "` ( " . rjoin( ' , ' , '`' , $inputs ) . " ) VALUES ( " . join( ' , ' ,  $dsql ) . " )";
					
					//echo $sql;
					
					run_sql( $sql );
					
					if( mysql_errno() != 0 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					
					$lid = last_id();

					if( $lid < 1 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR' . mysql_error() );
					
					//函数lib/app.function.php中
					$pri_name=get_pri_name($table);

					//将默认的id替换为表的primary key
					if( !$data = get_data( "SELECT " . rjoin( ' , ' , '`' , $outputs ). " FROM `" . s( $table ) . "` WHERE `".$pri_name."` = '" . intval( $lid ) . "'" , db() ))
						$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					else
					{
						if( strlen( $out_code ) > 0 ) eval( $out_code );
						$this->send_result( $data );
					}	
						
	
					
					break;
				
				case 'update':
					if( count( $inputs ) < 1 )  return $this->send_error( LR_API_ARGS_ERROR , 'INPUT MUST HAS 1 FIELD AT LEAST' );
					if( count( $requires ) < 1 )  return $this->send_error( LR_API_ARGS_ERROR , 'REQUIRE MUST HAS 1 FIELD AT LEAST' );
					
					foreach( $inputs as $input )
					{
						if( !in_array( $input , $likes ) && !in_array( $input , $equals ) )
						{
							if( isset( $_REQUEST[$input] ) )
								$dsql[] = " `" . s($input) . "` = '" . s(v($input)) . "' ";
						}
						else
						{
							if( in_array( $input , $likes ) )
							{
								$wsql[] = " `" . s( $input ) . "` LIKE '%" . s(v($input)) . "%' ";
							}
							else
							{
								$wsql[] = " `" . s( $input ) . "` = '" . s(v($input)) . "' ";
							}
						}
					}
	
					if( !isset($dsql) || !isset($wsql) ) return $this->send_error( LR_API_ARGS_ERROR , 'INPUT AND LIKE/EQUALS MUST HAS 1 FIELD AT LEAST' );
					
					//函数lib/app.function.php中
					$pri_name = get_pri_name($table);

					//获取所更新的id号
					$sql_GET_ID = "SELECT $pri_name FROM $table WHERE " . join( ' AND ' , $wsql );
					$lid = get_var($sql_GET_ID,db());

					if( $lid < 1 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );

					$sql = "UPDATE `" . s( $table ) . "` SET " . join( ' , ' , $dsql ) . " WHERE $pri_name = ".$lid;
					
					//echo $sql ;
					run_sql( $sql );
					
					if( mysql_errno() != 0 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					
					
					//$lid = intval(v('id'));//这里？？怎么会是用request获取
					
					//将默认的id替换为表的primary key
					if( !$data = get_data( "SELECT " . rjoin( ' , ' , '`' , $outputs ). " FROM `" . s( $table ) . "` WHERE `".$pri_name."` = '" . intval( $lid ) . "'" ))
						$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					else
					{
						if( strlen( $out_code ) > 0 ) eval( $out_code );
						$this->send_result( $data );
					}	
						
					
					break;
					
					
				case 'remove':
					
					if( count( $inputs ) < 1 )  return $this->send_error( LR_API_ARGS_ERROR , 'INPUT MUST HAS 1 FIELD AT LEAST' );
					if( count( $requires ) < 1 )  return $this->send_error( LR_API_ARGS_ERROR , 'REQUIRE MUST HAS 1 FIELD AT LEAST' );
					
					foreach( $inputs as $input )
					{
						if( in_array( $input , $likes ) )
						{
							$wsql[] = " `" . s( $input ) . "` LIKE '%" . s(v($input)) . "%' ";
						}
						elseif( in_array( $input , $equals ) )
						{
							$wsql[] = " `" . s( $input ) . "` = '" . s(v($input)) . "' ";
						}
					}
	
					if( !isset($wsql) ) return $this->send_error( LR_API_ARGS_ERROR , 'INPUT AND LIKE/EQUALS MUST HAS 1 FIELD AT LEAST' );
					
					if( count( $outputs ) > 0 )
					{
						$sql = "SELECT " . rjoin( ',' , '`' , $outputs ) . " FROM `" . s( $table ) . "` WHERE  ". join( ' AND ' , $wsql );			
						$data = get_line( $sql );
						
						if( mysql_errno() != 0 ) 
							return $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					}
					
					$sql = "DELETE FROM `" . s( $table ) . "` WHERE " . join( ' AND ' , $wsql );			
					run_sql( $sql );
					if( mysql_errno() != 0)
						$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					else	
						if(  count( $outputs ) < 1 ) 
							return $this->send_result( array( 'msg' => 'ok' ) );
						else
						{
							if( strlen( $out_code ) > 0 ) eval( $out_code );
							return 	$this->send_result( $data );
						}
												
					break;
					
					
				
				
				
				
				case 'list':
				default:
					$since_id = intval( v('since_id') );
					$max_id = intval( v('max_id') );
					$count = intval(v('count'));
					
					$order = strtolower(z(t(v('ord'))));
					$by = strtolower(z(t(v('by'))));
					
					
					if( $order == 'asc' ) $ord = ' ASC ';
					else $ord = ' DESC ';
					
					if( strlen($by) > 0 )
						$osql = ' ORDER BY `' . s( $by ) . '` ' . $ord . ' ';
					else
						$osql = '';
					
					if( $count < 1 ) $count = 10;
					if( $count > 100 ) $count = 100;
					
					
					if( count( $outputs ) < 1 )  $this->send_error( LR_API_ARGS_ERROR , 'OUTPUT MUST HAS 1 FIELD AT LEAST' );
					
					$sql = "SELECT " . rjoin( ',' , '`' , $outputs ) . " FROM `" . s( $table ) . "` WHERE 1 ";
					
					//函数lib/app.function.php中
					$pri_name = get_pri_name($table);

					//将id替换为$pri_name
					if( $since_id > 0 ) $wsql = " AND `$pri_name` > '" . intval( $since_id ) . "' ";
					elseif( $max_id > 0 ) $wsql = " AND `$pri_name` < '" . intval( $max_id ) . "' ";
					
					if( (count( $inputs ) > 0) && ((count($likes)+count($equals)) > 0) )
					{
						// AND `xxx` == $xxx
						if( count($likes) > 0 )
						{
							foreach( $likes as $like )
							{
								if( z(t(v($like))) != '' )
										$wwsql[] = " AND `" . s( $like ) . "` LIKE '%" . s(v($like)) . "%' ";
							}
						}
						
						if( count($equals) > 0 )
						{
							foreach( $equals as $equal )
							{
								if( z(t(v($equal))) != '' )
								$wwsql[] = " AND `" . s( $equal ) . "` = '" . s(v($equal)) . "' ";
							}
						}
						
						if( isset( $wwsql ) )
						$wsql = $wsql . join( ' ' , $wwsql );
					}
					
					
					$sql = $sql . $wsql . $osql .  " LIMIT " . $count ;
					
					
					//echo $sql;
					if($idata = get_data( $sql ))
					{

						$first = reset( $idata );
						$max_id = $first[$pri_name];
						$min_id = $first[$pri_name];
						
						foreach( $idata as $item )
						{
							if( $item[$pri_name] > $max_id ) $max_id = $item[$pri_name];
							if( $item[$pri_name] < $min_id ) $min_id = $item[$pri_name];
						}
						
						$data = array( 'items' => $idata , 'max_id' => $max_id , 'min_id' => $min_id );
					}
					else
						$data = $idata;
					
					
					
					
					if( mysql_errno() != 0  )
						return $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					else
					{
						if( strlen( $out_code ) > 0 ) eval( $out_code );
						return $this->send_result( $data );
	
					}
					
					
							
					
						
					
			}
		
		
		//return $this->send_error( LR_API_ARGS_ERROR , 'FIELD NOT EXISTS' );
			
			
		
	}
	

	public function get_token()
	{
		$token_account_field = c('token_account_field');
		$token_password_field = c('token_password_field');
		$token_table_name = c('token_table_name');
		$client_name_field = c('client_name_field');
		$teacher_level_table = c('teacher_level_table');
		
		$account = z(t(v($token_account_field)));
		$password = z(t(v($token_password_field)));
		$client_name = z(t(v($client_name_field)));
		$token_table_name  = z(t($token_table_name)); 
		$teacher_level_table = z(t($teacher_level_table));

		$sql = "SELECT * FROM `" . s( $token_table_name ) . "` WHERE `" . s($token_account_field) . "` = '" . s( $account ) . "' AND `" . s($token_password_field) . "` = '" . md5( $password ) . "' LIMIT 1";
		
		if( $teacher = get_line( $sql ) )
		{
			
			session_start();
			$token = session_id();
			$_SESSION['token'] = $token;
			$_SESSION['uid'] = $teacher['teacher_id'];
			$_SESSION['account'] = $teacher[c('token_account_field')];
			
			//同时返回管理级别

			$stmt = "SELECT `level` FROM `". s($teacher_level_table) ."` WHERE `client_name` = '". s($client_name) ."' AND teacher_id = ". s($teacher['teacher_id']);

			if($teacher_level = get_var($stmt))
			{
				return $this->send_result( array( 'token' => $token , 'uid' => $teacher['teacher_id'] ,'level' => $teacher_level) );
			}else{
				return $this->send_result( array( 'token' => $token , 'uid' => $teacher['teacher_id'] ) );
			}
			
		}
		else
		{
			return $this->send_error( LR_API_TOKEN_ERROR , 'BAD ACCOUNT OR PASSWORD' );
		}
		
	}
	
	private function check_token()
	{
		$token = z(t(v('token')));
		if( strlen( $token ) < 2 ) return $this->send_error( LR_API_TOKEN_ERROR , 'NO TOKEN' );
		
		session_id( $token );
		session_start();
		
		if( $_SESSION['token'] != $token ) return $this->send_error( LR_API_TOKEN_ERROR , 'BAD TOKEN' );
	}
	
	//check access_token

	private function check_access_token($need_user_id=false)
	{
		$oauth = parent::getOauth();
		$token = $oauth->getBearerToken();
		$result = $oauth->verifyAccessToken($token,$need_user_id);
		if($token!=$result['oauth_token'])
		{
			return $this->send_error( LR_API_TOKEN_ERROR , $result );
		}
	}

	public function verify()
	{
		$email = z(t(v('email')));
		//pwd已经md5处理
		$teacher_pwd = z(t(v('teacher_pwd')));
		$client_name = z(t(v('client_name')));
		
		if($email=="" || $teacher_pwd=="" ||$client_name=="")
			return $this->send_error( LR_API_VERIFY_ARGS_EMPTY , 'EMPTY ARGS' );

		//此使用了kvdb缓存
		//因此，对数据进行操作要使用封装的函数，同时写入kvdb
		$teacher_detail = get_teacher_detail( $email,$teacher_pwd,$client_name );

		if( is_null($teacher_detail) || !isset($teacher_detail) )
		{
			return $this->send_error( LR_API_VERIFY_NAME_ERROR , '不存在此用户' );
		}else{
			//验证密码
			if($teacher_pwd == $teacher_detail['teacher_pwd'])
			{
				/*
				*验证成功，返回数据
				*这里需要进一步处理
				*拼凑数组，返回更多的数据(pwd与mobile_phone等私密数据选择性返回)
				*/
				return $this->send_result($teacher_detail);

			}else{
				return $this->send_error( LR_API_VERIFY_ERROR , '用户名与密码不匹配' );
			}
		}

		
	}
	
	public function get_general()
	{
		//Oauth2,不需要user_id
		$this->check_access_token(false);
		$general =Array();
		$general['department'] = kget_all_department();
		$general['nation'] = kget_all_nation();
		$general['native_place'] = kget_all_native_place();

		return $this->send_result($general);
	}


	public function send_error( $number , $msg )
	{	
		$obj = array();
		$obj['err_code'] = intval( $number );
		$obj['err_msg'] = $msg;
		
		header('Content-type: application/json');
		die( json_encode( $obj ) );
	}
	
	public function send_result( $data )
	{ 
		$obj = array();
		$obj['err_code'] = '0';
		$obj['err_msg'] = 'success';
		$obj['data'] = $data;

		header('Content-type: application/json');
		die( json_encode( $obj ) );
	}

	
	
}
	