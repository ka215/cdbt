<?php
/**
 * Custom DataBase Tables APIs
 */

/**
 * Create pagination
 *
 * @param int $page_num
 * @param int $per_page
 * @param int $total_data
 * @param string $mode (optional) default 'list'
 * @param int $overflow (optional) default 10
 * @return string
 */
function cdbt_create_pagination($page_num, $per_page, $total_data, $mode='list', $overflow=10) {
	$max_pages = ceil($total_data / $per_page);
	$pagination_base = '<div class="text-center"><ul class="pagination pagination-sm">%s</ul>%s%s</div>';
	$active_class = ' class="active"';
	$pagination_left = '<li%s>%s<span class="glyphicon glyphicon-step-backward"></span>%s</li>'; // &laquo;
	$pagination_right = '<li%s>%s<span class="glyphicon glyphicon-step-forward"></span>%s</li>'; // &raquo;
	$pagination_prev = '<li%s>%s<span class="glyphicon glyphicon-arrow-left"></span>%s</li>';
	$pagination_next = '<li%s>%s<span class="glyphicon glyphicon-arrow-right"></span>%s</li>';
	$pagination_interval = '<li%s>%s...%s</li>'; // <span class="glyphicon glyphicon-resize-horizontal"></span>
	
	if ($max_pages > $overflow) {
		$pagination_html = sprintf($pagination_left, '', '<a href="#" data-page="1">', '</a>');
		$pagination_html .= $page_num == 1 ? sprintf($pagination_prev, disabled($page_num, 1, false), '<span>', '</span>') : sprintf($pagination_prev, '', '<a href="#" data-page="'. ($page_num - 1) .'">', '</a>');
		if ($page_num > $overflow) {
			if ($page_num < ($max_pages - $overflow)) {
				$start_num = $page_num - $overflow;
				$interval_position = 'both';
			} else {
				$start_num = $max_pages - $overflow;
				$interval_position = 'left';
			}
		} else {
			$start_num = 1;
			$interval_position = 'right';
		}
		if (preg_match('/^(left|both)$/i', $interval_position)) {
			$prev_start_num = ($start_num - $overflow) > 0 ? $start_num - $overflow : 1;
			$pagination_html .= sprintf($pagination_interval, '', '<a href="#" data-page="'. $prev_start_num .'">', '</a>');
		}
		for ($i = $start_num; $i <= $start_num + 10; $i++) {
			$pagination_inner = '<li%s>%s'. $i .'%s</li>';
			$pagination_html .= $page_num == $i ? sprintf($pagination_inner, $active_class, '<span>', ' <span class="sr-only">(current)</span></span>') : sprintf($pagination_inner, '', '<a href="#" data-page="'.$i.'">', '</a>');
		}
		if (preg_match('/^(both|right)$/i', $interval_position)) {
			$next_start_num = ($start_num + ($overflow * 2)) < $max_pages ? $start_num + ($overflow * 2) : $max_pages - $overflow;
			$pagination_html .= sprintf($pagination_interval, '', '<a href="#" data-page="'. $next_start_num .'">', '</a>');
		}
		$pagination_html .= $page_num == $max_pages ? sprintf($pagination_next, disabled($page_num, $max_pages, false), '<span>', '</span>') : sprintf($pagination_next, '', '<a href="#" data-page="'. ($page_num + 1) .'">', '</a>');
		$pagination_html .= sprintf($pagination_right, '', '<a href="#" data-page="'. $max_pages .'">', '</a>');
	} else {
		$pagination_html = $page_num == 1 ? sprintf($pagination_left, disabled($page_num, 1, false), '<span>', '</span>') : sprintf($pagination_left, '', '<a href="#" data-page="1">', '</a>');
		for ($i = 1; $i <= $max_pages; $i++) {
			$pagination_inner = '<li%s>%s'. $i .'%s</li>';
			$pagination_html .= $page_num == $i ? sprintf($pagination_inner, $active_class, '<span>', ' <span class="sr-only">(current)</span></span>') : sprintf($pagination_inner, '', '<a href="#" data-page="'.$i.'">', '</a>');
		}
		$pagination_html .= $page_num == $max_pages ? sprintf($pagination_right, disabled($page_num, $max_pages, false), '<span>', '</span>') : sprintf($pagination_right, '', '<a href="#" data-page="'. $max_pages .'">', '</a>');
	}
	
	$page_slug = CDBT_PLUGIN_SLUG;
	$nonce_field = wp_nonce_field($page_slug .'_'. $mode, '_cdbt_token', false, false);
	$pagination_form = <<<EOH
<form method="post" class="change-page" role="form">
	<input type="hidden" name="page" value="$page_slug">
	<input type="hidden" name="mode" value="$mode">
	<input type="hidden" name="page_num" value="$page_num">
	<input type="hidden" name="per_page" value="$per_page">
	<input type="hidden" name="action" value="" />
	<input type="hidden" name="search_key" value="" />
	<input type="hidden" name="sort_by" value="" />
	<input type="hidden" name="sort_order" value="" />
	$nonce_field
</form>
EOH;
	$pagination_script = <<<EOS
<script>
jQuery(document).ready(function(){
	jQuery('.pagination a').on('click', function(){
		jQuery('.change-page').children('input[name="page_num"]').val(jQuery(this).attr('data-page'));
		jQuery('.change-page').children('input[name="action"]').val(jQuery('.controller-form input[name="action"]').val());
		jQuery('.change-page').children('input[name="search_key"]').val(jQuery('.controller-form input[name="search_key"]').val());
		jQuery('.change-page').children('input[name="sort_by"]').val(jQuery('.controller-form input[name="sort_by"]').val());
		jQuery('.change-page').children('input[name="sort_order"]').val(jQuery('.controller-form input[name="sort_order"]').val());
		jQuery('.change-page').submit();
	});
});
</script>
EOS;
	return sprintf($pagination_base, $pagination_html, $pagination_form, $pagination_script);
}

