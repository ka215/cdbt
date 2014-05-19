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

$note_mes1 = __('Custom DataBase Tables is provided an extensive %sdocumentations%s. It includes Frequently Asked Questions for you to use in plugins and themes, as well as documentation for further details about how to use for programmers.', self::DOMAIN);
$note_mes2 = __('If you wonder how you can help the project, just %sread this%s.', self::DOMAIN);
$note_mes3 = __('Custom DataBase Table is free of charge and is released under the same license as WordPress, the %sGPL%s.', self::DOMAIN);
$note_mes4 = __('You will also find useful information in the %ssupport forum%s. However don&apos;t forget to make a search before posting a new topic.', self::DOMAIN);
$note_mes5 = __('Finally if you like this plugin or if it helps your business, donations to the author are greatly appreciated.', self::DOMAIN);
$note_message = sprintf('<p>'. $note_mes1, '<a href="http://cdbt.ka2.org/" target="_blank">', '</a>');
$note_message .= sprintf($note_mes2, '<a href="http://cdbt.ka2.org/" target="_blank">', '</a>');
$note_message .= sprintf($note_mes3 .'</p>', '<a href="http://www.gnu.org/licenses/gpl-2.0.html" target="_blank">', '</a>');
$note_message .= sprintf('<p class="pull-left">'. $note_mes4, '<a href="http://cdbt.ka2.org/" target="_blank">', '</a>');
$note_message .= $note_mes5 .'</p>';
$btn_alt = __('The safer, easier way to pay online!', self::DOMAIN);
$note_html = '<div class="panel panel-default other-note"><div class="panel-heading"><span class="glyphicon glyphicon-heart" style="color: #f33;"></span> %s</div><div class="panel-body">%s</div></div>';
$note_dont = <<<NOTE
$note_message<div class="pull-left"><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations"><input type="hidden" name="business" value="2YZY4HWYSWEWG"><input type="hidden" name="lc" value="JP"><input type="hidden" name="currency_code" value="USD"><input type="hidden" name="item_name" value="">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - $btn_alt"></form></div>
NOTE;

printf($note_html, __('About Custom DataBase Tables', self::DOMAIN), $note_dont);

create_console_footer();
