<?php
// tables tab display setting
$tab_name_label = translate_tab_name($tab_name);
$target_table = isset($target_table) ? $target_table : '';
$refresh_button_label = __('Reflesh Table List', PLUGIN_SLUG);
$current_table = get_option(PLUGIN_SLUG . '_current_table', $cdbt_options['tables'][0]['table_name']);
if (count($cdbt_options['tables']) > 1) {
	for ($i=1; $i<count($cdbt_options['tables']); $i++) {
		if (!empty($cdbt_options['tables'][$i]['table_name'])) 
			$load_tables[] = $cdbt_options['tables'][$i]['table_name'];
	}
	$index_label = array(
		__('No.', PLUGIN_SLUG), 
		__('Table Name', PLUGIN_SLUG), 
		__('Total Records', PLUGIN_SLUG), 
		__('Data Export', PLUGIN_SLUG), 
		__('Change Table Schema', PLUGIN_SLUG), 
		__('Truncate table', PLUGIN_SLUG), 
		__('Drop table', PLUGIN_SLUG), 
		__('Choise Current table', PLUGIN_SLUG), 
	);
	$thead_th = '';
	foreach ($index_label as $th_text) {
		$thead_th .= '<th>'. $th_text .'</th>';
	}
	$enable_handle = array(
		'data-export' => array('enable' => false, 'label' => __('Data Export', PLUGIN_SLUG)), 
		'alter-table' => array('enable' => false, 'label' => __('Alter table', PLUGIN_SLUG)), 
		'truncate-table' => array('enable' => true, 'label' => __('Truncate table', PLUGIN_SLUG)), 
		'drop-table' => array('enable' => true, 'label' => __('Drop table', PLUGIN_SLUG)), 
		'choise-current-table' => array('enable' => true, 'label' => __('Set Current table', PLUGIN_SLUG)), 
	);
	$table_rows = null;
	if (!empty($load_tables)) {
		$index_num = 1;
		foreach ($load_tables as $load_table_name) {
			if (empty($load_table_name)) 
				continue;
			$cdbt->current_table = $load_table_name;
			if ($cdbt->check_table_exists()) {
				$total = (array)array_shift($cdbt->get_data($load_table_name, 'COUNT(*)'));
				$is_current = ($current_table && $current_table == $load_table_name) ? true : false;
				$table_rows .= '<tr><td>'. $index_num .'</td>';
				$table_rows .= '<td>'. $load_table_name .'</td>';
				$table_rows .= '<td>'. array_shift($total) .'</td>';
				foreach ($enable_handle as $handle_name => $handle_info) {
					$add_attr = (!$handle_info['enable']) ? ' disabled="disabled"' : '';
					$add_class = '';
					if ($handle_name == 'choise-current-table') {
						$add_attr .= ' data-selected-text="'. __('Currently selected', PLUGIN_SLUG). '"';
						if ($is_current) {
							$add_class = ' active';
							$handle_info['label'] = __('Currently selected', PLUGIN_SLUG);
						}
					}
					$table_rows .= '<td><button type="button" class="btn btn-default'. $add_class .'" id="'. $load_table_name .':'. $handle_name .'" data-table="'. $load_table_name .'"'. $add_attr .'>'. $handle_info['label'] .'</button></td>' . "\n";
				}
				$table_rows .= '</tr>';
				$index_num++;
			}
		}
	}
	$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-th-list"></span> $tab_name_label</h3>
<div class="table-responsive">
	<table class="table table-bordered table-striped table-hover">
		<thead>
			<tr>
				$thead_th
			</tr>
		</thead>
		<tbody class="current-exists-tables">
			$table_rows
		</tbody>
	</table>
</div>
<div class="center-block">
	<form method="post" id="cdbt_managed_tables" role="form">
		<input type="hidden" name="mode" value="admin">
		<input type="hidden" name="action" value="tables">
		<input type="hidden" name="handle" value="reflesh">
		<input type="hidden" name="section" value="confirm">
		<input type="hidden" name="target_table" value="$target_table">
		$nonce_field
		<div class="form-group">
			<button type="button" class="btn btn-default pull-right on-bottom-margin" id="reflesh-table-list">$refresh_button_label</button>
		</div>
	</form>
</div>
EOH;
} else {
	$content_html = sprintf('<div class="alert alert-%s tab-header">%s<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>', 'warning', __('The enabled table is none.', PLUGIN_SLUG));
}
