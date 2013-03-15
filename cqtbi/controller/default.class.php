<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );

class defaultController extends appController
{
	function __construct()
	{
		parent::__construct();
		if( g('a') != 'login' && g('a') != 'login_check' && g('a') != 'register' && g('a') != 'showcaptcha' && g('a') != 'doreg' && g('a') != 'vemail' && g('a') != 'doactive'&& g('a') != 'active'&& g('a') != 'reactive')
		{
			if( !is_login() )
			{
				ajax_echo("<script>location = '/?a=login';</script>");
				exit;
			}
		}
		session_start();
	}

	private function getEdudataInstance($email=NULL,$password=NULL)
	{
		return parent::getEdudata($email,$password);
	}
	
	private function init_generaldata()
	{
		if($_SESSION['department']=="")
		{
			$edudata = self::getEdudataInstance();
			$general = $edudata->general_data();
			if($general['err_code']=='0')
			{
				$_SESSION['department'] = $general['data']['department'];
				$_SESSION['nation'] = $general['data']['nation'];
				$_SESSION['native_place'] = $general['data']['native_place'];
			}else{
				ajax_echo("API错误，无法获取数据");
			}
		}

		if($_SESSION['qlty_project_category']=="")
		{
			$qlty_options=$edudata->get_qlty_option();
			if($qlty_options!==FALSE){
				foreach($qlty_options as $qlty_option)
				{
					$_SESSION[$qlty_option['qlty_option_name']]=$qlty_option['qlty_option_value'];
				}
			}
		}

	}

	public function index()
	{
		//echo "access_token:  ";
		//var_dump($_COOKIE['atk'.$_SESSION['teacher']['teacher_id']]);
		//echo "refresh_token:  ";
		//var_dump($_COOKIE['rtk'.$_SESSION['teacher']['teacher_id']]);
		//setCOOKIE('atk'.$_SESSION['teacher']['teacher_id'],'',time()-43000);
		//setCOOKIE('rtk'.$_SESSION['teacher']['teacher_id'],'',time()-1209600);
		//exit();

		//获取department、nation、native_place
		self::init_generaldata();
		

		$data['title'] = $data['top_title'] = '个人信息';
		$data['teacher'] = $_SESSION['teacher'];
		render($data);
	}

	public function logout()
	{
		foreach( $_SESSION as $k => $v )
		{
			$_SESSION[$k]='';
			unset( $_SESSION[$k] );
		}
		
		return info_page( "成功退出……<script>location = '/?a=login';</script>" );
	}
	
	public function login()
	{
		//计划改为验证COOKIE中是否有access_token，但目前只检测session
		
		if( is_login() )
		{
			return ajax_echo("已登录，转向中…<script>location = '?a=index';</script>");
		}
		
		$data['title'] = $data['top_title'] = '登录';
		$data['js'][]="self.js";
		render( $data,'','logindex' );
	}

	
	public function login_check()
	{
		//var_dump($_COOKIE['atk']);exit();
		$email = z(t(v('email')));
		$password = z(t(v('password')));

		if( strlen( $email ) < 1 || strlen( $password ) < 1 )
			return ajax_echo( "电子邮件或密码不能为空" );
		
		$edudata = self::getEdudataInstance($email,$password);
		
		$teacher = $edudata->verify();

		if($teacher['err_code']==0)
		{
			//是否已激活
			if( $teacher['data']['is_email_verified']=='1' )
			{

				$_SESSION['teacher']=$teacher['data'];
				
				$url = '';

				//access_token是否有效
				if(IS_NULL($_COOKIE['atk'.$_SESSION['teacher']['teacher_id']]) || !isset($_COOKIE['atk'.$_SESSION['teacher']['teacher_id']]))
				{
					//refresh_token是否过期
					if(IS_NULL($_COOKIE['rtk'.$_SESSION['teacher']['teacher_id']]) || !isset($_COOKIE['rtk'.$_SESSION['teacher']['teacher_id']]))
					{
						$url = '/?a=authorize';
					}else{
						$url = '/?a=token';
					}

				}else{
					$url = '/?a=index';
				}

				return ajax_echo("成功登录，转向中…<script>location = '".$url."';</script>");
			}else{
				return ajax_echo("请激活账户…<script>location = '/?a=vemail';</script>");
			}

		}else{
			return ajax_echo(  $teacher['err_code']."  ".$teacher['err_msg'] );
		}

		
		
	}

