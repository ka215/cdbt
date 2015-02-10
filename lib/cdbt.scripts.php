<?php
function cdbt_create_javascript() {
	$media_nonce = wp_create_nonce(CDBT_PLUGIN_SLUG . '_media');
	$ajax_nonce = wp_create_nonce(CDBT_PLUGIN_SLUG . '_ajax');
	$action_nonce = wp_create_nonce("cdbt_ajax_core");
	
	if (is_admin()) {
		// header("Content-type: application/x-javascript");
?>
<script>
jQuery(document).ready(function($){
	
	$.QueryString = (function(a) {
		if (a == '') return {};
		var b = {};
		for (var i=0; i<a.length; ++i) {
			var p = a[i].split('=');
			if (p.length != 2) continue;
			b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, ' '));
		}
		return b;
	})(window.location.search.substr(1).split('&'))
	
	$('.alert').on('closed.bs.alert', function(e) {
		if ($.QueryString['mode'] == 'list' || $.QueryString['mode'] == 'edit') {
			location.href = $('a.active').attr('href');
			//location.reload();
		} else {
			if ($.QueryString['mode'] == 'input') {
				if ($.QueryString['action'] == 'update') {
					var cookie_data = getCookie('cdbt_update_referrer').split(',');
					var data = cookie_data.filter(function(x, i, self) { return self.indexOf(x) === i; });
					removeCookie('cdbt_update_referrer');
					location.href = data.pop();
				} else {
					location.href = $('a.active').attr('href');
					//location.reload();
				}
			}
		}
	});
	
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
				show_modal('<?php _e('Deleting confirmation', CDBT_PLUGIN_SLUG); ?>', '<?php _e('ID: %s of data will be deleted. Would you like?', CDBT_PLUGIN_SLUG); ?>'.replace('%s', target_ids.join(',')), '<?php _e('Delete', CDBT_PLUGIN_SLUG); ?>');
			} else {
				show_modal('<?php _e('Alert', CDBT_PLUGIN_SLUG); ?>', '<?php _e('Checked items is none!', CDBT_PLUGIN_SLUG); ?>', '');
			}
		}
	});
	
	$('#search_items').on('click', function(){
		if ($('.controller-form input[name="search_key"]').val() != '') {
			$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
			$('.controller-form input[name="action"]').val($(this).attr('data-action'));
			$('.controller-form').submit();
		} else {
			$('.controller-form input[name="page_num"]').val('');
			$('.controller-form input[name="sort_by"]').val('');
			$('.controller-form input[name="sort_order"]').val('DESC');
			show_modal('<?php _e('Alert', CDBT_PLUGIN_SLUG); ?>', '<?php _e('Search keyword is none!', CDBT_PLUGIN_SLUG); ?>', '');
			$('.modal.confirmation').modal('show');
		}
	});
	
	$('input[name="search_key"]').keypress(function(e) {
		if (e.which == 13) {
			e.preventDefault();
			var mode = $('#search_items') ? $('#search_items').attr('data-mode') : 'list';
			var action = $('#search_items') ? $('#search_items').attr('data-action') : 'search';
			$('.controller-form input[name="mode"]').val(mode);
			$('.controller-form input[name="action"]').val(action);
			$('.controller-form').submit();
		}
	});
	
	if ($('.search-hits').is('*')) {
		$.when($('.search-hits')).then(function() {
			var base_pos = $('.controller-form input[name="search_key"]').offset();
			var absolute_pos = $('.navbar-form.navbar-right').parent().offset();
			$(this).css({ left: (base_pos.left - absolute_pos.left - $(this).width() - 8) + 'px' });
			$('.change-page input[name="search_key"]').val($('.controller-form input[name="search_key"]').val());
			$('.change-page input[name="action"]').val('search');
		});
	}
	
	$('.sort-switch').on('click', function(e) {
		e.preventDefault();
		$('.controller-form input[name="sort_by"]').val($(this).attr('data-sort-column'));
		$('.controller-form input[name="sort_order"]').val($(this).attr('data-toggle-order'));
		if ($('.controller-form input[name="search_key"]').val() != '') {
			$('.controller-form input[name="action"]').val('search');
		}
		$('.controller-form').submit();
	});
	
	if ($('.sort-switch').is('*')) {
		$.when($('.sort-switch')).then(function() {
			var sort_by = $('.controller-form input[name="sort_by"]').val();
			var sort_order = $('.controller-form input[name="sort_order"]').val();
			if (sort_by != '') {
				$('#index-'+sort_by).css({ color: '#5bc0de' });
				$('.change-page input[name="sort_by"]').val(sort_by);
				$('.change-page input[name="sort_order"]').val(sort_order);
			}
		});
	}
	
	$('.edit-row').on('click', function(){
		setCookie('cdbt_update_referrer', location.href, 1);
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
		var post_data = {
			action: 'cdbt_ajax_core', 
			mode: $(this).attr('data-action'), 
			id: $(this).attr('data-id'), 
			token: '<?php echo $ajax_nonce; ?>', 
			'_ajax_nonce': '<?php echo $action_nonce; ?>' 
		}
		$.ajax({
			type: 'POST', 
			url: "<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>", 
			data: post_data
		}).done(function(res){
			show_modal('<?php _e('Download binary files', CDBT_PLUGIN_SLUG); ?>', res, '');
			$('.modal.confirmation').modal('show');
		}).always(function(){
			btn.removeClass('btn-primary').button('reset');
		});
	});
	
	$('.delete-row').on('click', function(){
		$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
		$('.controller-form input[name="action"]').val($(this).attr('data-action'));
		$('.controller-form input[name="ID"]').val($(this).attr('data-id'));
		show_modal('<?php _e('Deleting confirmation', CDBT_PLUGIN_SLUG); ?>', '<?php _e('ID: %s of data will be deleted. Would you like?', CDBT_PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-id')), '<?php _e('Delete', CDBT_PLUGIN_SLUG); ?>');
	});
	
	$('#entry-submit').on('click', function(){
		var btn = $(this);
		btn.button('<?php _e('loading', CDBT_PLUGIN_SLUG); ?>');
		$(this).parents('form').children('input[name="action"]').val($(this).attr('data-action'));
		$(this).parents('form').submit();
	});
	
	$('footer').children('div').each(function(){
		if ($(this).hasClass('modal-kicker')) {
			if ($(this).hasClass('show-run')) {
				var run_process = ($(this).attr('data-run-label') == '') ? '<?php _e('Yes, run', CDBT_PLUGIN_SLUG); ?>' : $(this).attr('data-run-label');
			} else {
				var run_process = '';
			}
			show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', $(this).html(), run_process, $(this).attr('data-hidden-callback'));
			$('.modal.confirmation').modal('show');
			$(this).remove();
		}
	});
	
	function show_modal(title, body, run_process, hidden_callback) {
		var modal_obj = $('.modal.confirmation .modal-content');
		modal_obj.find('.modal-title').text(title);
		body = body.replace(/{#(.*?)#}/gi, '<$1>');
		modal_obj.children('.modal-body').html(body);
		if (run_process != '') {
			modal_obj.find('.run-process').text(run_process).show();
			modal_obj.find('.run-process').click(function(e){
				e.preventDefault();
				if ($('#cdbt_managed_tables input[name="handle"]').val() == 'data-import') {
					// console.info('import proc.');
				} else 
				if ($('#cdbt_managed_tables input[name="handle"]').val() == 'data-export') {
					$('#cdbt_managed_tables').children('input[name="section"]').val('run');
					var url = '<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>?action=cdbt_media&tablename='+$('#cdbt_managed_tables input[name="target_table"]').val()+'&token=<?php echo wp_create_nonce(CDBT_PLUGIN_SLUG . '_csv_export'); ?>';
					$('.modal.confirmation').modal('hide');
					location.href = url;
				} else {
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
				}
			});
			modal_obj.find('.cancel-close').click(function(e){
				//e.preventDefault();
				var query_string = window.location.search.substr(1).split('&') || '';
				var queries = [];
				query_string.forEach( function(elm){
					var parse_elm = elm.split('=');
					queries[parse_elm[0]] = parse_elm[1];
				});
				if (typeof queries['mode'] != 'undefined' && queries['mode'] == 'admin') {
					var hash_string = window.location.hash.substr(1) || $('.tab-pane.active').attr('id');
					if (hash_string == 'cdbt-create') {
						$('input[name="is_incorporate_table"]').val('false');
						$('#cdbt_incorporate_table').html('<option value="" option-index="true"><?php _e('Incorporate Already Exists Table', CDBT_PLUGIN_SLUG); ?></option>');
					} else {
						var url = location.href + '#' + hash_string;
						location.assign(url);
					}
				}
			});
		} else {
			modal_obj.find('.run-process').parent('button').hide();
		}
		if (hidden_callback != '') {
			modal_obj.parent().parent('.modal').attr('data-callback', hidden_callback);
		}
	}
	
	$('.modal.confirmation').on('hidden.bs.modal', function(e){
		if (typeof e.target.dataset.callback != 'undefined') {
			eval(e.target.dataset.callback+'.call()');
		}
	});
	
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
				var naked_table_name = $('#cdbt_naked_table_name').val();
				if ($('#cdbt_use_wp_prefix_for_newtable').is(':checked')) {
					table_name = $('#cdbt_use_wp_prefix_for_newtable').attr('data-prefix') + naked_table_name;
				} else {
					table_name = naked_table_name;
				}
				$(this).text(table_name);
				if (e.type == 'blur') {
					var table_name = (naked_table_name == '') ? '{table_name}' : table_name;
					var reg = /CREATE\sTABLE\s(.*)?\s\(/gi;
					var get_sqlstr = $('#cdbt_create_table_sql').val();
					$('#cdbt_create_table_sql').val(get_sqlstr.replace(reg, 'CREATE TABLE ' + table_name + ' ('));
				}
			}
		});
	}
	
	$('#cdbt_naked_table_name').on('click focus blur', set_current_table_name);
	$('#cdbt_use_wp_prefix_for_newtable').on('click blur', set_current_table_name);
	
	// incorporate table
	$('#cdbt_incorporate_table').on('click', function(e) {
		//e.stopImmediatePropagation();
		if ($(this).attr('data-proc') != 'loaded') {
			$(this).children('option[option-index="true"]').text("<?php echo __('Now searching...', CDBT_PLUGIN_SLUG); ?>");
			var post_data = {
				action: 'cdbt_ajax_core', 
				mode: $(this).attr('data-action'), 
				token: '<?php echo $ajax_nonce; ?>', 
				'_ajax_nonce': '<?php echo $action_nonce; ?>' 
			}
			$.ajax({
				type: 'POST', 
				url: "<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>", 
				data: post_data
			}).done(function(res){
				$('#cdbt_incorporate_table').attr('data-proc', 'loaded').append(res).blur();
				var message = '';
				if ($('#cdbt_incorporate_table').children('option').length > 1) {
					message = "<?php _e('Select an incorporate table', CDBT_PLUGIN_SLUG); ?>";
				} else {
					message = "<?php _e('Can be incorporated table was none', CDBT_PLUGIN_SLUG); ?>";
				}
				$('#cdbt_incorporate_table').children('option[option-index="true"]').text(message);
				$('#cdbt_incorporate_table').focus();
			});
		}
	});
	
	$('#cdbt_incorporate_table').on('change', function() {
		if ($(this).attr('data-proc') == 'loaded') {
			var target_table = $(this).filter(':selected').context.value;
			if (target_table != '') {
				$('#cdbt_naked_table_name').val(target_table).attr('disabled', 'disabled');
				$('input[name="is_incorporate_table"]').val('true');
				$('#cdbt_use_wp_prefix_for_newtable').attr('disabled', 'disabled');
				$('#cdbt_table_comment').val('').attr('disabled', 'disabled');
				$('#cdbt_db_engine').attr('disabled', 'disabled');
				$('#sql-statements-mode').attr('disabled', 'disabled');
				$('#sql-table-creator').attr('disabled', 'disabled');
				$('#cdbt_create_table_sql').attr('disabled', 'disabled');
			} else {
				$('#cdbt_naked_table_name').val('').removeAttr('disabled');
				$('input[name="is_incorporate_table"]').val('false');
				$('#cdbt_use_wp_prefix_for_newtable').removeAttr('disabled');
				$('#cdbt_table_comment').val('').removeAttr('disabled');
				$('#cdbt_db_engine').removeAttr('disabled');
				$('#sql-statements-mode').removeAttr('disabled');
				$('#sql-table-creator').removeAttr('disabled');
				$('#cdbt_create_table_sql').val("CREATE TABLE {table_name} ( \n\n)").removeAttr('disabled');
			}
		}
	});
	
	function set_substance_sql() {
		//console.info($('input[name="substance_sql"]').val());
	}
	
	function set_alter_table_sql(e) {
		// From the presets other than
		if (e.target.id == 'cdbt_target_table_name') {
			ime_mode_inactive(e.type, $('#cdbt_target_table_name'));
		}
		var add_sql = ($(this).val() == '') ? '' : $(this).attr('data-sql-tmpl').replace(/{value}/gi, $(this).val())+",\n";
		var reg = /(RENAME\s|COMMENT\s|ENGINE\s)(`|\'|=\s)(.*)?(`|\'|)(\s|\n|,)/gi;
		var get_sqlstr = $('#cdbt_alter_table_sql').val();
		var now_sql = get_sqlstr.toLowerCase();
		var chk_sql = add_sql.toLowerCase().replace(reg, '$1').trim();
		if (now_sql.indexOf(chk_sql) > -1) {
			if (chk_sql == 'rename') 
				reg = /RENAME\s`(.*)?`(\s|\n|,(\s|)(\n|))/gi;
			if (chk_sql == 'comment') 
				reg = /COMMENT\s\'(.*)?\'(\s|\n|,(\s|)(\n|))/gi;
			if (chk_sql == 'engine') 
				reg = /ENGINE(\s|)=(\s|)(.*)?(\s|\n|,(\s|)(\n|))/gi;
			$('#cdbt_alter_table_sql').val(get_sqlstr.replace(reg, ''));
		}
		if ($(this).next('input').val() != $(this).val()) {
			$(this).parent().next('div').show();
			if (get_sqlstr.toLowerCase().indexOf(add_sql.toLowerCase()) == -1) {
				$('#cdbt_alter_table_sql').val($('#cdbt_alter_table_sql').val()+add_sql);
			}
		} else {
			$(this).parent().next('div').hide();
		}
	}
	
	if ($('#cdbt_create_table input[name="handle"]').val() == 'alter-table') {
		$('#cdbt_target_table_name').on('click focus blur', set_alter_table_sql);
		$('#cdbt_table_comment').on('click focus blur', set_alter_table_sql);
		$('#cdbt_db_engine').on('click focus blur change', set_alter_table_sql);
	}
	
	$('.sql-editor a').on('click', function() {
		if ($(this).attr('id') == 'sql-statements-mode') {
			$('#sql-statements-mode').addClass('active');
			$('#sql-table-creator').removeClass('active');
		} else {
			$('#sql-statements-mode').removeClass('active');
			$('#sql-table-creator').addClass('active');
		}
	});
	
	function set_alter_table_presets_sql(preset_id) {
		$('.popover-content').html("<?php _e('Preset Loading Now...', CDBT_PLUGIN_SLUG); ?>");
		var post_data = {
			action: 'cdbt_ajax_core', 
			mode: 'load_preset', 
			'preset_id': preset_id.substr(-1), 
			'preset_template': $('#'+preset_id).attr('data-preset-template'), 
			token: '<?php echo $ajax_nonce; ?>', 
			'_ajax_nonce': '<?php echo $action_nonce; ?>' 
		}
		$.ajax({
			type: 'POST', 
			url: "<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>", 
			data: post_data
		}).done(function(res){
			$('.popover-content').html(res);
			$(document).on('click', 'button[id^="set_preset_sql_"]', function() {
				var preset_id = $(this).attr('id').substr(-1);
				var sql_template = $('#sql-presets-'+preset_id).attr('data-preset-template');
				var regex = {};
				$('#cdbt_modify_table_preset_'+preset_id).find('input, select').each(function() {
					if ($(this).context.tagName == 'INPUT') {
						var value = $(this).val();
						if ($(this).attr('name') == 'position') {
							if (value.trim().toLowerCase() == 'first') {
								value = 'FIRST';
							} else {
								value = value != '' ? 'AFTER ' + value : '';
							}
						} else if ($(this).attr('name') == 'reference_definition') {
							value = 'REFERENCES ' + value;
						} else if ($(this).attr('name') == 'default_definition') {
							value = value.trim() != '' ? "SET DEFAULT '" + value + "'" : 'DROP DEFAULT';
						}
						regex['{'+$(this).attr('name')+'}'] = value;
					} else {
						regex['{'+$(this).attr('name')+'}'] = $(this).children('option:selected').val();
					}
				});
				if (Object.keys(regex).length > 0) {
					Object.keys(regex).forEach( function(k,i) {
						sql_template = sql_template.replace(k, regex[k]);
					} );
					var now_sqlstr = $('#cdbt_alter_table_sql').val();
					$('#cdbt_alter_table_sql').val(now_sqlstr + sql_template + ",\n");
				}
				$('.sql-preset a').popover('hide');
			}).on('click', 'button[id^="cancel_preset_sql_"]', function() {
				if ($(this).attr('data-dismiss') == 'popover') {
					$('.sql-preset a').popover('hide');
				}
			}).on('focus', 'form[id^="cdbt_modify_table_preset_"] input', function(e) {
				$('#'+$(this).attr('id')).attr('data-title', $(this).attr('placeholder')).attr('data-placement', 'bottom').tooltip('show');
			});
		});
	}
	
	$('.sql-preset a').popover().on('click', function() {
		var active_id = $(this).attr('id');
		$('.sql-preset a').each(function() {
			if (active_id == $(this).attr('id')) {
				$(this).addClass('active');
				set_alter_table_presets_sql(active_id);
			} else {
				$(this).removeClass('active');
				$(this).popover('hide');
			}
		});
	}).on('shown.bs.popover', function(e) {
		var add_class = $(this).attr('id').replace($(this).attr('id').substr(-1), 'popover');
		$('.popover.right.in').addClass(add_class).children('.arrow').css({ top: '24px', left: '24px', marginTop: '-6px' });
	}).on('hidden.bs.popover', function(e) {
		$('.popover').each(function() {
			if (!$(this).hasClass('in')) {
				$(this).remove();
			}
		});
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
	
	$('#cdbt_alter_table_submit').on('click', function() {
		var get_sqlstr = $('#cdbt_alter_table_sql').val().trim();
		if (get_sqlstr.substr(-1,1) == ',') 
			get_sqlstr = get_sqlstr.slice(0,-1);
		$('#cdbt_alter_table_sql').val(get_sqlstr);
		$('#cdbt_create_table').submit();
	});
	
	$('#cdbt_alter_table_cancel').on('click', function() {
		$('#cdbt_create_table input[name="handle"]').val('create-table');
		$('#cdbt_create_table input[name="section"]').val('confirm');
		$('#cdbt_managed_tables input[name="handle"]').val('reflesh');
		$('#cdbt_managed_tables input[name="target_table"]').val('');
		$('#cdbt_managed_tables').submit();
	});
	
	$('#cdbt_general_setting_save').on('click', function() {
		$('#cdbt_general_setting input[name="use_wp_prefix"]').val( $('#cdbt_use_wp_prefix').is(':checked') ? 'true' : 'false' );
		$('#cdbt_general_setting input[name="cleaning_options"]').val( $('#cdbt_cleaning_options').is(':checked') ? 'true' : 'false' );
		$('#cdbt_general_setting input[name="uninstall_options"]').val( $('#cdbt_uninstall_options').is(':checked') ? 'true' : 'false' );
		$('#cdbt_general_setting input[name="resume_options"]').val( $('#cdbt_resume_options').is(':checked') ? 'true' : 'false' );
		$('#cdbt_general_setting').submit();
	});
	
	$('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
		$('.tab-header').remove();
		if (e.currentTarget.hash == '#cdbt-create') {
			set_current_table_name(e.type);
			if ($('#cdbt_create_table input[name="handle"]').val() == 'alter-table') {
				$('#cdbt_create_table').submit();
			}
		} else if (e.currentTarget.hash == '#cdbt-tables') {
			$('#cdbt_managed_tables input[name="handle"]').val('reflesh');
			$('#cdbt_managed_tables input[name="target_table"]').val('');
			$('#cdbt_managed_tables').submit();
		} else {
			//$('#cdbt_general_setting').submit();
		}
	});
	
	$(document).on('click', '#download_template_csv', function(){
		var url = '<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>?action=cdbt_media&tablename='+$('#data-import-form input[name="import-table"]').val()+'&token='+$(this).attr('data-nonce');
		location.href = url;
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
			if (parse_str[1] == 'data-import') {
				var html = '<?php
$translate_text = array(
	__('1. Download template csv file to import data.', CDBT_PLUGIN_SLUG), 
	__('It will be downloaded csv file is included only index row. You should be add in it the import data.', CDBT_PLUGIN_SLUG), 
	__('Download CSV', CDBT_PLUGIN_SLUG), 
	__('2. Upload csv file including import data.', CDBT_PLUGIN_SLUG), 
	__('If invalid file will be uploaded, it will not import data.', CDBT_PLUGIN_SLUG), 
);
$dl_nonce = wp_create_nonce(CDBT_PLUGIN_SLUG . '_csv_tmpl_download');
$html =<<<EOH
<form method="post" id="data-import-form" class="import-form" enctype="multipart/form-data" role="form">
	<input type="hidden" name="import-table" value="%s">
	<div class="form-group">
		<label for="download_template_csv">{$translate_text[0]}</label>
		<p class="help-block">{$translate_text[1]}</p>
		<button type="button" id="download_template_csv" class="btn btn-default btn-xs" data-nonce="$dl_nonce">{$translate_text[2]}</button>
	</div>
	<div class="form-group">
		<label for="upload_import_csv">{$translate_text[3]}</label>
		<input type="file" id="upload_import_csv" name="csv_file">
		<p class="help-block upload_note">{$translate_text[4]}</p>
	</div>
</form>
EOH;
echo preg_replace('/\n|\r|\t/', '', $html);
				?>'.replace('%s', $(this).attr('data-table'));
				show_modal('<?php _e('Import procedures', CDBT_PLUGIN_SLUG); ?>', html, '<?php _e('Import now!', CDBT_PLUGIN_SLUG); ?>');
				$('.modal.confirmation').modal('show');
				$(document).on('click', '.confirmation .modal-footer .btn-primary', function(e) {
					e.preventDefault();
					if ($('#upload_import_csv').val().toLowerCase().indexOf('.csv') > 0) {
						$('#cdbt_managed_tables input[type="hidden"]').each(function(){
							if ($(this).attr('name') == 'section') 
								$(this).val('run');
							$(this).clone().appendTo('#data-import-form');
						});
						$('#data-import-form').submit();
					} else {
						$('.upload_note .alert-text').remove();
						$('.upload_note').append('<div class="alert-text"><?php _e('You are trying to upload a not CSV file.', CDBT_PLUGIN_SLUG); ?></div>');
					}
				});
			}
			if (parse_str[1] == 'data-export') {
				var msg = '<?php _e('Will export data of "%s" table. Would you like?', CDBT_PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-table'));
				show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', msg, '<?php _e('Export now!', CDBT_PLUGIN_SLUG); ?>');
				$('.modal.confirmation').modal('show');
			}
			if (parse_str[1] == 'alter-table') {
				var msg = '<?php _e('Will modify schema of "%s" table. This handle is same that recreating the table. Would you like?', CDBT_PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-table'));
				show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', msg, '<?php _e('Start modify', CDBT_PLUGIN_SLUG); ?>');
				$('.modal.confirmation').modal('show');
				$(document).on('click', '.confirmation .modal-footer .btn-primary', function(e) {
					e.preventDefault();
					$('#cdbt_create_table input[name="handle"]').val('alter-table');
					$('#cdbt_create_table input[name="target_table"]').val($('#cdbt_managed_tables input[name="target_table"]').val());
					$('#cdbt_create_table input[name="init_set"]').val('true');
					$('#cdbt_create_table').submit();
				});
			}
			if (parse_str[1] == 'truncate-table') {
				var msg = '<?php _e('Will truncate and initialize data of "%s" table. After this handled cannot resume. Would you like?', CDBT_PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-table'));
				show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', msg, '<?php _e('Yes, run', CDBT_PLUGIN_SLUG); ?>');
				$('.modal.confirmation').modal('show');
			}
			if (parse_str[1] == 'drop-table') {
				var msg = '<?php _e('Will delete a "%s" table. After this handled cannot resume. Would you like?', CDBT_PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-table'));
				show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', msg, '<?php _e('Yes, run', CDBT_PLUGIN_SLUG); ?>');
				$('.modal.confirmation').modal('show');
			}
			if (parse_str[1] == 'choose-current-table') {
				selected_id = $(this).attr('id');
				$('.current-exists-tables button[id$="choose-current-table"]').each(function() {
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
			var src = '<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>?action=cdbt_media&id='+$(this).attr('data-id')+'&filename='+$(this).attr('data-origin-file')+'&token=<?php echo $media_nonce; ?>';
			var img = '<img src="'+src+'" width="100%" class="img-thumbnail">';
			show_modal('<?php _e('Stored image', CDBT_PLUGIN_SLUG); ?>', img, '');
			$('.modal.confirmation').modal('show');
		} else {
			show_modal('<?php _e('Stored binary file', CDBT_PLUGIN_SLUG); ?>', decodeURI($(this).attr('data-origin-file')), '');
			$('.modal.confirmation').modal('show');
		}
	});
	
	$('#cdbt_generate_api_key').on('click', function(){
		if ($('#cdbt_api_key').val() != '') {
			
			var post_data = {
				action: 'cdbt_ajax_core', 
				mode: 'add_api_key', 
				'host_addr': $('#cdbt_api_key').val(), 
				token: '<?php echo $ajax_nonce; ?>', 
				'_ajax_nonce': '<?php echo $action_nonce; ?>' 
			}
			$.ajax({
				type: 'POST', 
				url: "<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>", 
				data: post_data
			}).done(function(res){
				var res = res.split(',');
				if (res[0] == 'error') {
					show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', res[1], '');
					$('.modal.confirmation').modal('show');
				} else {
					location.reload();
					//$('#api_key_list_tbody').append('<tr><td>' + res[0] + '</td><td>' + res[1] + '</td><td><button></button></td></tr>');
				}
			});
			
		} else {
			show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', '<?php _e('Request host address is none.', CDBT_PLUGIN_SLUG); ?>', '');
			$('.modal.confirmation').modal('show');
		}
	});
	
	$('#api_key_list_tbody button[id^="delete_api_key_"]').on('click', function(){
		$(this).attr('data-api-key')
		var post_data = {
			action: 'cdbt_ajax_core', 
			mode: 'delete_api_key', 
			'host_addr': $(this).attr('data-api-key'), 
			token: '<?php echo $ajax_nonce; ?>', 
			'_ajax_nonce': '<?php echo $action_nonce; ?>' 
		}
		$.ajax({
			type: 'POST', 
			url: "<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>", 
			data: post_data
		}).done(function(res){
			var res = res.split(',');
			if (res[0] == 'error') {
				show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', res[1], '');
				$('.modal.confirmation').modal('show');
			} else {
				location.reload();
			}
		});
	});
	
