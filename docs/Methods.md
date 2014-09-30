<div doc-label="specification"></div>
本項では、CDBTプラグインのコアライブラリである`CustomDataBaseTables`クラスのメソッド関数を解説します。
`CustomDataBaseTables`クラスは`$cdbt`としてグローバル宣言されているため、WordPressテーマのテンプレートからグローバル変数として参照することが可能です。
利用例：
```
global $cdbt;
$cdbt->check_table_exists("prefix_table_name");
```

### メソッド一覧

* <a href="#method-check_table_exists"> **check_table_exists()** </a> ─ テーブルが存在するか確認する。
* <a href="#method-truncate_table"> **truncate_table()** </a> ─ テーブルを初期化する。
* <a href="#method-drop_table"> **drop_table()** </a> ─ テーブルを削除する。
* <a href="#method-create_table"> **create_table()** </a> ─ テーブルを作成する。
* <a href="#method-get_table_schema"> **get_table_schema()** </a> ─ テーブルスキーマ情報を取得する。
* <a href="#method-get_table_comment"> **get_table_comment()** </a> ─ テーブルコメントを取得する。
* <a href="#method-get_create_table_sql"> **get_create_table_sql()** </a> ─ テーブル作成SQLを取得する。
* <a href="#method-get_data"> **get_data()** </a> ─ テーブルから条件に一致するデータを取得する（完全一致）。
* <a href="#method-find_data"> **find_data()** </a> ─ テーブルから条件に該当するデータを取得する（部分一致）。
* <a href="#method-insert_data"> **insert_data()** </a> ─ テーブルにデータを挿入する。
* <a href="#method-update_data"> **update_data()** </a> ─ テーブルのデータを更新する。
* <a href="#method-delete_data"> **delete_data()** </a> ─ テーブルのデータを削除する。
* <a href="#method-validate_data"> **validate_data()** </a> ─ 任意のデータがカラムスキーマに準拠しているか検証する。
* <a href="#method-validate_create_sql"> **validate_create_sql()** </a> ─ テーブル作成SQLを検証し、最適化する。
* <a href="#method-validate_alter_sql"> **validate_alter_sql()** </a> ─ テーブル構造変更SQLを検証し、最適化する。
* <a href="#method-compare_reservation_tables"> **compare_reservation_tables()** </a> ─ WordPressの予約テーブルとテーブル名を比較する。
* <a href="#method-import_table"> **import_table()** </a> ─ テーブルにデータをインポートする。
* <a href="#method-export_table"> **export_table()** </a> ─ テーブルからデータをエクスポートする。

 

<a id="method-check_table_exists" name="method-check_table_exists"></a>
### <i class="fa fa-code blue"></i> check_table_exists()
　 _check_table_exists( [string $table_name] )_

テーブルが存在するか確認します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列として存在確認をしたいテーブル名 **table_name** を指定します。
> **table_name** の指定がない場合は、CDBT管理コンソールで現在選択されているテーブルが指定されます。

#### <i class="fa fa-asterisk"></i> 返り値
テーブルが存在する場合、テーブル名の文字列を返します。テーブルが存在しなかった場合は **NULL** が返ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
if ( $cdbt->check_table_exists($table_name) ) {
    echo "$table_name is exists.";
} else {
    echo "$table_name is not found.";
}
```

<a id="method-truncate_table" name="method-truncate_table"></a>
### <i class="fa fa-code blue"></i> truncate_table()
　 _truncate_table( [string $table_name] )_

テーブルを初期化します（テーブルに含まれるデータを全て削除し、オートインクリメント型のフィールドの開始値を初期値に戻します）。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列として初期化を行いたいテーブル名 **table_name** を指定します。
> **table_name** の指定がない場合は、CDBT管理コンソールで現在選択されているテーブルが指定されます。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
テーブル初期化処理の結果を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。正常にトランケートされた場合は **TRUE** が、トランケートに失敗したり、対象テーブルが存在しなかった場合は **FALSE** が入ります。第2要素には、処理ステータスのメッセージ文字列が入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$result = $cdbt->truncate_table($table_name);
echo $result[1];
```

<a id="method-drop_table" name="method-drop_table"></a>
### <i class="fa fa-code blue"></i> drop_table()
　 _drop_table( [string $table_name] )_

