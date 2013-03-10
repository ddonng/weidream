<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );


class oauthController extends appController
{
	public $oauth;

	public $Oauth2Storage;

	function __construct()
	{
		// 载入默认的
		parent::__construct();
		//获得Oauth对象实例
		self::OauthInstance();

		self::Oauth2StorageInstance();
	}
	
	public function index()
	{
		
	}
	
	//默认action
	//Authorize
	public function authorize()
	{
		//获取参数
		$client_id = z(t(v("client_id")));
		$response_type = z(t(v("response_type")));
		$redirect_uri = urldecode (z(t(v("redirect_uri"))) );
		$state = z(t(v("state")));
		$scope = z(t(v("scope")));

		$tid = z(t(v("tid")));
		$inputData = Array(
			"client_id"=>$client_id,
			"response_type"=>$response_type,
			"redirect_uri"=>$redirect_uri,
			"state"=>$state,
			"scope"=>$scope
		);

		try {
			$auth_params = $this->oauth->getAuthorizeParams($inputData);
			} catch (OAuth2ServerException $oauthError) {
			$oauthError->sendHttpResponse();
			}

		$data['title'] = $data['top_title'] = 'ODP用户授权';
		$data['auth_params'] = $auth_params+Array("tid"=>$tid);

		//post到finishauthorization()
		render($data,'','authorize');
	}

	//也可跳过用户验证，直接到这里处理（目前就一个xm系统，所以……）
	public function finishauthorization()
	{
		//获取参数
		$client_id = z(t(v("client_id")));
		$response_type = z(t(v("response_type")));
		$redirect_uri = urldecode( z(t(v("redirect_uri"))) );
		$state = z(t(v("state")));
		$scope = z(t(v("scope")));
		
		$tid = z(t(v("tid")));
		
		$teacher_accept = z(t(v("accept")))!=""?z(t(v("accept"))):"Yep" ;

		$inputData = Array(
			"client_id"=>$client_id,
			"response_type"=>$response_type,
			"redirect_uri"=>$redirect_uri,
			"state"=>$state,
			"scope"=>$scope
		);

		try {
			$auth_params = $this->oauth->getAuthorizeParams($inputData);
			} catch (OAuth2ServerException $oauthError) {
			$oauthError->sendHttpResponse();
			}

		$auth_params += Array("tid"=>$tid);


		$this->oauth->finishClientAuthorization($teacher_accept == "Yep", $tid, $auth_params);
	}

	public function token()
	{
		//获取参数
		$client_id = z(t(v("client_id")));
		$client_secret = z(t(v("client_secret")));
		$refresh_token = z(t(v("refresh_token")));
		$grant_type = z(t(v("grant_type")));
		$redirect_uri = urldecode( z(t(v("redirect_uri"))) );
		
		$tid = z(t(v("tid")));

		$inputData = Array(
			"client_id"=>$client_id,
			"client_secret"=>$client_secret,
			"refresh_token"=>$refresh_token,
			"grant_type"=>$grant_type,
			"redirect_uri"=>$redirect_uri,
		);

		try {
			$this->oauth->grantAccessToken($inputData);
		} catch (OAuth2ServerException $oauthError) {
			$oauthError->sendHttpResponse();
		}		
	}



	//Add client Action
	//应该安全保证，先实现这个Action再说

	public function addclient()
	{
		if( !is_login() )
		{
			return ajax_echo("<script>location = '/server/?a=login';</script>");
		}

		$data['title'] = $data['top_title'] = '设置接入Client信息';
		render($data);
	}

	public function addClient_check()
	{
		$client_id = z(t(v('client_id')));
		$client_secret = z(t(v('client_secret')));
		$redirect_uri = z(t(v('redirect_uri')));
		
		if(strlen($client_id)>0 && strlen($client_secret)>0 && strlen($redirect_uri)>0 )
		{
			//self::Oauth2StorageInstance();
			$success=$this->Oauth2Storage->addClient( $client_id , $client_secret, $redirect_uri);
			
			if($success)
			{
				return ajax_echo( "增加 Client 成功！" );
			}

		}else{
			return ajax_echo( "错误：不能有空值！" );
		}


	}
	

	//返回父类中实例
	private function OauthInstance()
	{
		$this->oauth = parent::getOauth();
	}

	private function Oauth2StorageInstance()
	{
		$this->Oauth2Storage = parent::getOauth2Storage();
	}





}