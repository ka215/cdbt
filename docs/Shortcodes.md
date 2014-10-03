<div doc-label="specification"></div>
本項では、CDBTプラグインで利用可能なショートコードを解説します。
CDBTプラグインのすべてのショートコードは、投稿や固定ページをはじめ、WordPressのテーマ内であればどこでも利用可能で、各テーブル毎のショートコードは管理パネルの[設定]-[カスタムDBテーブル]のダッシュボード画面下部にて確認できます[^1]。

[^1]: 事前にCDBT管理コンソールにてショートコードを確認したいテーブルを有効化しておく必要があります。

### ショートコード一覧

* <a href="/cdbt/documentation/shortcodes/cdbt-view">&#91;cdbt-view&#93;</a> ─ 指定したテーブルのデータ一覧（HTMLコンテンツ）を出力します。
* <a href="/cdbt/documentation/shortcodes/cdbt-entry">&#91;cdbt-entry&#93;</a> ─ 指定したテーブルのデータ登録用フォーム（HTMLコンテンツ）を出力します。
* <a href="/cdbt/documentation/shortcodes/cdbt-edit">&#91;cdbt-edit&#93;</a> ─ 指定したテーブルのデータ編集用（HTMLコンテンツ）を出力します。


<div doc-label="specification"></div>
<a id="shortcode-cdbt-view" name="shortcode-cdbt-view"></a>
### &#91;cdbt-view&#93;

　 _&#91;cdbt-view table="{string}" bootstrap_style="{boolean}" display_title="{boolean}" display_search="{boolean}" display_list_num="{boolean}" enable_sort="{boolean}" exclude_cols="{string[,string,...]}" add_class="{string[ string...]}" &#93;_

指定したテーブルのデータ一覧（HTMLコンテンツ）を出力します。

#### <i class="fa fa-asterisk"></i> 属性
| 属性名（attribute） | 値（value） | 必須（require） | 説明（description） |
|:------------------|:-----------|:--------------:|:-------------------|
| table | string | ○ | CDBTプラグインで管理しているテーブル名を指定します。 |
| bootstrap_style | boolean | - | TwitterBootstrap3でスタイリングしない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| display_title | boolean | - | タイトル欄を表示しない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| display_search | boolean | - | データ検索欄を表示しない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| display_list_num | boolean | - | データの行番号を表示しない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| enable_sort | boolean | - | ソート機能を有効化しない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| exclude_cols | string | - | 表示しない項目（カラム）名を指定します。複数ある場合は、カンマ区切りで指定します。 |
| add_class | string | - | データ一覧が出力する`table`タグに追加するクラス名を指定します。複数指定したい場合は半角空白区切りで指定します。 |

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |
| 1.1.4 | **enable_sort** の属性を追加し、データ列のソート機能がON/OFFできるようになった |

#### <i class="fa fa-code"></i> 使用例
```
[cdbt-view table="sample_table" bootstrap_style="false" display_title="false" display_search="false" display_list_num="false" enable_sort="false" exclude_cols="created,updated" add_class="my-list-style"]
```


<div doc-label="specification"></div>
<a id="shortcode-cdbt-entry" name="shortcode-cdbt-entry"></a>
### &#91;cdbt-entry&#93;
　 _&#91;cdbt-entry table="{string}" bootstrap_style="{boolean}" display_title="{boolean}" hidden_cols="{string[,string,...]}" add_class="{string[ string...]}" &#93;_

指定したテーブルのデータ登録用フォーム（HTMLコンテンツ）を出力します。

#### <i class="fa fa-asterisk"></i> 属性
| 属性名（attribute） | 値（value） | 必須（require） | 説明（description） |
|:------------------|:-----------|:--------------:|:-------------------|
| table | string | ○ | CDBTプラグインで管理しているテーブル名を指定します。 |
| bootstrap_style | boolean | - | TwitterBootstrap3でスタイリングしない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| display_title | boolean | - | タイトル欄を表示しない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| hidden_cols | string | - | 入力フォームを非表示化する項目（カラム）名を指定します。複数ある場合は、カンマ区切りで指定します。 |
| add_class | string | - | データ登録用フォームを出力する親の`div`タグに追加するクラス名を指定します。複数指定したい場合は半角空白区切りで指定します。 |

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
[cdbt-entry table="sample_table" bootstrap_style="false" display_title="false" hidden_cols="ID,created,updated" add_class="my-entry-style"]
```



<div doc-label="specification"></div>
<a id="shortcode-cdbt-edit" name="shortcode-cdbt-edit"></a>
### &#91;cdbt-edit&#93;
　 _&#91;cdbt-edit table="{string}" entry_page="{mixed(int|string)}" bootstrap_style="{boolean}" display_title="{boolean}" enable_sort="{boolean}" exclude_cols="{string[,string,...]}" add_class="{string[ string...]}"&#93;_

指定したテーブルのデータ編集用（HTMLコンテンツ）を出力します。

#### <i class="fa fa-asterisk"></i> 属性
| 属性名（attribute） | 値（value） | 必須（require） | 説明（description） |
|:------------------|:-----------|:--------------:|:-------------------|
| table | string | ○ | CDBTプラグインで管理しているテーブル名を指定します。 |
| entry_page | mixed | ○ | テーブルのデータ登録用フォームのショートコードを設置した投稿や固定ページの投稿ID（数値）か投稿名（スラッグ文字列）を指定します。 |
| bootstrap_style | boolean | - | TwitterBootstrap3でスタイリングしない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| display_title | boolean | - | タイトル欄を表示しない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| display_list_num | boolean | - | データの行番号を表示しない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| enable_sort | boolean | - | ソート機能を有効化しない場合は`FALSE`を指定します。省略時は`TRUE`が指定されます。 |
| exclude_cols | string | - | 表示しない項目（カラム）名を指定します。複数ある場合は、カンマ区切りで指定します。 |
| add_class | string | - | データ登録用フォームを出力する親の`div`タグに追加するクラス名を指定します。複数指定したい場合は半角空白区切りで指定します。 |

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |
| 1.1.4 | **enable_sort** の属性を追加し、データ列のソート機能がON/OFFできるようになった<br> **entry_page** に固定ページの`post_name`を指定した際に編集ができなかった不具合を修正 |

#### <i class="fa fa-code"></i> 使用例
 &#91;cdbt-entry&#93; のショートコードが投稿ID=6に設置してある場合
```
[cdbt-edit table="sample_table" entry_page="6" bootstrap_style="false" display_title="false" display_list_num="false" enable_sort="false" exclude_cols="ID,updated" add_class="my-edit-style"]
```

