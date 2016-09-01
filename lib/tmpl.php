<?php

namespace CustomDataBaseTables\Lib;


/**
 * Trait for creating dynamic any html templates
 *
 * @since 2.0.0
 *
 */
trait DynamicTemplate {
  
  private $template_file_path;
  
  private $component_options;
  
  /**
   * For this methods that render each template is dynamically generated as a closure.
   *
   * @since 2.0.0
   */
  public function set_template_file_path( $template_file_path ) {
    
    $this->template_file_path = $template_file_path;
    
  }
  
  /**
   * Dynamically method for rendering any components in html page
   *
   * @since 2.0.0
   */
  public function component_render( $component_name, $options=null ) {
    $template_file_name = sprintf('cdbt_%s.php', $component_name);
    
    $template_file_path = sprintf('%s%s/components/%s', $this->plugin_dir, $this->plugin_templates_dir, $template_file_name);
    
    if (!file_exists($template_file_path)) 
      return;
    
    $this->component_options = empty($options) ? [] : (array) $options;
    
    $component_render_method = 'render_' . $component_name;
    $this->set_template_file_path( apply_filters( 'include_template-' . $component_name, $template_file_path ) );
    // Define Dynamic Closure
    $this->$component_render_method = function(){ require( $this->template_file_path ); };
    $this->$component_render_method();
    
  }
  
  
  /**
   * Common Dynamic Entry Field Renderer
   * - Note is rendered specific entry fields only and those wrapping form is not rendered.
   * - Required libraries are the bootstrap and the FuelUX.
   *
   * @since 2.1.31
   *
   * @param array $field_option [required] Refer the following as an array structure:
   * [
   * 'elementName' => 		@value string [required] name as attribute of the field element
   * 'elementId' => 		@value string [optional] id as attribute of the field element
   * 'idPrefix' => 			@value string [optional] this prefix string is prepend of id
   * 'elementLabel' => 		@value string [optional] label name for field item
   * 'elementType' => 		@value string [required] enable only if an element is "input".
   * 'isRequired' => 		@value bool   [optional] default is false (Enable if an element type is "text", "search", "url", "tel", "email", "password", "datetime", "date", "month", "week", "time", "number", "checkbox", "radio", "file")
   * 'defaultValue' => 		@value string [optional] initial value; This is initial selected item or checked item if an element is select list or multiple checkbox.
   * 'placeholder' => 		@value string [optional] attribute of placeholder (enable only if an element type is "text", "search", "url", "tel", "email", "password")
   * 'addWrapClass' => 		@value string [optional] attribute of class in the block wrapping the field element
   * 'addClass' => 			@value string [optional] attribute of class if you want to add
   * 'selectableList' => 	@value string [optional/required] required only if an element is select list or multiple checkbox. e.g. "value1:label1,value2:label2,value3:label3,..."
   * 'horizontalList' => 	@value bool   [optional] default is false (enable only if an element is checkbox or radio)
   * 'noWrap' => 			@value bool   [optional] default is false (enable only if an element is hidden field)
   * 'labelSize' => 		@value int    [optional] default is 2 (actually class is "col-sm-2")
   * 'fieldSize' => 		@value int    [optional] default is 9 (actually class is "col-sm-9")
   * 'appendContent' => 	@value string [optional] 
   * 'helperText' => 		@value string [optional] Helper text is displayed at the bottom of the input form
   * 'elementExtras' => 	@value array  [optional] Freely addition attributes for using when generating content in input form; As follow is: 
   *   [
   *   'accept' => 				(anything) Enable only if an element type is "file".
   *   'autocomplete' => 		(on|off|default) Enable if an element type is "text", "search", "url", "tel", "email", "password", "datetime", "date", "month", "week", "time", "number", "range", "color".
   *   'list' => 				(anything) Enable if an element type is "text", "search", "url", "tel", "email", "datetime", "date", "month", "week", "time", "number", "range", "color"
   *   'max' => 				(integer) Enable if an element type is "datetime", "date", "month", "week", "time", "number", "range"
   *   'min' => 				(integer) Enable if an element type is "datetime", "date", "month", "week", "time", "number", "range"
   *   'step' => 				(integer) Enable if an element type is "datetime", "date", "month", "week", "time", "number", "range"
   *   'maxlength' => 			(integer) Enable if an element type is "text", "search", "url", "tel", "email", "password"
   *   'multiple' => 			() Enable if an element type is "email", "file"
   *   'pattern' => 			() Enable if an element type is "text", "search", "url", "tel", "email", "password"
   *   'size' => 				() Enable if an element type is "text", "search", "url", "tel", "email", "password"
   *   'rows' => 				(integer) Enable only if an element type is "textarea"
   *   'datetime' => 			(bool) Enable if an element type is "datetime", "date"
   *   'child-class' => 		(hash) Enable if an element type is multiple "checkbox"
   *   'data-moment-locale' => 	(string) l18n location name; cf. `en`, `fr`,... For default is `en`
   *   'data-moment-format' => 	(string) Date format for `moment.js`, for default is `L`
   *   'status' => 				(string) For displaying function status
   *   ]
   * ]
   */
  public function dynamic_field( $field_option=[] ) {
    if ( ! isset( $field_option ) || empty( $field_option ) || ! isset( $field_option['elementName'] ) || ! isset( $field_option['elementType'] ) ) {
      return false;
    }
    
    // Initialize field options
    $field_name = $field_option['elementName'];
    $field_id = isset( $field_option['elementId'] ) && ! empty( $field_option['elementId'] ) ? $field_option['elementId'] : $this->create_hash( $field_name );
    if ( isset( $field_option['idPrefix'] ) && ! empty( $field_option['idPrefix'] ) ) {
      $field_id = $field_option['idPrefix'] . $field_id;
    }
    $field_label = isset( $field_option['elementLabel'] ) && ! empty( $field_option['elementLabel'] ) ? $field_option['elementLabel'] : $field_name;
    $is_required = isset( $field_option['isRequired'] ) ? $this->strtobool( $field_option['isRequired'] ) : false;
    $label_required = '<span class="label label-required">'. __('Required', CDBT) .'</span>';
    $selectable_list = [];
    if ( isset( $field_option['selectableList'] ) && ! empty( $field_option['selectableList'] ) ) {
      $selectable_list = is_array( $field_option['selectableList'] ) ? $field_option['selectableList'] : $this->strtohash( $field_option['selectableList'] );
    }
    $label_size = isset( $field_option['labelSize'] ) && intval( $field_option['labelSize'] ) > 0 && intval( $field_option['labelSize'] ) < 12 ? intval( $field_option['labelSize'] ) : 2;
    $max_field_size = 12 - $label_size;
    $field_size = isset( $field_option['fieldSize'] ) && intval( $field_option['fieldSize'] ) > 0 && intval( $field_option['fieldSize'] ) < $max_field_size ? intval( $field_option['fieldSize'] ) : 9;
    //$field_option_size = empty( $field_option['elementSize'] ) || ! preg_match( '/^col-.*/iU', $field_option['elementSize'] ) ? 'col-sm-9' : esc_attr( $field_option['elementSize'] );
    $placeholder = empty( $field_option['placeholder'] ) ? sprintf( __('Please enter the %s', CDBT), strtolower( $field_label ) ) : esc_attr( $field_option['placeholder'] );
    $wrapper_classes = isset( $field_option['addWrapClass'] ) && ! empty( $field_option['addWrapClass'] ) ? trim( $field_option['addWrapClass'] ) : '';
    $add_classes = isset( $field_option['addClass'] ) && ! empty( $field_option['addClass'] ) ? trim( $field_option['addClass'] ) : '';
    $append_content = isset( $field_option['appendContent'] ) && ! empty( $field_option['appendContent'] ) ? trim( $field_option['appendContent'] ) : '';
    $input_attributes = [];
    if ( ! empty( $field_option['elementExtras'] ) ) {
      foreach( $field_option['elementExtras'] as $attr_name => $attr_value ) {
        if ( ! in_array( $attr_name, [ 'child-class', 'status' ] ) ) 
          $input_attributes[] = sprintf( '%s="%s"', esc_attr( $attr_name ), esc_attr( $attr_value ) );
      }
    }
    $add_attributes = implode( ' ', $input_attributes );
    $label_state = isset( $field_option['elementExtras']['status'] ) && ! empty( $field_option['elementExtras']['status'] ) ? $this->during_trial( $field_name, false ) : '';
    
    switch ( $field_option['elementType'] ) {
      case 'text': 
      case 'url': 
      case 'tel': 
      case 'email': 
      case 'password': 
      case 'number': 
      case 'range': 
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="<?php echo esc_attr( $field_id ); ?>" class="col-sm-<?php echo $label_size; ?> control-label"><?php echo $field_label; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-<?php echo $field_size; ?>">
        <input id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>]" type="<?php echo esc_attr( $field_option['elementType'] ); ?>" value="<?php echo esc_attr( $field_option['defaultValue'] ); ?>" class="form-control <?php echo esc_attr( $add_classes ); ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $add_attributes; ?><?php if ( $is_required ) { echo ' required'; } ?>>
      </div>
      <?php echo $append_content; ?>
    <?php if ( isset( $field_option['helperText'] ) && ! empty( $field_option['helperText'] ) ) : ?>
      <div class="col-sm-<?php echo $max_field_size; ?> col-sm-offset-<?php echo $label_size; ?>">
        <p class="help-block"><?php echo $field_option['helperText']; ?></p>
      </div>
    <?php endif; ?>
    </div><!-- /#<?php echo esc_attr( $field_id ); ?> -->
<?php
        break;
      case 'spinbox': 
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="<?php echo esc_attr( $field_id ); ?>" class="col-sm-<?php echo $label_size; ?> control-label"><?php echo $field_label; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-<?php echo $max_field_size; ?>">
        <div class="spinbox disits-<?php echo $field_size; ?> <?php echo esc_attr( $add_classes ); ?>" data-initialize="spinbox" id="<?php echo esc_attr( $field_id ); ?>">
          <input type="text" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>]" value="<?php echo esc_attr( $field_option['defaultValue'] ); ?>" class="form-control input-mini spinbox-input" placeholder="<?php echo $placeholder; ?>" <?php echo $add_attributes; ?><?php if ( $is_required ) { echo ' required'; } ?>>
          <div class="spinbox-buttons btn-group btn-group-vertical">
            <button type="button" class="btn btn-default spinbox-up btn-xs"><span class="glyphicon glyphicon-chevron-up"></span><span class="sr-only">Increase</span></button>
            <button type="button" class="btn btn-default spinbox-down btn-xs"><span class="glyphicon glyphicon-chevron-down"></span><span class="sr-only">Decrease</span></button>
          </div>
        </div>
      </div>
      <?php echo $append_content; ?>
    <?php if ( isset( $field_option['helperText'] ) && ! empty( $field_option['helperText'] ) ) : ?>
      <div class="col-sm-<?php echo $max_field_size; ?> col-sm-offset-<?php echo $label_size; ?>">
        <p class="help-block"><?php echo $field_option['helperText']; ?></p>
      </div>
    <?php endif; ?>
    </div><!-- /#<?php echo esc_attr( $field_id ); ?> -->
<?php
        break;
      case 'textarea': 
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="<?php echo esc_attr( $field_id ); ?>" class="col-sm-<?php echo $label_size; ?> control-label"><?php echo $field_label; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-<?php echo $field_size; ?>">
        <textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>]" class="form-control <?php echo esc_attr( $add_classes ); ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $add_attributes; ?><?php if ($is_required) { echo ' required'; } ?>><?php echo $field_option['defaultValue']; ?></textarea>
      </div>
      <?php echo $append_content; ?>
    <?php if ( isset( $field_option['helperText'] ) && ! empty( $field_option['helperText'] ) ) : ?>
      <div class="col-sm-<?php echo $max_field_size; ?> col-sm-offset-<?php echo $label_size; ?>">
        <p class="help-block"><?php echo $field_option['helperText']; ?></p>
      </div>
    <?php endif; ?>
    </div><!-- /#<?php echo esc_attr( $field_id ); ?> -->
