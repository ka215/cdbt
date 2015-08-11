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
  
<?php if ($current_tab == 'hosts_list') : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php printf(__('現在APIリクエストが許可されているホストの一覧です。外部の許可ホストからはAPIキーを使用してこのプラグインで管理しているテーブルにアクセスして操作することができるようになります。提供されている%sAPIリクエストの詳細はこちら%sをご覧ください。', CDBT), '<a href="'. add_query_arg('tab', 'api_requests') .'">', '</a>'); ?> <?php $this->during_trial( 'hosts_list' ); ?>
    </p>
  </div>
  <?php 
  /**
   * Define the localized variables for tab of `apikey_list`
   */
  
  $datasource = [];
  if (is_array($shortcodes = $this->get_shortcode_list()) && !empty($shortcodes)) {
    $_index = 1;
    foreach ($shortcodes as $shortcode_name => $attributes) {
      $datasource[] = [
        'cdbt_index_id' => $_index, 
        'shortcode_id' => empty($attributes['alias_id']) ? '-' : $attributes['alias_id'], 
        'shortcode_name' => $shortcode_name, 
        'description' => $attributes['description'], 
        'shortcode_type' => $attributes['type'], 
        'shortcode_author' => 0 === $attributes['author'] ? '-' : get_the_author_meta('display_name', $attributes['author']), 
        'permission' => $attributes['permission'], 
        'operate_shortcode_url' => './' . basename( esc_url(admin_url(add_query_arg([ 'tab'=>'shortcode_' ]))) ), 
      ];
    }
  }
  $conponent_options = $this->create_scheme_datasource( 'cdbtShortcodes', 0, 10, 'shortcode_list', $datasource );
  $this->component_render('repeater', $conponent_options); // by trait `DynamicTemplate`
  
  ?>
<?php endif; ?>
  
<?php if ($current_tab == 'apikey_generator') : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('任意のホスト用のAPIキーを生成して、プラグインに登録を行います。', CDBT); ?> <?php $this->during_trial( 'apikey_generator' ); ?>
    </p>
  </div>
  <form id="" name="" action="" method="post" class="">
    
     <?php $this->during_trial( 'apikey_generator' ); ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'api_requests') : ?>
  <div class="well-sm">
    <p class="text-info">
      <?php _e('ここではAPIリクエスト（URL）の作成とテストを行うことができます。', CDBT); ?> <?php $this->during_trial( 'apikey_requests' ); ?>
    </p>
  </div>
  <form id="" name="" action="" method="post" class="">
    
    <?php $this->during_trial( 'api_requests' ); ?>
    
  </form>
<?php endif; ?>
  
</div><!-- /.wrap -->
