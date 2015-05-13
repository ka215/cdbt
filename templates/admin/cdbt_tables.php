<?php
$tabs = [
  'table_list' => esc_html__('Table List', CDBT), 
  'create_table' => esc_html__('Create Table', CDBT), 
  'modify_table' => esc_html__('Modify Table', CDBT), 
];
$default_tab = 'table_list';
$current_tab = isset($this->query['tab']) && !empty($this->query['tab']) ? $this->query['tab'] : $default_tab;
$dynamic_tab_title = esc_html__('Edit User', CDBT);

function cdbt_admin_users_listing_value_shortcode( $html_value, $shortcode_name, $table, $col, $row ) {
  if ( 'nasmiru-list' !== $shortcode_name || 'nas_admin_info' !== $table ) 
    return $html_value;
  
  if ( 'admin_id' === $col || 'user_account' === $col ) 
    $html_value = sprintf('<a href="%s">%s</a>', add_query_arg([ 'tab' => 'user_edit', 'admin_id' => $row->admin_id ]), $html_value );
  
  if ( 'user_id' === $col ) 
    $html_value = sprintf('<a href="%s">%s</a>', add_query_arg([ 'user_id' => $html_value ], 'user-edit.php'), $html_value );
  
  if ( 'created' === $col || 'updated' === $col ) 
    $html_value = date(__('Y/m/d H:i', NMP), strtotime($html_value));
  
  return $html_value;
}
add_filter( 'cdbt_listing_value_shortcode', 'cdbt_admin_users_listing_value_shortcode', 10, 5 );

function cdbt_admin_users_operate_elements_shortcode( $operate_elements, $shortcode_name, $table, $row ) {
  if ( 'nasmiru-list' !== $shortcode_name || 'nas_admin_info' !== $table ) 
    return $operate_elements;
  
  foreach ($operate_elements as $i => $element) {
    if (preg_match('/^<a\shref=\"([a-zA-Z0-9\._\-\/]+)\?(.*)\"\s.*$/iU', $element, $matches) && is_array($matches) && array_key_exists(2, $matches)) {
      $queries = [];
      foreach (explode('&#038;', $matches[2]) as $query_seed) {
        list($key, $value) = explode('=', $query_seed);
        $queries[trim($key)] = trim($value);
      }
      unset($queries['line_id']);
      $queries['admin_id'] = $row->admin_id;
      
      if (in_array($queries['action'], [ 'detail', 'edit' ])) 
        $queries['tab'] = 'user_edit';
      
      $request_to = esc_url(add_query_arg( $queries, $matches[1] ));
      $element = preg_replace('/^(<a\shref=\").*(\"\s.*)$/iU', "$1{$request_to}$2", $element);
      
      if (intval($row->delete_flag) === 1 && 'delete' === $queries['action']) 
        $element = preg_replace('/^(.*)(>.*)$/iU', "$1 disabled$2", $element);
      
      $operate_elements[$i] = $element;
    }
  }
  
  return $operate_elements;
}
add_filter( 'cdbt_operate_elements_shortcode', 'cdbt_admin_users_operate_elements_shortcode', 10, 4 );

/**
 * Actions this page
 */

if (isset($_GET['action']) && !empty($_GET['action'])) {
  switch ($_GET['action']) {
    case 'delete': 
      if ('user_list' === $_GET['tab'] && isset($_GET['admin_id']) && intval($_GET['admin_id']) > 0) {
//        var_dump('deleted!');
      }
      break;
    case 'detail': 
      if ('user_edit' === $_GET['tab'] && isset($_GET['admin_id']) && intval($_GET['admin_id']) > 0) {
        $dynamic_tab_title = esc_html__('User Detail', NMP);
//        var_dump('more information');
      }
      break;
    case 'edit': 
      if ('user_edit' === $_GET['tab'] && isset($_GET['admin_id']) && intval($_GET['admin_id']) > 0) {
        $dynamic_tab_title = esc_html__('Edit User', NMP);
//        var_dump('edit');
      }
      break;
    default: 
      break;
  }
}

?>
<div class="wrap">
  <h2><?php esc_html_e('CDBT Tables Management', CDBT); ?></h2>
  
  <h3 class="nav-tab-wrapper">
  <?php foreach ($tabs as $tab_name => $display_tab_title) : ?>
    <a class="nav-tab<?php if ($current_tab == $tab_name) : ?> nav-tab-active<?php endif; ?>" href="<?php echo esc_url( add_query_arg('tab', $tab_name) ); ?>"><?php echo $display_tab_title; ?></a>
  <?php endforeach; ?>
  </h3>
  
<?php if ($current_tab == 'table_list') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Enabled Table List', CDBT); ?></h4>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo do_shortcode('[nasmiru-list table="nas_admin_info" display_cols_order="admin_id,user_id,user_account,mail_address1,delete_flag,created,updated" operate_row="true" html_echo="true"]'); ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'create_table') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Create New Table', CDBT); ?></h4>
  <form id="" name="" action="" method="post" class="">
    
    <?php echo 'Create Table'; ?>
    
  </form>
<?php endif; ?>
  
<?php if ($current_tab == 'modify_table') : ?>
  <h4 class="tab-annotation"><?php esc_html_e('Modify Table', CDBT); ?></h4>
    
<?php
    if (isset($_GET['admin_id']) && intval($_GET['admin_id']) > 0) {
      $shortcode_string = sprintf( '[nasmiru-edit table="nas_admin_info" conditions="admin_id:%d" __display_cols_order="admin_id,user_id" render_mode="%s"]', intval($_GET['admin_id']), $render_mode );
      echo do_shortcode($shortcode_string);
    } else {
      printf( __('<p>%s</p>', CDBT), sprintf( __('Please select the table you want to modify from <a href="%s">the table list</a>.', CDBT), esc_url(add_query_arg([ 'tab' => 'table_list' ])) ) );
    }
?>
    
<?php endif; ?>
    
</div><!-- /.wrap -->