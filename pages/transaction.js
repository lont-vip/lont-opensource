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
       


    }


    // 新闻tab
    newspage.init();

    // 头条的焦点图滑动到最后的时候,要触发tab的下一个,所以这个需要抛出来
    newspage.tab = uiNewsTab;

    module.exports = newspage;
})
