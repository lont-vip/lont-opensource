<?php
namespace Home\Controller;

class AjaxController extends HomeController
{
	//上传用户身份证
	public function imgUser()
	{
		if (!userid()) {
			echo "nologin";
		}
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/idcard/';
		$upload->autoSub = false;
		$info = $upload->upload();
		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}
	//上传支付宝
	public function upuseraimg(){
		if (!userid()) {
			echo "nologin";
		}
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/finance/';
		$upload->autoSub = false;
		$info = $upload->upload();
		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}
  	public function uptradeimg()
	{
		if (!userid()) {
			echo "nologin";
		}
		
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/trade/';
		$upload->autoSub = false;
		$info = $upload->upload();
		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}
	
	public function getJsonTopshow($market = NULL, $ajax = 'json')
	{
		$data = (APP_DEBUG ? null : S('getJsonTopshow' . $market));
		$showcoin = M('market')->where(array('fshow'=>1))->select();
		foreach ($showcoin as $k => $v) {
			$v['xnb'] = explode('_', $v['name'])[0];
			$v['rmb'] = explode('_', $v['name'])[1];
			$data[$k]['name'] = $v['xnb'] ;
			$data[$k]['title'] = strtoupper($v['xnb']) ;
			$data[$k]['new_price'] =round($v['new_price'],2) ;
			$data[$k]['change'] = $v['change'];
			
			if ($v['change']>0) {
				$data[$k]['zd']=1;//涨
			} elseif ($v['change']<0) {
				$data[$k]['zd']=2;//跌
			} else {
				$data[$k]['zd']=0;//平
			}
			
			$data[$k]['cje'] = round($v['volume'] * $v['new_price'], 2);

			if ($data[$k]['volume'] > 10000 && $data[$k]['volume'] < 100000000) {
				$data[$k]['cjl'] = (intval($data[$k]['volume'] / 10000*100)/100) . "万";
			}
			if ($data[$k]['volume'] > 100000000) {
				$data[$k]['cjl'] = (intval($data[$k]['volume'] / 100000000*100)/100) . "亿";
			}
			if ($data[$k]['cje'] > 10000 && $data[$k]['cje'] < 100000000) {
				$data[$k]['cje']= (intval($data[$k]['cje'] / 10000*100)/100) . "万";
			}
			if ($data[$k]['cje'] > 100000000) {
				$data[$k]['cje'] = (intval($data[$k]['cje'] / 100000000*100)/100) . "亿";
			}
		}

		S('getJsonTopshow' , $data);
		if ($ajax) {
			// var_dump(json_encode($data));die;
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
	
	/** 自定义分区查询  改.HAOMA20181030 **/
	public function allcoin_a($id=1,$ajax='json'){
    	$trandata_data = array();
		$trandata_data['info'] = L("数据异常");
		$trandata_data['status'] = 0;
		$trandata_data['url'] = "";
        // 市场交易记录
        $marketLogs = array();
        foreach (C('market') as $k => $v) {
			$_tmp = null;
            if (!empty($_tmp)) {
                $marketLogs[$k] = $_tmp;
            } else {
                $tradeLog = M('TradeLog')->where(array('status' => 1, 'market' => $k))->order('id desc')->limit(50)->select();
                $_data = array();
                foreach ($tradeLog as $_k => $v) {
                    $_data['tradelog'][$_k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $_data['tradelog'][$_k]['type'] = $v['type'];
                    $_data['tradelog'][$_k]['price'] = $v['price'] * 1;
                    $_data['tradelog'][$_k]['num'] = round($v['num'], 6);
                    $_data['tradelog'][$_k]['mum'] = round($v['mum'], 2);
                }
                $marketLogs[$k] = $_data;
                S('getTradelog' . $k, $_data);
            }
        }
		$volume_24h = array();
		$tradeAmount_24h = array();	
        if ($marketLogs) {
            foreach (C('market') as $k => $v) {
				$_tradeLogs['num'] = M('TradeLog')->where(array(
					'status' => 1,
					'market' => $k,
					'addtime' => array('gt', time() - (60 * 60 * 24))
				))->sum('num');
				$_tradeLogs['mum'] = M('TradeLog')->where(array(
					'status' => 1,
					'market' => $k,
					'addtime' => array('gt', time() - (60 * 60 * 24))
				))->sum('mum');
                if ($_tradeLogs) {
					$volume_24h[$k] = round($_tradeLogs['num'], 4); // 24小时 交易量
                    $tradeAmount_24h[$k] = round($_tradeLogs['mum'], 4); // 24小时 交易额
                }
            }
        }
        
      	$configxx=M('Config')->where('id=1')->find();
		if (!$data) {
			$trandata_data['info']=L("数据正常");
			$trandata_data['status']=1;
			$trandata_data['url']="";
			$cfg=M('config')->where('id=1')->find();
			foreach (C('market') as $m => $v) {
              	$k=$v['sort'];
				if ($v['jiaoyiqu'] == $id) {
					$xx=M('market')->where("name='".$v['name']."'")->find();
					$htbz_xx = explode('_', $v['name'])[1];
					$xnb = strtoupper(explode('_', $v['name'])[0]);
					$market = strtoupper(explode('_', $v['name'])[1]);
					$trandata_data['url'][$k][0] = $xnb;
					$trandata_data['url'][$k][1] = $market;
					$trandata_data['url'][$k][2] = round($xx['a_lastprice'], 5);
					$trandata_data['url'][$k][3] = round($xx['a_bidprice'], $v['round']);
					$trandata_data['url'][$k][4] = round($xx['a_askprice'], $v['round']);
					$trandata_data['url'][$k][5] = $xx['a_volume'];
					$trandata_data['url'][$k][6] = '';
					$trandata_data['url'][$k][7] = $xx['a_volume'];
					$trandata_data['url'][$k][8] = round($xx['a_pricechangepercent'], 2);
					if($xx['a_pricechangepercent']>=0){
						$trandata_data['url'][$k][16] = 'background:#eb2b2b';
					}else{
						$trandata_data['url'][$k][16] = 'background:#09AF6D';
					}
					//链接专用
					$trandata_data['url'][$k][9] = $v['name'];
					//图图标地址
					$trandata_data['url'][$k][10] = $v['xnbimg'];
					//最高价
					$trandata_data['url'][$k][11] = round($xx['a_highprice'], $v['round']);
					//最低价
					$trandata_data['url'][$k][12] = round($xx['a_lowprice'], $v['round']);
					$rmbs = 0;
						$market = explode('_', $v['name'])[1];
						if ($market==Anchor_CNY) { //锚定法币
							$rmbs =  bcdiv($v['new_price'] * C('MYCOIN'),1,$v['round']) * 1;
						}
						if ($market=='btc') {
							$rmbs = bcdiv($v['new_price'] * C('market')['btc_'.Anchor_CNY]['new_price'],1,$v['round']) * 1;
						}
						if ($market=='eth') {
							$rmbs = bcdiv($v['new_price'] * C('market')['eth_'.Anchor_CNY]['new_price'],1,$v['round']) * 1;
						}
						if ($market=='usdt') {
							$rmbs = bcdiv($v['new_price'] * C('market')['usdt_'.Anchor_CNY]['new_price'],1,$v['round']) * 1;
						}
						$trandata_data['url'][$k][14] = $rmbs;
	                  	$trandata_data['url'][$k][15] = $trandata_data['url'][$k][2];
	                  	$trandata_data['url'][$k][2] = intval($configxx[$htbz_xx]*$trandata_data['url'][$k][15]*100000)/100000;
					
					$ixxx=0;
					if($ixxx==1){
						
						//每分钟自动变化
						$timeTemp=(int)date("i")*0.00001;
						 $v['new_price']=(1+$timeTemp)*$v['new_price'];
						//币种简称
						$trandata_data['url'][$k][0] = $xnb;
						//币种市场
						$trandata_data['url'][$k][1] = $market;
	                  	
	                  	//折合币种价格
	                  	//$trandata_data['url'][$k][2] = round($cfg[strtolower($xnb)],$v['round']);
	                  	$save = M('market')->where("id=".$v['id'])->save(array("dq_price"=>round($cfg[strtolower($xnb)]/$cfg[strtolower($market)],4)));
	                  	//$save = M('market')->where("id=".$v['id'])->save(array("dq_price"=>round($cfg[strtolower($xnb)]/$cfg['huilv_hq'],4)));
	                  	$save2 = M('market')->where("id=".$v['id'])->save(array("change2"=>round($cfg[strtolower($xnb).'c'],4)));
						//最新成交价
						$trandata_data['url'][$k][2] = round($v['new_price'], 5);
						//买一价
						$trandata_data['url'][$k][3] = round($v['buy_price'], $v['round']);
	                  	//$trandata_data['url'][$k][3] = round($cfg[strtolower($xnb)], $v['round']);
						//卖一价
						$trandata_data['url'][$k][4] = round($v['sell_price'], $v['round']);
						//交易额
						$trandata_data['url'][$k][5] = isset($tradeAmount_24h[$k]) ? $tradeAmount_24h[$k] : 0;//round($v['volume'] * $v['new_price'], 2) * 1;
						
						$trandata_data['url'][$k][6] = '';
						
						//交易量
						$trandata_data['url'][$k][7] = isset($volume_24h[$k]) ? $volume_24h[$k] : 0;//round($v['volume'], 4) * 1;
						
						//涨跌幅
	                  	/*
						$trandata_data['url'][$k][8] = round($v['change2'], 2);
	                  	if($v['change2']>=0){
	                    	$trandata_data['url'][$k][15] = 'background:#eb2b2b';
	                    }else{
	                    	$trandata_data['url'][$k][15] = 'background:#09AF6D';
	                    }*/
						$trandata_data['url'][$k][8] = round($v['change'], 2);
	                  	if($v['change']>=0){
	                    	$trandata_data['url'][$k][16] = 'background:#eb2b2b';
	                    }else{
	                    	$trandata_data['url'][$k][16] = 'background:#09AF6D';
	                    }
						//链接专用
						$trandata_data['url'][$k][9] = $v['name'];
						//图图标地址
						$trandata_data['url'][$k][10] = $v['xnbimg'];
						//最高价
						$trandata_data['url'][$k][11] = round($v['max_price'], $v['round']);
						//最低价
						$trandata_data['url'][$k][12] = round($v['min_price'], $v['round']);
						$rmbs = 0;
						$market = explode('_', $v['name'])[1];
						if ($market==Anchor_CNY) { //锚定法币
							$rmbs =  bcdiv($v['new_price'] * C('MYCOIN'),1,$v['round']) * 1;
						}
						if ($market=='btc') {
							$rmbs = bcdiv($v['new_price'] * C('market')['btc_'.Anchor_CNY]['new_price'],1,$v['round']) * 1;
						}
						if ($market=='eth') {
							$rmbs = bcdiv($v['new_price'] * C('market')['eth_'.Anchor_CNY]['new_price'],1,$v['round']) * 1;
						}
						if ($market=='usdt') {
							$rmbs = bcdiv($v['new_price'] * C('market')['usdt_'.Anchor_CNY]['new_price'],1,$v['round']) * 1;
						}
						$trandata_data['url'][$k][14] = $rmbs;
	                  	//$trandata_data['url'][$k][15] = intval($cfg[strtolower($xnb)]/$cfg[strtolower($market)]*1000)/1000;
	                  	//$trandata_data['url'][$k][15] = intval($trandata_data['url'][$k][2]/$cfg[strtolower($market)]*1000)/1000;
	                  	$trandata_data['url'][$k][15] = $trandata_data['url'][$k][2];
	                  	/*
	                  	if($htbz_xx=='usdt'){
	                      	$trandata_data['url'][$k][2] = intval($trandata_data['url'][$k][15]*100000)/100000;
	                    }else{
	                  		$trandata_data['url'][$k][2] = intval($configxx[$htbz_xx]*$trandata_data['url'][$k][15]*100000)/100000;
	                    }*/
	                  	$trandata_data['url'][$k][2] = intval($configxx[$htbz_xx]*$trandata_data['url'][$k][15]*100000)/100000;
					}
				}
			}
		}

		if ($ajax) {
			echo json_encode($trandata_data);
			unset($trandata_data);
			exit();
		} else {
			return $trandata_data;
		}
	}
	
	public function index_b_trends($ajax = 'json')
	{
		$data = (APP_DEBUG ? null : S('trends'));
		if (!$data) {
			foreach (C('market') as $k => $v) {
				$tendency = json_decode($v['tendency'], true);
				$data[$k]['data'] = $tendency;
				$data[$k]['yprice'] = $v['new_price'];
			}
			S('trends', $data);
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function allcoin($ajax = 'json')
	{
		$data = (APP_DEBUG ? null : S('allcoin'));

		if (!$data) {
			foreach (C('market') as $k => $v) {
				$data[$k][0] = $v['title'];
				$data[$k][1] = round($v['new_price'], $v['round']);
				$data[$k][2] = round($v['buy_price'], $v['round']);
				$data[$k][3] = round($v['sell_price'], $v['round']);
				$data[$k][4] = round($v['volume'] * $v['new_price'], 2) * 1;
				$data[$k][5] = '';
				$data[$k][6] = round($v['volume'], 2) * 1;
				$data[$k][7] = round($v['change'], 2);
				$data[$k][8] = $v['name'];
				$data[$k][9] = $v['xnbimg'];
				$data[$k][10] = '';
			}
			S('allcoin', $data);
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	// 交易中心调用
	public function getJsonTop($market = NULL, $ajax = 'json')
	{
		$ll=1;
		if($ll==1){
			$cfg=M('config')->where('id=1')->find();
			$market_xxx=M('Market')->field('name')->order('id asc')->find();
			if(preg_match("/[\',:;*?~`!#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$email)){
				$market=$market_xxx['name'];
			}
			$market_xxx2=M('Market')->field('name')->where("name='".$market."'")->find();
			if(empty($market_xxx2)){
				$market=$market_xxx['name'];
			}
			$xnb = explode("_", $market)[0];
			$rmb = explode("_", $market)[1];
			$market_arr=M('Market')->where("name='".$market."'")->find();
			$xx=$market_arr;
			$data['info']['new_price']=$xx['a_lastprice']*1;
			$data['info']['new_price_rmb']=round($data["info"]["new_price"]*$cfg[$rmb],4);
			$data['info']['change']=$xx['a_pricechangepercent']*1;
			$data['info']['max_price']=$xx['a_highprice']*1;
			$data['info']['min_price']=$xx['a_lowprice']*1;
			$data['info']['volume']=$xx['a_volume']*1;
			exit(json_encode($data));
		}
		
		
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E
		
		$data = (APP_DEBUG ? null : S("getJsonTop" . $market));
		if (!$data) {
			if ($market) {
				$xnb = explode("_", $market)[0];
				$rmb = explode("_", $market)[1];
				
				// 24小时 交易量
				$volume_24h = array();
				$volume_24h = M('TradeLog')->where(array(
					'status' => 1,
					'market' => $market,
					'addtime' => array('gt', time() - (60 * 60 * 24))
				))->sum('num');
				$volume_24h = round($volume_24h, 4);
	
/*				foreach (C("market") as $k => $v) {
					$v["xnb"] = explode("_", $v["name"])[0];
					$v["rmb"] = explode("_", $v["name"])[1];
					$data["list"][$k]["name"] = $v["name"];
					$data["list"][$k]["img"] = $v["xnbimg"];
					$data["list"][$k]["title"] = $v["title"];
					$data["list"][$k]["new_price"] = $v["new_price"];
					$data["list"][$k]["change"] = $v["change"];
					$data["list"][$k]['coin_name'] = strtoupper($v["xnb"]);
				}*/
				
				

				$data["info"]["img"] = C("market")[$market]["xnbimg"];
				//$data["info"]["title"] = C("market")[$market]["title"];
              	$cfg=M('config')->where('id=1')->find();
      			$c_price=$cfg[$rmb];
				//每分钟自动变化
				$timeTemp=(int)date("i")*0.00001;
				$data["info"]["new_price"] = round((1+$timeTemp)*C("market")[$market]["new_price"],4);
              	//$data["info"]['new_price_rmb'] = round($data["info"]["new_price"]*$c_price,4);
              	$data["info"]['new_price_rmb'] = round($data["info"]["new_price"]*$cfg[$rmb],4);
				$data["info"]["max_price"] = C("market")[$market]["max_price"];
				$data["info"]["min_price"] = C("market")[$market]["min_price"];
				$data["info"]["buy_price"] = C("market")[$market]["buy_price"];
				$data["info"]["sell_price"] = C("market")[$market]["sell_price"];
				//$data["info"]["volume"] = isset($volume_24h) ? $volume_24h : 0;//C("market")[$market]["volume"];
              	$data["info"]["volume"] = C("market")[$market]["volume"];
				$data["info"]["change"] =round(C("market")[$market]["change"],2);
				
				S("getJsonTop" . $market, $data);
			}
		}
		
		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
	
	/** 交易中心-币种列表  改.HAOMA20181101 **/
	public function getJsonTop2($id=1, $ajax = 'json')
	{
		if (!$data) {
			$trandata_data['info'] = "数据正常";
			$trandata_data['status'] = 1;
			$trandata_data['url'] = "";
			
			foreach (C("market") as $k => $v) {
				if ($v['jiaoyiqu'] == $id) {
					$trandata_data["list"][$k]["name"] = $v["name"];
					$trandata_data["list"][$k]["img"] = $v["xnbimg"];
					$trandata_data["list"][$k]["title"] = $v["title"];
					$trandata_data["list"][$k]["new_price"] = $v["new_price"];
					$trandata_data["list"][$k]["change"] = $v["change"];
					$trandata_data["list"][$k]['coin_name'] = strtoupper($v["xnb"]);
				}
			}
		}

		if ($ajax) {
			exit(json_encode($trandata_data));
		} else {
			return $trandata_data;
		}
	}

	public function getTradelog($market = NULL, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$data = (APP_DEBUG ? null : S('getTradelog' . $market));

		if (!$data) {
			$tradeLog = M('TradeLog')->where(array('status' => 1, 'market' => $market))->order('id desc')->limit(10)->select();
			if ($tradeLog) {
				foreach ($tradeLog as $k => $v) {
					$data['tradelog'][$k]['addtime'] = date('H:i:s', $v['addtime']);
					$data['tradelog'][$k]['type'] = $v['type'];
					$data['tradelog'][$k]['price'] = $v['price'] * 1;
					$data['tradelog'][$k]['num'] = round($v['num'], 6);
					$data['tradelog'][$k]['mum'] = round($v['mum'], 6);
				}
				S('getTradelog' . $market, $data);
			}
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function getDepth($market = NULL, $trade_moshi = 1,$limts = 5, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market) || checkstr($trade_moshi)) {
			$this->error(L('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!C('market')[$market]) {
			return null;
		}
		$i=0;
		if($i==1){
			if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$market)){
				exit(json_encode(array()));
			}
			$cx_market=M('Market')->field('name,waibu_mas')->where("name='".$market."'")->find();
	      	if(empty($cx_market)){
	          	exit(json_encode(array()));
	        }
	        $con_fig=M('Config')->field('api_key,api_secret')->where('id=1')->find();
	        $apikey = $con_fig['api_key'];
	        $apisecret = $con_fig['api_secret'];
	        require_once './vendor/autoload.php';
	        $api = new \Binance\API($apikey,$apisecret);
	        $allBNBOrders = $api->openOrders($cx_market['waibu_mas']);
	        $order_depth=array();
	        $i_buy=0;
	        $i_sell=0;
	        foreach($allBNBOrders as $n=>$var){
	        	if($var['side']=='SELL'){
	        		$order_depth['depth']['sell'][$i_sell][]=$var['price']*1;
	        		$order_depth['depth']['sell'][$i_sell][]=$var['origQty']*1;
	        		$i_sell++;
	        	}else{
	        		$order_depth['depth']['buy'][$i_buy][]=$var['price']*1;
	        		$order_depth['depth']['buy'][$i_buy][]=$var['origQty']*1;
	        		$i_buy++;
	        	}
	        }
	        echo json_encode($order_depth);exit;
		}
		$data_getDepth = (APP_DEBUG ? null : S('getDepth'));
		if (!$data_getDepth[$market][$trade_moshi]) {
			if ($trade_moshi == 1) {
				$limt = $limts;
			}
			if (($trade_moshi == 3) || ($trade_moshi == 4)) {
				$limt = $limts;
			}

			$mo = M();

			if ($trade_moshi == 1) {
				$buy = $mo->query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
				$sell = array_reverse($mo->query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
			}
			if ($trade_moshi == 3) {
				$buy = $mo->query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
				$sell = null;
			}
			if ($trade_moshi == 4) {
				$buy = null;
				$sell = array_reverse($mo->query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
			}
			
			if ($buy) {
				$maxNums = maxArrayKey($buy, 'nums') / 2;
				foreach ($buy as $k => $v) {
					$data['depth']['buy'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1));
					$data['depth']['buypbar'][$k] = ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100);
				}
			} else {
				$data['depth']['buy'] = '';
				$data['depth']['buypbar'] = '';
			}

			if ($sell) {
				$maxNums = maxArrayKey($sell, 'nums') / 2;
				foreach ($sell as $k => $v) {
					$data['depth']['sell'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1));
					$data['depth']['sellpbar'][$k] = ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100);
				}
			} else {
				$data['depth']['sell'] = '';
				$data['depth']['sellpbar'] = '';
			}

			$data_getDepth[$market][$trade_moshi] = $data;
			S('getDepth', $data_getDepth);
		} else {
			$data = $data_getDepth[$market][$trade_moshi];
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function getEntrustAndUsercoin($market = NULL, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			return null;
		}

		if (!C('market')[$market]) {
			return null;
		}

		$result = M()->query('select id,price,num,deal,mum,type,fee,status,addtime from tw_trade where status=0 and market=\'' . $market . '\' and userid=' . userid() . ' order by id desc limit 10;');

		if ($result) {
			foreach ($result as $k => $v) {
				$data['entrust'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
				$data['entrust'][$k]['type'] = $v['type'];
				$data['entrust'][$k]['price'] = $v['price'] * 1;
				$data['entrust'][$k]['num'] = round($v['num'], 6);
				$data['entrust'][$k]['deal'] = round($v['deal'], 6);
				$data['entrust'][$k]['id'] = round($v['id']);
				$data['entrust'][$k]['status'] = $v['status'];
			}
		} else {
			$data['entrust'] = null;
		}

		$userCoin = M('UserCoin')->where(array('userid' => userid()))->find();

		if ($userCoin) {
			$xnb = explode('_', $market)[0];
			$rmb = explode('_', $market)[1];
			$data['usercoin']['xnb'] = floatval($userCoin[$xnb]);
			$data['usercoin']['xnbd'] = floatval($userCoin[$xnb . 'd']);
			$data['usercoin']['rmb'] = floatval($userCoin[$rmb]);
			$data['usercoin']['rmbd'] = floatval($userCoin[$rmb . 'd']);
		} else {
			$data['usercoin'] = null;
		}
		// 处理开盘闭盘交易时间===开始
		$times = date('G',time());
		$minute = date('i',time());
		$minute = intval($minute);
		$data['time_state'] = 0;
		if (($times <= C('market')[$market]['start_time'] && $minute < intval(C('market')[$market]['start_minute']))|| ($times > C('market')[$market]['stop_time'] && $minute>= intval(C('market')[$market]['stop_minute']))) {
			$data['time_state'] = 1;
		}
		if (($times <C('market')[$market]['start_time'] )|| $times > C('market')[$market]['stop_time']) {
			$data['time_state'] = 1;
		} else {
			if ($times == C('market')[$market]['start_time']) {
				if ($minute< intval(C('market')[$market]['start_minute'])) {
					$data['time_state'] = 1;
				}
			} elseif ($times == C('market')[$market]['stop_time']) {
				if (( $minute > C('market')[$market]['stop_minute'])) {
					$data['time_state'] = 1;
				}
			}
		}
		// 处理周六周日是否可交易===开始
		$weeks = date('N',time());
		if(!C('market')[$market]['agree6']){
			if($weeks == 6){
				$data['time_state'] = 1;
			}
		}
		if(!C('market')[$market]['agree7']){
			if($weeks == 7){
				$data['time_state'] = 1;
			}
		}
		//处理周六周日是否可交易===结束
		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}


}
?>