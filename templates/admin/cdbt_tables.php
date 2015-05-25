<?php
/**
 * Template : Tables Management Page
 * URL: `/wp-admin/admin.php?page=cdbt_tables`
 *
 * @since 2.0.0
 *
 */
$options = get_option($this->domain_name);
$tabs = [
  'table_list' => esc_html__('Table List', CDBT), 
  'wp_core_table' => esc_html__('Core Tables', CDBT), 
  'create_table' => esc_html__('Create Table', CDBT), 
  'modify_table' => esc_html__('Modify Table', CDBT), 
  'table_info' => esc_html__('Table Detail', CDBT), 
  'view_data' => esc_html__('View Data', CDBT), 
  'entry_data' => esc_html__('Entry Data', CDBT), 
  'edit_data' => esc_html__('Edit Data', CDBT), 
];
$default_tab = 'table_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;

//var_dump($this->cdbt_sessions);

// 最終的に Common Utility にする関数



function cdbt_admin_users_listing_value_shortcode( $html_value, $shortcode_name, $table, $col, $row ) {
  if ( 'nasmiru-list' !== $shortcode_name || 'nas_admin_info' !== $table ) 
    return $html_value;
  
  if ( 'admin_id' === $col || 'user_account' === $col ) 
    $html_value = sprintf('<a href="%s">%s</a>', add_query_arg([ 'tab' => 'user_edit', 'admin_id' => $row->admin_id ]), $html_value );
  
  if ( 'user_id' === $col ) 
    $html_value = sprintf('<a href="%s">%s</a>', add_query_arg([ 'user_id' => $html_value ], 'user-edit.php'), $html_value );
  
  if ( 'created' === $col || 'updated' === $col ) 
    $html_value = date(__('Y/m/d H:i', NMP), strtotime($html_value));
  
  return $html_value;
}
add_filter( 'cdbt_listing_value_shortcode', 'cdbt_admin_users_listing_value_shortcode', 10, 5 );

function cdbt_admin_users_operate_elements_shortcode( $operate_elements, $shortcode_name, $table, $row ) {
  if ( 'nasmiru-list' !== $shortcode_name || 'nas_admin_info' !== $table ) 
    return $operate_elements;
  
  foreach ($operate_elements as $i => $element) {
    if (preg_match('/^<a\shref=\"([a-zA-Z0-9\._\-\/]+)\?(.*)\"\s.*$/iU', $element, $matches) && is_array($matches) && array_key_exists(2, $matches)) {
      $queries = [];
      foreach (explode('&#038;', $matches[2]) as $query_seed) {
        list($key, $value) = explode('=', $query_seed);
        $queries[trim($key)] = trim($value);
      }
      unset($queries['line_id']);
      $queries['admin_id'] = $row->admin_id;
      
      if (in_array($queries['action'], [ 'detail', 'edit' ])) 
        $queries['tab'] = 'user_edit';
      
      $request_to = esc_url(add_query_arg( $queries, $matches[1] ));
      $element = preg_replace('/^(<a\shref=\").*(\"\s.*)$/iU', "$1{$request_to}$2", $element);
      
      if (intval($row->delete_flag) === 1 && 'delete' === $queries['action']) 
        $element = preg_replace('/^(.*)(>.*)$/iU', "$1 disabled$2", $element);
      
      $operate_elements[$i] = $element;
    }
  }
  
  return $operate_elements;
}
add_filter( 'cdbt_operate_elements_shortcode', 'cdbt_admin_users_operate_elements_shortcode', 10, 4 );

/**
 * Actions this page
 */

if (isset($_GET['action']) && !empty($_GET['action'])) {
  switch ($_GET['action']) {
    case 'delete': 
      if ('user_list' === $_GET['tab'] && isset($_GET['admin_id']) && intval($_GET['admin_id']) > 0) {
//        var_dump('deleted!');
      }
      break;
    case 'detail': 
      if ('user_edit' === $_GET['tab'] && isset($_GET['admin_id']) && intval($_GET['admin_id']) > 0) {
        $dynamic_tab_title = esc_html__('User Detail', NMP);
//        var_dump('more information');
      }
      break;
    case 'edit': 
      if ('user_edit' === $_GET['tab'] && isset($_GET['admin_id']) && intval($_GET['admin_id']) > 0) {
        $dynamic_tab_title = esc_html__('Edit User', NMP);
//        var_dump('edit');
      }
      break;
    default: 
      break;
  }
}

//var_dump($options);

