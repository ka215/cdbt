<?php

function render_list_page($table=null, $mode=null, $_cdbt_token=null, $options=array()) {
	global $cdbt;
	foreach ($_REQUEST as $k => $v) {
		${$k} = $v;
	}
	if (!empty($options)) {
		$is_bootstrap_style = isset($options['bootstrap_style']) ? $options['bootstrap_style'] : false;
		$is_display_title = isset($options['display_title']) ? $options['display_title'] : false;
		$is_display_search = isset($options['display_search']) ? $options['display_search'] : false;
		$is_display_list_num = isset($options['display_list_num']) ? $options['display_list_num'] : true;
		$exclude_cols = isset($options['exclude_cols']) ? (array)$options['exclude_cols'] : array();
		$add_class = isset($options['add_class']) ? $options['add_class'] : '';
	} else {
		$is_bootstrap_style = $is_display_title = $is_display_search = false;
		$is_display_list_num = true;
		$exclude_cols = array();
		$add_class = '';
	}
	
	list($result, $table_name, $table_schema) = $cdbt->get_table_schema($table);
	if ($result && !empty($table_name) && !empty($table_schema)) {
		
		$page_num = (!isset($page_num) || empty($page_num)) ? 1 : intval($page_num);
		if (!isset($per_page) || empty($per_page)) {
			foreach ($cdbt->options['tables'] as $i => $table_opt) {
				if ($table_opt['table_name'] == $table_name) {
					$max_records = intval($table_opt['show_max_records']);
					break;
				}
			}
			$per_page = (!empty($max_records) && $max_records > 0) ? $max_records : intval(get_option('posts_per_page', 10));
		} else {
			$per_page = intval($per_page);
		}
		$table_class = $is_bootstrap_style ? 'table table-bordered table-striped table-hover ' : '';
		$title_attr = $is_bootstrap_style ? 'class="sr-only"' : 'style="display: none;"';
		if ($is_display_title) {
			$list_html = '<h3 class="dashboard-title">%s</h3>%s<div style="overflow-x: auto;"><table id="'. $table_name .'" class="'. $table_class . $add_class .'">%s%s</table></div>%s';
		} else {
			$list_html = '<span '. $title_attr .'>%s</span>%s<div style="overflow-x: auto;"><table id="'. $table_name .'" class="'. $table_class . $add_class .'" style="overflow-x: auto;">%s%s</table></div>%s';
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
			$view_cols = null; // If this value is null, will be all columns display.
			$order_by = null; // If this value is null, set the array('created' => 'DESC')
			if (isset($action) && $action == 'search') {
				if (isset($search_key) && !empty($search_key)) {
					$data = $cdbt->find_data($table_name, $table_schema, $search_key, $view_cols, $order_by, $limit);
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
			
			$page_slug = PLUGIN_SLUG;
			$controller_block_base = '<form method="post" class="controller-form" role="form">%s</form>';
			$search_key = (!isset($search_key)) ? '' : $search_key;
			$search_key_placeholder = __('Search keyword', PLUGIN_SLUG);
			$search_button_label = __('Search', PLUGIN_SLUG);
			$search_form = <<<SEARCH
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<div class="navbar-form navbar-right" role="search">
			<div class="form-group">
				<input type="text" name="search_key" class="form-control" placeholder="$search_key_placeholder" value="$search_key" />
			</div>
			<button type="button" class="btn btn-default" id="search_items" data-mode="$mode" data-action="search"><span class="glyphicon glyphicon-search"></span> $search_button_label</button>
		</div>
	</div>
SEARCH;
			$search_form = ($is_display_search) ? $search_form : null;
			$add_style = ($is_display_search) ? null : ' style="display: none;"';
			$content = <<<NAV
<nav class="navbar navbar-default"$add_style role="navigation">
	<div class="container-fluid">
		<input type="hidden" name="table" value="$table_name" />
		<input type="hidden" name="page" value="$page_slug" />
		<input type="hidden" name="mode" value="$mode" />
		<input type="hidden" name="action" value="" />
		$nonce_field
	</div>
	$search_form
</nav>
NAV;
			$controller_block = sprintf($controller_block_base, $content);
			
			if (!empty($data)) {
				$list_num = 1 + (($page_num - 1) * $per_page);
				foreach ($data as $record) {
					if ($list_num == (1 + (($page_num - 1) * $per_page))) {
						$list_index_row = '<thead><tr>';
						$list_index_row .= ($is_display_list_num) ? '<th>'. __('No.', PLUGIN_SLUG) .'</th>' : '';
						foreach ($record as $key => $val) {
							if (!empty($exclude_cols) && in_array($key, $exclude_cols)) {
								continue;
							} else {
								if (array_key_exists($key, $table_schema)) 
									$key = !empty($table_schema[$key]['logical_name']) ? $table_schema[$key]['logical_name'] : $key;
								$list_index_row .= '<th>'. $key .'</th>';
							}
						}
						$list_index_row .= '</tr></thead>';
					}
					$list_rows .= '<tr>';
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
						$val = ($is_binary) ? '<a href="#" class="binary-file" data-id="'. $data_id .'" data-origin-file="'. $origin_file .'"><span class="glyphicon glyphicon-paperclip"></span> '. $mine_type .' ('. ceil($file_size/1024) .'KB)</a>' : cdbt_str_truncate($val, 40, '...', true);
						if (!empty($exclude_cols) && in_array($key, $exclude_cols)) {
							continue;
						} else {
							$list_rows .= '<td>'. $val .'</td>';
						}
					}
					$list_rows .= '</tr>';
					$list_num++;
				}
				
				$pagination = ($total_data > $per_page) ? cdbt_create_pagination(intval($page_num), intval($per_page), $total_data, $mode) : null;
				$btn_cancel = __('Cancel', PLUGIN_SLUG);
				$btn_run = __('Yes, run', PLUGIN_SLUG);
				$modal_container = <<<MODAL
<!-- /* Modal */ -->
<div class="modal fade confirmation" tabindex="-1" role="dialog" aria-labelledby="confirmation" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove"></span></button>
        <h4 class="modal-title" style="width: 100%; background: none;"></h4>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> <span class="cancel-close">$btn_cancel</span></button>
        <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-ok"></span> <span class="run-process">$btn_run</span></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
MODAL;
				$display_html = sprintf($list_html, $title, $information_html.$controller_block, $list_index_row, '<tbody>' . $list_rows . '</tbody>', $pagination . $modal_container);
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
	
	return $display_html;
}