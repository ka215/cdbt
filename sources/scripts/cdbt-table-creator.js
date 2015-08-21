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
    
    
  };
  
  
  $(window).resize(function(){
    adjustModal( false );
  });
  initComponent();
  
  
};