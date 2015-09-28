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
    // for Firefox
    $(document).on('click.sortable mousedown.sortable selectstart.sortable input.sortable', '#sortable input', function(e){
      e.target.focus();
    });
    $('#sortable input').on('mousedown.ui-disableSelection selectstart.ui-disableSelection', function(e){
      e.stopImmediatePropagation();
    });
    
    var restoredCache = JSON.parse(localStorage.getItem('cdbt-tc-cache'));
    if (restoredCache !== null && _.isArray(restoredCache) && restoredCache.length > 0) {
      // Load column definitions from the saved cache
      loadColumns( restoredCache );
    }
    
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
  function isAllowedType( type_str ) {
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
  }
  
  
  // Renumbering the reference number.
  function renumberRowIndex(){
    var cnt = 0;
    $('#sortable').children('tr').each(function(){
      if (!$(this).hasClass('preset')) {
        cnt++;
        $(this).children('td.handler').html('<strong>'+cnt+'<strong>');
      }
    });
  }
  
  
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
    //console.info([_temp, loadedItems]);
    if (_.isArray(loadedItems) && loadedItems.length > 0 && loadedItems[0] !== '' && currentPopover.find('.pillbox').pillbox('itemCount') === 0) {
      _.each(loadedItems, function(v,k){
        return currentPopover.find('.pillbox').pillbox('addItems', k+1, [{ text: v, value: v }]);
      });
      currentPopover.find('.popover-footer button').prop('disabled', false);
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
  
  
  // Insert new row to the specific position
  function insertNewRow( insertPosition ){
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
    
    if ('after' === insertPosition) {
      newRow.insertAfter('tr.preset');
    } else {
      newRow.insertBefore('tr.preset');
    }
    renumberRowIndex();
  }
  
  
  // This event will fire when clicked "Add New Column" button.
  $('.cdbt_tc_preset_controll button[name=add-column]').on('click', function(){
    // Clear preset popover
    clearPopover();
    
    // Insert new row
    var insertPosition = 'before';
    insertNewRow( insertPosition );
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
  function switchingSizingCell( selectedItem, targetRowId ) {
    
    var columnDefine = cdbt_admin_vars.column_types[selectedItem];
    var targetRow = 'preset' === targetRowId ? $('tr.preset>td.length') : $('tr[data-id='+ targetRowId +']>td.length');
    var displayContent = '', targetElement, currentDefault;
    //console.info(columnDefine);
    if (_.isArray(columnDefine.arg_type)) {
      $('.length').show();
      if ('scale' === columnDefine.arg_type[1]) {
        displayContent = 'cdbt_tc_precision_scale';
        targetElement = targetRow.children('.' + displayContent).find('input[name^=precision_scale_m_]');
        currentDefault = targetElement.val() !== '' ? targetElement.val() : columnDefine.default[0];
        //targetRow.children('.' + displayContent).find('input[name^=precision_scale_m_]').attr('min', columnDefine.min[0]).attr('max', columnDefine.max[0]).val();
        targetElement.attr('min', columnDefine.min[0]).attr('max', columnDefine.max[0]).val(currentDefault);
        
        targetElement = targetRow.children('.' + displayContent).find('input[name^=precision_scale_d_]');
        currentDefault = targetElement.val() !== '' ? targetElement.val() : columnDefine.default[1];
        //targetRow.children('.' + displayContent).find('input[name^=precision_scale_d_]').attr('min', columnDefine.min[1]).attr('max', columnDefine.max[1]).val(columnDefine.default[1]);
        targetElement.attr('min', columnDefine.min[1]).attr('max', columnDefine.max[1]).val(currentDefault);
      } else {
        displayContent = 'cdbt_tc_precision';
        targetElement = targetRow.children('.' + displayContent).find('input[name^=precision_]');
        currentDefault = targetElement.val() !== '' ? targetElement.val() : columnDefine.default;
        //targetRow.children('.' + displayContent).find('input[name^=precision_]').attr('min', columnDefine.min).attr('max', columnDefine.max).attr('pattern', '^['+columnDefine.arg_type.join('|')+']$').val(columnDefine.default);
        targetElement.attr('min', columnDefine.min).attr('max', columnDefine.max).attr('pattern', '^['+columnDefine.arg_type.join('|')+']$').val(currentDefault);
      }
      targetRow.parent('tr').attr('data-sizing-cell', 'on');
    } else
    if (_.contains([ 'precision', 'maxlength', 'array' ], columnDefine.arg_type)) {
      $('.length').show();
      if ('precision' === columnDefine.arg_type) {
        displayContent = 'cdbt_tc_' + columnDefine.arg_type;
        targetElement = targetRow.children('.' + displayContent).find('input[name^=precision_]');
        currentDefault = targetElement.val() !== '' ? targetElement.val() : columnDefine.default;
        //targetRow.children('.' + displayContent).find('input[name^=precision_]').attr('min', columnDefine.min).attr('max', columnDefine.max).val(columnDefine.default);
        targetElement.attr('min', columnDefine.min).attr('max', columnDefine.max).val(currentDefault);
      }
      if ('maxlength' === columnDefine.arg_type) {
        displayContent = 'cdbt_tc_' + columnDefine.arg_type.replace('max', '');
        targetElement = targetRow.children('.' + displayContent).find('input[name^=length_]');
        currentDefault = targetElement.val() !== '' ? targetElement.val() : columnDefine.default;
        //targetRow.children('.' + displayContent).find('input[name^=length_]').attr('min', columnDefine.min).attr('max', columnDefine.max).val(columnDefine.default);
        targetElement.attr('min', columnDefine.min).attr('max', columnDefine.max).val(currentDefault);
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
    
  }
  
  
  // Toggle "Default Value" cell
  function switchingDefaultCell( selectedItem, targetRowId ) {
    
    var targetRow = 'preset' === targetRowId ? $('tr.preset>td.default') : $('tr[data-id='+ targetRowId +']>td.default');
    if (!_.contains([ 'tinytext', 'text', 'mediumtext', 'longtext', 'tinyblob', 'blob', 'mediumblob', 'longblob', 'set' ], selectedItem)) {
      $('.default').show();
      targetRow.find('.cdbt_tc_default').css({ display: 'table' });
      targetRow.parent('tr').attr('data-default-cell', 'on');
    } else {
      targetRow.find('.cdbt_tc_default').css({ display: 'none' });
      targetRow.parent('tr').removeAttr('data-default-cell');
      if ($('[data-default-cell=on]').size() === 0) {
        $('.default').hide();
      }
    }
    
  }
  
  
  // Toggle "Attributes" cell
  function switchingAttributesCell( selectedItem, targetRowId ) {
    
    var columnDefine = cdbt_admin_vars.column_types[selectedItem];
    var targetRow = 'preset' === targetRowId ? $('tr.preset>td.attributes') : $('tr[data-id='+ targetRowId +']>td.attributes');
    //console.info(columnDefine.atts);
    if (columnDefine.atts.length > 0) {
      $('.attributes').show();
      targetRow.find('input').val(targetRow.find('input').val() || '');
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
    
  }
  
  
  // Toggle "Auto Incr." cell
  function switchingAutoincrCell( selectedItem, targetRowId ) {
    
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
    
  }
  
  
  // Toggle "Extra" cell
  function switchingExtraCell( selectedItem, targetRowId ) {
    
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
  }
  
  
  // This event will fire when changed "Type Format" combobox.
  $(document).on('changed.fu.combobox', '.type_format .combobox', function (e, item) {
    var fixedType = isAllowedType(item.text);
    if (item.text.length < 3 || !fixedType) {
      return false;
    }
    var selectedItem = fixedType;
    var targetRowId = $(this).parent().parent('tr').hasClass('addnew') ? $(this).parent().parent('tr').data('id') : 'preset';
    switchingSizingCell( selectedItem, targetRowId );
    switchingDefaultCell( selectedItem, targetRowId );
    switchingAttributesCell( selectedItem, targetRowId );
    switchingAutoincrCell( selectedItem, targetRowId );
    switchingExtraCell( selectedItem, targetRowId );
  });
  
  
  // Validating column definition when enabled auto increment
  function validateAutoIncr( targetRowId ) {
    
    $('#sortable').children('tr.addnew').each(function(){
      if ($(this).data('id') === targetRowId) {
        $(this).children('td.default').find('input').prop('disabled', true).val('');
      } else {
        $(this).children('td.default').find('input').prop('disabled', false);
        //$(this).children('td.auto_increment').find('.checkbox-custom').removeClass('checked');
        //$(this).children('td.auto_increment').find('input').prop('checked', false);
        $(this).children('td.auto_increment').find('.checkbox-custom').checkbox('uncheck');
      }
    });
    
  }
  
  
  // This event will fire when checked "Auto Incr." checkbox.
  $(document).on('checked.fu.checkbox', '.auto_increment .checkbox', function (e) {
  //$(document).on('checked.fu.checkbox', '.auto_increment .checkbox-custom', function (e) {
    var targetRowId = $(this).parent().parent('tr').hasClass('addnew') ? $(this).parent().parent('tr').data('id') : 'preset';
    if ('preset' !== targetRowId) {
      validateAutoIncr(targetRowId);
    }
  });
  
  // This event will fire when unchecked "Auto Incr." checkbox.
  $(document).on('unchecked.fu.checkbox', '.auto_increment .checkbox', function (e) {
  //$(document).on('unchecked.fu.checkbox', '.auto_increment .checkbox-custom', function (e) {
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
    
    var cacheData = [], oneColumn;
    $('#sortable').children('tr.addnew').each(function(){
      oneColumn = {};
      $(this).find('input').each(function(){
        var key = $(this).attr('name').replace($(this).attr('name').substr($(this).attr('name').lastIndexOf('_')), '');
        oneColumn[key] = _.contains([ 'checkbox', 'radio' ], $(this).attr('type')) ? $(this).prop('checked') : $(this).val();
      });
      if (typeof oneColumn.col_name !== 'undefined' && '' !== oneColumn.col_name) {
        cacheData.push(oneColumn);
      }
    });
    if (cacheData.length > 0) {
      localStorage.setItem('cdbt-tc-cache', JSON.stringify(cacheData));
    } else {
      localStorage.setItem('cdbt-tc-cache', null);
    }
    
    $('#instance_create_table_sql').val( generateSQL(cacheData) );
    
    $('#cdbtTableCreator').modal('hide').on('hidden.bs.modal', function(e){
      $('#create-sql-support').trigger('click');
    });
    
  });
  
  
  function generateSQL( cacheData ) {
    var column_definition = [];
    
    _.each(cacheData, function(column){
      var sizing = '';
      var column_type = cdbt_admin_vars.column_types[isAllowedType(column.type_format)];
      if ('maxlength' === column_type.arg_type) {
        sizing = '' !== column.length ? column.length : column_type.default;
      } else
      if ('precision' === column_type.arg_type) {
        sizing = '' !== column.precision ? column.precision : column_type.default;
      } else
      if ('array' === column_type.arg_type) {
        var values = '' !== column.define_values_cache ? column.define_values_cache.split(',') : [];
        sizing = "'" + values.join("','") + "'";
      } else
      if (_.isArray(column_type.arg_type)) {
// console.info([ column.precision_scale_m, column.precision_scale_d, column_type.default ]);
        if ('scale' === column_type.arg_type[1]) {
        	var precision_scale_m_default = '';
        	if (_.isArray(column_type.default) && column_type.default.length > 0) {
        	  precision_scale_m_default = column_type.default[0];
        	}
          sizing = '' !== column.precision_scale_m ? column.precision_scale_m : precision_scale_m_default;
          if ('' !== sizing) {
            var precision_scale_d_default = '';
            if (_.isArray(column_type.default) && column_type.default.length > 1) {
              precision_scale_d_default = column_type.default[1];
            }
            if ('' !== column.precision_scale_d) {
              sizing += ',' + column.precision_scale_d;
            } else
            if ('' !== precision_scale_d_default) {
              sizing += ',' + precision_scale_d_default;
            }
          }
        } else {
         if ('' !== column.precision) {
           if (_.contains(column_type.arg_type, Number(column.precision))) {
             sizing = column.precision;
           }
         }
        }
      }
      column.sizing = '' !== sizing ? '(' + sizing + ')' : '';
      column.attributes = '' !== column.attributes ? ' ' + column.attributes.toUpperCase() : '';
      column.not_null = column.not_null ? ' NOT NULL' : '';
//console.info(column.type_format);
      if ('' !== column.default) {
        var default_prefix = ' DEFAULT ';
        if ('timestamp' === column.type_format && 'CURRENT_TIMESTAMP' === column.default.toUpperCase()) {
          column.default = default_prefix + column.default.toUpperCase();
        } else
        if (_.contains(['tinyint', 'smallint', 'mediumint', 'int', 'bigint'], column.type_format)) { // column.type_format.indexOf('int') !== -1
        	column.default = 'NULL' === column.default.toUpperCase() ? default_prefix + 'NULL' : default_prefix + Number(column.default);
        } else
        if ('bit' === column.type_format) {
          var reg = /^(|b\')([0-1]+)(|\')$/;
          if (column.default.match(reg)) {
            column.default = default_prefix + column.default.replace(reg, "b'$2'");
          } else {
            column.default = default_prefix + 'NULL';
          }
        } else
        if (_.contains(['timestamp', 'year', 'bool', 'boolean'], column.type_format)) {
          column.default = default_prefix + Number(column.default);
        } else {
          column.default = 'NULL' === column.default.toUpperCase() ? default_prefix + 'NULL' : default_prefix + "'" + column.default + "'";
        }
      }
      column.auto_increment = column.auto_increment ? ' AUTO_INCREMENT' : '';
      column.extra = '' !== column.extra ? ' ' + column.extra.toUpperCase() : '';
      column.comment = '' !== column.comment ? " COMMENT '" + column.comment + "'" : '';
      
      column_definition.push( "`"+ column.col_name +"` "+ column.type_format + column.sizing + column.attributes + column.not_null + column.default + column.auto_increment + column.extra + column.comment +", " );
    });
    
    return column_definition.join("\n");
  }
  
  
  function loadColumns( restoredCache ) {
    var preset = $('tr.preset'), checkbox, input, ai_col;
    var rows = restoredCache.length;
    var clearRows = {
      'col_name': '', 'type_format': '', 'precision': '', 'precision_scale_d': '', 'precision_scale_m': '', 'length': '', 'define_values_cache': '', 'not_null': false, 'default': '', 'attributes': '', 'auto_increment': false, 'key_index': '', 'extra': '', 'comment': '',
    };
    restoredCache.push(clearRows);
    
    _.each(restoredCache, function(col){
      
      _.each(col, function(value, key){
        
        if (_.contains([ 'not_null', 'auto_increment' ], key)) {
          checkbox = preset.find('.cdbt_tc_'+key+' .checkbox-custom');
          if (value) {
            checkbox.checkbox('check');
            if ('auto_increment' === key) {
              ai_col = col.col_name;
            }
          } else {
            checkbox.checkbox('uncheck');
          }
        } else {
          input = preset.find('input[name^='+key+']').val(value);
        }
        
        return;
      });
      
      if (rows > 0) {
        insertNewRow( 'before' );
        rows--;
      }
      return;
    });
    
    $('tr.addnew').each(function(){
      var currentRowId = $(this).data('id');
      var currentType = $(this).find('input[name^=type_format_]').val();
      var fixedType = isAllowedType(currentType);
      if (currentType.length >= 3 && fixedType) {
        switchingSizingCell( fixedType, currentRowId );
        switchingDefaultCell( fixedType, currentRowId );
        switchingAttributesCell( fixedType, currentRowId );
        switchingAutoincrCell( fixedType, currentRowId );
        switchingExtraCell( fixedType, currentRowId );
      }
      if ($(this).find('input[name^=col_name]').val() === ai_col) {
        $(this).find('.cdbt_tc_auto_increment .checkbox-custom').checkbox('check');
        //$(this).children('td.default').find('input').prop('disabled', true);
        validateAutoIncr(currentRowId);
      }
    });
    
  }
  
  
};