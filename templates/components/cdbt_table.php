<?php
/**
 * Table Options array `$this->component_options` scheme
 * [
 * 'id' => @string is element id [optional] For default is `cdbtTable`
 * 'enableSearch' => @boolean Switching search form is hidden if `false`; default `true` [optional]
 * 'enableFilter' => @boolean Switching filter dropdown is hidden if `false`; default `true` [optional]
 * 'filter_column' => @string Target column name to filter [optional]
 * 'filters' => @array(assoc) is listing data [optional] array key is data-value, array value is display label
 * 'enableView' => @boolean Switching view button is hidden if `false`; default `true` [optional]
 * 'defaultView' => @mixed is view type of default [optional] (-1 (default), 'list', 'thumbnail')
 * 'enableEditor' => @boolean Operation button for editing is displayed if `true`; default `false` [optional] For `cdbt-edit` only
 * 'disableEdit' => @boolean Flag to disable the data editing because it can not identify a single data [optional] For `cdbt-edit` only
 * 'displayIndexRow' => @mixed Added since v2.1.x [optional] (true (default), false, 'head-only')
 * 'listSelectable' => @mixed can not select items of default [option] (false (default), 'single', 'multi')
 * 'pageIndex' => @integer is start page number [optional] (>= 0)
 * 'pageSize' => @integer is displayed data per page [optional]
 * 'columns' => @array(assoc) is listing label [require]
 * 'data' => @array(assoc) is listing data [require]
 * 'draggable' => @boolean Switching draggable table or not [optional] defalt `true`
 * 'footerUI' => @string Changing interface in table footer [optional] default `pagination`, other 'pager'
 * 'ajaxLoad' => @boolean [optional]
 * 'totalData' => @integer For ajax loading [optional]
 * 'queryAssets' => @array For ajax loading [optional]
 * 'customRowScripts' => @array is customized row as javascript lines [optional]
 * 'customBeforeRender' => @string is custom javascript [optional]
 * 'customAfterRender' => @string is custom javascript [optional]
 * 'thumbnailOptions' => @array is the related setting of thumbnail image [optional] ([ title, column, width ])
 * 'tableClass' => @string [optional] The classes of parent table tag additions
 * 'theadClass' => @string [optional] The classes of thead tag additions
 * 'tbodyClass' => @string [optional] The classes of tbody tag additions
 * 'tfootClass' => @string [optional] The classes of tfoot tag additions
 * ]
 */

/**
 * Parse options
 * ---------------------------------------------------------------------------
 */

// `id` section
if ( isset( $this->component_options['id'] ) && ! empty( $this->component_options['id'] ) ) {
  $rand_hash = $this->create_hash( $this->component_options['id'] . mt_rand() );
  $table_id = esc_attr__( $this->component_options['id'] .'-'. $rand_hash );
} else {
  $table_id = 'cdbtTable';
}

// `search` section
$enable_search = isset( $this->component_options['enableSearch'] ) ? $this->strtobool( $this->component_options['enableSearch'] ) : false;

// `filter` section
$enable_filter = isset( $this->component_options['enableFilter'] ) ? $this->strtobool( $this->component_options['enableFilter'] ) : false;

if ( empty( $this->component_options['filter_column'] ) || empty( $this->component_options['filters'] ) ) {
  $enable_filter = false;
} else {
  $filter_column = $this->component_options['filter_column'];
  $filters_list = [];
  if ( $this->is_assoc( $this->component_options['filters'] ) ) {
    foreach ( $this->component_options['filters'] as $_list_value => $_label ) {
      $filters_list[] = sprintf( '<li data-value="%s"><a href="#">%s</a></li>', $_list_value, $_label );
    }
  } else {
    foreach ( $this->component_options['filters'] as $val ) {
      $_value = $this->strtohash( $val );
      $_list_value = esc_attr( mb_decode_numericentity( key( $_value ), array( 0x0, 0x10ffff, 0, 0xffffff ), 'UTF-8' ) );
      $_list_value = $_list_value === '0' ? $_value[0] : $_list_value;
      $_label = ! empty( $_value[key( $_value )] ) ? mb_decode_numericentity( $_value[key( $_value )], array( 0x0, 0x10ffff, 0, 0xffffff ), 'UTF-8' ) : $_list_value;
      $filters_list[] = sprintf( '<li data-value="%s"><a href="#">%s</a></li>', $_list_value, $_label );
    }
  }
}

// `view` section
$enable_view = isset( $this->component_options['enableView'] ) ? $this->strtobool( $this->component_options['enableView'] ) : false;

if ( isset( $this->component_options['defaultView'] ) && in_array( $this->component_options['defaultView'], [ 'list', 'thumbnail' ] ) ) {
  $default_view = $this->component_options['defaultView'];
} else {
  $default_view = 'list';
}

// `displayIndexRow` section
if ( isset( $this->component_options['displayIndexRow'] ) && ! empty( $this->component_options['displayIndexRow'] ) ) {
  $display_index_row = strval( $this->component_options['displayIndexRow'] );
  if ( ! in_array( $display_index_row, [ 'false', 'true', 'head-only' ] ) ) {
    $display_index_row = 'true';
  }
} else {
  $display_index_row = 'false';
}

// `enableEditor` section
$enable_editor = isset($this->component_options['enableEditor']) ? $this->strtobool( $this->component_options['enableEditor'] ) : false;

// For filter hooks
$shortcode_name = $enable_editor ? 'cdbt-edit' : 'cdbt-view';
$table_name = str_replace( $enable_editor ? 'cdbt-table-edit-' : 'cdbt-table-view-', '', $this->component_options['id'] );
$table_options = $this->get_table_option( $table_name );

// `disableEdit` section
$disable_edit = isset($this->component_options['disableEdit']) ? $this->strtobool( $this->component_options['disableEdit'] ) : false;

// Additional classes section
foreach ($this->component_options as $_optkey => $_optval) {
  if (in_array($_optkey, [ 'tableClass', 'theadClass', 'tbodyClass', 'tfootClass' ] )) {
    $var_name = str_replace('Class', '_class', $_optkey);
    ${$var_name} = !isset($_optval) || empty($_optval) ? [] : explode(' ', $_optval);
    // filter
    // 
    // @since 2.1.0
    ${$var_name} = apply_filters( 'cdbt_table_class_additions', ${$var_name}, $table_id );
    
    ${$var_name} = empty(${$var_name}) ? '' : ' ' . implode(' ', ${$var_name});
  }
}

// `thumbnail` section
if ( isset( $this->component_options['thumbnailOptions'] ) ) {
  $_thumb_title = isset( $this->component_options['thumbnailOptions']['title'] ) ? $this->component_options['thumbnailOptions']['title'] : ''; // Note: column name to be used as a title
  $_thumb_title = empty( $_thumb_title ) && is_admin() ? 'auto' : $_thumb_title;
  $_thumb_column = isset( $this->component_options['thumbnailOptions']['column'] ) ? $this->component_options['thumbnailOptions']['column'] : '';
  $_thumb_width = isset( $this->component_options['thumbnailOptions']['width'] ) ? intval( $this->component_options['thumbnailOptions']['width'] ) : 0;
  $_thumb_width = $_thumb_width < 1 ? 102 : $_thumb_width + 2; // Note: increment a border size
} else {
  $_thumb_title = 'auto';
  $_thumb_width = 102;
}
$_thumb_template = '<figure class="cdbt-thumbnail"><div class="crop-image" style="width:'. $_thumb_width .'px;height:'. $_thumb_width .'px"><a href="javascript:;" class="binary-data modal-preview"><img src="<%= src %>"></a></div><figcaption style="width:'. $_thumb_width .'px"><span><%= title %></span></figcaption></figure>';