<?php
        break;
      case 'combobox': 
        $placeholder = ! isset( $field_option['placeholder'] ) || empty( $field_option['placeholder'] ) ? __('Please choose or input', CDBT) : $field_option['placeholder'];
        $is_selected = in_array( $field_option['defaultValue'], $selectable_list );
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="<?php echo esc_attr( $field_id ); ?>" class="col-sm-<?php echo $label_size; ?> control-label"><?php echo $field_label; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-<?php echo $max_field_size; ?>">
        <div class="input-group input-append dropdown combobox col-sm-<?php echo $field_size; ?>" data-initialize="combobox" id="<?php echo esc_attr( $field_id ); ?>">
          <input type="text" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr($field_option['elementName']); ?>]"<?php if ( ! $is_selected ) : ?> value="<?php echo esc_attr($field_option['defaultValue']); ?>"<?php endif; ?> class="form-control text-center" placeholder="<?php echo $placeholder; ?>" <?php echo $add_attributes; ?><?php if ($is_required) { echo ' required'; } ?>>
          <div class="input-group-btn">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
            <ul class="dropdown-menu dropdown-menu-right">
            <?php foreach ( $selectable_list as $_key => $_val ) : ?>
              <li data-value="<?php echo esc_attr( $_key ); ?>"<?php if ( $is_selected && $field_option['defaultValue'] === $_val ) echo ' data-selected="true"'; ?>><a href="#"><?php echo esc_html( $_val ); ?></a></li>
            <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php echo $append_content; ?>
      <?php if ( isset( $field_option['helperText'] ) && ! empty( $field_option['helperText'] ) ) : ?><p class="help-block"><?php echo $field_option['helperText']; ?></p><?php endif; ?>
      </div>
    </div><!-- /#<?php echo esc_attr( $field_id ); ?> -->
