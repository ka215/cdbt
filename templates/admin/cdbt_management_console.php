<?php
/**
 * Template : Plugin Option Settings Page
 * URL: `/wp-admin/admin.php?page=cdbt_options`
 *
 * @since 2.0.2
 * @since 2.0.9 Updated
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
  $step1_content = '<section class="cdbt-wizard-content"><div class="pull-left" style="margin: 1em 1.5em 1em 0;"><i class="image-icon cdbt-icon-v1 square96 pull-left" style="margin-top: 10px;"></i><i class="fa fa-arrow-right text-danger" style="margin: 50px 10px 0;"></i><i class="image-icon cdbt-logo square128 pull-right"></i></div>';
  $step1_content .= $_p_begin. __('Sorry I made you wait! Just barely plug-major upgrade version of "CustomDataBaseTables (commonly called CDBT) Version 2 (hereinafter, referred to as V2)" had released.', CDBT) .$_p_fin;
  $step1_content .= $_p_begin. __('The CDBT is a database management plugin that you can create your own tables in the MySQL database for WordPress, you can directly input and output of data in, and be related data to themes and external sites.', CDBT) .'<br>';
  $step1_content .= __('For using this plugin, you never need detailed knowledge of the database. Then you  can intuitively handle tables and data in database.', CDBT);
  $step1_content .= __('Your website that will be able to store own extended data by this plugin, it will give you some phenomenal extension idea to your envisioning project.', CDBT) .$_p_fin;
  $step1_content .= $_p_begin. __('Also at the CDBT V2, finally ability to manage the core table of WordPress has been added. In this feature, you should be able to using the WordPress as likely CMS.', CDBT) .$_p_fin;
  $step1_content .= $_p_begin. __('However, in order to utilize the CDBT V2, please be aware that it is necessary to <strong class="text-danger">PHP5.4 or higher</strong> environment. In addition, in this plugin it is using the external library below.', CDBT) .$_p_fin;
  $step1_content .= '<ul class="contribute-extends list-inline">'. implode('', $_contribute_list) .'</ul>';
  $step1_content .= '</section>';
  // Step2 section
  $step2_content = '<section class="cdbt-wizard-content"><div class="pull-right"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_1.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step2_content .= $_p_begin. __('CDBT V2 In addition to the new features of the WordPress core table management has been added a lot. Then, review the internal processing for the conventional functions, it is possible to reform to easy-to-use interface, we made significant enhancements.', CDBT) .$_p_fin;
  $step2_content .= $_p_begin. __('Added typical new feature in V2 is as follows.<br><ol><li>Management functions of the WordPress core table</li><li>Table replication function of</li><li>Editing of short codes and save function</li><li>WebAPI editing functions</li><li>Implementation of debug mode</li></ol><br>', CDBT) .$_p_fin;
  $step2_content .= '<div class="clearfix"></div><div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_2.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step2_content .= $_p_begin. __('In addition,  significantly enhancemented benefits is as follows.<br><ol style="list-style-position: inside;"><li>Added a file type of import/export of table data</li><li>Enhancemented the tool of table creator</li><li>Changed the shortcode appearance to using repeater format of FuelUX</li><li>Refinement of the detailed information display of table</li><li>Reform of the management screen interface</li></ol><br>', CDBT) .$_p_fin;
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
      [ 'title' => __('Custom DataBase Tables version 2 arrival now!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step1_content ], 
      [ 'title' => __('Version 2 has powered up all feature!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step2_content ], 
      [ 'title' => __('Let&#39;s create a new table!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step3_content ], 
      [ 'title' => __('Let&#39;s use the shortcode!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step4_content ], 
      [ 'title' => __('Cooperation with external site by API', CDBT), 'bgcolor' => 'bg-default', 'content' => $step5_content ], 
    ], 
    'disablePreviousStep' => false, 
  ];
  $this->component_render('wizard', $conponent_options); // by trait `DynamicTemplate`
  
  endif; ?>
  
  <div class="row">
  
  <div class="col-md-6">
  <div class="panel panel-default last-changelog">
    <div class="panel-heading"><span class="glyphicon glyphicon-list-alt text-muted"></span> <?php _e( 'Latest change logs', CDBT ); ?></div>
    <!-- <div class="panel-body"></div> -->
    <?php echo str_replace( '<li>', '<li class="list-group-item">', str_replace( '<ul>', '<ul class="list-group">', $latest_changelog ) ); ?>
  </div><!-- /.panel -->
  </div>
  
  <div class="col-md-6">
  <div class="panel panel-default donate-info">
    <div class="panel-heading"><span class="glyphicon glyphicon-heart" style="color: #f33;"></span> <?php esc_html_e( 'About Custom DataBase Tables', CDBT ); ?></div>
    <div class="panel-body">
      <p><?php printf( __('Custom DataBase Tables is provided an extensive %sdocumentations%s. It includes Frequently Asked Questions for you to use in plugins and themes, as well as documentation for further details about how to use for programmers.', CDBT), '<a href="https://ka2.org/cdbt/" target="_blank" alt="CDBT Documentations">', '</a>' ); ?>
      <?php printf( __('If you wonder how you can help the project, just %sread this%s.', CDBT), '<a href="https://ka2.org/cdbt/toc/" target="_blank" alt="CDBT Tutorial">', '</a>' ); ?>
      <?php printf( __('Custom DataBase Table is free of charge and is released under the same license as WordPress, the %sGPL%s.', CDBT), '<a href="http://www.gnu.org/licenses/gpl-2.0.html" target="_blank" alt="GPL 2.0">', '</a>' ); ?></p>
      <p class="pull-left"><?php printf( __('You will also find useful information in the %ssupport forum%s. However don&apos;t forget to make a search before posting a new topic.', CDBT), '<a href="https://wordpress.org/support/plugin/custom-database-tables" target="_blank" alt="CDBT Support Forum">', '</a>' ); ?>
      <?php esc_html_e( 'Finally if you like this plugin or if it helps your business, donations to the author are greatly appreciated.', CDBT ); ?></p>
      <div class="clearfix"></div>
      <ul class="list-inline donate-links">
        <li class="donate-paypal"><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
          <input type="hidden" name="cmd" value="_donations">
          <input type="hidden" name="business" value="2YZY4HWYSWEWG">
          <input type="hidden" name="lc" value="en_US">
          <input type="hidden" name="currency_code" value="USD">
          <input type="hidden" name="item_name" value="Donate to CustomDataBaseTable">
          <!-- input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - <?php esc_html_e( 'The safer, easier way to pay online!', CDBT ); ?>" -->
          <button type="submit" name="submit" alt="PayPal - <?php esc_html_e( 'The safer, easier way to pay online!', CDBT ); ?>" class="btn btn-primary"><i class="fa fa-paypal"></i> Donate Paypal</button>
          <img alt="" border="0" src="https://www.paypalobjects.com/ja_JP/i/scr/pixel.gif" width="1" height="1">
        </form></li>
        <li class="donate-blockchain"><div style="font-size:16px;margin:0 auto;width:300px" class="blockchain-btn" data-address="1821oc4XvWrfiwfVcNCAKEC8gppcrab4Re" data-shared="false">
          <div class="blockchain stage-begin">
            <img src="https://blockchain.info/Resources/buttons/donate_64.png"/>
          </div>
          <div class="blockchain stage-loading" style="text-align:center">
            <img src="https://blockchain.info/Resources/loading-large.gif"/>
          </div>
          <div class="blockchain stage-ready">
            <p align="center"><?php _e('Please Donate To Bitcoin Address:', CDBT);?> <b>[[address]]</b></p>
            <p align="center" class="qr-code"></p>
          </div>
          <div class="blockchain stage-paid">
            Donation of <b>[[value]] BTC</b> Received. Thank You.
          </div>
          <div class="blockchain stage-error">
            <font color="red">[[error]]</font>
          </div>
        </div></li>
<?php /*
        <li class="donate-coinbase hide">
          <a class="coinbase-button" data-code="219e4dae601d44bd7c2766178aff9471" data-button-style="custom_small" data-custom="CDBTV2" href="https://www.coinbase.com/checkouts/219e4dae601d44bd7c2766178aff9471">Donate Bitcoins</a><script src="https://www.coinbase.com/assets/button.js" type="text/javascript"></script>
        </li>
*/ ?>
      </ul>
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