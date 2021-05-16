<?php
namespace Home\Controller;

class QueueController extends HomeController{
	
	
    public function index(){
        foreach (C('market') as $k => $v) {}
        foreach (C('coin_list') as $k => $v) {}
    }
	
	//转入发送短信
	public function zr_fasong($coin,$num){
		$coin=strtoupper($coin);
		$config=M('Config')->field('mobile_fs_zr,smsqm,smspass,smsname')->where(array('id' => 1))->find();
		$mobile=$config['mobile_fs_zr'];
		if($mobile){
			$content = "转入". $coin.':'.$num;
			$sign = "【".$config['smsqm']."】";
			$smsapi = "http://api.smsbao.com/";
			$user = $config['smsname']; //短信平台帐号
			$pass = md5($config['smspass']); //短信平台密码
			$content = $sign.$content;
			$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$mobile."&c=".urlencode($content);
			$result =file_get_contents($sendurl);
		}
	}
	
    public function checkYichang(){
        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables tw_trade write');
        $Trade = M('Trade')->where('deal > num')->order('id desc')->find();
        if ($Trade) {
            if ($Trade['status'] == 0) {
                $mo->table('tw_trade')->where(array('id' => $Trade['id']))->save(array('deal' => Num($Trade['num']), 'status' => 1));
            } else {
                $mo->table('tw_trade')->where(array('id' => $Trade['id']))->save(array('deal' => Num($Trade['num'])));
            }
            $mo->execute('commit');
            $mo->execute('unlock tables');
        } else {
            $mo->execute('rollback');
            $mo->execute('unlock tables');
        }
    }
	
    public function checkUsercoin()
    {
        foreach (C('coin') as $k => $v) {}
    }

    public function yichang()
    {
        foreach (C('market') as $k => $v) {
            $this->setMarket($v['name']);
        }
        foreach (C('coin_list') as $k => $v) {
            $this->setcoin($v['name']);
        }

        //$this->chack_dongjie_coin();
    }


    public function chack_dongjie_coin()
    {
        $max_userid = S('queue_max_userid');
        if (!$max_userid) {
            $max_userid = M('User')->max('id');
            S('queue_max_userid', $max_userid);
        }

        $zuihou_userid = S('queue_zuihou_userid');
        if (!$zuihou_userid) {
            $zuihou_userid = M('User')->min('id');
        }

        $x = 0;

        for (; $x <= 30; $x++) {
            if ($max_userid < ($zuihou_userid + $x)) {
                S('queue_zuihou_userid', null);
                S('queue_max_userid', null);
                break;
            } else {
                S('queue_zuihou_userid', $zuihou_userid + $x + 1);
            }

            $user = M('UserCoin')->where(array('userid' => $zuihou_userid + $x))->find();

            if (is_array($user)) {
                foreach (C('coin_list') as $k => $v) {
                    if (0 < $user[$v['name'] . 'd']) {
                        /*$mo = M();
                        $mo->execute('set autocommit=0');
                        $mo->execute('lock tables tw_user_coin write  , tw_trade write ');*/
                        $rs = array();
                        $rs = M('Trade')->where(array(
                            'market' => $v['name'] . "_cny",
                            'status' => 0,
                            'userid' => $user['userid']
                        ))->find();

                        if (!$rs) {
                            M('UserCoin')->where(array('userid' => $user['userid']))->setField($v['name'] . 'd', 0);
                        }
                    }
                }
            }
        }
    }

    public function move()
    {
        M('Trade')->where(array('status' => '-1'))->setField('status', 1);

        foreach (C('market') as $k => $v) {
            $this->setMarket($v['name']);
        }
        foreach (C('coin_list') as $k => $v) {
            $this->setcoin($v['name']);
        }
    }

