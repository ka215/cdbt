<?php
/**
 * Template : Shortcodes Management Page
 * URL: `/wp-admin/admin.php?page=cdbt_shortcodes`
 *
 * @since 2.0.0
 *
 */

/**
 * Define the various localized variables for rendering
 */
$options = get_option($this->domain_name);
$tabs = [
  'shortcode_list' => __('Shortcode List', CDBT), 
  'shortcode_entry' => __('Shortcode Register', CDBT), 
  'shortcode_edit' => __('Edit Shortcode', CDBT), 
];
$default_tab = 'shortcode_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;

/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div id="page-head" name="page-head" class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php _e('CDBT Shortcodes Management', CDBT); ?></h2>
  
  <div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
      <li role="presentation"<?php if ($current_tab == $tab_name) : ?> class="active"<?php endif; ?>><a href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>" role="tab"><?php echo $display_tab_title; ?></a></li>
    <?php endforeach; ?>
    </ul>
  </div>
  
<?php if ($current_tab == 'shortcode_list') : ?>
  <h4 class="tab-annotation"><?php _e('Shortcode List', CDBT); ?></h4>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'Shortcodes'; ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'shortcode_entry') : ?>
  <h4 class="tab-annotation"><?php _e('Shortcode Register', CDBT); ?></h3>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'Shortcodes'; ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'shortcode_edit') : ?>
  <h4 class="tab-annotation"><?php _e('Edit Shortcode', CDBT); ?></h4>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'Shortcodes'; ?>
    
  </form>
<?php endif; ?>
  
</div><!-- /.wrap -->