<?php
        break;
      case 'select': 
        $is_selected = in_array( $field_option['defaultValue'], $selectable_list );
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="<?php echo esc_attr( $field_id ); ?>" class="col-sm-<?php echo $label_size; ?> control-label"><?php echo $field_label; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-<?php echo $max_field_size; ?>">
        <div class="btn-group selectlist <?php echo esc_attr($field_option['addClass']); ?>" data-resize="auto" data-initialize="selectlist" id="<?php echo esc_attr( $field_id ); ?>">
          <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
            <span class="selected-label"></span>
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu" role="menu">
          <?php foreach ( $selectable_list as $_key => $_val ) : ?>
            <li data-value="<?php echo esc_attr( $_key ); ?>"<?php if ( $is_selected && $field_option['defaultValue'] === $_val) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php echo esc_html( $_val ); ?></a></li>
          <?php endforeach; ?>
          </ul>
          <input class="hidden hidden-field" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr($field_option['elementName']); ?>]" readonly="readonly" aria-hidden="true" type="text"/>
        </div>
        <?php echo $append_content; ?>
      <?php if ( isset( $field_option['helperText'] ) && ! empty( $field_option['helperText'] ) ) : ?><p class="help-block"><?php echo $field_option['helperText']; ?></p><?php endif; ?>
      </div>
    </div><!-- /#<?php echo esc_attr( $field_id ); ?> -->
