<link rel="stylesheet" href="http://static-10006892.file.myqcloud.com/bargain/supplier/css/index-ea33b0d587.css">
<!--活动列表-->
<div class="bargain radius-5" v-cloak>
    <!--标题-->
    <div class="new-nav-back">
        <a :href="idouziUrl + '/supplier/index/functionPanel'" class="nav-back"><<返回 </a>/ 微砍价
    </div>

    <!--没有数据时展示-->
    <div class="nodata radius-5" v-if="!hasData&&isInit" v-cloak>
        <div class="new-action-txt">新建活动</div>
        <div class="nodata-content">
            <div class="icon"></div>
            <p>你还没创建过任何活动诶，试试创建一个吧。</p>
            <a class="add-new radius-3" @click.prevent="toNew()">
                新建活动
            </a>
        </div>
    </div>

    <!-- 新建活动扫码 -->
    <div class="new-action-dialog" v-if="isNewDialog && actList[0]">
        <i class="close icon iconfont icon-guanbi1" @click="isNewDialog = false"></i>
        <div class="left">
            <p class="title">活动链接</p>
            <v-qrcode level="H"
                    cls="first-qrcode"
                    :size="100"
                    :padding="0"
                    :value="actList[0].cpActUrl">
            </v-qrcode>
            <div class="tip">微信扫一扫预览活动</div>
        </div>
        <div class="right">
            <span class="title">链接地址</span>
            <div class="copy-wrap">
                <p class="copy-tip">可复制链接到您的公众号菜单中</p>
                <input type="text" class="show-url" disabled :value="actList[0].cpActUrl">
                <button type="button" 
                    class="copy-btn"
                    :data-clipboard-text="actList[0].cpActUrl">
                    复制链接
                </button>
            </div>
        </div>
    </div>

    <!--填写手机号弹窗-->
    <el-dialog  v-model="isShowCheckPhone"
                :close-on-click-modal="false"
                :show-close="false">
        <!--提示标题-->
        <div class="dialog-title clear-fix">
            <div class="prompt fl">
                安全提示
            </div>
            <div class="dialog-close fr" @click="cancelBindTel">×</div>
        </div>

        <div class="dialog-content">
            <!--手机号填写表单-->
            <el-form ref="basePhone" :model="writePhone" :rules="checkPhoneRule">
                <div class="tips">为了你的账号安全，请先绑定手机号</div>

                <!--手机号码-->
                <el-form-item prop="tel">
                    <el-input placeholder="手机号码" v-model.number="writePhone.tel" maxLength="11"></el-input>
                </el-form-item>

                <!--图形验证码-->
                <el-form-item class="img-code" prop="imgCode">
                    <el-input placeholder="图形验证码" v-model="writePhone.imgCode"></el-input>
                    <span class="code-content">
                        <img v-if="idouziUrl"
                             :src="idouziUrl + '/public/code.php'"
                             onclick='this.src=this.src+"?"+Math.random();'
                             title="点击更换验证码">
                    </span>
                    <div class="error-msg"
                         v-show="isShowImgCodeError"
                         v-text="errorMessage.imgCode" v-cloak>
                    </div>
                </el-form-item>
            </el-form>

            <el-form ref="messagePhone" :model="writePhone" :rules="checkPhoneRule">
                <!--短信验证码-->
                <el-form-item class="message-code" prop="messageCode">
                    <el-input placeholder="短信验证码" v-model="writePhone.messageCode"></el-input>
                    <el-button class="code-content"
                               id="getMessageCode"
                               :disabled = 'writePhone.messageCodeDisabled'
                               @click="getCode('basePhone')">获取验证码</el-button>
                    <div class="error-msg"
                         v-show="isShowMessageCodeError"
                         v-text="errorMessage.messageCode" v-cloak>
                    </div>
                </el-form-item>
            </el-form>

            <div class="phone-bind-btn">
                <el-button class="bind-btn sure" @click="bind('basePhone','messagePhone')">绑定手机号</el-button>
                <el-button class="bind-btn no" @click="cancelBindTel">暂不绑定</el-button>
            </div>
        </div>

    </el-dialog>

    <!--没有购买时的弹窗-->
    <el-dialog  v-model="isShowBuyDialog"
                custom-class="gomall-dialog"
                :class="{'gomall-dialog-nocoupon' : !coupon.length}"
                title="提示"
                :close-on-click-modal="false">
        <div class="gomall-dialog-content">
            <div class="gomall-dialog-content-inner">
                客官，您的使用权已过期了哦~ <br>
                现在购买可继续使用该服务。
            </div>
        </div>
        <div class="gomall-dialog-footer">
            <div class="gomall-footer-confim">
                <a target="_blank"
                   :href="apiUrl.shopList"
                   @click="isShowBuyDialog = false"
                   class="gomall-confirm-btn cancel-btn">
                    买买买
                </a>
            </div>
        </div>

        <div class="gomall-dialog-coupon" v-if="coupon.length">
            <div class="gomall-dialog-coupon-wrap">
                <p class="gomall-coupon-title">【省钱小秘诀】·代金券免费兑换</p>
                <div class="gomall-cpupon-content">
                    <div class="gomall-coupon-info-wrap">
                        <div class="gomall-coupon-info"
                             :style="{'backgroundColor': coupon[0].denomination == 500 ? '#F1F4FE' : '#ffeeeb'}">
                            <div class="gomall-coupon-size"
                                 :style="{'color': coupon[0].denomination == 500 ? '#738EE5' : '#ff8066'}">
                                <span class="gomall-size-number">{{coupon[0].denomination}}</span>
                                <span class="gomall-size-type">{{coupon[0].name}}</span>
                            </div>
                            <div class="gomall-coupone-date"
                                 :style="{'borderColor': coupon[0].denomination == 500 ? '#B7C2E5' : '#FFBDB0'}">
                                <p class="gomall-coupone-date-text">
                                    - 仅限于<span class="strong">{{coupon[0].ruleType}}</span>使用
                                </p>
                                <p class="gomall-coupone-date-text">
                                    - 有效期至 {{coupon[0].endAt}}
                                </p>
                            </div>
                        </div>
                    </div>
                    <a target="_blank"
                       :href="apiUrl.shopList"
                       class="gomall-coupon-list-status"
                       :style="{'background-color': coupon[0].denomination == 500 ? '#738EE5' : '#FF9580'}">
                        <span>马上使用</span>
                    </a>
                </div>
            </div>
        </div>
    </el-dialog>

    <!--有数据时展示活动列表-->
    <div class="bargain-content" v-if="hasData" v-cloak>
        <div class="new-action-txt">新建活动</div>
        <a v-if="hasData"
           v-cloak
           class="add-new fr radius-3"
           @click.prevent="toNew()">新建活动</a>
        <!--活动列表标题-->
        <ul class="bargain-list-header clear-fix">
            <li class="activity-header-name">活动名称</li>
            <li class="activity-header-date">活动时间</li>
            <li class="activity-header-keyword">关键字</li>
            <li class="activity-header-num">参与人数</li>
            <li class="activity-header-status">活动状态</li>
            <li class="activity-header-operation">操作</li>
        </ul>
        <!--活动列表内容-->
        <ul class="bargain-list-content" v-loading="bargainLoading">
            <!--活动列表item-->
            <li class="bargain-item-wrap radius-5 hover"
                v-for="(good,actIndex) in actList">
                <ul class="bargain-item-content clear-fix"
                    :data-index="actIndex"
                    :data-eventId="good.eventId"
                    :data-type="good.type"
                    @click="getData($event)">
                    <li class="activity-name list-name">
                        <!--二维码-->
                        <div class="code">
                            <div class="list-name-code" @click="getQrcode($event)"></div>
                            <v-qrcode :cls="qrcode.class" :level="qrcode.level" :size="qrcode.size"
                                      :value="good.cpActUrl"/>
                        </div>
                        <span class="list-name-content text-overflow"
                              :class="{'mall-goods':good.type==0}"
                              v-text="good.name"
                              :title="good.name">
                        </span>
                    </li>
                    <li class="activity-date">
                        <div class="start-date" v-text="good.startTime"></div>
                        <div class="end-date" v-text="good.endTime"></div>
                    </li>
                    <!--关键字-->
                    <li class="activity-keyword text-overflow" :title="good.keyword" v-text="good.keyword"></li>
                    <li class="activity-num" v-text="good.participants"></li>
                    <!--状态码-->
                    <li class="activity-status" v-text="good.closeStatus"></li>
                    <li class="activity-operation"
                        :data-eventId="good.eventId"
                        :data-index="actIndex">
                        <span v-if="good.closeStatus=='未开始'||good.closeStatus=='进行中'"
                              class="operation-item"
                              @click="editAct($event)">编辑</span><!--

                     --><span v-if="good.closeStatus=='未开始'||good.closeStatus=='已结束'||good.closeStatus=='已关闭'"
                              class="operation-item"
                              @click="deleteAct($event)">删除</span><!--

                     --><span class="clip operation-item"
                              :data-clipboard-text="good.cpActUrl">复制链接</span><!--

                     --><span @click="copyAct($event)" class="operation-item">复制活动</span><!--

                     --><span v-if="good.closeStatus=='进行中'"
                              @click="closeAct($event)"
                              class="operation-item">关闭活动</span>
                    </li>
                </ul>
            </li>
            <!--活动列表分页-->
            <li class="bargain-item-wrap page" v-show="totalPage>1&&lookMoreStatus!==0">
                <el-pagination
                        layout="prev, pager, next"
                        :total="totalPage*10"
                        @current-change="getPageActData">
                </el-pagination>
            </li>
            <!--更多活动按钮-->
            <li class="bargain-more radius-5"
                ref="btnLookMore"
                @click="lookMore($event)">
                查看更多
            </li>
        </ul>
    </div>
    <!--删除活动弹窗-->
    <alert-tips :message="alertMessage" v-if="alertIsShow"></alert-tips>
