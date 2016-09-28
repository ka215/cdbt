<?php
/**
 * Template : Plugin Option Settings Page
 * URL: `/wp-admin/admin.php?page=cdbt_options`
 *
 * @since 2.0.0
 * @since 2.0.7 Added new options
 * @since 2.0.8 Added new tab
 * @since 2.1.31 Updated
 * @since 2.1.33 Updated
 *
 */
$options = get_option( $this->domain_name );
$tabs = [
  'general_setting' => __('General Settings', CDBT), 
  'messages' => __('Messages', CDBT), 
  'debug' => __('Debug', CDBT), 
  'addons' => __('Addons', CDBT), 
];
// Filter the tabs on page of plugin options
// 
// @since 2.1.33
$tabs = apply_filters( 'cdbt_plugin_options_tab', $tabs );
$default_tab = 'general_setting';
$current_tab = isset( $this->query['tab'] ) && ! empty( $this->query['tab'] ) ? $this->query['tab'] : $default_tab;
if ( ! $options['debug_mode'] ) {
  unset( $tabs['debug'] );
}

$default_action = 'update';

$fields_define = [];

//global $wpdb;
//var_dump($this->wpdb->prefix);
/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php _e('CDBT Plugin Options', CDBT); ?></h2>
  
  <div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
      <li role="presentation"<?php if ($current_tab == $tab_name) : ?> class="active"<?php endif; ?>><a href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>" role="tab"><?php echo $display_tab_title; ?></a></li>
    <?php endforeach; ?>
    </ul>
    
    <div class="tab-content">
      <div role="tabpanel" class="tab-pane active">
<?php if ($current_tab == 'general_setting') : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('In this configuration page, you can edit the common settings that affect the overall operation of the "Custom DataBase Tables" plugin.', CDBT); ?><br>
    </p>
  </div>
  
  <div class="cdbt-general-options">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="<?php echo $default_action; ?>">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <h4 class="title"><i class="fa fa-gears text-muted"></i> <?php _e('General Plugin Settings', CDBT); ?></h4>

