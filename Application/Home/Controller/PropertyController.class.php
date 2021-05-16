<?php
namespace Home\Controller;

class PropertyController extends HomeController{
  
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array('wallet_mas','coin_mas','coin_list','shoukuan_mas','moren_coin','rollout_mas','transfer_mobile_paypassword','financing_zr_num','financing_zr_log','financing_zc_num','financing_zc_log');
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error(L("非法操作！"));
		}
	}
	
	//钱包列表
	public function coin_list(){
		$coinlist=M('Coin')->field('name,js_yw,img')->where('status=1 and id>1')->select();
		echo json_encode(array('status'=>1,'msg'=>'获取成功','coinlist'=>$coinlist));exit;
	}
	
	//资产详情
	public function wallet_mas(){
    	$userid=userid();
      	if(!is_numeric($userid)){
          	echo json_encode(array('code'=>0,'msg'=>'请先登录'));exit;
        }
      	$user = M('User')->where('id='.$userid)->find();
      	if(empty($user)){
          	echo json_encode(array('code'=>0,'msg'=>'请先登录'));exit;
        }
      	$usercoin = M('UserCoin')->where('userid='.$userid)->find();
        $config=M('config')->where('id=1')->find();
        $coin=M('coin')->where('id>1')->order('id desc')->select();
        $zhehe_btc=0;
        $zhehe_btc_cny=0;
        foreach($coin as $k=>$v){
        	$coin[$k]['keyong']=$usercoin[$v['name']]*1;
        	$coin[$k]['dongjie']=$usercoin[$v['name'].'d']*1;
        	$price=$config[$v['name']]*1;
        	$mycoin=intval($usercoin[$v['name']]*100000)/100000;
        	$zong=intval($mycoin*$price*100)/100;
        	$coin[$k]['rmb']=$zong*1;
        	$zhehe_btc_cny+=$zong;
        }
      	echo json_encode(array('status'=>1,'msg'=>'获取成功','zhehe_cny'=>$zhehe_btc_cny,'coinlist'=>$coin));exit;
    }
	
	//钱包详情
	public function coin_mas(){
      	$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error("请先登录");
        }
      	$user = M('User')->field('id,mobile')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error("请先登录");
        }
		$coin=$_GET['coin'];
		if($coin==''){
			$this->error("币种错误");
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$coin)){
			$this->error("币种错误");
		}
		$coin_ok = M('Coin')->field('id,name,js_yw,title,img')->where("id>0 and name='".$coin."'")->find();
		if(empty($coin_ok)){
			$this->error("币种错误");
		}
		$config = M('Config')->field($coin)->where("id=1")->find();
		$usercoin=M('UserCoin')->where("userid=".$userid)->find();
		$coin_ok['keyong']=$usercoin[$coin];
		$coin_ok['dongjie']=$usercoin[$coin.'d'];
		$coin_ok['zong']=round(($coin_ok['keyong']+$coin_ok['dongjie'])*$config[$coin],2);
		echo json_encode(array('status'=>1,'msg'=>'获取成功','coin'=>$coin_ok));exit;
	}
	
	//默认币种
	public function moren_coin(){
		$list = M('Config')->field('xnb_mr')->where("id=1")->find();
		echo json_encode(array('status'=>1,'coin'=>$list['xnb_mr']));exit();
	}
	
	//转入信息
	public function shoukuan_mas(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$coin=$_GET['coin'];
		if($coin==''){
			$this->error('参数错误');
		}
		$coinb=$coin.'b';
		$usercoin=M('UserCoin')->field($coinb)->where("userid=".$uid)->find();
		if($usercoin[$coinb]==0 || $usercoin[$coinb]==''){
			$Coins = M('Coin')->where(array(
				'status' => 1,
				'type'   => array('neq', 'ptb'),
				'name'   => array('neq', Anchor_CNY),
			))->select();
			foreach ($Coins as $k => $v) {
				$coin_list[$v['name']] = $v;
			}
			if(!($coin)){
				$coin = $Coins[0]['name'];
			}
			$user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
			
			$user_coin[$coin] = round($user_coin[$coin], 6);
			$user_coin[$coin] = sprintf("%.4f", $user_coin[$coin]);
			$user_coin[$coin.'d'] = round($user_coin[$coin.'d'], 6);
			$user_coin[$coin.'d'] = sprintf("%.4f", $user_coin[$coin.'d']);
			$Coins = M('Coin')->where(array('name' => $coin))->find();
			$state_coin = 0;
			$qianbao = '';
			if (!$Coins['zr_jz']) {
				$qianbao = L('当前币种禁止转入！');
				$state_coin = 1;
			} else {
				$qbdz = $coin.'b';
				$dj_username = $Coins['dj_yh'];
				$dj_password = $Coins['dj_mm'];
				$dj_address = $Coins['dj_zj'];
				$dj_port = $Coins['dj_dk'];
				if (!$user_coin[$qbdz]) {
					if ($Coins['type'] == 'ptb') {
						$qianbao = md5(username() . $coin);
						$rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao));
						if (!$rs) {
							$qianbao = L('生成钱包地址出错！');
							$state_coin = 1;
						}
					}
					if ($Coins['type'] == 'qbb') {
						
						//查询对应中心钱包的地址
						$zxqianbao=M('Coin')->where("dj_yh='".$dj_username."' and name!='".$coin."'")->select();
						$yok_qianbao='';
						foreach($zxqianbao as $n=>$var){
							if($user_coin[$var['name'].'b']){
								if($user_coin[$var['name'].'b']!=0){
									$yok_qianbao=$user_coin[$var['name'].'b'];
								}
							}
						}
						if($yok_qianbao){
							$qianbao=$yok_qianbao;
							M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao));
						}else{
							if($Coins['api_type']=='eth'){
								$EthClient = EthCommon($dj_address, $dj_port);
									if (!$EthClient) {
										$qianbao = L('钱包链接失败！');
										$state_coin = 1;
									} else {
										$qianbao = $EthClient->personal_newAccount(username());//根据用户名生成账户
										if (!$qianbao || preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$qianbao)) {
											$qianbao = L('生成钱包地址出错5！');
											$state_coin = 1;
										} else {
											M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao));
										}
									}
							}else{
								$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
								$json = $CoinClient->getinfo();
								if (!isset($json['version']) || !$json['version']) {
									$qianbao = L('0x5DF26750DcFBc33089abB1f6F04ee6eFD92d3d81');
									//$qianbao = L('钱包链接失败！');
									$state_coin = 1;
								} else {
									$qianbao_addr = $CoinClient->getaddressesbyaccount(username());
									if (!is_array($qianbao_addr)) {
										$qianbao_ad = $CoinClient->getnewaddress(username());
										if (!$qianbao_ad) {
											$qianbao = L('生成钱包地址出错2！');
											$state_coin = 1;
										} else {
											$qianbao = $qianbao_ad;
										}
									} else {
										$qianbao = $qianbao_addr[0];
									}

									if (!$qianbao || preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$qianbao)) {
										//$this->error(L('生成钱包地址出错！'));
										$qianbao = L('生成钱包地址出错1！');
										$state_coin = 1;
									}
									$rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao));
									if (!$rs) {
										//$this->error(L('钱包地址添加出错！'));
									}
								}
							}
						}
					}
				}
			}
		}
		if(strlen($qianbao)==42){
			$qianbao='';
		}
		$coin_arr=M('Coin')->field('name,img,js_yw,title')->where("name='".$coin."'")->find();
		$coin_arr['name_d']=$coin_arr['js_yw'];
		$usercoin=M('UserCoin')->field($coinb)->where("userid=".$uid)->find();
		$usercoin['coin_xs']=$usercoin[$coinb];
		if(strlen($usercoin[$coinb])>0 && strlen($usercoin[$coinb])<=10){
			$usercoin['coin_xs']=substr($usercoin[$coinb],0,2).'...'.substr($usercoin[$coinb],-3);
		}
		if(strlen($usercoin[$coinb])>10 && strlen($usercoin[$coinb])<=20){
			$usercoin['coin_xs']=substr($usercoin[$coinb],0,5).'...'.substr($usercoin[$coinb],-5);
		}
		if(strlen($usercoin[$coinb])>20){
			$usercoin['coin_xs']=substr($usercoin[$coinb],0,13).'...'.substr($usercoin[$coinb],-13);
		}
		$coin_arr['script']='<script type="text/javascript">
       						 $(".codeaa").qrcode({
							 render: "canvas", //table方式
							 size: 200,
							 text: "'.$usercoin[$coinb].'"
						});
					</script>';
		$coinlist=M('Coin')->field('name,js_yw,img')->where('status=1 and id>1')->select();
		echo json_encode(array('status'=>1,'coin'=>$coin_arr,'coinlist'=>$coinlist,'qianbao_mas'=>'0x5DF26750DcFBc33089abB1f6F04ee6eFD92d3d81','usercoin'=>$usercoin));exit();
	}
	
	//转出信息
	public function rollout_mas(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$coin=$_GET['coin'];
		
		$coin_arr=M('Coin')->field('title,name,img,js_yw,zc_fee')->where("name='".$coin."'")->find();
		$coin_arr['zc_fee2']=$coin_arr['zc_fee']/100;
		$coin_arr['name_d']=$coin_arr['js_yw'];
		$usercoin=M('UserCoin')->field($coin)->where("userid=".$uid)->find();
      	$usercoin['usdt']=intval($usercoin['usdt']*100000)/100000;
      	$coin_arr['coinlist']=M('Coin')->field('name,js_yw,img')->where('status=1 and id>1')->select();
		echo json_encode(array('status'=>1,'coin'=>$coin_arr,'usercoin'=>$usercoin[$coin]));exit();
	}
	
  	//转出验证手机号
	public function transfer_mobile_paypassword(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		$user=M('User')->field('mobile')->where(array('id' => $uid))->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		if($user['mobile']==''){
			$this->error('没绑定手机号');
		}
		$mobile=$user['mobile'];
		$config=M('Config')->where(array('id' => 1))->find();
      	$chaoshi_time=session('mobile_transfer_time');
		if((time()-$chaoshi_time)<120 && $chaoshi_time>0){
			$this->error('操作频繁,2分钟后试');
		}
		$code = rand(111111, 999999);
		$content = "您的提现安全验证码为:". $code;
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
            session('mobile_transfer_code', md5($code.'mima'));
            session('mobile_transfer_mobile',$mobile);
            session('mobile_transfer_time',time());
			$this->success('验证码已发送');
		}
	}
	
	//转入信息量
	public function financing_zr_num(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$coin=$_GET['coin'];
		if($coin==''){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$coin)){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
		$list = M('Myzr')->where("userid=".$uid." and coinname='".$coin."'")->count();
		if($list>0){
			echo json_encode(array('status'=>1,'info'=>'获取成功'));exit();
		}
		echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
	}
	
	//转入信息
	public function financing_zr_log(){
    	$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
			echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
		}
		$coin_sql='';
		$coin=$_GET['coin'];
		if($coin==''){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$coin)){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
		$coinb=$coin.'b';
		//$coin_arr=M('Coin')->field('name,img,js_yw')->where("name='".$coin."'")->find();
		//$coin_user=M('UserCoin')->field($coinb)->where("userid=".$uid)->find();
		$pages=($page-1)*10;
		$sta_mas=array('0'=>'异常','1'=>'充值成功','2'=>'充值失败','3'=>'等待审核');
		
		if($coin=='cny'){
			$list = M('Myzr')->where("userid=".$uid)->order('id desc')->limit($pages,10)->select();
		}else{
			$list = M('Myzr')->where("userid=".$uid." and coinname='".$coin."'")->order('id desc')->limit($pages,10)->select();
		}
		foreach($list as $k => $v){
			if($v['address']==''){
				$list[$k]['address']='-';
			}else{
				$list[$k]['address']=substr($v['address'],0,8).'...'.substr($v['address'],-8);
			}
			$list[$k]['address']=substr($v['username'],0,8).'...'.substr($v['username'],-8);
      		$list[$k]['num']=$v['mum']*1;
			$list[$k]['coin']=strtoupper($v['coinname']);
          	$list[$k]['addtime']=date("Y-m-d H:i:s",$v['addtime']);
          	if($v['status']==1){
          		$list[$k]['status_mas']='<p>'.$sta_mas[$v['status']].'</p>';
          	}else{
          		$list[$k]['status_mas']='<p style="color: #1a604a;">'.$sta_mas[$v['status']].'</p>';
          	}
		}
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
	//转出信息量
	public function financing_zc_num(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$coin=$_GET['coin'];
		if($coin==''){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$coin)){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
		$list = M('Myzc')->where("userid=".$uid." and coinname='".$coin."'")->count();
		if($list>0){
			echo json_encode(array('status'=>1,'info'=>'获取成功'));exit();
		}
		echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
	}
	
	//转出信息
	public function financing_zc_log(){
    	$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
			echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
		}
		$coin_sql='';
		$coin=$_GET['coin'];
		if($coin==''){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$coin)){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
		//$coin_arr=M('Coin')->field('name,img,js_yw')->where("name='".$coin."'")->find();
		$pages=($page-1)*10;
		$sta_mas=array('0'=>'等待处理','1'=>'提现成功','2'=>'提现失败','3'=>'处理中');
		if($coin=='cny'){
			$list = M('Myzc')->where("userid=".$uid)->order('id desc')->limit($pages,10)->select();
		}else{
			$list = M('Myzc')->where("userid=".$uid." and coinname='".$coin."'")->order('id desc')->limit($pages,10)->select();
		}
		foreach($list as $k => $v){
			if($v['txid']==''){
				$list[$k]['address']='-';
			}else{
				$list[$k]['address']=substr($v['txid'],0,8).'...'.substr($v['txid'],-8);
			}
			$list[$k]['address']=substr($v['username'],0,8).'...'.substr($v['username'],-8);
      		$list[$k]['num']=$v['mum']*1;
			$list[$k]['coin']=strtoupper($v['coinname']);
			$list[$k]['status_mas']=$sta_mas[$v['status']];
          	$list[$k]['addtime']=date("Y-m-d H:i:s",$v['addtime']);
		}
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
	
}
?>