テーブルを削除します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列として削除したいテーブル名 **table_name** を指定します。
> **table_name** の指定がない場合は、CDBT管理コンソールで現在選択されているテーブルが指定されます。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
テーブル削除処理の結果を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。正常にドロップされた場合は **TRUE** が、ドロップに失敗したり、対象テーブルが存在しなかった場合は **FALSE** が入ります。第2要素には、処理ステータスのメッセージ文字列が入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$result = $cdbt->drop_table($table_name);
echo $result[1];
```

<a id="method-create_table" name="method-create_table"></a>
### <i class="fa fa-code blue"></i> create_table()
　 _create_table( array $table_data )_

テーブルを作成します。内部処理としてテーブルの存在確認を行うため、事前に **[check_table_exists()](./#method-check_table_exists)** を行う必要はありません。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_data**
> 作成するテーブルの`CREATE TABLE`のSQL文を定義した **sql** キーを持つ配列を指定します。
> **table_data** の指定がない、もしくは **table_data** 内に`CREATE TABLE`のSQL文定義要素である **sql** キーがない場合はエラーとなります。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
テーブル作成処理の結果を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。正常にテーブルが作成された場合は **TRUE** が、テーブル作成に失敗したり、作成対象テーブルがすでに存在している場合などは **FALSE** が入ります。第2要素には、処理ステータスのメッセージ文字列が入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |
| 1.1.0 | 内部処理のテーブル存在確認部分のバグを修正 |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$new_table_data = array(
  "table_name" => "new_table", 
  "sql" => "CREATE TABLE new_table (
      `ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID', 
      `account_name` varchar(200) NOT NULL COMMENT 'Account Name', 
      `gender` enum('male','female') NOT NULL COMMENT 'Gender', 
      `birth_year` year(4) COMMENT 'Birth year', 
      `birth_month` int(2) unsigned COMMENT 'Birth month', 
      `birth_day` int(2) unsigned COMMENT 'Birth day', 
      `password` varchar(100) NOT NULL COMMENT 'Password', 
      `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Created date', 
      `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated date', 
      PRIMARY KEY (`ID`), 
      INDEX index_1 (`account_name`)
    ) 
    ENGINE=InnoDB 
    DEFAULT CHARSET=utf8 
    COMMENT='New sample table' ;"
);
$result = $cdbt->create_table($new_table_data);
echo $result[1];
```

<a id="method-get_table_schema" name="method-get_table_schema"></a>
### <i class="fa fa-code blue"></i> get_table_schema()
　 _get_table_schema( [string $table_name] )_

テーブル構造（スキーマ情報）を取得します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列としてスキーマ情報を取得したいテーブル名 **table_name** を指定します。
> **table_name** の指定がない場合は、CDBT管理コンソールで現在選択されているテーブルが指定されます。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string[, array] )**
スキーマ情報を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。スキーマ情報が正常に取得された場合は **TRUE** が、対象テーブルが存在しなかった場合は **FALSE** が入ります。第2要素には、正常処理時にはテーブル名が返り、エラーの時は処理ステータスのメッセージ文字列が入ります。正常処理時のみ第3要素が返ります。第3要素はスキーマ情報の配列となります（下記参照）。スキーマ情報配列はテーブル内の各カラムの構成情報配列をネストしています。
> [2] => array(
>   "<カラム名>" => array(
>     "logical_name" => string 論理名（カラムコメント）, 
>     "max_length" => int 最大文字数, 
>     "octet_length" => int 最大バイト数, 
>     "not_null" => boolean NULL不可の時 **TRUE** , 
>     "default" => string デフォルト値（ない場合は **NULL** ）, 
>     "type" => string カラムタイプ, 
>     "type_format" => string カラムタイプ書式, 
>     "primary_key" => boolean プライマリキーかどうか, 
>     "column_key" => string カラムキー（ない場合は **""** ）, 
>     "unsigned" => boolean UNSIGNED属性があるかどうか, 
>     "extra" => string その他のオプション 
>   ),
>   ... 
> )

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$result = $cdbt->get_table_schema($table_name);
if ($result[0]) {
    printf("%s.tbl has %d columns.\n", $result[1], count($result[2]));
    foreach ($result[2] as $column_name => $column_schema) {
        echo "$column_name : {$column_schema['type_format']}\n";
    }
}
```

