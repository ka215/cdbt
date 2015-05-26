<?php
/**
 * Template : Plugin Option Settings Page
 * URL: `/wp-admin/admin.php?page=cdbt_options`
 *
 * @since 2.0.0
 *
 */

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

/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div class="wrap">
  <h2><?php esc_html_e('CDBT Management Console', CDBT); ?></h2>
  
  <div class="introduction">
    <p>Welcome to "Custom DataBase Tables" plugin! This page is tutorial of about plugin.</p>
  </div>
  
  <div class="wizard" data-initialize="wizard" id="welcome-wizard">
    <ul class="steps">
    <?php foreach ($wizard_step['name'] as $i => $step_name) : ?>
      <?php if ($i < $wizard_step['display_max']) : ?>
      <li data-step="<?php echo $i+1; ?>" data-name="cdbt-step-<?php echo $i+1; ?>"<?php if ($wizard_step['current'] === $i+1) echo ' class="active"'; ?>><span class="badge"><?php echo $i+1; ?></span><?php echo $step_name; ?><span class="chevron"></span></li>
      <?php endif; ?>
    <?php endforeach; ?>
    </ul>
    <div class="actions">
      <button type="button" class="btn btn-default btn-prev"><span class="glyphicon glyphicon-arrow-left"></span>Prev</button>
      <button type="button" class="btn btn-default btn-next" data-last="Complete">Next<span class="glyphicon glyphicon-arrow-right"></span></button>
    </div>
    <div class="step-content">
<?php
  /* Wizard Step1 Block */
      if (1 <= $wizard_step['display_max']) : ?>
      <div class="step-pane active sample-pane alert" data-step="1">
        <h4>`CdbtAdmin` Objests</h4>
        <p><?php 

//var_dump($this->add_new_table('wp_a'));

//$this->update_options(); 
var_dump($this->options);


      /* var_dump($this); */ ?></p>
      </div>
<?php
      endif;
  /* Wizard Step2 Block */
      if (2 <= $wizard_step['display_max']) : ?>
      <div class="step-pane sample-pane bg-info alert" data-step="2">
        <h4>Current Plugin Options</h4>
        <p><?php var_dump($this->options); ?></p>
      </div>
<?php
      endif;
  /* Wizard Step3 Block */
      if (3 <= $wizard_step['display_max']) : ?>
      <div class="step-pane sample-pane bg-danger alert" data-step="3">
        <h4>Design Template</h4>
        <p><?php var_dump( $this->get_table_status( 'wp_a' ) ); ?></p>
      </div>
<?php
      endif;
  /* Wizard Step4 Block */
      if (4 <= $wizard_step['display_max']) : ?>
      <div class="step-pane sample-pane bg-danger alert" data-step="4">
        <h4>Design Template</h4>
        <p>Nori grape silver beet broccoli kombu beet greens fava bean potato quandong celery. Bunya nuts black-eyed pea prairie turnip leek lentil turnip greens parsnip. Sea lettuce lettuce water chestnut eggplant winter purslane fennel azuki bean earthnut pea sierra leone bologi leek soko chicory celtuce parsley jÃ­cama salsify. </p>
      </div>
<?php
      endif; ?>
    </div><!-- /.step-content -->
  </div><!-- /.wizard -->
  
  <br><br>
  
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