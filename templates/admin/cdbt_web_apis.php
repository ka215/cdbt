<?php
/**
 * Template : WebAPIs Management Page
 * URL: `/wp-admin/admin.php?page=cdbt_web_apis`
 *
 * @since 2.0.0
 *
 */

/**
 * Define the various localized variables for rendering
 */
$options = get_option($this->domain_name);
$tabs = [
  'hosts_list' => __('Allowed Hosts', CDBT), 
  'apikey_generator' => __('Generate API Key', CDBT), 
  'api_requests' => __('Register API Request', CDBT), 
];
$default_tab = 'hosts_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;

$_allowed_hosts = $this->get_allowed_hosts();
//$label_required = '<h6><span class="label label-danger">'. __('Required', CDBT) .'</span></h6>';
$label_required = '<span class="label label-required">'. __('Required', CDBT) .'</span>';

/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div id="page-head" name="page-head" class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php _e('CDBT WEB APIs Management', CDBT); ?></h2>
  
  <div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
      <li role="presentation"<?php if ($current_tab == $tab_name) : ?> class="active"<?php endif; ?>><a href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>" role="tab"><?php echo $display_tab_title; ?></a></li>
    <?php endforeach; ?>
    </ul>
  </div>
  
<?php if ($current_tab == 'hosts_list') : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('From the external sites will be able to access and manipulate the table that is managed by this plugin by using the API key.', CDBT); ?> <?php $this->during_trial( 'hosts_list' ); ?><br>
      <?php printf(__('Learn more %sabout currently available API request%s.', CDBT), '<a href="'. add_query_arg('tab', 'api_requests') .'">', '</a>'); ?><br>
      <?php if (!empty($_allowed_hosts)) { _e('Currently list of hosts that was allowed API request is as follows.', CDBT); } ?><br>
      <?php if (empty($_allowed_hosts)) { printf(__('The currently allowed host does not exists. Please %sregister the host%s.', CDBT), '<a href="'. add_query_arg('tab', 'apikey_generator') .'">', '</a>'); } ?>
    </p>
  </div>
  <?php 
  /**
   * Define the localized variables for tab of `apikey_list`
   */
  if (!empty($_allowed_hosts)) :
  
  $datasource = [];
  $_index = 0;
  foreach ($_allowed_hosts as $_host_id => $_host) {
    $_valid_methods = $this->check_method_permission( $_host_id );
    $datasource[] = [
      'cdbt_index_id' => $_index++, 
      'host_id' => $_host_id, 
      'host_name' => $_host['host_name'], 
      'api_key' => $_host['api_key'], 
      'description' => empty($_host['desc']) ? '-' : $_host['desc'], 
      'permission' => implode(',', $_valid_methods), 
      'generated' => $_host['generated'], 
      'operate_webapi_url' => './' . basename( esc_url(admin_url(add_query_arg([ 'tab'=>'hosts_list' ]))) ), 
    ];
  }
  $conponent_options = $this->create_scheme_datasource( 'cdbtAllowedHosts', 0, 10, 'host_list', $datasource );
  $this->component_render('repeater', $conponent_options); // by trait `DynamicTemplate`
  
  endif;
  ?>
<?php endif; ?>
  
