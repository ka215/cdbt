<?php
/**
 * Repeater Options array `$this->component_options` scheme
 * [
 * 'id' => @string is element id [require]
 * 'enableSearch' => @boolean Switching search form is hidden if `false`; default `true` [optional]
 * 'enableFilter' => @boolean Switching filter dropdown is hidden if `false`; default `true` [optional]
 * 'filters' => @array(assoc) is listing data [optional] array key is data-value, array value is display label
 * 'enableView' => @boolean Switching view button is hidden if `false`; default `true` [optional]
 * 'defaultView' => @mixed is view type of default [optional] (-1 (default), 'list', 'thumbnail')
 * 'enableEditor' => @boolean Operation button for editing is displayed if `true`; default `false` [optional] For `cdbt-edit` only
 * 'disableEdit' => @boolean Flag to disable the data editing because it can not identify a single data [optional] For `cdbt-edit` only
 * 'listSelectable' => @mixed can not select items of default [option] (false (default), 'single', 'multi')
 * 'staticHeight' => @mixed is auto height of default [optional] (-1 (default), true, false, integer)
 * 'pageIndex' => @integer is start page number [optional] (>= 0)
 * 'pageSize' => @integer is displayed data per page [optional] (5, 10, 20, 50, 100)
 * 'customRowScripts' => @array is customized row as javascript lines [optional]
 * 'columns' => @array(assoc) is listing label [require]
 * 'data' => @array(assoc) is listing data [require]
 * 'addClass' => @string [optional]
 * 'thumbnailTemplate' => @string [optional]
 */

/**
 * Parse options
 * ---------------------------------------------------------------------------
 */

// `id` section
if (isset($this->component_options['id']) && !empty($this->component_options['id'])) {
  $repeater_id = esc_attr__($this->component_options['id']);
} else {
  return;
}

// `search` section
$enable_search = isset($this->component_options['enableSearch']) && false === $this->component_options['enableSearch'] ? false : true;

// `filter` section
$enable_filter = isset($this->component_options['enableFilter']) && false === $this->component_options['enableFilter'] ? false : true;

if (empty($this->component_options['filters'])) {
  $enable_filter = false;
} else {
  $filters_list = [];
  foreach ($this->component_options['filters'] as $value => $label) {
    $filters_list = sprintf( '<li data-value="%s"><a href="#">%s</a></li>', $value, $label );
  }
}

// `view` section
$enable_view = isset($this->component_options['enableView']) && false === $this->component_options['enableView'] ? false : true;

if (isset($this->component_options['defaultView']) && in_array($this->component_options['defaultView'], [ 'list', 'thumbnail' ])) {
  $default_view = $this->component_options['defaultView'];
} else {
  $default_view = 'list';
}

// `enableEditor` section
$enable_editor = isset($this->component_options['enableEditor']) && true === $this->component_options['enableEditor'] ? true : false;

// `disableEdit` section
$disable_edit = isset($this->component_options['disableEdit']) && true === $this->component_options['disableEdit'] ? true : false;

// `listSelectable` section
if (isset($this->component_options['listSelectable']) && in_array($this->component_options['listSelectable'], [ 'single', 'multi' ])) {
  $list_selectable = "'" . esc_attr($this->component_options['listSelectable']) . "'";
} else {
  $list_selectable = 'false';
}

// `staticHeight` section
$static_height = -1;
if (isset($this->component_options['staticHeight'])) {
  $static_height = $this->strtobool($this->component_options['staticHeight']) ? 'true' : 'false';
}

// `pageIndex` section
if (isset($this->component_options['pageIndex']) && intval($this->component_options['pageIndex']) >= 0) {
  $page_index = intval($this->component_options['pageIndex']);
} else {
  $page_index = 0;
}

// `pageSize` section
if (isset($this->component_options['pageSize']) && intval($this->component_options['pageSize']) > 0) {
  $page_size = intval($this->component_options['pageSize']);
  if (!in_array($page_size, [5, 10, 20, 50, 100])) {
    $insert_position = 0;
    if (5 < $page_size && $page_size < 10) 
      $insert_position = 1;
    if (10 < $page_size && $page_size < 20) 
      $insert_position = 2;
    if (20 < $page_size && $page_size < 50) 
      $insert_position = 3;
    if (50 < $page_size && $page_size < 100) 
      $insert_position = 4;
    if (100 < $page_size) 
      $insert_position = 5;
  } else {
    $insert_position = -1;
  }
} else {
  $page_size = 10;
  $insert_position = -1;
}
if ($insert_position >= 0) {
  $insert_page_size_line = sprintf( '<li data-value="%d" data-selected="true"><a href="#">%d</a></li>', $page_size, $page_size );
}