/*
 * for Table Creator
 */
	$('li.preset').delegate('input,textarea,select', 'click', function(e){
		e.target.focus();
	});
	$('#col_add_preset').on('click', function(){
		var new_row = $('li.preset').clone().delegate('input,textarea,select', 'click', function(e){
			e.target.focus();
		});
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
		new_row.insertBefore('li.preset');
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
					target_obj.val('').attr('placeholder', '<?php _e('Candidate value 1, Candidate value 2, ...', CDBT_PLUGIN_SLUG); ?>');
					controll_col_attribute(parse_str[2], 'none');
				} else if (type_format == 'decimal') {
					target_obj.val('10,0').attr('placeholder', '<?php _e('Integer of length, Integer of decimals', CDBT_PLUGIN_SLUG); ?>');
					controll_col_attribute(parse_str[2], 'numgrp');
				} else {
					target_obj.val('').attr('placeholder', '<?php _e('Integer of length, Integer of decimals', CDBT_PLUGIN_SLUG); ?>');
					controll_col_attribute(parse_str[2], 'numgrp');
				}
			} else if (type_val == 'int') {
				target_obj.val('').attr('placeholder', '<?php _e('Integer of length', CDBT_PLUGIN_SLUG); ?>');
				if (type_format == 'varchar' || type_format == 'char') {
					controll_col_attribute(parse_str[2], 'bingrp');
				} else {
					controll_col_attribute(parse_str[2], 'numgrp');
				}
			} else {
				target_obj.val(type_val).attr('placeholder', '<?php _e('Integer of length', CDBT_PLUGIN_SLUG); ?>');
				controll_col_attribute(parse_str[2], 'numgrp');
			}
		}
	});
	
	$('#set-sql').on('click', function(){
		renumber_row_index();
		var table_name = ($('#cdbt_naked_table_name').val() != '') ? $('.simulate_table_name').text() : '{table_name}';
		var sql = "CREATE TABLE " + table_name + " (\n";
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
					} else if (elms['type'] == 'bool') {
						// add nothing
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
				if (elms['default_val'] != '') {
					var delimit = (elms['type'] == 'int' || elms['type'] == 'bool') ? '' : "'";
					if (elms['type'] == 'bool') {
						var default_value = (Number(elms['default_val']) === 'NaN' || Number(elms['default_val']) != 1) ? 0 : 1;
					} else {
						var default_value = delimit+elms['default_val']+delimit;
					}
					col_sql += " DEFAULT " + default_value;
				}
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
		sql += cols.join(", \n") + ',';
		if (keys.length > 0) 
			sql += "\n" + keys.join(", \n");
		if (sql.substr(-1,1) == ',') 
			sql = sql.slice(0,-1);
		sql += "\n)\n";
		$('#cdbt_create_table_sql').val(sql);
	});
	
	$('.mysql-table-creator').on('hidden.bs.modal', function(e){
		$('#sql-statements-mode').addClass('active');
		$('#sql-table-creator').removeClass('active');
	});
	
});
<?php
	} else {
		global $cdbt;
		//header("Content-type: application/x-javascript");
?>
<script>
jQuery(document).ready(function($){
	
	$.QueryString = (function(a) {
		if (a == '') return {};
		var b = {};
		for (var i=0; i<a.length; ++i) {
			var p = a[i].split('=');
			if (p.length != 2) continue;
			b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, ' '));
		}
		return b;
	})(window.location.search.substr(1).split('&'))
	
	$('.alert').on('closed.bs.alert', function(e) {
		var current_mode = $('.controller-form input[name="mode"]').val();
		if (current_mode == 'list' || current_mode == 'edit') {
			//location.reload();
		} else {
			if (current_mode == 'input') {
				if ($('.controller-form input[name="action"]').val() == 'update') {
					var cookie_data = getCookie('cdbt_update_referrer').split(',');
					var data = cookie_data.filter(function(x, i, self) { return self.indexOf(x) === i; });
					removeCookie('cdbt_update_referrer');
					location.href = data.pop();
				} else {
					location.reload();
				}
			}
		}
	});
	
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
				show_modal('<?php _e('Deleting confirmation', CDBT_PLUGIN_SLUG); ?>', '<?php _e('ID: %s of data will be deleted. Would you like?', CDBT_PLUGIN_SLUG); ?>'.replace('%s', target_ids.join(',')), '<?php _e('Delete', CDBT_PLUGIN_SLUG); ?>');
			} else {
				show_modal('<?php _e('Alert', CDBT_PLUGIN_SLUG); ?>', '<?php _e('Checked items is none!', CDBT_PLUGIN_SLUG); ?>', '');
			}
		}
	});
	
	$('#search_items').on('click', function(){
		if ($('.controller-form input[name="search_key"]').val() != '') {
			$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
			$('.controller-form input[name="action"]').val($(this).attr('data-action'));
			$('.controller-form').submit();
		} else {
			$('.controller-form input[name="page_num"]').val('');
			$('.controller-form input[name="sort_by"]').val('');
			$('.controller-form input[name="sort_order"]').val('DESC');
			show_modal('<?php _e('Alert', CDBT_PLUGIN_SLUG); ?>', '<?php _e('Search keyword is none!', CDBT_PLUGIN_SLUG); ?>', '');
			$('.modal.confirmation').modal('show');
		}
	});
	
	$('input[name="search_key"]').keypress(function(e) {
		if (e.which == 13) {
			e.preventDefault();
			var mode = $('#search_items') ? $('#search_items').attr('data-mode') : 'list';
			var action = $('#search_items') ? $('#search_items').attr('data-action') : 'search';
			$('.controller-form input[name="mode"]').val(mode);
			$('.controller-form input[name="action"]').val(action);
			$('.controller-form').submit();
		}
	});
	
	$.when($('.search-hits')).then(function() {
		var base_width = $('.navbar-form.navbar-right').width();
		var parent_width = $('.navbar-form.navbar-right').parent().width();
		$(this).css({ left: parent_width - base_width - $(this).width() + 7 + 'px' });
		$('.change-page input[name="search_key"]').val($('.controller-form input[name="search_key"]').val());
		$('.change-page input[name="action"]').val('search');
	});
	
	$('.sort-switch').on('click', function(e) {
		e.preventDefault();
		$('.controller-form input[name="sort_by"]').val($(this).attr('data-sort-column'));
		$('.controller-form input[name="sort_order"]').val($(this).attr('data-toggle-order'));
		$('.change-page input[name="sort_by"]').val($(this).attr('data-sort-column'));
		$('.change-page input[name="sort_order"]').val($(this).attr('data-toggle-order'));
		if ($('.controller-form input[name="search_key"]').val() != '') {
			$('.controller-form input[name="action"]').val('search');
		}
		$('.controller-form').submit();
	});
	
	$.when($('.sort-switch')).then(function() {
		var sort_by = $('.controller-form input[name="sort_by"]').val();
		var sort_order = $('.controller-form input[name="sort_order"]').val();
		if (sort_by != '') {
			$('#index-'+sort_by).css({ color: '#5bc0de' });
			$('.change-page input[name="sort_by"]').val(sort_by);
			$('.change-page input[name="sort_order"]').val(sort_order);
		}
	});
	
	$('.text-collapse').on('click', function(){
		var current_display_content = $(this).html();
		$(this).html($(this).attr('full-content')).attr('full-content', current_display_content);
	});
	
	$('.binary-file').on('click', function(){
		if ($(this).text().indexOf('image/') > 0) {
			var tbl_name = $('.navbar .container-fluid input[name="table"]').val();
			var src = '<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>?action=cdbt_media&id='+$(this).attr('data-id')+'&filename='+$(this).attr('data-origin-file')+'&table='+tbl_name+'&token=<?php echo $media_nonce; ?>';
			var img = '<img src="'+src+'" width="100%" class="img-thumbnail">';
			show_modal('<?php _e('Stored image', CDBT_PLUGIN_SLUG); ?>', img, '');
			$('.modal.confirmation').modal('show');
		} else {
			show_modal('<?php _e('Stored binary file', CDBT_PLUGIN_SLUG); ?>', decodeURI($(this).attr('data-origin-file')), '');
			$('.modal.confirmation').modal('show');
		}
	});
	
	$('#entry-submit').on('click', function(){
		var btn = $(this);
		btn.button('<?php _e('loading', CDBT_PLUGIN_SLUG); ?>');
		$(this).parents('form').children('input[name="action"]').val($(this).attr('data-action'));
		$(this).parents('form').submit();
	});
	
	$('.edit-row').on('click', function(){
		setCookie('cdbt_update_referrer', location.href, 1);
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
		var post_data = {
			action: 'cdbt_ajax_core', 
			mode: $(this).attr('data-action'), 
				
			id: $(this).attr('data-id'), 
			table: tbl_name, 
			token: '<?php echo $ajax_nonce; ?>', 
			'_ajax_nonce': '<?php echo $action_nonce; ?>' 
		}
		$.ajax({
			type: 'POST', 
			url: "<?php echo esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))); ?>", 
			data: post_data
		}).done(function(res){
			show_modal('<?php _e('Download binary files', CDBT_PLUGIN_SLUG); ?>', res, '');
			$('.modal.confirmation').modal('show');
		}).always(function(){
			btn.removeClass('btn-primary').button('reset');
		});
	});
	
	$('.delete-row').on('click', function(){
		$('.controller-form input[name="mode"]').val($(this).attr('data-mode'));
		$('.controller-form input[name="action"]').val($(this).attr('data-action'));
		$('.controller-form input[name="ID"]').val($(this).attr('data-id'));
		show_modal('<?php _e('Deleting confirmation', CDBT_PLUGIN_SLUG); ?>', '<?php _e('ID: %s of data will be deleted. Would you like?', CDBT_PLUGIN_SLUG); ?>'.replace('%s', $(this).attr('data-id')), '<?php _e('Delete', CDBT_PLUGIN_SLUG); ?>');
	});
	
	var chk_modal_position = $('.modal.confirmation').parent();
	if (chk_modal_position.length > 0 && $('.modal.confirmation').parent()[0].tagName != 'BODY') {
		$('.modal.confirmation').appendTo('body');
	}
	
	if (typeof $('.modal-kicker').children() != 'undefined' && $('.modal-kicker').html() != '' && $('.modal-kicker').text() != '') {
		if ($('.modal-kicker').hasClass('show-run')) {
			var run_process = ($('.modal-kicker').attr('data-run-label') == '') ? '<?php _e('Yes, run', CDBT_PLUGIN_SLUG); ?>' : $('.modal-kicker').attr('data-run-label');
		} else {
			var run_process = '';
		}
		show_modal('<?php _e('Please confirm', CDBT_PLUGIN_SLUG); ?>', $('.modal-kicker').html(), run_process);
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
<?php
	}
	// Common