<a id="method-get_table_comment" name="method-get_table_comment"></a>
### <i class="fa fa-code blue"></i> get_table_comment()
　 _get_table_comment( [string $table_name] )_

テーブルコメントを取得します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列としてテーブルコメントを取得したいテーブル名 **table_name** を指定します。
> **table_name** の指定がない場合は、CDBT管理コンソールで現在選択されているテーブルが指定されます。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
テーブルコメントの文字列を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。テーブルコメントが正常に取得された場合は **TRUE** 、テーブルコメントが未定義だったりテーブルが存在しなかった場合は **FALSE** が入ります。第2要素には、正常処理時にはテーブルコメントの文字列が返り、それ以外の時は処理ステータスのメッセージ文字列が入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$result = $cdbt->get_table_comment($table_name);
if ($result[0]) {
    echo "This table is {$result[1]}.\n";
}
```

<a id="method-get_create_table_sql" name="method-get_create_table_sql"></a>
### <i class="fa fa-code blue"></i> get_create_table_sql()
　 _get_create_table_sql( [string $table_name] )_

テーブル作成SQL（CREATE TABLE）文を取得します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列としてテーブル作成SQLを取得したいテーブル名 **table_name** を指定します。
> **table_name** の指定がない場合は、CDBT管理コンソールで現在選択されているテーブルが指定されます。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
テーブル作成SQLの文字列を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。テーブル作成SQLが正常に取得された場合は **TRUE** 、テーブルが存在しなかった場合は **FALSE** が入ります。第2要素には、正常処理時にはテーブル作成SQLの文字列が返り、それ以外の時は処理ステータスのメッセージ文字列が入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$result = $cdbt->get_create_table_sql($table_name);
if ($result[0]) {
    echo $result[1];
}
```

<a id="method-get_data" name="method-get_data"></a>
### <i class="fa fa-code blue"></i> get_data()
　 _get_data( string $table_name[, mixed $columns][, array $conditions][, array $order][, int $limit][, int $offset] )_

テーブルから条件に該当するデータを取得します。絞り込み条件と一致したデータのみが取得されます。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列としてデータを取得したいテーブル名 **table_name** を指定します。

**$columns**
> 検索対象となるカラム名の配列を指定します。この引数はSQLにおけるSELECT句となります。指定しなかった場合は全てのカラムを対象とするワイルドカードの文字列 **"*"** が指定されます。テーブル内のデータ数をカウントする時などは **”COUNT(*)”** を指定します。

**$conditions**
> 検索対象となるデータの絞り込み条件の配列を指定します。この引数はSQLにおけるWHERE句となります。指定しなかった場合は絞り込みは行われません。また`LIKE`条件による部分一致はできず、完全一致での絞り込みのみをサポートしています。定義配列はカラム名がキーで、検索値が値となります。

**$order**
> 検索対象データのソート順の配列を指定します。この引数はSQLにおけるORDER BY句となります。指定しなかった場合、検索対象データは登録日時のカラム`created`にて降順にソートされてから検索されます。定義配列はカラム名をキーとして、値は降順（`DESC`）か昇順（`ASC`）のどちらかです。無効な文字列を指定すると降順となります。

**$limit**
> 検索対象データの最大取得行数を数値で指定します。検索対象データは **conditions** の条件で絞り込まれ、 **order** でソートされたデータとなります。この引数はSQLにおけるLIMIT句と同じ意味を持ちますが、開始位置と取得数を同時に指定することはできません（開始位置を指定する場合は後述の **offset** を使います）。また、0は指定できません。

**$offset**
> 検索対象データの取得開始行を数値で指定します。検索対象データは **conditions** の条件で絞り込まれ、 **order** でソートされたデータとなります。この引数はSQLにおけるOFFSET句と同じです。

