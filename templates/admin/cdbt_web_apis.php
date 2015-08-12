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
  'apikey_generator' => __('API Key generator', CDBT), 
  'api_requests' => __('API Requests', CDBT), 
];
$default_tab = 'hosts_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;

/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div id="page-head" name="page-head" class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php _e('CDBT Web APIs Management', CDBT); ?></h2>
  
  <div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
      <li role="presentation"<?php if ($current_tab == $tab_name) : ?> class="active"<?php endif; ?>><a href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>" role="tab"><?php echo $display_tab_title; ?></a></li>
    <?php endforeach; ?>
    </ul>
  </div>
  
<?php if ($current_tab == 'hosts_list') : 
  $_allowed_hosts = $this->get_allowed_hosts();
?>
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
    $_valid_methods = $this->check_method_permission($_host_id);
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
        <label for="register-webapi-host_name" class="col-sm-2 control-label"><?php _e('Request Origin Host', CDBT); ?><h6><span class="label label-danger"><?php _e('require', CDBT); ?></span></h6></label>
        <div class="col-sm-4">
          <input id="register-webapi-host_name" name="<?php echo $this->domain_name; ?>[host_name]" type="text" value="<?php if (isset($this_tab_vars['host_name'])) echo $this_tab_vars['host_name']; ?>" class="form-control" placeholder="<?php _e('Enter the FQDN or IP address', CDBT); ?>" required>
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
          <button type="submit" class="btn btn-primary"><?php _e('Generate API Key', CDBT); ?></button>
        </div>
      </div>
      
      <div class="pull-right">
        <a href="#"><i class="fa fa-arrow-up"></i></a>
      </div>
      <div class="clearfix"></div>
      
    </form>
  </div><!-- /.cdbt-webapis-options -->
<?php endif; ?>
  
<?php if ($current_tab == 'api_requests') : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('Here you can create the API request (URL) and test it.', CDBT); ?> <?php $this->during_trial( 'apikey_requests' ); ?>
    </p>
  </div>
  <form id="" name="" action="" method="post" class="">
    
    <?php $this->during_trial( 'api_requests' ); ?>
    
  </form>
<?php endif; ?>
  
</div><!-- /.wrap -->
