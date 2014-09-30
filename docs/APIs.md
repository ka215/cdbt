<div doc-label="specification"></div>
本項では、CDBTプラグインが有効化されているWordPressのサイトで利用可能になる関数群（API）を解説します。
これらのAPI関数はCDBTコアクラスに依存するメソッドとは違い、独立して利用することが可能です。ただし、対象のテーブルを指定する関数については、CDBTプラグインにて作成したテーブル以外を指定するとエラーとなる可能性が高いため、原則としてAPI関数はCDBTプラグインで作成したテーブルに対して利用されることをお奨めします。

### API一覧

* <a href="/cdbt/documentation/apis/cdbt_create_pagination">cdbt_create_pagination()</a> ─ ページネーションを自動生成して出力します。
* <a href="/cdbt/documentation/apis/cdbt_current_user_level">cdbt_current_user_level()</a> ─ 現在アクセスしているユーザーの権限レベルを取得する。
* <a href="/cdbt/documentation/apis/cdbt_check_current_table_role">cdbt_check_current_table_role()</a> ─ 現在アクセスしているユーザーの所属するロールを確認する。
* <a href="/cdbt/documentation/apis/cdbt_check_current_table_valid">cdbt_check_current_table_valid()</a> ─ 指定のテーブルが有効かどうかを調べます。
* <a href="/cdbt/documentation/apis/cdbt_get_options_table">cdbt_get_options_table()</a> ─ 指定のテーブルのオプション設定内容をすべて取得します。
* <a href="/cdbt/documentation/apis/cdbt_create_console_menu">cdbt_create_console_menu()</a> ─ ヘッダー用のコンソールメニュー欄を出力します。
* <a href="/cdbt/documentation/apis/cdbt_create_console_footer">cdbt_create_console_footer()</a> ─ フッター用のコンソールボタンとモーダルウィンドウ用の定義を出力します。
* <a href="/cdbt/documentation/apis/cdbt_create_form">cdbt_create_form()</a> ─ 指定のテーブルへの登録フォーム（HTML）を自動生成します。
* <a href="/cdbt/documentation/apis/cdbt_create_button">cdbt_create_button()</a> ─ TwitterBootstrap3準拠スタイルのボタンオブジェクト（HTML）を生成します。
* <a href="/cdbt/documentation/apis/cdbt_str_truncate">cdbt_str_truncate()</a> ─ 指定した文字数で切捨てられた文字列を取得します。
* <a href="/cdbt/documentation/apis/cdbt_compare_var">cdbt_compare_var()</a> ─ 与えられた二つの変数を比較します。
* <a href="/cdbt/documentation/apis/cdbt_get_boolean">cdbt_get_boolean()</a> ─ 与えられた文字列の真偽値（ブーリアン値）を取得します（PHPの`boolval()`の代替関数）。
* <a href="/cdbt/documentation/apis/cdbt__">cdbt__()</a> ─ 翻訳ファイルにて翻訳された文字列を取得します。
* <a href="/cdbt/documentation/apis/cdbt_e">cdbt_e()</a> ─ 翻訳ファイルにて翻訳された文字列を出力します。



<div doc-label="specification"></div>
<a id="api-cdbt_create_pagination" name="api-cdbt_create_pagination"></a>
### <i class="fa fa-code blue"></i> cdbt_create_pagination()

　 _cdbt_create_pagination( int $page_num, int $per_page, int $total_data[, string $mode] )_


ページネーションを自動生成して出力します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$page_num**
> 数値として現在のページ番号 **page_num** を指定します。
> **page_num** の指定がない場合は、

**$per_page**
> 数値としてページ内に表示する最大データ数 **per_page** を指定します。
> **per_page** の指定がない場合は、

**$total_data**
> 数値として表示するテーブルのデータ数（行数） **total_data** を指定します。
> **total_data** の指定がない場合は、

**$mode**
> 文字列として表示ページの種別 **mode** を指定します。
> ページ種別には2種類あり、テーブル内のデータ閲覧ページである`list`か編集ページである`edit`のどちらかです。**mode** の指定がない場合は、`list`として扱われます。

#### <i class="fa fa-asterisk"></i> 返り値
**string**
ページネーションのHTMLドキュメントを文字列として返します。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php

```


<div doc-label="specification"></div>
<a id="api-cdbt_current_user_level" name="api-cdbt_current_user_level"></a>
### cdbt_current_user_level()
　 _cdbt_current_user_level()_

現在ログイン中のユーザーの権限レベル(0~9)を取得します。

#### <i class="fa fa-asterisk"></i> パラメータ
なし

#### <i class="fa fa-asterisk"></i> 返り値
**int**
ユーザーレベルを数値として返します。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```

<div doc-label="specification"></div>
<a id="api-cdbt_check_current_table_role" name="api-cdbt_check_current_table_role"></a>
### cdbt_check_current_table_role()
　 _cdbt_check_current_table_role( string $mode[, string $table_name] )_

現在ログイン中のユーザーが指定されたテーブルにアクセスできる権限があるかを調べます。
 
#### <i class="fa fa-asterisk"></i> パラメータ
**$mode**
> 

**$table_name**
> default: null

