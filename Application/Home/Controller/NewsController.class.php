<?php
namespace Home\Controller;

class NewsController extends HomeController{
  
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array('home_banner','financial_time','financial_log','home_xiaoxi','home_tishi','home_tishi_no','news_mine','kefu_mas','sortByKeyAsc','news_list','news_mas','home_banner');
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error(L("非法操作！"));
		}
	}
	
	
	//提现记录时间
	public function financial_time(){
		$userid=userid();
		if(!is_numeric($userid)){
			echo json_encode(array('status'=>0,'info'=>'请先登录'));exit();
		}
		if($userid<=0){
			echo json_encode(array('status'=>0,'info'=>'请先登录'));exit();
		}
		$member=M('User')->field('id')->where('id='.$userid)->find();
		if(empty($member)){
			echo json_encode(array('status'=>0,'info'=>'请先登录'));exit();
		}
		$list = M('Finance')->field('addtime')->where("userid=".$userid)->find();
		$dqtime=time()-86400*2;
		if($list){
			if($list['addtime']>$dqtime){
				$start_time=date("Y-m-d",$dqtime);
			}else{
				$start_time=date("Y-m-d",$list['addtime']);
			}
        }else{
        	$start_time=date("Y-m-d",$dqtime);
        }
        $end_time=date("Y-m-d",time());
        echo json_encode(array('status'=>1,'msg'=>'获取成功','start'=>$start_time,'end'=>$end_time));exit;
	}
	
	//提现记录
	public function financial_log(){
    	$userid=userid();
		if(!is_numeric($userid)){
			echo json_encode(array('status'=>0,'info'=>'请先登录'));exit();
		}
		if($userid<=0){
			echo json_encode(array('status'=>0,'info'=>'请先登录'));exit();
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
			echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
		}
		$time_sql='';
		$start=$_GET['start'];
		$end=$_GET['end'];
		if($start && $end){
			$start_x=strtotime($start);
			$end_x=strtotime($end);
			if($start_x>$end_x){
				$time_sql.=' and addtime>='.strtotime($end);
				$time_sql.=' and addtime<'.(strtotime($start)+86400);
			}else{
				$time_sql.=' and addtime>='.strtotime($start);
				$time_sql.=' and addtime<'.(strtotime($end)+86400);
			}
		}else if($start){
			$time_sql.=' and addtime>='.strtotime($start);
		}else if($end){
			$time_sql.=' and addtime<'.(strtotime($end)+86400);
		}
		$sta_mas=array('0'=>'-','1'=>'+');
		$pages=($page-1)*10;
		$list = M('Finance')->field('type,fee,coinname,addtime,protypemas')->where('status=1 and protype!=6 and protype!=16 and userid='.$userid.$time_sql)->order('id desc')->limit($pages,10)->select();
		foreach($list as $k => $v){
          	$list[$k]['addtime']=date("Y.m.d H:i:s",$v['addtime']);
          	$list[$k]['fee']=$sta_mas[$v['type']].$v['fee'];
		}
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
	
  	//首页banner
	public function home_banner(){
    	$uid=userid();
		if(!is_numeric($uid)){
			$this->error('请先登录');
		}
		if($uid<=0){
			$this->error('请先登录');
		}
		$list = M('Adver')->where("status=1 and type='1'")->order('id desc')->limit(5)->select();
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
  	//系统消息我的消息首页
	public function home_xiaoxi(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$list1=M('Article')->field('id,title,addtime')->where("status=1 and type=1")->limit(10)->order('id desc')->select();
		//$list2=M('ArticleMine')->field('id,title,addtime')->where('userid='.$userid)->limit(10)->order('id desc')->select();
		$list2=array();
		if(empty($list1) && empty($list2)){
			$this->error('暂无数据');
		}
		$all = array_merge($list1,$list2);//合并两个二维数组
		$list=$this->sortByKeyAsc($all,'addtime');
		$list = array_slice($list, 0, 10);
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'msg'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$list));exit();
	}
	//二维数组按照键值升序排序
	function sortByKeyAsc($arr, $key) {
		array_multisort(array_column($arr, $key), SORT_DESC, $arr);
		return $arr;
	}
	
	//首页提示
	public function home_tishi(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('id')->where('id='.$userid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$config=M('Config')->field('sta_tankuang')->where('id=1')->find();
		if($config['sta_tankuang']==0){
			$list=M('Article')->field('id,content')->where("sta_mas=1")->find();
		}else{
			$usertixing=M('UserTixing')->field('article_id')->where('userid='.$userid)->select();
			$ids='';
			if($usertixing){
				$idsa=array_column($usertixing,'article_id');
				$ids=implode(',',$idsa);
			}
			if($ids==''){
				$list=M('Article')->field('id,content')->where("sta_mas=1")->find();
			}else{
				$list=M('Article')->field('id,content')->where("sta_mas=1 and id not in (".$ids.")")->find();
			}
		}
		if($list){
			echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$list));exit();
		}
		echo json_encode(array('status'=>0,'msg'=>'获取成功'));exit();
	}
	
	public function home_tishi_no(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$id=$_GET['article_id'];
		if(!is_numeric($id)){
			$this->error('参数错误');
		}
		$config=M('Config')->field('sta_tankuang')->where('id=1')->find();
		if($config['sta_tankuang']==1){
			M('UserTixing')->add(array('userid'=>$userid,'article_id'=>$id));
		}
	}
	
	//客服
	public function kefu_mas(){
		$list=M('Config')->field('wx_img,wx_hao,wx_sta')->where('id=1')->find();
		echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$list));exit();
	}
	
	//我的消息
	public function news_mine(){
    	$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
      		$this->error('暂无数据');
		}
		$pages=($page-1)*10;
		$list = M('ArticleMine')->field('title,addtime')->where('userid='.$userid)->order('id desc')->limit($pages,10)->select();
      	if(empty($list)){
          	$this->error('暂无数据');
        }
		foreach($list as $n=>$var){
			$list[$n]['addtime']=date("Y.m.d H:i:s",$var['addtime']);
		}
		echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$list));exit();
	}
	
	//新闻列表
	public function news_list(){
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
		$pages=($page-1)*10;
		$list = M('Article')->where('status=1 and type=1')->order('id desc')->limit($pages,10)->select();
		foreach($list as $k => $v){
			$list[$k]['title']=mb_substr(strip_tags(htmlspecialchars_decode($v['title'])), 0, 20, 'utf-8')."...";
          	$list[$k]['addtime']=date("Y.m.d H:i",$v['addtime']);
		}
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
  	
  	//新闻详情
  	public function news_mas(){
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
  	
  
}
?>