?>
function setCookie(ck_name, ck_value, expiredays) {
    // SetCookie
    var path = '/';
    var extime = new Date().getTime();
    var cltime = new Date(extime + (60*60*24*1000*expiredays));
    var exdate = cltime.toUTCString();
    var pre_data = getCookie(ck_name);
    var tmp_data = pre_data.split(',');
    tmp_data.push(ck_value);
    var fix_data = tmp_data.filter(function (x, i, self) { return self.indexOf(x) === i; });
    var s = '';
    s += ck_name + '=' + escape(fix_data.join(','));
    s += '; path=' + path;
    s += expiredays ? '; expires=' + exdate + '; ' : '; ';
    document.cookie = s;
}
function getCookie(ck_name) {
    // GetCookie
    var st = '', ed = '', res = '';
    if (document.cookie.length > 0) {
    st = document.cookie.indexOf(ck_name + '=');
    if (st != -1) {
        st = st + ck_name.length + 1;
        ed = document.cookie.indexOf(';', st);
        if (ed == -1) 
            ed = document.cookie.length;
            res = unescape(document.cookie.substring(st, ed));
        }
    }
    return res;
}
function removeCookie(ck_name) {
	// removeCookie
	var path = '/';
	if (!ck_name || document.cookie.indexOf(ck_name + '=') != -1) { return; }
	document.cookie = escape(ck_name) + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT' + (path ? '; path=' + path : '');
}
</script>
<?php
}