<?php
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) die();

if (is_admin()) {
	if (!check_admin_referer(self::DOMAIN .'_admin', '_cdbt_token')) 
		die(__('access is not from admin panel!', self::DOMAIN));
} else {
	die(__('Invild access!', self::DOMAIN));
}

$inherit_values = array();
foreach ($_REQUEST as $key => $value) {
	if (preg_match('/^(page|mode|_cdbt_token|action|handle|section|_wp_http_referer)$/', $key)) {
		${$key} = $value;
	} else {
		$inherit_values[$key] = $value;
	}
}
if (isset($handle)) 
	$inherit_values['handle'] = $handle;
$information_html = $contents_html = $nav_tabs_list = $tabs_content = null;
$tabs = array(
	'general' => false, 
	'create' => false, 
	'tables' => false, 
);

if (wp_verify_nonce($_cdbt_token, self::DOMAIN .'_'. $mode)) {
	if (!isset($action) || empty($action) || !array_key_exists($action, $tabs)) 
		$action = 'general';
	$tabs[$action] = true;
	global $wpdb;
	
	switch ($action) {
		case 'general': 
			if (isset($handle) && cdbt_compare_var($handle, 'save')) {
				foreach ($inherit_values as $key => $value) {
					if (preg_match('/^(use_wp_prefix|cleaning_options|uninstall_options|resume_options)$/', $key)) {
						$this->options[$key] = cdbt_get_boolean($value);
					} else {
						$this->options[$key] = $value;
					}
				}
				if (update_option(self::DOMAIN, $this->options)) {
					$msg = array('success', __('Completed successful to save option setting.', self::DOMAIN));
					if ($this->options['resume_options']) {
						$revision_tables = get_option(self::DOMAIN . '_previous_revision_backup');
						if ($revision_tables !== false && isset($revision_tables['tables']) && count($revision_tables['tables']) > 0) {
							$resume_count = 0;
							foreach ($revision_tables['tables'] as $i => $past_table_data) {
								if (isset($past_table_data['table_type']) && $past_table_data['table_type'] != 'controller_table') {
									$is_same_table = false;
									foreach ($this->options['tables'] as $j => $table_data) {
										if ($table_data['table_name'] == $past_table_data['table_name']) {
											$is_same_table = true;
											break;
										}
									}
									if (!$is_same_table) {
										if (cdbt_get_boolean($this->check_table_exists($past_table_data['table_name']))) {
											if (isset($past_table_data['sql']) && !empty($past_table_data['sql'])) {
												$table_create_sql = $past_table_data['sql'];
											} else {
												$res_ary = $this->get_create_table_sql($past_table_data['table_name']);
												$table_create_sql = $res_ary[1];
											}
											$this->options['tables'][] = array(
												'table_name' => $past_table_data['table_name'], 
												'table_type' => $past_table_data['table_type'], 
												'sql' => $table_create_sql, 
												'db_engine' => $past_table_data['db_engine'], 
												'show_max_records' => isset($past_table_data['show_max_records']) ? $past_table_data['show_max_records'] : $this->options['tables'][0]['show_max_records'], 
												'roles' => isset($past_table_data['roles']) ? $past_table_data['roles'] : $this->options['tables'][0]['roles'], 
												'display_format' => isset($past_table_data['display_format']) ? $past_table_data['display_format'] : $this->options['tables'][0]['display_format'], 
											);
											$resume_count++;
										}
									}
								}
							}
							if ($resume_count > 0) {
								update_option(self::DOMAIN, $this->options);
								$msg[1] .= "\n " . __('And have resumed as management table from in the past table setting.', self::DOMAIN);
							}
						}
					}
					if ($this->options['cleaning_options']) {
						$prev_current_table = $this->current_table;
						if (isset($this->options['tables']) && !empty($this->options['tables'])) {
							$re_tables = array();
							foreach ($this->options['tables'] as $i => $table) {
								$this->current_table = $table['table_name'];
								if (isset($table['table_type']) && $table['table_type'] != 'controller_table') {
									if (cdbt_get_boolean($this->check_table_exists())) {
										$re_tables[] = $table;
									}
								} else {
									$re_tables[] = $table;
								}
							}
							$this->options['tables'] = $re_tables;
							update_option(self::DOMAIN, $this->options);
						}
						$this->current_table = $prev_current_table;
						$msg[1] .= "\n " . __('And the cleaning of the table settings done.', self::DOMAIN);
					}
				} else {
					$msg = array('warning', __('Failed to save option setting. Please note it is not saved if there is no change.', self::DOMAIN));
				}
			}
			break;
		case 'create': 
			if ($handle == 'create-table') {
				if (cdbt_get_boolean($inherit_values['is_incorporate_table'])) {
					$incorporate_table_name = $inherit_values['incorporate_table'];
					if ($section == 'confirm') {
						if (cdbt_compare_var(empty($inherit_values['show_max_records']), true)) {
							$msg = array('warning', __('Show Max Records is empty.', self::DOMAIN));
						} else if (intval($inherit_values['show_max_records']) == 0) {
							$msg = array('warning', __('Show Max Records must be one more integer.', self::DOMAIN));
						}
						if (cdbt_compare_var(empty($inherit_values['view_role']), true) || intval($inherit_values['view_role']) < 1 || intval($inherit_values['view_role']) > 9) 
							$inherit_values['view_role'] = '1';
						if (cdbt_compare_var(empty($inherit_values['input_role']), true) || intval($inherit_values['input_role']) < 1 || intval($inherit_values['input_role']) > 9) 
							$inherit_values['input_role'] = '5';
						if (cdbt_compare_var(empty($inherit_values['edit_role']), true) || intval($inherit_values['edit_role']) < 1 || intval($inherit_values['edit_role']) > 9) 
							$inherit_values['edit_role'] = '7';
						if (cdbt_compare_var(empty($inherit_values['admin_role']), true) || intval($inherit_values['admin_role']) < 1 || intval($inherit_values['admin_role']) > 9) 
							$inherit_values['admin_role'] = '9';
						if (empty($msg)) {
							$section = 'run';
							$incorporate_table = $incorporate_table_name;
							$msg = array('confirmation', sprintf(__('Will incorporate a "%s" table. Would you like?', self::DOMAIN), $incorporate_table_name), __('Yes, resume.', self::DOMAIN));
						}
					} else if ($section == 'run') {
						$prev_current_table = $this->current_table;
						$this->current_table = trim($inherit_values['incorporate_table']);
						if ($this->check_table_exists()) {
							
							list($result, $create_table_sql) = $this->get_create_table_sql();
							if ($result) {
								if (preg_match('/\sENGINE=(.*)\s/iU', $create_table_sql, $matches)) {
									$db_engine = $matches[1];
								}
								$new_table = array(
									'table_name' => $this->current_table, 
									'table_type' => 'enable_table', 
									'sql' => $create_table_sql, 
									'db_engine' => $db_engine, 
									'show_max_records' => intval($inherit_values['show_max_records']), 
									'roles' => array(
										'view_role' => $inherit_values['view_role'], 
										'input_role' => $inherit_values['input_role'], 
										'edit_role' => $inherit_values['edit_role'], 
										'admin_role' => $inherit_values['admin_role'], 
									), 
									'display_format' => array(
										// {column_name} => array('(require|optional)', '(show|hide|none)', '{display_item_name}', '{default_value}', '(string|integer|float|date|binary)')
										'ID' => array('require', 'none', '', '', 'integer'), 
										'created' => array('require', 'none', '', '', 'date'), 
										'updated' => array('require', 'none', '', '', 'date'), 
									),
								);
								$table_list = $this->get_table_list('enable');
								if ($table_list && !in_array($this->current_table, $table_list)) {
									$this->options['tables'][] = $new_table;
									if (update_option(self::DOMAIN, $this->options)) {
										$msg = array('success', __('Have been completed that resume the setting as manageable table in this plugin.', self::DOMAIN));
										$inherit_values = array();
									} else {
										$msg = array('warning', __('Failed to save option setting. Please note it is not saved if there is no change.', self::DOMAIN));
									}
								}
							} else {
								$msg = array('warning', __('Could not get the sql to create this table.', self::DOMAIN));
							}
						} else {
							$msg = array('warning', __('This table is not exists.', self::DOMAIN));
						}
						$incorporate_table = '';
						$is_incorporate_table = false;
						$this->current_table = $prev_current_table;
					} else {
						$msg = array('warning', __('Is invalid call to incorporate table.', self::DOMAIN));
					}
				} else {
					if ($section == 'confirm') {
						$create_full_table_name = null;
						if (cdbt_compare_var(empty($inherit_values['naked_table_name']), true)) {
							$msg = array('warning', __('Table name is empty.', self::DOMAIN));
						} else {
							$create_full_table_name = (cdbt_get_boolean($inherit_values['use_wp_prefix_for_newtable']) ? $wpdb->prefix : '') . trim($inherit_values['naked_table_name']);
						}
						if (cdbt_compare_var(empty($create_full_table_name), true)) {
							$msg = array('warning', __('Table name is empty.', self::DOMAIN));
						} else {
							if (preg_match('/^([a-zA-Z0-9_\-]+)$/', $inherit_values['naked_table_name'], $matches)) {
								if ($this->compare_reservation_tables($matches[1])) {
									$msg = array('warning', __('Table name is invalid. Table name is not allowed that use reserved name on WordPress.', self::DOMAIN));
								}
								if ($create_full_table_name == $wpdb->prefix) {
									$msg = array('warning', __('Table name is invalid. Table name of the only prefix is not allowed.', self::DOMAIN));
								}
								if (strlen($create_full_table_name) > 64) {
									$msg = array('warning', __('Table name is invalid. Maximum string length of the table name is 64 bytes.', self::DOMAIN));
								}
								if (intval($create_full_table_name) > 0) {
									$msg = array('warning', __('Table name is invalid. Table name cannot named in only numbers.', self::DOMAIN));
								}
								foreach ($this->options['tables'] as $table) {
									if (cdbt_compare_var($create_full_table_name, $table['table_name'])) {
										$msg = array('warning', __('This table is already created.', self::DOMAIN));
										break;
									}
								}
							} else {
								$msg = array('warning', __('Table name is invalid. Characters that can not be used in table name is included.', self::DOMAIN));
							}
							if (empty($msg)) {
								if (cdbt_compare_var(empty($inherit_values['create_table_sql']), true)) {
									$msg = array('warning', __('Create Table SQL is empty.', self::DOMAIN));
								} else {
									$sql_str = stripcslashes(strip_tags($inherit_values['create_table_sql']));
									list($result, $fixed_sql) = $this->validate_create_sql($create_full_table_name, $sql_str);
									if ($result) {
										// sql validate done
										$esc_table_comment = stripcslashes(strip_tags($inherit_values['table_comment']));
										$fixed_sql = sprintf($fixed_sql, $inherit_values['db_engine'], $this->options['charset'], $esc_table_comment);
										$inherit_values['substance_sql'] = rawurlencode($fixed_sql);
									} else {
										$msg = array('warning', __('Create Table SQL is invalid.', self::DOMAIN));
									}
								}
								if (empty($msg)) {
									if (cdbt_compare_var(empty($inherit_values['show_max_records']), true)) {
										$msg = array('warning', __('Show Max Records is empty.', self::DOMAIN));
									} else if (intval($inherit_values['show_max_records']) == 0) {
										$msg = array('warning', __('Show Max Records must be one more integer.', self::DOMAIN));
									}
									if (cdbt_compare_var(empty($inherit_values['view_role']), true) || intval($inherit_values['view_role']) < 1 || intval($inherit_values['view_role']) > 9) 
										$inherit_values['view_role'] = '1';
									if (cdbt_compare_var(empty($inherit_values['input_role']), true) || intval($inherit_values['input_role']) < 1 || intval($inherit_values['input_role']) > 9) 
										$inherit_values['input_role'] = '5';
									if (cdbt_compare_var(empty($inherit_values['edit_role']), true) || intval($inherit_values['edit_role']) < 1 || intval($inherit_values['edit_role']) > 9) 
										$inherit_values['edit_role'] = '7';
									if (cdbt_compare_var(empty($inherit_values['admin_role']), true) || intval($inherit_values['admin_role']) < 1 || intval($inherit_values['admin_role']) > 9) 
										$inherit_values['admin_role'] = '9';
									if (empty($msg)) {
										$section = 'run';
										$msg = array('confirmation', '{#code class="confirm-sql"#}'. $fixed_sql .'{#/code#}' . sprintf(__('Will create a "%s" table. Would you like?', self::DOMAIN), $create_full_table_name), __('Yes, create.', self::DOMAIN), 'set_substance_sql');
									}
								}
							}
						}
					} else if ($section == 'run') {
						$prev_current_table = $this->current_table;
						$this->current_table = (cdbt_get_boolean($inherit_values['use_wp_prefix_for_newtable']) ? $wpdb->prefix : '') . trim($inherit_values['naked_table_name']);
						if (!$this->check_table_exists()) {
							$create_table_sql = rawurldecode($inherit_values['substance_sql']);
							$new_table = array(
								'table_name' => $this->current_table, 
								'table_type' => 'enable_table', 
								'sql' => $create_table_sql, 
								'db_engine' => $inherit_values['db_engine'], 
								'show_max_records' => intval($inherit_values['show_max_records']), 
								'roles' => array(
									'view_role' => $inherit_values['view_role'], 
									'input_role' => $inherit_values['input_role'], 
									'edit_role' => $inherit_values['edit_role'], 
									'admin_role' => $inherit_values['admin_role'], 
								), 
								'display_format' => array(
									// {column_name} => array('(require|optional)', '(show|hide|none)', '{display_item_name}', '{default_value}', '(string|integer|float|date|binary)')
									'ID' => array('require', 'none', '', '', 'integer'), 
									'created' => array('require', 'none', '', '', 'date'), 
									'updated' => array('require', 'none', '', '', 'date'), 
								),
							);
							list($result, $message) = $this->create_table($new_table);
							if ($result) {
								list(,$new_table['sql']) = $this->get_create_table_sql();
								$this->options['tables'][] = $new_table;
								if (update_option(self::DOMAIN, $this->options)) {
									$msg = array('success', $message);
									$inherit_values = array();
								} else {
									$msg = array('warning', __('Failed to save option setting. Please note it is not saved if there is no change.', self::DOMAIN));
								}
							} else {
								$msg = array('warning', $message);
							}
						} else {
							$msg = array('warning', __('This table is already created.', self::DOMAIN));
						}
						$this->current_table = $prev_current_table;
					} else {
						$msg = array('warning', __('Is invalid call to create table.', self::DOMAIN));
					}
				}
			} else if ($handle == 'alter-table') {
				if ($section == 'confirm') {
					if (cdbt_get_boolean($inherit_values['init_set'])) 
						break;
					$target_modify_table_name = $inherit_values['target_table'];
					if ($target_modify_table_name == $inherit_values['previous_table_name'] && $this->check_table_exists($target_modify_table_name)) {
						if (cdbt_compare_var(empty($inherit_values['alter_table_sql']), true)) {
							$msg = array('information', __('Modify Table SQL is empty.', self::DOMAIN));
						} else {
							$sql_str = stripcslashes(strip_tags($inherit_values['alter_table_sql']));
							if (preg_match('/RENAME\s(.*)(\s|;|,|$)/iU', trim($sql_str), $matches)) {
								if ($this->check_table_exists(str_replace('`', '', $matches[1]))) {
									$msg = array('warning', __('Can not rename to already exists table.', self::DOMAIN));
									$inherit_values['target_table_name'] = $inherit_values['previous_table_name'];
									$inherit_values['alter_table_sql'] = preg_replace('/RENAME\s(.*)(\s|;|,|$)/iU', '', $sql_str);
								}
							}
							if (empty($msg)) {
								list($result, $fixed_sql) = $this->validate_alter_sql($target_modify_table_name, $sql_str);
								if ($result) {
									// sql validate done
									$inherit_values['substance_sql'] = rawurlencode($fixed_sql);
								} else {
									$msg = array('information', __('Modify Table SQL is invalid.', self::DOMAIN));
								}
							}
						}
						if (empty($msg) || $msg[0] == 'information') {
							if (cdbt_compare_var(empty($inherit_values['show_max_records']), true)) {
								$msg = array('warning', __('Show Max Records is empty.', self::DOMAIN));
							} else if (intval($inherit_values['show_max_records']) == 0) {
								$msg = array('warning', __('Show Max Records must be one more integer.', self::DOMAIN));
							}
							if (cdbt_compare_var(empty($inherit_values['view_role']), true) || intval($inherit_values['view_role']) < 1 || intval($inherit_values['view_role']) > 9) 
								$inherit_values['view_role'] = '1';
							if (cdbt_compare_var(empty($inherit_values['input_role']), true) || intval($inherit_values['input_role']) < 1 || intval($inherit_values['input_role']) > 9) 
								$inherit_values['input_role'] = '5';
							if (cdbt_compare_var(empty($inherit_values['edit_role']), true) || intval($inherit_values['edit_role']) < 1 || intval($inherit_values['edit_role']) > 9) 
								$inherit_values['edit_role'] = '7';
							if (cdbt_compare_var(empty($inherit_values['admin_role']), true) || intval($inherit_values['admin_role']) < 1 || intval($inherit_values['admin_role']) > 9) 
								$inherit_values['admin_role'] = '9';
							if (empty($msg)) {
								$section = 'run';
								$msg = array('confirmation', '{#code class="confirm-sql"#}'. $fixed_sql .'{#/code#}' . sprintf(__('Will modify a "%s" table. Would you like?', self::DOMAIN), $target_modify_table_name), __('Yes, modify.', self::DOMAIN));
							} else if ($msg[0] == 'information') {
								$section = 'run';
								$msg = array('confirmation', $msg[1] ."\n". __('So, it will not issue the SQL query and will do other setup updates.', self::DOMAIN) ."\n". sprintf(__('Will modify a "%s" table. Would you like?', self::DOMAIN), $target_modify_table_name), __('Yes, modify.', self::DOMAIN));
							}
						}
					} else {
						$msg = array('warning', __('will modify table is not exists table.', self::DOMAIN));
					}
				} else if ($section == 'run') {
					//var_dump($inherit_values);
					$prev_current_table = $this->current_table;
					$this->current_table = $inherit_values['target_table'];
					if ($this->check_table_exists()) {
						$alter_table_sql = rawurldecode($inherit_values['substance_sql']);
						preg_match('/RENAME\s(\`|)(.*)(\`|)(\s|\n|,|;|)/iU', $alter_table_sql, $matches);
						if (isset($matches[2]) && !empty($matches[2])) 
							$inherit_values['target_table_name'] = $matches[2];
						$wpdb->get_results($alter_table_sql);
						list($result, $new_sql) = $this->get_create_table_sql($inherit_values['target_table_name']);
						if ($result) {
							preg_match('/ENGINE=(.*)(\s|,|$)/iU', $new_sql, $matches);
							$inherit_values['target_table'] = $inherit_values['target_table_name'];
							$inherit_values['create_table_sql'] = $new_sql;
							$inherit_values['db_engine'] = $matches[1];
							$inherit_values['alter_table_sql'] = 'ALTER TABLE '. $inherit_values['target_table_name'] ." \n";
							$inherit_values['substance_sql'] = null;
							$inherit_values['init_set'] = true;
							$update_table = array(
								'table_name' => $inherit_values['target_table_name'], 
								'table_type' => 'enable_table', 
								'sql' => $new_sql, 
								'db_engine' => $matches[1], 
								'show_max_records' => intval($inherit_values['show_max_records']), 
								'roles' => array(
									'view_role' => $inherit_values['view_role'], 
									'input_role' => $inherit_values['input_role'], 
									'edit_role' => $inherit_values['edit_role'], 
									'admin_role' => $inherit_values['admin_role'], 
								), 
								'display_format' => array(
									// {column_name} => array('(require|optional)', '(show|hide|none)', '{display_item_name}', '{default_value}', '(string|integer|float|date|binary)')
									'ID' => array('require', 'none', '', '', 'integer'), 
									'created' => array('require', 'none', '', '', 'date'), 
									'updated' => array('require', 'none', '', '', 'date'), 
								),
							);
							foreach ($this->options['tables'] as $i => $table) {
								if ($table['table_name'] == $inherit_values['previous_table_name']) {
									$this->options['tables'][$i] = $update_table;
									break;
								}
							}
							if (update_option(self::DOMAIN, $this->options)) {
								$msg = array('success', __('Modified of the table schema has been successfully completed.', self::DOMAIN));
							} else {
								$msg = array('warning', __('Failed to save option setting. Please note it is not saved if there is no change.', self::DOMAIN));
							}
						} else {
							$msg = array('warning', __('Failed to get the table modify sql.', self::DOMAIN));
						}
					} else {
						$msg = array('warning', __('will modify table is not exists table.', self::DOMAIN));
					}
					if ($inherit_values['target_table_name'] != $inherit_values['previous_table_name']) {
						if ($prev_current_table == $inherit_values['previous_table_name']) {
							$this->current_table = $inherit_values['target_table_name'];
							update_option(self::DOMAIN . '_current_table', $this->current_table);
						} else {
							$this->current_table = $prev_current_table;
						}
					}
				} else {
					$msg = array('warning', __('Is invalid call to modify table.', self::DOMAIN));
				}
			}
			break;
		case 'tables': 
			$prev_current_table = $this->current_table;
			$target_table = $inherit_values['target_table'];
			if ($handle == 'data-import') {
				if ($section == 'confirm') {
					$section = 'run';
				} else if ($section == 'run') {
					$this->current_table = $target_table;
					if (cdbt_check_current_table_valid($this->current_table)) {
						if (preg_match('/text\/csv$/', $_FILES['csv_file']['type']) && $_FILES['csv_file']['size'] > 0) {
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
							list($result, $message) = $this->import_table('', $import_data);
							$msg = array(($result ? 'success' : 'warning'), $message);
						} else {
							$msg = array('warning', __('Invalid file was uploaded.', self::DOMAIN));
						}
						unlink($_FILES['csv_file']['tmp_name']);
					} else {
						$msg = array('warning', __('You can not handle to import data.', self::DOMAIN));
					}
					$this->current_table = $prev_current_table;
				} else {
					$msg = array('warning', __('Is invalid call to import data.', self::DOMAIN));
				}
			}
			if ($handle == 'data-export') {
				if ($section == 'confirm') {
					$section = 'run';
				} else if ($section == 'run') {
					$this->current_table = $target_table;
					if (cdbt_check_current_table_valid($this->current_table)) {
						//$export_token = wp_create_nonce(self::DOMAIN . '_csv_export');
						//$url = $this->dir_url . '/lib/media.php?tablename='. $this->current_table .'&token='. $export_token;
					} else {
						$msg = array('warning', __('You can not handle to export data.', self::DOMAIN));
					}
					$this->current_table = $prev_current_table;
				} else {
					$msg = array('warning', __('Is invalid call to export data.', self::DOMAIN));
				}
			}
			if ($handle == 'alter-table') {
				if ($section == 'confirm') {
					$section = 'run';
					$msg = array('confirmation', sprintf(__('Will modify schema of "%s" table. This handle is same that recreating the table. Would you like?', self::DOMAIN), $target_table), '');
				} else if ($section == 'run') {
					$section = 'confirm';
					$action = 'create';
					$handle = 'create-table';
				} else {
					$msg = array('warning', __('Is invalid call to alter table.', self::DOMAIN));
				}
			}
			if ($handle == 'truncate-table') {
				if ($section == 'confirm') {
					$section = 'run';
					$msg = array('confirmation', sprintf(__('Will truncate and initialize data of "%s" table. After this handled cannot resume. Would you like?', self::DOMAIN), $target_table), '');
				} else if ($section == 'run') {
					$this->current_table = $target_table;
					if (cdbt_check_current_table_valid($this->current_table)) {
						list($result, $message) = $this->truncate_table();
						$msg = array(($result ? 'success' : 'warning'), $message);
					} else {
						$msg = array('warning', __('You can not handle to truncate this table.', self::DOMAIN));
					}
					$this->current_table = $prev_current_table;
				} else {
					$msg = array('warning', __('Is invalid call to truncate table.', self::DOMAIN));
				}
			}
			if ($handle == 'drop-table') {
				if ($section == 'confirm') {
					$section = 'run';
					$msg = array('confirmation', sprintf(__('Will delete a "%s" table. After this handled cannot resume. Would you like?', self::DOMAIN), $target_table), '');
				} else if ($section == 'run') {
					$this->current_table = $target_table;
					if (cdbt_check_current_table_valid($this->current_table)) {
						list($result, $message) = $this->drop_table();
						$msg = array(($result ? 'success' : 'warning'), $message);
						if ($result) {
							foreach ($this->options['tables'] as $i => $table) {
								if (cdbt_compare_var($table['table_name'], $target_table)) {
									unset($this->options['tables'][$i]);
								}
							}
							update_option(self::DOMAIN, $this->options);
							if (cdbt_compare_var(get_option(self::DOMAIN . '_current_table'), $target_table)) 
								delete_option(self::DOMAIN . '_current_table');
							$this->current_table = ($prev_current_table != $target_table) ? $prev_current_table : '';
						} else {
							$this->current_table = $prev_current_table;
						}
					} else {
						$msg = array('warning', __('You can not handle to drop this table.', self::DOMAIN));
						$this->current_table = $prev_current_table;
					}
				} else {
					$msg = array('warning', __('Is invalid call to drop table.', self::DOMAIN));
				}
			}
			if ($handle == 'choise-current-table') {
				$prev_current_table = get_option(self::DOMAIN . '_current_table');
				$this->current_table = $target_table;
				update_option(self::DOMAIN . '_current_table', $this->current_table);
				if (cdbt_check_current_table_valid()) {
					update_option(self::DOMAIN . '_current_table', $target_table);
					$message = sprintf(__('The %s&apos;s table was chosen as the current table.', self::DOMAIN), $target_table);
					$msg_type = 'success';
				} else {
					if ($prev_current_table) {
						$this->current_table = $prev_current_table;
						update_option(self::DOMAIN . '_current_table', $prev_current_table);
					}
					$message = sprintf(__('Did not choose the %s&apos;s table.', self::DOMAIN), $target_table);
					$msg_type = 'warning';
				}
			}
			break;
	}
	// view tabs
	$nav_tabs_html = '<ul class="nav nav-tabs">%s</ul><!-- /.nav-tabs -->';
	$tabs_content_html = '<div class="tab-content">%s</div><!-- /.tab-content -->';
	foreach ($tabs as $tab_name => $active) {
		$nav_active_class = ($active) ? 'active' : '';
		$nav_tabs_list .= sprintf('<li class="%s"><a href="#cdbt-%s" data-toggle="tab">%s</a></li>', $nav_active_class, $tab_name, cdbt_translate_tab_name($tab_name));
		$tabs_content .= sprintf('<div class="tab-pane %s" id="cdbt-%s">%s</div>', $nav_active_class, $tab_name, cdbt_create_tab_content($tab_name, $_cdbt_token, $inherit_values));
	}
	if (!empty($msg)) {
		$cls_btn = $msg[0] == 'success' ? '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' : '';
		$information_html = sprintf('<div class="alert alert-%s tab-header">%s%s</div>', $msg[0], $msg[1], $cls_btn);
	}
} else {
	$_cdbt_token = wp_create_nonce(self::DOMAIN . '_admin');
	cdbt_create_console_menu($_cdbt_token);
	
	$information_html = sprintf('<div class="alert alert-danger">%s</div>', __('Invild access!', self::DOMAIN));
}

