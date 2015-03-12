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
	cdbt_create_console_menu($_cdbt_token);
	
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
	$sc_store = array(
		'cdbt-view' => array(
			'introduce' => __('Shortcode to display list view of table data:', self::DOMAIN), 
			'helper' => '', 
			'table' => '{$table_name}', 
			'bootstrap_style' => 'true', 
			'display_title' => 'true', 
			'display_search' => 'true', 
			'display_list_num' => 'true', 
			'enable_sort' => 'true', 
			'exclude_cols' => 'column_name1,column_name2,...', 
			'add_class' => '', 
		), 
		'cdbt-entry' => array(
			'introduce' => __('Shortcode to display table data entry form:', self::DOMAIN), 
			'helper' => '', 
			'table' => '{$table_name}', 
			'bootstrap_style' => 'true', 
			'display_title' => 'true', 
			'hidden_cols' => 'column_name1,column_name2,...', 
			'add_class' => '', 
		), 
		'cdbt-edit' => array(
			'introduce' => __('Shortcode to display edit view of table data:', self::DOMAIN), 
			'helper' => __('The value of entry_page attribute should be post-id or post-name of entry page. But not required from version 1.1.14.', self::DOMAIN), 
			'table' => '{$table_name}', 
			'entry_page' => '<span class="glyphicon glyphicon-exclamation-sign"></span>', 
			'bootstrap_style' => 'true', 
			'display_title' => 'true', 
			'display_list_num' => 'true', 
			'enable_sort' => 'true', 
			'exclude_cols' => 'column_name1,column_name2,...', 
			'add_class' => '', 
		), 
		'cdbt-extract' => array(
			'introduce' => __('Shortcode for outputting a list of user-defined arbitrary display format:', self::DOMAIN), 
			'helper' => __('The image_render attribute is class name for directly image render. follow as enable class of bootstrap: "rounded", "circle", "thumbnail", "responsive"', self::DOMAIN), 
			'table' => '{$table_name}', 
			'bootstrap_style' => 'true', 
			'display_index_row' => 'true', 
			'narrow_keyword' => 'column_name1:keyword1,column_name2:keyword2,...', 
			'display_cols' => 'column_name1,column_name2,...', 
			'order_cols' => 'column_name3,column_name2,...', 
			'sort_order' => 'column_name1:desc,column_name2:asc,...', 
			'limit_items' => '5', 
			'image_render' => 'responsive', 
			'add_class' => '', 
		), 
		'cdbt-submit' => array(
			'introduce' => __('Shortcode to be able to submit custom insert and update queries from any where:', self::DOMAIN), 
			'helper' => __('Specified your sql query will be stored to database of WordPress and it is never render on the web front-end.', self::DOMAIN), 
			'table' => '{$table_name}', 
			'query' => "{UPDATE @ SET column_name1 = 'xxx' WHERE column_name2 = 'yyy' AND column_name3 > 100}", 
			'type' => 'button', 
			'label' => 'Submit', 
			'onclick' => 'my-custom-js-function-onclick', 
			'callback' => 'my-custom-js-function-complete', 
			'final' => 'my-custom-js-function-after-process', 
			'add_class' => '', 
		), 
	);
	$sc_list = '';
	foreach ($sc_store as $sc_name => $sc_attr) {
		$helper_tag = !empty($sc_attr['helper']) ? '<p class="text-info"><span class="glyphicon glyphicon-exclamation-sign"></span> '. $sc_attr['helper'] .'</p>' : '';
		$require_atts = $optional_atts = array();
		foreach ($sc_attr as $attr_name => $attr_value) {
			if (!in_array($attr_name, array('introduce', 'helper'))) {
				if (preg_match('/^\{(.*)\}$/iU', $attr_value, $matches) && array_key_exists(1, $matches)) {
					$value_string = preg_match('/^\$/', $matches[1]) ? ${trim($matches[1], '$')} : $matches[1];
					$require_atts[] = sprintf("%s=\"%s\"", $attr_name, $value_string);
				} else {
					$optional_atts[] = sprintf("%s=\"%s\"", $attr_name, $attr_value);
				}
			}
		}
		$sc_require_attr = implode(' ', $require_atts);
		$sc_optional_attr = implode(' ', $optional_atts);
		$shortest_code = sprintf('<code id="%s-short" class="cdbt-shortcode shortest-code"> &#91;%s %s&#93; </code>', $sc_name, $sc_name, $sc_require_attr);
		$full_code = sprintf('<code id="%s-full" class="cdbt-shortcode full-code"> &#91;%s %s %s&#93; </code>', $sc_name, $sc_name, $sc_require_attr, $sc_optional_attr);
		$btn_change_code = sprintf('<button sc-id="%s" class="btn btn-default btn-xs btn-change-code" data-toggle-label="%s" data-current="short">%s</button>', $sc_name, __('Shortest', self::DOMAIN), __('Full Code', self::DOMAIN));
		$sc_list .= sprintf('<li><label>%s %s</label>%s%s%s</li>', $sc_attr['introduce'], $btn_change_code, $shortest_code, $full_code, $helper_tag);
	}
	$shortcodes = sprintf($sc_html, __('Available shortcodes', self::DOMAIN), $sc_list);
	
	printf($schm_html, $title, $schm_index_row, '<tbody>'. $schm_rows . '</tbody>', $shortcodes);
	
} else {
	cdbt_create_console_menu($_cdbt_token);
?>
	<div class="alert alert-info">
		<?php _e('The enabled tables is not exists currently.<br />Please create tables.', self::DOMAIN); ?>
		<div class="pull-right"><button type="button" class="btn btn-default btn-sm btn-create-table-first"><?php _e('Create Table Now!', self::DOMAIN); ?></button></div>
	</div>
<?php
}