// `customRowScripts` section
if (isset($this->component_options['customRowScripts']) && !empty($this->component_options['customRowScripts'])) {
  $custom_rows = $this->component_options['customRowScripts'];
}

// `columns` section
if (!isset($this->component_options['columns']) || empty($this->component_options['columns'])) {
  return;
} else {
  $columns = [];
  $numric_properties = [];
  $custom_columns = [];
  foreach ($this->component_options['columns'] as $i => $setting) {
    $columns[$i] = [
      'label' => $setting['label'], 
      'property' => $setting['property'], 
      'sortable' => isset($setting['sortable']) && $setting['sortable'] ? true : false,
    ];
    if (isset($setting['sortDirection']) && in_array(strtolower($setting['sortDirection']), [ 'asc', 'desc' ])) 
      $columns[$i]['sortDirection'] = $setting['sortDirection'];
    
    if (isset($setting['dataNumric']) && true === $setting['dataNumric']) 
      $numric_properties[] = $columns[$i]['property'];
    
    if (isset($setting['className']) && !empty($setting['className'])) 
      $columns[$i]['className'] = $setting['className'];
    
    if (isset($setting['width']) && intval($setting['width']) > 0) 
      $columns[$i]['width'] = intval($setting['width']);
    
    if (isset($setting['customColumnRenderer']) && !empty($setting['customColumnRenderer'])) {
      if (is_array($setting['customColumnRenderer']) && array_key_exists($columns[$i]['property'], $setting['customColumnRenderer'])) {
        $custom_columns[$columns[$i]['property']] = $setting['customColumnRenderer'][$columns[$i]['property']];
      } else {
        $custom_columns[$columns[$i]['property']] = $setting['customColumnRenderer'];
      }
    }
    
    if (isset($setting['customRowRenderer']) && !empty($setting['customRowRenderer'])) 
      $custom_rows = isset($custom_rows) ? array_merge($custom_rows, $setting['customRowRenderer']) : $setting['customRowRenderer'];
    
  }
}

//var_dump($columns);

// `data` section
if (!isset($this->component_options['data']) || empty($this->component_options['data'])) {
  return;
} else {
  $items = $this->component_options['data'];
}

// `addClass` section
if (!isset($this->component_options['addClass']) || empty($this->component_options['addClass'])) {
  $add_class = '';
} else {
  $add_class = $this->component_options['addClass'];
}

// `thumbnailTemplate` section
if (!isset($this->component_options['thumbnailTemplate']) || empty($this->component_options['thumbnailTemplate'])) {
  $thumbnail_template = '\'<div class="thumbnail repeater-thumbnail {{thumbnail_class}}" style="background: {{thumbnail_bgcolor}};"><img height="{{thumbnail_height}}" src="{{thumbnail_src}}" width="{{thumbnail_width}}"><span>{{thumbnail_title}}</span></div>\'';
} else {
  $thumbnail_template = $this->component_options['thumbnailTemplate'];
}

/**
 * Render the Repeater
 * ---------------------------------------------------------------------------
 */
