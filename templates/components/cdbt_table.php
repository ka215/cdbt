<?php
/**
 * Table Options array `$this->component_options` scheme
 * [
 * 'id' => @string is element id [optional] For default is `cdbtTable`
 * 'enableSearch' => @boolean Switching search form is hidden if `false`; default `true` [optional]
 * 'columns' => @array(assoc) is listing label [require]
 * 'data' => @array(assoc) is listing data [require]
 * 'pageIndex' => @integer is start page number [optional] (>= 0)
 * 'pageSize' => @integer is displayed data per page [optional]
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
  $table_id = esc_attr__($this->component_options['id']);
} else {
  $table_id = 'cdbtTable';
}

// `search` section
$enable_search = isset($this->component_options['enableSearch']) && false === $this->component_options['enableSearch'] ? false : true;

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
  foreach ($columns as $_col_atts) {
    $_cell_width = isset($_col_atts['width']) && intval($_col_atts['width']) > 0 ? ' style="width: '. $_col_atts['width'] .'px;"' : '';
    $_add_class = isset($_col_atts['className']) && !empty($_col_atts['className']) ? ' ' . esc_attr($_col_atts['className']) : '';
    if ($_col_atts['sortable']) {
      $_index_cols[] = sprintf('<th class="property-%s sortable sortdir-%s%s"%s><label>%s</label></th>', $_col_atts['property'], $_col_atts['sortDirection'], $_add_class, $_cell_width, $_col_atts['label']);
    } else {
      $_index_cols[] = sprintf('<th class="property-%s%s"%s><label>%s</label></th>', $_col_atts['property'], $_add_class, $_cell_width, $_col_atts['label']);
    }
    $_data_wrapper = isset($_col_atts['dataNumric']) && $_col_atts['dataNumric'] ? '<span class="data-numric"><%= '. $_col_atts['property'] .' %></span>' : '<span class="data-raw"><%= '. $_col_atts['property'] .' %></span>';
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
  /*
  $_data_rows = [];
  foreach ($items as $_i => $_row) {
    $_data_cols = [];
    foreach ($columns as $_col_atts) {
      $_data_cols[] = sprintf('<td class="property-%s">%s</td>', $_col_atts['property'], $_row[$_col_atts['property']]);
    }
    $_data_rows[] = sprintf('<tr id="row-%d">%s</tr>', $_i, implode("\n", $_data_cols));
  }
  $data_rows = implode("\n", $_data_rows);
  */
}


/**
 * Render the Repeater
 * ---------------------------------------------------------------------------
 */
?>
<div class="cdbt-table-wrapper">
<?php if ($enable_search) : ?>
  <div class="well-sm row">
    <div class="col-lg-4 pull-right">
      <div class="input-group" role="search" id="<?php echo $table_id; ?>-search">
        <input type="search" class="form-control" placeholder="<?php _e('Search', CDBT); ?>">
        <span class="input-group-btn">
          <button class="btn btn-default" type="button"><span class="glyphicon glyphicon-search"></span><span class="sr-only"><?php _e('Search', CDBT); ?></span></button>
        </span>
      </div><!-- /input-group -->
    </div><!-- /.col-lg-6 -->
  </div><!-- /.row -->
<?php endif; ?>
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
  <div class="pagination-wrapper text-center">
    <ul class="pagination pagination-sm">
      <li><a href="#" aria-label="<?php _e('Previous', CDBT); ?>"><span aria-hidden="true"><i class="fa fa-angle-double-left"></i></span></a></li>
      <li class="active"><a href="#">1</a></li>
      <li><a href="#" aria-label="<?php _e('Next', CDBT); ?>"><span aria-hidden="true"><i class="fa fa-angle-double-right"></i></span></a></li>
    </ul>
  </div>