$note_mes1 = __('Custom DataBase Tables is provided an extensive %sdocumentations%s. It includes Frequently Asked Questions for you to use in plugins and themes, as well as documentation for further details about how to use for programmers.', self::DOMAIN);
$note_mes2 = __('If you wonder how you can help the project, just %sread this%s.', self::DOMAIN);
$note_mes3 = __('Custom DataBase Table is free of charge and is released under the same license as WordPress, the %sGPL%s.', self::DOMAIN);
$note_mes4 = __('You will also find useful information in the %ssupport forum%s. However don&apos;t forget to make a search before posting a new topic.', self::DOMAIN);
$note_mes5 = __('Finally if you like this plugin or if it helps your business, donations to the author are greatly appreciated.', self::DOMAIN);
$note_message = sprintf('<p>'. $note_mes1, '<a href="http://ka2.org/cdbt/documentation/" target="_blank" alt="CDBT Documentations">', '</a>');
$note_message .= sprintf($note_mes2, '<a href="http://ka2.org/cdbt/tutorials/" target="_blank" alt="CDBT Tutorials">', '</a>');
$note_message .= sprintf($note_mes3 .'</p>', '<a href="http://www.gnu.org/licenses/gpl-2.0.html" target="_blank" alt="GPL 2.0">', '</a>');
$note_message .= sprintf('<p class="pull-left">'. $note_mes4, '<a href="http://ka2.org/cdbt-forum/forum/support-forum/" target="_blank" alt="CDBT Support Forum">', '</a>');
$note_message .= $note_mes5 .'</p>';
$btn_alt = __('The safer, easier way to pay online!', self::DOMAIN);
$note_html = '<div class="panel panel-default other-note"><div class="panel-heading"><span class="glyphicon glyphicon-heart" style="color: #f33;"></span> %s</div><div class="panel-body">%s</div></div>';
$note_dont = <<<NOTE
$note_message<div class="pull-left"><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_donations"><input type="hidden" name="business" value="2YZY4HWYSWEWG"><input type="hidden" name="lc" value="en_US"><input type="hidden" name="currency_code" value="USD"><input type="hidden" name="item_name" value="">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - $btn_alt"></form></div>
NOTE;

printf($note_html, __('About Custom DataBase Tables', self::DOMAIN), $note_dont);

cdbt_create_console_footer();