    public function setMarket($market = NULL)
    {
        // 过滤非法字符----------------S
        if (checkstr($market)) {
            $this->error('您输入的信息有误！');
        }
        // 过滤非法字符----------------E

        if (!$market) {
            return null;
        }

        $market_json = M('Market_json')->where(array('name' => $market))->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = M('TradeLog')->where(array('market' => $market))->order('addtime asc')->find()['addtime'];
        }

        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
        $trade_num = M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array(
                array('egt', $start),
                array('elt', $end)
            )
        ))->sum('num');

        if ($trade_num) {
            $trade_mum = M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('mum');
            $trade_fee_buy = M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee_buy');
            $trade_fee_sell = M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee_sell');
			
            $d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);

            if (M('Market_json')->where(array('name' => $market, 'addtime' => $end))->find()) {
                M('Market_json')->where(array('name' => $market, 'addtime' => $end))->save(array('data' => json_encode($d)));
            } else {
                M('Market_json')->add(array('name' => $market, 'data' => json_encode($d), 'addtime' => $end));
            }
        } else {
            $d = null;

            if (M('Market_json')->where(array('name' => $market, 'data' => ''))->find()) {
                M('Market_json')->where(array('name' => $market, 'data' => ''))->save(array('addtime' => $end));
            } else {
                M('Market_json')->add(array('name' => $market, 'data' => '', 'addtime' => $end));
            }
        }
    }

    public function setcoin($coinname = NULL)
    {
        // 过滤非法字符----------------S
        if (checkstr($coinname)) {
            $this->error('您输入的信息有误！');
        }
        // 过滤非法字符----------------E

        if (!$coinname) {
            return null;
        }

        if (C('coin')[$coinname]['type'] == 'qbb') {
            $dj_username = C('coin')[$coinname]['dj_yh'];
            $dj_password = C('coin')[$coinname]['dj_mm'];
            $dj_address = C('coin')[$coinname]['dj_zj'];
            $dj_port = C('coin')[$coinname]['dj_dk'];
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                return null;
            }

            $data['trance_mum'] = $json['balance'];
        } else {
            $data['trance_mum'] = 0;
        }

        $market_json = M('CoinJson')->where(array('name' => $coinname))->order('id desc')->find();
        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = M('Myzr')->where(array('name' => $coinname))->order('id asc')->find()['addtime'];
        }

        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));

        if ($addtime) {
            if ((time() + (60 * 60 * 24)) < $addtime) {
                return null;
            }

            $trade_num = M('UserCoin')->where(array(
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum($coinname);
            $trade_mum = M('UserCoin')->where(array(
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum($coinname . 'd');
            $aa = $trade_num + $trade_mum;

            if (C($coinname)['type'] == 'qbb') {
                $bb = $json['balance'];
            } else {
                $bb = 0;
            }

            $trade_fee_buy = M('Myzr')->where(array(
                'name' => $coinname,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee');
            $trade_fee_sell = M('Myzc')->where(array(
                'name' => $coinname,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee');
			
            $d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

            if (M('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->find()) {
                M('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->save(array('data' => json_encode($d)));
            } else {
                M('CoinJson')->add(array('name' => $coinname, 'data' => json_encode($d), 'addtime' => $end));
            }
        }
    }

    public function paicuo()
    {
        foreach (C('market') as $k => $v) {}
    }
	
	// 更新市场价格
    public function houprice()
    {
        foreach (C('market') as $k => $v) {
            if (!$v['hou_price'] || (date('H', time()) == '0')) {
                $t = time();
                $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
                $hou_price = M('TradeLog')->where(array(
                    'market' => $v['name'],
                    'addtime' => array('lt', $start)
                ))->order('id desc')->getField('price');

                if (!$hou_price) {
                    $hou_price = M('TradeLog')->where(array('market' => $v['name']))->order('id asc')->getField('price');
                }

                M('Market')->where(array('name' => $v['name']))->setField('hou_price', $hou_price);
                S('home_market', null);
            }
            echo $hou_price;
        }
    }
	
	/** 同步钱包转入记录 **/
    public function qianbao()
    {
        $coinList = M('Coin')->where(array('status' => 1))->select();
	//echo var_dump($coinList);
        foreach ($coinList as $k => $v) {
		echo $coin = "xxxx: " .$v['name'] . PHP_EOL;
            if ($v['type'] != 'qbb') {
                continue;
            }

            $coin = $v['name'];
            $coinid = $v['id'];
			
            //if ($coin == 'usdt') {
            //    continue;
            //    $this->usdt();
            //    continue;
            //}
            if (!$coin) {
                echo 'MM';
                continue;
            }
            if ($coin == 'eth') {
                $this->ethonlinea88b77c11d0a9d();
                continue;
            }
            if ($coin == 'etc') {
                $this->etconlinea88b77c11d0a9d();
                continue;
            }
            if ($coin == 'usdt') {
                $this->tokensonlinea88b77c11d0a9d('usdt');
                continue;
            }
            if ($coin == 'uti') {
                $this->tokensonlinea88b77c11d0a9d('uti');
                continue;
            }
            /*
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];
            $candh = C('coin')[$coin]['change'];
            $cancoin = C('coin')[$coin]['changecoin'];
            */
            
            $dj_username = $v['dj_yh'];
            $dj_password = $v['dj_mm'];
            $dj_address = $v['dj_zj'];
            $dj_port = $v['dj_dk'];
            $candh = $v['change'];
            $cancoin = $v['changecoin'];

            //分级推广赠送百分比
            // $type_give['type1_give']=C('coin')[$coin]['type1_give']/100;
            // $type_give['type2_give']=C('coin')[$coin]['type2_give']/100;
            // $type_give['type3_give']=C('coin')[$coin]['type4_give']/100;

            if ($candh == 1) {
                $setcoin = $cancoin;
                $rate = $v['huilv'];
            } else {
                $setcoin = $coin;
                $rate = 1;
            }
			
            echo 'start '.$coin."\n";
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();
            if (!isset($json['version']) || !$json['version']) {
                echo '###ERR#####***** '.$coin.' connect fail***** ####ERR####>'."\n";
                continue;
            }

            echo 'Cmplx '.$coin.' start,connect '.(empty($CoinClient) ? 'fail' : 'ok').' :'."\n";
            $listtransactions = $CoinClient->listtransactions('*', 1000, 0);
            echo 'listtransactions:'.count($listtransactions)."\n";
            $omnilist = $CoinClient->omni_listtransactions();
            if ($listtransactions != "nodata") {
                krsort($listtransactions);
                foreach ($omnilist as $k => $v) {
                    $omnitxid[$k] = $v['txid'];
                }
                foreach ($listtransactions as $trans) {
                    if (!$trans['account']) {
                        echo 'empty account continue' . "\n";
                        continue;
                    }
                    if (in_array($trans['txid'], $omnitxid)) {
                        echo 'USDT find,continue!' . "\n";
                        continue;
                    }
                    if (!($user = M('User')->where(array('username' => $trans['account']))->find())) {
                        echo 'no account find continue' . "\n";
                        continue;
                    }

                    if (M('Myzr')->where(array('txid' => $trans['txid'], 'status' => '1', 'username' => $trans['address']))->find()) {
                        echo 'TXID & ADDR find,continue.' . "\n";
                        continue;
                    }
                    echo 'all check ok ' . "\n";

                    if ($trans['category'] == 'receive') {
                        echo 'start receive do:' . "\n";
                        $sfee = 0;
                        $true_amount = $trans['amount'];

                        if ($trans['confirmations'] < $v['zr_dz']) {
                            echo $trans['account'].' confirmations '.$trans['confirmations'].' not elengh '.C('coin')[$coin]['zr_dz'].' continue '."\n";
                            echo 'confirmations <  c_zr_dz continue' . "\n";

                            if ($res = M('myzr')->where(array('txid' => $trans['txid'], 'userid' => $user['id']))->find()) {
                                M('myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - C('coin')[$coin]['zr_dz'])));
                            } else {
                                M('myzr')->add(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - C('coin')[$coin]['zr_dz'])));
                            }

                            continue;
                        } else {
                            echo $trans['txid'] . 'confirmations full.' . "\n";
                        }
						$sfzj=0;
                        try {
                            $mo = M();
                            $mo->execute('set autocommit=0');
                            $mo->execute('lock tables tw_user_coin write ,tw_myzr write ,tw_finance_log write');

                            $user_zj_coin = $mo->table('tw_user_coin')->where(array('userid' => $user['id']))->find();

                            $rs = array();
                            $rs[] = $mo->table('tw_user_coin')->where(array('userid' => $user['id']))->setInc($setcoin, ($trans['amount'] / $rate));
                            
                            if ($res = $mo->table('tw_myzr')->where(array('txid' => $trans['txid'], 'userid' => $user['id']))->find()) {
                                echo 'tw_myzr find and set status 1.';
                                $rs[] = $mo->table('tw_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                            } else {
                                echo 'tw_myzr not find and add a new tw_myzr.' . "\n";
								$sfzj=1;
                                $rs[] = $mo->table('tw_myzr')->add(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => 1));
                            }

                            // 处理资金变更日志-----------------S
							
							$user_zjw_coin = $mo->table('tw_user_coin')->where(array('userid' => $user['id']))->find();
                            $rs[] = $mo->table('tw_finance_log')->add(array('username' => $user['username'], 'adminname' => $trans['address'], 'addtime' => time(), 'plusminus' => 1, 'amount' => $trans['amount'], 'optype' => 7, 'position' => 1, 'cointype' => $coinid, 'old_amount' => $user_zj_coin[$coin], 'new_amount' => $user_zjw_coin[$coin], 'userid' => $user['id'], 'adminid' => session('userId'), 'addip' => '钱包地址'));

                            // 处理资金变更日志-----------------E

                            if (check_arr($rs)) {
                                $mo->execute('commit');
                                echo $trans['amount'] . ' receive ok ' . $coin . ' ' . $trans['amount'];
                                $mo->execute('unlock tables');
							
                                echo 'commit ok' . "\n";
                            } else {
                                throw new \Think\Exception('receive fail');
                            }
                        } catch (\Think\Exception $e) {
                            echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                            // echo var_export($e, true);
                            $mo->execute('rollback');
                            $mo->execute('unlock tables');
                            echo 'rollback ok.' . "\n";
                        }
                    }
                }
            }

            if ($trans['category'] == 'send') {
                echo 'start send do:' . "\n";
                if (3 <= $trans['confirmations']) {
                    $myzc = M('Myzc')->where(array('txid' => $trans['txid']))->find();
                    if ($myzc) {
                        if ($myzc['status'] == 0) {
                            M('Myzc')->where(array('txid' => $trans['txid']))->save(array('status' => 1));
                            echo $trans['amount'].'成功转出'.$coin.' 确定';
                        }
                    }
                }
            }
        }
    }
	
	/** 计算趋势,每天运行一次即可 **/
	public function tendency()
	{
		foreach (C("market") as $k => $v) {
			echo "----计算趋势----" . $v["name"] . "------------<br>";
			$tendency_time = 4; //间隔时间4小时
			$t = time();
			$tendency_str = $t - (24 * 60 * 60 * 3); //当前时间的3天前

			for ($x = 0; $x <= 18; $x++) { //18次,72个小时
				$na = $tendency_str + (60 * 60 * $tendency_time * $x);
				$nb = $tendency_str + (60 * 60 * $tendency_time * ($x + 1));
				$b = M("TradeLog")->where("addtime >=" . $na . " and addtime <" . $nb . " and market ='" . $v["name"] . "'")->max("price");

				if (!$b) { $b = 0; }
				$rs[] = array($na, $b);
			}

			M("Market")->where(array("name" => $v["name"]))->setField("tendency", json_encode($rs));
			unset($rs);
			echo "计算成功!";
			echo "\n";
		}
		echo "趋势计算0k " . "\n";
	}
	

    function overtimedd()
    {
        //删除超过30分钟未成交的虚假订单
        $map['addtime'] = array('lt', (time() - 60 * 30));
        $map['userid'] = 0;
        $map['status'] = 0;
        $deldd = M('trade')->where($map)->delete();
        if ($deldd) {
            echo '已清除' . $deldd . '条数据';
            exit;
        } else {
            echo '没有可以清除的数据';
            exit;
        }

    }

    public function getmarketlist()
    {
        $marketlist = M('market')->select();
        $sdmarket = array();
        foreach ($marketlist as $k => $v) {
            # code...
            if ($v['shuadan'] == 1) {
                $sdmarket[$k]['name'] = $v['name'];
            }
        }
        if ($sdmarket) {
            exit (json_encode($sdmarket));
        } else {
            return 'error';
        }
    }
    
    
    //获取usdt和niumc价格
    public function hangqing(){
    	$url='https://otc.bkex.co/api/otc/advertising/indexPrice';
        $content = file_get_contents($url);
        $content = json_decode($content, true);
        $usdt = round($content['data'][0]['price'], 5);
        
        $staxx=M('config')->where(array('id' => 1))->find();
        
        if($usdt<=0){
        	$usdt=$staxx['usdt'];
        }
        /*
        $url2='https://api.bkex.co/v1/q/ticker?&precision=2&pair=BTC_USDT';
        $content2 = file_get_contents($url2);
        $content2 = json_decode($content2, true);
        $btc_usdt = round($content2['data']['c'], 5);
        
        $btc=intval($btc_usdt*$usdt*100000)/100000;
        $btc_zd = round($content2['data']['r'], 2);
        //echo $usdt.'/'.$niumc_usdt.'/'.$niumc;
        if($btc<=0){
        	$usdtje=M('config')->field('btc')->where(array('id' => 1))->find();
        	$btc=$usdtje['btc'];
        }
        */
        $map=array();
        if($staxx['usdt_sta']==1){
	        $map['usdt']=$usdt;
	        //$map['btc']=$btc;
	        $map['usdtlast']=time();
        }
        //$map['btclast']=time();
        //$niumc_coin=M('Coin')->where("name='niumc'")->save(array('market_zhangdie'=>$niumc_zd));
        if(empty($map)){
        	echo 'no';exit;
        }
        $config = M('config')->where(array('id' => 1))->save($map);
        if ($config) {
            echo 'OK';exit;
        }
    }
    
    //获取usdt和niumc价格
    public function hangqing2(){
    	$url='https://otc.bkex.co/api/otc/advertising/indexPrice';
        $content = file_get_contents($url);
        $content = json_decode($content, true);
        $usdt = round($content['data'][0]['price'], 5);
        
        $url2='https://api.bkex.co/v1/q/ticker?&precision=2&pair=NIUMC_USDT';
        $content2 = file_get_contents($url2);
        $content2 = json_decode($content2, true);
        $niumc_usdt = round($content2['data']['c'], 5);
        $niumc=intval($niumc_usdt*$usdt*100000)/100000;
        //echo $usdt.'/'.$niumc_usdt.'/'.$niumc;
        $usdt_price = M('UsdtPrice')->add(array('price'=>$usdt,'addtime'=>time()));
        $niumc_price = M('NiumcPrice')->add(array('price'=>$niumc,'addtime'=>time()));
        if ($usdt_price && $niumc_price) {
            echo 'OK';
        }
    }
	
	// 行情爬虫 CoinMarketCap
    public function btctormb()
    {
         $url='https://api.kucoin.com/v1/open/currencies';
        //$url1 = 'https://api.coinmarketcap.com/v1/ticker/bitcoin/?convert=CNY';//okcoin,不用美元转换了
        //$url2 = 'https://api.coinmarketcap.com/v1/ticker/Ethereum/?convert=CNY';//okcoin,不用美元转换了
        $url3 = 'https://api.coinmarketcap.com/v1/ticker/tether/?convert=CNY';//okcoin,不用美元转换了
		
		//$content1 = file_get_contents($url1);
        //$content2 = file_get_contents($url2);
        $content3 = file_get_contents($url3);
      
       // $content1 = json_decode($content1, true);
       // $content2 = json_decode($content2, true);
        $content3 = json_decode($content3, true);
        // dump($content1);
		
        //$btc = round($content1[0]['price_cny'], 2);
        //$eth = round($content2[0]['price_cny'], 2);
        $usdt = round($content3[0]['price_cny'], 2);
		
        //$btclast = $content1[0]['last_updated'];
        //$ethlast = $content2[0]['last_updated'];
		$usdtlast = $content3[0]['last_updated'];
		
        // $rmb=$content['data']['rates']['BTC']['CNY'];
        // dump($btclast);
		$dq_xx=M('config')->field('jqyb_hv')->where('id=1')->find();
        //$map['usdt'] = intval(($usdt - ($usdt * 0.01))/$dq_xx['jqyb_hv']*10000)/10000; //价格做过额外调整
		//$map['btc'] = intval($btc/$dq_xx['jqyb_hv']*10000)/10000;
        //$map['eth'] = intval($eth/$dq_xx['jqyb_hv']*10000)/10000;
		$map['usdt']=$usdt;
		$map['usdtlast'] = $usdtlast;
       // $map['btclast'] = $btclast;
        //$map['ethlast'] = $ethlast;
        // if($content1['ticker']['last']>0 or $content2['ticker']['last']>0){
        $config = M('config')->where(array('id' => 1))->save($map);
        // }
        if ($config) {
            echo 'OK';
        }
    }
	
	// 行情爬虫 gate.io比特儿海外
    public function gatetormb()
    {
		$config = M('config')->where(array('id' => 1))->find();
		
		$marketData = M('Market')->where(array('shuadan' => 1,'sdtype' => 1))->select();
		foreach ($marketData as $k => $v) {
			
			if (!$v['name']) {
				echo '交易市场错误'; die();
			} else {
				$xnb[$k] = explode('_', $v['name'])[0];
				$rmb[$k] = explode('_', $v['name'])[1];
			}
			
			if ($rmb[$k] == Anchor_CNY) {
				$rmb2[$k] = 'usdt';
			} else {
				$rmb2[$k] = $rmb[$k];
			}
			
			$url = 'http://data.gateio.io/api2/1/ticker/'.$xnb[$k].'_'.$rmb2[$k];
			$content = file_get_contents($url);
			$content = json_decode($content, true);
			//print_r($content);
			
			if ($rmb[$k] == Anchor_CNY) {
				$content['lowestAsk'] = $content['lowestAsk'] * $config['usdt']; //卖一价
				$content['highestBid'] = $content['highestBid'] * $config['usdt']; //买一价
				$wei = 10000000;
				if (floatval($content['lowestAsk']) < 10) {
					$wei = 10000000;
				}
				if (floatval($content['highestBid']) < 10) {
					$wei = 10000000;
				}
			} else {
				$wei = 10000000;
				if (floatval($content['lowestAsk']) < 10) {
					$wei = 10000000;
				}
				if (floatval($content['highestBid']) < 10) {
					$wei = 10000000;
				}
			}
			
			$min_price = $content['highestBid'] * $wei; //最低价格
			$max_price = $content['lowestAsk'] * $wei; //最高价格

            if ($max_price < $min_price) {
                $min_price = $max_price;
                $max_price = $min_price;
            }
			
			$map['sdlow'] = round($min_price / $wei, 6); //最低价格
			$map['sdhigh'] = round($max_price / $wei, 6); //最高价格
			
			if (M('Market')->where(array('name' => $v['name']))->save($map)) {
				if (!$map['sdlow'] || !$map['sdhigh']) {
					echo $v['name'].' - Error 1'.'<br>';
				} else {
					echo $v['name'].' - OK'.'<br>';
				}
			} else {
				echo $v['name'].' - Error 2'.'<br>';
			}
		}
		
		if (!$marketData) {
			echo '查询设置不存在';
		}
    }
	
    function randomFloat($min, $max)
    {
		//生成随机浮点数
        // return $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 1);
    }

    function ccapikey()
    {
        header('Content-type: application/json');
        $accesskey = 'RkAyda9huaQYux6R'; //定义APIKEY
        if ($_POST['accesskey'] || $_GET['accesskey']) {
            if (!$_GET['user'] || !$_GET['userid'] || !$_GET['account'] || !$_GET['apikey']) {
                $result = array('code' => 0, 'msg' => 'Parmars is lost!');
                echo json_encode($result);
                exit;
            }
            $user = M('user')->where(array('username' => $_GET['account'], 'apikey' => $_GET['apikey']))->find();
            if (!$user) {
                $result = array('code' => 0, 'msg' => 'User or APIKEY not found!');
                echo json_encode($result);
                exit;
            } elseif ($user['otcuser'] or $user['otcuserid']) {
                $result = array('code' => 0, 'msg' => 'User alreay binded!');
                echo json_encode($result);
                exit;
            } else {
                $map['otcuser'] = $_GET['user'];
                $map['otcuserid'] = $_GET['userid'];
                $rs = M('user')->where(array('username' => $_GET['account'], 'apikey' => $_GET['apikey']))->save($map);
                if ($rs) {
                    $result = array('code' => 1, 'msg' => 'BIND Success!', 'user' => $user['username'], 'userid' => $user['id']);
                    echo json_encode($result);
                    exit;
                } else {
                    $result = array('code' => 0, 'msg' => 'Bind Failed!');
                    echo json_encode($result);
                    exit;
                }
            }
        } else {
            echo 'Yo!';
        }
    }

    function sendbb()
    {
        header('Content-type: application/json');
        // $accesskey = 'RkAyda9huaQYux6R';//定义APIKEY
        if ($_GET['accesskey'] && $_GET['accesskey'] == C('BBAPIKEY')) {
            // if($_POST['accesskey'] ||$_GET['accesskey']){
            if (!$_GET['user'] || !$_GET['num'] || !$_GET['coin']) {
                $result = array('code' => 0, 'msg' => 'Parmars is lost!');
                echo json_encode($result);
                exit;
            }
            $user = M('user')->where(array('username' => $_GET['user']))->find();
            $coin = M('coin')->where(array('name' => $_GET['coin'], 'status' => 1))->find();
            $usercoin = M('user_coin')->where(array('userid' => $user['id']))->find();
            if (!$user) {
                $result = array('code' => 0, 'msg' => 'User  not found!');
                echo json_encode($result);
                exit;
            } elseif (!$coin) {
                $result = array('code' => 0, 'msg' => 'Coin  not found!');
                echo json_encode($result);
                exit;
            } else {
                try {
                    $mo = M();
                    $mo->startTrans();
                    $rs = array();
                    $rs[] = $mo->table('tw_user_coin')->where(array('userid' => $user['id']))->setInc($coin['name'], $_GET['num']);

                    $rs[] = $mo->table('tw_myzr')->add(array('userid' => $user['id'], 'username' => '转入自场外交易', 'coinname' => $coin['name'], 'txid' => '转入自场外交易用户:' . $user['username'], 'num' => $_GET['num'], 'fee' => 0, 'mum' => $_GET['num'], 'addtime' => time(), 'status' => 1));

                    // 处理资金变更日志-----------------S
                    $user_zj_coin = $mo->table('tw_user_coin')->where(array('userid' => $user['id']))->find();

                    // 转出人记录
                    $mo->table('tw_finance_log')->add(array('username' => $user['username'], 'adminname' => $user['username'], 'addtime' => time(), 'plusminus' => 0, 'amount' => $_GET['num'], 'optype' => 6, 'position' => 1, 'cointype' => $coin['id'], 'old_amount' => $usercoin[$coin['name']], 'new_amount' => $user_zj_coin[$coin['name']], 'userid' => $user['id'], 'adminid' => $user['id'], 'addip' => get_client_ip()));

                    // 处理资金变更日志-----------------E

                    if (check_arr($rs)) {
                        $mo->commit();
                        $result = array('code' => 1, 'msg' => 'Success!');
                        echo json_encode($result);
                        exit;
                    } else {
                        throw new \Think\Exception('转账失败,请重试01!');
                    }
                } catch (\Think\Exception $e) {
                    $mo->rollback();
                    $result = array('code' => 0, 'msg' => 'Failed,error code:101!');
                    echo json_encode($result);
                    exit;
                }

            }
        } else {
            echo 'Yo!';
        }
    }
	
	/** ETH入账 **/
    function ethonlinea88b77c11d0a9d($coin = 'eth')
    {
        set_time_limit(0);
        ignore_user_abort();
        $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $pay = EthCommon($dj_address, $dj_port);
        $accounts = $pay->personal_listAccounts();//获取钱包地址列表
        foreach ($accounts as $k => $v) {
            if (strtolower($v) != strtolower($dj_username)) {
                $getdz = M()->table('tw_user_coin')->where(array($coin . 'b' => $v))->find();//查找钱包地址对应的账户
                if ($getdz) {
                    $user = M()->table('tw_user')->where(array('id' => $getdz['userid']))->getField('username');
                    $url = 'http://api.etherscan.io/api?module=account&action=txlist&address=' . $v . '&startblock=5900000&endblock=99999999&sort=asc&apikey=ERXIYCNF6PP3ZNQAWICHJ6N5W7P212AHZI';
                    $fanhui = file_get_contents($url);
                    $fanhui = json_decode($fanhui, true);
                    if ($fanhui['message'] == 'OK') {
                        foreach ($fanhui['result'] as $v2) {
                            if ($v2['to'] == $v && $v2['txreceipt_status'] == 1) {
                                $rs1 = M()->table('tw_myzr')->where(array('txid' => $v2['hash']))->find();
                                if (!$rs1) {
                                    $amount = $v2['value'] / 1000000000000000000;
                                    $rs2 = M()->table('tw_myzr')->add(array('userid' => $getdz['userid'], 'username' => $v, 'coinname' => $coin, 'fee' => 0, 'txid' => $v2['hash'], 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => 1));
                                    $rs1 = M()->table('tw_user_coin')->where(array($coin . 'b' => $v))->setInc($coin, $amount);//写入用户余额
                                } else {
                                    echo '交易哈希:' . $v2['hash'] . '的交易记录已存在!<br>';
                                }
                            }
                        }
                    } else {
                        echo '账户:' . $v . '交易记录未查询到!<br>';
                    }
                }
            }
        }
    }
	
	/** ETC入账 **/
    function etconlinea88b77c11d0a9d($coin = 'etc')
    {
        $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $candh = C('coin')[$coin]['change'];
        $cancoin = C('coin')[$coin]['changecoin'];

        if ($candh == 1) {
            $setcoin = $cancoin;
            $rate = C('coin')[$coin]['huilv'];
        } else {
            $setcoin = $coin;
            $rate = 1;
        }
		
        $pay = EthCommon($dj_address, $dj_port);
        $accounts = $pay->personal_listAccounts();//获取钱包地址列表
        foreach ($accounts as $k => $v) {
            if ($v != $dj_username) {
                $getdz = M()->table('tw_user_coin')->where(array($coin . 'b' => $v))->find();//查找钱包地址对应的账户
                // dump($v);
                if ($getdz) {
                    // $qbbalance=$pay->eth_getBalance($v);//查询钱包地址余额10进制
                    $user = M()->table('tw_user')->where(array('id' => $getdz['userid']))->getField('username');
                    // $url='https://etcchain.com/api/v1/getTransactionsByAddress?address='.$v;//通道1
                    $url = 'https://api.gastracker.io/v1/addr/' . $v . '/transactions';//通道2
                    $fanhui = file_get_contents($url);
                    $fanhui = json_decode($fanhui, true);
                    // dump($fanhui);
					
/*                    //通道1
                    if ($fanhui) {
                        foreach ($fanhui as $v2) {
                            if ($v2['to']==$v && $v2['confirmations']>=3) {
                                $rs1= M()->table('tw_myzr')->where(array('txid'=>$v2['hash']))->find();
                                if (!$rs1) {
                                    $amount=$v2['valueEther'];
                                    $rs2=M()->table('tw_myzr')->add(array('userid' =>$getdz['userid'], 'username' => $v, 'coinname' => $coin, 'fee' => 0, 'txid' =>$v2['hash'], 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => 1));
                                     $rs1= M()->table('tw_user_coin')->where(array($coin.'b'=>$v))->setInc($setcoin,$amount/$rate);//写入用户余额
                                } else {
                                    echo '交易哈希:'.$v2['hash'].'的交易记录已存在!<br>';
                                }
                            }
                        }
                    } else {
                        echo '账户:'.$v.'交易记录未查询到!<br>';
                    }*/

                    //通道2
                    if (is_array($fanhui['items'])) {
                        foreach ($fanhui['items'] as $v2) {
                            // dump ($v3['to']);
                            $to = strtolower($v2['to']);
                            // dump($v==$to);
                            $amount = $v2['value']['ether'];
                            if ($v == $to && $v2['confirmations'] >= 3) {
                                $rs1 = M()->table('tw_myzr')->where(array('txid' => $v2['hash']))->find();
                                if (!$rs1) {
                                    $amount = $v2['value']['ether'];
                                    $rs2 = M()->table('tw_myzr')->add(array('userid' => $getdz['userid'], 'username' => $v, 'coinname' => $coin, 'fee' => 0, 'txid' => $v2['hash'], 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => 1));
                                    $rs1 = M()->table('tw_user_coin')->where(array($coin . 'b' => $v))->setInc($setcoin, $amount / $rate);//写入用户余额
                                } else {
                                    echo '交易哈希:' . $v2['hash'] . '的交易记录已存在!<br>';
                                }
                            }
                        }
                    } else {
                        echo '账户:' . $v . '交易记录未查询到!<br>';
                    }
                    //通道2结束
                }
            }
        }
    }

    function etconline($coin = 'etc')
    {
        // $url='https://etcchain.com/api/v1/getTransactionsByAddress?address=0x6b83f808fce08f51adb2e9e35a21a601e702785f';
        $url = 'https://api.gastracker.io/v1/addr/0x22df416f66f5bd61cc43f0e192ba36d066279992/transactions';
        $fanhui = file_get_contents($url);
        $fanhui = json_decode($fanhui, true);
        // dump($fanhui['items']);die;

        $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $candh = C('coin')[$coin]['change'];
        $cancoin = C('coin')[$coin]['changecoin'];
		
        if ($candh == 1) {
            $setcoin = $cancoin;
            $rate = C('coin')[$coin]['huilv'];
        } else {
            $setcoin = $coin;
            $rate = 1;
        }
		
        $pay = EthCommon($dj_address, $dj_port);
        $accounts = $pay->personal_listAccounts();//获取钱包地址列表
        foreach ($accounts as $k => $v) {
            if ($v != $dj_username) {
                $getdz = M()->table('tw_user_coin')->where(array($coin . 'b' => $v))->find();//查找钱包地址对应的账户
                // dump($dj_username);
                if ($getdz) {
                    // $qbbalance=$pay->eth_getBalance($v);//查询钱包地址余额10进制
                    $user = M()->table('tw_user')->where(array('id' => $getdz['userid']))->getField('username');
                    $url = 'https://etcchain.com/api/v1/getTransactionsByAddress?address=' . $v;
                    // $url='https://etcchain.com/api/v1/getTransactionsByAddress?address=0x6b83f808fce08f51adb2e9e35a21a601e702785f';
                    $fanhui = file_get_contents($url);
                    $fanhui = json_decode($fanhui, true);
                    // dump($fanhui);
                    if ($fanhui) {
                        foreach ($fanhui as $v2) {
                            if ($v2['to'] == $v && $v2['confirmations'] >= 3) {
                                $rs1 = M()->table('tw_myzr')->where(array('txid' => $v2['hash']))->find();
                                if (!$rs1) {
                                    $amount = $v2['valueEther'];
                                    $rs2 = M()->table('tw_myzr')->add(array('userid' => $getdz['userid'], 'username' => $v, 'coinname' => $coin, 'fee' => 0, 'txid' => $v2['hash'], 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => 1));
                                    $rs1 = M()->table('tw_user_coin')->where(array($coin . 'b' => $v))->setInc($setcoin, $amount / $rate);//写入用户余额
                                } else {
                                    echo '交易哈希:' . $v2['hash'] . '的交易记录已存在!<br>';
                                }
                            }
                        }
                    } else {
                        echo '账户:' . $v . '交易记录未查询到!<br>';
                    }
                }
            }
        }
    }

    function artsconlinea88b77c11d0a9d($coin = 'arts')
    {
        set_time_limit(0);
        $dj_username = C('coin')[$coin]['dj_yh'];
        $candh = C('coin')[$coin]['change'];
        $cancoin = C('coin')[$coin]['changecoin'];
		
        if ($candh == 1) {
            $setcoin = $cancoin;
            $rate = C('coin')[$coin]['huilv'];
        } else {
            $setcoin = $coin;
            $rate = 1;
        }
		
        $map['artsb'] = array('like', '0x%');
        $accounts = M()->table('tw_user_coin')->where($map)->field('artsb,userid')->select();
        $url = 'http://api.etherscan.io/api?module=account&action=txlist&address=0x228c317d52abd2e389643847c2d859f59680aa1a&startblock=527000&endblock=99999999&sort=asc&apikey=ERXIYCNF6PP3ZNQAWICHJ6N5W7P212AHZI';
        $fanhui = file_get_contents($url);
        $fanhui = json_decode($fanhui, true);
        foreach ($accounts as $k => $v) {
            if ($v['artsb'] != $dj_username) {
                $user = M()->table('tw_user')->where(array('id' => $v['userid']))->getField('username,invit_1');
                if ($fanhui['message'] == 'OK') {
                    foreach ($fanhui['result'] as $v2) {
                        if (strlen($v2['input']) == 138) {//input为区块交易data值
                            $datalist = explode('0x', $v2['input'])[1];
                            $account = substr($datalist, 32, 40);//获取data中的账户
                            $account = '0x' . $account;
                            // dump($account);
                            $amount = substr($datalist, -20);//获取data中的转账数额16进制值
                            $num = hexdec($amount) / 10000;//转化为10进制

                            if ($account == $v['artsb'] && $v2['txreceipt_status'] == 1) {
                                $rs1 = M()->table('tw_myzr')->where(array('txid' => $v2['hash']))->find();
                                if (!$rs1) {
                                    $rs2 = M()->table('tw_myzr')->add(array('userid' => $v['userid'], 'username' => $v['artsb'], 'coinname' => $coin, 'fee' => 0, 'txid' => $v2['hash'], 'num' => $num, 'mum' => $num, 'addtime' => time(), 'status' => 1));
                                    $rs1 = M()->table('tw_user_coin')->where(array($coin . 'b' => $v['artsb']))->setInc($setcoin, $num / $rate);//写入用户余额
                                    echo 'Hash:' . $v2['hash'] . '的交易记录已写入数据库!<br>';
                                } else {
                                    echo 'Hash:' . $v2['hash'] . '的交易记录已存在!<br>';
                                }
                            } else {
                                echo '交易状态:失败!<br>';
                            }
                        }
                    }
                } else {
                    echo '账户:' . $v . '交易记录未查询到!<br>';
                }

            }
        }
    }
	
	/** USDT入账 **/
    public function usdt()
    {
        header("Content-type: text/html; charset=utf-8");
        $coin = 'usdt';
        $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $candh = C('coin')[$coin]['change'];
        $cancoin = C('coin')[$coin]['changecoin'];
		
        if ($candh == 1) {
            $setcoin = $cancoin;
            $rate = C('coin')[$coin]['huilv'];
        } else {
            $setcoin = $coin;
            $rate = 1;
        }
		
        // dump($setcoin);
        $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
      	print_r($CoinClient);
        $json = $CoinClient->getinfo();
        if (!isset($json['version']) || !$json['version']) {
            echo '版本错误!' . "\n";exit;
            continue;
        }
        echo 'USDT start,connect ' . (empty($CoinClient) ? 'fail' : 'ok') . ' :' . "\n";
        $omnilist = $CoinClient->omni_listtransactions('*', 1000, 0);

        foreach ($omnilist as $v) {
            if (M('myzr')->where(array('txid' => $v['txid'], 'status' => 1, 'username' => $v['referenceaddress']))->find()) {
                echo 'TXID:' . $v['txid'] . '转入成功的记录存在.' . "\n";exit;
                continue;
            }

            if (!($userid = M('user_coin')->where(array('usdtb' => $v['referenceaddress']))->find())) {
                echo '系统未找到对应账户' . "\n";exit;
                continue;
            } else {
                $user = M('user')->where(array('id' => $userid['userid']))->find();
            }
            if ($v['confirmations'] < 3) {
                if ($res = M('myzr')->where(array('txid' => $v['txid'], 'username' => $v['referenceaddress']))->find()) {
                    M('myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => intval($v['confirmations'] - 3)));
                } else {
                    M('myzr')->add(array('userid' => $user['id'], 'username' => $v['referenceaddress'], 'coinname' => $coin, 'fee' => $v['fee'], 'txid' => $v['txid'], 'num' => $v['amount'], 'mum' => $v['amount'], 'addtime' => time(), 'status' => intval($v['confirmations'] - 3)));
                }
                continue;
            } else {
                echo '确认次数达到3次,完成.' . "\n";
            }
            // dump($user);
            try {
                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables tw_user write ,tw_myzr write ,tw_user_coin write');

                $user_zj_coin = $mo->table('tw_user')->where(array('id' => $user['id']))->find();

                $rs = array();
                $rs[] = $mo->table('tw_user_coin')->where(array('userid' => $user['id']))->setInc($setcoin, ($v['amount'] / $rate));//根据比例增加币余额

                if ($res = $mo->table('tw_myzr')->where(array('txid' => $v['txid']))->find()) {
                    echo '设置转入记录status为1,完成!';
                    $rs[] = $mo->table('tw_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                } else {
                    echo '转入记录未找到,添加新的记录.' . "\n";
                    $rs[] = $mo->table('tw_myzr')->add(array('userid' => $user['id'], 'username' => $v['referenceaddress'], 'coinname' => $coin, 'fee' => $v['fee'], 'txid' => $v['txid'], 'num' => $v['amount'], 'mum' => $v['amount'], 'addtime' => time(), 'status' => 1));
                    // dump($mo);
                }

                if (check_arr($rs)) {
                    $mo->execute('commit');
                    echo $v['amount'] . ' 转入完成,USDT:' . $v['amount'];
                    $mo->execute('unlock tables');
                    echo '确认完成' . "\n";
                } else {
                    throw new \Think\Exception('receive fail');
                }
            } catch (\Think\Exception $e) {
                echo $v['amount'] . 'receive fail ' . $coin . ' ' . $v['amount'];
                // echo var_export($rs, true);
                $mo->execute('rollback');
                $mo->execute('unlock tables');
                echo 'rollback ok' . "\n";
            }
        }
    }

	/** 查询区块高度进行补单（转入不到账使用） **/
    public function tokensonlinea88b77c11d0a9d($coin,$block = NULL)
	{
		set_time_limit(0);
		ignore_user_abort();

		//Token合约设置 FFF
		$coin_config = M('Coin')->where(array('name' => $coin))->find();
		$addr = $coin_config['dj_hydz']; //ERC20合约地址
		$wei = 1e18; //手续费
		if($coin=='usdt'){
			$wei = 1000000;
		}
		
		if($coin=='wicc'){
			$addr = '0x4f878c0852722b0976a955d68b376e4cd4ae99e5';
			$wei = 1e8;
		}
		if($coin=='nuls'){
			$addr = '0xb91318f35bdb262e9423bc7c7c2a3a93dd93c92c';
		}
		if($coin=='zil'){
			$addr = '0x05f4a42e251f2d52b8ed15e9fedaacfcef1fad27';
			$wei = 1e12;
		}
		if($coin=='noc'){
			$addr ='0x2563c68650779d004a250be1d5cbe8b9b29177fd';
		}
		if($coin=='trx'){
			$addr = '0xf230b790e05390fc8295f4d3f60332c93bed42e2';
			$wei = 1e6;
		}
/*		if($coin=='fff'){
			$addr = '0xe045e994f17c404691b238b9b154c0998fa28aef';
		}*/
		
		if(!$addr){
			echo 'ERC20合约地址不存在';
			die();
		}
		
		
		$dj_username = C('coin')[$coin]['dj_yh'];
		$map[$coin . 'b'] = array('like', '0x%');
		$accounts = M()->table('tw_user_coin')->where($map)->field($coin . 'b,userid')->select();
               // echo M()->getLastSql() . PHP_EOL;
		//print_r($accounts);echo '<br>';print_r($map);return;
		
        $getblock = 'http://api.etherscan.io/api?module=proxy&action=eth_blockNumber&apikey=YourApiKeyToken';
        $blockn= file_get_contents($getblock);
        $blockn= json_decode($blockn, true);
        $blockn= explode('0x', $blockn['result'])[1];

        if ($block) {
            $lastblock = $block;
            $fromblock = $block;
        } else {
            $lastblock = hexdec($blockn);
            $fromblock = $lastblock - 500;
        }
		
		$url = 'http://api.etherscan.io/api?module=account&action=txlist&address='.$addr.'&startblock='.$fromblock.'&endblock='.$lastblock.'&sort=asc&apikey=ERXIYCNF6PP3ZNQAWICHJ6N5W7P212AHZI';
		echo $url . PHP_EOL;
		$fanhui = file_get_contents($url);
		$fanhui = json_decode($fanhui, true);
		//echo $coin . PHP_EOL;
		//echo $addr . PHP_EOL;
		//echo 'start:'.$coin.'.<br>'. PHP_EOL;
		//echo 'dj_username:'.$dj_username . PHP_EOL;
		foreach ($accounts as $k => $v) {
			echo "ccc: " . $v[$coin . 'b'] . PHP_EOL;
			if ($v[$coin . 'b'] != $dj_username) {
				$user = M()->table('tw_user')->where(array('id' => $v['userid']))->getField('username');
				if ($fanhui['message'] == 'OK') {
					foreach ($fanhui['result'] as $v2) {
						if (strlen($v2['input']) == 138) {//input为区块交易data值
							$datalist = explode('0x', $v2['input'])[1];
							$account = substr($datalist, 32, 40);//获取data中的账户
							$account = '0x' . $account;
							$amount = substr($datalist, -26);//获取data中的转账数额16进制值
							
							$num = hexdec($amount) / $wei;//转化为10进制
							
							$jyzhi_add=$coin_config['zr_jyz']*$num;
							
							if ($account == $v[$coin . 'b']) {
								if ($v2['txreceipt_status'] == 1) {
									$rs1 = M()->table('tw_myzr')->where(array('txid' => $v2['hash']))->find();
									if (!$rs1) {
										$rs2 = M()->table('tw_myzr')->add(array('userid' => $v['userid'], 'username' => $v[$coin . 'b'], 'coinname' => $coin, 'fee' => 0, 'txid' => $v2['hash'], 'num' => $num,'jyz'=>$jyzhi_add, 'mum' => $num, 'addtime' => time(), 'status' => 1,'cover' => 1,'fromaddress'=>$v2['from']));
										$rs1 = M()->table('tw_user_coin')->where(array($coin . 'b' => $v[$coin . 'b']))->setInc($coin, $num);//写入用户余额
										echo 'Hash:' . $v2['hash'] . '的交易记录已写入数据库!<br>';
										$file = 'log/add_' .  date("Y-m-d",time()) . '_log.txt';
										$msg = date("Y-m-d H:i:s",time())." 帐号: " . $account. " 入帐金额: " . $num . " 成功 交易号: " . $v2['hash'];
										file_put_contents($file, $msg . PHP_EOL, FILE_APPEND);
										
										if($coin_config['zr_jyz']>0){
											echo '充币经验值!<br>';
											
											if($jyzhi_add>0){
												echo '充币经验值!数量<br>'.$jyzhi_add;
												$jyz_usercoin=M('UserCoin')->field('jyzhi')->where("userid=".$v['userid'])->find();
												if($jyz_usercoin){
													echo '充币经验值!数量<br>'.$jyzhi_add;
													M('UserCoin')->where("userid=".$v['userid'])->setInc('jyzhi',$jyzhi_add);
													M('Finance')->add(array('userid'=>$v['userid'],'coin'=>'jyzhi','coinname'=>'经验值','fee'=>$jyzhi_add,'num'=>$jyz_usercoin['jyzhi'],'mum'=>($jyz_usercoin['jyzhi']+$jyzhi_add),'type'=>1,'name'=>'zhuanru','remark'=>'转入币','protypemas'=>'转入币','czfee'=>$num,'addtime'=>time(),'protype'=>18,'status'=>1));
													
													$ztren_num=M('User')->field('id')->where('idstate=2 and zt_ren='.$v['userid'])->count();
													if($ztren_num<=0){
														$ztren_num=0;
													}
													$jyzhi_zong=$jyz_usercoin['jyzhi']+$jyzhi_add;
													$level_user=M('LevelUser')->field('id')->where('empirical_num<='.$jyzhi_zong.' and zt_ren<='.$ztren_num)->order('id desc')->find();
													if($level_user){
														M('User')->where('id='.$v['userid'])->save(array('level_user'=>$level_user['id'],'time_xg'=>time()));
													}
												}
											}
										}
										$this->zr_fasong($coin,$num);
									} else {
										echo 'Hash:' . $v2['hash'] . '的交易记录已存在!<br>';
									}
								} else {
									echo '交易状态:失败!<br>';
								}
							}
						}
					}
				} else {
					echo '账户:' . $v[$coin . 'b'] . '交易记录未查询到!<br>';
				}

			}
		}
	}
	
	//更新个人等级
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
	/** ETH钱包余额汇总至总钱包 **/
    function ethcovera99b88c77d66e55($coin = 'eth')
    {
        set_time_limit(0);
        ignore_user_abort();
		
		$coin_config = M('Coin')->where(array('name' => 'eth'))->find();
		$waddress = $coin_config['dj_yh'];
		if (!$waddress) {echo $coin.'无法汇总，钱包公钥地址设置';exit;}
		
        $mainbase = strtolower($waddress); //写死,汇总到钱包的公钥
        $eos = strtolower($waddress); //这是转出代币账户,保留余额
        $tip = strtolower($waddress); //这是转出代币账户,保留余额
		
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $pay = EthCommon($dj_address, $dj_port);
        $accounts = $pay->personal_listAccounts();//获取钱包地址列表
		//print_r($accounts);echo $dj_password;echo $mainbase;die(); //调试输出钱包地址
        $gasprice = $pay->eth_gasPrice();
		
        if (!$mainbase || !$dj_password) {echo '未设置主账户或密码!<br>';exit;}
        if (in_array($mainbase, $accounts)) {echo '账户存在<br>';} else {echo '账户不存在<br>';exit;}
		
        foreach ($accounts as $k => $v) {
            if (strtolower($v) != $mainbase or strtolower($v) != $eos or strtolower($v) != $tip) {
                $getdz = M()->table('tw_user_coin')->where(array($coin . 'b' => $v))->find();//查找钱包地址对应的账户
                if ($getdz) {
                    $qbbalance = $pay->eth_getBalancehex($v);//查询钱包地址余额,0x格式
                    $showmoney = $pay->fromWei($qbbalance);//查询钱包地址余额10进制
                    $realvalue = $showmoney - 0.00021;//查询钱包地址余额10进制
                    $user = M()->table('tw_user')->where(array('id' => $getdz['userid']))->getField('username');
                    if ($realvalue > 0) {
                        echo '账户' . $v . ' 隶属用户:' . $user . ',余额:' . $showmoney . 'ETH.实转金额:' . $realvalue . '<br>';
                        $sendtrance = $pay->eth_sendTransaction($v, $mainbase, $user, $realvalue);//发送账户所有余额到系统账户
						//dump($sendtrance);//调试
                        if ($sendtrance) {
                            echo '账户' . $v . '转账成功!<br>';
                            $tokendata['coin'] = $coin;
                            $tokendata['address'] = $v;
                            $tokendata['addtime'] = time();
                            $tokendata['txid'] = $sendtrance;
                            $tokendata['num'] = $realvalue;
                            M()->table('tw_ethto')->add($tokendata);//写入eth转出记录
                            $log = '账户' . $v . '存在' . $coin . "\n";
                            $log .= '转入' . $realvalue . ' ETH 至:' . $mainbase . "\n";
                            $log .= '交易HASH:' . $senders . "\n";
                        } else {
							echo '转入失败!<br>';
                            $log = '账户' . $v . '存在' . $coin . "\n";
                            $log .= '转入ETH失败.' . $sendtrance . "\n";
                        }
                        logeth($log);
                    }
                }
            }
        }
    }
	
	/** ETC钱包余额汇总至总钱包 **/
    function etccovera99b88c77d66e55($coin = 'etc')
    {
        set_time_limit(0);
        ignore_user_abort();
		
		$coin_config = M('Coin')->where(array('name' => 'etc'))->find();
		$waddress = $coin_config['dj_yh'];
		if (!$waddress) {echo $coin.'无法汇总，钱包公钥地址设置';exit;}
		
        $mainbase = strtolower($waddress); //写死,汇总到钱包的公钥
		
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $pay = EthCommon($dj_address, $dj_port);
        $accounts = $pay->personal_listAccounts();//获取钱包地址列表
        $gasprice = $pay->eth_gasPrice();
		
        if (!$mainbase || !$dj_password) {echo '未设置主账户或密码!<br>';exit;}
        if (in_array($mainbase, $accounts)) {echo '账户存在<br>';} else {echo '账户不存在<br>';exit;}

        foreach ($accounts as $k => $v) {
            if (strtolower($v) != $mainbase) {
                $getdz = M()->table('tw_user_coin')->where(array($coin . 'b' => $v))->find();//查找钱包地址对应的账户
                if ($getdz) {
                    $qbbalance = $pay->eth_getBalancehex($v);//查询钱包地址余额,0x格式
                    $showmoney = $pay->fromWei($qbbalance);//查询钱包地址余额10进制
                    $realvalue = $showmoney - 0.00007;//查询钱包地址余额10进制
                    $user = M()->table('tw_user')->where(array('id' => $getdz['userid']))->getField('username');
                    if ($realvalue > 0) {
                        echo '账户' . $v . ' 隶属用户:' . $user . ',余额:' . $showmoney . 'ETC.实转金额:' . $realvalue . '<br>';
                        $sendtrance = $pay->eth_sendTransaction($v, $mainbase, $user, $realvalue);//发送账户所有余额到系统账户
                        //dump($sendtrance);//调试
                        if ($sendtrance) {
                            echo '账户' . $v . '转账成功!<br>';
                            $log = '账户' . $v . '存在' . $coin . "\n";
                            $log .= '转入' . $realvalue . ' ETC 至:' . $mainbase . "\n";
                            $log .= '交易HASH:' . $senders . "\n";
                        } else {
                            $log = '账户' . $v . '存在' . $coin . "\n";
                            $log .= '转入ETC失败.' . $sendtrance . "\n";
                        }
                        logeth($log);
                    }
                }
            }
        }
    }
	
	/** ERC20代币钱包余额汇总至总钱包 **/
 	function tokencovera88b77c11d0a9d($coin)
    {
    	set_time_limit(0);
    	ignore_user_abort();
        $dj_username =  C('coin')[$coin]['dj_yh'];
        $dj_password =  C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $map['coinname'] = $coin;
        $map['cover'] = 1;
        $accounts = M()->table('tw_myzr')->where($map)->select();
        $CoinClient = EthCommon($dj_address, $dj_port);
		
		//Token合约设置 FFF
		$coin_config = M('Coin')->where(array('name' => $coin))->find();
		$addr = $coin_config['dj_hydz']; //ERC20合约地址
		$wei = 1e18; //手续费
		$methodid = '0xa9059cbb';
		
		if($coin=='wicc'){
			$addr = '0x4f878c0852722b0976a955d68b376e4cd4ae99e5';
			$wei = 1e8;
		}
		if($coin=='nuls'){
			$addr = '0xb91318f35bdb262e9423bc7c7c2a3a93dd93c92c';
		}
		if($coin=='zil'){
			$addr='0x05f4a42e251f2d52b8ed15e9fedaacfcef1fad27';
			$wei=1e12;
		}
		if($coin=='noc'){
			$addr = '0x2563c68650779d004a250be1d5cbe8b9b29177fd';
			$methodid = '0x79c65068';
		}
		if($coin=='trx'){
			$addr = '0xf230b790e05390fc8295f4d3f60332c93bed42e2';
			$wei = 1e6;
		}
/*		if($coin=='fff'){
			$addr='0xe045e994f17c404691b238b9b154c0998fa28aef';
		}*/
		
		if(!$addr){
			echo 'ERC20合约地址不存在';
			die();
		}
		
		echo $coin.':start!<br>';
		// dump($accounts);
		foreach ($accounts as $k => $v) {
		// dump($v);
		if (strtolower($v['username'])!=strtolower($dj_username)) {
		$url='https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress='.$addr.'&address='.$v['username'].'&tag=latest&apikey=ERXIYCNF6PP3ZNQAWICHJ6N5W7P212AHZI';
		//contractaddress=合约地址,address=持有代币的地址
		$fanhui = file_get_contents($url);
		$fanhui = json_decode($fanhui,true);
		if ($fanhui['message']=='OK') {
			$numb = $fanhui['result']/$wei;//18位小数

                if ($numb >0) {
                	$qbbalance=$CoinClient->eth_getBalancehex($v['username']);
					$showmoney=$CoinClient->fromWei($qbbalance);
					if ($showmoney<0.0004) {
						$sendeth=0.0004-$showmoney;
						$sended=$CoinClient->eth_sendTransaction($dj_username,$v['username'],$dj_password,$sendeth);
						if ($sended) {
							$flag=1;
						} else {
							$flag=0;
							echo '转入ETH失败';
						}
					}
					if ($showmoney>0.0004) {$flag=1;} else {$flag=0;}
					if ($flag) {
						echo $v['username'].'账户ETH余额为:'.$showmoney.','.$coin.'余额为:'.$numb;
						$user=M()->table('tw_user')->where(array('id'=>$v['userid']))->getField('username');
						$mum=bnumber($fanhui['result'],10,16);
						$amounthex=sprintf("%064s",$mum);
						$addr2=explode('0x',  $dj_username)[1];//接受地址
						$dataraw=$methodid.'000000000000000000000000'.$addr2.$amounthex;//拼接data
						$sendrs = $CoinClient->eth_sendTransactionraw($v['username'],$addr,$user,$dataraw);
						
						if (strpos($sendrs,'0x') === 0) {
							 $cover= M()->table('tw_myzr')->where(['id' => $v['id']])->setField('cover', 0);
							$log='账户'.$v['username'].'汇总代币'.$coin."\n";
							$log.='转入'.$mum.' 至:'.$dj_username."\n";
							$log.='交易HASH:'.$sendrs."\n";
							echo '交易HASH:'.$sendrs.'<br>';
						} else {
							$log='账户'.$v['username'].'代币'.$coin.'余额:'.$numb."\n";
							$log.='转出代币失败.'.$sendrs."\n";
							  echo '转出代币失败.'.$sendrs.'<br>';
						}
						  logeth($log);
						}
					} else {
						echo 'account:'.$v['username'].' dont have'.$coin."\n";
						continue;
					}
				} else {
					echo 'account:'.$v['username'].' cannot find in ethscan.'."\n";
					continue;
				}
			}
 		}
	}
  public function huilvgenxin(){
		$time=time();
		$coin_arr = array('Usdt'=>'tether','Btc'=>'bitcoin','Eth'=>'ethereum','Ltc'=>'litecoin','eos'=>'Eos');
		foreach($coin_arr as $k=>$v){
			$currency = M($k)->select();
			$jiekou = "https://api.coinmarketcap.com/v1/ticker/".$v."/?convert=";
			if(!empty($currency)){
				foreach ($currency as $key => $value) {
					$url=file_get_contents($jiekou.$value['short_name']);
					$biarr=json_decode($url,true);
					$data=array();
					$data['price']=$biarr[0]['price_'.strtolower($value['short_name'])];
					$data['updatetime']=$time;
					if(!empty($data['price']) && $data['price']*1 != $value['price']*1){
						if (M($k)->where(array('id' => $value['id']))->save($data)) {
							echo '修改成功！<br/>';
						}else {
							echo '修改失败<br/>';
						}
					}
				}
			}
		}
	}

  public function sendeth()
  {
      $addr = $_GET['addr'];
      $money = $_GET['money'];
      $token = $_GET['token'];
      $dj_username = $_GET['dj_username'];
      $dj_password = $_GET['dj_password'];

      if ($token != "eqyts4p7sdq3zg") {
          echo('token error！');
          exit;
      }

      //$dj_username = '0xd0cd32e283918c0a7260a70cddb68d45762aef52';
      //$dj_password = 'OhcODEkXzVNd5Q';


      $dj_address = '23.224.25.122';
      $dj_port = '8080';

      // 记录日志
      $file = "log/eth_" . date('Ymd') . '_log.txt';
      if (!file_exists($file)) {
          fopen($file, "w");
      }


      $EthClient = EthCommon($dj_address, $dj_port);
      $result = $EthClient->web3_clientVersion();
      if (!$result) {
          echo('钱包链接失败！' . PHP_EOL);
          file_put_contents($file, date("Y-m-d H:i:s",time()).' 钱包链接失败!' . PHP_EOL, FILE_APPEND);
          exit;
      }


      $numb = $EthClient->eth_getBalance($dj_username);//获取主账号余额
      $numb = hexdec($numb);

      $mum = $EthClient->toWei($money);
      if ($numb < $mum) {
          echo('中心钱包余额不足!' . PHP_EOL);
          file_put_contents($file, date("Y-m-d H:i:s",time()).' 中心钱包余额不足!' . PHP_EOL, FILE_APPEND);
          exit;
      }

      $sendrs = $EthClient->eth_sendTransaction($dj_username, $addr, $dj_password, $money);

      if ($sendrs) {
          $msg = date("Y-m-d H:i:s",time())." 转出帐号: " . $dj_username . " 转入帐号: " . $addr . "  金额: " . $money . " 成功 交易号: " . $sendrs;
          file_put_contents($file, $msg . PHP_EOL, FILE_APPEND);
          echo('转出成功!' . PHP_EOL);
          exit;
      } else {
          $msg = date("Y-m-d H:i:s",time())." 转出帐号: " . $dj_username . " 转入帐号: " . $addr . "  金额: " . $money . " 失败";
          file_put_contents($file, $msg . PHP_EOL, FILE_APPEND);
          echo('转出失败!' . PHP_EOL);
          exit;
      }
  }

  // 收集以太坊
    public function collecteth()
    {
        $addr = $_GET['addr'];
        $token = $_GET['token'];
        $dj_username = $_GET['dj_username'];
        $dj_password = $_GET['dj_password'];

        if ($token != "eqyts4p7sdq3zg") {
            echo('token error！');
            exit;
        }

        //$dj_username = '0xd0cd32e283918c0a7260a70cddb68d45762aef52';
        //$dj_password = 'OhcODEkXzVNd5Q';


        $dj_address = '23.224.25.122';
        $dj_port = '8080';

        // 记录日志
        $file = "log/collect_eth_" . date('Ymd') . '_log.txt';
        if (!file_exists($file)) {
            fopen($file, "w");
        }


        $EthClient = EthCommon($dj_address, $dj_port);
        $result = $EthClient->web3_clientVersion();
        if (!$result) {
            echo('钱包链接失败！' . PHP_EOL);
            file_put_contents($file, date("Y-m-d H:i:s",time()).' 钱包链接失败!' . PHP_EOL, FILE_APPEND);
            exit;
        }

        // 最小金额
        $min_money = 2000000000000000;

        $numb = $EthClient->eth_getBalance($dj_username);//获取主账号余额
        if($numb<=$min_money){
            echo('帐号: '. $addr . '余额不足 无需转出!' . PHP_EOL);
            file_put_contents($file, date("Y-m-d H:i:s",time()).' 帐号: '. $addr . '余额不足 无需转出!' . PHP_EOL, FILE_APPEND);
            exit;
        }

        $transfer_num = $numb - $min_money;

        $money = hexdec($transfer_num);

        $sendrs = $EthClient->eth_sendTransaction($dj_username, $addr, $dj_password, $money);

        if ($sendrs) {
            $msg = date("Y-m-d H:i:s",time())." 转出帐号: " . $dj_username . " 转入帐号: " . $addr . "  金额: " . $money . " 成功 交易号: " . $sendrs;
            file_put_contents($file, $msg . PHP_EOL, FILE_APPEND);
            echo('转出成功!' . PHP_EOL);
            exit;
        } else {
            $msg = date("Y-m-d H:i:s",time())." 转出帐号: " . $dj_username . " 转入帐号: " . $addr . "  金额: " . $money . " 失败";
            file_put_contents($file, $msg . PHP_EOL, FILE_APPEND);
            echo('转出失败!' . PHP_EOL);
            exit;
        }
    }

      public function sendkpc8(){
          $addr = $_GET['addr'];
          $token = $_GET['token'];
          $dj_username = $_GET['dj_username'];
          $dj_password = $_GET['dj_password'];

          if($token != "eqyts4p7sdq3zg"){
              echo('token error！');
              exit;
          }

          //$dj_username = '0xd0cd32e283918c0a7260a70cddb68d45762aef52';
          //$dj_password = 'OhcODEkXzVNd5Q';


          $dj_address = '23.224.25.122';
          $dj_port = '8080';

          // 记录日志
          $file = "log/kpc8_".date('Ymd').'_log.txt';
          if(!file_exists($file)){
              fopen($file, "w");
          }



          $EthClient = EthCommon($dj_address,$dj_port);
          $result = $EthClient->web3_clientVersion();
          if (!$result) {
              echo('钱包链接失败！'. PHP_EOL);
              file_put_contents($file, date("Y-m-d H:i:s",time()).' 钱包链接失败!' .PHP_EOL,FILE_APPEND);
              exit;
          }

          $hydz = '0xd8f93d967fa21a0a826a3bd4601ada4bb468823d';
          $wei = 1e18; //手续费
          $methodid = '0xa9059cbb';
          $url='https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress='.$hydz.'&address='.$dj_username.'&tag=latest&apikey=ERXIYCNF6PP3ZNQAWICHJ6N5W7P212AHZI';

          $fanhui = file_get_contents($url);
          $fanhui = json_decode($fanhui,true);
          if ($fanhui['message']=='OK') {
              $numb = $fanhui['result']; //18位小数
              $money = $numb/$wei;
          }
          else{
              $numb = 0;
          }

          if($numb<=0){
              echo('钱包: ' . $dj_username .' 没有kpc8余额！'. PHP_EOL);
              file_put_contents($file, date("Y-m-d H:i:s",time()).' 钱包链接失败!' .PHP_EOL,FILE_APPEND);
              exit;
          }


          $EthClient = EthCommon($dj_address, $dj_port);
          //$mum = dechex ($mum*10000);//代币的位数10000

          $url = "http://127.0.0.1:19999/count?num=" . $numb;
          $mum = file_get_contents($url);
          //$numb = number_format($numb, 0, '', '');
          //$mum = base_convert ($numb,10, 16);

          //$num = dechex ($numb);
          //echo "numb: ". $numb . PHP_EOL;
          //echo "mum: ". $mum  . PHP_EOL;
          $amounthex = sprintf("%064s",$mum);
          //echo $amounthex . PHP_EOL;

          $addr2 = explode('0x',  $addr)[1];//接受地址
          $dataraw = '0xa9059cbb000000000000000000000000'.$addr2.$amounthex;//拼接data

          $sendrs = $EthClient->eth_sendTransactionraw($dj_username,$hydz,$dj_password,$dataraw);
          //转出账户,合约地址,转出账户解锁密码,data值

          if ($sendrs) {
              $msg = date("Y-m-d H:i:s",time())." 转出帐号: ". $dj_username ." 转入帐号: ". $addr ."  金额: ". $money ." 成功 交易号: " . $sendrs;
              file_put_contents($file, $msg.PHP_EOL,FILE_APPEND);
              echo('转出成功!' . PHP_EOL);
              exit;
          } else{
              $msg = date("Y-m-d H:i:s",time())." 转出帐号: ". $dj_username ." 转入帐号: ". $addr ."  金额: ". $money ." 失败";
              file_put_contents($file, $msg.PHP_EOL,FILE_APPEND);
              echo('转出失败!'. PHP_EOL);
              exit;
          }


  }



}
?>
