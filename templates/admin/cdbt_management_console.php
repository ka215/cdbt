<div class="wrap">
  <h2><?php esc_html_e('CDBT Management Console', CDBT); ?></h2>
  
  <div class="introduction">
    <p><?php var_dump($this); ?></p>
  </div>
  
  <div class="general">
    <?php echo do_shortcode('[nasmiru-list table="nas_admin_info" display_cols_order="admin_id,user_id,user_account,mail_address1,created,updated" operate_row="false" html_echo="true"]'); ?>
  </div>
  
</div><!-- /.wrap -->