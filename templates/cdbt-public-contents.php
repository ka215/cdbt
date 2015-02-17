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
				array_push($classes, 'table-bordered', 'table-striped', 'table-hover', 'table-extract');
			} else {
				$classes = array_merge($classes, explode(' ', $add_class));
			}
		} else {
			$classes = explode(' ', $add_class);
		}
		$element_class = empty($classes) || !is_array($classes) ? '' : ' class="'. implode(' ', $classes) .'"';
		$content_html = '<table id="'. $table_name .'"'. $element_class .'>%s</table>%s';
		$primary_key_column = 'ID';
		foreach ($table_schema as $field_name => $field_schemas) {
			if ($field_schemas['primary_key']) {
				$primary_key_column = $field_name;
				break;
			}
		}
		if (wp_verify_nonce($_cdbt_token, CDBT_PLUGIN_SLUG .'_'. $mode)) {
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
					$is_include_binary_file = false;
					$binary_files = 0;
					if (!empty($order_cols)) {
						$data_id = intval($record->$primary_key_column);
						foreach ($order_cols as $order_col) {
							if ($row == 1 && array_key_exists($order_col, $table_schema)) {
								$index_name = !empty($table_schema[$order_col]['logical_name']) ? $table_schema[$order_col]['logical_name'] : $order_col;
								$list_index_row .= '<th id="index-'. $order_col .'">'. $index_name .'</th>';
							}
							$is_binary = (preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($record->$order_col, 0, 24))) ? true : false;
							$is_include_binary_file = ($is_binary) ? true : $is_include_binary_file;
							if ($is_binary) {
								eval('$tmp = array(' . trim(preg_replace('/(a:\d+:{|(|;)s:\d+:|(|;)i:|"$)/', ",", substr($record->$order_col, 0, strpos($record->$order_col, 'bin_data'))), ',,') . ');');
								foreach ($tmp as $i => $val) {
									if ($val == 'origin_file') $origin_file = $tmp[intval($i)+1];
									if ($val == 'mine_type') $mine_type = $tmp[intval($i)+1];
									if ($val == 'file_size') $file_size = $tmp[intval($i)+1];
								}
								$binary_files++;
							}
							$column_value = ($is_binary) ? '<a href="#" class="binary-file" data-id="'. $data_id .'" data-origin-file="'. $origin_file .'"><span class="glyphicon glyphicon-paperclip"></span> '. $mine_type .' ('. ceil($file_size/1024) .'KB)</a>' : $record->$order_col;
							
							$last_data_line .= '<td>'. $column_value .'</td>';
						}
					} else {
						foreach ($record as $column_name => $column_value) {
							if ($column_name == $primary_key_column) 
								$data_id = intval($column_value);
							if ($row == 1 && array_key_exists($column_name, $table_schema)) {
								$index_name = !empty($table_schema[$column_name]['logical_name']) ? $table_schema[$column_name]['logical_name'] : $column_name;
								$list_index_row .= '<th id="index-'. $column_name .'">'. $index_name .'</th>';
							}
							$is_binary = (preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($column_value, 0, 24))) ? true : false;
							$is_include_binary_file = ($is_binary) ? true : $is_include_binary_file;
							if ($is_binary) {
								eval('$tmp = array(' . trim(preg_replace('/(a:\d+:{|(|;)s:\d+:|(|;)i:|"$)/', ",", substr($column_value, 0, strpos($column_value, 'bin_data'))), ',,') . ');');
								foreach ($tmp as $i => $val) {
									if ($val == 'origin_file') $origin_file = $tmp[intval($i)+1];
									if ($val == 'mine_type') $mine_type = $tmp[intval($i)+1];
									if ($val == 'file_size') $file_size = $tmp[intval($i)+1];
								}
								$binary_files++;
							}
							$column_value = ($is_binary) ? '<a href="#" class="binary-file" data-id="'. $data_id .'" data-origin-file="'. $origin_file .'"><span class="glyphicon glyphicon-paperclip"></span> '. $mine_type .' ('. ceil($file_size/1024) .'KB)</a>' : $column_value;
							
							$last_data_line .= '<td>'. $column_value .'</td>';
						}
					}
					$last_data[] = $last_data_line . '<tr>';
					$row++;
				}
				$list_index_row .= '</tr></thead>';
				$in_contents = ($display_index_row ? $list_index_row : '') . "\n<tbody>\n" . implode("\n", $last_data) . "\n</tbody>\n";
				$btn_cancel = __('Cancel', CDBT_PLUGIN_SLUG);
				$btn_run = __('Yes, run', CDBT_PLUGIN_SLUG);
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
				$modal_container = $binary_files > 0 ? $modal_container : '';
				$content_html = sprintf($content_html, $in_contents, $modal_container);
			} else {
				$msg_str = __('Data is none.', CDBT_PLUGIN_SLUG);
				$add_close_btn = false;
				$close_btn = (isset($add_close_btn) && !$add_close_btn) ? '' : '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">'. __('Close', CDBT_PLUGIN_SLUG) .'</span></button>';
				$content_html = '<div class="alert alert-info">'. $close_btn . $msg_str .'</div>';
			}
		}
	} else {
		$content_html = '<div class="alert alert-info">'. __('The enabled tables is not exists currently.<br />Please create tables.', CDBT_PLUGIN_SLUG) .'</div>';
	}
	
	return $content_html;
}