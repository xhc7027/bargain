/*
 * 判断当前对象是否包含某个class
 * obj:当前对象
 * cls: class名
 * 返回Boolean值
 * */

var Bargain = function () {
    /**
     * 格式化get参数
     * @param {Object} data 需要格式化的参数
     * @returns {string}
     */
    this.formatGetUrlData = function (data) {
        var formatText = "?";
        for (var key in data) {
            if (data[key] === "" || data[key] === undefined ||data[key] === null) {
                continue;
            } else {
                formatText += "&" + key + "=" + data[key];
            }
        }
        return formatText.replace("&", "")
    };

    /**
     * 遍历
     * @param {Object} obj 需要遍历的对象
     * @param {Function} callback  回调函数
     */
    this.forEachData = function (obj, callback) {
        for (var index = 0, len = obj.length; index < len; index++) {
            var value = obj[index];
            if (callback instanceof Function) {
                // 如果返回false,终止
                if (callback(value, index) === false) {
                    return false;
                }
            }

        }
    };

    /**
     * 获取链接参数
     * @param {String} name 需要获取的url参数
     * @returns {null}
     */
    this.getUrlParam = function (name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if(r!=null)return  unescape(r[2]); return null;
    }
};

/**
 * 判断当前节点是否含有某个class
 * @param cls class名
 * @returns {boolean}
 */
Element.prototype.hasClass = function (cls) {
    cls = cls || '';
    if (cls.replace(/\s/g, '').length === 0) {
        return false;
    } //当cls没有参数时，返回false
    return new RegExp(' ' + cls + ' ').test(' ' + this.className + ' ');
};
/**
 * 为当前对象添加class
 * @param {String} cls class名
 */
Element.prototype.addClass = function (cls) {
    if (!this.hasClass(cls)) {
        this.className += " " + cls;
    }
};
/**
 * 删除当前对象的某个class
 * @param {String} cls class名
 */
Element.prototype.removeClass = function (cls) {
    if (this.hasClass(cls)) {
        var reg = new RegExp('(\\s|^)' + cls + '(\\s|$)');
        this.className = this.className.replace(reg, ' ');
    }
};
/**
 * @param {String} fmt 'yyyy-MM-dd hh:mm'
 * @returns {String} 格式化的日期时间
 */
Date.prototype.Format = function (fmt) {
    var o = {
        "M+": this.getMonth() + 1, //月份
        "d+": this.getDate(), //日
        "h+": this.getHours(), //小时
        "m+": this.getMinutes(), //分
        "s+": this.getSeconds(), //秒
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度
        "S": this.getMilliseconds() //毫秒
    };
    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
};

/**
 * 获取下一个兄弟元素
 * @returns {Element}
 */
Element.prototype.next = function () {
    if (this.nextElementSibling) {
        return this.nextElementSibling;
    }
    var sib = this.nextSibling;

    while (sib && sib.nodeType !== 1) {
        return sib;
    }
};
/**
 * 去掉首尾空格
 * @returns {string}
 */
String.prototype.trim = function () {
    return this.replace(/(^\s*)|(\s*$)/g, "");
};
