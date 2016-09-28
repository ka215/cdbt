<?php

namespace CustomDataBaseTables\Lib;

/**
 * Trait for shortcode of "cdbt-view"
 *
 * @since 2.1.31
 *
 */
trait CdbtView {
  
  /**
   * for [cdbt-view] ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
   * Retrieves all data that matches the specific conditions from the table, then it renders as list format.
   *
   * @since 1.0.0
   * @since 2.0.0 Refactored logic.
   * @since 2.1.31 Greatly enhanced
   * @since 2.1.32 Updated
   *
   * @param  array  $attributes [require]  - Array in shortcode's attributes
   * @param  string $content    [optional] - Should be actually nothing
   * @return string $html_content          - The formatted content to the specific list
   **/
  public function view_data_list() {
    list($attributes, $content) = func_get_args();
    extract( shortcode_atts([
      'table' => '', 					// @attribute string [required] Specifies the table name you want to display the data.
      'bootstrap_style' => true, 		// @attribute bool   [optional] Renders the data via the style of bootstrap if true; Render the data of the json format if false (since v2.1.x).
      'display_list_num' => false, 		// @attribute bool   [optional] Adds an auto increment number column at the left edge of the data row if true.
      'display_search' => true, 		// @attribute bool   [optional] Adds an input field for the data search if true.
      'display_title' => true, 			// @attribute bool   [optional] Displays the heading of content as a title if true.
      'enable_sort' => true, 			// @attribute bool   [optional] It'll be able to sort of data by clicking on the header row if true.
      'exclude_cols' => '', 			// @attribute string [optional] Specifies the comma-delimited column names if you want to hide the column. e.g. "column1,column2,column3,..."
      'add_class' => '', 				// @attribute string [optional] Specifies a CSS class name for styling the element of listed data table. If there are multiple class, please separated by a single-byte space.
      /* The attributes for a legacy shortcode "cdbt-extract" are followed: */
      'display_index_row' => true, 		// @attribute mixed  [optional] Displays the index row around the data rows as the header of the data column, if true. Also it's added of "head-only" for the table format besides boolean value. (since v2.1.x)
      'narrow_keyword' => '', 			// @attribute string [optional] Specifies the narrowing condition of the output data in a comma-delimited. If there are the multiple condition, it'll be evaluated at the "AND" condition. e.g. "keyword1,keyword2,..." or "column1:keyword1,column2:keyword2,..."
      'display_cols' => '', 			// @attribute string [optional] Specifies the comma-delimited column names if you want to show the column. This overrides the value of the attribute "exclude_cols". e.g. "column1,column2,column3,..."
      'order_cols' => '', 				// @attribute string [optional] Specifies the comma-delimited column names in the display order if you want to display columns in the order of your display request. This overrides the value of the attribute "exclude_cols" and "display_cols". e.g. "col3,col2,col1,..."
      'sort_order' => 'created:desc', 	// @attribute string [optional] Specifies in the pair of column name and the ascending(asc) or descending(desc) order, for the display order of the initial data. If there are multiple condition, please use the comma-delimited. e.g. "updated:desc,ID:asc,..."
      'limit_items' => '', 				// @attribute int    [optional] If this attribute is specified, it overrides the "Maximum display data per page" of the table.
      'image_render' => 'responsive', 	// @attribute string [optional] Specifies a CSS class name for styling the element of thumbnail image. This attribute is available only if the "enable_repeater" is true.
      /* The Added new attributes since v2.0.x are followed: */
      'enable_repeater' => false, 		// @attribute bool   [optional] Renders the data of table by using repeater component of the "FuelUX" libraries if true. Or render by using the original dynamic table component of this plugin if false (since v2.1.x).
      'display_filter' => false, 		// @attribute bool   [optional] Adds a dropdown list box for filtering the data if true. Then there should be specified the column to filter if you want to enable this.
      'filter_column' => '', 			// @attribute string [optional] Specifies a column name for filtering the data.
      'filters' => '', 					// @attribute string [optional] Specifies the keyword lists for filtering the data. Also, a plurality of the pairs of the keyword and the display label can be defined by using the comma-delimited. e.g. "filter-keyword1:display-label1,filter-keyword2:display-label2,..."
      'display_view' => false, 			// @attribute bool   [optional] You can switch to the thumbnail list view of the gallery format if there contained an image in the table data.
      'thumbnail_column' => '', 		// @attribute string [optional] Specifies a column as the thumbnail image. In this column it should be stored the image binary or a URL of image.
      'thumbnail_title_column' => '',   // @attribute string [optional] Specifies a column as displayed title on the thumbnail list view. it displays nothing if this is not fill.
      'thumbnail_width' => 100, 		// @attribute int    [optional] Specifies a width of the thumbnail image, also the default size of thumbnail will be square equal to this width.
      'ajax_load' => false, 			// @attribute bool   [optional] Use the Ajax to load the table data if true. If activated, you can improve performance when dealing with large tables of data size. (Not Implemented yet)
      'csid' => 0, 						// @attribute int    [optional] This is the alias number to call a custom shortcode settings that are stored in this plugin.
      /* The Added new attributes since v2.0.7 are followed: */
      'narrow_operator' => 'and', 		// @attribute string [optional] You can specify the value of either "and" or "or", as evaluated condition of multiple narrowing keywords.
      'strip_tags' => true, 			// @attribute bool   [optional] Whether to strip the tags in the string type data.
      /* The Added new attribute since v2.0.10 is followed: */
      'truncate_strings' => 40, 		// @attribute int    [optional] Truncates the display data if the strings type data is longer than the specified characters (not bytes). If value is zero it does not truncate.
      /* The Added new attributes since v2.1.32 is followed: */
      'draggable' => true, 				// @attribute bool	 [optional] You can drag overflow content if this is enabled. Default is true. Note: this attribute is enabled for table layout only.
      'clickable_cols' => '', 			// @attribute string [optional] Specifies the comma-delimited column names if you want to be able to click the column value. Also in those columns, it should be stored the string as like the url. e.g. "column1,column2,column3,..."
      'footer_interface' => 'pagination', 	// @attribute string [optional] You can specify which is "pagination (default)" or "pager". Note: this attribute is enabled for table layout only.
      'truncate_cols' => '', 			// @attribute string [optional] Specifies the comma-delimited column names if you want to specify the columns truncating the strings. e.g. "column1,column2,column3,..."
    ], $attributes) );
    if (empty($table) || !$this->check_table_exists($table)) 
      return;
    
    if (!$this->check_allowed_rendering_shortcode()) 
      return;
    
    // Initialization process for the shortcode
    $shortcode_name = 'cdbt-view';
    $table_schema = $this->get_table_schema($table);
    $table_option = $this->get_table_option($table);
    $pk_columns = $has_char = $has_text = $has_bin = $has_list = $has_bit = $has_datetime = [];
    if (false !== $table_option) {
      $table_type = $table_option['table_type'];
      $has_pk = !empty($table_option['primary_key']) ? true : false;
      $pk_columns = $has_pk ? $table_option['primary_key'] : [];
      $limit_items = empty( $limit_items ) || intval( $limit_items ) < 1 ? intval( $table_option['show_max_records'] ) : intval( $limit_items );
      $truncate_strings = empty( $truncate_strings ) || intval( $truncate_strings ) < 0 ? 0 : intval( $truncate_strings );
      $strip_tags = array_key_exists( 'sanitization', $table_option ) ? $table_option['sanitization'] : $strip_tags;
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
        
        if ( $this->validate->check_column_type( $scheme['type'], 'datetime' ) ) {
          if ( in_array( $scheme['type'], [ 'date', 'datetime', 'timestamp' ] ) ) 
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
      $limit_items = empty( $limit_items ) || intval( $limit_items ) < 1 ? intval( $this->options['default_per_records'] ) : intval( $limit_items );
      $strip_tags = false;
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
    
    // Filter the viewing rights check result of the shortcode
    // You can give viewing rights to specific users by utilizing this filter hook.
    //
    // @since 2.0.0
    $result_permit = apply_filters( 'cdbt_after_shortcode_permit', $result_permit, $shortcode_name, $table );
    
    if (!$result_permit) 
      return sprintf('<p>%s</p>', __('You can not see this content without permission.', CDBT));
    
    // Validation of the attributes, then sanitizing
    $boolean_atts = [ 'bootstrap_style', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'enable_repeater', 'display_filter', 'ajax_load', 'strip_tags', 'draggable' ];
    foreach ($boolean_atts as $attribute_name) {
      ${$attribute_name} = $this->strtobool( rawurldecode( ${$attribute_name} ) );
    }
    $not_assoc_atts = [ 'exclude_cols', 'display_cols', 'order_cols', 'clickable_cols', 'truncate_cols' ];
    foreach ($not_assoc_atts as $attribute_name) {
      ${$attribute_name} = $this->strtoarray( rawurldecode( ${$attribute_name} ) );
    }
    $hash_atts = [ 'narrow_keyword', 'sort_order', 'filters' ];
    foreach ( $hash_atts as $attribute_name ) {
      // @since 2.1.34 Updated
   	  $_tmp_str = rawurldecode( ${$attribute_name} );
      if ( 'filters' === $attribute_name ) {
        $_tmp_ary = explode( ',', $_tmp_str );
        if ( ! empty( $_tmp_ary ) && strpos( $_tmp_ary[0], ':' ) ) {
          ${$attribute_name} = $this->strtohash( $_tmp_str );
          foreach ( $filters as $_k => $_v ) {
            unset( $filters[$_k] );
            $filters[mb_encode_numericentity( $_k, array( 0x0, 0x10ffff, 0, 0xffffff ), 'UTF-8' )] = $_v;
          }
        } else {
          ${$attribute_name} = $this->strtoarray( $_tmp_str );
        }
      } else {
        ${$attribute_name} = $this->strtohash( $_tmp_str );
      }
    }
    $add_classes = [];
    if ( ! empty( $add_class ) ) {
      foreach ( explode( ' ', rawurldecode( $add_class ) ) as $_class ) {
        $add_classes[] = esc_attr( trim( $_class ) );
      }
    }
    
    if ( $csid > 0 && $this->validate->checkInt( $csid ) ) {
      // Checks whether the shortcode has "csid (Custom Shortcode ID)" or not.
      $loaded_settings = $this->get_shortcode_option( $csid );
      if ( isset( $loaded_settings['base_name'] ) && $loaded_settings['base_name'] === $shortcode_name && $loaded_settings['target_table'] === $table ) {
        foreach ( $loaded_settings as $_key => $_val ) {
          if ( ! in_array( $_key, [ 'base_name', 'target_table', 'description', 'csid', 'author', 'generate_shortcode', 'alias_code' ] ) ) {
            ${$_key} = $_val;
          }
        }
      }
    } else {
      $csid = 0;
    }
    
/* debug codes
    $all_vars = [
      'table', 'bootstrap_style', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'exclude_cols', 'add_class', 
      'display_index_row', 'narrow_keyword', 'display_cols', 'order_cols', 'sort_order', 'limit_items', 'image_render', 
      'enable_repeater', 'display_filter', 'filter_column', 'filters', 'display_view', 'thumbnail_column', 'thumbnail_title_column', 'thumbnail_width', 'ajax_load', 
      'csid', 
    ];
    foreach ($all_vars as $_var) {
      print_r($_var . '="' . ${$_var} . '"'. "\n");
    }
*/
    
    if ( $bootstrap_style ) {
      if ( $enable_repeater ) {
        $component_name = 'repeater';
        $display_index_row = $this->strtobool( $display_index_row );
        $ajax_load = false;
      } else {
        $component_name = 'table';
        $display_index_row = 'head-only' === $display_index_row ? $display_index_row : $this->strtobool( $display_index_row );
        $footer_interface = ! empty( $footer_interface ) && in_array( $footer_interface, [ 'pagination', 'pager' ] ) ? $footer_interface : 'pagination';
        $ajax_load = $this->strtobool( $ajax_load );
      }
    } else {
      $component_name = 'json';
      $ajax_load = false;
    }
    
    if ( ! empty( $image_render ) && ! in_array( strtolower( $image_render ), [ 'rounded', 'circle', 'thumbnail', 'responsive' ] ) ) {
      $image_render = 'responsive';
    } else {
      $image_render = strtolower( $image_render );
    }
    if ( $display_title ) {
      $disp_title = $this->get_table_comment( $table );
      $disp_title = ! empty( $disp_title ) ? $disp_title : $table;
      $title = '<h4 class="sub-description-title">' . sprintf( __('View Data in "%s" Table', CDBT), $disp_title ) . '</h4>';
    }
    
    $all_columns = array_keys( $table_schema );
    if ( $exclude_cols = $this->strtoarray( $exclude_cols ) ) {
      $output_columns = [];
      foreach ( $all_columns as $_col ) {
        if ( ! in_array( $_col, $exclude_cols ) ) 
          $output_columns[] = $_col;
      }
    }
    if ( $display_cols = $this->strtoarray( $display_cols ) ) {
      $output_columns = [];
      foreach ( $all_columns as $_col ) {
        if ( in_array( $_col, $display_cols ) ) 
          $output_columns[] = $_col;
      }
    }
    if ( $order_cols = $this->strtoarray( $order_cols ) ) {
      $output_columns = [];
      foreach ( $order_cols as $_col ) {
        if ( in_array( $_col, $all_columns ) ) 
          $output_columns[] = $_col;
      }
    }
    if ( ! isset( $output_columns ) ) 
      $output_columns = $all_columns;
    
    $narrow_keyword = $this->is_assoc( $narrow_keyword ) ? $narrow_keyword : $this->strtohash( $narrow_keyword );
    if ( empty( $narrow_keyword ) ) {
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
    
    if ( ! in_array( $filter_column, $all_columns ) ) {
      $filter_column = '';
    }
    if ( ! is_array( $filters ) ) {
      $filters = $this->strtohash( $filters );
    }
    
    if ( 'repeater' === $component_name && ! $display_index_row ) {
      $add_classes[] = 'hidden-index-row';
    }
    $add_class = implode( ' ', $add_classes );
    
    $clickable_cols = ! $clickable_cols ? [] : $this->strtoarray( $clickable_cols );
    foreach ( $clickable_cols as $_i => $_ck_col ) {
      if ( in_array( $_ck_col, $all_columns ) ) {
        if ( ( ! empty( $has_char ) && in_array( $_ck_col, $has_char ) ) || ( ! empty( $has_text ) && in_array( $_ck_col, $has_text ) ) ) {
          // done
        } else {
          unset( $clickable_cols[$_i] );
        }
      } else {
        unset( $clickable_cols[$_i] );
      }
    }
    $_candidate_truncate = [];
    foreach ( $all_columns as $_col ) {
      if ( ( ! empty( $has_char ) && in_array( $_col, $has_char ) ) || ( ! empty( $has_text ) && in_array( $_col, $has_text ) ) ) {
        $_candidate_truncate[] = $_col;
      }
    }
    $truncate_cols = ! $truncate_cols ? [] : $this->strtoarray( $truncate_cols );
    if ( empty( $truncate_cols ) ) {
      $truncate_cols = $_candidate_truncate;
    } else {
      foreach ( $truncate_cols as $_j => $_tc_col ) {
        if ( ! in_array( $_tc_col, $_candidate_truncate ) ) 
          unset( $truncate_cols[$_j] );
      }
    }
    
    // Filter conditions issued query via this shortcode
    // 
    // @since 2.1.32 Add new
    $conditions = apply_filters( 'cdbt_shortcode_query_conditions', $conditions, $narrow_operator, $shortcode_name, $table );
    
    // Added for loading data via Ajax since 2.1.32
    // Fixed a bug at v2.1.34
    if ( $ajax_load ) {
      $_limit_clause = intval( $limit_items );
      $_limit_clause = empty( $_limit_clause ) ? intval( $table_option['show_max_records'] ) : $_limit_clause;
    } else {
      $_limit_clause = null;
    }
    if ( 'get' === $query_type ) {
      $datasource = $this->get_data( $table, '`'.implode('`,`', $output_columns).'`', $conditions, $narrow_operator, $orders, $_limit_clause, 'ARRAY_A' );
    } else {
      $datasource = [];
      // Added since version 2.0.7
      // Fixed bug since version 2.1.31
      $narrow_operator = strtolower( $narrow_operator );
      $datasource = $this->find_data( $table, $conditions, $narrow_operator, $output_columns, $orders, $_limit_clause, 'ARRAY_A' );
    }
    if ( empty( $datasource ) ) {
      return sprintf('<p>%s</p>', __('The specified table&#39;s data was not found. There is no data in the table at all, or no data of matching condition.', CDBT));
    } else 
    if ( $ajax_load ) {
      $_cnt_col = $has_pk ? $pk_columns[0] : $output_columns[0];
      $total_data = 0;
      if ( 'get' === $query_type ) {
        $_res = $this->get_data( $table, 'COUNT(`'. $_cnt_col .'`)', $conditions, $narrow_operator, 'OBJECT_K' );
        $total_data = ! empty( $_res ) ? intval( array_keys( $_res )[0] ) : $total_data;
      } else {
      	$_res = $this->find_data( $table, $conditions, $narrow_operator, $_cnt_col );
        $total_data = count( $_res );
      }
      $query_assets = [ $query_type, $output_columns, $conditions, $narrow_operator, $orders, $_limit_clause ];
      unset( $_cnt_col, $_res );
    }
    
    if ( 'json' === $component_name ) 
      return json_encode( $datasource );
    
    $custom_column_renderer = [];
    
    // If contain string as char in the data source (added since version 2.0.7)
    if ( ! empty( $has_char ) ) {
      foreach ( $has_char as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) ) {
          foreach ( $datasource as $i => $row_data ) {
            if ( isset( $row_data[$column] ) ) {
              if ( $strip_tags ) {
                $datasource[$i][$column] = strip_tags( $row_data[$column] );
              } else {
                $datasource[$i][$column] = stripslashes_deep( $this->validate->esc_column_value( $row_data[$column], 'char' ) );
              }
              if ( in_array( $column, $clickable_cols ) ) 
                $custom_column_renderer[$column] = '$(\'<a target="_blank" />\').attr("href",rowData[\''. $column .'\']).html(rowData[\''. $column .'\'])';
            } else {
              $datasource[$i][$column] = null;
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
            if ( isset( $row_data[$column] ) ) {
              if ( $strip_tags ) {
                $datasource[$i][$column] = strip_tags( $row_data[$column] );
              } else {
                $datasource[$i][$column] = stripslashes_deep( $this->validate->esc_column_value( $row_data[$column], 'text' ) );
              }
              if ( in_array( $column, $clickable_cols ) ) 
                $custom_column_renderer[$column] = '$(\'<a target="_blank" />\').attr("href",rowData[\''. $column .'\']).html(rowData[\''. $column .'\'])';
            } else {
              $datasource[$i][$column] = null;
            }
          }
        }
      }
    }
    
    // If contain binary data in the datasource
    if ( ! empty( $has_bin ) ) {
      $custom_row_scripts = [];
      if ( $has_pk ) {
        
      }
      foreach ( $datasource as $i => $row_data ) {
        foreach ( $has_bin as $col_name ) {
          if ( array_key_exists( $col_name, $row_data ) ) {
            if ( 'image' === $this->check_binary_data( $row_data[$col_name] ) ) {
              $row_data[$col_name] = sprintf( 'data:%s;base64,%s', $this->esc_binary_data( $row_data[$col_name], 'mime_type' ), $this->esc_binary_data( $row_data[$col_name], 'bin_data' ) );
              if ( is_admin() && empty( $thumbnail_column ) ) {
                $display_view = true;
                $thumbnail_column = $col_name;
              }
              $custom_row_scripts[] = sprintf( 'helpers.rowData[\'%s\'] = !helpers.rowData[\'%s\'] ? \'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==\' : helpers.rowData[\'%s\'];', $col_name, $col_name, $col_name );
            } else {
              $row_data[$col_name] = $this->esc_binary_data( $row_data[$col_name], 'origin_file' );
            }
            $_where_conditions = [];
            if ( $has_pk ) {
              $_where_conditions = $pk_columns;
            }
            $_render_script_base = 'rowData[\'%s\'] !== false ? \'<a href="javascript:;" class="binary-data modal-preview" data-column-name="%s" data-where-conditions="%s"><input type="hidden" data="\' + rowData[\'%s\'] + \'" data-class="img-%s"></a>\' : \'\'';
            $custom_column_renderer[$col_name] = sprintf( $_render_script_base, $col_name, $col_name, implode( ',', $_where_conditions ), $col_name, $image_render );
            $datasource[$i] = $row_data;
          } else {
            $custom_column_renderer[$col_name] = '';
          }
        }
      }
    }
    
    // If contain list type columns
    if ( ! empty( $has_list ) ) {
      $_filter_items = [];
      foreach ( $has_list as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) && $column === $filter_column ) {
          foreach ( $this->parse_list_elements( $table_schema[$column]['type_format'] ) as $list_item ) {
            $_filter_items[] = sprintf( '%s:%s', mb_encode_numericentity( $list_item, array( 0x0, 0x10ffff, 0, 0xffffff ), 'UTF-8' ), mb_encode_numericentity( __( $list_item, CDBT ), array( 0x0, 0x10ffff, 0, 0xffffff ), 'UTF-8' ) );
          }
          if ( 'set' === $table_schema[$column]['type'] ) {
            $custom_column_renderer[$column] = '\'<ul class="list-inline">\' + convert_list(rowData[\''. $column .'\']) + \'</ul>\'';
          }
        }
      }
      if ( $display_filter && empty( $filters ) ) {
        if ( ! empty( $_filter_items ) ) 
          $filters = array_unique( $_filter_items );
      }
      unset( $_filter_items );
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
    if ( ! empty( $has_datetime ) ) {
      foreach ( $has_datetime as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) ) {
          if ( empty( $this->options['display_datetime_format'] ) ) {
            $_datetime_format = get_option( 'date_format' ) ."', '". get_option( 'time_format' );
          } else {
            $_datetime_format = $this->options['display_datetime_format'];
          }
          // Filter the outputting of date time format
          // 
          // @since 2.1.33
          $_datetime_format = "['". apply_filters( 'cdbt_shortcode_datetime_format', $_datetime_format, $column, $table_schema[$column]['type'], $shortcode_name, $table ) ."']";
          $custom_column_renderer[$column] = '\'<div class="custom-datetime">\' + convert_datetime(rowData[\''. $column .'\'], '. $_datetime_format .') + \'</div>\'';
        }
      }
      unset( $_datetime_format );
    }
    
    
    $columns = [];
    foreach ( $output_columns as $column ) {
      if ( array_key_exists( $column, $datasource[0] ) ) {
        $columns[] = [
          'label' => empty( $table_schema[$column]['logical_name'] ) ? $column : $table_schema[$column]['logical_name'], 
          'property' => $column, 
          'sortable' => in_array( $column, $truncate_cols ) ? ( in_array( $column, $clickable_cols ) ? $enable_sort : false ) : $enable_sort, 
          'sortDirection' => is_array( $sort_order ) && array_key_exists( $column, $sort_order ) ? $sort_order[$column] : 'asc', 
          'dataType' => in_array( $column, $clickable_cols ) ? 'clickable' : $table_schema[$column]['type'], // Added since 2.1.31; Updated since 2.1.32
          'dataNumric' => $this->validate->check_column_type( $table_schema[$column]['type'], 'numeric' ), 
          'isClickable' => in_array( $column, $clickable_cols ), 
          'isTruncate' => in_array( $column, $truncate_cols ), 
          'truncateStrings' => $truncate_strings, 
          'className' => $enable_sort ? '' : 'disable-sort', 
          'isRepeater' => 'repeater' === $component_name, // Added since 2.1.33
        ];
      }
    }
    
