<?php

namespace CustomDataBaseTables\Lib;

/**
 * Trait for shortcode of "cdbt-edit"
 *
 * @since 2.1.0
 *
 */
trait CdbtEdit {
  
  /**
   * for [cdbt-edit] ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
   * Render the editable data list for the specified table
   *
   * @since 1.0.0
   * @since 2.0.0 Refactored logic.
   * @since 2.1.0 Greatly enhanced
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
      'bootstrap_style' => true, // Change from v2.0.0 disabled.
      'display_list_num' => false, // Deprecated attributes, and the default value has changed to false  from v2.0.0 (actually not work)
      'display_title' => true, 
      'enable_sort' => true, //  Is enabled only if "bootstrap_style" is true.
      'exclude_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...`
      'add_class' => '', // Separator is a single-byte space character
      /* Added new attribute from 2.0.0 is follows: */
      'display_filter' => false, // Is enabled only if "bootstrap_style" is true.
      'filter_column' => '', // Target column name to filter.
      'filters' => '', // String as array (assoc); For example `filter1:label1,filter2:label2,...`
      'ajax_load' => false, // Is enabled only if "bootstrap_style" is true.
      'csid' => 0, // Valid value of "Custom Shortcode ID" is 1 or more integer. 
      /* Added new attribute from 2.0.6 is follows: */
      'narrow_keyword' => '', // String as array (not assoc) is `find_data()`; For example `keyword1,keyword2,...` Or String as hash is `get_data()`; For example `col1:keyword1,col2:keyword2,...`
      'sort_order' => 'created:desc', // String as hash for example `updated:desc,ID:asc,...`
      /* Added new attributes from 2.0.7 is follows: */
      'narrow_operator' => 'and', // String of either `and` or `or`; for method of `find_data()`
      // 'strip_tags' => true, // Whether to strip the tags in the string type data.
      /* Added new attributes from 2.0.10 is follows: */
      'truncate_strings' => 0, 
    ], $attributes) );
    if (empty($table) || !$this->check_table_exists($table)) 
      return;
    
    if (!$this->check_allowed_rendering_shortcode()) 
      return;
    
    // Initialization process for the shortcode
    $shortcode_name = 'cdbt-edit';
    $table_schema = $this->get_table_schema($table);
    $table_option = $this->get_table_option($table);
    $pk_columns = $has_char = $has_text = $has_bin = $has_list = $has_bit = $has_datetime = [];
    if (false !== $table_option) {
      $table_type = $table_option['table_type'];
      $has_pk = !empty($table_option['primary_key']) ? true : false;
      $pk_columns = $has_pk ? $table_option['primary_key'] : [];
      $limit_items = empty( $limit_items ) || intval( $limit_items ) < 1 ? intval( $table_option['show_max_records'] ) : intval( $limit_items );
      $truncate_strings = empty( $truncate_strings ) || intval( $truncate_strings ) < 0 ? 0 : intval( $truncate_strings );
      $strip_tags = array_key_exists( 'sanitization', $table_option ) ? $table_option['sanitization'] : true;
      foreach ($table_schema as $column => $scheme) {
      	if ($this->validate->check_column_type($scheme['type'], 'char'))
      	  $has_char[] = $column;
      	
      	if ($this->validate->check_column_type($scheme['type'], 'text'))
      	  $has_text[] = $column;
      	
        if ($this->validate->check_column_type($scheme['type'], 'blob')) 
          $has_bin[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'list')) 
          $has_list[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'binary')) 
          $has_bit[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'datetime')) {
          if (in_array($scheme['type'], [ 'date', 'datetime', 'timestamp' ])) 
            $has_datetime[] = $column;
        }
        
      }
    } else {
      if (in_array($table, $this->core_tables)) 
        $table_type = 'wp_core';
      
      $has_pk = false;
      foreach ($table_schema as $column => $scheme) {
        if ($scheme['primary_key']) {
          $has_pk = true;
          $pk_columns[] = $column;
        }
      	if ($this->validate->check_column_type($scheme['type'], 'char')) 
      	  $has_char[] = $column;
      	
      	if ($this->validate->check_column_type($scheme['type'], 'text')) 
      	  $has_text[] = $column;
      	
        if ($this->validate->check_column_type($scheme['type'], 'blob')) 
          $has_bin[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'datetime')) {
          if (in_array($scheme['type'], [ 'date', 'datetime', 'timestamp' ])) 
            $has_datetime[] = $column;
        }
      }
      $limit_items = intval($this->options['default_per_records']);
      $strip_tags = false;
    }
    $content = '';
    
    // Check user permission
    $result_permit = false;
    if (isset($table_option['permission']) && isset($table_option['permission']['edit_global']) && !empty($table_option['permission']['edit_global'])) {
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
    
    // Filter the viewing rights check result of the shortcode
    // You can give viewing rights to specific users by utilizing this filter hook.
    //
    // @since 2.0.0
    $result_permit = apply_filters( 'cdbt_after_shortcode_permit', $result_permit, $shortcode_name, $table );
    
    if (!$result_permit) 
      return sprintf('<p>%s</p>', __('You can not see this content without permission.', CDBT));
    
    
    // Validation of the attributes, then sanitizing
    $boolean_atts = [ 'bootstrap_style', 'display_list_num', 'display_title', 'enable_sort', 'display_filter', 'ajax_load', 'strip_tags' ];
    foreach ($boolean_atts as $attribute_name) {
      ${$attribute_name} = $this->strtobool( rawurldecode( ${$attribute_name} ) );
    }
    $not_assoc_atts = [ 'exclude_cols' ];
    foreach ($not_assoc_atts as $attribute_name) {
      ${$attribute_name} = $this->strtoarray( rawurldecode( ${$attribute_name} ) );
    }
    $hash_atts = [ 'narrow_keyword', 'sort_order', 'filters' ];
    foreach ($hash_atts as $attribute_name) {
      ${$attribute_name} = $this->strtohash( rawurldecode( ${$attribute_name} ) );
    }
    if ( ! empty( $add_class ) ) {
      $add_classes = [];
      foreach ( explode( ' ', rawurldecode( $add_class ) ) as $_class ) {
        $add_classes[] = esc_attr( trim( $_class ) );
      }
      $add_class = implode( ' ', $add_classes );
    }
    $image_render = 'responsive';
    if ($csid > 0 && $this->validate->checkInt($csid)) {
      // Checking whether the shortcode exists that has "csid (Custom Shortcode ID)".
      $loaded_settings = $this->get_shortcode_option($csid);
      if ($loaded_settings['base_name'] === $shortcode_name && $loaded_settings['target_table'] === $table) {
        foreach ($loaded_settings as $_key => $_val) {
          if (!in_array($_key, [ 'base_name', 'target_table', 'description', 'csid', 'author', 'generate_shortcode', 'alias_code' ])) {
            ${$_key} = $_val;
          }
        }
      }
    } else {
      $csid = 0;
    }
    if ($display_title) {
      $disp_title = $this->get_table_comment($table);
      $disp_title = !empty($disp_title) ? $disp_title : $table;
      $title = '<h4 class="sub-description-title">' . sprintf( __('Edit Data of "%s" Table', CDBT), $disp_title ) . '</h4>';
    }
    
    $all_columns = array_keys($table_schema);
    if ( $exclude_cols = $this->strtoarray( $exclude_cols ) ) {
      $output_columns = [];
      foreach ( $all_columns as $_col ) {
        if ( $has_pk && in_array( $_col, $pk_columns ) ) {
          $output_columns[] = $_col;
        } else
        if ( ! in_array( $_col, $exclude_cols ) ) {
          $output_columns[] = $_col;
        }
      }
    }
    if (!isset($output_columns)) 
      $output_columns = $all_columns;
    
    if (!in_array($filter_column, $all_columns)) {
      $filter_column = '';
    }
    $filters = $this->strtohash($filters);
    
    // Added since version 2.0.6
    $narrow_keyword = $this->is_assoc( $narrow_keyword ) ? $narrow_keyword : $this->strtohash( $narrow_keyword );
    if ( ! $narrow_keyword ) {
      $query_type = 'get';
    } else {
      $query_type = $this->is_assoc( $narrow_keyword ) ? 'get' : 'find';
      $conditions = [];
      if ( 'get' === $query_type ) {
        foreach ( $narrow_keyword as $_col => $_keywd ) {
          if ( in_array( $_col, $all_columns ) ) 
            $conditions[$_col] = $_keywd;
        }
      } else {
        $conditions = $narrow_keyword;
      }
    }
    if ( ! isset( $conditions ) ) 
      $conditions = null;
    
    $sort_order = $this->is_assoc( $sort_order ) ? $sort_order : $this->strtohash( $sort_order );
    if ( $this->is_assoc( $sort_order ) ) {
      $orders = [];
      foreach ( $sort_order as $_col => $_order ) {
        if ( ! is_int( $_col ) && in_array( $_col, $all_columns ) ) 
          $orders[$_col] = in_array( strtolower( $_order ), [ 'asc', 'desc' ] ) ? $_order : 'asc';
      }
    }
    if ( ! isset( $orders ) || empty( $orders ) ) 
      $orders = null;
    
    if ( 'get' === $query_type ) {
      // $datasource = $this->get_data( $table, 'ARRAY_A' );
      $datasource = $this->get_data( $table, '`'.implode( '`,`', $output_columns ).'`', $conditions, $orders, 'ARRAY_A' );
    } else {
      $datasource = [];
      // Added since version 2.0.7
      $narrow_operator = strtolower( $narrow_operator );
      if ( is_array( $conditions ) && ! empty( $conditions ) ) {
        foreach ( $conditions as $_i => $_keyword ) {
          if ( 0 === $_i ) {
            $datasource = $this->find_data( $table, $_keyword, $narrow_operator, $output_columns, $orders, 'ARRAY_A' );
          } else {
            // Currently, the plurality of keywords are not supported
            /*
            $diff_datasource = $this->find_data( $table, $_keyword, $output_columns, $orders, 'ARRAY_A' );
            if ( is_array( $diff_datasource ) && is_array( $datasource ) ) {
              $datasource = array_intersect( $diff_datasource, $datasource );
              //$datasource = array_merge( $datasource, $diff_datasource );
            }
            */
            break;
          }
        }
      } else {
        $datasource = $this->find_data( $table, $conditions, $narrow_operator, $output_columns, $orders, 'ARRAY_A' );
      }
    }
    if ( empty( $datasource ) ) 
      return sprintf( '<p>%s</p>', __('No data in this table.', CDBT ) );
    
    $custom_column_renderer = [];
    
    // If contain string as char in the data source (added since version 2.0.7)
    if ( ! empty( $has_char ) ) {
      foreach ( $has_char as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) ) {
          foreach ($datasource as $i => $row_data) {
            if ( $strip_tags ) {
              $datasource[$i][$column] = strip_tags( $row_data[$column] );
            } else {
              $datasource[$i][$column] = stripslashes_deep( $this->validate->esc_column_value( $row_data[$column], 'char' ) );
            }
          }
        }
      }
    }
    
    // If contain string as text in the data source (added since version 2.0.7)
    if ( ! empty( $has_text ) ) {
      foreach ( $has_text as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) ) {
          foreach ($datasource as $i => $row_data) {
            if ( $strip_tags ) {
              $datasource[$i][$column] = strip_tags( $row_data[$column] );
            } else {
              $datasource[$i][$column] = stripslashes_deep( $this->validate->esc_column_value( $row_data[$column], 'text' ) );
            }
          }
        }
      }
    }
    
    // If contain binary data in the datasource
    if (!empty($has_bin)) {
      foreach ($datasource as $i => $row_data) {
        foreach ($has_bin as $col_name) {
          if (array_key_exists($col_name, $row_data)) {
            if ('image' === $this->check_binary_data($row_data[$col_name])) {
              $row_data[$col_name] = sprintf('data:%s;base64,%s', $this->esc_binary_data($row_data[$col_name], 'mime_type'), $this->esc_binary_data($row_data[$col_name], 'bin_data') );
              // $custom_row_scripts[] = sprintf( 'helpers.rowData.%s = !helpers.rowData.%s ? \'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==\' : helpers.rowData.%s;', $col_name, $col_name, $col_name);
            } else {
              $_temp = $this->esc_binary_data( $row_data[$col_name], 'origin_file' );
              $row_data[$col_name] = !$_temp ? '' : $_temp;
            }
            $_where_conditions = [];
            if ($has_pk) {
              $_where_conditions = $pk_columns;
            }
            $_render_script_base = 'rowData[\'%s\'] !== false ? \'<div class="binary-data" data-column-name="%s" data-where-conditions="%s"><input type="hidden" data="\' + rowData[\'%s\'] + \'" data-class="img-%s"></div>\' : \'\'';
            $custom_column_renderer[$col_name] = sprintf($_render_script_base, $col_name, $col_name, implode(',', $_where_conditions), $col_name, $image_render);
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
          $custom_column_renderer[$column] = '\'<ul class="list-inline">\' + convert_list(rowData[\''. $column .'\']) + \'</ul>\'';
        }
      }
      if ($display_filter && empty($filters)) {
        if (!empty($_filter_items)) 
          $filters = array_unique($_filter_items);
      }
      unset($_filter_items);
    }
    
    // If contain bit binary data in the datasource
    // @since 2.0.7 Updated
    if ( ! empty( $has_bit ) ) {
      foreach ( $has_bit as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) || array_key_exists( 'BIN('. $column .')', $datasource[0] ) ) {
          
          foreach ( $datasource as $_i => $_data_row ) {
            foreach ( $_data_row as $_dcol => $_dval ) {
              if ( $column === $_dcol || 'BIN('. $column .')' === $_dcol ) {
                $datasource[$_i][$column] = $_dval;
                unset( $datasource[$_i][$_dcol] );
              } else {
                $datasource[$_i][$_dcol] = $_dval;
              }
            }
          }
          // Filter whether to use the icon display in the case of outputting the data registered in boolean form
          //
          // @since 2.0.0
          $bool_data_with_icon = apply_filters( 'cdbt_boolean_data_with_icon', true, $shortcode_name, $table );
          
          if ( $bool_data_with_icon ) {
            $custom_column_renderer[$column] = '\'<div class="center-block text-center"><small><i class="\' + (rowData[\''. $column .'\'] === \'1\' ? \'fa fa-circle-o\' : \'fa fa-time\' ) + \'"></i><span class="sr-only">\' + rowData[\''. $column .'\'] + \'</span></small></div>\'';
          } else {
            $custom_column_renderer[$column] = '\'<div class="center-block text-center">\' + (rowData[\''. $column .'\'] === \'1\' ? \'true\' : \'false\' ) + \'</div>\'';
          }
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
        $custom_column_renderer[$column] = '\'<div class="custom-datetime">\' + convert_datetime(rowData[\''. $column .'\'], '. $_datetime_format .') + \'</div>\'';
      }
      unset($_datetime_format);
    }
    
    
    if ($bootstrap_style) {
      // Generate repeater
      $columns = [];
      foreach ($table_schema as $column => $scheme) {
        $_classes = [];
        if ( ! in_array( $column, $output_columns ) ) 
          $_classes[] = 'hide';
        if ( isset( $exclude_cols ) && is_array( $exclude_cols ) && in_array( $column, $exclude_cols ) ) 
          $_classes[] = 'hide';
        if ( ! $enable_sort ) 
          $_classes[] = 'disable-sort';
        
        $columns[] = [
          'label' => empty($scheme['logical_name']) ? $column : $scheme['logical_name'], 
          'property' => $column, 
          'sortable' => $enable_sort, 
          'sortDirection' => 'asc', 
          'dataType' => $schema['type'], // Added since 2.1.0
          'dataNumric' => $this->validate->check_column_type( $scheme['type'], 'numeric' ), 
          'truncateStrings' => $truncate_strings, 
          'className' => implode(' ', $_classes), 
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
          $_temp[] = sprintf('%s:\' + encodeURIComponent(rowData[\'%s\']) + \'', $column, $column);
        }
        $where_condition = sprintf( '<input type="hidden" class="row_where_condition" value="%s">', implode(',', $_temp) );
      }
      if (array_key_exists('customColumnRenderer', $columns[0])) {
        $_temp = is_array($columns[0]['customColumnRenderer']) ? implode("\n", $columns[0]['customColumnRenderer']) : $columns[0]['customColumnRenderer'];
        $columns[0]['customColumnRenderer'] = sprintf( '\'<div class="cdbt-repeater-left-main">\' + %s + \'</div>%s\'', $_temp, $where_condition );
      } else {
        $columns[0]['customColumnRenderer'] = sprintf( '\'<div class="cdbt-repeater-left-main">\' + rowData[\'%s\'] + \'</div>%s\'', $columns[0]['property'], $where_condition );
      }
      
      
      if ('regular' === $table_type && $display_list_num) {
        foreach ($datasource as $i => $datum) {
          $datasource[$i] = array_merge([ 'data-index-number' => $i + 1 ], $datum);
        }
        $add_column = [ 'label' => '#', 'property' => 'data-index-number', 'sortable' => true, 'sortDirection' => 'asc', 'dataNumric' => true, 'width' => 80 ];
        array_unshift($columns, $add_column);
      }
      
      // Filter the column definition of the list content that is output by this shortcode
      //
      // @since 2.0.0
      $columns = apply_filters( 'cdbt_shortcode_custom_columns', $columns, $shortcode_name, $table );
      
      $component_options = [
        'id' => 'cdbt-repeater-edit-' . $table, 
        'enableSearch' => true, 
        'enableFilter' => $display_filter, 
        'filter_column' => $filter_column, 
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
      
      // Filter the component definition of the list content that is output by this shortcode
      //
      // @since 2.0.0
      $component_options = apply_filters( 'cdbt_shortcode_custom_component_options', $component_options, $shortcode_name, $table );
      
      if ( is_admin() ) {
        if (isset($title)) 
          echo $title;
        
        return $this->component_render('repeater', $component_options);
      } else {
        ob_start();
        if (isset($title)) 
          echo $title;
        
        echo $this->component_render('repeater', $component_options);
        
        $render_content = ob_get_contents();
        ob_end_clean();
        
        return $render_content;
      }
      
    }
    
  }
  
}