	public function authorize()
	{	
		$parameters = Array(
			'client_id' => c('client_id'),
			'client_secret' => c('client_secret'),
			'redirect_uri' => urlencode(c('redirect_uri')),
			'response_type' => 'token',
			'state' => 'test_state',
			'tid' => $_SESSION['teacher']['teacher_id']
		);

		$url = c('authorizeURL')."?".http_build_query($parameters);

		return ajax_echo("认证中…<script>location = '".$url."';</script>");
	
	}

	public function token()
	{	
		$parameters = Array(
			'client_id' => c('client_id'),
			'client_secret' => c('client_secret'),
			'refresh_token' => $_COOKIE['rtk'.$_SESSION['teacher']['teacher_id']],
			'redirect_uri' => urlencode(c('redirect_uri')),
			'grant_type' => 'refresh_token',
		);

		$url = c('tokenURL')."?".http_build_query($parameters);

		return ajax_echo("刷新access_token,认证中…<script>location = '".$url."';</script>");
	
	}

	//接收返回的access_token与refresh_token等
	public function oauth()
	{
		$need_refresh = z(t(v('nrefresh')));
		if($need_refresh=="gszy")
		{
			setCOOKIE('atk'.$_SESSION['teacher']['teacher_id'],'',time()-43000);
			setCOOKIE('rtk'.$_SESSION['teacher']['teacher_id'],'',time()-1209600);
			return ajax_echo("请重新认证……<script>location = '/?a=authorize';</script>");
		}else{
			$access_token = z(t(v('access_token')));
			$refresh_token = z(t(v('refresh_token')));
			$expires_in = z(t(v('expires_in')));
			$token_type = z(t(v('token_type')));
			$scope = z(t(v('scope')));
			$state = z(t(v('state')));

			//scope、token_type、state之类的暂未处理
			
			setCOOKIE('atk'.$_SESSION['teacher']['teacher_id'],$access_token,time()+$expires_in-1200);

			//14day，比odp提前一天
			setCOOKIE('rtk'.$_SESSION['teacher']['teacher_id'],$refresh_token,time()+1209600);
			return ajax_echo("<script>location = '/?a=index';</script>");
		}
	}

	public function register()
	{
		$_SESSION['could_use_captcha']=true;
		session_start();
		
		self::init_generaldata();
		$data['departments'] = $_SESSION['department'];
		$data['nations'] = $_SESSION['nation'];
		$data['native_places'] = $_SESSION['native_place'];
		$data['title']=$data['top_title']="新用户注册";

		$data['js'][]="timepicker/jquery-ui-timepicker-addon.js";
		$data['js'][]="timepicker/jquery-ui.min.js";
		$data['js'][]="timepicker/jquery-ui-sliderAccess.js";
		$data['js'][]="timepicker/jquery-ui-timepicker-zh-CN.js";
		$data['js'][]="self.js";
		$data['css'][]="jquery-ui.css";
		render($data,'','reg');
		
	}

