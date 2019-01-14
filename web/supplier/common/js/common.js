// 统计
BAIDU();
CNZZ();

// BAIDU统计
function BAIDU() {
    var hm = document.createElement("script");
    hm.src = "//hm.baidu.com/hm.js?315a9c2cdd0de286318309c95d525911";
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(hm, s);
}

// CNZZ统计
function CNZZ() {
    var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");
    document.write(unescape("%3Cspan id='cnzz_stat_icon_4295023'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s19.cnzz.com/stat.php%3Fid%3D4295023%26show%3Dpic ' type='text/javascript'%3E%3C/script%3E"));
}

$(function() {
    // var _hmt = _hmt || [];
    var layoutMain = {
            // 数据
            data: {
                'contact': {
                    'qq': '',
                    'tel': '',
                },
                'logo': 'http://static-10006892.file.myqcloud.com/public/img/back_logo.png',
                menus: [
                    // {
                    //     isPullDown: 0,
                    //     link: 'http://new.idouzi.com/supplier/index/index',
                    //     name: '首页',
                    //     class: 'icon-yingyongshichang',
                    //     color: '#ff9898',
                    //     submenus: []
                    // }
                ],
                username: '用户名',
                noReadNum: 0,
                wxid: ''
            },
            ajaxUrl: {
                couponUrl: $('meta[name="coupon-url"]').attr('content'),
                getIndexDataUrl: 'indexdata'
            },
            couponList: [],
            links: {
                editorUrl: '',
                mallUrl: '',
                idouziUrl: '',
            },
            // 页面元素
            $userCoupon: $(".user-coupon"),
            $userCouponList: $('.user-coupon-list'),
            $headerCouponList: $('.coupon-list'),
            $couponNoData: $('.user-coupon-list .no-data'),

            // 初始化
            init() {
                var _this = this;

                // 根据环境判断商城地址
                switch (IdouziTools.getEnv()) {
                    case 'dev':
                        _this.links = {
                            editorUrl: 'http://editor-dev.idouzi.com',
                            mallUrl: 'http://mall2.idouzi.com',
                            idouziUrl: 'http://new.idouzi.com',
                        }
                        break;
                    case 'test':
                        _this.links = {
                            editorUrl: 'http://editor-test.idouzi.com',
                            mallUrl: 'http://mall1.idouzi.com',
                            idouziUrl: 'http://wx.idouzi.com',
                        }
                        break;
                    case 'prod':
                        _this.links = {
                            editorUrl: 'http://editor.idouzi.com',
                            mallUrl: 'http://mall.idouzi.com',
                            idouziUrl: 'http://qq.idouzi.com',
                        }
                        break;
                    default:
                        _this.links = {
                            editorUrl: 'http://editor.idouzi.com',
                            mallUrl: 'http://mall.idouzi.com',
                            idouziUrl: 'http://qq.idouzi.com',
                        }
                        break;
                }

                // 地址渲染
                $('.layout-header .logo').attr('href', _this.links.idouziUrl + '/supplier/index/index');
                $('.layout-header .user-name').attr('href', _this.links.idouziUrl + '/supplier/index/authenticate');
                $('.layout-header .coupon-link a').attr('href', _this.links.mallUrl + '/frontend/order/index');
                $('.layout-header .user-coupon > a').attr('href', _this.links.mallUrl + '/frontend/coupon/index');
                $('.layout-header .sign-out a').attr('href', _this.links.idouziUrl + '/supplier/user/logout');
                $('.layout-nav .statement').attr('href', _this.links.idouziUrl + '/index.php?r=mobile/article/disclaimer&wxid=1693');

                // 顶部优惠券添加滚动条
                _this.$userCoupon.hover(function() {
                    // 添加优惠券与滚动条
                    _this.handleCouponHover();
                }, function() {
                    _this.$userCouponList.hide();
                });

                // 左侧菜单收缩展开
                $('#menus').on('click', '.link', function(e) {
                    if ($(this).attr('href') === 'javascript:void(0)') {
                        $(this).siblings('.sub-items').toggleClass('hide');
                        $(this).children('.icon-arrow').toggleClass('icon-xiala-').toggleClass('icon-gengduo');
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });

                // 左侧上滑时固定
                $(document).on('scroll', function() {
                    let $leftAsideWrap = $('.fix-wrap'),
                        scrollTop = $(document).scrollTop(),
                        rightSubLeft = $('#main-wrap')[0].offsetHeight - $leftAsideWrap[0].offsetHeight;

                    // windowSubLeft 用来判断左侧菜单比页面高的情况
                    if (scrollTop > 74 && rightSubLeft > 0) {
                        $leftAsideWrap.addClass('fixed');
                    } else {
                        $leftAsideWrap.removeClass('fixed');
                    }
                });

                // 右侧咨询收缩展开
                $('.show-hide-aside-btn').click(function() {
                    $('.layout-aside').toggleClass('hide-detail');
                });

                // 获取布局数据
                this.getData();
            },

            // 获取数据
            getData: function() {
                var _this = this;

                // 获取首页布局数据
                $.ajax({
                    xhrFields:{
                        withCredentials: true
                    },
                    url: _this.links.mallUrl + '/frontend/index/get-layout-data' || _this.ajaxUrl.getIndexDataUrl,
                    type: 'get',
                    success: function(data) {
                        var data = JSON.parse(data),
                            msg = data.return_msg,
                            status = data.return_code;

                        if (status === 'SUCCESS') {
                            msg.menus = msg.menus.menu;
                            msg.noReadNum = msg.noReadCount;

                            _this.data = msg;
                            _this.render(); // 渲染布局
                            _this.GIO(msg.wxid); // GIO统计

                            // 根据是否有未读优惠券添加未读标识
                            if (msg.noReadNum > 0) {
                                _this.$userCoupon.addClass("no-read");
                            }

                            // 右侧联系信息
                            $('aside .tel-number').text(msg.contact.tel); // 电话
                            $('aside .qq-number').text(msg.contact.qq); // qq
                            $('aside .contact-qq').attr('href', 'http://wpa.qq.com/msgrd?v=3&uin=' + msg.contact.qq + '&site=qq&menu=yes'); // 商桥
                        } else {
                            console.log('请求错误');
                        }
                    },
                    error: function() {
                        console.log('请求错误');
                    }
                });
            },

            // 渲染页面布局
            render: function() {
                var html = '',
                    data = this.data,
                    curMenu = document.querySelector('meta[name=cur-menu]')
                    .getAttribute('content');

                // 遍历数据构建左侧菜单html
                data.menus.forEach(function(menu, index) {
                    var mainActive = '', // 当前导航定位主菜单
                        subActive = '', // 当前导航定位二级菜单
                        menuLink = menu.submenus ? 'javascript:void(0)' : menu.link, // 主菜单链接
                        subHtml = '', // 次级菜单的html
                        hide = menu.isPullDown ? '' : ' hide', // 二级菜单是否展开
                        arrowClass = menu.isPullDown ? ' icon-xiala-' : ' icon-gengduo'; // 右侧箭头

                    // 当前导航定位主菜单
                    if (menu.name === curMenu) {
                        mainActive = ' active';
                    }

                    html += '<li class="item">';

                    // 优先遍历二级菜单 因为主菜单的subActive样式依赖与二级菜单
                    if (menu.submenus) {
                        subHtml += '<ul class="sub-items' + hide + '" style="max-height: ' + menu.submenus.length * 40 + 'px' + '">';

                        // 二级菜单遍历
                        menu.submenus.forEach(function(subMenu, subIndex) {
                            // 当前定位二级菜单
                            if (subMenu.name === curMenu) {
                                mainActive = ' sub-active';
                                subActive = ' active'
                            } else {
                                subActive = '';
                            }

                            subHtml += '<li class="sub-item' + subActive + '">' +
                                '<a href="' + subMenu.link + '" class="sub-link">' + subMenu.name + '</a>' +
                                '</li>';
                        });

                        subHtml += '</ul>';
                    }

                    // 一级菜单内容
                    html += '<a class="link' + mainActive + '" href="' + menuLink + '">' +
                        '<i class="icon-logo icon iconfont ' + menu.class + '"style="color:' + menu.color + '"></i>' +
                        '<span class="name">' + menu.name + '</span>';
                    if (menu.submenus) {
                        html += '<i class="icon-arrow icon iconfont' + arrowClass + '"></i>';
                    }

                    html += '</a>';

                    html += (subHtml + '</li>');
                });

                // 根据数据渲染
                $('.layout-header .logo img').attr('src', data.logo); // logo
                $('.layout-header .user-name').text(data.username); // 用户名
                $('#menus').html(html); // 左侧菜单
            },

            /**
             * GIO统计 
             * @param {String} wxid 后端返回的商家ID
             */
            GIO: function(wxid) {
                var growingIOurl = this.links.idouziUrl + '/supplier/api/growingIdouzi',
                    data = {
                        apikey: 839,
                        outer: 0,
                        wxid: wxid
                    };

                $.get(growingIOurl, data, function(data) {
                    data = JSON.parse(data).data;
                    var phone = data.cs11.replace('mobi:', '');
                    var wxType;
                    switch (data.service_type_info) {
                        case '0':
                            wxType = '订阅号';
                            break;
                        case '1':
                            wxType = '订阅号';
                            break;
                        case '2':
                            wxType = '服务号';
                            break;
                        default:
                            wxType = '无';
                            break;
                    }

                    var _vds = _vds || [];
                    window._vds = _vds;
                    (function() {
                        _vds.push(['setAccountId', '9c6fead577bbabb2']);

                        _vds.push(['setCS1', 'user_id', data.id]);
                        _vds.push(['setCS2', 'company_id', '无']);
                        _vds.push(['setCS3', 'wx_name', data.gz_name]);
                        _vds.push(['setCS4', 'register_day_count', data.create_time]);
                        _vds.push(['setCS5', 'login_day_count', data.login_time]);
                        _vds.push(['setCS6', 'login_count', data.loginCount]);
                        _vds.push(['setCS7', 'wx_type', wxType]);
                        _vds.push(['setCS8', 'phone', phone]);
                        _vds.push(['setCS9', 'first_pay_day_count', data.beginBuyTime]);
                        _vds.push(['setCS10', 'last_pay_day_count', data.lastBuyTime]);
                        (function() {
                            var vds = document.createElement('script');
                            vds.type = 'text/javascript';
                            vds.async = true;
                            vds.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'dn-growing.qbox.me/vds.js';
                            var s = document.getElementsByTagName('script')[0];
                            s.parentNode.insertBefore(vds, s);
                        })();
                    })();
                });
            },

            // 优惠券hover处理 获取数据 添加优惠券
            handleCouponHover: function() {
                var _this = this,
                    data = {},
                    $couponUrlType = $('meta[name=coupon-url-type]');

                // 优惠券接口couponState参数
                if ($couponUrlType.length) {
                    data.couponState = $couponUrlType.attr('content');
                }

                // 如果优惠券列表数据不存在，就获取优惠券列表
                if (_this.$userCoupon.hasClass("no-read")) {
                    // 获取优惠券列表
                    $.ajax({
                        type: 'get',
                        url: _this.ajaxUrl.couponUrl,
                        dataType: 'json',
                        data: data,
                        success: function(data) {
                            var couponReturnType = $('meta[name=coupon-return-type]').attr('content');

                            if(couponReturnType == 'returnMsg') {
                                var msg = data.return_msg,
                                    status = data.return_code;
                                
                                if (status === 'SUCCESS') {
                                    if (msg && msg.length > 0) {
                                        _this.renderCanuseCoupon(msg);
                                        _this.$couponNoData.hide();
                                    } else {
                                        _this.$couponNoData.show();
                                    }
                                }
                            } else if(couponReturnType == 'msg') {
                                var msg = data.msg;
                                
                                if (msg && msg.length > 0) {
                                    _this.renderCanuseCoupon(msg);
                                    _this.$couponNoData.hide();
                                } else {
                                    _this.$couponNoData.show();
                                }
                            }

                            _this.couponList = msg;

                            _this.$userCouponList.show();
                        },
                        error: function() {
                            console.log('请求错误');
                        }
                    });
                } else {
                    _this.$userCouponList.show();
                }

                // 如果当前有未读的状态，就清除这个状态 确保接口只被调用一次
                _this.$userCoupon.removeClass("no-read");
            },

            /**
             * 渲染优惠券列表
             * @param {Object} list 优惠券列表
             */
            renderCanuseCoupon: function renderCanuseCoupon(list) {
                var _this = this,
                    html = "";

                if (list) {
                    for (var index = 0, len = list.length; index < len; index++) {
                        html += _this.gottenCoupon(list[index]);
                    }
                }

                _this.$headerCouponList.html(html);

                // 优惠券列表添加滚动条
                _this.$userCouponList.show().niceScroll({
                    cursorcolor: '#ccc'
                });

                _this.$userCouponList.getNiceScroll().resize();
            },

            /**
             * 单个优惠券html拼接
             * @param {Object} data 单张优惠券数据
             * @return {String} 返回拼接好的字符串
             */
            gottenCoupon: function(data) {
                var _this = this,
                    overdueHtml = '',
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

                return '<li class="coupon-list-item">' +
                    '<div class="coupon-item-info-wrap">' +
                    '<div class="coupon-item-info" style="background-color: ' + infoColor + '">' +
                    '<div class="coupon-size" style="color: ' + textColor + '">' +
                    '<span class="size-number">' + denomination + '</span>' +
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
                    '<a href="' + _this.links.mallUrl + '?tabType=pay' + '" ' +
                    'target="_blank" ' +
                    'class="coupon-item-status"  ' +
                    'style="background-color: ' + statusColor + '">' +
                    '<span>马上使用</span>' +
                    '</a>' +
                    '</li>';
            }
        }
        // 初始化函数
    layoutMain.init();
});


/**
 * 弹窗组件
 * 调用方式: this.$refs.组件ref名称.open(options)
 * options可配置项：
 * - class       [String]   自定义弹窗类名
 * - title       [String]   弹窗标题，默认为‘弹窗标题’
 * - btnNum      [Number]   弹窗按钮数量，默认为2。0:无; 1:1个确认按钮; 2: 2个按钮
 * - btnOk       [String]   确认按钮文字，默认为确认
 * - btnNo       [String]   取消按钮文字，默认为取消
 * - okHandle    [Function] 点击确定按钮回调函数，默认关闭弹窗
 */
var Vue = Vue || ''; //防止旧公共文件报错
if (Vue) {
    var dialog = Vue.component('dialog', {
        template: '#dialog-template',
        data: function() {
            return {
                show: false,
                title: '弹窗标题',
                btnNum: 2, //弹窗按钮数量。0:无; 1:1个确认按钮; 2: 2个按钮
                btnOk: '确认', //确认按钮文字
                btnNo: '取消', //取消按钮文字
                dialogClass: null, //自定义弹窗类名
                callback: null //点击确定按钮回调函数
            }
        },
        computed: {
            isShowBtns: function() {
                var result;
                if (this.btnNum != 0) {
                    result = true;
                } else {
                    result = false;
                }
                return result;
            },
            isOnlyOneBtn: function() {
                return this.btnNum == 1 ? true : false;
            }
        },
        methods: {
            open: function(options) {
                options.hasOwnProperty('title') ? this.title = options.title : false;
                options.hasOwnProperty('btnOk') ? this.btnOk = options.btnOk : false;
                options.hasOwnProperty('btnNo') ? this.btnNo = options.btnNo : false;
                options.hasOwnProperty('class') ? this.dialogClass = options.class : false;
                options.hasOwnProperty('btnNum') ? this.btnNum = options.btnNum : false;
                this.show = true;
                if ((typeof options.okHandle).toLowerCase() == 'function') {
                    this.callback = options.okHandle;
                }
            },
            close: function() {
                this.show = false;
            },
            okHandle: function() {
                if ((typeof this.callback).toLowerCase() == 'function') {
                    this.callback();
                } else {
                    this.show = false;
                }
            }
        }
    });
}