// `columns` section
if ( ! isset( $this->component_options['columns'] ) || empty( $this->component_options['columns'] ) ) {
  return;
} else {
  $columns = $this->component_options['columns'];
  $_row_line = $enable_editor ? '<tr class="selectable">%s</tr>' : '<tr>%s</tr>';
  $_index_cols = $_tmpl_data_cols = $_custom_column_renders = [];
  $_sortable_cols = 0;
  foreach ( $columns as $_col_atts ) {
    $_cell_width = isset( $_col_atts['width'] ) && intval($_col_atts['width']) > 0 ? ' style="width: '. $_col_atts['width'] .'px;"' : '';
    $_add_class = isset( $_col_atts['className'] ) && ! empty( $_col_atts['className'] ) ? ' ' . esc_attr( $_col_atts['className'] ) : '';
    if ( $_col_atts['sortable'] && $_thumb_column !== $_col_atts['property'] ) {
      $_index_cols[] = sprintf( '<th data-property="%s" class="sortable sortdir-%s%s"%s><label>%s</label></th>', $_col_atts['property'], $_col_atts['sortDirection'], $_add_class, $_cell_width, $_col_atts['label'] );
      $_sortable_cols++;
    } else {
      $_index_cols[] = sprintf( '<th data-property="%s" class="%s"%s><label>%s</label></th>', $_col_atts['property'], $_add_class, $_cell_width, $_col_atts['label'] );
    }
    if ( isset( $_col_atts['dataNumric'] ) && $_col_atts['dataNumric'] ) {
      $_data_wrapper = '<span class="data-numric"><% "'. $_col_atts['property'] .'" %></span>';
    } else
    if ( isset( $_col_atts['dataType'] ) && strpos( $_col_atts['dataType'], 'text' ) !== false ) {
      if ( $_col_atts['isTruncate'] && $_col_atts['truncateStrings'] > 0 ) {
        $_data_wrapper = '<textarea class="data-'. $_col_atts['dataType'] .' truncation" data-truncate-length="'. $_col_atts['truncateStrings'] .'" readonly><% "'. $_col_atts['property'] .'" %></textarea>';
      } else {
        $_data_wrapper = '<span class="data-'. $_col_atts['dataType'] .'"><% "'. $_col_atts['property'] .'" %></span>';
      }
    } else {
      $_data_wrapper = '<span class="data-'. $_col_atts['dataType'] .'"><% "'. $_col_atts['property'] .'" %></span>';
    }
    // Note: Replaced with underscore if the property value contains except alphanumeric and the hyphen and underscore.
    
    $_tmpl_data_cols[] = sprintf( '<td class="property-%s%s"%s>%s</td>', preg_replace( '/[^a-zA-Z0-9_-]/', '_', $_col_atts['property'] ), $_add_class, $_cell_width, $_data_wrapper );
    if ( isset( $_col_atts['customColumnRenderer'] ) && ! empty( $_col_atts['customColumnRenderer'] ) ) {
      if ( ! empty( $_thumb_column ) && $_thumb_column === $_col_atts['property'] ) {
        $_render_script_base = 'rowData[\'%s\'] !== false ? \'<a href="javascript:;" class="binary-data modal-preview" data-column-name="%s" data-where-conditions=""><input type="hidden" data="\' + rowData[\'%s\'] + \'" data-class="img-responsive"></a>\' : \'\'';
      	$_custom_column_renders[$_col_atts['property']] = sprintf( $_render_script_base, $_col_atts['property'], $_col_atts['property'], $_col_atts['property'] );
      } else {
        $_custom_column_renders[$_col_atts['property']] = $_col_atts['customColumnRenderer'];
      }
    }
  }
  if ( $enable_editor ) {
    array_unshift( $_index_cols, '<th class="editable-checkbox"><div class="checkbox table-header-checkbox"><label class="checkbox-custom table-select-checkbox" data-initialize="checkbox"><input class="sr-only" type="checkbox" value=""></label></div></th>' );
    array_unshift( $_tmpl_data_cols, '<td class="editable-checkbox"><div class="checkbox table-body-checkbox"><label data-row="{%RowIndexNumber}" class="checkbox-custom table-select-checkbox" data-initialize="checkbox"><input class="sr-only" type="checkbox" value=""></label></div></td>' );
  }
  $index_row = sprintf( $_row_line, implode( "\n", $_index_cols ) );
  $template_row = sprintf( $_row_line, implode( '', $_tmpl_data_cols ) );
}

// `data` section
if ( ! isset( $this->component_options['data'] ) || empty( $this->component_options['data'] ) ) {
  return;
} else {
  $items = $this->component_options['data'];
}

// `draggable` section
$draggable = isset( $this->component_options['draggable'] ) ? $this->strtobool( $this->component_options['draggable'] ) : true;

// `footerUI` section
$footer_ui = isset( $this->component_options['footerUI'] ) ? $this->component_options['footerUI'] : 'pagination';

// `pageIndex` section
if ( isset( $this->component_options['pageIndex'] ) && intval( $this->component_options['pageIndex'] ) >= 0 ) {
  $page_index = intval( $this->component_options['pageIndex'] );
} else {
  $page_index = 0;
}

// `pageSize` section
if ( isset( $this->component_options['pageSize'] ) && intval( $this->component_options['pageSize'] ) > 0 ) {
  $page_size = intval( $this->component_options['pageSize'] );
} else {
  $page_size = intval( $table_options['show_max_records'] );
}

// `Paging` section
$_must_paging = ( $page_size !== 0 ? ceil( count( $items ) / $page_size ) : 1 ) > 1;

// `ajaxLoad` section
$ajax_load = isset( $this->component_options['ajaxLoad'] ) ? $this->strtobool( $this->component_options['ajaxLoad'] ) : false;
if ( $ajax_load ) {
  $query_assets = serialize( $this->component_options['queryAssets'] );
  $total_data = $this->component_options['totalData'];
  $_must_paging = ( $page_size !== 0 ? ceil( $total_data / $page_size ) : 1 ) > 1;
//var_dump( $total_data, $page_index, $page_size, $_must_paging, $query_assets );
}

// `customRowScripts` section
if ( isset( $this->component_options['customRowScripts'] ) && ! empty( $this->component_options['customRowScripts'] ) ) {
  $custom_rows = $this->component_options['customRowScripts'];
}

// `customBeforeRender` section
if ( isset( $this->component_options['customBeforeRender'] ) && ! empty( $this->component_options['customBeforeRender'] ) ) {
  $before_render_scripts = $this->component_options['customBeforeRender'];
}

// `customAfterRender` section
if ( isset( $this->component_options['customAfterRender'] ) && ! empty( $this->component_options['customAfterRender'] ) ) {
  $after_render_scripts = $this->component_options['customAfterRender'];
}

// Filter to crop position of thumbnail image
//
// @since 2.1.0
$adjust_thumbnail = apply_filters( 'cdbt_crop_thumbnail_position', [ 'landscape'=>'auto', 'portrait'=>'auto' ], $table_name, $shortcode_name );

/**
 * Render the Repeater
 * ---------------------------------------------------------------------------
 */
?>
<div class="panel panel-default cdbt-table-wrapper" for="<?php echo $table_id; ?>">
<?php if ( $enable_search || $enable_filter || $enable_view || $enable_editor ) : ?>
  <div class="panel-heading" for="<?php echo $table_id; ?>">
    <div class="row">
      <div class="col-xs-6 col-md-4 align-left">
<?php if ( $enable_search ) : ?>
        <div class="input-group" role="search" id="<?php echo $table_id; ?>-search">
          <input type="search" class="form-control" placeholder="<?php _e( 'Search', CDBT ); ?>">
          <span class="input-group-btn">
            <button class="btn btn-default" type="button"><i class="fa fa-search" aria-hidden="true"></i><span class="sr-only"><?php _e( 'Search', CDBT ); ?></span></button>
          </span>
        </div><!-- /.input-group[role=search] -->
<?php elseif ( $enable_filter ) : ?>
        <div class="btn-group selectlist cdbt-table-filters" data-resize="auto" id="<?php echo $table_id; ?>-filters">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="selected-label">&nbsp;</span>
            <span class="caret"></span>
            <span class="sr-only"><?php _e( 'Toggle Filters', CDBT ); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
            <li data-value="" data-selected="true"><a href="#"><?php _e( 'all', CDBT ); ?></a></li>
            <?php echo implode( "\n", $filters_list ); ?>
          </ul>
          <input class="hidden hidden-field" name="filterSelection" readonly="readonly" aria-hidden="true" type="text"/>
        </div><!-- /.cdbt-table-filters -->
<?php endif; ?>
      </div><!-- /.col-md-4 -->
      <div class="col-xs-6 col-sm-6 col-md-8 align-right">
<?php if ( $enable_editor && ! $disable_edit ) : ?>
        <div class="cdbt-table-editor pull-right" for="<?php echo $table_id; ?>">
          <button type="button" class="btn btn-default" id="table-editor-edit" title="<?php _e( 'Edit Data', CDBT ); ?>" disabled><i class="fa fa-pencil-square-o"></i><span class="sr-only"><?php _e( 'Edit Data', CDBT ); ?></span></button>
          <button type="button" class="btn btn-default" id="table-editor-refresh" title="<?php _e( 'Refresh List', CDBT ); ?>"><i class="fa fa-refresh"></i><span class="sr-only"><?php _e( 'Refresh List', CDBT ); ?></span></button>
          <button type="button" class="btn btn-default" id="table-editor-delete" title="<?php _e( 'Delete Data', CDBT ); ?>" disabled><i class="fa fa-trash-o"></i><span class="sr-only"><?php _e( 'Delete Data', CDBT ); ?></span></button>
        </div><!-- /.cdbt-table-editor -->
