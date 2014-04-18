<?php
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) die();

if (is_admin() && !check_admin_referer(self::DOMAIN .'_edit', '_cdbt_token')) 
	die(__('access is not from admin panel!', self::DOMAIN));

foreach ($_REQUEST as $k => $v) {
	${$k} = $v;
}
if (!isset($mode)) 
	$mode = 'edit';
if (!isset($_cdbt_token)) 
	$_cdbt_token = wp_create_nonce(self::DOMAIN .'_'. $mode);


list($result, $table_name, $table_schema) = $this->get_table_schema();
if ($result && !empty($table_name) && !empty($table_schema)) {
	create_console_menu($_cdbt_token);
	
	$page_num = (!isset($page_num) || empty($page_num)) ? 1 : intval($page_num);
	if (!isset($per_page) || empty($per_page)) {
		for ($i=0; $i<count($this->options['tables']); $i++) {
			if ($this->options['tables'][$i]['table_name'] == $this->current_table) 
				$data = intval($this->options['tables'][$i]['show_max_records']);
		}
		$per_page = (!empty($data) && intval($data) > 0) ? intval($data) : intval(get_option('posts_per_page', 10));
	} else {
		$per_page = intval($per_page);
	}
	$list_html = '<h3 class="dashboard-title">%s</h3>%s<table id="'. $table_name .'" class="table table-bordered table-striped table-hover">%s%s</table>%s';
	list($result, $value) = $this->get_table_comment($table_name);
	if ($result) {
		$title = sprintf(__('edit data in %s table (table comment: %s)', self::DOMAIN), $table_name, $value);
	} else {
		$title = sprintf(__('edit data in %s table', self::DOMAIN), $table_name);
	}
	$information_html = '';
	if (wp_verify_nonce($_cdbt_token, self::DOMAIN .'_'. $mode)) {
		if (isset($action) && $action == 'delete') {
			$IDs = explode(',', $ID);
			if (is_array($IDs) && !empty($IDs)) {
				$information_html_base = '<div class="alert alert-info"><ul>%s</ul></div>';
				$deleted_IDs = array();
				foreach ($IDs as $ID) {
					$is_deleted = $this->delete_data($table_name, intval($ID));
					$deleted_IDs[$ID] = ((bool)$is_deleted) ? true : false;
				}
				$delete_id_list = null;
				foreach ($deleted_IDs as $deleted_ID => $deleted_status) {
					if ($deleted_status) {
						$delete_id_list .= sprintf('<li><p class="text-success">%s %s.</p></li>', __('Deleted the data of ID:', self::DOMAIN), $deleted_ID);
					} else {
						$delete_id_list .= sprintf('<li><p class="text-warning">%s %s.</p></li>', __('Failed to delete data of ID:', self::DOMAIN), $deleted_ID);
					}
				}
				$information_html = sprintf($information_html_base, $delete_id_list);
			}
		}
		$list_index_row = $list_rows = $pagination = null;
		$nonce_field = wp_nonce_field(self::DOMAIN .'_'. $mode, '_cdbt_token', true, false);
		
		$limit = $per_page;
		$offset = ($page_num - 1) * $limit;
		$view_cols = null; // array('ID', 'code_number', 'name', 'bin_data', 'created', 'updated'); // This value is null when all columns display.
		$order_by = null; // null eq array('created' => 'DESC')
		if (isset($action) && $action == 'search') {
			if (isset($search_key) && !empty($search_key)) {
				$data = $this->find_data($table_name, $table_schema, $search_key, $view_cols, $order_by, $limit, $offset);
				if (count($data) == $limit) {
					$total_data = count($this->find_data($table_name, $table_schema, $search_key, $view_cols, $order_by));
				} else {
					$total_data = count($data);
				}
			}
		} else {
			// $order_by['name'] = 'ASC';
			$data = $this->get_data($table_name, $view_cols, null, $order_by, $limit, $offset);
			$total_data = $this->get_data($table_name, 'COUNT(*)');
			foreach (array_shift($total_data) as $key => $val) {
				if ($key == 'COUNT(*)') {
					$total_data = intval($val);
					break;
				}
			}
		}
		$is_controller = true;
		$is_checkbox_controller = true;
		$is_display_list_num = false;
		
		if ($is_controller) {
			$page_slug = self::DOMAIN;
			$controller_block_base = '<form method="post" class="controller-form" role="form">%s';
			$controller_block_base .= ($mode == 'list') ? '</form>' : '';
			$controller_block_title = __('Cosole', self::DOMAIN);
			$all_checkbox_button_label = __('Checked items delete', self::DOMAIN);
			$search_key = (!isset($search_key)) ? '' : $search_key;
			$search_key_placeholder = __('Search keyword', self::DOMAIN);
			$search_button_label = __('Search', self::DOMAIN);
			$content = <<<NAV
<nav class="navbar navbar-default" role="navigation">
	<div class="container-fluid">
		<span class="navbar-brand">$controller_block_title</span>
		<input type="hidden" name="page" value="$page_slug" />
		<input type="hidden" name="mode" value="$mode" />
		<input type="hidden" name="action" value="" />
		<input type="hidden" name="ID" value="" />
		$nonce_field
	</div>
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<button type="button" class="btn btn-default navbar-btn" id="checked_items_delete" data-mode="edit" data-action="delete" data-toggle="modal" data-target=".confirmation">
			<span class="glyphicon glyphicon-check"></span> $all_checkbox_button_label</button>
		<div class="navbar-form navbar-right" role="search">
			<div class="form-group">
				<input type="text" name="search_key" class="form-control" placeholder="$search_key_placeholder" value="$search_key" />
			</div>
			<button type="button" class="btn btn-default" id="search_items" data-mode="$mode" data-action="search"><span class="glyphicon glyphicon-search"></span> $search_button_label</button>
		</div>
	</div>
</nav>
NAV;
			$controller_block = sprintf($controller_block_base, $content);
		} else {
			$controller_block = null;
		}
		
		if (!empty($data)) {
			$list_num = 1 + (($page_num - 1) * $per_page);
			foreach ($data as $record) {
				if ($list_num == (1 + (($page_num - 1) * $per_page))) {
					$list_index_row = '<thead><tr>';
					$list_index_row .= ($is_checkbox_controller) ? '<th><input type="checkbox" id="all_checkbox_controller" /></th>' : '';
					$list_index_row .= ($is_display_list_num) ? '<th>'. __('No.', self::DOMAIN) .'</th>' : '';
					foreach ($record as $key => $val) {
						if (array_key_exists($key, $table_schema)) 
							$key = $table_schema[$key]['logical_name'];
						$list_index_row .= '<th>'. $key .'</th>';
					}
					$list_index_row .= ($mode == 'edit') ? '<th>'. __('Controll', self::DOMAIN) .'</th>' : '';
					$list_index_row .= '</tr></thead>';
				}
				$list_rows .= '<tr>';
				$list_rows .= ($is_controller) ? '<td><input type="checkbox" id="checkbox_controller_'. $list_num .'" class="inherit_checkbox" value="'. $record->ID .'" /></td>' : '';
				$list_rows .= ($is_display_list_num) ? '<td>'. $list_num .'</td>' : '';
				$is_include_binary_file = false;
				foreach ($record as $key => $val) {
					if (strtoupper($key) == 'ID') 
						$data_id = intval($val);
					// strlen('a:*:{s:11:"origin_file";') = 24
					$is_binary = (preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($val, 0, 24))) ? true : false;
					$is_include_binary_file = ($is_binary) ? true : $is_include_binary_file;
					if ($is_binary) {
						eval('$tmp = array(' . trim(preg_replace('/(a:\d+:{|(|;)s:\d+:|(|;)i:|"$)/', ",", substr($val, 0, strpos($val, 'bin_data'))), ',,') . ');');
						foreach ($tmp as $i => $val) {
							if ($val == 'origin_file') $origin_file = $tmp[intval($i)+1];
							if ($val == 'mine_type') $mine_type = $tmp[intval($i)+1];
							if ($val == 'file_size') $file_size = $tmp[intval($i)+1];
						}
					}
					$val = ($is_binary) ? '<a href="#" class="binary-file" data-id="'. $data_id .'" data-origin-file="'. $origin_file .'"><span class="glyphicon glyphicon-paperclip"></span> '. $mine_type .' ('. ceil($file_size/1024) .'KB)</a>' : str_truncate($val, 40, '...', true);
					$list_rows .= '<td>'. $val .'</td>';
				}
				if ($mode == 'edit') {
					$list_rows .= '<td><div class="btn-group-vertical">';
					$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm edit-row" data-id="'. $record->ID .'" data-mode="input" data-action="update" data-token="'. wp_create_nonce(self::DOMAIN .'_input') .'"><span class="glyphicon glyphicon-edit"></span> '. __('Edit', self::DOMAIN) .'</button>';
					if ($is_include_binary_file) 
						$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm download-binary" data-id="'. $record->ID .'" data-mode="edit" data-action="download" data-loading-text="'. __('Downloading...', self::DOMAIN) .'"><span class="glyphicon glyphicon-download"></span> '. __('Download', self::DOMAIN) .'</button>';
					$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm delete-row" data-id="'. $record->ID .'" data-mode="edit" data-action="delete" data-toggle="modal" data-target=".confirmation"><span class="glyphicon glyphicon-trash"></span> '. __('Delete', self::DOMAIN) .'</button>';
					$list_rows .= '</div></td>';
				}
				$list_rows .= '</tr>';
				$list_num++;
			}
			
			$pagination = ($total_data > $per_page) ? create_pagination(intval($page_num), intval($per_page), $total_data, $mode) : null;
			$pagination = (($mode == 'edit') ? '</form>' : '') . $pagination;
			printf($list_html, $title, $information_html.$controller_block, $list_index_row, '<tbody>' . $list_rows . '</tbody>', $pagination);
		} else {
			if (isset($action) && $action == 'search') {
				$msg_str = sprintf(__('No data to match for "%s".', self::DOMAIN), $search_key);
			} else {
				$msg_str = __('Data is none.', self::DOMAIN);
			}
			$information_html = '<div class="alert alert-info">'. $msg_str .'</div>';
			printf($list_html, $title, $controller_block, '', '', $information_html);
		}
	}
} else {
?>
	<div class="alert alert-info"><?php _e('The enabled tables is not exists currently.<br />Please create tables.', self::DOMAIN); ?></div>
<?php
}

create_console_footer();
