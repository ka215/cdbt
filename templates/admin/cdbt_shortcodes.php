<?php
/**
 * Template : Shortcodes Management Page
 * URL: `/wp-admin/admin.php?page=cdbt_shortcodes`
 *
 * @since 2.0.0
 *
 */

/**
 * Define the various localized variables for rendering
 */
$options = get_option($this->domain_name);
$tabs = [
  'shortcode_list' => __('Shortcode List', CDBT), 
  'shortcode_register' => __('Shortcode register', CDBT), 
  'shortcode_edit' => __('Edit Shortcode', CDBT), 
];
$default_tab = 'shortcode_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;

foreach ($this->cdbt_sessions as $_session_key => $_val) {
  if ($current_tab !== $_session_key) 
    $this->destroy_session($_session_key);
}
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
      <?php _e('This shortcode list has been managing in the plugin. Your newly created shortcodes, you can use very conveniently by registering to this plugin.', CDBT); /* プラグインが管理しているショートコードの一覧です。新たに作成したショートコードは、登録することで簡単に再利用できるようになります。 */?>
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
//var_dump($this_tab_vars);
  
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
      <?php _e('Here will create a new shortcode. Please enter the following item.', CDBT); /* 新しいショートコードを作成します。下記の項目を入力してください。 */?>
    </p>
  </div>
  
  <div class="cdbt-register-shortcode">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="register_shortcode">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <div class="form-group">
        <label for="register-shortcode-base_name" class="col-sm-2 control-label"><?php _e('Base shortcode name', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
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
      <div class="form-group">
        <label for="register-shortcode-target_table" class="col-sm-2 control-label"><?php _e('Target table name', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
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
      
      <div class="clearfix"><br></div>
      <h4 class="title" id="advanced-settings"><i class="fa fa-cogs text-muted"></i> <?php _e('Advanced setting of shortcode', CDBT); ?></h4>
      
      <div class="sr-only switching-item on-e">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[entry_page]" value=""><!-- entry_page [e] -->
      </div>
      
      <div class="form-group toggle-group">
        <label for="register-shortcode-look_feel" class="col-sm-2 control-label"><?php _e('Toggle of look and feel', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox switching-item on-v on-i on-e" id="register-shortcode-look_feel1"><!-- bootstrap_style [v,i,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][bootstrap_style]" type="checkbox" value="1"<?php if (isset($this_tab_vars['bootstrap_style']) && $this_tab_vars['bootstrap_style']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Use bootstrap style; If false is output by the static table tag layout in non the Repeater format. Also does not have any pagination when false.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-e" id="register-shortcode-look_feel2"><!-- display_list_num [v,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_list_num]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_list_num']) && $this_tab_vars['display_list_num']) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Display list number; The default value has changed to false from v2.0.0', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="register-shortcode-look_feel3"><!-- display_search [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_search]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_search']) && $this_tab_vars['display_search']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Display search; Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-i on-e" id="register-shortcode-look_feel4"><!-- display_title [v,i,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_title]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_title']) && $this_tab_vars['display_title']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Display title', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-e" id="register-shortcode-look_feel5"><!-- enable_sort [v,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][enable_sort]" type="checkbox" value="1"<?php if (isset($this_tab_vars['enable_sort']) && $this_tab_vars['enable_sort']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Enable sort; Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="register-shortcode-look_feel6"><!-- display_index_row [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_index_row]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_index_row']) && $this_tab_vars['display_index_row']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Display index row', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /register-shortcode-look_feel -->
      
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-exclude_cols" class="col-sm-2 control-label"><?php _e('Exclude Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-exclude_cols" name="<?php echo $this->domain_name; ?>[exclude_cols]" type="text" value="<?php if (isset($this_tab_vars['exclude_cols'])) echo $this_tab_vars['exclude_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('String as array (not assoc); For example `col1,col2,col3,...`', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-exclude_cols [v,e] -->
      <div class="form-group switching-item on-v on-i on-e">
        <label for="register-shortcode-add_class" class="col-sm-2 control-label"><?php _e('Add Classes', CDBT); ?></label>
        <div class="col-sm-7">
          <input id="register-shortcode-add_class" name="<?php echo $this->domain_name; ?>[add_class]" type="text" value="<?php if (isset($this_tab_vars['add_class'])) echo $this_tab_vars['add_class']; ?>" class="form-control" placeholder="class1 class2 class3 ...">
          <p class="help-block"><?php _e('Separator is a single-byte space character', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-add_class [v,i,e] -->
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-narrow_keyword" class="col-sm-2 control-label"><?php _e('Narrow Keywords', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-narrow_keyword" name="<?php echo $this->domain_name; ?>[narrow_keyword]" type="text" value="<?php if (isset($this_tab_vars['narrow_keyword'])) echo $this_tab_vars['narrow_keyword']; ?>" class="form-control" placeholder="keyword1,keyword2,... or col1:keyword1,col2:keyword2,...">
          <p class="help-block"><?php _e('String as array (not assoc) is `find_data()`; For example `keyword1,keyword2,...` Or String as hash is `get_data()`; For example `col1:keyword1,col2:keyword2,...`', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-narrow_keyword [v] -->
      <div class="form-group switching-item on-i">
        <label for="register-shortcode-hidden_cols" class="col-sm-2 control-label"><?php _e('Hidden Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-hidden_cols" name="<?php echo $this->domain_name; ?>[hidden_cols]" type="text" value="<?php if (isset($this_tab_vars['hidden_cols'])) echo $this_tab_vars['hidden_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('String as array (not assoc); For example `col1,col2,col3,...`', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-hidden_cols [i] -->
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-display_cols" class="col-sm-2 control-label"><?php _e('Display Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-display_cols" name="<?php echo $this->domain_name; ?>[display_cols]" type="text" value="<?php if (isset($this_tab_vars['display_cols'])) echo $this_tab_vars['display_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('String as array (not assoc); For example `col1,col2,col3,...` If overlapped with `exclude_cols`, set to override the `exclude_cols`.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-display_cols [v] -->
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-order_cols" class="col-sm-2 control-label"><?php _e('Order Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-order_cols" name="<?php echo $this->domain_name; ?>[order_cols]" type="text" value="<?php if (isset($this_tab_vars['order_cols'])) echo $this_tab_vars['order_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('String as array (not assoc); For example `col3,col2,col1,...` If overlapped with `display_cols`, set to override the `display_cols`.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-order_cols [v] -->
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-sort_order" class="col-sm-2 control-label"><?php _e('Sort Order', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-sort_order" name="<?php echo $this->domain_name; ?>[sort_order]" type="text" value="<?php if (isset($this_tab_vars['sort_order'])) : echo $this_tab_vars['sort_order']; else : echo 'created:desc'; endif; ?>" class="form-control" placeholder="updated:desc,ID:asc,...">
          <p class="help-block"><?php _e('String as hash for example `updated:desc,ID:asc,...`', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-sort_order [v] -->

      <div class="form-group switching-item on-v">
        <label for="register-shortcode-limit_items" class="col-sm-2 control-label"><?php _e('Limit Items', CDBT); ?></label>
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
      
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-image_render" class="col-sm-2 control-label"><?php _e('Render image type', CDBT); ?></label>
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
          <p class="help-block"><?php _e('class name for directly image render: `rounded`, `circle`, `thumbnail`, `responsive`, (until `minimum`, `modal` )', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-image_render [v] -->

      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-display_filter" class="col-sm-2 control-label"><?php _e('Display filter', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="register-shortcode-display_filter">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_filter]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_filter'])) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- register-shortcode-display_filter [v,e] -->
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-filters" class="col-sm-2 control-label"><?php _e('Filters definition', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-filters" name="<?php echo $this->domain_name; ?>[filters]" type="text" value="<?php if (isset($this_tab_vars['filters'])) echo $this_tab_vars['filters']; ?>" class="form-control" placeholder="filter1:label1,filter2:label2,...">
          <p class="help-block"><?php _e('String as array (assoc); For example `filter1:label1,filter2:label2,...`', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-filters [v,e] -->
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-display_view" class="col-sm-2 control-label"><?php _e('Display view', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="register-shortcode-display_view">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_view]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_view'])) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- register-shortcode-display_view [v] -->
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-thumbnail_column" class="col-sm-2 control-label"><?php _e('Thumbnail column', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="register-shortcode-thumbnail_column" name="<?php echo $this->domain_name; ?>[thumbnail_column]" type="text" value="<?php if (isset($this_tab_vars['thumbnail_column'])) echo $this_tab_vars['thumbnail_column']; ?>" class="form-control" placeholder="">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('Column name to be used as a thumbnail image (image binary or a URL of image must be stored in this column)', CDBT); ?></p>
      </div><!-- /register-shortcode-thumbnail_column [v] -->
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-thumbnail_title_column" class="col-sm-2 control-label"><?php _e('Thumbnail title column', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="register-shortcode-thumbnail_title_column" name="<?php echo $this->domain_name; ?>[thumbnail_title_column]" type="text" value="<?php if (isset($this_tab_vars['thumbnail_title_column'])) echo $this_tab_vars['thumbnail_title_column']; ?>" class="form-control" placeholder="">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('Column name to be used as a thumbnail title', CDBT); ?></p>
      </div><!-- /register-shortcode-thumbnail_title_column [v] -->
      <div class="form-group switching-item on-v">
        <label for="register-shortcode-thumbnail_width" class="col-sm-2 control-label"><?php _e('Thumbnail width', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="register-shortcode-thumbnail_width">
            <input type="text" name="<?php echo $this->domain_name; ?>[thumbnail_width]" value="<?php if (isset($this_tab_vars['thumbnail_width'])) : echo intval($this_tab_vars['thumbnail_width']); else : echo 100; endif; ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('Integer of thumbnail block size.', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-thumbnail_width [v] -->
      <div class="form-group switching-item on-v on-e">
        <label for="register-shortcode-ajax_load" class="col-sm-2 control-label"><?php _e('Ajax load', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="register-shortcode-ajax_load">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[ajax_load]" type="checkbox" value="1"<?php if (isset($this_tab_vars['ajax_load'])) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- register-shortcode-ajax_load [v,e] -->
      
      <div class="form-group switching-item on-i">
        <label for="register-shortcode-action_url" class="col-sm-2 control-label"><?php _e('Action url', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-action_url" name="<?php echo $this->domain_name; ?>[action_url]" type="text" value="<?php if (isset($this_tab_vars['action_url'])) echo $this_tab_vars['action_url']; ?>" class="form-control" placeholder="">
          <p class="help-block"><?php _e('String of url for form action [optional] For using shortcode of `cdbt-edit`', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-action_url [i] -->
      <div class="form-group switching-item on-i">
        <label for="register-shortcode-form_action" class="col-sm-2 control-label"><?php _e('Render image type', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="register-shortcode-form_action">
            <input type="text" name="<?php echo $this->domain_name; ?>[form_action]" value="<?php if (isset($this_tab_vars['form_action'])) : echo $this_tab_vars['form_action']; else : echo 'entry_data'; endif; ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ([ 'entry_data', 'edit_data' ] as $i => $_action) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $_action; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php _e('String of action name as method after submiting [optional] Is `edit_data` if edit data', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-display_submit [i] -->
      <div class="form-group switching-item on-i">
        <label for="register-shortcode-display_submit" class="col-sm-2 control-label"><?php _e('Display submit', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="register-shortcode-display_submit">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_submit]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_submit'])) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Boolean [optional] For using shortcode of `cdbt-edit`', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- register-shortcode-display_submit [i] -->
      <div class="form-group switching-item on-i">
        <label for="register-shortcode-where_clause" class="col-sm-2 control-label"><?php _e('Where clause', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-where_clause" name="<?php echo $this->domain_name; ?>[where_clause]" type="text" value="<?php if (isset($this_tab_vars['where_clause'])) echo $this_tab_vars['where_clause']; ?>" class="form-control" placeholder="col1:value1,col2:value2,...">
          <p class="help-block"><?php _e('String as array (assoc); For example `col1:value1,col2:value2,...`, For using shortcode of `cdbt-edit`', CDBT); ?></p>
        </div>
      </div><!-- /register-shortcode-where_clause [i] -->
      
      <div class="form-group">
        <label for="register-shortcode-description" class="col-sm-2 control-label"><?php _e('Description', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="register-shortcode-description" name="<?php echo $this->domain_name; ?>[description]" class="form-control" rows="2" placeholder="Enter description as meno"><?php if (isset($this_tab_vars['description'])) echo esc_textarea(stripslashes_deep($this_tab_vars['description'])); ?></textarea>
          <p class="help-block"><?php _e('Please enter as like description of shortcode that will be displayed in the list screen.', CDBT); /* 一覧画面に表示されるショートコードの説明文などを入力してください。 */ ?></p>
        </div>
      </div><!-- /register-shortcode-description -->
      
      <div class="form-group switching-item on-v on-i on-e">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[csid]" value="<?php echo $current_csid; ?>"><!-- Valid value of "Custom Shortcode ID" is 1 or more integer. [v,i,e] -->
        <input type="hidden" name="<?php echo $this->domain_name; ?>[author]" value="<?php echo $user_ID; ?>"><!-- Current user ID -->
      </div>
      
      <div class="clearfix"><br></div>
      <h4 class="title" id="confirm-shortcode"><i class="fa fa-eye text-muted"></i> <?php _e('Generated shortcode confirmation', CDBT); ?></h4>
      
      <div class="form-group">
        <label for="register-shortcode-generate_shortcode" class="col-sm-2 control-label"><?php _e('Generated Shortcode', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="register-shortcode-generate_shortcode" name="<?php echo $this->domain_name; ?>[generate_shortcode]" class="form-control" rows="5" readonly><?php if (isset($this_tab_vars['generate_shortcode'])) echo esc_textarea(stripslashes_deep($this_tab_vars['generate_shortcode'])); ?></textarea>
        </div>
      </div>
      <div class="form-group">
        <label for="register-shortcode-alias_code" class="col-sm-2 control-label"><?php _e('Alias Shortcode', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="register-shortcode-alias_code" name="<?php echo $this->domain_name; ?>[alias_code]" type="text" value="<?php if (isset($this_tab_vars['alias_code'])) echo esc_textarea(stripslashes_deep($this_tab_vars['alias_code'])); ?>" class="form-control" readonly>
        </div>
      </div>
      
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
    $_wall_message = sprintf(__('Please select a custom shortcode that you want to edit %sat the shortcode list%s.', CDBT), '<a href="'. add_query_arg('tab', 'shortcode_list') .'">', '</a>'); /* %sショートコード一覧%sで編集するカスタムショートコードを選択してください。 */
  }
  if (!isset($_wall_message) && !in_array(get_current_user_id(), [ 0, $this_tab_vars['author'] ])) {
    $_wall_message = __('Custom shortcode can not edit other than the registrant or privilege administrator.', CDBT); /* カスタムショートコードは登録者か特権管理者以外は編集できません。 */
  }
  
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
      <?php _e('You can edit the settings of custom shortcode at here.', CDBT); /* ここではカスタムショートコードの設定を編集できます。 */?>
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
        <label for="edit-shortcode-target_table" class="col-sm-2 control-label"><?php _e('Target table name', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
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
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][bootstrap_style]" type="checkbox" value="1"<?php if (isset($this_tab_vars['bootstrap_style']) && $this_tab_vars['bootstrap_style']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Use bootstrap style; If false is output by the static table tag layout in non the Repeater format. Also does not have any pagination when false.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-e" id="edit-shortcode-look_feel2"><!-- display_list_num [v,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_list_num]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_list_num']) && $this_tab_vars['display_list_num']) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Display list number; The default value has changed to false from v2.0.0', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="edit-shortcode-look_feel3"><!-- display_search [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_search]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_search']) && $this_tab_vars['display_search']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Display search; Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-i on-e" id="edit-shortcode-look_feel4"><!-- display_title [v,i,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_title]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_title']) && $this_tab_vars['display_title']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Display title', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v on-e" id="edit-shortcode-look_feel5"><!-- enable_sort [v,e] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][enable_sort]" type="checkbox" value="1"<?php if (isset($this_tab_vars['enable_sort']) && $this_tab_vars['enable_sort']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Enable sort; Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox switching-item on-v" id="edit-shortcode-look_feel6"><!-- display_index_row [v] -->
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[look_feel][display_index_row]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_index_row']) && $this_tab_vars['display_index_row']) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Display index row', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /edit-shortcode-look_feel -->
      
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-exclude_cols" class="col-sm-2 control-label"><?php _e('Exclude Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-exclude_cols" name="<?php echo $this->domain_name; ?>[exclude_cols]" type="text" value="<?php if (isset($this_tab_vars['exclude_cols'])) echo $this_tab_vars['exclude_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('String as array (not assoc); For example `col1,col2,col3,...`', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-exclude_cols [v,e] -->
      <div class="form-group switching-item on-v on-i on-e">
        <label for="edit-shortcode-add_class" class="col-sm-2 control-label"><?php _e('Add Classes', CDBT); ?></label>
        <div class="col-sm-7">
          <input id="edit-shortcode-add_class" name="<?php echo $this->domain_name; ?>[add_class]" type="text" value="<?php if (isset($this_tab_vars['add_class'])) echo $this_tab_vars['add_class']; ?>" class="form-control" placeholder="class1 class2 class3 ...">
          <p class="help-block"><?php _e('Separator is a single-byte space character', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-add_class [v,i,e] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-narrow_keyword" class="col-sm-2 control-label"><?php _e('Narrow Keywords', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-narrow_keyword" name="<?php echo $this->domain_name; ?>[narrow_keyword]" type="text" value="<?php if (isset($this_tab_vars['narrow_keyword'])) echo $this_tab_vars['narrow_keyword']; ?>" class="form-control" placeholder="keyword1,keyword2,... or col1:keyword1,col2:keyword2,...">
          <p class="help-block"><?php _e('String as array (not assoc) is `find_data()`; For example `keyword1,keyword2,...` Or String as hash is `get_data()`; For example `col1:keyword1,col2:keyword2,...`', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-narrow_keyword [v] -->
      <div class="form-group switching-item on-i">
        <label for="edit-shortcode-hidden_cols" class="col-sm-2 control-label"><?php _e('Hidden Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-hidden_cols" name="<?php echo $this->domain_name; ?>[hidden_cols]" type="text" value="<?php if (isset($this_tab_vars['hidden_cols'])) echo $this_tab_vars['hidden_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('String as array (not assoc); For example `col1,col2,col3,...`', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-hidden_cols [i] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-display_cols" class="col-sm-2 control-label"><?php _e('Display Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-display_cols" name="<?php echo $this->domain_name; ?>[display_cols]" type="text" value="<?php if (isset($this_tab_vars['display_cols'])) echo $this_tab_vars['display_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('String as array (not assoc); For example `col1,col2,col3,...` If overlapped with `exclude_cols`, set to override the `exclude_cols`.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-display_cols [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-order_cols" class="col-sm-2 control-label"><?php _e('Order Columns', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-order_cols" name="<?php echo $this->domain_name; ?>[order_cols]" type="text" value="<?php if (isset($this_tab_vars['order_cols'])) echo $this_tab_vars['order_cols']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('String as array (not assoc); For example `col3,col2,col1,...` If overlapped with `display_cols`, set to override the `display_cols`.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-order_cols [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-sort_order" class="col-sm-2 control-label"><?php _e('Sort Order', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-sort_order" name="<?php echo $this->domain_name; ?>[sort_order]" type="text" value="<?php if (isset($this_tab_vars['sort_order'])) : echo $this_tab_vars['sort_order']; else : echo 'created:desc'; endif; ?>" class="form-control" placeholder="updated:desc,ID:asc,...">
          <p class="help-block"><?php _e('String as hash for example `updated:desc,ID:asc,...`', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-sort_order [v] -->

      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-limit_items" class="col-sm-2 control-label"><?php _e('Limit Items', CDBT); ?></label>
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
      
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-image_render" class="col-sm-2 control-label"><?php _e('Render image type', CDBT); ?></label>
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
          <p class="help-block"><?php _e('class name for directly image render: `rounded`, `circle`, `thumbnail`, `responsive`, (until `minimum`, `modal` )', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-image_render [v] -->

      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-display_filter" class="col-sm-2 control-label"><?php _e('Display filter', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="edit-shortcode-display_filter">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_filter]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_filter'])) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- edit-shortcode-display_filter [v,e] -->
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-filters" class="col-sm-2 control-label"><?php _e('Filters definition', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-filters" name="<?php echo $this->domain_name; ?>[filters]" type="text" value="<?php if (isset($this_tab_vars['filters'])) echo $this_tab_vars['filters']; ?>" class="form-control" placeholder="filter1:label1,filter2:label2,...">
          <p class="help-block"><?php _e('String as array (assoc); For example `filter1:label1,filter2:label2,...`', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-filters [v,e] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-display_view" class="col-sm-2 control-label"><?php _e('Display view', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="edit-shortcode-display_view">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_view]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_view'])) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- edit-shortcode-display_view [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-thumbnail_column" class="col-sm-2 control-label"><?php _e('Thumbnail column', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="edit-shortcode-thumbnail_column" name="<?php echo $this->domain_name; ?>[thumbnail_column]" type="text" value="<?php if (isset($this_tab_vars['thumbnail_column'])) echo $this_tab_vars['thumbnail_column']; ?>" class="form-control" placeholder="">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('Column name to be used as a thumbnail image (image binary or a URL of image must be stored in this column)', CDBT); ?></p>
      </div><!-- /edit-shortcode-thumbnail_column [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-thumbnail_title_column" class="col-sm-2 control-label"><?php _e('Thumbnail title column', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="edit-shortcode-thumbnail_title_column" name="<?php echo $this->domain_name; ?>[thumbnail_title_column]" type="text" value="<?php if (isset($this_tab_vars['thumbnail_title_column'])) echo $this_tab_vars['thumbnail_title_column']; ?>" class="form-control" placeholder="">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('Column name to be used as a thumbnail title', CDBT); ?></p>
      </div><!-- /edit-shortcode-thumbnail_title_column [v] -->
      <div class="form-group switching-item on-v">
        <label for="edit-shortcode-thumbnail_width" class="col-sm-2 control-label"><?php _e('Thumbnail width', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="edit-shortcode-thumbnail_width">
            <input type="text" name="<?php echo $this->domain_name; ?>[thumbnail_width]" value="<?php if (isset($this_tab_vars['thumbnail_width'])) : echo intval($this_tab_vars['thumbnail_width']); else : echo 100; endif; ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('Integer of thumbnail block size.', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-thumbnail_width [v] -->
      <div class="form-group switching-item on-v on-e">
        <label for="edit-shortcode-ajax_load" class="col-sm-2 control-label"><?php _e('Ajax load', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="edit-shortcode-ajax_load">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[ajax_load]" type="checkbox" value="1"<?php if (isset($this_tab_vars['ajax_load'])) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Is enabled only if "bootstrap_style" is true.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- edit-shortcode-ajax_load [v,e] -->
      
      <div class="form-group switching-item on-i">
        <label for="edit-shortcode-action_url" class="col-sm-2 control-label"><?php _e('Action url', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-action_url" name="<?php echo $this->domain_name; ?>[action_url]" type="text" value="<?php if (isset($this_tab_vars['action_url'])) echo $this_tab_vars['action_url']; ?>" class="form-control" placeholder="">
          <p class="help-block"><?php _e('String of url for form action [optional] For using shortcode of `cdbt-edit`', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-action_url [i] -->
      <div class="form-group switching-item on-i">
        <label for="edit-shortcode-form_action" class="col-sm-2 control-label"><?php _e('Render image type', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="edit-shortcode-form_action">
            <input type="text" name="<?php echo $this->domain_name; ?>[form_action]" value="<?php if (isset($this_tab_vars['form_action'])) : echo $this_tab_vars['form_action']; else : echo 'entry_data'; endif; ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ([ 'entry_data', 'edit_data' ] as $i => $_action) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $_action; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php _e('String of action name as method after submiting [optional] Is `edit_data` if edit data', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-display_submit [i] -->
      <div class="form-group switching-item on-i">
        <label for="edit-shortcode-display_submit" class="col-sm-2 control-label"><?php _e('Display submit', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="edit-shortcode-display_submit">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_submit]" type="checkbox" value="1"<?php if (isset($this_tab_vars['display_submit'])) : ?> checked="checked"<?php else : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Boolean [optional] For using shortcode of `cdbt-edit`', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- edit-shortcode-display_submit [i] -->
      <div class="form-group switching-item on-i">
        <label for="edit-shortcode-where_clause" class="col-sm-2 control-label"><?php _e('Where clause', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-shortcode-where_clause" name="<?php echo $this->domain_name; ?>[where_clause]" type="text" value="<?php if (isset($this_tab_vars['where_clause'])) echo $this_tab_vars['where_clause']; ?>" class="form-control" placeholder="col1:value1,col2:value2,...">
          <p class="help-block"><?php _e('String as array (assoc); For example `col1:value1,col2:value2,...`, For using shortcode of `cdbt-edit`', CDBT); ?></p>
        </div>
      </div><!-- /edit-shortcode-where_clause [i] -->
      
      <div class="form-group">
        <label for="edit-shortcode-description" class="col-sm-2 control-label"><?php _e('Description', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="edit-shortcode-description" name="<?php echo $this->domain_name; ?>[description]" class="form-control" rows="2" placeholder="Enter description as meno"><?php if (isset($this_tab_vars['description'])) echo esc_textarea(stripslashes_deep($this_tab_vars['description'])); ?></textarea>
          <p class="help-block"><?php _e('Please enter as like description of shortcode that will be displayed in the list screen.', CDBT); /* 一覧画面に表示されるショートコードの説明文などを入力してください。 */ ?></p>
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
          <button type="submit" class="btn btn-primary"><?php _e('Save Shortcode', CDBT); ?></button>
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
      <?php echo $_wall_message; ?>
    </p>
  </div>
<?php }
endif; ?>
  
</div><!-- /.wrap -->
