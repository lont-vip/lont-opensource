<?php

namespace Home\Controller;

class LoginController extends HomeController{
	
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array('submit','forget','register_index','register','cfg_name','_get_client_ip','teams','create_uuid','generat',"loginout","index_invite",'regss','code','forget_mobile');
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error(L("非法操作！"));
		}
	}
	
	//注册邀请码
	public function index_invite(){
		$uid=@$_GET['id'];
      	if($uid==''){
          	echo json_encode(array('status' => 0, 'msg' =>'参数错误'));exit();
        }
      	if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$uid)){
      		echo json_encode(array('status' => 0, 'msg' =>'参数错误'));exit();
        }
		$user=M('User')->field('id,mobile,invit,username')->where("invit='".$uid."'")->find();
		if(empty($user)){
			echo json_encode(array('status' => 0, 'msg' =>'参数错误'));exit();
		}
		echo json_encode(array('status' => 1, 'msg' =>$user));exit();
	}
	
	// 登录提交处理
	public function submit(){
		$config=M('Config')->field('web_close,web_close_cause')->where('id=1')->find();
		if($config['web_close']==0){
			echo json_encode(array('status' => 0,'info' =>$config['web_close_cause']));exit;
		}
		$mobile=@$_POST['mobile'];
		$password=@$_POST['password'];
		if($mobile==''){
			echo json_encode(array('status' => 0,'info' =>'请输入手机号'));exit;
		}
		if($password==''){
			echo json_encode(array('status' => 0,'info' =>'请输入密码'));exit;
		}
		if(!is_numeric($mobile)){
			echo json_encode(array('status' => 0,'info' =>'手机号格式错误'));exit;
		}
		if(strlen($mobile)!=11){
			echo json_encode(array('status' => 0,'info' =>'手机号格式错误'));exit;
		}
		$user = M('User')->field('invit,password,status,logins,id,uid,denglu_time,denglu_num')->where(array('mobile' => $mobile))->find();
		if(empty($user)){
			echo json_encode(array('status' => 0,'info' =>'账号或密码错误'));exit;
		}
		$xzsj=time()-$user['denglu_time'];
		if($xzsj<0){
			echo json_encode(array('status' => 3,'info' =>'密码多次输入错误，10分钟后再试'));exit;
		}
		if (md5($password.$user['invit']) != $user['password']){
			if($xzsj<600){
				if($user['denglu_num']>=2){
					M('user')->where('id='.$user['id'])->save(array('denglu_num'=>0,'denglu_time'=>(time()+600)));
				}else{
					M('user')->where('id='.$user['id'])->setInc('denglu_num',1);
				}
			}else{
				M('user')->where('id='.$user['id'])->save(array('denglu_num'=>1,'denglu_time'=>time()));
			}
			echo json_encode(array('status' => 0,'info' =>'账号或密码错误'));exit;
		}
		if($user['status']!=1){
			echo json_encode(array('status' => 0,'info' =>'账户被冻结'));exit;
		}
		$time_denglu=rand(11111,99999).'_'.time();
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $user['id']))->save(array('logins'=>($user['logins']+1),'time_denglu'=>$time_denglu,'denglu_num'=>0,'denglu_time'=>0));
			if(check_arr($rs)) {
				$mo->commit();
				M('UserTixing')->where('userid='.$user['id'])->delete();
				cookie('userid',$user['id'],86400);
				cookie('uid',$user['uid'],86400);
				cookie('time_denglu',$time_denglu,86400);
				$this->success('登录成功');
			}else {
				$mo->rollback();
				echo json_encode(array('status' => 0,'info' =>'登录失败'));exit;
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			echo json_encode(array('status' => 0,'info' =>'登录失败'));exit;
		}
	}
	
  	//网站标题
	public function cfg_name(){
		$xxx=M('config')->field('web_title,xnb_mr')->where('id=1')->find();
		echo json_encode(array('status' => 1, 'msg' =>$xxx['web_title'],'xnb' =>$xxx['xnb_mr']));exit;
	}
	
	//注册1 添加信息
	public function register_index(){
		if(cookie('register_index')=='register_index'){
          	$this->error('请勿重复操作');
        }
        $config=M('Config')->field('sta_zhuce')->where('id=1')->find();
        if($config['sta_zhuce']!=1){
        	$this->error('已暂停注册');
        }
        $mobile=@$_POST['mobile'];
        if($mobile==''){
        	$this->error('请输入手机号');
        }
        $yzm=@$_POST['yzm'];
        if($yzm==''){
        	$this->error('请输入图片验证码');
        }
        $code=@$_POST['code'];
        if($code==''){
        	$this->error('请输入短信验证码');
        }
        $invit=@$_POST['invit'];
        if($invit==''){
        	$this->error('请输入邀请码');
        }
        if(!is_numeric($mobile)){
        	$this->error('手机号格式错误');
        }
        if(strlen($mobile)!=11){
        	$this->error('手机号格式错误');
        }
        $mobile_ok=M('User')->field('id')->where("mobile='".$mobile."'")->find();
        if($mobile_ok){
        	$this->error('手机号已存在');
        }
		if (!check_verify(strtoupper($yzm),"1")) {
			$this->error('图片验证码错误');
		}
        if ($mobile != session('mobile_register_mobile')) {
        	$this->error('短信验证码不匹配');
        }
        if (md5($code.'mima') != session('mobile_register_code')) {
        	$this->error('短信验证码不匹配');
        }
        $chaoshi_time=session('mobile_register_time');
        if((time()-$chaoshi_time) > 600){
        	$this->error('短信验证码已过期');
        }
      	if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$invit)){
      		$this->error('邀请码错误');
        }
        $zt_user=M('User')->field('id,mobile,invit,username,status')->where("invit='".$invit."'")->find();
		if(empty($zt_user)){
			$this->error('邀请码错误');
		}
		if($zt_user['status']!=1){
			$this->error('邀请账户被冻结');
		}
		$username=$mobile;
        session('invit',$invit);
        session('username',$username);
        session('mobile',$mobile);
		cookie('register_index','register_index',2);
        echo json_encode(array('status' => 1,'info'=>'验证成功', 'invit' =>$invit, 'username' =>$username, 'mobile' =>$mobile));exit();
	}
	
	// 注册提交处理
	public function register(){
		if(cookie('register')=='register'){
          	$this->error('请勿重复操作');
        }
        $config=M('Config')->field('sta_zhuce')->where('id=1')->find();
        if($config['sta_zhuce']!=1){
        	$this->error('已暂停注册');
        }
        $invit=@$_POST['invit'];
        if($invit==''){
        	$this->error('请输入邀请码');
        }
      	if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$invit)){
      		$this->error('邀请码错误');
        }
        if($invit!=session('invit')){
        	$this->error('邀请码错误');
        }
        $zt_user=M('User')->field('id,mobile,invit,status,username,ceng,r_nums')->where("invit='".$invit."'")->find();
		if(empty($zt_user)){
			$this->error('邀请码错误');
		}
		if($zt_user['status']!=1){
			$this->error('推荐人已冻结');
		}
        $mobile=$_POST['mobile'];
        if($mobile==''){
        	$this->error('请输入手机号');
        }
        if(!is_numeric($mobile)){
        	$this->error('手机号格式错误');
        }
        if(strlen($mobile)!=11){
        	$this->error('手机号格式错误');
        }
        $mobile_ok=M('User')->field('id')->where("mobile='".$mobile."'")->find();
        if($mobile_ok){
        	$this->error('手机号已存在');
        }
        if($mobile!=session('mobile')){
        	$this->error('手机号错误');
        }
        $password=$_POST['password'];
        $password2=$_POST['password2'];
        $paypassword=$_POST['paypassword'];
        $paypassword2=$_POST['paypassword2'];
        if($password==''){
        	$this->error('请输入登录密码');
        }
        if($password2==''){
        	$this->error('请输入确认登录密码');
        }
        if($paypassword==''){
        	$this->error('请输入交易密码');
        }
        if($paypassword2==''){
        	$this->error('请输入确认交易密码');
        }
        if (strlen($password) > 18 || strlen($password) < 8) {
        	$this->error('登录密码8-18位');
        }
        if($password!=$password2){
        	$this->error('两次登录密码输入不一致');
        }
        if (strlen($paypassword) > 18 || strlen($paypassword) < 8) {
        	$this->error('资产密码8-18位');
        }
        if($paypassword!=$paypassword2){
        	$this->error('两次交易密码输入不一致');
        }
        $username=$mobile;
        $ceng=$zt_user['ceng']+1;
        $invit_1=$zt_user['id'];
        $invit_2=0;
        $invit_3=0;
        if($zt_user['id']>0){
        	$invit_2_arr=M('User')->field('id,zt_ren')->where("id=".$zt_user['id'])->find();
        	if($invit_2_arr){
        		if($invit_2_arr['zt_ren']>0){
        			$invit_2=$invit_2_arr['zt_ren'];
        			$invit_3_arr=M('User')->field('id,zt_ren')->where("id=".$invit_2)->find();
        			if($invit_3_arr){
        				if($invit_3_arr['zt_ren']>0){
        					$invit_3=$invit_3_arr['zt_ren'];
        				}
        			}
        		}
        	}
        }
        $addip=$this->_get_client_ip();
        $invit_xx=$this->generat(8);
        $uid=$this->create_uuid();
        $password=md5($password.$invit_xx);
        $paypassword=md5($paypassword.$invit_xx);
        $delu_time=rand(111111,999999).'_'.time();
        $redis = new \Redis();
		$redis->connect('127.0.0.1', 6379);
        try{
        	$mo = M();
        	$mo->startTrans();
        	$rs[] = $mo->table('tw_user')->add(array('username'=>$username,'uid'=>$uid,'mobile'=>$mobile,'password' =>$password,'paypassword' =>$paypassword,'invit'=>$invit_xx,'addip' =>$addip,'time_denglu'=>$delu_time,'level_user'=>1,'level_tuandui'=>1,'addtime'=>time(),'status'=>1,'zt_ren'=>$invit_1,'ceng'=>$ceng,'invit_1'=>$invit_1,'invit_2'=>$invit_2,'invit_3'=>$invit_3));
        	$rs[] = $mo->table('tw_user_coin')->add(array('userid'=>$rs[0]));
        	if(check_arr($rs)) {
        		$mo->commit();
        		cookie('userid',$rs[0],86400);
				cookie('uid',$uid,86400);
				cookie('time_denglu',$delu_time,86400);
        		cookie('register','register',4);
        		$armap=array();
				$armap['zt_ren']=$zt_user['id'];
				$armap['level_user']=1;
				$armap['level_tuandui']=1;
				$armap['sta_zhitui']=1;
				$armap['sta_tuandui']=1;
				$redis->hmset('user_ztren_'.$rs[0],$armap);
        		if($zt_user['id']>0){
        			M('User')->where('id='.$zt_user['id'])->setInc('r_nums',1);
        			$this->teams($zt_user['id'],$rs[0]);
        		}
        		$this->success('注册成功');
        	}else {
        		$mo->rollback();
        		$this->error('注册失败');
        	}
        }catch(\Think\Exception $e){
        	$mo->rollback();
        	$this->error('注册失败');
        }
	}
  	
  	//得到真实ip
	private function _get_client_ip(){
      	$ip = $_SERVER['REMOTE_ADDR'];
      	if(isset($_SERVER['HTTP_CDN_SRC_IP'])){
          	$ip = $_SERVER['HTTP_CDN_SRC_IP'];
        }elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])){
          	$ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)){
          	foreach ($matches[0] AS $xip){
              	if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)){
                  	$ip = $xip;
                  	break;
                }
            }
        }
      	return $ip;
    }
  
	//团队上增
  	public function teams($zt_ren,$id){
		if(is_numeric($zt_ren) && is_numeric($id)){
			$ztren=M('User')->field('zt_ren,xq_nums,xq_ids')->where('id='.$zt_ren)->find();
			if($ztren){
				M('User')->where('id='.$zt_ren)->save(array('xq_nums'=>($ztren['xq_nums']+1),'xq_ids'=>($ztren['xq_ids'].$id.',')));
              	if($ztren['zt_ren']>0){
					$this->teams($ztren['zt_ren'],$id);
				}
			}
		}
	}
	
	//获得UUID
	function create_uuid($prefix = ""){
		$str = md5(uniqid(mt_rand(), true));
		$uuid  = substr($str,0,8) . '-';
		$uuid .= substr($str,8,4) . '-';
		$uuid .= substr($str,12,4) . '-';
		$uuid .= substr($str,16,4) . '-';
		$uuid .= substr($str,20,12);
		return $prefix . $uuid;
	}
	
	//邀请码
	public function generat( $length = 13 ) {
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$user_sn ='';  
		for ( $i = 0; $i < $length; $i++ ) {
			$user_sn .= $chars[ mt_rand(0, strlen($chars) - 1) ];  
		}
		$userinfo = M('user')->field('id')->where("invit='".$user_sn."'")->find();
		if (!empty($userinfo)) {
			$this->generat($length);
		} else {
			return $user_sn;
		}
	}
	
	// 找回密码页面
	public function forget(){
		//手机号登录
		$mobile=@$_POST['mobile'];
		$mobile_code=@$_POST['mobile_code'];
		$verify_code=@$_POST['verify_code'];
		$mobile_password=@$_POST['mobile_password'];
		$mobile_password2=@$_POST['mobile_password2'];
		if($mobile==''){
			echo json_encode(array('status' => 0,'info' =>'请输入手机号'));exit;
		}
		if($verify_code==''){
			echo json_encode(array('status' => 0,'info' =>'请输入图形验证码'));exit;
		}
		if (!check_verify(strtoupper($verify_code),"1")) {
			$this->error('图形验证码错误');
		}
		if($mobile_code==''){
			echo json_encode(array('status' => 0,'info' =>'请输入手机验证码'));exit;
		}
		if($mobile_password==''){
			echo json_encode(array('status' => 0,'info' =>'请输入登录密码'));exit;
		}
		if($mobile_password2==''){
			echo json_encode(array('status' => 0,'info' =>'请输入确认密码'));exit;
		}
		if(!preg_match("/^1[23456789]{1}\d{9}$/",$mobile)){
			echo json_encode(array('status' => 0,'info' =>'手机号格式错误'));exit;
		}
		if($mobile_password!=$mobile_password2){
			echo json_encode(array('status' => 0,'info' =>'两次新登录密码输入不一致'));exit;
		}
		if ($mobile != session('mobile_forget_mobile')) {
			echo json_encode(array('status' => 0,'info' =>'短信验证码不匹配'));exit;
		}
		if (md5($mobile_code.'mima') != session('mobile_forget_code')) {
			echo json_encode(array('status' => 0,'info' =>'短信验证码不匹配'));exit;
		}
		$chaoshi_time=session('mobile_forget_time');
		if((time()-$chaoshi_time)>120){
			echo json_encode(array('status' => 0,'info' =>'短信验证码已过期'));exit;
		}
		if(strlen($mobile_password)<8 || strlen($mobile_password)>18){
			echo json_encode(array('status' => 0,'info' =>'登录密码8-18位'));exit;
		}
		$user = M('User')->field('invit,id,mobile')->where(array('mobile' => $mobile))->find();
		if(empty($user)){
			echo json_encode(array('tishi_num'=>5,'status' => 0,'info' =>'手机号不存在'));exit;
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $user['id']))->save(array('password'=>md5($mobile_password.$user['invit']),'time_xg'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				session('mobile_forget_code', '');
				$this->success('修改成功');
			}else {
				$mo->rollback();
				echo json_encode(array('status' => 0,'info' =>'修改失败'));exit;
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			echo json_encode(array('status' => 0,'info' =>'修改失败'));exit;
		}
	}
	
	// 退出登录
	public function loginout(){
		cookie('userid','no',86400);
      	echo json_encode(array('status'=>0,'info'=>'退出成功'));exit();
	}
	
	// 注册账号：短信
	public function regss(){
      	$mobile=$_POST['mobile'];
      	$intnum=$_POST['intnum'];
      	$verify=$_POST['verify'];
		$config=M('Config')->where(array('id' => 1))->find();
		if (checkstr($mobile) || checkstr($verify)) {
			$this->error(L('您输入的信息有误！'));
		}
		if(!is_numeric($mobile)){
			$this->error('手机号格式错误');
		}
		if(strlen($mobile)!=11){
			$this->error('手机号格式错误');
		}
		if (!check_verify(strtoupper($verify),"1")) {
			$this->error('图形验证码错误');
		}
		$zhuce_num=M('User')->where(array('mobile' => $mobile))->count();
		if($zhuce_num>0){
			$this->error('手机号已存在');
		}
      	$chaoshi_time=session('mobile_register_time');
		if((time()-$chaoshi_time) < 600 && $chaoshi_time > 0){
			$this->error('操作频繁,10分钟后试');
		}
        /*$code = rand(100000, 999999);
		$mobile = $mobile;
        $smsapi = "http://utf8.api.smschinese.cn/"; //短信网关
		$user = '13063663676'; //短信平台帐号
		$content = '您好，您的验证码是' . $code; //要发送的短信内容
      	$statusStr = array("0" => "短信发送成功","-1" => "没有该用户账户","-2" => "接口密钥不正确","-21" => "MD5接口密钥加密不正确误","-3" => "短信数量不足","-11" => "该用户被禁用","-14" => "短信内容出现非法字符","-4" => "手机号格式不正确","-41" => "手机号码为空","-42"=>'短信内容为空',"-51" => "短信签名格式不正确","-52"=>'短信签名太长');
		$sendurl = $smsapi . "?Uid=" . $user . "&Key=d41d8cd98f00b204e980&smsMob=" . $mobile . "&smsText=" . $content;*/
        //要发送的短信内容

		$code = rand(111111, 999999);
		$content = "您正在注册账号，您的验证码是:". $code;
		$sign = "【".$config['smsqm']."】";
		$mobile = $mobile;
      	$statusStr = array("0" => "短信发送成功","-1" => "参数不全","-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！","30" => "服务密码错误","40" => "账号不存在","41" => "余额不足","42" => "帐户已过期","43" => "IP地址限制","50" => "内容含有敏感词","100"=>'您操作太频繁，请稍后再试');
      	$config = M('Config')->where(array('id' => 1))->find();
  		$smsapi = "http://api.smsbao.com/";
 		$user = $config['smsname']; //短信平台帐号
		$pass = md5($config['smspass']); //短信平台密码
  		$content = $sign.$content;
 		$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$mobile."&c=".urlencode($content);
 		$result =file_get_contents($sendurl);
      	 if ( $re != 0 ) {
           	$data['info'] = $statusStr[$re];
			$this->error($data['info']);
         } else {
            session('mobile_register_code', md5($code.'mima'));
            session('mobile_register_mobile',$mobile);
            session('mobile_register_time',time());
			$this->success('验证码已发送');
         }
	}
  	
	// 找回密码-短信
	public function forget_mobile(){
      	$mobile=$_POST['mobile'];
      	$intnum=$_POST['intnum'];
      	$verify=$_POST['verify'];
		$config=M('Config')->where(array('id' => 1))->find();
		if (checkstr($mobile) || checkstr($verify)) {
			$this->error(L('您输入的信息有误！'));
		}
		if(!is_numeric($mobile)){
			$this->error('手机号格式错误');
		}
		if(strlen($mobile)!=11){
			$this->error('手机号格式错误');
		}
		if (!check_verify(strtoupper($verify),"1")) {
			$this->error('图形验证码错误');
		}
		$user=M('User')->field('mobile')->where(array('mobile' => $mobile))->find();
		if (!$user) {
			$this->error(L('手机号错误！'));
		}
      	$chaoshi_time=session('mobile_forget_time');
		if((time()-$chaoshi_time)<120 && $chaoshi_time>0){
			$this->error('操作频繁,2分钟后试');
		}
        /*$code = rand(100000, 999999);
		$mobile = $mobile;
        $smsapi = "http://utf8.api.smschinese.cn/"; //短信网关
		$user = '13063663676'; //短信平台帐号
		$content = '您正在找回密码，您的验证码是' . $code; //要发送的短信内容
      	$statusStr = array("0" => "短信发送成功","-1" => "没有该用户账户","-2" => "接口密钥不正确","-21" => "MD5接口密钥加密不正确误","-3" => "短信数量不足","-11" => "该用户被禁用","-14" => "短信内容出现非法字符","-4" => "手机号格式不正确","-41" => "手机号码为空","-42"=>'短信内容为空',"-51" => "短信签名格式不正确","-52"=>'短信签名太长');
		$sendurl = $smsapi . "?Uid=" . $user . "&Key=d41d8cd98f00b204e980&smsMob=" . $mobile . "&smsText=" . $content;*/
		$code = rand(111111, 999999);
		$content = "您正在找回密码，您的验证码是:". $code;
		$sign = "【".$config['smsqm']."】";
		$mobile = $mobile;
      	$statusStr = array("0" => "短信发送成功","-1" => "参数不全","-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！","30" => "服务密码错误","40" => "账号不存在","41" => "余额不足","42" => "帐户已过期","43" => "IP地址限制","50" => "内容含有敏感词","100"=>'您操作太频繁，请稍后再试');
      	$config = M('Config')->where(array('id' => 1))->find();
  		$smsapi = "http://api.smsbao.com/";
 		$user = $config['smsname']; //短信平台帐号
		$pass = md5($config['smspass']); //短信平台密码
  		$content = $sign.$content;
 		$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$mobile."&c=".urlencode($content);
 		$result =file_get_contents($sendurl);
      	 if ( $r != 0 ) {
           	$data['info'] = $statusStr[$r];
			$this->error($data['info']);
         } else {
            session('mobile_forget_code', md5($code.'mima'));
            session('mobile_forget_mobile',$mobile);
            session('mobile_forget_time',time());
			$this->success('验证码已发送');
         }
	}
	
	//图片验证码
	public function code(){
		$config['useNoise'] = true; // 关闭验证码杂点
		$config['length'] = 4; // 验证码位数
		$config['codeSet'] = '0123456789';
		ob_clean();
		$verify = new \Think\Verify($config);
		$verify->entry(1);
	}
	
}
?>