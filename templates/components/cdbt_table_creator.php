<?php
/**
 * Table Creator Options array `$this->component_options` scheme
 * [
 * 'id' => @string is element id [optional] For default is `cdbtTableCreatorBody`
 * 'targetTable' => @string [optional] 
 * 'columnDefinitions' => @string [optional] 
 * ]
 */

/**
 * Parse options
 * ---------------------------------------------------------------------------
 */

// `id` section
if (isset($this->component_options['id']) && !empty($this->component_options['id'])) {
  $tcb_id = esc_attr__($this->component_options['id']);
} else {
  $tcb_id = 'cdbtTableCreatorBody';
}

// `targetTable` section
if (isset($this->component_options['targetTable']) && !empty($this->component_options['targetTable'])) {
  $tcb_table = esc_attr__($this->component_options['targetTable']);
} else {
  $tcb_table = '';
}

// `columnDefinition` section
if (isset($this->component_options['columnDefinition']) && !empty($this->component_options['columnDefinition'])) {
  $tcb_columns = esc_attr__($this->component_options['columnDefinition']);
} else {
  $tcb_columns = [];
}

/**
 * Render the Modal
 * ---------------------------------------------------------------------------
 */

$index_row_definitions = [
  'col_order'		=> [ 'label' => '<i class="fa fa-hand-rock-o"></i>', 'placeholder' => '', 'size' => [20, 20] ], 
  'col_name'		=> [ 'label' => __('Column Name', CDBT), 'placeholder' => __('Enter the column name', CDBT), 'size' => [140, 240] ], 
  'type_format'		=> [ 'label' => __('Type Format', CDBT), 'placeholder' => __('Choose or enter the column type', CDBT), 'size' => [128, 150] ], 
  'length'			=> [ 'label' => __('Sizing/Define Values', CDBT), 'placeholder' => __('Edit Define Values', CDBT), 'size' => [190,200] ], 
  'not_null'			=> [ 'label' => __('Not Null', CDBT), 'placeholder' => '', 'size' => [60, 60] ], 
  'default'			=> [ 'label' => __('Default Value', CDBT), 'placeholder' => __('Enter the default value', CDBT), 'size' => [110, 200] ], 
  'attributes'		=> [ 'label' => __('Attributes', CDBT), 'placeholder' => __('Choose or enter the attribute', CDBT), 'size' => [125, 140] ], 
  'auto_increment'	=> [ 'label' => __('Auto Incr.', CDBT), 'placeholder' => '', 'size' => [60, 60] ], 
  'key_index'		=> [ 'label' => __('Key/Index', CDBT), 'placeholder' => __('Choose or enter the key or index', CDBT), 'size' => [140, 150] ], 
  'extra'			=> [ 'label' => __('Extra', CDBT), 'placeholder' => __('Enter the other definitions', CDBT), 'size' => [120, 200] ], 
  'comment'		=> [ 'label' => __('Comment', CDBT), 'placeholder' => __('Enter the comment', CDBT), 'size' => [120, 240] ], 
  'controll'			=> [ 'label' => '', 'placeholder' => '', 'size' => [40, 40] ], 
];

$index_row_base = '<tr class="index-row ui-state-disabled">%s</tr>';
$index_row_cols = [];
foreach ($index_row_definitions as $_col_slug => $_col_attr) {
  $index_row_cols[] = sprintf('<th class="%s" style="min-width: %dpx; max-width: %dpx;">%s</th>', $_col_slug, $_col_attr['size'][0], $_col_attr['size'][1], $_col_attr['label']);
}
$index_row = sprintf($index_row_base, implode("\n", $index_row_cols));

$preset_col_types = $this->get_column_types();
$col_type_options = '';
foreach ($preset_col_types as $_key => $_val) {
  $default_value = is_array($_val['default']) ? implode(',', $_val['default']) : $_val['default'];
  $col_type_options .= sprintf('<li data-value="%s"><a href="#">%s</a></li>', $default_value, $_key);
}

$define_values_definition = [
  'label' => __('Define Values', CDBT), 
  'content' => '', 
];

$preset_attributes = [];
foreach ($preset_col_types as $_key => $_val) {
  if (is_array($_val['atts']) && !empty($_val['atts'])) 
    $preset_attributes = array_merge($preset_attributes, $_val['atts']);
}
$preset_attributes = array_unique($preset_attributes);
$preset_attribute_list = '';
foreach ($preset_attributes as $_attribute) {
  $preset_attribute_list .= sprintf('<li data-value="%s"><a href="#">%s</a></li>', $_attribute, strtoupper($_attribute));
}

