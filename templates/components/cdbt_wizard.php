<?php
/**
 * Wizard Options array `$this->component_options` scheme
 * [
 * 'id' => @string is element id [require]
 * 'defaultStep' => @integer is default step number [optional] For default `1`
 * 'currentStep' => @integer is currently step number [optional] For default `1`
 * 'displayMaxStep' => @integer is rendered step number of maximum [optional] For default `1`
 * 'stepLabels' => @array(no assoc) is displayed label of step [optional] For default is `Step` + (key-index + 1)
 * 'stepContents' => @array(assoc) is displayed content and styling of any step [optional] For default is null
 * 'splitRendering' => @string Outputting with dividing the components before and after content part [optional] `before` or `after`
 * 'disablePreviousStep' => @boolean Whether the previous step button disable [optional] For default `false`
 * 'extras' = @array(assoc) [optional]
 */

/**
 * Parse options
 * ---------------------------------------------------------------------------
 */

// `id` section
if (isset($this->component_options['id']) && !empty($this->component_options['id'])) {
  $wizard_id = esc_attr__($this->component_options['id']);
} else {
  return;
}

// `defaultStep` section
if (isset($this->component_options['defaultStep']) && intval($this->component_options['defaultStep']) > 0) {
  $default_step = intval($this->component_options['defaultStep']);
} else {
  $default_step = 1;
}

// `currentStep` section
if (isset($this->component_options['currentStep']) && intval($this->component_options['currentStep']) > 0) {
  $current_step = intval($this->component_options['currentStep']);
} else {
  $current_step = 1;
}

// `displayMaxStep` section
if (isset($this->component_options['displayMaxStep']) && intval($this->component_options['displayMaxStep']) >= 1) {
  $display_max_step = intval($this->component_options['displayMaxStep']);
} else {
  $display_max_step = 1;
}

// `stepLabels` section
$wizard_steps = [];
for ($i=0; $i<$display_max_step; $i++) {
  $wizard_steps[$i] = [ 'label' => sprintf( __('Step%d', CDBT), ($i + 1) ) ];
}
if (isset($this->component_options['stepLabels']) && !empty($this->component_options['stepLabels'])) {
  foreach ($this->component_options['stepLabels'] as $i => $label) {
    $wizard_steps[$i] = [ 'label' => $label ];
  }
}

// `stepContents` section
if (isset($this->component_options['stepContents']) && is_array($this->component_options['stepContents']) && !empty($this->component_options['stepContents'])) {
  foreach ($wizard_steps as $step_num => $step_values) {
    if (isset($this->component_options['stepContents'][$step_num]) && is_array($this->component_options['stepContents'][$step_num])) 
      $wizard_steps[$step_num] = array_merge($step_values, $this->component_options['stepContents'][$step_num]);
  }
}

// `splitRendering` section
$is_split = false;
if (isset($this->component_options['splitRendering']) && !empty($this->component_options['splitRendering']) && in_array($this->component_options['splitRendering'], [ 'before', 'after' ])) {
  $is_split = true;
  $render_part = $this->component_options['splitRendering'];
}

// `disablePreviousStep` section
$disable_previous_step = 'false';
if (isset($this->component_options['disablePreviousStep']) && !empty($this->component_options['disablePreviousStep'])) {
  $disable_previous_step = $this->component_options['disablePreviousStep'] ? 'true' : 'false';
}


// `extras` section


/**
 * Render the Repeater
 * ---------------------------------------------------------------------------
 */
if (!$is_split) :
?>
  <div class="wizard" data-initialize="wizard" id="<?php echo $wizard_id; ?>">
    <div class="steps-container">
      <ul class="steps" style="margin-left: 0">
      <?php foreach ($wizard_steps as $i => $step_values) : ?>
        <?php if ($i < $display_max_step) : ?>
        <li data-step="<?php echo $i+1; ?>" data-name="cdbt-step-<?php echo $i+1; ?>"<?php if ($current_step === $i+1) echo ' class="active"'; ?>><span class="badge"><?php echo $i+1; ?></span><?php echo $step_values['label']; ?><span class="chevron"></span></li>
        <?php endif; ?>
      <?php endforeach; ?>
      </ul>
    </div>
    <div class="actions">
      <button type="button" class="btn btn-default btn-prev"><span class="glyphicon glyphicon-arrow-left"></span><?php _e('Prev', CDBT); ?></button>
      <button type="button" class="btn btn-default btn-next" data-last="<?php _e('Complete', CDBT); ?>"><?php _e('Next', CDBT); ?><span class="glyphicon glyphicon-arrow-right"></span></button>
    </div>
    <div class="step-content">
    <?php foreach ($wizard_steps as $i => $step_values) : ?>
      <?php if ($i < $display_max_step) : ?>
      <div class="step-pane <?php if (isset($step_values['bgcolor'])) echo $step_values['bgcolor'] . ' '; ?><?php if ($current_step === $i+1) echo 'active '; ?>alert" data-step="<?php echo $i+1; ?>">
        <h4><?php echo isset($step_values['title']) ? $step_values['title'] : $step_values['label']; ?></h4>
        <?php if (isset($step_values['content'])) : ?>
        <p><?php echo $step_values['content']; ?><p>
        <?php else : ?>
        <div class="step-body"></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    <?php endforeach; ?>
    </div><!-- /.step-content -->
  </div><!-- /.wizard -->
  <div class="clearfix"><div style="height: 2em;"></div></div>
<script>
var wizard = function() {
  var wizard = $('#<?php echo $wizard_id; ?>');
  wizard.wizard(
    'disablePreviousStep', <?php echo $disable_previous_step; ?>
  );
};
</script>
<?php else : ?>
  <?php if ('before' === $render_part) : ?>
  <div class="wizard" data-initialize="wizard" id="<?php echo $wizard_id; ?>">
    <div class="steps-container">
      <ul class="steps">
      <?php foreach ($wizard_steps as $i => $step_values) : ?>
        <?php if ($i < $display_max_step) : ?>
        <li data-step="<?php echo $i+1; ?>" data-name="cdbt-step-<?php echo $i+1; ?>"<?php if ($current_step === $i+1) echo ' class="active"'; ?>><span class="badge"><?php echo $i+1; ?></span><?php echo $step_values['label']; ?><span class="chevron"></span></li>
        <?php endif; ?>
      <?php endforeach; ?>
      </ul>
    </div>
    <div class="actions">
      <button type="button" class="btn btn-default btn-prev"><span class="glyphicon glyphicon-arrow-left"></span><?php _e('Prev', CDBT); ?></button>
      <button type="button" class="btn btn-default btn-next" data-last="Complete"><?php _e('Next', CDBT); ?><span class="glyphicon glyphicon-arrow-right"></span></button>
    </div>
    <div class="step-content">
  <?php endif; ?>
  <?php if ('after' === $render_part) : ?>
    </div><!-- /.step-content -->
  </div><!-- /.wizard -->
  <div class="clearfix"><div style="height: 2em;"></div></div>
<script>
var wizard = function() {
  var wizard = $('#<?php echo $wizard_id; ?>');
  wizard.wizard(
    'disablePreviousStep', <?php echo $disable_previous_step; ?>
  );
};
</script>
  <?php endif; ?>
<?php endif; ?>