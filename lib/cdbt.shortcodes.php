<?php

$cdbt_shortcodes = array(
	'cdbt-view' => 'cdbt_display_view_table', 
	'cdbt-entry' => 'cdbt_display_entry_table', 
	'cdbt-edit' => 'cdbt_display_edit_table', 
	'cdbt-extract' => 'cdbt_display_extract_data', 
	'cdbt-submit' => 'cdbt_submit_custom_query', 
);
foreach ($cdbt_shortcodes as $shortcode_name => $function_name) {
	add_shortcode($shortcode_name, $function_name);
}

function cdbt_display_view_table($atts, $content='') {
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
		'display_title' => true, 
		'display_search' => true, 
		'display_list_num' => true, 
		'enable_sort' => true, 
		'exclude_cols' => '', // a column name or Comma-separated columns name
		'add_class' => '', 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('Specified table is not exist', CDBT_PLUGIN_SLUG);
	if (!cdbt_check_current_table_role('view', $table)) 
		return sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('view', CDBT_PLUGIN_SLUG));
	
	if (cdbt_get_boolean($bootstrap_style)) 
		cdbt_load_css_framework('bootstrap');
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_title' => cdbt_get_boolean($display_title), 
		'display_search' => cdbt_get_boolean($display_search), 
		'display_list_num' => cdbt_get_boolean($display_list_num), 
		'enable_sort' => cdbt_get_boolean($enable_sort), 
		'exclude_cols' => !empty($exclude_cols) ? explode(',', $exclude_cols) : array(), 
		'add_class' => $add_class, 
	);
	require_once CDBT_PLUGIN_TMPL_DIR . CDBT_DS . 'cdbt-public-list.php';
	$mode = 'list';
	$_cdbt_token = wp_create_nonce(CDBT_PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_list_page($table, $mode, $_cdbt_token, $options);
}

function cdbt_display_entry_table($atts, $content='') {
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
		'display_title' => true, 
		'hidden_cols' => '', // a column name or Comma-separated columns name
		'add_class' => '', 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('Specified table is not exist', CDBT_PLUGIN_SLUG);
	if (!cdbt_check_current_table_role('input', $table)) 
		return sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('input', CDBT_PLUGIN_SLUG));
	
	if (cdbt_get_boolean($bootstrap_style)) 
		cdbt_load_css_framework('bootstrap');
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_title' => cdbt_get_boolean($display_title), 
		'hidden_cols' => !empty($hidden_cols) ? explode(',', $hidden_cols) : array(), 
		'add_class' => $add_class, 
	);
	require_once CDBT_PLUGIN_TMPL_DIR . CDBT_DS . 'cdbt-public-input.php';
	$mode = 'input';
	$_cdbt_token = wp_create_nonce(CDBT_PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_input_page($table, $mode, $_cdbt_token, $options);
}