    if ( isset( $custom_column_renderer ) && ! empty( $custom_column_renderer ) ) {
      foreach ( $columns as $i => $column_definition ) {
        if ( array_key_exists( $column_definition['property'], $custom_column_renderer ) ) {
          $columns[$i] = array_merge( $columns[$i], [ 'customColumnRenderer' => $custom_column_renderer[$column_definition['property']] ] );
        }
      }
      unset( $i );
    }
    
    if ('regular' === $table_type && $display_list_num) {
      foreach ($datasource as $i => $datum) {
        $datasource[$i] = array_merge([ 'data-index-number' => $i + 1 ], $datum);
      }
      if ( 'repeater' === $component_name ) {
        $add_column = [ 'label' => '<i class="fa fa-hashtag"></i>', 'property' => 'data-index-number', 'sortable' => $enable_sort, 'sortDirection' => 'asc', 'dataNumric' => true, 'width' => 80 ];
      } else {
        $add_column = [ 'label' => '<i class="fa fa-hashtag"></i>', 'property' => 'data-index-number', 'sortable' => $enable_sort, 'sortDirection' => 'asc', 'dataNumric' => true ];
      }
      array_unshift($columns, $add_column);
    }
    
    // Filter the column definition of the list content that is output by this shortcode
    //
    // @since 2.0.0
    $columns = apply_filters( 'cdbt_shortcode_custom_columns', $columns, $shortcode_name, $table );
    
