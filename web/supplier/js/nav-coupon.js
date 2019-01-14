/**
 * Created by chengyi on 2017/11/16 0016.
 */
$(function () {
    var navCouponData = {
        userCouponListEle: $(".user-coupon-list"),
        navCouponEle: $("#header .user-coupon"),
        couponList: ''
    },ajaxUrl = {
        noReadNum: '/supplier/query-no-read-num',
        getCouponList: '/supplier/get-coupon'
    };

    // 查询是否有未读的优惠券
    $.ajax({
        type: 'get',
        url: ajaxUrl.noReadNum,
        dataType: 'json',
        success: function(res) {
            var status = res.return_code,
                msg = res.return_msg;

            if (status === 'SUCCESS') {
                if (parseInt(msg) > 0) {
                    navCouponData.navCouponEle.addClass("no-read");
                }
            }
        }
    });

    // 顶部优惠券添加滚动条
    $(".user-coupon").hover(function () {
        // 如果优惠券列表数据不存在，就获取优惠券列表
        if(!navCouponData.couponList) {
            // 获取优惠券列表
            $.ajax({
                type: 'get',
                url: ajaxUrl.getCouponList,
                dataType: 'json',
                success: function (res) {
                    var status = res.return_code,
                        msg = res.return_msg;

                    if (status === 'SUCCESS') {
                        if (msg && msg.length > 0) {
                            renderCanuseCoupon(msg);
                        } else {
                            renderNoneData();
                        }

                        navCouponData.couponList = msg;

                        // 如果当前有未读的状态，就清除这个状态
                        if(navCouponData.navCouponEle.hasClass("no-read")) {
                            navCouponData.navCouponEle.removeClass("no-read");
                        }

                        navCouponData.userCouponListEle.show();
                    }
                }
            });
        } else {
            navCouponData.userCouponListEle.show();
        }
    }, function () {
        navCouponData.userCouponListEle.hide();
    });

    /**
     * 渲染优惠券列表
     * @param {Object} list 优惠券列表
     */
    function renderCanuseCoupon(list) {
        var html = "";

        if (list) {
            for (var index = 0, len = list.length; index < len; index++) {
                html += gottenCoupon(list[index]);
            }
        }

        $('#head-coupon-list').html(html);

        // 优惠券列表添加滚动条
        navCouponData.userCouponListEle.show().niceScroll({
            cursorcolor: '#ccc'
        });

        navCouponData.userCouponListEle.getNiceScroll().resize();
    }

    /**
     * 单个优惠券html拼接
     * @param {Object} data 单张优惠券数据
     * @return {String} 返回拼接好的字符串
     */
    function gottenCoupon(data) {
        var overdueHtml = '',
            denomination = data.denomination, // 优惠券面额
            infoColor = '',
            tipsColor = '',
            textColor = '',
            statusColor = ''; // 是否使用状态背景颜色

        if (denomination == 500) {
            statusColor = '#738fe6';
            infoColor = '#f3f6ff';
            tipsColor = '#ff8066';
            textColor = '#738fe6';
        } else {
            statusColor = '#ff957f';
        }

        overdueHtml = data.isOverdue ?
                      '<div class="coupone-date-tips" style="background-color: ' + tipsColor + '">即将过期</div>' : '';

        return  '<li class="coupon-list-item">' +
                    '<div class="coupon-item-info-wrap">' +
                        '<div class="coupon-item-info" style="background-color: ' + infoColor + '">' +
                            '<div class="coupon-size" style="color: ' + textColor + '">' +
                                '<span class="size-number">'+ denomination + '</span>' +
                                '<span class="size-type">' + data.name + '</span>' +
                            '</div>' +
                            '<div class="coupone-date" style="border-color: ' + textColor + '">' +
                                '<p class="coupone-date-text">' +
                                    '- 仅限于<span class="strong">' + data.ruleType + '</span>使用' +
                                '</p>' +
                                '<p class="coupone-date-text">- 有效期至' + data.endAt.split(" ")[0] + '</p>' +
                            '</div>' +
                            overdueHtml +
                        '</div>' +
                    '</div>' +
                    '<a href="' + getEnv().mallLink + '?tabType=pay' + '" ' +
                        'target="_blank" ' +
                        'class="coupon-item-status"  ' +
                        'style="background-color: ' + statusColor + '">' +
                        '<span>马上使用</span>' +
                    '</a>' +
                '</li>';
    }

    // 优惠券为空时展示的样式
    function renderNoneData() {
        var editorLink = getEnv().editorLink;
        noDataHtml =
            "<li class='none-data'>" +
                "<div class='none-icon'></div>" +
                "<div class='none-tips'>" +
                    "<div class='none-tips-title color-orange'>【官方好福利】</div>" +
                    "<div class='none-tips-text'>" +
                        "通过豆子编辑器 <span class='color-orange'>“存流量”</span>模式 <br/>" +
                        "发送文章即可获得代金券哦~" +
                    "</div>" +
                    "<a class='none-tips-link color-orange' " +
                        "target='_blank' " +
                        "href='" + editorLink + "'>" +
                        "赶紧试试" +
                    "</a>" +
                "</div>" +
            "</li>";

        $("#head-coupon-list").html(noDataHtml);
    }

    /**
     * 根据当前环境获取对应的链接
     * @return {{editorLink: *}}
     */
    function getEnv() {
        var env = IdouziTools.getEnv(),
            editorLink,
            mallLink;

        switch (env) {
            case 'dev':
                editorLink = 'http://editor-dev.idouzi.com/';
                mallLink = 'http://mall2.idouzi.com/frontend/goods/index';
                break;
            case 'test':
                editorLink = 'http://editor-test.idouzi.com/';
                mallLink = 'http://mall1.idouzi.com/frontend/goods/index';
                break;
            case 'prod':
                editorLink = 'http://editor.idouzi.com/';
                mallLink = 'http://mall.idouzi.com/frontend/goods/index';
                break;
            default:
                editorLink = 'http://editor.idouzi.com/';
                mallLink = 'http://mall.idouzi.com/frontend/goods/index';
                break;
        }

        return {
            editorLink: editorLink,
            mallLink: mallLink
        }
    }
});
