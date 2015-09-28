<?php
/**
 * Modal Options array `$this->component_options` scheme
 * [
 * 'id' => @string is element id [optional] For default is `cdbtModal`
 * 'modalSize' => @string [optional] For default is null, or `large`, `small`
 * 'modalTitle' => @string [optional] 
 * 'modalBody' => @string [optional] Allowed HTML markup
 * 'modalFooter' => @array [optional] Array of HTML markup of the buttons that want to add to the footer
 * 'modalBackdrop' => @mixed [optional] Boolean or the string `static`, for default is true
 * 'modalKeyboard' => @boolean [optional] For default is true
 * 'modalShow' => @boolean [optional] For default is true
 * 'modalHideEvent' => @string [optional] Javascript is.
 * 'modalShowEvent' => @string [optional] Javascript is.
 * 'modalExtras' => @array [optional] Freely addition arguments for using when generating content in modal
 * ]
 */

/**
 * Parse options
 * ---------------------------------------------------------------------------
 */

// `id` section
if (isset($this->component_options['id']) && !empty($this->component_options['id'])) {
  $modal_id = esc_attr__($this->component_options['id']);
} else {
  $modal_id = 'cdbtModal';
}

// `modalSize` section
if (isset($this->component_options['modalSize']) && !empty($this->component_options['modalSize']) && in_array(strtolower($this->component_options['modalSize']), [ 'large', 'small' ])) {
  $modal_size = ('large' === strtolower($this->component_options['modalSize'])) ? 'modal-lg' : 'modal-sm';
} else {
  $modal_size = '';
}

// `modalTitle` section
if (isset($this->component_options['modalTitle']) && !empty($this->component_options['modalTitle'])) {
  $modal_title = esc_html__($this->component_options['modalTitle']);
} else {
  $modal_title = '';
}

// `modalBody` section
if (isset($this->component_options['modalBody']) && !empty($this->component_options['modalBody'])) {
  $modal_body = $this->component_options['modalBody'];
} else {
  $modal_body = '';
}

// `modalFooter` section
if (isset($this->component_options['modalFooter']) && is_array($this->component_options['modalFooter']) && !empty($this->component_options['modalFooter'])) {
  $modal_footer = implode("\n", $this->component_options['modalFooter']);
} else {
  $modal_footer = '';
}

// `modalBackdrop` section
if (isset($this->component_options['modalBackdrop']) && !empty($this->component_options['modalBackdrop'])) {
  if ('static' === $this->component_options['modalBackdrop']) {
    $modal_backdrop = $this->component_options['modalBackdrop'];
  } else {
    $modal_backdrop = $this->component_options['modalBackdrop'] ? 'true' : 'false';
  }
} else {
  $modal_backdrop = 'true';
}

// `modalKeyboard` section
if (isset($this->component_options['modalKeyboard']) && !empty($this->component_options['modalKeyboard'])) {
  $modal_keyboard = $this->component_options['modalKeyboard'] ? 'true' : 'false';
} else {
  $modal_keyboard = 'true';
}

// `modalShow` section
if (isset($this->component_options['modalShow']) && !empty($this->component_options['modalShow'])) {
  $modal_show = $this->component_options['modalShow'] ? 'true' : 'false';
} else {
  $modal_show = 'true';
}

// `modalHideEvent` section
if (isset($this->component_options['modalHideEvent']) && !empty($this->component_options['modalHideEvent'])) {
  $modal_hide_event = $this->component_options['modalHideEvent'];
} else {
  $modal_hide_event = 'return';
}

// `modalShowEvent` section
if (isset($this->component_options['modalShowEvent']) && !empty($this->component_options['modalShowEvent'])) {
  $modal_show_event = $this->component_options['modalShowEvent'];
} else {
  $modal_show_event = 'return;';
}


/**
 * Render the Modal
 * ---------------------------------------------------------------------------
 */
?>
<div class="modal fade cdbt-modal" id="<?php echo $modal_id; ?>" tabindex="-1" role="dialog" aria-labelledby="cdbtModalLabel" aria-hidden="true">
  <div class="modal-dialog<?php if (!empty($modal_size)) : ?> <?php echo $modal_size; ?><?php endif; ?>">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?php _e('Close', CDBT); ?>"><i class="fa fa-times" aria-hidden="true"></i></button>
        <?php if (!empty($modal_title)) : ?><h4 class="modal-title" id="cdbtModalLabel"><?php echo $modal_title; ?></h4><?php endif; ?>
      </div>
      <div class="modal-body">
        <?php echo do_shortcode($modal_body); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', CDBT); ?></button>
        <?php echo $modal_footer; ?>
      </div>
    </div>
  </div>
</div>
<script id="append-dynamic-modal">
var dynamic_modal = function(){
  $('#<?php echo $modal_id; ?>').modal({
    backdrop: <?php echo $modal_backdrop; ?>, 
    keyboard: <?php echo $modal_keyboard; ?>, 
    show: <?php echo $modal_show; ?>, 
  }).on('hidden.bs.modal', function(){
    <?php echo stripslashes_deep($modal_hide_event); ?>
  }).on('shown.bs.modal', function(){
    <?php echo stripslashes_deep($modal_show_event); ?>
  });
};
dynamic_modal();
</script>