?>
  <div class="repeater<?php echo $add_class; ?>" id="<?php echo $repeater_id; ?>">
  <?php if ($enable_search || $enable_filter || $enable_view || $enable_editor) : ?>
    <div class="repeater-header">
      <div class="repeater-header-left">
        <span class="repeater-title"></span>
      <?php if ($enable_editor) : ?>
        <div class="repeater-check-switch">
          <button type="button" class="btn btn-default" id="repeater-check-switch" title="<?php _e('Switch Checking', CDBT); ?>"><i class="fa fa-check-square-o"></i><span class="sr-only"><?php _e('Switch Checking', CDBT); ?></span></button>
        </div>
      <?php endif; ?>
      <?php if ($enable_search) : ?>
        <div class="repeater-search">
          <div class="search input-group">
            <input type="search" class="form-control" placeholder="<?php esc_html_e('Search', CDBT); ?>"/>
            <span class="input-group-btn">
              <button class="btn btn-default" type="button">
                <span class="glyphicon glyphicon-search"></span>
                <span class="sr-only"><?php esc_html_e('Search', CDBT); ?></span>
              </button>
            </span>
          </div>
        </div><!-- /.repeater-search -->
      <?php endif; ?>
      </div>
      <div class="repeater-header-right">
      <?php if ($enable_filter) : ?>
        <div class="btn-group selectlist repeater-filters" data-resize="auto">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="selected-label">&nbsp;</span>
            <span class="caret"></span>
            <span class="sr-only"><?php esc_html_e('Toggle Filters', CDBT); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
            <li data-value="all" data-selected="true"><a href="#"><?php esc_html_e('all', CDBT); ?></a></li>
            <?php echo implode("\n", $filters_list); ?>
          </ul>
          <input class="hidden hidden-field" name="filterSelection" readonly="readonly" aria-hidden="true" type="text"/>
        </div><!-- /.repeater-filters -->
      <?php endif; ?>
      <?php if ($enable_view) : ?>
        <div class="btn-group repeater-views" data-toggle="buttons">
          <label class="btn btn-default<?php if ('list' === $default_view) : ?> active<?php endif; ?>">
            <input name="repeaterViews" type="radio" value="list"><span class="glyphicon glyphicon-list"></span>
          </label>
          <label class="btn btn-default<?php if ('thumbnail' === $default_view) : ?> active<?php endif; ?>">
            <input name="repeaterViews" type="radio" value="thumbnail"><span class="glyphicon glyphicon-th"></span>
          </label>
        </div><!-- /.repeater-views -->
      <?php endif; ?>
      <?php if ($enable_editor) : ?>
        <div class="repeater-editor">
        <?php if ($disable_edit) : ?>
          <p class="text-danger" style="margin-top: 6px;"><?php _e('Disable the data editing because it can not identify a single data.', CDBT); ?></p>
        <?php else : ?>
          <button type="button" class="btn btn-default" id="repeater-editor-edit" title="<?php _e('Edit Data', CDBT); ?>"><i class="fa fa-pencil-square-o"></i><span class="sr-only"><?php _e('Edit Data', CDBT); ?></span></button>
          <button type="button" class="btn btn-default" id="repeater-editor-refresh" title="<?php _e('Refresh List', CDBT); ?>"><i class="fa fa-refresh"></i><span class="sr-only"><?php _e('Refresh List', CDBT); ?></span></button>
          <button type="button" class="btn btn-default" id="repeater-editor-delete" title="<?php _e('Delete Data', CDBT); ?>"><i class="fa fa-trash-o"></i><span class="sr-only"><?php _e('Delete Data', CDBT); ?></span></button>
        <?php endif; ?>
        </div><!-- /.repeater-editor -->
      <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
    <div class="repeater-viewport">
      <div class="repeater-canvas"></div>
      <div class="loader repeater-loader"></div>
    </div>
    <div class="repeater-footer">
      <div class="repeater-footer-left">
        <div class="repeater-itemization">
          <span><?php printf(esc_html__('%1$s - %2$s of %3$s items', CDBT), '<span class="repeater-start"></span>', '<span class="repeater-end"></span>', '<span class="repeater-count"></span>'); ?></span>
          <div class="btn-group selectlist" data-resize="auto">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
              <span class="selected-label">&nbsp;</span>
              <span class="caret"></span>
              <span class="sr-only"><?php esc_html_e('Toggle Dropdown', CDBT); ?></span>
            </button>
            <ul class="dropdown-menu" role="menu">
              <?php if ($insert_position === 0) echo $insert_page_size_line; ?>
              <li data-value="5"<?php if ($page_size === 5) : ?> data-selected="true"<?php endif; ?>><a href="#">5</a></li>
              <?php if ($insert_position === 1) echo $insert_page_size_line; ?>
              <li data-value="10"<?php if ($page_size === 10) : ?> data-selected="true"<?php endif; ?>><a href="#">10</a></li>
              <?php if ($insert_position === 2) echo $insert_page_size_line; ?>
              <li data-value="20"<?php if ($page_size === 20) : ?> data-selected="true"<?php endif; ?>><a href="#">20</a></li>
              <?php if ($insert_position === 3) echo $insert_page_size_line; ?>
              <li data-value="50"<?php if ($page_size === 50) : ?> data-selected="true"<?php endif; ?>><a href="#">50</a></li>
              <?php if ($insert_position === 4) echo $insert_page_size_line; ?>
              <li data-value="100"<?php if ($page_size === 100) : ?> data-selected="true"<?php endif; ?>><a href="#">100</a></li>
              <?php if ($insert_position === 5) echo $insert_page_size_line; ?>
            </ul>
            <input class="hidden hidden-field" name="itemsPerPage" readonly="readonly" aria-hidden="true" type="text"/>
          </div>
          <span><?php esc_html_e('Per Page', CDBT); ?></span>
        </div>
      </div>
      <div class="repeater-footer-right">
        <div class="repeater-pagination">
          <button type="button" class="btn btn-default btn-sm repeater-prev">
            <span class="glyphicon glyphicon-chevron-left"></span>
            <span class="sr-only"><?php esc_html_e('Previous Page', CDBT); ?></span>
          </button>
          <label class="page-label" id="cdbtPageLabel"><?php esc_html_e('Page', CDBT); ?></label>
          <div class="repeater-primaryPaging active">
            <div class="input-group input-append dropdown combobox">
              <input type="text" class="form-control" aria-labelledby="cdbtPageLabel">
              <div class="input-group-btn">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                  <span class="caret"></span>
                  <span class="sr-only"><?php esc_html_e('Toggle Dropdown', CDBT); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right"></ul>
              </div>
            </div>
          </div>
          <input type="text" class="form-control repeater-secondaryPaging" aria-labelledby="cdbtPageLabel">
          <span><?php printf(esc_html__('of %1$s', CDBT), '<span class="repeater-pages"></span>'); ?></span>
          <button type="button" class="btn btn-default btn-sm repeater-next">
            <span class="glyphicon glyphicon-chevron-right"></span>
            <span class="sr-only"><?php esc_html_e('Next Page', CDBT); ?></span>
          </button>
        </div>
      </div>
    </div>
  </div>
