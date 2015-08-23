<?php
/**
 * Table Creator Options array `$this->component_options` scheme
 * [
 * 'id' => @string is element id [optional] For default is `cdbtTableCreatorBody`
 * 'targetTable' => @string [optional] 
 * 'columnDefinitions' => @string [optional] 
 * ]
 */

/**
 * Parse options
 * ---------------------------------------------------------------------------
 */

// `id` section
if (isset($this->component_options['id']) && !empty($this->component_options['id'])) {
  $tcb_id = esc_attr__($this->component_options['id']);
} else {
  $tcb_id = 'cdbtTableCreatorBody';
}

// `targetTable` section
if (isset($this->component_options['targetTable']) && !empty($this->component_options['targetTable'])) {
  $tcb_table = esc_attr__($this->component_options['targetTable']);
} else {
  $tcb_table = '';
}

// `columnDefinition` section
if (isset($this->component_options['columnDefinition']) && !empty($this->component_options['columnDefinition'])) {
  $tcb_columns = esc_attr__($this->component_options['columnDefinition']);
} else {
  $tcb_columns = [];
}

/**
 * Render the Modal
 * ---------------------------------------------------------------------------
 */

// translate text
$table_creator_label = __('Table Creator', CDBT);
$tips_message = __('Columns of updated date and created date, and ID column of primary key can not be deleted or edited. You can create a new columns other that. And it will be sorted in drag.', CDBT);
$cancel_close_btn_label = __('Cancel', CDBT);
$set_sql_btn_label = __('Set SQL Statements', CDBT);

$placeholder_column_name = __('column name', CDBT);
$placeholder_length = __('integer', CDBT);
$placeholder_default = __('default', CDBT);
$placeholder_extra = __('extra', CDBT);
$placeholder_comment = __('comment', CDBT);
$value_primary_key = __('ID', CDBT);
$value_created = __('Created Date', CDBT);
$value_updated = __('Updated Date', CDBT);

$index_row_labels = [
  'col_order' => '', 
  'col_name' => __('Column Name', CDBT), 
  'type_format' => __('Type Format', CDBT), 
  'length' => __('Display Length', CDBT), 
  'not_null' => __('Not Null', CDBT), 
  'default' => __('Default Value', CDBT), 
  'attributes' => __('Attributes', CDBT), 
  'autoincrement' => __('Autoincrement', CDBT), 
  'key_index' => __('Key / Index', CDBT), 
  'extra' => __('Extra', CDBT), 
  'comment' => __('Comment', CDBT), 
  'controll' => '', 
];

$index_row_base = '<tr class="index-row ui-state-disabled">%s</tr>';
$index_row_cols = [];
foreach ($index_row_labels as $col_slug => $col_label) {
  $index_row_cols[] = sprintf('<th class="%s">%s</th>', $col_slug, $col_label);
}
$index_row = sprintf($index_row_base, implode("\n", $index_row_cols));

/*
$index_row = sprintf('<li class="index-row"><label class="null"></label><label class="w-xl">%s</label><label>%s</label><label class="w-sm">%s</label><label class="w-xs">%s</label><label>%s</label><label>%s</label><label class="w-xs">%s</label><label>%s</label><label class="w-lg">%s</label><label class="w-xl">%s</label><label class="null"></label></li>', 
	__('column name', CDBT), 
	__('type format', CDBT), 
	__('length', CDBT), 
	__('not null', CDBT), 
	__('default', CDBT), 
	__('attribute', CDBT), 
	__('autoinc.', CDBT), 
	__('key', CDBT), 
	__('extra', CDBT), 
	__('comment', CDBT));
*/

