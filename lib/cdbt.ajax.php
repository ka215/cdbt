<?php
class CustomDataBaseTables_Ajax {
	
	private static $instance;
	
	public static function instance() {
		if (isset(self::$instance)) 
			return self::$instance;
		
		self::$instance = new CustomDataBaseTables_Ajax;
		self::$instance->init();
		return self::$instance;
	}
	
	private function __costruct() {
		// Do nothing
	}
	
	protected function init() {
		add_action('wp_ajax_cdbt_ajax_core', array(&$this, 'cdbt_ajax_core'));
	}
	
	public function cdbt_ajax_core() {
		global $cdbt;
		$token = $_POST['token'];
		if (!wp_verify_nonce($token, PLUGIN_SLUG . '_ajax')) {
			die(__('Invalid access!', PLUGIN_SLUG));
		} else {
			$mode = $_POST['mode'];
			switch($mode) {
				case 'download': 
					$id = $_POST['id'];
					$table = (isset($_POST['table']) && !empty($_POST['table'])) ? $_POST['table'] : $cdbt->current_table;
					$data = $cdbt->get_data($table, '*', array('ID' => $id), null, 1);
					$binary_files = array();
					if (is_array($data) && !empty($data)) {
						$data = array_shift($data);
						foreach ($data as $key => $val) {
							if (is_string($val) && strlen($val) > 24 && preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($val, 0, 24))) {
								$binary_files[] = array_merge(array('ID' => $id), unserialize($val));
							}
						}
					}
					if (!empty($binary_files)) {
						$response = '';
						$response .= '<ul class="download-files">';
						foreach ($binary_files as $binary_file) {
							$url = esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))) . '?action=cdbt_media&id='. $binary_file['ID'] .'&filename='. $binary_file['origin_file'] .'&token='. wp_create_nonce(PLUGIN_SLUG . '_download');
							$response .= sprintf('<li><a href="%s">%s</a></li>', $url, rawurldecode($binary_file['origin_file']));
						}
						$response .= '</ul>';
						die( $response );
					} else {
						die(__('No binary data.', PLUGIN_SLUG));
					}
					break;
				case 'get_table_list': 
					$response = '';
					foreach ($cdbt->get_table_list('unmanageable') as $table_name) {
						$response .= sprintf('<option value="%s">%s</option>'."\n", $table_name, $table_name);
					}
					die($response);
					break;
				case 'load_preset': 
					$preset_id = $_POST['preset_id'];
					$preset_template = $_POST['preset_template'];
					
					
					#$response = $preset_id . ' : ' . $preset_template;
					
					$response = $this->create_preset_form($preset_id, $preset_template);
					#var_dump($response);
					die($response);
					break;
				default: 
					die(__('Invalid access!', PLUGIN_SLUG));
					break;
			}
		}
	}
	
	public function create_preset_form($preset_id, $preset_template) {
		$define_template = array(
			'column_definition' => array(
				'label' => __('Column definition', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the column definition.', PLUGIN_SLUG), 
			),
			'position' => array(
				'label' => __('Column position', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the FIRST or AFTER column name.', PLUGIN_SLUG), 
			),
			'index_or_key' => array(
				'label' => __('Index or Key', PLUGIN_SLUG), 
				'form_type' => 'select', 
				'form_elm' => '<select %s><option value="INDEX">INDEX</option><option value="KEY">KEY</option><option value="PRIMARY KEY">PRIMARY KEY</option><option value="UNIQUE">UNIQUE</option><option value="FULLTEXT">FULLTEXT</option><option value="SPATIAL">SPATIAL</option></select>', 
				'placeholder' => '', 
			),
			'index_name' => array(
				'label' => __('Index name', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the index name.', PLUGIN_SLUG), 
			),
			'index_col_name' => array(
				'label' => __('Index column name', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the index column name.', PLUGIN_SLUG), 
			),
			'reference_definition' => array(
				'label' => __('Reference definition', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the reference definition.', PLUGIN_SLUG), 
			),
			'col_name' => array(
				'label' => __('Column name', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the column name.', PLUGIN_SLUG), 
			),
			'default_definition' => array(
				'label' => __('Default definition', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the default definition.', PLUGIN_SLUG), 
			),
			'old_col_name' => array(
				'label' => __('Old column name', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the old column name.', PLUGIN_SLUG), 
			),
			'column_or_keys' => array(
				'label' => __('Column name or Index name or Key name', PLUGIN_SLUG), 
				'form_type' => 'text', 
				'form_elm' => '<input type="text" %s>', 
				'placeholder' => __('Enter the column name or key name or index name.', PLUGIN_SLUG), 
			),
			'switch_definition' => array(
				'label' => __('Enable or Disable', PLUGIN_SLUG), 
				'form_type' => 'select', 
				'form_elm' => '<select %s><option value="DISABLE">DISABLE</option><option value="ENABLE">ENABLE</option></select>', 
				'placeholder' => '', 
			),
		);
		if (preg_match_all('/\{.*\}/iU', $preset_template, $matches)) {
			if (is_array($matches) && !empty($matches)) {
				$template_key_bases = array_values(array_shift($matches));
				$preset_form = '<form method="post" id="cdbt_modify_table_preset_'. $preset_id .'" role="form">';
				foreach ($template_key_bases as $key_base) {
					$key_string = trim($key_base, '{}');
					$one_form_data = $define_template[$key_string];
					$form_set = '<div class="form-group">';
					$form_set .= sprintf('<label for="cdbt_%s" class="control-label">%s</label>', $key_string, $one_form_data['label']);
					$attributes = 'name="'. $key_string .'" id="cdbt_'. $key_string .'" class="form-control"';
					$attributes .=  $one_form_data['form_type'] == 'text' ? ' placeholder="'. $one_form_data['placeholder'] .'"' : '';
					$form_set .= sprintf($one_form_data['form_elm'], $attributes);
					$form_set .= '</div>';
					$preset_form .= $form_set;
				}
				$preset_form .= '<div class="form-group pull-right"><button type="button" id="set_preset_sql_'. $preset_id .'" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-plus"></span> '. __('Set Preset', PLUGIN_SLUG) .'</button></div>';
				$preset_form .= '</form>';
				return $preset_form;
			} else {
				return false;
			}
		}
	}
	
}
