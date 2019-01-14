/**
 * 微砍价webpack配置文件
 * @author  段龙剑
 * @date    2017-03-05 14:50:47
 */

//引用webpack模块
const webpack = require('webpack');

module.exports = {
    entry: __dirname +'/static/entry.js', //入口文件
    output: { //打包后输出路径及文件名
        path: __dirname + '/web/mobile/js',
        filename: 'bargain.js'
    },
    module: {
        loaders: [{
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.css$/,
                loader: 'css-loader'
            }
        ]
    },
    externals: {
        'Vue': 'window.Vue',
        'VueResource': 'window.VueResource'
    }
};