<?php
// GUI tool "table creator"

// inherit values
//var_dump($inherit_values);

// translate text
$table_creator_label = __('Table Creator', PLUGIN_SLUG);
$tips_message = __('Columns of updated date and created date, and ID column of primary key can not be deleted or edited. You can create a new columns other that. And it will be sorted in drag.', PLUGIN_SLUG);
$cancel_close_btn_label = __('Cancel', PLUGIN_SLUG);
$set_sql_btn_label = __('Set SQL Statements', PLUGIN_SLUG);

$index_row = sprintf('<li class="index-row"><label class="null"></label><label class="w-xl">%s</label><label>%s</label><label class="w-sm">%s</label><label class="w-xs">%s</label><label>%s</label><label class="w-xs">%s</label><label class="w-xs">%s</label><label class="w-xl">%s</label><label class="w-lg">%s</label><label class="w-xl">%s</label><label class="null"></label></li>', 
	_x('column name', 'table-creator', PLUGIN_SLUG), 
	_x('type format', 'table-creator', PLUGIN_SLUG), 
	_x('max length', 'table-creator', PLUGIN_SLUG), 
	_x('notnull', 'table-creator', PLUGIN_SLUG), 
	_x('default', 'table-creator', PLUGIN_SLUG), 
	_x('unsigned', 'table-creator', PLUGIN_SLUG), 
	_x('autoincrement', 'table-creator', PLUGIN_SLUG), 
	_x('key', 'table-creator', PLUGIN_SLUG), 
	_x('extra', 'table-creator', PLUGIN_SLUG), 
	_x('comment', 'table-creator', PLUGIN_SLUG));
$row = <<<EOH
<li class="ui-state-default ui-state-disabled tbl_cols">
	<label class="handler row-index-num num-disabled">1</label>
	<label class="w-xl"><input type="text" name="col_name_pk" value="ID" disabled="disabled"></label>
	<label><select name="col_type_pk" disabled="disabled"><option value="int" selected="selected">int</option><option value="timestamp">timestamp</option></select></label>
	<label class="w-sm"><input type="number" name="col_maxlength_pk" value="64" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_pk" value="1" checked="checked" disabled="disabled"></label>
	<label><input type="text" name="col_default_pk" value="" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_unsigned_pk" value="1" checked="checked" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_pk" value="1" checked="checked" disabled="disabled"></label>
	<label class="w-xl"><select name="col_key_pk" disabled="disabled"><option value="primary key" selected="selected">primary key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_pk" value="" disabled="disabled"></label>
	<label class="w-xl"><input type="text" name="col_comment_pk" value="ID" disabled="disabled"></label>
	<label class="delete-row"><button type="button" name="col_delete_pk" class="btn btn-info btn-sm" disabled="disabled"><span class="glyphicon glyphicon-remove"></span></button></label>
</li>
<li class="ui-state-default tbl_cols preset">
	<label class="handler"><span class="glyphicon glyphicon-edit"></span></label>
	<label class="w-xl"><input type="text" name="col_name_" value=""></label>
	<label><select name="col_type_"><option value="int">int</option><option value="varchar">varchar</option><option value="text">text</option><option value="datetime">datetime</option><option value="timestamp">timestamp</option></select></label>
	<label class="w-sm"><input type="number" name="col_maxlength_" value=""></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_" value="1"></label>
	<label><input type="text" name="col_default_" value=""></label>
	<label class="w-xs"><input type="checkbox" name="col_unsigned_" value="1"></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_" value="1"></label>
	<label class="w-xl"><select name="col_key_"><option value=""></option><option value="unique key">unique key</option><option value="index key">index key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_" value=""></label>
	<label class="w-xl"><input type="text" name="col_comment_" value=""></label>
	<label class="add-row"><button type="button" name="col_add_preset" id="col_add_preset" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-plus"></span></button></label>
