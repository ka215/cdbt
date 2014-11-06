<?php
// general tab display setting
$checkbox_attr = checked($cdbt_options['use_wp_prefix'], true, false);
$change_charset = false;
$charset_disabled = disabled($change_charset, false, false);
$change_timezone = false;
$timezone_disabled = disabled($change_timezone, false, false);
$cleaning_options = checked($cdbt_options['cleaning_options'], true, false);
$uninstall_options = checked($cdbt_options['uninstall_options'], true, false);
$resume_options = checked($cdbt_options['resume_options'], true, false);

// inherit values
//var_dump($inherit_values);

// translate text
$tab_name_label = cdbt_translate_tab_name($tab_name);
$submit_label = __('Save changes', CDBT_PLUGIN_SLUG);
$helper_msg = __('If you will create the new table, in default table name is used the table-prefix of WordPress&apos;s config.<br />However, when create table you can change this setting.', CDBT_PLUGIN_SLUG);
$charset_label = __('Table Charset', CDBT_PLUGIN_SLUG);
$charset_placeholder = __('Table Charset', CDBT_PLUGIN_SLUG);
$timezone_label = __('Database Timezone', CDBT_PLUGIN_SLUG);
$timezone_placeholder = __('Database Timezone', CDBT_PLUGIN_SLUG);
$api_key_label = __('API keys', CDBT_PLUGIN_SLUG);
$api_key_placeholder = __('Enter the request host address', CDBT_PLUGIN_SLUG);
$generate_api_key = __('Generate API key', CDBT_PLUGIN_SLUG);
$helper_msg2 = __('Can clean the setting by delete the setting of the table that does not exist in database when save this general setting.', CDBT_PLUGIN_SLUG);
$helper_msg3 = __('To erase all the configuration information for the CDBT plugin when you want to uninstall this plugin.', CDBT_PLUGIN_SLUG);
$helper_msg4 = __('Want to resume the management tables from in the past plugin settings. However, tables that do not currently exist will not be restored.', CDBT_PLUGIN_SLUG);
$helper_msg5 = __('Here is able to issue an API key for each server host (IP address or DNS name) as the request source. Then would allow you to access to the managable tables in this plugin from different hosts to this WordPress site by utilizing the API key.', CDBT_PLUGIN_SLUG);
//$helper_msg6 = sprintf(__('The destination URL of the API requests will be <code>%s/&lt;API key&gt;/&lt;table name&gt;/&lt;request method name&gt;?&lt;Parameter name&gt;=&lt;parameter value&gt;&amp;...</code>.', CDBT_PLUGIN_SLUG), get_option('siteurl'));
$api_url_format = sprintf('<code>%s/?cdbt_api_key=&lt;%s&gt;&amp;cdbt_table=&lt;%s&gt;&amp;cdbt_api_request=&lt;%s&gt;&amp;&lt;%s&gt;=&lt;%s&gt;&amp;...</code>', get_option('siteurl'), __('API key', CDBT_PLUGIN_SLUG), __('Table name', CDBT_PLUGIN_SLUG), __('Request method name', CDBT_PLUGIN_SLUG), __('Parameter name', CDBT_PLUGIN_SLUG), __('Parameter value', CDBT_PLUGIN_SLUG));
$api_url_example = sprintf('<code>%s/?cdbt_api_key=&lt;%s&gt;&amp;cdbt_table=sample_table&amp;cdbt_api_request=get_data&amp;order={created:desc}&amp;limit=5</code>', get_option('siteurl'), __('API key', CDBT_PLUGIN_SLUG));
$helper_msg6 = sprintf(__('The destination URL of the API requests will be : <br>%s.', CDBT_PLUGIN_SLUG), $api_url_format);
$helper_msg7 = sprintf(__('For example, if you want to get recently 5 data from the table of "sample_table" : <br>%s', CDBT_PLUGIN_SLUG), $api_url_example);

