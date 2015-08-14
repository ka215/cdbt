<?php
/**
 * Template : Plugin Option Settings Page
 * URL: `/wp-admin/admin.php?page=cdbt_options`
 *
 * @since 2.0.0
 *
 */
$options = get_option($this->domain_name);
$tabs = [
  'general_setting' => esc_html__('General Setting', CDBT), 
  'debug' => esc_html__('Debug', CDBT), 
];
$default_tab = 'general_setting';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;
if (!$options['debug_mode']) {
  unset($tabs['debug']);
  $current_tab = $default_tab;
}


$default_action = 'update';

/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php esc_html_e('CDBT Plugin Options', $this->domain_name); ?></h2>
  
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
      <?php _e('In this configuration page, you can edit the common settings that affect the overall operation of the "Custom DataBase Tables" plugin.', $this->domain_name); ?><br>
    </p>
  </div>
  
  <div class="cdbt-general-options">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>" class="form-horizontal">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="active_tab" value="<?php echo $current_tab; ?>">
      <input type="hidden" name="action" value="<?php echo $default_action; ?>">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      <h4 class="title"><?php _e('Plugin Setting', $this->domain_name); ?></h4>
      <div class="form-group">
        <label class="col-sm-2 control-label"><?php _e('Cleaning option', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="option-item-1">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[cleaning_options]" type="checkbox" value="1" <?php checked('1', $options['cleaning_options']); ?>>
              <span class="checkbox-label"><?php _e('When you save common settings, such as deleting the table settings that do not exist in the database, to perform the cleaning of the set value.', $this->domain_name); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /option-item-1 -->
      <div class="form-group">
        <label class="col-sm-2 control-label"><?php _e('Uninstall setting', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="option-item-4">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[uninstall_options]" type="checkbox" value="1" <?php checked('1', $options['uninstall_options']); ?>>
              <span class="checkbox-label"><?php _e('When you will uninstall this plugin, remove all of the configuration information related to plugin (but your created table is not deleted).', $this->domain_name); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /option-item-4 -->
      <div class="form-group">
        <label class="col-sm-2 control-label"><?php _e('Manageable table restoration', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="option-item-7">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[resume_options]" type="checkbox" value="1" <?php checked('1', $options['resume_options']); ?>>
              <span class="checkbox-label"><?php _e('It will reconfigure tables from the past plugin configuration. However, the table that does not exist in the database at the time of restoration can not be recovered.', $this->domain_name); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /option-item-7 -->
      <div class="form-group">
        <label class="col-sm-2 control-label"><?php _e('Manage WordPress core table', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="option-item-10">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[enable_core_tables]" type="checkbox" value="1" <?php checked('1', $options['enable_core_tables']); ?>>
              <span class="checkbox-label"><?php _e('You make manageable of the WordPress core tables. Then, you will allow the table management, data browsing, registration, editing, and the import or export.', $this->domain_name); ?> <?php $this->during_trial( 'enable_core_tables' ); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /option-item-10 -->
      <div class="form-group">
        <label class="col-sm-2 control-label"><?php _e('Display format of datetime', $this->domain_name); ?></label>
        <div class="col-sm-4">
          <input type="text" id="option-item-11" name="<?php echo $this->domain_name; ?>[display_datetime_format]" class="form-control" value="<?php echo $options['display_datetime_format']; ?>" placeholder="<?php echo get_option('links_updated_date_format'); ?>">
        </div><div class="col-sm-1"> <?php $this->during_trial( 'display_datetime_format' ); ?></div>
        <div class="col-sm-offset-2 col-sm-10">
          <p class="help-block"><?php _e('You can define the display format of datetime type data that is displayed in the plugin. By default, it will use the datetime format of the WordPress general settings.', $this->domain_name); ?></p>
        </div>
      </div><!-- /option-item-11 -->
      <div class="form-group">
        <label class="col-sm-2 control-label"><?php _e('Debug mode', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="option-item-15">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[debug_mode]" type="checkbox" value="1" <?php checked('1', $options['debug_mode']); ?>>
              <span class="checkbox-label"><?php _e('If you enable the debug mode, an error that occurred in the plugin will be output as a log. Please use as you want to investigate incidents.', $this->domain_name); ?> <?php $this->during_trial( 'debug_mode' ); ?></span>
            </label>
          </div>
        </div>
      </div><!-- /option-item-15 -->
      
      <div class="clearfix"><br></div>
      <h4 class="title"><?php _e('Initial definition for table creation', $this->domain_name); ?></h4>
      
      <div class="form-group">
        <label class="col-sm-2 control-label"><?php _e('Table prefix', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="option-item-21">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[use_wp_prefix]" type="checkbox" value="1" <?php checked('1', $options['use_wp_prefix']); ?>>
              <span class="checkbox-label"><?php global $wpdb; printf( __('Automatically prepend table prefix %s defined at the WordPress config (wp-config.php) when you create a new table.', $this->domain_name), '<code>'. $wpdb->prefix .'</code>' ); ?></span>
            </label>
            <p class="help-block"><?php _e('This setting can be changed individually at the time of table creation.', $this->domain_name); ?></p>
          </div>
        </div>
      </div><!-- /option-item-21 -->
      <div class="form-group">
        <label for="option-item-22" class="col-sm-2 control-label"><?php _e('Table character set', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="input-group input-append dropdown combobox col-sm-3" data-initialize="combobox" id="option-item-22">
            <input type="text" name="<?php echo $this->domain_name; ?>[charset]" value="<?php esc_attr_e($options['charset']); ?>" class="form-control">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php foreach ($this->db_charsets as $i => $charset) : ?>
                <li data-value="<?php echo $i + 1; ?>"><a href="#"><?php echo $charset; ?></a></li>
              <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <p class="help-block"><?php _e('This setting is default character set when created the table.', $this->domain_name); ?><a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php $this->during_trial( 'default_charset' ); ?></p>
        </div>
      </div><!-- /option-item-22 -->
      <div class="form-group">
        <label for="option-item-23" class="col-sm-2 control-label"><?php _e('Default timezone', $this->domain_name); ?></label>
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
          <p class="help-block inline-help"> <?php _e('Currently timezone of the MySQL database', $this->domain_name); ?>: <code><?php echo apply_filters( 'sanitize_option_timezone_string', $options['timezone'], 'timezone_string'); ?></code></p>
          <div class="clearfix">
            <p class="help-block"><?php _e('When this plugin insert the datetime type data, localized the value according to the set timezone.', $this->domain_name); ?> <?php $this->during_trial( 'localize_timezone' ); ?></p>
          </div>
        </div>
      </div><!-- /option-item-23 -->
      <div class="form-group">
        <label for="option-item-24" class="col-sm-2 control-label"><?php _e('Default database engine', $this->domain_name); ?></label>
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
          <p class="help-block"><?php _e('This initial value is the database engine of the table created by the plugin.', $this->domain_name); ?><a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php $this->during_trial( 'default_db_engine' ); ?></p>
        </div>
      </div><!-- /option-item-24 -->
      <div class="form-group">
        <label for="option-item-25" class="col-sm-2 control-label"><?php _e('Initial display number of records', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="spinbox disits-3" data-initialize="spinbox" id="option-item-25">
            <input type="text" name="<?php echo $this->domain_name; ?>[default_per_records]" value="<?php echo intval($options['default_per_records']); ?>" class="form-control input-mini spinbox-input">
            <div class="spinbox-buttons btn-group btn-group-vertical">
              <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only"><?php echo __('Increase', CDBT); ?></span></button>
              <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only"><?php echo __('Decrease', CDBT); ?></span></button>
            </div>
          </div>
          <p class="help-block"><?php _e('This initial value is the number of displayed records per one page of the table created by the plugin.', $this->domain_name); ?><a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php $this->during_trial( 'default_per_records' ); ?></p>
        </div>
      </div><!-- /option-item-25 -->

<!--
input.reguler-text { width: 25em; }
input.code { padding-top: 6px; } # for URL safe alnum
input[type=email], input[type=url], input.ltr { direction: ltr; } # for email, password
input.small-text { width: 50px; padding: 1px 6px }
input[type=number].small-text { width: 65px } # maxlength 4
textarea.code { line-height: 1.4; padding: 4px 6px 1px; }
input.large-text, textarea.large-text { width: 99%; }
-->
<!--
# for text field
<input id="option-item-1" type="text" name="" value="" aria-describedby="***-description" class="regular-text">
-->
<!--
# for numlic
<input name="posts_per_page" type="number" step="1" min="1" id="***" value="10" class="small-text">
-->
<!--
# for textarea
<fieldset>
  <legend class="screen-reader-text"><span>コメントブラックリスト</span></legend>
  <p>
    <label for="blacklist_keys">コメントの内容、名前、URL、メールアドレス、IP に以下の単語のうちいずれかでも含んでいる場合、そのコメントはスパムとしてマークされます。各単語や IP は改行で区切ってください。単語内に含まれる語句にもマッチします。例: “press” は “WordPress” にマッチします。</label>
  </p>
  <p>
    <textarea name="blacklist_keys" rows="10" cols="50" id="blacklist_keys" class="large-text code"></textarea>
  </p>
</fieldset>
-->
<!--
# for single checkbox
<fieldset>
  <legend class="screen-reader-text"><span>{label name}</span></legend>
  <label for="option-item-n">
    <input name="" type="checkbox" id="option-item-n" value="1">
    ********
  </label>
</fieldset>
-->
<!--
# for multi checkbox
<fieldset>
  <legend class="screen-reader-text"><span>整形</span></legend>
  <label for="use_smilies">
    <input name="use_smilies" type="checkbox" id="use_smilies" value="1" checked="checked">
    <code>:-)</code> や <code>:-P</code> のような顔文字を画像に変換して表示する
  </label>
  <br>
  <label for="use_balanceTags">
    <input name="use_balanceTags" type="checkbox" id="use_balanceTags" value="1">
     不正にネスト化した XHTML を自動的に修正する
  </label>
</fieldset>
-->
<!--
# .inline-description { padding-left: 25px; } # must add to style

# for selectbox
<select name="default_role" id="default_role">
  <option selected="selected" value="subscriber">購読者</option>
  <option value="contributor">寄稿者</option>
  <option value="author">投稿者</option>
  <option value="editor">編集者</option>
  <option value="administrator">管理者</option>
</select><span id="inline-description">*****</span>
-->
<!--
# for radio button
<fieldset>
  <legend class="screen-reader-text"><span>日付のフォーマット</span></legend>
  <label title="Y年n月j日"><input type="radio" name="date_format" value="Y年n月j日" checked="checked"> 2015年5月11日</label><br>
  <label title="Y-m-d"><input type="radio" name="date_format" value="Y-m-d"> 2015-05-11</label><br>
  <label title="m/d/Y"><input type="radio" name="date_format" value="m/d/Y"> 05/11/2015</label><br>
  <label title="d/m/Y"><input type="radio" name="date_format" value="d/m/Y"> 11/05/2015</label><br>
  <label><input type="radio" name="date_format" id="date_format_custom_radio" value="\c\u\s\t\o\m"> カスタム:<span class="screen-reader-text"> 以下の欄にカスタマイズした日付の書式を入力してください</span></label>
  <label for="date_format_custom" class="screen-reader-text">カスタム日付書式:</label>
  <input type="text" name="date_format_custom" id="date_format_custom" value="Y年n月j日" class="small-text"> <span class="screen-reader-text">例: </span><span class="example"> 2015年5月11日</span> <span class="spinner"></span>
</fieldset>
-->

      <div class="col-sm-offset-2 col-sm-10">
        <ul id="foot-note-1" class="foot-note">
          <li><span class="dashicons dashicons-info"></span> <?php _e('Already it is not reflected in the previously created table. Please change the table settings individually if you want to change it.', $this->domain_name); ?></li>
        </ul>
      </div>
      
      <div class="clearfix"><br></div>
      <h4 class="title"><?php _e('Advanced Plugin Settings', $this->domain_name); ?></h4>
      
      <div class="form-group">
        <label class="col-sm-2 control-label"><?php _e('Included Assets Definition', $this->domain_name); ?></label>
        <div class="col-sm-10">
          <div class="checkbox" id="option-item-31">
            <label class="checkbox-custom" data-initialize="checkbox">
              <input class="sr-only" name="<?php echo $this->domain_name; ?>[include_assets]" type="checkbox" value="1" <?php /*checked('1', $options['include_assets']);*/ ?>>
              <span class="checkbox-label"><?php _e('It will control the reading of various assets. Please change this settings if it conflicts the assets of the theme and other plugin.', $this->domain_name); /*各種アセットの読み込みを制御します。テーマや他のプラグインとのアセットが競合する場合に設定を変更してください。*/?></span> <?php $this->during_trial( 'include_assets' ); ?>
            </label>
          </div>
        </div>
      </div><!-- /option-item-31 -->
      
      
      <div class="clearfix"><br></div>
      <div class="form-group">
        <div class="col-sm-2">
          <input type="submit" name="submit" id="submit" class="btn btn-primary pull-right" value="<?php _e('Save Changes', $this->domain_name); ?>">
        </div>
      </div>
    </form>
  </div>
<?php endif; ?>
  
<?php if ($current_tab == 'debug') : 
  if (!isset($this->log_distination_path)) 
    $this->log_distination_path = $this->plugin_dir . 'debug.log';
?>
  
  <div class="well-sm">
    <p class="text-info">
      <?php _e('In this section you can check the log of various processes executed at the "Custom DataBase Tables" plugin.', $this->domain_name); ?><br>
      <?php _e('Available as a debugging log to follow the flow of the processing such as when trouble occurs.', $this->domain_name); ?>
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
          <textarea name="<?php echo $this->domain_name; ?>[debug-log]" id="debug-log-viewer" rows="20" class="form-control" readonly><?php echo file_get_contents($this->log_distination_path); ?></textarea>
        </div>
      </div>
      
      <div class="form-group">
        <div class="col-sm-1">
          <input type="submit" name="submit" id="debug-submit" class="btn btn-primary pull-left" value="<?php _e('Clear Logs', $this->domain_name); ?>">
        </div>
        <div class="checkbox highlight col-sm-10" id="debug-log-option">
          <label class="checkbox-custom highlight" data-initialize="checkbox">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[debug_log_option]" type="checkbox" value="1">
            <span class="checkbox-label"><?php _e('Remove the current log after backup of the log file.', $this->domain_name); /* ログファイルのバックアップ後に現在のログを削除する。 */ ?></span>
          </label>
        </div>
        <p class="help-block col-sm-offset-1 col-sm-10">
          <?php printf( __('Note: Backup files stores to the directory of %s.', $this->domain_name), '<code>'. $this->plugin_dir .'backup/</code>' ); /* バックアップファイルは %s に保存されます。*/ ?>
        </p>
      </div>
      
    </form>
  </div>
  
<?php endif; ?>
  
      </div><!-- /.tab-pane -->
    </div><!-- /.tab-content -->
  </div>
</div><!-- /.wrap -->