<?php
        break;
      case 'checkbox': 
        $index_num = 0;
        $is_horizontal = isset( $field_option['horizontalList'] ) && ! empty( $field_option['horizontalList'] ) ? $this->strtobool( $field_option['horizontalList'] ) : false;
        $default_values = $this->strtoarray( $field_option['defaultValue'] );
        $is_multiple = count( $selectable_list ) > 1 ? true : false;
        $child_classes = isset( $field_option['elementExtras']['child-class'] ) && ! empty( $field_option['elementExtras']['child-class'] ) ? $this->strtohash( $field_option['elementExtras']['child-class'] ) : []; // for not horizontal
        $add_classes = $is_required ? $add_classes . ' required' : $add_classes;
        $selectable_list = empty( $selectable_list ) ? [ __('Undefined', CDBT) => '' ] : $selectable_list;
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="<?php echo esc_attr( $field_id ); ?>" class="col-sm-<?php echo $label_size; ?> control-label"><?php echo $field_label; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-<?php echo $max_field_size; ?>">
      <?php foreach ( $selectable_list as $_key => $_val ) : $index_num++; ?>
        <?php if ( ! $is_horizontal ) : ?>
        <div class="checkbox <?php echo esc_attr( $add_classes ); ?> <?php if ( isset( $child_classes[$_key] ) ) echo trim( $child_classes[$_key] ); ?>" id="<?php echo esc_attr( $is_multiple ? $field_id . $index_num : $field_id ); ?>">
          <label class="checkbox-custom" data-initialize="checkbox">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>]<?php if ( $is_multiple ) : ?>[<?php echo esc_attr( $_key ); ?>]<?php endif; ?>" type="checkbox" value="1"<?php if ( is_array( $default_values ) && in_array( $_key, $default_values ) ) : ?> checked="checked"<?php endif; ?> <?php echo $add_attributes; ?>>
            <span class="checkbox-label"><?php echo $_val; ?></span>
          </label>
        </div>
        <?php else : ?>
        <label class="checkbox-custom checkbox-inline<?php if ( $is_multiple ) : ?> multiple<?php endif; ?> <?php echo esc_attr( $add_classes ); ?> <?php if ( isset( $child_classes[$_key] ) ) echo trim( $child_classes[$_key] ); ?>" data-initialize="checkbox" id="<?php echo esc_attr( $is_multiple ? $field_id . $index_num : $field_id ); ?>">
          <input class="sr-only" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>]<?php if ( $is_multiple ) : ?>[]<?php endif; ?>" type="checkbox" value="1"<?php if ( is_array( $default_values ) && in_array( $_key, $default_values ) ) : ?> checked="checked"<?php endif; ?> <?php echo $add_attributes; ?>>
          <span class="checkbox-label"><?php echo $_val; ?></span>
        </label>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php echo $append_content; ?>
      <?php if ( $is_multiple ) : ?><input type="hidden" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr($field_option['elementName']); ?>][checked]" value="0"><?php endif; ?>
      <?php if ( isset( $field_option['helperText'] ) && ! empty( $field_option['helperText'] ) ) : ?><p class="help-block"><?php echo $field_option['helperText']; ?></p><?php endif; ?>
      </div>
    </div><!-- /#<?php echo esc_attr( $field_id ); ?> -->
