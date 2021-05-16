<?php
namespace Home\Controller;

class ChongController extends HomeController{
  
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array('huafei_mas','huafei_num','huafei_ajax','huafei_log');
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error(L("非法操作！"));
		}
	}
	
	//话费充值详情
	public function huafei_mas(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$user=M('User')->field('id,level_user,mobile')->where('id='.$userid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$huafei=M('ChongHuafeiSet')->where('id=1')->find();
		$huafei['money']=explode('|',$huafei['money_list']);
		$usercoin=M('UserCoin')->field('uti')->where('userid='.$userid)->find();
		$config=M('Config')->field('uti')->where('id=1')->find();
		
		$level_zhe=1;
		$level_fee=0;
		$leveluser=M('LevelUser')->field('chong_zhe,fee_cz')->where('id='.$user['level_user'])->find();
		if($leveluser['chong_zhe']>0){
			if($leveluser['chong_zhe']<1){
				$level_zhe=$leveluser['chong_zhe'];
			}
		}
		if($leveluser['fee_cz']>0){
			$level_fee=$leveluser['fee_cz'];
		}
		$youhui_zhe=1-$level_zhe;
		
		$num=bcmul($huafei['money'][0]/$config['uti']*$level_zhe,100)/100;
		$fee=round($num*$level_fee,2);
		$huafei['mum']=$num;
		$huafei['fee']=$fee;
		$huafei['mobile']=$user['mobile'];
		echo json_encode(array('status'=>1,'info'=>'获取成功','uti'=>($usercoin['uti']*1),'huafei'=>$huafei));exit();
	}
	
	//话费充值详情
	public function huafei_num(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$user=M('User')->field('id,level_user')->where('id='.$userid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$huafei=M('ChongHuafeiSet')->where('id=1')->find();
		
		$num=$_GET['num'];
		if(!is_numeric($num)){
			echo json_encode(array('status'=>1,'info'=>'获取成功','mum'=>0,'fee'=>0));exit();
		}
		
		$num_ok=explode('|'.$num.'|','|'.$huafei['money_list']."|");
		if(count($num_ok)<2){
			echo json_encode(array('status'=>1,'info'=>'获取成功','mum'=>0,'fee'=>0));exit();
		}
		
		$level_zhe=1;
		$level_fee=0;
		$leveluser=M('LevelUser')->field('chong_zhe,fee_cz')->where('id='.$user['level_user'])->find();
		if($leveluser['chong_zhe']>0){
			if($leveluser['chong_zhe']<1){
				$level_zhe=$leveluser['chong_zhe'];
			}
		}
		if($leveluser['fee_cz']>0){
			$level_fee=$leveluser['fee_cz'];
		}
		$config=M('Config')->field('uti')->where('id=1')->find();
		$numx=bcmul($num/$config['uti']*$level_zhe,100)/100;
		
		$fee=round($numx*$level_fee,2);
		echo json_encode(array('status'=>1,'info'=>'获取成功','mum'=>($numx),'fee'=>$fee));exit();
	}
	
	//确认充值
	public function huafei_ajax(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error("请先登录");
        }
      	$user = M('User')->field('id,level_user,mobile')->where('id='.$userid)->find();
      	if(empty($user)){
          	$this->error("请先登录");
        }
		if(cookie('huafei_ajax')=='huafei_ajax'){
			$this->error("请勿重复操作");
		}
		$huafei=M('ChongHuafeiSet')->where('id=1')->find();
		if($huafei['status']!=1){
			$this->error("充值已关闭");
		}
		if($huafei['sta_mobile']==1){
			$mobile=@$_POST['mobile'];
			if($mobile==''){
				$this->error("请输入充值手机号");
			}
			if(!is_numeric($mobile)){
				$this->error("请选择正确的手机号");
			}
			if(strlen($mobile)!=11){
				$this->error("请选择正确的手机号");
			}
		}else{
			$mobile=$user['mobile'];
		}
		$money=@$_POST['num'];
		if(!is_numeric($money)){
			$this->error("请选择正确的充值金额");
		}
		$num_ok=explode('|'.$money.'|','|'.$huafei['money_list']."|");
		if(count($num_ok)<2){
			$this->error("请选择正确的充值金额");
		}
		//判断充值次数
		$chong_num=M('LevelUser')->field('chong_num_yue,chong_num,chong_zhe,fee_cz')->where('id='.$user['level_user'])->find();
		if($chong_num['chong_num']<1){
			$this->error("暂无充值次数");
		}
		$chong_xx=M('ChongHuafeiList')->field('id')->where('status>0 and userid='.$userid)->count();
		if($chong_xx>=$chong_num['chong_num_yue']){
			$this->error("每月充值次数已用完");
		}
		if($chong_xx>=$chong_num['chong_num']){
			$this->error("总充值次数已用完");
		}
		$config=M('Config')->field('uti')->where('id=1')->find();
		if($config['uti']<=0){
			$this->error("价格错误");
		}
		$level_zhe=1;
		$level_fee=0;
		if($chong_num['chong_zhe']>0){
			if($chong_num['chong_zhe']<1){
				$level_zhe=$chong_num['chong_zhe'];
			}
		}
		if($user['level_user']>0){
			$level_fee=$chong_num['fee_cz'];
		}
		$num_sj=bcmul($money/$config['uti'],100)/100;
		$num=bcmul($money/$config['uti']*$level_zhe,100)/100;
		
		$fee=round($num*$level_fee,2);
		$mum=$num+$fee;
		if($mum<=0){
			$this->error("错误");
		}
		$usercoin=M('UserCoin')->field('uti')->where('userid='.$userid)->find();
		if($usercoin['uti']<$mum){
			$this->error("账户UTI不足");
		}
		try{
			$mo = M();
			$mo->startTrans();
			$rs[] = $mo->table('tw_user_coin')->where(array('userid' => $userid))->save(array('uti'=>($usercoin['uti']-$mum)));
			$rs[] = $mo->table('tw_chong_huafei_list')->add(array('userid'=>$userid,'mobile'=>$mobile,'money'=>$money,'discount'=>$level_zhe,'price'=>$config['uti'],'num'=>$num,'num_sj'=>$num_sj,'fee'=>$fee,'mum'=>$mum,'status'=>2,'time_add'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				cookie('huafei_ajax','huafei_ajax',4);
				M('Finance')->add(array('userid'=>$userid,'coin'=>'uti','coinname'=>'UTI','fee'=>$num,'num'=>$usercoin['uti'],'mum'=>($usercoin['uti']-$num),'type'=>0,'name'=>'huafei_ajax','remark'=>'话费充值','protypemas'=>'话费充值','addtime'=>time(),'protype'=>13,'status'=>1));
				if($fee>0){
					M('Finance')->add(array('userid'=>$userid,'coin'=>'uti','coinname'=>'UTI','fee'=>$fee,'num'=>($usercoin['uti']-$num),'mum'=>($usercoin['uti']-$num-$fee),'type'=>0,'name'=>'huafei_ajax','remark'=>'话费充值手续费','protypemas'=>'话费充值手续费','addtime'=>time(),'protype'=>13,'status'=>1));
				}
				$this->success('充值成功');
			}else {
				$mo->rollback();
				$this->error('充值失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('充值失败');
		}
	}
	
	//话费充值、列表
	public function huafei_log(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
			echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
		}
		$pages=($page-1)*10;
		$sta_mas=array('0'=>'已取消','1'=>'到账成功','2'=>'等待到账');
		$list = M('ChongHuafeiList')->field('mum,money,time_add,status')->where('userid='.$userid)->order('id desc')->limit($pages,10)->select();
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
        foreach($list as $n=>$var){
        	$list[$n]['time_add']=date("Y.m.d H:i:s",$var['time_add']);
        	$list[$n]['mum']=$var['mum']*1;
        	$list[$n]['color']='';
        	if($var['status']==2){
        		$list[$n]['color']='color: #f7a619;';
        	}
        	$list[$n]['sta_mas']=$sta_mas[$var['status']];
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
}	
?>