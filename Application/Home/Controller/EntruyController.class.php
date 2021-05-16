<?php
/* 交易中心 */
namespace Home\Controller;

class EntruyController extends HomeController{
	
	//查询交易市场
	public function entruy_mas(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登陆');
		}
		$market='';
		$market_c=$_GET['market'];
		$market_moren=M('TrademarketSet')->field('market')->where("id=1")->find();
		if($market_c){
			if(!preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market_c)){
				$market_ok=M('Trademarket')->field('id')->where("name='".$market_c."'")->find();
				if($market_ok){
					$market=$market_c;
				}
			}
		}
		if($market==''){
			$market=$market_moren['market'];
		}
		$dq_market=M('Trademarket')->field('name,num_list,fenqu,price_min,price_max')->where("name='".$market."'")->find();
		$market_dx=strtoupper($dq_market['name']);
		$dq_market['num_list']=explode('|',$dq_market['num_list']);
		$dq_market['coin']=explode('_',$market_dx);
		$dq_market['market_name']=str_replace('_','/',$market_dx);
		
		$dq_market['price_min']=$dq_market['price_min']*1;
		$dq_market['price_max']=$dq_market['price_max']*1;
		
		$yhk_ok=M('UserBank')->field('id')->where('status=1 and userid='.$userid)->count();
		$zfb_ok=M('UserZfb')->field('id')->where('status=1 and userid='.$userid)->count();
		$wx_ok=M('UserWx')->field('id')->where('status=1 and userid='.$userid)->count();
		$user['yhk']=$yhk_ok*1;
		$user['zfb']=$zfb_ok*1;
		$user['wx']=$wx_ok*1;
		$market_arr=M('Trademarket')->field('name,img,fenqu')->where("status=1")->select();
		foreach ($market_arr as $n=>$var){
			$market_arr[$n]['name2']=str_replace("_",'/',strtoupper($var['name']));
		}
		echo json_encode(array('status'=>1,'data'=>$dq_market,'user'=>$user,'market_arr'=>$market_arr));exit();
	}
	
	//提交发布订单
  	public function fabu_ajax(){
  		$userid=userid();
  		if(!is_numeric($userid)){
  			$this->error('请先登录');
  		}
		if(cookie('fabu_ajax')=='fabu_ajax'){
			$this->error('请勿频繁操作');
		}
		$user_info = M('User')->where('id='.$userid)->find();
		if(empty($user_info)){
			$this->error('请先登录');
		}
		$market=$_POST['market'];
		if($market==''){
			$this->error('市场错误');
		}
		if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
			$this->error('市场错误');
		}
		$market_arr=M('Trademarket')->where("name='".$market."'")->find();
		if(empty($market_arr)){
			$this->error('市场错误');
		}
		if($market_arr['status']!=1){
			$this->error('市场已关闭');
		}
		if($market_arr['sta_sm']==1){
			if($user_info['idstate']!=2){
	          	$this->error("请先实名认证");
	        }
		}
		$dqsjd=date("H",time());
		if($market_arr['time_start']!=0){
			if($dqsjd<$market_arr['time_start']){
				$this->error("交易时间还未开始");
			}
		}
		if($market_arr['time_end']!=0){
			if($dqsjd>=$market_arr['time_end']){
				$this->error("交易时间已结束");
			}
		}
		$type=$_POST['type'];
		if($type != 1 && $type != 2){
			$this->error("类型错误");
		}
		if($type==2){
			if($market_arr['shop_sell']!=0){
				if($user_info['shop']!=1){
					$this->error("未开启商家");
				}
			}
		}
		if($type==1){
			if($market_arr['shop_buy']!=0){
				if($user_info['shop']!=1){
					$this->error("未开启商家");
				}
			}
		}
		if($type==2){
			$marketfabi=M('TrademarketSet')->field('sta_fabi,sell_min')->where("id=1")->find();
			if($market_arr['fenqu']=='cny'){
				if($marketfabi['sta_fabi']==0){
					if($user_info['level_user']==1){
						$this->error("Lv0会员不能卖出");
					}
				}
			}else{
				if($user_info['level_user']==1){
					$this->error("Lv0会员不能卖出");
				}
			}
		}
		$num=$_POST['num'];
		if(!is_numeric($num)){
			$this->error("请选择数量");
		}
		if($num<=0){
			$this->error("请选择数量");
		}
		$num_ok='|'.$market_arr['num_list'].'|';
		$num_xx=explode('|'.$num.'|',$num_ok);
		if(count($num_xx)<2){
			$this->error("请选择数量");
		}
		$price=$_POST['price'];
		if(!is_numeric($price)){
			$this->error("请输入价格");
		}
		if($price<=0){
			$this->error("请输入正确的价格");
		}
		if($price<$market_arr['price_min']){
			$this->error("最小交易价格".($market_arr['price_min']*1));
		}
		if($market_arr['price_max']>0){
			if($price>$market_arr['price_max']){
				$this->error("最大交易价格".($market_arr['price_max']*1));
			}
		}
		$price_xd=explode('.',$price);
		if(count($price_xd)>1){
			if(strlen($price_xd[1])>4){
				$this->error("价格最大4位小数");
			}
		}
		$money_min=$_POST['money_min'];
		if(!is_numeric($money_min)){
			$this->error("请输入最小交易数量");
		}
		if($money_min<=0){
			$this->error("请输入正确的最小交易数量");
		}
		
		if($type==2){
			$marketfabi=M('TrademarketSet')->field('sta_fabi,sell_min')->where("id=1")->find();
			if($marketfabi['sell_min']>$money_min){
				$min=$marketfabi['sell_min']*1;
				$this->error("最小交易数量不能低于".$min);
			}
			if($num<$money_min){
				$this->error("交易数量小于最小交易数量");
			}
		}
		if($type==1){
			$marketfabi=M('TrademarketSet')->field('sta_fabi,sell_min,buy_min')->where("id=1")->find();
			if($marketfabi['buy_min']>$money_min){
				$min=$marketfabi['buy_min']*1;
				$this->error("最小交易数量至少".$min);
			}
			if($num<$money_min){
				$this->error("交易数量小于最小交易数量");
			}
		}
		
		if($market_arr['fenqu']=='cny'){
			$pay_1=$_POST['pay_1'];
			$pay_2=$_POST['pay_2'];
			$pay_3=$_POST['pay_3'];
			if($pay_1!=1 && $pay_2!=1 && $pay_3!=1){
				if($type==1){
					$this->error("选择付款方式");
				}else{
					$this->error("选择收款方式");
				}
			}
			$paypass=$_POST['paypassword'];
			if($paypass==''){
				//$this->error("输入交易密码");
			}
	      	if($pay_1==1){
	      		$userban=M('user_bank')->field('id')->where("userid=".$userid." and status=1")->count();
	        	if($userban<1){
	            	$this->error("请先设置银行卡信息", $extra);
	            }
	      	}
	      	if($pay_2==1){
	      		$userzfb=M('user_zfb')->field('id')->where("userid=".$userid." and status=1")->count();
	        	if($userzfb<1){
	            	$this->error("请先设置支付宝信息", $extra);
	            }
	        }
	      	if($pay_3==1){
	        	$userwx=M('user_wx')->field('id')->where("userid=".$userid." and status=1")->count();
	        	if($userwx<1){
	            	$this->error("请先设置微信信息", $extra);
	            }
	        }
		}else{
			$pay_1=0;
			$pay_2=0;
			$pay_3=0;
		}
        if(md5($paypass.$user_info['invit'])!=$user_info['paypassword']){
        	//$this->error("交易密码错误");
        }
		$table = 'TrademarketEntruy';
		$ad_no = $this->getadvno();
		
		$market_schang=explode('_',$market);
		
		$coin_mc=$market_schang[0];//收款币种
		$coin_mr=$market_schang[1];//付款币种
		$coin_mc_d=strtoupper($market_schang[0]);//收款币种
		$coin_mr_d=strtoupper($market_schang[1]);//付款币种
		$usercoin= M('UserCoin')->where(array('userid'=>$userid))->find();
		
		//插入买入表
		if($type==1){
			$money_zong=bcmul($price*$num,1000000)/1000000;
			//查询币种是否cny
			if($coin_mr=='cny'){
		        try{
		        	$mo = M();
		        	$mo->startTrans();
		        	$rs[] = $mo->table('tw_trademarket_entruy')->add(array('fenqu'=>$coin_mr,'market'=>$market,'userid' => $userid, 'time_add' => time(),'type'=>1,'sta_zf'=>1,'price'=>$price, 'num_zong' => $num,'money'=>$money_zong, 'num_min' => $money_min,'pay_1' => $pay_1,'pay_2' => $pay_2,'pay_3' => $pay_3, 'status' => 2,'order_id'=> $ad_no));
		        	if(check_arr($rs)) {
		        		$mo->commit();
		        		cookie('fabu_ajax','fabu_ajax',4);
		        		$this->success('发布成功');
		        	}else {
		        		$mo->rollback();
		        		$this->error('发布失败');
		        	}
		        }catch(\Think\Exception $e){
		        	$mo->rollback();
		        	$this->error('发布失败');
		        }
			}else{
				if($usercoin[$coin_mr]<$money_zong){
					$this->error('账户'.$coin_mr_d.'不足');
				}
				try{
		        	$mo = M();
		        	$mo->startTrans();
		        	$rs[0] = $mo->table('tw_user_coin')->where('userid='.$userid)->save(array($coin_mr=>($usercoin[$coin_mr]-$money_zong),$coin_mr.'d'=>($usercoin[$coin_mr.'d']+$money_zong)));
		        	$rs[1] = $mo->table('tw_trademarket_entruy')->add(array('fenqu'=>$coin_mr,'market'=>$market,'userid' => $userid, 'time_add' => time(),'type'=>1,'sta_zf'=>1,'price'=>$price, 'num_zong' => $num,'money'=>$money_zong, 'num_min' => $money_min,'pay_1' => $pay_1,'pay_2' => $pay_2,'pay_3' => $pay_3, 'status' => 2,'order_id'=> $ad_no));
		        	if(check_arr($rs)) {
		        		$mo->commit();
		        		cookie('fabu_ajax','fabu_ajax',4);
		        		M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_mr,'coinname'=>$coin_mr_d,'fee'=>$money_zong,'num'=>$usercoin[$coin_mr],'mum'=>($usercoin[$coin_mr]-$money_zong),'type'=>0,'name'=>'fabu_ajax','remark'=>'买币','protypemas'=>'买币','addtime'=>time(),'protype'=>1,'status'=>1));
		        		$this->level_user_gx($userid);
		        		$this->success('发布成功');
		        	}else {
		        		$mo->rollback();
		        		$this->error('发布失败1');
		        	}
		        }catch(\Think\Exception $e){
		        	$mo->rollback();
		        	$this->error('发布失败2');
		        }
			}
		}else{
			$user_fee=M('LevelUser')->field('name,fee_mc,trade_cs,trade_num')->where('id='.$user_info['level_user'])->find();
			$fee_bili=$user_fee['fee_mc'];
			if($fee_bili<=0){
				$fee_bili=0;
			}
			if($market_arr['fenqu']!='cny'){
				if($user_fee['trade_cs']>0){
					$yjy_num1=M('TrademarketEntruy')->field('id')->where('userid='.$userid.' and type=2 and status>0')->count();
					$yjy_num2=M('TrademarketEntruyLog')->field('id')->where('peerid='.$userid.' and sta_zf!=4')->count();
					$yjy_num=$yjy_num1+$yjy_num2;
					if($yjy_num>=$user_fee['trade_cs']){
						$this->error('已达到限制次数');
					}
				}
				if($user_fee['trade_num']>0){
					$yjy_num1=M('TrademarketEntruy')->field('num_zong')->where('userid='.$userid.' and type=2 and status>0')->sum('num_zong');
					$yjy_num2=M('TrademarketEntruyLog')->field('num')->where('peerid='.$userid.' and sta_zf!=4')->sum('num');
					$yjy_num=$yjy_num1+$yjy_num2;
					if($yjy_num>=$user_fee['trade_num']){
						$this->error('已达到限制数量');
					}
					if(($yjy_num+$num)>$user_fee['trade_num']){
						$this->error('数量过大，累计可卖出'.($user_fee['trade_num']*1));
					}
				}
			}
			$fee=bcmul($fee_bili*$num,10000)/10000;
			if($market_arr['sta_fee']==0){
				$fee_bili=0;
				$fee=0;
			}
			
			$mum=$num;
			$money_zong=bcmul($price*$mum,10000)/10000;
			$num_kc=$mum+$fee;
			//计算扣除经验值
			$jyz_bili=$market_arr['fee_emp'];
			$jyz=bcmul($jyz_bili*$mum,10000)/10000;
			if($usercoin['jyzhi']<$jyz){
				$this->error('账户经验值不足');
			}
			if($usercoin[$coin_mc]<$num_kc){
				$this->error('账户'.$coin_mc_d.'不足');
			}
			
			try{
				$mo = M();
				$mo->startTrans();
				$rs[0] = $mo->table('tw_user_coin')->where('userid='.$userid)->save(array($coin_mc=>($usercoin[$coin_mc]-$num_kc),$coin_mc.'d'=>($usercoin[$coin_mc.'d']+$num_kc),'jyzhi'=>($usercoin['jyzhi']-$jyz)));
				$rs[1] = $mo->table('tw_trademarket_entruy')->add(array('fenqu'=>$coin_mr,'market'=>$market,'userid' => $userid, 'time_add' => time(),'type'=>2,'sta_zf'=>1,'price'=>$price,'money'=>$money_zong, 'num_zong' => $mum,'fee_bl'=>$fee_bili,'fee'=>$fee,'jyz_bl'=>$jyz_bili,'jyz'=>$jyz, 'num_min' => $money_min, 'pay_1' => $pay_1,'pay_2' => $pay_2,'pay_3' => $pay_3, 'status' => 2,'order_id'=> $ad_no));
				if(check_arr($rs)) {
					$mo->commit();
					cookie('fabu_ajax','fabu_ajax',4);
					M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_mc,'coinname'=>$coin_mc_d,'fee'=>$mum,'num'=>$usercoin[$coin_mc],'mum'=>($usercoin[$coin_mc]-$mum),'type'=>0,'name'=>'fabu_ajax','remark'=>'卖币','protypemas'=>'卖币','addtime'=>time(),'protype'=>2,'status'=>1));
					if($fee>0){
						M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_mc,'coinname'=>$coin_mc_d,'fee'=>$fee,'num'=>($usercoin[$coin_mc]-$mum),'mum'=>($usercoin[$coin_mc]-$mum-$fee),'type'=>0,'name'=>'fabu_ajax','remark'=>'卖币手续费','protypemas'=>'卖币手续费','addtime'=>time(),'protype'=>2,'status'=>1));
					}
					if($jyz>0){
						M('Finance')->add(array('userid'=>$userid,'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$jyz,'num'=>$usercoin['jyzhi'],'mum'=>($usercoin['jyzhi']-$jyz),'type'=>0,'name'=>'fabu_ajax','remark'=>'卖币','protypemas'=>'卖币','addtime'=>time(),'protype'=>2,'status'=>1));
						
					}
					$this->level_user_gx($userid);
		        	$this->success('发布成功');
		        }else {
		        	$mo->rollback();
		        	$this->error('发布失败');
		       	}
			}catch(\Think\Exception $e){
		       	$mo->rollback();
		        $this->error('发布失败');
		    }
		}
	}
	
	//委托订单
  	private function getadvno(){
		$adv_no = time().rand(1111.9999);
		$advbuy = M('TrademarketEntruy')->field('id')->where(array('order_id'=>$adv_no))->count();
		if($advbuy>0){
			$this->getadvno();
		}else{
			return $adv_no;
		}
	}
	
	//默认市场
	public function market_moren(){
		$market_moren=M('TrademarketSet')->field('market')->where("id=1")->find();
		$market_moren['name2']=str_replace("_",'/',strtoupper($market_moren['market']));
		
		$dqtime=strtotime(date("Y-m-d",time()));
		$max_price=M('TrademarketEntruyLog')->field('price')->where("market='".$market_moren['market']."' and time_add>".$dqtime)->order('price desc')->find();
		$min_price=M('TrademarketEntruyLog')->field('price')->where("market='".$market_moren['market']."' and time_add>".$dqtime)->order('price asc')->find();
		
		$market=M('Trademarket')->field('price,price_zt')->where("name='".$market_moren['market']."'")->find();
		$market['price_max']=0;
		$market['price_junjia']=0;
		if($max_price){
			$market['price_max']=$max_price['price'];
			$market['price_junjia']=($max_price['price']+$min_price['price'])/2;
		}
		$market_bi=explode("_",strtoupper($market_moren['market']));
		$market['name']=$market_bi[0];
		$market['day_chengjiao']=M('TrademarketEntruyLog')->field('num')->where("sta_zf=3 and market='".$market_moren['market']."' and time_add>".$dqtime)->sum('num');
		$market['chengjiao']=M('TrademarketEntruyLog')->field('num')->where("sta_zf=3 and market='".$market_moren['market']."'")->sum('num');
		
		$market['zhangdie']=intval(($market['price']-$market['price_zt'])/$market['price_zt']*10000)/100;
		if($market['zhangdie']>=0){
			$market['zhangdie']=$market['zhangdie'].'% <img src="/Public/images/up.png" alt="" />';
		}else{
			$market['zhangdie']=$market['zhangdie'].'% <img src="/Public/images/down.png" alt="" />';
		}
		
		echo json_encode(array('status'=>1,'market'=>$market_moren['market'],'market2'=>$market_moren['name2'],'market_arr'=>$market));exit();
	}
	
	//默认市场
	public function market_coin_xz(){
		$maeket=$_GET['market'];
		$market_moren=M('TrademarketSet')->field('market')->where("id=1")->find();
		if(!preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$maeket)){
			$shichang=M('Trademarket')->field('id')->where("name='".$maeket."'")->find();
			if($shichang){
				$market_moren['market']=$maeket;
			}
		}
		
		$market_moren['name2']=str_replace("_",'/',strtoupper($market_moren['market']));
		
		$dqtime=strtotime(date("Y-m-d",time()));
		$max_price=M('TrademarketEntruyLog')->field('price')->where("market='".$market_moren['market']."' and time_add>".$dqtime)->order('price desc')->find();
		$min_price=M('TrademarketEntruyLog')->field('price')->where("market='".$market_moren['market']."' and time_add>".$dqtime)->order('price asc')->find();
		
		$market=M('Trademarket')->field('price,price_zt')->where("name='".$market_moren['market']."'")->find();
		$market['price_max']=0;
		$market['price_junjia']=0;
		if($max_price){
			$market['price_max']=$max_price['price'];
			$market['price_junjia']=($max_price['price']+$min_price['price'])/2;
		}
		$market_bi=explode("_",strtoupper($market_moren['market']));
		$market['name']=$market_bi[0];
		$market['day_chengjiao']=M('TrademarketEntruyLog')->field('num')->where("sta_zf=3 and market='".$market_moren['market']."' and time_add>".$dqtime)->sum('num');
		$market['chengjiao']=M('TrademarketEntruyLog')->field('num')->where("sta_zf=3 and market='".$market_moren['market']."'")->sum('num');
		
		$market['zhangdie']=intval(($market['price']-$market['price_zt'])/$market['price_zt']*10000)/100;
		if($market['zhangdie']>=0){
			$market['zhangdie']=$market['zhangdie'].'% <img src="/Public/images/up.png" alt="" />';
		}else{
			$market['zhangdie']=$market['zhangdie'].'% <img src="/Public/images/down.png" alt="" />';
		}
		
		echo json_encode(array('status'=>1,'market'=>$market_moren['market'],'market2'=>$market_moren['name2'],'market_arr'=>$market));exit();
	}
	
  	//我要买 列表
  	public function transaction1(){
    	$userid = userid();
    	if(!is_numeric($userid)){
    		echo json_encode(array('status'=>0,'info'=>'请先登录'));exit();
    	}
    	$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
    	$market=$_GET['market'];
    	if($market==''){
    		echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
    	}
    	if($market==''){
    		echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
    	}
    	if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
    		echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
    	}
		$pages=($page-1)*6;
		$field = $_GET['field'];//要进行排序的字段
		$desc = $_GET['desc'];//正序为为1 倒叙为0
		$field_str = '';
		$order_str = '';
		if($field && $desc){
			if($desc == 1){
				if($field == 'num'){
					$list=M()->query("SELECT * FROM tw_trademarket_entruy where market='".$market."' and (num_zong-num_deal>=num_min) and status=2 and type=2 ORDER BY num_zong-num_deal asc limit ".$pages.",6");
				}else{
					$list = M('TrademarketEntruy')->where("market='".$market."' and status=2 and (num_zong-num_deal>=num_min) and type=2")->order('price asc')->limit($pages,6)->select();
				}
			}else{
				if($field == 'num'){
					$list=M()->query("SELECT * FROM tw_trademarket_entruy where market='".$market."' and (num_zong-num_deal>=num_min) and status=2 and type=2 ORDER BY num_zong-num_deal desc limit ".$pages.",6");
				}else{
					$list = M('TrademarketEntruy')->where("market='".$market."' and status=2 and (num_zong-num_deal>=num_min) and type=2")->order('price desc')->limit($pages,6)->select();
				}
			}
		}else{
			$list = M('TrademarketEntruy')->where("market='".$market."' and status=2 and (num_zong-num_deal>=num_min) and type=2")->order('id desc')->limit($pages,6)->select();
		}
		
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
        $market_d=strtoupper($market);
        $market_c=explode('_',$market_d);
        $bzfuh=$market_c[1];
      	foreach ($list as $k => $v) {
      		$list[$k]['price']=$v['price']*1;
      		$list[$k]['coin_1'] = $market_c[0];
      		$list[$k]['coin_2'] = $market_c[1];
      		$list[$k]['coin_3'] = $bzfuh;
			$list[$k]['num'] = bcmul(($v['num_zong']-$v['num_deal']),10000)/10000;
			$adverinfo = M('User')->where(array('id'=>$v['userid']))->find();
          	if($adverinfo['headimg']){
            	$list[$k]['avatar'] = '/Upload/headimg/'.$adverinfo['headimg'];
            }else{
            	$list[$k]['avatar'] = '/Public/images/photo.png';
            }
            $list[$k]['enname'] =$v['userid'];
			//$list[$k]['enname'] =mb_substr($name, 0, 1, 'utf-8').'**'.mb_substr($name,mb_strlen($name,'utf-8')-1,mb_strlen($name,'utf-8'),'utf-8');
          	$list[$k]['fkimg']='';
          	if($v['pay_1']==1){
              	$list[$k]['fkimg'].='<span><img style="width:0.4rem;" src="/Public/images/card.png" alt=""></span>';
            }
          	if($v['pay_2']==1){
              	$list[$k]['fkimg'].='<span><img style="width:0.4rem;" src="/Public/images/zfb.png" alt=""></span>';
            }
          	if($v['pay_3']==1){
              	$list[$k]['fkimg'].='<span><img style="width:0.4rem;" src="/Public/images/wx-wx.png" alt=""></span>';
            }
            $list[$k]['num_min']=$v['num_min']*1;
            $list[$k]['money_min']=$v['money_min']*1;
            $list[$k]['money_max']=$v['money_max']*1;
            $list[$k]['price']=$list[$k]['price']*1;
            $list[$k]['money']=$list[$k]['price']*$list[$k]['num'];
		}
		
		echo json_encode(array('status'=>1,'info'=>$id,'data'=>$list));exit();
    }
    
  	//我要卖列表
  	public function transaction2(){
    	$userid = userid();
    	if(!is_numeric($userid)){
    		echo json_encode(array('status'=>0,'info'=>'请先登录'));exit();
    	}
    	$page=$_GET['page'];
      	if($page=='' || !is_numeric($page)){
			echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
		}
    	$market=$_GET['market'];
    	if($market==''){
    		echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
    	}
    	if($market==''){
    		echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
    	}
    	if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
    		echo json_encode(array('status'=>0,'info'=>'参数错误'));exit();
    	}
    	if($market=='kkkkk'){
    		echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
    	}
		$pages=($page-1)*6;
		
		$field = $_GET['field'];//要进行排序的字段
		$desc = $_GET['desc'];//正序为为1 倒叙为0
		$field_str = '';
		$order_str = '';
		if($field && $desc){
			if($desc == 1){
				if($field == 'num'){
					$list=M()->query("SELECT * FROM tw_trademarket_entruy where market='".$market."' and (num_zong-num_deal>=num_min) and status=2 and type=1 ORDER BY num_zong-num_deal asc limit ".$pages.",6");
				}else{
					$list = M('TrademarketEntruy')->where("market='".$market."' and (num_zong-num_deal>=num_min) and status=2 and type=1")->order('price asc')->limit($pages,6)->select();
				}
			}else{
				if($field == 'num'){
					$list=M()->query("SELECT * FROM tw_trademarket_entruy where market='".$market."' and (num_zong-num_deal>=num_min) and status=2 and type=1 ORDER BY num_zong-num_deal desc limit ".$pages.",6");
				}else{
					$list = M('TrademarketEntruy')->where("market='".$market."' and status=2 and (num_zong-num_deal>=num_min) and type=1")->order('price desc')->limit($pages,6)->select();
				}
			}
		}else{
			$list = M('TrademarketEntruy')->where("market='".$market."' and status=2 and (num_zong-num_deal>=num_min) and type=1")->order('id desc')->limit($pages,6)->select();
		}
		
		
		
		//$list = M('TrademarketEntruy')->where("market='".$market."' and status=2 and type=1")->field($field_str)->order($order_str)->limit($pages,6)->select();
      	if(empty($list)){
          	echo json_encode(array('status'=>0,'info'=>'暂无数据'));exit();
        }
        $market_d=strtoupper($market);
        $market_c=explode('_',$market_d);
        $bzfuh=$market_c[1];
      	foreach ($list as $k => $v) {
      		$list[$k]['price']=$v['price']*1;
      		$list[$k]['coin_1'] = $market_c[0];
      		$list[$k]['coin_2'] = $market_c[1];
      		$list[$k]['coin_3'] = $bzfuh;
			$list[$k]['num'] = bcmul(($v['num_zong']-$v['num_deal']),10000)/10000;
			$adverinfo = M('User')->where(array('id'=>$v['userid']))->find();
          	if($adverinfo['headimg']){
            	$list[$k]['avatar'] = '/Upload/headimg/'.$adverinfo['headimg'];
            }else{
            	$list[$k]['avatar'] = '/Public/images/photo.png';
            }
            $list[$k]['enname'] =$v['userid'];
			//$list[$k]['enname'] =mb_substr($name, 0, 1, 'utf-8').'**'.mb_substr($name,mb_strlen($name,'utf-8')-1,mb_strlen($name,'utf-8'),'utf-8');
          	$list[$k]['fkimg']='';
          	if($v['pay_1']==1){
              	$list[$k]['fkimg'].='<span><img style="width:0.4rem;" src="/Public/images/card.png" alt=""></span>';
            }
          	if($v['pay_2']==1){
              	$list[$k]['fkimg'].='<span><img style="width:0.4rem;" src="/Public/images/zfb.png" alt=""></span>';
            }
          	if($v['pay_3']==1){
              	$list[$k]['fkimg'].='<span><img style="width:0.4rem;" src="/Public/images/wx-wx.png" alt=""></span>';
            }
            $list[$k]['num_min']=$v['num_min']*1;
            $list[$k]['money_min']=$v['money_min']*1;
            $list[$k]['money_max']=$v['money_max']*1;
            $list[$k]['money']=$list[$k]['price']*$list[$k]['num'];
		}
		echo json_encode(array('status'=>1,'info'=>$id,'data'=>$list));exit();
    }
    
	//计算总价
	public function num_ajax(){
		$price=floatval($_POST['price']);
		$num=floatval($_POST['num']);
		if($price>0 && $num>0){
			$money=bcmul($price*$num,1000000)/1000000;
			echo json_encode(array('status'=>0,'money'=>$money));exit();
		}
		echo json_encode(array('status'=>0,'money'=>0));exit();
	}
	
	//市场列表
	public function market_mas(){
		$market_arr=M('Trademarket')->field('name,img')->where("status=1")->select();
		foreach ($market_arr as $n=>$var){
			$market_arr[$n]['name2']=str_replace("_",'/',strtoupper($var['name']));
		}
		echo json_encode(array('status'=>1,'market_arr'=>$market_arr));exit();
	}
	
	//购买卖出+卖出购买
  	public function trade_ajax(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error("请先登录");
		}
		if(cookie('trade_ajax')=='trade_ajax'){
			$this->error("请勿频繁操作");
		}
		$my=M('user')->where(array('id'=>$userid))->find();
		if(empty($my)){
			$this->error("请先登录");
		}
		$mairu_id=$_POST['mairu_id'];
		if(!is_numeric($mairu_id)){
			$this->error("订单错误");
		}
		$marketfabi=M('TrademarketSet')->field('sta_fabi,sell_min,buy_min')->where("id=1")->find();
		$tradearr = M('TrademarketEntruy')->where('type=2 and id='.$mairu_id)->find();
		if(empty($tradearr)){
			$this->error("订单错误");
		}
		if($tradearr['status']!=2){
			$this->error("订单已交易或取消");
		}
		if($tradearr['userid']==$userid){
			$this->error("不能交易自己的订单");
		}
		$nums=$tradearr['num_zong']-$tradearr['num_deal'];
		$money_zong=bcmul($nums*$tradearr['price'],10000)/10000;
		$money=0;
		$num=0;
        $type_lx=$_POST['mairu_lx'];
        if($type_lx==1){
        	$money=$_POST['mairu_money'];
        	if($money==''){
        		$this->error("请输入买入金额");
        	}
        	if(!is_numeric($money)){
        		$this->error("买入金额错误");
        	}
        	if($money<=0){
        		$this->error("买入金额错误");
        	}
        	if($money_zong==$money){
        		$num=$nums;
        	}else{
        		$num=bcmul($money/$tradearr['price'],10000)/10000;
        	}
        	if($num<=0){
        		$this->error("交易数量错误");
        	}
	        if($num>$nums){
	        	$this->error("购买金额过大");
	        }
        }else{
        	$num=$_POST['mairu_num'];
        	if($num==''){
        		$this->error("请输入买入数量");
        	}
        	if(!is_numeric($num)){
        		$this->error("买入数量错误");
        	}
        	if($num<=0){
        		$this->error("买入数量错误");
        	}
        	$money=bcmul($num*$tradearr['price'],10000)/10000;
        	if($money_zong==$money){
        		$num=$nums;
        	}
        	if($money<=0){
        		$this->error("交易金额错误");
        	}
	        if($num>$nums){
	        	$this->error("购买数量过大");
	        }
        }
		$marketarr=M('Trademarket')->where("name='".$tradearr['market']."'")->find();
		
		if($marketarr['status']!=1){
			$this->error("市场已关闭");
		}
		if($marketarr['sta_sm']==1){
			if($my['idstate']!=2){
	          	//$this->error("请先实名认证");
	        }
		}
		if($marketarr['fenqu']=='cny'){
	        if($_POST['mairu_pay_1']!=1 && $_POST['mairu_pay_2']!=1 && $_POST['mairu_pay_3']!=1){
	        	$this->error("请选择付款方式");
	        }
	        if($_POST['mairu_pay_1']==1){
	        	if($tradearr['pay_1']!=1){
	        		$this->error("请选择正确的付款方式");
	        	}
	        	$user_bank=M('UserBank')->field('id')->where("status=1 and userid=".$userid)->find();
	        	if(empty($user_bank)){
	        		$this->error("请先设置银行卡信息");
	        	}
	        }
	        if($_POST['mairu_pay_2']==1){
	        	if($tradearr['pay_2']!=1){
	        		$this->error("请选择正确的付款方式");
	        	}
	        	$user_zfb=M('UserZfb')->field('id')->where("status=1 and userid=".$userid)->find();
	        	if(empty($user_zfb)){
	        		$this->error("请先设置支付宝信息");
	        	}
	        }
	        if($_POST['mairu_pay_3']==1){
	        	if($tradearr['pay_3']!=1){
	        		$this->error("请选择正确的付款方式");
	        	}
	        	$user_wx=M('UserWx')->field('id')->where("status=1 and userid=".$userid)->find();
	        	if(empty($user_wx)){
	        		$this->error("请先设置微信信息");
	        	}
	        }
		}else{
			$_POST['mairu_pay_1']=0;
			$_POST['mairu_pay_2']=0;
			$_POST['mairu_pay_3']=0;
		}
        
		$dqsjd=date("H",time());
        if($marketarr['time_start']!=0){
            if($dqsjd<$marketarr['time_start']){
                $this->error("交易时间还未开始");
        	}
        }
        if($marketarr['time_end']!=0){
            if($dqsjd>=$marketarr['time_end']){
                $this->error("交易时间已结束");
            }
        }
        //查询买方账户是否足够
        	$market_mas2=explode('_',$tradearr['market']);
	        $coin_1=$market_mas2[0];
	        $coin_2=$market_mas2[1];
	        $coin_1d=$coin_1.'d';
	        $coin_2d=$coin_2.'d';
	        $coin_1dx=strtoupper($coin_1);
	        $coin_2dx=strtoupper($coin_2);
        
        if($tradearr['num_min']>$num){
        	$this->error("最小交易数量".($tradearr['num_min']*1).$coin_1dx);
        }
        $fee=bcmul($num*$tradearr['fee_bl'],10000)/10000;
        if($tradearr['fenqu']=='cny'){
        	//$fee=0;
		}
        
        //计算扣除经验值
        $jyz_bili=$marketarr['fee_emp'];
        $jyz=bcmul($jyz_bili*$num,10000)/10000;
        
        $marketarrset=M('TrademarketSet')->where("id=1")->find();
        
        //查询送经验值数量
        $jyzhi_s=$marketarr['jyzhi_s']*$num;
        
        $arr=array();
        $arr['fenqu']=$tradearr['fenqu'];
        $arr['market']=$tradearr['market'];
        $arr['userid']=$userid;
        $arr['peerid']= $tradearr['userid'];
        $arr['sell_id']= $tradearr['id'];
        $arr['money']=$money;
        $arr['num']=$num;
        $arr['price']=$tradearr['price'];
        $arr['pay_1']=$_POST['mairu_pay_1'];
        $arr['pay_2']=$_POST['mairu_pay_2'];
        $arr['pay_3']=$_POST['mairu_pay_3'];
        $arr['time_add']=time();
        $arr['order_no']=$this->getorderno2();
        $arr['fee'] = $fee;
        $arr['jyzhi'] = $jyz;
        $arr['jyzhi_s'] = $jyzhi_s;
        $arr['time_fk_djs'] = time()+$marketarrset['time_fk']*60;
        if($marketarr['fenqu']=='cny'){
        	$arr['sta_type']=1;
        	$arr['sta_zf']=1;
        	try{
                $mo = M();
                $mo->startTrans();
        	    $rs[]=$id=$mo->table('tw_trademarket_entruy_log')->add($arr);
              	if($nums==$num){
					$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $mairu_id))->save(array('status'=>1,'num_deal'=>$tradearr['num_zong'],'time_ok'=>time()));
                }else{
                	$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $mairu_id))->save(array('num_deal'=>($tradearr['num_deal']+$num)));
                }
                if (check_arr($rs)) {
                    $mo->commit();
                    cookie('trade_ajax','trade_ajax',4);
                    
                    //修改当前价格
                    M('Trademarket')->where("name='".$tradearr['market']."'")->save(array('price'=>$tradearr['price'],'time_xg'=>time()));
                    
                    echo json_encode(array('status'=>1,'info'=>'购买成功,请及时付款','market'=>$tradearr['market']));exit();
                }else {
                  	$mo->rollback();
                	$this->error('购买失败');
                }
            }catch(\Think\Exception $e){
                $mo->rollback();
                $this->error('购买失败');
            }
        }else{
        	
        	$usercoin=M('UserCoin')->where('userid='.$userid)->find();
        	if($usercoin[$coin_2]<$money){
        		$this->error("账户".$coin_2dx."不足");
        	}
        	$usercoin_maichu=M('UserCoin')->where('userid='.$tradearr['userid'])->find();
        	$num_mc=$num+$fee;
        	if($usercoin_maichu[$coin_1d]<$num_mc){
        		$num_mc=$usercoin_maichu[$coin_1d];
        	}
        	$arr['sta_zf']=3;
        	try{
                $mo = M();
                $mo->startTrans();
        	    $rs[]=$id=$mo->table('tw_trademarket_entruy_log')->add($arr);
        	    $rs[]=$mo->table('tw_user_coin')->where('userid='.$userid)->save(array($coin_2=>($usercoin[$coin_2]-$money),$coin_1=>($usercoin[$coin_1]+$num),'jyzhi'=>($usercoin['jyzhi']+$arr['jyzhi_s'])));
        	    $rs[]=$mo->table('tw_user_coin')->where('userid='.$tradearr['userid'])->save(array($coin_2=>($usercoin_maichu[$coin_2]+$money),$coin_1d=>($usercoin_maichu[$coin_1d]-$num_mc)));
              	if($nums==$num){
					$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $mairu_id))->save(array('status'=>1,'num_deal'=>$tradearr['num_zong'],'time_ok'=>time()));
                }else{
                	$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $mairu_id))->save(array('num_deal'=>($tradearr['num_deal']+$num)));
                }
                if (check_arr($rs)) {
                    $mo->commit();
                    cookie('trade_ajax','trade_ajax',4);
                    //账户明细
                    M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_2,'coinname'=>$coin_2dx,'fee'=>$money,'num'=>$usercoin[$coin_2],'mum'=>($usercoin[$coin_2]-$money),'type'=>0,'name'=>'trade_ajax','remark'=>'买币','protypemas'=>'买币','addtime'=>time(),'protype'=>3,'status'=>1));
                    M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_1,'coinname'=>$coin_1dx,'fee'=>$num,'num'=>$usercoin[$coin_1],'mum'=>($usercoin[$coin_1]+$num),'type'=>1,'name'=>'trade_ajax','remark'=>'买币','protypemas'=>'买币','addtime'=>time(),'protype'=>3,'status'=>1));
                    M('Finance')->add(array('userid'=>$tradearr['userid'],'coin'=>$coin_2,'coinname'=>$coin_2dx,'fee'=>$money,'num'=>$usercoin_maichu[$coin_2],'mum'=>($usercoin_maichu[$coin_2]+$money),'type'=>1,'name'=>'trade_ajax','remark'=>'卖币','protypemas'=>'卖币','addtime'=>time(),'protype'=>3,'status'=>1));
                    //修改当前价格
                    if($arr['jyzhi_s']>0){
						M('Finance')->add(array('userid'=>$userid,'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$arr['jyzhi_s'],'num'=>$usercoin['jyzhi'],'mum'=>($usercoin['jyzhi']+$arr['jyzhi_s']),'type'=>1,'name'=>'trade_yz','remark'=>'买币赠送','protypemas'=>'买币赠送','addtime'=>time(),'protype'=>3,'status'=>1));
						$this->level_user_gx($userid);
					}
                    M('Trademarket')->where("name='".$tradearr['market']."'")->save(array('price'=>$tradearr['price'],'time_xg'=>time()));
                    $this->level_user_gx($tradearr['userid']);
                    echo json_encode(array('status'=>1,'info'=>'购买成功','market'=>$tradearr['market']));exit();
                }else {
                  	$mo->rollback();
                	$this->error('购买失败');
                }
            }catch(\Think\Exception $e){
                $mo->rollback();
                $this->error('购买失败');
            }
        }
	}
	
	
	public function level_user_gx($userid){
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
	
	//生成订单号
  	private function getorderno2(){
		$order_buy_no = M('TrademarketEntruyLog')->max('order_no');
		if(strlen($order_buy_no) == 8){			
			return ++$order_buy_no;
		}else{
			return '10000000';
		}
	}
	
	
	
	//购买卖出+卖出购买
  	public function mctrade_ajax(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error("请先登录");
		}
		if(cookie('mctrade_ajax')=='mctrade_ajax'){
			$this->error("请勿频繁操作");
		}
		$my=M('user')->where(array('id'=>$userid))->find();
		if(empty($my)){
			$this->error("请先登录");
		}
		$maichu_id=$_POST['maichu_id'];
		if(!is_numeric($maichu_id)){
			$this->error("订单错误");
		}
		
		$tradearr = M('TrademarketEntruy')->where('type=1 and id='.$maichu_id)->find();
		if(empty($tradearr)){
			$this->error("订单错误");
		}
		if($tradearr['status']!=2){
			$this->error("订单已交易或取消");
		}
		if($tradearr['userid']==$userid){
			$this->error("不能交易自己的订单");
		}
		
		
		$marketfabi=M('TrademarketSet')->field('sta_fabi,sell_min,buy_min')->where("id=1")->find();
		if($tradearr['fenqu']=='cny'){
			if($marketfabi['sta_fabi']==0){
				if($my['level_user']==1){
					$this->error("Lv0会员不能卖出");
				}
			}
		}else{
			if($my['level_user']==1){
				$this->error("Lv0会员不能卖出");
			}
		}
		
		
		
		$nums=$tradearr['num_zong']-$tradearr['num_deal'];
		$money_zong=bcmul($nums*$tradearr['price'],10000)/10000;
		$money=0;
		$num=0;
        $type_lx=$_POST['maichu_lx'];
        if($type_lx==1){
        	$money=$_POST['maichu_money'];
        	if($money==''){
        		$this->error("请输入卖出金额");
        	}
        	if(!is_numeric($money)){
        		$this->error("卖出金额错误");
        	}
        	if($money<=0){
        		$this->error("卖出金额错误");
        	}
        	if($money_zong==$money){
        		$num=$nums;
        	}else{
        		$num=bcmul($money/$tradearr['price'],10000)/10000;
        	}
        	if($num<=0){
        		$this->error("交易数量错误");
        	}
	        if($num>$nums){
	        	$this->error("卖出金额过大");
	        }
        }else{
        	$num=$_POST['maichu_num'];
        	if($num==''){
        		$this->error("请输入卖出数量");
        	}
        	if(!is_numeric($num)){
        		$this->error("卖出数量错误");
        	}
        	if($num<=0){
        		$this->error("卖出数量错误");
        	}
        	$money=bcmul($num*$tradearr['price'],10000)/10000;
        	if($money_zong==$money){
        		$num=$nums;
        	}
        	if($money<=0){
        		$this->error("交易金额错误");
        	}
	        if($num>$nums){
	        	$this->error("卖出数量过大");
	        }
        }
		$marketarr=M('Trademarket')->where("name='".$tradearr['market']."'")->find();
		if($marketarr['status']!=1){
			$this->error("市场已关闭");
		}
		if($marketarr['sta_sm']==1){
			if($my['idstate']!=2){
	          	//$this->error("请先实名认证");
	        }
		}
		if($marketarr['fenqu']=='cny'){
	        if($_POST['maichu_pay_1']!=1 && $_POST['maichu_pay_2']!=1 && $_POST['maichu_pay_3']!=1){
	        	$this->error("请选择收款方式");
	        }
	        if($_POST['maichu_pay_1']==1){
	        	if($tradearr['pay_1']!=1){
	        		//$this->error("请选择正确的收款方式");
	        	}
	        	$user_bank=M('UserBank')->field('id')->where("status=1 and userid=".$userid)->find();
	        	if(empty($user_bank)){
	        		$this->error("请先设置银行卡信息");
	        	}
	        }
	        if($_POST['maichu_pay_2']==1){
	        	if($tradearr['pay_2']!=1){
	        		//$this->error("请选择正确的收款方式");
	        	}
	        	$user_zfb=M('UserZfb')->field('id')->where("status=1 and userid=".$userid)->find();
	        	if(empty($user_zfb)){
	        		$this->error("请先设置支付宝信息");
	        	}
	        }
	        if($_POST['maichu_pay_3']==1){
	        	if($tradearr['pay_3']!=1){
	        		//$this->error("请选择正确的收款方式");
	        	}
	        	$user_wx=M('UserWx')->field('id')->where("status=1 and userid=".$userid)->find();
	        	if(empty($user_wx)){
	        		$this->error("请先设置微信信息");
	        	}
	        }
		}else{
			$_POST['maichu_pay_1']=0;
			$_POST['maichu_pay_2']=0;
			$_POST['maichu_pay_3']=0;
		}
		$dqsjd=date("H",time());
        if($marketarr['time_start']!=0){
            if($dqsjd<$marketarr['time_start']){
                $this->error("交易时间还未开始");
        	}
        }
        if($marketarr['time_end']!=0){
            if($dqsjd>=$marketarr['time_end']){
                $this->error("交易时间已结束");
            }
        }
        
        
        	//查询卖方账户是否足够
        	$market_mas2=explode('_',$tradearr['market']);
	        $coin_1=$market_mas2[0];
	        $coin_2=$market_mas2[1];
	        $coin_1d=$coin_1.'d';
	        $coin_2d=$coin_2.'d';
	        $coin_1dx=strtoupper($coin_1);
	        $coin_2dx=strtoupper($coin_2);
	        
        
        if($tradearr['num_min']>$num){
        	$this->error("最小交易数量".($tradearr['num_min']*1).$coin_1dx);
        }
        
        //固定手续费
        $userlevel=M('LevelUser')->field('fee_mc,trade_cs,trade_num')->where('id='.$my['level_user'])->find();
        if($tradearr['fenqu']!='cny'){
        	if($userlevel['trade_cs']>0){
				$yjy_num1=M('TrademarketEntruy')->field('id')->where('userid='.$userid.' and type=2 and status>0')->count();
				$yjy_num2=M('TrademarketEntruyLog')->field('id')->where('peerid='.$userid.' and sta_zf!=4')->count();
				$yjy_num=$yjy_num1+$yjy_num2;
				if($yjy_num>=$userlevel['trade_cs']){
					$this->error('已达到限制次数');
				}
			}
			if($userlevel['trade_num']>0){
				$yjy_num1=M('TrademarketEntruy')->field('num_zong')->where('userid='.$userid.' and type=2 and status>0')->sum('num_zong');
				$yjy_num2=M('TrademarketEntruyLog')->field('num')->where('peerid='.$userid.' and sta_zf!=4')->sum('num');
				$yjy_num=$yjy_num1+$yjy_num2;
				if($yjy_num>=$userlevel['trade_num']){
					$this->error('已达到限制数量');
				}
				if(($yjy_num+$num)>$userlevel['trade_num']){
					$this->error('数量过大，累计可卖出'.($userlevel['trade_num']*1));
				}
			}
        }	
        $fee_bili=$userlevel['fee_mc'];
        $fee=bcmul(($num*$fee_bili),10000)/10000;
        if($marketarr['sta_fee']==0){
        	$fee=0;
		}
        
        //经验值
        $jyz_bili=$marketarr['fee_emp'];
        $jyz=bcmul($num*$jyz_bili,1000)/1000;
        
        $marketarrset=M('TrademarketSet')->where("id=1")->find();
        
        //查询送经验值数量
        $jyzhi_s=$marketarr['jyzhi_s']*$num;
        
        $arr=array();
        $arr['fenqu']=$tradearr['fenqu'];
        $arr['market']=$tradearr['market'];
        $arr['userid']=$tradearr['userid'];
        $arr['peerid']= $userid;
        $arr['buy_id']= $tradearr['id'];
        $arr['money']=$money;
        $arr['num']=$num;
        $arr['type']=0;
        $arr['price']=$tradearr['price'];
        $arr['pay_1']=$_POST['maichu_pay_1'];
        $arr['pay_2']=$_POST['maichu_pay_2'];
        $arr['pay_3']=$_POST['maichu_pay_3'];
        $arr['time_add']=time();
        $arr['order_no']=$this->getorderno2();
        $arr['fee'] = $fee;
        $arr['jyzhi'] = $jyz;
        $arr['jyzhi_s'] = $jyzhi_s;
        $arr['time_fk_djs'] = time()+$marketarrset['time_fk']*60;
        if($marketarr['fenqu']=='cny'){
        	$arr['sta_type']=1;
        	$arr['sta_zf']=1;
        	$usercoin=M('UserCoin')->where('userid='.$userid)->find();
        	if($usercoin[$coin_1]<($num+$fee)){
        		$this->error("账户".$coin_1dx."不足");
        	}
        	if($usercoin['jyzhi']<$jyz){
        		$this->error("账户经验值不足");
        	}
        	
        	$usercoin_mairu=M('UserCoin')->where('userid='.$tradearr['userid'])->find();
        	$money_mc=$money;
        	if($usercoin_mairu[$coin_2d]<$money){
        		$money_mc=$usercoin_mairu[$coin_2d];
        	}
        	try{
                $mo = M();
                $mo->startTrans();
        	    $rs[]=$id=$mo->table('tw_trademarket_entruy_log')->add($arr);
        	    $rs[]=$mo->table('tw_user_coin')->where('userid='.$userid)->save(array($coin_1=>($usercoin[$coin_1]-$num-$fee),$coin_1d=>($usercoin[$coin_1d]+$num+$fee),'jyzhi'=>($usercoin['jyzhi']-$jyz)));
              	if($nums==$num){
					$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $maichu_id))->save(array('status'=>1,'num_deal'=>$tradearr['num_zong'],'time_ok'=>time()));
                }else{
                	$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $maichu_id))->save(array('num_deal'=>($tradearr['num_deal']+$num)));
                }
                if (check_arr($rs)) {
                    $mo->commit();
                    cookie('trade_ajax','trade_ajax',4);
                    //修改当前价格
                    M('Trademarket')->where("name='".$tradearr['market']."'")->save(array('price'=>$tradearr['price'],'time_xg'=>time()));
                    M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_1,'coinname'=>$coin_1dx,'fee'=>$num,'num'=>$usercoin[$coin_1],'mum'=>($usercoin[$coin_1]-$num),'type'=>0,'name'=>'trade_ajax','remark'=>'卖币','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    if($fee>0){
                    	M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_1,'coinname'=>$coin_1dx,'fee'=>$fee,'num'=>($usercoin[$coin_1]-$num),'mum'=>($usercoin[$coin_1]-$num-$fee),'type'=>0,'name'=>'trade_ajax','remark'=>'卖币【手续费】','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    }
                    if($jyz>0){
                    	M('Finance')->add(array('userid'=>$userid,'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$jyz,'num'=>$usercoin['jyzhi'],'mum'=>($usercoin['jyzhi']-$jyz),'type'=>0,'name'=>'trade_ajax','remark'=>'卖币','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    }
                    $this->level_user_gx($userid);
                    
                    //给买家发送信息
                    $user_mairu=M('User')->field('mobile')->where('id='.$tradearr['userid'])->find();
                    if($user_mairu['mobile']){
                    	if($marketarrset['duanxin_mr']){
	                    	$config = M('Config')->field('smsname,smspass,smsqm')->where(array('id' => 1))->find();
	                    	$content = $marketarrset['duanxin_mr'];
	                    	$sign = "【".$config['smsqm']."】";
					  		$smsapi = "http://api.smsbao.com/";
					 		$user = $config['smsname']; //短信平台帐号
							$pass = md5($config['smspass']); //短信平台密码
					  		$content = $sign.$content;
					 		$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$user_mairu['mobile']."&c=".urlencode($content);
					 		$result =file_get_contents($sendurl);
                    	}
                    }
                    
                    echo json_encode(array('status'=>1,'info'=>'卖出成功，请等待买家付款','market'=>$tradearr['market']));exit();
                }else {
                  	$mo->rollback();
                	$this->error('卖出失败');
                }
            }catch(\Think\Exception $e){
                $mo->rollback();
                $this->error('卖出失败');
            }
        }else{
        	//查询卖方账户是否足够
        	$market_mas2=explode('_',$tradearr['market']);
	        $coin_1=$market_mas2[0];
	        $coin_2=$market_mas2[1];
	        $coin_1d=$coin_1.'d';
	        $coin_2d=$coin_2.'d';
	        $coin_1dx=strtoupper($coin_1);
	        $coin_2dx=strtoupper($coin_2);
        	$usercoin=M('UserCoin')->where('userid='.$userid)->find();
        	
        	if($usercoin[$coin_1]<($num+$fee)){
        		$this->error("账户".$coin_1dx."不足");
        	}
        	if($usercoin['jyzhi']<$jyz){
        		$this->error("账户经验值不足");
        	}
        	$usercoin_mairu=M('UserCoin')->where('userid='.$tradearr['userid'])->find();
        	$money_mc=$money;
        	if($usercoin_mairu[$coin_2d]<$money){
        		$money_mc=$usercoin_mairu[$coin_2d];
        	}
        	$arr['sta_zf']=3;
        	try{
                $mo = M();
                $mo->startTrans();
        	    $rs[]=$id=$mo->table('tw_trademarket_entruy_log')->add($arr);
        	    $rs[]=$mo->table('tw_user_coin')->where('userid='.$userid)->save(array($coin_2=>($usercoin[$coin_2]+$money),$coin_1=>($usercoin[$coin_1]-$num-$fee),'jyzhi'=>($usercoin['jyzhi']-$jyz)));
        	    $rs[]=$mo->table('tw_user_coin')->where('userid='.$tradearr['userid'])->save(array($coin_2d=>($usercoin_mairu[$coin_2d]-$money_mc),$coin_1=>($usercoin_mairu[$coin_1]+$num),'jyzhi'=>($usercoin_mairu['jyzhi']+$arr['jyzhi_s'])));
              	if($nums==$num){
					$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $maichu_id))->save(array('status'=>1,'num_deal'=>$tradearr['num_zong'],'time_ok'=>time()));
                }else{
                	$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $maichu_id))->save(array('num_deal'=>($tradearr['num_deal']+$num)));
                }
                if (check_arr($rs)) {
                    $mo->commit();
                    cookie('trade_ajax','trade_ajax',4);
                    //账户明细
                    M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_2,'coinname'=>$coin_2dx,'fee'=>$money,'num'=>$usercoin[$coin_2],'mum'=>($usercoin[$coin_2]+$money),'type'=>1,'name'=>'trade_ajax','remark'=>'卖币','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_1,'coinname'=>$coin_1dx,'fee'=>$num,'num'=>$usercoin[$coin_1],'mum'=>($usercoin[$coin_1]-$num),'type'=>0,'name'=>'trade_ajax','remark'=>'卖币','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    if($fee>0){
                    	M('Finance')->add(array('userid'=>$userid,'coin'=>$coin_1,'coinname'=>$coin_1dx,'fee'=>$fee,'num'=>($usercoin[$coin_1]-$num),'mum'=>($usercoin[$coin_1]-$num-$fee),'type'=>0,'name'=>'trade_ajax','remark'=>'卖币【手续费】','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    }
                    if($jyz>0){
                    	M('Finance')->add(array('userid'=>$userid,'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$jyz,'num'=>$usercoin['jyzhi'],'mum'=>($usercoin['jyzhi']-$jyz),'type'=>0,'name'=>'trade_ajax','remark'=>'卖币','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    }
                    M('Finance')->add(array('userid'=>$tradearr['userid'],'coin'=>$coin_1,'coinname'=>$coin_1dx,'fee'=>$num,'num'=>$usercoin_mairu[$coin_1],'mum'=>($usercoin_mairu[$coin_1]+$num),'type'=>1,'name'=>'trade_ajax','remark'=>'买币','protypemas'=>'买币','addtime'=>time(),'protype'=>4,'status'=>1));
                    //修改当前价格
                    if($arr['jyzhi_s']>0){
						M('Finance')->add(array('userid'=>$tradearr['userid'],'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$arr['jyzhi_s'],'num'=>$usercoin_mairu['jyzhi'],'mum'=>($usercoin_mairu['jyzhi']+$arr['jyzhi_s']),'type'=>1,'name'=>'trade_yz','remark'=>'买币赠送','protypemas'=>'买币赠送','addtime'=>time(),'protype'=>4,'status'=>1));
						$this->level_user_gx($tradearr['userid']);
					}
                    M('Trademarket')->where("name='".$tradearr['market']."'")->save(array('price'=>$tradearr['price'],'time_xg'=>time()));
                    $this->level_user_gx($userid);
                    echo json_encode(array('status'=>1,'info'=>'卖出成功','market'=>$tradearr['market']));exit();
                }else {
                  	$mo->rollback();
                	$this->error('卖出失败');
                }
            }catch(\Think\Exception $e){
                $mo->rollback();
                $this->error('卖出失败');
            }
        }
	}
	
  	//法币--申诉中
  	public function advertising_5(){
  		$userid=userid();
  		if(!is_numeric($userid)){
  			$this->error('请先登录');
  		}
  		$user=M('user')->where("id=".$userid)->find();
  		$where = '(sta_su_mr=1 or sta_su_mc=1) and sta_zf in (1,2)';
        $market=$_GET['market'];
		if($market){
			if($market!=3){
				if(!preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
					$where .= " and market='".$market."'";
				}
			}
		}
		$type=$_GET['type'];
		if($type==1){
			$where .= " and userid=".$userid;
		}else if($type==2){
			$where .= " and peerid=".$userid;
		}else{
			$where .= " and (peerid=".$userid.' or userid='.$userid.')';
		}
      	$page=@$_GET['page'];
      	if($page=='' || !is_numeric($page)){
      		$this->error('暂无数据');
		}
      	$pages=($page-1)*10;
      	$list = M('TrademarketEntruyLog')->where($where)->order('id desc')->limit($pages,10)->select();
      	foreach ($list as $key => $vv) {
      		$list[$key]['sta_mas']='申诉中';
			$shichang=strtoupper($vv['market']);
			$shichang2=explode("_",$shichang);
			$list[$key]['coin_1']=$shichang2[0];
			$list[$key]['coin_2']=$shichang2[1];
			if($vv['userid']==$userid){
				$list[$key]['ztype_mas']='buy';
				$list[$key]['ztype_mas2']='buy2';
				$list[$key]['type_mas']='买';
				$list[$key]['type_url']='transaction/details_mr?id='.$vv['id'];
			}else{
				$list[$key]['ztype_mas']='sell';
				$list[$key]['ztype_mas2']='buy';
				$list[$key]['type_mas']='卖';
				$list[$key]['type_url']='transaction/details_mc?id='.$vv['id'];
			}
			$list[$key]['sk_img']='';
          	if($vv['pay_1']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/card.png" alt=""></span>';
            }
          	if($vv['pay_2']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/zfb.png" alt=""></span>';
            }
          	if($vv['pay_3']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/wx-wx.png" alt=""></span>';
            }
            if($vv['fenqu']!='cny'){
            	$coinimg=M('Coin')->field('img')->where("name='".$vv['fenqu']."'")->find();
            	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Upload/coin/'.$coinimg['img'].'" alt=""></span>';
            }
            $list[$key]['price']=$vv['price']*1;
            $list[$key]['num']=$vv['num']*1;
            $list[$key]['money']=$vv['money']*1;
      	}
     	echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
  	//法币--已取消
  	public function advertising_4(){
  		$userid=userid();
  		if(!is_numeric($userid)){
  			$this->error('请先登录');
  		}
  		$user=M('user')->where("id=".$userid)->find();
  		$where = 'sta_su_mr=0 and sta_su_mc=0 and sta_zf=4';
        $market=$_GET['market'];
		if($market){
			if($market!=3){
				if(!preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
					$where .= " and market='".$market."'";
				}
			}
		}
		$type=$_GET['type'];
		if($type==1){
			$where .= " and userid=".$userid;
		}else if($type==2){
			$where .= " and peerid=".$userid;
		}else{
			$where .= " and (peerid=".$userid.' or userid='.$userid.')';
		}
      	$page=@$_GET['page'];
      	if($page=='' || !is_numeric($page)){
      		$this->error('暂无数据');
		}
      	$pages=($page-1)*10;
      	$list = M('TrademarketEntruyLog')->where($where)->order('id desc')->limit($pages,10)->select();
      	foreach ($list as $key => $vv) {
      		$list[$key]['sta_mas']='已取消';
			$shichang=strtoupper($vv['market']);
			$shichang2=explode("_",$shichang);
			$list[$key]['coin_1']=$shichang2[0];
			$list[$key]['coin_2']=$shichang2[1];
			if($vv['userid']==$userid){
				$list[$key]['ztype_mas']='buy';
				$list[$key]['ztype_mas2']='buy2';
				$list[$key]['type_mas']='买';
				$list[$key]['type_url']='transaction/details_mr?id='.$vv['id'];
			}else{
				$list[$key]['ztype_mas']='sell';
				$list[$key]['ztype_mas2']='buy';
				$list[$key]['type_mas']='卖';
				$list[$key]['type_url']='transaction/details_mc?id='.$vv['id'];
			}
			$list[$key]['sk_img']='';
          	if($vv['pay_1']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/card.png" alt=""></span>';
            }
          	if($vv['pay_2']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/zfb.png" alt=""></span>';
            }
          	if($vv['pay_3']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/wx-wx.png" alt=""></span>';
            }
            if($vv['fenqu']!='cny'){
            	$coinimg=M('Coin')->field('img')->where("name='".$vv['fenqu']."'")->find();
            	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Upload/coin/'.$coinimg['img'].'" alt=""></span>';
            }
            $list[$key]['price']=$vv['price']*1;
            $list[$key]['num']=$vv['num']*1;
            $list[$key]['money']=$vv['money']*1;
      	}
     	echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
  	//法币--已完成
  	public function advertising_3(){
  		$userid=userid();
  		if(!is_numeric($userid)){
  			$this->error('请先登录');
  		}
  		$user=M('user')->where("id=".$userid)->find();
  		$where = 'sta_su_mr=0 and sta_su_mc=0 and sta_zf=3';
        $market=$_GET['market'];
		if($market){
			if($market!=3){
				if(!preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
					$where .= " and market='".$market."'";
				}
			}
		}
		$type=$_GET['type'];
		if($type==1){
			$where .= " and userid=".$userid;
		}else if($type==2){
			$where .= " and peerid=".$userid;
		}else{
			$where .= " and (peerid=".$userid.' or userid='.$userid.')';
		}
      	$page=@$_GET['page'];
      	if($page=='' || !is_numeric($page)){
      		$this->error('暂无数据');
		}
      	$pages=($page-1)*10;
      	$list = M('TrademarketEntruyLog')->where($where)->order('id desc')->limit($pages,10)->select();
      	foreach ($list as $key => $vv) {
      		$list[$key]['sta_mas']='已完成';
			$shichang=strtoupper($vv['market']);
			$shichang2=explode("_",$shichang);
			$list[$key]['coin_1']=$shichang2[0];
			$list[$key]['coin_2']=$shichang2[1];
			if($vv['userid']==$userid){
				$list[$key]['ztype_mas']='buy';
				$list[$key]['ztype_mas2']='buy2';
				$list[$key]['type_mas']='买';
				$list[$key]['type_url']='transaction/details_mr?id='.$vv['id'];
			}else{
				$list[$key]['ztype_mas']='sell';
				$list[$key]['ztype_mas2']='buy';
				$list[$key]['type_mas']='卖';
				$list[$key]['type_url']='transaction/details_mc?id='.$vv['id'];
			}
			$list[$key]['sk_img']='';
          	if($vv['pay_1']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/card.png" alt=""></span>';
            }
          	if($vv['pay_2']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/zfb.png" alt=""></span>';
            }
          	if($vv['pay_3']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/wx-wx.png" alt=""></span>';
            }
            if($vv['fenqu']!='cny'){
            	$coinimg=M('Coin')->field('img')->where("name='".$vv['fenqu']."'")->find();
            	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Upload/coin/'.$coinimg['img'].'" alt=""></span>';
            }
            $list[$key]['price']=$vv['price']*1;
            $list[$key]['num']=$vv['num']*1;
            $list[$key]['money']=$vv['money']*1;
      	}
     	echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
  	//法币--交易中
  	public function advertising_2(){
  		$userid=userid();
  		if(!is_numeric($userid)){
  			$this->error('请先登录');
  		}
  		$user=M('user')->where("id=".$userid)->find();
  		$where = 'sta_su_mr=0 and sta_su_mc=0 and sta_zf in (1,2)';
        $market=$_GET['market'];
		if($market){
			if($market!=3){
				if(!preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
					$where .= " and market='".$market."'";
				}
			}
		}
		$type=$_GET['type'];
		if($type==1){
			$where .= " and userid=".$userid;
		}else if($type==2){
			$where .= " and peerid=".$userid;
		}else{
			$where .= " and (peerid=".$userid.' or userid='.$userid.')';
		}
      	$page=@$_GET['page'];
      	if($page=='' || !is_numeric($page)){
      		$this->error('暂无数据');
		}
      	$pages=($page-1)*10;
      	$sta_mas=array('1'=>'未付款','2'=>'已付款');
      	$list = M('TrademarketEntruyLog')->where($where)->order('id desc')->limit($pages,10)->select();
      	foreach ($list as $key => $vv) {
      		$list[$key]['sta_mas']=$sta_mas[$vv['sta_zf']];
			$shichang=strtoupper($vv['market']);
			$shichang2=explode("_",$shichang);
			$list[$key]['coin_1']=$shichang2[0];
			$list[$key]['coin_2']=$shichang2[1];
			if($vv['userid']==$userid){
				$list[$key]['ztype_mas']='buy';
				$list[$key]['ztype_mas2']='buy2';
				$list[$key]['type_mas']='买';
				$list[$key]['type_url']='transaction/details_mr?id='.$vv['id'];
			}else{
				$list[$key]['ztype_mas']='sell';
				$list[$key]['ztype_mas2']='buy';
				$list[$key]['type_mas']='卖';
				$list[$key]['type_url']='transaction/details_mc?id='.$vv['id'];
			}
			$list[$key]['sk_img']='';
          	if($vv['pay_1']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/card.png" alt=""></span>';
            }
          	if($vv['pay_2']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/zfb.png" alt=""></span>';
            }
          	if($vv['pay_3']==1){
              	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/wx-wx.png" alt=""></span>';
            }
            if($vv['fenqu']!='cny'){
            	$coinimg=M('Coin')->field('img')->where("name='".$vv['fenqu']."'")->find();
            	$list[$key]['sk_img'].='<span><img style="width:0.4rem;" src="/Upload/coin/'.$coinimg['img'].'" alt=""></span>';
            }
            $list[$key]['price']=$vv['price']*1;
            $list[$key]['num']=$vv['num']*1;
            $list[$key]['money']=$vv['money']*1;
      	}
     	echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
	//广告列表
	public function advertising_1(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		$where= 'status=2 and userid='.$userid ;
		$market=$_GET['market'];
		if($market){
			if($market!=3){
				if(!preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
					$where .= " and market='".$market."'";
				}
			}
		}
		$type=$_GET['type'];
		if($type==1 || $type==2){
			$where .= " and type=".$type;
		}
		$page=@$_GET['page'];
		$pages=($page-1)*10;
		$list = M('TrademarketEntruy')->where($where)->order('id desc')->limit($pages,10)->select();
		foreach ($list as $k=>$v){
			$shichang=strtoupper($v['market']);
			$shichang2=explode("_",$shichang);
			$list[$k]['coin_1']=$shichang2[0];
			$list[$k]['coin_2']=$shichang2[1];
			
			if($v['type'] == 2){
				$list[$k]['ztype_mas']='sell';
				$list[$k]['ztype_mas2']='buy';
				$list[$k]['type_mas']='卖';
			}else{
				$list[$k]['ztype_mas']='buy';
				$list[$k]['ztype_mas2']='buy2';
				$list[$k]['type_mas']='买';
			}
			$list[$k]['sk_img'] ='';
			if($list[$k]['pay_1']==1){
				$list[$k]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/card.png" alt=""></span>';
			}
			if($list[$k]['pay_2']==1){
				$list[$k]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/zfb.png" alt=""></span>';
			}
			if($list[$k]['pay_3']==1){
				$list[$k]['sk_img'].='<span><img style="width:0.4rem;" src="/Public/images/wx-wx.png" alt=""></span>';
			}
            if($v['fenqu']!='cny'){
            	$coinimg=M('Coin')->field('img')->where("name='".$v['fenqu']."'")->find();
            	$list[$k]['sk_img'].='<span><img style="width:0.4rem;" src="/Upload/coin/'.$coinimg['img'].'" alt=""></span>';
            }
			$list[$k]['price']=$v['price']*1;
			$list[$k]['num']=$list[$k]['num_zong']-$list[$k]['num_deal'];
			$list[$k]['money']=($list[$k]['num']*$list[$k]['price']).$list[$k]['coin_2'];
		}
		echo json_encode(array('status'=>1,'info'=>'获取成功','data'=>$list));exit();
	}
	
	//分区列表
	public function fenqu_list(){
		$market_moren=M('TrademarketSet')->field('fenqu')->where("id=1")->find();
		$fenqu=explode('|',$market_moren['fenqu']);
		$market_list=M('Trademarket')->field('name,price')->where("fenqu='".$fenqu[0]."'")->select();
		foreach ($market_list as $n=>$var){
			$namexx=explode("_",strtoupper($var['name']));
			$market_list[$n]['coin_1']=$namexx[0];
			$market_list[$n]['coin_2']=$namexx[1];
		}
		echo json_encode(array('status'=>1,'fenqu'=>$fenqu,'market_list'=>$market_list));exit();
	}
	
	//分区列表2
	public function fenqu_list2(){
		$fenqu=$_GET['fenqu'];
		if(!preg_match("/^[a-zA-Z\s]+$/",$fenqu)){
			echo json_encode(array('status'=>0,'market_list'=>array()));exit();
		}
		$market_list=M('Trademarket')->field('name,price')->where("fenqu='".$fenqu."'")->select();
		foreach ($market_list as $n=>$var){
			$namexx=explode("_",strtoupper($var['name']));
			$market_list[$n]['coin_1']=$namexx[0];
			$market_list[$n]['coin_2']=$namexx[1];
		}
		echo json_encode(array('status'=>1,'market_list'=>$market_list));exit();
	}
	
	//买入联动
	public function mas_liandong(){
  		$userid=userid();
      	if (!is_numeric($userid)) {
			$this->error('请先登录');
		}
		$price=$_POST['price'];
		if(!is_numeric($price)){
			$this->error('价格错误');
		}
		if($price<=0){
			$this->error('价格错误');
		}
    	$type=$_POST['type'];
    	if($type==1){
    		$money=$_POST['money'];
    		if(!is_numeric($money)){
    			$this->error('金额错误');
    		}
    		$num_zx=intval($money/$price*10000)/10000;
    		echo json_encode(array('status'=>1,'num'=>$num_zx,'money'=>$money));exit();
    	}else{
    		$num=$_POST['num'];
    		if(!is_numeric($num)){
    			$this->error('数量错误');
    		}
    		$money_zx=intval($num*$price*10000)/10000;
    		echo json_encode(array('status'=>1,'num'=>$num,'money'=>$money_zx));exit();
    	}
	}
	
  	//交易-购买卖币
  	public function advdetail(){
  		$userid=userid();
      	if (!is_numeric($userid)) {
			$this->error('请先登录');
		}
    	$id=$_POST['id'];
    	if(!is_numeric($id)){
    		$this->error('参数错误');
    	}
    	$record = M('TrademarketEntruy')->where("type=2 and id=".$id)->find();
      	if(empty($record)){
            $this->error('参数错误');
        }
        if($record['status']!=2){
        	$this->error('订单已处理');
        }
        $market_mas=strtoupper($record['market']);
        $market_mas2=explode('_',$market_mas);
        $record['market_mas']=$market_mas2[0].'/'.$market_mas2[1];
        $record['coin_1']=$market_mas2[0];
        $record['coin_2']=$market_mas2[1];
        $record['num']=$record['num_zong']-$record['num_deal'];
        $record['money']=$record['num']*$record['price'];
        $pay_method=0;
        $record['pay_method_mas']='';
        if($record['pay_1']==1){
        	$pay_method=1;
        }else if($record['pay_2']==1){
        	$pay_method=2;
        }else if($record['pay_3']==1){
        	$pay_method=3;
        }
        if($record['pay_1']==1){
        	if($pay_method==1){
        		$record['pay_method_mas'].='<span onclick="mairu_xz(1)" class="active">银行卡</span>';
        	}else{
        		$record['pay_method_mas'].='<span onclick="mairu_xz(1)">银行卡</span>';
        	}
        }
        if($record['pay_2']==1){
        	if($pay_method==2){
        		$record['pay_method_mas'].='<span onclick="mairu_xz(2)" class="active">支付宝</span>';
        	}else{
        		$record['pay_method_mas'].='<span onclick="mairu_xz(2)">支付宝</span>';
        	}
        }
        if($record['pay_3']==1){
        	if($pay_method==3){
        		$record['pay_method_mas'].='<span onclick="mairu_xz(3)" class="active">微信</span>';
        	}else{
        		$record['pay_method_mas'].='<span onclick="mairu_xz(3)">微信</span>';
        	}
        }
        $record['pay_method']=$pay_method;
      	echo json_encode(array('status'=>1,'msg'=>$record,'pay_method'=>$pay_method));exit();
    }
    
  	//交易-购买卖币
  	public function advdetail_sell(){
  		$userid=userid();
      	if (!is_numeric($userid)) {
			$this->error('请先登录');
		}
    	$id=$_POST['id'];
    	if(!is_numeric($id)){
    		$this->error('参数错误');
    	}
    	$record = M('TrademarketEntruy')->where("type=1 and id=".$id)->find();
      	if(empty($record)){
            $this->error('参数错误');
        }
        if($record['status']!=2){
        	$this->error('订单已处理');
        }
        $market_mas=strtoupper($record['market']);
        $market_mas2=explode('_',$market_mas);
        $record['market_mas']=$market_mas2[0].'/'.$market_mas2[1];
        $record['coin_1']=$market_mas2[0];
        $record['coin_2']=$market_mas2[1];
        $record['num']=$record['num_zong']-$record['num_deal'];
        $record['money']=$record['num']*$record['price'];
        //查询当前账户方式
        
        $pay_mas=array();
        $pay_mas['dqxb']=0;
        $user_bank=M('UserBank')->field('bankcard')->where("status=1 and userid=".$userid)->find();
        $pay_mas['pay_1']=0;
        if($user_bank){
        	$pay_mas['dqxb']=1;
        	$pay_mas['pay_1']=1;
        	$pay_mas['pay_1_mas']=$user_bank;
        }
        $user_zfb=M('UserZfb')->field('card')->where("status=1 and userid=".$userid)->find();
        $pay_mas['pay_2']=0;
        if($user_zfb){
        	if($pay_mas['dqxb']==0){
        		$pay_mas['dqxb']=2;
        	}
        	$pay_mas['pay_2']=1;
        	$pay_mas['pay_2_mas']=$user_zfb;
        }
        $user_wx=M('UserWx')->field('card')->where("status=1 and userid=".$userid)->find();
        $pay_mas['pay_3']=0;
        if($user_wx){
        	if($pay_mas['dqxb']==0){
        		$pay_mas['dqxb']=3;
        	}
        	$pay_mas['pay_3']=1;
        	$pay_mas['pay_3_mas']=$user_wx;
        }
      	echo json_encode(array('status'=>1,'msg'=>$record,'pay_mas'=>$pay_mas));exit();
    }
    
    //委托订单撤销
    public function trade_chexiao(){
    	$userid=userid();
		if (!is_numeric($userid)) {
			$this->error(L('请先登录'));
		}
		if(cookie('trade_chexiao')=='trade_chexiao'){
			$this->error('请勿频繁操作');
		}
		$id=$_POST['id'];
		if(!is_numeric($id)){
			$this->error('参数错误');
		}
		$order=M('TrademarketEntruy')->where('id='.$id.' and userid='.$userid)->find();
		if(empty($order)){
			$this->error('参数错误');
		}
		if($order['status']!=2){
			$this->error('此订单已处理');
		}
		$market_arr=explode("_",$order['market']);
		$coin_mc=$market_arr[0];
		$coin_mr=$market_arr[1];
		$coin_mrd=$coin_mr.'d';
		$coin_mcd=$coin_mc.'d';
		$coin_mrdx=strtoupper($coin_mr);
		$coin_mcdx=strtoupper($coin_mc);
		$num=$order['num_zong']-$order['num_deal'];
		$usercoin=M('UserCoin')->where('userid='.$order['userid'])->find();
		if($order['type']==1){
			if($order['fenqu']=='cny'){
				try{
					$mo = M();
					$mo->startTrans();
					$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $id))->save(array('status'=>0,'time_cx'=>time()));
		        	if(check_arr($rs)) {
		        		$mo->commit();
		        		cookie('trade_chexiao','trade_chexiao',4);
		        		$this->success('撤销成功');
		        	}else {
		        		$mo->rollback();
		        		$this->error('撤销失败');
		        	}
		        }catch(\Think\Exception $e){
		        	$mo->rollback();
		        	$this->error('撤销失败');
		        }
			}else{
				$money_zong=bcmul($order['price']*$num,10000)/10000;
				try{
					$mo = M();
					$mo->startTrans();
					$rs[]=$mo->table('tw_user_coin')->where(array('userid' => $order['userid']))->save(array($coin_mr=>($usercoin[$coin_mr]+$money_zong),$coin_mrd=>($usercoin[$coin_mrd]-$money_zong)));
					$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $id))->save(array('status'=>0,'time_cx'=>time()));
		        	if(check_arr($rs)) {
		        		$mo->commit();
		        		cookie('trade_chexiao','trade_chexiao',4);
		        		M('Finance')->add(array('userid'=>$order['userid'],'coin'=>$coin_mr,'coinname'=>$coin_mrdx,'fee'=>$money_zong,'num'=>$usercoin[$coin_mr],'mum'=>($usercoin[$coin_mr]+$money_zong),'type'=>1,'name'=>'trade_chexiao','remark'=>'买币撤销','protypemas'=>'买币撤销','addtime'=>time(),'protype'=>1,'status'=>1));
		        		$this->success('撤销成功');
		        	}else {
		        		$mo->rollback();
		        		$this->error('撤销失败');
		        	}
		        }catch(\Think\Exception $e){
		        	$mo->rollback();
		        	$this->error('撤销失败');
		        }
			}
		}else{
			//卖出撤回反币+手续费+经验值
			//撤销反币 反经验值
			if($order['num_deal']<=0){
				$num_sy=$order['num_zong'];
				$num_shengyu=$order['num_zong']+$order['fee'];
				$fee=$order['fee'];
				$shengyu_jyz=$order['jyz'];
			}else{
				$num_sy=$order['num_zong']-$order['num_deal'];
				$fee=bcmul($order['fee_bl']*$num_sy,10000)/10000;
				$num_shengyu=$num_sy+$fee;
				$shengyu_jyz=bcmul($num_sy*$order['jyz_bl'],10000)/10000;
			}
			try{
				$mo = M();
				$mo->startTrans();
				$rs[]=$mo->table('tw_user_coin')->where(array('userid' => $order['userid']))->save(array($coin_mc=>($usercoin[$coin_mc]+$num_shengyu),$coin_mcd=>($usercoin[$coin_mcd]-$num_shengyu),'jyzhi'=>($usercoin['jyzhi']+$shengyu_jyz)));
				$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $id))->save(array('status'=>0,'time_cx'=>time()));
				if(check_arr($rs)) {
					$mo->commit();
					cookie('trade_chexiao','trade_chexiao',4);
					M('Finance')->add(array('userid'=>$order['userid'],'coin'=>$coin_mc,'coinname'=>$coin_mcdx,'fee'=>$num_sy,'num'=>$usercoin[$coin_mc],'mum'=>($usercoin[$coin_mc]+$num_sy),'type'=>1,'name'=>'trade_chexiao','remark'=>'卖币撤销','protypemas'=>'卖币撤销','addtime'=>time(),'protype'=>2,'status'=>1));
					if($fee>0){
						M('Finance')->add(array('userid'=>$order['userid'],'coin'=>$coin_mc,'coinname'=>$coin_mcdx,'fee'=>$fee,'num'=>($usercoin[$coin_mc]+$num_sy),'mum'=>($usercoin[$coin_mc]+$num_sy+$fee),'type'=>1,'name'=>'trade_chexiao','remark'=>'卖币撤销手续费','protypemas'=>'卖币撤销手续费','addtime'=>time(),'protype'=>2,'status'=>1));
					}
					if($shengyu_jyz>0){
						M('Finance')->add(array('userid'=>$order['userid'],'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$shengyu_jyz,'num'=>$usercoin['jyzhi'],'mum'=>($usercoin['jyzhi']+$shengyu_jyz),'type'=>1,'name'=>'trade_quxiao','remark'=>'卖币撤销','protypemas'=>'卖币撤销','addtime'=>time(),'protype'=>1,'status'=>1));
					}
					$this->level_user_gx($userid);
					$this->success('撤销成功');
				}else {
					$mo->rollback();
					$this->error('撤销失败');
				}
			}catch(\Think\Exception $e){
				$mo->rollback();
				$this->error('撤销失败');
			}
		}
    }
    
  	//验证释放币订单
	public function trade_yz(){
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
		$id=$_POST['yanzheng_id'];
		if(!is_numeric($id)){
			$this->error('订单不存在');
		}
		if($id<=0){
			$this->error('订单不存在');
		}
		$order=M('TrademarketEntruyLog')->where('id='.$id.' and peerid='.$userid)->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		if($order['fenqu']!='cny'){
			//$this->error('非可释放订单');
		}
		if($order['sta_zf']==3){
			$this->error('订单已完成');
		}
		if($order['sta_zf']==4){
			$this->error('订单已取消');
		}
		if($order['sta_zf']==1){
			$this->error('买家未付款');
		}
		if(@$_POST['quer']!=1){
			$this->error('请先勾选已收款');
		}
		$this->success('验证成功');
	}
	
	//验证密码
	public function password_yz(){
  		$userid=userid();
  		if(!is_numeric($userid)){
  			$this->error('请先登录');
  		}
  		$user=M('user')->field('invit,paypassword')->where("id=".$userid)->find();
  		if(empty($user)){
  			$this->error('请先登录');
  		}
  		$paypassword=$_POST['paypassword'];
  		if($paypassword==''){
  			$this->error('请输入资产密码');
  		}
  		if(md5($paypassword.$user['invit'])!=$user['paypassword']){
  			//$this->error('资产密码错误');
  		}
  		$this->success('验证成功');
	}
	
  	//卖家释放币
	public function sfbtc_ajax(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('id,mobile,invit,paypassword')->where('id='.$userid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$id=$_POST['shifang_id'];
		if(!is_numeric($id)){
			$this->error('订单不存在');
		}
		if($id<=0){
			$this->error('订单不存在');
		}
		$order=M('TrademarketEntruyLog')->where('id='.$id.' and peerid='.$userid)->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		if($order['fenqu']!='cny'){
			//$this->error('非可释放订单');
		}
		if($order['sta_zf']==3){
			$this->error('订单已完成');
		}
		if($order['sta_zf']==4){
			$this->error('订单已取消');
		}
		if($order['sta_zf']==1){
			$this->error('买家未付款');
		}
  		$paypassword=$_POST['paypassword'];
  		if($paypassword==''){
  			$this->error('请输入资产密码');
  		}
  		if(md5($paypassword.$user['invit'])!=$user['paypassword']){
  			$this->error('资产密码错误');
  		}
		$code=$_POST['code'];
		if($code==''){
			//$this->error('请输入短信验证码');
		}
		$mobile=$user['mobile'];
		if ($mobile != session('mobile_trade_mobile')) {
			//$this->error('短信验证码不匹配');
		}
		if (md5($code.'mima') != session('mobile_trade_code')) {
			//$this->error('短信验证码不匹配');
		}
		$chaoshi_time=session('mobile_trade_time');
		if((time()-$chaoshi_time) > 600){
			//$this->error('短信验证码已过期');
		}
		$market_arr=explode("_",$order['market']);
		$coin_mc=$market_arr[0];
		$coin_mr=$market_arr[1];
		$coin_mrd=$coin_mr.'d';
		$coin_mcd=$coin_mc.'d';
		$coin_mrdx=strtoupper($coin_mr);
		$coin_mcdx=strtoupper($coin_mc);
		$usercoin=M('UserCoin')->where('userid='.$order['userid'])->find();
		$usercoin_maichu=M('UserCoin')->where('userid='.$order['peerid'])->find();
		try{
			$mo = M();
			$mo->startTrans();
			$rs[]=$mo->table('tw_user_coin')->where(array('userid' => $order['userid']))->save(array($coin_mc=>($usercoin[$coin_mc]+$order['num']),'jyzhi'=>($usercoin['jyzhi']+$order['jyzhi_s'])));
			$rs[]=$mo->table('tw_user_coin')->where(array('userid' => $userid))->save(array($coin_mcd=>($usercoin_maichu[$coin_mcd]-$order['num']-$order['fee'])));
			$rs[]=$mo->table('tw_trademarket_entruy_log')->where(array('id' => $id))->save(array('sta_zf'=>3,'time_sf'=>time()));
			if(check_arr($rs)) {
				$mo->commit();
				cookie('trade_chexiao','trade_chexiao',4);
				M('Finance')->add(array('userid'=>$order['userid'],'coin'=>$coin_mc,'coinname'=>$coin_mcdx,'fee'=>$order['num'],'num'=>$usercoin[$coin_mc],'mum'=>($usercoin[$coin_mc]+$order['num']),'type'=>1,'name'=>'trade_yz','remark'=>'买币','protypemas'=>'买币','addtime'=>time(),'protype'=>1,'status'=>1));
				if($order['jyzhi_s']>0){
					M('Finance')->add(array('userid'=>$order['userid'],'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$order['jyzhi_s'],'num'=>$usercoin['jyzhi'],'mum'=>($usercoin['jyzhi']+$order['jyzhi_s']),'type'=>1,'name'=>'trade_yz','remark'=>'买币赠送','protypemas'=>'买币赠送','addtime'=>time(),'protype'=>1,'status'=>1));
					$this->level_user_gx($order['userid']);
				}
				$this->success('释放成功');
			}else {
				$mo->rollback();
				$this->error('释放失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('撤销失败');
		}
	}
	
  	//释放验证
	public function trade_mobile(){
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
      	$chaoshi_time=session('mobile_trade_time');
		if((time()-$chaoshi_time) < 600 && $chaoshi_time>0){
			$this->error('操作频繁,10分钟后试');
		}
        $code = rand(100000, 999999);
		$mobile = $mobile;
        $smsapi = "http://utf8.api.smschinese.cn/"; //短信网关
		$user = '13063663676'; //短信平台帐号
		$content = '您好，您的验证码是' . $code; //要发送的短信内容
      	$statusStr = array("0" => "短信发送成功","-1" => "没有该用户账户","-2" => "接口密钥不正确","-21" => "MD5接口密钥加密不正确误","-3" => "短信数量不足","-11" => "该用户被禁用","-14" => "短信内容出现非法字符","-4" => "手机号格式不正确","-41" => "手机号码为空","-42"=>'短信内容为空',"-51" => "短信签名格式不正确","-52"=>'短信签名太长');
		$sendurl = $smsapi . "?Uid=" . $user . "&Key=d41d8cd98f00b204e980&smsMob=" . $mobile . "&smsText=" . $content;
		/*$code = rand(111111, 999999);
		$content = "您的释放安全验证码为:". $code;
		$sign = "【".$config['smsqm']."】";
		$mobile = $mobile;
      	$statusStr = array("0" => "短信发送成功","-1" => "参数不全","-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！","30" => "服务密码错误","40" => "账号不存在","41" => "余额不足","42" => "帐户已过期","43" => "IP地址限制","50" => "内容含有敏感词","100"=>'您操作太频繁，请稍后再试');
      	$config = M('Config')->where(array('id' => 1))->find();
  		$smsapi = "http://api.smsbao.com/";
 		$user = $config['smsname']; //短信平台帐号
		$pass = md5($config['smspass']); //短信平台密码
  		$content = $sign.$content;
 		$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$mobile."&c=".urlencode($content);*/
 		$result =file_get_contents($sendurl);
		if ( $r != 0 ) {
           	$data['info'] = $statusStr[$r];
			$this->error($data['info']);
		} else {
            session('mobile_trade_code', md5($code.'mima'));
            session('mobile_trade_mobile',$mobile);
            session('mobile_trade_time',time());
			$this->success('验证码已发送');
		}
	}
	
	//卖出详情
	public function details_mc(){
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
		$id=$_POST['id'];
		if(!is_numeric($id)){
			$this->error('订单不存在');
		}
		if($id<=0){
			$this->error('订单不存在');
		}
		$order=M('TrademarketEntruyLog')->where('id='.$id.' and peerid='.$userid)->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		if($order['sta_zf']==3){
			$order['status_zt']='已完成';
			$order['zt_type']='订单已完成';
		}else if($order['sta_zf']==4){
			$order['status_zt']='已取消';
			$order['zt_type']='订单已取消';
		}else if($order['sta_zf']==1){
			if($order['sta_su_mr']==1){
				$order['status_zt']='申诉中';
				$order['zt_type']='订单申诉中，请等待';
			}else if($order['sta_su_mc']==1){
				$order['status_zt']='申诉中';
				$order['zt_type']='订单申诉中，请等待';
			}else{
				$order['status_zt']='未付款';
				$order['zt_type']='买家拍下未付款，请耐心等待';
			}
		}else if($order['sta_zf']==2){
			if($order['sta_su_mr']==1){
				$order['status_zt']='申诉中';
				$order['zt_type']='订单申诉中，请等待';
			}else if($order['sta_su_mc']==1){
				$order['status_zt']='申诉中';
				$order['zt_type']='订单申诉中，请等待';
			}else{
				$order['status_zt']='待释放';
				$order['zt_type']='买家已付款，请释放';
			}
		}
		$order['xiadan_time']=date("Y-m-d H:i:s",$order['time_add']);
		$usermc=M('User')->field('id,mobile,username,headimg,truename')->where('id='.$order['userid'])->find();
		$order['username']=$usermc['truename'];
		$order['headimg']='/Public/images/photo.png';
		if($usermc['headimg']){
			$order['headimg']='/Upload/headimg/'.$usermc['headimg'];
		}
		if($order['pay_1']==1){
			$usermc_xinxi=M('UserBank')->field('name,bankcard,bank,bankaddr')->where('status=1 and userid='.$order['userid'])->find();
			$order['username']=$usermc_xinxi['name'];
		}else if($order['pay_2']==1){
			$usermc_xinxi=M('UserZfb')->field('name,card')->where('status=1 and userid='.$order['userid'])->find();
			$order['username']=$usermc_xinxi['name'];
		}else if($order['pay_3']==1){
			$usermc_xinxi=M('UserWx')->field('name,card')->where('status=1 and userid='.$order['userid'])->find();
			$order['username']=$usermc_xinxi['name'];
		}
		$market_arr=explode("_",$order['market']);
		$coin_mc=$market_arr[0];
		$coin_mr=$market_arr[1];
		$order['coin_1']=strtoupper($coin_mr);
		$order['coin_2']=strtoupper($coin_mc);
		$order['mobile']=$usermc['mobile'];
		$order['price']=($order['price']*1).' '.$order['coin_1'];
		$order['num']=($order['num']*1);
		$order['jyzhi']=($order['jyzhi']*1);
		
		$order['fangshi']='';
		if($order['fenqu']=='cny'){
			if($order['sta_zf']==3){
				if($order['pay_1']==1){
					$order['fangshi']='银行卡';
				}else if($order['pay_2']==1){
					$order['fangshi']='支付宝';
				}else if($order['pay_3']==1){
					$order['fangshi']='微信';
				}
			}
		}else{
			$coinimg=M('Coin')->field('img')->where("name='".$order['fenqu']."'")->find();
			$order['fangshi']='<img style="width:0.4rem;" src="/Upload/coin/'.$coinimg['img'].'" alt="">'.(strtoupper($order['fenqu']));
		}
		$order['time_shengyu']=$order['time_sf_djs']-time();
		if($order['sell_id']>0){
			$order['num_zong']=($order['num']+$order['fee']).' '.$order['coin_2'];
			$order['num_shiji']=$order['num'].' '.$order['coin_2'];
		}else{
			$order['num_zong']=($order['num']+$order['fee']).' '.$order['coin_2'];
			$order['num_shiji']=$order['num'].' '.$order['coin_2'];
		}
		$order['fee']=($order['fee']*1).' '.$order['coin_2'];
		echo json_encode(array('status'=>1,'info'=>'获取成功','order'=>$order,'xinxi'=>$usermc_xinxi));exit();
	}
	
	//买入详情
	public function details_mr(){
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
		$id=$_POST['id'];
		if(!is_numeric($id)){
			$this->error('订单不存在');
		}
		if($id<=0){
			$this->error('订单不存在');
		}
		$order=M('TrademarketEntruyLog')->where('id='.$id.' and userid='.$userid)->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		if($order['sta_zf']==3){
			$order['status_zt']='已完成';
			$order['zt_type']='订单已完成';
		}else if($order['sta_zf']==4){
			$order['status_zt']='已取消';
			$order['zt_type']='订单已取消';
		}else if($order['sta_zf']==1){
			if($order['sta_su_mr']==1){
				$order['status_zt']='申诉中';
				$order['zt_type']='订单申诉中，请等待';
			}else if($order['sta_su_mc']==1){
				$order['status_zt']='申诉中';
				$order['zt_type']='订单申诉中，请等待';
			}else{
				$order['status_zt']='请付款';
				$order['zt_type']='您已成功下单，请及时付款';
			}
		}else if($order['sta_zf']==2){
			if($order['sta_su_mr']==1){
				$order['status_zt']='申诉中';
				$order['zt_type']='订单申诉中，请等待';
			}else if($order['sta_su_mc']==1){
				$order['status_zt']='申诉中';
				$order['zt_type']='订单申诉中，请等待';
			}else{
				$order['status_zt']='已付款';
				$order['zt_type']='卖家正在放行，请耐心等待';
			}
		}
		$order['xiadan_time']=date("Y-m-d H:i:s",$order['time_add']);
		$usermc=M('User')->field('id,mobile,username,headimg')->where('id='.$order['peerid'])->find();
		$order['headimg']='/Public/images/photo.png';
		if($usermc['headimg']){
			$order['headimg']='/Upload/headimg/'.$usermc['headimg'];
		}
		$order['username']=$usermc['id'];
		if($order['pay_1']==1){
			$usermc_xinxi=M('UserBank')->field('name,bankcard,bank,bankaddr')->where('status=1 and userid='.$order['peerid'])->find();
		}else if($order['pay_2']==1){
			$usermc_xinxi=M('UserZfb')->field('name,card,img')->where('status=1 and userid='.$order['peerid'])->find();
		}else if($order['pay_3']==1){
			$usermc_xinxi=M('UserWx')->field('name,card,img')->where('status=1 and userid='.$order['peerid'])->find();
		}
		$order['fangshi']='';
		if($order['fenqu']=='cny'){
			if($order['sta_zf']==3){
				if($order['pay_1']==1){
					$order['fangshi']='银行卡';
				}else if($order['pay_2']==1){
					$order['fangshi']='支付宝';
				}else if($order['pay_3']==1){
					$order['fangshi']='微信';
				}
			}
		}else{
			$coinimg=M('Coin')->field('img')->where("name='".$order['fenqu']."'")->find();
			$order['fangshi']='<img style="width:0.4rem;" src="/Upload/coin/'.$coinimg['img'].'" alt="">'.(strtoupper($order['fenqu']));
		}
		$market_arr=explode("_",$order['market']);
		$coin_mc=$market_arr[0];
		$coin_mr=$market_arr[1];
		$order['coin_1']=strtoupper($coin_mr);
		$order['coin_2']=strtoupper($coin_mc);
		$order['mobile']=$usermc['mobile'];
		$order['money']=($order['money']*1);
		$order['price']=($order['price']*1).' '.$order['coin_1'];
		$order['num']=($order['num']*1).' '.$order['coin_2'];
		$order['time_shengyu']=$order['time_fk_djs']-time();
		echo json_encode(array('status'=>1,'info'=>'获取成功','order'=>$order,'xinxi'=>$usermc_xinxi));exit();
	}
	
	//上传身份证图片
	public function fukuan_img(){
		$userid=userid();
      	if(!is_numeric($userid)){
          	$this->error('请先登录');
        }
		$upload = new \Think\Upload();
		$upload->maxSize = 15242880;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/fukuan/';
		$upload->autoSub = false;
		$info = $upload->upload();
		$upload_dir = 'Upload/fukuan/';
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
	            if($width > 400 || $height > 400){//原图宽度或高度大于1500时才缩放
	                // 按照原图的比例生成一个最大为1500*1500（根据宽高像素等比例缩小）的缩略图并保存，我这里直接覆盖掉了原图。
	                $image->thumb(400, 400)->save($file_url);
	            }
	            echo json_encode(array('status'=>1,'info'=>$path));exit;
				echo $path;
				exit();
			}
		}
	}
	
	//确认付款
	public function fukuan_ajax(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if(cookie('fukuan_ajax')=='fukuan_ajax'){
			$this->error('请勿频繁操作');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('id')->where('id='.$userid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$id=$_POST['mairu_fukuan_id'];
		if(!is_numeric($id)){
			$this->error('订单不存在');
		}
		if($id<=0){
			$this->error('订单不存在');
		}
		$order=M('TrademarketEntruyLog')->where('id='.$id.' and userid='.$userid)->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		$img=$_POST['img'];
		if($img==''){
			$this->error('请上传打款截图');
		}
		if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$img)){
			$this->error('请上传打款截图');
		}
		$marketarrset=M('TrademarketSet')->where("id=1")->find();
        $time_sf_djs = time()+$marketarrset['time_sk']*60;
		try{
			$mo = M();
			$mo->startTrans();
			$rs[]=$id=$mo->table('tw_trademarket_entruy_log')->where('id='.$id)->save(array('img'=>$img,'time_sf_djs'=>$time_sf_djs,'sta_zf'=>2,'time_fk'=>time()));
			if (check_arr($rs)) {
				$mo->commit();
				cookie('fukuan_ajax','fukuan_ajax',4);
				
                    //给买家发送信息
                    $user_mairu=M('User')->field('mobile')->where('id='.$order['peerid'])->find();
                    if($user_mairu['mobile']){
                    	if($marketarrset['duanxin_mc']){
	                    	$content = $marketarrset['duanxin_mc'];
                            $smsapi = "http://utf8.api.smschinese.cn/"; //短信网关
		                    $user = '13063663676'; //短信平台帐号
		                    $sendurl = $smsapi . "?Uid=" . $user . "&Key=d41d8cd98f00b204e980&smsMob=" . $user_mairu['mobile'] . "&smsText=" . $content;
	                    	/*$config = M('Config')->field('smsname,smspass,smsqm')->where(array('id' => 1))->find();
	                    	$content = $marketarrset['duanxin_mc'];
	                    	$sign = "【".$config['smsqm']."】";
					  		$smsapi = "http://api.smsbao.com/";
					 		$user = $config['smsname']; //短信平台帐号
							$pass = md5($config['smspass']); //短信平台密码
					  		$content = $sign.$content;
					 		$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$user_mairu['mobile']."&c=".urlencode($content);*/
					 		$result =file_get_contents($sendurl);
                    	}
                    }
				$this->success('付款成功');
			}else {
				$mo->rollback();
				$this->error('付款失败');
			}
		}catch(\Think\Exception $e){
			$mo->rollback();
			$this->error('付款失败');
		}
	}
	
	public function shensu_zdong(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if(cookie('shensu_ajax')=='shensu_ajax'){
			$this->error('请勿频繁操作');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('id')->where('id='.$userid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$id=$_POST['id'];
		if(!is_numeric($id)){
			$this->error('订单不存在');
		}
		if($id<=0){
			$this->error('订单不存在');
		}
		$order=M('TrademarketEntruyLog')->where('id='.$id)->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		if($order['sta_zf']==3){
			$this->error('订单已完成');
		}
		if($order['sta_zf']==4){
			$this->error('订单已取消');
		}
		$type=$_POST['type'];
		if($type==1){
			if($order['sta_su_mc']==1){
				$this->success('ok');
			}
			if($order['sta_su_mc']!=1){
				if($order['sta_zf']==1){
					if($order['time_fk_djs']<=time()){
						$zxcg=M('TrademarketEntruyLog')->where('id='.$id)->save(array('sta_su_mc'=>1,'mas_su_mc'=>'买家未付款','time_su_mc'=>time()));
						if($zxcg){
							$this->success('ok');
						}
					}
				}
			}
		}else{
			if($order['sta_su_mr']==1){
				$this->success('ok');
			}
			if($order['sta_su_mr']!=1){
				if($order['sta_zf']==2){
					if($order['time_sk_djs']<=time()){
						$zxcg=M('TrademarketEntruyLog')->where('id='.$id)->save(array('sta_su_mr'=>1,'mas_su_mr'=>'卖家未释放','time_su_mr'=>time()));
						if($zxcg){
							$this->success('ok');
						}
					}
				}
			}
		}
		$this->error('ok');
	}
	
	//确认申诉
	public function shensu_ajax(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if(cookie('shensu_ajax')=='shensu_ajax'){
			$this->error('请勿频繁操作');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('id')->where('id='.$userid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$id=$_POST['shensu_id'];
		if(!is_numeric($id)){
			$this->error('订单不存在');
		}
		if($id<=0){
			$this->error('订单不存在');
		}
		$order=M('TrademarketEntruyLog')->where('id='.$id)->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		if($order['sta_zf']==3){
			$this->error('订单已完成');
		}
		if($order['sta_zf']==4){
			$this->error('订单已取消');
		}
		if($order['userid']==$userid){
			if($order['sta_zf']==1){
				$this->error('未付款不能申诉');
			}
			if($order['sta_su_mr']==1){
				$this->error('已申诉，请勿重复操作');
			}
			if($order['sta_zf']==2){
				if($order['time_sf_djs']>time()){
					$this->success('收款未超时，不能申诉');
				}
			}
			$mas=$_POST['mas'];
			if($mas==''){
				$this->error('请输入申诉内容');
			}
			if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$mas)){
				$this->error('申诉内容不能输入特殊字符');
			}
			try{
				$mo = M();
				$mo->startTrans();
				$rs[]=$id=$mo->table('tw_trademarket_entruy_log')->where('id='.$id)->save(array('mas_su_mr'=>$mas,'sta_su_mr'=>1,'time_su_mr'=>time()));
				if (check_arr($rs)) {
					$mo->commit();
					cookie('shensu_ajax','shensu_ajax',4);
					$this->success('申诉成功');
				}else {
					$mo->rollback();
					$this->error('申诉失败');
				}
			}catch(\Think\Exception $e){
				$mo->rollback();
				$this->error('申诉失败');
			}
		}else if($order['peerid']==$userid){
			if($order['sta_zf']==1){
				if($order['time_fk_djs']>time()){
					$this->success('付款未超时，不能申诉');
				}
			}
			$mas=$_POST['mas'];
			if($mas==''){
				$this->error('请输入申诉内容');
			}
			if(preg_match("/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$mas)){
				$this->error('申诉内容不能输入特殊字符');
			}
			try{
				$mo = M();
				$mo->startTrans();
				$rs[]=$id=$mo->table('tw_trademarket_entruy_log')->where('id='.$id)->save(array('mas_su_mc'=>$mas,'sta_su_mc'=>1,'time_su_mc'=>time()));
				if (check_arr($rs)) {
					$mo->commit();
					cookie('shensu_ajax','shensu_ajax',4);
					$this->success('申诉成功');
				}else {
					$mo->rollback();
					$this->error('申诉失败');
				}
			}catch(\Think\Exception $e){
				$mo->rollback();
				$this->error('申诉失败');
			}
		}else{
			$this->error('订单不存在');
		}
	}
	
	//确认取消
	public function quxiao_ajax(){
		$userid=userid();
		if(!is_numeric($userid)){
			$this->error('请先登录');
		}
		if(cookie('quxiao_ajax')=='quxiao_ajax'){
			$this->error('请勿频繁操作');
		}
		if($userid<=0){
			$this->error('请先登录');
		}
		$user=M('User')->field('id')->where('id='.$userid)->find();
		if(empty($user)){
			$this->error('请先登录');
		}
		$id=$_POST['quxiao_id'];
		if(!is_numeric($id)){
			$this->error('订单不存在');
		}
		if($id<=0){
			$this->error('订单不存在');
		}
		$order=M('TrademarketEntruyLog')->where('id='.$id)->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		if($order['fenqu']!='cny'){
			$this->error('订单不存在');
		}
		if($order['userid']!=$userid){
			$this->error('订单不能取消');
		}
		if($order['sta_zf']!=1){
			$this->error('订单不能取消');
		}
		
		
		$user_maichu=M('User')->field('id,mobile')->where('id='.$order['peerid'])->find();
		//卖家订单加回 查询卖币是否完成
		if($order['sell_id']>0){
			$order_maichu=M('TrademarketEntruy')->field('num_zong,num_deal,status')->where('id='.$order['sell_id'])->find();
			if($order_maichu['status']==2){
				$shuliang=$order_maichu['num_deal']-$order['num'];
			}else{
				$shuliang=$order_maichu['num_zong']-$order['num'];
			}
			try{
				$mo = M();
				$mo->startTrans();
				$rs[]=$mo->table('tw_trademarket_entruy')->where(array('id' => $order['sell_id']))->save(array('status'=>2,'num_deal'=>$shuliang));
				$rs[]=$mo->table('tw_trademarket_entruy_log')->where(array('id' => $id))->delete();
				if(check_arr($rs)) {
					$mo->commit();
					cookie('quxiao_ajax','quxiao_ajax',4);
					$this->success('取消成功');
				}else {
					$mo->rollback();
					$this->error('取消失败');
				}
			}catch(\Think\Exception $e){
				$mo->rollback();
				$this->error('取消失败');
			}
		}else{
			$market_arr=explode("_",$order['market']);
			$coin_mc=$market_arr[0];
			$coin_mr=$market_arr[1];
			$coin_mrd=$coin_mr.'d';
			$coin_mcd=$coin_mc.'d';
			$coin_mrdx=strtoupper($coin_mr);
			$coin_mcdx=strtoupper($coin_mc);
			$user_maichu=M('UserCoin')->where('userid='.$order['peerid'])->find();
			try{
				$mo = M();
				$mo->startTrans();
				$rs[]=$mo->table('tw_trademarket_entruy_log')->where(array('id' => $id))->save(array('sta_zf'=>4,'time_qx'=>time()));
				$rs[]=$mo->table('tw_user_coin')->where(array('userid' => $order['peerid']))->save(array($coin_mc=>($user_maichu[$coin_mc]+$order['num']+$order['fee']),$coin_mcd=>($user_maichu[$coin_mcd]-$order['num']-$order['fee']),'jyzhi'=>($user_maichu['jyzhi']+$order['jyzhi'])));
				if(check_arr($rs)) {
					$mo->commit();
					cookie('quxiao_ajax','quxiao_ajax',4);
					
					M('Finance')->add(array('userid'=>$order['peerid'],'coin'=>$coin_mc,'coinname'=>$coin_mcdx,'fee'=>$order['num'],'num'=>$user_maichu[$coin_mc],'mum'=>($user_maichu[$coin_mc]+$order['num']),'type'=>1,'name'=>'trade_ajax','remark'=>'卖币取消','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    if($order['fee']>0){
                    	M('Finance')->add(array('userid'=>$order['peerid'],'coin'=>$coin_mc,'coinname'=>$coin_mcdx,'fee'=>$order['fee'],'num'=>($user_maichu[$coin_mc]+$order['num']),'mum'=>($user_maichu[$coin_mc]+$order['num']+$order['fee']),'type'=>1,'name'=>'trade_ajax','remark'=>'卖币取消【手续费】','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    }
                    if($order['jyzhi']>0){
                    	M('Finance')->add(array('userid'=>$order['peerid'],'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$order['jyzhi'],'num'=>$user_maichu['jyzhi'],'mum'=>($user_maichu['jyzhi']+$order['jyzhi']),'type'=>1,'name'=>'trade_ajax','remark'=>'卖币取消','protypemas'=>'卖币','addtime'=>time(),'protype'=>4,'status'=>1));
                    }
                    $this->level_user_gx($orderinfo['peerid']);
					$this->success('取消成功');
				}else {
					$mo->rollback();
					$this->error('取消失败');
				}
			}catch(\Think\Exception $e){
				$mo->rollback();
				$this->error('取消失败');
			}
		}
	}
	
}