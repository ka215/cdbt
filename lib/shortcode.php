<?php

namespace CustomDataBaseTables\Lib;


/**
 * Trait of shortcode difinitions for this plugin 
 *
 * @since 2.0.0
 *
 */
trait CdbtShortcodes {
  
  private $shortcodes;
  
  /**
   * 
   *
   * @since 2.0.0
   **/
  protected function shortcode_register() {
    
    $this->shortcodes = [
      'cdbt-view'     => 'view_data_list', 
      'cdbt-entry'    => 'entry_data_form', 
      'cdbt-edit'      => 'editable_data_list', 
      'cdbt-extract'  => 'view_data_list', // Deprecated from v2.0.0; It has been merged into `cdbt-view`
      'cdbt-submit'  => 'submit_custom_query', // Deprecated from v2.0.0
    ];
    foreach ($this->shortcodes as $shortcode_name => $method_name) {
      if (method_exists($this, $method_name)) 
        add_shortcode( $shortcode_name, array($this, $method_name) );
    }
    
  }
  
  
  /**
   * 
   *
   * @since 2.0.0
   *
   * @param string $table_name [require]
   * @return 
   **/
  
  
  
  
  /**
   * Retrieve a table data that match the specified conditions, then it outputs as list
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param array $attributes [require] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The formatted content as list
   **/
  public function view_data_list() {
    list($attributes, $content) = func_get_args();
    extract( shortcode_atts([
      'table' => '', // Required attribute
      'bootstrap_style' => true, // If false is output by the static table tag layout in non the Repeater format. Also does not have any pagination when false.
      'display_list_num' => false, // The default value has changed to false from v2.0.0
      'display_search' => true, // Is enabled only if "bootstrap_style" is true.
      'display_title' => true, 
      'enable_sort' => true, //  Is enabled only if "bootstrap_style" is true.
      'exclude_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...`
      'add_class' => '', // Separator is a single-byte space character
      // As legacy of `cdbt-extract` is follows:
      'display_index_row' => true, 
      'narrow_keyword' => '', // String as array (not assoc) is `find_data()`; For example `keyword1,keyword2,...` Or String as hash is `get_data()`; For example `col1:keyword1,col2:keyword2,...`
      'display_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...` If overlapped with `exclude_cols`, set to override the `exclude_cols`.
      'order_cols' => '', // String as array (not assoc); For example `col3,col2,col1,...` If overlapped with `display_cols`, set to override the `display_cols`.
      'sort_order' => 'created:desc', // String as hash for example `updated:desc,ID:asc,...`
      'limit_items' => '', // The default value is overwritten by the value of the max_show_records of the specified table.
      'image_render' => 'responsive', // class name for directly image render: 'rounded', 'circle', 'thumbnail', 'responsive', (until 'minimum', 'modal' )
      // Added new attribute from 2.0.0 is follows:
      'display_filter' => false, // Is enabled only if "bootstrap_style" is true.
      'filters' => '', // String as array (assoc); For example `filter1:label1,filter2:label2,...`
      'display_view' => false, //  Is enabled only if "bootstrap_style" is true.
      'thumbnail_column' => '', // Column name to be used as a thumbnail image (image binary or a URL of image must be stored in this column)
      'thumbnail_title_column' => '', // Column name to be used as a thumbnail title
      'thumbnail_width' => 100, // Integer of thumbnail block size
      'ajax_load' => false, // Is enabled only if "bootstrap_style" is true.
      'csid' => 0, // Valid value of "Custom Shortcode ID" is 1 or more integer. 
    ], $attributes) );
    if (empty($table) || !$this->check_table_exists($table)) 
      return;
    
    // Initialization process for the shortcode
    $shortcode_name = 'cdbt-view';
    $table_schema = $this->get_table_schema($table);
    $table_option = $this->get_table_option($table);
    $has_bin = [];
    if (false !== $table_option) {
      $table_type = $table_option['table_type'];
      $has_pk = !empty($table_option['primary_key']) ? true : false;
      $limit_items = empty($limit_items) || intval($limit_items) < 1 ? intval($table_option['show_max_records']) : intval($limit_items);
      foreach ($table_schema as $column => $scheme) {
        if ($this->validate->check_column_type($scheme['type'], 'blob')) 
          $has_bin[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'list')) 
          $has_list[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'binary')) 
          $has_bit[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'datetime')) 
        	$has_datetime[] = $column;
        
      }
    } else {
      if (in_array($table, $this->core_tables)) 
        $table_type = 'wp_core';
      
      $has_pk = false;
      foreach ($table_schema as $column => $scheme) {
        if ($scheme['primary_key']) {
          $has_pk = true;
          break;
        }
        if ($this->validate->check_column_type($scheme['type'], 'blob')) {
          $has_bin[] = $column;
        }
      }
      $limit_items = empty($limit_items) || intval($limit_items) < 1 ? intval($this->options['default_per_records']) : intval($limit_items);
    }
    $content = '';
    
    // Check user permission
    $result_permit = false;
    if (isset($table_option['permission']) && isset($table_option['permission']['view_global']) && !empty($table_option['permission']['view_global'])) {
      // Standard from v2.0.0
      $result_permit = $this->is_permit_user($table_option['permission']['view_global']);
    } else
    if (isset($table_option['roles']) && isset($table_option['roles']['view_role'])) {
      // As legacy v.1.x
      foreach(array_reverse($this->user_roles) as $role_name) {
        $_role = get_role($role_name);
        if (is_object($_role) && array_key_exists('level_' . $table_option['roles']['view_role'], $_role->capabilities)) {
          $check_role = $_role->name;
          break;
        }
      }
      $result_permit = $this->is_permit_user( $check_role );
    } else
    if ('wp_core' === $table_type) {
      // If WordPress core tables
      $result_permit = $this->is_permit_user( 'administrator' );
    }
    //
    // Filter the viewing rights check result of the shortcode
    // You can give viewing rights to specific users by utilizing this filter hook.
    //
    $result_permit = apply_filters( 'cdbt_after_shortcode_permit', $result_permit, $shortcode_name, $table );
    if (!$result_permit) 
      return sprintf('<p>%s</p>', __('You do not have viewing permits of this content.', CDBT));
    
    
    // Validation of the attributes, then sanitizing
    $boolean_atts = [ 'bootstrap_style', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'display_index_row', 'display_filter', 'ajax_load' ];
    foreach ($boolean_atts as $attribute_name) {
      ${$attribute_name} = $this->strtobool(${$attribute_name});
    }
    $not_assoc_atts = [ 'exclude_cols', 'display_cols', 'order_cols' ];
    foreach ($not_assoc_atts as $attribute_name) {
      ${$attribute_name} = $this->strtoarray(${$attribute_name});
    }
    $hash_atts = [ 'narrow_keyword', 'sort_order', 'filters' ];
    foreach ($hash_atts as $attribute_name) {
      ${$attribute_name} = $this->strtohash(${$attribute_name});
    }
    if (!empty($add_class)) {
      $add_classes = [];
      foreach (explode(' ', $add_class) as $_class) {
        $add_classes[] = esc_attr(trim($_class));
      }
      $add_class = implode(' ', $add_classes);
    }
    if (!empty($image_render) && !in_array(strtolower($image_render), [ 'rounded', 'circle', 'thumbnail', 'responsive' ])) {
      $image_render = 'responsive';
    } else {
      $image_render = strtolower($image_render);
    }
    if ($this->validate->checkInt($csid)) {
      // csidに対応したショートコードが存在するかのチェックを行う
    } else {
      $csid = 0;
    }
    if ($display_title) {
      $disp_title = $this->get_table_comment($table);
      $disp_title = !empty($disp_title) ? $disp_title : $table;
      $title = '<h4 class="sub-description-title">' . sprintf( __('View Data in "%s" Table', CDBT), $disp_title ) . '</h4>';
    }
    
    $datasource = $this->get_data($table, 'ARRAY_A');
    if (empty($datasource))
      return sprintf('<p>%s</p>', __('Data in this table does not exist.', CDBT));
    
    $custom_column_renderer = [];
    
    // If contain binary data in the datasource
    if (!empty($has_bin)) {
      $custom_row_scripts = [];
      foreach ($datasource as $i => $row_data) {
        foreach ($has_bin as $col_name) {
          if (array_key_exists($col_name, $row_data)) {
            if ('image' === $this->check_binary_data($row_data[$col_name])) {
              $row_data[$col_name] = sprintf('data:%s;base64,%s', $this->esc_binary_data($row_data[$col_name], 'mime_type'), $this->esc_binary_data($row_data[$col_name], 'bin_data') );
              $custom_column_renderer[$col_name] = 'rowData.'. $col_name .' !== false ? \'<a href="#" class="modal-preview"><img src="\' + rowData.'. $col_name .' + \'" class="img-'. $image_render .'"></a>\' : \'\'';
              // $row_data[$col_name] = $this->esc_binary_data( $row_data[$col_name], 'origin_file' );
              // $custom_column_renderer[$col_name] = 'rowData.'. $col_name .' !== false ? \'<div class="lazy-loading-image"><input type="hidden"value="\' + rowData.'. $col_name .' + \'"></div>\' : \'\'';
              if (is_admin() && empty($thumbnail_column)) {
                $display_view = true;
                $thumbnail_column = $col_name;
                $custom_row_scripts[] = sprintf( 'helpers.rowData.%s = !helpers.rowData.%s ? \'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==\' : helpers.rowData.%s;', $col_name, $col_name, $col_name);
              }
            } else {
              $row_data[$col_name] = $this->esc_binary_data( $row_data[$col_name], 'origin_file' );
              // $custom_column_renderer[$col_name] = 'rowData.'. $col_name .' !== false ? \'<a href="#" class="modal-download">rowData.'. $col_name .'</a>\' : \'<img src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==">\'';
            }
            $datasource[$i] = $row_data;
          } else {
            $custom_column_renderer[$col_name] = '';
          }
        }
      }
    }
    
    // If contain list type columns
    if (!empty($has_list)) {
      $_filter_items = [];
      foreach ($has_list as $column) {
        foreach ($this->parse_list_elements($table_schema[$column]['type_format']) as $list_item) {
          $_filter_items[] = sprintf( '%s:%s', esc_attr($list_item), __($list_item, CDBT) );
        }
        if ('set' === $table_schema[$column]['type']) {
          $custom_column_renderer[$column] = '\'<ul class="list-inline">\' + convert_list(rowData.'. $column .') + \'</ul>\'';
        }
      }
      if ($display_filter && empty($filters)) {
        if (!empty($_filter_items)) 
          $filters = array_unique($_filter_items);
      }
      unset($_filter_items);
    }
    
    // If contain bit binary data in the datasource
    if (!empty($has_bit)) {
      foreach ($has_bit as $column) {
        //
        // Filter whether to use the icon display in the case of outputting the data registered in boolean form
        //
        $bool_data_with_icon = apply_filters( 'cdbt_boolean_data_with_icon', true, $shortcode_name, $table );
        if ($bool_data_with_icon) {
          $custom_column_renderer[$column] = '\'<div class="center-block text-center"><small><i class="\' + (rowData.'. $column .' === \'1\' ? \'fa fa-circle-o\' : \'fa fa-time\' ) + \'"></i><span class="sr-only">\' + rowData.'. $column .' + \'</span></small></div>\'';
        } else {
        	$custom_column_renderer[$column] = '\'<div class="center-block text-center">\' + (rowData.'. $column .' === \'1\' ? \'true\' : \'false\' ) + \'</div>\'';
        }
      }
    }
    
    // If contain datetime data in the datasource
    if (!empty($has_datetime)) {
      foreach ($has_datetime as $column) {
        if (empty($this->options['display_datetime_format'])) {
          $_datetime_format = '[\''. get_option( 'date_format' ) .'\', \''. get_option( 'time_format' ) .'\']';
        } else {
        	$_datetime_format = '[\''. $this->options['display_datetime_format'] .'\']';
        }
        $custom_column_renderer[$column] = '\'<div class="custom-datetime">\' + convert_datetime(rowData.'. $column .', '. $_datetime_format .') + \'</div>\'';
      }
      unset($_datetime_format);
    }
    
    
    if ($bootstrap_style) {
      // Generate repeater
      $columns = [];
      foreach ($table_schema as $column => $scheme) {
        $columns[] = [
          'label' => empty($scheme['logical_name']) ? $column : $scheme['logical_name'], 
          'property' => $column, 
          'sortable' => true, 
          'sortDirection' => 'asc', 
          'dataNumric' => $this->validate->check_column_type( $scheme['type'], 'numeric' ), 
          'className' => '', 
        ];
      }
      
      if (isset($custom_column_renderer) && !empty($custom_column_renderer)) {
        foreach ($columns as $i => $column_definition) {
          if (array_key_exists($column_definition['property'], $custom_column_renderer)) {
            $columns[$i] = array_merge($columns[$i], [ 'customColumnRenderer' => $custom_column_renderer[$column_definition['property']] ]);
          }
        }
        unset($i);
      }
      
      if ('regular' === $table_type && $display_list_num) {
        foreach ($datasource as $i => $datum) {
          $datasource[$i] = array_merge([ 'data-index-number' => $i + 1 ], $datum);
        }
        $add_column = [ 'label' => '#', 'property' => 'data-index-number', 'sortable' => true, 'sortDirection' => 'asc', 'dataNumric' => true, 'width' => 80 ];
        array_unshift($columns, $add_column);
      }
      //
      // Filter the column definition of the list content that is output by this shortcode
      //
      $columns = apply_filters( 'cdbt_shortcode_custom_columns', $columns, $shortcode_name, $table );
      
      $conponent_options = [
        'id' => 'cdbt-repeater-view-' . $table, 
        'enableSearch' => $display_search, 
        'enableFilter' => $display_filter, 
        'filters' => $filters, 
        'enableView' => $display_view, 
        'defaultView' => 'list', 
        'listSelectable' => 'false', 
        'staticHeight' => -1, 
        'pageIndex' => 1, 
        'pageSize' => $limit_items, 
        'columns' => $columns, 
        'data' => $datasource, 
        'addClass' => $add_class, 
      ];
      
      if ($display_view && !empty($thumbnail_column) && array_key_exists($thumbnail_column, $table_schema)) {
        $thumbnail_title = !empty($thumbnail_title_column) ? sprintf('<span>{{%s}}</span>', esc_html($thumbnail_title_column)) : '';
        $thumbnail_template = '\'<div class="thumbnail repeater-thumbnail" style="background: #ffffff;"><img src="{{'. $thumbnail_column .'}}" width="'. intval($thumbnail_width) .'">'. $thumbnail_title .'</div>\'';
        $conponent_options = array_merge($conponent_options, [ 'thumbnailTemplate' => $thumbnail_template ]);
        if (isset($custom_row_scripts) && !empty($custom_row_scripts)) 
          $conponent_options = array_merge($conponent_options, [ 'customRowScripts' => $custom_row_scripts ]);
      }
      //
      // Filter the conponent definition of the list content that is output by this shortcode
      //
      $conponent_options = apply_filters( 'cdbt_shortcode_custom_conponent_options', $conponent_options, $shortcode_name, $table );
      
      if (isset($title)) 
        echo $title;
      
      return $this->component_render('repeater', $conponent_options);
      
    } else {
      // Generate table layout
      
      return $content;
    }
  }
  
  
  /**
   * Render the data registration form for the specified table
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param array $attributes [require] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The created form contents
   **/
  public function entry_data_form() {
    list($attributes, $content) = func_get_args();
    extract( shortcode_atts([
      'table' => '', // Required attribute
      'bootstrap_style' => true, // 
      'display_title' => true, 
      'hidden_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...`
      'add_class' => '', // Separator is a single-byte space character
      // Added new attribute from 2.0.0 is follows:
      'action_url' => '', // String of url for form action [optional] For using shortcode of `cdbt-edit`
      'form_action' => 'entry_data', // String of action name as method after submiting [optional] Is `edit_data` if edit data
      'display_submit' => true, // Boolean [optional] For using shortcode of `cdbt-edit`
      'where_clause' => '', // String as array (assoc); For example `col1:value1,col2:value2,...`, For using shortcode of `cdbt-edit`
      'csid' => 0, // Valid value of "Custom Shortcode ID" is 1 or more integer. 
    ], $attributes) );
    if (empty($table) || !$this->check_table_exists($table)) 
      return;
    
    // Initialization process for the shortcode
    $shortcode_name = 'cdbt-entry';
    $table_schema = $this->get_table_schema($table);
    $table_option = $this->get_table_option($table);
    if (false !== $table_option) {
      $table_type = $table_option['table_type'];
    } else {
      if (in_array($table, $this->core_tables)) 
        $table_type = 'wp_core';
    }
    $content = '';
    
    // Check user permission
    $result_permit = false;
    if (isset($table_option['permission']) && isset($table_option['permission']['entry_global']) && !empty($table_option['permission']['entry_global'])) {
      // Standard from v2.0.0
      $result_permit = $this->is_permit_user($table_option['permission']['entry_global']);
    } else
    if (isset($table_option['roles']) && isset($table_option['roles']['input_role'])) {
      // As legacy v.1.x
      foreach(array_reverse($this->user_roles) as $role_name) {
        $_role = get_role($role_name);
        if (is_object($_role) && array_key_exists('level_' . $table_option['roles']['input_role'], $_role->capabilities)) {
          $check_role = $_role->name;
          break;
        }
      }
      $result_permit = $this->is_permit_user( $check_role );
    } else
    if ('wp_core' === $table_type) {
      // If WordPress core tables
      $result_permit = $this->is_permit_user( 'administrator' );
    }
    //
    // Filter the viewing rights check result of the shortcode
    // You can give viewing rights to specific users by utilizing this filter hook.
    //
    $result_permit = apply_filters( 'cdbt_after_shortcode_permit', $result_permit, $shortcode_name, $table );
    if (!$result_permit) 
      return sprintf('<p>%s</p>', __('You do not have viewing permits of this content.', CDBT));
    
    
    // Validation of the attributes, then sanitizing
    $boolean_atts = [ 'bootstrap_style', 'display_title', 'display_submit' ];
    foreach ($boolean_atts as $attribute_name) {
      ${$attribute_name} = $this->strtobool(${$attribute_name});
    }
    $not_assoc_atts = [ 'hidden_cols' ];
    foreach ($not_assoc_atts as $attribute_name) {
      ${$attribute_name} = $this->strtoarray(${$attribute_name});
    }
    $hash_atts = [ 'where_clause' ];
    foreach ($hash_atts as $attribute_name) {
      ${$attribute_name} = $this->strtohash(${$attribute_name});
    }
    if (!empty($add_class)) {
      $add_classes = [];
      foreach (explode(' ', $add_class) as $_class) {
        $add_classes[] = esc_attr(trim($_class));
      }
      $add_class = implode(' ', $add_classes);
    }
    if ($this->validate->checkInt($csid)) {
      // csidに対応したショートコードが存在するかのチェックを行う
    } else {
      $csid = 0;
    }
    if ($display_title) {
      $disp_title = $this->get_table_comment($table);
      $disp_title = !empty($disp_title) ? $disp_title : $table;
      $title = '<h4 class="sub-description-title">' . sprintf( __('Entry Data to "%s" Table', CDBT), $disp_title ) . '</h4>';
    }
    
    
    $elements_options = [];
    $is_file_upload = false;
    foreach ($table_schema as $column => $scheme) {
      if ( $scheme['primary_key'] && false !== strpos( $scheme['extra'], 'auto_increment' ) ) 
        continue;
      
      $detect_column_type = $this->validate->check_column_type($scheme['type']);
      if( array_key_exists('datetime', $detect_column_type) && 'updated' === $column ) 
        continue;
      
//      var_dump([$column, $detect_column_type, $scheme]);
      unset($input_type, $rows, $max_file_size, $max_length, $element_size, $pattern, $selectable_list);
      if (array_key_exists('char', $detect_column_type)) {
        if (array_key_exists('text', $detect_column_type)) {
          $input_type = 'textarea';
          // $max_length = $scheme['max_length'];
          if ('longtext' === $detect_column_type['text']) {
            $rows = 20;
          } else
          if ('midiumtext' === $detect_column_type['text']) {
            $rows = 15;
          } else
          if ('tinytext' === $detect_column_type['text']) {
            $rows = 5;
          } else {
            $rows = 10;
          }
        } else
        if (array_key_exists('blob', $detect_column_type)) {
          $input_type = 'file';
          $max_file_size = $scheme['max_length'];
          $is_file_upload = true;
        } else {
          $input_type = 'text';
          $max_length = $scheme['max_length'];
        }
      } else
      if (array_key_exists('numeric', $detect_column_type)) {
        if (array_key_exists('integer', $detect_column_type)) {
          $input_type = 'number';
          if ($scheme['unsigned']) 
            $min = 0;
          $element_size = ceil($scheme['max_length'] / 10);
          $pattern = $scheme['unsigned'] ? '^[0-9]+$' : '^(\-|)[0-9]+$';
        } else
        if (array_key_exists('binary', $detect_column_type)) {
          $input_type = 'boolean';
          if (preg_match('/^b\'(.*)\'$/iU', $scheme['default'], $matches) && is_array($matches) && array_key_exists(1, $matches)) {
            $scheme['default'] = $this->strtobool($matches[1]);
          }
        } else {
          $input_type = 'text';
          $element_size = ceil($scheme['max_length'] / 10);
          $pattern = $scheme['unsigned'] ? '^[0-9]{0,}(|\.)[0-9]+$' : '^(\-|)[0-9]{0,}(|\.)[0-9]+$';
        }
      } else
      if (array_key_exists('list', $detect_column_type)) {
        $input_type = 'enum' === $detect_column_type['list'] ? 'select' : 'checkbox';
        $selectable_list = [];
        foreach ($this->parse_list_elements($scheme['type_format']) as $list_value) {
          $selectable_list[] = sprintf( '%s:%s', __($list_value, CDBT), esc_attr($list_value) );
        }
        unset($list_value);
      } else
      if (array_key_exists('datetime', $detect_column_type)) {
        $input_type = 'timestamp' === $detect_column_type['datetime'] ? 'number' : 'datetime';
      } else {
        $input_type = 'text';
      }
//var_dump([$column, $scheme]);
      $_temp_elements_options = [
        'elementName' => $column, 
        'elementLabel' => !empty($scheme['logical_name']) ? $scheme['logical_name'] : $column, 
        'elementType' => $input_type, 
        'isRequired' => $scheme['not_null'], 
        'defaultValue' => !empty($scheme['default']) ? $scheme['default'] : '', 
        'placeholder' => '', 
        'addClass' => '', 
        'selectableList' => isset($selectable_list) && !empty($selectable_list) ? implode(',', $selectable_list) : '', 
        'horizontalList' => false, 
        'elementSize' => isset($element_size) && !empty($element_size) ? $element_size : '', 
        'helperText' => '', 
        'elementExtras' => [], // 'maxlength' => '', 'pattern' => '', 
      ];
      if (isset($max_length) && !empty($max_length)) 
        $_temp_elements_options['elementExtras']['maxlength'] = $max_length;
      if (isset($pattern) && !empty($pattern)) 
        $_temp_elements_options['elementExtras']['pattern'] = $pattern;
      if (isset($rows) && !empty($rows)) 
        $_temp_elements_options['elementExtras']['rows'] = $rows;
      if ('datetime' === $input_type) {
        $_temp_elements_options['elementExtras']['data-moment-locale'] = 'ja';
        $_temp_elements_options['elementExtras']['data-moment-format'] = 'L';
      }
      // Override of initial value to for editing
      if (!empty($where_clause) && is_array($where_clause)) {
        $_current_data = $this->array_flatten($this->get_data( $table, $column, $where_clause, 'ARRAY_A' ));
        $_temp_elements_options['defaultValue'] = $this->validate->esc_column_value( $_current_data[$column], $detect_column_type );
      }
      
      
      $elements_options[] = $_temp_elements_options;
    }
    //
    // Filter the form content definition that is output by this shortcode
    //
    $elements_options = apply_filters( 'cdbt_shortcode_custom_forms', $elements_options, $shortcode_name, $table );
    
    $conponent_options = [
      'id' => 'cdbt-entry-data-to-' . $table, 
      'entryTable' => $table, 
      'useBootstrap' => true, 
      'outputTitle' => isset($title) ? $title : '', 
      'fileUpload' => isset($is_file_upload) ? $is_file_upload : false, 
      'formElements' => $elements_options, 
    ];
    if (!empty($action_url)) 
      $conponent_options['actionUrl'] = $action_url;
    if (!empty($form_action)) 
      $conponent_options['formAction'] = $form_action;
    if (!$display_submit) 
      $conponent_options['displaySubmit'] = $display_submit;
    if (!empty($where_clause) && is_array($where_clause)) 
      $conponent_options['whereClause'] = $where_clause;
    //
    // Filter the conponent definition of the list content that is output by this shortcode
    //
    $conponent_options = apply_filters( 'cdbt_shortcode_custom_conponent_options', $conponent_options, $shortcode_name, $table );
    
    return $this->component_render('forms', $conponent_options);
    
  }
  
  
  /**
   * Render the editable data list for the specified table
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param array $attributes [require] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The created form contents
   **/
  public function editable_data_list() {
    list($attributes, $content) = func_get_args();
    extract( shortcode_atts([
      'table' => '', // Required attribute
      'entry_page' => '', // Deprecated attributes from v2.0.0 (actually not work)
      'bootstrap_style' => true, // If false is output by the static table tag layout in non the Repeater format. Also does not have any pagination when false.
      'display_list_num' => false, // Deprecated attributes, and the default value has changed to false  from v2.0.0 (actually not work)
      'display_title' => true, 
      'enable_sort' => true, //  Is enabled only if "bootstrap_style" is true.
      'exclude_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...`
      'add_class' => '', // Separator is a single-byte space character
//      'display_search' => true, // true is static
//      'display_index_row' => true, // true is static
//      'image_render' => 'responsive', // 'responsive' is static
      // Added new attribute from 2.0.0 is follows:
      'display_filter' => false, // Is enabled only if "bootstrap_style" is true.
      'filters' => '', // String as array (assoc); For example `filter1:label1,filter2:label2,...`
      'ajax_load' => false, // Is enabled only if "bootstrap_style" is true.
      'csid' => 0, // Valid value of "Custom Shortcode ID" is 1 or more integer. 
    ], $attributes) );
    if (empty($table) || !$this->check_table_exists($table)) 
      return;
    
    // Initialization process for the shortcode
    $shortcode_name = 'cdbt-edit';
    $table_schema = $this->get_table_schema($table);
    $table_option = $this->get_table_option($table);
    $has_bin = $has_list = $has_bit = $has_datetime = [];
    if (false !== $table_option) {
      $table_type = $table_option['table_type'];
      $has_pk = !empty($table_option['primary_key']) ? true : false;
      $limit_items = intval($table_option['show_max_records']);
      foreach ($table_schema as $column => $scheme) {
        if ($this->validate->check_column_type($scheme['type'], 'blob')) 
          $has_bin[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'list')) 
          $has_list[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'binary')) 
          $has_bit[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'datetime')) 
        	$has_datetime[] = $column;
        
      }
    } else {
      if (in_array($table, $this->core_tables)) 
        $table_type = 'wp_core';
      
      $has_pk = false;
      foreach ($table_schema as $column => $scheme) {
        if ($scheme['primary_key']) {
          $has_pk = true;
          break;
        }
        if ($this->validate->check_column_type($scheme['type'], 'blob')) {
          $has_bin[] = $column;
        }
      }
      $limit_items = intval($this->options['default_per_records']);
    }
    $content = '';
    
    // Check user permission
    $result_permit = false;
    if (isset($table_option['permission']) && isset($table_option['permission']['eidt_global']) && !empty($table_option['permission']['edit_global'])) {
      // Standard from v2.0.0
      $result_permit = $this->is_permit_user($table_option['permission']['edit_global']);
    } else
    if (isset($table_option['roles']) && isset($table_option['roles']['edit_role'])) {
      // As legacy v.1.x
      foreach(array_reverse($this->user_roles) as $role_name) {
        $_role = get_role($role_name);
        if (is_object($_role) && array_key_exists('level_' . $table_option['roles']['edit_role'], $_role->capabilities)) {
          $check_role = $_role->name;
          break;
        }
      }
      $result_permit = $this->is_permit_user( $check_role );
    } else
    if ('wp_core' === $table_type) {
      // If WordPress core tables
      $result_permit = $this->is_permit_user( 'administrator' );
    }
    //
    // Filter the viewing rights check result of the shortcode
    // You can give viewing rights to specific users by utilizing this filter hook.
    //
    $result_permit = apply_filters( 'cdbt_after_shortcode_permit', $result_permit, $shortcode_name, $table );
    if (!$result_permit) 
      return sprintf('<p>%s</p>', __('You do not have viewing permits of this content.', CDBT));
    
    
    // Validation of the attributes, then sanitizing
    $boolean_atts = [ 'bootstrap_style', 'display_list_num', 'display_title', 'enable_sort', 'display_filter', 'ajax_load' ];
    foreach ($boolean_atts as $attribute_name) {
      ${$attribute_name} = $this->strtobool(${$attribute_name});
    }
    $not_assoc_atts = [ 'exclude_cols' ];
    foreach ($not_assoc_atts as $attribute_name) {
      ${$attribute_name} = $this->strtoarray(${$attribute_name});
    }
    $hash_atts = [ 'filters' ];
    foreach ($hash_atts as $attribute_name) {
      ${$attribute_name} = $this->strtohash(${$attribute_name});
    }
    if (!empty($add_class)) {
      $add_classes = [];
      foreach (explode(' ', $add_class) as $_class) {
        $add_classes[] = esc_attr(trim($_class));
      }
      $add_class = implode(' ', $add_classes);
    }
    $image_render = 'responsive';
    if ($this->validate->checkInt($csid)) {
      // csidに対応したショートコードが存在するかのチェックを行う
    } else {
      $csid = 0;
    }
    if ($display_title) {
      $disp_title = $this->get_table_comment($table);
      $disp_title = !empty($disp_title) ? $disp_title : $table;
      $title = '<h4 class="sub-description-title">' . sprintf( __('Edit Data of "%s" Table', CDBT), $disp_title ) . '</h4>';
    }
    
    $datasource = $this->get_data($table, 'ARRAY_A');
    if (empty($datasource))
      return sprintf('<p>%s</p>', __('Data in this table does not exist.', CDBT));
    
    // If contain binary data in the datasource
    if (!empty($has_bin)) {
      $custom_column_renderer = [];
      foreach ($datasource as $i => $row_data) {
        foreach ($has_bin as $col_name) {
          if (array_key_exists($col_name, $row_data)) {
            if ('image' === $this->check_binary_data($row_data[$col_name])) {
              $row_data[$col_name] = sprintf('data:%s;base64,%s', $this->esc_binary_data($row_data[$col_name], 'mime_type'), $this->esc_binary_data($row_data[$col_name], 'bin_data') );
              $custom_column_renderer[$col_name] = '\'<a href="#" class="modal-preview"><img src="\' + rowData.'. $col_name .' + \'" class="img-'. $image_render .'"></a>\'';
              // $row_data[$col_name] = $this->esc_binary_data( $row_data[$col_name], 'origin_file' );
              // $custom_column_renderer[$col_name] = 'rowData.'. $col_name .' !== false ? \'<div class="lazy-loading-image"><input type="hidden"value="\' + rowData.'. $col_name .' + \'"></div>\' : \'\'';
            } else {
              $_temp = $this->esc_binary_data( $row_data[$col_name], 'origin_file' );
              $row_data[$col_name] = !$_temp ? '' : $_temp;
            }
            $datasource[$i] = $row_data;
          } else {
            $custom_column_renderer[$col_name] = '';
          }
        }
      }
    }
    
    // If contain list type columns
    if (!empty($has_list)) {
      $_filter_items = [];
      foreach ($has_list as $column) {
        foreach ($this->parse_list_elements($table_schema[$column]['type_format']) as $list_item) {
          $_filter_items[] = sprintf( '%s:%s', esc_attr($list_item), __($list_item, CDBT) );
        }
        if ('set' === $table_schema[$column]['type']) {
          $custom_column_renderer[$column] = '\'<ul class="list-inline">\' + convert_list(rowData.'. $column .') + \'</ul>\'';
        }
      }
      if ($display_filter && empty($filters)) {
        if (!empty($_filter_items)) 
          $filters = array_unique($_filter_items);
      }
      unset($_filter_items);
    }
    
    // If contain bit binary data in the datasource
    if (!empty($has_bit)) {
      foreach ($has_bit as $column) {
        //
        // Filter whether to use the icon display in the case of outputting the data registered in boolean form
        //
        $bool_data_with_icon = apply_filters( 'cdbt_boolean_data_with_icon', true, $shortcode_name, $table );
        if ($bool_data_with_icon) {
          $custom_column_renderer[$column] = '\'<div class="center-block text-center"><small><i class="\' + (rowData.'. $column .' === \'1\' ? \'fa fa-circle-o\' : \'fa fa-time\' ) + \'"></i><span class="sr-only">\' + rowData.'. $column .' + \'</span></small></div>\'';
        } else {
        	$custom_column_renderer[$column] = '\'<div class="center-block text-center">\' + (rowData.'. $column .' === \'1\' ? \'true\' : \'false\' ) + \'</div>\'';
        }
      }
    }
    
    // If contain datetime data in the datasource
    if (!empty($has_datetime)) {
      foreach ($has_datetime as $column) {
        if (empty($this->options['display_datetime_format'])) {
          $_datetime_format = '[\''. get_option( 'date_format' ) .'\', \''. get_option( 'time_format' ) .'\']';
        } else {
        	$_datetime_format = '[\''. $this->options['display_datetime_format'] .'\']';
        }
        $custom_column_renderer[$column] = '\'<div class="custom-datetime">\' + convert_datetime(rowData.'. $column .', '. $_datetime_format .') + \'</div>\'';
      }
      unset($_datetime_format);
    }
    
    
    if ($bootstrap_style) {
      // Generate repeater
      $columns = [];
      foreach ($table_schema as $column => $scheme) {
        $columns[] = [
          'label' => empty($scheme['logical_name']) ? $column : $scheme['logical_name'], 
          'property' => $column, 
          'sortable' => true, 
          'sortDirection' => 'asc', 
          'dataNumric' => $this->validate->check_column_type( $scheme['type'], 'numeric' ), 
          'className' => '', 
        ];
      }
      
      if (isset($custom_column_renderer) && !empty($custom_column_renderer)) {
        foreach ($columns as $i => $column_definition) {
          if (array_key_exists($column_definition['property'], $custom_column_renderer)) {
            $columns[$i] = array_merge($columns[$i], [ 'customColumnRenderer' => $custom_column_renderer[$column_definition['property']] ]);
          }
        }
        unset($i);
      }
      
      // Responding to `listSelectable` for `cdbt-edit`
      $condition_keys = [];
      $disabled_edit = false;
      if ($has_pk) {
        foreach ($table_schema as $column => $scheme) {
          if ($scheme['primary_key']) {
            if ( false !== strpos( $scheme['extra'], 'auto_increment' ) ) {
              $condition_keys = [ $column ]; // Surrogate key is only one
              break;
            } else {
              $condition_keys[] = $column; // In the case of composite primary key
            }
          }
        }
      } else {
        foreach ($table_schema as $column => $scheme) {
          if ( 'UNI' === strtoupper($scheme['column_key']) ) {
            $condition_keys = [ $column ]; // If is a unique index
            break;
          } else
          if ( $scheme['not_null'] ) {
            if ( 'MUL' === strtoupper($scheme['column_key']) || 'datetime' === $scheme['type'] || 'timestamp' === $scheme['type'] ) 
              $condition_keys[] = $column; // The columns often high accuracy uniqueness
          }
        }
        if (empty($condition_keys)) {
        	foreach ($table_schema as $column => $scheme) {
        	  if (!$this->validate->check_column_type( $scheme['type'], 'blob' )) 
              $condition_keys[] = $column; // Considerably low matching
          }
        }
      }
      unset($column, $scheme);
      if (empty($condition_keys)) {
        $disabled_edit = true;
        $where_condition = '';
      } else {
        $_temp = [];
        foreach ($condition_keys as $column) {
          $_temp[] = sprintf('%s:\' + encodeURIComponent(rowData.%s) + \'', $column, $column);
        }
        $where_condition = sprintf( '<input type="hidden" class="row_where_condition" value="%s">', implode(',', $_temp) );
      }
      if (array_key_exists('customColumnRenderer', $columns[0])) {
        $_temp = is_array($columns[0]['customColumnRenderer']) ? implode("\n", $columns[0]['customColumnRenderer']) : $columns[0]['customColumnRenderer'];
        $columns[0]['customColumnRenderer'] = sprintf( '\'<div class="cdbt-repeater-left-main">\' + %s + \'</div>%s\'', $_temp, $where_condition );
      } else {
        $columns[0]['customColumnRenderer'] = sprintf( '\'<div class="cdbt-repeater-left-main">\' + rowData.%s + \'</div>%s\'', $columns[0]['property'], $where_condition );
      }
      
      
      if ('regular' === $table_type && $display_list_num) {
        foreach ($datasource as $i => $datum) {
          $datasource[$i] = array_merge([ 'data-index-number' => $i + 1 ], $datum);
        }
        $add_column = [ 'label' => '#', 'property' => 'data-index-number', 'sortable' => true, 'sortDirection' => 'asc', 'dataNumric' => true, 'width' => 80 ];
        array_unshift($columns, $add_column);
      }
      //
      // Filter the column definition of the list content that is output by this shortcode
      //
      $columns = apply_filters( 'cdbt_shortcode_custom_columns', $columns, $shortcode_name, $table );
      
      $conponent_options = [
        'id' => 'cdbt-repeater-edit-' . $table, 
        'enableSearch' => true, 
        'enableFilter' => $display_filter, 
        'filters' => $filters, 
        'enableView' => false, 
        'defaultView' => 'list', 
        'enableEditor' => true, 
        'disableEdit' => $disabled_edit, 
        'listSelectable' => 'multi', 
        'staticHeight' => -1, 
        'pageIndex' => 1, 
        'pageSize' => $limit_items, 
        'columns' => $columns, 
        'data' => $datasource, 
        'addClass' => $add_class, 
      ];
      //
      // Filter the conponent definition of the list content that is output by this shortcode
      //
      $conponent_options = apply_filters( 'cdbt_shortcode_custom_conponent_options', $conponent_options, $shortcode_name, $table );
      
      if (isset($title)) 
        echo $title;
      
      return $this->component_render('repeater', $conponent_options);
      
    } else {
      // Generate table layout
      
      return $content;
    }
    
  }
  
  

}