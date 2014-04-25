<?php

function render_list_page($table=null, $mode=null, $_cdbt_token=null, $options=array()) {
	global $cdbt;
	foreach ($_REQUEST as $k => $v) {
		${$k} = $v;
	}
	
	list($result, $table_name, $table_schema) = $cdbt->get_table_schema($table);
	if ($result && !empty($table_name) && !empty($table_schema)) {
		
		$page_num = (!isset($page_num) || empty($page_num)) ? 1 : intval($page_num);
		if (!isset($per_page) || empty($per_page)) {
			foreach ($cdbt->options['tables'] as $i => $table) {
				if ($table['table_name'] == $cdbt->current_table) {
					$data = intval($table['show_max_records']);
					break;
				}
			}
			$per_page = (!empty($data) && $data > 0) ? $data : intval(get_option('posts_per_page', 10));
		} else {
			$per_page = intval($per_page);
		}
		if (!empty($options) && isset($options['display_title']) && $options['display_title']) {
			$list_html = '<h3 class="dashboard-title">%s</h3>%s<div style="overflow-x: auto;"><table id="'. $table_name .'" class="table table-bordered table-striped table-hover">%s%s</table></div>%s';
		} else {
			$list_html = '<span class="sr-only">%s</span>%s<div style="overflow-x: auto;"><table id="'. $table_name .'" class="table table-bordered table-striped table-hover" style="overflow-x: auto;">%s%s</table></div>%s';
		}
		list($result, $value) = $cdbt->get_table_comment($table_name);
		if ($result) {
			$title = sprintf(__('%s table (table comment: %s)', PLUGIN_SLUG), $table_name, $value);
		} else {
			$title = sprintf(__('%s table', PLUGIN_SLUG), $table_name);
		}
		$information_html = '';
		if (wp_verify_nonce($_cdbt_token, PLUGIN_SLUG .'_'. $mode)) {
			$list_index_row = $list_rows = $pagination = null;
			$nonce_field = wp_nonce_field(PLUGIN_SLUG .'_'. $mode, '_cdbt_token', true, false);
			
			$limit = $per_page;
			$offset = ($page_num - 1) * $limit;
			$view_cols = null; // array('ID', 'code_number', 'name', 'bin_data', 'created', 'updated'); // This value is null when all columns display.
			$order_by = null; // null eq array('created' => 'DESC')
			if (isset($action) && $action == 'search') {
				if (isset($search_key) && !empty($search_key)) {
					$data = $cdbt->find_data($table_name, $table_schema, $search_key, $view_cols, $order_by, $limit, $offset);
					if (count($data) == $limit) {
						$total_data = count($cdbt->find_data($table_name, $table_schema, $search_key, $view_cols, $order_by));
					} else {
						$total_data = count($data);
					}
				}
			}
			if (!isset($data) || empty($data)) {
				// $order_by['name'] = 'ASC';
				$data = $cdbt->get_data($table_name, $view_cols, null, $order_by, $limit, $offset);
				$total_data = $cdbt->get_data($table_name, 'COUNT(*)');
				foreach (array_shift($total_data) as $key => $val) {
					if ($key == 'COUNT(*)') {
						$total_data = intval($val);
						break;
					}
				}
			}
			if (!empty($options) && isset($options['display_title']) && $options['display_search']) {
				$is_controller = true;
			} else {
				$is_controller = false;
			}
			$is_checkbox_controller = false;
			$is_display_list_num = true;
			
			if ($is_controller) {
				$page_slug = PLUGIN_SLUG;
				$controller_block_base = '<form method="post" class="controller-form" role="form">%s</form>';
				$controller_block_title = __('Cosole', PLUGIN_SLUG);
				$search_key = (!isset($search_key)) ? '' : $search_key;
				$search_key_placeholder = __('Search keyword', PLUGIN_SLUG);
				$search_button_label = __('Search', PLUGIN_SLUG);
				$content = <<<NAV
<nav class="navbar navbar-default" role="navigation">
	<div class="container-fluid">
		<span class="navbar-brand">$controller_block_title</span>
		<input type="hidden" name="table" value="$table_name" />
		<input type="hidden" name="page" value="$page_slug" />
		<input type="hidden" name="mode" value="$mode" />
		<input type="hidden" name="action" value="" />
		$nonce_field
	</div>
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<div class="navbar-form navbar-right" role="search">
			<div class="form-group">
				<input type="text" name="search_key" class="form-control" placeholder="$search_key_placeholder" value="$search_key" />
			</div>
			<button type="submit" class="btn btn-default" id="search_items" data-mode="$mode" data-action="search"><span class="glyphicon glyphicon-search"></span> $search_button_label</button>
		</div>
	</div>
</nav>
<script>
jQuery(function($){
	$('#search_items').on('click', function(){
		$('.controller-form input[name="action"]').val($(this).attr('data-action'));
	});
});
</script>
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
						$list_index_row .= ($is_display_list_num) ? '<th>'. __('No.', PLUGIN_SLUG) .'</th>' : '';
						foreach ($record as $key => $val) {
							if (array_key_exists($key, $table_schema)) 
								$key = !empty($table_schema[$key]['logical_name']) ? $table_schema[$key]['logical_name'] : $key;
							$list_index_row .= '<th>'. $key .'</th>';
						}
						$list_index_row .= ($mode == 'edit') ? '<th>'. __('Controll', PLUGIN_SLUG) .'</th>' : '';
						$list_index_row .= '</tr></thead>';
					}
					$list_rows .= '<tr>';
					$list_rows .= ($is_checkbox_controller) ? '<th><input type="checkbox" id="checkbox_controller_'. $list_num .'" class="inherit_checkbox" value="'. $record->ID .'" /></th>' : '';
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
						$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm edit-row" data-id="'. $record->ID .'" data-mode="input" data-action="update"><span class="glyphicon glyphicon-edit"></span> '. __('Edit', PLUGIN_SLUG) .'</button>';
						if ($is_include_binary_file) 
							$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm download-binary" data-id="'. $record->ID .'" data-mode="edit" data-action="download"><span class="glyphicon glyphicon-download"></span> '. __('Download', PLUGIN_SLUG) .'</button>';
						$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm delete-row" data-id="'. $record->ID .'" data-mode="edit" data-action="delete" data-toggle="modal" data-target=".confirmation"><span class="glyphicon glyphicon-trash"></span> '. __('Delete', PLUGIN_SLUG) .'</button>';
						$list_rows .= '</div></td>';
					}
					$list_rows .= '</tr>';
					$list_num++;
				}
				
				$pagination = ($total_data > $per_page) ? create_pagination(intval($page_num), intval($per_page), $total_data, $mode) : null;
				$pagination = (($mode == 'edit') ? '</form>' : '') . $pagination;
				$display_html = sprintf($list_html, $title, $information_html.$controller_block, $list_index_row, '<tbody>' . $list_rows . '</tbody>', $pagination);
			} else {
				if (isset($action) && $action == 'search') {
					$msg_str = sprintf(__('No data to match for "%s".', PLUGIN_SLUG), $search_key);
				} else {
					$msg_str = __('Data is none.', PLUGIN_SLUG);
				}
				$information_html = '<div class="alert alert-info">'. $msg_str .'</div>';
				$display_html = sprintf($list_html, $title, $controller_block, '', '', $information_html);
			}
		}
	} else {
		$display_html = '<div class="alert alert-info">'. __('The enabled tables is not exists currently.<br />Please create tables.', PLUGIN_SLUG) .'</div>';
	}
	
	//create_console_footer();
	
	return $display_html;
}