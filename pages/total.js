loader.define(function(require,exports,module) {

    var pageview = {},
        uiDialogNav ;

    // 模块初始化定义
    pageview.init = function () {
        navTab();  
    }
	
    // 底部导航
    function navTab() {
		
        //menu在tab外层,menu需要传id
        var tab = bui.tab({
            id:"#tabDynamic",
            menu:"#tabDynamicNav",
            swipe: true,
            animate: true,
            // 1: 声明是动态加载的tab
            autoload: true,
        })
        
        var dv_num = 0; 
        // 2: 监听加载后的事件, load 只加载一次
        tab.on("to",function (index) {
            var current = index || 0;
            switch(current){
                case 0:
                    $('.bui-box-vertical .img-block').show();
                    $('.bui-box-vertical .img-none').hide();
                    $('.bui-box-vertical.active .img-block').hide();
                    $('.bui-box-vertical.active .img-none').show();

                    time4 = setInterval(function  () {
                        console.log('行情定时任务===4');
                        home_hangqing_sx();
                    },2000);
                    // clearInterval(time1);
                    // clearInterval(time2);
                    // clearInterval(time3);
                    // clearInterval(time5);
                    home_hangqing_sx();
                    chongzhi_sx();
                    home_web_sx();
                    home_banner_sx();
                    home_tishi_sx();

                    loader.require(["pages/home"]);
                    break;
                case 1:
                    $('.bui-tab-main .home_tishi_xs').hide();//隐藏首页弹窗

                    $('.bui-box-vertical .img-block').show();
                    $('.bui-box-vertical .img-none').hide();
                    $('.bui-box-vertical.active .img-block').hide();
                    $('.bui-box-vertical.active .img-none').show();
                    loader.require(["pages/mill"]);

                    // time1 = setInterval(function  () {
                    //     console.log('矿机相关定时任务===1');
                    //     mill_sx2();
                    // },2000);
                    // clearInterval(time2);
                    // clearInterval(time3);
                    clearInterval(time4);
                    // clearInterval(time5);
                    mill_sx2();
                    mill_sx();
                    break;
                case 2:
                    $('.bui-tab-main .home_tishi_xs').hide();//隐藏首页弹窗

                    $('.bui-box-vertical .img-block').show();
                    $('.bui-box-vertical .img-none').hide();
                    $('.bui-box-vertical.active .img-block').hide();
                    $('.bui-box-vertical.active .img-none').show();
                    loader.require(["pages/transaction"]);

                    // time5 = setInterval(function  () {
                    //     console.log('交易价格定时任务===5');
                    //         bizhong_mas2();
                    // },2000);
                    // clearInterval(time1);
                    // clearInterval(time2);
                    // clearInterval(time3);
                    clearInterval(time4);

                    bizhong_mas2();
                    break;
                case 3:
                    $('.bui-tab-main .home_tishi_xs').hide();//隐藏首页弹窗

                    $('.bui-box-vertical .img-block').show();
                    $('.bui-box-vertical .img-none').hide();
                    $('.bui-box-vertical.active .img-block').hide();
                    $('.bui-box-vertical.active .img-none').show();
                    loader.require(["pages/property"]);

                    // time2 = setInterval(function  () {
                    //     console.log('资产相关定时任务===2');
                    //         property_sx();
                    // },2000);
                    // clearInterval(time1);
                    // clearInterval(time3);
                    clearInterval(time4);
                    // clearInterval(time5);

                    property_sx();
                    break;
                case 4:
                    $('.bui-tab-main .home_tishi_xs').hide();//隐藏首页弹窗

                    $('.bui-box-vertical .img-block').show();
                    $('.bui-box-vertical .img-none').hide();
                    $('.bui-box-vertical.active .img-block').hide();
                    $('.bui-box-vertical.active .img-none').show();
                    loader.require(["pages/mine"]);

                    // time3 = setInterval(function  () {
                    //     console.log('我的相关定时任务===3');
                    //     mine_sx();
                    // },2000);
                    // clearInterval(time1);
                    // clearInterval(time2);
                    clearInterval(time4);
                    // clearInterval(time5);

                    mine_sx();
                    break;
            }
        }).to(0)
       
    }

    // 初始化
    pageview.init();

    // 把弹出层模块抛出去
    pageview.columnDialog = uiDialogNav;

    // 输出模块
    module.exports = pageview;

})