/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div id="page-head" name="page-head" class="wrap">
  <h2><?php esc_html_e('CDBT Tables Management', CDBT); ?></h2>
  
  <div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
      <?php if ('wp_core_table' === $tab_name && !$options['enable_core_tables']) continue; ?>
      <li role="presentation"<?php if ($current_tab == $tab_name) : ?> class="active"<?php endif; ?>><a href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>" role="tab"><?php echo $display_tab_title; ?></a></li>
    <?php endforeach; ?>
    </ul>
  </div>
  
<?php if ($current_tab == 'table_list') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Enabled Table List', CDBT); ?></h4>
  <?php if ( false === ($table_list = !$this->get_table_list( 'enable' )) ) : ?>
    <p>現在、プラグインで管理可能なテーブルはありません。</p>
    <p>テーブルを新規作成する場合は、<a href="#">ここをクリック</a>してください。</p>
    <p>既存のテーブルをプラグインに取り込む場合は、<a href="#">ここをクリック</a>してください。</p>
  <?php else : ?>
  <form id="" name="" action="" method="post" class="">
    
    <?php var_dump($table_list); ?>
    
  </form>
  <?php endif; ?>
<?php endif; ?>
  
<?php if ($current_tab == 'wp_core_table') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('WordPress Core Table List', CDBT); ?></h4>
  <form id="cdbt-call-ajax">
    