	public function doreg()
	{
		//获取数据

		$captcha = z(t(v('captcha')));
		$email = z(t(v('email')));
		$pwd = z(t(v('pwd')));
		$pwd_again = z(t(v('pwd_again')));
		$school = z(t(v('school')));
		$department1 = z(t(v('department1')));
		$department2 = z(t(v('department2')));
		$staff_id = z(t(v('staff_id')));
		$school_id = z(t(v('schoolid')));
		$teacher_name = z(t(v('teacher_name')));
		$sex = z(t(v('sex')));
		$birthday = z(t(v('birthday')));
		$mobile_phone = z(t(v('mobile_phone')));
		$office_phone = z(t(v('office_phone')));
		$nation = z(t(v('nation')));
		$native_place = z(t(v('native_place')));
		$start_work_time =z(t(v('start_work_time')));


		//验证数据

		if(strtolower($captcha) !== strtolower( $_SESSION['captcha_code']) ) 
			return ajax_echo("验证码有误");
		if($email=="")
			return ajax_echo("邮箱不能为空");
		//邮箱有效性验证
		if( !validEmail($email) )
			return ajax_echo("邮箱无效！");

		//还需要检查是否已注册！！
		$edudata=self::getEdudataInstance();
		$has_email = $edudata->has_email($email);
		if($has_email) return ajax_echo("此邮箱已被注册，不能重复");

		if($pwd=="")
			return ajax_echo("密码不能为空");
		if(strlen($pwd)<8)
			return ajax_echo("密码长度至少为8位");
		if($pwd!==$pwd_again)
			return ajax_echo("密码不一致");
		if($school=='1')
		{
			$is_inside_school=1;
			$department_id = $department1;
			$school_id='13967';
			if($staff_id=="") return ajax_echo("工号不能为空");
			if(!preg_match("/(000[0-9]{4}$|0123456[0-9]{3}$)/i",$staff_id))
				return ajax_echo("工号无效");

		}else if($school=='2'){
			$is_inside_school=1;
			$department_id = $department2;
			$school_id='13967';
			$staff_id="";

		}else if($school=='3'){
			$is_inside_school=0;
			$department_id=NULL;
			$staff_id="";
			if($school_id=="") return ajax_echo("学校代码不能为空");
			if(!preg_match("/[0-9]{5}/i",$school_id)) return ajax_echo("学校代码无效");
		}
		if($teacher_name=="") return ajax_echo("姓名不能为空");
		if(strlen($teacher_name)>12) return ajax_echo("姓名长度不合法");
		if($birthday=="") return ajax_echo("生日不能为空");

		if($start_work_time=="") return ajax_echo("入职时间不能为空");

		if($mobile_phone=="") return ajax_echo("手机号码不能为空");
		if(!preg_match("/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/",$mobile_phone))
			return ajax_echo("手机号码无效！");

		$has_mobile_phone = $edudata->has_mobile_phone($mobile_phone);
		if($has_mobile_phone) return ajax_echo("手机号码已被注册，如有疑问，请联系项目科");

		if($office_phone=="") return ajax_echo("座机号码不能为空");
		if(!preg_match("/(0[0-9]{2,3}[\-][2-9][0-9]{6,7}[\-]?[0-9]?)/i",$office_phone))
			return ajax_echo("座机号码无效");



		//加工数据
		$pwd = md5($pwd);
		$is_working=1;//默认
		$register_time=time();
		$mobile_phone = encrypt($mobile_phone,c('key'));
		//生成邮件验证码
		$email_verificaion_code=dohash(generate_rand_str(7),generate_rand_str(8));
		$start_work_time=strtotime($start_work_time);


		//组装数据
		$params = Array(
			'school_id'=>$school_id,
			'department_id'=>$department_id,
			'staff_id'=>$staff_id,
			'teacher_name'=>$teacher_name,
			'teacher_pwd'=>$pwd,
			'teacher_sex'=>$sex,
			'is_inside_school'=>$is_inside_school,
			'is_working'=>$is_working,
			'nation_id'=>$nation,
			'native_place_id'=>$native_place,
			'start_work_time'=>$start_work_time,
			'birthday'=>$birthday,
			'email'=>$email,
			'office_phone'=>$office_phone,
			'mobile_phone'=>$mobile_phone,
			'email_verificaion_code'=>$email_verificaion_code,
			'register_time'=>$register_time
		);	
		
		$result = $edudata->add_teacher($params);
		if($result['err_code']=='0')
		{
			$subject = "请激活您的账号";
			$url = "http://cqtbi.sinaapp.com/?a=doactive&code=".$email_verificaion_code."&token=".$edudata->refresh_client_access_token();
			$content = "尊敬的".$teacher_name."：<br>请点击以下链接激活您的账号<br><a href='".$url."'>".$url."</a><br>教务处项目科";
			$email_status = send_mail($email,$subject,$content);
			if($email_status=='0')
				return ajax_echo("恭喜！注册成功！请验证邮箱……<script>location = '/?a=vemail';</script>");
		}else{

			return ajax_echo("注册失败！API获取数据错误");
		}

	}

	public function showcaptcha()
	{
		if( !$_SESSION['could_use_captcha'] ) die('bad request');

		require_once(AROOT.'lib'.DS.'captcha.class.php');
		$captcha = new Captcha(180,30,5);
		$captcha->showImg();
		session_start();
		$_SESSION["captcha_code"]= $captcha->getCaptcha();//验证码保存到SESSION中
	}

	public function vemail()
	{
		$data['title']=$data['top_title']="验证邮箱";
		render($data,'','reg');
	}
	
	public function active()
	{
		$data['title']=$data['top_title']="激活账户";
		render($data,'','reg');
	}

