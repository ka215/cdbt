<?php
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) die();

if (is_admin()) {
	if (!check_admin_referer(self::DOMAIN .'_admin', '_cdbt_token')) 
		die('access is not from admin panel!');
} else {
	die('Invild access!');
}

foreach ($_REQUEST as $k => $v) {
	${$k} = $v;
var_dump('$' . $k . ' = "' . $v . '"');
}
$title = __('Custom Database Tables option setting', self::DOMAIN);
$information_html = $contents_html = '';
$tabs = array(
	'general' => false, 
	'create' => false, 
	'tables' => false, 
);
//var_dump($this->current_table);
//var_dump($this->options);
$this->current_table = $this->options['tables'][0]['table_name'];
list($result, $table_name, $table_schema) = $this->get_table_schema();
if ($result && !empty($table_name) && !empty($table_schema)) {
	// If one table is specified.
	if (wp_verify_nonce($_cdbt_token, self::DOMAIN .'_'. $mode)) {
		if (isset($action) && array_key_exists($action, $tabs)) {
			$tabs[$action] = true;
			if (isset($handle) && !empty($handle)) {
				global $wpdb;
				$message = $msg_type = null;
				$current_table = $this->current_table;
				$full_table_name = ($this->options['use_wp_prefix'] ? $wpdb->prefix : '') . $this->options['table_name'];
				if ($handle == 'save') {
					$this->options['table_name'] = (isset($naked_table_name) && !empty($naked_table_name)) ? $naked_table_name : $this->options['table_name'];
					if (isset($use_wp_prefix) && !empty($use_wp_prefix)) {
						$this->options['use_wp_prefix'] = ($use_wp_prefix == 'true') ? true : false;
					}
					$this->current_table = ($this->options['use_wp_prefix'] ? $wpdb->prefix : '') . $this->options['table_name'];
					list($result, $fixed_sql) = $this->validate_sql_create_table($create_table_sql);
var_dump($fixed_sql);
					if ($result) {
						$cdbt_options['sql'] = $fixed_sql;
					}
					$cdbt_options['show_max_records'] = (isset($show_max_records) && !empty($show_max_records)) ? intval($show_max_records) : $cdbt_options['show_max_records'];
					$cdbt_options['view_role'] = (isset($view_role) && !empty($view_role)) ? $view_role : $cdbt_options['view_role'];
					$cdbt_options['input_role'] = (isset($input_role) && !empty($input_role)) ? $input_role : $cdbt_options['input_role'];
					$cdbt_options['edit_role'] = (isset($edit_role) && !empty($edit_role)) ? $edit_role : $cdbt_options['edit_role'];
					$cdbt_options['admin_role'] = (isset($admin_role) && !empty($admin_role)) ? $admin_role : $cdbt_options['admin_role'];
					if (update_option(PLUGIN_SLUG, $cdbt_options)) {
						update_option(PLUGIN_SLUG . '_current_table', $create_table_name);
						$message = 'Completed successful to save option setting.';
						$msg_type = 'success';
					} else {
						$message = 'Failed to save option setting. Please note it is not saved if there is no change.';
						$msg_type = 'warning';
					}
				}
				if ($handle == 'data-export') {
					// is not implemented in this version.
				}
				if ($handle == 'alter-table') {
					// is not implemented in this version.
				}
				if ($handle == 'truncate-table') {
					list($result, $message) = truncate_table($target_table);
					$msg_type = ($result) ? 'success' : 'warning';
				}
				if ($handle == 'drop-table') {
					list($result, $message) = drop_table($target_table);
					$msg_type = ($result) ? 'success' : 'warning';
					if ($result) {
						if ($full_table_name == $target_table) {
							$cdbt_options['table_name'] = '';
							update_option(PLUGIN_SLUG, $cdbt_options);
						}
						if ($current_table_name && $current_table_name == $target_table) 
							delete_option(PLUGIN_SLUG . '_current_table');
					}
				}
				if ($handle == 'create-table') {
					// is not implemented in this version.
					$msg_type = 'info';
					$message = "$target_table is $handle.";
				}
				if ($handle == 'choise-current-table') {
					if (update_option(PLUGIN_SLUG . '_current_table', $target_table)) {
						$msg_type = 'info';
						$message = $target_table . ' was chosen as the current table.';
						$reg_prefix = '/^'. $wpdb->prefix . '(.*)?/';
						if (preg_match($reg_prefix, $target_table, $matches)) {
							$cdbt_options['table_name'] = $matches[1];
							$cdbt_options['use_wp_prefix'] = true;
						} else {
							$cdbt_options['table_name'] = $target_table;
							$cdbt_options['use_wp_prefix'] = false;
						}
						update_option(PLUGIN_SLUG, $cdbt_options);
					} else {
						$msg_type = 'info';
						$message = 'Did not choose the ' . $target_table . '.';
					}
				}
				if (!empty($msg_type) && !empty($message)) 
					$information_html = sprintf('<div class="alert alert-%s tab-header">%s<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>', $msg_type, $message);
			}
		} else {
			$tabs['general'] = true;
		}
		$nav_tabs_html = '<ul class="nav nav-tabs">%s</ul><!-- /.nav-tabs -->';
		$tabs_content_html = '<div class="tab-content">%s</div><!-- /.tab-content -->';
		$nav_tabs_list = $tabs_content = null;
		foreach ($tabs as $tab_name => $active) {
			$nav_active_class = ($active) ? ' class="active"' : '';
			$nav_tabs_list .= '<li'. $nav_active_class. '><a href="#gh-'. $tab_name. '" data-toggle="tab">'. $tab_name .'</a></li>';
			$tabs_content .= '<div class="tab-pane'. ($active ? ' active' : '') .'" id="gh-'. $tab_name .'">'. create_tab_content($tab_name, $_cdbt_token) .'</div>';
		}
		$contents_html = sprintf($nav_tabs_html.$information_html.$tabs_content_html, $nav_tabs_list, $tabs_content);
		$information_html = '';
	} else {
		$information_html = '<div class="alert alert-danger">Invild access!</div>';
		$nonce = wp_create_nonce(PLUGIN_SLUG);
	}
} else {
	// If not have been specified a table.
	unset($tabs['tables']);
	if (wp_verify_nonce($nonce, PLUGIN_SLUG)) {
		if (isset($action) && array_key_exists($action, $tabs)) {
			$tabs[$action] = true;
			if (isset($handle) && !empty($handle)) {
				global $wpdb;
				$message = $msg_type = null;
				$cdbt_options = get_option(PLUGIN_SLUG);
				$full_table_name = ($cdbt_options['use_wp_prefix'] ? $wpdb->prefix : '') . $cdbt_options['table_name'];
				if ($handle == 'save') {
					$cdbt_options['table_name'] = (isset($naked_table_name) && !empty($naked_table_name)) ? $naked_table_name : $cdbt_options['table_name'];
					if (isset($use_wp_prefix) && !empty($use_wp_prefix)) {
						$cdbt_options['use_wp_prefix'] = ($use_wp_prefix == 'true') ? true : false;
					}
					$create_table_name = ($cdbt_options['use_wp_prefix'] ? $wpdb->prefix : '') . $cdbt_options['table_name'];
					list($result, $fixed_sql) = validate_sql_create_table($create_table_name, $create_table_sql);
					if ($result) {
						$cdbt_options['sql'] = $fixed_sql;
					}
					$cdbt_options['show_max_records'] = (isset($show_max_records) && !empty($show_max_records)) ? intval($show_max_records) : $cdbt_options['show_max_records'];
					$cdbt_options['view_role'] = (isset($view_role) && !empty($view_role)) ? $view_role : $cdbt_options['view_role'];
					$cdbt_options['input_role'] = (isset($input_role) && !empty($input_role)) ? $input_role : $cdbt_options['input_role'];
					$cdbt_options['edit_role'] = (isset($edit_role) && !empty($edit_role)) ? $edit_role : $cdbt_options['edit_role'];
					$cdbt_options['admin_role'] = (isset($admin_role) && !empty($admin_role)) ? $admin_role : $cdbt_options['admin_role'];
					if (update_option(PLUGIN_SLUG, $cdbt_options)) {
						list($result, $message) = create_table($create_table_name);
						$message = 'Completed successful to save option setting.<br />And, ' . $message;
						if ($result) {
							$msg_type = 'success';
						} else {
							$msg_type = 'warning';
						}
						if (update_option(PLUGIN_SLUG . '_current_table', $create_table_name)) {
							$tabs['general'] = false;
							$tabs['tables'] = true;
						}
					} else {
						$message = 'Failed to save option setting. Please note it is not saved if there is no change.';
						$msg_type = 'warning';
					}
				}
				if (!empty($msg_type) && !empty($message)) 
					$information_html = sprintf('<div class="alert alert-%s tab-header">%s<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>', $msg_type, $message);
			}
		} else {
			$tabs['general'] = true;
		}
		$nav_tabs_html = '<ul class="nav nav-tabs">%s</ul><!-- /.nav-tabs -->';
		$tabs_content_html = '<div class="tab-content">%s</div><!-- /.tab-content -->';
		$nav_tabs_list = $tabs_content = null;
		foreach ($tabs as $tab_name => $active) {
			$nav_active_class = ($active) ? ' class="active"' : '';
			$nav_tabs_list .= '<li'. $nav_active_class. '><a href="#gh-'. $tab_name. '" data-toggle="tab">'. $tab_name .'</a></li>';
			$tabs_content .= '<div class="tab-pane'. ($active ? ' active' : '') .'" id="gh-'. $tab_name .'">'. create_tab_content($tab_name, $nonce) .'</div>';
		}
		$contents_html = sprintf($nav_tabs_html.$information_html.$tabs_content_html, $nav_tabs_list, $tabs_content);
		$information_html = '';
	} else {
		$information_html = '<div class="alert alert-danger">Invild access!</div>';
		$nonce = wp_create_nonce(PLUGIN_SLUG);
	}
}