</li>
<!--<li class="ui-state-default tbl_cols addnew">
	<label class="handler row-index-num">2</label>
	<label class="w-xl"><input type="text" name="col_name_2" value=""></label>
	<label><select name="col_type_2"><option value="int">int</option><option value="varchar">varchar</option><option value="text">text</option><option value="datetime">datetime</option><option value="timestamp">timestamp</option></select></label>
	<label class="w-sm"><input type="number" name="col_maxlength_2" min="1" value=""></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_2" value="1"></label>
	<label><input type="text" name="col_default_2" value=""></label>
	<label class="w-xs"><input type="checkbox" name="col_unsigned_2" value="1"></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_2" value="1"></label>
	<label class="w-xl"><select name="col_key_2"><option value=""></option><option value="unique key">unique key</option><option value="index key">index key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_2" value=""></label>
	<label class="w-xl"><input type="text" name="col_comment_2" value=""></label>
	<label class="delete-row"><button type="button" name="col_delete_2" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-remove"></span></button></label>
</li>-->
<li class="ui-state-default ui-state-disabled tbl_cols">
	<label class="handler row-index-num num-disabled">3</label>
	<label class="w-xl"><input type="text" name="col_name_cd" value="created" disabled="disabled"></label>
	<label><select name="col_type_cd" disabled="disabled"><option value="datetime" selected="selected">datetime</option><option value="timestamp">timestamp</option></select></label>
	<label class="w-sm"><input type="number" name="col_maxlength_cd" value="" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_cd" value="1" checked="checked" disabled="disabled"></label>
	<label><input type="text" name="col_default_cd" value="0000-00-00 00:00:00" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_unsigned_cd" value="1" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_cd" value="1" disabled="disabled"></label>
	<label class="w-xl"><select name="col_key_cd" disabled="disabled"><option value=""></option><option value="">primary key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_cd" value="" disabled="disabled"></label>
	<label class="w-xl"><input type="text" name="col_comment_cd" value="Created" disabled="disabled"></label>
	<label class="delete-row"><button type="button" name="col_delete_cd" class="btn btn-info btn-sm" disabled="disabled"><span class="glyphicon glyphicon-remove"></span></button></label>
</li>
<li class="ui-state-default ui-state-disabled tbl_cols">
	<label class="handler row-index-num num-disabled">4</label>
	<label class="w-xl"><input type="text" name="col_name_ud" value="updated" disabled="disabled"></label>
	<label><select name="col_type_ud" disabled="disabled"><option value="timestamp" selected="selected">timestamp</option></select></label>
	<label class="w-sm"><input type="number" name="col_maxlength_ud" value="" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_notnull_ud" value="1" checked="checked" disabled="disabled"></label>
	<label><input type="text" name="col_default_ud" value="CURRENT_TIMESTAMP" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_unsigned_ud" value="1" disabled="disabled"></label>
	<label class="w-xs"><input type="checkbox" name="col_autoinc_ud" value="1" disabled="disabled"></label>
	<label class="w-xl"><select name="col_key_ud" disabled="disabled"><option value=""></option><option value="">primary key</option></select></label>
	<label class="w-lg"><input type="text" name="col_extra_ud" value="ON UPDATE CURRENT_TIMESTAMP" disabled="disabled"></label>
	<label class="w-xl"><input type="text" name="col_comment_ud" value="Updated" disabled="disabled"></label>
	<label class="delete-row"><button type="button" name="col_delete_ud" class="btn btn-info btn-sm" disabled="disabled"><span class="glyphicon glyphicon-remove"></span></button></label>
</li>
EOH;

$content_html .= <<<EOH
<!-- /* Table Creator Modal */ -->
<div class="modal fade mysql-table-creator" tabindex="-1" role="dialog" aria-labelledby="MySQLTableCreator" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove"></span></button>
        <h4 class="modal-title">$table_creator_label</h4>
      </div>
      <div class="modal-body">
    	   <p class="description-tips text-info"><span class="glyphicon glyphicon-info-sign"></span> $tips_message</p>
        <ul id="sortable">
$index_row
$row
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> <span class="cancel-close">$cancel_close_btn_label</span></button>
        <button type="button" class="btn btn-primary" data-dismiss="modal" id="set-sql"><span class="glyphicon glyphicon-ok"></span> <span class="run-process">$set_sql_btn_label</span></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<style>
.ui-state-highlight { height: 1.5em; line-height: 1.2em; }
</style>
<script>
jQuery(document).ready(function($){
  $('#sortable').sortable({
    items: 'li:not(.ui-state-disabled)', 
    placeholder: 'ui-state-highlight', 
  });
  $('#sortable').disableSelection();
});
</script>
EOH;
