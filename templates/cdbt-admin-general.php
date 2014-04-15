<?php
// general tab display setting
$checkbox_attr = checked($cdbt_options['use_wp_prefix'], true, false);
$change_charset = false;
$charset_disabled = disabled($change_charset, false, false);
$change_timezone = false;
$timezone_disabled = disabled($change_timezone, false, false);

// inherit values
//var_dump($inherit_values);

// translate text
$tab_name_label = translate_tab_name($tab_name);
$submit_label = __('Save changes', PLUGIN_SLUG);
$helper_msg = __('If you will create the new table, in default table name is used the table-prefix of WordPress&apos;s config.<br />However, when create table you can change this setting.', PLUGIN_SLUG);
$charset_label = __('Table Charset', PLUGIN_SLUG);
$charset_placeholder = __('Table Charset', PLUGIN_SLUG);
$timezone_label = __('Database Timezone', PLUGIN_SLUG);
$timezone_placeholder = __('Database Timezone', PLUGIN_SLUG);

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
		<label for="cdbt_table_name" class="col-sm-2 control-label">$charset_label</label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="table_charset" id="cdbt_table_charset" placeholder="$charset_placeholder" value="{$cdbt_options['charset']}"$charset_disabled>
		</div>
	</div>
	<div class="form-group">
		<label for="cdbt_table_name" class="col-sm-2 control-label">$timezone_label</label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="timezone" id="cdbt_timezone" placeholder="$timezone_placeholder" value="{$cdbt_options['timezone']}"$timezone_disabled>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-9">
			<button type="button" id="cdbt_general_setting_save" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span> $submit_label</button>
		</div>
	</div>
</form>
EOH;
