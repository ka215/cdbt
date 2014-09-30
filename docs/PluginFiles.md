<div doc-label="specification"></div>
Custom DataBase Tables（以下、CDBTと呼びます）プラグインの構成ファイルとディレクトリ構造は下記のようになっています。

<i class="fa fa-folder-open-o"></i> custom-database-tables/
　　├─ <i class="fa fa-folder-o"></i> assets/
　　│　　├─ <i class="fa fa-folder-o"></i> css/   
　　│　　│　　├─ <i class="fa fa-file-o"></i> cdbt-admin.css
　　│　　│　　├─ <i class="fa fa-file-o"></i> cdbt-main.min.css
　　│　　│　　└─ <i class="fa fa-file-o"></i> cdbt-style.css
　　│　　├─ <i class="fa fa-folder-o"></i> fonts/   
　　│　　│　　├─ <i class="fa fa-file-o"></i> glyphicons-halflings-regular.eot
　　│　　│　　├─ <i class="fa fa-file-o"></i> glyphicons-halflings-regular.svg
　　│　　│　　├─ <i class="fa fa-file-o"></i> glyphicons-halflings-regular.ttf
　　│　　│　　└─ <i class="fa fa-file-o"></i> glyphicons-halflings-regular.woff
　　│　　└─ <i class="fa fa-folder-o"></i> js/   
　　│　　 　　├─ <i class="fa fa-folder-o"></i> plugins/   
　　│　　 　　│　　└─ <i class="fa fa-folder-o"></i> bootstrap/   
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> affix.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> alert.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> button.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> carousel.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> collapse.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> dropdown.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> modal.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> popover.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> scrollspy.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> tab.js
　　│　　 　　│　　 　　├─ <i class="fa fa-file-o"></i> tooltip.js
　　│　　 　　│　　 　　└─ <i class="fa fa-file-o"></i> transition.js
　　│　　 　　└─ <i class="fa fa-file-o"></i> scripts.min.js
　　├─ <i class="fa fa-folder-o"></i> langs/
　　│　　├─ <i class="fa fa-file-o"></i> custom-database-tables.pot  
　　│　　├─ <i class="fa fa-file-o"></i> custom-database-tables-ja.mo  
　　│　　└─ <i class="fa fa-file-o"></i> custom-database-tables-ja.po  
　　├─ <i class="fa fa-folder-o"></i> lib/
　　│　　├─ <i class="fa fa-file-code-o"></i> [cdbt.ajax.php](#cdbt-ajax-php) 
　　│　　├─ <i class="fa fa-file-code-o"></i> [cdbt.class.php](#cdbt-class-php)
　　│　　├─ <i class="fa fa-file-code-o"></i> [cdbt.media.php](#cdbt-media-php)
　　│　　├─ <i class="fa fa-file-code-o"></i> [cdbt.scripts.php](#cdbt-scripts-php)
　　│　　└─ <i class="fa fa-file-code-o"></i> [cdbt.shortcodes.php](#cdbt-shortcodes-php)
　　├─ <i class="fa fa-folder-o"></i> [templates/](#templates-folder)
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-admin-controller.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-admin-create.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-admin-general.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-admin-table-creator.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-admin-tables.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-edit.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-index.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-input.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-list.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-public-edit.php 
　　│　　├─ <i class="fa fa-file-code-o"></i> cdbt-public-input.php 
　　│　　└─ <i class="fa fa-file-code-o"></i> cdbt-public-list.php 
　　├─ <i class="fa fa-file-code-o"></i> [cdbt.php](#cdbt-php)
　　├─ <i class="fa fa-file-code-o"></i> [functions.php](#functions-php)
　　├─ <i class="fa fa-file-text-o"></i> readme.txt
　　└─ <i class="fa fa-file-code-o"></i> uninstall.php


#### <a name="cdbt-ajax-php"></a><i class="fa fa-file-code-o"></i> lib/cdbt.ajax.php
Custom DataBase Tables（以下、CDBTと呼称します）プラグイン内のAJAX処理を司るクラス`CustomDataBaseTables_Ajax`が定義されています。このクラスはプラグインのコアクラス`CustomDatabaseTables`のコンストラクタにてインスタンス化されているため、コアクラス`CustomDatabaseTables`を経由することで呼び出すことが可能です。
バージョン1.0.0で登録されている処理は、テーブルデータ一覧画面や編集画面でのバイナリデータ格納フィールドからのダウンロードデータリストをモーダルウィンドウへ出力する処理となっています。

#### <a name="cdbt-class-php"></a><i class="fa fa-file-code-o"></i> lib/cdbt.class.php
CDBTプラグインのコアクラスである`CustomDatabaseTables`クラスが定義されています。登録されている処理は大きく、プラグイン本体の制御部と新規作成したテーブルに対するCRUD部に分かれます。CRUD部の各種メソッドについてはグローバル宣言されているオブジェクト変数`$cdbt`のメソッドとしてWordPressのテーマ等のテンプレート関数内で利用可能です。
[CRUD部メソッド一覧はこちら](./#plugin_methods)を参照してください。

#### <a name="cdbt-media-php"></a><i class="fa fa-file-code-o"></i> lib/cdbt.media.php
CDBTプラグイン内の各種メディアファイルダウンロード処理に利用されるAJAXクラス`CustomDataBaseTables_Media`が定義されています。このクラスもAJAXクラス同様にプラグインのコアクラス`CustomDatabaseTables`のコンストラクタにてインスタンス化されているため、コアクラス`CustomDatabaseTables`を経由することで呼び出すことが可能です。

#### <a name="cdbt-scripts-php"></a><i class="fa fa-file-code-o"></i> lib/cdbt.scripts.php
CDBTプラグインでインクルードされるJavaScriptを動的生成するライブラリです。WordPressの管理パネルとフロントエンドとで処理が分かれています。また、テーブルクリエーターの実働部も含まれています。

#### <a name="cdbt-shortcodes-php"></a><i class="fa fa-file-code-o"></i> lib/cdbt.shortcodes.php
CDBTプラグインで利用できるショートコード`[[cdbt-view]]``[[cdbt-entry]]``[[cdbt-edit]]`が定義されています。
[ショートコードの詳細はこちら](./#plugin_shortcodes)を参照してください。

#### <a name="templates-folder"></a><i class="fa fa-folder-o"></i> templates/
CDBTプラグインで生成される各種ページのテンプレートファイルが格納されているディレクトリです。ファイル名に`cdbt-public-`を含まないファイルはWordPress管理パネルのCDBTプラグイン管理コンソール用のテンプレートです。`cdbt-public-`で始まるファイルはショートコードで生成されるHTMLコンテンツを出力するために使われます。

#### <a name="cdbt-php"></a><i class="fa fa-file-code-o"></i> cdbt.php
CDBTプラグインの起点ファイルです。プラグインのメタ情報と各種定数定義、各種ライブラリの読み込みが行われます。

#### <a name="functions-php"></a><i class="fa fa-file-code-o"></i> functions.php
CDBTプラグイン用のAPIが定義されているファイルです。APIはプラグイン内の各クラスやテンプレート等から利用されています。なお、CDBTプラグインがインストールされているWordPressサイトであれば、テーマや他のプラグインからも利用が可能です。
[CDBTプラグインAPI一覧はこちら](./#plugin_apis)を参照してください。