#### <i class="fa fa-asterisk"></i> 返り値
**array( [object(stdClass), ] )**
テーブル検索結果のオブジェクト配列を返します。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
テーブル内の全データ数をカウントする
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$result = $cdbt->get_data($table_name, 'count(*)');
$result = get_object_vars($result[0]);
if (is_array($result)) {
	printf("The %s.tbl has total %d data.", $table_name, intval($result['count(*)']));
}
```

テーブル内の条件に一致する行（レコード）の特定カラムのデータのみを取得する
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$result = $cdbt->get_data($table_name, array('account_name'), array('gender'=>'male'));
$male_account_list = [];
foreach ($result as $data) {
	$male_account_list[] = $data->account_name;
}
printf("Number of male account is %d.", count($male_account_list));
```

テーブル内を特定カラムの値でソートし、条件に一致する行（レコード）から開始位置と取得数を指定してデータを取得する
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$result = $cdbt->get_data($table_name, '', array('gender'=>'male'), array('ID'=>'ASC'), 20, 20);
foreach ($result as $data) {
	printf("ID: %d, AccountName: %s, Gender: %s<br>\n", $data->ID, $data->account_name, $data->gender);
}
```


<a id="method-find_data" name="method-find_data"></a>
### <i class="fa fa-code blue"></i> find_data()
　 _find_data( string $table_name, array $table_schema, string $search_key[, array $columns][, array $order][, int $limit][, int $offset] )_

テーブルから条件に該当するデータを探し出します。絞り込み条件に指定されたキーを部分的に含むデータをすべて取得します。
このメソッドは、引数として与えられたテーブルスキーマ情報より、検索キーワードが含まれると想定されるカラムのデータ型を自動選定して最適なデータを照会・取得することができます（内部処理的には自動選定された各カラムへの`SELECT`文を`UNION`句で連結したクエリが発行されます）。この場合、テーブル内の全フィールドを検索対象としないため、テーブルスキーマ情報を与えられなかった時よりもパフォーマンスが良いです。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列としてデータを取得したいテーブル名 **table_name** を指定します。

**$table_schema**
> テーブルスキーマ情報が格納された配列を指定します。テーブルスキーマ情報は **[get_table_schema()](./#method-get_table_schema)** メソッドにて取得します。

**$columns**
> 検索対象となるカラム名の配列を指定します。この引数はSQLにおけるSELECT句となります。指定しなかった場合は全てのカラムを対象とするワイルドカードの文字列 **"*"** が指定されます。テーブル内のデータ数をカウントする時などは **”COUNT(*)”** を指定します。

**$conditions**
> 検索対象となるデータの絞り込み条件の配列を指定します。この引数はSQLにおけるWHERE句となります。指定しなかった場合は絞り込みは行われません。またこのメソッドでの検索は`LIKE`条件による部分一致のみとなり、完全一致での絞り込みはサポートされません。完全一致でのデータ検索には **[get_data()](./#method-get_data)** メソッドを使ってください。定義配列はカラム名がキーで、検索値が値となります。

**$order**
> 検索対象データのソート順の配列を指定します。この引数はSQLにおけるORDER BY句となります。指定しなかった場合、検索対象データは登録日時のカラム`created`にて降順にソートされてから検索されます。定義配列はカラム名をキーとして、値は降順（`DESC`）か昇順（`ASC`）のどちらかです。無効な文字列を指定すると降順となります。

**$limit**
> 検索対象データの最大取得行数を数値で指定します。検索対象データは **conditions** の条件で絞り込まれ、 **order** でソートされたデータとなります。この引数はSQLにおけるLIMIT句と同じ意味を持ちますが、開始位置と取得数を同時に指定することはできません（開始位置を指定する場合は後述の **offset** を使います）。また、0は指定できません。

**$offset**
> 検索対象データの取得開始行を数値で指定します。検索対象データは **conditions** の条件で絞り込まれ、 **order** でソートされたデータとなります。この引数はSQLにおけるOFFSET句と同じです。

#### <i class="fa fa-asterisk"></i> 返り値
**array( [object(stdClass), ] )**
テーブル検索結果のオブジェクト配列を返します。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
list($result, , $table_schema) = $cdbt->get_table_schema($table_name);
if ($result && !empty($table_schema)) {
	$data = $cdbt->find_data($table_name, $table_schema, array('account_name'), array('gender'=>'male'));
} else {
	$data = $cdbt->find_data($table_name, null, array('account_name'), array('gender'=>'male'));
}
$hits_account_list = [];
foreach ($data as $one_data) {
	$hits_account_list[] = $one_data->account_name;
}
printf("Number of account is %d.", count($male_account_list));
```


