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
		$('.controller-form').submit();
	});
	
	$('.download-binary').on('click', function(){
		var btn = $(this);
		btn.addClass('btn-primary').button('loading');
		$.post().always(function(){
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
			show_modal('<?php _e('Please comfirm', PLUGIN_SLUG); ?>', $(this).html(), '');
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
				$('.controller-form').submit();
			});
		} else {
			modal_obj.find('.run-process').parent('button').hide();
		}
	}
	
	function set_current_table_name() {
		$('code').each(function(){
			if ($(this).hasClass('simulate_table_name')) {
				var table_name = $('#cdbt_table_name').val();
				if ($('#cdbt_use_wp_prefix_for_newtable').is(':checked')) 
					table_name = $('#cdbt_use_wp_prefix_for_newtable').attr('data-prefix') + table_name;
				$(this).text(table_name);
			}
		});
	}
	
	$('#cdbt_table_name').on('focus blur', set_current_table_name);
	$('#cdbt_use_wp_prefix_for_newtable').on('click blur', set_current_table_name);
	
	$('#cdbt_create_table_submit').on('click', function() {
		if ($('#cdbt_use_wp_prefix_for_newtable').is(':checked')) {
			$('#cdbt_create_table input[name="use_wp_prefix_for_newtable"]').val('true');
		} else {
			$('#cdbt_create_table input[name="use_wp_prefix_for_newtable"]').val('false');
		}
		$('#cdbt_create_table').submit();
	});
	
	$('#cdbt_general_setting_save').on('click', function() {
		if ($('#cdbt_use_wp_prefix').is(':checked')) {
			$('#cdbt_general_setting input[name="use_wp_prefix"]').val('true');
		} else {
			$('#cdbt_general_setting input[name="use_wp_prefix"]').val('false');
		}
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
			if (parse_str[1] == 'choise-current-table') {
				selected_id = $(this).attr('id');
				$('.current-exists-tables button[id$="choise-current-table"]').each(function() {
					if ($(this).attr('id') == selected_id) {
						$(this).button('selected');
					} else {
						$(this).button('reset');
					}
				});
			}
			$('#cdbt_managed_tables').submit();
		} else {
			return false;
		}
	});
	
	$('#choise-current-table1').on('click', function() {
		alert('!');
		$('.current-exists-tables input[name="choise-current-table"]').each(function() {
			if (!$(this).is(':checked')) 
				console.info($(this).parents('label').attr('class'));
		});
	});
	
	
});