</div>
<script>
if (typeof dynamicTable === 'undefined') {
  var dynamicTable = {};
}
dynamicTable['<?php echo $table_id; ?>'] = function() {
  
  <?php $json_code = json_encode($items); ?>
  var items = <?php echo 'new Array(' . substr($json_code, 1, -1) . ')'; ?>;
  
  var templateRow = '<?php echo $template_row; ?>';
  var perPageLimit = <?php echo intval($this->component_options['pageSize']); ?>;
  var currentPage = <?php echo intval($this->component_options['pageIndex']); ?>;
  
  var totalItems = items.length;
  var totalPages = Math.ceil(totalItems / perPageLimit);
  var startIndex = ((currentPage - 1) * perPageLimit) + 1;
  var endIndex = (startIndex + perPageLimit) - 1;
  
  if (endIndex > totalItems) {
    endIndex = totalItems;
  }
  
  var options = {
    tableId: '<?php echo $table_id; ?>', 
    templateRow: templateRow, 
    perPageLimit: perPageLimit, 
    currentPage: currentPage, 
    totalItems: totalItems, 
    totalPages: totalPages, 
    startIndex: startIndex, 
    endIndex: endIndex, 
  };
  
  console.info(options);
  
  function tableRender() {
    var all_data = items;
    
    options.startIndex = ((options.currentPage - 1) * options.perPageLimit) + 1;
    options.endIndex = (options.startIndex + options.perPageLimit) - 1;
    
    if (options.endIndex > options.totalItems) {
      options.endIndex = options.totalItems;
    }
    data = all_data.slice(options.startIndex-1, options.endIndex);
    
//console.info([ data.length, options ]);
    
    $('#'+options.tableId+' tbody').html('');
    
    _.each(data, function(rowData){
      var template = _.template(options.templateRow);
      
    <?php if (!empty($_custom_column_renders)) : foreach ($_custom_column_renders as $_col => $_val) : ?>
      rowData.<?php echo $_col; ?> = <?php echo $_val; ?>;
    <?php endforeach; endif; ?>
      
      return $('#'+options.tableId+' tbody').append( template(rowData) );
    });
    
    // Render pagenation
    if (options.totalPages > 1) {
      var pagination = '<ul class="pagination pagination-sm"><li><a href="#'+options.tableId+'" aria-label="Previous"><span aria-hidden="true"><i class="fa fa-angle-double-left"></i></span></a></li>';
      for (var i=1; i<=options.totalPages; i++) {
        if (options.currentPage === i) {
          pagination += '<li class="active"><a href="#'+options.tableId+'" aria-label="'+i+'">'+i+'</a></li>';
        } else {
          pagination += '<li><a href="#'+options.tableId+'" aria-label="'+i+'">'+i+'</a></li>';
        }
      }
      pagination += '<li><a href="#'+options.tableId+'" aria-label="Next"><span aria-hidden="true"><i class="fa fa-angle-double-right"></i></span></a></li></ul>';
      $('#'+options.tableId+'+div.pagination-wrapper').html( pagination );
    }
  
  };
  
  function sortBy() {
    
  };
  
  function searchFor( keyword ) {
    
    console.info(keyword);
    
  };
  
  // Event handler
  $(document).on('click', '#'+options.tableId+'+div.pagination-wrapper>ul.pagination>li>a', function(e){
    // Change pagination
    e.preventDefault();
    var ariaLabel = $(this).attr('aria-label');
    if ('Previous' === ariaLabel) {
      options.currentPage = (options.currentPage-1) < 1 ? 1 : options.currentPage-1;
    } else
    if ('Next' === ariaLabel) {
      options.currentPage = (options.currentPage+1) > options.totalPages ? options.totalPages : options.currentPage+1;
    } else {
      options.currentPage = Number(ariaLabel);
    }
    tableRender();
  });
  
  $(document).on('click', '#'+options.tableId+'-search button', function(e){
    // Find data in table
    e.preventDefault();
    if ($('#'+options.tableId+'-search input').val() === '') {
      return false;
    }
    
    searchFor( $('#'+options.tableId+'-search input').val() );
    
  });
  
  
  // Initial action
  tableRender();
  
};
console.info(dynamicTable);
</script>