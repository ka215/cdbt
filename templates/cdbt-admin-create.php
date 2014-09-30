<?php
// create table tab display setting

// inherit values
if (is_array($inherit_values) && !empty($inherit_values)) {
	foreach ($inherit_values as $k => $v) { ${$k} = $v; }
}
$section = (isset($section) && !empty($section) && $section == 'run') ? 'run' : 'confirm';
$handle = (isset($handle) && !empty($handle) && $handle == 'alter-table') ? 'alter-table' : 'create-table';
$is_incorporate_table = (isset($incorporate_table) && !empty($incorporate_table)) ? true : false;

if ($handle == 'alter-table') {
	// Variables for alter table
	$is_init_set = (isset($init_set) && !empty($init_set)) ? cdbt_get_boolean($init_set) : false;
	$table_options = cdbt_get_options_table($target_table);
	if ($is_init_set) {
		$previous_table_name = $target_table_name = $target_table;
		list($result, $table_comment) = $cdbt->get_table_comment($target_table_name);
		if (!$result) 
			$table_comment = '';
		$previous_table_comment = $table_comment;
		$previous_db_engine = $db_engine = $table_options['db_engine'];
		list(, $create_table_sql) = $cdbt->get_create_table_sql($target_table_name);
		$alter_table_sql = 'ALTER TABLE '. $target_table_name ." \n";
		$previous_show_max_records = $show_max_records = $table_options['show_max_records'];
		foreach ($table_options['roles'] as $role_name => $level_num) {
			${$role_name} = $level_num;
		}
	} else {
		$target_table_name = (isset($target_table_name) && !empty($target_table_name)) ? $target_table_name : $target_table;
		$previous_table_name = (isset($previous_table_name) && !empty($previous_table_name)) ? $previous_table_name : $target_table_name;
		$create_table_sql = stripcslashes($create_table_sql);
		$alter_table_sql = stripcslashes($alter_table_sql);
	}
	$previous_roles = array();
	foreach ($table_options['roles'] as $role_name => $level_num) {
		$previous_roles[$role_name] = $level_num;
	}
	$init_set = 'false';
} else {
	// Variables for create table
	$naked_table_name = (isset($naked_table_name) && !empty($naked_table_name)) ? $naked_table_name : '';
	if (isset($use_wp_prefix_for_newtable) && !empty($use_wp_prefix_for_newtable)) {
		$create_table_checkbox_attr = checked(cdbt_get_boolean($use_wp_prefix_for_newtable), true, false);
		$use_wp_prefix_for_newtable = cdbt_get_boolean($use_wp_prefix_for_newtable);
	} else {
		$create_table_checkbox_attr = checked(cdbt_get_boolean($cdbt_options['use_wp_prefix']), true, false);
		$use_wp_prefix_for_newtable = cdbt_get_boolean($cdbt_options['use_wp_prefix']);
	}
	$table_comment = (isset($table_comment) && !empty($table_comment)) ? $table_comment : '';
	
	$db_engine = (!isset($db_engine) || empty($db_engine)) ? 'InnoDB' : $db_engine;
	$create_table_sql = (isset($create_table_sql) && !empty($create_table_sql)) ? stripcslashes($create_table_sql) : "CREATE TABLE {table_name} ( \n\n)";
	$substance_sql = (isset($substance_sql) && !empty($substance_sql)) ? $substance_sql : '';
	$show_max_records = (isset($show_max_records) && !empty($show_max_records) && intval($show_max_records) > 0) ? intval($show_max_records) : intval(get_option('posts_per_page', 10));
}
$db_engine_options = sprintf('<option value="InnoDB"%s>InnoDB</option><option value="MyISAM"%s>MyISAM</option>', selected($db_engine, 'InnoDB', false), selected($db_engine, 'MyISAM', false));

