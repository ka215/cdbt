// ## Globals
var argv          = require('minimist')(process.argv.slice(2)); // CLIコマンドの引数をGulp側で受け取れるようになる
//var autoprefixer = require('gulp-autoprefixer'); // ベンダープレフィックスを自動付与してくれる -> gulp-pleeease でできる
var browserSync  = require('browser-sync').create(); // アセットソースの変更検知時にGulpタスクを自動実行してパブリッシュアセットへの同期を行う
var changed      = require('gulp-changed'); // srcとdestをチェックして変更されたファイルだけStreamに流す
var coffee       = require('gulp-coffee'); // CoffeeScriptのコンパイル
var coffeelint   = require('gulp-coffeelint'); // CoffeeScriptの構文チェック
var concat       = require('gulp-concat'); // 複数ファイルを結合する
var flatten      = require('gulp-flatten'); // ファイルのディレクトリ階層を平坦化する
var gulp         = require('gulp'); // Gulp本体
var gulpif       = require('gulp-if'); // 分岐処理用。条件に合致した時にタスクを実行する
var imagemin     = require('gulp-imagemin'); // GIF,JPEG,PNG,SVGをロスレスで軽量化する
var jshint       = require('gulp-jshint'); // JavaScript構文チェッカー（cf. jshint-stylish で出力を見やすく整形できる）
var lazypipe     = require('lazypipe'); // 複数のタスクをグループ化して、別々のタスクで利用可能にする（同じタスク記述を増やさずにスッキリ書けるようになる）
var less         = require('gulp-less'); // LESSのコンパイル 
var merge        = require('merge-stream'); // タスク内の複数ストリームをマージする -> event-stream の mergeメソッドの方が良いかも
//var minifyCss    = require('gulp-minify-css'); // CSSをミニファイ化してくれる -> gulp-pleeease でできる
var please   = require('gulp-pleeease'); // gulp-autoprefixer と gulp-minify-css より高機能なプラグイン
var plumber      = require('gulp-plumber'); // Stream中のエラーによってタスクが強制停止するのを防止する（watch中のエラーによりプロセス停止を抑止するために使われることが多い）
var rev          = require('gulp-rev'); // ビルド時にアセットファイル名を変更する（リビジョン番号を追加する）
var runSequence  = require('run-sequence'); // タスクを任意の順番で（同期的に）実行する
var sass         = require('gulp-sass'); // Sass/SCSSのコンパイル。 gulp-ruby-sass だと出力スタイルを選択できる（要Ruby+Gem環境）。
var sourcemaps   = require('gulp-sourcemaps'); // source mapを出力する（initメソッドからwriteメソッドの間にパイプされたプラグインをマッピングする）
var uglify       = require('gulp-uglify'); // UglifyJSを使ってJavaScriptファイルをminifyする
var manifest = require('asset-builder')('./sources/manifest.json'); // アセット結合を定義ファイルとして一元化できる 参照: https://github.com/austinpray/asset-builder

// `path` - ベースアセットディレクトリのパス。末尾にスラッシュが必要
// - `path.source` - ソースファイルのパス。初期値: `assets/`
// - `path.dist` - ビルドディレクトリのパス。初期値: `dist/`
var path = manifest.paths;

// `config` - ここに任意の設定値を格納します
var config = manifest.config || {};

// `globs` - それぞれの最終的なビルドソース `gulp.src` の設定
// - `globs.js` - JS依存オブジェクトの asset-builder用配列。以下、例:
//   ```
//   {type: 'js', name: 'main.js', globs: []}
//   ```
// - `globs.css` - CSS依存オブジェクトの asset-builder用配列。以下、例:
//   ```
//   {type: 'css', name: 'main.css', globs: []}
//   ```
// - `globs.fonts` - フォントパスの配列
// - `globs.images` - イメージパスの配列
// - `globs.bower` - すべての主要Bowerファイルの配列
var globs = manifest.globs;

// `project` - first-partyアセットのパス
// - `project.js` - first-party JSアセットの配列
// - `project.css` - first-party CSSアセットの配列
var project = manifest.getProjectGlobs();

// CLI 設定
var enabled = {
  // 引数 `--production` 指定時はJSのコメントはすべて削除する（comment: true）
  comment: !argv.production, 
  // 引数 `--production` 指定時は静的アセットのリビジョンが有効化される（rev: true）
  rev: argv.production,
  // 引数 `--production` 指定時はソースマッピングを無効化する（maps: false）
  maps: !argv.production,
  // 引数 `--production` 指定のエラー時はスタイル系タスクを停止する（failStyleTask: true）
  failStyleTask: argv.production
};

