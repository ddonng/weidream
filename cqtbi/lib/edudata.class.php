<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'lib' . DS . 'mycurl.class.php' );

Class edudata
{
	public $mycurl;
	/**
	 * teacher email
	 * Need For Login
	 */
	public $teacher_email;
	/**
	 * teacher email
	 * Need For Login
	 */
	public $teacher_pwd;
	/**
	 * teacher level
	 * xm
	 */
	public $teacher_level;
	/**
	 * @ignore
	 */
	public function authorizeURL()  { return c('authorizeURL'); }
	public function tokenURL()  { return c('tokenURL'); }


	function __construct( $teacher_email = NULL, $teacher_pwd = NULL ) {
		$this->teacher_email = $teacher_email;
		$this->teacher_pwd = $teacher_pwd;
		$this->client_name = c('client_name');

		$this->mycurl = new mycurl();
	}
	
	public function refresh_client_access_token()
	{
		$access_token=$_COOKIE['atk'];
		if($access_token==""){

			$result=self::get_client_access_token();
			if($result['access_token']!==NULL)
			{
				$access_token=$result['access_token'];
				$expires_in=$result['expires_in'];
				setCOOKIE('atk',$access_token,time()+$expires_in-200);
			}
		}
		return $access_token;
	}

	function verify()
	{
		$url = "teacher/verify/";
		$params = array();
		$params['email'] = $this->teacher_email;

		//注意密码这里需要md5（也许能增强安全，再加上https）
		$params['teacher_pwd'] = md5( $this->teacher_pwd );

		//在这client（即项目管理子系统）的name
		$params['client_name'] = c('client_name');


		$result = $this->mycurl->post($url,$params);
		return $result;
	}

	function general_data()
	{
		//注册用户时需要
		$url = "general/get/";
		$access_token=self::refresh_client_access_token();

		$params = Array();

		$params['access_token']=$access_token;

		$result = $this->mycurl->post($url,$params);
		return $result;
	}
	
	function get_client_access_token()
	{
		//grant_type:client_credentials
		$params = Array();
		$params['client_id']=c("client_id");
		$params['client_secret']=c("client_secret");
		$params['grant_type']="client_credentials";
		
		$result = $this->mycurl->post(c('tokenURL'),$params);
		return $result;
	}

	function add_teacher($teacher_infos)
	{
		$url = "odp_teacher/insert/";
		$access_token = self::refresh_client_access_token();
		$params=$teacher_infos;
		$params['access_token'] = $access_token;
		$result = $this->mycurl->post($url,$params);
		return $result;

	}

	function has_email($email)
	{
		$url = "odp_teacher/list/";
		$access_token = self::refresh_client_access_token();
		$params=Array();
		$params['access_token'] = $access_token;
		$params['email'] = $email;
		$result = $this->mycurl->post($url,$params);
		
		if($result['err_code']=='0')
			return ($result['data']==false)?false:$result['data']['items'][0];
	}

	function has_mobile_phone($mobile_phone)
	{
		$url = "odp_teacher/list/";
		$access_token = self::refresh_client_access_token();
		$params=Array();
		$params['access_token'] = $access_token;
		$params['mobile_phone'] = encrypt($mobile_phone,c('key'));

		$result = $this->mycurl->post($url,$params);
		if($result['err_code']=='0')
			return ($result['data']==false)?false:true;
	}

	function verify_email($code,$token)
	{
		$url="odp_teacher/list/";
		$params=Array();
		$params['access_token']=$token;
		$params['email_verificaion_code']=$code;

		$result=$this->mycurl->post($url,$params);

		if($result['err_code']=='0' && $result['data']['items'][0]['is_email_verified']=='0')
		{
			$url="odp_teacher/update/";
			$teacher_id=$result['data']['items'][0]['teacher_id'];
			$arr=Array();
			//重新获取，防止过期
			$arr['access_token']=self::refresh_client_access_token();
			$arr['is_email_verified']=1;
			$arr['teacher_id']=$teacher_id;

			$result_update=$this->mycurl->post($url,$arr);

			if($result_update['err_code']=='0') 
				return (self::update_kvdb_status($email))?true:false;

		}else if($result['err_code']=='0' && $result['data']['items'][0]['is_email_verified']=='1')
		{
			return 2;
		}

		if($result['err_code']=='10001') return 3;
	}

	function update_verify_code($email,$code,$teacher_id)
	{
		$url="odp_teacher/update/";
		$params=Array();
		$params['email_verificaion_code']=$code;
		$params['teacher_id']=$teacher_id;

		$params['access_token']=self::refresh_client_access_token();

		$result=$this->mycurl->post($url,$params);

		if($result['err_code']=='0'){
			//更新数据后，设置缓存需要更新
			$status=self::update_kvdb_status($email);
			return ($status)?true:false;
		}
	}

	//设置email teacher的kvdb状态，使下次取出数据时重新从Mysql取出
	function update_kvdb_status($email)
	{
		$url_rekset="odp_teacher/rekset/";
		$arr=Array();
		$arr['access_token']=self::refresh_client_access_token();
		$arr['email']=$email;
		$result=$this->mycurl->post($url_rekset,$arr);

		return ($result['err_code']=='0')?True:False;
	}

	/******************************************
	*质量工程参数 字段名限制，只能修改value，采用了序列化存储，取出后逆序列化使用
	*
	*项目类别 qlty_category : qlty_project_category,qlty_course_category,qlty_team_category
	*项目级别 qlty_rank
	*所属科类 qlty_specity
	******************************************/
	function get_qlty_option()
	{
		$url="qlty_option/list/";
		$access_token = self::refresh_client_access_token();
		$params=Array();
		$params['access_token'] = $access_token;

		$result=$this->mycurl->post($url,$params);

		return ($result['err_code']=='0')?($result['data']['items']):FALSE;

	}

	
	//Add quality Project
	function add_qlty_project($params)
	{
		$url="qlty_project/insert/";
		$access_token = self::refresh_client_access_token();
		$params = $params + Array('access_token'=>$access_token);

		$result = $this->mycurl->post($url,$params);

		return ($result['err_code']=='0')?$result['data'][0]['qlty_project_id']:false;

	}
	
	function add_qlty_project_asset($params)
	{
		$url="qlty_asset/insert/";
		$access_token = self::refresh_client_access_token();
		$params = $params + Array('access_token'=>$access_token);
		$result = $this->mycurl->post($url,$params);

		return ($result['err_code']=='0')?$result['data'][0]['asset_id']:false;
	}

	function get_qlty_asset_status($pid,$tid)
	{
		$url="qlty_asset/status/";
		$access_token = self::refresh_client_access_token();
		$params = Array(
			'access_token'=>$access_token,
			'pid'=>$pid,
			'tid'=>$tid
		);
		$result = $this->mycurl->post($url,$params);

		return ($result['err_code']=='0')?$result['data']:false;
	}

}