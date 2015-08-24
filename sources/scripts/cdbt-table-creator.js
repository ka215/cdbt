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
  
  $('tr.preset').delegate('input', 'click', function(e){
    //e.target.focus();
  });
  
  // For column type of `enum` or `set`
  $('[data-toggle="popover"]').popover({ 
    html: true, 
    content: function(){ 
      // load current item
      
      return $('#cdbt_tc_preset_define_values_template').html();
    }, 
    template: '<div class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div><div class="popover-footer"><button type="button" class="btn btn-primary btn-sm" disabled="disabled">'+ cdbt_admin_vars.cdbt_tc_translate.popoverSetValues +'</button></div></div>'
  });
  
  
  // This event will fire when clicked "Add New Column" button.
  $('.cdbt_tc_preset_controll button[name=add-column]').on('click', function(){
    var newRow = $('tr.preset').clone().delegate('input', 'click', function(e){
      e.target.focus();
    });
    
    var addNum = $('#sortable>tbody').children('tr').length + 1;
    newRow.removeClass('preset').addClass('addnew');
    newRow.children('td').each(function(){
      if ($(this).hasClass('handler')) {
        $(this).html('<strong>'+ addNum +'</strong>');
      } else {
        var item_name = $(this).find('input').attr('name');
        $(this).find('input').removeAttr('id').attr('name', item_name + addNum);
      }
      if ($(this).hasClass('controll')) {
        $(this).children('div').removeClass('cdbt_tc_preset_controll').addClass('cdbt_tc_controll');
      }
    });
    
    newRow.insertBefore('tr.preset');
    
  });
  
  
  // This event will fire when clicked "Remove Column" button.
  $(document).on('click', '.cdbt_tc_controll button[name=delete-column]', function(){
    $(this).parent().parent().parent('tr.addnew').fadeOut('fast', function(){ $(this).remove(); });
    
    //renumber_row_index();
  });
  
  
};