// distディレクトリ内のコンパイル済みアセット設定のパス
var revManifest = path.dist + 'assets.json';

// ## 再利用可能なパイプライン
// See https://github.com/OverZealous/lazypipe

// ### CSS 処理パイプライン
// Example
// ```
// gulp.src(cssFiles)
//   .pipe(cssTasks('main.css')
//   .pipe(gulp.dest(path.dist + 'styles'))
// ```
var cssTasks = function(filename) {
  return lazypipe()
    .pipe(function() {
      return gulpif(!enabled.failStyleTask, plumber()); // `--production`指定時はエラー時にタスクが停止する
    })
    .pipe(function() {
      return gulpif(enabled.maps, sourcemaps.init()); // `--production`未指定時はソースマップの初期化を行わない
    })
    .pipe(function() {
      return gulpif('*.less', less()); // *.lessファイルがあれば、LESSコンパイル
    })
    .pipe(function() {
      return gulpif('*.scss', sass({ // *.scssファイルがあれば、SCSSコンパイル
        outputStyle: 'nested', // libsass ではまだ非対応
        precision: 10,
        includePaths: ['.'],
        errLogToConsole: !enabled.failStyleTask
      }));
    })
    .pipe(concat, filename)
    .pipe(please, {
      'autoprefixer': { 'browsers': [ 'last 2 versions', 'ie 8', 'ie 9', 'android 2.3', 'android 4', 'opera 12' ] },
      'filters': true,
      'rem': false,
      'opacity': true,
      'pseudoElements': false,
      'minifier': true,
      'mqpacker': true
    })
    .pipe(function() {
      return gulpif(enabled.rev, rev()); // `--production`指定時はリビジョンを有効化
    })
    .pipe(function() {
      return gulpif(enabled.maps, sourcemaps.write('.')); // `--production`未指定時はソースマップを出力しない
    })();
};

// ### JS 処理パイプライン
// Example
// ```
// gulp.src(jsFiles)
//   .pipe(jsTasks('main.js')
//   .pipe(gulp.dest(path.dist + 'scripts'))
// ```
var jsTasks = function(filename) {
  return lazypipe()
    .pipe(function() {
      return gulpif(enabled.maps, sourcemaps.init()); // `--production`未指定時はソースマップの初期化を行わない
    })
    .pipe(concat, filename)
    .pipe(function() {
      return gulpif(enabled.comment, uglify({ preserveComments: 'some' }), uglify()); // `--production`指定ありなしでJSのコメント削除操作を変更する
    })
    .pipe(function() {
      return gulpif(enabled.rev, rev()); // `--production`指定時はリビジョンを有効化
    })
    .pipe(function() {
      return gulpif(enabled.maps, sourcemaps.write('.')); // `--production`未指定時はソースマップを出力しない
    })();
};


// ### リビジョン設定の書き込み
// 複数のリビジョンファイルがある場合、リビジョン設定（rev manifest）に書き込みます
// See https://github.com/sindresorhus/gulp-rev
var writeToManifest = function(directory) {
  return lazypipe()
    .pipe(gulp.dest, path.dist + directory)
    .pipe(browserSync.stream, {match: '**/*.{js,css}'})
    .pipe(rev.manifest, revManifest, {
      base: path.dist,
      merge: true
    })
    .pipe(gulp.dest, path.dist)();
};

// ## Gulp メインタスク
// `gulp -T` コマンドでタスク一覧を確認できます

// ### スタイル系タスク
// `gulp styles` - 依存関係の自動連携後、コンパイル、結合、Bowerで読み込まれたCSSとプロジェクト用のCSSを最適化する
// プリコンパイル・エラーが発生した場合、デフォルトでは警告のみをログに出力する。
// 引数 `--production` が設定されている場合、タスクは完全に失敗して停止します。
gulp.task('styles', ['wiredep'], function() {
  var merged = merge();
  manifest.forEachDependency('css', function(dep) {
    var cssTasksInstance = cssTasks(dep.name);
    if (!enabled.failStyleTask) {
      cssTasksInstance.on('error', function(err) {
        console.error(err.message);
        this.emit('end');
      });
    }
    merged.add(gulp.src(dep.globs, {base: 'styles'})
      .pipe(cssTasksInstance));
  });
  return merged
    .pipe(writeToManifest('styles'));
});