<?php
        unset( $index_num, $is_horizontal, $default_values );
        break;
      case 'radio': 
        $index_num = 0;
        $is_horizontal = isset( $field_option['horizontalList'] ) && ! empty( $field_option['horizontalList'] ) ? $this->strtobool( $field_option['horizontalList'] ) : false;
        $default_value= $field_option['defaultValue'];
        $child_classes = isset( $field_option['elementExtras']['child-class'] ) && ! empty( $field_option['elementExtras']['child-class'] ) ? $this->strtohash( $field_option['elementExtras']['child-class'] ) : []; // for not horizontal
        $add_classes = $is_required ? $add_classes . ' required' : $add_classes;
        $selectable_list = empty( $selectable_list ) ? [ __('Undefined', CDBT) => '' ] : $selectable_list;
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="<?php echo esc_attr( $field_id ); ?>" class="col-sm-<?php echo $label_size; ?> control-label"><?php echo $field_label; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-<?php echo $max_field_size; ?>">
      <?php foreach ( $selectable_list as $_key => $_val ) : $index_num++; ?>
        <?php if ( ! $is_horizontal ) : ?>
        <div class="radio<?php if ( $default_value === $_key ) echo ' checked'; ?><?php echo esc_attr( $add_classes ); ?> <?php if ( isset( $child_classes[$_key] ) ) echo trim( $child_classes[$_key] ); ?>" id="<?php echo esc_attr( $field_id ) . $index_num; ?>">
          <label class="radio-custom" data-initialize="radio">
            <input class="sr-only"<?php if ( $default_value === $_key ) : ?> checked="checked"<?php endif; ?> name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>][]" type="radio" value="<?php echo esc_attr( $_key ); ?>">
            <span class="radio-label"><?php echo $_val; ?></span>
          </label>
        </div>
        <?php else : ?>
        <label class="radio-custom radio-inline<?php if ( $default_value === $_key ) echo ' checked'; ?><?php echo esc_attr( $add_classes ); ?> <?php if ( isset( $child_classes[$_key] ) ) echo trim( $child_classes[$_key] ); ?>" data-initialize="radio" id="<?php echo esc_attr( $field_id ) . $index_num; ?>">
          <input class="sr-only"<?php if ( $default_value === $_key ) : ?> checked="checked"<?php endif; ?> name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>][]" type="radio" value="<?php echo esc_attr( $_key ); ?>">
          <span class="radio-label"><?php echo $_val; ?></span>
        </label>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php echo $append_content; ?>
      <?php if ( isset( $field_option['helperText'] ) && ! empty( $field_option['helperText'] ) ) : ?><p class="help-block"><?php echo $field_option['helperText']; ?></p><?php endif; ?>
      </div>
    </div><!-- /#<?php echo esc_attr( $field_id ); ?>> -->
<?php
        unset($index_num, $is_horizontal, $default_value);
        break;
      case 'boolean': 
        $checked = ($this->strtobool($field_option['defaultValue'])) ? ' checked="checked"' : '';
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="entry-data-<?php echo esc_attr($field_option['elementName']); ?>" class="col-sm-2 control-label"><?php echo $field_option['elementLabel']; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-10">
        <div class="checkbox <?php echo esc_attr($field_option['addClass']); ?>" id="entry-data-<?php echo esc_attr($field_option['elementName']); ?>">
          <label class="checkbox-custom" data-initialize="checkbox">
            <input class="sr-only" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr($field_option['elementName']); ?>]" type="checkbox" value="1"<?php echo $checked; ?> <?php echo $add_attributes; ?><?php if ($is_required) { echo ' required'; } ?>>
            <span class="checkbox-label"><?php if (isset($field_option['helperText']) && !empty($field_option['helperText'])) : ?><?php echo esc_html($field_option['helperText']); ?><?php else : ?><?php echo $field_option['elementLabel']; ?><?php endif; ?></span>
          </label>
        </div>
        <?php echo $append_content; ?>
      </div>
    </div><!-- /entry-data-<?php echo esc_attr($field_option['elementName']); ?> -->
