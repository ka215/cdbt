<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

global $cdbt;
$media_nonce = wp_create_nonce(PLUGIN_SLUG . '_media');
$ajax_nonce = wp_create_nonce(PLUGIN_SLUG . '_ajax');

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
	
	$('.text-collapse').on('click', function(){
		var current_display_content = $(this).html();
		$(this).html($(this).attr('full-content')).attr('full-content', current_display_content);
	});
	
	$('.binary-file').on('click', function(){
		if ($(this).text().indexOf('image/') > 0) {
			var tbl_name = $('.navbar .container-fluid input[name="table"]').val();
			var img = '<img src="<?php echo $cdbt->dir_url; ?>/lib/media.php?id='+$(this).attr('data-id')+'&filename='+$(this).attr('data-origin-file')+'&table='+tbl_name+'&token=<?php echo $media_nonce; ?>" width="100%" class="img-thumbnail">';
			show_modal('<?php _e('Stored image', PLUGIN_SLUG); ?>', img, '');
			$('.modal.confirmation').modal('show');
		} else {
			show_modal('<?php _e('Stored binary file', PLUGIN_SLUG); ?>', decodeURI($(this).attr('data-origin-file')), '');
			$('.modal.confirmation').modal('show');
		}
	});
	
	$('#entry-submit').on('click', function(){
		var btn = $(this);
		btn.button('<?php _e('loading', PLUGIN_SLUG); ?>');
		$(this).parents('form').children('input[name="action"]').val($(this).attr('data-action'));
		$(this).parents('form').submit();
	});
	
	$('.edit-row').on('click', function(){
		$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
		$('.controller-form input[name="action"]').val($(this).attr('data-action'));
		$('.controller-form input[name="ID"]').val($(this).attr('data-id'));
		$('.controller-form input[name="_cdbt_token"]').val($(this).attr('data-token'));
		$('.controller-form input[name="search_key"]').remove();
		$('.controller-form').attr('action', $(this).attr('action-url'));
		$('.controller-form').submit();
	});
	
	$('.download-binary').on('click', function(){
		var tbl_name = $('.navbar .container-fluid input[name="table"]').val();
		var btn = $(this);
		btn.addClass('btn-primary').button('loading');
		$.ajax({
			type: 'POST', 
			url: '<?php echo $cdbt->dir_url; ?>/lib/ajax.php', 
			data: { mode: $(this).attr('data-action'), id: $(this).attr('data-id'), table: tbl_name, token: '<?php echo $ajax_nonce; ?>' }
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
	
	if (typeof $('.modal-kicker').children() != 'undefined' && $('.modal-kicker').html() != '' && $('.modal-kicker').text() != '') {
		if ($('.modal-kicker').hasClass('show-run')) {
			var run_process = ($('.modal-kicker').attr('data-run-label') == '') ? '<?php _e('Yes, run', PLUGIN_SLUG); ?>' : $('.modal-kicker').attr('data-run-label');
		} else {
			var run_process = '';
		}
		show_modal('<?php _e('Please confirm', PLUGIN_SLUG); ?>', $('.modal-kicker').html(), run_process);
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