if (isset($cdbt_options['api_key']) && !empty($cdbt_options['api_key']) && is_array($cdbt_options['api_key']) && count($cdbt_options['api_key']) > 0) {
	$table_header = sprintf('<thead><tr><th>%s</th><th>%s</th><th>%s</th></tr></thead>', __('Request host address', CDBT_PLUGIN_SLUG), __('API key', CDBT_PLUGIN_SLUG), __('Delete', CDBT_PLUGIN_SLUG));
	$table_body = '';
	$index_num = 1;
	foreach ($cdbt_options['api_key'] as $host_addr => $api_key_string) {
		$table_row = sprintf('<td data-index-id="%d">%s</td>', $index_num, $host_addr);
		$table_row .= '<td>'. $api_key_string .'</td>';
		$table_row .= sprintf('<td><button type="button" id="delete_api_key_%d" class="btn btn-default btn-sm" data-api-key="%s">%s</button></td>', $index_num, $host_addr, __('Delete', CDBT_PLUGIN_SLUG));
		$table_body .= sprintf('<tr>%s</tr>', $table_row);
		$index_num++;
	}
	$api_key_list = sprintf('<table class="table table-border">%s<tbody id="api_key_list_tbody">%s</tbody></table>', $table_header, $table_body);
} else {
	$api_key_list = sprintf('<p class="alert alert-info" style="margin-top: 1em;">%s</p>', __('Currently valid API key is not exists.', CDBT_PLUGIN_SLUG));
}

$content_html = <<<EOH
<h3><span class="glyphicon glyphicon-wrench"></span> $tab_name_label</h3>
<form method="post" class="form-horizontal" id="cdbt_general_setting" role="form">
	<input type="hidden" name="mode" value="admin">
	<input type="hidden" name="action" value="general">
	<input type="hidden" name="handle" value="save">
	$nonce_field
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_use_wp_prefix" value="1"$checkbox_attr> $helper_msg
					<input type="hidden" name="use_wp_prefix" value="false">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_charset" class="col-sm-2 control-label">$charset_label</label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="table_charset" id="cdbt_table_charset" placeholder="$charset_placeholder" value="{$cdbt_options['charset']}"$charset_disabled>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_timezone" class="col-sm-2 control-label">$timezone_label</label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="timezone" id="cdbt_timezone" placeholder="$timezone_placeholder" value="{$cdbt_options['timezone']}"$timezone_disabled>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_cleaning_options" value="1"$cleaning_options> $helper_msg2
					<input type="hidden" name="cleaning_options" value="true">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_uninstall_options" value="1"$uninstall_options> $helper_msg3
					<input type="hidden" name="uninstall_options" value="false">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<div class="checkbox">
				<label>
					<input type="checkbox" id="cdbt_resume_options" value="1"$resume_options> $helper_msg4
					<input type="hidden" name="resume_options" value="false">
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_api_key" class="col-sm-2 control-label">$api_key_label</label>
		<div class="col-sm-9">
			<p>$helper_msg5</p>
		</div>
		<div class="col-sm-offset-2 col-sm-4">
			<input type="text" class="form-control" name="api_key" id="cdbt_api_key" placeholder="$api_key_placeholder" value="">
		</div>
		<div class="col-sm-2">
			<button type="button" id="cdbt_generate_api_key" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span> $generate_api_key</button>
		</div>
		<div class="col-sm-offset-2 col-sm-7">
			$api_key_list
		</div>
		<div class="col-sm-offset-2 col-sm-9">
			<p class="help-block">
				<p class="text-info"><span class="glyphicon glyphicon-exclamation-sign"></span> $helper_msg6</p>
				<p class="text-info"><span class="glyphicon glyphicon-exclamation-sign"></span> $helper_msg7</p>
			</p>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<button type="button" id="cdbt_general_setting_save" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span> $submit_label</button>
		</div>
	</div>
</form>
EOH;
