<?php
namespace Home\Controller;

class UserController extends HomeController{
  
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array('mine_mas','shiming_yanzheng','commission_shifang_list','commission_list','auth_ajax2','user_mas2','set_ajax','generalize_list','headimg_img','invite','team_mas','team_1','team_2','financial_mas','alipay_mas','wechat_mas','zfb_img','wx_img','financial_sta','financial_sta_del','bank_add_ajax','user_bank','addcard_no','addcard_del','feed_log','feedback_img','repair','invite','auth_ajax','idcard_img','user_mas','paypassword','sendcheck_mobile_paypassword','password','sendcheck_mobile_password','mobile','sendcheck_mobile_mobile','mine_mas','message_mes','headimg_img','message_ajax','user_bank_mas','user_bank2','editcard_ajax','wx_img','addcard_ok','addcard_no','addcard_del','user_bank','addcard_ajax','addzfb_ajax','addwx_ajax','zfb_img','me_mas','shiming_tuandui','shiming_shenhe');
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error(L("非法操作！"));
		}
	}
	
	
	
	//我的返佣
	public function commission_list(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
      		$this->error('暂无数据');
		}
		$pages=($page-1)*10;
		$list = M('MiningFanyong')->field('id,sta_zxfy,status,time_add,num_zong,num_day,num_zx_zong')->where('userid='.$userid)->order('id desc')->limit($pages,10)->select();
      	if(empty($list)){
          	$this->error('暂无数据');
        }
		foreach($list as $n=>$var){
			$list[$n]['num_zong']=$list[$n]['num_zong']*1;
			$list[$n]['num_day']=$list[$n]['num_day']*1;
			$list[$n]['time_add']=date("Y.m.d H:i:s",$var['time_add']);
			if($var['status']==1){
				$list[$n]['sta_mas']='已到期';
			}else if($var['sta_zxfy']==1){
				$list[$n]['sta_mas']='已开启';
			}else{
				$list[$n]['sta_mas']='已停止';
			}
		}
		echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$list));exit();
	}
	
	
	//我的返佣释放记录
	public function commission_shifang_list(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$nameid=$_GET['id'];
		if(!is_numeric($nameid)){
			$this->error('暂无数据');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
      		$this->error('暂无数据');
		}
		$pages=($page-1)*10;
		$list = M('Finance')->field('addtime,fee')->where('protype=16 and status=1 and nameid='.$nameid.' and userid='.$userid)->order('id desc')->limit($pages,10)->select();
      	if(empty($list)){
          	$this->error('暂无数据');
        }
		foreach($list as $n=>$var){
			$list[$n]['fee']=$list[$n]['fee']*1;
			$list[$n]['addtime']=date("Y.m.d H:i:s",$var['addtime']);
		}
		echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$list));exit();
	}
	
	//上传身份证图片
	public function idcard_img(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
		$upload = new \Think\Upload();
		$upload->maxSize = 15728640;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/idcard/';
		$upload->autoSub = false;
		$info = $upload->upload();
		if(!$info){
			$this->error($upload->getError());
		}else{
			$upload_dir='Upload/idcard/';
			$image = new \Think\Image();
			foreach ($info as $k => $v) {
				$path = $v['savepath'] . $v['savename'];
				$file_url = $upload_dir . $v['savename'];
	            $image->open($file_url);
	            $width = $image->width();
	            $height = $image->height();
	            if($width > 500 || $height > 500){
	                $image->thumb(500, 500)->save($file_url);
	            }
	            echo json_encode(array('status'=>1,'info'=>$path));exit;
				echo $path;
				exit();
				
			}
		}
	}
	
	//实名认证
	public function auth_ajax2(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
      	$user = M('User')->field('mobile,idstate')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
        if($user['idstate']==2){
        	$this->error('请勿重复操作');
        }
		$input = I('post.');
		$truename=$input['truename'];
		$idcard=$input['idcard'];
		$card_a=$input['idcard_a'];
		$card_b=$input['idcard_b'];
		$card_c=$input['idcard_c'];
		if($truename==''){
			$this->error('请输入真实姓名');
		}
		if($idcard==''){
			$this->error('请输入身份证号');
		}
		if($card_a==''){
			$this->error('请上传身份证正面');
		}
		if($card_b==''){
			$this->error('请上传身份证反面');
		}
		if($card_c==''){
			$this->error('请上传手持身份证');
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$truename)){
			$this->error('真实姓名格式错误');
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$idcard)){
			$this->error('身份证号格式错误');
		}
		if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$card_a)){
			$this->error('身份证正面上传错误');
		}
		if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$card_b)){
			$this->error('身份证反面上传错误');
		}
		if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$card_c)){
			$this->error('手持身份证上传错误');
		}
		if(strlen($idcard)!=15 && strlen($idcard)!=18){
			//$this->error('身份证号格式错误');
		}
		$condd=M('Config')->field('idcard_num')->where('id=1')->find();
		$idcard_ok = M('User')->field('id')->where("idcard='".$idcard."' and id!=".$userid)->count();
		if(($idcard_ok+1)>$condd['idcard_num']){
			$this->error('身份证号账号数量已达到'.$config['idcard_num'].'个');
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $userid))->save(array('truename'=>$truename,'idstate'=>1,'idcard'=>$idcard,'idcard_a'=>$card_a,'idcard_b'=>$card_b,'idcard_c'=>$card_c,'time_xg'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				$this->success('操作成功');
			}else {
				$mo->rollback();
				$this->error('修改失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('修改失败');
		}
	}
	//用户信息
	public function user_mas2(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
      	$user = M('User')->field('mobile,truename,idcard,idcard_a,idcardinfo,idcard_b,idcard_c,idstate')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
        $user['idcard2']=$user['idcard'];
        $shiming=0;
        if($user['idstate']>0){
	        if($user['truename']){
	        	$shiming=1;
	        	$user['idcard']=substr($user['idcard'],0,2).'********'.substr($user['idcard'],-2);
	        }
        }
        echo json_encode(array('status'=>1,'xinxi_xg'=>$config['xinxi_xg'],'type'=>1,'shiming'=>$shiming,'user'=>$user,'mobile'=>$user['mobile']));exit;
	}
	//团队手续费分红
	public function generalize_list(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
			echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
		}
		$pages=($page-1)*10;
		$list = M('Finance')->field('fee,addtime,level_tuandui')->where('protype=11 and userid='.$userid)->order('id desc')->limit($pages,10)->select();
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
        foreach($list as $n=>$var){
        	$list[$n]['addtime']=date("Y.m.d H:i:s",$var['addtime']);
        	$list[$n]['fee']=$var['fee']*1;
        	$list[$n]['level_tuandui']='-';
        	if($var['level_tuandui']>0){
        		$level_tuandui=M('LevelTuandui')->field('name')->where('id='.$var['level_tuandui'])->find();
        		if($level_tuandui){
        			$list[$n]['level_tuandui']=$level_tuandui['name'];
        		}
        	}
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	//个人中心用户信息
	public function mine_mas(){
      	$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error("请先登录");
        }
      	$user = M('User')->field('id,username,mobile,level_user,level_tuandui,headimg,addtime,idstate,sta_rz')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error("请先登录");
        }
        if($user['sta_rz']==1){
        	$user['renzheng_url']='pages/mine/autonym';
        }else if($user['idstate']==1 || $user['idstate']==2){
        	$user['renzheng_url']='pages/mine/autonym2';
        }else{
        	$config=M('Config')->field('sta_sming')->where('id=1')->find();
        	if($config['sta_sming']==1){
        		$user['renzheng_url']='pages/mine/autonym';
        	}else{
        		$user['renzheng_url']='pages/mine/autonym2';
        	}
        }
        $user['headimg2']=$user['headimg'];
        if($user['headimg']!=''){
        	$user['headimg']='/Upload/headimg/'.$user['headimg'];
        }else{
        	$user['headimg']='/Public/images/photo.png';
        }
        $usercoin=M('UserCoin')->field('jyzhi')->where('userid='.$userid)->find();
        $user['jyzhi']=$usercoin['jyzhi'];
        $user['zgj']=0;
        $user['img_user']='';
        $user['level_user_name']='';
        if($user['level_user']>0){
        	$level_mas=M('LevelUser')->field('img,name,empirical_num')->where('id='.$user['level_user'])->find();
        	if($level_mas){
        		$user['img_user']='/Upload/logo/'.$level_mas['img'];
        		$user['yjz']=$user['jyzhi'];
        		$user['level_user_name']=$level_mas['name'];
        		$dyjyz=M('LevelUser')->field('empirical_num')->where('empirical_num>'.$level_mas['empirical_num'])->order('empirical_num asc')->find();
        		if($dyjyz){
        			$user['zgj']=1;
        			$user['yjz_xia']=$dyjyz['empirical_num']-$user['yjz'];
        			if($user['yjz_xia']<=0){
        				$user['yjz_xia']=0;
        				$user['yjz']=$level_mas['empirical_num'];
        			}
        			$user['shengyu']=(intval($user['yjz']/$dyjyz['empirical_num']*10000)/100).'%';
        		}else{
        			$user['zgj']=2;
        		}
        	}
        }
        $user['img_tuandui']='';
        $user['level_tuandui_name']='';
        if($user['level_tuandui']>0){
        	$level_mas=M('LevelTuandui')->field('img,name')->where('id='.$user['level_tuandui'])->find();
        	if($level_mas){
        		$user['img_tuandui']='/Upload/logo/'.$level_mas['img'];
        		$user['level_tuandui_name']=$level_mas['name'];
        	}
        }
        $user['addtime']=date("Y.m.d H:i",$user['addtime']);
        if($user['idstate']==2){
        	$user['renzheng']='已认证';
        }else if($user['idstate']==1){
        	$user['renzheng']='待审核';
        }else if($user['idstate']==8){
        	$user['renzheng']='未通过';
        }else{
        	$user['renzheng']='未认证';
        }
        echo json_encode(array('status'=>1,'msg'=>'获取成功','user'=>$user));exit;
	}
	//上传身份证图片
	public function headimg_img(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
		$upload = new \Think\Upload();
		$upload->maxSize = 15242880;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/headimg/';
		$upload->autoSub = false;
		$info = $upload->upload();
		$upload_dir = 'Upload/headimg/';
		if(!$info){
			$this->error($upload->getError());
		}else{
			$image = new \Think\Image();
			foreach ($info as $k => $v) {
				$path = $v['savepath'] . $v['savename'];
				
				$file_url = $upload_dir . $v['savename'];
	            $image->open($file_url);
	            $width = $image->width(); // 返回图片的宽度
	            $height = $image->height(); // 返回图片的高度
	            if($width > 80 || $height > 80){//原图宽度或高度大于1500时才缩放
	                // 按照原图的比例生成一个最大为1500*1500（根据宽高像素等比例缩小）的缩略图并保存，我这里直接覆盖掉了原图。
	                $image->thumb(80, 80)->save($file_url);
	            }
	            echo json_encode(array('status'=>1,'info'=>$path));exit;
				echo $path;
				exit();
			}
		}
	}
	
	//修改头像
	public function set_ajax(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
        if(cookie('set_ajax')=='set_ajax'){
        	$this->error('修改失败');
        }
      	$user = M('User')->field('id')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
		$input = I('post.');
		$headimg=$input['headimg'];
		if($headimg==''){
			$this->error('请上传头像');
		}
		if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$headimg)){
			$this->error('用户头像上传错误');
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $userid))->save(array('headimg'=>$headimg,'time_xg'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				cookie('set_ajax','set_ajax',4);
				$this->success('操作成功');
			}else {
				$mo->rollback();
				$this->error('修改失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('修改失败');
		}
	}
	
	//邀请好友
  	public function invite(){
      	$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
      	$user = M('User')->field('id,mobile,headimg,username,invit')->where("id=".$uid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
        $user['touxiang']='/Public/images/photo.png';
        if($user['headimg']){
        	$user['touxiang']='/Upload/headimg/'.$user['headimg'];
        }
		$userid=$uid;
      	$user['yaoqing']='http://'.$_SERVER['HTTP_HOST'].'/#pages/register?invit='.$user['invit'];
      	$user['script']='<script type="text/javascript">
       						 $(".codeaa").qrcode({
							 render: "canvas", //table方式
							 size: 160,
							 text: "http://'.$_SERVER['HTTP_HOST'].'/#pages/register?invit='.$user["invit"].'"
						});
					</script>';
		$user['yaoqing2']=substr($user['yaoqing'],0,25).'...';
      	echo json_encode(array('status'=>1,'info'=>'获取成功','user'=>$user));exit();
    }
	
	//团队信息
	public function team_mas(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		$user = M('User')->field('ww_td,ww_zt,ww_zs,ww_yx,r_nums,xq_nums')->where("id=".$uid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$user['ww_zt']=$user['ww_zt']*1;
		$user['ww_yx']=$user['ww_yx']*1;
		$user['ww_td']=($user['ww_td']*1);
		
		
		$user['jyji']=M('User')->field('id')->where('level_tuandui>1 and zt_ren='.$uid)->count();
		echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$user));exit();
	}
  	
  	//我的团队好友
	public function team_2(){
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
		$mas_sql='';
		$mas=$_GET['mas'];
		if($mas){
			if($mas=='a'){
			}else{
				if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$mas)){
					$mas_sql=' and id=0';
				}else{
					if(is_numeric($mas)){
						$yhsfcx=M('User')->field('id')->where("id=".$mas)->find();
						if(empty($yhsfcx)){
							$yhsfcx=M('User')->field('id')->where("username='".$mas."'")->find();
						}
					}else{
						$yhsfcx=M('User')->field('id')->where("username='".$mas."'")->find();
					}
					if($yhsfcx){
						$mas_sql=' and id='.$yhsfcx['id'];
					}else{
						$mas_sql=' and id=0';
					}
				}
			}
		}
      	$user = M('User')->field('xq_ids')->where("id=".$uid)->find();
      	$ids=$user['xq_ids'].'0';
      	$pages=($page-1)*10;
		$list = M('User')->field('id,username,idstate,mobile,addtime,headimg,xq_nums,level_tuandui')->where("id in (".$ids.")".$mas_sql)->order('xq_nums desc')->limit($pages,10)->select();
		foreach($list as $k => $v){
			//$list[$k]['username']=mb_substr($v['username'], 0, 6, 'utf-8');
			$list[$k]['mobile']=substr($v['mobile'],0,3).'****'.substr($v['mobile'],-4);
          	$list[$k]['addtime']=date("Y-m-d H:i:s",$v['addtime']);
          	if($v['idstate']==2){
          		$list[$k]['sta_mas']='<span>已实名</span>';
          	}else{
          		$list[$k]['sta_mas']='<span style="background:#ccc;">未实名</span>';
          	}
          	$list[$k]['user_name']='-';
          	if($v['level_tuandui']>0){
	          	$user_name=M('LevelTuandui')->field('name')->where('id='.$v['level_tuandui'])->find();
	          	if($user_name){
	          		$list[$k]['user_name']=$user_name['name'];
	          	}
          	}
          	$list[$k]['headimg2']='/Public/images/photo.png';
          	if($v['headimg']){
          		$list[$k]['headimg2']='/Upload/headimg/'.$v['headimg'];
          	}
		}
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
  	//我的直推好友
	public function team_1(){
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
		$mas_sql='';
		$mas=$_GET['mas'];
		if($mas){
			if($mas=='.'){
			}else{
				if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$mas)){
					$mas_sql=' and id=0';
				}else{
					if(is_numeric($mas)){
						$yhsfcx=M('User')->field('id')->where("id=".$mas)->find();
						if(empty($yhsfcx)){
							$yhsfcx=M('User')->field('id')->where("username='".$mas."'")->find();
						}
					}else{
						$yhsfcx=M('User')->field('id')->where("username='".$mas."'")->find();
					}
					if($yhsfcx){
						$mas_sql=' and id='.$yhsfcx['id'];
					}else{
						$mas_sql=' and id=0';
					}
				}
			}
		}
		$pages=($page-1)*10;
		$list = M('User')->field('id,username,idstate,mobile,headimg,r_nums,level_user')->where("zt_ren=".$uid.$mas_sql)->order('r_nums desc')->limit($pages,10)->select();
		foreach($list as $k => $v){
			//$list[$k]['username']=mb_substr($v['username'],0,2).'****';
			$list[$k]['mobile']=substr($v['mobile'],0,3).'****'.substr($v['mobile'],-4);
          	$list[$k]['addtime']=date("Y-m-d H:i:s",$v['addtime']);
          	if($v['idstate']==2){
          		$list[$k]['sta_mas']='<span>已实名</span>';
          	}else{
          		$list[$k]['sta_mas']='<span style="background:#ccc;">未实名</span>';
          	}
          	$list[$k]['user_name']='-';
          	$user_name=M('LevelUser')->field('name')->where('id='.$v['level_user'])->find();
          	if($user_name){
          		$list[$k]['user_name']=$user_name['name'];
          	}
          	$list[$k]['headimg2']='/Public/images/photo.png';
          	if($v['headimg']){
          		$list[$k]['headimg2']='/Upload/headimg/'.$v['headimg'];
          	}
		}
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
	//收款信息
	public function financial_mas(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
        $user = M('User')->field('id')->where('id='.$userid)->find();
        $user['sta_zfb']=0;
        $userzfb=M('UserZfb')->where('userid='.$userid)->find();
        if($userzfb['status']==1){
        	$user['sta_zfb']=1;
        	$user['zfb_tupian']='<span><img src="/Public/images/ridio1.png"/>开启</span>';
        }else{
        	$user['zfb_tupian']='<span><img src="/Public/images/ridio2.png">关闭</span>';
        }
        $user['zfb']=$userzfb;
        $user['sta_wx']=0;
        $userwx=M('UserWx')->where('userid='.$userid)->find();
        if($userwx['status']==1){
        	$user['sta_wx']=1;
        	$user['wx_tupian']='<span><img src="/Public/images/ridio1.png"/>开启</span>';
        }else{
        	$user['wx_tupian']='<span><img src="/Public/images/ridio2.png"/>关闭</span>';
        }
        $user['wx']=$userwx;
		echo json_encode(array('status'=>1,'user'=>$user));exit();
	}
	
	//支付宝信息
	public function alipay_mas(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
      	$user = M('User')->field('id')->where("id=".$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
        $user_zfb=M('UserZfb')->where('userid='.$userid)->find();
		$input = I('post.');
		if($input){
			$name=$input['name'];
			$card=$input['card'];
			$img=$input['img'];
	        if($name==''){
	        	$this->error('请输入姓名');
	        }
	        if(strlen($name)>60){
	        	$this->error('姓名长度太长');
	        }
	        if($card==''){
	        	$this->error('请输入支付宝账号');
	        }
	        if($img==''){
	        	$this->error('请上传收款二维码');
	        }
			if(preg_match("/[\',:;*?~!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$name)){
				$this->error('姓名不能输入特殊字符');
			}
			if(preg_match("/[\',:;*?~!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$card)){
				$this->error('支付宝账号不能输入特殊字符');
			}
			if(preg_match("/[\',:;*?~!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$img)){
				$this->error('请上传正确的收款二维码');
			}
			try{
				$mo = M();
				$mo->startTrans();
				if($user_zfb){
					$rs[] = $mo->table('tw_user_zfb')->where(array('userid' => $userid))->save(array('name'=>$name,'card'=>$card,'img'=>$img,'endtime'=>time()));
				}else{
					$rs[] = $mo->table('tw_user_zfb')->add(array('userid' => $userid,'name'=>$name,'card'=>$card,'status'=>1,'img'=>$img,'addtime'=>time()));
				}
				if(check_arr($rs)) {
					$mo->commit();
					$this->success('操作成功');
				}else {
					$mo->rollback();
					$this->error('操作失败');
				}
			}catch(\Think\Exception $e){
				$mo->rollback();
				$this->error('操作失败');
			}
		}
      	echo json_encode(array('status'=>1,'info'=>'获取成功','user'=>$user_zfb));exit();
	}
	
	//上传支付宝图片
	public function zfb_img(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
		$upload = new \Think\Upload();
		$upload->maxSize = 15728640;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/zfb/';
		$upload->autoSub = false;
		$info = $upload->upload();
		if(!$info){
			$this->error($upload->getError());
		}else{
			$upload_dir='Upload/zfb/';
			$image = new \Think\Image();
			foreach ($info as $k => $v) {
				$path = $v['savepath'] . $v['savename'];
				
				$file_url = $upload_dir . $v['savename'];
	            $image->open($file_url);
	            $width = $image->width(); // 返回图片的宽度
	            $height = $image->height(); // 返回图片的高度
	            if($width > 500 || $height > 500){//原图宽度或高度大于1500时才缩放
	                // 按照原图的比例生成一个最大为1500*1500（根据宽高像素等比例缩小）的缩略图并保存，我这里直接覆盖掉了原图。
	                $image->thumb(500, 500)->save($file_url);
	            }
	            echo json_encode(array('status'=>1,'info'=>$path));exit;
			}
		}
	}
	
	//微信信息
	public function wechat_mas(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
      	$user = M('User')->field('id')->where("id=".$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
        $user_wx=M('UserWx')->where('userid='.$userid)->find();
		$input = I('post.');
		if($input){
			$name=$input['name'];
			$card=$input['card'];
			$img=$input['img'];
	        if($name==''){
	        	$this->error('请输入姓名');
	        }
	        if(strlen($name)>60){
	        	$this->error('姓名长度太长');
	        }
	        if($card==''){
	        	$this->error('请输入微信号');
	        }
	        if($img==''){
	        	$this->error('请上传收款二维码');
	        }
			if(preg_match("/[\',:;*?~!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$name)){
				$this->error('姓名不能输入特殊字符');
			}
			if(preg_match("/[\',:;*?~!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$card)){
				$this->error('微信号不能输入特殊字符');
			}
			if(preg_match("/[\',:;*?~!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$img)){
				$this->error('请上传正确的收款二维码');
			}
			try{
				$mo = M();
				$mo->startTrans();
				if($user_wx){
					$rs[] = $mo->table('tw_user_wx')->where(array('userid' => $userid))->save(array('name'=>$name,'card'=>$card,'img'=>$img,'endtime'=>time()));
				}else{
					$rs[] = $mo->table('tw_user_wx')->add(array('userid' => $userid,'name'=>$name,'card'=>$card,'status'=>1,'img'=>$img,'addtime'=>time()));
				}
				if(check_arr($rs)) {
					$mo->commit();
					$this->success('操作成功');
				}else {
					$mo->rollback();
					$this->error('操作失败');
				}
			}catch(\Think\Exception $e){
				$mo->rollback();
				$this->error('操作失败');
			}
		}
      	echo json_encode(array('status'=>1,'info'=>'获取成功','user'=>$user_wx));exit();
	}
	
	//上传微信图片
	public function wx_img(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
		$upload = new \Think\Upload();
		$upload->maxSize = 15728640;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/wx/';
		$upload->autoSub = false;
		$info = $upload->upload();
		if(!$info){
			$this->error($upload->getError());
		}else{
			$upload_dir='Upload/wx/';
			$image = new \Think\Image();
			foreach ($info as $k => $v) {
				$path = $v['savepath'] . $v['savename'];
				
				$file_url = $upload_dir . $v['savename'];
	            $image->open($file_url);
	            $width = $image->width(); // 返回图片的宽度
	            $height = $image->height(); // 返回图片的高度
	            if($width > 500 || $height > 500){//原图宽度或高度大于1500时才缩放
	                // 按照原图的比例生成一个最大为1500*1500（根据宽高像素等比例缩小）的缩略图并保存，我这里直接覆盖掉了原图。
	                $image->thumb(500, 500)->save($file_url);
	            }
	            echo json_encode(array('status'=>1,'info'=>$path));exit;
			}
		}
	}
	
	//修改微信/支付宝状态
	public function financial_sta(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$userbank=M('User')->field('id')->where("id=".$userid)->find();
		if(empty($userbank)){
			$this->error('参数错误');
		}
		$type=$_POST['type'];
		if($type==1){
			$zfb_ok=M('UserZfb')->field('status')->where("userid=".$userid)->find();
			if($zfb_ok['status']==1){
				$sc=M('UserZfb')->where("userid=".$userid)->save(array('status'=>0,'endtime'=>time()));
				if($sc){
					$this->success('关闭成功');
				}
				$this->error('关闭失败');
			}else{
				$sc=M('UserZfb')->where("userid=".$userid)->save(array('status'=>1,'endtime'=>time()));
				if($sc){
					$this->success('开启成功');
				}
				$this->error('开启失败');
			}
		}else{
			$wx_ok=M('UserWx')->field('status')->where("userid=".$userid)->find();
			if($wx_ok['status']==1){
				$sc=M('UserWx')->where("userid=".$userid)->save(array('status'=>0,'endtime'=>time()));
				if($sc){
					$this->success('关闭成功');
				}
				$this->error('关闭失败');
			}else{
				$sc=M('UserWx')->where("userid=".$userid)->save(array('status'=>1,'endtime'=>time()));
				if($sc){
					$this->success('开启成功');
				}
				$this->error('开启失败');
			}
		}
	}
	
	//修改微信/支付宝删除
	public function financial_sta_del(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$userbank=M('User')->field('id')->where("id=".$userid)->find();
		if(empty($userbank)){
			$this->error('参数错误');
		}
		$type=$_POST['type'];
		if($type==1){
			$zfb_ok=M('UserZfb')->where("userid=".$userid)->delete();
			if($zfb_ok){
				$this->success('删除成功');
			}
			$this->error('删除失败');
		}else{
			$wx_ok=M('UserWx')->where("userid=".$userid)->delete();
			if($wx_ok){
				$this->success('删除成功');
			}
			$this->error('删除失败');
		}
	}
	
	//添加银行卡
	public function bank_add_ajax(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
      	$user = M('User')->field('id')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
		$input = I('post.');
		$name=$input['name'];
		$card=$input['card'];
		$bank=$input['bank'];
		$address=$input['address'];
        if($name==''){
        	$this->error('请输入银行卡姓名');
        }
        if($card==''){
        	$this->error('请输入银行卡号');
        }
        if(strlen($name)>60){
        	$this->error('姓名长度太长');
        }
		if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$name)){
			$this->error('姓名不能输入特殊字符');
		}
		if(!is_numeric($card)){
			$this->error('银行卡号应为数字');
		}
		if(strlen($card)<15 || strlen($card)>20){
			$this->error('银行卡号位数错误');
		}
		if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$bank)){
			$this->error('开户银行不能输入特殊字符');
		}
		if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$address)){
			$this->error('开户行地址不能输入特殊字符');
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user_bank')->add(array('userid'=>$userid,'name'=>$name,'bankcard'=>$card,'bank'=>$bank,'bankaddr'=>$address,'addtime'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				$this->success('操作成功');
			}else {
				$mo->rollback();
				$this->error('操作失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('操作失败');
		}
	}
	
	//用户银行卡列表
	public function user_bank(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
			echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
		}
		$pages=($page-1)*10;
		$list = M('UserBank')->field('id,status,bank,bankcard,name')->where('userid='.$userid)->order('status desc,id desc')->limit($pages,10)->select();
		foreach($list as $k => $v){
          	if($v['status']==1){
          		$list[$k]['img']='<span><img src="/Public/images/ridio1.png"/>开启</span>';
          	}else{
          		$list[$k]['img']='<span><img src="/Public/images/ridio2.png"/>关闭</span>';
          	}
          	$list[$k]['bankcard'] = substr($v['bankcard'],0,4).'*********'.substr($v['bankcard'],strlen($v['bankcard'])-4,strlen($v['bankcard']));
		}
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
	//修改银行卡状态
	public function addcard_no(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$id=$_POST['id'];
		if(!is_numeric($id)){
			$this->error('参数错误');
		}
		$userbank=M('UserBank')->field('id')->where("userid=".$userid.' and id='.$id)->find();
		if(empty($userbank)){
			$this->error('参数错误');
		}
		$status=$_POST['status'];
		if($status==1){
			$sc=M('UserBank')->where("userid=".$userid.' and id='.$id)->save(array('status'=>0,'time_add'=>time()));
			if($sc){
				$this->success('关闭成功');
			}
			$this->error('关闭失败');
		}else{
			$sc=M('UserBank')->where("userid=".$userid.' and id='.$id)->save(array('status'=>1,'time_add'=>time()));
			if($sc){
				M('UserBank')->where("userid=".$userid.' and id!='.$id)->save(array('status'=>0,'time_add'=>time()));
				$this->success('开启成功');
			}
			$this->error('开启失败');
		}
	}
	
	//用户银行卡删除
	public function addcard_del(){
    	$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$id=$_POST['id'];
		if(!is_numeric($id)){
			$this->error('参数错误');
		}
		$userbank=M('UserBank')->field('id')->where("userid=".$uid.' and id='.$id)->find();
		if(empty($userbank)){
			$this->error('参数错误');
		}
		$sc=M('UserBank')->where("userid=".$uid.' and id='.$id)->delete();
		if($sc){
			$this->success('删除成功');
		}
		$this->error('删除失败');
	}
	
	//获取银行卡信息
	public function user_bank_mas(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
      	$user = M('User')->field('id')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
		$input = I('get.');
		$id=$input['id'];
		if(!is_numeric($id)){
			$this->error('参数错误');
		}
		$userbank = M('UserBank')->where('userid='.$userid.' and id='.$id)->find();
		if(empty($userbank)){
			$this->error('参数错误');
		}
		echo json_encode(array('status'=>1,'user'=>$userbank));exit();
	}
	
	//编辑银行卡
	public function editcard_ajax(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
      	$user = M('User')->field('id')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
		$input = I('post.');
		$id=$input['id'];
		if(!is_numeric($id)){
			$this->error('参数错误');
		}
		$userbank = M('UserBank')->field('id')->where('userid='.$userid.' and id='.$id)->find();
		if(empty($userbank)){
			$this->error('参数错误');
		}
		$name=$input['name'];
		$card=$input['card'];
		$bank=$input['bank'];
		$address=$input['address'];
        if($name==''){
        	$this->error('请输入银行卡姓名');
        }
        if($card==''){
        	$this->error('请输入银行卡号');
        }
        if(strlen($name)>60){
        	$this->error('姓名长度太长');
        }
        if($bank==''){
        	$this->error('请输入开户银行');
        }
        if($address==''){
        	$this->error('请输入开户行地址');
        }
		if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$name)){
			$this->error('姓名不能输入特殊字符');
		}
		if(!is_numeric($card)){
			$this->error('银行卡号应为数字');
		}
		if(strlen($card)<15 || strlen($card)>18){
			$this->error('银行卡号位数错误');
		}
		if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$bank)){
			$this->error('开户银行不能输入特殊字符');
		}
		if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$address)){
			$this->error('开户行地址不能输入特殊字符');
		}
		try{
			$mo = M();
			$mo->startTrans();
			
			$rs[] = $mo->table('tw_user_bank')->where('id='.$id)->save(array('userid'=>$userid,'name'=>$name,'bankcard'=>$card,'bank'=>$bank,'bankaddr'=>$address,'addtime'=>time()));
			
			if(check_arr($rs)) {
				$mo->commit();
				$this->success('操作成功');
			}else {
				$mo->rollback();
				$this->error('操作失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('操作失败');
		}
	}
	
	//修改信息
	public function message_mes(){
      	$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error("请先登录");
        }
      	$user = M('User')->field('id,username,headimg,level_kuang,addtime')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error("请先登录");
        }
        if($user['level_kuang']>0){
        	$level_mas=M('LevelTuandui')->field('name')->where('id='.$user['level_kuang'])->find();
        	if($level_mas){
        		$user['dengji_mas']=$level_mas['name'];
        	}
        }else{
        	$user['dengji_mas']='无';
        }
        
        if($user['headimg']!=''){
        	$user['headimg2']='/Upload/headimg/'.$user['headimg'];
        }else{
        	$user['headimg2']='/Public/images/mine_t.png';
        }
        $user['mes_addtime']=date('Y-m-d H:i:s',$user['addtime']);
        echo json_encode(array('status'=>1,'msg'=>'获取成功','user'=>$user));exit;
	}
	
	//修改手机号--手机号
	public function sendcheck_mobile_mobile(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$yzm=$_POST['mobile_yzm'];
		if($yzm==''){
			$this->error('请输入图片验证码');
		}
		if (!check_verify(strtoupper($yzm),"1")) {
			$this->error('图形验证码错误');
		}
		$user=M('User')->field('mobile')->where("id=".$uid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		if($user['mobile']==''){
			$this->error('请先绑定手机号');
		}
		$mobile=$user['mobile'];
		$config=M('Config')->where(array('id' => 1))->find();
      	$chaoshi_time=session('mobile_mobile_time');
		if((time()-$chaoshi_time)<120 && $chaoshi_time>0){
			$this->error('操作频繁,2分钟后试');
		}
		$code = rand(111111, 999999);
		$content = "您正在修改手机号，您的验证码是:". $code;
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
            session('mobile_mobile_code', md5($code.'mima'));
            session('mobile_mobile_mobile',$mobile);
            session('mobile_mobile_time',time());
			$this->success('验证码已发送');
		}
	}
	
	//修改资产密码
	public function mobile(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('username,mobile,invit,id')->where(array('id' => $uid))->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		if(cookie('mobile')=='mobile'){
			$this->error('请勿频繁操作');
		}
		$yzm=$_POST['mobile_yzm'];
		$code=$_POST['mobile_code'];
		$mobile=$_POST['new_mobile'];
		if($yzm==''){
			$this->error('请输入图片验证码');
		}
		if($code==''){
			$this->error('请输入短信验证码');
		}
		if($mobile==''){
			$this->error('请输入新手机号');
		}
		if (!check_verify(strtoupper($yzm),"1")) {
			$this->error('图形验证码错误');
		}
		$mobileyzm=$mobile;
		if ($user['mobile'] != session('mobile_mobile_mobile')) {
			$this->error('短信验证码不匹配');
		}
		if (md5($code.'mima') != session('mobile_mobile_code')) {
			$this->error('短信验证码不匹配');
		}
		$chaoshi_time=session('mobile_mobile_time');
		if((time()-$chaoshi_time)>1120){
			$this->error('短信验证码已过期');
		}
		if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$mobile)){
			$this->error('新手机号不规范');
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $user['id']))->save(array('mobile'=>$mobile,'time_xg'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				session('mobile_paypassword_code', '');
				cookie('mobile','mobile',4);
				$this->success('修改成功');
			}else {
				$mo->rollback();
				$this->error('修改失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('修改失败');
		}
	}
	
  	//修改密码--手机号
	public function sendcheck_mobile_password(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$yzm=$_POST['password_yzm'];
		if($yzm==''){
			$this->error('请输入图片验证码');
		}
		if (!check_verify(strtoupper($yzm),"1")) {
			$this->error('图形验证码错误');
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
		if (checkstr($mobile) || checkstr($verify)) {
			$this->error(L('您输入的信息有误！'));
		}
      	$chaoshi_time=session('mobile_password_time');
		if((time()-$chaoshi_time)<120 && $chaoshi_time>0){
			$this->error('操作频繁,2分钟后试');
		}
        /*$code = rand(100000, 999999);
		$mobile = $mobile;
        $smsapi = "http://utf8.api.smschinese.cn/"; //短信网关
		$user = '13063663676'; //短信平台帐号
		$content = '您好，您的验证码是' . $code; //要发送的短信内容
      	$statusStr = array("0" => "短信发送成功","-1" => "没有该用户账户","-2" => "接口密钥不正确","-21" => "MD5接口密钥加密不正确误","-3" => "短信数量不足","-11" => "该用户被禁用","-14" => "短信内容出现非法字符","-4" => "手机号格式不正确","-41" => "手机号码为空","-42"=>'短信内容为空',"-51" => "短信签名格式不正确","-52"=>'短信签名太长');
		$sendurl = $smsapi . "?Uid=" . $user . "&Key=d41d8cd98f00b204e980&smsMob=" . $mobile . "&smsText=" . $content;*/
		$code = rand(111111, 999999);
		$content = "您正在修改登录密码，您的验证码是:". $code;
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
            session('mobile_password_code', md5($code.'mima'));
            session('mobile_password_mobile',$mobile);
            session('mobile_password_time',time());
			$this->success('验证码已发送');
		}
	}
	
	//修改登录密码
	public function password(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('username,mobile,invit,id')->where(array('id' => $uid))->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$yzm=$_POST['yzm'];
		$code=$_POST['code'];
		$password=$_POST['password'];
		$password2=$_POST['password2'];
		if($yzm==''){
			$this->error('请输入图片验证码');
		}
		if($code==''){
			$this->error('请输入短信验证码');
		}
		if($password==''){
			$this->error('请输入新登录密码');
		}
		if($password2==''){
			$this->error('请输入确认密码');
		}
		if (!check_verify(strtoupper($yzm),"1")) {
			$this->error('图形验证码错误');
		}
		$mobile=$user['mobile'];
		if ($mobile != session('mobile_password_mobile')) {
			$this->error('短信验证码不匹配');
		}
		if (md5($code.'mima') != session('mobile_password_code')) {
			$this->error('短信验证码不匹配');
		}
		$chaoshi_time=session('mobile_password_time');
		if((time()-$chaoshi_time)>120){
			$this->error('短信验证码已过期');
		}
		if(strlen($password)<8 || strlen($password)>18){
			$this->error('登录密码8-18位');
		}
		if($password!=$password2){
			$this->error('两次密码不一致');
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $user['id']))->save(array('password'=>md5($password.$user['invit']),'time_xg'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				cookie('userid','no',86400);
				session('mobile_password_code', '');
				$this->success('修改成功');
			}else {
				$mo->rollback();
				$this->error('修改失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('修改失败');
		}
	}
	
  	//修改安全密码--手机号
	public function sendcheck_mobile_paypassword(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$yzm=$_POST['password_yzm'];
		if($yzm==''){
			$this->error('请输入图片验证码');
		}
		if (!check_verify(strtoupper($yzm),"1")) {
			$this->error('图形验证码错误');
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
		if (checkstr($mobile) || checkstr($verify)) {
			$this->error(L('您输入的信息有误！'));
		}
      	$chaoshi_time=session('mobile_paypassword_time');
		if((time()-$chaoshi_time)<120 && $chaoshi_time>0){
			$this->error('操作频繁,2分钟后试');
		}
        /*$code = rand(100000, 999999);
		$mobile = $mobile;
        $smsapi = "http://utf8.api.smschinese.cn/"; //短信网关
		$user = '13063663676'; //短信平台帐号
		$content = '您好，您的验证码是' . $code; //要发送的短信内容
      	$statusStr = array("0" => "短信发送成功","-1" => "没有该用户账户","-2" => "接口密钥不正确","-21" => "MD5接口密钥加密不正确误","-3" => "短信数量不足","-11" => "该用户被禁用","-14" => "短信内容出现非法字符","-4" => "手机号格式不正确","-41" => "手机号码为空","-42"=>'短信内容为空',"-51" => "短信签名格式不正确","-52"=>'短信签名太长');
		$sendurl = $smsapi . "?Uid=" . $user . "&Key=d41d8cd98f00b204e980&smsMob=" . $mobile . "&smsText=" . $content;*/
		$code = rand(111111, 999999);
		$content = "您正在修改密码，您的验证码是:". $code;
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
            session('mobile_paypassword_code', md5($code.'mima'));
            session('mobile_paypassword_mobile',$mobile);
            session('mobile_paypassword_time',time());
			$this->success('验证码已发送');
		}
	}
	
	//修改资产密码
	public function paypassword(){
		$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('username,mobile,invit,id')->where(array('id' => $uid))->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		if(cookie('paypassword')=='paypassword'){
			$this->error('请勿频繁操作');
		}
		$yzm=$_POST['yzm'];
		$code=$_POST['code'];
		$password=$_POST['password'];
		$password2=$_POST['password2'];
		if($yzm==''){
			$this->error('请输入图片验证码');
		}
		if($code==''){
			$this->error('请输入短信验证码');
		}
		if($password==''){
			$this->error('请输入新资产密码');
		}
		if($password2==''){
			$this->error('请输入确认资产密码');
		}
		if (!check_verify(strtoupper($yzm),"1")) {
			$this->error('图形验证码错误');
		}
		$mobile=$user['mobile'];
		if ($mobile != session('mobile_paypassword_mobile')) {
			$this->error('短信验证码不匹配');
		}
		if (md5($code.'mima') != session('mobile_paypassword_code')) {
			$this->error('短信验证码不匹配');
		}
		$chaoshi_time=session('mobile_paypassword_time');
		if((time()-$chaoshi_time)>120){
			$this->error('短信验证码已过期');
		}
		if(strlen($password)<8 || strlen($password)>18){
			$this->error('资产密码8-18位');
		}
		if($password!=$password2){
			$this->error('两次密码不一致');
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $user['id']))->save(array('paypassword'=>md5($password.$user['invit']),'time_xg'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				session('mobile_paypassword_code', '');
				cookie('paypassword','paypassword',4);
				$this->success('修改成功');
			}else {
				$mo->rollback();
				$this->error('修改失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('修改失败');
		}
	}
	
	//用户信息
	public function user_mas(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
      	$user = M('User')->field('truename,mobile,idcard,idcardinfo,card,true_mobile,idstate')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
        $user['idcard2']=$user['idcard'];
        $user['card2']=$user['card'];
        $shiming=0;
        if($user['idstate']>0){
	        if($user['truename']){
	        	$shiming=1;
	        	$user['idcard']=substr($user['idcard'],0,2).'********'.substr($user['idcard'],-2);
	        	$user['card']=substr($user['card'],0,2).'********'.substr($user['card'],-2);
	        }
        }
        $config=M('Config')->field('sifang_mobile')->where('id=1')->find();
        $user['sifang_mobile']=$config['sifang_mobile'];
        echo json_encode(array('status'=>1,'type'=>1,'shiming'=>$shiming,'user'=>$user,'mobile'=>$user['true_mobile']));exit;
	}
	
	//实名认证
	public function message_ajax(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
        if(cookie('message_ajax')=='message_ajax'){
        	$this->error('修改失败');
        }
      	$user = M('User')->field('mobile')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
		$input = I('post.');
		$username=$input['username'];
		$headimg=$input['headimg'];
		if($username==''){
			$this->error('请输入昵称');
		}
		if($headimg==''){
			$this->error('请上传头像');
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$username)){
			$this->error('用户名格式错误');
		}
		if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$headimg)){
			$this->error('用户头像上传错误');
		}
		$idcard_ok = M('User')->field('id')->where("username='".$username."' and id!=".$userid)->find();
		if($idcard_ok){
			$this->error('该用户名已存在');
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $userid))->save(array('username'=>$username,'headimg'=>$headimg,'time_xg'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				cookie('message_ajax','message_ajax',4);
				$this->success('操作成功');
			}else {
				$mo->rollback();
				$this->error('修改失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('修改失败');
		}
	}
	
	//实名验证
	public function shiming_yanzheng($sifang_appcode=null,$card_number=null,$id_number=null,$name=null,$phone_number=null){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
      	$user = M('User')->field('id')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
		if(!is_numeric($card_number)){
			$this->error('银行卡号错误1');
		}
		if($id_number==''){
			$this->error('身份证号错误2');
		}
		if($name==''){
			$this->error('姓名错误3');
		}
		if(!is_numeric($phone_number)){
			$this->error('手机号错误4');
		}
		$host = "https://4bankcard.market.alicloudapi.com";
		$path = "/getapilist/";
		$method = "POST";
		$appcode = $sifang_appcode;
		$headers = array();
		array_push($headers, "Authorization:APPCODE " . $appcode);
		$querys = "card_number=".$card_number."&id_number=".$id_number."&name=".$name."&phone_number=".$phone_number;
		$bodys = "";
		$url = $host . $path . "?" . $querys;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		if (1 == strpos("$".$host, "https://")){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}
		$result=curl_exec($curl);
		$result_ok=json_decode($result,true);
		if($result_ok['status']!='OK'){
			M('User')->where('id='.$userid)->setInc('sifang_num',1);
			$this->error($result_ok['reason']);
		}
	}
	
	//实名认证
	public function auth_ajax(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
        //$this->error('系统维护请稍后再试');
      	$user = M('User')->field('mobile,zt_ren,sifang_num,idstate,sta_pipei')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error('请先登录');
        }
        if(cookie('auth_ajax')=='auth_ajax'){
        	$this->error('请勿频繁操作');
        }
        if($user['idstate']==2){
        	$this->error('请勿重复操作');
        }
        $config=M('Config')->field('sifang_mobile')->where('id=1')->find();
		$input = I('post.');
		$truename=$input['truename'];
		$idcard=$input['idcard'];
		$card=$input['card'];
		if($config['sifang_mobile']==1){
			$mobile=$user['mobile'];
		}else{
			$mobile=$input['true_mobile'];
		}
		if($truename==''){
			$this->error('请输入真实姓名');
		}
		if($mobile==''){
			$this->error('请输入手机号');
		}
		if($idcard==''){
			$this->error('请输入身份证号');
		}
		if($card==''){
			$this->error('请输入银行卡号');
		}
		$search = array(" ","　");
		$replace = array("","","");
		$truename=str_replace($search, $replace, $truename);
		$idcard=str_replace($search, $replace, $idcard);
		$mobile=str_replace($search, $replace, $mobile);
		$card=str_replace($search, $replace, $card);
		if(preg_match("/[\',:;*?~!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$truename)){
			$this->error('真实姓名格式错误');
		}
		if(!is_numeric($mobile)){
			$this->error('手机号格式错误');
		}
		if(strlen($mobile)!=11){
			$this->error('手机号格式错误');
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$idcard)){
			$this->error('身份证号格式错误');
		}
		if(!is_numeric($card)){
			$this->error('银行卡号格式错误');
		}
		$condd=M('Config')->field('idcard_num,sifang_appcode,sifang_num')->where('id=1')->find();
		$idcard_ok = M('User')->field('id')->where("idcard='".$idcard."' and id!=".$userid)->count();
		if(($idcard_ok+1)>$condd['idcard_num']){
			$this->error('身份证号账号数量已达到'.$config['idcard_num'].'个');
		}
		if($condd['sifang_appcode']==''){
			$this->error('认证AppCode不存在');
		}
		if($condd['sifang_num']<=$user['sifang_num']){
			$this->error('认证次数已用完');
		}
		//$this->shiming_yanzheng($condd['sifang_appcode'],$card,$idcard,$truename,$mobile);
		$sifang_appcode=$condd['sifang_appcode'];
		$host = "https://4bankcard.market.alicloudapi.com";
		$path = "/getapilist/";
		$method = "POST";
		$appcode = $sifang_appcode;
		$headers = array();
		array_push($headers, "Authorization:APPCODE " . $appcode);
		$querys = "card_number=".$card."&id_number=".$idcard."&name=".$truename."&phone_number=".$mobile;
		$bodys = "";
		$url = $host . $path . "?" . $querys;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		if (1 == strpos("$".$host, "https://")){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}
		$result=curl_exec($curl);
		$result_ok=json_decode($result,true);
		//echo $querys = "card_number=".$card."&id_number=".$idcard."&name=".$truename."&phone_number=".$mobile;
		//print_r($result_ok);
		
		if($result_ok['status']=='OK'){
			if($result_ok['result']!=1){
				if($user['sta_pipei']==0){
					$this->error('信息不匹配');
				}
			}
		}else{
			M('User')->where('id='.$userid)->setInc('sifang_num',1);
			$this->error($result_ok['reason']);
		}
      	$config=M('Config')->field('sta_zsong,sm_zsong,jyz_zt')->where('id=1')->find();
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user')->where(array('id' => $userid))->save(array('truename'=>$truename,'idstate'=>2,'sta_rz'=>1,'idcard'=>$idcard,'card'=>$card,'true_mobile'=>$mobile,'time_xg'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				cookie('auth_ajax','auth_ajax',10);
				if($config['sm_zsong']>0){
					M('User')->where('id='.$userid)->setInc('kj_ok',1);
	      			$kj_arr=M('Mining')->where('id='.$config['sm_zsong'])->find();
	      			$time_dq=time()+$kj_arr['gq_day']*86400;
	      			$time_dq_h=time()+$kj_arr['h_num']*3600;
	      			$dq_dqtime=time()+$kj_arr['yx_day']*86400;
	      			$zs_kli=$kj_arr['zs_kli'];
	      			$jyz_gm=$kj_arr['zs_jyzhi'];
	      			$id=$userid;
	      			$ze_kjid=M('MiningList')->add(array('userid'=>$id,'status'=>2,'sta_zs'=>3,'time_add'=>time(),'time_zx'=>time(),'time_zx_h'=>$time_dq_h,'num'=>1,'zt_ren'=>$user['zt_ren'],'num_usdt'=>$kj_arr['price'],'num_usdt_zong'=>$kj_arr['price'],'time_dq'=>$time_dq,'kji_id'=>$config['sm_zsong'],'kj_day'=>$kj_arr['gq_day'],'kj_h'=>$kj_arr['h_num'],'zs_kli'=>$kj_arr['zs_kli'],'coin'=>$kj_arr['coin']));
	      			if($jyz_gm>0){
	      				//购买矿机赠送经验值
	      				$zt_usercoin=M('UserCoin')->field('jyzhi')->where(array('userid' => $id))->find();
	      				M('UserCoin')->where(array('userid' => $id))->setInc('jyzhi',$jyz_gm);
	      				M('Finance')->add(array('userid'=>$id,'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$jyz_gm,'num'=>$zt_usercoin['jyzhi'],'mum'=>($zt_usercoin['jyzhi']+$jyz_gm),'type'=>1,'caozid'=>$id,'name'=>'buy_ajax','protypemas'=>'实名认证','remark'=>'实名认证赠送矿机','kji_id'=>$config['sm_zsong'],'czfee'=>1,'addtime'=>time(),'protype'=>14,'status'=>1));
	      				$this->shiming_shenhe($id);
	      			}
	      			if($user['zt_ren']>0){
	      				$fanyong_num=$kj_arr['uti_bili']*$kj_arr['price'];
	      				if($fanyong_num>0){
	      					$fanyong_day=bcmul($fanyong_num/$kj_arr['yx_day'],1000000)/1000000;
	      					M('MiningFanyong')->add(array('userid'=>$user['zt_ren'],'caozid'=>$id,'kjis_id'=>$ze_kjid,'status'=>2,'kji_id'=>$config['sm_zsong'],'num_zong'=>$fanyong_num,'num_day'=>$fanyong_day,'time_add'=>time(),'time_zx'=>time(),'time_dq'=>$dq_dqtime,'num_zx_zong'=>$kj_arr['yx_day']));
	      				}
						if($zs_kli>0){
							$redis = new \Redis();
							$redis->connect('127.0.0.1', 6379);
							M('User')->where(array('id' => $id))->setInc('ww_zs',$zs_kli);
							$this->shiming_tuandui($user['zt_ren'],$userid,$zs_kli,$config['sta_zsong'],$redis);
						}
	      			}
	      		}else{
					if($user['zt_ren']>0){
						$this->shiming_shenhe($user['zt_ren']);
						$redis = new \Redis();
						$redis->connect('127.0.0.1', 6379);
						$this->shiming_tuandui($user['zt_ren'],$userid,0,$config['sta_zsong'],$redis);
		      		}
	      		}
	      		if($config['jyz_zt']>0){
	      			if($user['zt_ren']>0){
	      				//购买矿机赠送经验值
	      				$zt_usercoin2=M('UserCoin')->field('jyzhi')->where(array('userid' => $user['zt_ren']))->find();
	      				M('UserCoin')->where(array('userid' => $user['zt_ren']))->setInc('jyzhi',$config['jyz_zt']);
	      				M('Finance')->add(array('userid'=>$user['zt_ren'],'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$config['jyz_zt'],'num'=>$zt_usercoin2['jyzhi'],'mum'=>($zt_usercoin2['jyzhi']+$config['jyz_zt']),'type'=>1,'caozid'=>$id,'name'=>'buy_ajax','protypemas'=>'实名认证','remark'=>'实名认证赠送矿机','kji_id'=>$config['sm_zsong'],'czfee'=>1,'addtime'=>time(),'protype'=>14,'status'=>1));
	      				$this->shiming_shenhe($user['zt_ren']);
	      			}
	      		}
				$this->success('操作成功');
			}else {
				$mo->rollback();
				$this->error('认证失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('认证失败');
		}
	}
	
	public function shiming_shenhe($userid){
		//查询会员经验值
		//查询会员实名直推人数
		if(is_numeric($userid)){
			$usercoin=M('UserCoin')->field('jyzhi')->where('userid='.$userid)->find();
			$ztren_num=M('User')->field('id')->where('idstate=2 and zt_ren='.$userid)->count();
			if($ztren_num<=0){
				$ztren_num=0;
			}
			$level_user=M('LevelUser')->field('id')->where('empirical_num<='.$usercoin['jyzhi'].' and zt_ren<='.$ztren_num)->order('id desc')->find();
			if($level_user){
				M('User')->where('id='.$userid)->save(array('level_user'=>$level_user['id'],'time_xg'=>time()));
			}
		}
	}
	
    //增加威望值 更新团队等级
    public function shiming_tuandui($userid,$caozid,$zengsong,$sta_zsong,$redis){
    	if(is_numeric($userid)){
			$user=M('User')->field('ww_td,ww_zs,ww_zt,ww_yx,zt_ren,level_tuandui')->where('id='.$userid)->find();
			if($user){
				if($zengsong>0){
					M('Finance')->add(array('userid'=>$userid,'coin'=>'ww_td','caozid'=>$caozid,'coinname'=>'矿力','fee'=>$zengsong,'num'=>$user['ww_td'],'mum'=>($user['ww_td']+$zengsong),'type'=>1,'name'=>'buy_ajax','remark'=>'实名认证','protypemas'=>'实名认证','addtime'=>time(),'protype'=>14,'status'=>1));
					//更新一下大神矿力
					$idzt_s='0';
					$ww_yx=0;
					$zongww2=M()->query('SELECT id,ww_td,ww_zs FROM tw_user where zt_ren='.$userid.' ORDER BY ww_td+ww_zs desc limit 2');
					foreach($zongww2 as $n2=>$var2){
						$ww_yx+=$var2['ww_td']+$var2['ww_zs'];
						$idzt_s.=','.$var2['id'];
					}
					$ww_td=$user['ww_td']+$zengsong;
					if($ww_td<=0){
						$ww_td=0;
					}
					$ww_zt=$ww_td-$ww_yx;
					if($ww_zt<=0){
						$ww_zt=0;
					}
					M('User')->where('id='.$userid)->save(array('ww_td'=>$ww_td,'ww_yx'=>$ww_yx,'ww_zt'=>$ww_zt));
					$user=M('User')->field('ww_td,ww_zs,ww_zt,ww_yx,zt_ren,level_tuandui')->where('id='.$userid)->find();
				}
				$ztren_num=M('User')->field('id')->where('zt_ren='.$userid.' and kj_ok>0')->count();
				if($ztren_num<1){
					$ztren_num=0;
				}
				$level_tuandui=M('LevelTuandui')->field('id,zs_kj')->where('ww_zt<='.$user['ww_zt'].' and ww_td<='.$user['ww_td'].' and zt_ren<='.$ztren_num)->order('id desc')->find();
				if($level_tuandui){
					if($user['level_tuandui']!=$level_tuandui['id']){
						$dqk_coin='uti_ztren_'.$userid;
						$shuju=$redis->hgetall($dqk_coin);
						$shuju['level_tuandui']=$level_tuandui['id'];
						$redis->hmset($dqk_coin,$shuju);
						M('User')->where('id='.$userid)->save(array('level_tuandui'=>$level_tuandui['id'],'time_xg'=>time()));
						if($level_tuandui['zs_kj']>0){
							$kj_arr=M('Mining')->field('id,price,h_num,gq_day')->where('status=1 and id='.$level_tuandui['zs_kj'])->find();
							if($kj_arr){
								$zongsong=0;
								if($sta_zsong==1){
									//判断本级别是否赠送矿机
									$zongsong_ok=M('MiningList')->field('id')->where('userid='.$userid.' and sta_zs=1 and zs_level='.$level_tuandui['id'])->count();
									if($zongsong_ok<1){
										$zongsong=1;
									}
								}else if($sta_zsong==2){
									//判断是否已经赠送矿机
									$zongsong_ok=M('MiningList')->field('id')->where('sta_zs=1 and userid='.$userid)->count();
									if($zongsong_ok<1){
										$zongsong=1;
									}
								}
								if($zongsong==1){
									$time_dq_h=time()+$kj_arr['h_num']*3600;
									$time_dq=time()+$kj_arr['gq_day']*86400;
									M('MiningList')->add(array('userid'=>$userid,'status'=>2,'time_add'=>time(),'time_zx'=>time(),'time_zx_h'=>$time_dq_h,'num'=>1,'zt_ren'=>$user['zt_ren'],'num_usdt'=>$kj_arr['price'],'num_usdt_zong'=>$kj_arr['price'],'time_dq'=>$time_dq,'kji_id'=>$kj_arr['id'],'kj_day'=>$kj_arr['gq_day'],'kj_h'=>$kj_arr['h_num'],'sta_zs'=>1,'zs_level'=>$level_tuandui['id']));
								}
							}
						}
					}
				}
				if($user['zt_ren']>0){
					$this->shiming_tuandui($user['zt_ren'],$caozid,$zengsong,$sta_zsong,$redis);
				}
			}
		}
    }
    
}
?>