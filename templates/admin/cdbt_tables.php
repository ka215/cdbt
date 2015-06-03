<?php
/**
 * Template : Tables Management Page
 * URL: `/wp-admin/admin.php?page=cdbt_tables`
 *
 * @since 2.0.0
 *
 */
 
/**
 * Define the various localized variables for rendering
 */
$options = get_option($this->domain_name);
$tabs = [
  'table_list' => esc_html__('Table List', CDBT), 
  'wp_core_table' => esc_html__('Core Tables', CDBT), 
  'create_table' => esc_html__('Create Table', CDBT), 
  'modify_table' => esc_html__('Modify Table', CDBT), 
  'operate_table' => esc_html__('Operate Table', CDBT), 
  'operate_data' => esc_html__('Operate Data', CDBT), 
];
$default_tab = 'table_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;

$enable_table = $this->get_table_list( 'enable' );
$enable_table = !is_array($enable_table) ? [] : $enable_table;
$unreserved_table = $this->get_table_list( 'unreserved' );
$unreserved_table = !is_array($unreserved_table) ? [] : $unreserved_table;

$selectable_table = $options['enable_core_tables'] ? array_merge($enable_table, $this->core_tables) : $enable_table;
sort($selectable_table);

$allow_file_types = [];
foreach ($this->allow_file_types as $file_type) {
  $allow_file_types[$file_type] = __(strtoupper($file_type), CDBT);
}

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
  <?php if ( 0 === count($enable_table) ) : ?>
    <p>現在、プラグインで管理可能なテーブルはありません。</p>
    <p>テーブルを新規作成する場合は、<a href="<?php echo add_query_arg('tab', 'create_table'); ?>">ここをクリック</a>してください。</p>
    <p>既存のテーブルをプラグインに取り込む場合は、<a href="<?php echo add_query_arg('tab', 'create_table'); ?>#resume-table">ここをクリック</a>してください。</p>
  <?php else : 
  /**
   * Define the localized variables for tab of `table_list`
   */
  
  $datasource = $this->create_tablelist_datasorce($enable_table);
  $conponent_options = $this->create_scheme_datasource( 'cdbtAdminTables', 0, 20, 'table_list', $datasource );
  $this->component_render('repeater', $conponent_options); // by trait `DynamicTemplate`
  
    endif; ?>
<?php endif; /* End of `table_list` tab contents */ ?>
  
<?php if ($current_tab == 'wp_core_table') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('WordPress Core Table List', CDBT); ?></h4>
    
<?php
  /**
   * Define the localized variables for tab of `wp_core_table`
   */
  
//  $ajax_url = $this->ajax_url( [ 'event' => 'update_target_table' ] );
  $datasource = $this->create_tablelist_datasorce($this->core_tables); // by trait `CdbtExtras`
  $conponent_options = $this->create_scheme_datasource( 'cdbtWpCoreTables', 0, 20, 'table_list', $datasource, [ 'avg_row_length', 'data_length', 'create_time' ] );
  $this->component_render('repeater', $conponent_options); // by trait `DynamicTemplate`
  
?>
<?php endif; /* End of `wp_core_table` tab contents */ ?>
  
