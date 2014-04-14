<?php
/**
 * Custom DataBase Tables APIs
 */

/**
 * Create pagination
 * @param int $page
 * @param int $per_page
 * @param int $total_data
 * @param string $mode (optional) default 'list'
 * @return string
 */
function create_pagination($page, $per_page, $total_data, $mode='list') {
	$max_pages = ceil($total_data / $per_page);
	$pagination_base = '<div class="text-center"><ul class="pagination pagination-sm">%s</ul>%s</div>';
	$disabled_class = ' class="disabled"';
	$active_class = ' class="active"';
	$pagination_left = '<li%s>%s&laquo;%s</li>';
	$pagination_right = '<li%s>%s&raquo;%s</li>';
	$pagination_html = null;
	$pagination_html .= ($page == 1) ? sprintf($pagination_left, $disabled_class, '<span>', '</span>') : sprintf($pagination_left, '', '<a href="#" data-page="1">', '</a>');
	for ($i = 1; $i <= $max_pages; $i++) {
		$pagination_inner = '<li%s>%s'. $i .'%s</li>';
		$pagination_html .= ($page == $i) ? sprintf($pagination_inner, $active_class, '<span>', ' <span class="sr-only">(current)</span></span>') : sprintf($pagination_inner, '', '<a href="#" data-page="'.$i.'">', '</a>');
	}
	$pagination_html .= ($page == $max_pages) ? sprintf($pagination_right, $disabled_class, '<span>', '</span>') : sprintf($pagination_right, '', '<a href="#" data-page="'. $max_pages .'">', '</a>');
	$pagination_script = <<<EOS
<script>
jQuery(document).ready(function(){
	jQuery('.pagination a').on('click', function(){
		jQuery('.change-page').children('input[name="page"]').val(jQuery(this).attr('data-page'));
		jQuery('.change-page').submit();
	});
});
</script>
EOS;
	return sprintf($pagination_base, $pagination_html, $pagination_script);
}

/**
 * get level of current login user
 * @return int
 */
function current_user_level() {
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
		$level = 0;
	}
	return $level;
}

/**
 * check role if current login user can use current table 
 * @param string $mode
 * @return boolean
 */
function check_current_table_role($mode) {
	$cdbt_option = get_option(PLUGIN_SLUG);
	$current_table = get_option(PLUGIN_SLUG . '_current_table');
	if (!$current_table || !$cdbt_option) 
		return false;
	$is_enable_mode = false;
	foreach ($cdbt_option['tables'] as $table) {
		if ($table['table_name'] == $current_table) {
			if ($table['roles'][$mode . '_role'] <= current_user_level()) {
				$is_enable_mode = true;
				break;
			}
		}
	}
	return $is_enable_mode;
}

/**
 * output console's header menu area
 * @param string $nonce
 * @return void
 */
