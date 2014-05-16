<?php

function render_input_page($table=null, $mode=null, $_cdbt_token=null, $options=array()) {
	global $cdbt;
	foreach ($_REQUEST as $k => $v) {
		${$k} = (is_array($v)) ? $v : stripcslashes($v);
//var_dump("\$_REQUEST['{$k}'] = '$v' \n");
	}
	
	
	list($result, $table_name, $table_schema) = $cdbt->get_table_schema($table);
	if ($result && !empty($table_name) && !empty($table_schema)) {
		$is_update_mode = (isset($ID) && !empty($ID)) ? true : false;
		if ($is_update_mode) {
			$title_str = sprintf(__('Update to %s table', PLUGIN_SLUG), $table_name);
		} else {
			$title_str = sprintf(__('Regist to %s table', PLUGIN_SLUG), $table_name);
		}
		$page_title = '<h3 class="dashboard-title">'. $title_str .'</h3>';
		if ($is_update_mode) {
			$ID = intval($ID);
			if (isset($action) && !empty($action) && $action == 'update') {
				// update data load
				$data = $cdbt->get_data($table_name, null, array('ID' => $ID));
				$data = array_shift($data);
				if (!empty($data)) {
					foreach ($data as $column_name => $column_value) {
						${$table_name.'-'.$column_name} = $column_value;
					}
					$info_msg = null;
				} else {
					$info_msg = __('No data applicable.', PLUGIN_SLUG);
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
		if (wp_verify_nonce($_cdbt_token, PLUGIN_SLUG .'_'. $mode)) {
			$form_html = '<div>%s<form method="post" id="'. $table_name .'" enctype="multipart/form-data" role="form">';
			$form_html .= ($is_update_mode) ? '<input type="hidden" name="ID" value="'. $ID .'" />' : '';
			$form_html .= '<input type="hidden" name="mode" value="input" />';
			$form_html .= '<input type="hidden" name="action" value="'. $action .'" />';
			$form_html .= wp_nonce_field(PLUGIN_SLUG .'_'. $mode, '_cdbt_token', true, false);
			$form_html .= '%s</form></div>';
			$form_objects = array();
			$post_values = $validate_values = array();
			foreach ($table_schema as $column_name => $column_schema) {
				$value = isset(${$table_name.'-'.$column_name}) ? ${$table_name.'-'.$column_name} : '';
				$form_objects[] = create_form($table_name, $column_name, $column_schema, $value);
				
				$post_values[$column_name] = (is_array($value)) ? implode(',', $value) : $value;
				if (!preg_match('/^(ID|created|updated)$/i', $column_name)) {
					$validate_result = $cdbt->validate_data($column_schema, $value);
					if (!array_shift($validate_result)) 
						$validate_values[$column_name] = array_pop($validate_result);
				}
			}
			if ($is_update_mode) {
				$form_button = '<div class="center-block on-bottom-margin entry-button-block"><div class="text-left">' . create_button('stateful', array(__('update data', PLUGIN_SLUG), __('now updating...', PLUGIN_SLUG)), 'entry-submit', 'primary', 'confirm', 'send');
				$form_button .= '<a href="'. $_wp_http_referer .'" class="btn btn-default" style="margin-left: 1em;"><span class="glyphicon glyphicon-remove"></span> ' . __('Cancel', PLUGIN_SLUG) . '</a>';
				$form_button .= '</div></div>';
			} else {
				$form_button = '<div class="center-block on-bottom-margin entry-button-block"><div class="text-left">' . create_button('stateful', array(__('entry data', PLUGIN_SLUG), __('now sending...', PLUGIN_SLUG)), 'entry-submit', 'primary', 'confirm', 'send') . '</div></div>';
			}
			$form_objects[] = $form_button;
			
			if (!empty($post_values)) {
				if (empty($validate_values)) {
					if ($is_update_mode) {
						if ($action == 'confirm') 
							$update_id = $cdbt->update_data($table_name, $ID, $post_values, $table_schema);
					} else {
						if ($action == 'confirm') 
							$insert_id = $cdbt->insert_data($table_name, $post_values, $table_schema);
					}
				} else {
					$err_list = null;
					foreach ($validate_values as $key => $val) {
						$key = (!empty($table_schema[$key]['logical_name'])) ? $table_schema[$key]['logical_name'] : $key;
						$err_list .= sprintf(__("<li>%s is %s.</li>\n", PLUGIN_SLUG), $key, $val);
					}
					$info_msg = '<div class="alert alert-warning"><ul>'. $err_list .'</ul></div>';
				}
			}
			
			if ((isset($insert_id) && (bool)$insert_id) || (isset($update_id) && (bool)$update_id)) {
				if ($is_update_mode) {
					$complete_msg = __('Data update successful. Data ID is : ', PLUGIN_SLUG) . $update_id;
				} else {
					$complete_msg = __('Completed new add data. Data ID is : ', PLUGIN_SLUG) . $insert_id;
				}
				$display_html = sprintf('%s<div class="alert alert-success">%s</div>', $page_title, $complete_msg);
			} else {
				if (isset($action) && $action == 'confirm') {
					if ((isset($insert_id) && !(bool)$insert_id) || (isset($update_id) && !(bool)$update_id)) {
						if ($is_update_mode) {
							$err_msg = sprintf(__('Did not update the data ID: %s. Please check if there is a change item.', PLUGIN_SLUG), $ID);
						} else {
							$err_msg = __('Failed to add the data.', PLUGIN_SLUG);
						}
						$info_msg = '<div class="alert alert-danger">'. $err_msg .'</div>';
					}
				} else {
					if (!$is_update_mode) 
						$info_msg = null;
				}
				if (!empty($info_msg)) {
					$message = str_replace("\n", '<br />', strip_tags($info_msg));
				} else {
					$message = null;
				}
				$modal_kicker = <<<MKICK
<div style="display: none;">
	<div class="modal-kicker">$message</div>
</div>
MKICK;
				$translate_text = array(
					__('loading', PLUGIN_SLUG), 
					__('Yes, run', PLUGIN_SLUG), 
					__('Please confirm', PLUGIN_SLUG), 
				);
				$entry_scripts = <<<EOS
<script>
jQuery(function($){
	$('#entry-submit').on('click', function(){
		var btn = $(this);
		btn.button('{$translate_text[0]}');
		$(this).parents('form').children('input[name="action"]').val($(this).attr('data-action'));
		$(this).parents('form').submit();
	});
	
	if ($('.modal-kicker').html() != '') {
		if ($('.modal-kicker').hasClass('show-run')) {
			var run_process = ($('.modal-kicker').attr('data-run-label') == '') ? '{$translate_text[1]}' : $('.modal-kicker').attr('data-run-label');
		} else {
			var run_process = '';
		}
		show_modal('{$translate_text[2]}', $('.modal-kicker').html(), run_process);
		$('.modal.confirmation').modal('show');
		$('.modal-kicker').remove();
	}
	
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
	
	function ime_mode_inactive(event, targetObj) {
		if ($('body').hasClass('locale-ja')) {
			if (event == 'focus' || event == 'click') {
				targetObj.attr('type', 'tel').css('ime-mode', 'disabled');
			} else if (event == 'blur') {
				targetObj.attr('type', 'text');
			}
		}
	}
	
});
</script>
EOS;
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
				$display_html = sprintf($form_html, $page_title, implode("\n", $form_objects) . $modal_kicker . $modal_container . $entry_scripts);
			}
		} else {
			$display_html = '<div class="alert alert-danger">'. __('Invild access!', PLUGIN_SLUG) .'</div>';
		}
	} else {
		$display_html = '<div class="alert alert-info">'. __('The enabled tables is not exists currently.<br />Please create tables.', PLUGIN_SLUG) .'</div>';
	}
	
	return $display_html;
}