$preset_col_types = [ 
  'tinyint' => [ 'arg_type' => 'precision', 'default' => 4, 'min' => 1, 'max' => 4, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // precision: 精度。数字全体の有効桁数
  'smallint' => [ 'arg_type' => 'precision', 'default' => 6, 'min' => 1, 'max' => 6, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // precision: 精度。数字全体の有効桁数
  'mediumint' => [ 'arg_type' => 'precision', 'default' => 9, 'min' => 1, 'max' => 9, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // precision: 精度。数字全体の有効桁数
  'int' => [ 'arg_type' => 'precision', 'default' => 11, 'min' => 1, 'max' => 11, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [ 'integer' ] ], // precision: 精度。数字全体の有効桁数
  'bigint' => [ 'arg_type' => 'precision', 'default' => 20, 'min' => 1, 'max' => 20, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // precision: 精度。数字全体の有効桁数
  'float' => [ 'arg_type' => [ 'precision', 'scale' ], 'default' => null, 'min' => [ 1, 0 ], 'max' => [ 53, 30 ], 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // 小数部を含んで6桁まで入力された通りに保存する用途であれば、float型を使う
  'double' => [ 'arg_type' => [ 'precision', 'scale' ], 'default' => null, 'min' => [ 1, 0 ], 'max' => [ 53, 30 ], 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [ 'double precision', 'real' ] ], // precisionが25以上のfloat(*)はdoubleと同等
  'decimal' => [ 'arg_type' => [ 'precision', 'scale' ], 'default' => [ 10, 0 ], 'min' => [ 1, 0 ], 'max' => [ 65, 30 ], 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [ 'dec', 'numeric', 'fixed' ] ], // 小数点以下を指定して型を揃えて正確に扱うならば、decimal型を使う （例:緯度経度情報）
  'bool' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [ 'boolean' ] ], // tinyint(1)のエイリアス
  'bit' => [ 'arg_type' => 'precision', 'default' => 1, 'min' => 1, 'max' => 64, 'atts' => null, 'alias' => [] ], // precisionはbitのByte数
  'varchar' => [ 'arg_type' => 'maxlength', 'default' => 1, 'min' => 0, 'max' => 255, 'atts' => [ 'binary' ], 'alias' => [ 'national varchar' ] ], // maxlengthが255より大きい場合はtext型に変換される
  'char' => [ 'arg_type' => 'maxlength', 'default' => 255, 'min' => 0, 'max' => 255, 'atts' => [ 'binary', 'ascii', 'unicode' ], 'alias' => [ 'national char', 'nchar', 'character' ] ], // maxlength省略時はchar(1)となる
  'tinytext' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [] ], // 最大長 255文字
  'text' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [] ], // 最大長 65535文字
  'mediumtext' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [] ], // 最大長 16777215文字
  'longtext' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [] ], // 最大長 4294967295文字
  'tinyblob' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [] ], // 最大長 255Byte
  'blob' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [] ], // 最大長 64KB
  'mediumblob' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [] ], // 最大長 16MB
  'longblob' => [ 'arg_type' => null, 'default' => null, 'min' => null, 'max' => null, 'atts' => null, 'alias' => [] ], // 最大長 4GB
  'binary' => [ 'arg_type' => 'maxlength', 'default' => 255, 'min' => 0, 'max' => 255, 'atts' => null, 'alias' => [ 'char byte' ] ], // 最大長 255Byte、指定バイト数より格納値が少ない場合に末尾を0x00で埋める
  'varbinary' => [ 'arg_type' => 'maxlength', 'default' => 65535, 'min' => 0, 'max' => 65535, 'atts' => null, 'alias' => [] ], // 最大長 64KB、末尾の0x00埋めを行わない
  'enum' => [ 'arg_type' => 'array', 'default' => null, 'min' => 1, 'max' => 65535, 'atts' => null, 'alias' => [] ], // ユニークリスト 65535個まで
  'set' => [ 'arg_type' => 'array', 'default' => null, 'min' => 0, 'max' => 64, 'atts' => null, 'alias' => [] ], // ユニークリスト 64個まで
  'date' => [ 'arg_type' => null, 'default' => null, 'min' => '1000-01-01', 'max' => '9999-12-31', 'atts' => null, 'alias' => [] ], // 'YYYY-MM-DD'形式文字列か数値を使用できる
  'datetime' => [ 'arg_type' => null, 'default' => null, 'min' => '1000-01-01 00:00:00', 'max' => '9999-12-31 23:59:59', 'atts' => null, 'alias' => [] ], // 'YYYY-MM-DD HH:MM:SS'形式文字列か数値を使用できる
  'time' => [ 'arg_type' => null, 'default' => null, 'min' => '-838:59:59', 'max' => '838:59:59', 'atts' => null, 'alias' => [] ], // 'HH:MM:SS'形式文字列か数値を使用できる
  'timestamp' => [ 'arg_type' => [ 6, 8, 12, 14 ], 'default' => null, 'min' => 6, 'max' => 14, 'atts' => [ 'NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' ],  'alias' => [] ], // 引数は表示形式の桁数（'YYMMDD','YYYYMMDD','YYMMDDHHMMSS', 'YYYYMMDDHHMMSS'）を表す
  'year' => [ 'arg_type' => [ 2, 4 ], 'default' => 4, 'min' => 2, 'max' => 4, 'atts' => null, 'alias' => [] ], // 'YYYY'か'YY'形式の文字列か数値を使用できる
];
$col_type_options = '';
foreach($preset_col_types as $_key => $_val) {
  $default_value = is_array($_val['default']) ? implode(',', $_val['default']) : $_val['default'];
  $col_type_options .= sprintf('<option value="%s">%s</option>', $default_value, $_key);
}

