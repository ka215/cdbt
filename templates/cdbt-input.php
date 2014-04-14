<?php
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) die();

if (is_admin() && !check_admin_referer(self::DOMAIN .'_input', '_cdbt_token')) 
	die(__('access is not from admin panel!', self::DOMAIN));

list($result, $table_name, $table_schema) = $this->get_table_schema();
if ($result && !empty($table_name) && !empty($table_schema)) {
	foreach ($_REQUEST as $k => $v) {
		${$k} = (is_array($v)) ? $v : stripcslashes($v);
	}
	$is_update_mode = (isset($ID) && !empty($ID)) ? true : false;
	if ($is_update_mode) {
		$title_str = sprintf(__('Update to %s table', self::DOMAIN), $table_name);
	} else {
		$title_str = sprintf(__('Regist to %s table', self::DOMAIN), $table_name);
	}
	$page_title = '<h3 class="dashboard-title">'. $title_str .'</h3>';
	if ($is_update_mode) {
		$ID = intval($ID);
		if (isset($action) && !empty($action) && $action == 'update') {
			// update data load
			$data = $this->get_data($table_name, null, array('ID' => $ID));
			$data = array_shift($data);
			if (!empty($data)) {
				foreach ($data as $column_name => $column_value) {
					${$table_name.'-'.$column_name} = $column_value;
				}
				$info_msg = null;
			} else {
				$info_msg = __('No data applicable.', self::DOMAIN);
			}
		}
	}
	foreach ($_FILES as $k => $v) {
		if ($v['size'] > 0) {
			$fh = fopen($v['tmp_name'], 'rb');
			$bin_data = fread($fh, filesize($v['tmp_name']));
			fclose($fh);
			${$k} = serialize(array(
				'origin_file' => rawurlencode($v['name']), 
				'mine_type' => $v['type'], 
				'file_size' => $v['size'], 
				'bin_data' => addslashes($bin_data), 
				'hash' => md5(addslashes($bin_data))
			));
		} else {
			if (!empty($origin_bin_data)) 
				${$k} = rawurldecode($origin_bin_data);
		}
	}
	if (wp_verify_nonce($_cdbt_token, self::DOMAIN .'_'. $mode)) {
		$form_html = '<div>%s<form method="post" id="'. $table_name .'" enctype="multipart/form-data" role="form">';
		$form_html .= ($is_update_mode) ? '<input type="hidden" name="ID" value="'. $ID .'" />' : '';
		$form_html .= '<input type="hidden" name="mode" value="input" />';
		$form_html .= '<input type="hidden" name="action" value="" />';
		$form_html .= wp_nonce_field(self::DOMAIN .'_'. $mode, '_cdbt_token', true, false);
		$form_html .= '%s</form></div>';
		$form_objects = array();
		$post_values = $validate_values = array();
		foreach ($table_schema as $column_name => $column_schema) {
			$value = isset(${$table_name.'-'.$column_name}) ? ${$table_name.'-'.$column_name} : '';
			$form_objects[] = create_form($table_name, $column_name, $column_schema, $value);
			
			$post_values[$column_name] = (is_array($value)) ? implode(',', $value) : $value;
			if (!preg_match('/^(ID|created|updated)$/i', $column_name)) {
				$validate_result = $this->validate_data($column_schema, $value);
				if (!array_shift($validate_result)) 
					$validate_values[$column_name] = array_pop($validate_result);
			}
		}
		$form_objects[] = '<div class="center-block on-bottom-margin"><div class="text-left">' . create_button('stateful', array(__('submit data', self::DOMAIN), __('now sending...', self::DOMAIN)), 'entry-submit', 'primary', 'confirm', 'send') . '</div></div>';
		
		if (!empty($post_values)) {
			if (empty($validate_values)) {
				if ($is_update_mode) {
					if ($action == 'confirm') 
						$update_id = $this->update_data($table_name, $ID, $post_values, $table_schema);
				} else {
					$insert_id = $this->insert_data($table_name, $post_values, $table_schema);
				}
			} else {
				$err_list = null;
				foreach ($validate_values as $key => $val) {
					$key = (!empty($table_schema[$key]['logical_name'])) ? $table_schema[$key]['logical_name'] : $key;
					$err_list .= sprintf(__("<li>%s is %s.</li>\n", self::DOMAIN), $key, $val);
				}
				$info_msg = '<div class="alert alert-warning"><ul>'. $err_list .'</ul></div>';
			}
		}
		
		create_console_menu($_cdbt_token);
		
		if ((isset($insert_id) && (bool)$insert_id) || (isset($update_id) && (bool)$update_id)) {
			if ($is_update_mode) {
				$complete_msg = __('Data update successful. Data ID is : ', self::DOMAIN) . $update_id;
			} else {
				$complete_msg = __('Completed new add data. Data ID is : ', self::DOMAIN) . $insert_id;
			}
			printf('%s<div class="alert alert-success">%s</div>', $page_title, $complete_msg);
		} else {
			if (isset($action) && $action == 'confirm') {
				if ((isset($insert_id) && !(bool)$insert_id) || (isset($update_id) && !(bool)$update_id)) {
					if ($is_update_mode) {
						$err_msg = sprintf(__('Did not update the data ID: %s. Please check if there is a change item.', self::DOMAIN), $ID);
					} else {
						$err_msg = __('Failed to add the data.', self::DOMAIN);
					}
					$info_msg = '<div class="alert alert-danger">'. $err_msg .'</div>';
				}
			} else {
				if (!$is_update_mode) 
					$info_msg = null;
			}
			//printf($form_html, $page_title.$info_msg, implode("\n", $form_objects));
			printf($form_html, $page_title, implode("\n", $form_objects));
		}
	} else {
?>
	<div class="alert alert-danger"><?php _e('Invild access!', self::DOMAIN); ?></div>
<?php
	}
} else {
?>
	<div class="alert alert-info"><?php _e('The enabled tables is not exists currently.<br />Please create tables.', self::DOMAIN); ?></div>
<?php
}

create_console_footer($info_msg);