<?php if ($current_tab == 'create_table') : 
  /**
   * Define the localized variables for tab of `create_table`
   */
  
  if (isset($this->cdbt_sessions['do_' . $this->query['page'] . '_' . $current_tab])) {
    // Set variables from session
    $session_vars = $this->cdbt_sessions['do_' . $this->query['page'] . '_' . $current_tab];
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
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <h4 class="title" id="resume-table"><i class="fa fa-reply text-muted"></i> 既存テーブル取り込み</h4>
      
      <div class="well-sm">
        <p class="text-info">
          既に存在しているテーブルを、プラグインで管理できるように取り込みます。
        </p>
      </div>
      
    <?php
    if ( 0 === count($resume_table_list = array_diff($unreserved_table, $enable_table)) ) : ?>
      
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
          <?php foreach ($resume_table_list as $table) : ?>
            <li data-value="<?php echo $table; ?>"><a href="#"><?php echo $table; ?></a></li>
          <?php endforeach; ?>
          </ul>
          <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[resume_table]" readonly="readonly" aria-hidden="true" type="text"/>
        </div>
      </div>
      
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary"><?php _e('Resume Table', CDBT); ?></button>
        </div>
      </div>
    <?php endif; ?>
      
      <div class="pull-right">
        <a href="#page-head"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
    </form>
  </div><!-- /.cdbt-create-table -->
<?php endif; /* End of `create_table` tab contents */ ?>
  
<?php if ($current_tab == 'modify_table') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Modify Table', CDBT); ?></h4>
  
<?php
  /**
   * Define the localized variables for tab of `modify_table`
   */
  
  if ( !isset($this->cdbt_sessions[$current_tab]) ) 
    $this->destroy_session();
  
?>
<?php endif; /* End of `modify_table` tab contents */ ?>
  
<?php if ($current_tab == 'operate_table') : 
  /**
   * Define the localized variables for tab of `operate_table`
   */
  
  $target_table = '';
  $current_action = '';
  if (isset($this->cdbt_sessions[$current_tab]) && !empty($this->cdbt_sessions[$current_tab])) {
    
    if (array_key_exists('operate_target_table', $this->cdbt_sessions[$current_tab])) {
      $target_table = $this->cdbt_sessions[$current_tab]['operate_target_table'];
    } else
    if (array_key_exists('target_table', $this->cdbt_sessions[$current_tab])) {
      $target_table = $this->cdbt_sessions[$current_tab]['target_table'];
    } else
    if (array_key_exists('operate_current_table', $this->cdbt_sessions[$current_tab])) {
      $target_table = $this->cdbt_sessions[$current_tab]['operate_current_table'];
    }
    
    if (array_key_exists('operate_action', $this->cdbt_sessions[$current_tab])) {
      $current_action = $this->cdbt_sessions[$current_tab]['operate_action'];
    } else
    if (array_key_exists('default_action', $this->cdbt_sessions[$current_tab])) {
      $current_action = $this->cdbt_sessions[$current_tab]['default_action'];
    }
    
  }
  
  // Definition of belong table type
  if (in_array($target_table, $this->core_tables)) {
    $belong_table_type = [ 'type_name' => 'wordpress', 'icon' => 'fa fa-wordpress' ];
  } else
  if (in_array($target_table, $enable_table)) {
    $belong_table_type = [ 'type_name' => 'regular', 'icon' => 'fa fa-cubes' ];
  } else {
    $belong_table_type = [ 'type_name' => 'other', 'icon' => 'fa fa-database' ];
  }
  
  // Definition of operatable console buttons
  $operatable_buttons = [
    'detail'      => [ 'labal' => __( 'Detail View', CDBT),      'icon' => 'fa fa-list-alt',                         'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
    'import'    => [ 'labal' => __( 'Import Data', CDBT),      'icon' => 'glyphicon glyphicon-import',       'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
    'export'    => [ 'labal' => __( 'Export Data', CDBT),      'icon' => 'glyphicon glyphicon-export',       'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
    'duplicate' => [ 'labal' => __( 'Duplicate Table', CDBT), 'icon' => 'glyphicon glyphicon-duplicate',   'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
    'truncate'  => [ 'labal' => __( 'Truncate Table', CDBT),  'icon' => 'glyphicon glyphicon-certificate', 'allow_type' => [ 'regular', 'other' ] ], 
    'modify'    => [ 'labal' => __( 'Modify Table', CDBT),     'icon' => 'fa fa-wrench',                        'allow_type' => [ 'regular', 'other' ] ], 
    'backup'    => [ 'labal' => __( 'Backup Table', CDBT),   'icon' => 'glyphicon glyphicon-save-file',   'allow_type' => [  ] ], // Release in near future
    'drop'       => [ 'labal' => __( 'Delete Table', CDBT),     'icon' => 'fa fa-trash-o',                        'allow_type' => [ 'regular', 'other' ] ], 
  ];
  
?>
  
  <nav class="navbar navbar-default navbar-inner-tab" style="margin-top: 1em;">
    <div class="container-fluid">
      <!-- This icon represents the belonging table type -->
      <label class="navbar-brand"><i class="<?php echo $belong_table_type['icon']; ?>"></i></label>
      <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="navbar-form">
        <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
        <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
        <input type="hidden" name="action" value="change_table">
        <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
        <div class="navbar-left">
          <div class="form-group">
            <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="operate-table-target_table">
              <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
                <span class="selected-label"></span>
                <span class="caret"></span>
                <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
              </button>
              <ul class="dropdown-menu" role="menu">
                <li data-value=""><a href="#"><span class="text-muted"><?php _e('Please select', CDBT); ?></span></a></li>
              <?php foreach ($selectable_table as $table) : ?>
                <li data-value="<?php echo $table; ?>"<?php if ($target_table === $table) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php echo $table; ?></a></li>
              <?php endforeach; ?>
              </ul>
              <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[operate_target_table]" readonly="readonly" aria-hidden="true" type="text"/>
            </div>
          </div>
          <button type="submit" class="btn btn-default" id="operate-table-action-change_table">操作テーブルを変更</button>
        </div>
        <input type="hidden" name="<?php echo $this->domain_name; ?>[operate_current_table]" value="<?php echo $target_table; ?>">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[operate_action]" value="<?php echo $current_action; ?>">
        <div class="navbar-right">
        <?php foreach ($operatable_buttons as $action_name => $definitions) : ?>
          <button type="button" class="btn btn-default<?php if ($action_name === $current_action) : ?> active<?php endif; ?>" id="operate-table-action-<?php echo $action_name; ?>" title="<?php echo $definitions['labal']; ?>"<?php if (empty($target_table) || !in_array($belong_table_type['type_name'], $definitions['allow_type'])) : ?> disabled="disabled"<?php endif; ?>><span class="sr-only"><?php echo $definitions['labal']; ?></span><i class="<?php echo $definitions['icon']; ?>"></i></button>
        <?php endforeach; ?>
        </div>
      </form>
    </div>
  </nav>
  
<?php 
  if (!empty($target_table)) {
    $table_status = $this->get_table_status($target_table);
    $columns_schema = $this->get_table_schema($target_table);
    $columns_schema_index = is_array($columns_schema) ? array_keys(reset($columns_schema)) : [];
    $row_index_number = 1;
    
    if (empty($columns_schema_index)) 
      $current_action = '';
  }
  
  if (empty($current_action)) : 
?>
  
  <div class="well-sm">
    <p class="text-info">
      このセクションでは指定のテーブルに対して様々な操作を行うことができます。実行したい操作ボタンを押してください。
    </p>
  </div>
  
<?php endif; ?>
  

<section id="detail" class="<?php if ('detail' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  <div class="table-responsive">
    <strong><i class="fa fa-square text-muted"></i> カラム情報</strong>
    <table id="columns-detail" class="table table-striped table-bordered table-hover table-condensed">
      <thead>
        <tr class="active">
          <th><small>#</small></th>
          <th><small><?php _e('Column Name', CDBT); ?></small></th>
        <?php foreach ($columns_schema_index as $columns_index_name) : ?>
          <th class="text-center"><small><?php _e($columns_index_name, CDBT); ?></small></th>
        <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($columns_schema as $column_name => $column_scheme) : ?>
        <tr>
          <td><small><?php echo $row_index_number; ?></small></td>
          <td><small><?php echo $column_name; ?></small></td>
        <?php foreach ($columns_schema_index as $columns_index_name) : ?>
          <?php if (in_array($columns_index_name, [ 'not_null', 'primary_key', 'unsigned' ])) : ?>
          <td class="text-center"><small><?php echo 1 === intval($column_scheme[$columns_index_name]) ? '<i class="fa fa-circle-thin text-center"></i>' : ''; ?></small></td>
          <?php else : ?>
          <td><small><?php echo $column_scheme[$columns_index_name]; ?></small></td>
          <?php endif; ?>
        <?php endforeach; ?>
        </tr>
      <?php $row_index_number++; endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="<?php echo count($columns_schema_index) + 2; ?>" style="padding: 0;"></td></tr>
      </tfoot>
    </table>
  </div>
  
  <div class="table-responsive">
    <table id="table-detail" class="table table-striped table-hover table-condensed">
      <thead>
        <tr>
          <th colspan="4" class="col"><i class="fa fa-square text-muted"></i> テーブル情報</th>
        </tr>
      </thead>
      <tbody>
      <?php $index_num = 0; foreach ($table_status as $key => $value) : ?>
        <?php if (intval(fmod($index_num, 2)) === 0) : ?><tr><?php endif; ?>
          <th class="row"><small><?php _e($key, CDBT); ?></small></th><td><small><?php echo $value; ?></small></td>
        <?php if ($index_num > 0 && intval(fmod($index_num, 2)) === 1) : ?></tr><?php endif; ?>
      <?php $index_num++; endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="4" style="padding: 0;"></td></tr>
      </tfoot>
    </table>
  </div>
</section>
  
<section id="import" class="<?php if ('import' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons['import']['icon']; ?> text-muted"></i> <?php esc_html_e('Import Table Options', CDBT); ?></h4> <?php $this->during_trial( 'import_table' ); ?>
  
  
  
</section>
  
<section id="export" class="<?php if ('export' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons['export']['icon']; ?> text-muted"></i> <?php esc_html_e('Export Table Options', CDBT); ?></h4> <?php $this->during_trial( 'export_table' ); ?>
  
  <div class="well-sm">
    <p class="text-info">
    <?php if (intval($table_status['Rows']) > 0) : ?>
      現在テーブルに格納されているデータのエクスポートを行います。ダウンロードしたいファイル形式と、エクスポートの対象となるカラムを指定してください。
    <?php else : ?>
      このテーブルにはエクスポートするデータがありません。
    <?php endif; ?>
    </p>
  </div>
  
  <?php if (intval($table_status['Rows']) > 0) : ?>
  <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal" id="form-export_table">
    <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
    <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
    <input type="hidden" name="action" value="export_table">
    <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
    
    <div class="form-group">
      <label for="export-table-download_filetype" class="col-sm-2 control-label"><?php _e('Download File Type', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
      <div class="col-sm-10">
        <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="export-table-download_filetype">
          <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
            <span class="selected-label"></span>
            <span class="caret"></span>
            <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
          <?php foreach ($allow_file_types as $filetype_name => $filetype_label) : ?>
            <li data-value="<?php echo $filetype_name; ?>"<?php if (isset($this->cdbt_sessions[$current_tab]['export_filetype']) && $this->cdbt_sessions[$current_tab]['export_filetype'] === $filetype_name) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php echo $filetype_label; ?></a></li>
          <?php endforeach; ?>
          </ul>
          <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[export_filetype]" readonly="readonly" aria-hidden="true" type="text"/>
        </div>
      </div>
    </div><!-- /export-table-download_filetype -->
    <div class="form-group" id="switching-item-add_index_line">
      <label for="export-table-add_index_line" class="col-sm-2 control-label"><?php _e('Add Index Line', CDBT); ?></label>
      <div class="col-sm-10">
        <div class="checkbox" id="export-table-add_index_line">
          <label class="checkbox-custom" data-initialize="checkbox">
            <input class="sr-only" type="checkbox" name="<?php echo $this->domain_name; ?>[add_index_line]" value="1"<?php if (isset($this->cdbt_sessions[$current_tab]['add_index_line']) && $this->cdbt_sessions[$current_tab]['add_index_line']) : ?> checked="checked"<?php endif; ?>>
            <span class="checkbox-label">先頭の行にカラム名だけの列をインデックス行として追加する</span>
          </label>
        </div>
        <p class="help-block">この設定はダウンロードファイルが「CSV」か「TSV」の時のみ有効です。</p>
      </div>
    </div><!-- /export-table-add_index_line -->
    <div class="form-group">
      <label for="export-table-target_columns" class="col-sm-2 control-label"><?php _e('Export Columns', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
      <div class="col-sm-10" id="export-table-target_columns">
      <?php foreach(array_keys($columns_schema) as $i => $column) : ?>
        <?php 
    $default_checked = ' checked="checked"';
    if (isset($this->cdbt_sessions[$current_tab]['export_columns']) && !in_array($column, $this->cdbt_sessions[$current_tab]['export_columns'])) 
      $default_checked = '';
        ?>
        <div class="checkbox highlight" id="export-table-target_columns<?php echo $i+1; ?>">
          <label class="checkbox-custom highlight" data-initialize="checkbox">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[export_columns][]"<?php echo $default_checked; ?> type="checkbox" value="<?php esc_attr_e($column); ?>"> <span class="checkbox-label"><?php esc_html_e($column); ?></span><?php if ($columns_schema[$column]['primary_key']) : ?>
            &nbsp;<span class="label label-default"><?php _e('PK', CDBT); ?></span><?php endif; ?>
          </label>
        </div>
      <?php endforeach; ?>
        <p class="help-block">
        	<?php _e('You must specify at least one or more columns.', CDBT); ?>
          <button type="button" class="btn btn-default btn-sm" id="switch-checkbox-export_columns"><?php _e('Switch of all checking', CDBT); ?></button>
        </p>
      </div>
    </div><!-- /export-table-target_columns -->
    <input type="hidden" name="<?php echo $this->domain_name; ?>[export_table]" value="<?php echo $target_table; ?>">
    <?php if (isset($this->cdbt_sessions[$current_tab]['ajax_download']) && $this->cdbt_sessions[$current_tab]['ajax_download']) : ?><input type="hidden" id="_ajax_download_export" value="true"><?php endif; ?>
    <div class="form-group">
      <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-primary" id="button-submit-export_table"><?php _e('Export', CDBT); ?></button>
      </div>
    </div>
  </form>
  <?php endif; ?>
  
</section>
  
<section id="duplicate" class="<?php if ('duplicate' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons['duplicate']['icon']; ?> text-muted"></i> <?php esc_html_e('Duplicate Table Options', CDBT); ?></h4> <?php $this->during_trial( 'duplicate_table' ); ?>
  
  <div class="well-sm">
    <p class="text-info">
      複製テーブルのテーブル名以外の設定値は複製元テーブルの設定を引き継ぎます。設定を変更したい場合は、テーブル複製後に個別に変更してください。
    </p>
  </div>
  
  <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
    <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
    <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
    <input type="hidden" name="action" value="duplicate_table">
    <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
    
    <div class="form-group">
      <label for="duplicate-table-table_name" class="col-sm-2 control-label"><?php _e('Duplicate Table Name', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
      <div class="col-sm-5">
        <input id="duplicate-table-table_name" name="<?php echo $this->domain_name; ?>[duplicate_table_name]" type="text" value="<?php if (isset($this->cdbt_sessions[$current_tab]['duplicate_table_name'])) echo $this->cdbt_sessions[$current_tab]['duplicate_table_name']; ?>" class="form-control" placeholder="Duplicate Table Name">
        <p class="help-block">複製されるテーブルのテーブル名を入力してください。</p>
      </div>
    </div><!-- /create-table-duplicate_table_name -->
    <div class="form-group">
      <label for="duplicate-table-with_data_true" class="col-sm-2 control-label"><?php _e('Duplicate With Data', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
      <div class="col-sm-10">
        <div class="radio">
          <label class="radio-custom" data-initialize="radio" id="duplicate-table-with_data_true">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[duplicate_with_data]" type="radio" value="true"<?php if (isset($this->cdbt_sessions[$current_tab]['duplicate_with_data'])) { if ($this->cdbt_sessions[$current_tab]['duplicate_with_data']) : ?> checked="checked"<?php endif; } else { ?> checked="checked"<?php } ?>>
            複製元テーブルに格納されているデータが含まれる完全な複製を行う
          </label>
        </div>
        <div class="radio checked">
          <label class="radio-custom" data-initialize="radio" id="duplicate-table-with_data_false">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[duplicate_with_data]" type="radio" value="false"<?php if (isset($this->cdbt_sessions[$current_tab]['duplicate_with_data'])) { if (!$this->cdbt_sessions[$current_tab]['duplicate_with_data']) : ?> checked="checked"<?php endif; } ?>>
            複製元テーブルのデータを含まないテーブル構造だけを複製する（複製後のテーブルはデータのない空テーブルになります）
          </label>
        </div>
      </div>
    </div><!-- /create-table-duplicate_with_data -->
    <input type="hidden" name="<?php echo $this->domain_name; ?>[duplicate_origin_table]" value="<?php echo $target_table; ?>">
    <div class="form-group">
      <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-primary"><?php _e('Duplicate Table', CDBT); ?></button>
      </div>
    </div>
  </form>
  
</section>
  
<section id="backup" class="<?php if ('backup' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons['backup']['icon']; ?> text-muted"></i> <?php esc_html_e('Backup Table Options', CDBT); ?></h4> <?php $this->during_trial( 'backup_table' ); ?>
  
</section>
  
<?php endif; /* End of `operate_table` tab contents */ ?>
  
<?php if ($current_tab == 'operate_data') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Operate Data', CDBT); ?></h4>
    
    <?php var_dump( $this->cdbt_sessions[$current_tab] ); ?>
    
<?php endif; /* End of `operate_data` tab contents */ ?>
  
</div><!-- /.wrap -->