// translate text
$tab_name_label = ($handle == 'create-table') ? cdbt_translate_tab_name($tab_name) : __('Modify table', PLUGIN_SLUG);
$submit_label = ($handle == 'create-table') ? __('Create table', PLUGIN_SLUG) : __('Modify table', PLUGIN_SLUG);
$cancel_label = __('Cancel', PLUGIN_SLUG);
$table_name_label = __('Table Name', PLUGIN_SLUG);
$table_name_placeholder = __('Enter Table Name', PLUGIN_SLUG);
$incorporate_table_label = __('Incorporate Already Exists Table', PLUGIN_SLUG);
$helper_msg1 = __('If you will create the new table, in default table name is used the table-prefix of WordPress&apos;s config.', PLUGIN_SLUG);
$helper_msg2 = __('Table name in the current configuration:', PLUGIN_SLUG);
$helper_msg3 = __('It does not reflect if you change the table name, and not re-created after you delete a table of current created. In addition, in this table name is not possible use the name of the origin table that WordPress generates.', PLUGIN_SLUG);
$table_comment_label = __('Table Comment', PLUGIN_SLUG);
$table_comment_placeholder = __('Enter Table Comment', PLUGIN_SLUG);
$helper_msg4 = __('Table comment is used for display name as logical name of table.', PLUGIN_SLUG);
$db_engine_label = __('Database Engine', PLUGIN_SLUG);
$sql_label = __('Create Table SQL', PLUGIN_SLUG);
$sql_label2 = __('Modify Table SQL', PLUGIN_SLUG);
$sql_label3 = __('Preset of SQL', PLUGIN_SLUG);
$edit_sql_mode1 = __('Statements Mode', PLUGIN_SLUG);
$edit_sql_mode2 = __('Table Creator', PLUGIN_SLUG);
$create_table_sql_placeholder = __('Enter SQL Statements to create table', PLUGIN_SLUG);
$alter_table_sql_placeholder = __('Enter SQL Statements to alter table', PLUGIN_SLUG);
$helper_msg5 = __('ID field of primary key for autoincrement type will be automatically inserted at the beginning. Then, the fields of update date and registration date and time will be added automatically to the end. Please do not include the entry of the field for those if you want to write SQL directly create the table.', PLUGIN_SLUG);
$example_sql_label = __('Example of SQL statements: ', PLUGIN_SLUG);
$example_col_comment1 = __('Account Name', PLUGIN_SLUG);
$example_col_comment2 = __('Gender', PLUGIN_SLUG);
$show_max_records_label = __('Show Max Records', PLUGIN_SLUG);
$show_max_records_placeholder = __('Enter Integer', PLUGIN_SLUG);
$show_max_records_unit = __('records', PLUGIN_SLUG);
$helper_msg6 = __('The maximum number of records to be displayed on one page.', PLUGIN_SLUG);
$helper_msg7 = __('Please enter the full text of the SQL statement of the table structure you want to change in this input field.', PLUGIN_SLUG);
$timezone_label = __('Database Timezone', PLUGIN_SLUG);
$timezone_placeholder = __('Database Timezone', PLUGIN_SLUG);
$roles = array(
	'view_role' => array(__('User Role for View', PLUGIN_SLUG), (isset($view_role) && !empty($view_role) ? $view_role : '1')), 
	'input_role' => array(__('User Role for Input', PLUGIN_SLUG), (isset($input_role) && !empty($input_role) ? $input_role : '5')), 
	'edit_role' => array(__('User Role for Edit', PLUGIN_SLUG), (isset($edit_role) && !empty($edit_role) ? $edit_role : '7')), 
	'admin_role' => array(__('User Role for Admin', PLUGIN_SLUG), (isset($admin_role) && !empty($admin_role) ? $admin_role : '9')), 
);
$cap_levels = array(
	'1' => __('All users &mdash; If you grant privileges to all users, including subscribers.', PLUGIN_SLUG), 
	'3' => __('Contributor or more &mdash; If you grant privileges to user of contributor or more parties.', PLUGIN_SLUG), 
	'5' => __('Author or more &mdash; If you grant privileges to user of author or more parties.', PLUGIN_SLUG), 
	'7' => __('Editor or more &mdash; If you grant privileges to user of editor or more parties.', PLUGIN_SLUG), 
	'9' => __('Administrator only.', PLUGIN_SLUG), 
);
$presets = array(
	array(__('Add column', PLUGIN_SLUG), 'ADD COLUMN {column_definition} {position}'), 
	array(__('Add index or key', PLUGIN_SLUG), 'ADD {index_or_key} {index_name} ({index_col_name})'), 
	array(__('Add foreign key', PLUGIN_SLUG), 'ADD FOREIGN KEY {index_name} ({index_col_name}) {reference_definition}'), 
	array(__('Alter default', PLUGIN_SLUG), 'ALTER {col_name} {default_definition}'), 
	array(__('Change column', PLUGIN_SLUG), 'CHANGE {old_col_name} {column_definition} {position}'), 
	array(__('Modify column', PLUGIN_SLUG), 'MODIFY {column_definition} {position}'), 
	array(__('Drop column or keys', PLUGIN_SLUG), 'DROP {column_or_keys}'), 
	array(__('Switch keys', PLUGIN_SLUG), '{switch_definition} KEYS'), 
);

