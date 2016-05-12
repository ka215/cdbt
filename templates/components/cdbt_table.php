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
 * 'columns' => @array(assoc) is listing label [require]
 * 'data' => @array(assoc) is listing data [require]
 * 'pageIndex' => @integer is start page number [optional] (>= 0)
 * 'pageSize' => @integer is displayed data per page [optional]
 * 'customRowScripts' => @array is customized row as javascript lines [optional]
 * 'customBeforeRender' => @string is custom javascript [optional]
 * 'customAfterRender' => @string is custom javascript [optional]
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
if (isset($this->component_options['id']) && !empty($this->component_options['id'])) {
  $rand_hash = $this->create_hash( $this->component_options['id'] . mt_rand() );
  $table_id = esc_attr__( $this->component_options['id'] .'-'. $rand_hash );
} else {
  $table_id = 'cdbtTable';
}

// `search` section
$enable_search = isset($this->component_options['enableSearch']) ? $this->strtobool( $this->component_options['enableSearch'] ) : false;

// `filter` section
$enable_filter = isset($this->component_options['enableFilter']) ? $this->strtobool( $this->component_options['enableFilter'] ) : false;

if (empty($this->component_options['filter_column']) || empty($this->component_options['filters'])) {
  $enable_filter = false;
} else {
  $filter_column = $this->component_options['filter_column'];
  $filters_list = [];
  foreach ($this->component_options['filters'] as $value => $label) {
    $filters_list[] = sprintf( '<li data-value="%s"><a href="#">%s</a></li>', $value, $label );
  }
}

// `view` section
$enable_view = isset($this->component_options['enableView']) ? $this->strtobool( $this->component_options['enableView'] ) : false;

if (isset($this->component_options['defaultView']) && in_array($this->component_options['defaultView'], [ 'list', 'thumbnail' ])) {
  $default_view = $this->component_options['defaultView'];
} else {
  $default_view = 'list';
}

// `enableEditor` section
$enable_editor = isset($this->component_options['enableEditor']) ? $this->strtobool( $this->component_options['enableEditor'] ) : false;

// `disableEdit` section
$disable_edit = isset($this->component_options['disableEdit']) ? $this->strtobool( $this->component_options['disableEdit'] ) : false;

// Additional classes section
foreach ($this->component_options as $_optkey => $_optval) {
  if (in_array($_optkey, [ 'tableClass', 'theadClass', 'tbodyClass', 'tfootClass' ] )) {
    $var_name = str_replace('Class', '_class', $_optkey);
    ${$var_name} = !isset($_optval) || empty($_optval) ? [] : explode(' ', $_optval);
    // filter
    // 
    // @since 2.0.0
    ${$var_name} = apply_filters( 'cdbt_table_class_additions', ${$var_name}, $table_id );
    
    ${$var_name} = empty(${$var_name}) ? '' : ' ' . implode(' ', ${$var_name});
  }
}