	public function doactive()
	{
		$code=z(t(v('code')));
		$access_token=z(t(v('token')));
		
		if($code=="" ||$access_token=="") return ajax_echo("code与token不能为空");

		$edudata=self::getEdudataInstance();
		$status=$edudata->verify_email($code,$access_token);

		$data['status']=$status;
		$data['title']=$data['top_title']="激活账户";
		render($data,'','reg');
	}
	
	public function reactive()
	{
		$email=z(t(v('email')));

		if($email=="") return ajax_echo("请输入您的邮箱");
		//邮箱有效性验证
		if( !validEmail($email) )
			return ajax_echo("邮箱无效！");

		//还需要检查是否已注册！！
		$edudata=self::getEdudataInstance();
		$has_email = $edudata->has_email($email);
		if($has_email) {
			if($has_email['is_email_verified']=='0'){
				//更新verify code
				$email_verificaion_code=dohash(generate_rand_str(7),generate_rand_str(8));
				$teacher_id=$has_email['teacher_id'];

				$result=$edudata->update_verify_code($email,$email_verificaion_code,$teacher_id);
				if( $result ){
					//重新发送email
					$subject = "请激活您的账号";
					$url = "http://cqtbi.sinaapp.com/?a=doactive&code=".$email_verificaion_code."&token=".$edudata->refresh_client_access_token();
					$content = "尊敬的".$has_email['teacher_name']."：<br>请点击以下链接激活您的账号<br><a href='".$url."'>".$url."</a><br>教务处项目科";

					$email_status = send_mail($email,$subject,$content);
					
					if($email_status=='0'){
						return ajax_echo("已重新发送验证邮件，请登录邮箱验证！");
					}
				}
			}else{
				return ajax_echo("账户已激活，不能重复操作");
			}

		}

	}
		
	public function updatepwd()
	{
	
		$data['title']=$data['top_title']="修改密码";
		render($data);
		
	}

	public function doupdatepwd()
	{
		$oldpwd=z(t(v("oldpwd")));
		$newpwd=z(t(v("newpwd")));
		$pwd_again=z(t(v("pwd_again")));
		
		if($oldpwd=="" || $newpwd=="" || $pwd_again=="") return ajax_echo("不能有空");
		if($newpwd!==$pwd_again) return ajax_echo("两次输入的新密码需要相同！");

		if(!preg_match("/^[0-9a-zA-Z]{8,20}$/",$newpwd)) return ajax_echo("新密码只能为字母或数字，长度为8到20位");

		$edudata=self::getEdudataInstance();
		$result = $edudata->update_pwd($newpwd,$oldpwd,$_SESSION['teacher']['teacher_id']);

		if($result===true){
			return ajax_echo("修改密码成功……<script>location = '/?a=logout';</script>");}
		if($result==='invalidpwd'){
			return ajax_echo("旧密码输入错误！");}
		if($result===false){
			return ajax_echo("发生错误，修改密码不成功");}
	}

	public function sysparams()
	{
		$edudata=self::getEdudataInstance();
		$qlty_options=$edudata->get_qlty_option();
		$data['qlty_options']=$qlty_options;

		$data['title']=$data['top_title']="质量工程参数";
		render($data);

	}

