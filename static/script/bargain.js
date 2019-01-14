/**
 * 砍价前端页面JS
 * @author  段龙剑
 * @date    2017-03-03 13:51:52
 */

import { swiper, swiperSlide } from 'vue-awesome-swiper';

Vue.use(VueResource);
Vue.http.options.emulateJSON = true;

let bargain = new Vue({
    el: '#wrap',
    components: {
        swiper,
        swiperSlide,

        // 模态弹窗组件
        modal: {template: '#modal-template'}
    },

    data: {
        // 接口地址
        requestUrl: {
            baseUrl:        '/mobile/',         //基本地址
            getBaseInfo:    'get-base-info',    //活动基本信息接口
            getBargainInfo: 'get-bargain-info', //砍价信息接口
            getHelperList:  'get-helper-list',  //贡献列表接口
            join:           'join',             //参加活动接口
            bargain:        'bargain',          //砍价接口
            buy:            'buy',              //购买接口
            getRedeemCode:  'get-redeem-code'   //获取兑换码接口
        },

        // 活动基本信息
        baseInfo: {
            requestType: 'index', //访问类型：1:活动首页"index" 2:砍价页"sponsor" 3:帮砍页"helper"
            // 活动信息 
            eventInfo: {
                name: '',              //活动名称
                status: '',            //活动状态: "未开始"、"进行中"、"已结束"
                content: '',           //活动说明
                organizer: '',         //活动单位
                participants: '',      //参与人数
                expireIn: '',          //活动剩余时间
                contactInfo: [],       //联系信息
                acquisitionTiming: '', //领奖信息填写时机: 0参与时填写、1领奖时填写
                qrcodeUrl: '',          //商家公众号二维码图片链接
                supplierId: '' //商家ID
            },

            // 商品相关信息
            goodsInfo: {
                type: '',        //商品类型: 0微商城商品、1线下商品
                name: '',        //商品名字
                adImages: [],    //商品轮播图链接
                adLink: '',      //商品轮播图跳转链接
                price: '',       //商品原价
                lowestPrice: '', //商品最低价
                number: '',      //商品库存
                shopUrl: ''      //商城链接
            },

            // 微信信息
            wxInfo: {
                headImg: '',  //微信头像链接
                nickName: '', //微信昵称
            },

            // 微信SDK配置
            jsSdkConf: {
                appId: '',     //公众号标识
                signatrue: '', //签名
                nonceStr: '',  //生成签名的随机串
                timestamp: '', //生成签名的时间戳
                shareUrl: ''   //分享链接
            },

            // 微信分享设置信息
            wxShareInfo: {
                shareTitle: '',   //分享标题
                shareImage: '',   //分享图片
                shareContent: ''  //分享简介
            }
        },

        // 砍价页面信息
        bargainInfo: {
            // 砍价信息
            eventInfo: {
                disparityPrice: '', //已砍价格
                isBargain: '',      //当前用户是否已砍过
                progress: '',       //砍价进度
                isLowestPrice: ''   //是否最低价
            },

            // 商品信息
            goodsInfo: {
                exchangeStatus: '', //是否已购买或兑奖（商城商品为布尔型，线下有空值、待兑奖、已兑奖）
                relateInfo: ''      //购买后的我的订单链接或兑换码
            }
        },

        /** 
         * 贡献列表
         * 每条子项
         * {
         *   headImg: '',     //头像链接
         *   nickName: '',    //微信昵称
         *   bargainTime: '', //帮砍时间
         *   disparity: '',   //帮砍价格 为正标识提价，为负表示降价
         * }
         */
        helperList: [],

        // 格式化后的数据
        formatRemainingTime: '-天--小时--分--秒', //倒计时
        formatProgress: 0,                        //砍价进度条
        curPriceLeft: '-20%',                     //已砍价格进度

        // 弹窗控制
        showExplain:    false, //活动说明弹窗
        showForm:       false, //填写信息弹窗
        showBargaining: false, //砍价动画
        showBargainSub: false, //砍价价格(减)弹窗
        showBargainAdd: false, //砍价价格(加)弹窗
        showRedeemCode: false, //兑奖码弹窗
        showOver:       false, //活动结束弹窗
        showSoldOut:    false, //商品被抢光弹窗
        // 提示框
        tips: {
            show: false, //是否显示
            text: ''     //提示文字
        },

        // 砍价异步缓存数据
        bargainCache: {
            nickName: '',      //当前用户昵称
            headImg: '',       //当前用户头像
            bargainTime: '',   //当前砍价时间  
            bargainPrice: '',  //砍价价格
            isLowestPrice: '', //是否最低价
            totalBargain: '',  //总已砍价价格
            progress: ''       //总进度
        },

        postType: '',    //提交信息时的请求接口类型，参与前：join，领奖时：get。
        contactData: {}, //要发送的联系信息（姓名name、电话phone、地址address）

        // 轮播图配置
        swiperOption: {
            autoplay: 900,
            autoplayDisableOnInteraction: false,
            setWrapperSize : true,
            pagination : '.swiper-pagination',
            notNextTick: true
        },
        // 活动说明弹窗内容配置
        explainOption: {
            scrollbar: '.swiper-scrollbar',
            direction: 'vertical',
            slidesPerView: 'auto',
            mousewheelControl: true,
            freeMode: true
        },
        // 贡献列表配置
        scrollOption: {
            scrollbar: '.swiper-scrollbar',
            direction: 'vertical',
            slidesPerView: 'auto',
            mousewheelControl: true,
            freeMode: true,
            isLoading: false, //是否显示加载中
            isLast: false,    //是否加载完毕
            curPage: 1, //当前请求页码
            onSliderMove(swiper, e) { //当下拉到最后则加载下一页
                if (swiper.isEnd && swiper.translate<0) {
                    let _this = bargain,
                        scrollOpt = _this.scrollOption;

                    // 如果没有加载中及已到最后一页则加载下一页
                    if (!scrollOpt.isLoading && !scrollOpt.isLast) {
                        _this.getHelperList(scrollOpt.curPage);
                    }
                }
            }
        },

        // 砍价动效图片地址
        bargainingImg: '',
        realBargainImg: 'http://static-10006892.file.myqcloud.com/bargain/mobile/img/bargaining.gif'
    },

    computed: {
        // 当前活动id
        eventId() {
            return this.getQueryString('eventId');
        },

        // 当前砍价id
        bargainId() {
            return this.getQueryString('bargainId');
        },

        // 轮播图链接，为空则不跳转
        adLink() {
            let link = this.baseInfo.goodsInfo.adLink;
            return link ? link : '##';
        },

        // 活动开始或已结束提示文字
        statusText() {
            return this.baseInfo.eventInfo.status=='未开始' ? '活动未开始' : '活动已结束';
        },

        // 是否显示当前已砍价格
        showCurPrice() {
            let _this    = this,
                baseInfo = _this.baseInfo;

            if (baseInfo.requestType != 'index' //不是首页
                && baseInfo.eventInfo.status == '进行中' //活动进行中
                && !_this.bargainInfo.eventInfo.isLowestPrice) { //未砍到最低价
                return true;
            } else {
                return false;
            }
        },

        // 是否显示自己砍一刀按钮
        showBargainSelfBtn() {
            let _this       = this,
                bargainInfo = _this.bargainInfo;

            if (_this.baseInfo.requestType == 'sponsor' //为砍价页
                && !bargainInfo.eventInfo.isBargain //当前用户没砍过
                && !bargainInfo.eventInfo.isLowestPrice) { //没砍到最低价
                return true;
            } else {
                return false;
            }
        },

        // 是否显示立即购买按钮
        showBuyBtn() {
            let _this       = this,
                baseInfo    = _this.baseInfo,
                bargainInfo = _this.bargainInfo;

            if (baseInfo.requestType == 'sponsor' //为砍价页
                && baseInfo.goodsInfo.type == '0' //商城商品
                && (bargainInfo.eventInfo.isBargain //已砍过
                    || bargainInfo.eventInfo.isLowestPrice) //或者已到最低价
                && !bargainInfo.goodsInfo.exchangeStatus) { //未购买过
                return true;
            } else {
                return false;
            }
        },

        // 是否显示兑换商品按钮
        showGetBtn() {
            let _this       = this,
                baseInfo    = _this.baseInfo,
                bargainInfo = _this.bargainInfo;

            if (baseInfo.requestType == 'sponsor' //砍价页
                && baseInfo.goodsInfo.type == '1' //线下商品
                && bargainInfo.eventInfo.isLowestPrice //已砍到最低价
                && bargainInfo.goodsInfo.exchangeStatus != '已兑奖') { //未兑奖
                return true;
            } else {
                return false;
            }
        },

        // 是否显示查看我的小宝贝按钮
        showCheckGoods() {
            let _this    = this,
                baseInfo = _this.baseInfo;

            if (baseInfo.requestType == 'sponsor' //砍价页
                && baseInfo.goodsInfo.type == '0' //商城商品
                && _this.bargainInfo.goodsInfo.exchangeStatus) { //已经购买
                return true;
            } else {
                return false;
            }
        },

        //  是否显示活动结束查看我的小宝贝按钮
        showCheckGooodsDialog() {
            let _this = this;

            if (_this.baseInfo.goodsInfo.type == '0' //商城商品
                && _this.bargainInfo.goodsInfo.exchangeStatus) { //已经购买
                return true;
            } else {
                return false;
            }
        },

        // 是否显示已领奖按钮
        showGotBtn() {
            let _this    = this,
                baseInfo = _this.baseInfo;

            if (baseInfo.requestType == 'sponsor' //砍价页
                && baseInfo.goodsInfo.type == '1' //线下商品
                && _this.bargainInfo.goodsInfo.exchangeStatus == '已兑奖') { // 已兑奖
                return true;
            } else {
                return false;
            }
        },

        // 是否显示帮砍一刀按钮
        showBargainBtn() {
            let _this       = this,
                bargainInfo = _this.bargainInfo;

            if (_this.baseInfo.requestType == 'helper'  //帮砍页
                && !bargainInfo.eventInfo.isBargain //未帮砍过
                && !bargainInfo.eventInfo.isLowestPrice) { //未到最低价
                return true;
            } else {
                return false;
            }
        },

        // 是否显示参与人数
        showParticipants() {
            let _this    = this,
                baseInfo = _this.baseInfo;

            if (baseInfo.eventInfo.status != '未开始' //活动不是未开始
                && (!_this.bargainInfo.eventInfo.isBargain //未砍过价
                    || baseInfo.requestType == 'helper')) { //或者是帮砍页
                return true;
            } else {
                return false;
            }
        },

        // 是否显示分享提示
        showShareTips() {
            let _this       = this,
                bargainInfo = _this.bargainInfo;

            if (_this.baseInfo.requestType == 'sponsor' //砍价页
                && bargainInfo.eventInfo.isBargain //自己已砍过
                && !bargainInfo.eventInfo.isLowestPrice) { //未到最低价
                return true;
            } else {
                return false;
            }
        },

        // 贡献列表类名
        scrollClass() {
            let _this = this;

            return {
                'loading': _this.scrollOption.isLoading,
                'is-last': _this.scrollOption.isLast
            }
        }
    },

    filters: {
        // 求绝对值，保留两位小数
        abs(val) { return (Math.abs(val)).toFixed(2); }
    },

    directives: {
        // 指令v-input-type：限制输入类型(表单不能用双向绑定v-modal，而要手动赋值)
        inputType(el, binding) {
            let value,
                name = el.getAttribute('data-name');

            // 监听输入去掉非数字
            el.addEventListener('input', () => {
                let type = binding.value;

                if (type == 'int') {
                    el.value = el.value.replace(/\D+/, ''); //过滤非数字
                }
            });

            bargain.contactData[name] = el.value; //手动赋值
        }
    },

    methods: {
        /**
         * 获取基本数据信息
         * @param  String eventId 活动id
         * @param  String fromUrl 当前url
         * @return null
         */
        getBaseInfo(eventId = this.eventId, fromUrl = location.href.split('#')[0], bargainId = this.bargainId) {
            let _this      = this,
                requestUrl = _this.requestUrl;

            _this.$http.get(requestUrl.baseUrl + requestUrl.getBaseInfo, {
                params: {
                    eventId,
                    fromUrl,
                    bargainId
                },
                timeout: 10 * 1000
            }).then((res) => {
                res = res.body;
                
                if (res.return_code == 'SUCCESS') {
                    let msg = res.return_msg,
                        eventInfo = msg.eventInfo;

                    _this.baseInfo = msg;
                    _this.modifyTitle(_this.baseInfo.eventInfo.name); //修改标题

                    // 如果isShowId 为1就显示
                    // 加载小尾巴
                    if(eventInfo.isShowAd) {
                        (function(win,doc) {
                            var s = doc.createElement('script'),
                                h = doc.getElementsByTagName('head')[0];

                            win.idouzi_sdk_config = {
                                deployId: [eventInfo.adsenseId], // 广告位ID
                                ad_local: ['idouzi-ad'], // 放置广告的的位置的ID
                                size: ['100%', '10vw'] // 广告的大小 w h
                            };

                            s.charset='utf-8';
                            s.async=true;
                            s.src=_this.getEnvLink().sdk;
                            h.insertBefore(s,h.firstChild);
                        })(window,document)
                    }
                } else {
                    _this.openTips(res.return_msg);
                }
                
                // 移除首屏画面
                let timeout = setTimeout(() => {
                    document.body.removeAttribute('class');
                    clearTimeout(timeout);
                }, 500);
            }, (res) => {
                document.body.removeAttribute('class'); //移除首屏画面
                _this.openTips(res.status + '：加载活动信息失败，再刷新试试~');
            });
        },

        getEnvLink() {
            let env = IdouziTools.getEnv(),
                sdk = '';

            switch (env) {
                case 'dev':
                    sdk = 'http://ad-dev.idouzi.com/js/js-sdk-dev.js';
                    break;
                case 'test':
                    sdk = 'http://ad-test.idouzi.com/js/js-sdk-test.js';
                    break;
                case 'prod':
                    sdk = 'http://ad.idouzi.com/js/js-sdk.js';
                    break;
                default:
                    sdk = 'http://ad.idouzi.com/js/js-sdk.js';
            }

            return {
                sdk
            }
        },

        /**
         * 参加活动接口
         * @param  Array(object) contact   [{name：'字段英文名', value: '字段值'}, ...]
         * @return null
         */
        join(contact) {
            let _this = this,
                requestUrl = _this.requestUrl;

            _this.$http.post(requestUrl.baseUrl + requestUrl.join, {contact}, {
                before() {_this.openTips('参加中...');},
                timeout: 10 * 1000
            }).then((res) => {
                _this.closeTips();
                res = res.body;

                if (res.return_code == 'SUCCESS') {
                    let baseInfo    = _this.baseInfo,
                        wxConf      = baseInfo.jsSdkConf,
                        wxInfo      = baseInfo.wxInfo,
                        msg         = res.return_msg,
                        resWxconf   = msg.jsSdk;

                    baseInfo.requestType = 'sponsor'; //转为砍价页

                    _this.helperList = [];//清空贡献列表

                    // 更新微信配置信息
                    wxConf.nonceStr  = resWxconf.nonceStr;
                    wxConf.signature = resWxconf.signature;
                    wxConf.timestamp = resWxconf.timestamp;
                    wxConf.shareUrl  = resWxconf.shareUrl;

                    _this.baseInfo.wxInfo = msg.wxInfo; // 更新当前用户头像昵称信息
                    
                    //如果是提交信息过来的则隐藏填写信息弹窗
                    if (_this.showForm) {
                        _this.showForm = false;
                    }

                    _this.enabledBtn(); //取消按钮禁用
                } else {
                    _this.openTips(res.return_msg, true, 3);
                    _this.enabledBtn();
                    _this.$refs.postBtn.disabled = false;
                }
            }, (res) => {
                _this.openTips(res.status + '：参加失败，请稍后重试~', true, 3);
            });
        },

        /**
         * 获取砍价信息
         * @param  String bargainId 砍价id
         * @return null
         */
        getBargainInfo(bargainId = this.bargainId) {
            let _this      = this,
                requestUrl = _this.requestUrl;

            _this.$http.get(requestUrl.baseUrl + requestUrl.getBargainInfo, {
                params: { bargainId },
                before() {_this.openTips('获取砍价信息中...');},
                timeout: 10 * 1000
            }).then((res) => {
                _this.closeTips();
                res = res.body;

                if (res.return_code == 'SUCCESS') {
                    _this.bargainInfo = res.return_msg; //转为砍价页
                } else {
                    _this.openTips(res.return_msg);
                }
            }, (res) => {
                _this.openTips(res.status + '：获取砍价信息失败，再刷新试试~');
            });
        },

        /**
         * 获取贡献列表
         * @param  Number page      请求页码
         * @param  String bargainId 砍价id
         * @return null
         */
        getHelperList(page = 1, bargainId = this.bargainId) {
            let _this      = this,
                requestUrl = _this.requestUrl,
                scrollOpt  = _this.scrollOption;

            scrollOpt.isLoading = true; //显示加载中

            _this.$http.get(requestUrl.baseUrl + requestUrl.getHelperList, {
                params: {
                    page,
                    bargainId
                },
                timeout: 10 * 1000
            }).then((res) => {
                res = res.body;

                if (res.return_code == 'SUCCESS') {
                    let msg = res.return_msg;

                    if (msg.length) {
                        // 加入贡献列表数组
                        _this.helperList = [..._this.helperList, ...msg];
                    } else {
                        scrollOpt.isLast = true; //显示加载完所有数据
                    }

                    scrollOpt.isLoading = false; //隐藏加载中
                    scrollOpt.curPage++; //页码加1
                } else {
                    _this.openTips(res.return_msg, true, 2);
                }
            }, (res) => {
                _this.openTips(res.status + '：获取英雄榜失败, 请稍后重试~', true, 2);
            });
        },

        /**
         * 砍价接口
         * @param  String bargainId 砍价id
         * @return null
         */
        bargain(bargainId = this.bargainId) {
            let _this      = this,
                requestUrl = _this.requestUrl;

            _this.$http.get(requestUrl.baseUrl + requestUrl.bargain, {
                params: { bargainId },
                timeout: 10 * 1000
            }).then((res) => {
                res = res.body;

                if (res.return_code == 'SUCCESS') {
                    let msg = res.return_msg;

                    // 缓存砍价信息数据
                    _this.bargainCache = msg;

                    // 如果砍价动画已完成则加载弹窗
                    if (!_this.showBargaining) {
                        _this.showBargainReult();
                    }
                } else {
                    _this.openTips(res.return_msg, true, 3);
                    _this.enabledBtn();
                }
            }, (res) => {
                _this.openTips(res.status + '：砍价失败，请稍后重试~', true, 3);
            });
        },

        /**
         * 获取兑换码接口
         * @param  Array(object) contact 联系信息[{name：'字段英文名', value: '字段值'}, ...]
         * @return null
         */
        getRedeemCode(contact) {
            let _this      = this,
                requestUrl = _this.requestUrl,
                goodsInfo  = _this.bargainInfo.goodsInfo;

            //如果是提交信息过来的则隐藏填写信息弹窗
            if (_this.showForm) {
                _this.$refs.postBtn.disabled = false;
                _this.showForm = false;
            }
            _this.showRedeemCode = true; // 显示兑换码弹窗

            // 如果未兑过奖则异步请求获取兑换码
            if (!goodsInfo.exchangeStatus) {
                _this.$http.post(requestUrl.baseUrl + requestUrl.getRedeemCode, {contact}, {
                    before() {_this.openTips('兑换码获取中...');},
                    timeout: 10 * 1000
                }).then((res) => {
                    _this.closeTips();
                    res = res.body;

                    if (res.return_code == 'SUCCESS') {
                        goodsInfo.relateInfo = res.return_msg; //兑换码
                        goodsInfo.exchangeStatus = '待兑奖'; //已兑奖
                    } else {
                        _this.openTips(res.return_msg, true, 3);
                        _this.enabledBtn();
                    }
                }, (res) => {
                    _this.openTips(res.status + '：兑换码获取失败，请稍后重试', true, 3);
                });
            }
            
            _this.enabledBtn(); //取消按钮禁用
        },

        // 微商城购买接口
        buy() {
            let _this      = this,
                requestUrl = _this.requestUrl,
                goodsInfo  = _this.bargainInfo.goodsInfo;

            _this.$http.post(requestUrl.baseUrl + requestUrl.buy,{
                before() {_this.openTips('跳转中...');},
                timeout: 10 * 1000
            }).then((res) => {
                res = res.body;

                if (res.return_code == 'SUCCESS') {
                    location.href = res.return_msg; //去微商城购买
                    _this.enabledBtn(); //取消按钮禁用
                } else {
                    _this.openTips(res.return_msg, true, 3);
                    _this.enabledBtn(); //取消按钮禁用
                }
            }, (res) => {
                _this.openTips(res.status + '：获取商城链接失败，请稍后重试', true, 3);
            });
        },

        // 倒计时
        countRemainingTime() {
            let _this            = this,
                eventInfo        = _this.baseInfo.eventInfo,
                remainingSeconds = eventInfo.expireIn,
                timeout;

            // 如果时间已到则状态转为已结束
            if (remainingSeconds > 0) {
                let d = Math.floor(remainingSeconds/60/60/24),
                    h = Math.floor(remainingSeconds/60/60%24),
                    m = Math.floor(remainingSeconds/60%60),
                    s = Math.floor(remainingSeconds%60);

                _this.formatRemainingTime = d + '天' + h + '小时' + m + '分' + s + '秒';
            } else {
                eventInfo.status = '已结束';
                clearTimeout(timeout);
            }

            timeout = setTimeout(() => {eventInfo.expireIn--;}, 1000);
        },

        /* 已砍价格进度
         * 要定位到三角形且长度不定，
         * 所以已砍进度应该是实际进度条减去已砍元素宽度的一半
         */
        countProgress() {
            let _this         = this,
                basicProgress = _this.bargainInfo.eventInfo.progress,
                halfProgress;

                // 如果进度小于大于0且0.04则计算进度条为0.04，否则UI显示不明显
                basicProgress = (basicProgress>0 && basicProgress<0.04) ? 0.04 : basicProgress;

            if (_this.$refs.curPrice) {
                // 稍微做点延迟，否则宽度获取不正确
                let timeout = setTimeout(() => {
                    halfProgress = _this.$refs.curPrice.offsetWidth / _this.$refs.curPrice.parentNode.offsetWidth;
                    _this.curPriceLeft = (basicProgress - halfProgress / 2) * 100 + '%';
                    clearTimeout(timeout);
                }, 0);
            }

            _this.formatProgress = basicProgress * 100 + '%';
        },

        // 我要参加操作
        joinHandler(e) {
            let _this    = this,
                baseInfo = _this.baseInfo;

            if (!baseInfo.goodsInfo.number) {
                _this.openTips('客官，商品已经被抢光了~', true, 3);
                return;
            }

            e.target.disabled = true; //禁用按钮
            
            // 如果为商城商品或线下商品参与时填写信息则弹出填写信息弹窗，否则直接参与
            if (baseInfo.goodsInfo.type == '0'
                || (baseInfo.goodsInfo.type == '1'
                    && baseInfo.eventInfo.acquisitionTiming == '0')) {
                _this.showForm = true;
                _this.postType = 'join'; //填写信息后要请求的接口
            } else {
                _this.join(); //请求参加接口
            }
        },

        // 我也要抢操作
        join2Handler(e) {
            e.target.disabled = true;
            location.href = location.protocol + '//'
                            + location.host
                            + location.pathname
                            + '?eventId=' + this.eventId;
        },

        // 砍价操作
        bargainHandler(e) {
            let _this = this,
                timeout;

            e.target.disabled = true; // 禁用按钮
            _this.showBargaining = true; //显示砍价动画
            _this.bargain(); //请求砍价接口
        },

        // 更新砍价信息
        updateBargainInfo(e) {
            let _this      = this,
                goodsInfo  = _this.baseInfo.goodsInfo,
                eventInfo  = _this.bargainInfo.eventInfo,
                cacheData  = _this.bargainCache,
                helperInfo;

            e.target.disabled = true;

            // 隐藏砍价弹窗
            _this.showBargainAdd = false;
            _this.showBargainSub = false;

            _this.enabledBtn(); //取消按钮禁用

            //更新砍价数据
            eventInfo.disparityPrice = (eventInfo.disparityPrice - cacheData.bargainPrice).toFixed(2);
            eventInfo.isLowestPrice  = cacheData.isLowestPrice;
            eventInfo.progress       = eventInfo.disparityPrice / (goodsInfo.price - goodsInfo.lowestPrice);

            eventInfo.isBargain = true; //当前用户已砍过

            // 插入当前砍价记录到砍价列表
            helperInfo = {
                headImg: cacheData.headImg,
                nickName: cacheData.nickName,
                disparity: cacheData.bargainPrice,
                bargainTime: cacheData.bargainTime
            }
            _this.helperList.unshift(helperInfo);
        },

        // 领取商品操作
        getHandler(e) {
            let _this = this;

            e.target.disabled = true; //禁用按钮

            // 如果为线下商品领奖时填写信息且没有获取过兑换码则弹出填写信息弹窗
            if (_this.baseInfo.eventInfo.acquisitionTiming== '1' && !_this.bargainInfo.goodsInfo.relateInfo) {
                _this.showForm = true;
                _this.postType = 'get'; //填写信息后要请求的接口
            } else {
                _this.getRedeemCode(); //获取兑奖码
            }
            
        },

        // 立即购买操作
        buyHandler(e) {
            let _this = this;

            e.target.disabled = true;

            // 判断是否还有库存
            if (!_this.baseInfo.goodsInfo.number) {
                _this.showSoldOut = true; //没库存则弹出提示弹窗
            } else {
                this.buy(); //有则去商城
            }
        },

        // 查看我的小宝贝
        checkHandler(e) {
            let _this = this,
                relateInfo = _this.bargainInfo.goodsInfo.relateInfo;

            // 如果有订单链接则跳转到订单链接，否则到商城链接
            if (relateInfo) {
                location.href = relateInfo;
            } else {
                location.href = _this.baseInfo.goodsInfo.shopUrl;
            }
        },

        // 提交联系信息操作
        postHandler(e) {
            let _this       = this,
                contactData = _this.contactData, //已填写的数据
                isValid     = true, //是否通过验证
                contactParam; //格式化后要提交的数据

            // contactData数据还没绑定，稍微做点延迟
            let timeout = setTimeout(() => {

                // 遍历填写项判断是否都已填写
                for (let item in contactData) {
                    // 检查是否有未填写的项
                    if (!contactData[item]) {
                        // 如果有则找出对应的字段名字并提示
                        for (let originalItem of _this.baseInfo.eventInfo.contactInfo) {
                            if (item == originalItem.name) {
                                _this.openTips(originalItem.label+'还没填哦~', true);

                                isValid = false;
                                break;
                            }
                        }
                        break;
                    }
                }

                // 如果都已填写
                if (isValid) {
                    // 检查手机格式是否正确
                    if (!(/^1(3|4|5|7|8)\d{9}$/.test(contactData.phone))) {
                        _this.openTips('手机号码格式不对哦~', true);
                        return;
                    }

                    // 格式化联系信息
                    contactParam = _this.formatContactData(contactData);

                    e.target.disabled = true; //禁用按钮
                    
                    // 判断是跳转到参与还是兑换码
                    if (_this.postType == 'join') {
                        _this.join(contactParam);
                    } else if (_this.postType == 'get') {
                        _this.getRedeemCode(contactParam);
                    }
                }
                clearTimeout(timeout);
            }, 500);
        },

        // 显示砍价结果弹窗
        showBargainReult() {
            let _this = this,
                cachePrice = _this.bargainCache.bargainPrice;

            // 如果砍价接口结果已返回
            if (cachePrice) {
                _this.closeTips(); //若有加载中弹窗则去掉

                // 判断是砍降还是砍涨来显示对应的弹窗
                if (cachePrice > 0) { //砍涨
                    _this.showBargainAdd = true;
                } else { //砍降
                    _this.showBargainSub = true;
                }
            } else {
                // 否则请求还未返回显示加载中
                _this.openTips();
            }
        },

        /**
         * 获取url指定参数值
         * @param  String param 参数名
         * @return string 参数值
         */
        getQueryString(param) {
            let reg   = new RegExp('(^|&)' + param + '=([^&]*)(&|$)', 'i'),
                value = location.search.substr(1).match(reg);

            if (value != null) {
                return unescape(value[2]);
            }
            return null;
        },

        // 遍历按钮取消禁用
        enabledBtn() {
            for (let item of Array.from(this.$refs.btnWrap.children)) {
                item.disabled ? item.disabled=false : false;
            }
        },

        // 提交表单信息关闭后启用我要参与按钮
        hideForm() {
            let _this = this;

            _this.showForm = false;
            _this.enabledBtn();
        },

        // 提交
        hideSoldOut() {
            let _this = this;

            _this.showSoldOut = false;
            _this.enabledBtn();
        },

        /**
         * 格式化联系信息数据
         * @param  Object data 联系信息对象
         * @return Array  result 联系信息数组
         */
        formatContactData(data) {
            let result = [];

            for (let item in data) {
                let itemObj = {};
                
                itemObj.name  = item;
                itemObj.value = data[item];
                result.push(itemObj);
            }

            return result;
        },

        /**
         * 修改标题
         * @param  String title 要修改的标题
         * @return null
         */
        modifyTitle(title) {
            let isIOS = /iPad|iPhone|iPod/i.test(navigator.userAgent);
            document.title = title;

            if (isIOS) {
                // 利用iframe的onload事件刷新页面从而显示title
                let iframe = document.createElement('iframe');
                iframe.src = location.protocol + '//' + location.host + '/favicon.ico'; //必须要有src
                iframe.style.cssText = 'visibility:hidden;width:1px;height:1px;';
                document.body.appendChild(iframe);

                iframe.onload = function () {
                    let timeout = setTimeout(() => {
                        document.body.removeChild(iframe);
                        clearTimeout(timeout);
                    }, 0);
                };
            }
        },

        // 设置微信分享
        setShareConf(data) {
            let _this     = this,
                baseInfo  = _this.baseInfo,
                config    = baseInfo.jsSdkConf,
                shareConf = baseInfo.wxShareInfo;

            wx.config({
                debug: false,
                appId: config.appId,
                timestamp: config.timestamp,
                nonceStr: config.nonceStr,
                signature: config.signature,
                jsApiList: [
                    'onMenuShareTimeline',
                    'onMenuShareAppMessage',
                    'hideMenuItems'
                ]
            });
        
            wx.ready(() => {
                let shareOpts = {
                    title: shareConf.shareTitle,
                    desc: shareConf.shareContent,
                    imgUrl: shareConf.shareImage,
                    link: config.shareUrl
                };

                wx.hideMenuItems({
                    menuList: [
                        'menuItem:copyUrl',
                        'menuItem:openWithQQBrowser',
                        'menuItem:share:email',
                        'menuItem:openWithSafari'
                    ]
                });
                
                wx.onMenuShareTimeline(shareOpts); // 分享到朋友圈
                wx.onMenuShareAppMessage(shareOpts); // 分享给朋友
            });
        },

        /**
         * 打开提示弹窗
         * @param  String  text       提示文字
         * @param  Boolean isAutoHide 是否自动消失，默认为false
         * @param  Number  time       自动消失时间(秒)
         * @return null
         */
        openTips(text = '加载中...', isAutoHide = false, time = 2) {
            let _this = this,
                tips  = _this.tips;

            tips.text = text;
            tips.show = true;

            // 如果是自动消失
            if (isAutoHide) {
                let timeout = setTimeout(() => {
                    _this.closeTips();
                    clearTimeout(timeout);
                }, time * 1000);
            }
        },
        // 关闭提示弹窗
        closeTips() {
            this.tips.show = false;
        }
    },

    watch: {
        // 监听剩余时间
        'baseInfo.eventInfo.expireIn': {
            handler() {this.countRemainingTime();},
            deep: true
        },

        // 监听活动页面：'index'为活动首页，'sponsor'为砍价页，'helper'为帮砍页
        'baseInfo.requestType': {
            handler(val) {
                let _this = this;

                if (val=='sponsor' || val=='helper') {
                    _this.getBargainInfo();
                    _this.getHelperList();

                    // 没砍过则预加载砍价动效图片
                    if (!_this.bargainInfo.eventInfo.isBargain) {
                        let img = new Image();
                        img.src = _this.realBargainImg;
                    }
                }
            },
            deep: true
        },

        // 监听进度
        'bargainInfo.eventInfo.progress': {
            handler() {
                this.countProgress();
            },
            deep: true
        },

        // 监听砍价动画
        showBargaining(val) {
            let _this = this;
            if(val) {
                let timeout;

                _this.bargainingImg = _this.realBargainImg;
                timeout = setTimeout(() => {
                    _this.bargainingImg = ''; //清空砍价动画图片
                    _this.showBargaining = false; //隐藏砍价动画
                    clearTimeout(timeout);
                }, 500);
            } else {
                //显示砍价结果弹窗
                let timeout = setTimeout(() => {
                    _this.showBargainReult();
                }, 500);
            }
        },

        // 监听活动状态
        'baseInfo.eventInfo.status': {
            handler(val) {
                this.showOver = val == '已结束' ? true : false;
            },
            deep: true
        },

        // 监听微信配置
        'baseInfo.jsSdkConf.timestamp': {
            handler() {
                this.setShareConf();
            },
            deep: true
        }
    },

    created() {
        let _this = this;

        _this.getBaseInfo(); //加载基本信息

        _this.countRemainingTime(); //实例加载后就倒计时
        _this.$nextTick(_this.countProgress); //计算已砍价进度
        document.body.addEventListener('touchstart', () => {}); //兼容ios active效果
    }
});