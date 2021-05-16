loader.define(["main"],function(main,require,exports,module) {

    var uiSlidePage,    // 底部公共导航
        uiDialogNav,  // 新闻弹出导航
        slideHeight,
        uiNewsTab,
        newspage = {},  // 新闻页
        topicpage = {},  // 话题页
        livepage = {},  // 直播页
        minepage = {};  // 个人页

    // 新闻页;
    newspage.bind = function () {

        // 触发新闻导航自定义对话框
        $(".btn-dropdown").on("click",function () {
            main.columnDialog.open();
        })
    }
    newspage.init = function () {

        // 初始化导航
        this.nav();

        // 绑定事件
        this.bind();
    }

    // 顶部导航
    newspage.nav = function (argument) {
        // 初始化新闻页的main的高度,需要减去最外层的公用footer;
        var newspageMainHeight = bui.init({
            id:"#newsPage3",
            footer:"#tabDynamicNav3"
        });

       slideHeight = $(window).height() - $(".bui-bar-side").height() - $("#tabDynamicNav3").height()- $("#uiNewsTabNav3").height();
       // 新闻导航选项卡 js 初始化:
       uiNewsTab = bui.tab({
           id:"#uiNewsTab3",
           menu:"#uiNewsTabNav3",
           height: slideHeight,
           scroll: false,
           animate: false,
           // 1: 声明是动态加载的tab
           autoload: true
       })
       // 让顶部导航滚动到可视位置
        uiNewsTab.on("to",function (index) {
            // 有滚动条时在居中显示
            var itemwidth = $("#uiNewsTabNav3 li").eq(index).prev().width();
            var left = $("#uiNewsTabNav3 li")[index].offsetLeft - itemwidth*3;
            document.getElementById("uiSlideNavbar3").scrollLeft = left;

            var index = index,
                num = index +1 ;
            // 加载对应的脚本文件
              loader.require(["pages/mill/mill_my"+num]);
        }).to(0)


    }


    // 新闻tab
    newspage.init();

    // 头条的焦点图滑动到最后的时候,要触发tab的下一个,所以这个需要抛出来
    newspage.tab = uiNewsTab;

    module.exports = newspage;
})