	public function init_sysparams()
	{
		//在schoolcloud.sinaapp.com/初始化了
		return ajax_echo("初始化请慎重！注意数据关联。请注释本行后重新执行");
		$qlty_project_category=Array('11'=>"重大教育教学改革项目",'12'=>"重点教育教学改革项目",'13'=>"一般教育教学改革项目");
		$qlty_course_category=Array('21'=>"教改课程",'22'=>"精品建设课程",'23'=>"精品课程",'24'=>"精品资源共享课",'25'=>"视频公开课");
		$qlty_team_category=Array('31'=>"优秀教学团队",'32'=>"一般教学团队");
		$qlty_rank=Array("院校级","中央电大级","市级","国家级");
		$qlty_specity=Array("林业技术类","公路运输类","自动化类","轻纺食品大类","经济贸易类","医药卫生大类","临床医学类","艺术设计类","农林牧渔大类","农业技术类","畜牧兽医类","水产养殖类","农林管理类","交通运输大类","铁道运输类","城市轨道运输类","水上运输类","民航运输类","港口运输类","管道运输类","生化与药品大类","资源开发与测绘大类","材料与能源大类","材料类","能源类","电力技术类","土建大类","建筑设计类","城镇规划与管理类","土建施工类","建筑设备类","工程管理类","市政工程类","房地产类","水利大类","水文与水资源类","水利工程与管理类","水利水电设备类","水土保持与水环境类","制造大类","机械设计制造类","机电设备类","汽车类","电子信息大类","计算机类","电子信息类","通信类","环保、气象与安全大类","环保类","气象类","安全类","轻化工类","纺织服装类","食品类","包装印刷类","财经大类","财政金融类","财务会计类","市场营销类","工商管理类","护理类","药学类","医学技术类","卫生管理类","旅游大类","旅游管理类","餐饮管理与服务类","公共事业大类","公共事业类","公共管理类","公共服务类","文化教育大类","语言文化类","教育类","体育类","康复训练艺术设计传媒大类","表演艺术类","广播影视类","公安大类","公安管理类","公安指挥类","公安技术类","部队基础工作类","法律大类","法律实务类","法律执行类","司法技术类");
		$qlty_asset_type=Array("11"=>"申报书WORD","12"=>"申报书PDF","21"=>"申报书修改版WORD（立项评审专家意见）","22"=>"申报书修改版PDF（立项评审专家意见）","31"=>"任务书WORD","32"=>"任务书PDF","41"=>"开题报告WORD","42"=>"开题报告PDF","51"=>"中期检查报告WORD","52"=>"中期检查PDF","61"=>"项目变更报告WORD","62"=>"项目变更报告PDF","71"=>"结题报告WORD","72"=>"结题报告PDF");

		$qlty_project_category_str=serialize($qlty_project_category);
		$qlty_course_category_str=serialize($qlty_course_category);
		$qlty_team_category_str=serialize($qlty_team_category);
		$qlty_rank_str=serialize($qlty_rank);
		$qlty_specity_str=serialize($qlty_specity);
		$qlty_asset_type_str=serialize($qlty_asset_type);

		$sql="INSERT INTO `qlty_option`(`qlty_option_name`,`qlty_option_value`) VALUES ('qlty_project_category','".$qlty_project_category_str."'),('qlty_course_category','".$qlty_course_category_str."'),('qlty_team_category','".$qlty_team_category_str."'),('qlty_rank','".$qlty_rank_str."'),('qlty_specity','".$qlty_specity_str."'),('qlty_asset_type','".$alty_asset_type_str."')";
		run_sql($sql);
	}


	//申报教改课程
	public function capply()
	{
//		print_r($_SESSION['qlty_project_category']);exit();

		//$data['qlty_project_category']=unserialize($_SESSION['qlty_project_category']);
		$data['qlty_course_category']=unserialize($_SESSION['qlty_course_category']);
		//$data['qlty_team_category']=unserialize($_SESSION['qlty_team_category']);
		$data['qlty_rank']=unserialize($_SESSION['qlty_rank']);
		$data['qlty_specity']=unserialize($_SESSION['qlty_specity']);

		$data['title']=$data['top_title']="教改课程申报";
		render($data);
	}

	public function add_project()
	{
		//接收数据
		$project_type=z(t(v("project_type")));
		$category=z(t(v("category")));
		$course_name=z(t(v("course_name")));
		$belong_cqdd=z(t(v("belong_cqdd")));
		$rank=z(t(v("rank")));
		$specialty=z(t(v("specialty")));
		$funds=z(t(v("funds")));
		//$research_year=z(t(v("research_year")));
		$research_year=2;
		$achievement=z(t(v("achievement")));
		$course_experience=z(t(v("course_experience")));
		$teacher_id=$_SESSION['teacher']['teacher_id'];

		//匹配出来数据
		//待后续实现,hxd 13/02/22



		//组装数据
		$params=Array(
			'director_id'=>$teacher_id,
			'name'=>$course_name,
			'type'=>$project_type,
			'category'=>$category,
			'rank'=>$rank,
			'belong_cqdd'=>$belong_cqdd,
			'belong_specialty'=>$specialty,
			'apply_funds'=>$funds,
			'research_year_num'=>$research_year,
			'achievement'=>$achievement
		);
		if($course_experiment!=="")
		{
			$params =$params+Array('course_teach_experience'=>$course_experience);
		}

		$edudata = self::getEdudataInstance();
		$qlty_project_id = $edudata->add_qlty_project($params);

		if($qlty_project_id) 
		{
			//防止恶意修改URL上传文件,验证后即销毁此session
			$_SESSION['sys_need_upload_file']=TRUE;

			//qlty_project_id  project_type  file(文档类型asset_type，头数字)
			return ajax_echo("操作成功，下一步请提交材料<script>location = '/?a=upload&qltyid=".$qlty_project_id."&ptype=".$project_type."&file=1';</script>");
		}


	}