function create_console_menu($nonce) {
	$user_level = current_user_level();
//var_dump($user_level);
	$current_table = get_option(PLUGIN_SLUG . '_current_table');
	$attr = disabled($current_table, false, false);
	$buttons[0] = array( // Index key number is button order from left.
		'_mode' => 'index', 
		'_name' => __('Home position', PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => '', 
		'_icon' => 'dashboard', 
	);
	if ($user_level >= 9) {
		if (!$current_table) {
			$admin_attr = '';
		} else {
			$admin_attr = disabled(check_current_table_role('admin'), false, false);
		}
	} else {
		if (!$current_table) {
			$admin_attr = $attr;
		} else {
			$admin_attr = disabled(check_current_table_role('admin'), false, false);
		}
	}
	$buttons[1] = array(
		'_mode' => 'admin', 
		'_name' => __('Setting', PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => $admin_attr, 
		'_icon' => 'cog', 
	);
	$buttons[2] = array(
		'_mode' => 'input', 
		'_name' => __('Input data', PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => empty($attr) ? disabled(check_current_table_role('input'), false, false) : $attr, 
		'_icon' => 'pencil', 
	);
	$buttons[3] = array( 
		'_mode' => 'list', 
		'_name' => __('View data', PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => empty($attr) ? disabled(check_current_table_role('view'), false, false) : $attr, 
		'_icon' => 'list', 
	); 
	$buttons[4] = array(
		'_mode' => 'edit', 
		'_name' => __('Edit data', PLUGIN_SLUG), 
		'_class' => 'default', 
		'_attr' => empty($attr) ? disabled(check_current_table_role('edit'), false, false) : $attr, 
		'_icon' => 'edit', 
	);
	ksort($buttons);
	$menu_content = '';
	foreach ($buttons as $button) {
		if ($nonce == wp_create_nonce(PLUGIN_SLUG .'_'. $button['_mode'])) 
			$button['_class'] .= ' active';
		if (is_admin()) {
			$menu_url = wp_nonce_url(admin_url('options-general.php?page=' . PLUGIN_SLUG . '&mode=' . $button['_mode']), PLUGIN_SLUG .'_'. $button['_mode'], '_cdbt_token');
		} else {
			$menu_url = wp_nonce_url($_SERVER['SCRIPT_NAME'] . '?page=' . PLUGIN_SLUG . '&mode=' . $button['_mode'], PLUGIN_SLUG .'_'. $button['_mode'], '_cdbt_token');
		}
		$menu_content .= sprintf('<a href="%s" class="btn btn-%s"%s><span class="glyphicon glyphicon-%s"></span> %s</a>', $menu_url, $button['_class'], $button['_attr'], $button['_icon'], $button['_name']);
	}
	$console_title = sprintf('<h2 class="cdbt-title">%s</h2>', __('Custom DataBase Tables Management console', PLUGIN_SLUG));
	echo sprintf('<div class="console-container"><div class="console-menu">%s<div class="btn-group btn-group-justified">%s</div></div>', $console_title, $menu_content);
}

/**
 * output console's footer buttons and defined modal
 * @param string $message default null
 * @return void
 */
function create_console_footer($message=null) {
	if (!empty($message)) {
		$modal_kicker = sprintf('<div class="modal-kicker">%s</div>', str_replace("\n", '<br />', strip_tags($message)));
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
        <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> <span class="cancel-close"><?php _e('Cancel', PLUGIN_SLUG); ?></span></button>
        <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-ok"></span> <span class="run-process"><?php _e('Yes, run', PLUGIN_SLUG); ?></span></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
}

/**
 * automatically create an input form based on a column schema
 * @param string $table_name (must containing prefix of table)
 * @param string $culumn_name
 * @param array $culumn_schema
 * @param string $value
 * @return string (eq. html document)
 */
function create_form($table_name, $column_name, $column_schema, $value) {
	if (preg_match('/^(ID|created|updated)$/i', $column_name)) {
		// Automatic insertion by the database column is excluded.
		$component = null;
	} else {
		$font_size = 13;
		$col_width = (int)ceil(($column_schema['max_length'] * $font_size) / 60);
		$col_width = ($col_width > 11) ? 11 : ($col_width == 1 ? 2 : $col_width);
		$set_value = !empty($value) ? $value : $column_schema['default'];
		$attr_id = $table_name . '-' . $column_name;
		$label_title = (empty($column_schema['logical_name'])) ? $column_name : $column_schema['logical_name'];
		$require_label = ($column_schema['not_null']) ? ' <span class="label label-warning">require</span>' : '';
		$base_component = '<div class="form-group">%s</div>';
		if ($column_schema['type'] == 'enum') {
			// selectbox
			$eval_string = str_replace('enum', '$items = array', $column_schema['type_format']) . ';';
			eval($eval_string);
			$input_form = '<div class="row"><div class="col-xs-'. $col_width .'"><label for="'. $attr_id .'">'. $label_title . $require_label .'</label>';
			$input_form .= '<select class="form-control" id="'. $attr_id .'" name="'. $attr_id .'">';
			foreach ($items as $item) {
				$input_form .= '<option value="'. $item .'"'. selected($set_value, $item, false) .'>'. _cdbt($item) .'</option>';
			}
			$input_form .= '</select></div></div>';
			
		} else if ($column_schema['type'] == 'set') {
			// multiple checkbox
			$eval_string = str_replace('set', '$items = array', $column_schema['type_format']) . ';';
			eval($eval_string);
			$input_form = '<label>'. $label_title . $require_label .'</label><div>';
			$item_index = 1;
			if (!is_array($set_value)) {
				$set_value = explode(',', $set_value);
			}
			foreach ($items as $item) {
				$attr_checked = checked(in_array($item, $set_value), true, false);
				$input_form .= '<label class="checkbox-inline">';
				$input_form .= '<input type="checkbox" id="'. $attr_id .'-'. $item_index .'" name="'. $attr_id .'[]" value="'. $item .'"'. $attr_checked .' />';
				$input_form .= _cdbt($item) . '</label>';
				$item_index++;
			}
			$input_form .= '</div>';
			
		} else if (strtolower($column_schema['type_format']) == 'tinyint(1)') {
			// single checkbox
			$attr_checked = checked($set_value, 1, false);
			$input_form = '<label>'. $label_title . $require_label .'</label><div class="checkbox"><label>';
			$input_form .= '<input type="checkbox" id="'. $attr_id .'" name="'. $attr_id .'" value="1"'. $attr_checked .' />';
			$input_form .= _cdbt($label_title) .'</label></div>';
			
		} else if ($column_schema['type'] == 'text') {
			// textarea
			$default_rows = ceil(($column_schema['max_length'] * $font_size) / 940);
			$default_rows = ($default_rows > 6) ? 6 : 3;
			$input_form = '<label for="'. $attr_id .'">'. $label_title . $require_label .'</label>';
			$input_form .= '<textarea class="form-control" id="'. $attr_id .'" name="'. $attr_id .'" rows="'. $default_rows .'">'. esc_textarea($set_value) .'</textarea>';
			
		} else if (preg_match('/blob/i', strtolower($column_schema['type']))) {
			// file uploader
			$input_form = '<label for="'. $attr_id .'">'. $label_title . $require_label .'</label>';
			$input_form .= '<input type="file" id="'. $attr_id .'" name="'. $attr_id .'" accept="image/*, video/*, audio/*" />';
			if (isset($value) && !empty($value)) {
				$origin_bin_data = unserialize($value);
				if (!is_array($origin_bin_data)) 
					$origin_bin_data = array();
				if (!empty($origin_bin_data)) {
					$input_form .= '<input type="hidden" name="origin_bin_data" value="'. rawurlencode($value) .'" /> ';
					$input_form .= '<p class="help-block"><span class="glyphicon glyphicon-paperclip"></span> '. $origin_bin_data['origin_file'] .' ('. $origin_bin_data['file_size'] .'byte)</p>';
				}
			}
			
		} else {
			// text field
			$placeholder = sprintf(__('Enter %s', PLUGIN_SLUG), $label_title);
			$input_type = (preg_match('/(password|passwd)/i', strtolower($column_name))) ? 'password' : 'text';
			$input_form = '<div class="row"><div class="col-xs-'. $col_width .'"><label for="'. $attr_id .'">'. $label_title . $require_label .'</label>';
			$input_form .= '<input type="'. $input_type .'" class="form-control" id="'. $attr_id .'" name="'. $attr_id .'" placeholder="'. $placeholder .'" value="'. esc_html($set_value) .'" />';
			$input_form .= '</div></div>';
			
		}
		$component = sprintf($base_component, $input_form);
	}
	return $component;
}

/**
 * generate a button object of the bootstrap
 * @param string $btn_type default 'button'
 * @param string|array $btn_value (If $btn_type is "stateful", second arg in array is used for string that will change after clicked button.)
 * @param string $btn_id (optional) (eq. id attribute value in button tag)
 * @param string $btn_class (optional) default 'default' (eq. class attribute value of "btn-*" in button tag)
 * @param string $btn_action (optional) (eq. data-action attribute value in button tag)
 * @param string $prefix_icon (optional) (eq. value of "glyphicon-*" of the bootstrap)
 * @return string (eq. html document)
 */
function create_button($btn_type='button', $btn_value, $btn_id=null, $btn_class='default', $btn_action=null, $prefix_icon=null) {
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
 * return translated strings
 * @param string $string
 * @return string
 */
function _cdbt($string) {
	return __($string, PLUGIN_SLUG);
}

/**
 * output for echo translated strings
 * @param string $string
 * @return void
 */
function _e_cdbt($string) {
	_e($string, PLUGIN_SLUG);
}


// will have translated strings for system before use.
$translate_temp = array(
	__('This plug-in allows you to perform data storage and reference by creating a free tables in database of WordPress.'), 
	__('Katsuhiko Maeno'), 
	__('http://www.ka2.org'), 
	__('logical_name'), 
	__('max_length'), 
	__('octet_length'), 
	__('not_null'), 
	__('default'), 
	__('type'), 
	__('type_format'), 
	__('primary_key'), 
	__('column_key'), 
	__('unsigned'), 
	__('extra'), 
	__('auto_increment'), 
);
