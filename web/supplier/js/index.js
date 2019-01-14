Vue.component('alert-tips', {
    props: ['message'],
    template: '<div class="alert-tips" ref="deleteList">' +
    '{{message}}' +
    '</div>'
});


// 活动列表
var bargain = new Vue({
    el: ".bargain",
    data: {
        // 二维码组件配置
        qrcode: {
            allqrcode: document.getElementsByClassName('qrcode'),
            class: 'qrcode',
            size: '150',
            level: 'H',
            padding: 0,
            isShow: 0
        },
        bargainProtoType: new Bargain(),  //公共方法的构造函数
        lookMoreStatus: '',   //活动列表是否收缩
        // 活动列表分页数据
        totalPage: '',
        // 是否初始化完成
        isInit: false,
        // ajax请求url
        apiUrl: {
            getList: '/supplier/list', // 获取活动列表
            delList: '/supplier/delete', // 删除单个活动
            closeAct: '/supplier/close', // 关闭活动
            create: '/supplier/bargain?from=create', //创建活动
            shopList: '', //商城列表
            checkPhoneBind: '/supplier/user/CheckPhoneBind',  //校验手机是否绑定
            checkPhoneReg: '/supplier/user/newCheckPhone',  //校验手机是否注册
            createBindCode: '/supplier/user/CreateBindCode',
            bindPhone: '/supplier/user/BindPhone'
        },
        idouziUrl: '',  //idouzi链接
        isFree: '',  // 活动是否免费
        // 用户活动状态
        userActivityModel: {
            tryoutEndDate: '', // 试用模式截止时间
            freeEndDate: '',  // 免费模式截至时间
            payEndDate: ''  // 付费模式截至时间
        },
        // 手机号填写信息
        writePhone: {
            tel: '',
            imgCode: '',
            messageCode: '',
            messageCodeDisabled: false
        },
        errorMessage: {
            imgCode: '',
            messageCode: ''
        },
        coupon: [], // 优惠券信息
        gid: '', // 商城商品ID
        goodList: [], // 展开时展示的活动
        goodListNow: [], // 收缩时展示的活动
        nowPage: '1', // 当前列表分页
        alertMessage: '', // alert提示弹窗文字
        alertIsShow: false, // 是否显示alert弹窗
        isShowCheckPhone: false,  //是否显示填写手机号弹窗
        isShowBuyDialog: false, // 是否显示去购买弹窗
        bargainLoading: false, // 是否显示loading
        isBuy: '', //判断是否购买过期
        signKey: '', //签名
        userId: '',  //当前用户的Id
        isNewDialog: false, // 是否显示新建弹窗
    },
    // 注册组件
    components: {
        // 获取二维码组件
        VQrcode: VQrcode.qrcode
    },

    computed: {
        /**
         * 判断列表是否有数据
         * @returns Boolean
         */
        hasData: function () {
            var _this = this;
            if (_this.actList.length > 0) {
                return true;
            } else {
                return false;
            }
        },
        /**
         * 通过判断活动列表是展开还是收缩
         * 返回展示或收缩列表数据
         * @returns {Object}
         */
        actList: function () {
            var _this = this,
                lookMoreStatus = _this.lookMoreStatus;
            if (lookMoreStatus === 0) {
                return _this.goodListNow
            } else {
                return _this.goodList;
            }
        },

        // 判断是否显示图形验证码错误信息
        isShowImgCodeError: function () {
            var _this = this;

            return _this.errorMessage.imgCode !== '' && _this.writePhone.imgCode;
        },

        // 判断是否显示短信验证码错误信息
        isShowMessageCodeError: function () {
            var _this = this;

            return _this.errorMessage.messageCode !== '' && _this.writePhone.messageCode;
        },

        /**
         * 校验手机号码是否填写
         */
        checkPhoneRule: function () {
            return {
                tel: [
                    {required: true, message: '请输入手机号码'},
                    {type: 'number', message: '手机号码必须为数字'},
                    {validator: this.checkTel, trigger: 'blur'}
                ],

                imgCode: [
                    {required: true, message: '请输入图形验证码'}
                ],

                messageCode: [
                    {required: true, message: '请输入短信验证码'}
                ]
            }
        }
    },
    methods: {
        /**
         * 活动列表初始化
         */
        init: function () {
            var _this = this;
            // 获取活动列表数据
            var getData = {
                page: 1
            };
            _this.getActList(getData, function (status, msg) {
                var list = msg.lists,
                    freeEndDate = msg.couponEndAt,
                    payEndDate = msg.endAt,
                    tryoutEndDate = msg.freeUseEndTime,
                    gid = msg.gid, // 商城链接
                    mallUrl = '',
                    coupon = msg.coupon,
                    isFree = msg.isFree,
                    userActivityModel = _this.userActivityModel;

                // 为活动列表赋值
                _this.goodList = list;
                _this.goodListNow = list.length > 0 ? new Array(list[0]) : [];
                _this.lookMoreStatus = 0;
                _this.isInit = true;

                // 如果活动列表有值，
                if (list.length > 0) {
                    _this.totalPage = msg.totalPage;
                    statistics.isMall = parseInt(list[0].type);
                    statistics.eventId = list[0].eventId;
                }

                coupon ? _this.coupon = coupon : false;

                // 用户活动状态结束时间赋值
                freeEndDate = freeEndDate ? freeEndDate : '';
                payEndDate = payEndDate ? payEndDate : '';
                tryoutEndDate = tryoutEndDate ? tryoutEndDate : '';

                userActivityModel.freeEndDate = freeEndDate;
                userActivityModel.payEndDate = payEndDate;
                userActivityModel.tryoutEndDate = tryoutEndDate;
                _this.isFree = isFree;

                // 跳转到新建页面添加用户购买状态参数,如果是免费的则不加
                if(!isFree) {
                    _this.apiUrl.create +=
                                            '&freeEndDate=' + freeEndDate +
                                            '&payEndDate=' + payEndDate +
                                            '&tryoutEndDate=' + tryoutEndDate;
                }

                // 活动的商品ID赋值
                gid = gid ? gid : '';

                _this.apiUrl.shopList = _this.getConfigUrl().mallUrl + '/frontend/goods-detail/index?gid=' + gid;
            });
            // 隐藏二维码
            document.addEventListener('click', function (event) {
                var target = event.target;
                !target.hasClass('list-name-code') ? _this.hideAllCode() : false;
            });
            // 复制链接到剪切板
            var clipboard = new Clipboard('.clip');

            _this.isBuy = document.getElementById('isBuyGood').value;
            _this.idouziUrl = document.getElementById('idouziUrl').value;
            _this.signKey = document.getElementById('signKey').value;
            _this.userId = document.getElementById('userId').value;

            clipboard.on('success', function () {
                _this.alertTipsShow('复制成功');
            });


            // 来自新建活动
            if(location.hash.indexOf('newFlag') !== -1) {
                // 删除标志
                location.hash = location.hash.replace('newFlag', '');
                _this.isNewDialog = true;
            }

            // 新建活动预览
            var previewClipboard = new Clipboard('.copy-btn');

            previewClipboard.on('success', function() {
                _this.showCopyed();
            });
        },
        // 复制成功函数
        showCopyed: function() {
            var msgBox = document.createElement('p'),
                body = document.querySelector('body');

            msgBox.classList.add('msg-box');
            msgBox.innerHTML = '<i class="icon iconfont icon-zhengque1"></i><p>复制成功</p>';
    
            body.appendChild(msgBox);
            
            // 隐藏效果计时器
            var hideTimer = setTimeout(function() {
                msgBox.style.opacity = 0;
                clearTimeout(hideTimer);
            }, 1800);

            // 移除节点计时器
            var removeTimer = setTimeout(function() {
                body.removeChild(msgBox);
                clearTimeout(removeTimer);
            }, 2000);
        },
        /**
         * 分页获取活动列表数据
         * @param {Number} current  当前分页数
         */
        getPageActData: function (current) {
            var _this = this,
                getData = {
                    page: current
                };
            _this.nowPage = current;
            _this.getActList(getData, function (status, msg) {
                _this.goodList = msg.lists;
            })
        },
        /**
         * 活动列表数据获取
         * @param  {Object}  data      传入参数
         * @param  {Function}  callback  回调函数
         * @return null
         */
        getActList: function (data, callback) {
            var _this = this;

            _this.bargainLoading = true;
            _this.$http.get(_this.apiUrl.getList, {
                params: data,
                timeout: 10 * 1000
            }).then(function (res) {

                var data = res.data,
                    status = data.return_code,
                    msg = data.return_msg;

                if (status === 'SUCCESS') {
                    if ((typeof callback).toLowerCase() === 'function') {
                        callback(status, msg);
                        _this.bargainLoading = false;
                    }
                } else {
                    _this.alertTipsShow(msg);
                }
            }, function (res) {
                _this.alertTipsShow(res.status + ': 网络错误，请刷新后重试', false);
            });
        },
        //
        /**
         * 点击商品 初始化统计数据
         * @param {Element} event 当前点击的DOM节点
         */
        getData: function (event) {
            var _this = this,
                current = event.currentTarget, // 当前点击的DOM对象
                target = event.target, // 当前点击的子元素
                eventId = current.getAttribute('data-eventId'), // 当前的eventId
                actListIndex = current.getAttribute('data-index'), // 当前的 index
                type = current.getAttribute('data-type'), // 是否是微商城商品
                currentData = _this.goodList[actListIndex], // 当前点击的数组数据
                // 排除当前点击不需要初始化统计数据的节点
                isClick = target.hasClass('operation-item') ||
                    target.hasClass('code') ||
                    target.hasClass('list-name-code') ||
                    target.parentNode.hasClass("qrcode");

            /*
             * 如果当前不是执行操作
             * 在活动列表中删除当前的元素
             * 并将当前的元素添加到数组的顶部
             * 如果当前的活动列表未收缩，收缩当前列表
             * */

            if (!isClick) {
                _this.goodList.splice(actListIndex, 1);
                _this.goodList.unshift(currentData);
                // 将当前选择的活动放入收缩列表中
                _this.goodListNow[0] = currentData;

                if (_this.lookMoreStatus !== 0) {
                    _this.$refs.btnLookMore.innerText = '查看更多';
                    // 修改查看更多按钮的状态
                    _this.lookMoreStatus = 0;
                }
                // 将活动统计列表筛选项置空
                statistics.startTime = '';
                statistics.endTime = '';
                statistics.selectStatus = '';

                statistics.initPage = 1;
                statistics.isMall = type;
                statistics.eventId = eventId;
            }
        },
        /**
         * 查看二维码
         * @param {Element} event 当前点击的DOM节点
         */
        getQrcode: function (event) {
            var _this = this,
                target = event.currentTarget,   //当前对象节点
                next = target.next(); //当前对象子元素

            _this.hideAllCode();

            next.hasClass('qrcode') ? next.addClass('qrcode-active') : false;

        },
        /**
         * 隐藏所有二维码
         */
        hideAllCode: function () {
            var _this = this;

            _this.bargainProtoType.forEachData(_this.qrcode.allqrcode, function (value, index) {
                value.removeClass('qrcode-active');
            });
        },
        /**
         * 编辑活动
         * @param {Element} event 当前点击的DOM节点
         */
        editAct: function (event) {
            var _this = this,
                target = event.currentTarget,
                eventId = target.parentNode.getAttribute('data-eventId');

            open('/supplier/bargain?from=edit&eventId=' + eventId);
        },
        /**
         * 复制活动
         * @param {Element} event 当前点击的DOM节点
         */
        copyAct: function (event) {
            var _this = this,
                userActivityModel = _this.userActivityModel,
                target = event.currentTarget,
                eventId = target.parentNode.getAttribute('data-eventId'),
                isBuy = !(userActivityModel.payEndDate || userActivityModel.freeEndDate) && !userActivityModel.tryoutEndDate;

            if (!_this.isFree && isBuy) {
                // 显示去购买弹窗
                _this.isShowBuyDialog = true;
            }else {
                open('/supplier/bargain?from=copy&eventId=' + eventId);
            }

        },
        /**
         * 删除活动
         * @param {Element} event 当前点击的DOM节点
         */
        deleteAct: function (event) {
            var _this = this,
                target = event.currentTarget,
                // 当前点击活动的eventId
                eventId = target.parentNode.getAttribute('data-eventId'),
                // 活动统计的eventId
                statisticsEventId = statistics.eventId,
                // 当前点击活动的index
                index = target.parentNode.getAttribute('data-index');

            _this.confimDialog('删除活动', '你确定要删除这个活动？', function () {
                _this.$http.get(_this.apiUrl.delList, {
                    params: {
                        eventId: eventId
                    },
                    timeout: 10 * 1000
                }).then(function (res) {
                    var data = res.data,
                        status = data.return_code,
                        msg = data.return_msg;

                    if (status === 'SUCCESS') {
                        _this.alertTipsShow('删除成功');
                        var getData = {
                            page: _this.nowPage
                        };
                        // 重新获取列表数据
                        _this.getActList(getData, function (status, msg) {
                            var list = msg.lists;
                            /*
                             *如果删除的是当前收缩列表的那个活动
                             *就为当前收缩列表重新赋值，并为展开列表赋值
                             *如果不是，就遍历重新获取的列表，找出当前收缩列表的那个活动，并置顶
                             */
                            if (eventId === statisticsEventId) {
                                _this.goodListNow[0] = list.length > 0 ? list[0] : [];
                                _this.goodList = list;
                            } else {
                                _this.goodList = list;
                            }
                            _this.totalPage = msg.totalPage;
                        })
                    } else {
                        _this.alertTipsShow(msg);
                    }
                }, function (res) {
                    _this.alertTipsShow(res.status + ': 网络错误，请刷新后重试');
                })
            });
        },
        /**
         * 关闭活动
         * @param {Element} event 当前点击的DOM节点
         */
        closeAct: function (event) {
            var _this = this,
                target = event.currentTarget,
                parent = target.parentNode,
                eventId = parent.getAttribute('data-eventId'),
                index = parent.getAttribute('data-index');

            _this.confimDialog('关闭活动', '你确定要关闭这个活动？', function () {
                // 执行关闭操作
                _this.$http.get(_this.apiUrl.closeAct, {
                    params: {
                        eventId: eventId
                    },
                    timeout: 10 * 1000
                }).then(function (res) {
                    var data = res.data,
                        status = data.return_code,
                        msg = data.return_msg;

                    if (status === 'SUCCESS') {
                        _this.alertTipsShow('关闭活动成功');
                        // 将当前活动的状态改变
                        if (_this.lookMoreStatus === 0) {
                            _this.goodListNow[0].closeStatus = '已关闭';
                            _this.goodListNow[0].endTime = msg;
                            _this.goodList[0].closeStatus = '已关闭';
                            _this.goodList[0].endTime = msg;
                        } else {

                            if (index == 0) {
                                _this.goodListNow[0].closeStatus = "已关闭";
                                _this.goodListNow[0].endTime = msg
                            }
                            _this.goodList[index].closeStatus = "已关闭";
                            _this.goodList[index].endTime = msg;
                        }
                    } else {
                        _this.alertTipsShow(msg);
                    }
                }, function (res) {
                    _this.alertTipsShow(res.status + ': 网络错误，请刷新后重试');
                })
            });
        },
        /**
         * 查看更多按钮执行的操作
         * @param {Element} event 当前点击的DOM节点
         */
        lookMore: function (event) {
            var _this = this,
                //当前点击的对象
                currentTarget = event.currentTarget;

            if (_this.lookMoreStatus === 0) {
                currentTarget.innerText = '收起';
                // 修改查看更多按钮的状态
                _this.lookMoreStatus = 1;
            } else {
                // 修改查看更多按钮的状态
                _this.lookMoreStatus = 0;
                currentTarget.innerText = '查看更多';
            }
        },
        /**
         * 创建活动
         * @param {Element} event 当前点击的DOM节点
         */
        toNew: function () {
            var _this = this,
                userActivityModel = _this.userActivityModel,
                isBindPhoneUrl = _this.idouziUrl + _this.apiUrl.checkPhoneBind,
                userId = _this.userId,
                signKey = _this.signKey,
                timestamp = (Date.parse(new Date()) / 1000).toString(),
                isEnd = !(userActivityModel.payEndDate || userActivityModel.freeEndDate)  && !userActivityModel.tryoutEndDate, // 是否过期
                params = 'id=' + userId + '&isVaild=1' + '&timestamp=' + timestamp + '&' + signKey;

            if (!_this.isFree && isEnd) {
                // 显示去购买弹窗
                _this.isShowBuyDialog = true;
            } else {
                _this.$http.jsonp(isBindPhoneUrl, {
                    params: {
                        isVaild: 1,
                        id: userId,
                        sign: $.md5(params),
                        timestamp: timestamp
                    }
                }).then(function (res) {
                    var data = res.body;

                    if (data.status == 1) {
                        location.href = _this.apiUrl.create;
                    } else {
                        _this.isShowCheckPhone = true;
                    }

                })
            }
        },

        // 获取短信验证码
        getCode: function (basePhone) {
            var _this = this,
                url = _this.idouziUrl + _this.apiUrl.createBindCode,
                userId = _this.userId,
                signKey = _this.signKey,
                phone = _this.writePhone.tel,
                msgcode = _this.writePhone.imgCode,
                getMessageCodeBtn = document.getElementById('getMessageCode').getElementsByTagName('span')[0],
                timestamp = (Date.parse(new Date()) / 1000).toString(),
                params = 'id=' + userId + '&isVaild=1' + '&msgcode=' + msgcode + '&phone=' + phone + '&timestamp=' + timestamp + '&' + signKey;

            // 发送手机短信验证码
            _this.$refs[basePhone].validate(function (valid) {
                if (valid) {
                    _this.$http.jsonp(url, {
                        params: {
                            isVaild: 1,
                            id: userId,
                            phone: phone,
                            msgcode: msgcode,
                            sign: $.md5(params),
                            timestamp: timestamp
                        }
                    }).then(function (res) {
                        var data = res.body,
                            msg = data.msg;

                        switch (data.status) {
                            case 0:
                            case 1:
                            case 2:
                            case 6:
                            case 8:
                                _this.alertTipsShow(msg);
                                break;
                            case 3:
                            case 4:
                                _this.errorMessage.imgCode = msg;
                                break;
                            default:
                                _this.errorMessage.imgCode = '';
                                _this.alertTipsShow('发送验证码成功');
                                _this.writePhone.messageCodeDisabled = true;
                                var num = 60;
                                var resend = setInterval(function () {
                                    if (num >= 0) {
                                        getMessageCodeBtn.innerText = '重新发送(' + num-- + ')';
                                    } else {
                                        _this.writePhone.messageCodeDisabled = false;
                                        getMessageCodeBtn.innerText = '重新发送';
                                        clearInterval(resend);
                                    }
                                }, 1000);
                                break;

                        }
                    })
                } else {
                    return false;
                }
            })


        },

        // 绑定手机号
        bind: function (basePhone, messagePhone) {
            var _this = this;

            _this.$refs[basePhone].validate(function (valid) {
                if (valid) {
                    // 校验手机号码
                    _this.$refs[messagePhone].validate(function (valid) {
                        var bindPhoneUrl = _this.idouziUrl + _this.apiUrl.bindPhone,
                            userId = _this.userId,
                            signKey = _this.signKey,
                            phone = _this.writePhone.tel,
                            messageCode = _this.writePhone.messageCode,
                            timestamp = (Date.parse(new Date()) / 1000).toString(),
                            params = 'code=' + messageCode + '&id=' + userId + '&isVaild=1' + '&phone=' + phone + '&timestamp=' + timestamp + '&' + signKey;

                        if (valid) {
                            // 提交数据
                            _this.$http.jsonp(bindPhoneUrl, {
                                params: {
                                    id: userId,
                                    isVaild: 1,
                                    phone: phone,
                                    code: messageCode,
                                    sign: $.md5(params),
                                    timestamp: timestamp
                                }
                            }).then(function (res) {
                                var data = res.body,
                                    status = data.status,
                                    msg = data.msg;

                                switch (status) {
                                    case 0:
                                        _this.alertTipsShow(msg);
                                        break;
                                    case 3:
                                        _this.errorMessage.messageCode = msg;
                                        break;
                                    case 6:
                                        _this.alertTipsShow(msg);
                                        break;
                                    case 5:
                                        _this.alertTipsShow('绑定成功');
                                        location.href = _this.apiUrl.create;
                                        break;
                                    default:
                                        break;
                                }
                            })

                        } else {
                            return false;
                        }
                    })

                } else {
                    return false;
                }
            })
        },

        // 取消创建活动
        cancelBindTel: function () {
            var _this = this,
                writePhone = _this.writePhone;

            _this.isShowCheckPhone = false;
            writePhone.tel = '';
            writePhone.imgCode = '';
            writePhone.messageCode = '';
        },

        /**
         * 检验手机号码是否正确
         * @param rule  框架传入的校验规则
         * @param value  框架传入的校验input的值
         * @param callback 错误回调函数
         */
        checkTel: function (rule, value, callback) {
            var _this = this,
                reg = /^1[3|4|5|8|7][0-9]\d{8}$/,
                phone = _this.writePhone.tel,
                signKey = _this.signKey,
                timestamp = (Date.parse(new Date()) / 1000).toString(),
                url = _this.idouziUrl + _this.apiUrl.checkPhoneReg,
                params = 'isVaild=1' + '&phone=' + phone + '&timestamp=' + timestamp + '&' + signKey;

            if (!reg.test(value)) {
                return callback(new Error('请输入正确的手机号码'));
            } else {
                _this.$http.jsonp(url, {
                    params: {
                        isVaild: 1,
                        phone: phone,
                        sign: $.md5(params),
                        timestamp: timestamp
                    }
                }).then(function (res) {
                    var data = res.body;

                    if (data.status != 1) {
                        callback(new Error(data.msg));
                    } else {
                        callback();
                    }
                })
            }
        },

        /**
         * alert提示
         * @param {String} message alert提示的内容
         * @param {Boolean}  isAutoHide 是否自动隐藏
         */
        alertTipsShow: function (message, isAutoHide) {
            var _this = this;
            // 为alertTips赋值
            _this.alertMessage = message;
            // 显示alert提示
            _this.alertIsShow = true;
            // 2S后关闭alert提示
            isAutoHide = isAutoHide !== false ? true : isAutoHide;
            var time = setTimeout(function () {
                _this.alertIsShow = false;
                clearTimeout(time);
            }, 2000);
        },
        /**
         * 确认弹窗
         * 由于框架自带的弹窗确认按钮在右边，需求确认按钮在左边
         * 现将两个按钮调换，原来取消按钮执行确认函数，确认按钮执行取消函数
         * @param {String} title 弹窗标题
         * @param {String} message 弹窗内容
         * @param {Function} callback 回调函数
         */
        confimDialog: function (title, message, callback) {
            var _this = this;
            _this.$confirm(message, title, {
                confirmButtonText: '确定',
                confirmButtonClass: "",
                cancelButtonText: '取消',
                cancelButtonClass: ''

            }).then(function () {
                if ((typeof callback).toLowerCase() === 'function') {
                    callback();
                }
            }).catch(function () {

            });
        },
        /**
         * 获取各个环境的链接
         * @returns {{mallUrl: string}}
         */
        getConfigUrl: function() {
            var env = IdouziTools.getEnv(),
                editUrl = '', // 编辑器URL
                mallUrl = '';

            switch (env) {
                case 'dev':
                    mallUrl = 'http://mall2.idouzi.com';
                    editUrl = 'http://editor-dev.idouzi.com';
                    break;
                case 'test':
                    mallUrl = 'http://mall1.idouzi.com';
                    editUrl = 'http://editor-test.idouzi.com';
                    break;
                case 'prod':
                    mallUrl = 'http://mall.idouzi.com';
                    editUrl = 'http://editor.idouzi.com';
                    break;
                default:
                    mallUrl = 'http://mall.idouzi.com';
                    editUrl = 'http://editor.idouzi.com';
                    break;
            }

            return {
                mallUrl: mallUrl,
                editUrl: editUrl
            }
        }
    },
    created: function () {
        var _this = this;
        _this.init();
    }
});