<?php
  $ajax_url = $this->ajax_url( [ 'event' => 'update_target_table' ] );

  $datasource = $this->create_datasorce($this->core_tables); // by trait `CdbtExtras`

  $conponent_options = [
    'id' => 'cdbtAdminTables', 
    'pageIndex' => 0, 
    'pageSize' => 20, 
    'columns' => [
// [ 'label' => '{display label}', 'property' => '{table name}', 'sortable' => {true|false}, 'sortDirection' => '{asc|desc}', 'dataNumric' => {true|false}, 'customColumnRenderer' => {null|string}, 'className' => {null|string}, 'width' => {null|integer} ], 
//      [ 'label' => 'ID', 'property' => 'id', 'sortable' => false, 'sortDirection' => 'asc', 'className' => null, 'width' => null ], 
      [ 'label' => 'TableName', 'property' => 'table_name', 'sortable' => true, 'sortDirection' => 'asc', 'className' => null, 'width' => null, 'customColumnRenderer' => "'<div><a href=\"'+rowData.info+'\" data-ajax-url=\"". $ajax_url ."\" data-ajax-data=\"target_table:'+rowData.table_name+'\">'+rowData.table_name+'</a></div><div class=\"small text-muted\">'+rowData.logical_name+'</div>'" ], 
//      [ 'label' => 'TableComment', 'property' => 'logical_name', 'sortable' => true, 'sortDirection' => null, 'className' => null, 'width' => null ], 
      [ 'label' => 'Records', 'property' => 'records', 'sortable' => true, 'sortDirection' => 'asc', 'dataNumric' => true, 'className' => 'text-right', 'width' => 100 ], 
      [ 'label' => 'Information', 'property' => 'info', 'sortable' => false, 'customColumnRenderer' => "'<div class=\"text-center\"><a href=\"'+rowData.info+'\" class=\"btn btn-default\" data-ajax-url=\"". $ajax_url ."\" data-ajax-data=\"target_table:'+rowData.table_name+'\">".__('Click here', CDBT)."</a></div>'", 'width' => 120 ], 
// Manage Table
      [ 'label' => 'Table Controls', 'property' => 'table_controls', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'Import', 'property' => 'import', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'Export', 'property' => 'export', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'Duplicate', 'property' => 'duplicate', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'Modify', 'property' => 'modify', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'Drop', 'property' => 'drop', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
// Manage Records
      [ 'label' => 'Data Controls', 'property' => 'data_controls', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'Truncate', 'property' => 'truncate', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'ViewData', 'property' => 'view', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'EntryData', 'property' => 'entry', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
//      [ 'label' => 'EditData', 'property' => 'edit', 'sortable' => false, 'sortDirection' => null, 'className' => null, 'width' => null ], 
    ],
    'data' => $datasource, 
  ];
  $this->component_render('repeater', $conponent_options); // by trait `DynamicTemplate`
?>
    
    <input type="hidden" name="cdbt_ajax_url" value="<?php echo $ajax_url; ?>">
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'create_table') : 

if (isset($this->cdbt_sessions['do_' . $this->query['page'] . '_' . $current_tab])) {
  $session_vars = $this->cdbt_sessions['do_' . $this->query['page'] . '_' . $current_tab];
//  var_dump($session_vars);
}

?>
  <div class="well-sm">
    <p class="text-info">
      テーブルの新規作成、または既存テーブルの取り込みを行うことができます。<a href="#resume-table">既存テーブルを取り込む場合はこちらから</a>行ってください。
    </p>
  </div>
  
  <div class="cdbt-create-table">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="create_table">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <h4 class="title" id="create-table"><i class="fa fa-database text-muted"></i> データベース用テーブル設定</h4>
      
      <div class="well-sm">
        <p class="text-info">
          データベースに新しいテーブルを作成します。必要な設定内容を入力してください。
        </p>
      </div>
      
      <div class="form-group">
        <label for="create-table-table_name" class="col-sm-2 control-label"><?php _e('Table Name', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
        <div class="col-sm-10">
          <div class="input-group col-sm-5" id="create-table-table_name">
            <div class="input-group-addon<?php if ('1' === $options['use_wp_prefix']) : ?> active<?php endif; ?>"><?php echo $this->wpdb->prefix; ?></div>
            <input id="instance_table_name" name="instance_table_name" type="text" value="<?php if (isset($session_vars)) echo $session_vars['instance_table_name']; ?>" class="form-control" placeholder="Table Name">
            <input name="<?php echo $this->domain_name; ?>[table_name]" type="hidden" value="<?php if (isset($session_vars)) echo $session_vars[$this->domain_name]['table_name']; ?>" class="sr-only">
          </div>
          <p id="live_preview" class="help-block col-sm-10"> <?php _e('Live preview of setting name:', CDBT); ?> <code>tablename</code></p>
          <div class="checkbox" id="instance_prefix_switcher">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="instance_prefix_switcher" type="checkbox" value="1" <?php if (isset($session_vars)) { checked('1', intval(isset($session_vars['instance_prefix_switcher']))); } else { checked('1', $options['use_wp_prefix']); } ?>>
              <span class="checkbox-label">WordPressの設定（wp-config.php）で定義されているテーブル接頭辞を使う。</span>
            </label>
          </div>
        </div>
      </div><!-- /create-table-table_name -->
      <div class="form-group">
        <label for="create-table-table_comment" class="col-sm-2 control-label"><?php _e('Table Comment', CDBT); ?></label>
        <div class="col-sm-5">
          <input id="create-table-table_comment" name="<?php echo $this->domain_name; ?>[table_comment]" type="text" value="<?php if (isset($session_vars)) echo $session_vars[$this->domain_name]['table_comment']; ?>" class="form-control" placeholder="Table Comment">
          <p class="help-block">テーブルコメントは論理名として表示名などに使われます。</p>
        </div>
      </div><!-- /create-table-table_comment -->
      <div class="form-group">
        <label for="create-table-table_charset" class="col-sm-2 control-label"><?php _e('Table Charset', CDBT); ?><h6> <?php $this->during_trial( 'default_charset' ); ?></h6></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="create-table-table_charset">
            <input type="text" name="<?php echo $this->domain_name; ?>[table_charset]" value="<?php if (isset($session_vars)) { esc_attr_e($session_vars[$this->domain_name]['table_charset']); } else { esc_attr_e($this->charset); } ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_charsets as $i => $charset) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $charset; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block">指定しない場合、現在のデータベースの初期値: <code><?php echo $this->db_default_charset; ?></code>が設定されます。</p>
        </div>
      </div><!-- /create-table-table_charset -->
      <div class="form-group">
        <label for="create-table-table_db_engine" class="col-sm-2 control-label"><?php _e('DB Engine', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="create-table-table_db_engine">
            <input type="text" name="<?php echo $this->domain_name; ?>[table_db_engine]" value="<?php if (isset($session_vars)) { esc_attr_e($session_vars[$this->domain_name]['table_db_engine']); } else { esc_attr_e($this->db_default_engine); } ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_engines as $i => $db_engine) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $db_engine; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block">指定しない場合、現在のデータベースの初期値: <code><?php echo $this->db_default_engine; ?></code>が設定されます。</p>
        </div>
      </div><!-- /create-table-table_db_engine -->
      <div class="form-group">
        <label for="automatically-add-columns" class="col-sm-2 control-label"><?php _e('Automatically Add Columns', CDBT); ?><h6> <?php $this->during_trial( 'auto_add_columns' ); ?></h6></label>
        <div class="col-sm-10">
          <div class="checkbox" id="automatically-add-columns1">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[automatically_add_columns][]" type="checkbox" value="ID"<?php if (isset($session_vars)) { if (isset($session_vars[$this->domain_name]['automatically_add_columns']) && in_array('ID', $session_vars[$this->domain_name]['automatically_add_columns'])) { ?> checked="checked"<?php } } else { ?> checked="checked"<?php } ?>>
              <span class="checkbox-label">先頭にプライマリキーの「ID」カラムを追加する（自動採番式のサロゲートキー）</span>
            </label>
          </div>
          <div class="checkbox" id="automatically-add-columns2">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[automatically_add_columns][]" type="checkbox" value="created"<?php if (isset($session_vars)) { if (isset($session_vars[$this->domain_name]['automatically_add_columns']) && in_array('created', $session_vars[$this->domain_name]['automatically_add_columns'])) { ?> checked="checked"<?php } } else { ?> checked="checked"<?php } ?>">
              <span class="checkbox-label">データ登録日時を格納する「created」カラムを追加する</span>
            </label>
          </div>
          <div class="checkbox" id="automatically-add-columns3">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[automatically_add_columns][]" type="checkbox" value="updated"<?php if (isset($session_vars)) { if (isset($session_vars[$this->domain_name]['automatically_add_columns']) && in_array('updated', $session_vars[$this->domain_name]['automatically_add_columns'])) { ?> checked="checked"<?php } } else { ?> checked="checked"<?php } ?>>
              <span class="checkbox-label">データ更新日時を格納する「updated」カラムを追加する</span>
            </label>
          </div>
        </div>
      </div><!-- /create-table-automatically-add-columns -->
      <div class="form-group">
        <label for="create-table-create_table_sql" class="col-sm-2 control-label"><?php _e('Create Table SQL', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
        <div class="col-sm-9">
          <div role="tabpanel">
            <ul class="nav nav-tabs" role="tablist">
              <li role="presentation" class="active"><a href="#direct_sql" aria-controls="direct_sql" role="tab" data-toggle="tab"><?php _e('Direct Edit SQL', CDBT); ?></a></li>
              <li role="presentation"><a href="#table_creator" aria-controls="table_creator" role="tab" data-toggle="tab"><?php _e('Table Creator', CDBT); ?></a></li>
            </ul>
            <div class="tab-content">
              <div role="tabpanel" class="tab-pane active" id="direct_sql"><textarea id="create-table-create_table_sql" name="<?php echo $this->domain_name; ?>[create_table_sql]" class="form-control" rows="10" placeholder="Create Table SQL"><?php if (isset($session_vars)) echo esc_textarea(stripslashes_deep($session_vars[$this->domain_name]['create_table_sql'])); ?></textarea></div>
              <div role="tabpanel" class="tab-pane" id="table_creator"><textarea id="instance_create_table_sql" class="form-control" rows="10" disabled="disabled"><?php if (isset($session_vars)) echo esc_textarea(stripslashes_deep($session_vars[$this->domain_name]['create_table_sql'])); ?></textarea></div>
            </div>
            <div class="sql-support-button pull-right">
              <button type="button" id="create-sql-support" class="btn btn-default btn-xs"><?php _e('Make Template', CDBT); /* 設定値から雛形を作る - Make a template from the set value */ ?></button>
            </div>
          </div>
          <p class="help-block">
            <?php _e('Example of SQL Statements:', CDBT); ?> <br>
            <pre><code>CREATE TABLE prefix_new_table ( `account_name` varchar(64) NOT NULL COMMENT 'アカウント名',  `gender` enum('female','male') DEFAULT NULL COMMENT '性別' )</code></pre>
          </p>
        </div>
      </div><!-- /create-table-create_table_sql -->
      <div class="pull-right">
        <a href="#create-table"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
      <h4 class="title" id="plugin-settings"><i class="fa fa-cubes text-muted"></i> プラグイン用テーブル設定</h4>
      
      <div class="well-sm">
        <p class="text-info">
          プラグインで管理可能になった際のテーブル設定を指定します。この設定はテーブル作成後も変更可能です。
        </p>
      </div>
      
      <div class="form-group">
        <label for="create-table-max_show_records" class="col-sm-2 control-label">最大表示データ数</label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="create-table-max_show_records">
            <input type="text" name="<?php echo $this->domain_name; ?>[max_show_records]" value="<?php if (isset($session_vars)) { echo intval($session_vars[$this->domain_name]['max_show_records']); } else { echo intval($options['default_per_records']); } ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block">このプラグインで管理するテーブルの1ページに表示される最大データ行数の初期値です。</p>
        </div>
      </div>
      <div class="form-group">
        <label for="create-table-user_permission_view" class="col-sm-2 control-label">テーブルデータ閲覧を許可するユーザー</label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="create-table-user_permission_view">
            <input type="text" name="<?php echo $this->domain_name; ?>[user_permission_view]" value="<?php if (isset($session_vars)) { esc_html_e($session_vars[$this->domain_name]['user_permission_view']); } else { echo 'guest'; } ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->user_roles as $i => $role) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $role; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block">この設定値は管理画面以外でテーブルが表示される場合に有効になります。主にショートコード<code>&#091;cdbt-view&#093;</code>向けの設定です。<a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php $this->during_trial( 'user_permission_view' ); ?></p>
        </div>
      </div>
      <div class="form-group">
        <label for="create-table-user_permission_entry" class="col-sm-2 control-label">テーブルデータ登録を許可するユーザー</label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="create-table-user_permission_entry">
            <input type="text" name="<?php echo $this->domain_name; ?>[user_permission_entry]" value="<?php if (isset($session_vars)) { echo esc_html_e($session_vars[$this->domain_name]['user_permission_entry']); } else { echo 'contributor'; } ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->user_roles as $i => $role) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $role; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block">この設定値は管理画面以外でテーブルにデータ登録する場合に有効になります。主にショートコード<code>&#091;cdbt-entry&#093;</code>向けの設定です。<a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php $this->during_trial( 'user_permission_entry' ); ?></p>
        </div>
      </div>
      <div class="form-group">
        <label for="create-table-user_permission_edit" class="col-sm-2 control-label">テーブルデータ編集を許可するユーザー</label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="create-table-user_permission_edit">
            <input type="text" name="<?php echo $this->domain_name; ?>[user_permission_edit]" value="<?php if (isset($session_vars)) { echo esc_html_e($session_vars[$this->domain_name]['user_permission_edit']); } else { echo 'editor'; } ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->user_roles as $i => $role) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $role; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block">この設定値は管理画面以外でテーブルのデータ編集を行う場合に有効になります。主にショートコード<code>&#091;cdbt-edit&#093;</code>向けの設定です。<a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php $this->during_trial( 'user_permission_edit' ); ?></p>
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <ul id="foot-note-1" class="foot-note">
            <li><span class="dashicons dashicons-info"></span> 任意の権限を設定する場合は、WordPressのCapability値を設定してください。<a href="https://codex.wordpress.org/Roles_and_Capabilities" target="_blank">Capabilityの詳細はこちら</a>を参照してください。</li>
          </ul>
        </div>
      </div>
      
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary"><?php _e('Create Table', CDBT); ?></button>
        </div>
      </div>
      
      <div class="pull-right">
        <a href="#plugin-settings"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
    </form>
      
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="resume_table">
      
      <h4 class="title" id="resume-table"><i class="fa fa-reply text-muted"></i> 既存テーブル取り込み</h4>
      
      <div class="well-sm">
        <p class="text-info">
          既に存在しているテーブルを、プラグインで管理できるように取り込みます。
        </p>
      </div>
      
    <?php if ( false === ($resume_table_list = !$this->get_table_list( 'unmanageable' )) ) : ?>
      
      <div class="form-group">
        <p class="well-sm col-sm-offset-2 col-sm-8">現在、プラグインに取り込めるテーブルはありません。</p>
      </div>
      
    <?php else : ?>
      <div class="form-group">
        <label for="resume-table-resume_table" class="col-sm-2 control-label">取り込むテーブル</label>
        <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="resume-table-resume_table">
          <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
            <span class="selected-label"></span>
            <span class="caret"></span>
            <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
          <?php foreach ($resume_table_list as $i => $table) : ?>
            <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $table; ?></a></li>
          <?php endforeach; ?>
          </ul>
          <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[resume_table]" readonly="readonly" aria-hidden="true" type="text"/>
        </div>
      </div>
      
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-default"><?php _e('Resume Table', CDBT); ?></button>
        </div>
      </div>
    <?php endif; ?>
      
      <div class="pull-right">
        <a href="#page-head"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
    </form>
  </div><!-- /.cdbt-create-table -->
<?php endif; ?>
  
<?php if ($current_tab == 'modify_table') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Modify Table', CDBT); ?></h4>
    
    
<?php endif; ?>
  
<?php if ($current_tab == 'table_info') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Table Information', CDBT); ?></h4>
    
    <?php echo $this->cdbt_sessions['target_table']; ?>
    
<?php endif; ?>
  
    
</div><!-- /.wrap -->