</div>

<!--活动统计-->
<div v-if="bargain.hasData"
     v-cloak
     class="bargain-statistics radius-5"
     id="bargain-statistics">
    <!--活动统计标题-->
    <div class="statistics-content clear-fix">
        <a :href="sendGoodsUrl" v-show="isMall==0" class="go-mall" target="_blank">现在去发货 &gt;&gt;</a>
        <el-tabs v-model="activeName">
            <!--活动统计-->
            <el-tab-pane label="活动统计" name="act">
                <!--活动统计-->
                <div class="act-statistics">
                    <!--活动统计筛选列表-->
                    <div class="act-statistics-title clear-fix">
                        <!--商品状态-->
                        <div class="shop-status fl">
                            <span v-text="selOption.name"></span>
                            <div class="comp-idouzi-selection">
                                <!--商城商品状态选择-->
                                <el-select v-model="selectStatus" placeholder="请选择"
                                           @change="screenByDateOrType">
                                    <el-option
                                            v-for="item in selOption.optionList"
                                            :label="item.label"
                                            :value="item.value">
                                    </el-option>
                                </el-select>
                            </div>
                        </div>

                        <!--选择日期-->
                        <div class="date fl clear-fix">
                            <el-date-picker
                                    v-model="startTime"
                                    type="date"
                                    :editable="false"
                                    :clearable="false"
                                    placeholder="选择日期">
                            </el-date-picker>
                            <span>至</span>
                            <el-date-picker
                                    v-model="endTime"
                                    type="date"
                                    :editable="false"
                                    :clearable="false"
                                    placeholder="选择日期">
                            </el-date-picker>
                        </div>

                        <button type="button" class="export radius-3" @click="screenByDateOrType">搜索</button>
                        <!--导出按钮-->
                        <button type="button" class="export radius-3" @click="outPut">导出数据</button>

                        <!--搜索框-->
                        <div class="search radius-3 fr">
                            <input type="text" placeholder="姓名/手机号"
                                   v-model="searchByNameOrPhone"
                                   @keyup.enter="screenByNameOrPhone">
                            <span class="search-btn fr" @click="screenByNameOrPhone"></span>
                        </div>

                    </div>

                    <!--数据统计展示列表-->

                    <!--商城商品列表-->
                    <div class="act-statistics-content" v-loading="bargainActLoading">
                        <div v-if="isMall==0">
                            <ul class="content-header clear-fix">
                                <li class="mall-goods-name">姓名</li>
                                <li class="mall-goods-tel">手机号</li>
                                <li class="mall-goods-address">地址</li>
                                <li class="mall-goods-prize">商品名称</li>
                                <li class="mall-goods-num">帮砍人数</li>
                                <li class="mall-goods-price">当前金额</li>
                                <li class="mall-goods-status">商品状态</li>
                            </ul>
                            <div class="content-list" v-for="goods in goodLists">
                                <ul class="content-list-item clear-fix hover">
                                    <li class="mall-goods-name text-overflow"
                                        :title="goods.name"
                                        v-text="goods.name"></li>
                                    <li class="mall-goods-tel" v-text="goods.phone"></li>
                                    <li class="mall-goods-address text-overflow"
                                        v-text="goods.address"
                                        :title="goods.address"></li>
                                    <li class="mall-goods-prize text-overflow"
                                        v-text="goods.goodsName"
                                        :title="goods.goodsName"></li>
                                    <li class="mall-goods-num" v-text="goods.helpBargainNum"></li>
                                    <li class="mall-goods-price"
                                        :class="{'lower-price':goods.isLowestPrice===1}"
                                        v-text="goods.bargainPrice"></li>
                                    <li class="mall-goods-status" v-text="goods.resourceStatus"></li>
                                </ul>
                            </div>
                        </div>

                        <!--非商城商品列表-->
                        <div v-if="isMall==1">
                            <ul class="content-header clear-fix">
                                <li class="nonmall-goods-name">姓名</li>
                                <li class="nonmall-goods-tel">手机号</li>
                                <li class="nonmall-goods-address">地址</li>
                                <li class="nonmall-goods-prize">商品名称</li>
                                <li class="nonmall-goods-num">帮砍人数</li>
                                <li class="nonmall-goods-price">当前金额</li>
                                <li class="nonmall-goods-code">兑奖码</li>
                                <li class="nonmall-goods-status">兑奖状态</li>
                                <li class="nonmall-goods-cash">操作</li>
                            </ul>
                            <div class="content-list" v-for="(goods,index) in goodLists">
                                <ul class="content-list-item clear-fix hover">
                                    <li class="nonmall-goods-name text-overflow"
                                        :title="goods.name"
                                        v-text="goods.name"></li>
                                    <li class="nonmall-goods-tel" v-text="goods.phone"></li>
                                    <li class="nonmall-goods-address text-overflow"
                                        v-text="goods.address"
                                        :title="goods.address"></li>
                                    <li class="nonmall-goods-prize text-overflow"
                                        v-text="goods.goodsName"
                                        :title="goods.goodsName"></li>
                                    <li class="nonmall-goods-num" v-text="goods.helpBargainNum"></li>
                                    <li class="nonmall-goods-price"
                                        :class="{'lower-price':goods.isLowestPrice===1}"
                                        v-text="goods.bargainPrice"></li>
                                    <li class="nonmall-goods-code" v-text="goods.resourceExplain"></li>
                                    <li class="nonmall-goods-status" v-text="goods.resourceStatus"></li>
                                    <li class="nonmall-goods-cash" v-if="goods.resourceStatus=='未兑奖'">
                                        <button type="button"
                                                class="cash-btn radius-3"
                                                :data-prizeId="goods.bargainId"
                                                :data-index="index"
                                                @click="getCashPrize($event)">兑奖
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!--没有数据是展示-->
                        <div class="statistics-nondata" v-if="!statisticsHasData" v-loading="bargainActLoading">
                            没有参与者信息
                        </div>

                        <div class="page" v-if="totalPage>1">
                            <el-pagination
                                    layout="prev, pager, next"
                                    :total="totalPage*10"
                                    :current-page="initPage"
                                    @current-change="getActData"
                            >
                            </el-pagination>
                        </div>
                    </div>
                </div>
            </el-tab-pane>
        </el-tabs>
    </div>

    <input type="hidden" id="idouziUrl" value="<?php echo Yii::$app->params['serviceUrl']['idouziUrl'];?>">
    <input type="hidden" id="signKey" value="<?php echo Yii::$app->params['signKey']['voteSignKey'];?>">
    <input type="hidden" id="userId" value="<?php echo Yii::$app->session->get('userAuthInfo')['supplierId'];?>">
    <input type="hidden" id="isBuyGood" value="<?php echo Yii::$app->session->get('check_buy_newbargain') ? 1 : 0;?>">
    <input type="hidden" id="mallUrl" value="<?php echo Yii::$app->params['serviceUrl']['MALL_URL'];?>">
</div>
<!--vue二维码插件-->
<script src="http://static-10006892.file.myqcloud.com/plugin/v-qrcode/v-qrcode.min.js"></script>
<!--复制链接到剪切板插件-->
<script src="http://static-10006892.file.myqcloud.com/plugin/clipboard/clipboard-1.6.1.min.js"></script>
<script src="http://static-10006892.file.myqcloud.com/plugin/jquery-md5/jquery.md5.js"></script>
<script src="http://static-10006892.file.myqcloud.com/public/js/idouzi-tools.min.js"></script>
<!--本页JS-->
<script src="http://static-10006892.file.myqcloud.com/bargain/supplier/js/index-1c50ef5b88.js"></script>