// display management console
cdbt_create_console_menu($_cdbt_token);

$contents_base = (!empty($msg) && $msg[0] == 'success') ? $nav_tabs_html . $information_html . $tabs_content_html : $nav_tabs_html . $tabs_content_html;
$contents_html = sprintf($contents_base, $nav_tabs_list, $tabs_content);
printf('<div class="tab-container">%s</div>', $contents_html);

cdbt_create_console_footer(((!empty($msg) && $msg[0] != 'success') ? $information_html : ''), ((!empty($msg) && $msg[0] == 'confirmation') ? true : false), ((!empty($msg) && isset($msg[2])) ? $msg[2] : ''), ((!empty($msg) && isset($msg[3])) ? $msg[3] : ''));


function cdbt_create_tab_content($tab_name, $nonce, $inherit_values=null) {
	global $wpdb, $cdbt;
	$cdbt_options = get_option(PLUGIN_SLUG);
	$controller_table = count($cdbt_options['tables']) > 0 ? $cdbt_options['tables'][0]['table_name'] : null;
	$content_html = null;
	$nonce_field = wp_nonce_field(PLUGIN_SLUG .'_admin', '_cdbt_token', true, false);
	switch ($tab_name) {
		case 'general': 
			// save to plugin option.
			require_once PLUGIN_TMPL_DIR . DS . 'cdbt-admin-general.php';
			break;
		case 'create': 
			// create database table.
			require_once PLUGIN_TMPL_DIR . DS . 'cdbt-admin-create.php';
			require_once PLUGIN_TMPL_DIR . DS . 'cdbt-admin-table-creator.php';
			break;
		case 'tables': 
			// enable tables list
			require_once PLUGIN_TMPL_DIR . DS . 'cdbt-admin-tables.php';
			break;
		default: 
			break;
	}
	return $content_html;
}

function cdbt_translate_tab_name($tab_name){
	$translate_tab_name = array(
		'general' => __('General setting', PLUGIN_SLUG), 
		'create' => __('Create table', PLUGIN_SLUG), 
		'tables' => __('Enable tables list', PLUGIN_SLUG), 
	);
	return $translate_tab_name[$tab_name];
}