    $component_options = [
      'id' => 'cdbt-'. $component_name .'-view-' . $table, 
      'enableSearch' => $display_search, 
      'enableFilter' => $display_filter, 
      'filter_column' => $filter_column, 
      'filters' => $filters, 
      'enableView' => $display_view, 
      'defaultView' => 'list', 
      'listSelectable' => 'false', 
      'staticHeight' => -1, 
      'pageIndex' => 1, 
      'pageSize' => intval( $limit_items ), 
      'columns' => $columns, 
      'data' => $datasource, 
      'customRowScripts' => [], 
    ];
    
    if ('repeater' === $component_name) {
      $add_options = [ 
        'addClass' => $add_class, 
      ];
    } else {
      // For static table format
      $add_options = [
        'draggable' => $draggable, 
        'footerUI' => $footer_interface,
        'displayIndexRow' => $display_index_row, 
        'ajaxLoad' => $ajax_load, 
        'customBeforeRender' => '', 
        'customAfterRender' => '', 
        'thumbnailOptions' => [ 'title' => $thumbnail_title_column, 'column' => $thumbnail_column, 'width' => intval( $thumbnail_width ) ], 
        'tableClass' => $add_class, 
        'theadClass' => '', 
        'tbodyClass' => '', 
        'tfootClass' => '', 
      ];
      if ( $ajax_load ) {
        $add_options['totalData'] = $total_data;
        $add_options['queryAssets'] = $query_assets;
      }
    }
    $component_options = array_merge( $component_options, $add_options );
    