<script>
if (typeof repeater === 'undefined') {
  var repeater = {};
}
repeater['<?php echo $repeater_id; ?>'] = function() {

  // define the columns in your datasource
<?php /*
  var columns = [
    {
      label: 'Name &amp; Description',
        property: 'name',
        sortable: true
    },
    {
      label: 'Code',
      property: 'code',
      sortable: true
    }
  ];
*/ ?>
  var columns = <?php echo json_encode($columns); ?>
  
<?php /*
      // define the rows in your datasource
      var items = [];
      var statuses = ['archived', 'active', 'draft'];
      function getRandomStatus() {
        var min = 0;
        var max = 2;
        var index = Math.floor(Math.random() * (max - min + 1)) + min;
        return statuses[index];
      }
 
      for(var i=1; i<=100; i++) {
        var item = {
          id: i,
          name: 'item ' + i,
          code: 'code ' + i,
          description: 'desc ' + i,
          status: getRandomStatus()
        };
        items.push(item);
      }
*/ ?>
  var items = <?php echo json_encode($items); ?>

  
  function customColumnRenderer(helpers, callback) {
    // determine what column is being rendered
    var column = helpers.columnAttr;
    
    // get all the data for the entire row
    var rowData = helpers.rowData;
    var customMarkup = '';
    
    // only override the output for specific columns.
    // will default to output the text value of the row item
    switch(column) {
<?php /*
      case 'name':
        // let's combine name and description into a single column
        customMarkup = '<div style="font-size:12px;">' + rowData.name + '</div><div class="small text-muted">' + rowData.description + '</div>';
        break;
*/ ?>
<?php if (!empty($custom_columns)) :
  foreach ($custom_columns as $column => $custom_content) :
?>
      case '<?php echo $column; ?>':
        customMarkup = <?php echo $custom_content; ?>;
        break;
<?php 
  endforeach;
endif; ?>
      default:
        // otherwise, just use the existing text value
        customMarkup = helpers.item.text();
        break;
    }
    
    helpers.item.html(customMarkup);
    
    callback();
  }
  
  function customRowRenderer(helpers, callback) {
    // let's get the id and add it to the "tr" DOM element
    var item = helpers.item;
<?php
//  if (isset($this->component_options['customRowScripts']) && !empty($this->component_options['customRowScripts'])) :
  if (isset($custom_rows) && !empty($custom_rows)) :
//    echo implode("\n", $this->component_options['customRowScripts']);
    echo implode("\n", $custom_rows);
  endif;
?>
    //if ('' !== helpers.rowData.hobby) { var list = helpers.rowData.hobby.split(','); helpers.rowData.hobby = '<ul><li>' + list.join('</li><li>') + '</li></ul>'; };
    
    
    callback();
  }
  
  // this example uses an API to fetch its datasource.
  // the API handles filtering, sorting, searching, etc.
  function customDataSource(options, callback) {
    // set options
    var pageIndex = options.pageIndex;
    var pageSize = options.pageSize;

    var data = items;
<?php /*
    var new_options = {
      pageIndex: pageIndex,
      pageSize: pageSize,
      sortDirection: options.sortDirection,
      sortBy: options.sortProperty,
      filterBy: options.filter.value || '',
      searchBy: options.search || ''
    };
    
    // call API, posting options
    $.ajax({
      type: 'post',
      url: '/repeater/data',
      data: new_options
    })
    .done(function(data) {
      
      var items = data.items;
      var totalItems = data.total;
      var totalPages = Math.ceil(totalItems / pageSize);
      var startIndex = (pageIndex * pageSize) + 1;
      var endIndex = (startIndex + pageSize) - 1;
      
      if(endIndex > items.length) {
        endIndex = items.length;
      }
      
      // configure datasource
      var dataSource = {
        page: pageIndex,
        pages: totalPages,
        count: totalItems,
        start: startIndex,
        end: endIndex,
        columns: columns,
//        items: items
        items: {
          id : 1,
          name : 'wp_users', 
          description : 'TEXT TEXT ...'
        }
      };
      
      // invoke callback to render repeater
      callback(dataSource);
    });
*/ ?>

    // sort by
    data = _.sortBy(data, function(item) {
<?php if (!empty($numric_properties)) : 
    $conditions = [];
    foreach ($numric_properties as $property) {
      $conditions[] = sprintf("options.sortProperty === '%s'", $property);
    }
?>
      if (<?php echo implode(' || ', $conditions); ?>) {
        return parseFloat(item[options.sortProperty]);
      } else {
        return item[options.sortProperty];
      }
<?php else : ?>
      return item[options.sortProperty];
<?php endif; ?>
    });
    
    // sort direction
    if (options.sortDirection === 'desc') {
      data = data.reverse();
    }
    
<?php if ($enable_filter) : ?>
    // filter
    if (options.filter && options.filter.value !== 'all') {
      data = _.filter(data, function(item) {
        return item.status === options.filter.value;
      });
    }
<?php endif; ?>
    
<?php if ($enable_search) : ?>
    // search
    if (options.search && options.search.length > 0) {
      var searchedData = [];
      var searchTerm = options.search.toLowerCase();
      
      _.each(data, function(item) {
        var values = _.values(item);
        var found = _.find(values, function(val) {
          
          if(null === val) val = false;
          if(val.toString().toLowerCase().indexOf(searchTerm) > -1) {
            searchedData.push(item);
            return true;
          }
        });
      });
      
      data = searchedData;
    }
<?php endif; ?>
    
    var totalItems = data.length;
    var totalPages = Math.ceil(totalItems / pageSize);
    var startIndex = (pageIndex * pageSize) + 1;
    var endIndex = (startIndex + pageSize) - 1;
    if(endIndex > data.length) {
      endIndex = data.length;
    }
    
    data = data.slice(startIndex-1, endIndex);
    
    var dataSource = {
      page: pageIndex,
      pages: totalPages,
      count: totalItems,
      start: startIndex,
      end: endIndex,
      columns: columns,
      items: data
    };
    
    callback(dataSource);

  }
  
  // 初期化処理 - initialize the repeater
  var repeater = $('#<?php echo $repeater_id; ?>');
  repeater.repeater({
    list_selectable: <?php echo $list_selectable; ?>, // (single | multi)
    list_noItemsHTML: '<?php esc_html_e( 'nothing to see here... move along', CDBT); ?>',
    
    // カスタムレンダラを介して列出力をオーバーライドする - override the column output via a custom renderer.
    // これにより各列の出力のカスタムマークアップが可能になる - this will allow you to output custom markup for each column.
    list_columnRendered: customColumnRenderer,
    
    // カスタムレンダラを介して行出力をオーバーライドする - override the row output via a custom renderer.
    // この例では、各行に「id」属性を追加するために使用している - this example will use this to add an "id" attribute to each row.
    list_rowRendered: customRowRenderer,
    
    // データ検索処理のためのデータソースをセットアップする - setup your custom datasource to handle data retrieval;
    // 任意のページング、ソート、フィルタリング、検索ロジックを担当する - responsible for any paging, sorting, filtering, searching logic
    dataSource: customDataSource,
    
    // 初期ビューの設定。デフォルトは -1。「.repeater-views」要素の値を設定する。
    defaultView: '<?php echo $default_view; ?>', // 'list' or 'thumbnail'
    
    //dropPagingCap: 3, 
    
    <?php if ($static_height !== -1) printf('staticHeight: %s,', $static_height); ?>
    
    thumbnail_template: <?php echo $thumbnail_template; ?>,
    
  });

  $('#repeater-check-switch').on('click', function(){
    if (repeater.repeater('list_getSelectedItems').length > 0) {
      repeater.repeater('list_clearSelectedItems');
    } else {
      var _index = 0;
      $('input.row_where_condition').each(function(){
        var _ary = $(this).val().split(':');
        repeater.repeater('list_setSelectedItems', [ { index: _index }, { property: _ary[0], value: _ary[1] }]);
        _index++;
      });
    }
  });

};

