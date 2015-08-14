<?php
/**
 * Template : Plugin Option Settings Page
 * URL: `/wp-admin/admin.php?page=cdbt_options`
 *
 * @since 2.0.0
 *
 */

// 暫定設定
$wizard_step = [
  'default' => 1, 
  'current' => isset($_REQUEST['wizard_step']) && !empty($_REQUEST['wizard_step']) && intval($_REQUEST['wizard_step']) > 0 ? intval($_REQUEST['wizard_step']) : 1, 
  'display_max' => 3, 
  'name' => [
    __('Step1', CDBT), 
    __('Step2', CDBT), 
    __('Step3', CDBT), 
    __('Step4', CDBT), 
  ]
];
$this->destroy_session();

/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php esc_html_e('CDBT Management Console', CDBT); ?></h2>
  
  <div class="introduction">
    <p><?php _e('Welcome to the "Custom DataBase Tables" plugin! In this page is introductions about feature of plugin, and be able to go short trip as the tutorial.', CDBT); ?></p>
  </div>
  
  <?php
  /**
   * Define the localized variables for tab of `wizard`
   */
  $step1_content = '<p><div class="pull-left" style="margin: 1em 1.5em 1em 0;"><i class="image-icon cdbt-icon-v1 square96 pull-left" style="margin-top: 10px;"></i><i class="fa fa-arrow-right text-danger" style="margin: 50px 10px 0;"></i><i class="image-icon cdbt-logo square128 pull-right"></i></div>';
  $step1_content .= __('We were waiting very long you! Finally CustomDataBaseTable (hereinafter referred to as CDBT) is a plug-in major version up version V2 appeared.<br>In CDBT V2 finally ability to manage the core table of WordPress has been added. This, it should be to be able to defeat use the WordPress more CMS basis.<br>I aim to start this CDBT plug-ins, you can completely customize the login system, scheduled for release next "CustomLoginSuites (provisional)" or, you can easily add the original setting screen on the management screen "AnythingSetup (provisional)" , by using, for example, to protect the posts and media to cooperation "ProtectPostsPower (provisional)", it is to the WordPress and strongest of CMS.<br>First of all, please enjoy the CDBT plug-in is the first stage of the project!<br>', CDBT);
  $step1_content .= __('However, it is not yet in the release version.<br>Degree of completion of the current CDBT V2 is about <strong style="font-size: 32px; color: #dc4c3a;">90%</strong>.<br>Please wait for until complete.', CDBT);
  $step1_content .= '</p>';
  /* お待たせしました！ ようやくCustomDataBaseTable（以下、CDBTと呼ぶ）プラグインのメジャーバージョンアップ版V2が登場です。
CDBT V2ではついにWordPressのコアテーブルを管理できる機能が追加されました。これによって、WordPressをよりCMS的に使い倒すことができるようになるはずです。
私が目指すのはこのCDBTプラグインをはじめ、次にリリース予定のログイン系を完全にカスタマイズできる「CustomLoginSuites（仮）」や、管理画面にオリジナルの設定画面を簡単に追加できる「AnythingSetup（仮）」、投稿やメディアを協力に保護する「ProtectPostsPower（仮）」などを利用して、WordPressを最強のCMSとすることです。
まずは、そのプロジェクトの第一段階であるCDBTプラグインをご堪能ください！*/
  
  $conponent_options = [
    'id' => 'cdbt-wizard', 
    'defaultStep' => 1, 
    'currentStep' => 1, 
    'displayMaxStep' => 5, 
    'stepLabels' => [ __('Step1', CDBT), __('Step2', CDBT), __('Step3', CDBT), __('Step4', CDBT), __('Step5', CDBT) ], 
    'stepContents' => [ 
      [ 'title' => __('Custom DataBase Tables version 2 arrival now!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step1_content ], 
      [ 'title' => __('Version 2 has powered up all feature!', CDBT), 'bgcolor' => 'bg-danger', 'content' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.' ], 
      [ 'title' => __('Let&#39;s create a new table!', CDBT), 'bgcolor' => 'bg-info', 'content' => __('Since not yet written content, please wait.', CDBT) ], 
      [ 'title' => __('Let&#39;s use the shortcode!', CDBT), 'bgcolor' => 'bg-success', 'content' => __('Since not yet written content, please wait.', CDBT) ], 
      [ 'title' => __('Cooperation with external site by API', CDBT), 'bgcolor' => 'bg-warning', 'content' => __('Since not yet written content, please wait.', CDBT) ], 
    ], 
    'disablePreviousStep' => false, 
  ];
  $this->component_render('wizard', $conponent_options); // by trait `DynamicTemplate`
  
  ?>
  
<?php /*
  <div class="wizard" data-initialize="wizard" id="welcome-wizard">
    <ul class="steps">
    <?php foreach ($wizard_step['name'] as $i => $step_name) : ?>
      <?php if ($i < $wizard_step['display_max']) : ?>
      <li data-step="<?php echo $i+1; ?>" data-name="cdbt-step-<?php echo $i+1; ?>"<?php if ($wizard_step['current'] === $i+1) echo ' class="active"'; ?>><span class="badge"><?php echo $i+1; ?></span><?php echo $step_name; ?><span class="chevron"></span></li>
      <?php endif; ?>
    <?php endforeach; ?>
    </ul>
    <div class="actions">
      <button type="button" class="btn btn-default btn-prev"><span class="glyphicon glyphicon-arrow-left"></span><?php _e('Prev', CDBT); ?></button>
      <button type="button" class="btn btn-default btn-next" data-last="Complete"><?php _e('Next', CDBT); ?><span class="glyphicon glyphicon-arrow-right"></span></button>
    </div>
    <div class="step-content">
<?php
  /* Wizard Step1 Block * /
      if (1 <= $wizard_step['display_max']) : ?>
      <div class="step-pane active sample-pane alert" data-step="1">
        <h4>`$this` Object</h4>
        <p><?php 

var_dump($this);

        ?></p>
      </div>
<?php
      endif;
  /* Wizard Step2 Block * /
      if (2 <= $wizard_step['display_max']) : ?>
      <div class="step-pane sample-pane bg-info alert" data-step="2">
        <h4>Current Plugin Options</h4>
        <p><?php var_dump($this->options); ?></p>
      </div>
<?php
      endif;
  /* Wizard Step3 Block * /
      if (3 <= $wizard_step['display_max']) : ?>
      <div class="step-pane sample-pane bg-danger alert" data-step="3">
        <h4>`$this->get_table_status( 'wp_a' )`</h4>
        <p><?php var_dump( $this->get_table_status( 'wp_a' ) ); ?></p>
      </div>
<?php
      endif;
  /* Wizard Step4 Block * /
      if (4 <= $wizard_step['display_max']) : ?>
      <div class="step-pane sample-pane bg-danger alert" data-step="4">
        <h4>Design Template</h4>
        <p>Nori grape silver beet broccoli kombu beet greens fava bean potato quandong celery. Bunya nuts black-eyed pea prairie turnip leek lentil turnip greens parsnip. Sea lettuce lettuce water chestnut eggplant winter purslane fennel azuki bean earthnut pea sierra leone bologi leek soko chicory celtuce parsley jÃ­cama salsify. </p>
      </div>
<?php
      endif; ?>
    </div><!-- /.step-content -->
  </div><!-- /.wizard -->
  <div class="clearfix"><div style="height: 2em;"></div></div>
*/ ?>
  
  <div class="panel panel-default other-note">
    <div class="panel-heading"><span class="glyphicon glyphicon-heart" style="color: #f33;"></span> <?php esc_html_e( 'About Custom DataBase Tables', CDBT ); ?></div>
    <div class="panel-body">
      <p><?php printf( __('Custom DataBase Tables is provided an extensive %sdocumentations%s. It includes Frequently Asked Questions for you to use in plugins and themes, as well as documentation for further details about how to use for programmers.', CDBT), '<a href="http://ka2.org/cdbt/documentation/" target="_blank" alt="CDBT Documentations">', '</a>' ); ?>
      <?php printf( __('If you wonder how you can help the project, just %sread this%s.', CDBT), '<a href="http://ka2.org/cdbt/tutorials/" target="_blank" alt="CDBT Tutorials">', '</a>' ); ?>
      <?php printf( __('Custom DataBase Table is free of charge and is released under the same license as WordPress, the %sGPL%s.', CDBT), '<a href="http://www.gnu.org/licenses/gpl-2.0.html" target="_blank" alt="GPL 2.0">', '</a>' ); ?></p>
      <p class="pull-left"><?php printf( __('You will also find useful information in the %ssupport forum%s. However don&apos;t forget to make a search before posting a new topic.', CDBT), '<a href="http://ka2.org/cdbt-forum/forum/support-forum/" target="_blank" alt="CDBT Support Forum">', '</a>' ); ?>
      <?php esc_html_e( 'Finally if you like this plugin or if it helps your business, donations to the author are greatly appreciated.', CDBT ); ?></p>
      <div class="pull-left">
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
          <input type="hidden" name="cmd" value="_donations">
          <input type="hidden" name="business" value="2YZY4HWYSWEWG">
          <input type="hidden" name="lc" value="en_US">
          <input type="hidden" name="currency_code" value="USD">
          <input type="hidden" name="item_name" value="">
          <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - <?php esc_html_e( 'The safer, easier way to pay online!', CDBT ); ?>">
        </form>
      </div>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
  
</div><!-- /.wrap -->