/**
 * get level of current login user
 *
 * @return int
 */
function cdbt_current_user_level() {
	if (is_user_logged_in()) {
		$user_cap = wp_get_current_user()->caps;
		if (array_key_exists('subscriber', $user_cap) && $user_cap['subscriber']) 
			$level = 1;
		if (array_key_exists('contributor', $user_cap) && $user_cap['contributor']) 
			$level = 3;
		if (array_key_exists('author', $user_cap) && $user_cap['author']) 
			$level = 5;
		if (array_key_exists('editor', $user_cap) && $user_cap['editor']) 
			$level = 7;
		if (array_key_exists('administrator', $user_cap) && $user_cap['administrator']) 
			$level = 9;
	} else {
		$level = 1;
	}
	return $level;
}

/**
 * check role if current login user can use current table 
 *
 * @param string $mode
 * @param string $table (optional) default null
 * @return boolean
 */
function cdbt_check_current_table_role($mode, $table=null) {
	$cdbt_option = get_option(CDBT_PLUGIN_SLUG);
	if (empty($table)) {
		$current_table = get_option(CDBT_PLUGIN_SLUG . '_current_table');
	} else {
		$current_table = $table;
	}
	if (!$current_table || !$cdbt_option) 
		return false;
	$is_enable_mode = false;
	foreach ($cdbt_option['tables'] as $table) {
		if ($table['table_name'] == $current_table) {
			if (intval($table['roles'][$mode . '_role']) <= cdbt_current_user_level()) {
				$is_enable_mode = true;
				break;
			}
		}
	}
	return $is_enable_mode;
}

/**
 * check whether current table enable
 *
 * @param string $table_name (optional) default null
 * @return boolean
 */
function cdbt_check_current_table_valid($table_name=null) {
	$cdbt_option = get_option(CDBT_PLUGIN_SLUG);
	$current_table = empty($table_name) ? get_option(CDBT_PLUGIN_SLUG . '_current_table') : $table_name;
	if (!$current_table || !$cdbt_option) 
		return false;
	$is_enable_table = false;
	foreach ($cdbt_option['tables'] as $table) {
		if ($table['table_name'] == $current_table) {
			if ($table['table_type'] == 'enable_table') {
				$is_enable_table = true;
				break;
			}
		}
	}
	return $is_enable_table;
}

/**
 * get all options of table
 *
 * @param string $table_name (optional) default null
 * @return array|boolean
 */