// 活动统计
var statistics = new Vue({
    el: '.bargain-statistics',
    data: {
        isMall: '', //是否是微商城商品
        selectStatus: '', //兑奖状态选择
        startTime: '', // 开始时间
        endTime: '', // 结束时间
        eventId: '', // 活动ID
        totalPage: '', // 总页数
        searchByNameOrPhone: '', // 姓名或手机号筛选
        activeName: 'act', // 当前tab值
        sendGoodsUrl: '', // 去商城发货链接
        goodLists: [], // 数据统计列表
        // ajax请求
        apiUrl: {
            activityStatistic: '/supplier/activity-statistic',  //获取统计列表
            exportExcel: '/supplier/activity-excel', // 导出Url
            cashPrize: '/supplier/cash-prize' // 兑奖
        },
        bargainActLoading: false, // 是否显示统计加载loading
        status: 0,
        initPage: 1 //初始页的值
    },
    computed: {
        /**
         * 根据是否是微商城商品计算筛选select
         * @returns {Object} 当前select的option值
         */
        selOption: function () {
            var isMallOption = {
                    name: '商品状态',
                    optionList: [
                        {
                            value: '',
                            label: '全部'
                        }, {
                            value: '已砍完',
                            label: '已砍完'
                        }, {
                            value: '待付款',
                            label: '待付款'
                        }, {
                            value: '正在砍',
                            label: '正在砍'
                        }, {
                            value: '已购买',
                            label: '已购买'
                        }
                    ]
                },
                nonMallOption = {
                    name: '兑奖状态',
                    optionList: [
                        {
                            value: '',
                            label: '全部'
                        }, {
                            value: '已兑奖',
                            label: '已兑奖'
                        }, {
                            value: '未兑奖',
                            label: '未兑奖'
                        }
                    ]
                };

            if (this.isMall == 0) {
                return isMallOption;
            } else {
                return nonMallOption;
            }
        },
        /**
         * 统计列表是否有数据
         * @returns {boolean} 判断当前统计列表是否有数据
         */
        statisticsHasData: function () {
            if (this.goodLists.length > 0) {
                return true;
            } else {
                return false;
            }
        },
        /**
         * 格式化后开始时间
         * @returns {Date|String|Number}
         */
        startFormatTime: function () {
            var _this = this,
                startTime = _this.startTime;

            if (startTime instanceof Date) {
                return parseInt(startTime.getTime() / 1000)
            } else if (!startTime) {
                return ''
            } else {
                return startTime
            }
        },
        /**
         * 格式化后结束时间
         * @returns {Date|String|Number}
         */
        endFormatTime: function () {
            var endTime = this.endTime;

            if (endTime instanceof Date) {
                return parseInt(endTime.getTime() / 1000)
            } else if (!endTime) {
                return "";
            } else {
                return endTime;
            }
        }
    },
    methods: {
        /**
         * 根据类型时间筛选活动统计数据
         */
        screenByDateOrType: function () {
            var _this = this,
                postData = {
                    eventId: _this.eventId,
                    startTime: _this.startFormatTime,
                    endTime: _this.endFormatTime,
                    resourceStatus: _this.selectStatus,
                    // 姓名手机查询
                    searchByNameOrPhone: '',
                    page: 1
                };

            _this.getStatisticsData(postData, function (status, msg) {
                // 将当前的统计列表数据赋值
                _this.goodLists = msg.bargainerLists;
                // 将当前的分页数据赋值
                _this.totalPage = msg.totalPage;
                _this.initPage = 1;

            });
        },
        /**
         * 根据姓名手机号筛选数据
         */
        screenByNameOrPhone: function () {
            var _this = this,
                postData = {
                    eventId: _this.eventId,
                    startTime: '',
                    endTime: '',
                    resourceStatus: '',
                    // 姓名手机查询
                    searchByNameOrPhone: _this.searchByNameOrPhone,
                    page: 1
                };

            _this.getStatisticsData(postData, function (status, msg) {
                // 将当前的统计列表数据赋值
                _this.goodLists = msg.bargainerLists;
                // 将当前的分页数据赋值
                _this.totalPage = msg.totalPage;
                _this.initPage = 1;
            })

        },
        /**
         * 导出数据
         */
        outPut: function () {
            var _this = this,
                postData = {
                    eventId: _this.eventId,
                    startTime: _this.startFormatTime,
                    endTime: _this.endFormatTime,
                    resourceStatus: _this.selectStatus,
                    isMall: _this.isMall
                },
                url = _this.apiUrl.exportExcel;

            open(url + bargain.bargainProtoType.formatGetUrlData(postData));
        },
        /**
         * 分页获取统计列表数据
         * @param {Number} [currentPage] 当前分页数
         */
        getActData: function (currentPage) {
            var _this = this,
                postData = {
                    eventId: _this.eventId,
                    endTime: _this.endFormatTime,
                    startTime: _this.startFormatTime,
                    searchByNameOrPhone: _this.searchByNameOrPhone,
                    resourceStatus: _this.selectStatus
                };

            postData.page = currentPage ? currentPage : 1;

            _this.getStatisticsData(postData, function (status, msg) {
                // 将当前的统计列表数据赋值

                _this.goodLists = msg.bargainerLists;
                // 将当前的分页数据赋值
                _this.totalPage = msg.totalPage;

                currentPage ? _this.initPage = currentPage : false;
                // 为去商城发货url赋值
                if (!_this.sendGoodsUrl) {
                    _this.sendGoodsUrl = msg.sendGoodsUrl;
                }
            });
        },
        /**
         * 获取统计列表数据
         * @param {Object} postData 获取统计列表post参数
         * @param {Function} callback  回调函数
         */
        getStatisticsData: function (postData, callback) {
            var _this = this;

            _this.goodLists = [];
            _this.bargainActLoading = true;

            _this.$http.post(_this.apiUrl.activityStatistic + '?page=' + postData.page, postData, {
                emulateJSON: true,
                timeout: 10 * 1000
            }).then(function (res) {
                // 判断callback是否是一个回调函数
                var data = res.data,
                    status = data.return_code,
                    msg = data.return_msg;
                // 如果返回成功，就执行回调函数
                if (status === 'SUCCESS') {
                    _this.bargainActLoading = false;
                    if ((typeof callback).toLowerCase() === 'function') {
                        callback(status, msg);
                    }
                } else {
                    _this.alertTipsShow(msg);
                }

            }, function (res) {
                _this.alertTipsShow(res.status + ': 网络错误，请刷新后重试');
            })
        },
        /**
         * 兑奖
         * @param {Element} event 当前点击的DOM节点
         */
        getCashPrize: function (event) {
            var _this = this,
                target = event.currentTarget,
                bargainId = target.getAttribute('data-prizeId'),
                actIndex = target.getAttribute('data-index');

            bargain.confimDialog('兑奖', '你确定要兑奖？', function () {
                _this.$http.get(_this.apiUrl.cashPrize, {
                    params: {
                        bargainId: bargainId
                    },
                    timeout: 10 * 1000
                }).then(function (res) {
                    var data = res.data,
                        status = data.return_code,
                        msg = data.return_msg;

                    if (status === 'SUCCESS') {
                        bargain.alertTipsShow('兑换成功');
                        bargain.bargainProtoType.forEachData(_this.goodLists, function (value, index) {
                            if (actIndex == index) {
                                value.resourceStatus = '已兑奖';
                            }
                        });
                    } else {
                        bargain.alertTipsShow(msg);
                    }
                }, function (res) {
                    _this.alertTipsShow(res.status + ': 网络错误，请刷新后重试');
                })
            });
        }
    },
    watch: {
        /**
         * 监听eventId,如果eventId发生改变，就重新获取数据
         */
        eventId: function () {
            var _this = this;
            _this.getActData();
        }
    }

});
