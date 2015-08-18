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

$preset_col_type = array( 
	'int' => 11, // = int(11)
	'tinyint' => 4, // = tinyint(4)
	'smallint' => 6, // = smallint(6)
	'mediumint' => 9, // = mediumint(9)
	'bigint' => 20, // = bigint(20)
	'float' => 'Array', // or null
	'double' => 'Array', // or null // = double precision = real
	'decimal' => 'Array', // = decimal(10,0) = numeric = fixed
	'bool' => 1, // = tinyint(1) = boolean
	'bit' => 1,
	'varchar' => 'int', // = varchar(*)  *0-65535 int unit is bytes
	'char' => 'int', // = char(*) = national char(*) = nchar(*) = character(*)  *0-255 int unit is bytes
	'text' => null, 
	'tinytext' => null, 
	'mediumtext' => null, 
	'longtext' => null, 
	'blob' => null, 
	'tinyblob' => null, 
	'mediumblob' => null, 
	'longblob' => null, 
	'binary' => 1, // = binary(*)  *0-255 int unit is bytes
	'varbinary' => 'int', // = varbinary(*)  *0-65535 int unit is bytes
	'enum' => 'Array', 
	'set' => 'Array', 
	'date' => null, 
	'datetime' => null, 
	'time' => null, 
	'timestamp' => null, // NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	'year' => 4, // = year(4)
);
$col_type_options = '';
foreach($preset_col_type as $key => $val) {
	$col_type_options .= sprintf('<option value="%s">%s</option>', $val, $key);
}
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

?>
<ul id="sortable">
<?php echo $index_row; ?>
<?php echo $row; ?>
</ul>