$preset_key_indexes = [
  'primary_key' => 'Primary Key', 
  'index' => 'Index', 
  'unique' => 'Unique', 
  'fulltext' => 'Fulltext', 
  'foreign_key' => 'Foreign Key', 
];
$preset_key_index_list = '';
foreach ($preset_key_indexes as $_slug => $_label) {
  $preset_key_index_list .= sprintf('<li data-value="%s"><a href="#">%s</a></li>', $_slug, strtoupper($_label));
}

$controll_buttons = [
  'add_column' => __('Add New Column', CDBT), 
  'delete_column' => __('Remove Column', CDBT), 
];

$body_row_base = '<tr class="ui-state-default tbl_cols preset">%s</tr>';
$body_row_cols = <<<EOH
  <td class="col_order handler">
    <div style="min-width: {$index_row_definitions['col_order']['size'][0]}px; max-width: {$index_row_definitions['col_order']['size'][1]}px;">
      <i class="fa fa-arrows-v"></i>
    </div>
  </td>
  <td class="col_name">
    <div style="min-width: {$index_row_definitions['col_name']['size'][0]}px; max-width: {$index_row_definitions['col_name']['size'][1]}px;">
      <input class="form-control cdbt_tc_col_name" name="col_name_" type="text" placeholder="{$index_row_definitions['col_name']['placeholder']}">
    </div>
  </td>
  <td class="type_format">
    <div class="input-group input-append dropdown combobox cdbt_tc_type_format" data-initialize="combobox" style="min-width: {$index_row_definitions['type_format']['size'][0]}px; max-width: {$index_row_definitions['type_format']['size'][1]}px;">
      <input type="text" class="form-control" name="type_format_" placeholder="{$index_row_definitions['type_format']['placeholder']}">
      <div class="input-group-btn">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li data-value=""><a href="#"></a></li>
          $col_type_options
        </ul>
      </div>
    </div>
  </td>
  <td class="length">
    <div class="input-group cdbt_tc_precision">
      <span class="input-group-addon">(</span>
      <input type="number" class="form-control" name="precision_">
      <span class="input-group-addon">)</span>
    </div>
    <div class="input-group cdbt_tc_precision_scale" style="min-width: {$index_row_definitions['length']['size'][0]}px; max-width: {$index_row_definitions['length']['size'][1]}px;">
      <span class="input-group-addon">(</span>
      <input type="number" class="form-control" name="precision_scale_m_">
      <span class="input-group-addon addon-middle">, </span>
      <input type="number" class="form-control" name="precision_scale_d_">
      <span class="input-group-addon">)</span>
    </div>
    <div class="input-group cdbt_tc_length">
      <span class="input-group-addon">(</span>
      <input type="number" class="form-control" name="length_">
      <span class="input-group-addon">)</span>
    </div>
    <div class="cdbt_tc_define_values">
      <button type="button" class="btn btn-default open_pillbox_" data-placement="bottom" title="{$define_values_definition['label']}" data-content="{$define_values_definition['content']}"><i class="fa fa-edit"></i> {$index_row_definitions['length']['placeholder']}</button>
      <input type="hidden" name="define_values_cache_" value="">
    </div>
  </td>
  <td class="not_null">
    <div class="checkbox cdbt_tc_not_null" style="min-width: {$index_row_definitions['not_null']['size'][0]}px; max-width: {$index_row_definitions['not_null']['size'][1]}px;">
      <label class="checkbox-custom" data-initialize="checkbox">
        <input class="sr-only" type="checkbox" name="not_null_" value="1">
        <span class="checkbox-label"></span>
      </label>
    </div>
  </td>
  <td class="default">
    <div style="min-width: {$index_row_definitions['default']['size'][0]}px; max-width: {$index_row_definitions['default']['size'][1]}px;">
      <input class="form-control cdbt_tc_default" name="default_" type="text" placeholder="{$index_row_definitions['default']['placeholder']}">
    </div>
  </td>
  <td class="attributes">
    <div class="input-group input-append dropdown combobox cdbt_tc_attributes" data-initialize="combobox" style="min-width: {$index_row_definitions['attributes']['size'][0]}px; max-width: {$index_row_definitions['attributes']['size'][1]}px;">
      <input type="text" class="form-control" name="attributes_" placeholder="{$index_row_definitions['attributes']['placeholder']}">
      <div class="input-group-btn">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li data-value=""><a href="#"></a></li>
          $preset_attribute_list
        </ul>
      </div>
    </div>
  </td>
  <td class="auto_increment">
    <div class="checkbox cdbt_tc_auto_increment" style="min-width: {$index_row_definitions['auto_increment']['size'][0]}px; max-width: {$index_row_definitions['auto_increment']['size'][1]}px;">
      <label class="checkbox-custom" data-initialize="checkbox">
        <input class="sr-only" type="checkbox" name="auto_increment_" value="1">
        <span class="checkbox-label"></span>
      </label>
    </div>
  </td>
  <td class="key_index">
    <div class="input-group input-append dropdown combobox cdbt_tc_key_index" data-initialize="combobox" style="min-width: {$index_row_definitions['key_index']['size'][0]}px; max-width: {$index_row_definitions['key_index']['size'][1]}px;">
      <input type="text" class="form-control" name="key_index_" placeholder="{$index_row_definitions['key_index']['placeholder']}">
      <div class="input-group-btn">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li data-value=""><a href="#">None</a></li>
          $preset_key_index_list
        </ul>
      </div>
    </div>
  </td>
  <td class="extra">
    <div style="min-width: {$index_row_definitions['extra']['size'][0]}px; max-width: {$index_row_definitions['extra']['size'][1]}px;">
      <input class="form-control cdbt_tc_extra" name="extra_" type="text" placeholder="{$index_row_definitions['extra']['placeholder']}">
    </div>
  </td>
  <td class="comment">
    <div style="min-width: {$index_row_definitions['comment']['size'][0]}px; max-width: {$index_row_definitions['comment']['size'][1]}px;">
      <input class="form-control cdbt_tc_comment" name="comment_" type="text" placeholder="{$index_row_definitions['comment']['placeholder']}">
    </div>
  </td>
  <td class="controll">
    <div class="cdbt_tc_preset_controll" style="min-width: {$index_row_definitions['controll']['size'][0]}px; max-width: {$index_row_definitions['controll']['size'][1]}px;">
      <button type="button" name="add-column" class="btn btn-primary" title="{$controll_buttons['add_column']}"><i class="fa fa-plus"><span class="sr-only">{$controll_buttons['add_column']}</span></i></button>
      <button type="button" name="delete-column" class="btn btn-default" title="{$controll_buttons['delete_column']}"><i class="fa fa-times"><span class="sr-only">{$controll_buttons['delete_column']}</span></i></button>
    </div>
  </td>
