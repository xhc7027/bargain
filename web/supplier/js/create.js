var app = new Vue({
    el: "#main-wrap",
    components: {
        // alert组件
        'alert-tips': {
            props: ['message'],
            template: '<div class="alert-tips" ref="deleteList">' +
            '{{message}}' +
            '</div>'
        }
    },
    data: {
        // 配置默认显示tab
        activeName: 'first',

        mallId: '', //商城id

        // ajax链接
        ajaxUrl: {
            uploadImg: '/supplier/file', //上传图片接口
            checkWord: '/supplier/check-keyword', // 检查关键词是否存在
            getDefaultData: '/supplier/get-bargain-data', // 活动页面数据获取
            getBargainCalculate: '/supplier/get-bargain-calcul', // 获取预估砍价刀数
            saveBargain: '/supplier/save-bargain' // 保存
        },

        // 判断来源
        from: '',

        // 编辑器
        editor: '',

        // 公共的方法
        bargain: new Bargain(),

        // 是否显示选择商品按钮
        isShowSelecteGoodsBtn: false,

        // 手机展示跳转
        phone: {
            index: 1,
            oldIndex: ''
        },

        // 是否是进行中的活动
        isGoingAct: 0,

        // 用户账户信息（免费模式，付费模式截止时间）
        userActivityModel: {
            freeModelEndDate: '',
            payModelEndDate: '',
            tryoutModelEndDate: '', // 试用模式截止时间
            userModelInfo: '', // 当前用户的模式
            formatUserModelEndDate: ''  // 格式化用户结束时间
        },

        // 基础设置数据
        baseSetting: {
            name: '', // 商品名称
            isMallGood: 1, // 是否是微商城商品
            organizer: '', // 活动单位
            startDate: '', // 开始时间
            endDate: '', // 结束时间
            adLink: '', // 跳转链接（选填）
            adImages: [] // 轮播图
        },
        selGoods: false, // 是否显示选择微商城商品弹出框

        stock: '', // 商品库存(如果是微商城才有此字段)

        //微商城商品数据
        mallGoodsData: {
            cate: {}, // 商品分类
            goods: '', // 商品数据
            size: 8, // 每页显示个数选择器的选项
            nowPage: 1, // 当前分页数
            pageTotal: '', // 分页数
            errorMsg: '', // 错误提示
            mallSearchCate: '', // 当前选中的分类
            goodsSelected: '', // 选中的商品
            oldGoodsSelected: '', //保存旧的选中的商品id
            mallSearchName: '', // 搜索商品名称
            isLoadingShow: false, // 是否显示loading
        },

        // 活动设置数据
        actSetting: {
            price: 0, // 商品原价
            number: 0, // 活动商品数量
            lowestPrice: 0, // 砍价目标
            priceReduction: '', // 降价概率
            priceReductionMin: 0, // 降价最小范围
            priceReductionMax: 0, // 降价最大范围
            priceIncrease: '', // 涨价概率
            priceIncreaseMin: 0, // 涨价最大范围
            priceIncreaseMax: 0, // 涨价最小范围
            acquisitionTiming: '1', // 联系信息在报名前后填写的判断
            contact: [], // 收集参与者的信息字段
            // 预计砍多少刀
            priceTimes: {
                leastTimes: 0,
                mostTimes: 0
            }
        },
        // 选中的联系信息
        contactSelect: [],
        // 高级设置数据
        advSetting: {
            title: '', // 活动标题
            keyword: '', // 关键词
            image: '', // 活动照片
            shareTitle: '', // 分享标题
            shareContent: '', // 分享内容
            shareImage: '', // 分享图
            description: '', // 活动介绍
            isSettingShare: '1' //是否是默认分享设置
        },
        saveTrafficModel: true, // 存流量模式 （0 去除小尾巴模式 1 存流量模式）
        isShowModelSelect: '', // 是否显示选择存流量模式选择框
        /*
         * 如果是编辑需要此变量，
         * 根据此变量判断关键词是否改变，
         * 从而判断是否需要校验关键词
         * */
        oldKeyword: '',
        // 是否校验关键词
        isCheckKeyword: true,
        // 默认分享数据
        defaultShare: {
            shareContent: '',
            shareImage: '',
            shareTitle: ''
        },
        // 是否离开页面
        isLeave: false,
        // 错误提示信息
        errorMessage: {
            date: '',
            adImages: '',
            content: '',
            settingPrice: '',
            contact: '',
            actImg: '',
            shareImg: ''
        },
        // 是否显示alert
        alertIsShow: false,
        // alert信息
        alertMessage: '',
        // 是否初始化完成
        isInit: false
    },
    watch: {
      'baseSetting.endDate': function(val,oldVal) {
          var _this = this,
              userModel = '', // 当前用户选择时间状态（0 超出免费范围 1 超出付费范围 2 免费模式 3 付费模式）
              tipsInfo = '', // 用户当前模式提示信息
              userActivityModel = _this.userActivityModel,
              freeEndDate = userActivityModel.freeModelEndDate,
              payEndDate = userActivityModel.payModelEndDate,
              tryoutEndDate = userActivityModel.tryoutModelEndDate,
              freeModelEndDate = '', //  当前用户免费模式截止时间
              payModelEndDate = '', // 当前用户活动付费模式截止时间
              tryoutModelEndDate = '', // 当前用户活动试用模式截止时间
              // 免费模式截至时间字符串
              freeModelEndDateString = '',
              // 付费模式截至时间字符串
              payModelEndDateString = '',
              // 试用模式截止时间字符串
              tryoutModelEndDateString = '',
              endDate = new Date(val).getTime() - 60 * 60 * 1000 * 24;

          if(freeEndDate) {
              freeModelEndDate = new Date(freeEndDate).getTime();
              freeModelEndDateString = _this.formatDateToChinese(new Date(freeEndDate));
          }

          if(payEndDate) {
              payModelEndDate = new Date(payEndDate).getTime();
              payModelEndDateString = _this.formatDateToChinese(new Date(payEndDate));
          }

          //    如果付费模式和免费模式都不存在
          if(!(freeEndDate || payEndDate) && tryoutEndDate ) {
              tryoutModelEndDate = new Date(tryoutEndDate).getTime();
              tryoutModelEndDateString = _this.formatDateToChinese(new Date(tryoutEndDate));
          }

          if(endDate) {
              // 如果免费模式和付费模式都存在
              if(freeModelEndDate && payModelEndDate) {
                  // 如果免费模式大于付费模式
                  if(freeModelEndDate > payModelEndDate) {
                      if(endDate <= payModelEndDate) {
                          // 当前为付费模式
                          _this.isShowModelSelect = true;
                          userModel = 3;
                      }else if(endDate <= freeModelEndDate) {
                          // 当前为免费模式
                          _this.isShowModelSelect = false;
                          userModel = 2;
                      }else {
                          // 当前超出免费范围
                          _this.isShowModelSelect = false;
                          userModel = 0;
                      }
                  }else {
                      if(endDate <= freeModelEndDate) {
                          // 当前为免费模式
                          _this.isShowModelSelect = false;
                          userModel = 2;
                      }else if(endDate <= payModelEndDate) {
                          // 当前为付费模式
                          _this.isShowModelSelect = true;
                          userModel = 3;
                      }else {
                          // 当前超出免费范围
                          _this.isShowModelSelect = false;
                          userModel = 1;
                      }
                  }
              } else if(payModelEndDate) {
                  if(endDate <= payModelEndDate) {
                      _this.isShowModelSelect = true;
                      userModel = 3;
                  }else {
                      _this.isShowModelSelect = false;
                      userModel = 1;
                  }
              } else if(freeModelEndDate) {
                  // 如果免费模式存在
                  userModel = endDate < freeModelEndDate ? 2 : 0;

                  _this.isShowModelSelect = false;
              } else if(tryoutModelEndDate) {
                  // 如果试用模式存在
                  userModel = endDate < tryoutModelEndDate ? 4 : 0;

                  _this.isShowModelSelect = false;
              }
          }else {
              _this.isShowModelSelect = false;
          }

          if(userModel === 2) {
              tipsInfo = '您当前为<span class="strong">免费使用</span>模式，截止日期至' + freeModelEndDateString;
          }else if(userModel === 3) {
              tipsInfo = '您当前为<span class="strong">付费使用</span>模式，截止日期至' + payModelEndDateString;
          }else if(userModel === 4) {
              tipsInfo = '您当前为<span class="strong">免费试用</span>模式，截止日期至' + tryoutModelEndDateString;
          }else if(userModel === 1 || userModel === 0) {
              tipsInfo = '活动截止时间不能超过使用模式截止期';
          }else {
              tipsInfo = '';
          }

          userActivityModel.userModelInfo = tipsInfo;

      }
    },
    computed: {
        // 获取商品数据接口
        getMallGoods: function() {
            var param = '',
                env = IdouziTools.getEnv();

            if (env == 'dev') {
                param += '-dev';
            } else if (env == 'test') {
                param += '-test';
            } else {
                param = '';
            }

            return 'http://search' + param + '.mall.idouzi.com/api/bargain-get-shop-info';
        },

        /**
         * 是否是开始编辑的
         * @returns {boolean} 判断是否是来自编辑
         */
        isStartEdit: function () {
            return this.from === 'edit' && this.isGoingAct === 1;
        },
        /**
         * 是否是正确的砍价刀数
         * @returns {boolean}
         */
        isTruePriceTimes: function () {
            var _this = this;
            return _this.actSetting.priceTimes.leastTimes >= 0 && _this.actSetting.priceTimes.mostTimes >= 0;
        },

        /**
         * 基础设置规则校验
         * @returns {Object}
         */
        baseSettingRules: function () {
            return {
                name: [
                    {required: true, message: '请输入活动名称'}
                ],
                organizer: [
                    {required: true, message: '请输入活动单位', trigger: 'blur'}
                ],
                startDate: [
                    {type: 'date', required: true, message: '请选择开始时间', trigger: 'change'}
                ],
                endDate: [
                    {type: 'date', required: true, message: '请选择结束时间', trigger: 'change'},
                    {validator: this.checkEndDate}
                ],
                adLink: [
                    {validator: this.checkAdLink}
                ]
            }
        },
        //
        /**
         * 活动设置规则校验
         * @returns {Object}
         */
        actSettingRules: function () {
            return {
                price: [
                    {required: true, message: '商品原价不能为空'},
                    {type: 'number', message: '商品原价必须为数字'}
                ],
                number: [
                    {validator: this.checkMallNumber}
                ],
                lowestPrice: [
                    {validator: this.checkMallPrice}
                ]
            };
        },
        /**
         * 高级设置规则校验
         * @returns {Object}
         */
        advSettingRules: function () {
            return {
                title: [
                    {required: true, message: '活动标题不能为空'}
                ],
                keyword: [
                    {required: true, message: '关键词不能为空'},
                    {validator: this.checkAdvKeyword, trigger: "blur"}
                ],
                description: [
                    {required: true, message: '活动说明不能为为空'}
                ],
                shareTitle: [
                    {required: true, message: '分享标题不能为空'}
                ],
                shareContent: [
                    {required: true, message: '分享内容不能为空'}
                ]
            }
        },
        // 手机展示默认关键词
        phoneKeyWord: function () {
            return this.advSetting.keyword === '' ? '关键词' : this.advSetting.keyword;
        },
        // 手机展示背景图片
        phoneBg: function () {
            var index = this.phone.index,
                phoneBgClass;

            switch (index) {
                case 0:
                    phoneBgClass = 'phone-description';
                    break;
                case 1:
                    phoneBgClass = 'phone-index';
                    break;
                case 2:
                    phoneBgClass = 'phone-write';
                    break;
                case 3:
                    phoneBgClass = 'phone-cut';
                    break;
                case 4:
                    phoneBgClass = 'phone-success';
                    break;
                case 5:
                    phoneBgClass = 'phone-prize';
                    break;
                case 6:
                    phoneBgClass = 'phone-prize-code';
                    break;
                case 7:
                    phoneBgClass = 'phone-prize-success';
                    break;
            }

            return phoneBgClass;

        },
        // 手机展示活动说明按钮是否显示
        isShowPhoneBtn: function () {
            var index = this.phone.index;
            return index === 1 || index === 3 || index === 5 || index === 7
        }
    },
    methods: {
        /**
         * 初始化
         */
        init: function () {
            var _this = this,
                userActivityModel = _this.userActivityModel,
                freeModelEndDate = IdouziTools.getQueryValue('freeEndDate'), // 免费模式截止时间
                payModelEndDate = IdouziTools.getQueryValue('payEndDate'),  // 付费模式截止时间
                tryoutModelEndDate = IdouziTools.getQueryValue('tryoutEndDate'), // 试用模式截止时间
                bannerList = document.getElementsByClassName('banner-list')[0];
            // 配置轮播图拖拽排序
            Sortable.create(bannerList, {
                animation: 250,
                sort: true,
                dataIdAttr: 'data-sort'
            });

            // 用户功能免费模式截止时间，付费模式截至时间赋值
            userActivityModel.freeModelEndDate = freeModelEndDate ? freeModelEndDate.replace(/-/g, '/') : '';
            userActivityModel.payModelEndDate = payModelEndDate ? payModelEndDate.replace(/-/g, '/') : '';
            userActivityModel.tryoutModelEndDate = tryoutModelEndDate ? tryoutModelEndDate.replace(/-/g, '/') : '';

            // 初始化编辑器
            _this.editor = UM.getEditor('editor');
            // 离开网页打开系统确认弹窗
            window.onbeforeunload = this.isShowLeaveTips;
            // 获取url参数
            _this.from = _this.bargain.getUrlParam('from');

            // 将时间格式中的 - 转换成  /

            // 获取页面数据
            _this.getOldData();
        },
        /**
         * 手机展示跳转
         */
        jumpWriteInfo: function () {
            var _this = this,
                phone = _this.phone,
                index = phone.index;

            index !== 0 ? phone.oldIndex = index : false;

            switch (index) {
                case 0:
                    phone.index = phone.oldIndex;
                    break;
                case 1:
                    phone.index = 2;
                    break;
                case 2:
                    phone.index = 3;
                    break;
                case 3:
                    phone.index = 4;
                    break;
                case 4:
                    phone.index = 5;
                    break;
                case 5:
                    phone.index = 6;
                    break;
                case 6:
                    phone.index = 7;
            }
        },
        /**
         * 手机活动说明展示
         */
        jumpActDescription: function () {
            var _this = this,
                phone = _this.phone;
            phone.oldIndex = phone.index;
            phone.index = 0;
        },
        /**
         * 手机展示返回上一步
         */
        phoneJumpBack: function () {
            var phone = this.phone,
                index = phone.index;
            index > 1 ? phone.index = index - 1 : false;
        },
        /**
         * 初始化数据获取
         */
        getOldData: function () {
            var _this = this,
                bargainFn = _this.bargain,
                from = _this.from,
                eventId = bargainFn.getUrlParam('eventId');

            var postData = {
                from: from,
                eventId: from === 'create' ? '' : eventId
            };

            // 获取数据
            _this.$http.get(_this.ajaxUrl.getDefaultData, {
                params: postData,
                timeout: 10 * 1000
            }).then(function (res) {
                var data = res.data,
                    msg = data.return_msg;
    
                if (data.return_code === 'SUCCESS') {
                    // 砍价设置
                    var bargainProbability = msg.bargainProbability,
                        // 商品数据
                        resources = msg.resources,
                        // 商品数量
                        number = resources.number,
                        // 高级设置
                        advSettingData = msg.advancedSetting,
                        // 关键词
                        keyword = advSettingData.keyword ? advSettingData.keyword : '';

                    _this.isShowSelecteGoodsBtn = !!msg.mallStatus,

                    // 基础设置
                    _this.baseSetting = {
                        name: '',
                        isMallGood: parseInt(resources.type),
                        organizer: msg.organizer,
                        startDate: new Date(msg.startTime * 1000),
                        endDate: new Date(msg.endTime * 1000),
                        adLink: msg.adLink,
                        adImages: msg.adImages
                    };
                    // 为编辑器赋值
                    _this.editor.setContent(msg.content);

                    _this.mallGoodsData.goodsSelected = resources.id ? resources.id : '';
                    // 是否是进行中的活动
                    _this.isGoingAct = msg.eventStart ? msg.eventStart : '';
                    // 活动设置
                    _this.actSetting = {
                        // 商品原价
                        price: resources.price,
                        // 砍价目标
                        lowestPrice: msg.lowestPrice,
                        // 降价几率
                        priceReduction: bargainProbability.priceReduction === '' ? '' : bargainProbability.priceReduction * 100,
                        // 最小降价范围
                        priceReductionMin: bargainProbability.priceReductionRange[0],
                        // 最大降价范围
                        priceReductionMax: bargainProbability.priceReductionRange[1],
                        // 涨价几率
                        priceIncrease: bargainProbability.priceIncrease === '' ? '' : bargainProbability.priceIncrease * 100,
                        // 最小涨价几率
                        priceIncreaseMin: bargainProbability.priceIncreaseRange[0],
                        // 最大涨价几率
                        priceIncreaseMax: bargainProbability.priceIncreaseRange[1],
                        // 联系信息填写时间
                        acquisitionTiming: msg.acquisitionTiming.toString(),
                        // 参与者字段信息
                        contact: msg.contact,
                        // 预计砍多少刀
                        priceTimes: msg.priceTimes
                    };

                    // 高级设置
                    _this.advSetting = {
                        // 活动标题
                        title: advSettingData.title,
                        // 关键词
                        keyword: keyword,
                        // 活动照片
                        image: advSettingData.image,
                        // 分享标题
                        shareTitle: advSettingData.shareTitle,
                        // 分享内容
                        shareContent: advSettingData.shareContent,
                        // 分享图
                        shareImage: advSettingData.shareImage,
                        // 活动说明
                        description: advSettingData.description,
                        // 是否自定义设置分享
                        isSettingShare: advSettingData.shareType.toString()
                    };

                    _this.mallId = resources.mallId || '';
                    
                    /**
                     * 根据from判断默认数据来源
                     * 如果是新建，就将分享数据储存为默认分享数据，
                     * 否则，从高级数据中获取
                     */
                    if (from === 'create') {
                        _this.defaultShare = {
                            shareContent: advSettingData.shareContent,
                            shareImage: advSettingData.shareImage,
                            shareTitle: advSettingData.shareTitle
                        }
                    } else {
                        _this.defaultShare = {
                            shareContent: advSettingData.defaultShareContent,
                            shareImage: advSettingData.defaultShareImage,
                            shareTitle: advSettingData.defaultShareTitle
                        }
                    }

                    // 绑定初始关键词
                    _this.oldKeyword = advSettingData.keyword ? advSettingData.keyword : '';

                    /**
                     * 如果是微商城商品，重新获取当前选择商品的库存
                     * 否则直接为当前商品数量赋值
                     */
                    if (resources.type == 0 && _this.from === 'copy') {
                        _this.$http.get(_this.getMallGoods, {
                            params: {
                                goodsId: resources.id
                            },
                            timeout: 10 * 1000,
                            credentials: true
                        }).then(function (res) {
                            var data = res.data,
                                msg = data.return_msg;

                            // 如果当前商品存在，就将当前商品库存赋值，否则当前商品库存为-1；
                            if (data.return_code === 'SUCCESS') {
                                var goodsList = msg.goodsList.goodsData;

                                if (goodsList.length) {
                                    _this.stock = goodsList[0].stock;
                                    _this.baseSetting.name = goodsList[0].goodsName;
                                } else {
                                    _this.clearMallName();
                                    _this.alertTipsShow('商品为空');
                                }
                            } else {
                                _this.clearMallName();
                                _this.alertTipsShow(msg);
                            }

                            _this.actSetting.number = number;
                        }, function (res) {
                            _this.alertTipsShow(res.status + ': 网络错误，请刷新后重试');
                        })
                    } else {
                        _this.baseSetting.name = msg.name;
                        _this.actSetting.number = number;
                    }

                    // 初始化完成
                    _this.isInit = true;

                } else {
                    _this.alertTipsShow(msg);
                }
            }, function (res) {
                _this.alertTipsShow(res.status + ': 获取数据失败，请刷新后重试', false);
            })
        },
        /**
         * 删除轮播图片
         * @param {Element} event 当前点击的DOM节点
         */
        removeBanner: function (event) {
            var adImage = this.baseSetting.adImages,
                currentTarget = event.currentTarget,
                bannerListItem = currentTarget.parentNode,
                index = parseInt(bannerListItem.getAttribute('data-sort'));

            adImage.splice(index, 1);
        },
        /**
         * 选择微商城商品按钮点击事件
         */
        selMallGoods: function () {
            var _this = this;

            _this.selGoods = true;
            _this.mallGoodsData.oldGoodsSelected = _this.mallGoodsData.goodsSelected;
            // 获取微商城商品数据
            _this.getGoodsData();
        },
        /**
         * 获取商城数据
         * @param {Number} [page] 当前分页数
         * @param {Object} [getData] 微商城商品筛选项
         */
        getGoodsData: function (page, getData) {
            var _this = this,
                formatGoodsData; // 格式化后的商品数据

            // 如果getData存在，就验证是否传入page
            getData = getData || {};

            getData.pageSize = 10;
            getData.page = page || _this.mallGoodsData.nowPage;
            // 显示loading图标
            _this.mallGoodsData.isLoadingShow = true;

            // 获取商城商品数据
            _this.$http.get(_this.getMallGoods, {
                params: getData,
                timeout: 10 * 1000,
                credentials: true
            }).then(function (res) {
                var data = res.data,  //返回的数据
                    msg = data.return_msg,  //返回值内容
                    mallGoodsData = _this.mallGoodsData;

                if (data.return_code === 'SUCCESS') {
                    // 遍历分类及赋值
                    var oldCate = msg.categoryList,
                        newCate = [];

                    for (var i=0,len=oldCate.length; i<len; i++) {
                        var childs = oldCate[i].children;
                        
                        oldCate[i].isTop = true;
                        newCate.push(oldCate[i]);

                        if (childs && childs.length) {
                            for (var j=0,secLen=childs.length; j<secLen; j++) {
                                newCate.push(childs[j]);
                            }
                        }
                    }

                    // 将商品分类数据赋值
                    mallGoodsData.cate = newCate;
                    // 商品列表分页赋值
                    mallGoodsData.pageTotal = msg.goodsList.total;

                    formatGoodsData = msg.goodsList.goodsData;

                    // 商品列表分类赋值
                    for (var i = 0, len = formatGoodsData.length; i < len; i++) {
                        var createTime = formatGoodsData[i].createdTime;

                        formatGoodsData[i].createdTime = new Date(createTime * 1000).Format('yyyy-MM-dd hh:mm');
                    }

                    mallGoodsData.goods = formatGoodsData;

                    // 初始化商品分类滚动条
                    if (msg.goodsList.total !== 0) {
                        _this.mallId = msg.goodsList.goodsData[0].mallId;
                        
                        var timeout = setTimeout(function () {
                            $('.googs-content-list').niceScroll({
                                cursorcolor: '#ccc'
                            });
                            $('.googs-content-list').getNiceScroll().resize();

                            clearTimeout(timeout);
                        }, 100);
                    }

                    // 将当前选择的商品列表选中项置空
                    _this.mallGoodsData.goodsSelected = '';
                    // 隐藏loading图标
                    _this.mallGoodsData.isLoadingShow = false;
                } else {
                    // 隐藏loading图标
                    _this.mallGoodsData.isLoadingShow = false;
                    mallGoodsData.goods = [];
                }
            }, function (res) {
                _this.alertTipsShow(res.status + ": 网络错误，请刷新后重试");
            });

        },
        /**
         * 分页获取商城数据
         * @param {Number} currentPage 当前分页数
         */
        mallCurrentPage: function (currentPage) {
            var _this = this;
            // 将当前page改为currentPage
            _this.mallGoodsData.nowPage = currentPage;
            _this.getGoodsData(currentPage);

        },
        /**
         * 按商品名称搜索微商城商品
         */
        SearchGoodsByName: function () {
            var _this = this,
                getData = {
                    goodsName: _this.mallGoodsData.mallSearchName
                };

            _this.getGoodsData(1, getData);
        },
        /**
         * 按商品分类查询
         */
        SearchGoodsByType: function () {
            var _this = this,
                getData = {
                    categoryId: _this.mallGoodsData.mallSearchCate
                };

            _this.getGoodsData(1, getData);
        },
        /**
         * 添加商品
         * @returns {boolean}
         */
        addMallGoods: function () {
            var _this = this,
                mallGood = _this.mallGoodsData,
                goodsSelected = mallGood.goodsSelected,
                // 活动商品数量
                number = _this.actSetting.number,
                // 砍价目标
                lowerPrice = _this.actSetting.lowestPrice,
                goodsName = '',
                goodsPrice = '',
                stock = '';

            // 判断当前是否选择
            if (!mallGood.oldGoodsSelected && !mallGood.goodsSelected) {
                mallGood.errorMsg = '请选择要砍价的商品';
                return false;
            } else {
                _this.selGoods = false;
                // 如果当前的商品名称有值
                _this.bargain.forEachData(mallGood.goods, function(value) {
                    if (value.goodsId === mallGood.goodsSelected) {
                        goodsName = value['goodsName'];
                        goodsPrice = value['benefitPrice'];
                        stock = value['stock'];
                        return false;
                    }
                });

                if (goodsName) {
                    // 为商品名称赋值
                    _this.baseSetting.name = goodsName;
                    // 将当前的商品状态改为微商城商品
                    _this.baseSetting.isMallGood = 0;
                    // 将当前商品原价改为该商品的价格
                    _this.actSetting.price = goodsPrice;
                    // 如果当前砍价目标大于商品原价，就将当前的砍价目标置为商品原价
                    _this.actSetting.lowestPrice = lowerPrice > goodsPrice ? goodsPrice : lowerPrice;
                    // 设置该商品的库存，商品数量不对大于该库存
                    _this.stock = parseInt(stock);
                    // 如果当前的商品数量大于库存，就将当前的商品数量置为当前库存
                    _this.actSetting.number = number > stock ? stock : number;
                    // 清空错误信息
                    mallGood.errorMsg = '';
                    // 将当前的联系信息填写时间置为参赛前填写
                    _this.actSetting.acquisitionTiming = '0';
                    // 获取砍价刀数
                    _this.getPriceTimes();
                }

                // 如果当前选择的商品id和进来保存的商品id不相同，就让当前选择的商品id等于进来保存的商品id
                if ((mallGood.goodsSelected !== mallGood.oldGoodsSelected) && !mallGood.goodsSelected) {
                    mallGood.goodsSelected = mallGood.oldGoodsSelected;
                }
            }
        },
        /**
         * 取消添加微商城商品
         */
        cancelAddMallGoods: function () {
            var _this = this,
                mallGoods = _this.mallGoodsData;
            // 关闭弹框
            _this.selGoods = false;
            // 如果当前选择的商品id和进来保存的商品id不相同，就让当前选择的商品id等于进来保存的商品id
            mallGoods.goodsSelected !== mallGoods.oldGoodsSelected ?
                mallGoods.goodsSelected = mallGoods.oldGoodsSelected : false;

            // 清空错误信息
            mallGoods.errorMsg = '';
        },
        /**
         * 清除当前选择的微商城商品
         */
        clearMallName: function () {
            var _this = this;
            // 清空商品名称
            _this.baseSetting.name = '';
            // 将当前的活动商品状态改为非微商城
            _this.baseSetting.isMallGood = 1;
            // 将当前的价格置空
            _this.actSetting.price = '';
            // 将当前的微商城商品选择置空
            _this.mallGoodsData.goodsSelected = '';

        },
        /**
         * 获取预砍刀数
         */
        getPriceTimes: function () {
            var _this = this,
                actData = _this.actSetting,
                // 砍价设置input框
                priceSettingInput = document.querySelectorAll('.setting-price input'),
                // 判断降价几率和涨价几率之和是否等于100
                isChangeNumber = actData.priceIncrease + actData.priceReduction === 100,
                // 最大降价值或涨价值
                maxRange = _this.actSetting.price - _this.actSetting.lowestPrice,
                // 判断降价范围是否正确
                isReduceRange = actData.priceReductionMax - actData.priceReductionMin >= 0,
                // 最大降价范围是否超出最大可砍范围
                isReduceMax = maxRange >= actData.priceReductionMax,
                // 判断涨价范围是否正确
                isRiseRange = actData.priceIncreaseMax - actData.priceIncreaseMin >= 0,
                // 最大涨价范围是否超出最大可砍范围
                isRiseMax = maxRange >= actData.priceIncreaseMax;

            /*
             * 判断是否为数字
             * 遍历概率设置下的所有input
             * 判断是否是数字或空字符
             * */
            for (var index = 0; index < priceSettingInput.length; index++) {
                var input = priceSettingInput[index],//当前对象
                    value = parseFloat(input.value),  //当前对象的值
                    // 判断当前对象的值是否是数字以及是否大于0
                    isTrueNumber = _this.checkNumber(value) && value >= 0;

                if (!isTrueNumber) return false;

                // 判断填写的是否是砍价几率
                if (input.hasClass('priceReduction') || input.hasClass('priceIncrease')) {
                    var isChanceRange = value < 0 || value > 100;
                    if (isChanceRange) return false;
                }
            }

            // 判断降价几率和涨价几率之和是否等于100
            if (!isChangeNumber) return false;
            // 校验降价范围是否正确
            if (!isReduceRange) return false;
            // 校验涨价范围是否正确
            if (!isRiseRange) return false;

            if (!isReduceMax || !isRiseMax) {
                _this.actSetting.priceTimes = {
                    leastTimes: 0,
                    mostTimes: 0
                };
                return false;
            }
            // // 判断降价范围是否超出可砍范围
            // if (!isReduceMax) return false;
            // // 校验涨价范围是否超出可砍范围
            // if (!isRiseMax) return false;

            var postData = {
                    bargainProbability: {
                        // 降价概率
                        priceReduction: actData.priceReduction / 100,
                        // 降价范围
                        priceReductionRange: [actData.priceReductionMin, actData.priceReductionMax],
                        // 涨价概率
                        priceIncrease: actData.priceIncrease / 100,
                        // 涨价范围
                        priceIncreaseRange: [actData.priceIncreaseMin, actData.priceIncreaseMax]
                    },
                    // 最低价
                    lowestPrice: actData.lowestPrice,
                    // 原价
                    price: actData.price
                },
                url = _this.ajaxUrl.getBargainCalculate;
            // 发送请求获取刀数
            _this.$http.post(url, postData, {
                emulateJSON: true,
                timeout: 10 * 1000
            }).then(function (res) {
                var data = res.data,
                    status = data.return_code,
                    msg = data.return_msg;
                if (status === 'SUCCESS') {
                    // 是否能砍到最低价
                    var timesIsTrue = msg.leastTimes < 0 || msg.mostTimes < 0;

                    _this.actSetting.priceTimes = {
                        leastTimes: msg.leastTimes,
                        mostTimes: msg.mostTimes
                    };
                    // 错误提示信息
                    _this.errorTips({
                        status: timesIsTrue,
                        errorName: 'settingPrice',
                        errorMsg: '您这样设置可能永远砍不到最低价哦'
                    });
                }
            }, function (res) {
                _this.alertTipsShow(res.status + ': 网络错误，请刷新后重试');
            })
        },
        /**
         * 上传轮播图片
         * @param {Element} event 当前点击的DOM节点
         * @returns {boolean}
         */
        uploadBanner: function (event) {
            var _this = this,
                target = event.currentTarget,
                files = target.files,
                bannerListLen = document.querySelectorAll('.banner-list li').length;
            /*
             * 如果浏览器版本是IE9及以下版本，
             * 不支持files api 只能一次上传一张图片
             *
             * */

            if (files.length === 0) {
                return false;
            }

            if (files.length + bannerListLen > 3) {
                _this.$alert('最多只能上传3张图片', '上传图片', {
                    confirmButtonText: '确定'
                })
            } else {
                for (var i = 0, len = files.length; i < len; i++) {
                    var file = files[i];
                    if (!_this.checkImgFormat(file)) {
                        return false;
                    } else {
                        // 上传图片操作
                        _this.uploadImg(target, file, function (data) {
                            _this.baseSetting.adImages.push(data);
                        });
                    }
                }
            }
        },
        /**
         * 上传活动图片
         * @param {Element} event 当前点击的DOM节点
         */
        uploadActImg: function (event) {
            var _this = this,
                // 当前file input对象
                target = event.currentTarget,
                file = target.files[0];
            // 如果文件不存在
            if (!file) {
                return false;
            }
            // 如果图片文件类型和大小不符合
            if (!_this.checkImgFormat(file)) {
                return false;
            }
            // 调用上传图片接口
            _this.uploadImg(target, file, function (data) {
                _this.advSetting.image = data;
            });
        },
        /**
         * 上传分享图片
         * @param {Element} event 当前点击的DOM节点
         */
        uploadShareImg: function (event) {
            var _this = this,
                target = event.currentTarget,
                file = target.files[0];
            // 如果文件不存在
            if (!file) {
                return false;
            }
            // 如果图片文件类型和大小不符合
            if (!_this.checkImgFormat(file)) {
                return false;
            }
            // 调用上传图片方法
            _this.uploadImg(target, file, function (data) {
                _this.advSetting.shareImage = data;
            });
        },
        /**
         * 上传图片
         * @param {Element} target  按钮的文字
         * @param {Object} file  上传的文件
         * @param {Function} callback 回调函数
         */
        uploadImg: function (target, file, callback) {
            var _this = this,
                next = target.next(),
                parentNode = next.parentNode,
                formData = new FormData();

            target.disabled = true; // 将当前的按钮禁止
            parentNode.addClass('upload-btn-disabled'); // 为上传按钮添加置灰class

            formData.append('file', file);

            // 上传图片接口
            _this.$http.post(_this.ajaxUrl.uploadImg, formData, {
                emulateJSON: true,
                progress: function(e) {
                    next.innerText = parseInt(e.loaded/e.total*100) + '%';
                }
            }).then(function (res) {
                var data = res.data;

                next.innerText = '选择图片';
                target.disabled = false;
                if (data.return_code === 'SUCCESS') {
                    parentNode.removeClass('upload-btn-disabled');

                    if ((typeof callback).toLowerCase() === 'function') {
                        callback(data.return_msg);
                    }
                } else {
                    parentNode.removeClass('upload-btn-disabled');
                    _this.alertTipsShow('上传图片失败，请稍后重试');
                }
            }, function (res) {
                next.innerText = '选择图片';
                target.disabled = false;
                parentNode.removeClass('upload-btn-disabled');
                _this.alertTipsShow('上传图片失败，请稍后重试');
            });
        },
        /**
         * 校验图片格式是否正确
         * @param {String} file 图片文件
         */
        checkImgFormat: function (file) {
            var _this = this,
                reg = /^(\s|\S)+(jpg|png|jpeg|bmp)+$/,
                imgType = file.type,
                imgSize = file.size;
            // 如果上传图片大小超过3M，提示错误
            if (imgSize > 1024 * 1024 * 3) {
                _this.$alert('只能上传3M以下图片', '上传图片', {
                    confirmButtonText: '确定'
                });
                return false;
            }


            // 如果格式不正确，弹窗提示
            if (!reg.test(imgType.toLowerCase())) {
                _this.$alert('仅支持图片格式：jpg、jpeg、png、bmp', '上传图片', {
                    confirmButtonText: '确定'
                });
                return false;
            }

            return true;
        },
        /**
         * 降价概率input事件
         */
        calcPriceReduction: function () {
            var _this = this,
                actSetting = _this.actSetting,
                // 降价几率
                priceReduction = actSetting.priceReduction,
                // 判断当前的数值是否为数字
                isNumber = _this.checkNumber(priceReduction);

            // 判断降价几率是否在0~100之间
            if (!isNumber || priceReduction < 0) {
                actSetting.priceReduction = 0;
            } else if (priceReduction > 100) {
                actSetting.priceReduction = 100;
            }

            // 如果当前的概率等于0，就将降价范围也置为0
            if (!priceReduction) {
                actSetting.priceReductionMax = 0;
                actSetting.priceReductionMin = 0;
            }
            // 计算涨价概率
            actSetting.priceIncrease = 100 - actSetting.priceReduction;
        },
        /**
         * 涨价概率input事件
         */
        calcPriceIncrease: function () {
            var _this = this,
                actSetting = _this.actSetting,
                // 涨价几率
                priceIncrease = actSetting.priceIncrease,
                // 判断当前的数值是否为数字
                isNumber = _this.checkNumber(priceIncrease);

            // 判断涨价几率是否在0~100之间
            if (!isNumber || priceIncrease < 0) {
                actSetting.priceIncrease = 0;
            } else if (priceIncrease > 100) {
                actSetting.priceIncrease = 100;
            }
            // 如果涨价概率===0；就将当前的涨价范围置为0
            if (!priceIncrease) {
                actSetting.priceIncreaseMax = 0;
                actSetting.priceIncreaseMin = 0;
            }
            // 计算降价范围
            actSetting.priceReduction = 100 - actSetting.priceIncrease;
        },
        /**
         * 是否显示页面离开提示
         * @returns {string}
         */
        isShowLeaveTips: function () {
            if (!this.isLeave) {
                return "确定要离开页面？"
            }
        },
        /**
         * 保存
         * @param {Object} baseForm 基础设置表单对象
         * @param {Object} actForm  活动设置表单
         * @param {Object} advForm  高级设置表单
         */
        save: function (baseForm, actForm, advForm) {
            var _this = this,
                // 基础设置表单
                checkBaseForm = false,
                // 活动设置表单
                checkActForm = false,
                // 高级设置表单
                checkAdvForm = false,
                // 基础数据
                baseData = _this.baseSetting,
                // 活动数据
                actData = _this.actSetting,
                // 高级数据
                advData = _this.advSetting,
                // url参数
                urlParam = location.search,
                target = document.getElementById('save');
            // 将当前的保存按钮置灰
            target.innerText = '保存中...';
            target.style.backgroundColor = '#ccc';
            target.disabled = true;
            // 保存不需要校验关键词
            _this.isCheckKeyword = false;

            // 基础设置校验
            _this.$refs[baseForm].validate(function (valid) {
                if (valid) {
                    var isUploadBanner = _this.baseSetting.adImages.length === 0,
                        isContent = _this.editor.getContentTxt() === '';

                    // 校验砍价轮播图是否上传
                    _this.errorTips({
                        status: isUploadBanner,
                        errorName: 'adImages',
                        errorMsg: '请至少上传一张轮播图片'
                    });
                    if (isUploadBanner) return false;

                    // 校验活动说明
                    _this.errorTips({
                        status: isContent,
                        errorName: 'content',
                        errorMsg: '请填写活动说明'
                    });
                    if (isContent) return false;
                    // 将当前的基础设置校验值设置为真
                    checkBaseForm = true;

                } else {
                    // 清除保存按钮的disabled样式
                    _this.enableSaveBtn();
                    return false;
                }
            });

            // 校验活动设置
            if (checkBaseForm) {
                _this.$refs[actForm].validate(function (valid) {
                    if (valid) {
                        var priceSettingInput = document.querySelectorAll('.setting-price input');

                        /*
                         * 判断是否为数字
                         * 遍历概率设置下的所有input
                         * 判断是否是数字或空字符
                         * */

                        for (var index = 0; index < priceSettingInput.length; index++) {

                            var input = priceSettingInput[index],//当前对象
                                value = parseFloat(input.value),  //当前对象的值
                                // 判断当前对象的值是否是数字以及是否大于0
                                isTrueNumber = _this.checkNumber(value) && value >= 0;

                            _this.errorTips({
                                status: !isTrueNumber,
                                errorName: 'settingPrice',
                                errorMsg: '请输入正确的砍价设置',
                                errorEle: input
                            });

                            if (!isTrueNumber) return false;

                            /*
                             * 判断填写的是否是砍价几率或涨价几率
                             * 是的话就判断涨价几率和砍价几率的和是否等于100
                             *
                             * 否则就判断降价范围或涨价范围是否小于商品原价和砍价目标之差
                             */
                            if (input.hasClass('priceReduction') || input.hasClass('priceIncrease')) {
                                var isChanceRange = value < 0 || value > 100;
                                _this.errorTips({
                                    status: isChanceRange,
                                    errorName: 'settingPrice',
                                    errorMsg: '请输入正确的砍价概率',
                                    errorEle: input
                                });
                                if (isChanceRange) return false;
                            }
                        }

                        var //涨价几率和降价几率的和是否等于100
                            isChangeNumber = actData.priceIncrease + actData.priceReduction === 100,
                            // 最大降价值或涨价值
                            maxRange = _this.actSetting.price - _this.actSetting.lowestPrice,
                            // 最大降价范围是否超出最大可砍范围
                            isReduceMax = maxRange >= actData.priceReductionMax,
                            // 判断降价范围是否正确
                            isReduceRange = actData.priceReductionMax - actData.priceReductionMin >= 0,
                            // 最大涨价范围是否超出最大可砍范围
                            isRiseMax = maxRange >= actData.priceIncreaseMax,
                            // 判断涨价范围是否正确
                            isRiseRange = actData.priceIncreaseMax - actData.priceIncreaseMin >= 0,
                            // 联系信息是否勾选
                            isSelContact = actData.contact.length > 0;

                        // 校验降价几率和涨价几率的和是否等于100
                        _this.errorTips({
                            status: !isChangeNumber,
                            errorName: 'settingPrice',
                            errorMsg: '涨价几率和降价几率的和必须等于100'
                        });
                        if (!isChangeNumber) return false;

                        // 校验降价范围是否正确
                        _this.errorTips({
                            status: !isReduceRange,
                            errorName: 'settingPrice',
                            errorMsg: '请输入正确的降价范围'
                        });
                        if (!isReduceRange) return false;

                        // 校验降价范围是否超出最大可砍范围
                        _this.errorTips({
                            status: !isReduceMax,
                            errorName: 'settingPrice',
                            errorMsg: '降价范围不能超出最大可砍范围',
                            errorEle: 'priceReductionMax'
                        });
                        if (!isReduceMax) return false;
                        // 校验涨价范围是否正确
                        _this.errorTips({
                            status: !isRiseRange,
                            errorName: 'settingPrice',
                            errorMsg: '请输入正确的涨价范围'
                        });
                        if (!isRiseRange) return false;

                        // 校验涨价范围是否超出最大可砍范围
                        _this.errorTips({
                            status: !isRiseMax,
                            errorName: 'settingPrice',
                            errorMsg: '涨价范围不能超出最大可砍范围',
                            errorEle: 'priceIncreaseMax'
                        });
                        if (!isRiseMax)  return false;

                        // 校验联系信息是否勾选
                        _this.errorTips({
                            status: !isSelContact,
                            errorName: 'contact',
                            errorMsg: '请至少选择一项联系信息'
                        });
                        if (!isSelContact) return false;

                        checkActForm = true;

                    } else {
                        _this.enableSaveBtn();
                        return false;
                    }
                });

            }

            // 校验高级设置
            if (checkActForm) {
                _this.$refs[advForm].validate(function (valid) {
                    if (valid) {

                        checkAdvForm = true;
                        if (checkBaseForm && checkActForm) {
                            var postData = {
                                Event: {
                                    acquisitionTiming: actData.acquisitionTiming,
                                    adImages: [],
                                    adLink: baseData.adLink,
                                    advancedSetting: {
                                        description: advData.description,
                                        image: advData.image,
                                        keyword: advData.keyword,
                                        shareContent: advData.shareContent,
                                        shareImage: advData.shareImage,
                                        shareTitle: advData.shareTitle,
                                        title: advData.title,
                                        shareType: advData.isSettingShare
                                    },
                                    contact: _this.contactSelect,
                                    lowestPrice: actData.lowestPrice,
                                    name: baseData.name,
                                    organizer: baseData.organizer,
                                    resources: {
                                        id: _this.mallGoodsData.goodsSelected,
                                        name: baseData.name,
                                        price: actData.price,
                                        type: baseData.isMallGood,
                                        mallId: _this.mallId
                                    },
                                    startTime: parseInt(baseData.startDate.getTime() / 1000),
                                    endTime: parseInt(baseData.endDate.getTime() / 1000)
                                },
                                BargainProbability: {
                                    priceIncrease: actData.priceIncrease / 100,
                                    priceIncreaseRange: [actData.priceIncreaseMin, actData.priceIncreaseMax],
                                    priceReduction: actData.priceReduction / 100,
                                    priceReductionRange: [actData.priceReductionMin, actData.priceReductionMax]
                                },
                                content: _this.editor.getContent()

                            };
                            // 判断是否是默认设置
                            if (_this.from !== 'edit') {
                                postData.Event.resources.number = actData.number;

                                if(_this.isShowModelSelect) {
                                    postData.Event.isShowAd = _this.saveTrafficModel ? 1 : 0;
                                }

                                postData.Event.pattern = _this.isShowModelSelect ? 'chargePattern' : 'freePattern';
                            }

                            if (_this.advSetting.isSettingShare === '0') {
                                postData.Event.advancedSetting.shareImage = _this.defaultShare.shareImage;
                                postData.Event.advancedSetting.shareTitle = _this.defaultShare.shareTitle;
                                postData.Event.advancedSetting.shareContent = _this.defaultShare.shareContent;
                            }
                            /*
                             * 根据轮播图的data-sort排序
                             * 遍历砍价轮播图DOM对象，
                             * 根据当前LI的data-sort值，来获取当前图片的URL
                             * */
                            _this.bargain.forEachData(document.querySelectorAll('.banner-list li'), function (value, index) {
                                var sortIndex = value.getAttribute('data-sort');
                                postData.Event.adImages.push(baseData.adImages[sortIndex]);
                            });

                            _this.$http.post(_this.ajaxUrl.saveBargain + urlParam, postData, {
                                emulateJSON: true,
                                timeout: 10 * 1000
                            }).then(function (res) {
                                var data = res.data,
                                    status = data.return_code,
                                    msg = data.return_msg;

                                if (status === 'SUCCESS') {
                                    // 取消离开事件绑定
                                    _this.isLeave = true;
                                    location.href = _this.from == 'edit' ? '/supplier/index' 
                                        : '/supplier/index' + '#newFlag'
                                } else {
                                    _this.enableSaveBtn();
                                    _this.alertTipsShow(msg);
                                }
                            }, function (res) {
                                _this.enableSaveBtn();
                                _this.alertTipsShow(res.status + ': 网络错误，请刷新后重试');
                            })
                        }
                    } else {
                        _this.activeName = 'third';
                        _this.enableSaveBtn();
                        return false;
                    }
                })
            }

            // 判断错误跳转tab
            if (!checkBaseForm) {
                _this.activeName = 'first'
            } else if (!checkActForm) {
                _this.activeName = 'second'
            }
        },
        /**
         * 取消保存
         */
        cancelSave: function () {
            location.href = '/supplier/index';
        },
        /**
         * 取消保存按钮的disabled样式
         */
        enableSaveBtn: function () {
            // 保存按钮
            var target = document.getElementById('save');

            target.innerText = "保存";
            target.style.backgroundColor = '#ff981a';
            target.removeAttribute('disabled');
            this.isCheckKeyword = true;
        },
        /**
         * 检查微商城商品数量是否超出库存
         * @param {Object} rule 当前校验规则
         * @param {String|Number} value 当前校验对象值
         * @param {Function} callback  回调函数
         * @returns {*}  返回错误信息
         */
        checkMallNumber: function (rule, value, callback) {
            var _this = this;

            if (value === "" || value === null || value === undefined) {
                return callback(new Error('商品数量不能为空'));
            }

            if (!_this.checkNumber(value)) {
                return callback(new Error('商品数量必须为数字'));
            }


            if (!_this.isStartEdit) {
                if (_this.stock < value && _this.stock > 0 && _this.baseSetting.isMallGood == 0) {
                    return callback(new Error('商品数量不能大于库存'));
                }
            }
            callback();
        },
        /**
         * 检查砍价目标是否超出商品价格
         * @param {Object} rule 当前校验规则
         * @param {String|Number} value 当前校验对象值
         * @param {Function} callback  回调函数
         * @returns {*}  返回错误信息
         */
        checkMallPrice: function (rule, value, callback) {
            var _this = this;

            if (value === "" || value === null || value === undefined) {
                return callback(new Error('砍价目标不能为空'));
            }

            if (!_this.checkNumber(value)) {
                return callback(new Error('砍价目标必须为数字'));
            }

            if (!_this.isStartEdit) {
                if (value > _this.actSetting.price) {
                    return callback(new Error('砍价目标不能大于商品原价'));
                }
            }

            callback();
        },
        /**
         * 检查结束时间不能小于开始时间
         * @param {Object} rule 当前校验规则
         * @param {String|Number} value 当前校验对象值
         * @param {Function} callback  回调函数
         * @returns {*}  返回错误信息
         */
        checkEndDate: function (rule, value, callback) {
            var _this = this,
                userActivityModel = _this.userActivityModel,
                endDateTimeStamp = new Date(value).getTime() - 60 * 60 * 1000 * 24, // 结束时间时间戳
                tryoutModelEndDate = new Date(userActivityModel.tryoutModelEndDate).getTime(), //  当前用户免费模式截止时间
                freeModelEndDate = new Date(userActivityModel.freeModelEndDate).getTime(), //  当前用户免费模式截止时间
                payModelEndDate = new Date(userActivityModel.payModelEndDate).getTime(); // 当前用户活动付费模式截止时间

            if (_this.checkDate(_this.baseSetting.startDate, value)) {
                return callback(new Error('结束时间不能小于开始时间'));
            }

            // 判断活动结束时间是不是在用户购买活动时间范围内，如果超出，返回错误信息
            if(freeModelEndDate && payModelEndDate) {
                // 如果免费时间在后面
                if(freeModelEndDate > payModelEndDate) {
                    if(endDateTimeStamp > freeModelEndDate) {
                        return callback(new Error('活动截止时间不能超过免费模式截止期'));
                    }
                }else {
                    if(endDateTimeStamp > payModelEndDate) {
                        return callback(new Error('活动截止时间不能超过付费模式截止期'));
                    }
                }
            }else if(freeModelEndDate) {
                if(endDateTimeStamp > freeModelEndDate) {
                    return callback(new Error('活动截止时间不能超过免费模式截止期'));
                }
            }else if(payModelEndDate) {
                if(endDateTimeStamp > payModelEndDate) {
                    return callback(new Error('活动截止时间不能超过付费模式截止期'));
                }
            }else if(tryoutModelEndDate) {
                if(endDateTimeStamp > tryoutModelEndDate) {
                    return callback(new Error('活动截止时间不能超过试用模式截止期'));
                }
            }

            callback();
        },
        /**
         * 检查跳转链接
         * @param {Object} rule 当前校验规则
         * @param {String|Number} value 当前校验对象值
         * @param {Function} callback  回调函数
         * @returns {*}  返回错误信息
         */
        checkAdLink: function (rule, value, callback) {
            var isLink = this.checkLink(value);
            if (value !== "") {
                if (!isLink) {
                    callback(new Error('请输入正确的跳转链接'));
                } else {
                    callback();
                }
            } else {
                callback();
            }

        },
        /**
         * 检查关键词是否正确
         * @param {Object} rule 当前校验规则
         * @param {String|Number} value 当前校验对象值
         * @param {Function} callback  回调函数
         * @returns {*}  返回错误信息
         */
        checkAdvKeyword: function (rule, value, callback) {
            var _this = this,
                keyword = _this.advSetting.keyword,
                // 判断是否来自编辑
                isFromEdit = _this.from === 'edit',
                // 判断关键词是否改变
                isKeywordChange = _this.oldKeyword === _this.advSetting.keyword;

            /*
             * (如果关键词不为空&&不来自编辑||来自编辑但是关键词改变)&&不是来自保存
             * 调用接口判断关键词是否存在
             * 如果来自编辑&&关键词未改变
             * 直接通过检验
             * 否则清除错误信息
             * */

            if (((keyword && !isFromEdit) || (isFromEdit && !isKeywordChange)) && _this.isCheckKeyword) {
                _this.$http.get(_this.ajaxUrl.checkWord, {
                    params: {
                        keyword: keyword
                    },
                    timeout: 10 * 1000
                }).then(function (res) {
                    var resData = res.data;
                    resData.return_code === 'SUCCESS' ? callback() : callback(new Error(resData.return_msg));
                }, function (res) {
                    callback(new Error(res.status + ': 网络错误，请刷新后重试！'))
                })
            } else {
                callback();
            }
        },
        /**
         * 校验开始时间是否大于结束时间
         * @param {Number} start  开始时间戳
         * @param {Number} end  结束时间戳
         * @returns {boolean}  返回结果（true 开始时间大于结束时间  false...）
         */
        checkDate: function (start, end) {
            var startDate = new Date(start).getTime(),
                endDate = new Date(end).getTime();

            return endDate - startDate < 0;
        },
        /**
         * 校验链接
         * @param {String} linkStr 需要校验的url
         * @returns {boolean}
         */
        checkLink: function (linkStr) {
            var urlReg = new RegExp('(http[s]{0,1}|ftp)://[a-zA-Z0-9\\.\\-]+\\.([a-zA-Z]{2,4})(:\\d+)?(/[a-zA-Z0-9\\.\\-~!@#$%^&*+?:_/=<>]*)?', 'gi');
            if (urlReg.test(linkStr)) {
                return true;
            }

            return false;
        },
        /**
         * 校验是否为数字
         * @param {Number} number 需要校验的对象
         * @returns {boolean}
         */
        checkNumber: function (number) {
            return !(parseFloat(number).toString() == 'NaN');
        },
        /**
         * 错误提示
         * @param {Object} errorSetting
         *
         * {
         *  status 当前状态
         *  errorEle 错误位置的ref name
         *  errorName  错误提示名称
         *  errorMsg  错误提示文字
         * }
         *
         */
        errorTips: function (errorSetting) {
            var _this = this,
                // 错误状态
                status = errorSetting.status,
                // 错误提示变量名称
                errorName = errorSetting.errorName,
                // 错误提示文字
                errorMsg = errorSetting.errorMsg,
                // 错误input框
                errorEle = errorSetting.errorEle,
                // 判断errorEle是否传入
                element;

            if (errorEle) {
                element = _this.$refs[errorEle] ? _this.$refs[errorEle] : errorEle;
            }

            // 如果错误位置存在就添加错误位置input框提示
            if (element) {
                // 如果ref绑定的是子组件，择errorEle就是当前组件
                errorEle = element.$el ? element.$el : element;

                if (status) {
                    _this.errorMessage[errorName] = errorMsg;
                    errorEle.addClass('error-border');
                    _this.enableSaveBtn();
                } else {
                    _this.errorMessage[errorName] = "";
                    errorEle.removeClass('error-border');
                }
            } else {
                if (status) {
                    _this.errorMessage[errorName] = errorMsg;
                    _this.enableSaveBtn();
                } else {
                    _this.errorMessage[errorName] = ""
                }
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
            if (isAutoHide) {
                var time = setTimeout(function () {
                    _this.alertIsShow = false;
                    clearTimeout(time);
                }, 2000);
            }
        },

        /**
         * 将日期转化为中文的日期格式
         * @param {Date} date 需要转化的日期
         * @return {String} 转化好的日期字符串
         */
        formatDateToChinese: function(date) {
            var year = date.getFullYear(),
                month = date.getMonth() + 1,
                day = date.getDate();

            month = month < 9 ? '0' + month : month;
            day = day < 9 ? '0' + day : day;

            return year + '年' + month + '月' + day + '日';
        }
    },
    mounted: function () {
        this.init();
        // 配置左侧手机滚动条
        $('.phone-content').niceScroll({
            cursorborder: '1px solid transparent'
        });
    }
});