function cdbt_display_edit_table($atts, $content='') {
	extract(shortcode_atts(array(
		'table' => '', 
		'entry_page' => '', // integer of post ID or strings of post name
		'bootstrap_style' => true, 
		'display_title' => true, 
		'display_list_num' => true, 
		'enable_sort' => true, 
		'exclude_cols' => '', // a column name or Comma-separated columns name
		'add_class' => '', 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('Specified table is not exist', CDBT_PLUGIN_SLUG);
	if (!cdbt_check_current_table_role('edit', $table)) 
		return sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('edit', CDBT_PLUGIN_SLUG));
	
	if (cdbt_get_boolean($bootstrap_style)) 
		cdbt_load_css_framework('bootstrap');
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_title' => cdbt_get_boolean($display_title), 
		'display_list_num' => cdbt_get_boolean($display_list_num), 
		'enable_sort' => cdbt_get_boolean($enable_sort), 
		'exclude_cols' => !empty($exclude_cols) ? explode(',', $exclude_cols) : array(), 
		'entry_page' => $entry_page, 
		'add_class' => $add_class, 
	);
	require_once CDBT_PLUGIN_TMPL_DIR . CDBT_DS . 'cdbt-public-edit.php';
	$mode = 'edit';
	$_cdbt_token = wp_create_nonce(CDBT_PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_edit_page($table, $mode, $_cdbt_token, $options);
}

function cdbt_display_extract_data($atts, $content='') {
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
		'display_index_row' => true, 
		'narrow_keyword' => array(), // example: "keyword1,keyword2,..." is find_data() or "col1:keyword1,col2:keyword2,..." is get_data()
		'display_cols' => array(), // example: "col1,col2,col3,..."
		'order_cols' => array(), // example: "col3,col2,col1,..."
		'sort_order' => array('created'=>'desc'), // eq. hash example: "updated:desc,ID:asc,..."
		'limit_items' => 5, 
		'image_render' => '', 
		'add_class' => '', 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('Specified table is not exist', CDBT_PLUGIN_SLUG);
	if (!cdbt_check_current_table_role('view', $table)) 
		return sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('view', CDBT_PLUGIN_SLUG));
	
	if (cdbt_get_boolean($bootstrap_style)) 
		cdbt_load_css_framework('bootstrap');
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	if (!empty($narrow_keyword) && !is_array($narrow_keyword)) {
		$tmp_ary = explode(',', $narrow_keyword);
		$narrow_keyword = array();
		foreach ($tmp_ary as $tmp_val) {
			$parse_val = explode(':', $tmp_val);
			if (count($parse_val) > 1) {
				$col_name = trim(trim(stripcslashes($parse_val[0])), "\"' ");
				$keyword = trim(trim(stripcslashes($parse_val[1])), "\"' ");
				if (!empty($col_name) && !empty($keyword)) 
					$narrow_keyword[$col_name] = $keyword;
			} else {
				$keyword = trim(trim(stripcslashes($parse_val[0])), "\"' ");
				if (empty($keyword)) 
					$narrow_keyword[] = $keyword;
			}
		}
	}
	if (empty($narrow_keyword)) 
		$narrow_keyword = array();
	
	if (!empty($sort_order) && !is_array($sort_order)) {
		$tmp_ary = explode(',', $sort_order);
		$sort_order = array();
		foreach ($tmp_ary as $tmp_val) {
			list($col_name, $order_str) = explode(':', $tmp_val);
			$col_name = trim(trim(stripcslashes($col_name)), "\"' ");
			$order_str = trim(trim(stripcslashes($order_str)), "\"' ");
			if (!empty($col_name) && !empty($order_str)) 
				$sort_order[$col_name] = $order_str;
		}
	}
	if (empty($sort_order)) 
		$sort_order = array('created'=>'desc');
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_index_row' => cdbt_get_boolean($display_index_row), 
		'narrow_keyword' => $narrow_keyword, 
		'display_cols' => !empty($display_cols) ? explode(',', $display_cols) : array(), 
		'order_cols' => !empty($order_cols) ? explode(',', $order_cols) : array(), 
		'sort_order' => $sort_order, 
		'limit_items' => intval($limit_items), 
		'image_render' => $image_render, 
		'add_class' => $add_class, 
	);
	require_once CDBT_PLUGIN_TMPL_DIR . CDBT_DS . 'cdbt-public-contents.php';
	$mode = 'extract';
	$_cdbt_token = wp_create_nonce(CDBT_PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_contents($table, $mode, $_cdbt_token, $options);
}

function cdbt_submit_custom_query($atts, $content='') {
	extract(shortcode_atts(array(
		'table' => '', 
		'query' => '', 
		'type' => 'button', // or 'link'
		'label' => 'Submit', 
		'onclick' => '', // my custom onclick event name (when click on element)
		'callback' => '', // my callback function name (when ajax complete event)
		'final' => '', // my final process event name (when ending after ajax)
		'add_class' => '', 
	), $atts));
	global $cdbt;
	// verification for using shortcode
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('Specified table is not exist', CDBT_PLUGIN_SLUG);
	if (empty($query)) 
		return __('Specifying query is nothing', CDBT_PLUGIN_SLUG);
	if (preg_match('/^(insert|update)\s(.*)$/iU', $query, $matches)) {
		$query_action = strtolower($matches[1]);
		if ($query_action == 'insert' && !cdbt_check_current_table_role('input', $table)) 
			return sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('input', CDBT_PLUGIN_SLUG));
		if ($query_action == 'update' && !cdbt_check_current_table_role('edit', $table)) 
			return sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('edit', CDBT_PLUGIN_SLUG));
	} else {
		return __('Can not use your specified query', CDBT_PLUGIN_SLUG);
	}
	if (!isset($query_action)) 
		return __('Specified query is invalid', CDBT_PLUGIN_SLUG);
	
	// verification of sql query
	if ($query_action == 'insert') {
		if (preg_match('/into\s(.*)(\s|)\((.*)\)\s{1,}values(\s|)\((.*)\)\s{0,}(;|)$/iU', preg_replace('/(?:\n|\r|\r\n)/', '', trim($matches[2])), $parse_query)) {
			$query_elms = array();
			$query_elms['table_name'] = ($parse_query[1] == '@' || $parse_query[1] != $table) ? $table : trim($parse_query[1]);
			$query_elms['columns'] = explode(',', trim($parse_query[3]));
			$tmp_values = explode(',', trim($parse_query[5]));
			$query_elms['values'] = array();
			foreach ($query_elms['columns'] as $i => $col) {
				$query_elms['values'][] = $tmp_values[$i];
			}
			$prepared_query = sprintf('INSERT INTO `%s` (%s) VALUES (%s);', $query_elms['table_name'], implode(',', $query_elms['columns']), implode(',', $query_elms['values']));
		} else {
			return __('Specified query is invalid', CDBT_PLUGIN_SLUG);
		}
	}
	if ($query_action == 'update') {
		if (preg_match('/(.*)\s{1,}set\s{1,}(.*)(where\s{1,}(.*)|)(;|)$/iU', preg_replace('/(?:\n|\r|\r\n)/', '', trim($matches[2])), $parse_query)) {
			$query_elms = array();
			$query_elms['table_name'] = ($parse_query[1] == '@' || $parse_query[1] != $table) ? $table : trim($parse_query[1]);
			$tmp_sets = explode(',', trim($parse_query[2]));
			$query_elms['columns'] = $query_elms['values'] = $query_elms['set_clause'] = array();
			foreach ($tmp_sets as $val) {
				list($str_col, $str_val) = explode('=', trim($val));
				$query_elms['columns'][] = trim($str_col);
				$query_elms['values'][] = trim($str_val);
				$query_elms['set_clause'][] = trim($str_col) .' = '. trim($str_val);
			}
			$query_elms['where'] = trim($parse_query[4]);
			if (empty($query_elms['where'])) {
				$prepared_query = sprintf('UPDATE `%s` SET %s;', $query_elms['table_name'], implode(', ', $query_elms['set_clause']));
			} else {
				$prepared_query = sprintf('UPDATE `%s` SET %s WHERE %s;', $query_elms['table_name'], implode(', ', $query_elms['set_clause']), $query_elms['where']);
			}
			
		} else {
			return __('Specified query is invalid', CDBT_PLUGIN_SLUG);
		}
	}
	if (empty($prepared_query)) 
		return __('Specified query is invalid', CDBT_PLUGIN_SLUG);
	
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	// create content for rendering at HTML
	$hash_id = md5($prepared_query);
	if (get_option(CDBT_PLUGIN_SLUG . '_stored_queries') !== false) {
		$stored_queries = get_option(CDBT_PLUGIN_SLUG . '_stored_queries');
		$stored_queries[$hash_id] = $prepared_query;
		update_option(CDBT_PLUGIN_SLUG . '_stored_queries', $stored_queries);
	} else {
		add_option(CDBT_PLUGIN_SLUG . '_stored_queries', array($hash_id => $prepared_query), '', 'no');
	}
	$template_content = $type == 'link' ? '<a href="#" id="%s" class="%s" %s>%s</a>' : '<button type="button" id="%s" class="btn %s" %s>%s</button>';
	$content_id = "cdbt-submit-{$hash_id}";
	$add_class = ($type != 'link' && empty($add_class)) ? 'btn-primary' : $add_class;
	$attributes = array();
	//$attributes[] = sprintf('data-query="%s"', $query_data);
	if (!empty($onclick)) 
		$attributes[] = sprintf('data-onclick="%s"', $onclick);
	if (!empty($callback)) 
		$attributes[] = sprintf('data-callback="%s"', $callback);
	if (!empty($final)) 
		$attributes[] = sprintf('data-final="%s"', $final);
	$render_content = sprintf($template_content, $content_id, $add_class, implode(' ', $attributes), $label);
	
	return $render_content;
}