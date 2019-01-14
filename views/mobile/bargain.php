<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" type="text/css" href="http://static-10006892.file.myqcloud.com/bargain/mobile/css/bargain-25c6790443.css">
</head>
<body class="loading">
    <p class="loading-text">微砍价</p>
    <div id="wrap">
        <header>
            <div class="left"></div>
            <div class="wx-msg">
                <img class="avator" :src="baseInfo.wxInfo.headImg">
                <p class="nickname" :class="{'not-self': baseInfo.requestType=='helper'}" v-cloak>{{ baseInfo.wxInfo.nickName }}</p>
            </div>
            <button type="button" class="act-explain-btn" @click="showExplain = true"></button>
        </header>

        <div class="main">
            <!-- 轮播图 S -->
            <div class="swipe">
                <a class="swipe-wrap" :href="adLink">
                    <swiper class="goods-swiper" :options="swiperOption">
                        <swiper-slide v-for="img in baseInfo.goodsInfo.adImages" :key="img.id">
                            <img class="slide-img" :src="img + '?imageMogr2/crop/750x400'">
                        </swiper-slide>
                        <div class="swiper-pagination" slot="pagination"></div>
                    </swiper>
                </a>
                <div class="time-wrap">
                    <span class="time" v-if="baseInfo.eventInfo.status=='进行中'" v-cloak>仅剩<i class="icon-clock"></i>{{ formatRemainingTime }}</span>
                    <span class="time" v-if="baseInfo.eventInfo.status!='进行中'" v-cloak>{{ statusText }}</span>
                </div>
            </div>
            <!-- 轮播图 E -->

            <!-- 价格与进度条 S -->
            <div class="price-progress">
                <p class="price-item original-price" v-cloak>{{ baseInfo.goodsInfo.price }}</p>
                <div class="progress">
                    <div class="out-bar">
                        <div class="cur-progress" :style="{width: formatProgress}"></div>
                        <div class="cur-price"
                             ref="curPrice"
                             :style="{left: curPriceLeft}"
                             v-if="showCurPrice"
                             v-cloak>
                             <span class="price-num">{{ bargainInfo.eventInfo.disparityPrice }}</span>
                        </div>
                        <div class="cur-price is-lowest" v-if="bargainInfo.eventInfo.isLowestPrice" v-cloak></span>
                        </div>
                    </div>
                </div>
                <p class="price-item lowest-price" v-cloak>{{ baseInfo.goodsInfo.lowestPrice }}</p>
            </div>
            <!-- 价格与进度条 E -->

            <p class="goods-name" v-cloak>{{ baseInfo.goodsInfo.name }}<span class="stock" v-cloak>(剩余{{ baseInfo.goodsInfo.number }}件)</span></p>

            <!-- 按钮区域 S -->
            <div class="btn-wrap" ref="btnWrap" :class="{'is-lowest': bargainInfo.eventInfo.isLowestPrice}">
                <!-- 我要参加 -->
                <!-- 商品页面才有 -->
                <button type="button"
                        class="btn btn-join"
                        v-if="baseInfo.requestType=='index'"
                        @click="joinHandler"
                        :disabled="baseInfo.eventInfo.status != '进行中'">
                </button>

                <!-- 自己砍一刀 -->
                <!-- 砍价页面且没砍过且最低价 -->
                <button type="button"
                        class="btn btn-bargain-self"
                        v-if="showBargainSelfBtn"
                        @click="bargainHandler"
                        :disabled="baseInfo.eventInfo.status != '进行中'">
                </button>

                <!-- 立即购买 -->
                <!-- 商城商品、砍价页面且已经砍过 -->
                <button type="button"
                        class="btn btn-buy"
                        v-if="showBuyBtn"
                        @click="buyHandler"
                        :disabled="baseInfo.eventInfo.status != '进行中'">
                </button>

                <!-- 兑换商品 -->
                <!-- 非商城商品、砍价页面、最低价且未兑奖 -->
                <button type="button"
                        class="btn btn-get"
                        v-if="showGetBtn"
                        @click="getHandler">
                </button>

                <!-- 查看我的小宝贝 -->
                <!-- 商城商品、砍价页面、已购买 -->
                <button type="button"
                        class="btn btn-check"
                        v-if="showCheckGoods"
                        @click="checkHandler">
                </button>

                <!-- 已领奖 -->
                <!-- 非商城商品、砍价页面、已领奖 -->
                <button type="button"
                        class="btn btn-got"
                        v-if="showGotBtn"
                        @click="getHandler"
                        disabled>
                </button>

                <!-- 帮砍一刀 -->
                <button type="button"
                        class="btn btn-sm btn-bargain"
                        v-if="showBargainBtn"
                        @click="bargainHandler"
                        :disabled="baseInfo.eventInfo.status != '进行中'">
                </button>

                <!-- 我也要抢 -->
                <button type="button"
                        class="btn btn-sm btn-join2"
                        v-if="baseInfo.requestType=='helper'"
                        @click="join2Handler"
                        :disabled="baseInfo.eventInfo.status != '进行中'">
                </button>
            </div>
            <!-- 按钮区域 E -->

            <!-- 参与人数与活动店铺 S -->
            <div class="participation-num-store">
                <!-- 参与人数 -->
                <p class="participation-num" v-if="showParticipants" v-cloak>(已有{{ baseInfo.eventInfo.participants }}人参与)</p>

                <!-- 分享提示 -->
                <p class="share-tips" v-if="showShareTips" :class="{'notice': !showGetBtn}">剩余的交给小伙伴砍吧~</p>

                <!-- 店铺名称 -->
                <p class="act-store" v-cloak>{{ baseInfo.eventInfo.organizer }} 提供</p>
            </div>
            <!-- 参与人数与活动店铺 E -->

            <!-- 贡献列表 S -->
            <div class="helper-wrap" v-if="baseInfo.requestType!='index'">
                <div class="helper-container">
                    <swiper class="helper-list" :options="scrollOption">
                        <swiper-slide>
                            <ul class="helper-ul"
                                :class="scrollClass"
                                v-if="helperList.length"
                            >
                                <li class="helper-item" v-for="item in helperList">
                                    <div class="helper-msg">
                                        <img class="helper-avator" :src="item.headImg">
                                        <div class="helper-text">
                                            <p class="helper-nickname" v-cloak>{{ item.nickName }}</p>
                                            <p class="helper-time" v-cloak>{{ item.bargainTime }}</p>
                                        </div>
                                    </div>
                                    <div class="bargain-price" :class="{'add': item.disparity>0}" v-cloak>{{ item.disparity | abs }}</div>
                                </li>
                            </ul>
                            <p class="helper-empty" v-if="!helperList.length"></p>
                        </swiper-slide>
                        <div class="swiper-scrollbar" slot="scrollbar"></div>
                    </swiper>
                </div>
            </div>
            <!-- 贡献列表 E -->
        </div>
        <div id="empty"></div>
        <div id="idouzi-ad"></div>

        <!-- 砍价动图 -->
        <img class="bargaining" v-show="showBargaining" :src="bargainingImg">

        <!-- 提示框 -->
        <div class="tips" v-show="tips.show" @touchmove.prevent>
            <span class="tips-text" v-cloak>{{ tips.text }}</span>
        </div>

        <!-- 活动说明弹窗 -->
        <modal v-if="showExplain" class="explain-container" @close="showExplain = false">
            <span slot="modal-title" class="modal-title"></span>
            <div slot="modal-container" class="modal-container">
                <swiper class="helper-list" :options="explainOption">
                    <swiper-slide>
                        <div class="content" v-html="baseInfo.eventInfo.content"></div>
                        <!--底部微信号-->
                        <div class="footer" v-if="baseInfo.eventInfo.qrcodeUrl">
                            <p class="more-activities">/ 更多活动参与及奖品兑换 /</p>
                            <div class="code">
                                <img :src="baseInfo.eventInfo.qrcodeUrl" class="qrcode-url">    
                            </div>
                        </div>
                    </swiper-slide>
                    <div class="swiper-scrollbar" slot="scrollbar"></div>
                </swiper>
            </div>
            <button slot="modal-btn"
                    type="button"
                    class="modal-btn"
                    @click="showExplain = false">
            </button>
            
        </modal>

        <!-- 填写信息弹窗 -->
        <modal v-if="showForm" class="form-container" @close="hideForm">
            <span slot="modal-title" class="modal-title"></span>
            <div slot="modal-container" class="modal-container">
                <template v-for="item in baseInfo.eventInfo.contactInfo">
                    <label class="label-item" v-if="item.isChosen">
                        <span class="input-name" v-cloak>{{ item.label }}</span>
                        <input class="input-item"
                               type="text"
                               :data-name="item.name"
                               :maxlength="item.length"
                               v-input-type="item.type">
                    </label>
                </template>
            </div>
            <button slot="modal-btn"
                    type="button"
                    class="modal-btn"
                    ref="postBtn"
                    @click="postHandler">
            </button>
        </modal>

        <!-- 砍价(减)弹窗 -->
        <modal v-if="showBargainSub"
               class="emoji-modal bargain-sub-container"
               @close="updateBargainInfo">
            <span slot="modal-title" class="modal-title"></span>
            <div slot="modal-container" class="modal-container">哎呦~手气不错哦!<br>成功砍掉<span class="price-num" v-cloak>{{ bargainCache.bargainPrice | abs }}</span>元耶~</div>
            <button slot="modal-btn"
                    type="button"
                    class="modal-btn"
                    @click="updateBargainInfo">
            </button>
        </modal>

        <!-- 砍价(加)弹窗 -->
        <modal v-if="showBargainAdd"
               class="emoji-modal bargain-add-container"
               @close="updateBargainInfo">
            <span slot="modal-title" class="modal-title"></span>
            <div slot="modal-container" class="modal-container">客官你竟然....<br>砍涨了<span class="price-num" v-cloak>{{ bargainCache.bargainPrice | abs }}</span>元!</div>
            <button slot="modal-btn"
                    type="button"
                    class="modal-btn"
                    @click="updateBargainInfo">
            </button>
        </modal>

        <!-- 兑奖码弹窗 -->
        <modal v-if="showRedeemCode" class="redeem-code-container" @close="showRedeemCode = false">
            <span slot="modal-title" class="modal-title"></span>
            <div slot="modal-container" class="modal-container" v-cloak>{{ bargainInfo.goodsInfo.relateInfo }}</div>
            <button slot="modal-btn"
                    type="button"
                    class="modal-btn"
                    @click="showRedeemCode = false">
            </button>
        </modal>

        <!-- 活动已结束弹窗 -->
        <modal v-if="showOver" class="emoji-modal event-over-container" @close="showOver = false">
            <span slot="modal-title" class="modal-title"></span>
            <div slot="modal-container" class="modal-container">
                <div class="code-container" v-if="baseInfo.eventInfo.qrcodeUrl">
                    <img class="code-img" :src="baseInfo.eventInfo.qrcodeUrl">
                </div>
                <span class="modal-footer-txt" v-if="baseInfo.eventInfo.qrcodeUrl">长按关注店铺最新活动</span>
            </div>
            <button slot="modal-btn"
                    type="button"
                    class="modal-btn"
                    v-if="showCheckGooodsDialog"
                    @click="checkHandler">
            </button>
        </modal>

        <!-- 商品被抢光弹窗 -->
        <modal v-if="showSoldOut" class="emoji-modal sold-out-container" @close="hideSoldOut">
            <span slot="modal-title" class="modal-title"></span>
            <div slot="modal-container" class="modal-container">来晚了~<br>商品抢光了~~</div>
            <button slot="modal-btn"
                    type="button"
                    class="modal-btn"
                    @click="checkHandler">
            </button>
        </modal>
    </div>

    <!-- 模态弹窗 -->
    <script type="text/x-template" id="modal-template">
        <transition name="modal">
            <div class="modal-mask" @click.self="$emit('close')" @touchmove.prevent>
                <div class="modal-wrapper">
                    <slot name="modal-title"></slot>
                    <slot name="modal-container"></slot>
                    <slot name="modal-btn"></slot>
                </div>
            </div>
        </transition>
    </script>

    <script type="text/javascript" src="http://static-10006892.file.myqcloud.com/plugin/vue/vue-2.2.0.min.js"></script>
    <script type="text/javascript" src="http://static-10006892.file.myqcloud.com/plugin/vue-resource/vue-resource-1.2.1.min.js"></script>
    <script type="text/javascript" src="http://static-10006892.file.myqcloud.com/public/js/idouzi-tools.min.js"></script>
    <script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
    <script type="text/javascript" src="http://static-10006892.file.myqcloud.com/bargain/mobile/js/bargain-d7a7c88f4e.js"></script>
</body>
</html>