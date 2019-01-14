<link rel="stylesheet" href="/supplier/js/umeditor/themes/default/css/umeditor.min.css">
<link rel="stylesheet" href="http://static-10006892.file.myqcloud.com/bargain/supplier/css/create-d514fa615d.css">
<!--标题-->
<div class="title">
    <ul class="clear-fix title-nav">
        <li class="fl"><a href="/supplier/index">活动列表</a></li>
        <li class="fl">&nbsp;&nbsp;&GT;&nbsp;&nbsp;</li>
        <li class="fl now" v-if="from==='create'||from==='copy'" v-cloak>新建砍价</li>
        <li class="fl now" v-if="from==='edit'" v-cloak>编辑砍价</li>
    </ul>
</div>
<!--内容-->
<div class="content radius-5" v-loading="!isInit">
    <div class="clear-fix setting">
        <!--左侧手机展示-->
        <div class="phone-wrap fl">
            <!--活动设置手机展示-->
            <div class="phone-content" v-show="activeName==='first'||activeName==='second'">
                <!--立即参与按钮-->
                <div class="phone-inner" :class="phoneBg">
                    <!--返回按钮-->
                    <button type="button" class="phone-back" @click="phoneJumpBack"></button>
                    <!--活动说明按钮-->
                    <button type="button"
                            v-show="isShowPhoneBtn"
                            class="phone-actDescriptionButton"
                            @click="jumpActDescription"></button>
                    <!--跳转按钮-->
                    <button type="button" class="phone-indexButton" @click="jumpWriteInfo"></button>
                </div>
            </div>
            <!--高级设置手机展示-->
            <div class="phone-content" v-show="activeName==='third'">
                <div class="phone-adv phone-inner">
                    <!--关键词-->
                    <div class="phone-keyword">
                        <div class="phone-keyword-inner text-overflow" v-text="phoneKeyWord">

                        </div>
                    </div>
                    <!--回复内容-->
                    <div class="phone-reply">
                        <div class="phone-reply-title text-overflow" v-text="advSetting.title">

                        </div>
                        <div class="phone-replydate">
                            x月xx日
                        </div>
                        <div class="phone-reply-img">
                            <img :src="advSetting.image+'?imageMogr2/crop/188x105'" alt="">
                        </div>
                        <div class="phone-reply-content text-overflow" v-text="advSetting.description">

                        </div>
                        <div class="phone-reply-lookmore">
                            查看更多
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--右侧设置-->
        <div class="setting-content fl">
            <el-tabs v-model="activeName">
                <!--基础设置-->
                <el-tab-pane class="base-setting" label="基础设置" name="first">
                    <!--付费模式提示-->
                    <div class="model-tips"
                         v-if="userActivityModel.userModelInfo"
                         v-html="userActivityModel.userModelInfo">
                    </div>

                    <el-form ref="baseSetting"
                             label-position="left"
                             label-width="100px"
                             :model="baseSetting"
                             :rules="baseSettingRules">
                        <!--商品名称-->
                        <el-form-item label="商品名称" class="goods-name input-num-wrap" prop="name">
                            <el-input v-model="baseSetting.name"
                                      placeholder="请输入内容"
                                      class="radius-3 width-285 goods-nameInput"
                                      maxlength="30"
                                      :disabled="isStartEdit"
                                      :class="{'mall-good':baseSetting.isMallGood==0}"
                                      :readonly="baseSetting.isMallGood==0"></el-input>
                            <!--统计商品名称字数-->
                            <span class="input-num" v-show="baseSetting.isMallGood==1">
                                <span v-text="baseSetting.name.length">0</span>
                                <span>/</span>
                                <span>30</span>
                            </span>
                            <!--清除当前选中的微商城商品-->
                            <span class="clear-name"
                                  v-show="baseSetting.isMallGood==0&&!isStartEdit" @click="clearMallName">
                            </span>
                            <el-button :plain="true"
                                       class="sel-goodBtn"
                                       v-if="isShowSelecteGoodsBtn"
                                       :disabled="isStartEdit"
                                       @click="selMallGoods">
                                选微商城商品
                            </el-button>


                            <!--选择微商城商品dialog-->
                            <el-dialog v-model="selGoods"
                                       :show-close="false"
                                       @scroll.prevent
                                       :close-on-click-modal="false">
                                <div class="dialog-title clear-fix">
                                    <div class="prompt fl">
                                        选择商城商品
                                        <span>(需要微信支付的商品，确保绑定一个认证服务号)</span>
                                    </div>
                                    <div class="dialog-close fr" @click="cancelAddMallGoods">×</div>
                                </div>
                                <div class="dialog-content">
                                    <!--筛选-->
                                    <div class="screen clear-fix">
                                        <el-select v-model="mallGoodsData.mallSearchCate"
                                                   class="category-selecte"
                                                   popper-class="category-selection"
                                                   @change="SearchGoodsByType"
                                                   placeholder="请选择">
                                            <el-option :label="'全部分类'" :value="''"></el-option>
                                            <el-option v-for="item in mallGoodsData.cate"
                                                       :class="{'is-second': !item.isTop}"
                                                       :label="item.name"
                                                       :value="item.id">
                                            </el-option>
                                        </el-select>
                                        <!--搜索框-->
                                        <div class="search hover radius-3 fr">
                                            <input type="text"
                                                   v-model="mallGoodsData.mallSearchName"
                                                   placeholder="输入商品名称">
                                            <span class="search-btn fr" @click="SearchGoodsByName"></span>
                                        </div>
                                    </div>
                                    <!--商品列表-->
                                    <div class="googs-content" v-loading="mallGoodsData.isLoadingShow">
                                        <ul class="goods-content-header clear-fix radius-3">
                                            <li class="googs-name">商品</li>
                                            <li class="goods-price">价格</li>
                                            <li class="goods-sales">销量</li>
                                            <li class="goods-stock">库存</li>
                                            <li class="goods-time">添加时间</li>
                                        </ul>
                                        <!--商城没有商品时-->
                                        <div class="no-goods" v-if="mallGoodsData.goods.length===0" v-cloak>
                                            <div class="no-goods-inner">
                                                <div class="no-goods-icon"></div>
                                                <div class="no-goods-tips">
                                                    暂无相关商品
                                                </div>
                                            </div>
                                        </div>
                                        <!--商城有商品时展示-->
                                        <div class="googs-content-list"
                                             v-if="mallGoodsData.goods.length!==0">
                                            <ul class="content-list-inner">
                                                <li class="goods-item radius-3"
                                                    v-for="item in mallGoodsData.goods">
                                                    <ul class="goods-item-content clear-fix">
                                                        <!--商品名称-->
                                                        <li class="googs-name">
                                                            <el-radio class="radio"
                                                                      v-model="mallGoodsData.goodsSelected"
                                                                      :label="item.goodsId">
                                                                <span class="goods-img">
                                                                    <img :src="item.goodsImage" alt="">
                                                                </span>
                                                                <span class="text-overflow goods-text"
                                                                      v-text="item.goodsName">
                                                                </span>
                                                            </el-radio>
                                                        </li>
                                                        <!--商品价格-->
                                                        <li class="goods-price"
                                                            v-text="'￥'+item.benefitPrice"></li>
                                                        <!--商品销量-->
                                                        <li class="goods-sales"
                                                            v-text="item.saleNum"></li>
                                                        <!--商品库存-->
                                                        <li class="goods-stock"
                                                            v-text="item.stock"></li>
                                                        <!--商品创建时间-->
                                                        <li class="goods-time"
                                                            v-text="item.createdTime"></li>
                                                    </ul>
                                                </li>

                                                <li class="page" v-if="mallGoodsData.pageTotal/10 > 1">
                                                    <el-pagination
                                                            layout="prev, pager, next"
                                                            :page-size="10"
                                                            :total="mallGoodsData.pageTotal"
                                                            @current-change="mallCurrentPage">
                                                    </el-pagination>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div slot="footer" class="dialog-footer"
                                         v-if="mallGoodsData.goods.length!==0">
                                                    <span class="mall-error-msg"
                                                          v-text="mallGoodsData.errorMsg"></span>
                                        <el-button class="dialog-cancel"
                                                   @click="addMallGoods">确定
                                        </el-button>
                                        <el-button class="dialog-sure"
                                                   @click="cancelAddMallGoods">取消
                                        </el-button>
                                    </div>
                                </div>
                            </el-dialog>
                            <!--结束-->
                        </el-form-item>

                        <!--活动单位-->
                        <el-form-item label="活动单位" class="input-num-wrap" prop="organizer">
                            <el-input v-model="baseSetting.organizer"
                                      placeholder="请输入内容活动单位"
                                      class="radius-3 width-285"
                                      maxlength="15"></el-input>
                            <span class="input-num">
                                <span v-text="baseSetting.organizer.length">0</span>
                                <span>/</span>
                                <span>15</span>
                            </span>
                        </el-form-item>

                        <!--活动时间-->
                        <el-form-item label="活动时间" class="act-date">
                            <!--开始时间-->
                            <el-form-item prop="startDate">
                                <el-date-picker type="datetime"
                                                placeholder="请选择"
                                                :editable="false"
                                                :clearable="false"
                                                v-model="baseSetting.startDate"
                                                :disabled="isStartEdit"
                                                format="yyyy-MM-dd HH:mm"></el-date-picker>
                            </el-form-item>

                            <span class="date-placeholder">&nbsp;&nbsp;至&nbsp;&nbsp;</span>

                            <el-form-item prop="endDate">
                                <el-date-picker type="datetime"
                                                placeholder="请选择"
                                                :editable="false"
                                                :clearable="false"
                                                format="yyyy-MM-dd HH:mm"
                                                v-model="baseSetting.endDate"></el-date-picker>
                            </el-form-item>

                        </el-form-item>

                        <!--砍价轮播图-->
                        <el-form-item label="砍价轮播图">
                            <div class="clear-fix banner-wrap">
                                <ul class="banner-list clear-fix fl" ref="adImages">
                                    <li v-for="(bannerItem,index) in  baseSetting.adImages" :data-sort="index"
                                        class="radius-3">
                                        <span class="close-banner" @click="removeBanner($event)"></span>
                                        <img :src="bannerItem + '?imageMogr2/crop/90x48'" alt="">
                                    </li>
                                </ul>

                                <div class="upload-banner fl">
                                    <div class="upload-btn clear-fix radius-3">
                                        <input type="file"
                                               accept="image/jpeg,image/jpg,image/png,image/bmp"
                                               multiple
                                               @change="uploadBanner($event)">
                                        <span>选择图片</span>
                                    </div>

                                    <div class="upload-tip">
                                        建议尺寸：750x400
                                    </div>
                                </div>
                            </div>

                            <div class="error-msg banner-error"
                                 v-show="errorMessage.adImages!==''"
                                 v-text="errorMessage.adImages" v-cloak>
                            </div>

                        </el-form-item>

                        <!--跳转链接-->
                        <el-form-item label="跳转链接" prop="adLink">
                            <el-input v-model="baseSetting.adLink"
                                      class="width-400"
                                      placeholder="选填，点击任何轮播图都跳转至此链接"></el-input>
                        </el-form-item>

                        <!--活动说明-->
                        <el-form-item label="活动说明" prop="content">
                            <script id="editor"
                                    name="content"
                                    type="text/plain">这里写你的初始化内容</script>

                            <div class="error-msg content-error"
                                 v-show="errorMessage.content!==''"
                                 v-text="errorMessage.content" v-cloak>
                            </div>
                        </el-form-item>

                    </el-form>
                </el-tab-pane>

                <!--活动设置-->
                <el-tab-pane class="act-setting" label="活动设置" name="second">
                    <el-form ref="actSetting"
                             label-position="left"
                             label-width="100px"
                             :model="actSetting"
                             :rules="actSettingRules">

                        <!--商品原价-->
                        <el-form-item label="商品原价" prop="price">
                            <el-input v-model.number="actSetting.price"
                                      class="width-100"
                                      @blur="getPriceTimes"
                                      :disabled="baseSetting.isMallGood===0||isStartEdit"
                                      maxlength="8" :disabled="baseSetting.isMallGood==0"></el-input>
                            <span class="tips">
                                <span>&nbsp;元&nbsp;</span>
                                活动发布后无法更新
                            </span>
                        </el-form-item>
                        <!--活动商品数量-->
                        <el-form-item label="活动商品数量" prop="number">
                            <el-input v-model.number="actSetting.number"
                                      class="width-100"
                                      :disabled="isStartEdit"
                                      maxlength="8"></el-input>
                            <div class="tips">
                                <span>&nbsp;件</span>
                                <span class="icon">
                                    <!--商城商品提示语-->
                                    <span class="question-content" v-show="baseSetting.isMallGood==0">
                                        您商城的库存包含做活动的库存，活动商品抢光了的话，只要商城库存充足，用户还能用原价进行购买
                                    </span>
                                    <!--非商城商品提示语-->
                                    <span class="question-content" v-show="baseSetting.isMallGood==1">
                                        即做这次活动您准备拿出多少库存来给消费者购买
                                    </span>
                                </span>
                                <span>活动发布后无法更新</span>
                            </div>
                        </el-form-item>

                        <!--砍价目标-->
                        <el-form-item label=" " class="floor-price" prop="lowestPrice">
                            <div class="label">
                                砍价目标
                                <div class="text-small">最低价</div>
                            </div>
                            <el-input v-model.number="actSetting.lowestPrice"
                                      :disabled="isStartEdit"
                                      @blur="getPriceTimes"
                                      class="width-100"></el-input>
                            <span class="tips">
                                        <span>&nbsp;元&nbsp;</span>
                                        活动发布后无法更新
                                    </span>
                        </el-form-item>

                        <!--砍价设置-->
                        <el-form-item label="砍价设置">
                            <div class="text-small">
                                (涨价概率及金额最好小于降价概率及金额，避免出现砍不到最低价的情况)
                            </div>
                            <!--降价设置-->
                            <div class="setting-price radius-3">
                                <div class="price-title">
                                    砍价时降价
                                    <span class="text-small">(好友帮砍价时，砍一刀价格会降低)</span>
                                </div>
                                <div class="price-content">
                                    <div class="chance">
                                        <span>降价几率</span>
                                        <input v-model.number="actSetting.priceReduction"
                                               ref="priceReduction"
                                               @blur="getPriceTimes"
                                               @input="calcPriceReduction"
                                               :disabled="actSetting.priceReduction===0"
                                               class="width-80 radius-3 priceReduction">
                                        <span>%</span>
                                    </div>
                                    <div class="price-range">
                                        <span>降价范围</span>
                                        <input v-model.number="actSetting.priceReductionMin"
                                               ref="priceReductionMin"
                                               :disabled="actSetting.priceReduction===0"
                                               @blur="getPriceTimes"
                                               class="width-80 radius-3">
                                        <span>-</span>
                                        <input v-model.number="actSetting.priceReductionMax"
                                               ref="priceReductionMax"
                                               :disabled="actSetting.priceReduction===0"
                                               @blur="getPriceTimes"
                                               class="width-80 radius-3">
                                    </div>
                                </div>
                            </div>

                            <!--涨价设置-->
                            <div class="setting-price radius-3">
                                <div class="price-title">
                                    砍价时涨价
                                    <span class="text-small">(好友帮砍价时，砍一刀价格会上涨)</span>
                                </div>
                                <div class="price-content">
                                    <div class="chance">
                                        <span>涨价几率</span>
                                        <input v-model.number="actSetting.priceIncrease"
                                               ref="priceIncrease"
                                               @blur="getPriceTimes"
                                               :disabled="actSetting.priceIncrease===0"
                                               @input="calcPriceIncrease"
                                               class="width-80 radius-3 priceIncrease">
                                        <span>%</span>
                                    </div>
                                    <div class="price-range">
                                        <span>涨价范围</span>
                                        <input v-model.number="actSetting.priceIncreaseMin"
                                               ref="priceIncreaseMin"
                                               :disabled="actSetting.priceIncrease===0"
                                               @blur="getPriceTimes"
                                               class="width-80 radius-3">
                                        <span>-</span>
                                        <input v-model.number="actSetting.priceIncreaseMax"
                                               ref="priceIncreaseMax"
                                               :disabled="actSetting.priceIncrease===0"
                                               @blur="getPriceTimes"
                                               class="width-80 radius-3">
                                    </div>
                                </div>
                            </div>

                            <!--砍价次数-->
                            <div class="text-small bargain-time" v-if="isTruePriceTimes">
                                <span>按照当前你填的概率计算，需要砍</span>
                                <span class="bargain-time-strong"
                                      v-text="actSetting.priceTimes.leastTimes"></span>
                                <span class="bargain-time-strong">-</span>
                                <span class="bargain-time-strong"
                                      v-text="actSetting.priceTimes.mostTimes"></span>
                                <span>刀才能砍到最低价</span>
                            </div>

                            <div class="error-msg"
                                 v-show="errorMessage.settingPrice!==''"
                                 v-text="errorMessage.settingPrice" v-cloak>
                            </div>


                        </el-form-item>

                        <!--联系信息-->
                        <el-form-item label="联系信息">

                            <el-radio-group class="info-writetime" v-model="actSetting.acquisitionTiming">
                                <el-radio class="radio" label="0" :disabled="isStartEdit">参与前填写</el-radio>
                                <el-radio class="radio"
                                          label="1"
                                          v-show="baseSetting.isMallGood!=0"
                                          :disabled="isStartEdit">领奖时填写
                                </el-radio>
                            </el-radio-group>


                            <el-checkbox-group v-model="contactSelect">
                                <el-checkbox v-for="contactItem in actSetting.contact"
                                             :label="contactItem.name"
                                             :disabled="isStartEdit||contactItem.name==='name'||contactItem.name==='phone'"
                                             v-show="!(baseSetting.isMallGood==0&&contactItem.name=='address')"
                                             :checked="contactItem.isChosen">{{contactItem.label}}
                                </el-checkbox>
                            </el-checkbox-group>

                            <div class="error-msg"
                                 v-show="errorMessage.contact!==''"
                                 v-text="errorMessage.contact" v-cloak>
                            </div>
                        </el-form-item>


                    </el-form>
                </el-tab-pane>

                <!--高级设置-->
                <el-tab-pane label="高级设置" name="third" class="advSetting">
                    <el-form ref="advSetting"
                             label-position="left"
                             :model="advSetting"
                             label-width="100px"
                             :rules="advSettingRules">
                        <!--活动标题-->
                        <el-form-item label="活动标题" class="input-num-wrap" prop="title">

                            <el-input v-model="advSetting.title"
                                      ref="organizer"
                                      class="radius-3 width-300"
                                      maxlength="20"></el-input>
                            <span class="input-num">
                                        <span v-text="advSetting.title.length">0</span>
                                        <span>/</span>
                                        <span>20</span>
                                    </span>
                        </el-form-item>

                        <!--关键词-->
                        <el-form-item label="关键词" class="input-num-wrap" prop="keyword">
                            <el-input v-model="advSetting.keyword"
                                      ref="keyword"
                                      class="radius-3 width-300"
                                      :disabled="from==='edit'"
                                      maxlength="20"></el-input>
                            <span class="input-num">
                                        <span v-text="advSetting.keyword.length">0</span>
                                        <span>/</span>
                                        <span>20</span>
                                    </span>
                        </el-form-item>
                        <!--活动图片-->
                        <el-form-item label="活动图片" class="clear-fix">

                            <div class="act-images radius-3 fl">
                                <img :src="advSetting.image+'?imageMogr2/crop/144x80'" alt="">
                            </div>


                            <div class="upload-banner fl">

                                <div class="upload-btn clear-fix radius-3">
                                    <input type="file"
                                           @change="uploadActImg($event)"
                                           accept="image/jpeg,image/jpg,image/png,image/bmp">
                                    <span>选择图片</span>
                                </div>

                                <div class="upload-tip">
                                    图片建议尺寸：900*500 <br>
                                    图片支持格式：jpg、jpeg、png
                                </div>

                                <div class="error-msg"
                                     v-show="errorMessage.actImg!==''"
                                     v-text="errorMessage.actImg" v-cloak>
                                </div>
                            </div>
                        </el-form-item>
                        <!--活动介绍-->
                        <el-form-item label="活动介绍" class="description" prop="description">
                            <el-input type="textarea"
                                      v-model="advSetting.description"
                                      class="width-300 activity-des"
                                      maxlength="50"></el-input>

                            <span class="input-num">
                                        <span v-text="advSetting.description.length">0</span>
                                        <span>/</span>
                                        <span>50</span>
                                    </span>

                        </el-form-item>
                        <!--分享设置-->
                        <el-form-item label="分享设置">
                            <el-radio-group v-model="advSetting.isSettingShare">
                                <el-radio class="radio" label="0">默认设置</el-radio>
                                <el-radio class="radio" label="1">自定义设置</el-radio>
                            </el-radio-group>
                            <!--设置分享-->
                            <div class="setting-share" v-if="advSetting.isSettingShare==1">

                                <!--分享图-->
                                <el-form-item class="clear-fix">
                                    <div class="error-msg"
                                         v-show="errorMessage.shareImg!==''"
                                         v-text="errorMessage.shareImg" v-cloak>
                                    </div>
                                    <div class="share-label fl">
                                        分享图
                                    </div>

                                    <div class="share-img fl">
                                        <img :src="advSetting.shareImage+'?imageMogr2/crop/40x40'"  class="radius-3">
                                    </div>
                                    <div class="upload-banner fl">
                                        <div class="upload-btn clear-fix radius-3">
                                            <input type="file"
                                                   @change="uploadShareImg($event)"
                                                   accept="image/jpeg,image/jpg,image/png,image/bmp">
                                            <span>选择图片</span>
                                        </div>
                                    </div>

                                    <div class="fl share-img-tips">
                                        建议尺寸：200x200 <br/>
                                        支持格式：jpg、jpeg、png
                                    </div>
                                </el-form-item>


                                <!--分享标题-->
                                <el-form-item prop="shareTitle" class="share-title input-num-wrap">
                                    <div class="share-label fl">
                                        分享标题
                                    </div>
                                    <el-input v-model="advSetting.shareTitle"
                                              class="width-260"
                                              maxlength="20"></el-input>

                                    <span class="input-num">
                                                <span v-text="advSetting.shareTitle.length">0</span>
                                                <span>/</span>
                                                <span>20</span>
                                            </span>
                                </el-form-item>


                                <!--分享内容-->
                                <el-form-item prop="shareContent" class="share-content">
                                    <div class="share-label fl">
                                        分享内容
                                    </div>
                                    <el-input type="textarea"
                                              v-model="advSetting.shareContent"
                                              class="width-260"
                                              maxlength="30"></el-input>

                                    <span class="input-num">
                                                <span v-text="advSetting.shareContent.length">0</span>
                                                <span>/</span>
                                                <span>30</span>
                                            </span>

                                </el-form-item>
                            </div>
                        </el-form-item>

                        <!--模式选择-->
                        <!--isShowModelSelect改为false使其隐藏-->
                        <el-form-item label="模式选择" class="select-traffic-model" v-show="false">
                            <el-checkbox v-model="saveTrafficModel">存流量模式</el-checkbox>

                            <div class="tips">
                                在您的活动中展示广告，您从广告中获得可用流量收益。
                            </div>
                        </el-form-item>
                    </el-form>
                </el-tab-pane>
            </el-tabs>
        </div>
    </div>

    <!--底部保存按钮-->
    <div class="footer-btn">
        <el-button :plain="true"
                   class="save"
                   id="save"
                   @click="save('baseSetting','actSetting','advSetting')">保存
        </el-button>
        <el-button :plain="true" class="cancel" @click="cancelSave">取消</el-button>
    </div>

    <!--alert提示-->
    <alert-tips :message="alertMessage" v-if="alertIsShow"></alert-tips>
</div>

<!--百度编辑器配置文件-->
<script src="/supplier/js/umeditor/umeditor.config.js"></script>
<!--百度编辑器源码文件-->
<script src="/supplier/js/umeditor/umeditor.min.js"></script>
<!--拖拽插件-->
<script src="http://static-10006892.file.myqcloud.com/plugin/sortable/sortable-1.5.1.min.js"></script>
<!--idouzi公用方法-->
<script src="http://static-10006892.file.myqcloud.com/public/js/idouzi-tools.min.js"></script>
<!--本页JS-->
<script src="http://static-10006892.file.myqcloud.com/bargain/supplier/js/create-fd9117c1b3.js"></script>