<?php
        unset($checked);
        break;
      case 'file': 
        $is_fileupsize = isset($field_option['elementExtras']['maxlength']) && !empty($field_option['elementExtras']['maxlength']) ? true : false;
        if (!empty($field_option['defaultValue'])) {
          $_file_type = $this->check_binary_data($field_option['defaultValue']);
          $_binary_array = $this->esc_binary_data($field_option['defaultValue']);
          if ('image' === $_file_type) {
            $_image_src = sprintf( 'data:%s;base64, %s', $_binary_array['mime_type'], $_binary_array['bin_data'] );
            $add_field = sprintf( '<input class="hidden hidden-field" type="hidden" name="%s[%s-cache]" value="%s">', $this->domain_name, esc_attr($field_option['elementName']), $_binary_array['bin_data'] );
            $add_field .= sprintf( '<div class="current-image-thumbnail" style="display: inline-block;"><img src="%s" class="img-thumbnail" style="height: 64px;"> <small>%s (%s)</small></div>', $_image_src, rawurldecode($_binary_array['origin_file']), $this->convert_filesize($_binary_array['file_size']) );
          } else {
            $add_field = sprintf( '<input class="hidden hidden-field" type="hidden" name="%s[%s-cache]" value="%s">', $this->domain_name, esc_attr($field_option['elementName']), $_binary_array['bin_data'] );
            $icon_type = in_array($_file_type, ['audio', 'excel', 'movie', 'pdf', 'powerpoint', 'sound', 'text', 'video', 'word', 'zip' ]) ? $_file_type . '-o' : 'o';
            $add_field .= sprintf( '<div class="current-binary-filename pull-left" style="display: inline-block;"><i class="fa fa-file-%s"></i> <small>%s (%s)</small></div>', $icon_type, rawurldecode($_binary_array['origin_file']), $this->convert_filesize($_binary_array['file_size']) );
          }
        } else {
          $add_field = sprintf( '<input class="hidden hidden-field" type="hidden" name="%s[%s-cache]" value="%s">', $this->domain_name, esc_attr($field_option['elementName']), $field_option['defaultValue'] );
        }
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="entry-data-<?php echo esc_attr($field_option['elementName']); ?>" class="col-sm-2 control-label"><?php echo $field_option['elementLabel']; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-9">
        <input class="<?php echo esc_attr($field_option['addClass']); ?>" type="file" id="entry-data-<?php echo esc_attr($field_option['elementName']); ?>" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr($field_option['elementName']); ?>]"<?php if ($is_required) : ?> required<?php endif; ?>>
        <?php if ($is_fileupsize) : ?><p class="help-block"><?php printf(__('Notice: Maximum upload file size is %s.', CDBT), '<strong>'. $field_option['elementExtras']['maxlength'] .'</strong>'); ?></p><?php endif; ?>
      </div>
      <?php echo $append_content; ?>
      <div class="col-sm-offset-2 col-sm-9">
      <?php echo $add_field; ?>
      </div>
    <?php if (isset($field_option['helperText']) && !empty($field_option['helperText'])) : ?><p class="help-block"><?php echo $field_option['helperText']; ?></p><?php endif; ?>
    </div><!-- /entry-data-<?php echo esc_attr($field_option['elementName']); ?> -->
<?php
        unset($_file_type, $_binary_array, $add_field);
        break;
      case 'datetime': 
        $month_list = [
          [ 'fullname' => __('January', CDBT), 'aliase' => __('Jan', CDBT) ], 
          [ 'fullname' => __('February', CDBT), 'aliase' => __('Feb', CDBT) ], 
          [ 'fullname' => __('March', CDBT), 'aliase' => __('Mar', CDBT) ], 
          [ 'fullname' => __('April', CDBT), 'aliase' => __('Apr', CDBT) ], 
          [ 'fullname' => __('May', CDBT), 'aliase' => __('May', CDBT) ], 
          [ 'fullname' => __('June', CDBT), 'aliase' => __('Jun', CDBT) ], 
          [ 'fullname' => __('July', CDBT), 'aliase' => __('Jul', CDBT) ], 
          [ 'fullname' => __('August', CDBT), 'aliase' => __('Aug', CDBT) ], 
          [ 'fullname' => __('September', CDBT), 'aliase' => __('Sep', CDBT) ], 
          [ 'fullname' => __('October', CDBT), 'aliase' => __('Oct', CDBT) ], 
          [ 'fullname' => __('November', CDBT), 'aliase' => __('Nov', CDBT) ], 
          [ 'fullname' => __('December', CDBT), 'aliase' => __('Dec', CDBT) ], 
        ];
        $week_list = [
          [ 'fullname' => __('Sunday', CDBT), 'aliase' => __('Su', CDBT) ], 
          [ 'fullname' => __('Monday', CDBT), 'aliase' => __('Mo', CDBT) ], 
          [ 'fullname' => __('Tuesday', CDBT), 'aliase' => __('Tu', CDBT) ], 
          [ 'fullname' => __('Wednesday', CDBT), 'aliase' => __('We', CDBT) ], 
          [ 'fullname' => __('Thursday', CDBT), 'aliase' => __('Th', CDBT) ], 
          [ 'fullname' => __('Friday', CDBT), 'aliase' => __('Fr', CDBT) ], 
          [ 'fullname' => __('Saturday', CDBT), 'aliase' => __('Sa', CDBT) ], 
        ];
        if ( ! empty( $field_option['defaultValue'] ) ) {
          $_parse_vars = explode( ' ', $field_option['defaultValue'] );
          if ( array_key_exists( 1, $_parse_vars ) ) {
            $_time = $_parse_vars[1];
          }
          $_date = $_parse_vars[0];
          if ( '0000-00-00' !== $_date ) {
            list( $_year, $_month, $_day ) = explode( '-', $_date );
            $default_date = sprintf( '%s/%s/%s', $_month, $_day, $_year );
          } else {
            $default_date = date_i18n( 'm/d/Y' );
          }
        } else {
          $default_date = date_i18n( 'm/d/Y' );
        }
        
        if ( isset( $_time ) && ! empty( $_time ) ) {
          list( $_hour, $_minute, $_second ) = explode( ':', $_time );
        } else {
          $_hour = $_minute = $_second = 0;
        }
        if ( isset( $field_option['elementExtras']['datetime'] ) && $this->strtobool( $field_option['elementExtras']['datetime'] ) ) {
          $toggle_datetime = '';
        } else {
          $toggle_datetime = ' style="visibility: hidden;"';
        }
        $_on_timer = 'created' === $field_option['elementName'] ? true : false;
