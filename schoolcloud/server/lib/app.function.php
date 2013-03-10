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


function is_login()
{
	if( isset( $_COOKIE['PHPSESSID'] ) )
	{
		session_start();
		return ss('uid') > 0;
	}
	
	return false;
}

function is_admin()
{
	return ss('ulevel') > 5 ;
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

function kdelete($key)
{
	if( has_saekv() )
	{
		$kv = new SaeKV();$kv->init();
		$value = $kv->delete( $key );
		return $value;
	}
}

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

//若kvdb中无，则直接数据库中查找，写入kvdb后再return
function get_pri_name(  $table , $db=NULL )
{
	$pri_name=kget('primary_key_name_'.$table);
	if ($pri_name=="")
	{
		$pri_name=get_table_pri( $table , $db);
		if($pri_name){
			kset( 'primary_key_name_'.$table , $pri_name, false);
		}
	}
	return $pri_name;
}

function get_my_action_code( $table , $action )
{
	$my_action_code_status=kget("my_action_code_status_".$table."_".$action);
	//初次或发生改动
	$my_code="";
	if($my_action_code_status=='' || $my_action_code_status=='1')
	{
		$my_code=get_action_code( $table , $action);
		kset( $table . "_" . $action ."_code" , $my_code ,false);
		kset("my_action_code_status_".$table."_".$action,'0',false);
	}else{
		$my_code=kget( $table . "_" . $action ."_code");
	}
	return $my_code;
}

//将teacher中的所有信息都写入kvdb，作为缓存
function get_teacher_detail( $email,$teacher_pwd,$client_name )
{
	//在更新数据时会设置这个值，标记缓存数据是否需要重新从Mysql中获取

	//个人信息发生修改时，email来标记
	$need_self_refresh = kget("teacher_detail_".s($email)."_status");

	//level发生修改
	$need_refresh = kget("teacher_detail_status");

	if($need_refresh==''||$need_refresh=='1'||$need_self_refresh==''||$need_self_refresh=='1')
	{
		$teacher_detail = get_db_teacher_detail($email,$teacher_pwd,$client_name);
		//如果有这个用户数据，才能写入kvdb，同时备份到mysql
		if($teacher_detail){
			kset("t_detail_".$client_name."_".$email,serialize($teacher_detail),true);
		}
		kset("teacher_detail_status",'0',false);
		kset("teacher_detail_".s($email)."_status",'0',false);
		return $teacher_detail;
	}else{
		$teacher_detail = kget("t_detail_".$client_name."_".$email);
		return unserialize($teacher_detail);
	}
	
}

function get_db_teacher_detail($email,$teacher_pwd,$client_name)
{
	$sql = "SELECT * FROM `odp_teacher` WHERE `email`='".s($email)."' AND `teacher_pwd`='".s($teacher_pwd)."'";
	//print_r($sql);exit();
	if($teacher = get_line($sql))
	{
		$stmt = "SELECT `level` FROM `odp_level` WHERE `client_name` = '". s($client_name) ."' AND `teacher_id` = ". s($teacher['teacher_id']);
		
		if($teacher_level = get_line($stmt))
		{
			return ($teacher + $teacher_level);
		}else{
			$teacher_level=Array('level'=>'0');
			return ($teacher + $teacher_level);
		}
	}

}

function get_all_teacher()
{
	$sql = "SELECT `teacher_id`,`teacher_name`,`staff_id` FROM `odp_teacher`";
	return get_data($sql);

}

function get_all_nation()
{
	$sql = "SELECT * FROM `odp_nation` order by `nation_id` ASC";
	return get_data($sql);
}

function get_all_department()
{
	$sql = "SELECT * FROM `odp_department` order by `department_id` ASC";
	return get_data($sql);
}

function get_all_native_place()
{
	$sql="SELECT * FROM `odp_native_place` order by `native_place_id` ASC";
	return get_data($sql);
}

function kget_all_nation()
{
	$nation_status = kget("nation_status");
	if($nation_status=='' || $nation_status=='1')
	{
		$nations = get_all_nation();
		if($nations)
		{
			kset("nation_all",serialize($nations),true);
		}
		kset("nation_status",'0',false);
		return $nations;
	}else{
		$nations = kget("nation_all");
		return unserialize($nations);
	}
}

function kget_all_department()
{
	$department_status = kget("department_status");
	if($department_status=='' ||$department_status=='1')
	{
		$departments = get_all_department();
		if($departments)
		{
			kset("department_all",serialize($departments),true);
		}
		kset("department_status",'0',false);
		return $departments;
	}else{
		$departments = kget("department_all");
		return unserialize($departments);
	}
}

function kget_all_native_place()
{
	$native_place_status = kget("native_place_status");
	if($native_place_status==''||$native_place_status=='1')
	{
		$native_places = get_all_native_place();
		if($native_places)
		{
			kset("native_place_all",serialize($native_places),true);
		}
		kset("native_place_status",'0',false);
		return $native_places;
	}else{
		$native_places = kget("native_place_all");
		return unserialize($native_places);
	}
}


function get_level_table()
{
	$sql = "SELECT * FROM `odp_level`";
	return get_data($sql);
}

function get_db_list( $db = NULL )
{
	if( $data = get_data("SHOW DATABASES" , $db) )
	{
		foreach( $data as $line )
		{
			if( substr( $line['Database'] , 0 , strlen( '__meta_' ) )  ==  '__meta_' ) continue;
			$ret[] = $line['Database'];
		}
		
		return $ret;
	}
	else
		return false;
}

function table_exists( $table , $db = NULL)
{
	$ret = false;
	if( $data = get_data("SHOW TABLES" , $db ) )
		foreach( $data as $line )
			if( strtolower( $table ) == strtolower(reset( $line )) ) $ret = true;
	
	return $ret;

}

function get_table_list( $db = NULL )
{
	if( $data = get_data("SHOW TABLES" , $db ) )
	{
		foreach( $data as $line )
		{
			if( substr( reset($line) , 0 , strlen( '__meta_' ) )  ==  '__meta_' ) continue;
			$ret[] = reset( $line );
		}
		
		return $ret;
	}
	else
		return false;
}

function get_fields_info( $table , $db = NULL )
{
	if( $data = get_data("SHOW COLUMNS FROM `" . $table . "`" , $db ) )
	{
		foreach( $data as $line )
		{
			$ret[] = $line;
		}
		
		return $ret;
	}
	else
		return false;
}

function get_fields( $table , $db = NULL )
{
	if( $data = get_data("SHOW COLUMNS FROM `" . $table . "`" , $db ) )
	{
		foreach( $data as $line )
		{
			$ret[] = $line['Field'];
		}
		
		return $ret;
	}
	else
		return false;
}



function get_field_info( $table , $field , $db = NULL )
{
	
	if( $data = get_data("SHOW COLUMNS FROM `" . $table . "`" , $db ) )
	{
		foreach( $data as $line )
		{
			if( $line['Field'] == $field  )
			{
				$line['Length'] = get_field_length( $line['Type'] );
				$line['Type'] = get_field_type( $line['Type'] );
				return  $line;
			}
		}
		
		return false;
	}
	
	return false;
}