$admin_html = '<h2>%s</h2>%s<div class="center-block">%s</div>';
printf($admin_html, $title, $information_html, $contents_html);

$this->create_console_footer($_cdbt_token);


function create_tab_content($tab_name, $nonce) {
	global $wpdb, $cdbt;
	$cdbt_options = get_option(PLUGIN_SLUG);
	$controller_table = $cdbt_options['tables'][0]['table_name'];
	$content_html = null;
	$nonce_field = wp_nonce_field(PLUGIN_SLUG .'_admin', '_cdbt_token', true, false);
	if ($tab_name == 'general') {
		// save to plugin option.
		$tab_name_label = __('General setting', PLUGIN_SLUG);
		$submit_label = __('Save changes', PLUGIN_SLUG);
		$checkbox_attr = $cdbt_options['use_wp_prefix'] ? ' checked="checked"' : '';
		$helper_msg = __('If you will create the new table, in default table name is used the table-prefix of WordPress&apos;s config.<br />However, when create table you can change this setting.', PLUGIN_SLUG);
		$charset_label = __('Table Charset', PLUGIN_SLUG);
		$charset_placeholder = __('Table Charset', PLUGIN_SLUG);
		$timezone_label = __('Database Timezone', PLUGIN_SLUG);
		$timezone_placeholder = __('Database Timezone', PLUGIN_SLUG);
		
		$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-wrench"></span> $tab_name_label</h3>
<form method="post" class="form-horizontal" id="cdbt_general_setting" role="form">
	<input type="hidden" name="mode" value="admin">
	<input type="hidden" name="action" value="general">
	<input type="hidden" name="handle" value="save">
	$nonce_field
	<div class="form-group">
		<div class="col-sm-offset-3 col-sm-9">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_use_wp_prefix" value="1"$checkbox_attr> $helper_msg
					<input type="hidden" name="use_wp_prefix" value="false">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_name" class="col-sm-3 control-label">$charset_label</label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="table_charset" id="cdbt_table_charset" placeholder="$charset_placeholder" value="{$cdbt_options['charset']}" disabled="disabled">
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_name" class="col-sm-3 control-label">$timezone_label</label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="timezone" id="cdbt_timezone" placeholder="$timezone_placeholder" value="{$cdbt_options['timezone']}" disabled="disabled">
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-3 col-sm-9">
			<button type="button" id="cdbt_general_setting_save" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span> $submit_label</button>
		</div>
	</div>
</form>
EOH;
		
	} elseif ($tab_name == 'create') {
		// create database table.
		$tab_name_label = __('Create table', PLUGIN_SLUG);
		$naked_table_name = '';
		$checkbox_attr = ($cdbt_options['use_wp_prefix'] ? ' checked="checked"' : '') . ' data-prefix="'. $wpdb->prefix .'"';
		$full_table_name = ($cdbt_options['use_wp_prefix'] ? $wpdb->prefix : '') . $naked_table_name;
		$submit_label = __('Create table', PLUGIN_SLUG);
		$table_name_label = __('Table Name', PLUGIN_SLUG);
		$table_name_placeholder = __('Enter Table Name', PLUGIN_SLUG);
		$helper_msg1 = __('If you will create the new table, in default table name is used the table-prefix of WordPress&apos;s config.', PLUGIN_SLUG);
		$helper_msg2 = __('Table name in the current configuration:', PLUGIN_SLUG);
		$helper_msg3 = __('It does not reflect if you change the table name, and not re-created after you delete a table of current created. In addition, in this table name is not possible use the name of the origin table that WordPress generates.', PLUGIN_SLUG);
		$table_comment_label = __('Table Comment', PLUGIN_SLUG);
		$table_comment_placeholder = __('Enter Table Comment', PLUGIN_SLUG);
		$db_engine_label = __('Database Engine', PLUGIN_SLUG);
		$sql_label = __('Create Table SQL', PLUGIN_SLUG);
		$show_max_records_label = __('Show Max Records', PLUGIN_SLUG);
		$show_max_records_placeholder = __('Enter Integer', PLUGIN_SLUG);
		$show_max_records_unit = __('records', PLUGIN_SLUG);
		$helper_msg4 = __('The maximum number of records to be displayed on one page.', PLUGIN_SLUG);
		$timezone_label = __('Database Timezone', PLUGIN_SLUG);
		$timezone_placeholder = __('Database Timezone', PLUGIN_SLUG);
		
		$roles = array(
			'view_role' => __('User Role for View', PLUGIN_SLUG), 
			'input_role' => __('User Role for Input', PLUGIN_SLUG), 
			'edit_role' => __('User Role for Edit', PLUGIN_SLUG), 
			'admin_role' => __('User Role for Admin', PLUGIN_SLUG)
		);
		$cap_levels = array(
			'1' => __('All users &mdash; If you grant privileges to all users, including subscribers.', PLUGIN_SLUG), 
			'3' => __('Contributor or more &mdash; If you grant privileges to user of contributor or more parties.', PLUGIN_SLUG), 
			'5' => __('Author or more &mdash; If you grant privileges to user of author or more parties.', PLUGIN_SLUG), 
			'7' => __('Editor or more &mdash; If you grant privileges to user of editor or more parties.', PLUGIN_SLUG), 
			'9' => __('Administrator only.', PLUGIN_SLUG), 
		);
		$user_role_forms = null;
		foreach ($roles as $param_name => $label_title) {
			$user_role_forms .= '<div class="form-group">';
			$user_role_forms .= '<label for="'. $param_name . $cdbt_options[$param_name] .'" class="col-sm-3 control-label">'. $label_title .'</label>';
			$user_role_forms .= '<div class="col-sm-9">';
			foreach ($cap_levels as $level => $description) {
				$checked = ($cdbt_options[$param_name] == $level) ? ' checked="checked"' : '';
				$user_role_forms .= '<div class="radio"><label>';
				$user_role_forms .= '<input type="radio" name="'. $param_name .'" id="'. $param_name . $level .'" value="'. $level .'"'. $checked .'>' . $description;
				$user_role_forms .= '</label></div>';
			}
			$user_role_forms .= '</div>';
			$user_role_forms .= '</div>';
		}
		
		$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-wrench"></span> $tab_name_label</h3>
<form method="post" class="form-horizontal" id="cdbt_general_setting" role="form">
	<input type="hidden" name="mode" value="admin">
	<input type="hidden" name="action" value="create">
	<input type="hidden" name="handle" value="create">
	$nonce_field
	<div class="form-group">
		<label for="cdbt_table_name" class="col-sm-3 control-label">$table_name_label</label>
		<div class="col-sm-6">
			<input type="text" class="form-control" name="naked_table_name" id="cdbt_table_name" placeholder="$table_name_placeholder" value="$naked_table_name">
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-3 col-sm-9">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_use_wp_prefix" value="1"$checkbox_attr> $helper_msg1
					<input type="hidden" name="use_wp_prefix" value="false">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-3 col-sm-9">
			<p class="help-block">$helper_msg2 <code class="simulate_table_name"></code></p>
			<p class="help-block"><p class="text-info"><span class="glyphicon glyphicon-exclamation-sign"></span> 
				$helper_msg3</p></p>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_comment" class="col-sm-3 control-label">$table_comment_label</label>
		<div class="col-sm-6">
			<input type="text" class="form-control" name="table_comment" id="cdbt_table_comment" placeholder="$table_comment_placeholder" value="$table_comment">
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_db_engine" class="col-sm-3 control-label">$db_engine_label</label>
		<div class="col-sm-2">
			<select type="text" class="form-control" name="db_engine" id="cdbt_db_engine">
				<option value="InnoDB" selected="selected">InnoDB</option>
				<option value="MyISAM">MyISAM</option>
			</select>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_name" class="col-sm-3 control-label">$sql_label</label>
		<div class="col-sm-9">
			<textarea class="form-control" name="create_table_sql" id="cdbt_create_table_sql" rows="20">$create_table_sql</textarea>
		</div>
	</div>
	<div class="form-group">
		<label for="gh_max_records" class="col-sm-3 control-label">$show_max_records_label</label>
		<div class="col-sm-1">
			<input type="text" class="form-control" name="show_max_records" id="gh_show_max_records" placeholder="$show_max_records_placeholder" value="$max_records">
		</div>
		<p class="help-block">$show_max_records_unit</p>
		<div class="col-sm-offset-3 col-sm-9">
			<p class="help-block">$helper_msg4</p>
		</div>
	</div>
	$user_role_forms
	<div class="form-group">
		<div class="col-sm-offset-3 col-sm-9">
			<button type="button" id="cdbt_general_setting_save" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span> $submit_label</button>
		</div>
	</div>
</form>
EOH;
	
	} elseif ($tab_name == 'tables') {
		$current_table = get_option(PLUGIN_SLUG . '_current_table');
		$load_tables = array(
			($cdbt_options['use_wp_prefix'] ? $wpdb->prefix : '') . $cdbt_options['table_name'], 
		);
		$enable_handle = array(
			'data-export' => array('enable' => false, 'label' => __('Data Export', PLUGIN_SLUG)), 
			'alter-table' => array('enable' => false, 'label' => __('Alter table', PLUGIN_SLUG)), 
			'truncate-table' => array('enable' => true, 'label' => __('Truncate table', PLUGIN_SLUG)), 
			'drop-table' => array('enable' => true, 'label' => __('Drop table', PLUGIN_SLUG)), 
			'create-table' => array('enable' => false, 'label' => __('Edit Create SQL', PLUGIN_SLUG)), 
			'choise-current-table' => array('enable' => true, 'label' => __('Set Current table', PLUGIN_SLUG)), 
		);
		$table_rows = null;
		$index_num = 1;
		foreach ($load_tables as $load_table_name) {
			if ($cdbt->check_table_exists($load_table_name)) {
				$total = (array)array_shift($cdbt->get_data($load_table_name, 'COUNT(*)'));
				$is_current = ($current_table && $current_table == $load_table_name) ? true : false;
				$table_rows .= '<tr><td>'. $index_num .'</td>';
				$table_rows .= '<td>'. $load_table_name .'</td>';
				$table_rows .= '<td>'. array_shift($total) .'</td>';
				foreach ($enable_handle as $handle_name => $handle_info) {
					$add_attr = (!$handle_info['enable']) ? ' disabled="disabled"' : '';
					$add_class = '';
					if ($handle_name == 'choise-current-table') {
						$add_attr .= ' data-selected-text="'. __('Currently selected', PLUGIN_SLUG). '"';
						if ($is_current) {
							$add_class = ' active';
							$handle_info['label'] = __('Currently selected', PLUGIN_SLUG);
						}
					}
					$table_rows .= '<td><button type="button" class="btn btn-default'. $add_class .'" id="'. $load_table_name .':'. $handle_name .'" data-table="'. $load_table_name .'"'. $add_attr .'>'. $handle_info['label'] .'</button></td>' . "\n";
				}
				$table_rows .= '</tr>';
				$index_num++;
			}
		}
		$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-th-list"></span> $tab_name</h3>
<div class="table-responsive">
	<table class="table table-bordered table-striped table-hover">
		<thead>
			<tr>
				<th>No.</th>
				<th>Table Name</th>
				<th>Total Records</th>
				<th>Data Export</th>
				<th>Change Table Schema</th>
				<th>Truncate table</th>
				<th>Drop table</th>
				<th>Create table(SQL)</th>
				<th>Choise Current table</th>
			</tr>
		</thead>
		<tbody class="current-exists-tables">
			$table_rows
		</tbody>
	</table>
</div>
<div class="center-block">
	<form method="post" id="cdbt_managed_tables" role="form">
		<input type="hidden" name="mode" value="admin">
		<input type="hidden" name="action" value="tables">
		<input type="hidden" name="handle" value="reflesh">
		<input type="hidden" name="target_table" value="">
		<input type="hidden" name="nonce" value="$nonce">
		<div class="form-group">
			<button type="button" class="btn btn-default pull-right on-bottom-margin" id="reflesh-table-list">Reflesh Table List</button>
		</div>
	</form>
</div>
EOH;
		
	} else {
		
	}
	return $content_html;
}