<?php endif; ?>
<?php if ( $enable_view ) : ?>
        <div class="btn-group cdbt-table-views pull-right" data-toggle="buttons" data-current-view="<?php echo $default_view; ?>" for="<?php echo $table_id; ?>">
          <label class="btn btn-default<?php if ( 'list' === $default_view ) : ?> active<?php endif; ?>">
            <input name="tableViews" type="radio" value="list"><i class="fa fa-th-list"></i>
          </label>
          <label class="btn btn-default<?php if ( 'thumbnail' === $default_view ) : ?> active<?php endif; ?>">
            <input name="tableViews" type="radio" value="thumbnail"><i class="fa fa-th"></i>
          </label>
        </div><!-- /.cdbt-table-views -->
<?php endif; ?>
<?php if ( $enable_search && $enable_filter ) : ?>
        <div class="btn-group selectlist cdbt-table-filters pull-right" data-resize="auto" id="<?php echo $table_id; ?>-filters">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="selected-label">&nbsp;</span>
            <span class="caret"></span>
            <span class="sr-only"><?php _e( 'Toggle Filters', CDBT ); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
            <li data-value="" data-selected="true"><a href="#"><?php _e( 'all', CDBT ); ?></a></li>
            <?php echo implode( "\n", $filters_list ); ?>
          </ul>
          <input class="hidden hidden-field" name="filterSelection" readonly="readonly" aria-hidden="true" type="text"/>
        </div><!-- /.cdbt-table-filters -->
<?php endif; ?>
      </div><!-- /.col-md-8 -->
    </div><!-- /.row -->
  </div><!-- /.panel-heading -->
<?php endif; ?>
  <div class="panel-body" for="<?php echo $table_id; ?>">
  <?php if ( $disable_edit ) : ?>
    <p class="text-danger" style="margin-top: 6px;"><?php _e( 'Disable the data editing because it can not identify a single data.', CDBT ); ?></p>
  <?php else : ?>
    <div class="loading">
      <i class="fa fa-spinner fa-pulse fa-3x fa-fw margin-bottom text-muted"></i><span class="sr-only"><?php _e('Loading...', CDBT); ?></span>
    </div>
  <?php endif; ?>
  </div><!-- /.panel-body -->
  <div class="panel-table-wrapper">
    <table class="table<?php echo empty($table_class) ? ' table-striped table-bordered table-hover' : $table_class; ?> hide" id="<?php echo $table_id; ?>">
      <thead class="<?php echo $thead_class; ?>">
        <?php echo $index_row; ?>
      </thead>
      <tbody class="<?php echo $tbody_class; ?>">
      </tbody>
      <tfoot class="<?php echo $tfoot_class; ?>">
        <?php echo $index_row; ?>
      </tfoot>
    </table>
<?php if ( $enable_view ) : ?>
    <div class="thumbnail-view hide" id="<?php echo $table_id; ?>-view">
      <div class="loading">
        <i class="fa fa-spinner fa-pulse fa-3x fa-fw margin-bottom text-muted"></i><span class="sr-only"><?php _e('Loading...', CDBT); ?></span>
      </div>
    </div>
<?php endif; ?>
  </div><!-- /.panel-table-wrapper-->
<?php if ( $_must_paging ) : ?>
  <div class="panel-footer" for="<?php echo $table_id; ?>">
    <nav class="cdbt-<?php echo $footer_ui; ?> text-center" for="<?php echo $table_id; ?>"></nav>
  </div><!-- /.panel-footer -->
