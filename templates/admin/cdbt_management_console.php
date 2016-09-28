<?php
/**
 * Template : Plugin Option Settings Page
 * URL: `/wp-admin/admin.php?page=cdbt_options`
 *
 * @since 2.0.2
 * @since 2.0.9 Updated
 * @since 2.1.31 Updated
 *
 */

$this->destroy_session();
$_local_code = defined('WPLANG') ? '-' . WPLANG : '';

$contribute_extends = $this->contribute_extends;

$_contribute_list = [];
foreach ($contribute_extends as $_key => $_val) {
  $_contribute_list[] = sprintf('<li><a href="%s" target="_blank">%s</a> %s </li>', esc_url($_val['url']), $_key, $_val['version']);
}

$plugin_information = $this->retrieve_plugin_api();
$plugin_changelogs = $this->parse_chengelog( $plugin_information['changelog'] );
$latest_changelog = $plugin_changelogs[$plugin_information['latest_version']];
/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php esc_html_e('CDBT Management Console', CDBT); ?></h2>
  
  <div class="introduction">
  <?php if ( isset( $this->options['hide_tutorial'] ) && $this->options['hide_tutorial'] === $this->version ) : ?>
    <p><?php _e('Welcome to the "Custom DataBase Tables" plugin!', CDBT); ?> <a href="javascript:;" id="show-tutorial" class="pull-right"><?php _e('Show the tutorial again.', CDBT); ?></a></p>
  <?php else : ?>
    <p><?php echo __('Welcome to the "Custom DataBase Tables" plugin!', CDBT), ' ', __('In this page is introductions about feature of plugin, and be able to go short trip as the tutorial.', CDBT); ?></p>
  <?php endif; ?>
  </div>
  
  <?php if ( ! isset( $this->options['hide_tutorial'] ) || $this->options['hide_tutorial'] !== $this->version ) :
  /**
   * Define the localized variables for tab of `wizard`
   */
  $_p_begin = '<p class="paragraph'. $_local_code .'">';
  $_p_fin = '</p>';
  // Step1 section
  $step1_content = '<section class="cdbt-wizard-content"><div class="pull-left" style="margin: 1em 1.5em 1em 0;"><i class="image-icon cdbt-logo square_max pull-right"></i></div>';
  $step1_content .= $_p_begin. __('Thank you for waiting! We released the plugin upgraded version of "Custom DataBase Tables (commonly called CDBT) Version 2.1 (hereinafter, referred to as V2.1)" at last.', CDBT) .$_p_fin;
  $step1_content .= $_p_begin. __('The "CDBT" plugin is a database management tool. Using this plugin, you can create freely own table in  MySQL database of WordPress, and you can do input and output of data in an intuitive operation. Moreover you will be able to provide the data to as like themes and external sites easily.', CDBT) .'<br>';
  $step1_content .= __('Your website will be able to store own extended data by this plugin, it causes give you some phenomenal extension idea to your project.', CDBT) .$_p_fin;
  $step1_content .= $_p_begin. __('Since version 2.0, it has been added the feature to manage the core tables built in the WordPress. Using this feature, you would be able to operating the WordPress as with CMS.', CDBT) .' ';
  $step1_content .= __('Further in the "CDBT V2.1", we enhanced the display of the data lists that is rendered in such as shortcode, and we revised various text on the management screen to make intelligible operation.', CDBT) .$_p_fin;
  $step1_content .= $_p_begin. __('However, in order to utilize the "CDBT V2.1", please be aware that it is necessary environment of <strong class="text-danger">PHP 5.4 or higher</strong>. In addition, in this plugin it is using the external library below.', CDBT) .$_p_fin;
  $step1_content .= '<ul class="contribute-extends list-inline">'. implode( '', $_contribute_list ) .'</ul>';
  $step1_content .= '</section>';
  // Step2 section
  $step2_content = '<section class="cdbt-wizard-content"><div class="pull-right"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_1.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step2_content .= $_p_begin. __('"CDBT V2.1" release notes are as follows:', CDBT);
  $_tmp_list = [
    __('Added dynamic table layout as shortcode renderer for corresponded to multidevice', CDBT), 
    __('Supported to render data of JSON format via shortcode', CDBT), 
    __('Revised English in the management screen', CDBT), 
    __('Added the ability to copy the specific string like shortcode, referenceable SQL with one click to the clipboard', CDBT), 
    __('Changed the plugin&#39;s version notation specifications to the "(Major version number).(Minor version number).(Cumulative version number)".', CDBT), 
    __('Supported the loading data via AJAX for handling large size table', CDBT), 
    //__('Added a table backup function (a trial version)', CDBT), 
  ];
  $step2_content .= '<br><ol><li>'. implode( '</li><li>', $_tmp_list ) .'</li></ol><br>' .$_p_fin;
  $step2_content .= '<div class="clearfix"></div><div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_2.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step2_content .= $_p_begin. __('Since CDBT version 2.0, in addition to the new features of the WordPress core table management has been added a lot. Then, review the internal processing for the conventional functions, it is possible to reform to easy-to-use interface, we made significant enhancements.', CDBT) .$_p_fin;
  $step2_content .= $_p_begin. __('The Added new features since version 2.0 are as follows:', CDBT);
  $_tmp_list = [
    __('Capable of managing the core tables built in WordPress', CDBT), 
    __('Function for duplicating a table', CDBT), 
    __('Function for editing the shortcodes and maintaining', CDBT), 
    __('Function for managing Web APIs (a trial version)', CDBT), 
    __('Implementation of debug mode', CDBT), 
    __('Capable of overwriting various notice messages of the plugin', CDBT), 
  ];
  $step2_content .= '<br><ol style="list-style-position: inside;"><li>'. implode( '</li><li>', $_tmp_list ) .'</li></ol><br>' .$_p_fin;
  $step2_content .= '</section>';
  // Step3 section
  $step3_content = '<section class="cdbt-wizard-content">';
  $step3_content .= '<div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_3.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step3_content .= $_p_begin. __('So, let&apos;s create a new table first with CDBT V2.', CDBT) .$_p_fin;
  $step3_content .= $_p_begin. __('First, please choose the "tables" from the CDBT menu of WordPress, then selected of "Create Table" tab. When you see the screen of the "Table setting for a database", let&apos;s enter the name of the table you want to create.', CDBT) .$_p_fin;
  $step3_content .= $_p_begin. __('Then, select a character set and the database engine of the table, please click on the button of "Make Template" on the right-hand side of the "Create Table SQL" column. SQL statement for the table creation has been generated automatically in the textarea of "Create Table SQL". The basic table creation operation is in this flow.', CDBT) .$_p_fin;
  $step3_content .= $_p_begin. __('This left the table when you run the "Create Table" will be created at this time. But, there is no shelf (column) in that table to store your data. So, let&apos;s add a column to the table.', CDBT) .$_p_fin;
  $step3_content .= '<div class="clearfix"></div><div class="pull-right"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_4.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step3_content .= $_p_begin. __('When you click the "Table Creator" tab of the "Create Table SQL" column, a new dialog opens, it dedicated editor that you can insert and update the columns.', CDBT) .$_p_fin;
  $step3_content .= $_p_begin. __('At the "Table Creator", enter the column name, and it will automatically switch settable item column when you choose the data format to be stored in the column. Also, you can change by drag-and-drop the order of the columns for each row. You will be able to edit the table freely without knowledge about database using this editor tool.', CDBT) .$_p_fin;
  $step3_content .= $_p_begin. __('When you are finished editing the column, Do not forget to click the "Apply SQL" button. At this time, the column settings that you&apos;ve edited will be stored in the browser. So you will be able to resume the column editing work as when you will re-open after close the dialog.', CDBT) .$_p_fin;
  $step3_content .= '<div class="clearfix"></div><div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_5.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step3_content .= $_p_begin. __('The section of "Table setting for the plugin", it sets for when you want to use a table that you have created at CDBT V2 on the web frontend. It is the main set for handling data in the table via shortcodes. Note that this setting can be changed after the table creation at any time.', CDBT) .$_p_fin;
  $step3_content .= $_p_begin. __('Finally, there is table importation to CDBT. You can also allow you to manage it at CDBT V2, in the own table that you have created via like another plugins. From the section of "Incorporate an existing table", please select the table you want to capture.', CDBT) .$_p_fin;
  $step3_content .= $_p_begin. '<div style="margin-top: 3em;"><a href="/wp-admin/admin.php?page=cdbt_tables&tab=create_table" class="btn btn-default pull-right">'. __('Go To Table Creation', CDBT) .'</a></div>' .$_p_fin;
  $step3_content .= '</section>';
  // Step4 section
  $step4_content = '<section class="cdbt-wizard-content">';
  $step4_content .= '<div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_6.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step4_content .= $_p_begin. __('Table managed by the CDBT V2 is, it is possible to use some shortcodes, you can display the data content to the frontend of the site, or you can be registered the data from the frontend. By the using these shortcodes, you will be able to provide an interactive database available to users who visit your site.', CDBT) .$_p_fin;
  $step4_content .= $_p_begin. __('For example, it can in cooperation with the user to collect and accumulate content and data. Or you may be able to provide like CRM service that built own user management table.', CDBT) .$_p_fin;
  $step4_content .= $_p_begin. __('The appearance of the content that is output via the shortcode can be fully customizable from the management screen to fit the available scene. In addition, you can be registered the shortcode of your own settings to the plugin. After registering shortcodes, it will be issued alias code complex attribute setting is omitted in. When you actually use it, you may just paste the alias code in posts and fixed page.', CDBT) .$_p_fin;
  $step4_content .= $_p_begin. '<div style="margin-top: 3em;"><a href="/wp-admin/admin.php?page=cdbt_shortcodes" class="btn btn-default pull-right">'. __('Go To Shortcodes Management', CDBT) .'</a></div>' .$_p_fin;
  $step4_content .= '</section>';
  // Step5 section
  $step5_content = '<section class="cdbt-wizard-content">';
  $step5_content .= '<div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_7.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step5_content .= $_p_begin. __('The data in the table managed by the CDBT V2 By using the Web API, you can access from the outside of the site. To do this you need to register the site to allow access from the "CDBT Web APIs Management" (in the Web APIs menu).', CDBT) .$_p_fin;
  $step5_content .= $_p_begin. __('Since not yet written content, please wait.', CDBT) .$_p_fin;
  $step5_content .= $_p_begin. '<div style="margin-top: 3em;"><a href="/wp-admin/admin.php?page=cdbt_web_apis" class="btn btn-default pull-right">'. __('Go To WebAPIs Management', CDBT) .'</a></div>' .$_p_fin;
  $step5_content .= '</section>';
  
  $conponent_options = [
    'id' => 'cdbt-wizard', 
    'defaultStep' => 1, 
    'currentStep' => 1, 
    'displayMaxStep' => 5, 
    'stepLabels' => [ __('Step1', CDBT), __('Step2', CDBT), __('Step3', CDBT), __('Step4', CDBT), __('Step5', CDBT) ], 
    'stepContents' => [ 
      [ 'title' => __('New Custom DataBase Tables release - version 2.1!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step1_content ], 
      [ 'title' => __('Version 2.1 has improved the user experience!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step2_content ], 
      [ 'title' => __('Let&#39;s create a new table!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step3_content ], 
      [ 'title' => __('Let&#39;s try to use the shortcode!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step4_content ], 
      [ 'title' => __('Cooperation with external site by API', CDBT), 'bgcolor' => 'bg-default', 'content' => $step5_content ], 
    ], 
    'disablePreviousStep' => false, 
  ];
  $this->component_render('wizard', $conponent_options); // by trait `DynamicTemplate`
  
  endif; ?>
  
  <div class="row">
  
  <div class="col-md-6">
  <div class="panel panel-default last-changelog">
    <div class="panel-heading"><span class="glyphicon glyphicon-list-alt text-muted"></span> <?php _e( 'Latest change logs', CDBT ); ?> &nbsp; <?php $this->during_trial( 'changelog_panel' ); ?></div>
    <!-- <div class="panel-body"></div> -->
    <?php echo str_replace( '<li>', '<li class="list-group-item">', str_replace( '<ul>', '<ul class="list-group">', $latest_changelog ) ); ?>
  </div><!-- /.panel -->
  </div>
  
  <div class="col-md-6">
  <div class="panel panel-default donate-info">
    <div class="panel-heading"><span class="glyphicon glyphicon-heart" style="color: #f33;"></span> <?php esc_html_e( 'About Custom DataBase Tables', CDBT ); ?></div>
    <div class="panel-body">
      <p><?php printf( __('Custom DataBase Tables is free of charge and is released under the same license as WordPress, the %sGPL%s.', CDBT), '<a href="http://www.gnu.org/licenses/gpl-2.0.html" target="_blank" alt="GPL 2.0">', '</a>' ); ?>
      <?php printf( __('We have informed to %sfacebook page%s the latest developments about this plugin. Since it has also accepted requests and questions, please try to see.', CDBT), '<a href="https://www.facebook.com/ka2.org/" target="_blank">', '</a>' ); ?></p>
      <div class="noteworthy">
        <ul class="list-inline">
          <li><a href="https://twitter.com/intent/tweet?text=Custom%20DataBase%20Tables%20v<?php echo $this->version; ?>%20-%20&url=https%3A%2F%2Fka2.org%2Fcdbt%2F&via=ka2bowy" target="_blank" class="cdbt-btn-group cdbt-twitter">
            <span class="btn-left"><i class="fa fa-twitter" aria-hidden="true"></i></span><span class="btn-right"><?php _e('SHOW YOUR LOVE', CDBT); ?></span></a></li>
          <li><a href="https://www.facebook.com/ka2.org/" target="_blank" class="cdbt-btn-group cdbt-facebook">
            <span class="btn-left"><i class="fa fa-facebook" aria-hidden="true"></i></span><span class="btn-right"><?php _e('CHECK LATEST INFO', CDBT); ?></span></a></li>
          <li><a href="https://wordpress.org/support/view/plugin-reviews/custom-database-tables#postform" target="_blank" class="cdbt-btn-group cdbt-review">
            <span class="btn-left"><i class="fa fa-star" aria-hidden="true"></i></span><span class="btn-right"><?php _e('LEAVE A REVIEW', CDBT); ?></span></a></li>
          <li><a href="https://ka2.org/cdbt/" target="_blank" class="cdbt-btn-group cdbt-official">
            <span class="btn-left"><i class="fa fa-home" aria-hidden="true"></i></span><span class="btn-right"><?php _e('OFFICIAL SITE', CDBT); ?></span></a></li>
        </ul>
      </div>
      <p><?php printf( __('Custom DataBase Tables is provided an extensive %sdocumentations%s. It includes Frequently Asked Questions for you to use in plugins and themes, as well as documentation for further details about how to use for programmers.', CDBT), '<a href="https://ka2.org/cdbt/" target="_blank" alt="CDBT Documentations">', '</a>' ); ?>
      <?php printf( __('If you wonder how you can help the project, just %sread this%s.', CDBT), '<a href="https://ka2.org/cdbt/toc/" target="_blank" alt="CDBT Tutorial">', '</a>' ); ?></p>
      <p><?php printf( __('You will also find useful information in the %ssupport forum%s. However don&apos;t forget to make a search before posting a new topic.', CDBT), '<a href="https://wordpress.org/support/plugin/custom-database-tables" target="_blank" alt="CDBT Support Forum">', '</a>' ); ?></p>
      <p><?php esc_html_e( 'Finally, if you like this plugin or help your business, please consider donation for continuous development.', CDBT ); ?></p>
      <div class="clearfix" style="margin-bottom: 1em;"></div>
      <ul class="list-inline donate-links">
        <li class="donate-paypal"><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
          <div class="pull-left">
          <input type="hidden" name="cmd" value="_s-xclick">
          <input type="hidden" name="hosted_button_id" value="RJZRBH7MFWQCA">
          <input type="hidden" name="on0" value="Donations">
          <select name="os0">
            <option value="USD-10">$10.00</option>
            <option value="USD-20">$20.00</option>
            <option value="USD-30">$30.00</option>
            <option value="USD-40">$40.00</option>
            <option value="USD-50">$50.00</option>
            <option value="USD-60">$60.00</option>
            <option value="USD-70">$70.00</option>
            <option value="USD-80">$80.00</option>
            <option value="USD-90">$90.00</option>
          </select></div>
          <div class="pull-right">
          <input type="hidden" name="currency_code" value="USD">
          <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_paynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
          <img alt="" border="0" src="https://www.paypalobjects.com/ja_JP/i/scr/pixel.gif" width="1" height="1"></div>
        </form></li>
      <?php /* if ( 'ja' === get_locale() ) : */ ?>
        <li class="donate-spike">
          <a href="https://spike.cc/shop/ka215" target="_blank" rel="nofollow" style="padding: 8px;">
            <img src="<?php printf( '%s/%s/%s', $this->plugin_url, $this->plugin_assets_dir, 'images/spike-logo-90x36.png' ); ?>" border="0" alt="SPIKE">
          </a>
          <p class="help-block">(<?php _e( 'Notice: The payment currency by SPIKE is Japanese Yen only', CDBT ); ?>)</p>
        </li>
      <?php /* endif; */ ?>
      </ul>
      <div class="clearfix" style="margin-bottom: 1em;"></div>
        <p><?php printf( __( 'If you want to pay by other currency, please use from %sdonation page%s of the official site.', CDBT ), '<a href="https://ka2.org/donation/cdbt/">', '</a>' ); ?><br>
        <?php printf( __('Also, I don&#39;t care how to send mail to "%s" for support email if you have other request and question.', CDBT), '<em class="text-info">'. $this->support_email .'</em>' ); ?></p>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
  </div>
  
  </div><!-- /.row -->
  
  <div class="panel panel-default other-note">
    <div class="panel-heading"><i class="fa fa-check-circle-o" style="color: #999900;"></i> <?php esc_html_e( 'CustomDataBaseTables License Agreement', CDBT ); ?></div>
    <div class="panel-body">
      <p>Copyright <i class="fa fa-copyright"><span class="sr-only">(c)</span></i> 2014 - <?php echo date('Y'); ?>, ka2 ( <a href="https://ka2.org/" target="_blank">https://ka2.org/</a> )</p>
      <p><?php _e('This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 2, as published by the Free Software Foundation.'); ?></p>
      <p><?php _e('This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.'); ?></p>
      <p><?php _e('You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA'); ?></p>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
  
</div><!-- /.wrap -->