<?php $this->dynamic_field( [ 'elementName'=>'cleaning_options', 'elementId'=>'option-item-1', 'elementLabel'=>__('Cleanup Setting', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['cleaning_options'] ) && $this->strtobool( $options['cleaning_options'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>__('Optimizes such as to remove a table setting that does not exist in the database whenever you save the plugin settings if checked.', CDBT) ] ] ); ?>
<?php $this->dynamic_field( [ 'elementName'=>'uninstall_options', 'elementId'=>'option-item-4', 'elementLabel'=>__('Uninstall Setting', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['uninstall_options'] ) && $this->strtobool( $options['uninstall_options'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>__('When you uninstall the plugin, it removes all of the configuration settings related to the plugin. However, your created table will not be deleted.', CDBT) ] ] ); ?>
<?php $this->dynamic_field( [ 'elementName'=>'resume_options', 'elementId'=>'option-item-7', 'elementLabel'=>__('Restore Table', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['resume_options'] ) && $this->strtobool( $options['resume_options'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>__('If there is a plugin settings in the past, it restores the table setting from there. However, if a table does not exist when you are about to restore, it will not be restored.', CDBT) ] ] ); ?>
<?php $this->dynamic_field( [ 'elementName'=>'enable_core_tables', 'elementId'=>'option-item-10', 'elementLabel'=>__('Manage WP Core Tables', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['enable_core_tables'] ) && $this->strtobool( $options['enable_core_tables'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>__('Capable of managing the core tables built in the WordPress if checked. Then you can do operation such as the data browsing, data registration, data editing, data exporting and data importing.', CDBT) ] ] ); ?>
<?php $this->dynamic_field( [ 'elementName'=>'display_datetime_format', 'elementId'=>'option-item-11', 'elementLabel'=>__('Datetime Format', CDBT), 'elementType'=>'text', 'fieldSize'=>4, 
  'defaultValue'=>isset( $options['display_datetime_format'] ) && ! empty( $options['display_datetime_format'] ) ? esc_attr( $options['display_datetime_format'] ) : '', 
  'helperText'=>__('Defines the display format of datetime type data at the time of displaying in the plugin. By default, it will inherit the datetime format of the WordPress general settings.', CDBT) ] ); ?>
<?php 
  $_positions = [
    'top' => __( 'Top (After &quot;dashboard&quot;): 3', CDBT ), 
    'default' => __( 'Default (Before &quot;appearance&quot;): 55', CDBT ), 
    'middle' => __( 'Middle (After &quot;tools&quot;): 77', CDBT ), 
    'bottom' => __( 'Bottom (After &quot;setting&quot;): 85', CDBT ), 
  ];
  $_default_value = '';
  if ( array_key_exists( 'plugin_menu_position', $options ) ) {
    if ( array_key_exists( $options['plugin_menu_position'], $_positions ) ) {
      $_current_pos = $_positions[$options['plugin_menu_position']];
    } else {
      $_current_pos = intval( $options['plugin_menu_position'] ) > 0 ? intval( $options['plugin_menu_position'] ) : 'default';
      if ( is_int( $_current_pos ) ) 
        $_default_value = ' value="'. $_current_pos .'"';
    }
  } else {
    $_current_pos = 'bottom';
  }
  $this->dynamic_field( [ 'elementName'=>'plugin_menu_position', 'elementId'=>'option-item-12', 'elementLabel'=>__('Plugin Menu Position', CDBT), 'elementType'=>'combobox', 'fieldSize'=>4, 
    'defaultValue'=>$_current_pos, 'selectableList'=>$_positions, 'placeholder'=>__('Enter position number', CDBT), 
    'helperText'=>__('Specifies the display position of plugin menu on the WordPress admin panel.', CDBT) ] ); ?> 
<?php $this->dynamic_field( [ 'elementName'=>'notices_via_modal', 'elementId'=>'option-item-13', 'elementLabel'=>__('Modal Dialog Notice', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['notices_via_modal'] ) && $this->strtobool( $options['notices_via_modal'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>__('All notifications (on the management console) from this plugin will be displayed via a modal dialog if checked.', CDBT) ] ] ); ?>
<?php /* $this->dynamic_field( [ 'elementName'=>'display_list_format', 'elementId'=>'option-item-14', 'elementLabel'=>__('Data List Format', CDBT), 'elementType'=>'radio', 'horizontalList'=>true, 
    'defaultValue'=>isset( $options['display_list_format'] ) && in_array( $options['display_list_format'], ['table', 'repeater'] ) ? $options['display_list_format'] : 'table', 
    'selectableList'=>[ 'table'=>__('Table Layout (Recommended)', CDBT), 'repeater'=>__('Repeater Layout (conventional layout)', CDBT) ], 
    'helperText'=>__( 'You can choose which the table layout or the repeater layout, as the display format when listed data on the tables management.', CDBT ), 
    'elementExtras' => [ 'status' => 'under-test' ] ] ); */ ?>
      <div class="form-group">
        <label class="col-sm-2 control-label" for="option-item-14"><?php _e('Data List Format', CDBT); ?> <?php $this->during_trial( 'display_list_format' ); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="option-item-14">
            <label class="radio-custom radio-inline" data-initialize="radio" id="option-item-14-table">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_list_format]" type="radio" value="table"<?php checked( 'table', $options['display_list_format'] ); ?>> <?php _e('Table Layout (Recommended)', CDBT); ?>
            </label>
            <label class="radio-custom radio-inline" data-initialize="radio" id="option-item-14-repeater">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[display_list_format]" type="radio" value="repeater"<?php checked( 'repeater', $options['display_list_format'] ); ?>> <?php _e('Repeater Layout (conventional layout)', CDBT); ?>
            </label>
            <p class="help-block"><?php _e('You can choose which the table layout or the repeater layout, as the display format when listed data on the tables management.', CDBT); ?></p>
          </div>
        </div>
      </div><!-- /#option-item-14 -->
<?php $this->dynamic_field( [ 'elementName'=>'debug_mode', 'elementId'=>'option-item-15', 'elementLabel'=>__('Debug Mode', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['debug_mode'] ) && $this->strtobool( $options['debug_mode'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>__('If you enable the debug mode, the occurred errors in the plugin will be outputted as a log file. Please see the log as to if you investigate any incidents.', CDBT) ] ] ); ?>
      
      <div class="clearfix"><br></div>
      <h4 class="title"><i class="fa fa-gears text-muted"></i> <?php _e('Table Creation Initial Definitions', CDBT); ?></h4>
      
<?php $this->dynamic_field( [ 'elementName'=>'use_wp_prefix', 'elementId'=>'option-item-21', 'elementLabel'=>__('Table Prefix', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['use_wp_prefix'] ) && $this->strtobool( $options['use_wp_prefix'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>sprintf( __('Prepends a %s as table prefix defined at the WordPress configuration (wp-config.php) when you create a newly table if checked.', CDBT), '<code>'. $this->wpdb->prefix .'</code>' ) ], 
  'helperText'=> __('Note: You can individually change this setting whenever you create a table.', CDBT) ] ); ?>
<?php /*
  $_default_charset = '';
  if ( isset( $options['charset'] ) && ! empty( $options['charset'] ) ) {
    foreach ( $this->db_charsets as $_i => $_val ) {
      if ( $options['charset'] === $_val ) {
        $_default_charset = $_i;
        break;
      }
    }
  }
  $this->dynamic_field( [ 'elementName'=>'charset', 'elementId'=>'option-item-22', 'elementLabel'=>__('Table Charset', CDBT), 'elementType'=>'combobox', 'fieldSize'=>3, 
    'defaultValue'=>$this->db_charsets[$_default_charset], 'selectableList'=>$this->db_charsets, 'placeholder'=>__('Enter the table charset', CDBT), 
    'helperText'=>__('To be set as default charset whenever you create a newly table if checked.', CDBT) . ' <a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a>' ] ); */ ?>
      <div class="form-group">
        <label for="option-item-22" class="col-sm-2 control-label"><?php _e('Table Charset', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="option-item-22">
            <input type="text" name="<?php echo $this->domain_name; ?>[charset]" value="<?php esc_attr_e($options['charset']); ?>" class="form-control" placeholder="<?php _e('Enter the table charset', CDBT); ?>">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_charsets as $i => $charset) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $charset; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php _e('This value will be set as default charset whenever you create a newly table.', CDBT); ?> <a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a></p>
        </div>
      </div><!-- /option-item-22 -->
      <div class="form-group">
        <label for="option-item-23" class="col-sm-2 control-label"><?php _e('Localizing Timezone', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-4 pull-left" data-initialize="combobox" id="option-item-23">
            <input type="text" name="<?php echo $this->domain_name; ?>[timezone]" value="<?php esc_attr_e($options['timezone']); ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->timezone_identifiers as $i => $timezone) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $timezone; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block inline-help"> <?php _e('Current timezone of the MySQL database', CDBT); ?>: <code><?php echo apply_filters( 'sanitize_option_timezone_string', $options['timezone'], 'timezone_string'); ?></code></p>
          <div class="clearfix">
            <p class="help-block"><?php _e('This value will be set as default timezone whenever you create a newly table. Also the set timezone will be used for localizing of the datetime type data.', CDBT); ?></p>
          </div>
        </div>
      </div><!-- /option-item-23 -->
      <div class="form-group">
        <label for="option-item-24" class="col-sm-2 control-label"><?php _e('Database Engine', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="option-item-24">
            <input type="text" name="<?php echo $this->domain_name; ?>[default_db_engine]" value="<?php esc_attr_e($options['default_db_engine']); ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_engines as $i => $db_engine) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $db_engine; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php _e('This value will be set as default database engine whenever you create a newly table.', CDBT); ?><a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a></p>
        </div>
      </div><!-- /option-item-24 -->
      <div class="form-group">
        <label for="option-item-25" class="col-sm-2 control-label"><?php _e('Maximum display data per page', CDBT); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="option-item-25">
            <input type="text" name="<?php echo $this->domain_name; ?>[default_per_records]" value="<?php echo intval($options['default_per_records']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only">Increase</span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only">Decrease</span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('This value will be set as the number of displayed data per page whenever you create a newly table.', CDBT); ?><a href="#foot-note-1" class="note-link"><i class="fa fa-info-circle"></i></a></p>
        </div>
      </div><!-- /option-item-25 -->
      
      <div class="col-sm-offset-2 col-sm-10">
        <ul id="foot-note-1" class="foot-note">
          <li><i class="fa fa-info-circle"></i> <?php _e('Those values are not applied to the already created table. Please modify the table settings individually if you want to change.', CDBT); ?></li>
        </ul>
      </div>
      
      <div class="clearfix"><br></div>
      <h4 class="title"><i class="fa fa-gears text-muted"></i> <?php _e('Advanced Plugin Settings', CDBT); ?></h4>
      
<?php $this->dynamic_field( [ 'elementName'=>'allow_rendering_shortcodes', 'elementId'=>'option-item-31', 'elementLabel'=>__('Page to Apply Shortcode', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['allow_rendering_shortcodes'] ) && $this->strtobool( $options['allow_rendering_shortcodes'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>__('If checked, the page that is rendered the shortcodes restrict to a singular post only.', CDBT) ], 
  'helperText'=> __('Note: There is a possibility that degrade your web page performance by rendering the all shortcodes in an archive page if unchecked.', CDBT) ] ); ?>
      <div class="form-group">
        <label class="col-sm-2 control-label" for="option-item-32"><?php _e('Loading Resources', CDBT); ?></label>
        <div class="col-sm-10">
          <p style="margin-top: 6px; font-size: 14px;"><?php _e('You can switch loading/unloading of individual resources as needed. Please try to change this settings if the specific resource is in conflict with the resource of the theme or other plugin.', CDBT); ?> <?php $this->during_trial( 'include_assets' ); ?></p>
          <table class="table table-bordered col-sm-10" id="option-item-32">
            <thead>
              <tr>
                <th class="text-center"><?php _e('Administration Screen <br>(when managed this plugin only)', CDBT); ?></th>
                <th class="text-center"><?php _e('Front-end Screen <br>(when rendered the shortcode only)', CDBT); ?></th>
              </tr>
            </thead>
            <tbody>
              <td><ul>
              <?php $_admin_resources = [ 'jQuery', 'Underscore.js', 'Bootstrap', 'Fuel UX', 'Kinetic', 'Clipboard' ]; ?>
              <?php foreach ( $_admin_resources as $_resource_name ) : 
                $_resource_slug = str_replace( [ ' ', '.', '-' ], '_', strtolower( $_resource_name ) );
                $_checked_cond = isset( $options['include_assets']['admin_'. $_resource_slug ] ) ? $options['include_assets']['admin_'. $_resource_slug ] : '1';
                $_disp_resource = '<strong class="text-info">'. $_resource_name .' (v'. $this->contribute_extends[$_resource_name]['version'] .')</strong>'; ?>
                <li><div class="checkbox">
                  <label class="checkbox-custom" data-initialize="checkbox">
                    <input class="sr-only" name="<?php echo $this->domain_name; ?>[include_assets][admin_<?php echo $_resource_slug; ?>]" type="checkbox" value="1" <?php checked('1', $_checked_cond ); ?>>
                    <span class="checkbox-label"><?php $_resource = printf( __('the built-in %s in plugin', CDBT), $_disp_resource ); ?></span>
                  </label>
                </div></li>
              <?php endforeach; ?>
              </ul></td>
              <td><ul>
              <?php $_main_resources = [ 'jQuery', 'Underscore.js', 'Bootstrap', 'Fuel UX', 'Kinetic', 'Clipboard' ]; ?>
              <?php foreach ( $_main_resources as $_resource_name ) : 
                $_resource_slug = str_replace( [ ' ', '.', '-' ], '_', strtolower( $_resource_name ) );
                $_checked_cond = isset( $options['include_assets']['main_'. $_resource_slug ] ) ? $options['include_assets']['main_'. $_resource_slug ] : '1';
                $_disp_resource = '<strong class="text-info">'. $_resource_name .' (v'. $this->contribute_extends[$_resource_name]['version'] .')</strong>'; ?>
                <li><div class="checkbox">
                  <label class="checkbox-custom" data-initialize="checkbox">
                    <input class="sr-only" name="<?php echo $this->domain_name; ?>[include_assets][main_<?php echo $_resource_slug; ?>]" type="checkbox" value="1" <?php checked('1', $_checked_cond ); ?>>
                    <span class="checkbox-label"><?php printf( __('the built-in %s in plugin', CDBT), $_disp_resource ); ?></span>
                  </label>
                </div></li>
              <?php endforeach; ?>
              </ul></td>
            </tbody>
          </table>
          <div class="clearfix"></div>
          <p class="help-block" style="margin-top: 0;"><?php _e('Note: If disabled the loading of resource, you should be loaded those resources individually. Also it does not recommend of disabling resources of the administration screen.', CDBT); ?></p>
        </div>
      </div><!-- /option-item-32 -->
<?php $this->dynamic_field( [ 'elementName'=>'prevent_duplicate_sending', 'elementId'=>'option-item-34', 'elementLabel'=>__('Use Onetime Token', CDBT), 'elementType'=>'checkbox', 
  'defaultValue'=>isset( $options['prevent_duplicate_sending'] ) && $this->strtobool( $options['prevent_duplicate_sending'] ) ? '1' : '0', 
  'selectableList'=>[ '1'=>__('Takes with issuing a onetime token in the cookie whenever registered data.', CDBT) ], 
  'helperText'=> __('Note: This option is for preventing a duplicate data registration, an illegal accessed or an invalid data registration.', CDBT) ] ); ?>
      
      
      <div class="clearfix"><br></div>
      <div class="form-group">
        <div class="col-sm-10 col-sm-offset-2">
          <input type="submit" name="submit" id="submit" class="btn btn-primary pull-left" value="<?php _e('Save Changes', CDBT); ?>">
          <input type="button" name="initialize" id="initialize" class="btn btn-default" value="<?php _e('Initialize Options', CDBT); ?>" style="margin-left: 1.5em;">
        </div>
      </div>
    </form>
  </div>
<?php elseif ($current_tab == 'messages') : 
  // Filter translate text to extend
  //
  // @since 2.0.9
  $override_messages = apply_filters( 'cdbt_override_translate_text', $this->override_messages ); ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('In this section, you can overwrite of the own custom messages to any notification messages displayed at this plugin.', CDBT); ?> <?php $this->during_trial( 'override_messages' ); ?>
    </p>
  </div>
  
  <div class="messages-section">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="override">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <div class="row" style="margin-bottom: .5em;">
        <div class="col-md-5"><span class="label label-default" style="margin-left: 1em;"><?php _e('Original Text', CDBT); ?></span></div>
        <div class="col-md-7"><span class="label label-default"><?php _e('Current Translating Text', CDBT); ?></span></div>
      </div>
<?php foreach ( $override_messages as $_text ) : $msg_hash = $this->create_hash( $_text ); ?>
      <div class="form-group row">
        <label class="col-md-5 control-label" for="override-messages-<?php echo $msg_hash; ?>"><p class="text-left" style="margin-left: 1em;">
          <?php echo $_text; ?>
        </p></label>
        <div class="col-md-7"><textarea class="form-control" id="override-messages-<?php echo $msg_hash; ?>" name="<?php echo $this->domain_name; ?>[override_messages][<?php echo $msg_hash; ?>]" rows="2" placeholder="<?php esc_attr_e( $_text ); ?>"><?php if ( isset( $options['override_messages'][$msg_hash] ) ) {
          echo esc_textarea( $this->cdbt_strarc( $options['override_messages'][$msg_hash], 'decode' ) );
        } else {
          echo esc_textarea( __( $_text, CDBT ) );
        } ?></textarea></div>
        <?php if ( ! isset( $options['override_messages'][$msg_hash] ) ) : ?><div class="hide" id="override-messages-<?php echo $msg_hash; ?>-default"><?php echo esc_textarea( __( $_text, CDBT ) ); ?></div><?php endif; ?>
      </div>
<?php endforeach; ?>
      
      <div class="form-group">
        <div class="col-sm-10">
          <input type="button" name="override" id="override-messages" class="btn btn-primary pull-left" value="<?php _e('Save Changes', CDBT); ?>">
          <input type="button" name="format" id="format-messages" class="btn btn-default" value="<?php _e('Initialize Messages', CDBT); ?>" style="margin-left: 1.5em;">
        </div>
      </div>
      
    </form>
  </div>
<?php elseif ( $current_tab === 'debug' ) : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('In this section, you can check the logs of various processes executed in the "Custom DataBase Tables" plugin.', CDBT); ?><br>
      <?php _e('Those logs maybe help you as a debugging log to follow the flow of the processing if it occurs something trouble.', CDBT); ?>
    </p>
  </div>
  
  <div class="debug-section">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="debug_log">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      
      <div class="form-group">
        <div class="col-sm-11">
          <textarea name="<?php echo $this->domain_name; ?>[debug-log]" id="debug-log-viewer" rows="20" class="form-control" readonly><?php echo file_get_contents( $this->log_distination_path ); ?></textarea>
        </div>
      </div>
      
      <div class="form-group">
        <div class="checkbox highlight col-sm-11" id="debug-log-option">
          <input type="submit" name="submit" id="debug-submit" class="btn btn-primary pull-left" value="<?php _e('Clear Logs', CDBT); ?>">
          <label class="checkbox-custom highlight" data-initialize="checkbox">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[debug_log_option]" type="checkbox" value="1">
            <span class="checkbox-label"><?php _e('Removes the current logs after backup of the log file.', CDBT); ?></span>
          </label>
        </div>
        <p class="help-block col-sm-offset-1 col-sm-11">
          <?php printf( __('Note: All backup files are stored to the directory of %s.', CDBT), '<code>'. $this->plugin_dir .'backup/</code>' ); ?>
        </p>
      </div>
      
    </form>
  </div>
<?php elseif ( $current_tab === 'addons' ) : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('By installing these add-on packs, this plugin will further evolve, and extend various functionality.', CDBT); ?><br>
    </p>
  </div>
  
  <div class="addons-section">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" id="cdbt-addons" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="<?php echo $this->domain_name; ?>[addon_class]" id="cdbt-addon-classname" value="">
      <input type="hidden" name="<?php echo $this->domain_name; ?>[dist_uri]" id="cdbt-addon-disturi" value="">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>

<?php
  $official_site = 'https://ka2.org';
  $response = wp_remote_get( esc_url_raw( $official_site . '/wp-json/cdbt/v2.1/addons/' ) );
  $response_code = wp_remote_retrieve_response_code( $response );
  if ( $response_code == 200 ) {
    $all_addons = json_decode( wp_remote_retrieve_body( $response ) );
    if ( empty( $all_addons ) ) {
      printf( '<div class="addon-message">%s</div>', __('Sorry, the providable addon is nothing yet.', CDBT) );
    } else {
?>
  <ul class="list-inline addons-list">
  <?php foreach( $all_addons as $_addon => $_meta ) : 
    if ( is_array( $this->extend ) && array_key_exists( $_addon, $this->extend ) ) {
      $action_name = 'deactivate';
      $btn_class = 'default';
      $btn_label = __('Deactivate', CDBT);
    } elseif ( in_array( $_meta->basename, $this->installed_addons ) ) {
      $action_name = 'activate';
      $btn_class = 'primary';
      $btn_label = __('Activate', CDBT);
    } else {
      $action_name = 'install';
      $btn_class = 'default';
      $btn_label = __('Install', CDBT);
    }
    $_btn = sprintf( 
      '<button type="submit" form="cdbt-addons" name="action" value="%s" class="btn btn-%s pull-left" data-class-name="%s" data-dist-uri="%s">%s</button>', 
      $action_name, 
      $btn_class, 
      $_meta->classname, 
      $_meta->distribution_url, 
      $btn_label 
    );
  ?>
    <li><div class="thumbnail">
      <img src="<?= $this->plugin_url . $this->plugin_assets_dir ?>/images/cdbt-noimage_reversal.png" alt="<?= $_meta->label ?>">
      <div class="caption">
        <h3 class="addon-title"><?= $_meta->label ?></h3>
        <p class="addon-description"><?= $_meta->description ?></p>
        <p class="addon-version"><label><?php _e('Latest Ver.', CDBT) ?></label> <?= $_meta->version ?></p>
        <p class="addon-author"><label><?php _e('Produced by', CDBT) ?></label> <a href="<?= $_meta->author_url ?>"><?= $_meta->author ?></a></p>
        <div class="addon-footer"><div class="addon-btn"><?= $_btn ?></div><div class="addon-amount"><?= intval( $_meta->amount ) == 0 ? __('FREE', CDBT) : '$ ' . $_meta->amount ?></div></div>
      </div>
    </div></li>
  <?php endforeach; ?>
  </ul><!-- /.addons-list -->
<?php
    }
  } else {
    //$api_response = json_decode( wp_remote_retrieve_body( $response ) );
//var_dump( wp_remote_retrieve_body( $response ) );
    printf( '<div class="addon-message error">%s (%d)</p>', $api_response->message, $api_response->data->status );
  }
?>
    </form>
  </div><!-- /.addons-section -->
<?php else : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php do_action( 'cdbt_get_admin_tab_info', $current_tab ); ?>
    </p>
  </div>
  
  <div class="addons-section">
    <?php do_action( 'cdbt_get_admin_tab_body', $current_tab ); ?>
  </div>
<?php endif; ?>
  
      </div><!-- /.tab-pane -->
    </div><!-- /.tab-content -->
  </div>
</div><!-- /.wrap -->