<?php endif; ?>
</div><!-- /.cdbt-table-wrapper -->
<script>
// For doing inherit class
if (typeof DynamicTables === 'undefined') {
  var DynamicTables = {};
}
DynamicTables['<?php echo $table_id; ?>'] = function() {
  if (typeof $ === 'undefined' && typeof jQuery === 'function') {
    $ = jQuery;
  }
  this.init();
};
DynamicTables['<?php echo $table_id; ?>'].prototype = {
<?php $json_code = json_encode($items); ?>
  items: new Array(<?php echo substr($json_code, 1, -1); ?>),
  filteredItems: new Array(),
<?php if ( $ajax_load ) : ?>
  baseQuery: '<?php echo $query_assets; ?>',
<?php endif; ?>
  init: function() {
    var items = this.items;
    
    var templateRow = '<?php echo $template_row; ?>';
    var perPageLimit = <?php echo intval( $page_size ); ?>;
    var currentPage = <?php echo intval( $page_index ); ?>;
    
    var optCookie = docCookies.getItem('<?php echo $table_name; ?>');
    if ( ! _.isNull(optCookie) ) {
      optCookie = JSON.parse(optCookie);
      docCookies.removeItem('<?php echo $table_name; ?>');
      currentPage = optCookie.currentPage;
    }
    
    var startIndex = ((currentPage - 1) * perPageLimit) + 1;
    var endIndex = (startIndex + perPageLimit) - 1;
    
    this.options = {
      tableId: '<?php echo $table_id; ?>', 
      templateRow: templateRow, 
      perPageLimit: perPageLimit, 
      currentPage: currentPage, 
<?php if ( $ajax_load ) : ?>
      totalItems: <?php echo $total_data; ?>, 
      totalPages: Math.ceil(<?php echo $total_data; ?> / perPageLimit), 
      startIndex: startIndex, 
      endIndex: endIndex > <?php echo $total_data; ?> ? <?php echo $total_data; ?> : endIndex, 
      filterType: '', 
      cachePage: _.isNull(optCookie) ? '' : optCookie.cachePage, 
<?php else : ?>
      totalItems: items.length, 
      totalPages: Math.ceil(items.length / perPageLimit), 
      startIndex: startIndex, 
      endIndex: endIndex > items.length ? items.length : endIndex, 
<?php endif; ?>
      searchKeyword: _.isNull(optCookie) ? '' : optCookie.searchKeyword, 
      filterKeyword: _.isNull(optCookie) ? '' : optCookie.filterKeyword, 
      searchedData: [], 
      isFiltering: false, 
      showTh: <?php echo 'head-only' === $display_index_row ? "'". $display_index_row ."'" : $display_index_row; ?>, 
      sortedProperty: _.isNull(optCookie) ? '' : optCookie.sortedProperty, 
      currentSortDir: _.isNull(optCookie) ? '' : optCookie.currentSortDir, 
    };
    
    // Add Event Listener
    var _self = this;
<?php if ( $_sortable_cols > 0 ) : ?>
    $('#<?php echo $table_id; ?> thead th.sortable').on('click', function(e){ _self.sortBy(e,$(this)); }); // sort
    if ( ! _.isNull(optCookie) && '' !== optCookie.sortedProperty ) {
      $('#<?php echo $table_id; ?> thead th.sortable[data-property="'+optCookie.sortedProperty+'"]').addClass('sortdir-'+optCookie.currentSortDir).trigger('click');
      //_self.sortBy();
    }
<?php endif; ?>
<?php if ( $_must_paging && 'pagination' === $footer_ui ) : ?>
    $(document).on('click', 'nav.cdbt-pagination[for="<?php echo $table_id; ?>"] a', function(e){ _self.pageFeed(e,$(this)); }); // paging
<?php endif; ?>
<?php if ( $_must_paging && 'pager' === $footer_ui ) : ?>
    $(document).on('click', 'nav.cdbt-pager[for="<?php echo $table_id; ?>"] button', function(e){ _self.pageFeed(e,$(this)); }); // paging by clicking
    $(document).on('keypress', 'nav.cdbt-pager[for="<?php echo $table_id; ?>"] .cdbt-pager-combobox>input', function(e){ if (e.which === 13) { _self.pageFeed(e,$(this)); } }); // paging by inputting
    $(document).on('changed.fu.combobox', 'nav.cdbt-pager[for="<?php echo $table_id; ?>"] .cdbt-pager-combobox', function(e){ _self.pageFeed(e,$(this)); }); // paging by changing
<?php endif; ?>
<?php if ( $enable_view ) : ?>
    $(document).on('click', '.cdbt-table-views[for="<?php echo $table_id; ?>"]', function(e){ _self.changeView(e,$(this)); }); // view
<?php endif; ?>
<?php if ( $enable_filter ) : ?>
	$('#<?php echo $table_id; ?>-filters').on('changed.fu.selectlist', function(e){ _self.filterAt(e,$(this)); }); // filter
	if ( ! _.isNull(optCookie) && '' !== optCookie.filterKeyword ) {
      var _value = optCookie.filterKeyword.split(':');
      $('#<?php echo $table_id; ?>-filters').selectlist('selectByValue', _value[1]);
      if ( '' === optCookie.searchKeyword ) {
        $('#<?php echo $table_id; ?>-filters').trigger('changed.fu.selectlist');
      }
    }
<?php endif; ?>
<?php if ( $enable_search ) : ?>
    $('#<?php echo $table_id; ?>-search button').on('click', function(e){ _self.searchFor(e,$(this)); }); // search button
    $('#<?php echo $table_id; ?>-search input').on('keypress', function(e){ if (e.which === 13) { _self.searchFor(e,$(this)); } }); // search enter key
    if ( ! _.isNull(optCookie) && '' !== optCookie.searchKeyword ) {
      $('#<?php echo $table_id; ?>-search input').val(optCookie.searchKeyword);
      $('#<?php echo $table_id; ?>-search button').trigger('click');
    }
<?php endif; ?>
<?php if ( $enable_editor ) : ?>
    $(document).on('click', '.cdbt-table-editor[for="<?php echo $table_id; ?>"] button#table-editor-edit, .cdbt-table-editor[for="<?php echo $table_id; ?>"] button#table-editor-delete', function(e){ _self.cacheOpt(e,$(this)); }); // cache
<?php endif; ?>
    $(window).on('resize', function(e){ _self.render(); });
    
  }, 
  deepCopy: function(object) {
    return JSON.parse(JSON.stringify(Array.prototype.slice.call(object,0)));
  },
<?php if ( $ajax_load ) : ?>
  render: function(now_data) {
    var options = this.options;
    if (typeof now_data === 'object') {
      this.items = now_data;
    } else if ( typeof now_data === 'string' ) {
      var method = now_data;
    }
    var data = this.deepCopy(this.items);
<?php else : ?>
  render: function(method) {
    var options = this.options;
    var data = ! options.isFiltering ? this.deepCopy(this.items) : this.deepCopy(this.filteredItems);
    options.totalItems = data.length;
<?php endif; ?>
    
    options.totalPages = Math.ceil( options.totalItems / options.perPageLimit );
    options.startIndex = ((options.currentPage - 1) * options.perPageLimit) + 1;
    options.endIndex = (options.startIndex + options.perPageLimit) - 1;
    
    if (options.endIndex > options.totalItems) {
      options.endIndex = options.totalItems;
    }
<?php if ( ! $ajax_load ) : ?>
    data = data.slice(options.startIndex-1, options.endIndex);
<?php endif; ?>
    
    // customBeforeRenderer
    this.beforeRender();
    
<?php if ( $enable_filter ) : ?>
    $('#'+options.tableId+'-filters').selectlist();
<?php endif; ?>
    
    $('#'+options.tableId+' tbody').empty();
    _.each(data, function(rowData){
      var template = options.templateRow;
      var helpers = {}; // For compatibility with repeater
      
      // Convert list type data as common utility function for static table
      var convert_list = function(){
        if (typeof arguments.length === 'undefined' || null === arguments[0]) { return ''; }
        var list_data = arguments[0].split(',');
        return _.reduce(list_data, function(ctx, data){ return ctx + '<li><small>' + data + '</small></li>'; }, '');
      };
      
      // customColumnRenderer
      _.map(rowData, function(val,column){
      	var customMarkup = '';
        helpers.item = val;
        
        switch(column) {
<?php if ( ! empty( $_custom_column_renders ) ) : foreach ( $_custom_column_renders as $_col => $_val ) : ?>
          case '<?php echo $_col; ?>':
            customMarkup = <?php echo $_val; ?>;
            break;
<?php endforeach; endif; ?>
	      default:
	        customMarkup = $('<div/>').html(helpers.item).text();
            break;
        }
        rowData[column] = typeof customMarkup === 'object' ? customMarkup.get(0).outerHTML : strip_slashes( customMarkup );
      });
      
      var rowMarkup = _.reduce(options.templateRow.match(/<%+\s(.|\s)*?\s+%>/gi),function(tmpl,placeholder){
        var __property = placeholder.replace(/<%+\s(\'|\")?/, '').replace(/(\'|\")?\s+%>$/, '');
        return tmpl.replace(placeholder, rowData[__property]);
      },options.templateRow);
      helpers = { rowData: rowData };
      var customRowMarkup = $('<div/>').html(rowMarkup);
      // customRowRenderer
      var item = customRowMarkup.find('tr'); // For compatibility with repeater
<?php if ( isset( $custom_rows ) && ! empty( $custom_rows ) ) : ?>
      <?php echo implode( "\n", $custom_rows ) . "\n"; ?>
<?php endif; ?>
        customRowMarkup.find('.binary-data input[type=hidden]').each(function(){
          if ('data:image' === $(this).attr('data').substr(0, 10)) {
            $(this).parents('span[class^="data-"]').css({position:'relative',display:'inline-block',maxWidth:'100%',maxHeight:'<?php echo $_thumb_width; ?>px',overflow:'hidden',transition:'all .2s ease-in-out'});
            $(this).replaceWith('<img src="'+ $(this).attr('data') +'" style="position:relative;" class="'+ $(this).attr('data-class') +'">');
          } else {
            if ('' !== $(this).attr('data')) {
              if ('' !== $(this).parent().data().whereConditions) {
                var where_conditions = [];
                _.each($(this).parent().attr('data-where-conditions').split(','), function(v){ where_conditions.push(v + ':' + helpers.rowData[v]); });
                $(this).parent().attr('data-where-conditions', where_conditions.join(','));
                $(this).parent().attr('data-target-table', '<?php echo $table_name; ?>');
                $(this).replaceWith('<i class="fa fa-file-o"></i> ' + decodeURIComponent($(this).attr('data')));
              } else {
                var __source = $(this).attr('data');
                $(this).parents('span[class^="data-"]').css({position:'relative',display:'inline-block',maxWidth:'100%',maxHeight:'<?php echo $_thumb_width; ?>px',overflow:'hidden',transition:'all .2s ease-in-out'});
                $(this).replaceWith('<img src="'+ __source +'" style="position:relative;" class="'+ $(this).attr('data-class') +'">');
              }
            }
          }
        });
      
      $('#'+options.tableId+' tbody').append( customRowMarkup.html() );
    });
    
    var cols = $('#'+options.tableId+' thead').find('th').size() > 0 ? $('#'+options.tableId+' thead').find('th').size() : 1;
    if ('' === $('#'+options.tableId+' tbody').text()) {
      // If no data
      $('#'+options.tableId+' tbody').html('<tr><td colspan="'+cols+'" class="no-item"><div class="abs-text"><?php _e( "No result.", CDBT); ?></div></td></tr>');
      var __left = ($('#'+options.tableId).parent('.panel-table-wrapper').width() - $('#'+options.tableId+' .abs-text').outerWidth()) / 2;
      var __scrollX = $.fn['kinetic'] !== undefined ? $('#'+options.tableId).parent('.panel-table-wrapper').kinetic().get(0).scrollLeft : 0;
      $('#'+options.tableId+' .abs-text').css({ left: __left + __scrollX +'px' });
    }
    
    // Adjust and survey the size of cells
    var row_width = [];
    var max_th_width = [];
    var th_index = expected_th_width = gapSize = 0;
    $('#'+options.tableId+' thead>tr>th').each(function(){
      gapSize = Number(parseInt($(this).css('padding-left'))) > 0 ? parseInt($(this).css('padding-left')) * 2 : $.em2pxl(1);
      expected_th_width = $.strWidth( $(this).find('label').text() ) + gapSize;
      if (_.isUndefined(max_th_width[th_index]) || _.isNaN(max_th_width[th_index])) {
        max_th_width[th_index] = 0;
      }
      max_th_width[th_index] = $(this).hasClass('editable-checkbox') ? $.em2pxl(2.5) : Math.max(max_th_width[th_index], expected_th_width, $.em2pxl(4));
      th_index++;
    });
    var max_td_width = [];
    $('#'+options.tableId+' tbody>tr').each(function(){
      var td_index = expected_td_width = cols_width = 0;
      $(this).find('td').each(function(){
        gapSize = Number(parseInt($(this).css('padding'))) > 0 ? parseInt($(this).css('padding-left')) * 2 : $.em2pxl(1);
        if ('' === $(this).children().text() && $(this).children().find('img').size() > 0 ) {
          var thumbSize = <?php echo $_thumb_width; ?>;
          var imgSize = $.imageSize( $(this).children().find('img').attr('src') );
          var adjust, longBoundary;
          $(this).children().css({height:thumbSize+'px'});
          if (imgSize.w > imgSize.h) { // landscape
            longBoundary = Math.ceil((imgSize.w * thumbSize) / imgSize.h);
            adjust = (-1 * ((longBoundary - thumbSize) / 2));
            $(this).children().find('img').attr('width',longBoundary).attr('height',thumbSize).css({left: adjust + 'px', maxWidth: 'none', maxHeight: 'none' });
          } else
          if (imgSize.h > imgSize.w) { // portrait
            longBoundary = Math.ceil((imgSize.h * thumbSize) / imgSize.w);
            adjust = (-1 * ((longBoundary - thumbSize) / 2));
            $(this).children().find('img').attr('width',thumbSize).attr('height',longBoundary).css({top: adjust + 'px', maxWidth: 'none', maxHeight: 'none' });
          } else { // square
            $(this).children().find('img').attr('width',thumbSize);
          }
          expected_td_width = thumbSize + gapSize;
        } else {
          var chkWidth = $.strWidth( $(this).children().text(), true );
          expected_td_width = (chkWidth > $.em2pxl(12) ? $.em2pxl(12) : chkWidth) + gapSize;
        }
        if (_.isUndefined(max_td_width[td_index]) || _.isNaN(max_td_width[td_index])) {
          max_td_width[td_index] = 0;
        }
        max_td_width[td_index] = $(this).hasClass('editable-checkbox') ? $.em2pxl(2.5) : Math.max(max_td_width[td_index], expected_td_width, $.em2pxl(4));
        cols_width += max_td_width[td_index];
        td_index++;
      });
      row_width.push(cols_width);
    });
    var table_width = Math.ceil(_.max(row_width));
    var wrapper_width = $('.cdbt-table-wrapper[for="'+options.tableId+'"]').parent().width();
    
    // Adjust table size
    var draggableTable = false;
<?php if ( $draggable ) : ?>
    //if (table_width > $.em2pxl(4) * cols && table_width > wrapper_width) {
    if (table_width > $.em2pxl(6) * cols) {
      draggableTable = true;
      var total_width = c = 0;
      $('#'+options.tableId+' thead>tr>th').each(function(){
        c++;
        var fix_width = Math.max( max_th_width[$(this).index()], max_td_width[$(this).index()] );
        total_width += fix_width;
        if (fix_width > table_width) {
          $(this).css({ width: 'auto' });
        } else {
          $(this).css({ width: (c === cols ? fix_width + $.em2pxl(1) : fix_width) + 'px' });
        }
      });
    } else {
      $('#'+options.tableId).css({ tableLayout: 'fixed' });
    }
    if (draggableTable) {
      $('#'+options.tableId).css({ overflow: 'hidden', overflowX: 'scroll' });
      if ($.fn['kinetic'] !== undefined) {
        // To enable the draggable table
        $('#'+options.tableId).parent('.panel-table-wrapper').kinetic({
          filterTarget: function(target, e){
            if (!/down|start/.test(e.type)){
              return !(/span|area|a|input/i.test(target.tagName));
            }
          },
          moved: function(e){
            $('.panel-table-wrapper[for="'+options.tableId+'"]').css({width:'calc(100%+1px)'});
            var __defaultLeft = ($('#'+options.tableId).parent('.panel-table-wrapper').width() - $('#'+options.tableId+' .abs-text').outerWidth()) / 2;
            $('#'+options.tableId+' .abs-text').css({ left: (__defaultLeft + Math.floor(e.scrollLeft)) +'px'});
          }
        });
      }
    }
<?php endif; ?>
    
    var _is_safari = false;
    if (/AppleWebkit/i.test(window.navigator.userAgent)) {
      _is_safari = /Chrome/i.test(window.navigator.userAgent) ? false : true;
    }
    
    if (typeof Clipboard === 'function' && ! _is_safari) {
      // To enable the clipboard copy
      var clipboard = new Clipboard('tbody>tr>td', {
        text: function(trigger){
          var _this = $(trigger).find('[class^="data-"]');
          if (_this.hasClass('data-datetime') || _this.hasClass('data-timestamp')) {
            _this = _this.children();
          }
          var text = _this.text() || false;
          if (text) {
            _this.addClass('copied');
          }
          return text;
        }
      });
      clipboard.on('success', function(e){
        var _this = $(e.trigger).find('[class^="data-"]');
        _this.tooltip({ trigger: 'manual', title: '<?php _e('Copied', CDBT); ?>' }).tooltip('show');
        if (_this.hasClass('data-datetime') || _this.hasClass('data-timestamp')) {
          _this = _this.children();
        }
        _this.animate({ backgroundColor:'#fff', color:'#333' }, 500, function(){
          $(this).removeClass('copied');
          $(e.trigger).find('[aria-describedby^="tooltip"]').tooltip('hide');
          e.clearSelection();
        });
      });
      clipboard.on('error', function(e){
        var _this = $(e.trigger).find('[class^="data-"]');
        if (_this.hasClass('data-datetime') || _this.hasClass('data-timestamp')) {
          _this = _this.children();
        }
        $(e.trigger).find('[aria-describedby^="tooltip"]').tooltip('hide');
        e.clearSelection();
      });
    }
    
<?php if ( $_must_paging ) : ?>
    // Render <?php echo $footer_ui; ?> 
    if (options.totalPages > <?php echo 'pager' === $footer_ui ? 0 : 1; ?>) {
      this.<?php echo $footer_ui; ?>();
    } else {
      $('.cdbt-<?php echo $footer_ui; ?>[for="'+options.tableId+'"]').html('');
    }
<?php endif; ?>
    
    // Adjust current display position
    var componentPos = $('.cdbt-table-wrapper[for="'+options.tableId+'"]').offset();
    var topMargin = $('.cdbt-table-wrapper[for="'+options.tableId+'"]').prev('.sub-description-title').size() === 1 ? $('.cdbt-table-wrapper[for="'+options.tableId+'"]').prev('.sub-description-title').outerHeight() + 40 : 30;
    var adminBar = $('#wpadminbar').size() > 0 ? $('#wpadminbar').height() : 0;
    $(window).scrollTop(componentPos.top - topMargin - adminBar);
    
    // customAfterRenderer
<?php if ( $ajax_load ) : ?>
    this.afterRender(method);
<?php else : ?>
    this.afterRender(method);
<?php endif; ?>
    
  }, 
<?php if ( $_sortable_cols > 0 ) : ?>
<?php if ( ! $ajax_load ) : ?>
  objArraySort: function(data,prop,order){
    data.sort(function(a,b){
      if (_.isNumber(a[prop]) && _.isNumber(b[prop])) {
        return a[prop] - b[prop];
      } else {
        var _tmp_a = ! _.isNull(a[prop]) ? _.each(a[prop].match(/[0-9]+\.?[0-9]*/g), function(v,i){ return parseFloat(v[i]); }) : [0];
        var _tmp_b = ! _.isNull(b[prop]) ? _.each(b[prop].match(/[0-9]+\.?[0-9]*/g), function(v,i){ return parseFloat(v[i]); }) : [0];
        if ( ! _.isNull(_tmp_a) && ! _.isNull(_tmp_b)) {
          a = parseFloat(_tmp_a.join(''));
          b = parseFloat(_tmp_b.join(''));
          return a - b;
        } else {
          a = a[prop].toString().toLowerCase();
          b = b[prop].toString().toLowerCase();
          if (a < b) {
            return -1;
          } else
          if (a > b) {
            return 1;
          } else {
           return 0;
          }
        }
      }
    });
    return order !== 'asc' ? data.reverse() : data;
  },
<?php endif; ?>
  sortBy: function(e,target) {
    var options = this.options;
<?php if ( $ajax_load ) : ?>
    if ( options.totalItems <= 1 ) {
      return false;
    }
<?php else : ?>
    var data = ! options.isFiltering ? this.deepCopy(this.items) : this.deepCopy(this.filteredItems);
  	if (data.length <= 1) {
  	  return false;
  	}
<?php endif; ?>
    var sortedProperty = target.data('property');
    options.sortedProperty = sortedProperty;
    if ( ! target.hasClass('sorted')) {
      target.parent('tr').find('th').removeClass('sorted');
      target.addClass('sorted');
      $('#'+options.tableId+' tfoot').find('th').removeClass('sorted');
      $('#'+options.tableId+' tfoot').find('th[data-property="'+sortedProperty+'"]').addClass('sorted');
    } else {
      if (target.hasClass('sortdir-desc')) {
        target.removeClass('sortdir-desc').addClass('sortdir-asc');
      } else {
        target.removeClass('sortdir-asc').addClass('sortdir-desc');
      }
    }
    var currentSortDir = target.hasClass('sortdir-desc') ? 'desc' : 'asc';
    options.currentSortDir = currentSortDir;
<?php if ( $ajax_load ) : ?>
	var new_query = {
      sortColumn: options.sortedProperty,
      sortOrder: options.currentSortDir,
      offset: (options.currentPage - 1) * options.perPageLimit,
    };
    if ( options.isFiltering ) {
      if ( options.searchKeyword !== '' ) {
        new_query.narrowSearchKey = options.searchKeyword;
      }
      if ( options.filterKeyword !== '' ) {
        new_query.narrowFilterKey = options.filterKeyword;
      }
    }
    this.reload( new_query, this );
<?php else : ?>
    if ( options.isFiltering ) {
      this.filteredItems = this.objArraySort(data,sortedProperty,currentSortDir);
    } else {
      this.items = this.objArraySort(data,sortedProperty,currentSortDir);
    }
    return this.render();
<?php endif; ?>
    
  }, 
<?php endif; ?>
<?php if ( $enable_search ) : ?>
  searchFor: function(e,target) {
    var options = this.options;
<?php if ( ! $ajax_load ) : ?>
    var data = ! options.isFiltering ? this.deepCopy(this.items) : this.deepCopy(this.filteredItems);
<?php endif; ?>
    var keyword = $('#'+options.tableId+'-search input').val().toLowerCase();
    var searchedData = [];
    if ('' === keyword) {
      $('#'+options.tableId+'-search').find('i').attr('class', 'fa fa-search');
      return false;
    }
    if (target.find('i').hasClass('fa-close')) {
      $('#'+options.tableId+'-search').find('input').val('').prop('disabled', false);
      $('#'+options.tableId+'-search').find('i').attr('class', 'fa fa-search');
<?php if ( $ajax_load ) : ?>
      options.currentPage = 1;
      options.isFiltering = options.filterKeyword === undefined || options.filterKeyword === '' ? false : true;
      options.searchKeyword = '';
      //options.filterType = '';
    <?php if ( $enable_filter ) : ?>
      var __selectlist = $('#'+options.tableId+'-filters');
      __selectlist.selectlist('enable');
    <?php endif; ?>
      var new_query = {
        narrowSearchKey: options.searchKeyword, 
        offset: 0,
      };
      if ( options.filterKeyword !== '' ) {
        new_query.narrowFilterKey = options.filterKeyword;
      }
      if ( options.sortedProperty !== '' ) {
        new_query.sortColumn = options.sortedProperty;
        new_query.sortOrder = options.currentSortDir;
      }
      this.reload( new_query, this );
<?php else : ?>
      options.isFiltering = false;
    <?php if ( $enable_filter ) : ?>
      var __selectlist = $('#'+options.tableId+'-filters');
      __selectlist.selectlist('enable');
      if ('' !== __selectlist.selectlist('selectedItem').value) {
        return this.filterAt();
      }
    <?php endif; ?>
      return this.render();
<?php endif; ?>
    } else {
<?php if ( $ajax_load ) : ?>
      options.currentPage = 1;
      options.isFiltering = true;
      options.searchKeyword = keyword;
      var new_query = {
        narrowSearchKey: options.searchKeyword, 
        offset: 0,
      };
      if ( options.cachePage > 0 ) {
        new_query.offset = (options.cachePage - 1) * options.perPageLimit;
        options.currentPage = options.cachePage;
        options.cachePage = 0;
      }
      $('#'+options.tableId+'-search').find('i').attr('class', 'fa fa-close');
      $('#'+options.tableId+'-search').find('input').prop('disabled', true);
    <?php if ( $enable_filter ) : ?>
      $('#'+options.tableId+'-filters').selectlist('disable');
      if ( options.filterKeyword !== '' ) {
        new_query.narrowFilterKey = options.filterKeyword;
      }
    <?php endif; ?>
      if ( options.sortedProperty !== '' ) {
        new_query.sortColumn = options.sortedProperty;
        new_query.sortOrder = options.currentSortDir;
      }
      this.reload( new_query, this );
    }
<?php else : ?>
      _.each(data, function(item){
        var values = _.values(item);
        var found = _.find(values, function(v) {
          if (null === v || (_.isString(v) && 'data:image' === v.substr(0, 10)) ) v = false;
          if (v.toString().toLowerCase().indexOf(keyword) > -1) {
            searchedData.push(item);
            return true;
          }
        });
      });
      $('#'+options.tableId+'-search').find('i').attr('class', 'fa fa-close');
      $('#'+options.tableId+'-search').find('input').prop('disabled', true);
    }
    if (searchedData.length > 0) {
      options.currentPage = 1;
      options.searchKeyword = $('#'+options.tableId+'-search input').val();
    }
    this.filteredItems = searchedData;
  <?php if ( $enable_filter ) : ?>
    $('#'+options.tableId+'-filters').selectlist('disable');
  <?php endif; ?>
    options.isFiltering = true;
    return this.render();
<?php endif; ?>
    
  }, 
<?php endif; ?>
<?php if ( $enable_filter ) : ?>
  filterAt: function(e,target) {
    var options = this.options;
<?php if ( $ajax_load ) : ?>
    var keyword = $('#'+options.tableId+'-filters').selectlist('selectedItem').value;
    var new_query = {};
    if (keyword !== '') {
      options.currentPage = 1;
      options.isFiltering = true;
      options.filterKeyword = '<?php echo $filter_column; ?>:' + keyword;
      new_query = {
        narrowFilterKey: options.filterKeyword, 
        offset: 0,
      };
      if ( options.cachePage > 0 && options.searchKeyword === '' ) {
        new_query.offset = (options.cachePage - 1) * options.perPageLimit;
        options.currentPage = options.cachePage;
        options.cachePage = 0;
      }
    } else {
      options.currentPage = 1;
      options.isFiltering = options.searchKeyword === '' ? false : true;
      options.filterKeyword = '';
    }
    if ( options.sortedProperty !== '' ) {
      new_query.sortColumn = options.sortedProperty;
      new_query.sortOrder = options.currentSortDir;
    }
    this.reload( new_query, this );
<?php else : ?>
    var data = this.deepCopy(this.items);
    var searchedData = [], searchObj = { column: '<?php echo $filter_column; ?>', keyword: $('#'+options.tableId+'-filters').selectlist('selectedItem').value };
    if (searchObj.keyword === '') {
      options.isFiltering = false;
      return this.render();
    }
    _.each(data, function(item){
      if (item[searchObj.column] === String(searchObj.keyword)) {
        searchedData.push(item);
      }
    });
    if (searchedData.length > 0) {
      options.currentPage = 1;
    }
    this.filteredItems = searchedData;
    options.isFiltering = true;
    return this.render();
<?php endif; ?>
    
  },
<?php endif; ?>
<?php if ( $_must_paging ) : ?>
<?php   if ( 'pagination' === $footer_ui ) : ?>
  pageFeed: function(e,target) {
    var options = this.options;
    var ariaLabel = target.attr('aria-label');
    if ('Previous' === ariaLabel) {
      options.currentPage = (options.currentPage - 1) < 1 ? 1 : options.currentPage - 1;
    } else
    if ('Next' === ariaLabel) {
      options.currentPage = (options.currentPage + 1) > options.totalPages ? options.totalPages : options.currentPage + 1;
    } else {
      if (Number(ariaLabel) > 0) {
        options.currentPage = Number(ariaLabel);
      } else {
        return false;
      }
    }
<?php if ( $ajax_load ) : ?>
	var new_query = {
      offset: (options.currentPage - 1) * options.perPageLimit
    };
    if ( options.sortedProperty !== '' ) {
      new_query.sortColumn = options.sortedProperty;
      new_query.sortOrder = options.currentSortDir;
    }
    if ( options.isFiltering ) {
      if ( options.searchKeyword !== '' ) {
        new_query.narrowSearchKey = options.searchKeyword;
      }
      if ( options.filterKeyword !== '' ) {
        new_query.narrowFilterKey = options.filterKeyword;
      }
    }
    this.reload( new_query, this );
<?php else : ?>
    return this.render();
<?php endif; ?>
    
  }, 
  pagination: function() {
    var options = this.options;
    var disp = 5; // Display page range
    var start = options.currentPage - Math.floor(disp / 2) > 0 ? options.currentPage - Math.floor(disp / 2) : 1;
    var end = start > 1 ? options.currentPage + Math.floor(disp / 2) : disp;
    start = options.totalPages < end ? start - (end - options.totalPages) : start;
    
    var pagination = '<ul class="pagination pagination-sm" data-currentpage="'+options.currentPage+'">';
    pagination += '<li><a href="javascript:;" aria-label="1"><span aria-hidden="true"><i class="fa fa-angle-double-left"></i></span></a></li>';
    pagination += '<li><a href="javascript:;" aria-label="Previous"><span aria-hidden="true"><i class="fa fa-angle-left"></i></span></a></li>';
    if (start >= Math.floor(disp / 2)) {
      pagination += '<li><a href="javascript:;" aria-label="" class="disabled" disabled><i class="fa fa-ellipsis-h" class="text-muted"></i></a></li>';
    }
    for (var i=1; i<=options.totalPages; i++) {
      if (options.currentPage === i) {
        pagination += '<li class="active"><a href="javascript:;" aria-label="'+i+'">'+i+'</a></li>';
      } else 
      if (start <= i && i <= end) {
        pagination += '<li><a href="javascript:;" aria-label="'+i+'">'+i+'</a></li>';
      }
    }
    if (options.totalPages > end && options.totalPages > end) {
      pagination += '<li><a href="javascript:;" aria-label="" class="disabled" disabled"><i class="fa fa-ellipsis-h" class="text-muted"></i></a></li>';
    }
    pagination += '<li><a href="javascript:;" aria-label="Next"><span aria-hidden="true"><i class="fa fa-angle-right"></i></span></a></li>';
    pagination += '<li><a href="javascript:;" aria-label="'+options.totalPages+'"><span aria-hidden="true"><i class="fa fa-angle-double-right"></i></span></a></li></ul>';
    $('.panel-footer[for="'+options.tableId+'"]>nav.cdbt-pagination').html( pagination );
    
  }, 
<?php   endif; ?>
<?php   if ( 'pager' === $footer_ui ) : ?>
  pageFeed: function(e,target) {
    var options = this.options;
    var mustRender = true;
    if ('click' === e.type) {
      if (target.parent().hasClass('pager-prev')) {
        options.currentPage = (options.currentPage - 1) < 1 ? 1 : options.currentPage - 1;
      }
      if (target.parent().hasClass('pager-next')) {
        options.currentPage = (options.currentPage + 1) > options.totalPages ? options.totalPages : options.currentPage + 1;
      }
    } else
    if ('changed' === e.type) {
      var _selected = target.combobox('selectedItem');
      if (options.currentPage !== _selected.value) {
        options.currentPage = _selected.value;
      } else {
        mustRender = false;
      }
    } else {
      if (target.get(0).defaultValue !== target.get(0).value && target.get(0).value > 0 && target.get(0).value <= options.totalPages) {
        options.currentPage = Number(target.get(0).value);
      } else {
        target.val(target.get(0).defaultValue);
        mustRender = false;
      }
    }
    
<?php if ( $ajax_load ) : ?>
	var new_query = {
      offset: (options.currentPage - 1) * options.perPageLimit
    };
    if ( options.sortedProperty !== '' ) {
      new_query.sortColumn = options.sortedProperty;
      new_query.sortOrder = options.currentSortDir;
    }
    if ( options.isFiltering ) {
      if ( options.searchKeyword !== '' ) {
        new_query.narrowSearchKey = options.searchKeyword;
      }
      if ( options.filterKeyword !== '' ) {
        new_query.narrowFilterKey = options.filterKeyword;
      }
    }
    if ( mustRender ) {
      this.reload( new_query, this );
    } else {
      return false;
    }
<?php else : ?>
    return mustRender ? this.render() : false;
<?php endif; ?>
    
  },
  pager: function() {
    var options = this.options;
    var pagerContainer = $('.panel-footer[for="'+options.tableId+'"]>nav.cdbt-pager');
    
    var pager = '<div class="cdbt-table-pager row" data-curentpage="'+options.currentPage+'">';
    pager += '<div class="cdbt-table-itemization pull-left"><?php printf( esc_html__('%1$s - %2$s of %3$s items', CDBT), '<span class="item-start"></span>', '<span class="item-end"></span>', '<span class="item-count"></span>'); ?></div>'; // .cdbt-table-itemization
    pager += '<div class="cdbt-pager-body pull-right"><div class="pager-row">';
    pager += '<div class="pager-prev"><button type="button" class="btn btn-default btn-sm"><i class="fa fa-chevron-left" aria-hidden="true"></i></button></div>'; // .pager-prev
    pager += '<div class="pager-control"><label class="pager-label"><?php _e('Page', CDBT); ?></label>';
    pager += '<div class="input-group input-append dropdown combobox cdbt-pager-combobox" data-initialize="combobox"><input type="text" class="form-control" value="'+options.currentPage+'">';
    pager += '<div class="input-group-btn"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="fa fa-caret-down" aria-hidden="true"></i></button>';
    pager += '<ul class="dropdown-menu dropdown-menu-right"></ul></div>'; // .input-group-btn
    pager += '<span class="pager-index"><?php printf(esc_html__('of %1$s', CDBT), '<span class="total-pages"></span>'); ?></span></div>'; // .cdbt-pager-combobox
    pager += '</div>'; // .pager-control
    pager += '<div class="pager-next"><button type="button" class="btn btn-default btn-sm"><i class="fa fa-chevron-right" aria-hidden="true"></i></button></div>'; // .pager-next
    pager += '</div></div>'; // .cdbt-pager-body
    pager += '</div>'; // .cdbt-table-pager
    pagerContainer.html( pager );
    
    // Set variables
    pagerContainer.find('.item-start').text(options.startIndex);
    pagerContainer.find('.item-end').text(options.endIndex);
    pagerContainer.find('.item-count').text(options.totalItems);
    pagerContainer.find('.total-pages').text(options.totalPages);
    // Adjustment size
    var _actual_center_width = pagerContainer.find('.pager-label').get(0).offsetWidth + pagerContainer.find('.cdbt-pager-combobox').get(0).offsetWidth + $.em2pxl(1);
    pagerContainer.find('.pager-control').css({ width: _actual_center_width + 'px' });
    pagerContainer.find('.pager-prev').css({ width: pagerContainer.find('.pager-row').get(0).offsetWidth - (_actual_center_width + pagerContainer.find('.pager-next').get(0).offsetWidth) + 'px' });
    // Initialize elements
    if ( options.currentPage === 1 ) {
      pagerContainer.find('.pager-prev>button').prop('disabled', true);
    }
    if ( options.currentPage === options.totalPages ) {
      pagerContainer.find('.pager-next>button').prop('disabled', true);
    }
    if ( options.totalPages > 1 ) {
      var tmpl = _.template('<li data-value="<%= page %>"><a href="javascript:;"><%= page %></a></li>');
      var dropdownLists = _.reduce(_.range(options.totalPages), function(lists, pnum){
        return lists += tmpl({ page: pnum+1 });
      }, '');
      pagerContainer.find('ul.dropdown-menu').html(dropdownLists);
      pagerContainer.find('[data-toggle="dropdown"]').dropdown();
      pagerContainer.find('.cdbt-pager-combobox').combobox();
    } else {
      pagerContainer.find('[data-toggle="dropdown"]').prop('disabled', true);
    }
  },
<?php   endif; ?>
<?php endif; ?>
  beforeRender: function() {
    var options = this.options;
<?php if ( ! $ajax_load ) : ?>
    var data = ! options.isFiltering ? this.deepCopy(this.items) : this.deepCopy(this.filteredItems);
<?php endif; ?>
    
  <?php if ( isset($before_render_scripts) ) : ?>
    <?php echo $before_render_scripts; ?>
  <?php endif; ?>
    
  },
  afterRender: function(method) {
    var options = this.options;
<?php if ( ! $ajax_load ) : ?>
    var data = ! options.isFiltering ? this.deepCopy(this.items) : this.deepCopy(this.filteredItems);
<?php endif; ?>
    
<?php if ( $enable_editor ) : ?>
    $('.cdbt-table-editor[for="'+options.tableId+'"] button#table-editor-edit').removeClass('btn-primary').addClass('btn-default').prop('disabled', true);
    $('.cdbt-table-editor[for="'+options.tableId+'"] button#table-editor-delete').removeClass('btn-primary').addClass('btn-default').prop('disabled', true);
<?php endif; ?>
<?php if ( $enable_view ) : ?>
    var toView = $('.cdbt-table-views[for="'+options.tableId+'"]>label.active>input').val();
    if ('thumbnail' === toView) {
      $('.panel-body[for="'+options.tableId+'"]').fadeOut(300, function(){
        $('.thumbnail-view[for="'+options.tableId+'"]').fadeIn(300).removeClass('hide');
      });
      this.changeView();
    } else {
<?php endif; ?>
    if ( 'head-only' === options.showTh ) {
      $('#'+options.tableId).find('tfoot').hide();
    } else
    if ( ! options.showTh ) {
      $('#'+options.tableId).find('thead th').each(function(){
        var _tbody_first_row = $('#'+options.tableId+' tbody>tr').get(0);
        $($(_tbody_first_row).children('td')[$(this).index()]).css({width:$(this).css('width')});
      });
      $('#'+options.tableId).find('thead,tfoot').hide();
    }
  	$('.panel-body[for="'+options.tableId+'"]').fadeOut(300, function(){
      $('#'+options.tableId).fadeIn(300).removeClass('hide');
      
      $('#'+options.tableId+' tbody>tr>td>textarea.truncation').each(function(){
        var origin_str = $(this).val();
        var raw_text = $('<div/>').html( strip_tags( _.unescape( origin_str ) ) ).text();
        var truncate_length = Number( $(this).data('truncateLength') );
        if ( mb_strlen( raw_text ) > truncate_length ) {
          var truncate_str = mb_substr( raw_text, 0, truncate_length - 1 );
          $(this).val( truncate_str + ' ...' ).css({height: $(this)[0].scrollHeight+'px'});
          var esc_str = raw_text.replace(/\"/mg, '&quot;');
          var collapse_link = '<a href="javascript:;" class="btn btn-default btn-sm collapse-col-data pull-right" style="position:absolute;right:8px;bottom:8px;" data-raw="'+ esc_str +'"><i class="fa fa-ellipsis-h" aria-hidden="true"></i> <i class="fa fa-level-up" aria-hidden="true"></i></a>';
          $(this).parent().append(collapse_link);
          if ( method === 'disabled' ) {
            $(this).next('.collapse-col-data').addClass('disabled');
          }
        }
      });
    });
<?php if ( $enable_view ) : ?>}<?php endif; ?>
    $('#'+options.tableId).find('img').error(function(){
      $(this).attr('src', '<?php echo $this->plugin_url; ?>assets/images/cdbt-noimage.png').parent('a').prop('disabled', true);
    });
    if (method === 'disabled') {
      return this.disabled();
    }
    
  <?php if ( isset($after_render_scripts) ) : ?>
    <?php echo $after_render_scripts; ?>
  <?php endif; ?>
    
  },
<?php if ( $enable_view ) : ?>
  changeView: function(e,target) {
    var options = this.options;
<?php if ( $ajax_load ) : ?>
    var data = this.deepCopy(this.items);
<?php else : ?>
    var data = ! options.isFiltering ? this.deepCopy(this.items) : this.deepCopy(this.filteredItems);
<?php endif; ?>
    var toView = $('.cdbt-table-views[for="'+options.tableId+'"]>label.active>input').val();
    var currentView = $('.cdbt-table-views[for="'+options.tableId+'"]').data().currentView;
    if (currentView !== toView) {
      $('.cdbt-table-views[for="'+options.tableId+'"]').attr('data-current-view', toView);
      $('.cdbt-table-views[for="'+options.tableId+'"]').data().currentView = toView;
    }
    if ('list' === toView) {
      $('#'+options.tableId+'-view').addClass('hide').html('');
      $('#'+options.tableId).fadeIn(200).removeClass('hide');
      
    } else {
      $('#'+options.tableId).hide();
      if ($.fn['kinetic'] !== undefined) {
        // To disable the draggable table
        $('#'+options.tableId).parent('.panel-table-wrapper').kinetic('stop');
      }
      $('#'+options.tableId+'-view').removeClass('hide');
      
      $('#'+options.tableId+'-view').empty();
      var thumbnails = [];
      var thumbnail_template = _.template('<?php echo $_thumb_template; ?>');
      _.each(data, function(row,i){
<?php if ( ! $ajax_load ) : ?>
        if ( i + 1 >= options.startIndex && i + 1 < options.startIndex + options.perPageLimit ) {
<?php endif; ?>
          var thumb_data = {};
          thumb_data['title'] = <?php if ( empty( $_thumb_title ) || 'auto' === strtolower( $_thumb_title ) ) : ?>''<?php else: ?>row['<?php echo $_thumb_title; ?>']<?php endif; ?>;
          var titled = <?php echo 'auto' === strtolower( $_thumb_title ) ? 'false' : 'true'; ?>;
          _.each(_.values(row), function(col){
            if ( ! _.isNull(col) && _.isString(col) && 'data:image' === col.substr(0, 10)) {
              thumb_data['src'] = col;
            } else
            if ( ! titled ) {
              thumb_data['title'] = col;
              //titled = String(Number(col)) !== col ? true : false;
              titled = true;
            }
          });
          if ( ! titled ) {
            thumb_data['title'] = i + 1;
          }
        <?php if ( ! empty( $_thumb_column ) ) : ?>
          if ( thumb_data.src === undefined ) {
            thumb_data['src'] = row['<?php echo $_thumb_column; ?>'];
          }
        <?php endif; ?>
          thumbnails.push(thumb_data);
<?php if ( ! $ajax_load ) : ?>
        }
<?php endif; ?>
      });
      if (thumbnails.length > 0) {
        _.each(thumbnails, function(val){
          $('#'+options.tableId+'-view').append(thumbnail_template(val));
        });
      } else {
        $('#'+options.tableId+'-view').append('<div class="text-center text-muted" style="margin-top:2.5em;margin-bottom:4em;cpacity:0.5;"><?php _e( "No result.", CDBT); ?></div>');
      }
      
      $('#'+options.tableId+'-view').find('img').each(function(){
        $(this).error(function(){
          $(this).attr('src', '<?php echo $this->plugin_url; ?>assets/images/cdbt-noimage.png').parent('a').prop('disabled', true);
        });
        var cropSize = $(this).parents('.crop-image').width();
        var imgSize = $.imageSize($(this).attr('src'));
        var adjust, longBoundary;
        if (imgSize.w > imgSize.h) { // landscape
          longBoundary = Math.ceil((imgSize.w * cropSize) / imgSize.h);
          adjust = <?php if ( 'auto' === $adjust_thumbnail['landscape'] ) : ?>(-1 * ((longBoundary - cropSize) / 2))<?php else: echo intval( $adjust_thumbnail['landscape'] ); endif; ?>;
          $(this).attr('width',longBoundary).attr('height',cropSize).css({left: adjust + 'px', maxWidth: 'none', maxHeight: 'none' });
        } else
        if (imgSize.h > imgSize.w) { // portrait
          longBoundary = Math.ceil((imgSize.h * cropSize) / imgSize.w);
          adjust = <?php if ( 'auto' === $adjust_thumbnail['portrait'] ) : ?>(-1 * ((longBoundary - cropSize) / 2))<?php else: echo intval( $adjust_thumbnail['portrait'] ); endif; ?>;
          $(this).attr('width',cropSize).attr('height',longBoundary).css({top: adjust + 'px', maxWidth: 'none', maxHeight: 'none' });
        } else { // square
          $(this).attr('width',cropSize);
        }
      });
      
    }
    
  },
<?php endif; ?>
<?php if ( $enable_editor ) : ?>
  cacheOpt: function(e,target){
    var options = this.options;
    var saveOpt = {};
    saveOpt['currentPage'] = options.currentPage;
    saveOpt['searchKeyword'] = options.searchKeyword;
<?php if ( $ajax_load ) : ?>
    saveOpt['filterKeyword'] = options.filterKeyword;
    saveOpt['cachePage'] = options.currentPage;
<?php endif; ?>
    saveOpt['sortedProperty'] = options.sortedProperty;
    saveOpt['currentSortDir'] = options.currentSortDir;
    docCookies.setItem( '<?php echo $table_name; ?>', JSON.stringify(saveOpt) );
    return;
  },
<?php endif; ?>
<?php if ( $ajax_load ) : ?>
  reload: function( args, callback ){
    //var options = this.options;
    var ajaxUrl = cdbt_<?php if ( is_admin() ) : ?>admin<?php else : ?>main<?php endif; ?>_vars.ajax_url;
    var post_data = {
      tableName: '<?php echo $table_name; ?>',
      queryAssets: this.baseQuery, 
      event: 'get_data', 
      newQuery: args, 
    };
    var ajax_params = {
      async: true, 
      url: ajaxUrl, 
      type: 'post', 
      data: post_data, 
      dataType: 'json', 
      cache: false,
    };
  	$('#<?php echo $table_id; ?>').animate({opacity: .5}, 150);
    var container_size = { w: $('.cdbt-table-wrapper[for="<?php echo $table_id; ?>"]').outerWidth(), h: $('.cdbt-table-wrapper[for="<?php echo $table_id; ?>"]').outerHeight() };
    $('.panel-body[for="<?php echo $table_id; ?>"]').css({position:'absolute', width:(container_size.w-2)+'px', top:(container_size.h/2-$.em2pxl(3))+'px', zIndex:999 }).fadeIn(300);
    $.ajax( ajax_params ).done( function( data, stat, xhr ) {
//console.log({ done: stat, data: data, xhr: xhr });
      var _totalItems = data.pop();
      if ( callback.options.isFiltering ) {
        callback.options.totalItems = _totalItems;
      } else {
        callback.options.totalItems = <?php echo $total_data; ?>;
      }
      $('#<?php echo $table_id; ?>').animate({opacity: 1}, 150);
      callback.render( data );
    }).fail( function( xhr, stat, err ) {
//console.log({ fail: stat, error: err, xhr: xhr });
      
    });
  },
<?php endif; ?>
  disabled: function(){
    var options = this.options;
    $('.cdbt-table-wrapper[for="'+options.tableId+'"]').find('input,a').prop('disabled', true);
    $('.cdbt-table-wrapper[for="'+options.tableId+'"]').find('button').on('click',function(e){
      e.preventDefault();
    });
  },
};
</script>