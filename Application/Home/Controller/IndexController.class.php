<?php
namespace Home\Controller;

class IndexController extends HomeController{
	
	protected function _initialize(){
		parent::_initialize();
		$allow_action = array('home_hangqing','home_news','home_banner','app_mas','home_chongzhi');
		if (!in_array(ACTION_NAME,$allow_action)) {
			$this->error("非法操作！");
		}
	}
	
	//充值状态
	public function home_chongzhi(){
		$huafei=M('ChongHuafeiSet')->field('status')->where('id=1')->find();
		echo json_encode(array('status'=>1,'sta_huafei'=>$huafei['status']));exit();
	}
	
	//币种行情
	public function home_hangqing(){
		$news=M('Coin')->field('name,img,js_yw,market_zhangdie')->where("status=1 and id>1")->order('id asc')->select();
		if(empty($news)){
			$this->error('暂无');
		}
		$config=M('Config')->where('id=1')->find();
		foreach ($news as $n=>$var){
			$news[$n]['price']=$config[$var['name']]*1;
			if($var['market_zhangdie']>=0){
				$news[$n]['zhangdie']='<span class="home-coin-ri">'.$var['market_zhangdie'].'% <img src="/Public/images/up.png" alt=""></span>';
			}else{
				$news[$n]['zhangdie']='<span style="background:#ff7667;" class="home-coin-ri">'.$var['market_zhangdie'].'% <img src="/Public/images/down.png" alt=""></span>';
			}
		}
		echo json_encode(array('status'=>1,'data'=>$news));exit();
	}
	
	
	public function app_mas(){
  		$config=M('Config')->where('id=1')->find();
  		echo json_encode(array('status'=>1,'msg'=>'获取成功','data'=>$config));exit;
  	}
  
	//首页-图片2
	public function home_banner(){
		$news=M('Adver')->field('id,img')->where("status=1")->order('id desc')->select();
		if(empty($news)){
			$this->error('暂无');
		}
		$xingq='';
		foreach($news as $n=>$var){
			$xingq.='<div class="swiper-slide"><img src="/Upload/ad/'.$var['img'].'" alt=""></div>';
		}
		echo json_encode(array('code'=>1,'msg'=>$xingq));exit();
	}

  
	//首页-滚动新闻
	public function home_news(){
		$news=M('Article')->field('id,title')->where('status=1')->order('id desc')->limit(5)->select();
		if(empty($news)){
			$this->error('暂无');
		}
		$xingq='';
		foreach($news as $n=>$var){
			$xingq.=' <div class="swiper-slide"  style="font-size:0.27rem;"><a class="bui-btn" href="pages/details?id='.$var['id'].'">'.$var['title'].'</a></div>';
		}
		echo json_encode(array('code'=>1,'msg'=>$xingq));exit();
	}
	
	
	//二维数组排序
	public function multi_array_sort($arr,$shortKey,$short=SORT_DESC,$shortType=SORT_REGULAR)
	{
	    foreach ($arr as $key => $data){
	        $name[$key] = $data[$shortKey];
	    }
	        array_multisort($name,$shortType,$short,$arr);
	    return $arr;
	}
  
}
?>