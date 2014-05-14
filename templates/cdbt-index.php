<?php
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) 
	die();

foreach ($_REQUEST as $k => $v) {
	${$k} = $v;
}
if ($page != self::DOMAIN) 
	die(__('Invalid access that is not from admin panel!', self::DOMAIN));
if (!isset($mode)) 
	$mode = 'index';
if (!isset($_cdbt_token)) 
	$_cdbt_token = wp_create_nonce(self::DOMAIN .'_'. $mode);
if(!$this->check_table_exists()) 
	die(__('The controller table of this plugin is not exists! Please try to reinstall.', self::DOMAIN));

list($result, $table_name, $table_schema) = $this->get_table_schema();
if ($result && !empty($table_name) && !empty($table_schema)) {
	create_console_menu($_cdbt_token);
	
	$schm_html = '<h3 class="dashboard-title"><span class="glyphicon glyphicon-list-alt"></span> %s</h3><div class="current-table-schema"><table id="'. $table_name .'" class="table table-bordered">%s%s</table></div>%s';
	list($result, $value) = $this->get_table_comment($table_name);
	if ($result) {
		$title = sprintf(__('%s table (table comment: %s) schema', self::DOMAIN), $table_name, $value);
	} else {
		$title = sprintf(__('%s table schema', self::DOMAIN), $table_name);
	}
	$schm_rows = null;
	$offset = 0;
	$exclude_items_reg = '/^(octet_length|type|primary_key|unsigned)$/i';
	$boolean_items_reg = '/^(not_null|primary_key|unsigned)$/i';
	foreach ($table_schema as $col_name => $types) {
		if ($offset == 0) {
			$schm_index_row = '<thead><tr><th>'. __('Column Name', self::DOMAIN) .'</th>';
			foreach (array_keys($types) as $index_name) {
				if (preg_match($exclude_items_reg, $index_name)) 
					continue;
				$schm_index_row .= '<th class="cell-'. $index_name .'">'. __($index_name, self::DOMAIN) .'</th>';
			}
			$schm_index_row .= '</tr></thead>';
		}
		$schm_rows .= '<tr>';
		$schm_rows .= '<th>'. $col_name .'</th>';
		foreach ($types as $key => $val) {
			if (preg_match($exclude_items_reg, $key)) 
				continue;
			$val = preg_replace('/(,|\s)/', '$1<wbr>', $val);
			if (preg_match($boolean_items_reg, $key)) 
				$val = (bool)$val ? '<span class="label label-info">'. __('true', self::DOMAIN) .'</span>' : '<span class="label label-default">'. __('false', self::DOMAIN) .'</span>';
			if ($key == 'logical_name' && empty($val)) 
				$val = $col_name;
			$schm_rows .= '<td>'. $val .'</td>';
		}
		$schm_rows .= '</tr>';
		$offset++;
	}
	$sc_html = '<div class="current-table-shortcodes"><h4><span class="glyphicon glyphicon-expand"></span> %s</h4><ul>%s</ul></div>';
	$sc_list = '<li><label>'. __('Shortcode to display list view of table data:', self::DOMAIN) .'</label><code class="cdbt-shortcode"> &#91;cdbt-view table="'. $table_name .'"&#93; </code></li>';
	$sc_list .= '<li><label>'. __('Shortcode to display table data entry form:', self::DOMAIN) .'</label><code class="cdbt-shortcode"> &#91;cdbt-entry table="'. $table_name .'"&#93; </code></li>';
	$sc_list .= '<li><label>'. __('Shortcode to display edit view of table data:', self::DOMAIN) .'</label><code class="cdbt-shortcode"> &#91;cdbt-edit table="'. $table_name .'" entry_page="entry-page[*]"&#93; </code><p class="text-info"><span class="glyphicon glyphicon-exclamation-sign"></span> '. __('The value of entry_page attribute should be post-id or post-name of entry page.', self::DOMAIN) .'</p></li>';
	$shortcodes = sprintf($sc_html, __('Available shortcodes', self::DOMAIN), $sc_list);
	printf($schm_html, $title, $schm_index_row, '<tbody>'. $schm_rows . '</tbody>', $shortcodes);
	
} else {
	create_console_menu($_cdbt_token);
?>
	<div class="alert alert-info"><?php _e('The enabled tables is not exists currently.<br />Please create tables.', self::DOMAIN); ?></div>
<?php
}

create_console_footer();