$body_row_base = '<tr class="row ui-state-default tbl_cols preset">%s</tr>';
$body_row_cols = <<<EOH
  <td class="col_order">
    <i class="fa fa-arrows-v"></i>
  </td>
  <td class="col_name">
    <input class="form-control input-sm" name="col_name_" type="text" placeholder=".input-sm">
  </td>
  <td class="type_format">
    <select class="form-control input-sm" name="type_format_">$col_type_options</select>
  </td>
  <td class="length">
    <input class="form-control input-sm" name="length_" type="text" placeholder=".input-sm">
  </td>
  <td class="not_null">
    <input class="form-control input-sm" name="not_null_" type="checkbox" value="1">
  </td>
  <td class="default">
    <input class="form-control input-sm" name="default_" type="text" placeholder=".input-sm">
  </td>
  <td class="attributes">
    <select class="form-control input-sm" name="attributes_"><option value="" class="numgrp bingrp"></option><option value="unsigned" class="numgrp">unsigned</option><option value="zerofill" class="numgrp">zerofill</option><option value="binary" class="bingrp">binary</option><option value="ascii" class="bingrp">ascii</option><option value="unicode" class="bingrp">unicode</option></select>
  </td>
  <td class="autoincrement">
    <input class="form-control input-sm" name="autoincrement_" type="checkbox" value="1">
  </td>
  <td class="key_index">
    <select class="form-control input-sm" name="key_index_"><option value=""></option><option value="primary key" disabled="disabled">primary key</option><option value="index">index</option><option value="unique">unique</option><option value="fulltext">fulltext</option><option value="foreign key">foreign key</option></select>
  </td>
  <td class="extra">
    <input class="form-control input-sm" name="extra_" type="text" placeholder=".input-sm">
  </td>
  <td class="comment">
    <input class="form-control input-sm" name="comment_" type="text" placeholder=".input-sm">
  </td>
  <td class="controll">
    <button type="button" name="add-column" id="add-preset-column" class="btn btn-primary btn-sm" title="Add Preset Column"><i class="fa fa-plus"><span class="sr-only">Add Preset Column</span></i></button>
  </td>
EOH;
$body_row = sprintf($body_row_base, $body_row_cols);