<a id="method-insert_data" name="method-insert_data"></a>
### <i class="fa fa-code blue"></i> insert_data()
　 _insert_data( string $table_name, array $data[, array $table_schema])_

テーブルにデータを追加（挿入）します。実体はWordPressコアクラスのメソッドである`$wpdb->insert()`のラッパーであり、メソッドの仕様もそれに準じます。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列としてデータを追加したいテーブル名 **table_name** を指定します。

**$data**
> 追加するデータ（カラム名=>値のペア）の配列として指定します。カラム名と値のデータの両方がSQLセーフなエスケープされた値である必要があります。
> なお、CDBTプラグインで作成されたテーブルに自動付与される`ID`、`created`、`updated`のカラムに対して値を指定しても無視され、データの挿入時にプライマリキーである`ID`にはオートインクリメントにより自動採番が行われ、`created`にはこのメソッドを実行した時点での日時が自動挿入されます。その際、`updated`の値は破棄されます。データ挿入後は生成された`ID`を使ってデータにアクセスすることができます。

**$table_schema**
> データを追加するテーブルのテーブルスキーマ情報の配列を指定します。テーブルスキーマ情報は **[get_table_schema()](./#method-get_table_schema)** メソッドにて取得します。このパラメータは必須ではありませんが、指定されると、追加するデータの値についてテーブルのカラム型に応じたマッピングが行われます。省略された場合は、WordPressの`wpdb::$field_types`で指定されていない限り、文字列として扱われます。

#### <i class="fa fa-asterisk"></i> 返り値
データが追加できなかった場合、 **FALSE** を返します。データが正常に追加された場合、追加したデータのプライマリキーである`ID`の値を返します。これは、WordPressコアクラスのメソッド`$wpdb->insert_id`の結果と同一です。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |
| 1.1.0 | データが追加できなかった場合の返り値が不正だったのを修正 |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
list($result, , $table_schema) = $cdbt->get_table_schema($table_name);
if ($result && !empty($table_schema)) {
	$id = $cdbt->insert_data($table_name, array('account_name' => 'john', 'gender' => 'male'), $table_schema);
} else {
	$id = $cdbt->insert_data($table_name, array('account_name' => 'john', 'gender' => 'male'));
}
echo $id;
```

<a id="method-update_data" name="method-update_data"></a>
### <i class="fa fa-code blue"></i> update_data()
　 _update_data( string $table_name, int $ID, array $data[, array $table_schema])_

テーブルの指定のデータを更新します。実体はWordPressコアクラスのメソッドである`$wpdb->update()`のラッパーであり、メソッドの仕様もそれに準じます。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列としてデータを更新したいテーブル名 **table_name** を指定します。

**$ID**
> 数値として更新したいデータのプライマリキー`ID`の値を指定します。

**$data**
> 追加するデータ（カラム名=>値のペア）の配列として指定します。カラム名と値のデータの両方がSQLセーフなエスケープされた値である必要があります。
> なお、CDBTプラグインで作成されたテーブルに自動付与される`ID`、`created`、`updated`のカラムに対して値を指定しても無視され、データの挿入時には破棄されます。

**$table_schema**
> データを追加するテーブルのテーブルスキーマ情報の配列を指定します。テーブルスキーマ情報は **[get_table_schema()](./#method-get_table_schema)** メソッドにて取得します。このパラメータは必須ではありませんが、指定されると、追加するデータの値についてテーブルのカラム型に応じたマッピングが行われます。省略された場合は、WordPressの`wpdb::$field_types`で指定されていない限り、文字列として扱われます。

#### <i class="fa fa-asterisk"></i> 返り値
データが更新できなかった場合、 **FALSE** を返します。データが正常に更新された場合、更新したデータのプライマリキーである`ID`の値を返します。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | パラメータの不備によってデータが更新できなかった場合のみ、返り値が`0` |
| 1.1.0 | データが追加できなかった場合の返り値を **FALSE** に統一 |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
list($result, , $table_schema) = $cdbt->get_table_schema($table_name);
if ($result && !empty($table_schema)) {
	$id = $cdbt->update_data($table_name, 1006, array('account_name' => 'maria', 'gender' => 'female'), $table_schema);
} else {
	$id = $cdbt->update_data($table_name, 1006, array('account_name' => 'maria', 'gender' => 'female'));
}
echo $id;
```

<a id="method-delete_data" name="method-delete_data"></a>
### <i class="fa fa-code blue"></i> delete_data()
　 _delete_data( string $table_name, int $ID )_

テーブル内の指定のデータを削除します。実体はWordPressコアクラスのメソッドである`$wpdb->delete()`のラッパーであり、メソッドの仕様もそれに準じますが、削除条件がIDカラム完全一致型なので1行のみの削除しかできません。WHERE句による該当する複数行の削除を行う場合は`$wpdb->delete()`を利用してください。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 文字列としてデータを削除したいテーブル名 **table_name** を指定します。

**$ID**
> 数値として削除したいデータのプライマリキー`ID`の値を指定します。

#### <i class="fa fa-asterisk"></i> 返り値
データが削除できなかった場合、 **0** を返します。データが正常に更新された場合、 **1** を返します。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
echo $cdbt->delete_data($table_name, 1006);
```

<a id="method-validate_data" name="method-validate_data"></a>
### <i class="fa fa-code blue"></i> validate_data()
　 _validate_data( array $column_schema, mixed $data )_

任意のデータがテーブルスキーマで定義されているデータ型に準拠しているかを検証して、その結果を返します。
この関数を使うことで、WEB側のフォーム等から入力された値をデータベースに追加する前に、整合性の検証を行うことができます。

#### <i class="fa fa-asterisk"></i> パラメータ
**$column_schema**
> 検証対象となるデータを格納するカラムのスキーマ情報の配列を指定します。テーブル全体のスキーマ情報ではなく、単一のカラムのスキーマ情報配列になるので注意してください。

**$data**
> 検証したいデータの値を指定します。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
このメソッドは検証処理の結果を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。検証結果が正常だった場合は **TRUE** 、それ以外は **FALSE** が入ります。第2要素には、正常処理時には **NULL** が返り、それ以外の時は検証結果のメッセージ文字列が入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "prefix_tablename";
$data = $_POST['account_name'];
list($result, , $table_schema) = $cdbt->get_table_schema($table_name);
$column_schema = $table_schema['account_name'];
list($status, $message) = $cdbt->validate_data($column_schema, $data);
if ($status) {
	echo $cdbt->insert_data($table_name, array('account_name' => $data), $table_schema);
} else {
	echo $message;
}
```

<a id="method-validate_create_sql" name="method-validate_create_sql"></a>
### <i class="fa fa-code blue"></i> validate_create_sql()
　 _validate_create_sql( string $table_name, string $sql )_

テーブル作成SQL（CREATE TABLE）文の検証と最適化を行います。この関数によって最適化されたテーブル作成SQL文を **[create_table()](./#method-create_table)** メソッドに与えることでデータベース上にテーブルを作成することが可能です[^1]。ただし、現在のところ **[create_table()](./#method-create_table)** で作成したテーブルはCDBTプラグインの管理下におかれないため、CDBT管理コンソールからGUIを経由して利用することはできません（※ 将来のバージョンにて対応する予定です）。

[^1]: 現在のところ、返り値となるSQL文はデータベースオプション部が未完となり、そのまま`create_table()`メソッドにSQLを与えてもエラーになります。このメソッドを使ってテーブルを作成する場合は使用例を参照してください。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 検証対象となるテーブル名を文字列で指定します。テーブル作成SQL文で作成対象のテーブルとして定義されている必要があります。

**$sql**
> 検証したいテーブル作成SQL文を文字列で指定します。この関数におけるテーブル作成SQL文は公式のMySQLにおける完全なSQL文ではなく、CDBTプラグイン専用のSQL文であり、CDBT管理コンソールから入力する形式のSQLと同義です。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
このメソッドは検証・最適化処理の結果を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。検証結果が正常だった場合は **TRUE** 、それ以外は **FALSE** が入ります。第2要素には、正常処理時に最適化されたテーブル作成SQL文が返ります。それ以外の時は **NULL** が返ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "customer_master";
if (!$cdbt->check_table_exists($table_name)) {
	$db_engine = "InnoDB";
	$db_charset = $cdbt->options['charset'];
	$table_comment = "Customer Master";
	$create_table_sql = "CREATE TABLE $table_name (
	  `customer_name` varchar(255) NOT NULL COMMENT 'Costomer Name',
	  `zipcode` mediumint(7) unsigned zerofill DEFAULT NULL COMMENT 'Zipcode',
	  `address` text COMMENT 'Address',
	  `tel_number` varchar(12) DEFAULT NULL COMMENT 'Telephone Number',
	  `url` text COMMENT 'Web site'
	)";
	list($status, $fixed_sql) = $cdbt->validate_create_sql($table_name, $create_table_sql);
	if ($status) {
		$table_data = [ "sql" => sprintf($fixed_sql, $db_engine, $db_charset, $table_comment) ];
		list($result, $message) = $cdbt->create_table($table_data);
		echo $message;
	}
}
```


<a id="method-validate_alter_sql" name="method-validate_alter_sql"></a>
### <i class="fa fa-code blue"></i> validate_alter_sql()
　 _validate_alter_sql( string $table_name, string $sql )_

テーブル構造変更SQL（ALTER TABLE）文の検証と最適化を行います。この関数によって最適化されたテーブル構造変更SQL文を実行するには、WordPressコアクラスのメソッド`$wpdb->query()`を使います。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 検証対象となるテーブル名を文字列で指定します。テーブル構造変更SQL文で変更対象のテーブルとして定義されている必要があります。

**$sql**
> 検証したいテーブル構造変更SQL文を文字列で指定します。この関数におけるテーブル構造変更SQL文は公式のMySQLにおける完全なSQL文ではなく、CDBTプラグイン専用のSQL文であり、CDBT管理コンソールから入力する形式のSQLと同義です。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
このメソッドは検証・最適化処理の結果を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。検証結果が正常だった場合は **TRUE** 、それ以外は **FALSE** が入ります。第2要素には、正常処理時に最適化されたテーブル作成SQL文が返ります。それ以外の時は **NULL** が返ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
特定カラムの直後に新しいカラムを追加する
```
<?php
global $cdbt;
$table_name = "customer_master";
if ($cdbt->check_table_exists($table_name)) {
	$alter_table_sql = "ALTER TABLE $table_name 
	  ADD `description` text DEFAULT NULL COMMENT 'Description' AFTER `url`, 
	";
	list($status, $fixed_sql) = $cdbt->validate_alter_sql($table_name, $alter_table_sql);
	if ($status) {
		$wpdb->query($fixed_sql);
	}
}
```

データベースエンジンを変更する
```
<?php
global $cdbt;
$table_name = "customer_master";
if ($cdbt->check_table_exists($table_name)) {
	$alter_table_sql = "ALTER TABLE $table_name 
	  ENGINE = MyISAM
	";
	list($status, $fixed_sql) = $cdbt->validate_alter_sql($table_name, $alter_table_sql);
	if ($status) {
		$wpdb->query($fixed_sql);
	}
}
```

特定のカラムにインデックスを貼る
```
<?php
global $cdbt;
$table_name = "customer_master";
if ($cdbt->check_table_exists($table_name)) {
	$alter_table_sql = "ALTER TABLE $table_name 
	  ADD FULLTEXT `fulltext_index` (`address`)
	";
	list($status, $fixed_sql) = $cdbt->validate_alter_sql($table_name, $alter_table_sql);
	if ($status) {
		$wpdb->query($fixed_sql);
	}
}
```

<a id="method-compare_reservation_tables" name="method-compare_reservation_tables"></a>
### <i class="fa fa-code blue"></i> compare_reservation_tables()
　 _compare_reservation_tables( string $table_name )_

WordPressの予約テーブルとテーブル名を比較します。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> 比較するテーブル名を文字列で指定します。

#### <i class="fa fa-asterisk"></i> 返り値
比較した結果が **Boolean** 値で返ります。比較するテーブル名がWordPressで予約されているテーブル名と一致した場合は **TRUE** 、それ以外は **FALSE** が入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$table_name = "posts";
if ($cdbt->compare_reservation_tables($table_name)) {
    echo "Table name: $table_name has been reserved by WordPress.";
} else {
    echo "Table name: $table_name is available.";
}
```

<a id="method-import_table" name="method-import_table"></a>
### <i class="fa fa-code blue"></i> import_table()
　 _import_table( string $table_name, array $import_data )_

指定のテーブルに（複数の）データをインポートします。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> インポートするテーブル名を文字列で指定します。

**$import_data**
> インポートするデータを配列で指定します。インポート用のデータ配列は行毎にカラム名=>値の配列を含む連想配列になります。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, string )**
インポート処理の結果を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。インポートが正常に終了した場合は **TRUE** 、それ以外は **FALSE** が入ります。第2要素には、処理内容もしくはエラー内容のメッセージが入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
インポートデータ配列を直接インポートする
```
<?php
global $cdbt;
$import_table = "prefix_tablename";
$import_data = [
  [ "ID" => "", "account_name" => "John", "gender" => "male", "created" => "", "updated" => "" ], 
  [ "ID" => "", "account_name" => "David", "gender" => "male", "created" => "", "updated" => "" ], 
  [ "ID" => "", "account_name" => "Maria", "gender" => "female", "created" => "", "updated" => "" ]
];
list($result, $message) = $cdbt->import_table($import_table, $import_data);
echo $message;
```