function cdbt_get_options_table($table_name=null) {
	$cdbt_option = get_option(CDBT_PLUGIN_SLUG);
	$target_table = empty($table_name) ? get_option(CDBT_PLUGIN_SLUG . '_current_table') : $table_name;
	if (!$target_table || !$cdbt_option) 
		return false;
	foreach ($cdbt_option['tables'] as $table) {
		if ($table['table_name'] == $target_table) {
			$option_data = $table;
			break;
		}
	}
	$option_data = !isset($option_data) ? false : $option_data;
	return $option_data;
}

/**
 * output console's header menu area
 *
 * @param string $nonce
 * @return void
 */
function cdbt_create_console_menu($nonce) {
	$user_level = cdbt_current_user_level();
	$current_table = get_option(CDBT_PLUGIN_SLUG . '_current_table');
	$options = get_option(CDBT_PLUGIN_SLUG);
	$attr = disabled($current_table, false, false);
	$buttons[0] = array( // Index key number is button order from left.
		'_mode' => 'index', 
		'_name' => __('Home position', CDBT_PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => '', 
		'_icon' => 'dashboard', 
	);
	if ($user_level >= 9) {
		if (!$current_table) {
			$admin_attr = '';
		} else {
			$admin_attr = disabled(cdbt_check_current_table_role('admin'), false, false);
		}
	} else {
		if (!$current_table) {
			$admin_attr = $attr;
		} else {
			$admin_attr = disabled(cdbt_check_current_table_role('admin'), false, false);
		}
	}
	$buttons[1] = array(
		'_mode' => 'admin', 
		'_name' => __('Setting', CDBT_PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => $admin_attr, 
		'_icon' => 'cog', 
	);
	$buttons[2] = array(
		'_mode' => 'input', 
		'_name' => __('Input data', CDBT_PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => empty($attr) ? disabled(cdbt_check_current_table_role('input'), false, false) : $attr, 
		'_icon' => 'pencil', 
	);
	$buttons[3] = array( 
		'_mode' => 'list', 
		'_name' => __('View data', CDBT_PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => empty($attr) ? disabled(cdbt_check_current_table_role('view'), false, false) : $attr, 
		'_icon' => 'list', 
	); 
	$buttons[4] = array(
		'_mode' => 'edit', 
		'_name' => __('Edit data', CDBT_PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => empty($attr) ? disabled(cdbt_check_current_table_role('edit'), false, false) : $attr, 
		'_icon' => 'edit', 
	);
	ksort($buttons);
	$menu_content = '';
	foreach ($buttons as $button) {
		if ($nonce == wp_create_nonce(CDBT_PLUGIN_SLUG .'_'. $button['_mode'])) 
			$button['_class'] .= ' active';
		if (is_admin()) {
			$menu_url = wp_nonce_url(admin_url('options-general.php?page=' . CDBT_PLUGIN_SLUG . '&mode=' . $button['_mode']), CDBT_PLUGIN_SLUG .'_'. $button['_mode'], '_cdbt_token');
		} else {
			$menu_url = wp_nonce_url($_SERVER['SCRIPT_NAME'] . '?page=' . CDBT_PLUGIN_SLUG . '&mode=' . $button['_mode'], CDBT_PLUGIN_SLUG .'_'. $button['_mode'], '_cdbt_token');
		}
		$menu_content .= sprintf('<a href="%s" class="btn btn-%s"%s><span class="glyphicon glyphicon-%s"></span> %s</a>', $menu_url, $button['_class'], $button['_attr'], $button['_icon'], $button['_name']);
	}
	$plugin_version = sprintf('<div class="cdbt-version pull-right"><span class="label label-default">Ver. %s</span></div>', $options['plugin_version']);
	$console_title = sprintf('<h2 class="cdbt-title">%s%s</h2>', __('Custom DataBase Tables Management console', CDBT_PLUGIN_SLUG), $plugin_version);
	echo sprintf('<div class="console-container"><div class="console-menu">%s<div class="btn-group btn-group-justified">%s</div></div>', $console_title, $menu_content);
}

/**
 * output console's footer buttons and defined modal
 *
 * @param string $message default null
 * @param boolean $run default false
 * @param string $run_label default null
 * @param string $hidden_callback default null
 * @return void
 */
function cdbt_create_console_footer($message=null, $run=false, $run_label=null, $hidden_callback=null) {
	if (!empty($message)) {
		$is_run = cdbt_get_boolean($run) ? 'show-run' : '';
		$modal_kicker = sprintf('<div class="modal-kicker %s" data-run-label="%s" data-hidden-callback="%s">%s</div>', $is_run, $run_label, $hidden_callback, str_replace("\n", '<br />', strip_tags($message)));
	} else {
		$modal_kicker = '';
	}
?>
<footer>
	<?php echo $modal_kicker; ?>
</footer>
</div><!-- /.console-container -->
<!-- /* Modal */ -->
<div class="modal fade confirmation" tabindex="-1" role="dialog" aria-labelledby="confirmation" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove"></span></button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> <span class="cancel-close"><?php _e('Cancel', CDBT_PLUGIN_SLUG); ?></span></button>
        <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-ok"></span> <span class="run-process"><?php _e('Yes, run', CDBT_PLUGIN_SLUG); ?></span></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
}

/**
 * automatically create an input form based on a column schema
 *
 * @param string $table_name (must containing prefix of table)
 * @param string $culumn_name
 * @param array $culumn_schema
 * @param string $value
 * @param string $option (optional) If hide form 'hide', If not create form 'none'
 * @return string (eq. html document)
 */
function cdbt_create_form($table_name, $column_name, $column_schema, $value, $option=null) {
	$primary_key_name = null;
	$is_exists_created = $is_exists_updated = false;
	if ($column_schema['primary_key']) 
		$primary_key_name = $column_name;
	if (preg_match('/^updated$/i', $column_name) || $option == 'none') {
		// Automatic insertion by the database column is excluded.
		$component = null;
	} else if ($primary_key_name == $column_name && $column_schema['extra'] == 'auto_increment') {
		// Automatic insertion by the database column is excluded.
		$component = null;
	} else {
		$font_size = 13;
		$col_width = (int)ceil(($column_schema['max_length'] * $font_size) / 60);
		$col_width = ($col_width > 11) ? 11 : ($col_width == 1 ? 2 : $col_width);
		if (strval($value) == '0') {
			$set_value = $value;
		} else {
			$set_value = !empty($value) ? $value : $column_schema['default'];
		}
		$attr_id = $table_name . '-' . $column_name;
		$label_title = (empty($column_schema['logical_name'])) ? cdbt_sanitize_for_php($column_name, 'decode') : $column_schema['logical_name'];
		$require_label = ($column_schema['not_null']) ? ' <span class="label label-warning">'. __('require', CDBT_PLUGIN_SLUG) .'</span>' : '';
		$is_hidden = ($option == 'hide') ? true: false;
		$base_component = '<div class="form-group">%s</div>';
		if ($column_schema['type'] == 'enum') {
			// selectbox
			$eval_string = str_replace('enum', '$items = array', $column_schema['type_format']) . ';';
			eval($eval_string);
			if ($column_schema['not_null'] && empty($set_value)) 
				$is_hidden = false;
			if (!$is_hidden) {
				$input_form = '<div class="row"><div class="col-xs-'. $col_width .'"><label for="'. $attr_id .'">'. $label_title . $require_label .'</label>';
				$input_form .= '<select class="form-control" id="'. $attr_id .'" name="'. $attr_id .'">';
				foreach ($items as $item) {
					$input_form .= '<option value="'. $item .'"'. selected($set_value, $item, false) .'>'. cdbt__($item) .'</option>';
				}
				$input_form .= '</select></div></div>';
			} else {
				$input_form = '<input type="hidden" id="'. $attr_id .'" name="'. $attr_id .'" value="'. $set_value .'">';
			}
			
		} else if ($column_schema['type'] == 'set') {
			// multiple checkbox
			$eval_string = str_replace('set', '$items = array', $column_schema['type_format']) . ';';
			eval($eval_string);
			if ($column_schema['not_null'] && empty($set_value)) 
				$is_hidden = false;
			if (!$is_hidden) {
				$input_form = '<label>'. $label_title . $require_label .'</label><div>';
				$item_index = 1;
				if (!is_array($set_value)) {
					$set_value = explode(',', $set_value);
				}
				foreach ($items as $item) {
					$attr_checked = checked(in_array($item, $set_value), true, false);
					$input_form .= '<label class="checkbox-inline">';
					$input_form .= '<input type="checkbox" id="'. $attr_id .'-'. $item_index .'" name="'. $attr_id .'[]" value="'. $item .'"'. $attr_checked .' />';
					$input_form .= cdbt__($item) . '</label>';
					$item_index++;
				}
				$input_form .= '</div>';
			} else {
				$input_form = '<input type="hidden" id="'. $attr_id .'" name="'. $attr_id .'[]" value="'. $set_value .'">';
			}
			
		} else if (strtolower($column_schema['type_format']) == 'tinyint(1)') {
			// single checkbox
			$attr_checked = checked($set_value, 1, false);
			if (!$is_hidden) {
				$input_form = '<label>'. $label_title . $require_label .'</label><div class="checkbox"><label>';
				$input_form .= '<input type="checkbox" id="'. $attr_id .'" name="'. $attr_id .'" value="1"'. $attr_checked .' />';
				$input_form .= cdbt__($label_title) .'</label></div>';
			} else {
				if ($column_schema['not_null'] && empty($set_value)) 
					$set_value = 1;
				$input_form = '<input type="hidden" id="'. $attr_id .'" name="'. $attr_id .'" value="'. $set_value .'">';
			}
			
		} else if (strtolower($column_schema['type_format']) == 'bit(1)') {
			// radio
			if (!$is_hidden) {
				$input_form = '<label>'. $label_title . $require_label .'</label><div class="radio">';
				for ($i=1; $i>=0; $i--) {
					$attr_checked = checked($set_value, $i, false);
					$input_form .= '<label class="radio-inline pull-left"><input type="radio" id="'. $attr_id .'-'. $i .'" name="'. $attr_id .'" value="'. $i .'"'. $attr_checked .' />';
					$input_form .= ($i == 1 ? cdbt__('Yes') : cdbt__('No')) .'</label>';
				}
				$input_form .= '</div>';
			} else {
				if ($column_schema['not_null'] && empty($set_value)) 
					$set_value = 1;
				$input_form = '<input type="hidden" id="'. $attr_id .'" name="'. $attr_id .'" value="'. $set_value .'">';
			}
			
		} else if (preg_match('/^(text|mediumtext|longtext)$/i', $column_schema['type'])) {
			// textarea
			if (!$is_hidden) {
				$placeholder = sprintf(__('Enter %s', CDBT_PLUGIN_SLUG), $label_title);
				$default_rows = ceil(($column_schema['max_length'] * $font_size) / 940);
				$default_rows = ($default_rows > 6) ? 6 : 3;
				$input_form = '<label for="'. $attr_id .'">'. $label_title . $require_label .'</label>';
				$input_form .= '<textarea class="form-control" id="'. $attr_id .'" name="'. $attr_id .'" rows="'. $default_rows .'" placeholder="'. $placeholder .'">'. esc_textarea($set_value) .'</textarea>';
			} else {
				$input_form = '<input type="hidden" id="'. $attr_id .'" name="'. $attr_id .'" value="'. esc_textarea($set_value) .'">';
			}
			
		} else if (preg_match('/blob/i', strtolower($column_schema['type']))) {
			// file uploader
			$input_form = '<label for="'. $attr_id .'">'. $label_title . $require_label .'</label>';
			$input_form .= '<input type="file" id="'. $attr_id .'" name="'. $attr_id .'" accept="image/*, video/*, audio/*" />';
			if (isset($value) && !empty($value)) {
				$origin_bin_data = unserialize($value);
				if (!is_array($origin_bin_data)) 
					$origin_bin_data = array();
				if (!empty($origin_bin_data)) {
					$input_form .= '<input type="hidden" id="'. $attr_id .'-origin_bin_data" name="'. $attr_id .'-origin_bin_data" value="'. rawurlencode($value) .'" /> ';
					$input_form .= '<p class="help-block"><span class="glyphicon glyphicon-paperclip"></span> '. rawurldecode($origin_bin_data['origin_file']) .' ('. $origin_bin_data['file_size'] .'byte)</p>';
				}
			} else {
				$origin_bin_data = null;
			}
			
		} else {
			// text field
			if (strtolower($column_schema['default']) == 'current_timestamp' || preg_match('/current_timestamp/i', strtolower($column_schema['extra']))) {
				$current_timestamp = date('Y-m-d h:i:s', strtotime('now'));
				$input_form = '<input type="hidden" id="'. $attr_id .'" name="'. $attr_id .'" value="'. $current_timestamp .'">';
			} else {
				if (!$is_hidden) {
					$placeholder = sprintf(__('Enter %s', CDBT_PLUGIN_SLUG), $label_title);
					$input_type = (preg_match('/(password|passwd)/i', strtolower($column_name))) ? 'password' : 'text';
					$input_type = (preg_match('/(int|numeric|float|bit)/i', strtolower($column_schema['type_format']))) ? 'number' : $input_type;
					$input_form = '<div class="row"><div class="col-xs-'. $col_width .'"><label for="'. $attr_id .'">'. $label_title . $require_label .'</label>';
					$input_form .= '<input type="'. $input_type .'" class="form-control" id="'. $attr_id .'" name="'. $attr_id .'" placeholder="'. $placeholder .'" value="'. esc_html($set_value) .'" />';
					$input_form .= '</div></div>';
				} else {
					$input_form = '<input type="hidden" id="'. $attr_id .'" name="'. $attr_id .'" value="'. esc_html($set_value) .'">';
				}
			}
			
		}
		$component = sprintf($base_component, $input_form);
	}
	return $component;
}

/**
 * generate a button object of the bootstrap
 *
 * @param string $btn_type default 'button'
 * @param string|array $btn_value (If $btn_type is "stateful", second arg in array is used for string that will change after clicked button.)
 * @param string $btn_id (optional) (eq. id attribute value in button tag)
 * @param string $btn_class (optional) default 'default' (eq. class attribute value of "btn-*" in button tag)
 * @param string $btn_action (optional) (eq. data-action attribute value in button tag)
 * @param string $prefix_icon (optional) (eq. value of "glyphicon-*" of the bootstrap)
 * @return string (eq. html document)
 */
function cdbt_create_button($btn_type='button', $btn_value, $btn_id=null, $btn_class='default', $btn_action=null, $prefix_icon=null) {
	$btn_display = (is_array($btn_value)) ? array_shift($btn_value) : $btn_value;
	$change_str = (is_array($btn_value)) ? array_shift($btn_value) : $btn_display;
	if (!empty($prefix_icon)) {
		$btn_display = '<span class="glyphicon glyphicon-'. $prefix_icon .'"></span> ' . $btn_display;
	}
	$base_btn_content = '<button type="%s"%s class="btn btn-%s" data-action="%s">%s</button>';
	if ($btn_type == 'stateful') {
		$btn_type = 'button';
		$attr_id = (!empty($btn_id)) ? ' id="'. $btn_id .'" data-loading-text="' . $change_str . '"' : '';
	} else if ($btn_type == 'toggle') {
		$btn_type = 'button';
		$attr_id = (!empty($btn_id)) ? ' id="'. $btn_id .'" data-toggle="button"' : '';
	} else {
		$attr_id = (!empty($btn_id)) ? ' id="'. $btn_id .'"' : '';
	}
	$btn_content = sprintf($base_btn_content, $btn_type, $attr_id, $btn_class, $btn_action, $btn_display);
	return $btn_content;
}

/**
 * truncate strings by specific length
 *
 * @param string $string
 * @param int $length default 40
 * @param string $suffix (optional) default '...'
 * @param boolean $collapse (optional) default false
 * @return string
 */
function cdbt_str_truncate($string, $length=40, $suffix='...', $collapse=false) {
	if (mb_check_encoding($string, 'utf-8') && function_exists('mb_strwidth') && function_exists('mb_strimwidth')) {
		if (mb_strwidth($string) > (int)$length*2) {
			$truncated_str = mb_strimwidth(strip_tags($string), 0, (int)$length*2, $suffix, 'utf-8');
		}
	} else if (strlen($string) > (int)$length) {
		$truncated_str = substr(strip_tags($string), 0, $length) . $suffix;
	}
	if (isset($truncated_str) && !empty($truncated_str)) {
		if ($collapse) {
			$truncated_str = '<p class="text-collapse" full-content="'. esc_html($string) .'">'. $truncated_str .' <b class="caret"></b></p>';
		}
	} else {
		$truncated_str = esc_html($string);
	}
	return $truncated_str;
}

/**
 * compare the variable
 *
 * @param mixed (string|int|boolean) $var
 * @param mixed (string|int|boolean) $compare
 * @return boolean
 */
function cdbt_compare_var($var, $compare=null) {
	return (string)$var === (string)$compare;
}

/**
 * return boolean
 *
 * @param string $string
 * @return boolean
 */
function cdbt_get_boolean($string) {
	if (is_bool($string)) {
		return $string;
	} else {
		if (empty($string)) {
			return false;
		} else if (is_string($string)) {
			return ($string == 'false' || $string == '0') ? false : true;
		} else if (is_int($string)) {
			return $string == 0 ? false : true;
		} else {
			return false;
		}
	}
}

/**
 * return translated strings
 *
 * @param string $string
 * @return string
 */
function cdbt__($string) {
	return __($string, CDBT_PLUGIN_SLUG);
}

/**
 * output for echo translated strings
 *
 * @param string $string
 * @return void
 */
function cdbt_e($string) {
	_e($string, CDBT_PLUGIN_SLUG);
}

/**
 * sanitize strings of MySQL identifier for using on PHP
 *
 * @param string $string
 * @param string $action default 'encode'
 * @return string
 */
function cdbt_sanitize_for_php($string, $action='encode') {
	if ($action == 'encode') {
		$result = preg_replace('/\.+/', '%2E', rawurlencode($string));
	} else {
		$result = rawurldecode(preg_replace('/(%2E)+/', '.', $string));
	}
	return $result;
}

/**
 * verify whether passed value is binary data
 *
 * @param string $raw_data
 * @param boolean $include_bin_data (optional) true if want to include of binary data body encoded base64 (but image only) in return value
 * @return mixed (boolean|array) false if not binary. Or, array of parsed binary data
 */
function cdbt_verify_binary($raw_data, $include_bin_data=false) {
	$is_binary = (preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($raw_data, 0, 24))) ? true : false;
	if ($is_binary) {
		$response = array();
		eval('$tmp = array(' . trim(preg_replace('/(a:\d+:{|(|;)s:\d+:|(|;)i:|"$)/', ",", substr($raw_data, 0, strpos($raw_data, 'bin_data'))), ',,') . ');');
		foreach ($tmp as $i => $val) {
			if ($val == 'origin_file')	$response[$val] = $tmp[intval($i)+1];
			if ($val == 'mine_type')	$response[$val] = $tmp[intval($i)+1];
			if ($val == 'file_size') 	$response[$val] = $tmp[intval($i)+1];
		}
		if (preg_match('/^image\/.*/i', $response['mine_type'])) {
			$unpack_bin = unserialize($raw_data);
			$response['bin_base64'] =  base64_encode($unpack_bin['bin_data']);
		} else {
			$response['bin_base64'] = '';
		}
	} else {
		$response = false;
	}
	return $response;
}

/**
 * load for using css framework
 * 
 * @param string $cssfw (optional)
 * @return void
 */
function cdbt_load_css_framework($cssfw=null) {
	if ($cssfw == 'bootstrap') {
		global $cdbt;
		wp_register_style('cdbt-common-style', $cdbt->dir_url . '/assets/css/cdbt-main.min.css', array(), $cdbt->version, 'all');
		wp_register_style('cdbt-custom-style', $cdbt->dir_url . '/assets/css/cdbt-style.css', array(), $cdbt->version, 'all');
		wp_enqueue_style('cdbt-common-style');
		wp_enqueue_style('cdbt-custom-style');
		wp_register_script('cdbt-common-script', $cdbt->dir_url . '/assets/js/scripts.min.js', array(), null, false);
		wp_enqueue_script('cdbt-common-script');
	}
}

/**
 * discription
 * 
 * @param - -
 * @return - -
 */
function new_function() {
	// for New Featrue
}