// ### スクリプト系タスク
// `gulp scripts` - JSHintの実行後、コンパイル、結合、Bowerで読み込まれたJSとプロジェクト用のJSを最適化する
gulp.task('scripts', ['coffee', 'jshint'], function() {
  var merged = merge();
  manifest.forEachDependency('js', function(dep) {
    merged.add(
      gulp.src(dep.globs, {base: 'scripts'})
        .pipe(jsTasks(dep.name))
    );
  });
  return merged
    .pipe(writeToManifest('scripts'));
});

// ### フォント系タスク
// `gulp fonts` - すべてのフォントを取得して平坦なディレクトリ構造にして出力する。
// 参考: https://github.com/armed/gulp-flatten
gulp.task('fonts', function() {
  return gulp.src(globs.fonts)
    .pipe(flatten())
    .pipe(gulp.dest(path.dist + 'fonts'))
    .pipe(browserSync.stream());
});

// ### イメージ系タスク
// `gulp images` - すべての画像のロスレス圧縮を実行する
gulp.task('images', function() {
  return gulp.src(globs.images)
    .pipe(imagemin({
      progressive: true,
      interlaced: true,
      svgoPlugins: [{removeUnknownsAndDefaults: false}]
    }))
    .pipe(gulp.dest(path.dist + 'images'))
    .pipe(browserSync.stream());
});

// ### CoffeeScriptコンパイル
// coffeelint による構文チェック後にコンパイルする
gulp.task('coffee', function() {
  return gulp.src(path.source + 'scripts/*.coffee')
    .pipe(coffeelint())
    .pipe(coffeelint.reporter())
    .pipe(coffeelint.reporter('fail'))
    .pipe(coffee())
    .pipe(gulp.dest(path.source + 'scripts'));
});

// ### JavaScript構文チェック（JSHint）
// `gulp jshint` - Lints設定（JSON）とプロジェクト用のJSをチェックする
gulp.task('jshint', function() {
  return gulp.src([
    'bower.json', 'gulpfile.js'
  ].concat(project.js))
    .pipe(jshint())
    .pipe(jshint.reporter('jshint-stylish'))
    .pipe(jshint.reporter('fail'));
});

// ### クリーンアップ
// `gulp clean` - ビルドフォルダを完全に削除する
gulp.task('clean', require('del').bind(null, [path.dist]));

// ### 監視（Watch）
// `gulp watch` - BrowserSyncを使用すると、開発サーバを介してデバイス間でのコードの変更が同期されます。
// 開発サーバのホスト名を `manifest.config.devUrl` に指定してください。
// アセットに対して変更が行われた場合、そのアセットのビルドステップを実行され、ページへ変更が反映されます。
// 参考: http://www.browsersync.io
gulp.task('watch', function() {
  browserSync.init({
    files: ['{lib,templates}/**/*.php', '*.php'],
    proxy: config.devUrl,
    snippetOptions: {
      whitelist: ['/wp-admin/admin-ajax.php'],
      blacklist: ['/wp-admin/**']
    }
  });
  gulp.watch([path.source + 'styles/**/*'], ['styles']);
  gulp.watch([path.source + 'scripts/**/*'], ['coffee', 'jshint', 'scripts']);
  gulp.watch([path.source + 'fonts/**/*'], ['fonts']);
  gulp.watch([path.source + 'images/**/*'], ['images']);
  gulp.watch(['bower.json', 'assets/manifest.json'], ['build']);
});

// ### ビルド
// `gulp build` - すべてのビルドを実行するが、事前にクリーンアップされない。
// 原則として、`gulp build` の実行前に `gulp` を実行している必要がある。
gulp.task('build', function(callback) {
  runSequence('styles',
              'scripts',
              ['fonts', 'images'],
              callback);
});

// ### 依存関係の自動連携（Wiredep）
// `gulp wiredep` - 自動的にLESSとSassのBower依存関係を読み込みます。
// 参考: https://github.com/taptapship/wiredep
gulp.task('wiredep', function() {
  var wiredep = require('wiredep').stream;
  return gulp.src(project.css)
    .pipe(wiredep())
    .pipe(changed(path.source + 'styles', {
      hasChanged: changed.compareSha1Digest
    }))
    .pipe(gulp.dest(path.source + 'styles'));
});

// ### Gulp
// `gulp` - Run a complete build. To compile for production run `gulp --production`.
gulp.task('default', ['clean'], function() {
  gulp.start('build');
});
