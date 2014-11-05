<?php

function cdbt_render_contents($table=null, $mode=null, $_cdbt_token=null, $options=array()) {
	global $cdbt;
	if (!empty($options)) {
		foreach ($options as $option_name => $option_value) {
			${$option_name} = $option_value;
		}
	} else {
		$bootstrap_style = $display_index_row = true;
		$narrow_keyword = '';
		$display_cols = $order_cols = array();
		$sort_order = array('created'=>'desc');
		$limit_items = 5;
		$add_class = '';
	}
	
	list($result, $table_name, $table_schema) = $cdbt->get_table_schema($table);
	if ($result && !empty($table_name) && !empty($table_schema)) {
		
		if ($bootstrap_style) {
			$classes = array('table');
			if (empty($add_class)) {
				array_push($classes, 'table-bordered', 'table-striped', 'table-hover');
			} else {
				$classes = array_merge($classes, explode(' ', $add_class));
			}
		} else {
			$classes = explode(' ', $add_class);
		}
		$element_class = empty($classes) || !is_array($classes) ? '' : ' class="'. implode(' ', $classes) .'"';
		$content_html = '<table'. $element_class .'>%s</table>';
		if (wp_verify_nonce($_cdbt_token, PLUGIN_SLUG .'_'. $mode)) {
			$view_cols = !empty($display_cols) ? implode(',', $display_cols) : '*';
			if (!empty($narrow_keyword)) {
				foreach ($narrow_keyword as $key => $val) {
					$is_find = is_int($key) ? true : false;
					break;
				}
				if (!$is_find) {
					$data = $cdbt->get_data($table_name, $view_cols, $narrow_keyword, $sort_order, $limit_items, 0);
				} else {
					$data = $cdbt->find_data($table_name, $table_schema, $narrow_keyword, $view_cols, $sort_order, $limit_items, 0);
				}
			} else {
				$data = $cdbt->get_data($table_name, $view_cols, null, $sort_order, $limit_items, 0);
			}
			
			if (!empty($data) && is_array($data)) {
				$row = 1;
				$last_data = array();
				foreach ($data as $record) {
					$last_data_line = '<tr>';
					if ($row == 1) {
						$list_index_row = '<thead><tr>';
					}
					if (!empty($order_cols)) {
						foreach ($order_cols as $order_col) {
							if ($row == 1 && array_key_exists($order_col, $table_schema)) {
								$index_name = !empty($table_schema[$order_col]['logical_name']) ? $table_schema[$order_col]['logical_name'] : $order_col;
								$list_index_row .= '<th id="index-'. $order_col .'">'. $index_name .'</th>';
							}
							$last_data_line .= '<td>'. $record->$order_col .'</td>';
						}
					} else {
						foreach ($record as $column_name => $column_value) {
							if ($row == 1 && array_key_exists($column_name, $table_schema)) {
								$index_name = !empty($table_schema[$column_name]['logical_name']) ? $table_schema[$column_name]['logical_name'] : $column_name;
								$list_index_row .= '<th id="index-'. $column_name .'">'. $index_name .'</th>';
							}
							$last_data_line .= '<td>'. $column_value .'</td>';
						}
					}
					$last_data[] = $last_data_line . '<tr>';
					$row++;
				}
				$list_index_row .= '</tr></thead>';
				$in_contents = ($display_index_row ? $list_index_row : '') . "\n<tbody>\n" . implode("\n", $last_data) . "\n</tbody>\n";
				$content_html = sprintf($content_html, $in_contents);
			} else {
				$msg_str = __('Data is none.', PLUGIN_SLUG);
				$add_close_btn = false;
				$close_btn = (isset($add_close_btn) && !$add_close_btn) ? '' : '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">'. __('Close', PLUGIN_SLUG) .'</span></button>';
				$content_html = '<div class="alert alert-info">'. $close_btn . $msg_str .'</div>';
			}
		}
	} else {
		$content_html = '<div class="alert alert-info">'. __('The enabled tables is not exists currently.<br />Please create tables.', PLUGIN_SLUG) .'</div>';
	}
	
	return $content_html;
}