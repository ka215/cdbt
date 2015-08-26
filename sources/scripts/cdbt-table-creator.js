/*!
 * Custom DataBase Tables v2.0.0 (http://ka2.org)
 * Table Creator v2.0.0
 * Copyright 2014-2015 ka2@ka2.org
 * Licensed under GPLv2 (http://www.gnu.org/licenses/gpl.txt)
 */
if (typeof doTableCreator !== 'undefined') {
  doTableCreator = null;
}
doTableCreator = function(){
  
  var adjustModal = function( firstest ){
    
    //console.info([ $(window).height(), $('#wpadminbar').height(), $('#cdbtTableCreator div.modal-header').height(), $('#cdbtTableCreator div.modal-footer').height() ]);
    var duration_time = 40;
    var widen_height = $(window).height() - ($('#wpadminbar').height() + Math.max($('#cdbtTableCreator div.modal-header').height(), 56) + Math.max($('#cdbtTableCreator div.modal-footer').height(), 65));
    if (firstest) {
      $('#adminmenuwrap').hide();
      $('#cdbtTableCreator div.modal-dialog').css({ position: 'fixed', zIndex: 9999 }).animate({ width: '100%' }, { duration: duration_time, easing: 'swing', queue: true });
      $('#cdbtTableCreator div.modal-body').css({ overflow: 'auto' }).animate({ height: widen_height + 'px' }, { duration: duration_time, easing: 'swing', queue: false });
    } else {
      $('#cdbtTableCreator div.modal-body').css('height', widen_height + 'px');
    }
    
  };
  
  
  var initComponent = function(){
    // Constractor
    $('#cdbtModalLabel').prepend('<i class="fa fa-table"></i> ');
    adjustModal( true );
    
    $('#sortable').sortable({
      items: 'tr:not(.ui-state-disabled)', 
      placeholder: 'ui-state-highlight', 
    }).css({ position: 'relative' });
    $('#sortable').disableSelection();
    
    //console.info(cdbt_admin_vars.column_types);
    
  };
  
  
  $(window).resize(function(){
    adjustModal( false );
  });
  initComponent();
  
  
  /**
   * Event handlers on the "Table Creator"
   * -------------------------------------------------------------------------
   */
  
  
  // Checking whether selected type is allowed type.
  var isAllowedType = function( type_str ) {
    var isMatch = false;
    var fixedType;
    for (var type in cdbt_admin_vars.column_types) {
      if (type_str === type || _.contains(cdbt_admin_vars.column_types[type].alias, type_str)) {
        isMatch = true;
        fixedType = type;
        break;
      }
    }
    return isMatch ? fixedType : isMatch;
  };
  
  
  // Renumbering the reference number.
  var renumberRowIndex = function(){
    var cnt = 0;
    $('#sortable').children('tr').each(function(){
      if (!$(this).hasClass('preset')) {
        cnt++;
        $(this).children('td.handler').html('<strong>'+cnt+'<strong>');
      }
    });
  };
  
  
  // Attached popover for column type of `enum` or `set`
  // Firstly, initialize popover
  $(document).popover({ 
    selector: '.open_pillbox_', 
    html: true, 
    content: function(){ 
      var newPillbox = $('#cdbt_tc_preset_define_values_template').clone();
      var currentRow = $(this).parent().parent().parent('tr');
      var currentRowId = currentRow.hasClass('addnew') ? currentRow.data('id') : '';
      var currentInputName = newPillbox.find('input').attr('name');
      newPillbox.find('input').attr('name', currentInputName + currentRowId);
      return newPillbox.html();
    }, 
    template: '<div class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div><div class="popover-footer"><button type="button" name="set_values_" class="btn btn-primary btn-sm" disabled="disabled">'+ cdbt_admin_vars.cdbt_tc_translate.popoverSetValues +'</button></div></div>', 
    //trigger: 'manual', 
  });
  // Clear popover
  var clearPopover = function(){
    // $('.open_pillbox_').popover('hide');
    var excludeId = arguments.length > 0 ? arguments[0] : '';
    $('.cdbt_tc_define_values').each(function(){
      var currentRowId = $(this).parent().parent('tr').hasClass('addnew') ? $(this).parent().parent('tr').data('id') : 'preset';
      var popover_debris = $(this).find('.popover');
      if (!popover_debris.hasClass('in')) {
        var debris_id = popover_debris.attr('id');
        popover_debris.remove();
        //console.info(debris_id);
        if (typeof $(this).children('.open_pillbox_').attr('aria-describedby') !== 'undefined' && $(this).children('.open_pillbox_').attr('aria-describedby') === debris_id) {
          $(this).children('.open_pillbox_').removeAttr('aria-describedby');
        }
      } else {
        //console.info([ currentRowId, excludeId ]);
        if (currentRowId !== excludeId) {
          $(this).children('.open_pillbox_').trigger('click');
        }
      }
    });
  };
  $(document).on('click', 'body', function(e){
    var activeRowId = '';
    if ($('.popover').size() > 0) {
      $('.popover').each(function(){
        if ($(this).hasClass('in')) {
          activeRowId = $(this).parent().parent().parent('tr').hasClass('addnew') ? $(this).parent().parent().parent('tr').data('id') : 'preset';
        }
      });
    }
    $('.open_pillbox_').each(function(){
      var currentRowId = $(this).parent().parent().parent('tr').hasClass('addnew') ? $(this).parent().parent().parent('tr').data('id') : 'preset';
      if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
        clearPopover();
      } else {
        clearPopover(activeRowId);
      }
    });
  });
  // Event handler for popover
  $(document).on('click', '.open_pillbox_', function(e){
    e.stopPropagation();
    e.preventDefault();
  }).on('shown.bs.popover', '.open_pillbox_', function(e){
    var currentRowId = $(this).parent().parent().parent('tr').hasClass('addnew') ? $(this).parent().parent().parent('tr').data('id') : 'preset';
    clearPopover(currentRowId);
    //$('.open_pillbox_').not(this).popover('hide');
    // console.info(['shown', e]);
  }).on('hidden.bs.popover', '.open_pillbox_', function(e){
    // console.info(['hidden', e]);
  }).on('inserted.bs.popover', '.open_pillbox_', function(e){
    var currentRow = $(this).parent().parent().parent('tr');
    var currentRowId = currentRow.hasClass('addnew') ? currentRow.data('id') : '';
    //console.info([ currentRow, currentRowId ]);
    var currentPopoverId = $(this).attr('aria-describedby'), currentPopover;
    $('.popover').each(function(){
      if ($(this).attr('id') === currentPopoverId) {
        currentPopover = $(this);
      }
    });
    if (typeof currentPopover !== 'undefined' && _.isObject(currentPopover)) {
      var popoverButton = currentPopover.find('div.popover-footer>button');
      //console.info([ currentPopover, popoverButton ]);
      popoverButton.attr('name', 'set_values_' + currentRowId);
      
    }
    // load current item from cache
    var _temp = $('input[name=define_values_cache_'+currentRowId+']').val();
    var loadedItems = _temp.split(',');
    //console.info([_temp,loadedItems]);
    if (_.isArray(loadedItems) && loadedItems.length > 0 && loadedItems[0] !== '') {
      _.each(loadedItems, function(v,k){
        return currentPopover.find('.pillbox').pillbox('addItems', k+1, [{ text: v, value: v }]);
      });
    }
  });
  
  
  // For Pillbox
  // Toggle "Set Values" button
  var toggleSetValues = function( items, target_id ){
    var flag = items > 0 ? false : true;
    $('button[name=set_values_'+target_id+']').prop('disabled', flag);
  };
  $(document).on('clicked.fu.pillbox', '.pillbox', function(e, item){
    //console.info([ 'clicked', e, item ]);
    var targetId = $(this).find('input[name^=define_values_]').attr('name').replace('define_values_', '');
    var items = $(this).pillbox('itemCount');
    toggleSetValues(items, targetId);
  }).on('added.fu.pillbox', '.pillbox', function(e, item){
    //console.info([ 'added', e, item ]);
    var targetId = $(this).find('input[name^=define_values_]').attr('name').replace('define_values_', '');
    var items = $(this).pillbox('itemCount');
    toggleSetValues(items, targetId);
  }).on('removed.fu.pillbox', '.pillbox', function(e, item){
    //console.info([ 'removed', e, item ]);
    var targetId = $(this).find('input[name^=define_values_]').attr('name').replace('define_values_', '');
    var items = $(this).pillbox('itemCount');
    toggleSetValues(items, targetId);
  }).on('edited.fu.pillbox', '.pillbox', function(e, item){
    //console.info([ 'edited', e, item ]);
    var targetId = $(this).find('input[name^=define_values_]').attr('name').replace('define_values_', '');
    var items = $(this).pillbox('itemCount');
    toggleSetValues(items, targetId);
  });
  // This event will fire when clicked "Set Values" Button
  $(document).on('click', '[name^=set_values_]', function(e){
    var targetId = $(this).attr('name').replace('set_values_', ''), itemCount, items;
    $('.pillbox').each(function(){
      if ($(this).find('input[name^=define_values_]').attr('name') === 'define_values_' + targetId) {
        itemCount = $(this).pillbox('itemCount');
        items = $(this).pillbox('items');
        if (itemCount > 0) {
          var items_ary = _.pluck(items, 'value');
          $('input[name=define_values_cache_'+targetId+']').val(items_ary.join(','));
          
          $(this).parent().parent().parent('.cdbt_tc_define_values').children('.open_pillbox_').trigger('click');
          return;
        }
      }
    });
    return false;
  });
  
  
  // This event will fire when clicked "Add New Column" button.
  $('.cdbt_tc_preset_controll button[name=add-column]').on('click', function(){
    // Clear preset popover
    clearPopover();
    var newRow = $('tr.preset').clone();
    
    var addNum = $('#sortable').children('tr').length;
    newRow.removeClass('preset').addClass('addnew').attr('data-id', addNum);
    newRow.children('td').each(function(){
      if ($(this).hasClass('handler')) {
        $(this).html('<strong>'+ addNum +'</strong>');
      } else {
        $(this).find('input').each(function(){
          var item_name = $(this).attr('name');
          $(this).removeAttr('id').attr('name', item_name + addNum);
        });
      }
      if ($(this).hasClass('auto_increment')) {
        $(this).find('.checkbox-custom').removeClass('checked');
        $(this).find('input').prop('checked', false);
      }
      if ($(this).hasClass('controll')) {
        $(this).children('div').removeClass('cdbt_tc_preset_controll').addClass('cdbt_tc_controll').attr('data-id', addNum);
      }
    });
    
    newRow.insertBefore('tr.preset');
    renumberRowIndex();
  });
  
  
  // This event will fire when clicked "Remove Column" button.
  $(document).on('click', '.cdbt_tc_controll button[name=delete-column]', function(){
    clearPopover();
    $(this).parent().parent().parent('tr.addnew').fadeOut('fast', function(){
      $(this).remove();
      
      renumberRowIndex();
    });
  });
  
  
  // This event will fire when drag sort list.
  $(document).on('sortstart', '#sortable', function(e, ui){
    clearPopover();
  });
  
  
  // This event will fire when dropped sort list.
  $(document).on('sortout', '#sortable', function(e, ui){
    //console.info([e, ui]);
    renumberRowIndex();
  });
  
  
  // Toggle "Sizing/Define Values" cell
  var switchingSizingCell = function( selectedItem, targetRowId ) {
    
    var columnDefine = cdbt_admin_vars.column_types[selectedItem];
    var targetRow = 'preset' === targetRowId ? $('tr.preset>td.length') : $('tr[data-id='+ targetRowId +']>td.length');
    var displayContent = '';
    //console.info(columnDefine);
    if (_.isArray(columnDefine.arg_type)) {
      $('.length').show();
      if ('scale' === columnDefine.arg_type[1]) {
        displayContent = 'cdbt_tc_precision_scale';
        targetRow.children('.' + displayContent).find('input[name^=precision_scale_m_]').attr('min', columnDefine.min[0]).attr('max', columnDefine.max[0]).val(columnDefine.default[0]);
        targetRow.children('.' + displayContent).find('input[name^=precision_scale_d_]').attr('min', columnDefine.min[1]).attr('max', columnDefine.max[1]).val(columnDefine.default[1]);
      } else {
        displayContent = 'cdbt_tc_precision';
        targetRow.children('.' + displayContent).find('input[name^=precision_]').attr('min', columnDefine.min).attr('max', columnDefine.max).attr('pattern', '^['+columnDefine.arg_type.join('|')+']$').val(columnDefine.default);
      }
      targetRow.parent('tr').attr('data-sizing-cell', 'on');
    } else
    if (_.contains([ 'precision', 'maxlength', 'array' ], columnDefine.arg_type)) {
      $('.length').show();
      if ('precision' === columnDefine.arg_type) {
        displayContent = 'cdbt_tc_' + columnDefine.arg_type;
        targetRow.children('.' + displayContent).find('input[name^=precision_]').attr('min', columnDefine.min).attr('max', columnDefine.max).val(columnDefine.default);
      }
      if ('maxlength' === columnDefine.arg_type) {
        displayContent = 'cdbt_tc_' + columnDefine.arg_type.replace('max', '');
        targetRow.children('.' + displayContent).find('input[name^=length_]').attr('min', columnDefine.min).attr('max', columnDefine.max).val(columnDefine.default);
      }
      if ('array' === columnDefine.arg_type) {
        displayContent = 'cdbt_tc_define_values';
      }
      targetRow.parent('tr').attr('data-sizing-cell', 'on');
    } else {
      targetRow.parent('tr').removeAttr('data-sizing-cell');
      if ($('[data-sizing-cell=on]').size() === 0) {
        $('.length').hide();
      }
    }
    targetRow.children('div').each(function(){
      if (displayContent !== '' && $(this).hasClass(displayContent)) {
        $(this).css({ display: 'table' });
      } else {
        $(this).css({ display: 'none' });
      }
    });
    
  };
  
  
  // Toggle "Attributes" cell
  var switchingAttributesCell = function( selectedItem, targetRowId ) {
    
    var columnDefine = cdbt_admin_vars.column_types[selectedItem];
    var targetRow = 'preset' === targetRowId ? $('tr.preset>td.attributes') : $('tr[data-id='+ targetRowId +']>td.attributes');
    //console.info(columnDefine.atts);
    if (columnDefine.atts.length > 0) {
      $('.attributes').show();
      targetRow.find('input').val('');
      targetRow.find('li').each(function(){
        if (_.contains(columnDefine.atts, $(this).data('value'))) {
          $(this).css({ display: 'block' });
        } else {
          $(this).css({ display: 'none' });
        }
      });
      targetRow.children('.cdbt_tc_attributes').css({ display: 'table' });
      targetRow.parent('tr').attr('data-attributes-cell', 'on');
    } else {
      targetRow.children('.cdbt_tc_attributes').css({ display: 'none' });
      targetRow.parent('tr').removeAttr('data-attributes-cell');
      if ($('[data-attributes-cell=on]').size() === 0) {
        $('.attributes').hide();
      }
    }
    
  };
  
  
  // Toggle "Auto Incr." cell
  var switchingAutoincrCell = function( selectedItem, targetRowId ) {
    
    var targetRow = 'preset' === targetRowId ? $('tr.preset>td.auto_increment') : $('tr[data-id='+ targetRowId +']>td.auto_increment');
    if (selectedItem.indexOf('int') !== -1) {
      $('.auto_increment').show();
      targetRow.children('.cdbt_tc_auto_increment').css({ display: 'table' });
      targetRow.parent('tr').attr('data-autoincr-cell', 'on');
    } else {
      targetRow.children('.cdbt_tc_auto_increment').css({ display: 'none' });
      targetRow.parent('tr').removeAttr('data-autoincr-cell');
      if ($('[data-autoincr-cell=on]').size() === 0) {
        $('.auto_increment').hide();
      }
    }
    
  };
  
  
  // Toggle "Extra" cell
  var switchingExtraCell = function( selectedItem, targetRowId ) {
    
    var targetRow = 'preset' === targetRowId ? $('tr.preset>td.extra') : $('tr[data-id='+ targetRowId +']>td.extra');
    if ('timestamp' === selectedItem) {
      $('.extra').show();
      targetRow.find('.cdbt_tc_extra').css({ display: 'block' });
      targetRow.parent('tr').attr('data-extra-cell', 'on');
    } else {
      targetRow.find('.cdbt_tc_extra').css({ display: 'none' });
      targetRow.parent('tr').removeAttr('data-extra-cell');
      if ($('[data-extra-cell=on]').size() === 0) {
        $('.extra').hide();
      }
    }
  };
  
  
  // This event will fire when changed "Type Format" combobox.
  $(document).on('changed.fu.combobox', '.type_format .combobox', function (e, item) {
    var fixedType = isAllowedType(item.text);
    if (item.text.length < 3 || !fixedType) {
      return false;
    }
    var selectedItem = fixedType;
    var targetRowId = $(this).parent().parent('tr').hasClass('addnew') ? $(this).parent().parent('tr').data('id') : 'preset';
    switchingSizingCell( selectedItem, targetRowId );
    switchingAttributesCell( selectedItem, targetRowId );
    switchingAutoincrCell( selectedItem, targetRowId );
    switchingExtraCell( selectedItem, targetRowId );
  });
  
  
  // Validating column definition when enabled auto increment
  var validateAutoIncr = function( targetRowId ) {
    
    $('#sortable').children('tr.addnew').each(function(){
      if ($(this).data('id') === targetRowId) {
        $(this).children('td.default').find('input').prop('disabled', true).val('');
      } else {
        $(this).children('td.default').find('input').prop('disabled', false);
        $(this).children('td.auto_increment').find('.checkbox-custom').removeClass('checked');
        $(this).children('td.auto_increment').find('input').prop('checked', false);
      }
    });
    
  };
  
  
  // This event will fire when checked "Auto Incr." checkbox.
  $(document).on('checked.fu.checkbox', '.auto_increment .checkbox', function (e) {
    var targetRowId = $(this).parent().parent('tr').hasClass('addnew') ? $(this).parent().parent('tr').data('id') : 'preset';
    if ('preset' !== targetRowId) {
      validateAutoIncr(targetRowId);
    }
  });
  
  // This event will fire when unchecked "Auto Incr." checkbox.
  $(document).on('unchecked.fu.checkbox', '.auto_increment .checkbox', function (e) {
    var targetRow = $(this).parent().parent('tr').hasClass('addnew') ? $(this).parent().parent('tr') : 'preset';
    if ('preset' !== targetRow) {
      targetRow.children('td.default').find('input').prop('disabled', false);
    }
  });
  
  
  // This event will fire when clicked "Reset" button.
  $('#reset_sql').on('click', function(){
    
    $('#sortable').sortable('cancel');
    $('#sortable>tr.addnew').remove();
    
  });
  
  
  // This event will fire when clicked "Apply SQL" button.
  $('#apply_sql').on('click', function(){
    
    var rawData = [], oneColumn;
    $('#sortable').children('tr.addnew').each(function(){
      oneColumn = {};
      $(this).find('input').each(function(){
        var key = $(this).attr('name').replace($(this).attr('name').substr($(this).attr('name').lastIndexOf('_')), '');
        oneColumn[key] = _.contains([ 'checkbox', 'radio' ], $(this).attr('type')) ? $(this).prop('checked') : $(this).val();
      });
      if (typeof oneColumn.col_name !== 'undefined' && '' !== oneColumn.col_name) {
        rawData.push(oneColumn);
      }
    });
    console.info(rawData);
    var sql = 'Column Definitions';
    
    // var sql = generateSQL(rawData);
    $('#instance_create_table_sql').prop('disabled', false).val(sql);
    
    $('#cdbtTableCreator').modal('hide');
    
  });
  
};