// role section
$user_role_forms = null;
foreach ($roles as $param_name => $param_value) {
	list($label_title, $default_level) = $param_value;
	if (cdbt_compare_var($param_name, 'admin_role')) {
		$user_role_forms .= '<input type="hidden" name="'. $param_name .'" id="'. $param_name .'" value="'. $default_level .'">';
	} else {
		$user_role_forms .= '<div class="form-group">';
		$user_role_forms .= '<label for="'. $param_name . $default_level .'" class="col-sm-2 control-label">'. $label_title .'</label>';
		$user_role_forms .= '<div class="col-sm-9 btn-group" data-toggle="buttons">';
		foreach ($cap_levels as $level => $description) {
			$checked = checked($default_level, $level, false);
			$tmp_arr = explode('&mdash;', $description);
			$role_name = $tmp_arr[0];
			$helper_tips = (count($tmp_arr) > 1) ? $tmp_arr[1] : '';
			$class_active = (!empty($checked)) ? 'active' : '';
			$user_role_forms .= '<label class="btn btn-default '. $class_active .'">';
			$user_role_forms .= '<input type="radio" name="'. $param_name .'" id="'. $param_name . $level .'" value="'. $level .'" data-helper-tips="'. $helper_tips .'"'. $checked .'>' . $role_name;
			$user_role_forms .= '</label>';
		}
		$user_role_forms .= '<p class="'. $param_name .'-helper help-block">&nbsp;</p>';
		$user_role_forms .= '</div>';
		$user_role_forms .= '</div>';
	}
	if ($handle == 'alter-table') 
		$user_role_forms .= '<input type="hidden" name="previous_'. $param_name .'" id="previous_'. $param_name .'" value="'. $previous_roles[$param_name] .'">';
}

if ($handle == 'alter-table') {
	// presets section
	$presets_buttons = null;
	foreach ($presets as $idx => $preset_data) {
		$i = intval($idx) + 1;
		$presets_buttons .= sprintf('<a href="#alter_table_sql" id="sql-presets-%d" data-preset-template="%s" class="btn btn-default btn-sm" data-toggle="popover" data-container="body" data-html="true" data-placement="right" title="%s">%s</a>', $i, $preset_data[1], $preset_data[0], $preset_data[0]);
	}
}

