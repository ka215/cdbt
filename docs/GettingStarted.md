### 概要
Custom DataBase Tables（カスタムDBテーブル、以降CDBTと呼称します）プラグインは、非常に簡単にWordPressのデータベースにテーブルを新規追加することができます。また、新たに作成したテーブルへのデータの入力や参照、編集もGUI経由で直感的に行うことができます。さらに、作成したテーブルに対してはショートコードやAPIを通して豊富な操作ができ、WordPressをCMSとして利用するための助けになるでしょう。

### 基本
CDBTの基本操作はWordPressの管理パネルで行います。CDBTプラグインをインストールした後に管理パネルの「設定」メニューには「カスタムDBテーブル」というメニューが追加されます。
テーブルの作成や管理といった様々な操作がこの設定メニューから行うことができます。

現在のところ、CDBTプラグインはWordPressのコアとなるテーブルに対しては一切処理を行いません。CDBTプラグインの設定情報のみが`wp_option`テーブルに格納されるだけで、それ以外のテーブルに対して参照や編集、削除といった管理機能を持っていません。

CDBTプラグインによって新規追加されたテーブルに対しては、プラグインのコアライブラリである`CustomDatabaseTables`クラスによって制御を行います。`CustomDatabaseTables`クラスはオブジェクト変数`$cdbt`としてグローバル宣言されているため、WordPressのテーマや他のプラグインから参照することができます。

また、ネイティブのWP関数から利用可能なAPIも準備されており、これらを利用することで、テーマやプラグインなどにCDBTプラグインで作成したテーブルを連携させることが容易になっています。

以下は`CustomDatabaseTables`クラスのメソッドを利用して新規作成したテーブルからデータを取得する使用例です。

```
<?php
/*
Template Name: CDBT Sample Template
*/
global $cdbt
$data = $cdbt->get_data('my_table_name');
?>
<h2>my_table_name.tbl all data</h2>
<table>
<?php foreach ($data as $record) : ?>
    <tr>
    <?php foreach ($record as $key => $val) : ?>
      <th><?php echo $key; ?></th>
      <td><?php echo $val; ?></td>
    <?php endforeach; ?>
    </tr>
<?php endforeach; ?>
</table>
```

### CDBTのショートコード
CDBTプラグインはショートコードを利用することで、新規作成したテーブルのデータ一覧ページやデータ登録用のエントリーページなども簡単に提供することができます。ショートコードはCDBTプラグイン設定のダッシュボードページの下部に表示されているので、それを投稿や固定ページに貼り付けるだけです。

ショートコードは豊富なオプションを持っており、用途にあわせてオプションを最適化することで、テーマやプラグインにあわせたカスタマイズも可能です。

以下は、新規作成した`prefix_new_table`というテーブルのデータ登録フォームを表示するためのショートコードです。ショートコードのオプションで、テーブル名が表示されるタイトル表記を非表示にし、出力される`<table>`に`my_cdbt_style`というクラス属性を付与するという指定です。
```
[cdbt-entry table="prefix_new_table" display_title="false" add_class="my_cdbt_style"]
```
