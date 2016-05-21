<?php

namespace CustomDataBaseTables\Lib;

/**
 * Trait for shortcode of "cdbt-submit"
 *
 * @since 2.1.31
 *
 */
trait CdbtSubmit {
  
  /**
   * for [cdbt-submit] ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
   * This shortcode can executes any queries from front-end. (Currently it's deprecated; actually not working)
   *
   * @since 2.0.x
   *
   * @param  array  $attributes [require]  - Array in shortcode's attributes
   * @param  string $content    [optional] - Should be actually nothing
   * @return string $html_content          - The formatted content to the specific list
   **/
  public function submit_custom_query() {
	extract(shortcode_atts(array(
      'table' => '', 					// @attribute string [required] Specifies the table name you want to display the data.
      'query' => '', 					// @attribute string [required] Specifies the sql query to execute after submitted.
      'type' => 'button', 				// @attribute string [optional] Specifies the component type for submitting a query, whether it's a "button" or a "link".
      'label' => 'Submit', 				// @attribute string [optional] Specifies the label name of button or link.
      'onclick' => '', 					// @attribute string [optional] Specifies the custom onclick event (as function name of javascript) to fire when this component will be clicked.
      'callback' => '', 				// @attribute string [optional] Specifies the custom callback function name of javascript to fire after the submitted process has completed.
      'final' => '', 					// @attribute string [optional] Specifies the custom final event name (as function name of javascript) to fire at last after the ajax process.
      'add_class' => '', 				// @attribute string [optional] Specifies a CSS class name for styling the element of listed data table. If there are multiple class, please separated by a single-byte space.
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
			$err_permission = sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('input', CDBT_PLUGIN_SLUG));
		if ($query_action == 'update' && !cdbt_check_current_table_role('edit', $table)) 
			$err_permission = sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('edit', CDBT_PLUGIN_SLUG));
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
	$attributes = array();
	if (isset($err_permission) && !empty($err_permission)) {
		$content_id = "cdbt-submit";
		$attributes[] = sprintf('title="%s"', $err_permission);
		$attributes[] = 'disabled="disabled"';
	} else {
		$content_id = "cdbt-submit-{$hash_id}";
		if (!empty($onclick)) 
			$attributes[] = sprintf('data-onclick="%s"', $onclick);
		if (!empty($callback)) 
			$attributes[] = sprintf('data-callback="%s"', $callback);
		if (!empty($final)) 
			$attributes[] = sprintf('data-final="%s"', $final);
	}
	$add_class = ($type != 'link' && empty($add_class)) ? 'btn-primary' : $add_class;
	$render_content = sprintf($template_content, $content_id, $add_class, implode(' ', $attributes), $label);
	
	return $render_content;
  }

}