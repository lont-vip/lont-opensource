<?php
namespace Home\Controller;

class HomeController extends \Think\Controller{
	
	protected function _initialize(){
		$allow_controller=array("Index",'Property','Chong','Feed',"Entruy",'News','Market','Newajax','Mining','API','Home',"Ajax","Article","Finance","Login","Queue","Trade","User");
		if(!in_array(CONTROLLER_NAME,$allow_controller)){
			$this->error("非法操作");
		}
		$config=M('Config')->field('web_close')->where('id=1')->find();
		if($config['web_close']==0){
			cookie('userid', 0);
		}
		defined('APP_DEMO') || define('APP_DEMO', 0);
		if(!cookie('userid')) {
			cookie('userid', 0);
		}else if(CONTROLLER_NAME!='Login'){
			$home_userid=cookie('userid');
          	if(is_numeric($home_userid)){
				$home_cx=M('User')->field('uid,time_denglu,status')->where(array('id' => $home_userid))->find();
				if($home_cx){
					if($home_cx['status']==1){
						if($home_cx['uid']==cookie('uid') && $home_cx['time_denglu']==cookie('time_denglu')){
						}else{
							//cookie('userid', 0);
						}
					}else{
						cookie('userid', 0);
					}
				}else{
					cookie('userid', 0);
				}
            }else{
				cookie('userid', 0);
			}
		}
	}
  	
	public function sfdl(){
		$uid=cookie('userid');
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
      	$home_cx=M('User')->field('id')->where(array('id' => $uid))->find();
      	if(empty($home_cx)){
          	$this->error('请先登录');
        }
        $data=M('user')->field('time_denglu,status')->where('id='.$uid)->find();
		$lasttime = cookie('time_denglu');
      	if($data['time_denglu']!=$lasttime){
          	cookie('userid',0);
        	$this->error('请先登录');
          	return;
        }
        if($data['status']!=1){
          	cookie('userid',0);
        	$this->error('请先登录');
          	return;
        }
		$this->success('登录成功');
	}
	
	
  	
}
?>