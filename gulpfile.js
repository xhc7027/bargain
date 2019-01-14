const gulp         = require('gulp'), //引用node_modules下的gulp
      less         = require('gulp-less'),
      cssAutofix   = require('gulp-autoprefixer'),
      cssMin       = require('gulp-clean-css'),
      rev          = require('gulp-rev'),
      revCollector = require('gulp-rev-collector'),
      clean        = require('gulp-clean'),
      sequence     = require('gulp-sequence'),
      rename       = require('gulp-rename'),
      plumber      = require('gulp-plumber'),
      spriter      = require('gulp.spritesmith'),
      babel        = require('gulp-babel'),
      uglify       = require('gulp-uglify'),
      webpack      = require('webpack'),
      browserSync  = require('browser-sync').create();

// less编译、添加前缀、压缩、添加版本号
gulp.task('css', ['cleanCSS'], () => {
    return gulp.src('./static/less/bargain.less')
               .pipe(plumber())
               .pipe(less())
               .pipe(cssAutofix())
               .pipe(cssMin())
               .pipe(rev())
               .pipe(gulp.dest('./web/mobile/css/'))
               .pipe(rev.manifest({
                    base: 'static',
                    path: 'static/rev-manifest.json',
                    merge: true
               }))
               .pipe(gulp.dest('static/'));
});

// js(ES6)编译压缩、添加版本号
gulp.task('js', ['cleanJS'], () => {
    return gulp.src('./web/mobile/js/bargain.js')
                .pipe(babel( {presets: ['es2015']} ))
                .pipe(uglify())
                .pipe(rev())
                .pipe(gulp.dest('./web/mobile/js/'))
                .pipe(rev.manifest({
                     base: 'static',
                     path: 'static/rev-manifest.json',
                     merge: true
                }))
                .pipe(gulp.dest('static/'));
});

// 清空CSS文件目录(去掉冗余文件)
gulp.task('cleanCSS', () => {
    return gulp.src(['./web/mobile/css/'])
               .pipe(clean());
});

// 删除多余带版本JS文件(去掉冗余文件)
gulp.task('cleanJS', () => {
    return gulp.src(['./web/mobile/js/bargain-*.js'])
               .pipe(clean());
});

// 删除webpack打包之后的原文件
gulp.task('delOriginalJS', () => {
    return gulp.src(['./web/mobile/js/bargain.js'])
               .pipe(clean());
});

// 添加静态文件引用
gulp.task('htmlRef', () => {
    return gulp.src(['./static/rev-manifest.json', './static/bargain.html'])
               .pipe(revCollector()) //添加版本号
               .pipe(rename('bargain.php'))
               .pipe(gulp.dest('./views/mobile/'));
});

// 图片合并
gulp.task('sprite', () => {
    return gulp.src('./static/images/*.png')
                .pipe(spriter({
                    imgName: 'sprite.png',
                    cssName: 'sprite.css',
                    padding: 10
                }))
                .pipe(gulp.dest('./web/mobile/img/'));
});

// webpack任务
gulp.task('webpack', (cb) => {
    webpack(require('./webpack.config.js'), () => { cb(); });
});

// 实时监听文件改动并刷新页面
gulp.task('server', ['default'], () => {
    // 开启本地服务器
    // browserSync.init({
    //     server: {
    //         baseDir: './',
    //         index: './views/mobile/bargain.php'
    //     }
    // });

    gulp.watch('./static/less/bargain.less', ['default']);
    gulp.watch('./static/script/bargain.js', ['default']);
    gulp.watch('./static/entry.js', ['default']);
    gulp.watch('./static/webpack.config.js', ['default']);
    gulp.watch('./static/bargain.html', ['default']);
    gulp.watch('./views/mobile/bargain.php').on('change', browserSync.reload);
});

gulp.task('default', (cb) => { //必须带回调，否则监听会错误
   sequence(['webpack', 'css'], 'js', ['htmlRef', 'delOriginalJS'], cb);
});

// PC端gulp配置

// PC端less编译、添加前缀、压缩、添加版本号
gulp.task('supplierCss', ['cleanSupplierCSS'], () => {
    return gulp.src('./web/supplier/css/less/*.less')
        .pipe(plumber())
        .pipe(less())
        .pipe(cssAutofix())
        .pipe(cssMin())
        .pipe(rev())
        .pipe(gulp.dest('./web/supplier/css/'))
        .pipe(rev.manifest({
            base: 'web',
            path: 'web/supplier/rev-css-manifest.json',
            merge: true
        }))
        .pipe(gulp.dest('web'));
});

// PC端清空css目录
gulp.task('cleanSupplierCSS', () => {
    return gulp.src(['./web/supplier/css/*.css'])
        .pipe(clean());
});

gulp.task('supplierJs', ['cleanSupplierJS'], () => {
    return gulp.src(['./web/supplier/js/index.js','./web/supplier/js/create.js'])
        .pipe(babel( {presets: ['es2015']} ))
        .pipe(uglify())
        .pipe(rev())
        .pipe(gulp.dest('./web/supplier/js/index_js'))
        .pipe(rev.manifest({
            base: 'web',
            path: 'web/supplier/rev-js-manifest.json',
            merge: true
        }))
        .pipe(gulp.dest('web'));
});

// 删除多余带版本JS文件(去掉冗余文件)
gulp.task('cleanSupplierJS', () => {
    return gulp.src(['./web/supplier/js/index_js/*.js'])
        .pipe(clean());
});

// PC段添加静态文件引用
gulp.task('supplierHtmlRef', () => {
    return gulp.src(['./web/supplier/*.json', './web/supplier/*.html'])
        .pipe(revCollector()) //添加版本号
        .pipe(rename(function (path) {
            path.extname = path.extname.replace("html", 'php');
        }))
        .pipe(gulp.dest('./views/supplier/'));
});

gulp.task('supplier', (cb) => { //必须带回调，否则监听会错误
    sequence('supplierCss', 'supplierJs', 'supplierHtmlRef', cb);
});