アップロードされたCSVファイルからインポートする（≒CDBT管理コンソールのインポート処理）
```
<?php
global $cdbt;
$import_table = "prefix_tablename";
if (preg_match('/^application\/(vnd.ms-excel|octet-stream)$/', $_FILES['csv_file']['type']) && $_FILES['csv_file']['size'] > 0) {
	$data = file_get_contents($_FILES['csv_file']['tmp_name']);
	if (function_exists('mb_convert_encoding')) {
		$data = mb_convert_encoding($data, 'UTF-8', 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1');
	}
	$import_data = array();
	foreach (explode("\n", trim($data)) as $i => $row) {
		$parse_row = explode(',', trim($row));
		if ($i == 0) {
			$index_cols = array();
			foreach ($parse_row as $col_value) {
				$index_cols[] = preg_replace('/^"(.*)"$/iU', '$1', trim($col_value));
			}
		} else {
			$row_data = array();
			foreach ($index_cols as $j => $col_name) {
				$row_data[$col_name] = preg_replace('/^"(.*)"$/iU', '$1', trim($parse_row[$j]));
			}
			$import_data[] = $row_data;
		}
	}
	list($result, $message) = $cdbt->import_table($import_table, $import_data);
	$msg = ($result ? "success" : "warning") .": ". $message;
} else {
	$msg = "warning: Invalid file was uploaded.";
}
unlink($_FILES['csv_file']['tmp_name']);
echo $msg;
```