    if ( $display_view && ! empty( $thumbnail_column ) && array_key_exists( $thumbnail_column, $table_schema ) ) {
      if ('repeater' === $component_name) {
        $thumbnail_title = ! empty( $thumbnail_title_column ) ? sprintf( '<span>{{%s}}</span>', esc_html( $thumbnail_title_column ) ) : '';
        $thumbnail_template = '\'<div class="thumbnail repeater-thumbnail" style="background: #ffffff;"><img src="{{'. $thumbnail_column .'}}" width="'. intval( $thumbnail_width ) .'">'. $thumbnail_title .'</div>\'';
        $component_options = array_merge( $component_options, [ 'thumbnailTemplate' => $thumbnail_template ] );
        if ( isset( $custom_row_scripts ) && ! empty( $custom_row_scripts ) ) 
          $component_options = array_merge( $component_options, [ 'customRowScripts' => $custom_row_scripts ] );
      }
    }
    
    // Filter the component definition of the list content that is output by this shortcode
    //
    // @since 2.0.0
    $component_options = apply_filters( 'cdbt_shortcode_custom_component_options', $component_options, $shortcode_name, $table );
    
    /* old render
    if ( is_admin() ) {
      if (isset($title)) 
        echo $title;
      
      return $this->component_render( $component_name, $component_options );
    } else {
      ob_start();
      if (isset($title)) 
        echo $title;
      
      echo $this->component_render( $component_name, $component_options );
      
      $render_content = ob_get_contents();
      ob_end_clean();
      
      return $render_content;
    }
    */
    // Buffering @since 2.1.34
    ob_start();
    if ( isset( $title ) ) 
      echo $title;
    
    echo $this->component_render( $component_name, $component_options );
    $render_content = ob_get_contents();
    ob_clean();
    
    return $render_content;
  }
  
}
