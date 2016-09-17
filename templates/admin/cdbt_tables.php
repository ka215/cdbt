<?php
/**
 * Template : Tables Management Page
 * URL: `/wp-admin/admin.php?page=cdbt_tables`
 *
 * @since 2.0.0
 * @since 2.1.31 Updated
 *
 */
 
/**
 * Define the various localized variables for rendering
 */
$options = get_option($this->domain_name);
$tabs = [
  'table_list' => __('Table List', CDBT), 
  'wp_core_table' => __('Core Tables', CDBT), 
  'create_table' => __('Create Table', CDBT), 
  'modify_table' => __('Modify Table', CDBT), 
  'operate_table' => __('Operate Table', CDBT), 
  'operate_data' => __('Operate Data', CDBT), 
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
//$label_required = '<h6><span class="label label-danger">'. __('Required', CDBT) .'</span></h6>';
$label_required = '<span class="label label-required">'. __('Required', CDBT) .'</span>';
/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div id="page-head" name="page-head" class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php _e('CDBT Tables Management', CDBT); ?></h2>
  
  <div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
      <?php if ('wp_core_table' === $tab_name && !$options['enable_core_tables']) continue; ?>
      <li role="presentation"<?php if ($current_tab == $tab_name) : ?> class="active"<?php endif; ?>><a href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>" role="tab"><?php echo $display_tab_title; ?></a></li>
    <?php endforeach; ?>
    </ul>
  </div>
  
<?php if ($current_tab == 'table_list') : ?>
  <h4 class="tab-annotation"><?php _e('Manageable Table Lists', CDBT); ?></h4>
  <?php if ( 0 === count( $enable_table ) ) : ?>
    <p><?php _e('Now, the manageable table in this plugin does not exist.', CDBT); ?></p>
    <p><?php printf( __('If you want to create a new table, please %sclick here%s.', CDBT), '<a href="'. add_query_arg('tab', 'create_table') .'">', '</a>' ); ?></p>
    <p><?php printf( __('If you want to import an existing table to this plugin, please %sclick here%s.', CDBT), '<a href="'. add_query_arg('tab', 'create_table') .'#resume-table">', '</a>' ); ?></p>
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
  <h4 class="tab-annotation"><?php _e('WordPress Core Table Lists', CDBT); ?></h4>
    
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
  
  if (isset($this->cdbt_sessions[$current_tab]['to_redirect']) && !empty($this->cdbt_sessions[$current_tab]['to_redirect'])) {
    if (isset($this->cdbt_sessions[$current_tab]['target_table']) && !empty($this->cdbt_sessions[$current_tab]['target_table'])) {
      $target_table = $this->cdbt_sessions[$current_tab]['target_table'];
    }
    $to_redirect = $this->cdbt_sessions[$current_tab]['to_redirect'];
  } else {
    $target_table = '';
    $to_redirect = '';
  }
  
?>
  <div class="well-sm">
    <p class="text-info">
      <?php printf( __('In this section, you can create a new table or import an existing table. Please %sclick here%s if you want to import.', CDBT), '<a href="#resume-table">', '</a>' ); ?>
    </p>
  </div>
  
  <div class="cdbt-create-table">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="create_table">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <h4 class="title" id="create-table"><i class="image-icon cdbt-icon-db-leaf square26"></i> <?php _e('General Table Creation Settings', CDBT); ?></h4>
      
      <div class="well-sm">
        <p class="text-info">
          <?php _e('Creates a new table in the database. Please enter the required settings.', CDBT); ?>
        </p>
      </div>
      
      <div class="form-group">
        <label for="create-table-table_name" class="col-sm-2 control-label"><?php _e('Table Name', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-10">
          <div class="input-group col-sm-5" id="create-table-table_name">
            <div class="input-group-addon<?php if ('1' === $options['use_wp_prefix']) : ?> active<?php endif; ?>"><?php echo $this->wpdb->prefix; ?></div>
            <input id="instance_table_name" name="instance_table_name" type="text" value="<?php if (isset($session_vars)) echo $session_vars['instance_table_name']; ?>" class="form-control" placeholder="<?php _e('Please enter the table name', CDBT); ?>">
            <input name="<?php echo $this->domain_name; ?>[table_name]" type="hidden" value="<?php if (isset($session_vars)) echo $session_vars[$this->domain_name]['table_name']; ?>" class="sr-only">
          </div>
          <p id="live_preview" class="help-block col-sm-10"> <?php _e('Realtime preview of finalized table name:', CDBT); ?> <code>tablename</code></p>
          <div class="checkbox" id="instance_prefix_switcher">
          <?php $_enable_prefix = (isset($session_vars) && isset($session_vars['instance_prefix_switcher'])) || $this->strtobool($options['use_wp_prefix']) ? true : false; ?>
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="instance_prefix_switcher" type="checkbox" value="1"<?php if ($_enable_prefix) : ?> checked="checked"<?php endif; ?>>
              <span class="checkbox-label"><?php _e('Uses a table prefix defined at the WordPress configuration (wp-config.php).', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /create-table-table_name -->
      <div class="form-group">
        <label for="create-table-table_comment" class="col-sm-2 control-label"><?php _e('Table Comment', CDBT); ?></label>
        <div class="col-sm-5">
          <input id="create-table-table_comment" name="<?php echo $this->domain_name; ?>[table_comment]" type="text" value="<?php if (isset($session_vars)) echo $session_vars[$this->domain_name]['table_comment']; ?>" class="form-control" placeholder="<?php _e('Please enter the table comment', CDBT); ?>">
          <p class="help-block"><?php _e('This table comment will be used to the table&#39;s display label as a logical name.', CDBT); ?></p>
        </div>
      </div><!-- /create-table-table_comment -->
      <div class="form-group">
        <label for="create-table-table_charset" class="col-sm-2 control-label"><?php _e('Table Charset', CDBT); ?><h6> <?php $this->during_trial( 'default_charset' ); ?></h6></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="create-table-table_charset">
          <?php if ( isset( $session_vars ) ) {
            $_default_charset = ! empty( $session_vars[$this->domain_name]['table_charset'] ) ? $session_vars[$this->domain_name]['table_charset'] : $this->db_default_charset;
          }
          if ( ! isset( $_default_charset ) || empty( $_default_charset ) ) {
            $_default_charset = ! empty( $options['charset'] ) ? $options['charset'] : $this->charset;
          } ?>
            <input type="text" name="<?php echo $this->domain_name; ?>[table_charset]" value="<?php esc_attr_e( $_default_charset ); ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_charsets as $i => $charset) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $charset; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php printf( __('If this is unspecified, the initial value of the current database: %s will be set.', CDBT), '<code>'. $this->db_default_charset .'</code>' ); ?></p>
        </div>
      </div><!-- /create-table-table_charset -->
      <div class="form-group">
        <label for="create-table-table_db_engine" class="col-sm-2 control-label"><?php _e('Database Engine', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="create-table-table_db_engine">
          <?php if ( isset( $session_vars ) ) {
            $_default_engine = ! empty( $session_vars[$this->domain_name]['table_db_engine'] ) ? $session_vars[$this->domain_name]['table_db_engine'] : $this->db_default_engine;
          }
          if ( ! isset( $_default_engine ) || empty( $_default_engine ) ) {
            $_default_engine = ! empty( $options['default_db_engine'] ) ? $options['default_db_engine'] : $this->db_default_engine;
          } ?>
            <input type="text" name="<?php echo $this->domain_name; ?>[table_db_engine]" value="<?php esc_attr_e( $_default_engine ); ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_engines as $i => $db_engine) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $db_engine; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php printf( __('If this is unspecified, the initial value of the current database: %s will be set.', CDBT), '<code>'. $this->db_default_engine .'</code>' ); ?></p>
        </div>
      </div><!-- /create-table-table_db_engine -->
      <div class="form-group">
        <label for="automatically-add-columns" class="col-sm-2 control-label"><?php _e('Automatically Columns Insertion', CDBT); ?><h6> <?php $this->during_trial( 'auto_add_columns' ); ?></h6></label>
        <div class="col-sm-10">
          <div class="checkbox" id="automatically-add-columns1">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[automatically_add_columns][]" type="checkbox" value="ID"<?php if (isset($session_vars)) { if (isset($session_vars[$this->domain_name]['automatically_add_columns']) && in_array('ID', $session_vars[$this->domain_name]['automatically_add_columns'])) { ?> checked="checked"<?php } } else { ?> checked="checked"<?php } ?>>
              <span class="checkbox-label"><?php _e('Prepends the "ID" primary key column (to be AUTO-INCREMENT as surrogate key).', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox" id="automatically-add-columns2">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[automatically_add_columns][]" type="checkbox" value="created"<?php if (isset($session_vars)) { if (isset($session_vars[$this->domain_name]['automatically_add_columns']) && in_array('created', $session_vars[$this->domain_name]['automatically_add_columns'])) { ?> checked="checked"<?php } } else { ?> checked="checked"<?php } ?>">
              <span class="checkbox-label"><?php _e('Appends the "created" column (datetime type) to store a registration date.', CDBT); ?></span>
            </label>
          </div>
          <div class="checkbox" id="automatically-add-columns3">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[automatically_add_columns][]" type="checkbox" value="updated"<?php if (isset($session_vars)) { if (isset($session_vars[$this->domain_name]['automatically_add_columns']) && in_array('updated', $session_vars[$this->domain_name]['automatically_add_columns'])) { ?> checked="checked"<?php } } else { ?> checked="checked"<?php } ?>>
              <span class="checkbox-label"><?php _e('Appends the "updated" column (timestamp type) to store a modification date.', CDBT); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /create-table-automatically-add-columns -->
      <div class="form-group">
        <label for="create-table-create_table_sql" class="col-sm-2 control-label"><?php _e('SQL of CREATE TABLE', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-9">
          <div role="tabpanel">
            <ul class="nav nav-tabs" role="tablist">
              <li role="presentation" class="active"><a href="#direct_sql" aria-controls="direct_sql" role="tab" data-toggle="tab"><?php _e('Edit Directly SQL', CDBT); ?></a></li>
              <li role="presentation"><a href="#table_creator" aria-controls="table_creator" role="tab" data-toggle="tab"><?php _e('Table Creator', CDBT); ?></a></li>
            </ul>
            <div class="tab-content">
              <div role="tabpanel" class="tab-pane active" id="direct_sql"><textarea id="create-table-create_table_sql" name="<?php echo $this->domain_name; ?>[create_table_sql]" class="form-control" rows="10" placeholder="CREATE TABLE table_name ..."><?php if (isset($session_vars)) echo esc_textarea(stripslashes_deep($session_vars[$this->domain_name]['create_table_sql'])); ?></textarea></div>
              <div role="tabpanel" class="tab-pane" id="table_creator"><textarea id="instance_create_table_sql" class="form-control" rows="10" disabled="disabled"><?php if (isset($session_vars)) echo esc_textarea(stripslashes_deep($session_vars[$this->domain_name]['create_table_sql'])); ?></textarea></div>
            </div>
            <div class="sql-support-button pull-right">
              <button type="button" id="create-sql-support" class="btn btn-default btn-xs"><?php _e('Generate Base SQL', CDBT); ?></button>
            </div>
          </div>
          <p class="help-block">
            <?php _e('Example of SQL Statements:', CDBT); ?> <br>
            <pre><code>CREATE TABLE prefix_new_table ( `account_name` varchar(64) NOT NULL COMMENT 'Account Name',  `gender` enum('female','male') DEFAULT NULL COMMENT 'Gender' )</code></pre>
          </p>
        </div>
      </div><!-- /create-table-create_table_sql -->
      <div class="pull-right">
        <a href="#create-table"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
      <h4 class="title" id="plugin-settings"><i class="fa fa-cubes text-muted"></i> <?php _e('Extended Table Settings', CDBT); ?></h4>
      
      <div class="well-sm">
        <p class="text-info">
          <?php _e('In this settings, specifies the options for controlling this table at the plugin. Also you will be able to change about those settings after the table creation.', CDBT); ?>
        </p>
      </div>
      
      <div class="form-group">
        <label for="create-table-max_show_records" class="col-sm-2 control-label"><?php _e('Maximum display data per page', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="create-table-max_show_records">
            <input type="text" name="<?php echo $this->domain_name; ?>[max_show_records]" value="<?php if (isset($session_vars)) { echo intval($session_vars[$this->domain_name]['max_show_records']); } else { echo intval($options['default_per_records']); } ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('This value is the maximum data rows per one page at the time of displaying the table data lists. It will enable at the shortcode or the administration screen.', CDBT); ?></p>
        </div>
      </div><!-- /create-table-max_show_records -->
      <div class="form-group">
        <label for="create-table-sanitaization" class="col-sm-2 control-label"><?php _e('Sanitization', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="create-table-sanitaization">
            <label class="checkbox-custom" data-initialize="checkbox">
            <?php if ( isset( $session_vars ) ) {
                if ( isset( $session_vars[$this->domain_name][ 'sanitization' ] ) ) {
                  $_atts_checked = $session_vars[$this->domain_name]['sanitization'] ? ' checked="checked"' : '';
                } else {
                  $_atts_checked = '';
                }
              } else {
                $_atts_checked = ' checked="checked"';
              } ?>
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[sanitization]" type="checkbox" value="1"<?php echo $_atts_checked; ?>>
              <span class="checkbox-label"><?php _e('Whether to do sanitization when register the string type data into this table.', CDBT); ?></span>
              <?php $this->during_trial( 'sanitaization' ); ?>
            </label>
          </div>
          <div class="clearfix"></div>
          <p class="help-block"><?php _e('If checked, it will remove tags except allowed tags (by default there are "a", "br", "em", "strong") from the entry data. Please uncheck if you want to allow all tags.', CDBT); ?></p>
        </div>
      </div><!-- /create-table-sanitaization -->
      <div class="form-group">
        <label for="create-table-user_permission_view" class="col-sm-2 control-label"><?php _e('Viewable User Roles', CDBT); ?></label>
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
          <p class="help-block"><?php printf( __('This option will be enabled when any user sees the table data out of the administration screen. Actually, this is for the mainly shortcode %s.', CDBT), '<code>&#091;cdbt-view&#093;</code>' ); ?><a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a> <?php $this->during_trial( 'user_permission_view' ); ?></p>
        </div>
      </div><!-- /create-table-user_permission_view -->
      <div class="form-group">
        <label for="create-table-user_permission_entry" class="col-sm-2 control-label"><?php _e('Registrable User Roles', CDBT); ?></label>
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
          <p class="help-block"><?php printf( __('This option will be enabled when any user registers the table data out of the administration screen. Actually, this is for the mainly shortcode %s.', CDBT), '<code>&#091;cdbt-entry&#093;</code>' ); ?><a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a> <?php $this->during_trial( 'user_permission_entry' ); ?></p>
        </div>
      </div><!-- /create-table-user_permission_entry -->
      <div class="form-group">
        <label for="create-table-user_permission_edit" class="col-sm-2 control-label"><?php _e('Editable User Roles', CDBT); ?></label>
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
          <p class="help-block"><?php printf( __('This option will be enabled when any user edits the table data out of the administration screen. Actually, this is for the mainly shortcode %s.', CDBT), '<code>&#091;cdbt-edit&#093;</code>' ); ?><a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a> <?php $this->during_trial( 'user_permission_edit' ); ?></p>
        </div>
      </div><!-- /create-table-user_permission_edit -->
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <ul id="foot-note-1" class="foot-note">
            <li><i class="fa fa-info-circle"></i> <?php printf( __('If you want to set specific permissions, please set the capability of WordPress. %sPlease see here more information of capability%s.', CDBT), '<a href="https://codex.wordpress.org/Roles_and_Capabilities" target="_blank">', '</a>' ); ?></li>
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
      
      <h4 class="title" id="resume-table"><i class="fa fa-reply text-muted"></i> <?php _e('Import Existing Table', CDBT); ?></h4>
      
      <div class="well-sm">
        <p class="text-info">
          <?php _e('You can import an existing table that is not manageable. You will be able to manage in this plugin if that has impoeted successfully.', CDBT); ?>
        </p>
      </div>
      
    <?php
    if ( 0 === count($resume_table_list = array_diff($unreserved_table, $enable_table)) ) : ?>
      
      <div class="form-group">
        <p class="well-sm col-sm-offset-2 col-sm-8"><?php _e('Now, an importable table to this plugin does not exist.', CDBT); ?></p>
      </div>
      
    <?php else : ?>
      <div class="form-group">
        <label for="resume-table-resume_table" class="col-sm-2 control-label"><?php _e('Importable Tables', CDBT); ?></label>
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
          <button type="submit" class="btn btn-primary"><?php _e('Import Table', CDBT); ?></button>
        </div>
      </div>
    <?php endif; ?>
      
      <div class="pull-right">
        <a href="#page-head"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
    </form>
  </div><!-- /.cdbt-create-table -->
<?php if (!empty($to_redirect)) : ?>
  <input type="hidden" name="target_table" value="<?php echo $target_table; ?>" disabled="disabled">
  <input type="hidden" id="after-notice-redirection" value="<?php echo $to_redirect; ?>" disabled="disabled">
<?php endif; ?>
<?php endif; /* End of `create_table` tab contents */ ?>
  
<?php if ($current_tab == 'modify_table') : ?>
  
<?php
  /**
   * Define the localized variables for tab of `modify_table`
   */
  
  $is_enable_modify = false;
  $is_updated = false;
  $to_redirect = '';
  $modify_table = '';
  $table_options = [];
  if (isset($this->cdbt_sessions[$current_tab]) && isset($this->cdbt_sessions[$current_tab]['target_table'])) {
    $modify_table = $this->cdbt_sessions[$current_tab]['target_table'];
    if (in_array($modify_table, $enable_table)) {
      $is_enable_modify = true;
      $_session_key = 'do_' . $this->query['page'] . '_' . $current_tab;
      if (isset($this->cdbt_sessions[$_session_key]) && array_key_exists($this->domain_name, $this->cdbt_sessions[$_session_key]) && !empty($this->cdbt_sessions[$_session_key][$this->domain_name])) {
        // Set variables from session
        $session_vars = $this->cdbt_sessions[$_session_key][$this->domain_name];
      }
    }
    if (isset($this->cdbt_sessions[$current_tab]['is_modified']) && $this->cdbt_sessions[$current_tab]['is_modified']) {
      $is_updated = true;
      if (isset($this->cdbt_sessions[$current_tab]['to_redirect']) && !empty($this->cdbt_sessions[$current_tab]['to_redirect'])) {
        $to_redirect = $this->cdbt_sessions[$current_tab]['to_redirect'];
      }
    }
    $table_options = $this->get_table_option($modify_table);
  }
  
?>
<?php if (!$is_enable_modify) : ?>
  
  <div class="cdbt-modify-table">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="modify_table">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <div class="well-sm">
        <p class="text-info">
          <?php if (!empty($modify_table)) {
            _e('A table is not specified. Please choose a table that you want to modify.', CDBT); 
          } else {
            _e('The specified table cannot be modified in this plugin. Please choose the other table.', CDBT); 
          } ?>
        </p>
      </div>
      
      <div class="form-group">
        <label for="modify-table-change_table" class="col-sm-3 control-label"><?php _e('Choose Modify Table', CDBT); ?></label>
        <div class="col-sm-9">
          <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="modify-table-change_table">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
              <span class="selected-label"></span>
              <span class="caret"></span>
              <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
            </button>
            <ul class="dropdown-menu" role="menu">
              <li data-value=""><a href="#"><span class="text-muted"><?php _e('Please choose', CDBT); ?></span></a></li>
              <?php foreach ($enable_table as $table) : ?>
                <li data-value="<?php echo $table; ?>"<?php if ($modify_table === $table) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php echo $table; ?></a></li>
              <?php endforeach; ?>
            </ul>
            <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[modify_table]" readonly="readonly" aria-hidden="true" type="text">
          </div>
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9">
          <button type="submit" class="btn btn-primary" id="modify-table-action-change_table"><?php _e('To Modify Table', CDBT); ?></button>
        </div>
      </div>
    </form>
  </div>
  
<?php else : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php printf( __('In this section, you can modify specific table schema. Also you can change permission to access to the rendered content that related the table by the shortcode. In the latter case %sgo here%s.', CDBT), '<a href="#table-attribution">', '</a>' ); ?>
    </p>
  </div>
  
  <div class="cdbt-modify-table">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" id="cdbt-alter-table-form" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="modify_table">
      <input type="hidden" name="target_table" value="<?php echo $modify_table; ?>">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <h4 class="title" id="alter-table"><i class="fa fa-wrench text-muted"></i> <?php _e('Table Schema Modification', CDBT); ?></h4>
      
      <div class="well-sm">
        <p class="text-info">
          <?php _e('Modify the specified table schema by issuing the SQL query (of as like ALTER TABLE). Please enter the below item that you want to modify.', CDBT); ?>
        </p>
      </div>
      
      <div class="form-group">
        <label for="modify-table-table_name" class="col-sm-2 control-label"><?php _e('Table Name', CDBT); ?></label>
        <div class="col-sm-4">
          <input id="modify-table-table_name" name="<?php echo $this->domain_name; ?>[table_name]" type="text" value="<?php echo isset($session_vars) && isset($session_vars['table_name']) ? esc_attr($session_vars['table_name']) : esc_attr($modify_table); ?>" class="form-control" placeholder="Table Name">
        </div>
        <div class="col-sm-6">
          <button type="button" id="btn-undo-modify-table-table_name" class="btn btn-default" data-prev-value="<?php echo esc_attr($modify_table); ?>" ><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
        </div>
      </div><!-- /modify-table-table_name -->
      <div class="form-group">
        <label for="modify-table-table_comment" class="col-sm-2 control-label"><?php _e('Table Comment', CDBT); ?></label>
        <div class="col-sm-5">
          <input id="modify-table-table_comment" name="<?php echo $this->domain_name; ?>[table_comment]" type="text" value="<?php echo isset($session_vars) && isset($session_vars['table_comment']) ? esc_attr($session_vars['table_comment']) : isset( $table_options['table_comment'] ) ? esc_attr($table_options['table_comment']) : ''; ?>" class="form-control" placeholder="Table Comment">
          <p class="help-block"><?php _e('Table Comments are used to display name as a logical name.', CDBT); ?></p>
        </div>
        <div class="col-sm-5">
          <button type="button" id="btn-undo-modify-table-table_comment" class="btn btn-default" data-prev-value="<?php echo esc_attr($table_options['table_comment']); ?>" ><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
        </div>
      </div><!-- /modify-table-table_comment -->
      <div class="form-group">
        <label for="modify-table-table_charset" class="col-sm-2 control-label"><?php _e('Table Charset', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3 pull-left" data-initialize="combobox" id="modify-table-table_charset">
          <?php 
            if ( isset( $session_vars ) && isset( $session_vars['table_charset'] ) ) {
              $current_charset = esc_attr( $session_vars['table_charset'] );
            } else {
              $current_charset = $_undo_charset = $this->get_table_charset( $modify_table );
            }
            if ( ! in_array( $current_charset, $this->db_charsets ) ) {
              $_default_charset_value = ' value="'. $current_charset .'"';
            } else {
              $_default_charset_value = '';
            } ?>
            <input type="text" id="modify-table-table_charset_input" name="<?php echo $this->domain_name; ?>[table_charset]"<?php echo $_default_charset_value; ?> class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_charsets as $i => $charset) : ?>
                <li data-value="<?php echo $i + 1; ?>"<?php if ( $current_charset === $charset ) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php echo $charset; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="col-sm-7 pull-left" style="margin-left: 15px;">
            <button type="button" id="btn-undo-modify-table-table_charset_input" class="btn btn-default" data-prev-value="<?php echo esc_attr( $_undo_charset ); ?>" ><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
          </div>
          <div class="clearfix"></div>
        </div>
      </div><!-- /modify-table-table_charset -->
      <div class="form-group">
        <label for="modify-table-table_db_engine" class="col-sm-2 control-label"><?php _e('Database Engine', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3 pull-left" data-initialize="combobox" id="modify-table-table_db_engine">
          <?php 
            if ( isset( $session_vars ) && isset( $session_vars['table_db_engine'] ) ) {
              $current_db_engine = esc_attr( $session_vars['table_db_engine'] );
            } else {
              $current_db_engine = $_undo_engine = $this->get_table_status( $modify_table, 'Engine' );
            }
            if ( ! in_array( $current_db_engine, $this->db_engines ) ) {
              $_default_engine_value = ' value="'. $current_db_engine .'"';
            } else {
              $_default_engine_value = '';
            } ?>
            <input type="text" id="modify-table-table_db_engine_input" name="<?php echo $this->domain_name; ?>[table_db_engine]"<?php echo $_default_engine_value; ?> class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_engines as $i => $db_engine) : ?>
                <li data-value="<?php echo $i + 1; ?>"<?php if ( $current_db_engine === $db_engine ) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php echo $db_engine; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="col-sm-7 pull-left" style="margin-left: 15px;">
            <button type="button" id="btn-undo-modify-table-table_db_engine_input" class="btn btn-default" data-prev-value="<?php echo esc_attr( $_undo_engine ); ?>" ><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
          </div>
          <div class="clearfix"></div>
        </div>
      </div><!-- /modify-table-table_db_engine -->
      <div class="form-group">
        <label for="modify-table-alter_table_sql" class="col-sm-2 control-label"><?php _e('SQL Query', CDBT); ?></label>
        <div class="col-sm-9">
          <div role="tabpanel">
            <ul class="nav nav-tabs" role="tablist">
              <li role="presentation" class="active"><a href="#direct_sql" aria-controls="direct_sql" role="tab" data-toggle="tab"><?php _e('Edit Directly SQL', CDBT); ?></a></li>
              <li role="presentation"><a href="#create_table_sql" aria-controls="create_table_sql" role="tab" data-toggle="tab"><?php _e('Show CREATE TABLE', CDBT); ?></a></li>
              <?php /* <li role="presentation"><a href="#table_creator" aria-controls="table_creator" role="tab" data-toggle="tab"><?php _e('Table Creator', CDBT); ?></a></li> */ ?>
            </ul>
            <div class="tab-content">
              <div role="tabpanel" class="tab-pane active" id="direct_sql"><textarea id="modify-table-alter_table_sql" name="<?php echo $this->domain_name; ?>[alter_table_sql]" class="form-control" rows="10" placeholder="ALTER TABLE table_name ..."><?php if (isset($session_vars) && isset($session_vars['alter_table_sql'])) echo esc_textarea(stripslashes_deep($session_vars['alter_table_sql'])); ?></textarea></div>
              <div role="tabpanel" class="tab-pane" id="create_table_sql"><textarea id="view_create_table_sql" class="form-control" rows="10" readonly="readonly"><?php echo esc_textarea($table_options['sql']); ?></textarea></div>
              <?php /* <div role="tabpanel" class="tab-pane" id="table_creator"><textarea id="instance_alter_table_sql" class="form-control" rows="10" disabled="disabled"></textarea></div> */ ?>
            </div>
            <div class="sql-support-button pull-right" style="top: 3px;">
              <button type="button" id="btn-undo-modify-table-alter_table_sql" class="btn btn-default" data-prev-value=""><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
            </div>
          </div>
          <p class="help-block">
            <?php _e('Example of SQL Statements:', CDBT); ?> <br>
            <pre><code>ALTER TABLE prefix_new_table CHANGE `before_column` `after_column` varchar(100) NOT NULL COMMENT 'After Column' AFTER `first_column`;</code></pre>
          </p>
        </div>
      </div><!-- /modify-table-create_table_sql -->
      
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" id="submit-alter-table" class="btn btn-primary"><?php _e('Modify Table Schema', CDBT); ?></button>
        </div>
      </div>
      
      <div class="pull-right">
        <a href="#plugin-settings"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
    </form>
    
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" id="cdbt-update-options-form" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="update_options">
      <input type="hidden" name="target_table" value="<?php echo $modify_table; ?>">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <h4 class="title" id="table-attribution"><i class="fa fa-cubes text-muted"></i> <?php _e('Table Settings Modification', CDBT); ?></h4>
      
      <div class="well-sm">
        <p class="text-info">
          <?php _e('The below settings are for when rendering this table with mainly shortcodes and administration screen.', CDBT); ?>
        </p>
      </div>
      
      <div class="form-group">
        <label for="modify-table-max_show_records" class="col-sm-2 control-label"><?php _e('Maximum display data per page', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3 pull-left" data-initialize="spinbox" id="modify-table-max_show_records">
            <input type="text" id="modify-table-max_show_records_input" name="<?php echo $this->domain_name; ?>[max_show_records]" value="<?php echo isset($session_vars) && isset($session_vars['max_show_records']) ? intval($session_vars['max_show_records']) : intval($table_options['show_max_records']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <div class="col-sm-5 pull-left" style="margin-left: 15px;">
            <button type="button" id="btn-undo-modify-table-max_show_records_input" class="btn btn-default" data-prev-value="<?php echo $is_updated ? $session_vars['max_show_records'] : intval($table_options['show_max_records']); ?>" ><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
          </div>
          <div class="clearfix"></div>
          <p class="help-block"><?php _e('This value is the maximum data rows per one page at the time of displaying the table data lists. It will enable at the shortcode or the administration screen.', CDBT); ?></p>
        </div>
      </div><!-- /modify-table-max_show_records -->
      <div class="form-group">
        <label for="modify-table-sanitaization" class="col-sm-2 control-label"><?php _e('Sanitization', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="modify-table-sanitaization">
            <label class="checkbox-custom" data-initialize="checkbox">
            <?php if ( isset( $session_vars ) && isset( $session_vars[$this->domain_name][ 'sanitization' ] ) ) {
                $_atts_checked = $session_vars[$this->domain_name]['sanitization'] ? ' checked="checked"' : '';
                $_undo_val = $session_vars[$this->domain_name]['sanitization'] ? '1' : '0';
              } else if ( isset( $table_options['sanitization'] ) ) {
                $_atts_checked = $table_options['sanitization'] ? ' checked="checked"' : '';
                $_undo_val = $table_options['sanitization'] ? '1' : '0';
              }
              if ( ! isset( $_atts_checked ) ) {
                $_atts_checked = ' checked="checked"';
                $_undo_val = '1';
              } ?>
              <input class="sr-only" id="modify-table-sanitization_input" name="<?php echo $this->domain_name; ?>[sanitization]" type="checkbox" value="1"<?php echo $_atts_checked; ?>>
              <span class="checkbox-label"><?php _e('Whether to do sanitization when register the string type data into this table.', CDBT); ?></span>
              <?php $this->during_trial( 'sanitaization' ); ?>
              <button type="button" id="btn-undo-modify-table-sanitization_input" class="btn btn-default" data-prev-value="<?php echo $_undo_val; ?>" style="margin-left: 15px;"><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
            </label>
          </div>
          <div class="clearfix"></div>
          <p class="help-block"><?php _e('If checked, it will remove tags except allowed tags (by default there are "a", "br", "em", "strong") from the entry data. Please uncheck if you want to allow all tags.', CDBT); ?></p>
        </div>
      </div><!-- /create-table-sanitaization -->
      <div class="form-group">
        <label for="modify-table-user_permission_view" class="col-sm-2 control-label"><?php _e('Viewable User Roles', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3 pull-left" data-initialize="combobox" id="modify-table-user_permission_view">
          <?php if ( isset( $session_vars ) && isset( $session_vars['user_permission_view'] ) ) { $_current_value = $session_vars['user_permission_view']; } elseif ( isset( $table_options['permission'] ) ) { $_current_value = implode( ',', $table_options['permission']['view_global'] ); } else { $_current_value = implode( ',', $this->convert_cap_level( intval( $table_options['roles']['view_role'] ) - 1 ) ); } ?>
            <input type="text" id="modify-table-user_permission_view_input" name="<?php echo $this->domain_name; ?>[user_permission_view]" value="<?php echo $_current_value; ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->user_roles as $i => $role) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $role; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="col-sm-7 pull-left" style="margin-left: 15px;">
            <button type="button" id="btn-undo-modify-table-user_permission_view_input" class="btn btn-default" data-prev-value="<?php echo $is_updated ? esc_attr($session_vars['user_permission_view']) : esc_attr(implode(',', $table_options['permission']['view_global'])); ?>" ><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
          </div>
          <div class="clearfix"></div>
          <p class="help-block"><?php printf( __('This option will be enabled when any user sees the table data out of the administration screen. Actually, this is for the mainly shortcode %s.', CDBT), '<code>&#091;cdbt-view&#093;</code>' ); ?><a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a></p>
        </div>
      </div><!-- /modify-table-user_permission_view -->
      <div class="form-group">
        <label for="modify-table-user_permission_entry" class="col-sm-2 control-label"><?php _e('Registrable User Roles', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3 pull-left" data-initialize="combobox" id="modify-table-user_permission_entry">
          <?php if ( isset( $session_vars ) && isset( $session_vars['user_permission_entry'] ) ) { $_current_value = $session_vars['user_permission_entry']; } elseif ( isset( $table_options['permission'] ) ) { $_current_value = implode( ',', $table_options['permission']['entry_global'] ); } else { $_current_value = implode( ',', $this->convert_cap_level( intval( $table_options['roles']['input_role'] ) - 1 ) ); } ?>
            <input type="text" id="modify-table-user_permission_entry_input" name="<?php echo $this->domain_name; ?>[user_permission_entry]" value="<?php echo $_current_value; ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->user_roles as $i => $role) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $role; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="col-sm-7 pull-left" style="margin-left: 15px;">
            <button type="button" id="btn-undo-modify-table-user_permission_entry_input" class="btn btn-default" data-prev-value="<?php echo $is_updated ? esc_attr($session_vars['user_permission_entry']) : esc_attr(implode(',', $table_options['permission']['entry_global'])); ?>" ><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
          </div>
          <div class="clearfix"></div>
          <p class="help-block"><?php printf( __('This option will be enabled when any user registers the table data out of the administration screen. Actually, this is for the mainly shortcode %s.', CDBT), '<code>&#091;cdbt-entry&#093;</code>' ); ?><a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a></p>
        </div>
      </div><!-- /modify-table-user_permission_entry -->
      <div class="form-group">
        <label for="modify-table-user_permission_edit" class="col-sm-2 control-label"><?php _e('Editable User Roles', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3 pull-left" data-initialize="combobox" id="modify-table-user_permission_edit">
          <?php if ( isset( $session_vars ) && isset( $session_vars['user_permission_edit'] ) ) { $_current_value = $session_vars['user_permission_edit']; } elseif ( isset( $table_options['permission'] ) ) { $_current_value = implode( ',', $table_options['permission']['edit_global'] ); } else { $_current_value = implode( ',', $this->convert_cap_level( intval( $table_options['roles']['edit_role'] ) - 1 ) ); } ?>
            <input type="text" id="modify-table-user_permission_edit_input" name="<?php echo $this->domain_name; ?>[user_permission_edit]" value="<?php echo $_current_value; ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->user_roles as $i => $role) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $role; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="col-sm-7 pull-left" style="margin-left: 15px;">
            <button type="button" id="btn-undo-modify-table-user_permission_edit_input" class="btn btn-default" data-prev-value="<?php echo $is_updated ? esc_attr($session_vars['user_permission_edit']) : esc_attr(implode(',', $table_options['permission']['edit_global'])); ?>" ><i class="fa fa-undo"></i> <?php _e('Undo', CDBT); ?></button>
          </div>
          <div class="clearfix"></div>
          <p class="help-block"><?php printf( __('This option will be enabled when any user edits the table data out of the administration screen. Actually, this is for the mainly shortcode %s.', CDBT), '<code>&#091;cdbt-edit&#093;</code>' ); ?><a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a></p>
        </div>
      </div><!-- /modify-table-user_permission_edit -->
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <ul id="foot-note-1" class="foot-note">
            <li><i class="fa fa-info-circle"></i> <?php printf( __('If you want to set specific permissions, please set the capability of WordPress. %sPlease see here more information of capability%s.', CDBT), '<a href="https://codex.wordpress.org/Roles_and_Capabilities" target="_blank">', '</a>' ); ?></li>
          </ul>
        </div>
      </div>
      
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" id="submit-update-options" class="btn btn-primary"><?php _e('Save Changes', CDBT); ?></button>
        </div>
      </div>
      
      <div class="pull-right">
        <a href="#page-head"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
    </form>
  </div><!-- /.cdbt-modify-table -->
<?php if (!empty($to_redirect)) : ?>
  <input type="hidden" id="after-notice-redirection" value="<?php echo $to_redirect; ?>" disabled="disabled">
<?php endif; ?>
<?php endif; ?>
<?php endif; /* End of `modify_table` tab contents */ ?>
  
<?php if ($current_tab === 'operate_table' || $current_tab === 'operate_data') : 
  /**
   * Define the localized variables for tab of `operate_table` and `operate_data`
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
    
  } else {
    if ( isset( $_COOKIE['cdbtOperatingTable'] ) && ! empty( $_COOKIE['cdbtOperatingTable'] ) ) {
      $target_table = $_COOKIE['cdbtOperatingTable'];
    }
    
    if ( ! empty( $target_table ) && empty( $current_action ) ) 
      $current_action = 'operate_table' === $current_tab ? 'detail' : 'view';
    
  }
  
  // Definition of belong table type
  if (in_array($target_table, $this->core_tables)) {
    $belong_table_type = [ 'type_name' => 'wordpress', 'icon' => 'fa fa-wordpress' ];
  } else
  if (in_array($target_table, $enable_table)) {
    $belong_table_type = [ 'type_name' => 'regular', 'icon' => 'image-icon cdbt-icon square22' ];
  } else {
    $belong_table_type = [ 'type_name' => 'other', 'icon' => 'image-icon cdbt-icon-db square22' ];
  }
  
  // Definition of operatable console buttons
  if ($current_tab === 'operate_table') {
    $operatable_buttons = [
      'detail'      => [ 'label' => __( 'View Detail', CDBT),      'icon' => 'fa fa-list-alt',                         'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
      'import'    => [ 'label' => __( 'Import Data', CDBT),      'icon' => 'glyphicon glyphicon-import',       'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
      'export'    => [ 'label' => __( 'Export Data', CDBT),      'icon' => 'glyphicon glyphicon-export',       'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
      'duplicate' => [ 'label' => __( 'Duplicate Table', CDBT), 'icon' => 'glyphicon glyphicon-duplicate',   'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
      'truncate'  => [ 'label' => __( 'Initialize Table', CDBT),  'icon' => 'glyphicon glyphicon-certificate', 'allow_type' => [ 'regular', 'other' ] ], 
      'modify'    => [ 'label' => __( 'Modify Table', CDBT),     'icon' => 'fa fa-wrench',                        'allow_type' => [ 'regular', 'other' ] ], 
      'backup'    => [ 'label' => __( 'Backup Table', CDBT),   'icon' => 'glyphicon glyphicon-save-file',   'allow_type' => [ 'regular', 'wordpress', 'other' ] ], // Added new since v2.1.31
      'drop'       => [ 'label' => __( 'Delete Table', CDBT),     'icon' => 'fa fa-trash-o',                        'allow_type' => [ 'regular', 'other' ] ], 
    ];
  } else
  if ($current_tab === 'operate_data') {
    $operatable_buttons = [
      'view'  => [ 'label' => __( 'View Data', CDBT),  'icon' => 'fa fa-eye',                   'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
      'entry' => [ 'label' => __( 'Entry Data', CDBT),  'icon' => 'fa fa-plus',                  'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
      'edit'   => [ 'label' => __( 'Edit Data', CDBT),    'icon' => 'fa fa-pencil-square-o',  'allow_type' => [ 'regular', 'wordpress', 'other' ] ], 
    ];
  }
  
?>
  
  <nav class="navbar navbar-default navbar-inner-tab" id="operation-navbar">
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
          <button type="submit" class="btn btn-default" id="operate-table-action-change_table"><?php _e('Change Operating Table', CDBT); ?></button>
        </div>
        <input type="hidden" name="<?php echo $this->domain_name; ?>[operate_current_table]" value="<?php echo $target_table; ?>">
        <input type="hidden" name="<?php echo $this->domain_name; ?>[operate_action]" value="<?php echo $current_action; ?>">
        <div class="navbar-right">
        <?php foreach ($operatable_buttons as $action_name => $definitions) : ?>
          <button type="button" class="btn btn-default<?php if ($action_name === $current_action) : ?> active<?php endif; ?>" id="operate-table-action-<?php echo $action_name; ?>" title="<?php echo $definitions['label']; ?>"<?php if (empty($target_table) || !in_array($belong_table_type['type_name'], $definitions['allow_type'])) : ?> disabled="disabled"<?php endif; ?>><span class="sr-only"><?php echo $definitions['label']; ?></span><i class="<?php echo $definitions['icon']; ?>"></i></button>
        <?php endforeach; ?>
        </div>
      </form>
    </div>
  </nav>
  
<?php 
  if ( ! empty( $target_table ) ) {
    $table_status = $this->get_table_status( $target_table );
    if ( ! $table_status ) 
      $table_status = [];
    
    $columns_schema = $this->get_table_schema( $target_table );
    $columns_schema_index = is_array( $columns_schema ) ? array_keys( reset( $columns_schema ) ) : [];
    $row_index_number = 1;
    if ( empty( $columns_schema_index ) ) {
      $columns_schema = [];
      $current_action = '';
    }
  }
  
  if ( empty( $current_action ) ) : 
?>
  
  <div class="well-sm">
    <p class="text-info">
      <?php if ($current_tab === 'operate_table') { _e('In this section, you can perform various operations to any table. Please press an operating button that you want to perform.', CDBT); } ?>
      <?php if ($current_tab === 'operate_data') { _e('In this section, you can manipulate data in the specified table. Please press the operating button that you want to perform.', CDBT); } ?>
    </p>
  </div>
  
<?php endif;
  if ($current_tab === 'operate_table') : ?>

<section id="detail" class="<?php if ('detail' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  <div class="table-responsive cdbt-kinetic">
    <strong><i class="fa fa-square text-muted"></i> <?php _e('Table Columns Information', CDBT); ?></strong>
    <table id="columns-detail" class="table table-striped table-bordered table-hover table-condensed">
      <thead>
        <tr class="active">
          <th><small><i class="fa fa-hashtag" aria-hidden="true"></i></small></th>
          <th><small><?php _e('Column Name', CDBT); ?></small></th>
        <?php foreach ($columns_schema_index as $columns_index_name) : ?>
          <th class="text-center"><small><?php echo _x( $columns_index_name, 'cdbt:table-columns-info', CDBT ); ?></small></th>
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
          <th colspan="4" class="col"><i class="fa fa-square text-muted"></i> <?php _e('Table Schema Information', CDBT); ?></th>
        </tr>
      </thead>
      <tbody>
      <?php $index_num = 0; foreach ($table_status as $key => $value) : ?>
        <?php if (intval(fmod($index_num, 2)) === 0) : ?><tr><?php endif; ?>
          <th class="row"><small><?php echo _x( $key, 'cdbt:table-schema-info', CDBT ); ?></small></th><td><small><?php echo $value; ?></small></td>
        <?php if ($index_num > 0 && intval(fmod($index_num, 2)) === 1) : ?></tr><?php endif; ?>
      <?php $index_num++; endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="4" style="padding: 0;"></td></tr>
      </tfoot>
    </table>
  </div>
  
  <div class="table-responsive">
    <table id="table-create-sql" class="table table-condensed">
      <thead>
        <tr>
          <th class="col"><i class="fa fa-square text-muted"></i> <?php _e('SQL of CREATE TABLE', CDBT); ?></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><pre class="cdbt-clipboard"><code><?php echo $this->get_create_table_sql( $target_table ) . ';'; ?></code></pre></td>
        </tr>
      </tbody>
    </table>
  </div>
</section>
  
<section id="import" class="<?php if ('import' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons['import']['icon']; ?> text-muted"></i> <?php _e('Import Data', CDBT); ?></h4> <?php $this->during_trial( 'import_table' ); ?>
  <div class="well-sm">
    <p class="text-info">
      <?php printf(__('In this section, you can import the data into the currently specified table "%s". The import process is done in the wizard format along the procedure. Please follow the instructions of each step.', CDBT), $target_table); ?>
    </p>
  </div>
<?php
  $session_var = [];
  if (isset($this->cdbt_sessions)) {
    foreach ($this->cdbt_sessions as $sessions) {
      $session_var = array_merge($session_var, $sessions);
    }
  }
//var_dump($session_var);
  $wizard_step = (isset($session_var['import_current_step']) && !empty($session_var['import_current_step'])) ? intval($session_var['import_current_step']) : 1;
//  $wizard_step = (isset($session_var[$current_tab]['import_current_step']) && !empty($session_var[$current_tab]['import_current_step'])) ? intval($session_var[$current_tab]['import_current_step']) : 1;
?>

  <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal" id="form-import_table"<?php if ($wizard_step === 1) : ?> enctype="multipart/form-data"<?php endif; ?>>
    <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
    <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
    <input type="hidden" name="action" value="import_table">
    <input type="hidden" name="import_to" value="<?php echo $target_table; ?>">
    <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
<?php
  /**
   * Define the localized variables for tab of `wizard`
   */
  $conponent_options = [
    'id' => 'cdbt-wizard', 
    'defaultStep' => 1, 
    'currentStep' => $wizard_step, 
    'displayMaxStep' => 3, 
    'stepLabels' => [ __('Step1', CDBT), __('Step2', CDBT), __('Step3', CDBT), ], 
    'splitRendering' => 'before', 
    'disablePreviousStep' => true, 
  ];
  $this->component_render('wizard', $conponent_options); // by trait `DynamicTemplate`
  
  if (isset($session_var[$this->domain_name]['add_first_line']) && !empty($session_var[$this->domain_name]['add_first_line'])) {
    $add_first_line = !is_array($session_var[$this->domain_name]['add_first_line']) ? $this->strtoarray($session_var[$this->domain_name]['add_first_line']) : $session_var[$this->domain_name]['add_first_line'];
  }
  
//var_dump($session_var);
?>
  <div class="step-pane bg-default active alert" data-step="1">
    <h4><?php _e('Upload file for importing', CDBT); ?></h4>
    <div class="form-group">
      <label for="import-table-upload_filetype" class="col-sm-3 control-label"><?php _e('Upload File Type', CDBT); ?><?php echo $label_required; ?></label>
      <div class="col-sm-9">
        <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="import-table-upload_filetype">
          <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
            <span class="selected-label"></span>
            <span class="caret"></span>
            <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
          <?php foreach ($allow_file_types as $filetype_name => $filetype_label) : ?>
            <li data-value="<?php echo $filetype_name; ?>"<?php if (isset($session_var[$this->domain_name]['import_filetype']) && $session_var[$this->domain_name]['import_filetype'] === $filetype_name) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php echo $filetype_label; ?></a></li>
          <?php endforeach; ?>
          </ul>
          <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[import_filetype]" readonly="readonly" aria-hidden="true" type="text"/>
        </div>
      </div>
    </div><!-- /import-table-upload_filetype -->
    <div class="form-group" id="switching-item-add_first_line">
      <label for="import-table-add_first_line" class="col-sm-3 control-label"><?php _e('Prepend Indices Line', CDBT); ?><?php echo $label_required; ?></label>
<!--
      <div class="col-sm-9">
        <textarea id="import-table-add_first_line" name="<?php echo $this->domain_name; ?>[add_first_line]" class="form-control" rows="2"><?php echo '"' . implode('","', array_keys($columns_schema)) . '"'; ?></textarea>
        <div class="sr-only" id="csv_index_line_preset"><?php echo '"' . implode('","', array_keys($columns_schema)) . '"'; ?></div>
        <div class="sr-only" id="tsv_index_line_preset"><?php echo '"' . implode("\"\t\"", array_keys($columns_schema)) . '"'; ?></div>
      </div>
      <div class="col-sm-offset-2 col-sm-10">
-->
      <div class="col-sm-9">
        <div class="pillbox" data-initialize="pillbox" id="import-table-add_first_line">
          <ul class="clearfix pill-group">
          <?php foreach (array_keys($columns_schema) as $column_name) : ?>
          <?php if (isset($add_first_line)) { if (!in_array($column_name, $add_first_line)) continue; } ?>
            <li class="btn btn-default pill" data-value="<?php echo esc_attr($column_name); ?>">
              <span><?php echo $column_name; ?></span>
              <span class="glyphicon glyphicon-close">
                <span class="sr-only"><?php _e('Remove', CDBT); ?></span>
              </span>
            </li>
          <?php endforeach; ?>
            <li class="pillbox-input-wrap btn-group">
              <a class="pillbox-more"><?php printf(__('and %s more...', CDBT), '<span class="pillbox-more-count"></span>'); ?></a>
              <input type="text" class="form-control dropdown-toggle pillbox-add-item" placeholder="<?php esc_attr_e('Add column', CDBT); ?>">
              <button type="button" class="dropdown-toggle sr-only">
                <span class="caret"></span>
                <span class="sr-only"><?php _e('Toggle Dropdown', CDBT); ?></span>
              </button>
              <ul class="suggest dropdown-menu" role="menu" data-toggle="dropdown" data-flip="auto"></ul>
            </li>
          </ul>
        </div>
        <input type="hidden" id="import-table-add_first_line-instance" name="<?php echo $this->domain_name; ?>[add_first_line]" value="<?php if (isset($add_first_line)) echo implode(',', $add_first_line); ?>">
        <p class="help-block"><?php _e('You should prepend a column&#39;s name only line as an indices of data in that file if you upload the file of "CSV" or "TSV" without an indices head line.', CDBT); ?> 
          <?php _e('Please specify the indices line to match the data order within uploading file at the above pillbox field as needed.', CDBT); ?></p>
      </div>
    </div><!-- /import-table-add_first_line -->
    <div class="form-group">
      <label for="import-table-upfile" class="col-sm-3 control-label"><?php _e('Upload File', CDBT); ?><?php echo $label_required; ?></label>
      <div class="col-sm-9">
        <input type="file" name="<?php echo $this->domain_name; ?>[upfile]" id="import-table-upfile">
        <p class="help-block"><?php _e('Please upload a file of format based in the preceding settings.', CDBT); ?></p>
      </div>
    </div><!-- /import-table-upfile -->
    <div class="form-group">
      <div class="col-sm-offset-3 col-sm-9">
        <button type="submit" class="btn btn-primary" id="button-submit-import_step1"><?php _e('Upload', CDBT); ?></button>
      </div>
    </div>
  </div>
  
  <div class="step-pane bg-default alert" data-step="2">
    <h4><?php _e('Confirm SQL for importing data', CDBT); ?></h4>
    <div class="step-body">
      <div class="form-group">
    <?php if ($wizard_step === 2 && isset($session_var[$this->domain_name]['import_filetype'])) : ?>
      <?php if (in_array($session_var[$this->domain_name]['import_filetype'], ['csv', 'tsv', 'json', 'sql'])) : ?>
        <label for="import-table-upfile" class="col-sm-2 control-label"><?php _e('Import SQL Statement', CDBT); ?></label>
        <div class="col-sm-9">
        <?php $import_data = is_array( $session_var[$this->domain_name]['upfile'] ) ? array_shift( $session_var[$this->domain_name]['upfile'] ) : $session_var[$this->domain_name]['upfile']; ?>
          <textarea name="confirm_sql" id="confirm_sql" class="form-control" rows="15" readonly="readonly"><?php echo stripslashes_deep( $import_data ); ?></textarea>
          <p class="help-block"><?php _e('Note: If contain the binary data to the importing SQL, it may not be successfully imported.', CDBT); ?></p>
        </div>
        <input type="hidden" name="<?php echo $this->domain_name; ?>[import_sql]" value="<?php echo esc_attr( $import_data ); ?>">
      <?php endif; ?>
    <?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-primary" id="button-submit-import_step2" disabled="disabled"><?php _e('Import', CDBT); ?></button>
      </div>
    </div>
  <?php if ($wizard_step === 2 && isset($session_var[$this->domain_name]['import_filetype'])) : ?>
    <script>var delay_load_importing_sql = true;</script>
  <?php endif; ?>
  </div>
  
  <div class="step-pane<?php echo isset($session_var['import_result']) && $session_var['import_result'] ? ' bg-info' : ' bg-danger'; ?> alert" data-step="3" data-current-table="<?php if ( array_key_exists( 'operate_current_table', $session_var ) ) echo $session_var['operate_current_table']; ?>">
    <h4><?php _e('Checking the import result', CDBT); ?></h4>
    <div class="step-body">
  <?php if ($wizard_step === 3) : ?>
      <p><?php echo $session_var['result_message']; ?></p>
    <?php if ($session_var['import_result']) : ?>
      <button type="button" class="btn btn-default" id="to-view-data"><?php _e('See Table Data', CDBT); ?></button>
    <?php else : ?>
      <p><?php _e('When you are enabling the debug mode, it will be outputted error of the imported processes in the log file. If you cannot import data, please try to see the logs.', CDBT); ?></p>
      <button type="button" class="btn btn-default" id="retry-import"><?php _e('Retry Import', CDBT); ?></button>
    <?php endif; ?>
  <?php endif; ?>
    </div>
  </div>
  
<?php
  $conponent_options['splitRendering'] = 'after';
  $this->component_render('wizard', $conponent_options); // by trait `DynamicTemplate`
?>
  
  <input type="hidden" name="<?php echo $this->domain_name; ?>[import_current_step]" value="<?php if (isset($this->cdbt_sessions[$current_tab]['import_current_step']) && !empty($this->cdbt_sessions[$current_tab]['import_current_step'])) { echo $this->cdbt_sessions[$current_tab]['import_current_step']; } else { echo $conponent_options['currentStep']; } ?>">
  </form>
  
</section>
  
<section id="export" class="<?php if ('export' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons['export']['icon']; ?> text-muted"></i> <?php _e('Export Data', CDBT); ?></h4> <?php $this->during_trial( 'export_table' ); ?>
  
  <div class="well-sm">
    <p class="text-info"><?php
    if ( isset( $table_status ) && intval( $table_status['Rows'] ) > 0 ) {
      _e('In this section, you can export the current data stored in the table. Please choose the downloading file format, and specify the column names that contained data that you want to export.', CDBT);
    } else {
      _e('No exportable data in this table.', CDBT);
    } ?>
    </p>
  </div>
  
  <?php if ( isset( $table_status ) && intval( $table_status['Rows'] ) > 0 ) : ?>
  <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal" id="form-export_table">
    <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
    <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
    <input type="hidden" name="action" value="export_table">
    <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
    
    <div class="form-group">
      <label for="export-table-download_filetype" class="col-sm-3 control-label"><?php _e('Download File Type', CDBT); ?><?php echo $label_required; ?></label>
      <div class="col-sm-9">
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
      <label for="export-table-add_index_line" class="col-sm-3 control-label"><?php _e('Prepend Indices Line', CDBT); ?></label>
      <div class="col-sm-9">
        <div class="checkbox" id="export-table-add_index_line">
          <label class="checkbox-custom" data-initialize="checkbox">
            <input class="sr-only" type="checkbox" name="<?php echo $this->domain_name; ?>[add_index_line]" value="1"<?php if (isset($this->cdbt_sessions[$current_tab]['add_index_line']) && $this->cdbt_sessions[$current_tab]['add_index_line']) : ?> checked="checked"<?php endif; ?>>
            <span class="checkbox-label"><?php _e('Prepends a column&#39;s name only line as an indices of data in the table.', CDBT); ?></span>
          </label>
        </div>
        <p class="help-block"><?php _e('This setting will enable only if you choose "CSV" or "TSV" to the downloading file type.', CDBT); ?></p>
      </div>
    </div><!-- /export-table-add_index_line -->
  <?php if (function_exists('mb_list_encodings')) : ?>
    <div class="form-group">
      <label for="export-table-output_encoding" class="col-sm-3 control-label"><?php _e('Encoding', CDBT); ?></label>
      <div class="col-sm-9">
        <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="export-table-output_encoding">
          <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
            <span class="selected-label"></span>
            <span class="caret"></span>
            <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
          </button>
          <ul class="dropdown-menu" role="menu">
          <?php foreach (mb_list_encodings() as $encoding) : ?>
            <li data-value="<?php echo $encoding; ?>"<?php if (isset($this->cdbt_sessions[$current_tab]['encoding']) && $this->cdbt_sessions[$current_tab]['encoding'] === $encoding) : ?> data-selected="true"<?php else : if ('utf-8' === strtolower($encoding)) { ?> data-selected="true"<?php } endif; ?>><a href="#"><?php echo $encoding; ?></a></li>
          <?php endforeach; ?>
          </ul>
          <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[output_encoding]" readonly="readonly" aria-hidden="true" type="text"/>
        </div>
        <p class="help-block"><?php printf(__('Specifies the encoding of export file. For reference, an internal encoding of the current system is the %s.', CDBT), sprintf('<code>%s</code>', mb_internal_encoding())); ?> <?php _e('If you want to download the JSON file, it recommends to specify the "UTF-8".', CDBT); ?></p>
      </div>
    </div><!-- /export-table-output_encoding -->
  <?php endif; ?>
    <div class="form-group">
      <label for="export-table-target_columns" class="col-sm-3 control-label"><?php _e('Export Columns', CDBT); ?><?php echo $label_required; ?></label>
      <div class="col-sm-9" id="export-table-target_columns">
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
          <button type="button" class="btn btn-default btn-sm" id="switch-checkbox-export_columns"><?php _e('Toggle Checked', CDBT); ?></button>
        </p>
      </div>
    </div><!-- /export-table-target_columns -->
    <input type="hidden" name="<?php echo $this->domain_name; ?>[export_table]" value="<?php echo $target_table; ?>">
    <input type="hidden" name="file_download" id="cdbt_file_download_flag" value="false">
    <div class="form-group">
      <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-primary" id="button-submit-export_table"><?php _e('Export', CDBT); ?></button>
      </div>
    </div>
  </form>
  <?php endif; ?>
  
</section>
  
<section id="duplicate" class="<?php if ('duplicate' === $current_action) : ?>show<?php else : ?>hidden<?php endif; ?>">
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons['duplicate']['icon']; ?> text-muted"></i> <?php _e('Duplicate Table', CDBT); ?></h4> <?php $this->during_trial( 'duplicate_table' ); ?>
  
  <div class="well-sm">
    <p class="text-info">
      <?php _e('The settings other than the table name of newly the duplicated table, it is inherited the settings of the original table. If you want to modify the settings of new table, you should modify individually after the table duplication.', CDBT); ?>
    </p>
  </div>
  
  <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
    <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
    <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
    <input type="hidden" name="action" value="duplicate_table">
    <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
    
    <div class="form-group">
      <label for="duplicate-table-table_name" class="col-sm-2 control-label"><?php _e('Duplicate Table Name', CDBT); ?><?php echo $label_required; ?></label>
      <div class="col-sm-5">
        <input id="duplicate-table-table_name" name="<?php echo $this->domain_name; ?>[duplicate_table_name]" type="text" value="<?php if (isset($this->cdbt_sessions[$current_tab]['duplicate_table_name'])) echo $this->cdbt_sessions[$current_tab]['duplicate_table_name']; ?>" class="form-control" placeholder="<?php _e('Please enter a new table name', CDBT); ?>">
        <p class="help-block"><?php _e('Specifies a table name to be duplicated. The same name with original table cannot be specified.', CDBT); ?></p>
      </div>
    </div><!-- /create-table-duplicate_table_name -->
    <div class="form-group">
      <label for="duplicate-table-with_data_true" class="col-sm-2 control-label"><?php _e('Duplicate With Data', CDBT); ?><?php echo $label_required; ?></label>
      <div class="col-sm-10">
        <div class="radio">
          <label class="radio-custom" data-initialize="radio" id="duplicate-table-with_data_true">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[duplicate_with_data]" type="radio" value="true"<?php if (isset($this->cdbt_sessions[$current_tab]['duplicate_with_data'])) { if ($this->cdbt_sessions[$current_tab]['duplicate_with_data']) : ?> checked="checked"<?php endif; } else { ?> checked="checked"<?php } ?>>
            <?php _e('To be duplicated the table that contains all data stored in the original table. It would be almost complete duplication.', CDBT); ?>
          </label>
        </div>
        <div class="radio checked">
          <label class="radio-custom" data-initialize="radio" id="duplicate-table-with_data_false">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[duplicate_with_data]" type="radio" value="false"<?php if (isset($this->cdbt_sessions[$current_tab]['duplicate_with_data'])) { if (!$this->cdbt_sessions[$current_tab]['duplicate_with_data']) : ?> checked="checked"<?php endif; } ?>>
            <?php _e('To be duplicated only schema of the origin table which does not contain the data. The duplicated table will be empty table without data.', CDBT); ?>
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
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons['backup']['icon']; ?> text-muted"></i> <?php _e('Backup Table', CDBT); ?></h4> <?php $this->during_trial( 'backup_table' ); ?>
  
  <div class="well-sm">
    <p class="text-info">
      <?php _e('In this section, you can perform the backup of any tables. Sorry, we cannot provide this function yet.', CDBT); ?>
    </p>
  </div>
  
</section>
  
<?php endif; /* End of `operate_table` tab contents */ ?>
  
<?php if ($current_tab == 'operate_data') : 
  
  if (empty($target_table)) :
?>
  
  <div class="well-sm">
    <p class="text-info">
      <?php _e('Please choose the table to perform data manipulation.', CDBT); ?>
    </p>
  </div>
  
<?php else : 
    
    //var_dump( $this->cdbt_sessions );
    //var_dump( $this->cdbt_sessions[$current_tab] );
    $title_labels = [
      'view' => sprintf( __('View Data in "%s" Table', CDBT), $target_table ), 
      'entry' => sprintf( __('Entry Data to "%s" Table', CDBT), $target_table ), 
      'edit' => sprintf( __('Edit Data of "%s" Table', CDBT), $target_table ), 
    ];
    
    $current_action = empty($current_action) ? 'view' : $current_action;
    
    $shortcode_options = [];
    if ( in_array( $current_action, [ 'view', 'edit' ] ) ) {
      $shortcode_options = 'table' === $options['display_list_format'] ? [ 'enable_repeater="false"', 'footer_interface="pager"', 'ajax_load="true"' ] : [ 'enable_repeater="true"' ];
    }
?>
<section id="<?php echo $current_action; ?>" data-target_table="<?php echo $target_table; ?>">
  
  <h4 class="tab-annotation sub-description-title"><i class="<?php echo $operatable_buttons[$current_action]['icon']; ?> text-muted"></i> <?php echo $title_labels[$current_action]; ?></h4> <?php $this->during_trial( $current_action . '_data' ); ?>
  <div class="clearfix"></div>
  
  <?php echo do_shortcode( sprintf('[cdbt-%s table="%s" display_title="false" %s]', $current_action, $target_table, implode( ' ', $shortcode_options ) ) ); ?>
  
</section>

<?php endif; 
    endif; /* End of `operate_data` tab contents */ 
  endif; ?>
  
</div><!-- /.wrap -->