#### <i class="fa fa-asterisk"></i> 返り値
**boolean**
結果を真偽値（ブーリアン値）として返します。権限があれば **TRUE** 、なければ **FALSE** となります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt_check_current_table_valid" name="api-cdbt_check_current_table_valid"></a>
### cdbt_check_current_table_valid()
　 _cdbt_check_current_table_valid( [string $table_name] )_

指定のテーブルが有効かどうかを調べます。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> default : null

#### <i class="fa fa-asterisk"></i> 返り値
**boolean**
結果を真偽値（ブーリアン値）として返します。有効であれば **TRUE** 、無効であれば **FALSE** となります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt_get_options_table" name="api-cdbt_get_options_table"></a>
### cdbt_get_options_table()
　 _cdbt_get_options_table( [string $table_name] )_

指定のテーブルのオプション設定内容をすべて取得します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> default : null

#### <i class="fa fa-asterisk"></i> 返り値
**mixed**
オプション設定がある場合は、設定内容を配列として返します。なければ **FALSE** となります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```

<div doc-label="specification"></div>
<a id="api-cdbt_create_console_menu" name="api-cdbt_create_console_menu"></a>
### cdbt_create_console_menu()
　 _cdbt_create_console_menu( [string $nonce] )_

ヘッダー用のコンソールメニュー欄を出力します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$nonce**
> 

#### <i class="fa fa-asterisk"></i> 返り値
**void**
なし

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt_create_console_footer" name="api-cdbt_create_console_footer"></a>
### cdbt_create_console_footer()
　 _cdbt_create_console_footer( string $message, boolean $run, string $run_label, string $hidden_callback )_

フッター用のコンソールボタンとモーダルウィンドウ用の定義を出力します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$message**
> default : null

**$run**
> default : false

**$run_label**
> default : null

**$hidden_callback**
> default : null

#### <i class="fa fa-asterisk"></i> 返り値
**void**
なし

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt_create_form" name="api-cdbt_create_form"></a>
### cdbt_create_form()
　 _cdbt_create_form( string $table_name, string $column_name, array $column_schema, string $value[, $option] )_

テーブルのフィールド構造（カラム形式）を基にして入力フォーム（HTML）を自動生成します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> must containing prefix of table

**$culumn_name**
> 

**$culumn_schema**
> 

**$value**
> 

**$option**
> be hidden form

#### <i class="fa fa-asterisk"></i> 返り値
**string**
> eq. html document

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt_create_button" name="api-cdbt_create_button"></a>
### cdbt_create_button()
　 _cdbt_create_button( string $btn_type, mixed $btn_value[, string $btn_id][, string $btn_class][, string $btn_action][, string $prefix_icon] )_

TwitterBootstrap3準拠スタイルのボタンオブジェクト（HTML）を生成します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$btn_type**
> default : `button`

**$btn_value**
> If $btn_type is "stateful", second arg in array is used for string that will change after clicked button.

**$btn_id**
> eq. id attribute value in button tag

**$btn_class**
> default : `default` (eq. class attribute value of "btn-*" in button tag)

**$btn_action**
> eq. data-action attribute value in button tag

**$prefix_icon**
> eq. value of "glyphicon-*" of the bootstrap

#### <i class="fa fa-asterisk"></i> 返り値
**string**
> eq. html document

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt_str_truncate" name="api-cdbt_str_truncate"></a>
### cdbt_str_truncate()
　 _cdbt_str_truncate( string $string, int $length[, string $suffix][, boolean $collapse] )_

指定した文字数で切捨てられた文字列を取得します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$string**
> 

**$length**
> default : 40

**$suffix**
> default : "..."

**$collapse**
> default : `false`

#### <i class="fa fa-asterisk"></i> 返り値
**string**
> 

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt_compare_var" name="api-cdbt_compare_var"></a>
### cdbt_compare_var()
　 _cdbt_compare_var( mixed $var, mixed $compare )_

与えられた二つの変数を比較します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$var**
> mixed(string|int|boolean)

**$compare**
> mixed(string|int|boolean)
> default : null

#### <i class="fa fa-asterisk"></i> 返り値
**boolean**
> 
 
#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt_get_boolean" name="api-cdbt_get_boolean"></a>
### cdbt_get_boolean()
　 _cdbt_get_boolean( mixed $string )_

与えられた文字列の真偽値（ブーリアン値）を取得します（`boolval()`の代替関数）。

#### <i class="fa fa-asterisk"></i> パラメータ
**$string**
> 

#### <i class="fa fa-asterisk"></i> 返り値
**boolean**
> 

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```


<div doc-label="specification"></div>
<a id="api-cdbt__" name="api-cdbt__"></a>
### cdbt__()
　 _cdbt__( string $string )_

翻訳ファイルにて翻訳された文字列を取得します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$string**
> 

#### <i class="fa fa-asterisk"></i> 返り値
**string**
> 

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```

<div doc-label="specification"></div>
<a id="api-cdbt_e" name="api-cdbt_e"></a>
### cdbt_e()
　 _cdbt_e( string $string )_

翻訳ファイルにて翻訳された文字列を出力します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$string**
> 

#### <i class="fa fa-asterisk"></i> 返り値
**void**
> 

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
```