// `columns` section
if (!isset($this->component_options['columns']) || empty($this->component_options['columns'])) {
  return;
} else {
  $columns = $this->component_options['columns'];
  $_row_line = '<tr>%s</tr>';
  $_index_cols = $_tmpl_data_cols = $_custom_column_renders = [];
  $_sortable_cols = 0;
  foreach ($columns as $_col_atts) {
    $_cell_width = isset($_col_atts['width']) && intval($_col_atts['width']) > 0 ? ' style="width: '. $_col_atts['width'] .'px;"' : '';
    $_add_class = isset($_col_atts['className']) && !empty($_col_atts['className']) ? ' ' . esc_attr($_col_atts['className']) : '';
    if ($_col_atts['sortable']) {
      $_index_cols[] = sprintf('<th data-property="%s" class="sortable sortdir-%s%s"%s><label>%s</label></th>', $_col_atts['property'], $_col_atts['sortDirection'], $_add_class, $_cell_width, $_col_atts['label']);
      $_sortable_cols++;
    } else {
      $_index_cols[] = sprintf('<th data-property="%s" class="%s"%s><label>%s</label></th>', $_col_atts['property'], $_add_class, $_cell_width, $_col_atts['label']);
    }
    //$_data_wrapper = isset($_col_atts['dataNumric']) && $_col_atts['dataNumric'] ? '<span class="data-numric"><%= '. $_col_atts['property'] .' %></span>' : '<span class="data-'. $_col_atts['dataType'] .'"><%= '. $_col_atts['property'] .' %></span>';
    if ( isset( $_col_atts['dataNumric'] ) && $_col_atts['dataNumric'] ) {
      $_data_wrapper = '<span class="data-numric"><%= '. $_col_atts['property'] .' %></span>';
    } else
    if ( isset( $_col_atts['dataType'] ) && strpos( $_col_atts['dataType'], 'text' ) !== false ) {
      $_data_wrapper = '<textarea class="data-'. $_col_atts['dataType'] .'" readonly><%= '. $_col_atts['property'] .' %></textarea>';
    } else {
      $_data_wrapper = '<span class="data-'. $_col_atts['dataType'] .'"><%= '. $_col_atts['property'] .' %></span>';
    }
    $_tmpl_data_cols[] = sprintf('<td class="property-%s%s"%s>%s</td>', $_col_atts['property'], $_add_class, $_cell_width, $_data_wrapper);
    if (isset($_col_atts['customColumnRenderer']) && !empty($_col_atts['customColumnRenderer'])) {
      $_custom_column_renders[$_col_atts['property']] = $_col_atts['customColumnRenderer'];
    }
  }
  $index_row = sprintf($_row_line, implode("\n", $_index_cols));
  $template_row = sprintf($_row_line, implode('', $_tmpl_data_cols));
}

