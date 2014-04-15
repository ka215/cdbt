<?php
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) die();

if (is_admin()) {
	if (!check_admin_referer(self::DOMAIN .'_admin', '_cdbt_token')) 
		die(__('access is not from admin panel!', self::DOMAIN));
} else {
	die(__('Invild access!', self::DOMAIN));
}

foreach ($_REQUEST as $k => $v) {
	${$k} = $v;
//var_dump('$' . $k . ' = "' . $v . '"');
}
$information_html = $contents_html = '';
$tabs = array(
	'general' => false, 
	'create' => false, 
	'tables' => false, 
);
//var_dump($this->current_table);
//var_dump(get_option(self::DOMAIN . '_current_table'));
//var_dump($this->options);

if (wp_verify_nonce($_cdbt_token, self::DOMAIN .'_'. $mode)) {
	create_console_menu($_cdbt_token);
	
	if (isset($action) && array_key_exists($action, $tabs)) {
		// If is selected any tab.
		$tabs[$action] = true;
		if (isset($handle) && !empty($handle)) {
			global $wpdb;
			$message = $msg_type = null;
			switch($handle) {
				case 'save':
					// save to cdbt's general options.
					if (isset($use_wp_prefix) && !empty($use_wp_prefix)) {
						$this->options['use_wp_prefix'] = ($use_wp_prefix == 'true') ? true : false;
					}
					if (isset($charset) && !empty($charset) && $this->options['charset'] != $charset) {
						$this->options['charset'] = $charset;
					}
					if (isset($timezone) && !empty($timezone) && $this->options['timezone'] != $timezone) {
						$this->options['timezone'] = $timezone;
					}
					if (update_option(self::DOMAIN, $this->options)) {
						$message = __('Completed successful to save option setting.', self::DOMAIN);
						$msg_type = 'success';
					} else {
						$message = __('Failed to save option setting. Please note it is not saved if there is no change.', self::DOMAIN);
						$msg_type = 'warning';
					}
					break;
				case 'create-table':
					$inherit_values = array(
						'section' => $section, 
						'naked_table_name' => $naked_table_name, 
						'use_wp_prefix_for_newtable' => $use_wp_prefix_for_newtable, 
						'table_comment' => $table_comment, 
						'db_engine' => $db_engine, 
						'create_table_sql' => $create_table_sql, 
						'show_max_records' => $show_max_records, 
						'view_role' => $view_role, 
						'input_role' => $input_role, 
						'edit_role' => $edit_role, 
						'admin_role' => $admin_role, 
					);
var_dump($inherit_values);
					if (isset($section) && $section == 'confirm') {
						// validation to create new table.
						if (isset($naked_table_name) && !empty($naked_table_name)) {
							if ($use_wp_prefix_for_newtable == 'true') {
								$create_full_table_name = $wpdb->prefix . trim($naked_table_name);
							} else {
								$create_full_table_name = trim($naked_table_name);
							}
						} else {
							$message = __('Table name is empty.', self::DOMAIN);
							$msg_type = 'warning';
						}
						if (isset($create_full_table_name) && !empty($create_full_table_name)) {
							if (preg_match('/^([a-zA-Z0-9_\-]+)$/', $naked_table_name, $matches)) {
								if ($this->compare_reservation_tables($matches[1])) {
									$message = __('Table name is invalid. Table name is not allowed that use reserved name on WordPress.', self::DOMAIN);
									$msg_type = 'warning';
								}
								if ($create_full_table_name == $wpdb->prefix) {
									$message = __('Table name is invalid. Table name of the only prefix is not allowed.', self::DOMAIN);
									$msg_type = 'warning';
								}
								if (strlen($create_full_table_name) > 64) {
									$message = __('Table name is invalid. Maximum string length of the table name is 64 bytes.', self::DOMAIN);
									$msg_type = 'warning';
								}
								
							} else {
								$message = __('Table name is invalid. Characters that can not be used in table name is included.', self::DOMAIN);
								$msg_type = 'warning';
							}
						}
						if ($msg_type == 'warning') 
							break;
						if (isset($create_table_sql) && !empty($create_table_sql)) {
							// sql to create table will validate here.
							$create_table_sql = stripcslashes($create_table_sql);
						} else {
							$message = __('SQL to create table is empty.', self::DOMAIN);
							$msg_type = 'warning';
						}
						if ($msg_type != 'warning') {
							$inherit_values['section'] = 'run';
						}
						break;
					} else if (isset($section) && $section == 'run') {
						// run the create table.
						if (isset($create_full_table_name) && !empty($create_full_table_name)) {
							$this->current_table = $create_full_table_name;
							
							if (!$this->check_table_exists()) {
								$new_table = array(
									'table_name' => $create_full_table_name, 
									'table_type' => 'enable_table', 
									'sql' => "CREATE TABLE `$create_full_table_name` (
										`ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '". __('ID', self::DOMAIN) ."',
										$create_table_sql
										`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '". __('Created Date', self::DOMAIN) ."',
										`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '". __('Updated Date', self::DOMAIN) ."',
										PRIMARY KEY (`ID`)
										) ENGINE=%s DEFAULT CHARSET=%s COMMENT='$table_comment' AUTO_INCREMENT=1 ;", 
									'db_engine' => $db_engine, 
									'show_max_records' => intval($show_max_records), 
									'roles' => array(
										'view_role' => $view_role, 
										'input_role' => $input_role, 
										'edit_role' => $edit_role, 
										'admin_role' => $admin_role, 
									), 
									'display_format' => array(
										// {column_name} => array('(require|optional)', '(show|hide|none)', '{display_item_name}', '{default_value}', '(string|integer|float|date|binary)')
										'ID' => array('require', 'none', '', '', 'integer'), 
										'created' => array('require', 'none', '', '', 'date'), 
										'updated' => array('require', 'none', '', '', 'date'), 
									),
								);
								$is_exists_table = false;
								for ($i=1; $i<count($this->options['tables']); $i++) {
									if ($this->options['tables'][$i]['table_name'] == $create_full_table_name) {
										$this->options['tables'][$i] = $new_table;
										$is_exists_table = true;
										break;
									}
								}
								if (!$is_exists_table) 
									$this->options['tables'][] = $new_table;
								if (update_option(self::DOMAIN, $this->options)) {
//var_dump($this->current_table);
//var_dump($this->options);
									list($result, $message) = $this->create_table();
									$msg_type = ($result) ? 'success' : 'warning';
								} else {
									$message = __('Failed to save option setting. Please note it is not saved if there is no change.', self::DOMAIN);
									$msg_type = 'warning';
								}
							} else {
								$message = __('This table is already created.', self::DOMAIN);
								$msg_type = 'warning';
							}
						} else {
							$message = __('Table name is empty. Table name of the only prefix is not allowed.', self::DOMAIN);
							$msg_type = 'warning';
						}
					} else {
						$message = __('Is invalid call to create table.', self::DOMAIN);
						$msg_type = 'warning';
					}
					break;
				case 'data-export':
					// is not implemented in this version.
					
					break;
				case 'alter-table':
					// is not implemented in this version.
					
					break;
				case 'truncate-table':
					$this->current_table = $target_table;
					if ($this->current_table != $this->options['tables'][0]['table_name']) {
						list($result, $message) = $this->truncate_table();
						$msg_type = ($result) ? 'success' : 'warning';
					} else {
						$message = __('You can not handle to truncate controller table.', self::DOMAIN);
						$msg_type = 'warning';
					}
					break;
				case 'drop-table':
					$this->current_table = $target_table;
					if ($this->current_table != $this->options['tables'][0]['table_name']) {
						list($result, $message) = $this->drop_table();
						$msg_type = ($result) ? 'success' : 'warning';
						if ($result) {
							for($i=0; $i<count($this->options['tables']); $i++) {
								if ($this->options['tables'][$i]['table_name'] == $target_table) {
									unset($this->options['tables'][$i]);
								}
							}
							update_option(self::DOMAIN, $this->options);
							if (get_option(self::DOMAIN . '_current_table') == $target_table) 
								delete_option(self::DOMAIN . '_current_table');
						}
					} else {
						$message = __('You can not handle to drop controller table.', self::DOMAIN);
						$msg_type = 'warning';
					}
					break;
				case 'choise-current-table':
					$is_usable_table = false;
					for ($i=1; $i<count($this->options['tables']); $i++) {
						if ($this->options['tables'][$i]['table_name'] == $target_table) {
							if ($this->options['tables'][$i]['table_type'] == 'enable_table') 
								$is_usable_table = true;
							break;
						}
					}
					if ($is_usable_table) {
						$this->current_table = $target_table;
						update_option(self::DOMAIN . '_current_table', $target_table);
						$message = sprintf(__('The %s&apos;s table was chosen as the current table.', self::DOMAIN), $target_table);
						$msg_type = 'success';
					} else {
						$message = sprintf(__('Did not choose the %s&apos;s table.', self::DOMAIN), $target_table);
						$msg_type = 'warning';
					}
					break;
				default:
					break;
			}
			if (!empty($msg_type) && !empty($message)) 
				$information_html = sprintf('<div class="alert alert-%s tab-header">%s<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>', $msg_type, $message);
		}
	} else {
		// If tab is not only selected.
		$tabs['general'] = true;
	}
//var_dump($tabs);
	$nav_tabs_html = '<ul class="nav nav-tabs">%s</ul><!-- /.nav-tabs -->';
	$tabs_content_html = '<div class="tab-content">%s</div><!-- /.tab-content -->';
	$nav_tabs_list = $tabs_content = null;
	$inherit_values = empty($inherit_values) ? null : $inherit_values;
	foreach ($tabs as $tab_name => $active) {
		$nav_active_class = ($active) ? ' class="active"' : '';
		$nav_tabs_list .= '<li'. $nav_active_class. '><a href="#cdbt-'. $tab_name. '" data-toggle="tab">'. translate_tab_name($tab_name) .'</a></li>';
		$tabs_content .= '<div class="tab-pane'. ($active ? ' active' : '') .'" id="cdbt-'. $tab_name .'">'. create_tab_content($tab_name, $_cdbt_token, $inherit_values) .'</div>';
	}
	$contents_html = sprintf($nav_tabs_html.$information_html.$tabs_content_html, $nav_tabs_list, $tabs_content);
	$information_html = '';
} else {
	$_cdbt_token = wp_create_nonce(self::DOMAIN . '_admin');
	create_console_menu($_cdbt_token);
	
	$information_html = '<div class="alert alert-danger">'. __('Invild access!', self::DOMAIN) .'</div>';
}

$admin_html = '%s<div class="tab-container">%s</div>';
printf($admin_html, $information_html, $contents_html);

create_console_footer();


function create_tab_content($tab_name, $nonce, $inherit_values=null) {
	global $wpdb, $cdbt;
	$cdbt_options = get_option(PLUGIN_SLUG);
	$controller_table = $cdbt_options['tables'][0]['table_name'];
	$content_html = null;
	$nonce_field = wp_nonce_field(PLUGIN_SLUG .'_admin', '_cdbt_token', true, false);
	if ($tab_name == 'general') {
		// save to plugin option.
		$tab_name_label = translate_tab_name($tab_name);
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
		<div class="col-sm-offset-2 col-sm-9">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_use_wp_prefix" value="1"$checkbox_attr> $helper_msg
					<input type="hidden" name="use_wp_prefix" value="false">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_name" class="col-sm-2 control-label">$charset_label</label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="table_charset" id="cdbt_table_charset" placeholder="$charset_placeholder" value="{$cdbt_options['charset']}" disabled="disabled">
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_name" class="col-sm-2 control-label">$timezone_label</label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="timezone" id="cdbt_timezone" placeholder="$timezone_placeholder" value="{$cdbt_options['timezone']}" disabled="disabled">
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<button type="button" id="cdbt_general_setting_save" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span> $submit_label</button>
		</div>
	</div>
</form>
EOH;
		
	} elseif ($tab_name == 'create') {
		// create database table.
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
		// values
		if (is_array($inherit_values) && !empty($inherit_values)) {
			foreach ($inherit_values as $k => $v) { ${$k} = $v; }
		}
		$section = (isset($section) && !empty($section) && $section == 'run') ? 'run' : 'confirm';
		$naked_table_name = (isset($naked_table_name) && !empty($naked_table_name)) ? $naked_table_name : '';
		if (isset($use_wp_prefix_for_newtable) && !empty($use_wp_prefix_for_newtable)) {
			$create_table_checkbox_attr = $use_wp_prefix_for_newtable ? ' checked="checked"' : '';
			$use_wp_prefix_for_newtable = (bool)$use_wp_prefix_for_newtable;
		} else {
			$create_table_checkbox_attr = $cdbt_options['use_wp_prefix'] ? ' checked="checked"' : '';
			$use_wp_prefix_for_newtable = (bool)$cdbt_options['use_wp_prefix'];
		}
		$table_comment = (isset($table_comment) && !empty($table_comment)) ? $table_comment : '';
		$db_engine = (isset($db_engine) && !empty($db_engine)) ? $db_engine : 'InnoDB';
		$db_engine_options_base = '<option value="InnoDB"%s>InnoDB</option><option value="MyISAM"%s>MyISAM</option>';
		if ($db_engine == 'InnoDB') {
			$db_engine_options = sprintf($db_engine_options_base, ' selected="selected"', '');
		} else {
			$db_engine_options = sprintf($db_engine_options_base, '', ' selected="selected"');
		}
		$create_table_sql = (isset($create_table_sql) && !empty($create_table_sql)) ? $create_table_sql : '';
		$show_max_records = (isset($show_max_records) && !empty($show_max_records) && intval($show_max_records) > 0) ? intval($show_max_records) : intval(get_option('posts_per_page', 10));
		
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
		$user_role_forms = null;
		foreach ($roles as $param_name => $param_value) {
			list($label_title, $default_level) = $param_value;
			$user_role_forms .= '<div class="form-group">';
			$user_role_forms .= '<label for="'. $param_name . $default_level .'" class="col-sm-2 control-label">'. $label_title .'</label>';
			$user_role_forms .= '<div class="col-sm-9">';
			foreach ($cap_levels as $level => $description) {
				$checked = ($default_level == $level) ? ' checked="checked"' : '';
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
	
	} elseif ($tab_name == 'tables') {
		// enable tables list
		$tab_name_label = translate_tab_name($tab_name);
		$refresh_button_label = __('Reflesh Table List', PLUGIN_SLUG);
		$current_table = get_option(PLUGIN_SLUG . '_current_table', $cdbt_options['tables'][0]['table_name']);
		if (count($cdbt_options['tables']) > 1) {
			for ($i=1; $i<count($cdbt_options['tables']); $i++) {
				if (!empty($cdbt_options['tables'][$i]['table_name'])) 
					$load_tables[] = $cdbt_options['tables'][$i]['table_name'];
			}
			$index_label = array(
				__('No.', PLUGIN_SLUG), 
				__('Table Name', PLUGIN_SLUG), 
				__('Total Records', PLUGIN_SLUG), 
				__('Data Export', PLUGIN_SLUG), 
				__('Change Table Schema', PLUGIN_SLUG), 
				__('Truncate table', PLUGIN_SLUG), 
				__('Drop table', PLUGIN_SLUG), 
				__('Choise Current table', PLUGIN_SLUG), 
			);
			$thead_th = '';
			foreach ($index_label as $th_text) {
				$thead_th .= '<th>'. $th_text .'</th>';
			}
			$enable_handle = array(
				'data-export' => array('enable' => false, 'label' => __('Data Export', PLUGIN_SLUG)), 
				'alter-table' => array('enable' => false, 'label' => __('Alter table', PLUGIN_SLUG)), 
				'truncate-table' => array('enable' => true, 'label' => __('Truncate table', PLUGIN_SLUG)), 
				'drop-table' => array('enable' => true, 'label' => __('Drop table', PLUGIN_SLUG)), 
				'choise-current-table' => array('enable' => true, 'label' => __('Set Current table', PLUGIN_SLUG)), 
			);
			$table_rows = null;
			if (!empty($load_tables)) {
				$index_num = 1;
				foreach ($load_tables as $load_table_name) {
					if (empty($load_table_name)) 
						continue;
					$cdbt->current_table = $load_table_name;
					if ($cdbt->check_table_exists()) {
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
			}
			$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-th-list"></span> $tab_name_label</h3>
<div class="table-responsive">
	<table class="table table-bordered table-striped table-hover">
		<thead>
			<tr>
				$thead_th
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
		$nonce_field
		<div class="form-group">
			<button type="button" class="btn btn-default pull-right on-bottom-margin" id="reflesh-table-list">$refresh_button_label</button>
		</div>
	</form>
</div>
EOH;
		} else {
			$content_html = sprintf('<div class="alert alert-%s tab-header">%s<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>', 'warning', __('The enabled table is none.', PLUGIN_SLUG));
		}
	} else {
		
	}
	return $content_html;
}

function translate_tab_name($tab_name){
	$translate_tab_name = array(
		'general' => __('General setting', PLUGIN_SLUG), 
		'create' => __('Create table', PLUGIN_SLUG), 
		'tables' => __('Enable tables list', PLUGIN_SLUG), 
	);
	return $translate_tab_name[$tab_name];
}
