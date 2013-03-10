<?php
	//防止一些非法上传

if (!isset($_POST["sys"]) || $_POST["sys"]!=="qlty" || !isset($_POST["weisecret"]) ) {
	die("Bad Request");
}
else
{
	include_once("decrypt.php");
	$decrypt_str=decrypt($_POST["weisecret"],gmdate("Ymd",time()));

	if( substr($decrypt_str,4,4)==$_POST["sys"] && substr($decrypt_str,12,2)==$_POST["type"] && substr($decrypt_str,18)==$_POST["pid"])
	{		

		//加密字符串，验证成功
		$category=$_POST["sys"];
		$type=$_POST["type"];
		$pid=$_POST["pid"];

		if( !defined('DS') ) define( 'DS' , '/' );

		if (!isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0) {
			die(json_encode(Array('err_code'=>1,'msg'=>'发生错误，上传失败！')));
		}else{
			$dir=$category.DS.$pid.DS.$type;
			if(!is_dir($dir)) mkdir($dir, 0755, true);

			$file_extension=pathinfo($_FILES["Filedata"]["name"], PATHINFO_EXTENSION);
			$file_name=date("Ymdhi",time()).'.'.$file_extension;

			//返回数据给swfupload，在uploadsuccess侦听函数的serverdata中获取
			if (move_uploaded_file($_FILES['Filedata']['tmp_name'], $dir.DS.$file_name)) {
				die(json_encode(Array('err_code'=>0,'filename'=>$dir.DS.$file_name,'type'=>$type,'pid'=>$pid)));
			}

		}

		die(json_encode(Array('err_code'=>2,'msg'=>"Possible file upload attack!")));

	}


}