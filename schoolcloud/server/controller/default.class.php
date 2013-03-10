<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );

class defaultController extends appController
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		if( g('a') != 'login' && g('a') != 'login_check' && g('a') != 'install')
		{
			if( !is_login() )
			{
				info_page('<a href="?a=login">请先登入</a>');
				exit;
			}
		} 
	}
	
	public function install()
	{
		if( !table_exists( '__meta_user' ) && !table_exists( '__meta_code' ))
		{
			$sql = "CREATE TABLE IF NOT EXISTS `__meta_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table` varchar(32) NOT NULL,
  `action` varchar(32) NOT NULL,
  `code` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table` (`table`,`action`)
)";
			run_sql( $sql );
			
			$sql = "CREATE TABLE IF NOT EXISTS `__meta_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ";
			run_sql( $sql );
			$sql ="CREATE TABLE IF NOT EXISTS `__meta_setting` (
  `key` varchar(200) NOT NULL,
  `value` varchar(1000) NOT NULL,
  UNIQUE KEY `key` (`key`)
)";
			run_sql( $sql );
			$email = 'admin'.rand(1,999).'@admin.com';
			$password = substr(md5(rand( 10000,8000 ) . time()) , 0 , 8);
			
			$sql = "INSERT INTO `__meta_user` ( `email` , `password` ) VALUES ( '" . s($email) . "' , '" . md5($password) . "' ) ";
			
			run_sql( $sql );
			
			if( mysql_errno() == 0 )
			{
				return info_page( "初始化成功，请使用【".$email."】和【" . $password . "】<a href='?a=login' target='_blank'>登录</a>。您可以通过phpmyadmin修改【__meta_user】表来管理账户"
					. '<br /><br /><a href="http://ftqq.com/2012/01/10/build-a-rest-server-in-5-minutes/" target="_blank">使用教程</a>'
				);
			}
		}
		
		return info_page("已经初始化或数据库错误，请稍后重试");
	}
	
	public function index()
	{
		$data['tables'] = get_table_list(db());
		
		//print_r( $data );
		$data['title'] = $data['top_title'] = '数据表';
		render( $data , 'web'  );
	}
	
	public function table_settings()
	{
		$table = z(t(v('table')));
		
		$tables = get_table_list(db());
		
		if( !in_array( $table , $tables ) )
			return info_page( '<a href="javascript:history.back(1);">table不存在，点击返回</a>' );
		
		$data['fields'] = get_fields_info( $table );
		
		//LazyRest默认实现的接口
		$data['actions'] = array( 'list' => 'List' , 'insert'=>'Insert' , 'update'=> 'Update' , 'remove' => 'Remove'  );
		
		$data['table'] = $table;
		
		$data['my_actions'] = get_data( "SELECT * FROM `__meta_code` WHERE `table` = '" . s( $table ) . "' ORDER BY `id` DESC" );
		//print_r( $fields );
		$data['title'] = $data['top_title'] = 'API设置';
		
		$data['js'][] = 'codemirror.js';
		$data['js'][] = 'util/runmode.js';
		$data['js'][] = 'mode/php/php.js';
		$data['js'][] = 'mode/htmlmixed/htmlmixed.js';
		$data['js'][] = 'mode/css/css.js';
		$data['js'][] = 'mode/javascript/javascript.js';
		$data['js'][] = 'mode/xml/xml.js';
		$data['js'][] = 'mode/clike/clike.js';
		
		
		$data['css'][] = 'codemirror.css';
		$data['css'][] = 'theme/night.css';

		render( $data );
		
	}
	
	public function iosettings_save()
	{
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1  )
		return ajax_echo( '参数不完整' );
		
		$in_code = t(v('in_code'));
		$out_code = t(v('out_code'));
		
		//print_r( $_REQUEST );
		$data['input_settings'] = kset( 'iosetting_input_' . $table  . '_' . $action  ,  $in_code ) ;
		$data['out_settings'] =  kset( 'iosetting_output_' . $table  . '_' . $action  , $out_code )  ;
		
		
		return ajax_echo('<script>window.location.reload();</script>');
		
	}
	
	public function fsettings_save()
	{
		//print_r( $_REQUEST ); 
		
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		$field = z(t(v('field')));
		$tdid = z(t(v('tdid')));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1 || strlen( $field ) < 1 || strlen( $tdid ) < 1 )
		return ajax_echo( '参数不完整' );
		
		
		
		$ret = array();
		foreach( $_REQUEST['st'] as $k=>$v )
		{
			$ret[z(t($k))] = intval( $v );
		}
		
		$_REQUEST['st'] = $ret;
		
		kset( 'msetting_' . $table . '_' . $action .  '_' . $field ,  serialize( v('st') )  );
		return ajax_echo('<script>window.location.reload();</script>');
		
		
	}
	
	public function action_add()
	{
		$data = array();
		$data['table'] = z(t(v('table')));
		return render( $data , 'ajax' );
	}
	
	public function action_modify()
	{
		$data = array();
		
		$action = z(t(v('action')));
		$table = $data['table'] = z(t(v('table')));
		
		$sql = "SELECT * FROM `__meta_code` WHERE `action` = '" . s( $action ) . "' AND `table` = '" . s( $table ) . "' LIMIT 1";
		$data['my_action'] = get_line( $sql );
		kset("my_action_code_status_".$table."_".$action,'1',false);
		return render( $data , 'ajax' );
	}
	
	public function action_delete()
	{
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1  )
		return ajax_echo( '参数不完整' );
		
		$sql = "DELETE FROM `__meta_code` WHERE `action` = '" . s( $action ) . "' AND `table` = '" . s( $table ) . "' LIMIT 1";
		
		run_sql( $sql );
		kdelete( $table . "_" . $action ."_code" );
		kdelete("my_action_code_status_".$table."_".$action);

		return ajax_echo('<script>window.location.reload();</script>');
	}
	
	public function action_save()
	{
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		$code = t(v('code'));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1  )
		return ajax_echo( '参数不完整' );
		
		$sql = "REPLACE INTO `__meta_code` ( `table` , `action` , `code` ) VALUES ( '" . s( $table ) . "' , '" . s( $action ) . "' , '" . s($code) . "' ) ";
		
		run_sql( $sql );

		kset("my_action_code_status_".$table."_".$action,'1',false);
		return ajax_echo('<script>window.location.reload();</script>');
		
	}
	
	public function asettings_save()
	{
		//print_r( $_REQUEST ); 
		
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1  )
		return ajax_echo( '参数不完整' );
		
		if( $_REQUEST['st']['public'] == 1 ) $_REQUEST['st']['basic'] = 0;
		else $_REQUEST['st']['basic'] = 1;
		
		if( $_REQUEST['st']['on'] == 1 ) $_REQUEST['st']['off'] = 0;
		else $_REQUEST['st']['off'] = 1;
		

		$ret = array();
		foreach( $_REQUEST['st'] as $k=>$v )
		{
			$ret[z(t($k))] = intval( $v );
		}
		
		$_REQUEST['st'] = $ret;

		//print_r(v('st'));
		kset( 'msetting_' . $table . '_' . $action  ,  serialize( v('st') )  );
		//print_r(serialize(v('st')));
		//echo 'msetting_' . $table . '_' . $action .  '_' . $field . '`~'.serialize( v('st') );
		//print_r( $kv );
		//echo kget( 'msetting_' . $table . '_' . $action  );
		//exit();
		return ajax_echo('<script>window.location.reload();</script>');
		
		
	}
	
	public function action_settings()
	{
		$settings = array();
		
		$settings[] = array( 'text' => '开' , 'value' => 'on' , 'desp' => '开启' ) ;
		$settings[] = array( 'text' => '关' , 'value' => 'off' , 'desp' => '关闭' ) ;
		$settings[] = array( 'text' => '全' , 'value' => 'public' , 'desp' => '无需认证') ;
		$settings[] = array( 'text' => '认' , 'value' => 'basic' , 'desp' => '用户认证') ;
		
		
		$data['settings'] = $settings;
		$data['table'] = z(t(v('table')));
		$data['action'] = z(t(v('action')));
		
		$data['title'] = '接口属性设置';
		
		$data['ainfo'] =  unserialize( kget( 'msetting_' . $data['table'] . '_' . $data['action']   ) ) ;
		
		

		
		return render( $data , 'ajax' ); 
	
	}
	
	public function io_settings()
	{
		$data['table'] = z(t(v('table')));
		$data['action'] = z(t(v('action')));
		
		
		$data['title'] = 'I/O过滤设置';
		
		
		
		$data['input_settings'] =  kget( 'iosetting_input_' . $data['table'] . '_' . $data['action']  )  ;
		$data['output_settings'] =  kget( 'iosetting_output_' . $data['table'] . '_' . $data['action']  ) ;
		
		//print_r(  $data );
		
		return render( $data , 'ajax' );
	}
	
	public function fields_settings()
	{
		$settings = array();
		
		$settings[] = array( 'text' => '入' , 'value' => 'input' , 'desp' => '作为输入参数' ) ;
		$settings[] = array( 'text' => '返' , 'value' => 'output' , 'desp' => '作为返回值' ) ;
		$settings[] = array( 'text' => '必' , 'value' => 'required' , 'desp' => '必填参数') ; 
		
		$settings[] = array( 'text' => '%' , 'value' => 'like' , 'desp' => 'Like匹配') ; 
		
		$settings[] = array( 'text' => '=' , 'value' => 'equal' , 'desp' => '相等匹配') ; 
		
		$data['settings'] = $settings;
		$data['table'] = z(t(v('table')));
		$data['field'] = z(t(v('field')));
		$data['action'] = z(t(v('action')));
		
		$data['tdid'] = intval(v('tdid'));
		
		$data['title'] = '字段属性设置';
		
		$data['finfo'] =  unserialize( kget( 'msetting_' . $data['table'] . '_' . $data['action'] .  '_' . $data['field']  ) ) ;
		
		

		
		return render( $data , 'ajax' );
	}
	
	public function logout()
	{
		foreach( $_SESSION as $k => $v )
		{
			unset( $_SESSION[$k] );
		}
		
		return info_page( '<a href="/">成功退出，点击返回首页</a>' );
	}
	
	public function login()
	{
		$data['title'] = $data['top_title'] = 'LazyRest - 最简单的Rest Server';
		render( $data );
	}
	
	public function login_check()
	{
		$email = z(t(v('email')));
		$password = z(t(v('password')));
		
		if( strlen( $email ) < 1 || strlen( $password ) < 1 )
			return ajax_echo( "电子邮件或密码不能为空" );
		
		$sql = "SELECT `id` , `email` FROM `__meta_user` WHERE `email` = '" . s( $email ) . "' AND `password` = '" . md5( $password ) . "'";
		
		if( !$user = get_line($sql) ) return ajax_echo( "电子邮件和密码不匹配，请重试" );
		
		$_SESSION['uid'] = $user['id'];
		$_SESSION['email'] = $user['email'];
		$_SESSION['ulevel'] = 9;
		
		// do login
		return ajax_echo("成功登录，转向中…<script>location = '?a=index';</script>");
	}
	
	public function nation()
	{
		return info_page("确保先重建此表，再删除此行return执行重新插入");
		$sql="INSERT INTO `odp_nation`(`nation_name`) values ".
			"('汉族'),('壮族'),('满族'),('回族'),('苗族'),('维吾尔族'),('土家族'),('彝族'),('蒙古族'),('藏族')".
			",('布依族'),('侗族'),('瑶族'),('朝鲜族'),('白族'),('哈尼族'),('哈萨克族'),('黎族'),('傣族'),('畲族')".
			",('僳僳族'),('仡佬族'),('东乡族'),('拉祜族'),('水族'),('佤族'),('纳西族'),('羌族'),('土族'),('仫佬族')".
			",('锡伯族'),('柯尔克孜族'),('达斡尔族'),('景颇族'),('毛南族'),('撒拉族'),('布朗族'),('哈吉克族'),('阿昌族'),('普米族')".
			",('鄂温克族'),('怒族'),('京族'),('基诺族'),('德昂族'),('保安族'),('俄罗斯族'),('裕固族'),('乌孜别克族'),('门巴族')".
			",('鄂伦春族'),('独龙族'),('塔塔尔族'),('赫哲族'),('高山族'),('珞巴族')";
		run_sql($sql);
		kset("nation_status",'1',false);
	}

	public function setlevel()
	{
		$level_table = get_level_table();
		$teachers = get_all_teacher();
		
		//typeahead的引号必须用这个……不然用"输出后只能显示一个字
		$ret = '&quot;数据仅供查询比对&quot;';
		$tinfo = Array();
		foreach($teachers as $teacher){
			
			$ret .= ',&quot;'.$teacher['teacher_id'].$teacher['teacher_name'].$teacher['staff_id'].'&quot;';

			$tinfo["'".$teacher['teacher_id']."'"]['teacher_name'] =$teacher['teacher_name'];
			$tinfo["'".$teacher['teacher_id']."'"]['staff_id'] =$teacher['staff_id'];
		}
		//print_r($tinfo);exit();
		$data['ret'] = $ret;
		//$data['js'][]="bootstrap-typeahead.js";
		$data['level_table'] = $level_table;
		$data['tinfo'] = $tinfo;
		$data['title'] = $data['top_title']='管理权限设置';
		render($data);

	}
	public function level_modify()
	{
		$data['client_name'] = z(t(v('client_name')));
		$data['teacher_id'] = z(t(v('teacher_id')));
		$data['level'] = z(t(v('level')));
		
		render($data,'ajax');

	}

	public function level_delete()
	{
		$data['client_name'] = z(t(v('client_name')));
		$data['teacher_id'] = z(t(v('teacher_id')));
		$data['level'] = z(t(v('level')));
		
		render($data,'ajax');
	}

	public function level_add()
	{
		$client_arr = Array('xm','qlty');
		$data['client_arr'] = $client_arr;
		render($data,'ajax');
	}

	public function level_update()
	{
		$client_name = z(t(v('client_name')));
		$teacher_id = z(t(v('teacher_id')));
		$level = z(t(v('level')));
		$sql = "UPDATE `odp_level` SET `level` =".s($level)." WHERE `client_name`='".s($client_name)."' AND `teacher_id`=".$teacher_id;
		//print_r($sql);exit();
		run_sql($sql);
		if( mysql_errno() == 0 )
		{
			kset("teacher_detail_status",'1',false);
			return ajax_echo("修改成功……<script>location = '?a=setlevel';</script>");
		}
	}

	public function level_dodelete()
	{
		$client_name = z(t(v('client_name')));
		$teacher_id = z(t(v('teacher_id')));
		$level = z(t(v('level')));
		$sql = "DELETE FROM `odp_level` WHERE `client_name`='".s($client_name)."' AND `teacher_id`=".$teacher_id." AND `level` =".s($level);
		run_sql($sql);
		if( mysql_errno() == 0 )
		{
			kset("teacher_detail_status",'1',false);
			return ajax_echo("删除成功……<script>location = '?a=setlevel';</script>");
		}
	}

	public function ajax_checkid()
	{
		return ajax_echo("目前未完成……直接提交会检查是否有重复滴^^");
		$client_name = z(t(v('client_name')));
		$teacher_id = z(t(v('teacher_id')));
	}

	public function level_doadd()
	{
		$client_name = z(t(v('client_name')));
		$teacher_id = z(t(v('teacher_id')));
		$level = z(t(v('level')));
		if($client_name=="" ||$teacher_id ==""||$level =="") 
		{
			return ajax_echo("不能有空");
		}else{
			$sql = "SELECT `level` FROM `odp_level` WHERE `client_name`='".s($client_name)."' AND `teacher_id`=".s($teacher_id);
			$oldlevel = get_var($sql);
			if(is_null($oldlevel))
			{
				$sql = "INSERT INTO `odp_level`(`client_name`,`teacher_id`,`level`) VALUES('".s($client_name)."',".s($teacher_id).",".s($level).")";
				run_sql($sql);
				if( mysql_errno() == 0 )
				{
					kset("teacher_detail_status",'1',false);
					return ajax_echo("新增成功……<script>location = '?a=setlevel';</script>");
				}
				else
					return ajax_echo("发生错误，添加失败，请联系管理员");
			}else{
				return ajax_echo("已有记录！不能新增！");
			}
		}
	}

	public function department()
	{
		$departments = kget_all_department();
		
		$data['departments'] = $departments;
		$data['top_title'] = '部门设置';
		render($data);
	}

	public function department_add()
	{
		$data['title']=$data['top_title']='增加部门';
		render($data,'ajax');
	}

	public function department_doadd()
	{
		$is_inside_school = z(t(v('is_inside_school')));
		$department_name = z(t(v('department_name')));
		if($is_inside_school=="" || $department_name=="") return ajax_echo("部门名称不能为空");

		$sql = "SELECT `department_id` FROM `odp_department` WHERE `department_name`='".s($department_name)."'";
		$oldepartment_id=get_var($sql);
		if($oldepartment_id){
			return ajax_echo("此部门已有记录，不允许重复插入！");
		}else{
			$sql = "INSERT INTO `odp_department`(`is_inside_school`,`department_name`) VALUES(".s($is_inside_school).",'".s($department_name)."')";
			run_sql($sql);
			kset("department_status",'1',false);
			if( mysql_errno() != 0 ) 
				return ajax_echo("发生错误，新增失败，请联系管理员");
		}
		return ajax_echo("新增成功……<script>location = '?a=department';</script>");
	}

	public function department_modify()
	{
		$data['department_id'] = z(t(v('department_id')));
		$data['is_inside_school'] = z(t(v('is_inside_school')));
		$data['department_name'] = z(t(v('department_name')));
		
		render($data,'ajax');
	}
	public function department_update()
	{
		$department_id = z(t(v('department_id')));
		$is_inside_school = z(t(v('is_inside_school')));
		$department_name = z(t(v('department_name')));
		if($department_name=="") return ajax_echo("不能为空");

		$sql = "UPDATE `odp_department` SET `is_inside_school`=".s($is_inside_school).",`department_name`='".$department_name."' WHERE `department_id`=".$department_id;
		run_sql($sql);
		kset("department_status",'1',false);
		if( mysql_errno() != 0 ) return ajax_echo("发生错误，更新失败，请联系管理员");
		return ajax_echo("新增成功……<script>location = '?a=department';</script>");
	}

	public function department_delete()
	{
		$data['department_id'] = z(t(v('department_id')));
		$data['is_inside_school'] = z(t(v('is_inside_school')));
		$data['department_name'] = z(t(v('department_name')));
		render($data,'ajax');
	}

	public function department_dodelete()
	{
		$department_id = z(t(v('department_id')));
		$sql = "SELECT `teacher_id` FROM `odp_teacher` WHERE `department_id`=".s($department_id)." limit 1";
		$teacher = get_data($sql);
		if( mysql_errno() != 0 ) return ajax_echo("发生错误，删除失败，请联系管理员");
		if($teacher){
			return ajax_echo("数据库中已有教师属于此部门，若要删除，请联系管理员处理");
		}else{
			$sql = "DELETE FROM `odp_department` WHERE `department_id`=".s($department_id);
			run_sql($sql);
			kset("department_status",'1',false);
			if( mysql_errno() != 0 ) return ajax_echo("发生错误，删除失败，请联系管理员");
		}
		return ajax_echo("删除成功……<script>location = '?a=department';</script>");
	}

	public function native()
	{
		$native_places=kget_all_native_place();
		$data['native_places'] = $native_places;
		$data['title']=$data['top_title']='籍贯设置';
		render($data);
	}
	public function native_place_add()
	{
		$data['title']=$data['top_title']="增加地名";
		render($data,'ajax');
	}

	public function native_place_doadd()
	{
		$native_place_name = z(t(v('native_place_name')));
		if($native_place_name=="") return ajax_echo("地名不能为空");

		$sql = "SELECT native_place_id FROM `odp_native_place` WHERE `native_place_name`='".s($native_place_name)."'";
		$old = get_var($sql);
		if( mysql_errno() != 0 ) return ajax_echo("发生错误，添加失败，请联系管理员");
		if($old){
			return ajax_echo("不允许添加重复地名");
		}else{
			$sql = "INSERT INTO `odp_native_place`(`native_place_name`) VALUES('".s($native_place_name)."')";
			run_sql($sql);
			kset("native_place_status",'1',false);
			if( mysql_errno() != 0 ) return ajax_echo("发生错误，添加失败，请联系管理员");
		}
		return ajax_echo("添加成功……<script>location = '?a=native';</script>");
	}

	public function native_place_modify()
	{
		$data['native_place_id']=z(t(v('native_place_id')));
		$data['native_place_name'] = z(t(v('native_place_name')));
		render($data,'ajax');
	}

	public function native_place_update()
	{
		$native_place_id = z(t(v('native_place_id')));
		$native_place_name = z(t(v('native_place_name')));
		if($native_place_id=="" || $native_place_name=="") return ajax_echo("地名不能为空");

		$sql = "UPDATE `odp_native_place` SET `native_place_name`='".s($native_place_name)."' WHERE `native_place_id`=".$native_place_id;
		run_sql($sql);
		kset("native_place_status",'1',false);
		if( mysql_errno() != 0 ) return ajax_echo("发生错误，更新失败，请联系管理员");
		return ajax_echo("更新成功……<script>location = '?a=native';</script>");
	}
	
	public function native_place_delete()
	{
		$data['native_place_id']=z(t(v('native_place_id')));
		$data['native_place_name'] = z(t(v('native_place_name')));

		render($data,'ajax');
	}

	public function native_place_dodelete()
	{
		$native_place_id = z(t(v('native_place_id')));
		if($native_place_id=="") return ajax_echo("id不能为空");
		
		$sql = "SELECT `teacher_id` FROM `odp_teacher` WHERE `native_place_id`=".s($native_place_id)." limit 1";
		$teacher = get_data($sql);
		if( mysql_errno() != 0 ) return ajax_echo("发生错误，删除失败，请联系管理员");
		if($teacher){
			return ajax_echo("数据库中已有教师关联此地名，若要删除，请联系管理员处理");
		}else{
			$sql = "DELETE FROM `odp_native_place` WHERE `native_place_id`=".$native_place_id;
			run_sql($sql);
			kset("native_place_status",'1',false);
			if( mysql_errno() != 0 ) return ajax_echo("发生错误，添加失败，请联系管理员");
		}
		return ajax_echo("删除成功……<script>location = '?a=native';</script>");
	}

}