<a id="method-export_table" name="method-export_table"></a>
### <i class="fa fa-code blue"></i> export_table()
　 _export_table( string $table_name, boolean $index_only )_

指定のテーブルから全データをエクスポートします。

#### <i class="fa fa-asterisk"></i> パラメータ
**$table_name**
> エクスポートするテーブル名を文字列で指定します。

**$index_only**
> インデックス行（カラム名だけの行）のみエクスポートするかどうか。デフォルトは **FALSE** で、インデックス行とデータ行を含んだ全データがエクスポートされます。

#### <i class="fa fa-asterisk"></i> 返り値
**array( bool, mixed )**
エクスポート処理の結果を含む配列を返します。第1要素に処理結果を示す **Boolean** 値が入ります。インポートが正常に終了した場合は **TRUE** 、それ以外は **FALSE** が入ります。第2要素には、エクスポート処理が正常に行われた場合はデータの入った配列が返ります。エラー時はメッセージ文字列が入ります。

#### <i class="fa fa-code-fork"></i> 変更履歴
| バージョン | 内容 |
|:--------:|:-----|
| 1.0.0 | - |

#### <i class="fa fa-code"></i> 使用例
```
<?php
global $cdbt;
$export_table = "prefix_tablename"; 
list($result, $data) = $cdbt->export_table($export_table, false);
if ($result) {
    var_dump($data);
}
```