EOH;
$body_row = sprintf($body_row_base, $body_row_cols);

?>
<table id="table-creator-ui" class="table table-striped table-hover">
  <thead>
  <?php echo $index_row; ?>
  </thead>
  <tbody id="sortable">
  <?php echo $body_row; /*$row;*/ ?>
  </tbody>
  <tfoot>
  <?php echo $index_row; ?>
  </tfoot>
</table>
<div id="cdbt_tc_preset_define_values_template">
  <div class="pillbox" data-initialize="pillbox">
    <ul class="clearfix pill-group">
<?php /*
      <li class="btn btn-default pill" data-value="foo">
        <span><?php _e('Item Name', CDBT); ?></span>
        <span class="glyphicon glyphicon-close">
          <span class="sr-only"><?php _e('Remove', CDBT); ?></span>
        </span>
      </li>
*/ ?>
      <li class="pillbox-input-wrap btn-group">
        <a class="pillbox-more"><?php printf(__('and %s more...', CDBT), '<span class="pillbox-more-count"></span>'); ?></a>
        <input type="text" name="define_values_" class="form-control dropdown-toggle pillbox-add-item" placeholder="<?php _e('Add New Value', CDBT); ?>">
        <button type="button" class="dropdown-toggle sr-only">
          <span class="caret"></span>
          <span class="sr-only"><?php _e('Toggle Dropdown', CDBT); ?></span>
        </button>
        <ul class="suggest dropdown-menu" role="menu" data-toggle="dropdown" data-flip="auto"></ul>
      </li>
    </ul>
  </div>
</div>
<script>
  
  // Set to localize scripts
  <?php $json_code = json_encode($preset_col_types); ?>
  cdbt_admin_vars.column_types = <?php echo $json_code; ?>;
  cdbt_admin_vars.cdbt_tc_translate = {
    popoverSetValues: "<?php _e('Set Values', CDBT); ?>", 
  };
  
</script>