// `data` section
if (!isset($this->component_options['data']) || empty($this->component_options['data'])) {
  return;
} else {
  $items = $this->component_options['data'];
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

// `Paging` section
$_must_paging  = ceil(count($items) / $this->component_options['pageSize']) > 1;

/**
 * Render the Repeater
 * ---------------------------------------------------------------------------
 */
?>
<div class="panel panel-default cdbt-table-wrapper" for="<?php echo $table_id; ?>">
<?php if ( $enable_search || $enable_filter || $enable_view || $enable_editor ) : ?>
  <div class="panel-heading" for="<?php echo $table_id; ?>">
    <div class="row">
<?php if ( $enable_search ) : ?>
      <div class="col-xs-6 col-md-4">
        <div class="input-group" role="search" id="<?php echo $table_id; ?>-search">
          <input type="search" class="form-control" placeholder="<?php _e( 'Search', CDBT ); ?>">
          <span class="input-group-btn">
            <button class="btn btn-default" type="button"><i class="fa fa-search" aria-hidden="true"></i><span class="sr-only"><?php _e( 'Search', CDBT ); ?></span></button>
          </span>
        </div><!-- /input-group -->
      </div><!-- /.col-md-4 -->
<?php endif; ?>
      <div class="col-xs-12 col-sm-6 col-md-8">
<?php if ( $enable_filter ) : ?>
        <div class="btn-group selectlist repeater-filters" data-resize="auto">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="selected-label">&nbsp;</span>
            <span class="caret"></span>
            <span class="sr-only"><?php _e( 'Toggle Filters', CDBT ); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
            <li data-value="all" data-selected="true"><a href="#"><?php _e( 'all', CDBT ); ?></a></li>
            <?php echo implode( "\n", $filters_list ); ?>
          </ul>
          <input class="hidden hidden-field" name="filterSelection" readonly="readonly" aria-hidden="true" type="text"/>
        </div><!-- /.repeater-filters -->
<?php endif; ?>
<?php if ( $enable_view ) : ?>
        <div class="btn-group repeater-views" data-toggle="buttons">
          <label class="btn btn-default<?php if ( 'list' === $default_view ) : ?> active<?php endif; ?>">
            <input name="repeaterViews" type="radio" value="list"><span class="glyphicon glyphicon-list"></span>
          </label>
          <label class="btn btn-default<?php if ( 'thumbnail' === $default_view ) : ?> active<?php endif; ?>">
            <input name="repeaterViews" type="radio" value="thumbnail"><span class="glyphicon glyphicon-th"></span>
          </label>
        </div><!-- /.repeater-views -->
<?php endif; ?>
<?php if ( $enable_editor ) : ?>
        <div class="repeater-editor<?php if ( $enable_filter ) : ?> pull-right<?php endif; ?>">
        <?php if ( $disable_edit ) : ?>
          <p class="text-danger" style="margin-top: 6px;"><?php _e( 'Disable the data editing because it can not identify a single data.', CDBT ); ?></p>
        <?php else : ?>
          <button type="button" class="btn btn-default" id="repeater-editor-edit" title="<?php _e( 'Edit Data', CDBT ); ?>"><i class="fa fa-pencil-square-o"></i><span class="sr-only"><?php _e( 'Edit Data', CDBT ); ?></span></button>
          <button type="button" class="btn btn-default" id="repeater-editor-refresh" title="<?php _e( 'Refresh List', CDBT ); ?>"><i class="fa fa-refresh"></i><span class="sr-only"><?php _e( 'Refresh List', CDBT ); ?></span></button>
          <button type="button" class="btn btn-default" id="repeater-editor-delete" title="<?php _e( 'Delete Data', CDBT ); ?>"><i class="fa fa-trash-o"></i><span class="sr-only"><?php _e( 'Delete Data', CDBT ); ?></span></button>
        <?php endif; ?>
        </div><!-- /.repeater-editor -->
<?php endif; ?>
      </div><!-- /.col-md-8 -->
    </div><!-- /.row -->
  </div><!-- /.panel-heading -->
<?php endif; ?>
  <div class="panel-body hide" for="<?php echo $table_id; ?>">
  </div><!-- /.panel-body -->
  <div class="panel-table-wrapper">
  <table class="table<?php echo empty($table_class) ? ' table-striped table-bordered table-hover' : $table_class; ?>" id="<?php echo $table_id; ?>">
    <thead class="<?php echo $thead_class; ?>">
      <?php echo $index_row; ?>
    </thead>
    <tbody class="<?php echo $tbody_class; ?>">
      <?php /* echo $data_rows; */ ?>
    </tbody>
    <tfoot class="<?php echo $tfoot_class; ?>">
      <?php echo $index_row; ?>
    </tfoot>
  </table>
  </div>
<?php if ( $_must_paging ) : ?>
  <div class="panel-footer" for="<?php echo $table_id; ?>">
    <nav class="cdbt-pagination text-center" for="<?php echo $table_id; ?>"></nav>
  </div><!-- /.panel-footer -->
<?php endif; ?>
</div>
<script>
// For doing inherit class
if (typeof DynamicTables === 'undefined') {
  var DynamicTables = {};
}
DynamicTables['<?php echo $table_id; ?>'] = function() {
  this.init();
};
DynamicTables['<?php echo $table_id; ?>'].prototype = {
<?php $json_code = json_encode($items); ?>
  items: new Array(<?php echo substr($json_code, 1, -1); ?>),
  init: function() {
    var items = this.items;
    
    var templateRow = '<?php echo $template_row; ?>';
    var perPageLimit = <?php echo intval($this->component_options['pageSize']); ?>;
    var currentPage = <?php echo intval($this->component_options['pageIndex']); ?>;
    
    var startIndex = ((currentPage - 1) * perPageLimit) + 1;
    var endIndex = (startIndex + perPageLimit) - 1;
    
    this.options = {
      tableId: '<?php echo $table_id; ?>', 
      templateRow: templateRow, 
      perPageLimit: perPageLimit, 
      currentPage: currentPage, 
      totalItems: items.length, 
      totalPages: Math.ceil(items.length / perPageLimit), 
      startIndex: startIndex, 
      endIndex: endIndex > items.length ? items.length : endIndex, 
    };
    
    // Add Event Listener
    var _self = this;
<?php if ( $_sortable_cols > 0 ) : ?>
    $('#<?php echo $table_id; ?> thead th.sortable').on('click', function(e){ _self.sortBy(e,$(this)); }); // sort
<?php endif; ?>
<?php if ( $enable_search ) : ?>
    $('#<?php echo $table_id; ?>-search button').on('click', function(e){ _self.searchFor(e,$(this)); }); // search
<?php endif; ?>
<?php if ( $_must_paging ) : ?>
    $(document).on('click', 'nav.cdbt-pagination[for="<?php echo $table_id; ?>"] a', function(e){ _self.pageFeed(e,$(this)); }); // paging
<?php endif; ?>
    
  }, 
  deepCopy: function(object) {
    return JSON.parse(JSON.stringify(Array.prototype.slice.call(object,0)));
  },
  render: function(filtereditems) {
    var data = typeof filtereditems === 'undefined' ? this.deepCopy(this.items) : filtereditems;
    var options = this.options;
    
    options.startIndex = ((options.currentPage - 1) * options.perPageLimit) + 1;
    options.endIndex = (options.startIndex + options.perPageLimit) - 1;
    
    if (options.endIndex > options.totalItems) {
      options.endIndex = options.totalItems;
    }
    data = data.slice(options.startIndex-1, options.endIndex);
    
    // customBeforeRenderer
    this.beforeRender();
    
    $('#'+options.tableId+' tbody').empty();
    _.each(data, function(rowData){
      var template = _.template(options.templateRow);
      
      // customColumnRenderer
    <?php if (!empty($_custom_column_renders)) : foreach ($_custom_column_renders as $_col => $_val) : ?>
      rowData['<?php echo $_col; ?>'] = <?php echo $_val; ?>;
    <?php endforeach; endif; ?>
      
      var rowMarkup = template(rowData);
      // customRowRenderer
    <?php if (isset($custom_rows) && !empty($custom_rows)) : ?>
      <?php echo implode("\n", $custom_rows); ?>
    <?php endif; ?>
      var customRowMarkup = $('<div/>').html(rowMarkup);
      customRowMarkup.find('.binary-data input[type=hidden]').each(function(){
        if ('data:image' === $(this).attr('data').substr(0, 10)) {
          $(this).replaceWith('<img src="'+ $(this).attr('data') +'" class="'+ $(this).attr('data-class') +'">');
        } else {
          if ('' !== $(this).attr('data')) {
            var where_conditions = [];
            _.each($(this).parent().attr('data-where-conditions').split(','), function(v){ where_conditions.push(v + ':' + helpers.rowData[v]); });
            $(this).parent().attr('data-where-conditions', where_conditions.join(','));
            $(this).replaceWith('<i class="fa fa-file-o"></i> ' + decodeURIComponent($(this).attr('data')));
          }
        }
      });
      
      $('#'+options.tableId+' tbody').append( customRowMarkup.html() );
    });
    
    var cols = $('#'+options.tableId+' thead').find('th').size() > 0 ? $('#'+options.tableId+' thead').find('th').size() : 1;
    if ('' === $('#'+options.tableId+' tbody').text()) {
      $('#'+options.tableId+' tbody').html('<tr><td colspan="'+cols+'" class="no-item"><?php _e( 'No result.', CDBT); ?></td></tr>');
    }
    
    // Adjust cell size
    $('#'+options.tableId+' tbody>tr>td>textarea').each(function(e){
      var origin_str = $(this).val();
      var _tmp = $('<div/>').html($(this).val());
      var truncated_str = _tmp.text();
      var collapse_link = _tmp.find('.collapse-col-data');
      if (collapse_link.size()) {
        var full_str = collapse_link.data('raw');
        collapse_link.addClass('pull-right').css({position:'absolute',right:'8px',bottom:'8px'});
        $(this).val(truncated_str).html(truncated_str).parent().append(collapse_link);
        $(this).css({height: $(this)[0].scrollHeight+'px'});
        $(this).val(full_str).html(full_str);
      }
    });
    
    // Adjust table size
    if ($.em2pxl(4) * cols > $('.cdbt-table-wrapper[for="'+options.tableId+'"]').parent().width()) {
      $('#'+options.tableId).css({ overflow: 'hidden', overflowX: 'scroll' });
      if ($.fn['kinetic'] !== undefined) {
        // To enable the draggable table
        $('#'+options.tableId).parent('.panel-table-wrapper').kinetic({
          filterTarget: function(target, e){
            if (!/down|start/.test(e.type)){
              return !(/span|area|a|input/i.test(target.tagName));
            }
          }
        });
      }
    } else {
      $('#'+options.tableId).css({ tableLayout: 'fixed' });
    }
    
    if (typeof Clipboard === 'function') {
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
    // Render pagenation
    if (options.totalPages > 1 && data.length >= options.perPageLimit) {
      this.pagination();
    } else {
      $('.cdbt-pagination[for="'+options.tableId+'"]').html('');
    }
<?php endif; ?>
    
    // Adjust current display position
    var componentPos = $('.cdbt-table-wrapper[for="'+options.tableId+'"]').offset();
    var topMargin = $('.cdbt-table-wrapper[for="'+options.tableId+'"]').prev('.sub-description-title').size() === 1 ? $('.cdbt-table-wrapper[for="'+options.tableId+'"]').prev('.sub-description-title').outerHeight() + 40 : 30;
    var adminBar = $('#wpadminbar').size() > 0 ? $('#wpadminbar').height() : 0;
    $(window).scrollTop(componentPos.top - topMargin - adminBar);
    
    // customAfterRenderer
    this.afterRender();
    
  }, 
<?php if ( $_sortable_cols > 0 ) : ?>
  objArraySort: function(data,prop,order){
    data.sort(function(a,b){
      if (_.isNumber(a[prop]) && _.isNumber(b[prop])) {
        return a[prop] - b[prop];
      } else {
        var _tmp_a = _.each(a[prop].match(/[0-9]+\.?[0-9]*/g), function(v,i){ return parseFloat(v[i]); });
        var _tmp_b = _.each(b[prop].match(/[0-9]+\.?[0-9]*/g), function(v,i){ return parseFloat(v[i]); });
        if (!_.isNull(_tmp_a) && !_.isNull(_tmp_b)) {
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
  sortBy: function(e,target) {
    e.preventDefault();
  	var data = this.deepCopy(this.items);
    var options = this.options;
    var sortedProperty = target.data('property');
    if (!target.hasClass('sorted')) {
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
    this.items = this.objArraySort(data,sortedProperty,currentSortDir);
    this.render();
    
  }, 
<?php endif; ?>
<?php if ( $enable_search ) : ?>
  searchFor: function(e,target) {
    e.preventDefault();
    var data = this.deepCopy(this.items);
    var options = this.options;
    var keyword = $('#'+options.tableId+'-search input').val().toLowerCase();
    var searchedData = [];
    if ('' === keyword) {
      target.find('i').attr('class', 'fa fa-search');
      return false;
    }
    if (target.find('i').hasClass('fa-close')) {
      $('#'+options.tableId+'-search').find('input').val('');
      target.find('i').attr('class', 'fa fa-search');
      this.render();
    } else {
      _.each(data, function(item){
        var values = _.values(item);
        var found = _.find(values, function(v) {
          if (null === v) v = false;
          if (v.toString().toLowerCase().indexOf(keyword) > -1) {
            searchedData.push(item);
            return true;
          }
        });
      });
      target.find('i').attr('class', 'fa fa-close');
      this.render(searchedData);
    }
    
  }, 
<?php endif; ?>
<?php if ( $_must_paging ) : ?>
  pageFeed: function(e,target) {
    e.preventDefault();
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
    this.render();
  }, 
  pagination: function() {
    var options = this.options;
    var disp = 5;
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
<?php endif; ?>
  beforeRender: function() {
  <?php if ( isset($before_render_scripts) ) : ?>
console.log('beforeRender');
    <?php echo $before_render_scripts; ?>
  <?php endif; ?>
    
  },
  afterRender: function() {
  <?php if ( isset($after_render_scripts) ) : ?>
console.log('afterRender');
    <?php echo $after_render_scripts; ?>
  <?php endif; ?>
    
  }
};
</script>