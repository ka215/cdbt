<?php
// create table tab display setting

// inherit values
if (is_array($inherit_values) && !empty($inherit_values)) {
	foreach ($inherit_values as $k => $v) { ${$k} = $v; }
}
$section = (isset($section) && !empty($section) && $section == 'run') ? 'run' : 'confirm';
$naked_table_name = (isset($naked_table_name) && !empty($naked_table_name)) ? $naked_table_name : '';
if (isset($use_wp_prefix_for_newtable) && !empty($use_wp_prefix_for_newtable)) {
	$create_table_checkbox_attr = checked((bool)$use_wp_prefix_for_newtable, true, false);
	$use_wp_prefix_for_newtable = (bool)$use_wp_prefix_for_newtable;
} else {
	$create_table_checkbox_attr = checked((bool)$cdbt_options['use_wp_prefix'], true, false);
	$use_wp_prefix_for_newtable = (bool)$cdbt_options['use_wp_prefix'];
}
$table_comment = (isset($table_comment) && !empty($table_comment)) ? $table_comment : '';

$db_engine = (!isset($db_engine) || empty($db_engine)) ? $controller_table['db_engine'] : $db_engine;
$db_engine_options = sprintf('<option value="InnoDB"%s>InnoDB</option><option value="MyISAM"%s>MyISAM</option>', selected($db_engine, 'InnoDB', false), selected($db_engine, 'MyISAM', false));
$create_table_sql = (isset($create_table_sql) && !empty($create_table_sql)) ? $create_table_sql : '';
$show_max_records = (isset($show_max_records) && !empty($show_max_records) && intval($show_max_records) > 0) ? intval($show_max_records) : intval(get_option('posts_per_page', 10));

// translate text
$tab_name_label = translate_tab_name($tab_name);
$submit_label = __('Create table', PLUGIN_SLUG);
$table_name_label = __('Table Name', PLUGIN_SLUG);
$table_name_placeholder = __('Enter Table Name', PLUGIN_SLUG);
$helper_msg1 = __('If you will create the new table, in default table name is used the table-prefix of WordPress&apos;s config.', PLUGIN_SLUG);
$helper_msg2 = __('Table name in the current configuration:', PLUGIN_SLUG);
$helper_msg3 = __('It does not reflect if you change the table name, and not re-created after you delete a table of current created. In addition, in this table name is not possible use the name of the origin table that WordPress generates.', PLUGIN_SLUG);
$table_comment_label = __('Table Comment', PLUGIN_SLUG);
$table_comment_placeholder = __('Enter Table Comment', PLUGIN_SLUG);
$helper_msg4 = __('Table comment is used for display name as logical name of table.', PLUGIN_SLUG);
$db_engine_label = __('Database Engine', PLUGIN_SLUG);
$sql_label = __('Create Table SQL', PLUGIN_SLUG);
$show_max_records_label = __('Show Max Records', PLUGIN_SLUG);
$show_max_records_placeholder = __('Enter Integer', PLUGIN_SLUG);
$show_max_records_unit = __('records', PLUGIN_SLUG);
$helper_msg5 = __('The maximum number of records to be displayed on one page.', PLUGIN_SLUG);
$timezone_label = __('Database Timezone', PLUGIN_SLUG);
$timezone_placeholder = __('Database Timezone', PLUGIN_SLUG);
$roles = array(
	'view_role' => array(__('User Role for View', PLUGIN_SLUG), '1'), 
	'input_role' => array(__('User Role for Input', PLUGIN_SLUG), '5'), 
	'edit_role' => array(__('User Role for Edit', PLUGIN_SLUG), '7'), 
	'admin_role' => array(__('User Role for Admin', PLUGIN_SLUG), '9'), 
);
$cap_levels = array(
	'1' => __('All users &mdash; If you grant privileges to all users, including subscribers.', PLUGIN_SLUG), 
	'3' => __('Contributor or more &mdash; If you grant privileges to user of contributor or more parties.', PLUGIN_SLUG), 
	'5' => __('Author or more &mdash; If you grant privileges to user of author or more parties.', PLUGIN_SLUG), 
	'7' => __('Editor or more &mdash; If you grant privileges to user of editor or more parties.', PLUGIN_SLUG), 
	'9' => __('Administrator only.', PLUGIN_SLUG), 
);

// role section
$user_role_forms = null;
foreach ($roles as $param_name => $param_value) {
	list($label_title, $default_level) = $param_value;
	$user_role_forms .= '<div class="form-group">';
	$user_role_forms .= '<label for="'. $param_name . $default_level .'" class="col-sm-2 control-label">'. $label_title .'</label>';
	$user_role_forms .= '<div class="col-sm-9">';
	foreach ($cap_levels as $level => $description) {
		$checked = checked($default_level, $level, false);
		$user_role_forms .= '<div class="radio"><label>';
		$user_role_forms .= '<input type="radio" name="'. $param_name .'" id="'. $param_name . $level .'" value="'. $level .'"'. $checked .'>' . $description;
		$user_role_forms .= '</label></div>';
	}
	$user_role_forms .= '</div>';
	$user_role_forms .= '</div>';
}

$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-wrench"></span> $tab_name_label</h3>
<form method="post" class="form-horizontal" id="cdbt_create_table" role="form">
	<input type="hidden" name="mode" value="admin">
	<input type="hidden" name="action" value="create">
	<input type="hidden" name="handle" value="create-table">
	<input type="hidden" name="section" value="$section">
	$nonce_field
	<div class="form-group">
		<label for="cdbt_table_name" class="col-sm-2 control-label">$table_name_label</label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="naked_table_name" id="cdbt_table_name" placeholder="$table_name_placeholder" value="$naked_table_name">
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_use_wp_prefix_for_newtable" value="1" data-prefix="{$wpdb->prefix}"$create_table_checkbox_attr> $helper_msg1
					<input type="hidden" name="use_wp_prefix_for_newtable" value="$use_wp_prefix_for_newtable">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
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
		<div class="col-sm-offset-2 col-sm-9">
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
		<label for="cdbt_table_name" class="col-sm-2 control-label">$sql_label</label>
		<div class="col-sm-8">
			<textarea class="form-control" name="create_table_sql" id="cdbt_create_table_sql" rows="20">$create_table_sql</textarea>
		</div>
	</div>
	<div class="form-group">
		<label for="gh_max_records" class="col-sm-2 control-label">$show_max_records_label</label>
		<div class="col-sm-1">
			<input type="text" class="form-control" name="show_max_records" id="gh_show_max_records" placeholder="$show_max_records_placeholder" value="$show_max_records">
		</div>
		<p class="help-block">$show_max_records_unit</p>
		<div class="col-sm-offset-2 col-sm-9">
			<p class="help-block">$helper_msg5</p>
		</div>
	</div>
	$user_role_forms
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<button type="button" id="cdbt_create_table_submit" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span> $submit_label</button>
		</div>
	</div>
</form>
EOH;