<?php if ($current_tab == 'apikey_generator') : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('You can generate an API key for any host, and register to the plugin.', CDBT); ?> <?php $this->during_trial( 'apikey_generator' ); ?>
    </p>
  </div>
  <?php 
  /**
   * Define the localized variables for tab of `apikey_list`
   */
  
  $_session_key = 'do_'. $this->query['page'] .'_'. $this->query['tab'];
  $this_tab_vars = [];
  if (isset($this->cdbt_sessions[$_session_key])) {
    if (isset($this->cdbt_sessions[$_session_key][$this->domain_name])) 
      $this_tab_vars = $this->cdbt_sessions[$_session_key][$this->domain_name];
  }
  
  $api_methods = $this->request_methods;
  
  ?>
  <div class="cdbt-webapis-options">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="generate">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <h4 class="title"><?php _e('Allowing Hosts Registration', CDBT); ?></h4>
      
      <div class="form-group">
        <label for="register-webapi-host_name" class="col-sm-2 control-label"><?php _e('Request Origin Host', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-4">
          <input id="register-webapi-host_name" name="<?php echo $this->domain_name; ?>[host_name]" type="text" value="<?php if (isset($this_tab_vars['host_name'])) echo $this_tab_vars['host_name']; ?>" class="form-control" placeholder="<?php _e('Please enter the FQDN or IP address', CDBT); ?>" required>
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php printf(__('Please enter the FQDN (for example, the site name such as %s) or IP address of the requesting origin site.', CDBT), '<code>www.example.com</code>'); ?></p>
      </div><!-- /register-webapi-host_name -->
      <div class="form-group">
        <label for="register-webapi-description" class="col-sm-2 control-label"><?php _e('Description Host', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="register-webapi-description" name="<?php echo $this->domain_name; ?>[description]" class="form-control" rows="2" placeholder="Enter description as meno"><?php if (isset($this_tab_vars['description'])) echo esc_textarea(stripslashes_deep($this_tab_vars['description'])); ?></textarea>
          <p class="help-block"><?php _e('Please enter as like description about this host that will be displayed in the list screen.', CDBT); ?></p>
        </div>
      </div><!-- /register-webapi-description -->
      <div class="form-group">
        <label for="register-webapi-permission" class="col-sm-2 control-label"><?php _e('Request Method Permissions', CDBT); ?></label>
        <div class="col-sm-9">
        <?php foreach ($api_methods as $_i => $_method) : 
          if (isset($this_tab_vars['permission'])) {
            $_checked = isset($this_tab_vars['permission'][$_method]) && $this->strtobool($this_tab_vars['permission'][$_method]) ? ' checked="checked"' : '';
          } else {
            $_checked = ' checked="checked"'; // For default
          }
        ?>
          <div class="checkbox" id="register-webapi-permission<?php echo $_i + 1; ?>">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[permission][<?php echo $_method; ?>]" type="checkbox" value="1"<?php echo $_checked; ?>>
              <span class="checkbox-label"><?php printf(__('Allow the request of %s method.', CDBT), '<code>'. $_method .'</code>'); ?></span>
            </label>
          </div><!-- /#register-webapi-permission<?php echo $_i + 1; ?> (<?php echo $_method; ?>) -->
        <?php endforeach; ?>
        </div>
      </div>
      
      <div class="clearfix"><br></div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary"><?php _e('Register Host', CDBT); ?></button>
        </div>
      </div>
      
      <div class="pull-right">
        <a href="#"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
    </form>
  </div><!-- /.cdbt-webapis-options -->
<?php endif; ?>
  
<?php if ($current_tab == 'api_requests') : 
  
  $enable_table = $this->get_table_list( 'enable' );
  $enable_table = !is_array($enable_table) ? [] : $enable_table;
  
  $selectable_table = $options['enable_core_tables'] ? array_merge($enable_table, $this->core_tables) : $enable_table;
  sort($selectable_table);
  
?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('Here you can create the API request (URL) and test it.', CDBT); ?> <?php $this->during_trial( 'apikey_requests' ); ?>
    </p>
  </div>
  <div class="cdbt-webapis-request">
    <form class="form-horizontal">
      
      <div class="form-group">
        <label for="edit-webapi-request-allowed_host" class="col-sm-2 control-label"><?php _e('Allowed Host Name', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-10">
          <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="edit-webapi-request-allowed_host">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
              <span class="selected-label">&nbsp;</span>
              <span class="caret"></span>
              <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
            </button>
            <ul class="dropdown-menu" role="menu">
              <li data-value="" ><a href="#" class="text-muted"><?php if (!empty($_allowed_hosts)) { _e('Please choose item', CDBT); } else { _e('Allowed host is none', CDBT); } ?></a></li>
            <?php foreach($_allowed_hosts as $_host_id => $_host) : ?>
              <li data-value="<?php echo $_host_id; ?>"><a href="#"><?php echo $_host_id .': '. $_host['host_name']; ?></a></li>
            <?php endforeach; ?>
            </ul>
            <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[allowed_host]" readonly="readonly" aria-hidden="true" type="text"/>
          </div>
          <p class="help-block"></p>
        </div>
      </div><!-- /edit-webapi-request-allowed_host -->
      <div class="form-group">
        <label for="edit-webapi-request-target_table" class="col-sm-2 control-label"><?php _e('Target Table', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-10">
          <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="edit-webapi-request-target_table">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
              <span class="selected-label">&nbsp;</span>
              <span class="caret"></span>
              <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
            </button>
            <ul class="dropdown-menu" role="menu">
              <li data-value="" ><a href="#" class="text-muted"><?php if (!empty($selectable_table)) { _e('Please choose item', CDBT); } else { _e('Selectable table is none', CDBT); } ?></a></li>
            <?php foreach($selectable_table as $_table) : ?>
              <li data-value="<?php echo $_table; ?>"><a href="#"><?php echo $_table; ?></a></li>
            <?php endforeach; ?>
            </ul>
            <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[target_table]" readonly="readonly" aria-hidden="true" type="text"/>
          </div>
          <p class="help-block"></p>
        </div>
      </div><!-- /edit-webapi-request-target_table -->
      <div class="form-group">
        <label for="edit-webapi-request-method" class="col-sm-2 control-label"><?php _e('Request Method', CDBT); ?><?php echo $label_required; ?></label>
        <div class="col-sm-10">
          <div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist" id="edit-webapi-request-method">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
              <span class="selected-label">&nbsp;</span>
              <span class="caret"></span>
              <span class="sr-only"><?php esc_attr_e('Toggle Dropdown'); ?></span>
            </button>
            <ul class="dropdown-menu" role="menu">
              <li data-value="" ><a href="#" class="text-muted"><?php _e('Please choose item', CDBT); ?></a></li>
            <?php foreach($this->request_methods as $_method) : ?>
              <li data-value="<?php echo $_method; ?>"><a href="#"><?php echo $_method; ?></a></li>
            <?php endforeach; ?>
            </ul>
            <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[method]" readonly="readonly" aria-hidden="true" type="text"/>
          </div>
          <p class="help-block"></p>
        </div>
      </div><!-- /edit-webapi-request-method -->
      <input type="hidden" id="current_host_apikey" value="" disabled="disabled">
      <input type="hidden" id="self_host_root" value="<?php echo site_url(); ?>" disabled="disabled">
      <input type="hidden" id="preview_nonce" value="<?php echo wp_create_nonce('cdbt_api_ownhost'); ?>" disabled="disabled">
      <input type="hidden" id="preview_uri" value="" disabled="disabled">
      
      <div class="clearfix"><br></div>
      <h4 class="title" id="api-request-queries"><i class="fa fa-cogs text-muted"></i> <?php _e('Request Queries Definition', CDBT); ?></h4>
      
      <div class="form-group switching-item search-key">
        <label for="edit-webapi-request-search_key" class="col-sm-2 control-label"><?php _e('Search Keywords Specification', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-webapi-request-search_key" name="<?php echo $this->domain_name; ?>[search_key]" type="text" value="<?php if (isset($this_tab_vars['search_key'])) echo $this_tab_vars['search_key']; ?>" class="form-control" placeholder="keyword1,keyword2,...">
          <p class="help-block"><?php _e('If you want to narrow down the data by keyword, please specify the keywords of comma-separator. For example, "keyword1,keyword2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-webapi-request-search_key -->
      <div class="form-group switching-item target-columns">
        <label for="edit-webapi-request-target_columns" class="col-sm-2 control-label"><?php _e('Target Columns Specification', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-webapi-request-target_columns" name="<?php echo $this->domain_name; ?>[target_columns]" type="text" value="<?php if (isset($this_tab_vars['target_columns'])) echo $this_tab_vars['target_columns']; ?>" class="form-control" placeholder="col1,col2,col3,...">
          <p class="help-block"><?php _e('Please enter the column names of comma-separator if you want to get the data from specific columns. For example, "col1,col2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-webapi-request-target_columns -->
      <div class="form-group switching-item conditions">
        <label for="edit-webapi-request-conditions" class="col-sm-2 control-label"><?php _e('Where Conditions', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-webapi-request-conditions" name="<?php echo $this->domain_name; ?>[conditions]" type="text" value="<?php if (isset($this_tab_vars['conditions'])) echo $this_tab_vars['conditions']; ?>" class="form-control" placeholder="col1:value1,col2:value2,...">
          <p class="help-block"><?php _e('Please specify the conditions of the data to retrieve as the hash value in the pair of column name and value. For example, "col1:value1,col2:value2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-webapi-request-conditions -->
      <div class="form-group switching-item upsert_data">
        <label for="edit-webapi-request-upsert_data" class="col-sm-2 control-label"><?php _e('Upsert Data', CDBT); ?></label>
        <div class="col-sm-9">
          <input id="edit-webapi-request-upsert_data" name="<?php echo $this->domain_name; ?>[upsert_data]" type="text" value="<?php if (isset($this_tab_vars['upsert_data'])) echo $this_tab_vars['upsert_data']; ?>" class="form-control" placeholder="col1:value1,col2:value2,...">
          <p class="help-block"><?php _e('Please specify the data insertion or updation as the hash values in the pairs of column name and value. For example, "col1:value1,col2:value2,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-webapi-request-upsert_data -->
      <div class="form-group switching-item pk_value">
        <label for="edit-webapi-request-pk_value" class="col-sm-2 control-label"><?php _e('Primary Key Value', CDBT); ?></label>
        <div class="col-sm-3">
          <input id="edit-webapi-request-pk_value" name="<?php echo $this->domain_name; ?>[pk_value]" type="text" value="<?php if (isset($this_tab_vars['pk_value'])) echo $this_tab_vars['pk_value']; ?>" class="form-control" placeholder="primary key value">
        </div>
        <p class="help-block col-sm-offset-2 col-sm-9"><?php _e('Please specify the value of the primary key of the operation target data.', CDBT); /**/ ?></p>
      </div><!-- /edit-webapi-request-pk_value -->
      <div class="form-group switching-item orders">
        <label for="edit-webapi-request-orders" class="col-sm-2 control-label"><?php _e('Orders', CDBT); ?></label>
        <div class="col-sm-8">
          <input id="edit-webapi-request-orders" name="<?php echo $this->domain_name; ?>[orders]" type="text" value="<?php if (isset($this_tab_vars['orders'])) echo $this_tab_vars['orders']; ?>" class="form-control" placeholder="col1:asc,col2:desc,...">
          <p class="help-block"><?php _e('Please specify the sort order of the retrieve data at the hash value in the pair of the column name and the order. For example, "col1:asc,col2:desc,..." so on.', CDBT); ?></p>
        </div>
      </div><!-- /edit-webapi-request-orders -->
      <div class="form-group switching-item limit">
        <label for="edit-webapi-request-limit" class="col-sm-2 control-label"><?php _e('Limit of Getting Data', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="edit-webapi-request-limit">
            <input type="text" name="<?php echo $this->domain_name; ?>[limit]" value="<?php if (isset($this_tab_vars['limit'])) echo intval($this_tab_vars['limit']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('This specifies the maximum number of retrieved data.', CDBT); ?></p>
        </div>
      </div><!-- /edit-webapi-request-limit -->
      <div class="form-group switching-item offset">
        <label for="edit-webapi-request-offset" class="col-sm-2 control-label"><?php _e('Starting Offset', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="edit-webapi-request-offset">
            <input type="text" name="<?php echo $this->domain_name; ?>[offset]" value="<?php if (isset($this_tab_vars['offset'])) echo intval($this_tab_vars['offset']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('This specifies the starting offset number to retrieve data.', CDBT); ?></p>
        </div>
      </div><!-- /edit-webapi-request-offset -->
      
      <div class="clearfix"><br></div>
      <h4 class="title" id="preview-request"><i class="fa fa-link text-muted"></i> <?php _e('Generated API Request', CDBT); ?></h4>
      
      <div class="form-group">
        <label for="edit-webapi-request-generate_uri" class="col-sm-2 control-label"><?php _e('Generated URI', CDBT); ?></label>
        <div class="col-sm-9">
          <textarea id="edit-webapi-request-generate_uri" name="<?php echo $this->domain_name; ?>[generate_uri]" class="form-control" rows="5" readonly><?php if (isset($this_tab_vars['generate_uri'])) echo esc_textarea(stripslashes_deep($this_tab_vars['generate_uri'])); ?></textarea>
        </div>
      </div><!-- /edit-webapi-request-generate_uri -->
      
      <div class="clearfix"><br></div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="button" class="btn btn-primary" id="webapi-preview" disabled="disabled"><?php _e('Preview Request', CDBT); ?></button>
        </div>
      </div>
      
    </form>
  </div><!-- /.cdbt-webapis-request -->
<?php endif; ?>
  
</div><!-- /.wrap -->
