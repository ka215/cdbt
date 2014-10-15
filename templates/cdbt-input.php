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
	foreach ($table_schema as $col_name => $col_schema) {
		if ($col_schema['primary_key']) {
			$primary_key_name = $col_name;
			break;
		}
	}
	if ($is_update_mode) {
		if (isset($action) && !empty($action) && $action == 'update') {
			// update data load
			$data = $this->get_data($table_name, null, array($primary_key_name => $ID), null);
			$data = array_shift($data);
			if (!empty($data)) {
				foreach ($data as $column_name => $column_value) {
					${$table_name.'-'.$column_name} = $column_value;
				}
				$info_msg = null;
			} else {
				$info_msg = __('No data applicable.', self::DOMAIN);
			}
		} else {
			$action = 'confirm';
		}
	} else {
		$action = (!isset($action) || empty($action)) ? '' : $action;
	}
	foreach ($_FILES as $k => $v) {
		if ($v['size'] > 0) {
			$bin_data = file_get_contents($v['tmp_name']);
			${$k} = serialize(array(
				'origin_file' => rawurlencode($v['name']), 
				'mine_type' => $v['type'], 
				'file_size' => $v['size'], 
				'bin_data' => $bin_data, 
				'hash' => md5($bin_data), 
			));
		} else {
			if (!empty($origin_bin_data)) 
				${$k} = rawurldecode($origin_bin_data);
		}
	}
	if (wp_verify_nonce($_cdbt_token, self::DOMAIN .'_'. $mode)) {
		$form_html = '<div>%s<form method="post" id="'. $table_name .'" enctype="multipart/form-data" role="form">';
		$form_html .= ($is_update_mode) ? '<input type="hidden" name="'. $primary_key_name .'" value="'. $ID .'" />' : '';
		$form_html .= '<input type="hidden" name="mode" value="input" />';
		$form_html .= '<input type="hidden" name="action" value="'. $action .'" />';
		$form_html .= wp_nonce_field(self::DOMAIN .'_'. $mode, '_cdbt_token', true, false);
		$form_html .= '%s</form></div>';
		$form_objects = array();
		$post_values = $validate_values = array();
		foreach ($table_schema as $column_name => $column_schema) {
			$value = isset(${$table_name.'-'.$column_name}) ? ${$table_name.'-'.$column_name} : '';
			$form_objects[] = cdbt_create_form($table_name, $column_name, $column_schema, $value);
			
			$post_values[$column_name] = (is_array($value)) ? implode(',', $value) : $value;
			if (!preg_match('/^('. $primary_key_name .'|created|updated)$/i', $column_name)) {
				$validate_result = $this->validate_data($column_schema, $value);
				if (!array_shift($validate_result)) 
					$validate_values[$column_name] = array_pop($validate_result);
			}
		}
		$form_objects[] = '<div class="center-block on-bottom-margin"><div class="text-left">' . cdbt_create_button('stateful', array(__('submit data', self::DOMAIN), __('now sending...', self::DOMAIN)), 'entry-submit', 'primary', 'confirm', 'send') . '</div></div>';
		
		if (!empty($post_values)) {
			if (empty($validate_values)) {
				if ($is_update_mode) {
					if ($action == 'confirm') 
						$update_id = $this->update_data($table_name, $ID, $post_values, $table_schema);
				} else {
					if ($action == 'confirm') 
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
		
		if (is_admin()) 
			cdbt_create_console_menu($_cdbt_token);
		
		if ((isset($insert_id) && (bool)$insert_id) || (isset($update_id) && (bool)$update_id)) {
			if ($is_update_mode) {
				$complete_msg = sprintf(__('Data update successful. Data %s is : %s', self::DOMAIN), $primary_key_name, $update_id);
			} else {
				$complete_msg = sprintf(__('Completed new add data. Data %s is : %s', self::DOMAIN), $primary_key_name, $insert_id);
			}
			printf('%s<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">%s</span></button>%s</div>', $page_title, __('Close', self::DOMAIN), $complete_msg);
		} else {
			if (isset($action) && $action == 'confirm') {
				if ((isset($insert_id) && !(bool)$insert_id) || (isset($update_id) && !(bool)$update_id)) {
					if ($is_update_mode) {
						$err_msg = sprintf(__('Did not update the data %s: %s. Please check if there is a change item.', self::DOMAIN), $primary_key_name, $ID);
					} else {
						$err_msg = __('Failed to add the data.', self::DOMAIN);
					}
					$info_msg = '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">'. __('Close', self::DOMAIN) .'</span></button>'. $err_msg .'</div>';
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

$info_msg = empty($info_msg) ? null : $info_msg;

cdbt_create_console_footer($info_msg);