if ($handle != 'alter-table') {
	// to create table
	if ($is_incorporate_table) {
		$incorporate_options = sprintf('<option value="%s" selected="selected">%s</option>', $incorporate_table, $incorporate_table);
	} else {
		$incorporate_options = '<option value="" option-index="true">'. $incorporate_table_label .'</option>';
	}
	$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-wrench"></span> $tab_name_label</h3>
<form method="post" class="form-horizontal" id="cdbt_create_table" role="form">
	<input type="hidden" name="mode" value="admin">
	<input type="hidden" name="action" value="create">
	<input type="hidden" name="handle" value="$handle">
	<input type="hidden" name="section" value="$section">
	<input type="hidden" name="target_table" value="">
	<input type="hidden" name="init_set" value="false">
	$nonce_field
	<div class="form-group">
		<label for="cdbt_naked_table_name" class="col-sm-2 control-label">$table_name_label</label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="naked_table_name" id="cdbt_naked_table_name" placeholder="$table_name_placeholder" value="$naked_table_name" required>
		</div>
		<div class="col-sm-3">
			<select type="text" class="form-control" name="incorporate_table" id="cdbt_incorporate_table" data-action="get_table_list">
				$incorporate_options
			</select>
			<input type="hidden" name="is_incorporate_table" value="{$is_incorporate_table}">
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-8">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_use_wp_prefix_for_newtable" value="1" data-prefix="{$wpdb->prefix}"$create_table_checkbox_attr> $helper_msg1
					<input type="hidden" name="use_wp_prefix_for_newtable" value="$use_wp_prefix_for_newtable">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-8">
			<p class="help-block">$helper_msg2 <code class="simulate_table_name"></code></p>
			<p class="help-block"><p class="text-info"><span class="glyphicon glyphicon-exclamation-sign"></span> 
				$helper_msg3</p></p>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_comment" class="col-sm-2 control-label">$table_comment_label</label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="table_comment" id="cdbt_table_comment" placeholder="$table_comment_placeholder" value="$table_comment">
		</div>
		<div class="col-sm-offset-2 col-sm-8">
			<p class="help-block">$helper_msg4</p>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_db_engine" class="col-sm-2 control-label">$db_engine_label</label>
		<div class="col-sm-1">
			<select type="text" class="form-control" name="db_engine" id="cdbt_db_engine">
				$db_engine_options
			</select>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_create_table_sql" class="col-sm-2 control-label">$sql_label
			<div class="sql-editor btn-group-vertical">
				<a href="#cdbt_create_table_sql" id="sql-statements-mode" class="btn btn-default btn-sm active">$edit_sql_mode1</a>
				<a href="#cdbt_create_table_sql" id="sql-table-creator" class="btn btn-default btn-sm" data-toggle="modal" data-target=".mysql-table-creator">$edit_sql_mode2</a>
			</div>
		</label>
		<div class="col-sm-8">
			<textarea class="form-control" name="create_table_sql" id="cdbt_create_table_sql" cols="20" rows="15" placeholder="$create_table_sql_placeholder" wrap="hard" required>$create_table_sql</textarea>
			<input type="hidden" name="substance_sql" value="$substance_sql">
		</div>
		<div class="col-sm-offset-2 col-sm-8">
			<p class="help-block">$helper_msg5</p>
			<p class="help-block">$example_sql_label <br><code class="example-sql">CREATE TABLE prefix_new_table ( <wbr>
			`account_name` varchar(64) NOT NULL COMMENT '$example_col_comment1', <wbr>
			`gender` enum('female','male') DEFAULT NULL COMMENT '$example_col_comment2' )</code></p>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_max_records" class="col-sm-2 control-label">$show_max_records_label</label>
		<div class="col-sm-1">
			<input type="number" class="form-control" name="show_max_records" id="cdbt_show_max_records" placeholder="$show_max_records_placeholder" value="$show_max_records" min="1">
		</div>
		<p class="help-block">$show_max_records_unit</p>
		<div class="col-sm-offset-2 col-sm-8">
			<p class="help-block">$helper_msg6</p>
		</div>
	</div>
	$user_role_forms
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-8">
			<button type="button" id="cdbt_create_table_submit" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span> $submit_label</button>
		</div>
	</div>
</form>
EOH;

}
if ($handle == 'alter-table') {
	// to modify table
	$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-wrench"></span> $tab_name_label</h3>
<form method="post" class="form-horizontal" id="cdbt_create_table" role="form">
	<input type="hidden" name="mode" value="admin">
	<input type="hidden" name="action" value="create">
	<input type="hidden" name="handle" value="$handle">
	<input type="hidden" name="section" value="$section">
	<input type="hidden" name="target_table" value="$target_table">
	<input type="hidden" name="init_set" value="$init_set">
	$nonce_field
	<div class="form-group">
		<label for="cdbt_target_table_name" class="col-sm-2 control-label">$table_name_label</label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="target_table_name" id="cdbt_target_table_name" placeholder="$table_name_placeholder" value="$target_table_name" data-sql-tmpl="RENAME `{value}`">
			<input type="hidden" name="previous_table_name" value="$previous_table_name">
		</div>
		<div class="col-sm-5 prev-name">
			<span class="glyphicon glyphicon-arrow-left"></span> table name of before change: <code>$previous_table_name</code>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_comment" class="col-sm-2 control-label">$table_comment_label</label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="table_comment" id="cdbt_table_comment" placeholder="$table_comment_placeholder" value="$table_comment" data-sql-tmpl="COMMENT '{value}'">
			<input type="hidden" name="previous_table_comment" value="$previous_table_comment">
		</div>
		<div class="col-sm-5 prev-comment">
			<span class="glyphicon glyphicon-arrow-left"></span> table comment of before change: <code>$previous_table_comment</code>
		</div>
		<div class="col-sm-offset-2 col-sm-8">
			<p class="help-block">$helper_msg4</p>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_db_engine" class="col-sm-2 control-label">$db_engine_label</label>
		<div class="col-sm-1">
			<select type="text" class="form-control" name="db_engine" id="cdbt_db_engine" data-sql-tmpl="ENGINE = {value}">
				$db_engine_options
			</select>
			<input type="hidden" name="previous_db_engine" value="$previous_db_engine">
		</div>
		<div class="col-sm-7 prev-engine">
			<span class="glyphicon glyphicon-arrow-left"></span> database engine of before change: <code>$previous_db_engine</code>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_create_table_sql" class="col-sm-2 control-label">$sql_label</label>
		<div class="col-sm-8">
			<textarea class="form-control" name="create_table_sql" id="cdbt_create_table_sql" cols="20" rows="10" wrap="hard" readonly>$create_table_sql</textarea>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_alter_table_sql" class="col-sm-2 control-label">$sql_label2
			<div class="sql-preset-label">$sql_label3</div>
			<div class="sql-preset btn-group-vertical">
				$presets_buttons
			</div>
		</label>
		<div class="col-sm-8">
			<textarea class="form-control" name="alter_table_sql" id="cdbt_alter_table_sql" cols="20" rows="15" placeholder="$alter_table_sql_placeholder" wrap="hard" required>$alter_table_sql</textarea>
			<input type="hidden" name="substance_sql" value="$substance_sql">
		</div>
		<div class="col-sm-offset-2 col-sm-8">
			<p class="help-block">$helper_msg7</p>
			<p class="help-block">$example_sql_label <br><code class="example-sql">ALTER TABLE prefix_table ADD `account_code` CHAR(10), ADD INDEX (`account_code`), <wbr>
			DROP COLUMN `gender`, CHANGE `old_column` `new_column` INTEGER, MODIFY `account_name` TINYINT NOT NULL, <wbr>
			CONVERT TO CHARACTER SET utf8, ENGINE = MyISAM;</code></p>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_max_records" class="col-sm-2 control-label">$show_max_records_label</label>
		<div class="col-sm-1">
			<input type="number" class="form-control" name="show_max_records" id="cdbt_show_max_records" placeholder="$show_max_records_placeholder" value="$show_max_records" min="1">
			<input type="hidden" name="previous_show_max_records" value="$previous_show_max_records">
		</div>
		<p class="help-block">$show_max_records_unit</p>
		<div class="col-sm-offset-2 col-sm-8">
			<p class="help-block">$helper_msg6</p>
		</div>
	</div>
	$user_role_forms
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-8">
			<button type="button" id="cdbt_alter_table_submit" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span> $submit_label</button>
			<button type="button" id="cdbt_alter_table_cancel" class="btn btn-default"><span class="glyphicon glyphicon-remove"></span> $cancel_label</button>
		</div>
	</div>
</form>
EOH;

}