?>
    <div class="form-group <?php echo $wrapper_classes; ?>">
      <label for="entry-data-<?php echo esc_attr( $field_option['elementName'] ); ?>-date" class="col-sm-2 control-label"><?php echo $field_option['elementLabel']; ?><?php if ( $is_required ){ echo $label_required; } ?><?php echo $label_state; ?></label>
      <div class="col-sm-10">
        <div class="datepicker cdbt-datepicker" data-initialize="datepicker" id="entry-data-<?php echo esc_attr( $field_option['elementName'] ); ?>-date" <?php if (isset($default_date) && !empty($default_date)) : ?>data-date="<?php echo $default_date; ?>"<?php endif; ?> data-allow-past-dates="allowPastDates" <?php echo $add_attributes; ?>>
          <div class="input-group col-sm-3 pull-left">
            <input class="form-control text-center" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>][date]" type="text">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="glyphicon glyphicon-calendar"></span>
                <span class="sr-only"><?php _e('Toggle Calendar', CDBT); ?></span>
              </button>
              <div class="dropdown-menu dropdown-menu-right datepicker-calendar-wrapper" role="menu">
                <div class="datepicker-calendar">
                  <div class="datepicker-calendar-header">
                    <button type="button" class="prev"><span class="glyphicon glyphicon-chevron-left"></span><span class="sr-only"><?php _e('Previous Month', CDBT); ?></span></button>
                    <button type="button" class="next"><span class="glyphicon glyphicon-chevron-right"></span><span class="sr-only"><?php _e('Next Month', CDBT); ?></span></button>
                    <button type="button" class="title">
                      <span class="month">
                      <?php foreach ($month_list as $i => $month_data) : ?>
                        <span data-month="<?php echo $i; ?>"><?php echo $month_data['fullname']; ?></span>
                      <?php endforeach; ?>
                      </span>
                      <span class="year"></span>
                    </button>
                  </div>
                  <table class="datepicker-calendar-days">
                    <thead>
                      <tr>
                      <?php foreach ($week_list as $week_data) : ?>
                        <th><?php echo $week_data['aliase']; ?></th>
                      <?php endforeach; ?>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                  <div class="datepicker-calendar-footer">
                    <button type="button" class="datepicker-today"><?php _e('Today', CDBT); ?></button>
                  </div>
                </div>
                <div class="datepicker-wheels" aria-hidden="true">
                  <div class="datepicker-wheels-month">
                    <h2 class="header"><?php _e('Month', CDBT); ?></h2>
                    <ul>
                    <?php foreach ($month_list as $i => $month_data) : ?>
                      <li data-month="<?php echo $i; ?>"><button type="button"><?php echo $month_data['aliase']; ?></button></li>
                    <?php endforeach; ?>
                    </ul>
                  </div>
                  <div class="datepicker-wheels-year">
                    <h2 class="header"><?php _e('Year', CDBT); ?></h2>
                    <ul></ul>
                  </div>
                  <div class="datepicker-wheels-footer clearfix">
                    <button type="button" class="btn datepicker-wheels-back"><span class="glyphicon glyphicon-arrow-left"></span><span class="sr-only"><?php _e('Return to Calendar', CDBT); ?></span></button>
                    <button type="button" class="btn datepicker-wheels-select"><?php _e('Select', CDBT); ?> <span class="sr-only"><?php _e('Month and Year', CDBT); ?></span></button>
                  </div>
                </div>
              </div>
            </div>
          </div><!-- /date-picker -->
        </div>
        <div class="clock-mark pull-left"<?php echo $toggle_datetime; ?>><span class="glyphicon glyphicon-time text-muted"></span></div>
        <div class="col-sm-2 pull-left datepicker-combobox-hour"<?php if ( $_on_timer ) : ?> data-on-timer="true"<?php endif; ?><?php echo $toggle_datetime; ?>>
          <div class="input-group input-append dropdown combobox" data-initialize="combobox">
            <input type="text" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>][hour]" id="entry-data-<?php echo esc_attr( $field_option['elementName'] ); ?>-hour" value="<?php echo $_hour; ?>" class="form-control text-center" pattern="^[0-9]+$">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php for ($hour=0; $hour<24; $hour++) : ?>
                <li data-value="<?php printf('%02d', $hour); ?>"<?php if ($hour === intval($_hour)) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php printf('%02d', $hour); ?></a></li>
              <?php endfor; ?>
              </ul>
            </div>
          </div><!-- /hour-combobox -->
        </div>
        <p class="time-separater-block pull-left"<?php echo $toggle_datetime; ?>><b class="time-separater text-muted">:</b></p>
        <div class="col-sm-2 pull-left datepicker-combobox-minute"<?php if ( $_on_timer ) : ?> data-on-timer="true"<?php endif; ?><?php echo $toggle_datetime; ?>>
          <div class="input-group input-append dropdown combobox" data-initialize="combobox">
            <input type="text" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>][minute]" id="entry-data-<?php echo esc_attr( $field_option['elementName'] ); ?>-minute" value="<?php echo $_minute; ?>" class="form-control text-center" pattern="^[0-9]+$">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php for ($minute=0; $minute<60; $minute++) : ?>
                <li data-value="<?php printf('%02d', $minute); ?>"<?php if ($minute === intval($_minute)) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php printf('%02d', $minute); ?></a></li>
              <?php endfor; ?>
              </ul>
            </div>
          </div><!-- /minute-combobox -->
        </div>
        <p class="time-separater-block pull-left"<?php echo $toggle_datetime; ?>><b class="time-separater text-muted">:</b></p>
        <div class="col-sm-2 pull-left datepicker-combobox-second"<?php if ( $_on_timer ) : ?> data-on-timer="true"<?php endif; ?><?php echo $toggle_datetime; ?>>
          <div class="input-group input-append dropdown combobox" data-initialize="combobox">
            <input type="text" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>][second]" id="entry-data-<?php echo esc_attr( $field_option['elementName'] ); ?>-second" value="<?php echo $_second; ?>" class="form-control text-center" pattern="^[0-9]+$">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
              <ul class="dropdown-menu dropdown-menu-right">
              <?php for ($second=0; $second<60; $second++) : ?>
                <li data-value="<?php printf('%02d', $second); ?>"<?php if ($second === intval($_second)) : ?> data-selected="true"<?php endif; ?>><a href="#"><?php printf('%02d', $second); ?></a></li>
              <?php endfor; ?>
              </ul>
            </div>
          </div><!-- /second-combobox -->
        </div>
      </div>
      <?php echo $append_content; ?>
      <input type="hidden" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>][prev_date]" value="<?php echo esc_attr( $field_option['defaultValue'] ); ?>">
    </div><!-- /entry-data-<?php echo esc_attr($field_option['elementName']); ?> -->
<?php
        unset($default_date, $_time, $_hour, $_minute, $_second);
        break;
      case 'hidden':
        $no_wrap = isset( $field_option['noWrap'] ) && ! empty( $field_option['noWrap'] ) ? $this->strtobool( $field_option['noWrap'] ) : false;
?>
    <?php if ( ! $no_wrap ) : ?><div class="from-group <?php echo $wrapper_classes; ?>"><?php endif; ?>
      <input id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo $this->domain_name; ?>[<?php echo esc_attr( $field_option['elementName'] ); ?>]" type="hidden" value="<?php echo esc_attr( $field_option['defaultValue'] ); ?>" class="form-control <?php echo esc_attr( $add_classes ); ?>" <?php echo $add_attributes; ?>><!-- /#<?php echo esc_attr( $field_id ); ?> -->
    <?php if ( ! $no_wrap ) : ?></div><?php endif; ?>
<?php
        break;
      default: 
//echo print_r( $field_option );
        break;
    }
    
    
  }
  
}