if (typeof convert_list !== 'undefined') {
  var convert_list = null;
}
/**
 * Convert list type data as common utility function for repeater
 */
convert_list = function() {
  if (arguments.length === 0) {
    return;
  }
  
  var list_data = arguments[0].split(',');
  
  return _.reduce(list_data, function(memo, data){ return memo + '<li><small>' + data + '</small></li>'; }, '');
  
};

if (typeof convert_datetime !== 'undefined') {
  var convert_datetime = null;
}
/**
 * Convert datetime format as common utility function for repeater
 */
convert_datetime = function() {
  if (arguments.length < 2) {
    return arguments.length === 1 ? arguments[0] : false;
  }
  
  var datetime = new Date(arguments[0]);
  var format = arguments[1].join(' ');
  // year
  format = format.replace(/Y/g, datetime.getFullYear());
  format = format.replace(/y/g, ('' + datetime.getFullYear()).slice(-2));
  // month
  format = format.replace(/m/g, ('0' + (datetime.getMonth() + 1)).slice(-2));
  format = format.replace(/n/g, (datetime.getMonth() + 1));
  var month = { Jan: 'January', Feb: 'February', Mar: 'March', Apr: 'April', May: 'May', Jun: 'June', Jul: 'July', Aug: 'August', Sep: 'September', Oct: 'October', Nov: 'November', Dec: 'December' };
  format = format.replace(/F/g, _.find(month, datetime.getMonth()));
  format = format.replace(/F/g, _.findKey(month, datetime.getMonth()));
  // day
  format = format.replace(/d/g, ('0' + datetime.getDate()).slice(-2));
  format = format.replace(/j/g, datetime.getDate());
  var suffix = [ 'st', 'nd', 'rd', 'th' ];
  var suffix_index = function(){ var d = datetime.getDate(); return d > 3 ? 3 : d - 1; };
  format = format.replace(/S/g, suffix[suffix_index()]);
  var day = { Sun: 'Sunday', Mon: 'Monday', Tue: 'Tuesday', Wed: 'Wednesday', Thu: 'Thurseday', Fri: 'Friday', Sat: 'Saturday' };
  format = format.replace(/l/g, _.find(day, datetime.getDay()));
  format = format.replace(/D/g, _.findKey(day, datetime.getDay()));
  // time
  var half_hours = function(){ var h = datetime.getHours(); return h > 12 ? h - 12 : h; };
  var ampm = function(){ var h = datetime.getHours(); return h > 12 ? 'pm' : 'am' };
  format = format.replace(/a/g, ampm());
  format = format.replace(/A/g, ampm().toUpperCase());
  format = format.replace(/g/g, half_hours());
  format = format.replace(/h/g, ('0' + half_hours()).slice(-2));
  format = format.replace(/G/g, datetime.getHours());
  format = format.replace(/H/g, ('0' + datetime.getHours()).slice(-2));
  format = format.replace(/i/g, ('0' + datetime.getMinutes()).slice(-2));
  format = format.replace(/s/g, ('0' + datetime.getSeconds()).slice(-2));
  format = format.replace(/T/g, '');
  // other
  format = format.replace(/c/g, (arguments[0].replace(' ', 'T') + '+00:00'));
  format = format.replace(/r/g, datetime);
  
  return format;
};

</script>
