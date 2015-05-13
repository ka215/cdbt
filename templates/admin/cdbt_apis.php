<?php
$tabs = [
  'apikey_list' => esc_html__('API Key List', CDBT), 
  'apikey_entry' => esc_html__('API Key Register', CDBT), 
  'apikey_edit' => esc_html__('Edit API key', CDBT), 
];
$default_tab = 'apikey_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;
?>
<div class="wrap">
  <h2><?php esc_html_e('CDBT APIs Management', CDBT); ?></h2>
  
  <h3 class="nav-tab-wrapper">
  <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
    <a class="nav-tab<?php if ($current_tab == $tab_name) : ?> nav-tab-active<?php endif; ?>" href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>"><?php echo $display_tab_title; ?></a>
  <?php endforeach; ?>
  </h3>
  
<?php if ($current_tab == 'apikey_list') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('API Key List', CDBT); ?></h4>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'API Keys'; ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'apikey_entry') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('API Key Register', CDBT); ?></h3>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'API Key'; ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'apikey_edit') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Edit API Key', CDBT); ?></h4>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'API Key'; ?>
    
  </form>
<?php endif; ?>
  
</div><!-- /.wrap -->
