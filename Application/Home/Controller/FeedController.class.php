<?php
namespace Home\Controller;

class FeedController extends HomeController{
  
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array('log_list','log_mas','log_ajax','feed_log','feedback_img','repair','repair2');
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error(L("非法操作！"));
		}
	}
	
	//我的反馈
	public function log_list(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
      		$this->error('暂无数据');
		}
		$pages=($page-1)*10;
		$list = M('Feed')->where('type=1 and userid='.$userid)->order('id desc')->limit($pages,10)->select();
      	if(empty($list)){
          	$this->error('暂无数据');
        }
		foreach($list as $n=>$var){
			$list[$n]['addtime']=date("Y.m.d H:i:s",$var['create_time']);
		}
		echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$list));exit();
	}
	
  	//新闻详情
  	public function log_mas(){
    	$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
      	$id=$_GET['id'];
      	if(!is_numeric($id)){
			$this->error('参数错误');
		}
      	$news=M('Article')->where('id='.$id)->find();
      	if(empty($news)){
          	$this->error('参数错误');
        }
      	$news['addtime']=date("Y.m.d H:i",$news['addtime']);
      	$config=M('Config')->field('web_title')->where('id=1')->find();
      	$news['web']=$config['web_title'];
      	echo json_encode(array('status'=>1,'info'=>'获取成功','msg'=>$news));exit();
    }
  	
  	//反馈信息
	public function repair(){
    	$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
      	if(cookie('repair')=='repair'){
          	$this->error('请勿频繁操作');
        }
        $hfid=$_POST['hf_id'];
        if(!is_numeric($hfid)){
        	$this->error('参数错误');
        }
        $hf_ok=M('Feed')->field('id')->where('userid='.$uid.' and type=1 and id='.$hfid)->find();
        if(empty($hf_ok)){
        	$this->error('参数错误');
        }
		$content=$_POST['content'];
      	if($content==''){
          	$this->error('请输入反馈内容');
        }
      	if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$content)){
          	$this->error('内容含有特殊字符');
        }
        $type=0;
      	try{
          	$mo = M();
          	$mo->startTrans();
          	$rs[] = $mo->table('tw_feed')->add(array('content'=>$content,'userid'=>$uid,'hfid'=>$hfid,'type'=>$type,'status'=>0,'create_time'=>time()));
          	if(check_arr($rs)) {
              	$mo->commit();
              	cookie('repair','repair',4);
              	$this->success('反馈成功');
            }else {
              	$mo->rollback();
              	$this->error('反馈失败');
            }
        }catch(\Think\Exception $e){
          	$mo->rollback();
          	$this->error('反馈失败');
        }
    }
  	
  	//反馈信息
	public function repair2(){
    	$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
      	if(cookie('repair2')=='repair2'){
          	$this->error('请勿频繁操作');
        }
		$content=$_POST['content'];
      	if($content==''){
          	$this->error('请输入反馈内容');
        }
      	if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$content)){
          	$this->error('内容含有特殊字符');
        }
      	//查询是否存在反馈
      	$hfid=0;
      	$type=1;
      	$img='';
      	if(@$_POST['img']){
      		$img=implode('|',$_POST['img']);
      	}
      	if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]\]|\[|\/|\\\|\"\|/",$img)){
          	$this->error('内容含有特殊字符');
        }
      	try{
          	$mo = M();
          	$mo->startTrans();
          	$rs[] = $mo->table('tw_feed')->add(array('content'=>$content,'userid'=>$uid,'hfid'=>$hfid,'type'=>$type,'status'=>0,'create_time'=>time()));
          	if(check_arr($rs)) {
              	$mo->commit();
              	if($img){
              		$mo->table('tw_feed')->add(array('img'=>$img,'userid'=>$uid,'hfid'=>$rs[0],'type'=>0,'status'=>0,'create_time'=>time()));
              	}
              	cookie('repair2','repair2',4);
              	$this->success('反馈成功');
            }else {
              	$mo->rollback();
              	$this->error('反馈失败');
            }
        }catch(\Think\Exception $e){
          	$mo->rollback();
          	$this->error('反馈失败');
        }
    }
  	
    //客服上传图片
    public function feedback_img(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
		$upload = new \Think\Upload();
		$upload->maxSize = 15728640;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/feed/';
		$upload->autoSub = false;
		$info = $upload->upload();
		if(!$info){
			$this->error($upload->getError());
		}else{
			$upload_dir='Upload/feed/';
			$image = new \Think\Image();
			foreach ($info as $k => $v) {
				$path = $v['savepath'] . $v['savename'];
				
				$file_url = $upload_dir . $v['savename'];
	            $image->open($file_url);
	            $width = $image->width(); // 返回图片的宽度
	            $height = $image->height(); // 返回图片的高度
	            if($width > 600 || $height > 600){//原图宽度或高度大于1500时才缩放
	                // 按照原图的比例生成一个最大为1500*1500（根据宽高像素等比例缩小）的缩略图并保存，我这里直接覆盖掉了原图。
	                $image->thumb(600, 600)->save($file_url);
	            }
	            echo json_encode(array('status'=>1,'info'=>$path));exit;
		      	//查询是否存在反馈
		      	$hfid=0;
		      	$type=1;
		      	$list = M('Feed')->field('id')->where("userid=".$userid)->find();
		      	if($list){
		          	$hfid=$list['id'];
		          	$type=0;
		        }
		      	try{
		          	$mo = M();
		          	$mo->startTrans();
		          	$rs[] = $mo->table('tw_feed')->add(array('img'=>$path,'userid'=>$userid,'hfid'=>$hfid,'type'=>$type,'status'=>0,'create_time'=>time()));
		          	if(check_arr($rs)) {
		              	$mo->commit();
		              	cookie('repair','repair',4);
		              	echo json_encode(array('status'=>1,'info'=>$path));exit;
		            }else {
		              	$mo->rollback();
		              	$this->error('反馈失败');
		            }
		        }catch(\Think\Exception $e){
		          	$mo->rollback();
		          	$this->error('反馈失败');
		        }
			}
		}
    }
	
    //联系客服列表
  	public function feed_log(){
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
		$id=$_GET['id'];
		$pages=($page-1)*10;
		$list = M('Feed')->where("userid=".$uid.' and (hfid='.$id.' or id='.$id.')')->order('id asc')->limit($pages,10)->select();
		foreach($list as $k => $v){
          	if($v['content']!=''){
            	$list[$k]['type']=1;
            }else if($v['img']!=''){
            	$list[$k]['type']=1;
            	$imgarr=explode("|",$v['img']);
            	$xq='<div>';
            	for($i=0;$i<count($imgarr);$i++){
            		if($imgarr[$i]){
            			$xq.='<img src="./Upload/feed/'.$imgarr[$i].'" />';
            		}
            	}
            	$list[$k]['content']=$xq.'</div>';
            }else{
            	$list[$k]['type']=2;
            }
          	$list[$k]['addtime']=date("Y-m-d H:i:s",$v['create_time']);
		}
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
    }
    
  
}
?>