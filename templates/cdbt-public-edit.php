<?php

function render_edit_page($table=null, $mode=null, $_cdbt_token=null, $options=array()) {
	global $cdbt;
	foreach ($_REQUEST as $k => $v) {
		${$k} = $v;
//var_dump("\$_REQUEST['{$k}'] = '$v' \n");
	}
	if (!empty($options)) {
		$is_bootstrap_style = isset($options['bootstrap_style']) ? $options['bootstrap_style'] : false;
		$is_display_title = isset($options['display_title']) ? $options['display_title'] : false;
		$is_display_list_num = isset($options['display_list_num']) ? $options['display_list_num'] : true;
		$entry_page = isset($options['entry_page']) ? $options['entry_page'] : '';
		$exclude_cols = isset($options['exclude_cols']) ? (array)$options['exclude_cols'] : array();
		$add_class = isset($options['add_class']) ? $options['add_class'] : '';
	} else {
		$is_bootstrap_style = $is_display_title = $is_display_search = false;
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
			if (is_array($post_types) && !empty($post_types)) {
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
				$is_entry_page = true;
				$entry_page_url = str_replace(get_option('siteurl'), '', get_permalink($post->ID));
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
			$title = sprintf(__('edit data in %s table (table comment: %s)', PLUGIN_SLUG), $table_name, $value);
		} else {
			$title = sprintf(__('edit data in %s table', PLUGIN_SLUG), $table_name);
		}
		$information_html = '';
		if (wp_verify_nonce($_cdbt_token, PLUGIN_SLUG .'_'. $mode)) {
			if (isset($action) && $action == 'delete') {
				$IDs = explode(',', $ID);
				if (is_array($IDs) && !empty($IDs)) {
					$information_html_base = '<div class="alert alert-info"><ul>%s</ul></div>';
					$deleted_IDs = array();
					foreach ($IDs as $ID) {
						$is_deleted = $cdbt->delete_data($table_name, intval($ID));
						$deleted_IDs[$ID] = ((bool)$is_deleted) ? true : false;
					}
					$delete_id_list = null;
					foreach ($deleted_IDs as $deleted_ID => $deleted_status) {
						if ($deleted_status) {
							$delete_id_list .= sprintf('<li><p class="text-success">%s %s.</p></li>', __('Deleted the data of ID:', PLUGIN_SLUG), $deleted_ID);
						} else {
							$delete_id_list .= sprintf('<li><p class="text-warning">%s %s.</p></li>', __('Failed to delete data of ID:', PLUGIN_SLUG), $deleted_ID);
						}
					}
					$information_html = sprintf($information_html_base, $delete_id_list);
				}
			}
			
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
			$controller_block_base = '<form method="post" class="controller-form" role="form">%s';
			$all_checkbox_button_label = __('Checked items delete', PLUGIN_SLUG);
			$search_key = (!isset($search_key)) ? '' : $search_key;
			$search_key_placeholder = __('Search keyword', PLUGIN_SLUG);
			$search_button_label = __('Search', PLUGIN_SLUG);
			$translate_text = array(
				__('Deleting confirmation', PLUGIN_SLUG), 
				__('ID: %s of data will be deleted. Would you like?', PLUGIN_SLUG), 
				__('Delete', PLUGIN_SLUG), 
				__('Alert', PLUGIN_SLUG), 
				__('Checked items is none!', PLUGIN_SLUG), 
				__('Search keyword is none!', PLUGIN_SLUG), 
				__('Download binary files', PLUGIN_SLUG), 
				__('Stored image', PLUGIN_SLUG), 
				__('Stored binary file', PLUGIN_SLUG), 
			);
			$plugin_dir_path = plugins_url(PLUGIN_SLUG);
			$ajax_nonce = wp_create_nonce(PLUGIN_SLUG . '_ajax');
			$media_nonce = wp_create_nonce(PLUGIN_SLUG . '_media');
			$content = <<<NAV
<nav class="navbar navbar-default" role="navigation">
	<div class="container-fluid">
		<input type="hidden" name="table" value="$table_name" />
		<input type="hidden" name="page" value="$page_slug" />
		<input type="hidden" name="mode" value="$mode" />
		<input type="hidden" name="action" value="" />
		<input type="hidden" name="ID" value="" />
		$nonce_field
	</div>
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<button type="button" class="btn btn-default navbar-btn" style="position: absolute;" id="checked_items_delete" data-mode="edit" data-action="delete" data-toggle="modal" data-target=".confirmation">
			<span class="glyphicon glyphicon-check"></span> $all_checkbox_button_label</button>
		<div class="navbar-form navbar-right" role="search">
			<div class="form-group">
				<input type="text" name="search_key" class="form-control" placeholder="$search_key_placeholder" value="$search_key" />
			</div>
			<button type="button" class="btn btn-default" id="search_items" data-mode="$mode" data-action="search"><span class="glyphicon glyphicon-search"></span> $search_button_label</button>
		</div>
	</div>
</nav>
<script>
jQuery(function($){
	$('#all_checkbox_controller').on('click', function(){
		if ($(this).is(':checked')) {
			$('.inherit_checkbox').each(function(){
				if (!$(this).is(':checked')) 
					$(this).prop('checked', 'checked');
			});
		} else {
			$('.inherit_checkbox').each(function(){
				if ($(this).is(':checked')) 
					$(this).removeAttr('checked');
			});
		}
	});
	
	$('#checked_items_delete').on('click', function(){
		index = 0;
		target_ids = new Array();
		var modal_obj = $('.modal.confirmation .modal-content');
		$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
		$('.controller-form input[name="action"]').val($(this).attr('data-action'));
		$('.inherit_checkbox').each(function(){
			index = (typeof index == 'undefined') ? 0 : index;
			if ($(this).is(':checked')) {
				target_ids.push(Number($(this).val()));
			}
			index++;
		});
		if (typeof target_ids == 'object') {
			if (target_ids.length > 0) {
				$('.controller-form input[name="ID"]').val(target_ids.join(','));
				show_modal('{$translate_text[0]}', '{$translate_text[1]}'.replace('%s', target_ids.join(',')), '{$translate_text[2]}');
			} else {
				show_modal('{$translate_text[3]}', '{$translate_text[4]}', '');
			}
		}
	});
	
	$('#search_items').on('click', function(){
		if ($('.controller-form input[name="search_key"]').val() != '') {
			$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
			$('.controller-form input[name="action"]').val($(this).attr('data-action'));
			$('.controller-form').submit();
		} else {
			show_modal('{$translate_text[3]}', '{$translate_text[5]}', '');
			$('.modal.confirmation').modal('show');
		}
	});
	
	$('.edit-row').on('click', function(){
		$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
		$('.controller-form input[name="action"]').val($(this).attr('data-action'));
		$('.controller-form input[name="ID"]').val($(this).attr('data-id'));
		$('.controller-form input[name="_cdbt_token"]').val($(this).attr('data-token'));
		$('.controller-form input[name="_wp_http_referer"]').remove();
		$('.controller-form input[name="search_key"]').remove();
//		$('.controller-form').attr('method', 'get');
		$('.controller-form').attr('action', $(this).attr('action-url'));
		$('.controller-form').submit();
	});
	
	$('.download-binary').on('click', function(){
		var btn = $(this);
		btn.addClass('btn-primary').button('loading');
		$.ajax({
			type: 'POST', 
			url: '{$plugin_dir_path}/lib/ajax.php', 
			data: { mode: $(this).attr('data-action'), id: $(this).attr('data-id'), table: '$table_name', token: '$ajax_nonce' }
		}).done(function(res){
			show_modal('{$translate_text[6]}', res, '');
			$('.modal.confirmation').modal('show');
		}).always(function(){
			btn.removeClass('btn-primary').button('reset');
		});
	});
	
	$('.delete-row').on('click', function(){
		$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
		$('.controller-form input[name="action"]').val($(this).attr('data-action'));
		$('.controller-form input[name="ID"]').val($(this).attr('data-id'));
		show_modal('{$translate_text[0]}', '{$translate_text[1]}'.replace('%s', $(this).attr('data-id')), '{$translate_text[2]}');
	});
	
	function show_modal(title, body, run_process) {
		var modal_obj = $('.modal.confirmation .modal-content');
		modal_obj.find('.modal-title').text(title);
		modal_obj.children('.modal-body').html(body);
		if (run_process != '') {
			modal_obj.find('.run-process').text(run_process).show();
			modal_obj.find('.run-process').click(function(){
				$('form[role="form"]').each(function(){
					if ($(this).hasClass('controller-form')) {
						$('.controller-form').submit();
					}
				});
			});
		} else {
			modal_obj.find('.run-process').parent('button').hide();
		}
	}
	
	$('.text-collapse').on('click', function(){
		var current_display_content = $(this).html();
		$(this).html($(this).attr('full-content')).attr('full-content', current_display_content);
	});
	
	$('.binary-file').on('click', function(){
		if ($(this).text().indexOf('image/') > 0) {
			var img = '<img src="{$plugin_dir_path}/lib/media.php?id='+$(this).attr('data-id')+'&filename='+$(this).attr('data-origin-file')+'&table=$table_name&token=$media_nonce" width="100%" class="img-thumbnail">';
			show_modal('{$translate_text[7]}', img, '');
			$('.modal.confirmation').modal('show');
		} else {
			show_modal('{$translate_text[8]}', decodeURI($(this).attr('data-origin-file')), '');
			$('.modal.confirmation').modal('show');
		}
	});
});
</script>
NAV;
			$controller_block = sprintf($controller_block_base, $content);
			
			if (!empty($data)) {
				$list_num = 1 + (($page_num - 1) * $per_page);
				foreach ($data as $record) {
					if ($list_num == (1 + (($page_num - 1) * $per_page))) {
						$list_index_row = '<thead><tr>';
						$list_index_row .= '<th><input type="checkbox" id="all_checkbox_controller" /></th>';
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
						$list_index_row .= '<th>'. __('Controll', PLUGIN_SLUG) .'</th>';
						$list_index_row .= '</tr></thead>';
					}
					$list_rows .= '<tr>';
					$list_rows .= '<td><input type="checkbox" id="checkbox_controller_'. $list_num .'" class="inherit_checkbox" value="'. $record->ID .'" /></td>';
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
						if (!empty($exclude_cols) && in_array($key, $exclude_cols)) {
							continue;
						} else {
							$list_rows .= '<td>'. $val .'</td>';
						}
					}
					$list_rows .= '<td><div class="btn-group-vertical">';
					if ($is_entry_page) 
						$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm edit-row" action-url="'. $entry_page_url .'" data-id="'. $record->ID .'" data-mode="input" data-action="update" data-token="'. wp_create_nonce(PLUGIN_SLUG .'_input') .'"><span class="glyphicon glyphicon-edit"></span> '. __('Edit', PLUGIN_SLUG) .'</button>';
					if ($is_include_binary_file) 
						$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm download-binary" data-id="'. $record->ID .'" data-mode="edit" data-action="download" data-loading-text="'. __('Downloading...', PLUGIN_SLUG) .'"><span class="glyphicon glyphicon-download"></span> '. __('Download', PLUGIN_SLUG) .'</button>';
					$list_rows .= "\t" . '<button type="button" class="btn btn-default btn-sm delete-row" data-id="'. $record->ID .'" data-mode="edit" data-action="delete" data-toggle="modal" data-target=".confirmation"><span class="glyphicon glyphicon-trash"></span> '. __('Delete', PLUGIN_SLUG) .'</button>';
					$list_rows .= '</div></td>';
					$list_rows .= '</tr>';
					$list_num++;
				}
				
				$pagination = ($total_data > $per_page) ? create_pagination(intval($page_num), intval($per_page), $total_data, $mode) : null;
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
				$display_html = sprintf($list_html, $title, $information_html.$controller_block, $list_index_row, '<tbody>' . $list_rows . '</tbody>', '</form>' . $pagination . $modal_container);
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