/*
$row = <<<EOH
<li class="ui-state-default ui-state-disabled tbl_cols">
	<label class="handler row-index-num num-disabled">1</label>
	<label class="w-xl"><input type="text" name="col_name_pk" value="$value_primary_key" disabled="disabled"></label>
	<label><select name="col_type_pk" disabled="disabled"><option value="int" selected="selected">int</option><option value="timestamp">timestamp</option></select></label>
	<label class="w-sm"><input type="text" name="col_length_pk" value="11" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_pk" value="1" checked="checked" disabled="disabled"></label>
	<label><input type="text" name="col_default_pk" value="" disabled="disabled"></label>
	<label><select name="col_attribute_pk" disabled="disabled"><option value=""></option><option value="unsigned" selected="selected">unsigned</option><option value="zerofill">zerofill</option></select></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_pk" value="1" checked="checked" disabled="disabled"></label>
	<label><select name="col_key_pk" disabled="disabled"><option value="primary key" selected="selected">primary key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_pk" value="" disabled="disabled"></label>
	<label class="w-xl"><input type="text" name="col_comment_pk" value="ID" disabled="disabled"></label>
	<label class="delete-row"><button type="button" name="col_delete_pk" class="btn btn-info btn-sm" disabled="disabled"><span class="glyphicon glyphicon-remove"></span></button></label>
</li>
<li class="ui-state-default tbl_cols preset">
	<label class="handler"><span class="glyphicon glyphicon-edit"></span></label>
	<label class="w-xl"><input type="text" name="col_name_" value="" placeholder="$placeholder_column_name"></label>
	<label><select name="col_type_">$col_type_options</select></label>
	<label class="w-sm"><input type="text" name="col_length_" value="" placeholder="$placeholder_length"></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_" value="1"></label>
	<label><input type="text" name="col_default_" value="" placeholder="$placeholder_default"></label>
	<label><select name="col_attribute_"><option value="" class="numgrp bingrp"></option><option value="unsigned" class="numgrp">unsigned</option><option value="zerofill" class="numgrp">zerofill</option><option value="binary" class="bingrp">binary</option><option value="ascii" class="bingrp">ascii</option><option value="unicode" class="bingrp">unicode</option></select></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_" value="1"></label>
	<label><select name="col_key_"><option value=""></option><option value="primary key" disabled="disabled">primary key</option><option value="index">index</option><option value="unique">unique</option><option value="fulltext">fulltext</option><option value="foreign key">foreign key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_" value="" placeholder="$placeholder_extra"></label>
	<label class="w-xl"><input type="text" name="col_comment_" value="" placeholder="$placeholder_comment"></label>
	<label class="add-row"><button type="button" name="col_add_preset" id="col_add_preset" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-plus"></span></button></label>
</li>
<li class="ui-state-default ui-state-disabled tbl_cols">
	<label class="handler row-index-num num-disabled">3</label>
	<label class="w-xl"><input type="text" name="col_name_cd" value="created" disabled="disabled"></label>
	<label><select name="col_type_cd" disabled="disabled"><option value="datetime" selected="selected">datetime</option><option value="timestamp">timestamp</option></select></label>
	<label class="w-sm"><input type="text" name="col_length_cd" value="" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_cd" value="1" checked="checked" disabled="disabled"></label>
	<label><input type="text" name="col_default_cd" value="0000-00-00 00:00:00" disabled="disabled"></label>
	<label><select name="col_attribute_cd" disabled="disabled"><option value="" selected="selected"></option><option value="unsigned">unsigned</option><option value="zerofill">zerofill</option></select></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_cd" value="1" disabled="disabled"></label>
	<label><select name="col_key_cd" disabled="disabled"><option value=""></option><option value="">primary key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_cd" value="" disabled="disabled"></label>
	<label class="w-xl"><input type="text" name="col_comment_cd" value="$value_created" disabled="disabled"></label>
	<label class="delete-row"><button type="button" name="col_delete_cd" class="btn btn-info btn-sm" disabled="disabled"><span class="glyphicon glyphicon-remove"></span></button></label>
</li>
<li class="ui-state-default ui-state-disabled tbl_cols">
	<label class="handler row-index-num num-disabled">4</label>
	<label class="w-xl"><input type="text" name="col_name_ud" value="updated" disabled="disabled"></label>
	<label><select name="col_type_ud" disabled="disabled"><option value="timestamp" selected="selected">timestamp</option></select></label>
	<label class="w-sm"><input type="text" name="col_length_ud" value="" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_ud" value="1" checked="checked" disabled="disabled"></label>
	<label><input type="text" name="col_default_ud" value="CURRENT_TIMESTAMP" disabled="disabled"></label>
	<label><select name="col_attribute_ud" disabled="disabled"><option value="" selected="selected"></option><option value="unsigned">unsigned</option><option value="zerofill">zerofill</option></select></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_ud" value="1" disabled="disabled"></label>
	<label><select name="col_key_ud" disabled="disabled"><option value=""></option><option value="">primary key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_ud" value="ON UPDATE CURRENT_TIMESTAMP" disabled="disabled"></label>
	<label class="w-xl"><input type="text" name="col_comment_ud" value="$value_updated" disabled="disabled"></label>
	<label class="delete-row"><button type="button" name="col_delete_ud" class="btn btn-info btn-sm" disabled="disabled"><span class="glyphicon glyphicon-remove"></span></button></label>
</li>
EOH;
*/

?>
<table>
  <thead>
  <?php echo $index_row; ?>
  </thead>
  <tbody id="sortable"><!-- <ul id="sortable"> -->
  <?php echo $body_row; /*$row;*/ ?>
  </tbody><!-- </ul> -->
  <tfoot>
  <?php echo $index_row; ?>
  </tfoot>
</table>
