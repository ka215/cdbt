<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

header("Content-type: application/x-javascript");
?>
jQuery(document).ready(function($){
	
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
				show_modal('<?php _e('Deleting confirmation', PLUGIN_SLUG); ?>', '<?php _e('ID: %s of data will be deleted. Would you like?', PLUGIN_SLUG); ?>'.replace('%s', target_ids.join(',')), '<?php _e('Delete', PLUGIN_SLUG); ?>');
			} else {
				show_modal('<?php _e('Alert', PLUGIN_SLUG); ?>', '<?php _e('Checked items is none!', PLUGIN_SLUG); ?>', '');
			}
		}
	});
	
	$('#search_items').on('click', function(){
		if ($('.controller-form input[name="search_key"]').val() != '') {
			$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
			$('.controller-form input[name="action"]').val($(this).attr('data-action'));
			$('.controller-form').submit();
		} else {
			show_modal('<?php _e('Alert', PLUGIN_SLUG); ?>', '<?php _e('Search keyword is none!', PLUGIN_SLUG); ?>', '');
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
		$('.controller-form').attr('method', 'get');
		$('.controller-form').submit();
	});
	
	$('.download-binary').on('click', function(){
		var btn = $(this);
		btn.addClass('btn-primary').button('loading');
		$.ajax({
			type: 'POST', 
			url: "<?php echo plugins_url(PLUGIN_SLUG) . '/lib/ajax.php'; ?>", 
			data: { mode: $(this).attr('data-action'), id: $(this).attr('data-id'), token: '<?php echo wp_create_nonce(PLUGIN_SLUG . '_ajax'); ?>' }
		}).done(function(res){
			show_modal('<?php _e('Download binary files', PLUGIN_SLUG); ?>', res, '');
			$('.modal.confirmation').modal('show');
		}).always(function(){
			btn.removeClass('btn-primary').button('reset');
		});
	});
	
	$('.delete-row').on('click', function(){
		$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
		$('.controller-form input[name="action"]').val($(this).attr('data-action'));
		$('.controller-form input[name="ID"]').val($(this).attr('data-id'));
		show_modal('<?php _e('Deleting confirmation', PLUGIN_SLUG); ?>', '<?php _e('ID: %s of data will be deleted. Would you like?', PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-id')), '<?php _e('Delete', PLUGIN_SLUG); ?>');
	});
	
	$('#entry-submit').on('click', function(){
		var btn = $(this);
		btn.button('<?php _e('loading', PLUGIN_SLUG); ?>');
		$(this).parents('form').children('input[name="action"]').val($(this).attr('data-action'));
		$(this).parents('form').submit();
	});
	
	$('footer').children('div').each(function(){
		if ($(this).hasClass('modal-kicker')) {
			if ($(this).hasClass('show-run')) {
				var run_process = ($(this).attr('data-run-label') == '') ? '<?php _e('Yes, run', PLUGIN_SLUG); ?>' : $(this).attr('data-run-label');
			} else {
				var run_process = '';
			}
			show_modal('<?php _e('Please confirm', PLUGIN_SLUG); ?>', $(this).html(), run_process);
			$('.modal.confirmation').modal('show');
			$(this).remove();
		}
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
					} else {
						if ($(this).parents('.tab-pane').hasClass('active')) {
							$(this).children('input[name="section"]').val('run');
							$(this).submit();
						}
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
	
	function set_current_table_name(e) {
		ime_mode_inactive(e.type, $('#cdbt_naked_table_name'));
		$('code').each(function(){
			if ($(this).hasClass('simulate_table_name')) {
				var table_name = $('#cdbt_naked_table_name').val();
				if ($('#cdbt_use_wp_prefix_for_newtable').is(':checked')) 
					table_name = $('#cdbt_use_wp_prefix_for_newtable').attr('data-prefix') + table_name;
				$(this).text(table_name);
			}
		});
	}
	
	$('#cdbt_naked_table_name').on('click focus blur', set_current_table_name);
	$('#cdbt_use_wp_prefix_for_newtable').on('click blur', set_current_table_name);
	
	$('.sql-editor a').on('click', function() {
		if ($(this).attr('id') == 'sql-statements-mode') {
			$('#sql-statements-mode').addClass('active');
			$('#sql-table-creator').removeClass('active');
		} else {
			$('#sql-statements-mode').removeClass('active');
			$('#sql-table-creator').addClass('active');
		}
	});
	
	$('div[data-toggle="buttons"] > label').hover(function() {
		var target_class = '.' + $(this).children('input').attr('name') + '-helper';
		var tips_content = $(this).children('input').attr('data-helper-tips');
		$(target_class).html('<span class="glyphicon glyphicon-exclamation-sign"></span> ' + tips_content);
	}, function(){
		var target_class = '.' + $(this).children('input').attr('name') + '-helper';
		$(target_class).html('&nbsp;');
	});
	
	$('#cdbt_create_table_submit').on('click', function() {
		if ($('#cdbt_use_wp_prefix_for_newtable').is(':checked')) {
			$('#cdbt_create_table input[name="use_wp_prefix_for_newtable"]').val('true');
		} else {
			$('#cdbt_create_table input[name="use_wp_prefix_for_newtable"]').val('false');
		}
		$('div[data-toggle="buttons"] > label').each(function() {
			if ($(this).hasClass('active')) {
				$(this).children('input').prop('checked', 'checked');
			} else {
				$(this).children('input').removeAttr('checked');
			}
		});
		$('#cdbt_create_table').submit();
	});
	
	$('#cdbt_general_setting_save').on('click', function() {
		$('#cdbt_general_setting input[name="use_wp_prefix"]').val( $('#cdbt_use_wp_prefix').is(':checked') ? 'true' : 'false' );
		$('#cdbt_general_setting input[name="cleaning_options"]').val( $('#cdbt_cleaning_options').is(':checked') ? 'true' : 'false' );
		$('#cdbt_general_setting').submit();
	});
	
	$('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
		$('.tab-header').remove();
		if (e.target.text == 'create') {
			set_current_table_name();
		}
		// console.info(e.relatedTarget); // previous tab
	});
	
	$('#reflesh-table-list').on('click', function() {
		$('#cdbt_managed_tables input[name="handle"]').val('reflesh');
		$('#cdbt_managed_tables input[name="target_table"]').val('');
		$('#cdbt_managed_tables').submit();
	});
	
	$('.current-exists-tables button').on('click', function() {
		var parse_str = $(this).attr('id').split(':');
		if (parse_str[0] == $(this).attr('data-table')) {
			$('#cdbt_managed_tables input[name="handle"]').val(parse_str[1]);
			$('#cdbt_managed_tables input[name="target_table"]').val($(this).attr('data-table'));
			if (parse_str[1] == 'truncate-table') {
				var msg = '<?php _e('Will truncate and initialize data of "%s" table. After this handled cannot resume. Would you like?', PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-table'));
				show_modal('<?php _e('Please confirm', PLUGIN_SLUG); ?>', msg, '<?php _e('Yes, run', PLUGIN_SLUG); ?>');
				$('.modal.confirmation').modal('show');
			}
			if (parse_str[1] == 'drop-table') {
				var msg = '<?php _e('Will delete a "%s" table. After this handled cannot resume. Would you like?', PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-table'));
				show_modal('<?php _e('Please confirm', PLUGIN_SLUG); ?>', msg, '<?php _e('Yes, run', PLUGIN_SLUG); ?>');
				$('.modal.confirmation').modal('show');
			}
			if (parse_str[1] == 'choise-current-table') {
				selected_id = $(this).attr('id');
				$('.current-exists-tables button[id$="choise-current-table"]').each(function() {
					if ($(this).attr('id') == selected_id) {
						$(this).button('selected');
					} else {
						$(this).button('reset');
					}
				});
				$('#cdbt_managed_tables').submit();
			}
		} else {
			return false;
		}
	});
	
	$('.text-collapse').on('click', function(){
		var current_display_content = $(this).html();
		$(this).html($(this).attr('full-content')).attr('full-content', current_display_content);
	});
	
	$('.binary-file').on('click', function(){
		if ($(this).text().indexOf('image/') > 0) {
			var img = '<img src="<?php echo plugins_url(PLUGIN_SLUG) . '/lib/media.php'; ?>?id='+$(this).attr('data-id')+'&filename='+$(this).attr('data-origin-file')+'&token=<?php echo wp_create_nonce(PLUGIN_SLUG . '_media'); ?>" width="100%" class="img-thumbnail">';
			show_modal('<?php _e('Stored image', PLUGIN_SLUG); ?>', img, '');
			$('.modal.confirmation').modal('show');
		} else {
			show_modal('<?php _e('Stored binary file', PLUGIN_SLUG); ?>', decodeURI($(this).attr('data-origin-file')), '');
			$('.modal.confirmation').modal('show');
		}
	});
	
	// table creator
	$('#col_add_preset').on('click', function(){
		var new_row = $('li.preset').clone();
		var add_num = $('#sortable').children('li').length;
		new_row.removeClass('preset').addClass('addnew');
		new_row.children('label').each(function(){
			if ($(this).hasClass('handler')) {
				$(this).addClass('row-index-num').html('');
			} else {
				$(this).children().attr('name', $(this).children().attr('name')+add_num);
			}
			if ($(this).hasClass('add-row')) {
				$(this).removeClass('add-row').addClass('delete-row');
				$(this).html('<button type="button" name="col_delete_'+add_num+'" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-remove"></span></button>');
			}
		});
		$('li.preset').children('label').each(function(){
			$(this).children('input[type="checkbox"]').removeAttr('checked');
			$(this).children('input[type!="checkbox"]').val('');
			$(this).children('select').children().each(function(){
				$(this).removeAttr('selected');
			});
		});
		new_row.insertAfter('li.preset');
		renumber_row_index();
	});
	
	$('.tbl_cols select').on('change', function(){
		$(this).children(':selected').attr('selected', 'selected');
	});
	
	$(document).on('click', 'button[name^="col_delete_"]', function(){
		$(this).parent().parent('li.addnew').fadeOut('fast').remove();
		renumber_row_index();
	});
	
	function renumber_row_index(){
		var i = 0;
		$('#sortable').children('li').each(function(){
			if (!$(this).hasClass('preset')) {
				$(this).children('label.handler').html(i);
				i++;
			}
		});
	}
	
	function controll_col_attribute(index, mode) {
		var attr_obj = $('select[name="col_attribute_'+index+'"]');
		attr_obj.children('option').each(function(){
			if (mode == 'none') {
				$(this).attr('disabled', 'disabled');
			} else if (mode == 'numgrp' || mode == 'bingrp') {
				if ($(this).hasClass(mode)) {
					$(this).removeAttr('disabled');
				} else {
					$(this).attr('disabled', 'disabled');
				}
			} else {
				$(this).removeAttr('disabled');
			}
		});
	}
	
	$(document).on('change', 'select[name^="col_type_"]', function(){
		var parse_str = $(this).attr('name').split('_');
		var target_obj = $('input[name="col_length_'+parse_str[2]+'"]');
		var type_format = $(this).children(':selected').text();
		var type_val = $(this).children(':selected').val();
		if (type_val == '') {
			target_obj.val('').attr('placeholder', '-').attr('disabled', 'disabled');
		} else {
			target_obj.removeAttr('disabled');
			if (type_val == 'Array') {
				if (type_format == 'enum' || type_format == 'set') {
					target_obj.val('').attr('placeholder', '<?php _e('Candidate value 1, Candidate value 2, ...', PLUGIN_SLUG); ?>');
					controll_col_attribute(parse_str[2], 'none');
				} else if (type_format == 'decimal') {
					target_obj.val('10,0').attr('placeholder', '<?php _e('Integer of length, Integer of decimals', PLUGIN_SLUG); ?>');
					controll_col_attribute(parse_str[2], 'numgrp');
				} else {
					target_obj.val('').attr('placeholder', '<?php _e('Integer of length, Integer of decimals', PLUGIN_SLUG); ?>');
					controll_col_attribute(parse_str[2], 'numgrp');
				}
			} else if (type_val == 'int') {
				target_obj.val('').attr('placeholder', '<?php _e('Integer of length', PLUGIN_SLUG); ?>');
				if (type_format == 'varchar' || type_format == 'char') {
					controll_col_attribute(parse_str[2], 'bingrp');
				} else {
					controll_col_attribute(parse_str[2], 'numgrp');
				}
			} else {
				target_obj.val(type_val).attr('placeholder', '<?php _e('Integer of length', PLUGIN_SLUG); ?>');
				controll_col_attribute(parse_str[2], 'numgrp');
			}
		}
	});
	
	$('#set-sql').on('click', function(){
		renumber_row_index();
		var sql = "CREATE TABLE " + $('.simulate_table_name').text() + " (\n";
		var cols = new Array();
		var keys = new Array();
		$('#sortable').children('li.addnew').each(function(){
			var elms = new Array();
			elms['index'] = $(this).children('.handler').text();
			var obj = $(this).children().children();
			obj.map(function(){
				if ($(this).attr('name').indexOf('col_name_') == 0) 
					elms['col_name'] = $(this).val();
				if ($(this).attr('name').indexOf('col_type_') == 0) {
					elms['type'] = $(this).children(':selected').text();
					elms['type_val'] = $(this).children(':selected').val();
				}
				if ($(this).attr('name').indexOf('col_length_') == 0) 
					elms['type_length'] = parseInt($(this).val(), 10) || '';
				if ($(this).attr('name').indexOf('col_notnull_') == 0) 
					elms['not_null'] = $(this).prop('checked') ? 'NOT NULL' : '';
				if ($(this).attr('name').indexOf('col_default_') == 0) 
					elms['default_val'] = $(this).val();
				if ($(this).attr('name').indexOf('col_attribute_') == 0) 
					elms['attribute'] = $(this).children(':selected').val();
				if ($(this).attr('name').indexOf('col_autoinc_') == 0) 
					elms['auto_inc'] = $(this).prop('checked') ? 'AUTOINCREMENT' : '';
				if ($(this).attr('name').indexOf('col_key_') == 0) 
					elms['key'] = $(this).children(':selected').val();
				if ($(this).attr('name').indexOf('col_extra_') == 0) 
					elms['extra'] = $(this).val();
				if ($(this).attr('name').indexOf('col_comment_') == 0) 
					elms['comment'] = $(this).val();
			});
			if (elms['col_name']) {
				var typeformat = elms['type'];
				if (elms['type_val'] != '') {
					if (elms['type_val'] == 'int') {
						typeformat += '('+elms['type_length']+')';
					} else if (elms['type_val'] == 'Array') {
						typeformat += '('+elms['default_val']+')';
						elms['default_val'] = '';
					} else {
						if (elms['type_length'] != '') {
							typeformat += '('+elms['type_length']+')';
						} else {
							typeformat += '('+elms['type_val']+')';
						}
					}
				}
				col_sql = '`'+elms['col_name']+'` '+typeformat;
				if (elms['attribute'] != '') 
					col_sql += ' '+elms['attribute'].toUpperCase();
				if (elms['not_null'] != '') 
					col_sql += ' '+elms['not_null'].toUpperCase();
				if (elms['default_val'] != '') 
					col_sql += " DEFAULT '"+elms['default_val']+"'";
				if (elms['auto_inc'] != '') 
					col_sql += ' '+elms['auto_inc'].toUpperCase();
				if (elms['key'] != '') {
					if (elms['key'] == 'index') {
						keys.push(' '+elms['key'].toUpperCase()+' index_'+(keys.length+1)+' ('+elms['col_name']+')');
					} else if (elms['key'] == 'unique') {
						keys.push(' '+elms['key'].toUpperCase()+' unique_index_'+(keys.length+1)+' ('+elms['col_name']+')');
					} else if (elms['key'] == 'fulltext') {
						keys.push(' '+elms['key'].toUpperCase()+' fulltext_index_'+(keys.length+1)+' ('+elms['col_name']+')');
					} else if (elms['key'] == 'foreign key') {
						keys.push(' '+elms['key'].toUpperCase()+' ('+elms['col_name']+') REFERENCES [table(column_name)] ON DELETE CASCADE');
					}
				}
				if (elms['extra'] != '') 
					col_sql += ' '+elms['extra'].toUpperCase();
				if (elms['comment'] != '') 
					col_sql += " COMMENT '"+elms['comment']+"'";
				cols.push(col_sql);
			}
		});
		sql += cols.join(", \n") + ", \n";
		sql += keys.join(", \n") + " )\n";
		$('#cdbt_create_table_sql').val(sql);
	});
	
	$('.mysql-table-creator').on('hidden.bs.modal', function(e){
		$('#sql-statements-mode').addClass('active');
		$('#sql-table-creator').removeClass('active');
	});
	
	
});