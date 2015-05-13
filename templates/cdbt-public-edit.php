<?php

function cdbt_render_edit_page($table=null, $mode=null, $_cdbt_token=null, $options=array()) {
	global $cdbt;
	foreach ($_POST as $k => $v) {
		${$k} = $v;
	}
	if (!empty($options)) {
		$is_bootstrap_style = isset($options['bootstrap_style']) ? $options['bootstrap_style'] : false;
		$is_display_title = isset($options['display_title']) ? $options['display_title'] : false;
		$is_display_list_num = isset($options['display_list_num']) ? $options['display_list_num'] : true;
		$is_enable_sort = isset($options['enable_sort']) ? $options['enable_sort'] : false;
		$entry_page = isset($options['entry_page']) ? $options['entry_page'] : '';
		$exclude_cols = isset($options['exclude_cols']) ? (array)$options['exclude_cols'] : array();
		$add_class = isset($options['add_class']) ? $options['add_class'] : '';
	} else {
		$is_bootstrap_style = $is_display_title = $is_display_search = $is_enable_sort = false;
		$is_display_list_num = true;
		$exclude_cols = array();
		$entry_page = $add_class = '';
	}
	$is_entry_page = false;
	if (!empty($entry_page)) {
		if (intval($entry_page) > 0) {
			$post = get_post(intval($entry_page));
		} else {
			$post_types = get_post_types( array('public'=>true, '_builtin'=>false), 'names', 'and' );
			if (is_array($post_types)) {
				array_unshift($post_types, 'post', 'page');
			}
			$posts = get_posts( array('numberposts'=>-1, 'post_type'=>$post_types, 'orderby'=>'ID', 'order'=>'ASC') );
			foreach ($posts as $one_post) {
				if ($one_post->post_name == $entry_page) {
					$post = $one_post;
					break;
				}
			}
		}
		if (!empty($post)) {
			$pattern = get_shortcode_regex();
			if (preg_match_all('/'. $pattern .'/s', $post->post_content, $matches) && array_key_exists(2, $matches) && in_array('cdbt-entry', $matches[2])) {
				if (preg_match('/table=(\'|\")'. $table .'(\'|\")/iU', $matches[0][0])) {
					$is_entry_page = true;
					$entry_page_url = str_replace(get_option('siteurl'), '', get_permalink($post->ID));
				}
			}
		}
	} else {
		// if entry_page is undefined
		$post_types = get_post_types( array('public'=>true, '_builtin'=>false), 'names', 'and' );
		if (is_array($post_types)) {
			array_unshift($post_types, 'post', 'page');
		}
		$pattern = get_shortcode_regex();
		$posts = get_posts( array('numberposts'=>-1, 'post_type'=>$post_types, 'orderby'=>'ID', 'order'=>'DESC') );
		foreach ($posts as $one_post) {
			if (preg_match_all('/'. $pattern .'/s', $one_post->post_content, $matches) && array_key_exists(2, $matches) && in_array('cdbt-entry', $matches[2])) {
				if (preg_match('/table=(\'|\")'. $table .'(\'|\")/iU', $matches[0][0])) {
					$is_entry_page = true;
					$entry_page_url = str_replace(get_option('siteurl'), '', get_permalink($one_post->ID));
					break;
				}
			}
		}
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
			$title = sprintf(__('edit data in %s table (table comment: %s)', CDBT_PLUGIN_SLUG), $table_name, $value);
		} else {
			$title = sprintf(__('edit data in %s table', CDBT_PLUGIN_SLUG), $table_name);
		}
		$information_html = '';
		if (wp_verify_nonce($_cdbt_token, CDBT_PLUGIN_SLUG .'_'. $mode)) {
			if (isset($action) && $action == 'delete') {
				$IDs = explode(',', $ID);
				if (is_array($IDs) && !empty($IDs)) {
					$information_html_base = '<div class="alert alert-info"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><ul>%s</ul></div>';
					$deleted_IDs = array();
					foreach ($IDs as $ID) {
						$is_deleted = $cdbt->delete_data($table_name, intval($ID));
						$deleted_IDs[$ID] = ((bool)$is_deleted) ? true : false;
					}
					$delete_id_list = null;
					foreach ($deleted_IDs as $deleted_ID => $deleted_status) {
						if ($deleted_status) {
							$delete_id_list .= sprintf('<li><p class="text-success">%s %s.</p></li>', __('Deleted the data of ID:', CDBT_PLUGIN_SLUG), $deleted_ID);
						} else {
							$delete_id_list .= sprintf('<li><p class="text-warning">%s %s.</p></li>', __('Failed to delete data of ID:', CDBT_PLUGIN_SLUG), $deleted_ID);
						}
					}
					$information_html = sprintf($information_html_base, $delete_id_list);
				}
			}
			
			$list_index_row = $list_rows = $pagination = null;
			$nonce_field = wp_nonce_field(CDBT_PLUGIN_SLUG .'_'. $mode, '_cdbt_token', true, false);
			
			$limit = $per_page;
			$offset = ($page_num - 1) * $limit;
			$view_cols = null; // If this value is null, will be all columns display.
			$order_by = (isset($sort_by) && !empty($sort_by) && isset($sort_order) && !empty($sort_order)) ? array($sort_by => $sort_order) : null;
			if (isset($action) && $action == 'search') {
				if (isset($search_key) && !empty($search_key)) {
					$data = $cdbt->find_data($table_name, $table_schema, $search_key, $view_cols, $order_by);
					$total_data = count($data);
					if ($total_data > $limit) {
						$data = $cdbt->find_data($table_name, $table_schema, $search_key, $view_cols, $order_by, $limit, $offset);
					}
				}
			} else {
				$data = $cdbt->get_data($table_name, $view_cols, null, $order_by, $limit, $offset);
				$total_data = $cdbt->get_data($table_name, 'COUNT(*)', null, null);
				if (is_array($total_data) && !empty($total_data)) {
					$total_data = array_shift($total_data);
					foreach ($total_data as $key => $val) {
						if ($key == 'COUNT(*)') {
							$total_data = intval($val);
							break;
						}
					}
				} else {
					$total_data = 0;
				}
				$total_data_info = $total_data > 0 ? sprintf(__('Total %d items', CDBT_PLUGIN_SLUG), $total_data) : '';
			}
			
			$page_slug = CDBT_PLUGIN_SLUG;
			$controller_block_base = '<form method="post" class="controller-form" role="form">%s';
			$all_checkbox_button_label = __('Checked items delete', CDBT_PLUGIN_SLUG);
			$current_sort_by = (isset($sort_by) && !empty($sort_by)) ? $sort_by : '';
			$current_order_by = (isset($sort_order) && !empty($sort_order)) ? $sort_order : 'DESC';
			$data_info = (isset($total_data_info) && !empty($total_data_info)) ? '<div class="navbar-inherit edit-adjust"><span class="label label-info">'. $total_data_info .'</span></div>' : '';
			if (isset($action) && $action == 'search' && isset($total_data) && $total_data > 0) {
				$hits_message = $total_data == 1 ? __('1 row matched', CDBT_PLUGIN_SLUG) : sprintf(__('%d rows matched', CDBT_PLUGIN_SLUG), $total_data);
				$search_hits = <<<HITS
			<div class="search-hits tooltip left">
				<div class="tooltip-arrow"></div>
				<div class="tooltip-inner">$hits_message</div>
			</div>
HITS;
			} else {
				$search_hits = '';
			}
			$search_key = (!isset($search_key)) ? '' : $search_key;
			$search_key_placeholder = __('Search keyword', CDBT_PLUGIN_SLUG);
			$search_button_label = __('Search', CDBT_PLUGIN_SLUG);
			$action = (isset($action) && !empty($action)) ? $action : '';
			$content = <<<NAV
<nav class="navbar navbar-default" role="navigation">
	<div class="container-fluid">
		<input type="hidden" name="table" value="$table_name" />
		<input type="hidden" name="page" value="$page_slug" />
		<input type="hidden" name="page_num" value="$page_num" />
		<input type="hidden" name="mode" value="$mode" />
		<input type="hidden" name="action" value="$action" />
		<input type="hidden" name="ID" value="" />
		<input type="hidden" name="sort_by" value="$current_sort_by" />
		<input type="hidden" name="sort_order" value="$current_order_by" />
		$nonce_field
	</div>
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<button type="button" class="btn btn-default navbar-btn" style="position: absolute; left: 14px;" id="checked_items_delete" data-mode="edit" data-action="delete" data-toggle="modal" data-target=".confirmation">
			<span class="glyphicon glyphicon-check"></span> $all_checkbox_button_label</button>
		$data_info
		<div class="navbar-form navbar-right" role="search">
			$search_hits
			<div class="form-group">
				<input type="text" name="search_key" class="form-control" placeholder="$search_key_placeholder" value="$search_key" />
			</div>
			<button type="button" class="btn btn-default" id="search_items" data-mode="$mode" data-action="search"><span class="glyphicon glyphicon-search"></span> $search_button_label</button>
		</div>
	</div>
</nav>
NAV;
			$controller_block = sprintf($controller_block_base, $content);
			
			if (!empty($data) && is_array($data)) {
				
				$list_num = 1 + (($page_num - 1) * $per_page);
				foreach ($data as $record) {
					$primary_key_name = $primary_key_value = null;
					foreach ($record as $key => $val) {
						if (empty($primary_key) && $table_schema[$key]['primary_key']) {
							$primary_key_name = $key;
							$primary_key_value = $val;
							break;
						}
					}
					if ($list_num == (1 + (($page_num - 1) * $per_page))) {
						$list_index_row = '<thead><tr>';
						$list_index_row .= '<th><input type="checkbox" id="all_checkbox_controller" /></th>';
						$list_index_row .= ($is_display_list_num) ? '<th>'. __('No.', CDBT_PLUGIN_SLUG) .'</th>' : '';
						foreach ($record as $key => $val) {
							if (!empty($exclude_cols) && in_array($key, $exclude_cols)) {
								continue;
							} else {
								if (array_key_exists($key, $table_schema)) {
									if ($is_enable_sort) {
										$column_type = $table_schema[$key]['type'];
										if (preg_match('/^((|tiny|small|medium|big)int|float|double(| precision)|real|dec(|imal)|numeric|fixed|bool(|ean)|bit)$/i', $column_type)) {
											$icon_type = strtoupper($current_order_by) == 'DESC' ? 'sort-by-order' : 'sort-by-order-alt';
										} else if (preg_match('/^((|var|national |n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set)$/i', $column_type)) {
											$icon_type = strtoupper($current_order_by) == 'DESC' ? 'sort-by-alphabet' : 'sort-by-alphabet-alt';
										} else {
											$icon_type = strtoupper($current_order_by) == 'DESC' ? 'sort-by-attributes' : 'sort-by-attributes-alt';
										}
										$toggle_order_by = strtoupper($current_order_by) == 'DESC' ? 'ASC' : 'DESC';
										$sort_switch = '<a href="#" class="sort-switch btn btn-default btn-xs" data-sort-column="'. $key .'" data-toggle-order="'. $toggle_order_by .'"><span class="glyphicon glyphicon-'. $icon_type .'"></span></a>';
									} else {
										$sort_switch = '';
									}
									$display_name = !empty($table_schema[$key]['logical_name']) ? $table_schema[$key]['logical_name'] : $key;
								}
								$list_index_row .= '<th id="index-'. $key .'">'. $display_name . $sort_switch .'</th>';
							}
						}
						$list_index_row .= '<th>'. __('Controll', CDBT_PLUGIN_SLUG) .'</th>';
						$list_index_row .= '</tr></thead>';
					}
					$list_rows .= '<tr>';
					$list_rows .= '<td><input type="checkbox" id="checkbox_controller_'. $list_num .'" class="inherit_checkbox" value="'. $primary_key_value .'" /></td>';
					$list_rows .= ($is_display_list_num) ? '<td>'. $list_num .'</td>' : '';
					$is_include_binary_file = false;
					foreach ($record as $key => $val) {
						//if (strtoupper($key) == 'ID') 
						//	$data_id = intval($val);
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
						$val = ($is_binary) ? '<a href="#" class="binary-file" data-id="'. $primary_key_value .'" data-origin-file="'. $origin_file .'"><span class="glyphicon glyphicon-paperclip"></span> '. $mine_type .' ('. ceil($file_size/1024) .'KB)</a>' : cdbt_str_truncate($val, 40, '...', true);
						if (!empty($exclude_cols) && in_array($key, $exclude_cols)) {
							continue;
						} else {
							$output = apply_filters('cdbt_edit_column_value', $val, $table_name, $key, $primary_key_value);
							$list_rows .= '<td>'. $output .'</td>';
						}
					}
					$list_rows .= '<td><div class="btn-group-vertical">';
					if ($is_entry_page) 
						$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm edit-row" action-url="'. $entry_page_url .'" data-id="'. $primary_key_value .'" data-mode="input" data-action="update" data-token="'. wp_create_nonce(CDBT_PLUGIN_SLUG .'_input') .'"><span class="glyphicon glyphicon-edit"></span> '. __('Edit', CDBT_PLUGIN_SLUG) .'</button>';
					if ($is_include_binary_file) 
						$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm download-binary" data-id="'. $primary_key_value .'" data-mode="edit" data-action="download" data-loading-text="'. __('Downloading...', CDBT_PLUGIN_SLUG) .'"><span class="glyphicon glyphicon-download"></span> '. __('Download', CDBT_PLUGIN_SLUG) .'</button>';
					$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm delete-row" data-id="'. $primary_key_value .'" data-mode="edit" data-action="delete" data-toggle="modal" data-target=".confirmation"><span class="glyphicon glyphicon-trash"></span> '. __('Delete', CDBT_PLUGIN_SLUG) .'</button>';
					$list_rows .= '</div></td>';
					$list_rows .= '</tr>';
					$list_num++;
				}
				
				$pagination = ($total_data > $per_page) ? cdbt_create_pagination(intval($page_num), intval($per_page), $total_data, $mode) : null;
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
				$display_html = sprintf($list_html, $title, $information_html.$controller_block, $list_index_row, '<tbody>' . $list_rows . '</tbody>', '</form>' . $pagination . $modal_container);
			} else {
				if (isset($action) && $action == 'search') {
					$msg_str = sprintf(__('No data to match for "%s".', CDBT_PLUGIN_SLUG), $search_key);
				} else {
					$msg_str = __('Data is none.', CDBT_PLUGIN_SLUG);
					$add_close_btn = false;
				}
				$close_btn = (isset($add_close_btn) && !$add_close_btn) ? '' : '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">'. __('Close', CDBT_PLUGIN_SLUG) .'</span></button>';
				$information_html = '<div class="alert alert-info">'. $close_btn . $msg_str .'</div>';
				$display_html = sprintf($list_html, $title, $controller_block, '', '', $information_html);
			}
		}
	} else {
		$display_html = '<div class="alert alert-info">'. __('The enabled tables is not exists currently.<br />Please create tables.', CDBT_PLUGIN_SLUG) .'</div>';
	}
	
	return $display_html;
}