	public function upload()
	{
		//if($_SESSION['sys_need_upload_file']){
			//unset($_SESSION['sys_need_upload_file']);
			$pid=z(t(v("qltyid")));
			$tid=$_SESSION['teacher']['teacher_id'];
			$asset_type_head=z(t(v("file")));


			/*
			*验证pid与tid一致
			*然后查询pid所有asset
			*/
			$edudata = self::getEdudataInstance();
			$qlty_asset_status = $edudata->get_qlty_asset_status($pid,$tid);
			
			$status_arr=Array();
			if($qlty_asset_status!==FALSE)
			{
				for($i=1;$i<10;$i++)
				{
					$status_arr[$i]=0;
					foreach($qlty_asset_status as $qs)
					{

						if($qs['asset_type']==($asset_type_head.$i) && $qs['asset_uri']!="")
						{
							$status_arr[$i]=1;
							break;
						}
					}

				}
			}

			$data['status_arr']=$status_arr;
			$data['qlty_project_id']=$pid;
			$data['asset_type_head']=$asset_type_head;
			$data['qlty_asset_type']=unserialize($_SESSION['qlty_asset_type']);
			$data['title']=$data['top_title']="上传资料";

			$data['title']=$data['top_title']="上传资料";
			render($data);

		//}else{die("Bad Request");}
	}
	
	public function uploadform()
	{
		$qlty_project_id=z(t(v('qid')));
		$asset_type=z(t(v("atp")));
		$tid=$_SESSION['teacher']['teacher_id'];

		//作为文件目录，区分证明材料certification目录
		$category="qlty";
		//生成安全验证字符串
		$str=generate_rand_str(4).$category.generate_rand_str(4).$asset_type.generate_rand_str(4).$qlty_project_id;

		//再次验证
		$edudata = self::getEdudataInstance();
		$qlty_asset_status = $edudata->get_qlty_asset_status($qlty_project_id,$tid);
		
		$disabled_btn=false;
		if($qlty_asset_status!==FALSE)
		{

			foreach($qlty_asset_status as $qs){
				if($qs['asset_type']==$asset_type && $qs['asset_uri']!="")
				{
					$disable_btn=true;
					break;
				}
			}
		}

		$data['disabled_btn']=$disable_btn;

		$data['pid']=$qlty_project_id;
		$data['asset_type']=$asset_type;
		$data['category']=$category;
		//用格林威治时间保证一致
		$data['encrypt_str']=encrypt($str,gmdate("Ymd",time()));
		
		$filetype=substr($asset_type,1);
		if($filetype=='1'){
			$data['filetype']="*.doc";
		}else if($filetype=='2'){
			$data['filetype']="*.pdf";
		}else if($filetype=='3'){
			$data['filetype']="*.ppt";
		}

		$data['js'][]="swfupload/swfupload.js";//这个改用SAE lib
		$data['js'][]="swfupload/fileprogress.js";
		$data['js'][]="swfupload/handlers.js";
		$data['js'][]="swfupload/swfupload.queue.js";

		$data['css'][]="swfupload.css";
		$data['title']=$data['top_title']="上传";
		render($data,'ajax','uploadform');
	}

	function ajax_update_asset()
	{
	//if($_SESSION['sys_need_upload_file']){
		//unset($_SESSION['sys_need_upload_file']);

		/*
		*这里需要验证，确认当前用户正在操作其主持的 状态为目前状态的项目
		*以后再补
		*/
		$fileName=z(t(v("fileName")));
		$type=z(t(v("type")));
		$pid=z(t(v("pid")));
		$qlty_asset_type=unserialize($_SESSION['qlty_asset_type']);
		$params=Array(
			'asset_name'=>$qlty_asset_type[$type],
			'qlty_project_id'=>$pid,
			'asset_uri'=>$fileName,
			'asset_type'=>$type
		);
		$edudata = self::getEdudataInstance();
		$asset_id = $edudata->add_qlty_project_asset($params);
		if($asset_id>0)
		{
			return ajax_echo("上传成功，数据已记录！");
		}else{
			return ajax_echo("发生错误，数据未成功记录！请联系管理员处理！");
		}


	//}else{die("Bad Request");}

	}

	public function mycourse()
	{
	
		$data['title']=$data['top_title']="课程项目状态";
		render($data);
	}

}
	