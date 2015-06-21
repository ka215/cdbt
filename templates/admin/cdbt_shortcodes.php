<?php
$tabs = [
  'shortcode_list' => esc_html__('Shortcode List', CDBT), 
  'shortcode_entry' => esc_html__('Shortcode Register', CDBT), 
  'shortcode_edit' => esc_html__('Edit Shortcode', CDBT), 
];
$default_tab = 'shortcode_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;
?>
<div class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php esc_html_e('CDBT Shortcodes Management', CDBT); ?></h2>
  
  <h3 class="nav-tab-wrapper">
  <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
    <a class="nav-tab<?php if ($current_tab == $tab_name) : ?> nav-tab-active<?php endif; ?>" href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>"><?php echo $display_tab_title; ?></a>
  <?php endforeach; ?>
  </h3>
  
<?php if ($current_tab == 'shortcode_list') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Shortcode List', CDBT); ?></h4>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'Shortcodes'; ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'shortcode_entry') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Shortcode Register', CDBT); ?></h3>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'Shortcodes'; ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'shortcode_edit') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Edit Shortcode', CDBT); ?></h4>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'Shortcodes'; ?>
    
  </form>
<?php endif; ?>
  
</div><!-- /.wrap -->
