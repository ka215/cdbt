<?php
/**
 * Template : Shortcodes Management Page
 * URL: `/wp-admin/admin.php?page=cdbt_shortcodes`
 *
 * @since 2.0.0
 * @since 2.1.31 Enhanced
 * @since 2.1.33 Updated
 *
 */

/**
 * Define the various localized variables for rendering
 */
$options = get_option($this->domain_name);
$tabs = [
  'shortcode_list' => __('Shortcode Lists', CDBT), 
  'shortcode_register' => __('Shortcode Register', CDBT), 
  'shortcode_edit' => __('Shortcode Edit', CDBT), 
];
$default_tab = 'shortcode_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;

foreach ($this->cdbt_sessions as $_session_key => $_val) {
  if ($current_tab !== $_session_key) 
    $this->destroy_session($_session_key);
}
$label_required = '<span class="label label-required">'. __('Required', CDBT) .'</span>';

$fields_define = [
  'baseName' => [ 'elementName'=>'base_name', 'elementId'=>'base_name', 'elementLabel'=>__('Base Shortcode', CDBT), 'idPrefix'=>'', 'elementType'=>'combobox', 'isRequired'=>true, 'labelSize'=>3, 'fieldSize'=>3, 'defaultValue'=>'', 'selectableList'=>[] ], 
  'targetTable' => [ 'elementName'=>'target_table', 'elementId'=>'target_table', 'elementLabel'=>__('Target Table', CDBT), 'idPrefix'=>'', 'elementType'=>'combobox', 'isRequired'=>true, 'labelSize'=>3, 'fieldSize'=>3, 'defaultValue'=>'', 'selectableList'=>[] ], 
  'entryPage' => [ 'elementName'=>'entry_page', 'elementId'=>'entry_page', 'idPrefix'=>'', 'elementType'=>'hidden', 'defaultValue'=>'', 'addWrapClass'=>'switching-item on-e' ], 
  'appearances' => [ 'elementName' => 'look_feel', 'elementId' => 'look_feel', 'elementLabel' => __('Appearance and LookAndFeel', CDBT), 'idPrefix' => '', 'elementType' => 'checkbox', 'fieldSize' => 10, 
    'defaultValue' => 'bootstrap_style,display_search,display_title,enable_sort,draggable', 
    'selectableList' => [
      'bootstrap_style' => 	__( 'Renders the data via the style of bootstrap if checked. Render the data of the JSON format if unchecked.', CDBT ) . $this->during_trial( 'json_support', false ), 
      'enable_repeater' => 	__( 'Renders the data of table by using repeater component of the "FuelUX" libraries if checked. <wbr/>Or if unchecked, renders by using the original dynamic table component of this plugin.', CDBT ) . $this->during_trial( 'dynamic_table_layout', false ), 
      'display_list_num' => __( 'Adds an auto increment number column at the left edge of the data row if checked.', CDBT ), 
      'display_search' => 	__( 'Displays an input field for the data search if checked.', CDBT ), 
      'display_title' => 	__( 'Displays the heading of content as a title if checked.', CDBT ), 
      'enable_sort' => 		__( 'It will be able to sort of data by clicking on the header row if checked.', CDBT ), 
//    'display_index_row' =>__( 'Displays the index row around the data rows as the header of the data column, if true. Also it&#39;s added of "head-only" for the table format besides boolean value.', CDBT ), 
      'display_filter' => 	__( 'Adds a dropdown list box for filtering the data if checked. Then there should be specified the column to filter if you want to enable this.', CDBT ), 
      'display_view' => 	__( 'You can switch to the thumbnail list view of the gallery format if there contained an image in the table data.', CDBT ), 
      'draggable' => 		__( 'You will be draggable the hiding content area (moreover it performs the cell&#39;s width adjustment in automatically) if the table content overflows from display area when you are using the table layout.', CDBT ) . $this->during_trial( 'draggable_table', false ), 
    ], 'addWrapClass' => 'toggle-group', 'addClass' => 'switching-item', 
    'elementExtras' => [
      'child-class' => 'bootstrap_style:on-v,enable_repeater:on-v on-e,display_list_num:on-v on-e,display_search:on-v on-e,display_title:on-v on-i on-e,enable_sort:on-v on-e,display_index_row:on-v,display_filter:on-v on-e,display_view:on-v,draggable:on-v on-e for-tbl'
    ]
  ], 
  'indexRow' => [ 'elementName'=>'display_index_row', 'elementId'=>'display_index_row', 'elementLabel'=>__('Display Index Row', CDBT), 'idPrefix'=>'', 'elementType'=>'radio', 'horizontalList'=>true, 
    'defaultValue'=>'true', 
    'selectableList'=>[ 'false'=>__('Does not show', CDBT), 'true'=>__('Show all', CDBT), 'head-only'=>__('Show the header only', CDBT) ], 
    'helperText'=>__( 'Displays the index row around the data rows as the header of the data column. Also you can specify the "head-only" if uses the table layout.', CDBT ), 
    'elementExtras' => [
      'child-class' => 'false:for-rpt for-tbl,true:for-rpt for-tbl,head-only:for-tbl', 
      'status' => 'under-test',
    ], 'addWrapClass' => 'switching-item on-v on-e', 
  ], 
  'footerInterface' => [ 'elementName'=>'footer_interface', 'elementId'=>'footer_interface', 'elementLabel'=>__('Footer Interface', CDBT), 'idPrefix'=>'', 'elementType'=>'radio', 'horizontalList'=>true, 
    'defaultValue'=>'pagination', 
    'selectableList'=>[ 'pagination'=>__('Pagination', CDBT), 'pager'=>__('Pager (Conventional)', CDBT) ], 
    'helperText'=>__( 'You can choose either "Pagination" or "Pager (Conventional Interface)", as the paging interface for the table layout.', CDBT ), 
    'elementExtras' => [
      'status' => 'under-test',
    ], 'addWrapClass' => 'switching-item on-v on-e for-tbl', 
  ], 
  'excludeCols' => [ 'elementName'=>'exclude_cols', 'elementId'=>'exclude_cols', 'elementLabel'=>__('Excludes Columns', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'column1,column2,column3,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Specifies the comma-delimited column names if you want to hide the column. e.g. "column1,column2,column3,..."', CDBT) ], 
  'addClass' => [ 'elementName'=>'add_class', 'elementId'=>'add_class', 'elementLabel'=>__('Adds Classes', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'class1 class2 class3 ...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-i on-e', 'helperText'=>__('Specifies a CSS class name for styling the element of listed data table. If there are multiple class, please separated by a single-byte space.', CDBT) ], 
  'narrowKeyword' => [ 'elementName'=>'narrow_keyword', 'elementId'=>'narrow_keyword', 'elementLabel'=>__('Narrow-down Keywords', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'keyword1,keyword2,... OR column1:keyword1,column2:keyword2,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-e', 
    'helperText'=>__('Specifies the narrowing condition of the output data in a comma-delimited. If you specify in the pair of the column name and keyword, it will be narrowed down in the complete matching data. In the meanwhile, it will be narrowed down in the partial matching data if the keyword only. <wbr/>e.g. "keyword1,keyword2,..." or "column1:keyword1,column2:keyword2,..."', CDBT) ], 
  'narrowOperator' => [ 'elementName'=>'narrow_operator', 'elementId'=>'narrow_operator', 'elementLabel'=>__('Narrowing Condition', CDBT), 'idPrefix'=>'', 'elementType'=>'radio', 'horizontalList'=>true, 
    'defaultValue'=>'and', 'selectableList'=>[ 'and'=>__('AND', CDBT), 'or'=>__('OR', CDBT) ], 
    'helperText'=>__( 'Chooses the narrowing conditional operator for joining each keywords if you want to narrow down the data by using a plurality of keywords.', CDBT ), 
    'elementExtras' => [
      'child-class' => 'and:for-multiple,or:for-multiple', 
      'status' => 'under-test',
    ], 'addWrapClass' => 'switching-item on-v on-e', 
   ], 
  'hiddenCols' => [ 'elementName'=>'hidden_cols', 'elementId'=>'hidden_cols', 'elementLabel'=>__('Hides Columns', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>9, 'placeholder'=>'column1,column2,column3,...', 'addWrapClass' => 'switching-item on-i', 'helperText'=>__('Specifies a comma delimited the column names if you want to hide any column. Then the hidden column will be rendered as field of "hidden" type. <wbr/>e.g. "column1,column2,column3,..."', CDBT) ], 
  'displayCols' => [ 'elementName'=>'display_cols', 'elementId'=>'display_cols', 'elementLabel'=>__('Displays Columns', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'column1,column2,column3,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v', 'helperText'=>__('Specifies the comma-delimited column names if you want to show the column. This overrides the value of the option "Excludes columns". e.g. "column1,column2,column3,..."', CDBT) ], 
  'orderCols' => [ 'elementName'=>'order_cols', 'elementId'=>'order_cols', 'elementLabel'=>__('Columns Display Order', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'column3,column1,column2,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Specifies the comma-delimited column names in the display order if you want to display columns in the order of your display request. This overrides the value of the option "Excludes Columns" and "Displays Columns". e.g. "column3,column1,column2,..."', CDBT) ], 
  'sortOrder' => [ 'elementName'=>'sort_order', 'elementId'=>'sort_order', 'elementLabel'=>__('Initial Sort Order', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'created:desc', 'placeholder'=>'updated:desc,ID:asc,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Specifies in the pair of column name and the ascending(asc) or descending(desc) order, for the display order of the initial data. If there are multiple condition, please use the comma-delimited. e.g. "updated:desc,ID:asc,..."', CDBT) ], 
  'limitItems' => [ 'elementName'=>'limit_items', 'elementId'=>'limit_items', 'elementLabel'=>__('Max Rows Per Page', CDBT), 'idPrefix'=>'', 'elementType'=>'spinbox', 'defaultValue'=>'', 'fieldSize'=>3, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('If this attribute is specified, it overrides the "Maximum display data per page" of the table.', CDBT) ], 
  'truncateStr' => [ 'elementName'=>'truncate_strings', 'elementId'=>'truncate_strings', 'elementLabel'=>__('Truncates String', CDBT), 'idPrefix'=>'', 'elementType'=>'spinbox', 'defaultValue'=>40, 'fieldSize'=>3, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Truncates the display data if the strings type data is longer than the specified characters (not bytes). If value is zero it does not truncate.', CDBT) ], 
  'truncateCols' => [ 'elementName'=>'truncate_cols', 'elementId'=>'truncate_cols', 'elementLabel'=>__('Truncate Columns', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>9, 'placeholder'=>'column1,column2,column3,...', 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Specifies the comma-delimited column names if you want to specify the columns truncating the strings. If nothing is specified, it will be performed the strings truncation to all string type columns. <wbr/>e.g. "column1,column2,column3,..."', CDBT), 'elementExtras'=>[ 'status'=>'under-test' ] ], 
  'clickableCols' => [ 'elementName'=>'clickable_cols', 'elementId'=>'clickable_cols', 'elementLabel'=>__('Clickable Columns', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>9, 'placeholder'=>'column1,column2,column3,...', 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Specifies the comma-delimited column names if you want to be able to click the column value. Also in those columns, it should be stored the string as like the url. <wbr/>e.g. "column1,column2,column3,..."', CDBT), 'elementExtras'=>[ 'status'=>'under-test' ] ], 
  'imageRender' => [ 'elementName'=>'image_render', 'elementId'=>'image_render', 'elementLabel'=>__('Thumbnail Image Class', CDBT), 'idPrefix'=>'', 'elementType'=>'combobox', 'fieldSize'=>3, 'defaultValue'=>'responsive', 'selectableList'=>['rounded', 'circle', 'thumbnail', 'responsive'], 'addWrapClass'=>'switching-item on-v on-e for-rpt', 'helperText'=>__('Specifies a CSS class name for styling the thumbnail images. This CSS class will be added to img tag of thumbnail image. It will enable only if renders the repeater layout.', CDBT) ], 
  'submitLabel' => [ 'elementName'=>'submit_button_label', 'elementId'=>'submit_button_label', 'elementLabel'=>__('Labeled Submit Button', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>4, 'addWrapClass' => 'switching-item on-i', 'helperText'=>__('Specifies the label name of button for submitting in the entry form.', CDBT) ], 
  'redirectUrl' => [ 'elementName'=>'redirect_url', 'elementId'=>'redirect_url', 'elementLabel'=>__('Redirect URL', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-i', 'helperText'=>__('Specifies the url to redirect after the time of insertion and the update of the data. If not specified, self page is reloaded.', CDBT) ], 
  'filterCol' => [ 'elementName'=>'filter_column', 'elementId'=>'filter_column', 'elementLabel'=>__('Filtering Column Name', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>4, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies a column name for filtering the data. If you specify a column of "enum" or "set" type, the filtering will be enabled by automatic without a filter definition below.', CDBT) ], 
  'filters' => [ 'elementName'=>'filters', 'elementId'=>'filters', 'elementLabel'=>__('Filter Definition', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'filter-keyword1:display-label1,filter-keyword2:display-label2,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies the keyword lists for filtering the data. Also, a plurality of the pairs of the keyword and the display label can be defined by using the comma-delimited. e.g. "filter-keyword1:display-label1,filter-keyword2:display-label2,..."', CDBT) ], 
  'thumbCol' => [ 'elementName'=>'thumbnail_column', 'elementId'=>'thumbnail_column', 'elementLabel'=>__('Thumbnail Image Column', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>4, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies a column as the thumbnail image. In this column it should be stored the image binary or a URL of image.', CDBT) ], 
  'thumbTitle' => [ 'elementName'=>'thumbnail_title_column', 'elementId'=>'thumbnail_title_column', 'elementLabel'=>__('Thumbnail Title column', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>4, 'addWrapClass' => 'switching-item on-v', 'helperText'=>__('Specifies a column as displayed title on the thumbnail list view. it displays nothing if this is not fill.', CDBT) ], 
  'thumbWidth' => [ 'elementName'=>'thumbnail_width', 'elementId'=>'thumbnail_width', 'elementLabel'=>__('Thumbnail Block Size', CDBT), 'idPrefix'=>'', 'elementType'=>'spinbox', 'defaultValue'=>100, 'fieldSize'=>3, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies a width of the thumbnail image, also the default size of thumbnail will be square equal to this width.', CDBT) ], 
  'ajaxLoad' => [ 'elementName'=>'ajax_load', 'elementId'=>'ajax_load', 'elementLabel'=>__('Adding Ajax Support', CDBT), 'idPrefix'=>'', 'elementType'=>'checkbox', 'defaultValue'=>'ajax_load', 'selectableList'=>[ 'ajax_load'=>__('To Use the Ajax for loading the table data if checked.', CDBT) ], 'addWrapClass'=>'switching-item on-e on-v', 'helperText'=>__('If activated, you can improve performance when dealing with large tables of data size.', CDBT), 'elementExtras'=>[ 'status'=>'new' ] ], 
  'actionUrl' => [ 'elementName'=>'action_url', 'elementId'=>'action_url', 'idPrefix'=>'', 'elementType'=>'hidden', 'defaultValue'=>'', 'noWrap'=>true ], 
  'formAction' => [ 'elementName'=>'form_action', 'elementId'=>'form_action', 'idPrefix'=>'', 'elementType'=>'hidden', 'defaultValue'=>'entry_data', 'noWrap'=>true ], 
  'displaySubmit' => [ 'elementName'=>'display_submit', 'elementId'=>'display_submit', 'idPrefix'=>'', 'elementType'=>'hidden', 'defaultValue'=>'1', 'noWrap'=>true ], 
  'whereClause' => [ 'elementName'=>'where_clause', 'elementId'=>'where_clause', 'idPrefix'=>'', 'elementType'=>'hidden', 'defaultValue'=>'', 'noWrap'=>true ], 
  'description' => [ 'elementName'=>'description', 'elementId'=>'description', 'elementLabel'=>__('Description', CDBT), 'idPrefix'=>'', 'elementType'=>'textarea', 'fieldSize' => 9, 'defaultValue'=>'', 'helperText'=>__('You can specify as like description that will be displayed in the shortcode lists screen.', CDBT) ], 
  'csid' => [ 'elementName'=>'csid', 'elementId'=>'csid', 'idPrefix'=>'', 'elementType'=>'hidden', 'defaultValue'=>0, 'noWrap'=>true ], 
  'author' => [ 'elementName'=>'author', 'elementId'=>'author', 'idPrefix'=>'', 'elementType'=>'hidden', 'defaultValue'=>0, 'noWrap'=>true ], 
  'generateSC' => [ 'elementName'=>'generate_shortcode', 'elementId'=>'generate_shortcode', 'elementLabel'=>__('Generated Shortcode', CDBT), 'idPrefix'=>'', 'elementType'=>'textarea', 'fieldSize' => 9, 'defaultValue'=>'', 'placeholder'=>__('No generated shortcode yet.', CDBT), 'addClass'=>'cdbt-clipboard', 'elementExtras'=>[ 'rows'=>4, 'readonly'=>'readonly' ] ], 
  'aliasCode' => [ 'elementName'=>'alias_code', 'elementId'=>'alias_code', 'elementLabel'=>__('Alias Shortcode', CDBT), 'idPrefix'=>'', 'elementType'=>'text', 'fieldSize' => 9, 'defaultValue'=>'', 'placeholder'=>__('No alias shortcode yet.', CDBT), 'addClass'=>'cdbt-clipboard', 'helperText'=>__('When you click the code field you can copy the contents to the clipboard.', CDBT), 'elementExtras'=>[ 'readonly'=>'readonly' ] ], 
];
/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div id="page-head" name="page-head" class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php _e('CDBT Shortcodes Management', CDBT); ?></h2>
  
  <div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
      <li role="presentation"<?php if ($current_tab == $tab_name) : ?> class="active"<?php endif; ?>><a href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>" role="tab"><?php echo $display_tab_title; ?></a></li>
    <?php endforeach; ?>
    </ul>
  </div>
  
<?php if ($current_tab == 'shortcode_list') : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('Those shortcode lists are managed in this plugin now. If you will create a newly shortcode, you can use very conveniently by registering to this plugin.', CDBT); ?> <?php $this->during_trial( 'shortcode_list' ); ?>
    </p>
  </div>

  <?php 
  /**
   * Define the localized variables for tab of `shortcode_list`
   */
  
  $datasource = [];
  if (is_array($shortcodes = $this->get_shortcode_list()) && !empty($shortcodes)) {
    $_index = 1;
    foreach ($shortcodes as $shortcode_name => $attributes) {
      $datasource[] = [
        'cdbt_index_id' => $_index, 
        'shortcode_id' => empty($attributes['alias_id']) ? '-' : $attributes['alias_id'], 
        'shortcode_name' => $shortcode_name, 
        'description' => $attributes['description'], 
        'shortcode_type' => $attributes['type'], 
        'shortcode_author' => 0 === $attributes['author'] ? '-' : get_the_author_meta('display_name', $attributes['author']), 
        'permission' => $attributes['permission'], 
        'operate_shortcode_url' => './' . basename( esc_url(admin_url(add_query_arg([ 'tab'=>'shortcode_' ]))) ), 
      ];
    }
  }
  $conponent_options = $this->create_scheme_datasource( 'cdbtShortcodes', 0, 10, 'shortcode_list', $datasource );
  $this->component_render('repeater', $conponent_options); // by trait `DynamicTemplate`
  
  ?>
<?php endif; ?>
  
<?php if ($current_tab == 'shortcode_register') : 
  /**
   * Define the localized variables for tab of `shortcode_register`
   */
  
  $session_vars = isset($this->cdbt_sessions[$current_tab]) ? $this->cdbt_sessions[$current_tab] : [];
  $this_tab_vars = array_key_exists($this->domain_name, $session_vars) ? $session_vars[$this->domain_name] : [];
  if (!isset($this_tab_vars) || empty($this_tab_vars)) {
    // Set default values
    $this_tab_vars = [
      'bootstrap_style' => true, 
      'display_list_num' => false, 
      'display_search' => true, 
      'display_title' => true, 
      'enable_sort' => true, 
      'display_index_row' => true, 
      'enable_repeater' => true, 
      'ajax_load' => true, 
    ];
  }
  
  $base_shortcode = '';
  if (isset($session_vars) && !empty($session_vars)) {
    if (array_key_exists('target_shortcode', $session_vars)) 
      $base_shortcode = $session_vars['target_shortcode'];
    if (array_key_exists('base_name', $this_tab_vars)) 
      $base_shortcode = $this_tab_vars['base_name'];
  }
  
  $_shortcodes = $this->get_shortcode_list('built-in');
  $_tables = $this->get_table_list();
  
  $current_csid = $this->get_increment_unique_csid();
  
  $user_ID = get_current_user_id();
  
  $id_prefix = 'register-shortcode-';
  
  foreach ( $fields_define as $__key => $__val ) {
    $__val['idPrefix'] = $id_prefix;
    if ( 'baseName' === $__key ) {
      $__val['defaultValue'] = $base_shortcode;
      $__val['selectableList'] = array_keys( $_shortcodes );
    } else
    if ( 'targetTable' === $__key ) {
      $__val['defaultValue'] = isset( $this_tab_vars['target_table'] ) ? $this_tab_vars['target_table'] : '';
      $__val['selectableList'] = $_tables;
    } else
    if ( 'actionUrl' === $__key ) {
      $__val['defaultValue'] = isset( $this_tab_vars['action_url'] ) ? $this_tab_vars['action_url'] : '';
    } else
    if ( 'formAction' === $__key ) {
      $__val['defaultValue'] = isset( $this_tab_vars['form_action'] ) ? $this_tab_vars['form_action'] : 'entry_data';
    } else
    if ( 'whereClause' === $__key ) {
      $__val['defaultValue'] = isset( $this_tab_vars['where_clause'] ) ? $this_tab_vars['where_clause'] : '';
    } else
    if ( 'csid' === $__key ) {
      $__val['defaultValue'] = $current_csid;
    } else
    if ( 'author' === $__key ) {
      $__val['defaultValue'] = $user_ID;
    }
    $fields_define[$__key] = $__val;
  }
?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('In this section you can create a new custom shortcode. Please enter the following your necessary items.', CDBT); ?> <?php $this->during_trial( 'shortcode_register' ); ?>
    </p>
  </div>
  
  <div class="cdbt-register-shortcode">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="register_shortcode">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
<?php $this->dynamic_field( $fields_define['baseName'] ); ?>
<?php $this->dynamic_field( $fields_define['targetTable'] ); ?>
      
      <div class="form-group">
        <div class="col-sm-12" id="columns-information" style="padding: 0 2em;">
        </div>
      </div><!-- /colmuns-information -->
      
      <div class="clearfix"></div>
      <h4 class="title" id="advanced-settings"><i class="fa fa-cogs text-muted"></i> <?php _e('Advanced Shortcode Settings', CDBT); ?></h4>
      
      <div class="overflow-block" style="margin: 0; padding: 1em 0; overflow-y: scroll; overflow-x: hidden; height: 400px;">
      
<?php $this->dynamic_field( $fields_define['entryPage'] ); ?>
      
<?php 
  $_field_order = [ 
    'appearances', 'indexRow', 'footerInterface', 'narrowKeyword', 'narrowOperator', 'excludeCols', 'hiddenCols', 'displayCols', 'orderCols', 'sortOrder', 'limitItems', 'truncateStr', 
    'truncateCols', 'clickableCols', 'filterCol', 'filters', 'thumbCol', 'thumbTitle', 'thumbWidth', 'imageRender', 'submitLabel', 'redirectUrl', 'addClass', 'ajaxLoad', 
  ];
  foreach ( $_field_order as $_df_key ) {
    $this->dynamic_field( $fields_define[$_df_key] );
  }
?>
      
      <div class="form-group switching-item on-i">
<?php foreach ( [ 'actionUrl', 'formAction', 'displaySubmit', 'whereClause' ] as $_df_key ) { $this->dynamic_field( $fields_define[$_df_key] ); } ?>
      </div>
      
<?php $this->dynamic_field( $fields_define['description'] ); ?>
      
      <div class="form-group switching-item on-v on-i on-e">
<?php $this->dynamic_field( $fields_define['csid'] ); ?>
<?php $this->dynamic_field( $fields_define['author'] ); ?>
      </div>
      
      </div><!-- /.overflow-block -->
      
      <div class="clearfix"></div>
      <h4 class="title" id="confirm-shortcode"><i class="fa fa-eye text-muted"></i> <?php _e('Confirm the Generated Shortcode', CDBT); ?></h4>
      
<?php $this->dynamic_field( $fields_define['generateSC'] ); ?>
<?php $this->dynamic_field( $fields_define['aliasCode'] ); ?>
      
      <div class="clearfix"><br></div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary"><?php _e('Save Shortcode', CDBT); ?></button>
          <button type="button" class="btn btn-default" id="register-shortcode-preview"><?php _e('Preview Shortcode', CDBT); ?></button>
        </div>
      </div>
      
      <div class="pull-right">
        <a href="#"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
    </form>
  </div><!-- /.cdbt-register-shortcode -->
<?php endif; ?>
  
<?php if ($current_tab == 'shortcode_edit') : 
  /**
   * Define the localized variables for tab of `shortcode_edit`
   */
  
  $session_vars = isset($this->cdbt_sessions[$current_tab]) ? $this->cdbt_sessions[$current_tab] : [];
  if (array_key_exists($this->domain_name, $session_vars)) {
    $this_tab_vars = $session_vars[$this->domain_name];
  } else
  if (array_key_exists('target_scid', $session_vars)) {
    $this_tab_vars = $this->get_shortcode_option($session_vars['target_scid']);
  } else {
    $_wall_message = sprintf(__('Please choose which a custom shortcode you want to edit from %sthe shortcode lists%s.', CDBT), '<a href="'. add_query_arg('tab', 'shortcode_list') .'">', '</a>');
  }
  if (!isset($_wall_message) && !in_array(get_current_user_id(), [ 1, $this_tab_vars['author'] ])) {
    $_wall_message = __('The custom shortcode cannot edit other than the registrant or privilege administrator.', CDBT);
  }
  
  if ( ! isset( $_wall_message ) && isset( $this_tab_vars ) ) {
    $base_shortcode = '';
    if ( isset( $session_vars ) && ! empty( $session_vars ) ) {
      if ( array_key_exists( 'base_name', $this_tab_vars ) ) 
        $base_shortcode = $this_tab_vars['base_name'];
    }
    
    $_tables = $this->get_table_list();
    
    $current_csid = $this_tab_vars['csid'];
    
    $user_ID = $this_tab_vars['author'];
    
    $id_prefix = 'edit-shortcode-';
    
    foreach ( $fields_define as $__key => $__val ) {
      $__val['idPrefix'] = $id_prefix;
      if ( 'baseName' === $__key ) {
        $__val = array_merge( $__val, [ 'elementType'=>'text', 'isRequired'=>false, 'fieldSize'=>2, 'defaultValue'=>$base_shortcode, 'addClass'=>'text-center', 'elementExtras'=>[ 'readonly'=>'readonly' ] ] );
      } else
      if ( 'targetTable' === $__key ) {
        $__val['defaultValue'] = isset( $this_tab_vars['target_table'] ) ? $this_tab_vars['target_table'] : '';
        $__val['selectableList'] = $_tables;
      } else
      if ( 'appearances' === $__key ) {
        $_defval = [];
        foreach ( $__val['selectableList'] as $_sl_key => $__v ) {
          if ( array_key_exists( $_sl_key, $this_tab_vars ) && $this_tab_vars[$_sl_key] ) {
            $_defval[] = $_sl_key;
          }
        }
        $__val['defaultValue'] = implode( ',', $_defval );
      } else
      if ( 'indexRow' === $__key || 'narrowOperator' === $__key || 'footerInterface' === $__key ) {
        if ( array_key_exists( $__val['elementName'], $this_tab_vars ) && ! empty( $this_tab_vars[$__val['elementName']] ) ) {
          $__val['defaultValue'] = $this_tab_vars[$__val['elementName']];
        }
      } else
      if ( in_array( $__val['elementType'], [ 'text', 'textarea', 'spinbox' ] ) ) {
        if ( array_key_exists( $__val['elementName'], $this_tab_vars ) ) {
          $__val['defaultValue'] = stripslashes_deep( $this_tab_vars[$__val['elementName']]) ;
        }
      } else
      if ( 'actionUrl' === $__key ) {
        $__val['defaultValue'] = isset( $this_tab_vars['action_url'] ) ? $this_tab_vars['action_url'] : '';
      } else
      if ( 'formAction' === $__key ) {
        $__val['defaultValue'] = isset( $this_tab_vars['form_action'] ) ? $this_tab_vars['form_action'] : 'entry_data';
      } else
      if ( 'whereClause' === $__key ) {
        $__val['defaultValue'] = isset( $this_tab_vars['where_clause'] ) ? $this_tab_vars['where_clause'] : '';
      } else
      if ( 'ajaxLoad' === $__key ) {
        $__val['defaultValue'] = isset( $this_tab_vars['ajax_load'] ) && $this_tab_vars['ajax_load'] ? key( $__val['selectableList'] ) : '';
      } else
      if ( 'csid' === $__key ) {
        $__val['defaultValue'] = $current_csid;
      } else
      if ( 'author' === $__key ) {
        $__val['defaultValue'] = $user_ID;
      }
      $fields_define[$__key] = $__val;
    }
?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('In this section you can edit an existing custom shortcode. Please modify the following your necessary items.', CDBT); ?> <?php $this->during_trial( 'shortcode_edit' ); ?>
    </p>
  </div>
  
  <div class="cdbt-edit-shortcode">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="edit_shortcode">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
<?php $this->dynamic_field( $fields_define['baseName'] ); ?>
<?php $this->dynamic_field( $fields_define['targetTable'] ); ?>
	  
      <div class="form-group">
        <div class="col-sm-12" id="columns-information" style="padding: 0 2em;">
        </div>
      </div><!-- /colmuns-information -->
      
      <div class="clearfix"><br></div>
      <h4 class="title" id="advanced-settings"><i class="fa fa-cogs text-muted"></i> <?php _e('Advanced Shortcode Settings', CDBT); ?></h4>
      
      <div class="overflow-block" style="margin: 0; padding: 1em 0; overflow-y: scroll; overflow-x: hidden; height: 400px;">
      
<?php $this->dynamic_field( $fields_define['entryPage'] ); ?>
      
<?php 
  $_field_order = [ 
    'appearances', 'indexRow', 'footerInterface', 'narrowKeyword', 'narrowOperator', 'excludeCols', 'hiddenCols', 'displayCols', 'orderCols', 'sortOrder', 'limitItems', 'truncateStr', 
    'truncateCols', 'clickableCols', 'filterCol', 'filters', 'thumbCol', 'thumbTitle', 'thumbWidth', 'imageRender', 'submitLabel', 'redirectUrl', 'addClass', 'ajaxLoad', 
  ];
  foreach ( $_field_order as $_df_key ) {
    $this->dynamic_field( $fields_define[$_df_key] );
  }
?>
      
      <div class="form-group switching-item on-i">
<?php foreach ( [ 'actionUrl', 'formAction', 'displaySubmit', 'whereClause' ] as $_df_key ) { $this->dynamic_field( $fields_define[$_df_key] ); } ?>
      </div>
      
<?php $this->dynamic_field( $fields_define['description'] ); ?>
      
      <div class="form-group switching-item on-v on-i on-e">
<?php $this->dynamic_field( $fields_define['csid'] ); ?>
<?php $this->dynamic_field( $fields_define['author'] ); ?>
      </div>
      
      </div><!-- /.overflow-block -->
      
      <div class="clearfix"></div>
      <h4 class="title" id="confirm-shortcode"><i class="fa fa-eye text-muted"></i> <?php _e('Confirm the Generated Shortcode', CDBT); ?></h4>
      
<?php $this->dynamic_field( $fields_define['generateSC'] ); ?>
<?php $this->dynamic_field( $fields_define['aliasCode'] ); ?>
      
      <div class="clearfix"><br></div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary"><?php _e('Update Shortcode', CDBT); ?></button>
          <button type="button" class="btn btn-default" id="edit-shortcode-preview"><?php _e('Preview Shortcode', CDBT); ?></button>
        </div>
      </div>
      
      <div class="pull-right">
        <a href="#"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
    </form>
  </div><!-- /.cdbt-edit-shortcode -->
<?php } else { ?>
  <div class="well-sm">
    <p class="text-info">
      <?php echo $_wall_message; ?> <?php $this->during_trial( 'shortcode_edit' ); ?>
    </p>
  </div>
<?php }
endif; ?>
  
</div><!-- /.wrap -->
