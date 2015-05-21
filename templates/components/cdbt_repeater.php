<?php
// var_dump($this->component_options);
// array `$this->component_options` scheme
// [
//   'id' = @string is element id [require]
//   'pageIndex' = @integer is start page number [optional] (>= 0)
//   'pageSize' = @integer is displayed data per page [optional] (5, 10, 20, 50, 100)
//   'data' = @array(assoc) is listing data [require]

$repeater_id = $this->component_options['id'];
$items = [];
$i = 0;
foreach ($this->component_options['data'] as $key => $value) {
  $i++;
  $items[] = [
    'id' => $i,
    'name' => $value,
    'code' => $key,
  ];
}
?>
  <div class="repeater" id="<?php echo $repeater_id; ?>">
    <div class="repeater-header">
      <div class="repeater-header-left">
        <span class="repeater-title"><?php esc_html_e('', CDBT); ?></span>
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
        </div>
      </div>
      <div class="repeater-header-right">
        <div class="btn-group selectlist repeater-filters" data-resize="auto">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="selected-label">&nbsp;</span>
            <span class="caret"></span>
            <span class="sr-only"><?php esc_html_e('Toggle Filters', CDBT); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
            <li data-value="all" data-selected="true"><a href="#"><?php esc_html_e('all', CDBT); ?></a></li>
            <li data-value="some"><a href="#"><?php esc_html_e('some', CDBT); ?></a></li>
            <li data-value="others"><a href="#"><?php esc_html_e('others', CDBT); ?></a></li>
          </ul>
          <input class="hidden hidden-field" name="filterSelection" readonly="readonly" aria-hidden="true" type="text"/>
        </div>
        <div class="btn-group repeater-views" data-toggle="buttons">
          <label class="btn btn-default active">
            <input name="repeaterViews" type="radio" value="list"><span class="glyphicon glyphicon-list"></span>
          </label>
          <label class="btn btn-default">
            <input name="repeaterViews" type="radio" value="thumbnail"><span class="glyphicon glyphicon-th"></span>
          </label>
        </div>
      </div>
    </div>
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
              <li data-value="5"<?php if (intval($this->component_options['pageSize']) <= 5): ?> data-selected="true"<?php endif; ?>><a href="#">5</a></li>
              <li data-value="10"<?php if (intval($this->component_options['pageSize']) == 10): ?> data-selected="true"<?php endif; ?>><a href="#">10</a></li>
              <li data-value="20"<?php if (intval($this->component_options['pageSize']) == 20): ?> data-selected="true"<?php endif; ?>><a href="#">20</a></li>
              <li data-value="50"<?php if (intval($this->component_options['pageSize']) == 50): ?> data-selected="true"<?php endif; ?> data-foo="bar" data-fizz="buzz"><a href="#">50</a></li>
              <li data-value="100"<?php if (intval($this->component_options['pageSize']) >= 100): ?> data-selected="true"<?php endif; ?>><a href="#">100</a></li>
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
          <label class="page-label" id="myPageLabel"><?php esc_html_e('Page', CDBT); ?></label>
          <div class="repeater-primaryPaging active">
            <div class="input-group input-append dropdown combobox">
              <input type="text" class="form-control" aria-labelledby="myPageLabel">
              <div class="input-group-btn">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                  <span class="caret"></span>
                  <span class="sr-only"><?php esc_html_e('Toggle Dropdown', CDBT); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right"></ul>
              </div>
            </div>
          </div>
          <input type="text" class="form-control repeater-secondaryPaging" aria-labelledby="myPageLabel">
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
var repeater = function() {

  // define the columns in your datasource
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
  
/*
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
*/
  items = <?php echo json_encode($items); ?>

  
  function customColumnRenderer(helpers, callback) {
    // determine what column is being rendered
    var column = helpers.columnAttr;
    
    // get all the data for the entire row
    var rowData = helpers.rowData;
    var customMarkup = '';
    
    // only override the output for specific columns.
    // will default to output the text value of the row item
    switch(column) {
      case 'name':
        // let's combine name and description into a single column
        customMarkup = '<div style="font-size:12px;">' + rowData.name + '</div><div class="small text-muted">' + rowData.description + '</div>';
        break;
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
    item.attr('id', 'row' + helpers.rowData.id);
    
    callback();
  }
  
  // this example uses an API to fetch its datasource.
  // the API handles filtering, sorting, searching, etc.
  function customDataSource(options, callback) {
    // set options
    console.info(options);
<?php if (isset($this->component_options['pageIndex']) && intval($this->component_options['pageIndex']) >= 0 ) : ?>
    options.pageIndex = <?php echo intval($this->component_options['pageIndex']); ?>;
<?php endif; ?>
<?php if (isset($this->component_options['pageSize']) && intval($this->component_options['pageSize']) > 0 ) : ?>
    options.pageSize = <?php echo intval($this->component_options['pageSize']); ?>;
<?php endif; ?>
    var pageIndex = options.pageIndex;
    var pageSize = options.pageSize;

    var data = items;
/*
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
*/

    // sort by
    data = _.sortBy(data, function(item) {
      return item[options.sortProperty];
    });
    
    // sort direction
    if (options.sortDirection === 'desc') {
      data = data.reverse();
    }
    
    // filter
    if (options.filter && options.filter.value !== 'all') {
      data = _.filter(data, function(item) {
        return item.status === options.filter.value;
      });
    }
    
    // search
    if (options.search && options.search.length > 0) {
      var searchedData = [];
      var searchTerm = options.search.toLowerCase();
      
      _.each(data, function(item) {
        var values = _.values(item);
        var found = _.find(values, function(val) {
          
          if(val.toString().toLowerCase().indexOf(searchTerm) > -1) {
            searchedData.push(item);
            return true;
          }
        });
      });
      
      data = searchedData;
    }
    
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
  
  // initialize the repeater
  var repeater = $('#<?php echo $repeater_id; ?>');
  repeater.repeater({
    list_selectable: false, // (single | multi)
    list_noItemsHTML: 'nothing to see here... move along',
    
    // override the column output via a custom renderer.
    // this will allow you to output custom markup for each column.
    list_columnRendered: customColumnRenderer,
    
    // override the row output via a custom renderer.
    // this example will use this to add an "id" attribute to each row.
    list_rowRendered: customRowRenderer,
    
    // setup your custom datasource to handle data retrieval;
    // responsible for any paging, sorting, filtering, searching logic
    dataSource: customDataSource
  });

};
</script>
