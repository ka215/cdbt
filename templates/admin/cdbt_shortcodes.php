<?php
/**
 * Template : Shortcodes Management Page
 * URL: `/wp-admin/admin.php?page=cdbt_shortcodes`
 *
 * @since 2.0.0
 * @since 2.1.31 Emhanced
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
      <?php _e('This shortcode list has been managing in the plugin. Your newly created shortcodes, you can use very conveniently by registering to this plugin.', CDBT); ?> <?php $this->during_trial( 'shortcode_list' ); ?>
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
      
<?php $this->dynamic_field( [ 'elementName'=>'base_name', 'elementId'=>'base_name', 'elementLabel'=>__('Base Shortcode', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'combobox', 'isRequired'=>true, 'labelSize'=>3, 'fieldSize'=>3, 'defaultValue'=>$base_shortcode, 'selectableList'=>array_keys( $_shortcodes ) ] ); ?>

<?php /*
      <div class="form-group">
        <label for="register-shortcode-base_name" class="col-sm-2 control-label"><?php _e('Base Shortcode', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="register-shortcode-base_name">
            <input type="text" name="<?php echo $this->domain_name; ?>[base_name]" value="<?php echo $base_shortcode; ?>" class="form-control" pattern=".{1,}" required>
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php $i = 0; foreach ($_shortcodes as $shortcode_name => $attributes) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $shortcode_name; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"></p>
        </div>
      </div><!-- /register-shortcode-base_name -->
*/ ?>
<?php $this->dynamic_field( [ 'elementName'=>'target_table', 'elementId'=>'target_table', 'elementLabel'=>__('Target Table', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'combobox', 'isRequired'=>true, 'labelSize'=>3, 'fieldSize'=>3, 'defaultValue'=>isset( $this_tab_vars['target_table'] ) ? $this_tab_vars['target_table'] : '', 'selectableList'=>$_tables ] ); ?>

<?php /*
      <div class="form-group">
        <label for="register-shortcode-target_table" class="col-sm-2 control-label"><?php _e('Target Table', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="register-shortcode-target_table">
            <input type="text" name="<?php echo $this->domain_name; ?>[target_table]" value="<?php if (isset($this_tab_vars['target_table'])) echo $this_tab_vars['target_table']; ?>" class="form-control" pattern=".{1,}" required>
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($_tables as $i => $table) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $table; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"></p>
        </div>
      </div><!-- /register-shortcode-target_table -->
*/ ?>
      <div class="form-group">
        <div class="col-sm-12" id="columns-information" style="padding: 0 2em;">
        </div>
      </div><!-- /colmuns-information -->
      
      <div class="clearfix"></div>
      <h4 class="title" id="advanced-settings"><i class="fa fa-cogs text-muted"></i> <?php _e('Advanced Shortcode Settings', CDBT); ?></h4>
      
<div class="overflow-block" style="margin: 0; padding: 1em 0; overflow-y: scroll; overflow-x: hidden; height: 400px;">
      
      <div class="sr-only switching-item on-e">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[entry_page]" value=""><!-- entry_page [e] -->
      </div>
      
<?php
  $appearances = [ 'elementName' => 'look_feel', 'elementId' => 'look_feel', 'elementLabel' => __('Appearance and LookAndFeel', CDBT), 'idPrefix' => 'register-shortcode-', 'elementType' => 'checkbox', 'fieldSize' => 10, 
    'defaultValue' => 'bootstrap_style,enable_repeater,display_search,display_title,enable_sort,display_index_row', 
    'selectableList' => [
      'bootstrap_style' => 		__( 'Renders the data via the style of bootstrap if checked. Render the data of the json format if unchecked.', CDBT ), 
      'enable_repeater' => 		__( 'Renders the data of table by using repeater component of the "FuelUX" libraries if checked. <wbr/>Or if unchecked, renders by using the original dynamic table component of this plugin.', CDBT ), 
      'display_list_num' => 	__( 'Adds an auto increment number column at the left edge of the data row if checked.', CDBT ), 
      'display_search' => 		__( 'Displays an input field for the data search if checked.', CDBT ), 
      'display_title' => 		__( 'Displays the heading of content as a title if checked.', CDBT ), 
      'enable_sort' => 			__( 'It will be able to sort of data by clicking on the header row if checked.', CDBT ), 
//    'display_index_row' => 	__( 'Displays the index row around the data rows as the header of the data column, if true. Also it&#39;s added of "head-only" for the table format besides boolean value.', CDBT ), 
      'display_filter' => 		__( 'Adds a dropdown list box for filtering the data if checked. Then there should be specified the column to filter if you want to enable this.', CDBT ), 
      'display_view' => 		__( 'You can switch to the thumbnail list view of the gallery format if there contained an image in the table data.', CDBT ), 
    ], 'addWrapClass' => 'toggle-group', 'addClass' => 'switching-item', 
    'elementExtras' => [
      'child-class' => 'bootstrap_style:on-v on-i on-e,enable_repeater:on-v,display_list_num:on-v on-e,display_search:on-v on-e,display_title:on-v on-i on-e,enable_sort:on-v on-e,display_index_row:on-v,display_filter:on-v on-e,display_view:on-v'
    ]
  ];
  $this->dynamic_field( $appearances );
?>

<?php /*
      <div class="form-group toggle-group">
        <label for="register-shortcode-look_feel" class="col-sm-2 control-label"><?php _e('Appearance and LookAndFeel', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox switching-item on-v on-i on-e" id="register-shortcode-look_feel1"><!-- bootstrap_style [v,i,e] -->
            <label class="checkbox-custom checked" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][bootstrap_style]" type="checkbox" value="1" <?php checked( isset( $this_tab_vars['bootstrap_style'] ) && $this_tab_vars['bootstrap_style'], true, true ); ?>>
              <span class="checkbox-label"><?php _e( 'Renders the data via the style of bootstrap if checked. Render the data of the json format if unchecked.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="register-shortcode-look_feel2"><!-- enable_repeater [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][enable_repeater]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['enable_repeater']) && $this_tab_vars['enable_repeater'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether rendering by repeater component at Fuel UX; It is output by the static table tag layout, also does not have any pagination if disabled.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-e" id="register-shortcode-look_feel3"><!-- display_list_num [v,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_list_num]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_list_num']) && $this_tab_vars['display_list_num'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether displaying list number; The default value has changed to disable since v2.0.0', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="register-shortcode-look_feel4"><!-- display_search [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_search]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_search']) && $this_tab_vars['display_search'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether display search box or not.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-i on-e" id="register-shortcode-look_feel5"><!-- display_title [v,i,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_title]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_title']) && $this_tab_vars['display_title'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether display title or not.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-e" id="register-shortcode-look_feel6"><!-- enable_sort [v,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][enable_sort]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['enable_sort']) && $this_tab_vars['enable_sort'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether enabling the sorting of each columns.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="register-shortcode-look_feel7"><!-- display_index_row [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_index_row]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_index_row']) && $this_tab_vars['display_index_row'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether displaying the index row; In the index row, it is rendered the column name of table.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /register-shortcode-look_feel -->
*/ ?>
      
<?php
  $dislay_index_row = [ 'elementName'=>'display_index_row', 'elementId'=>'display_index_row', 'elementLabel'=>__('Display Index Row', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'radio', 'horizontalList'=>true, 
    'defaultValue'=>'true', 
    'selectableList'=>[ 'false'=>__('Does not show', CDBT), 'true'=>__('Show all', CDBT), 'head-only'=>__('Show the header only', CDBT) ], 
    'helperText'=>__( 'Displays the index row around the data rows as the header of the data column. Also  you can specify the "head-only" if uses the table layout.', CDBT ), 
    'elementExtras' => [
      'child-class' => 'false:for-rpt for-tbl,true:for-rpt for-tbl,head-only:for-tbl', 
      'status' => 'under-test',
    ], 'addWrapClass' => 'switching-item on-v on-e', 
  ];
  $this->dynamic_field( $dislay_index_row );
?>
      
<?php $this->dynamic_field( [ 'elementName'=>'exclude_cols', 'elementId'=>'exclude_cols', 'elementLabel'=>__('Excludes Columns', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'column1,column2,column3,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Specifies the comma-delimited column names if you want to hide the column. e.g. "column1,column2,column3,..."', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-exclude_cols" class="col-sm-2 control-label"><?php _e('Exclude Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-exclude_cols" name="<?php echo $this->domain_name; ?>[exclude_cols]" type="text" value="<?php if (isset($this_tab_vars['exclude_cols'])) echo $this_tab_vars['exclude_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the column name that does not output at the specific string of comma-separated. For example, "col1,col2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-exclude_cols [v,e] -->
*/?>
      
<?php $this->dynamic_field( [ 'elementName'=>'add_class', 'elementId'=>'add_class', 'elementLabel'=>__('Adds Classes', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'class1 class2 class3 ...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-i on-e', 'helperText'=>__('Specifies a CSS class name for styling the element of listed data table. If there are multiple class, please separated by a single-byte space.', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v on-i on-e">
        <label for="register-shortcode-add_class" class="col-sm-2 control-label"><?php _e('Add Classes', CDBT); ?></label>
        <div class="col-sm-7">
          <input id="register-shortcode-add_class" name="<?php echo $this->domain_name; ?>[add_class]" type="text" value="<?php if (isset($this_tab_vars['add_class'])) echo $this_tab_vars['add_class']; ?>" class="form-control" placeholder="class1 class2 class3 ...">
          <p class="help-block"><?php _e('Separator is a single-byte space character', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-add_class [v,i,e] -->
*/?>
      
<?php $this->dynamic_field( [ 'elementName'=>'narrow_keyword', 'elementId'=>'narrow_keyword', 'elementLabel'=>__('Narrow-down Keywords', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'keyword1,keyword2,... OR column1:keyword1,column2:keyword2,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Specifies the narrowing condition of the output data in a comma-delimited. If there are the multiple condition, it will be evaluated at the "AND" condition. <wbr/>e.g. "keyword1,keyword2,..." or "column1:keyword1,column2:keyword2,..."', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-narrow_keyword" class="col-sm-2 control-label"><?php _e('Narrow Keywords', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-narrow_keyword" name="<?php echo $this->domain_name; ?>[narrow_keyword]" type="text" value="<?php if (isset($this_tab_vars['narrow_keyword'])) echo $this_tab_vars['narrow_keyword']; ?>" class="form-control" placeholder="keyword1,keyword2,... or col1:keyword1,col2:keyword2,...">
          <p class="help-block"><?php _e('Please enter the narrow keywords in a comma-separated. For example, "keyword1,keyword2,..." or "col1:keyword1,col2:keyword2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-narrow_keyword [v,e] -->
*/?>
      
<?php $this->dynamic_field( [ 'elementName'=>'hidden_cols', 'elementId'=>'hidden_cols', 'elementLabel'=>__('Hides Columns', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>9, 'placeholder'=>'column1,column2,column3,...', 'addWrapClass' => 'switching-item on-i', 'helperText'=>__('Specifies a comma delimited the column names if you want to hide any column. Then the hidden column will be rendered as field of "hidden" type. <wbr/>e.g. "column1,column2,column3,..."', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-i">
        <label for="register-shortcode-hidden_cols" class="col-sm-2 control-label"><?php _e('Hidden Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-hidden_cols" name="<?php echo $this->domain_name; ?>[hidden_cols]" type="text" value="<?php if (isset($this_tab_vars['hidden_cols'])) echo $this_tab_vars['hidden_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the column name that does not output at the specific string of comma-separated. For example, "col1,col2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-hidden_cols [i] -->
*/?>
      
<?php $this->dynamic_field( [ 'elementName'=>'display_cols', 'elementId'=>'display_cols', 'elementLabel'=>__('Displays Columns', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'column1,column2,column3,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v', 'helperText'=>__('Specifies the comma-delimited column names if you want to show the column. This overrides the value of the option "Excludes columns". e.g. "column1,column2,column3,..."', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-display_cols" class="col-sm-2 control-label"><?php _e('Display Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-display_cols" name="<?php echo $this->domain_name; ?>[display_cols]" type="text" value="<?php if (isset($this_tab_vars['display_cols'])) echo $this_tab_vars['display_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the displaying column name in comma-delimited. If it overlap with excluding column, this setting takes precedence..', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-display_cols [v] -->
*/ ?>
      
<?php $this->dynamic_field( [ 'elementName'=>'order_cols', 'elementId'=>'order_cols', 'elementLabel'=>__('Columns Display Order', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'placeholder'=>'column3,column1,column2,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v', 'helperText'=>__('Specifies the comma-delimited column names in the display order if you want to display columns in the order of your display request. This overrides the value of the option "Excludes Columns" and "Displays Columns". e.g. "column3,column1,column2,..."', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-order_cols" class="col-sm-2 control-label"><?php _e('Column Order', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-order_cols" name="<?php echo $this->domain_name; ?>[order_cols]" type="text" value="<?php if (isset($this_tab_vars['order_cols'])) echo $this_tab_vars['order_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the displaying column order in comma-delimited. If it overlap with display columns, this setting takes precedence.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-order_cols [v] -->
*/?>
	  
<?php $this->dynamic_field( [ 'elementName'=>'sort_order', 'elementId'=>'sort_order', 'elementLabel'=>__('Initial Sort Order', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'created:desc', 'placeholder'=>'updated:desc,ID:asc,...', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Specifies in the pair of column name and the ascending(asc) or descending(desc) order, for the display order of the initial data. If there are multiple condition, please use the comma-delimited. e.g. "updated:desc,ID:asc,..."', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-sort_order" class="col-sm-2 control-label"><?php _e('Column Sort Order', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-sort_order" name="<?php echo $this->domain_name; ?>[sort_order]" type="text" value="<?php if (isset($this_tab_vars['sort_order'])) : echo $this_tab_vars['sort_order']; else : echo 'created:desc'; endif; ?>" class="form-control" placeholder="updated:desc,ID:asc,...">
          <p class="help-block"><?php _e('Please enter the default column sort order at comma-delimited. For example, "updated:desc,ID:asc,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-sort_order [v,e] -->
*/?>
      
<?php $this->dynamic_field( [ 'elementName'=>'limit_items', 'elementId'=>'limit_items', 'elementLabel'=>__('Max Rows Per Page', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'spinbox', 'defaultValue'=>'', 'fieldSize'=>3, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('If this attribute is specified, it overrides the "Maximum display data per page" of the table.', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-limit_items" class="col-sm-2 control-label"><?php _e('Limit Records Per Page', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="register-shortcode-limit_items">
            <input type="text" name="<?php echo $this->domain_name; ?>[limit_items]" value="<?php if (isset($this_tab_vars['limit_items'])) echo intval($this_tab_vars['limit_items']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('The default value is overwritten by the value of the max_show_records of the specified table.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-limit_items [v] -->
*/?>
      
<?php $this->dynamic_field( [ 'elementName'=>'truncate_strings', 'elementId'=>'truncate_strings', 'elementLabel'=>__('Truncates String', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'spinbox', 'defaultValue'=>40, 'fieldSize'=>3, 'addWrapClass' => 'switching-item on-v on-e', 'helperText'=>__('Truncates the display data if the strings type data is longer than the specified characters (not bytes). If value is zero it does not truncate.', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-truncate_strings" class="col-sm-2 control-label"><?php _e('Truncate Strings', CDBT); ?> <?php $this->during_trial( 'truncate_strings' ); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="register-shortcode-limit_items">
            <input type="text" name="<?php echo $this->domain_name; ?>[truncate_strings]" value="<?php if (isset($this_tab_vars['truncate_strings'])) echo intval($this_tab_vars['truncate_strings']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('Number of characters in the string type column truncates the display to the case more than the specified value.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-truncate_strings [v,e] -->
*/?>
      
<?php $this->dynamic_field( [ 'elementName'=>'image_render', 'elementId'=>'image_render', 'elementLabel'=>__('Thumbnail Image Class', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'combobox', 'fieldSize'=>3, 'defaultValue'=>'responsive', 'selectableList'=>['rounded', 'circle', 'thumbnail', 'responsive'], 'addWrapClass'=>'switching-item on-v on-e', 'helperText'=>__('Specifies a CSS class name for styling the thumbnail images. This CSS class will be added to img tag of thumbnail image. It will enable only if renders the repeater layout.', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-image_render" class="col-sm-2 control-label"><?php _e('Rendering Image Type', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="register-shortcode-image_render">
            <input type="text" name="<?php echo $this->domain_name; ?>[image_render]" value="<?php if (isset($this_tab_vars['image_render'])) : echo $this_tab_vars['image_render']; else : echo 'responsive'; endif; ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ([ 'rounded', 'circle', 'thumbnail', 'responsive' ] as $i => $_render) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $_render; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php _e('Please choose class name for rendering image tag.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-image_render [v] -->
*/?>

<?php $this->dynamic_field( [ 'elementName'=>'submit_button_label', 'elementId'=>'submit_button_label', 'elementLabel'=>__('Labeled Submit Button', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>4, 'addWrapClass' => 'switching-item on-i', 'helperText'=>__('Specifies the label name of button for submitting in the entry form.', CDBT) ] ); ?>

<?php /*
      <div class="form-group switching-item on-i">
        <label for="register-shortcode-submit_button_label" class="col-sm-2 control-label"><?php _e('Submit Button Label', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="register-shortcode-submit_button_label" name="<?php echo $this->domain_name; ?>[submit_button_label]" type="text" value="<?php if (isset($this_tab_vars['submit_button_label'])) echo $this_tab_vars['submit_button_label']; ?>" class="form-control" placeholder="<?php _e('Enter strings', CDBT); ?>">
        </div>
        <div class="col-sm-offset-2" style="clear: left; padding-top: 3px;">
          <p class="help-block" style="margin-left: 1em;"><?php _e('Please enter strings that you want to display on the submit button.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-submit_button_label [i] -->
*/?>

<?php $this->dynamic_field( [ 'elementName'=>'redirect_url', 'elementId'=>'redirect_url', 'elementLabel'=>__('Redirect URL', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-i', 'helperText'=>__('Specifies the url to redirect after the time of insertion and the update of the data. If not specified, self page is reloaded.', CDBT) ] ); ?>

<?php /*
      <div class="form-group switching-item on-i">
        <label for="register-shortcode-redirect_url" class="col-sm-2 control-label"><?php _e('Redirect URL', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-redirect_url" name="<?php echo $this->domain_name; ?>[redirect_url]" type="text" value="<?php if (isset($this_tab_vars['redirect_url'])) echo $this_tab_vars['redirect_url']; ?>" class="form-control" placeholder="Absolute URI">
          <p class="help-block"><?php _e('Please enter URL (absolute URI) that you want to redirect after the completion of the data registration.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-redirect_url [i] -->
*/?>

<?php /*
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-display_filter" class="col-sm-2 control-label"><?php _e('Display Filter Box', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="register-shortcode-display_filter">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_filter]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_filter']) && $this_tab_vars['display_filter'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether displaying the filter box; Is enabled only if rendering by repeater component.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- register-shortcode-display_filter [v,e] -->
*/?>

<?php $this->dynamic_field( [ 'elementName'=>'filter_column', 'elementId'=>'filter_column', 'elementLabel'=>__('Filtering Column Name', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>4, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies a column name for filtering the data. If you specify a column of "enum" or "set" type, the filtering will be enabled by automatic without a filter definition below.', CDBT) ] ); ?>

<?php /*
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-filter_column" class="col-sm-2 control-label"><?php _e('Target Filter Column', CDBT); ?></label>
        <div class="col-sm-6">
          <input id="register-shortcode-filter_column" name="<?php echo $this->domain_name; ?>[filter_column]" type="text" value="<?php if (isset($this_tab_vars['filter_column'])) echo $this_tab_vars['filter_column']; ?>" class="form-control" placeholder="column name">
          <p class="help-block"><?php _e('Please enter the column name to filter.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-filters_column [v,e] -->
*/?>

<?php $this->dynamic_field( [ 'elementName'=>'filters', 'elementId'=>'filters', 'elementLabel'=>__('Filter Definition', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>9, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies the keyword lists for filtering the data. Also, a plurality of the pairs of the keyword and the display label can be defined by using the comma-delimited. e.g. "filter-keyword1:display-label1,filter-keyword2:display-label2,..."', CDBT) ] ); ?>

<?php /*
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-filters" class="col-sm-2 control-label"><?php _e('Filters Definition', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-filters" name="<?php echo $this->domain_name; ?>[filters]" type="text" value="<?php if (isset($this_tab_vars['filters'])) echo $this_tab_vars['filters']; ?>" class="form-control" placeholder="filter1:label1,filter2:label2,...">
          <p class="help-block"><?php _e('Please enter the pair of the filter value and displaying label at comma-separator. For example, "filter1:label1,filter2:label2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-filters [v,e] -->
*/?>
<?php /*
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-display_view" class="col-sm-2 control-label"><?php _e('Enable Switching View', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="register-shortcode-display_view">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_view]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_view']) && $this_tab_vars['display_view'], true, true); ?>>
              <span class="checkbox-label"><?php _e('You are able to switch list view and thumbnail view if checked; This is enabled only if rendering by repeater component.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- register-shortcode-display_view [v] -->
*/?>

<?php $this->dynamic_field( [ 'elementName'=>'thumbnail_column', 'elementId'=>'thumbnail_column', 'elementLabel'=>__('Thumbnail Image Column', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>4, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies a column as the thumbnail image. In this column it should be stored the image binary or a URL of image.', CDBT) ] ); ?>

<?php /*
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-thumbnail_column" class="col-sm-2 control-label"><?php _e('Thumbnail Image Column', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="register-shortcode-thumbnail_column" name="<?php echo $this->domain_name; ?>[thumbnail_column]" type="text" value="<?php if (isset($this_tab_vars['thumbnail_column'])) echo $this_tab_vars['thumbnail_column']; ?>" class="form-control" placeholder="">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('The data of this column used as a thumbnail image. In this column, it must be stored the image binary or a image URL.', CDBT); ?></p>
      </div><!-- /register-shortcode-thumbnail_column [v] -->
*/?>

<?php $this->dynamic_field( [ 'elementName'=>'thumbnail_title_column', 'elementId'=>'thumbnail_title_column', 'elementLabel'=>__('Thumbnail Title column', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'defaultValue'=>'', 'fieldSize'=>4, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies a column as displayed title on the thumbnail list view. it displays nothing if this is not fill.', CDBT) ] ); ?>

<?php /*
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-thumbnail_title_column" class="col-sm-2 control-label"><?php _e('Thumbnail Title column', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="register-shortcode-thumbnail_title_column" name="<?php echo $this->domain_name; ?>[thumbnail_title_column]" type="text" value="<?php if (isset($this_tab_vars['thumbnail_title_column'])) echo $this_tab_vars['thumbnail_title_column']; ?>" class="form-control" placeholder="">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('This column name to use as the title of the thumbnail image.', CDBT); ?></p>
      </div><!-- /register-shortcode-thumbnail_title_column [v] -->
*/ ?>

<?php $this->dynamic_field( [ 'elementName'=>'thumbnail_width', 'elementId'=>'thumbnail_width', 'elementLabel'=>__('Thumbnail Block Size', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'spinbox', 'defaultValue'=>100, 'fieldSize'=>3, 'addWrapClass' => 'switching-item on-e on-v', 'helperText'=>__('Specifies a width of the thumbnail image, also the default size of thumbnail will be square equal to this width.', CDBT) ] ); ?>

<?php /*
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-thumbnail_width" class="col-sm-2 control-label"><?php _e('Thumbnail Block Size', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="register-shortcode-thumbnail_width">
            <input type="text" name="<?php echo $this->domain_name; ?>[thumbnail_width]" value="<?php if (isset($this_tab_vars['thumbnail_width'])) : echo intval($this_tab_vars['thumbnail_width']); else : echo 100; endif; ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('Please enter the integer for width of thumbnail block.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-thumbnail_width [v] -->
*/ ?>

<?php $this->dynamic_field( [ 'elementName'=>'ajax_load', 'elementId'=>'ajax_load', 'elementLabel'=>__('Adding Ajax Support', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'checkbox', 'defaultValue'=>'', 'selectableList'=>[ 'ajax_load'=>__('To Use the Ajax for loading the table data if checked.', CDBT) ], 'addWrapClass'=>'switching-item on-e on-v', 'helperText'=>__('If activated, you can improve performance when dealing with large tables of data size. (Not Implemented yet)', CDBT), 'elementExtras'=>[ 'child-class'=>'ajax_load:disabled', 'disabled'=>'disabled', 'status'=>'futrue' ] ] ); ?>

<?php /*
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-ajax_load" class="col-sm-2 control-label"><?php _e('Loading Via Ajax', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="register-shortcode-ajax_load">
            <label class="checkbox-custom disabled" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[ajax_load]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['ajax_load']) && $this_tab_vars['ajax_load'], true, true); ?> disabled="disabled">
              <span class="checkbox-label"><?php _e('It will be on loading data via Ajax if checked this; Sorry, this feature is currently disabled yet.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- register-shortcode-ajax_load [v,e] -->
*/ ?>
      
      <div class="form-group switching-item on-i">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[action_url]" value="<?php if (isset($this_tab_vars['action_url'])) echo $this_tab_vars['action_url']; ?>" disabled="disabled"><!-- URL for form action for using shortcode of "cdbt-edit". -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[form_action]" value="<?php if (isset($this_tab_vars['form_action'])) : echo $this_tab_vars['form_action']; else : echo 'entry_data'; endif; ?>" disabled="disabled"><!-- for using shortcode of "cdbt-edit" -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[display_submit]" value="1" disabled="disabled"><!-- for using shortcode of "cdbt-edit" -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[where_clause]" value="<?php if (isset($this_tab_vars['where_clause'])) echo $this_tab_vars['where_clause']; ?>" placeholder="col1:value1,col2:value2,..." disabled="disabled"><!-- for using shortcode of "cdbt-edit" -->
      </div>
      
<?php $this->dynamic_field( [ 'elementName'=>'description', 'elementId'=>'description', 'elementLabel'=>__('Description', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'textarea', 'fieldSize' => 9, 'defaultValue'=>'', 'helperText'=>__('You can specify as like description that will be displayed in the shortcode lists screen.', CDBT) ] ); ?>
      
<?php /*
      <div class="form-group">
        <label for="register-shortcode-description" class="col-sm-2 control-label"><?php _e('Description', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="register-shortcode-description" name="<?php echo $this->domain_name; ?>[description]" class="form-control" rows="2" placeholder="Enter description as meno"><?php if (isset($this_tab_vars['description'])) echo esc_textarea(stripslashes_deep($this_tab_vars['description'])); ?></textarea>
          <p class="help-block"><?php _e('Please enter as like description of shortcode that will be displayed in the list screen.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-description -->
*/ ?>
      
      <div class="form-group switching-item on-v on-i on-e">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[csid]" value="<?php echo $current_csid; ?>"><!-- Valid value of "Custom Shortcode ID" is 1 or more integer. [v,i,e] -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[author]" value="<?php echo $user_ID; ?>"><!-- Current user ID -->
      </div>
      
</div><!-- /.overflow-block -->
      
      <div class="clearfix"></div>
      <h4 class="title" id="confirm-shortcode"><i class="fa fa-eye text-muted"></i> <?php _e('Confirm the Generated Shortcode', CDBT); ?></h4>
      
<?php $this->dynamic_field( [ 'elementName'=>'generate_shortcode', 'elementId'=>'generate_shortcode', 'elementLabel'=>__('Generated Shortcode', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'textarea', 'fieldSize' => 9, 'defaultValue'=>'', 'placeholder'=>__('No generated shortcode yet.', CDBT), 'elementExtras'=>[ 'rows'=>4, 'readonly'=>'readonly' ] ] ); ?>
      
<?php /*
      <div class="form-group">
        <label for="register-shortcode-generate_shortcode" class="col-sm-2 control-label"><?php _e('Generated Shortcode', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="register-shortcode-generate_shortcode" name="<?php echo $this->domain_name; ?>[generate_shortcode]" class="form-control" rows="5" readonly><?php if (isset($this_tab_vars['generate_shortcode'])) echo esc_textarea(stripslashes_deep($this_tab_vars['generate_shortcode'])); ?></textarea>
        </div>
      </div>
*/ ?>
      
<?php $this->dynamic_field( [ 'elementName'=>'alias_code', 'elementId'=>'alias_code', 'elementLabel'=>__('Alias Shortcode', CDBT), 'idPrefix'=>'register-shortcode-', 'elementType'=>'text', 'fieldSize' => 9, 'defaultValue'=>'', 'placeholder'=>__('No alias shortcode yet.', CDBT), 'elementExtras'=>[ 'readonly'=>'readonly' ] ] ); ?>
      
<?php /*
      <div class="form-group">
        <label for="register-shortcode-alias_code" class="col-sm-2 control-label"><?php _e('Alias Shortcode', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-alias_code" name="<?php echo $this->domain_name; ?>[alias_code]" type="text" value="<?php if (isset($this_tab_vars['alias_code'])) echo esc_textarea(stripslashes_deep($this_tab_vars['alias_code'])); ?>" class="form-control" readonly>
        </div>
      </div>
*/ ?>
      
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
    $_wall_message = sprintf(__('Please select a custom shortcode that you want to edit %sat the shortcode list%s.', CDBT), '<a href="'. add_query_arg('tab', 'shortcode_list') .'">', '</a>');
  }
  if (!isset($_wall_message) && !in_array(get_current_user_id(), [ 1, $this_tab_vars['author'] ])) {
    $_wall_message = __('Custom shortcode can not edit other than the registrant or privilege administrator.', CDBT);
  }
//var_dump($this_tab_vars);
  
  if (!isset($_wall_message)) {
    $base_shortcode = '';
    if (isset($session_vars) && !empty($session_vars)) {
      if (array_key_exists('base_name', $this_tab_vars)) 
        $base_shortcode = $this_tab_vars['base_name'];
    }
    
    $_tables = $this->get_table_list();
    
    $current_csid = $this_tab_vars['csid'];
    
    $user_ID = $this_tab_vars['author'];
?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('You can edit the settings of custom shortcode at here.', CDBT); ?> <?php $this->during_trial( 'shortcode_edit' ); ?>
    </p>
  </div>
  
  <div class="cdbt-edit-shortcode">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="edit_shortcode">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <div class="form-group">
        <label for="edit-shortcode-base_name" class="col-sm-2 control-label"><?php _e('Base shortcode name', CDBT); ?></label>
        <div class="col-sm-3">
          <input type="text" name="<?php echo $this->domain_name; ?>[base_name]" id="edit-shortcode-base_name" value="<?php echo $base_shortcode; ?>" class="form-control" readonly>
        </div>
        <p class="help-block sr-only"></p>
      </div><!-- /edit-shortcode-base_name -->
      <div class="form-group">
        <label for="edit-shortcode-target_table" class="col-sm-2 control-label"><?php _e('Target table name', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="edit-shortcode-target_table">
            <input type="text" name="<?php echo $this->domain_name; ?>[target_table]" value="<?php if (isset($this_tab_vars['target_table'])) echo $this_tab_vars['target_table']; ?>" class="form-control" pattern=".{1,}" required>
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($_tables as $i => $table) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $table; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block sr-only"></p>
        </div>
      </div><!-- /edit-shortcode-target_table -->
      <div class="form-group">
        <div class="col-sm-12" id="columns-information" style="padding: 0 2em;">
        </div>
      </div><!-- /colmuns-information -->
      
      <div class="clearfix"><br></div>
      <h4 class="title" id="advanced-settings"><i class="fa fa-cogs text-muted"></i> <?php _e('Advanced setting of shortcode', CDBT); ?></h4>
      
      <div class="sr-only switching-item on-e">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[entry_page]" value=""><!-- entry_page [e] -->
      </div>
      
      <div class="form-group toggle-group">
        <label for="edit-shortcode-look_feel" class="col-sm-2 control-label"><?php _e('Toggle of look and feel', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox switching-item on-v on-i on-e" id="edit-shortcode-look_feel1"><!-- bootstrap_style [v,i,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][bootstrap_style]" type="checkbox" value="1" checked="checked" disabled="disabled"<?php /* checked(isset($this_tab_vars['bootstrap_style']) && $this_tab_vars['bootstrap_style'], true, true); */ ?>>
              <!-- <span class="checkbox-label"><?php _e('Whether of using Bootstrap style; It is output by the static table tag layout in non the Repeater format, also does not have any pagination if disabled.', CDBT); ?></span> -->
              <span class="checkbox-label"><?php _e('Whether of using Bootstrap style; This can not changed since v2.0.0.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="edit-shortcode-look_feel2"><!-- enable_repeater [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][enable_repeater]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['enable_repeater']) && $this_tab_vars['enable_repeater'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether rendering by repeater component at Fuel UX; It is output by the static table tag layout, also does not have any pagination if disabled.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-e" id="edit-shortcode-look_feel3"><!-- display_list_num [v,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_list_num]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_list_num']) && $this_tab_vars['display_list_num'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether displaying list number; The default value has changed to disable since v2.0.0', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="edit-shortcode-look_feel4"><!-- display_search [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_search]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_search']) && $this_tab_vars['display_search'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether display search box or not.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-i on-e" id="edit-shortcode-look_feel5"><!-- display_title [v,i,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_title]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_title']) && $this_tab_vars['display_title'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether display title or not.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-e" id="edit-shortcode-look_feel6"><!-- enable_sort [v,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][enable_sort]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['enable_sort']) && $this_tab_vars['enable_sort'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether enabling the sorting of each columns.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="edit-shortcode-look_feel7"><!-- display_index_row [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_index_row]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_index_row']) && $this_tab_vars['display_index_row'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether displaying the index row; In the index row, it is rendered the column name of table.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /edit-shortcode-look_feel -->
      
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-exclude_cols" class="col-sm-2 control-label"><?php _e('Exclude Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-exclude_cols" name="<?php echo $this->domain_name; ?>[exclude_cols]" type="text" value="<?php if (isset($this_tab_vars['exclude_cols'])) echo $this_tab_vars['exclude_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the column name that does not output at the specific string of comma-separated. For example, "col1,col2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-exclude_cols [v,e] -->
      <div class="form-group switching-item on-v on-i on-e">
        <label for="edit-shortcode-add_class" class="col-sm-2 control-label"><?php _e('Add Classes', CDBT); ?></label>
        <div class="col-sm-7">
          <input id="edit-shortcode-add_class" name="<?php echo $this->domain_name; ?>[add_class]" type="text" value="<?php if (isset($this_tab_vars['add_class'])) echo $this_tab_vars['add_class']; ?>" class="form-control" placeholder="class1 class2 class3 ...">
          <p class="help-block"><?php _e('Separator is a single-byte space character', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-add_class [v,i,e] -->
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-narrow_keyword" class="col-sm-2 control-label"><?php _e('Narrow Keywords', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-narrow_keyword" name="<?php echo $this->domain_name; ?>[narrow_keyword]" type="text" value="<?php if (isset($this_tab_vars['narrow_keyword'])) echo $this_tab_vars['narrow_keyword']; ?>" class="form-control" placeholder="keyword1,keyword2,... or col1:keyword1,col2:keyword2,...">
          <p class="help-block"><?php _e('Please enter the narrow keywords in a comma-separated. For example, "keyword1,keyword2,..." or "col1:keyword1,col2:keyword2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-narrow_keyword [v,e] -->
      <div class="form-group switching-item on-i">
        <label for="edit-shortcode-hidden_cols" class="col-sm-2 control-label"><?php _e('Hidden Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-hidden_cols" name="<?php echo $this->domain_name; ?>[hidden_cols]" type="text" value="<?php if (isset($this_tab_vars['hidden_cols'])) echo $this_tab_vars['hidden_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the column name that does not output at the specific string of comma-separated. For example, "col1,col2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-hidden_cols [i] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-display_cols" class="col-sm-2 control-label"><?php _e('Display Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-display_cols" name="<?php echo $this->domain_name; ?>[display_cols]" type="text" value="<?php if (isset($this_tab_vars['display_cols'])) echo $this_tab_vars['display_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the displaying column name in comma-delimited. If it overlap with excluding column, this setting takes precedence..', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-display_cols [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-order_cols" class="col-sm-2 control-label"><?php _e('Column Order', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-order_cols" name="<?php echo $this->domain_name; ?>[order_cols]" type="text" value="<?php if (isset($this_tab_vars['order_cols'])) echo $this_tab_vars['order_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the displaying column order in comma-delimited. If it overlap with display columns, this setting takes precedence.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-order_cols [v] -->
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-sort_order" class="col-sm-2 control-label"><?php _e('Column Sort Order', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-sort_order" name="<?php echo $this->domain_name; ?>[sort_order]" type="text" value="<?php if (isset($this_tab_vars['sort_order'])) : echo $this_tab_vars['sort_order']; else : echo 'created:desc'; endif; ?>" class="form-control" placeholder="updated:desc,ID:asc,...">
          <p class="help-block"><?php _e('Please enter the default column sort order at comma-delimited. For example, "updated:desc,ID:asc,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-sort_order [v,e] -->

      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-limit_items" class="col-sm-2 control-label"><?php _e('Limit Records Per Page', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="edit-shortcode-limit_items">
            <input type="text" name="<?php echo $this->domain_name; ?>[limit_items]" value="<?php if (isset($this_tab_vars['limit_items'])) echo intval($this_tab_vars['limit_items']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('The default value is overwritten by the value of the max_show_records of the specified table.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-limit_items [v] -->
      
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-truncate_strings" class="col-sm-2 control-label"><?php _e('Truncate Strings', CDBT); ?> <?php $this->during_trial( 'truncate_strings' ); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="edit-shortcode-limit_items">
            <input type="text" name="<?php echo $this->domain_name; ?>[truncate_strings]" value="<?php if (isset($this_tab_vars['truncate_strings'])) echo intval($this_tab_vars['truncate_strings']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('Number of characters in the string type column truncates the display to the case more than the specified value.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-truncate_strings [v,e] -->
      
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-image_render" class="col-sm-2 control-label"><?php _e('Rendering Image Type', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="edit-shortcode-image_render">
            <input type="text" name="<?php echo $this->domain_name; ?>[image_render]" value="<?php if (isset($this_tab_vars['image_render'])) : echo $this_tab_vars['image_render']; else : echo 'responsive'; endif; ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ([ 'rounded', 'circle', 'thumbnail', 'responsive' ] as $i => $_render) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $_render; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php _e('Please choose class name for rendering image tag.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-image_render [v] -->

      <div class="form-group switching-item on-i">
        <label for="edit-shortcode-submit_button_label" class="col-sm-2 control-label"><?php _e('Submit Button Label', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="edit-shortcode-submit_button_label" name="<?php echo $this->domain_name; ?>[submit_button_label]" type="text" value="<?php if (isset($this_tab_vars['submit_button_label'])) echo $this_tab_vars['submit_button_label']; ?>" class="form-control" placeholder="<?php _e('Enter strings', CDBT); ?>">
        </div>
        <div class="col-sm-offset-2" style="clear: left; padding-top: 3px;">
          <p class="help-block" style="margin-left: 1em;"><?php _e('Please enter strings that you want to display on the submit button.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-submit_button_label [i] -->
      <div class="form-group switching-item on-i">
        <label for="edit-shortcode-redirect_url" class="col-sm-2 control-label"><?php _e('Redirect URL', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-redirect_url" name="<?php echo $this->domain_name; ?>[redirect_url]" type="text" value="<?php if (isset($this_tab_vars['redirect_url'])) echo $this_tab_vars['redirect_url']; ?>" class="form-control" placeholder="Absolute URI">
          <p class="help-block"><?php _e('Please enter URL (absolute URI) that you want to redirect after the completion of the data registration.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-redirect_url [i] -->

      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-display_filter" class="col-sm-2 control-label"><?php _e('Display Filter Box', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="edit-shortcode-display_filter">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_filter]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_filter']) && $this_tab_vars['display_filter'], true, true); ?>>
              <span class="checkbox-label"><?php _e('Whether displaying the filter box; Is enabled only if rendering by repeater component.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- edit-shortcode-display_filter [v,e] -->
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-filter_column" class="col-sm-2 control-label"><?php _e('Target Filter Column', CDBT); ?></label>
        <div class="col-sm-6">
          <input id="edit-shortcode-filter_column" name="<?php echo $this->domain_name; ?>[filter_column]" type="text" value="<?php if (isset($this_tab_vars['filter_column'])) echo $this_tab_vars['filter_column']; ?>" class="form-control" placeholder="column name">
          <p class="help-block"><?php _e('Please enter the column name to filter.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-filters_column [v,e] -->
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-filters" class="col-sm-2 control-label"><?php _e('Filters Definition', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-filters" name="<?php echo $this->domain_name; ?>[filters]" type="text" value="<?php if (isset($this_tab_vars['filters'])) echo $this_tab_vars['filters']; ?>" class="form-control" placeholder="filter1:label1,filter2:label2,...">
          <p class="help-block"><?php _e('Please enter the pair of the filter value and displaying label at comma-separator. For example, "filter1:label1,filter2:label2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-filters [v,e] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-display_view" class="col-sm-2 control-label"><?php _e('Enable Switching View', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="edit-shortcode-display_view">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_view]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['display_view']) && $this_tab_vars['display_view'], true, true); ?>>
              <span class="checkbox-label"><?php _e('You are able to switch list view and thumbnail view if checked; This is enabled only if rendering by repeater component.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- edit-shortcode-display_view [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-thumbnail_column" class="col-sm-2 control-label"><?php _e('Thumbnail Image Column', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="edit-shortcode-thumbnail_column" name="<?php echo $this->domain_name; ?>[thumbnail_column]" type="text" value="<?php if (isset($this_tab_vars['thumbnail_column'])) echo $this_tab_vars['thumbnail_column']; ?>" class="form-control" placeholder="">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('The data of this column used as a thumbnail image. In this column, it must be stored the image binary or a image URL.', CDBT); ?></p>
      </div><!-- /edit-shortcode-thumbnail_column [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-thumbnail_title_column" class="col-sm-2 control-label"><?php _e('Thumbnail Title column', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="edit-shortcode-thumbnail_title_column" name="<?php echo $this->domain_name; ?>[thumbnail_title_column]" type="text" value="<?php if (isset($this_tab_vars['thumbnail_title_column'])) echo $this_tab_vars['thumbnail_title_column']; ?>" class="form-control" placeholder="">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('This column name to use as the title of the thumbnail image.', CDBT); ?></p>
      </div><!-- /edit-shortcode-thumbnail_title_column [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-thumbnail_width" class="col-sm-2 control-label"><?php _e('Thumbnail Block Size', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="edit-shortcode-thumbnail_width">
            <input type="text" name="<?php echo $this->domain_name; ?>[thumbnail_width]" value="<?php if (isset($this_tab_vars['thumbnail_width'])) : echo intval($this_tab_vars['thumbnail_width']); else : echo 100; endif; ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('Please enter the integer for width of thumbnail block.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-thumbnail_width [v] -->
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-ajax_load" class="col-sm-2 control-label"><?php _e('Loading Via Ajax', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="edit-shortcode-ajax_load">
            <label class="checkbox-custom disabled" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[ajax_load]" type="checkbox" value="1"<?php checked(isset($this_tab_vars['ajax_load']) && $this_tab_vars['ajax_load'], true, true); ?> disabled="disabled">
              <span class="checkbox-label"><?php _e('It will be on loading data via Ajax if checked this; Sorry, this feature is currently disabled yet.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- edit-shortcode-ajax_load [v,e] -->
      
      <div class="form-group switching-item on-i">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[action_url]" value="<?php if (isset($this_tab_vars['action_url'])) echo $this_tab_vars['action_url']; ?>" disabled="disabled"><!-- URL for form action for using shortcode of "cdbt-edit". -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[form_action]" value="<?php if (isset($this_tab_vars['form_action'])) : echo $this_tab_vars['form_action']; else : echo 'entry_data'; endif; ?>" disabled="disabled"><!-- for using shortcode of "cdbt-edit" -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[display_submit]" value="1" disabled="disabled"><!-- for using shortcode of "cdbt-edit" -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[where_clause]" value="<?php if (isset($this_tab_vars['where_clause'])) echo $this_tab_vars['where_clause']; ?>" placeholder="col1:value1,col2:value2,..." disabled="disabled"><!-- for using shortcode of "cdbt-edit" -->
      </div>
      
      <div class="form-group">
        <label for="edit-shortcode-description" class="col-sm-2 control-label"><?php _e('Description', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="edit-shortcode-description" name="<?php echo $this->domain_name; ?>[description]" class="form-control" rows="2" placeholder="Enter description as meno"><?php if (isset($this_tab_vars['description'])) echo esc_textarea(stripslashes_deep($this_tab_vars['description'])); ?></textarea>
          <p class="help-block"><?php _e('Please enter as like description of shortcode that will be displayed in the list screen.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-description -->
      
      <div class="form-group switching-item on-v on-i on-e">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[csid]" value="<?php echo $current_csid; ?>"><!-- Valid value of "Custom Shortcode ID" is 1 or more integer. [v,i,e] -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[author]" value="<?php echo $user_ID; ?>"><!-- Current user ID -->
      </div>
      
      <div class="clearfix"><br></div>
      <h4 class="title" id="confirm-shortcode"><i class="fa fa-eye text-muted"></i> <?php _e('Generated shortcode confirmation', CDBT); ?></h4>
      
      <div class="form-group">
        <label for="edit-shortcode-generate_shortcode" class="col-sm-2 control-label"><?php _e('Generated Shortcode', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="edit-shortcode-generate_shortcode" name="<?php echo $this->domain_name; ?>[generate_shortcode]" class="form-control" rows="5" readonly><?php if (isset($this_tab_vars['generate_shortcode'])) echo esc_textarea(stripslashes_deep($this_tab_vars['generate_shortcode'])); ?></textarea>
        </div>
      </div>
      <div class="form-group">
        <label for="edit-shortcode-alias_code" class="col-sm-2 control-label"><?php _e('Alias Shortcode', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-alias_code" name="<?php echo $this->domain_name; ?>[alias_code]" type="text" value="<?php if (isset($this_tab_vars['alias_code'])) echo esc_textarea(stripslashes_deep($this_tab_vars['alias_code'])); ?>" class="form-control" readonly>
        </div>
      </div>
      
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
