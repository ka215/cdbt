/*!
 * Custom DataBase Tables v2.0.0 (http://ka2.org)
 * Copyright 2014-2015 ka2@ka2.org
 * Licensed under GPLv2 (http://www.gnu.org/licenses/gpl.txt)
 */
$(function() {
  
  /**
   * Utility functions
   * Return as an object by parsing the query string of the current URL
   */
  $.QueryString = (function(queries) {
    if ('' === queries) { return {}; }
    var results = {};
    for (var i=0; i<queries.length; ++i) {
      var param = queries[i].split('=');
      if (param.length !== 2) { continue; }
      results[param[0]] = decodeURIComponent(param[1].replace(/\+/g, ' '));
    }
    return results;
  })(window.location.search.substr(1).split('&'));
  
  /**
   * Localize the variables passed from wordpress
   */
  $.isDebug = 'true' === cdbt_main_vars.is_debug ? true : false;
  $.ajaxUrl = cdbt_main_vars.ajax_url;
  $.nonce = '';
  $.emitMessage = cdbt_main_vars.emit_message;
  $.emitType = cdbt_main_vars.emit_type;
  if ($.isDebug) {
    // check debug mode
    console.info( $.extend({ debugMode: 'ON' }, $.QueryString) );
  }
  
  /**
   * Define a global variable for retrieving the response of Ajax
   */
  $.ajaxResponse = {};
  
  /**
   * Define a class for the callback
   */
  var CallbackClass = function() {
    
    /**
     * Reload combobox of time within datepicker
     */
    this.reload_timer = function(){
      
      var now = new Date();
      $('.cdbt-datepicker').datepicker('getDate', now);
      $('.datepicker-combobox-hour input[type="text"]').val(('00' + now.getHours()).slice(-2));
      $('.datepicker-combobox-minute input[type="text"]').val(('00' + now.getMinutes()).slice(-2));
      $('.datepicker-combobox-second input[type="text"]').val(('00' + now.getSeconds()).slice(-2));
      
    };
    
    /**
     * Render a modal dialog
     */
    this.render_modal = function(){
      
      if ($('div.modal').size() > 0) {
        $('div.modal').remove();
      }
      
      $('body').append( $.ajaxResponse.responseText );
      
    };
    
    /**
     * Insert the content into the modal dialog
     */
    this.load_into_modal = function(){
      
      if ($('div.modal').size() > 0) {
        $('div.modal-body').html( $.ajaxResponse.responseText ).trigger('create');
        // Initialize the form components of fuel ux
        $('.checkbox').checkbox();
        $('.combobox').combobox();
        $('.datepicker').datepicker({ 
          date: new Date($('input[name="custom-database-tables[created][prev_date]"]').val()), 
          allowPastDates: true, 
          restrictDateSelection: true, 
          momentConfig: { culture: $('.cdbt-datepicker').attr('data-moment-locale'), format: $('.cdbt-datepicker').attr('data-moment-format') }, 
        });
        $('.infinitescroll').infinitescroll();
        $('.loader').loader();
        $('.pillbox').pillbox();
        $('.placard').placard();
        $('.radio').radio();
        $('.repeater').repeater();
        $('.search').search();
        $('.selectlist').selectlist();
        $('.spinbox').spinbox();
        $('.tree').tree();
        $('.wizard').wizard();
      }
      
    };
    
    /**
     * Generate dynamic form
     */
    this.submit_data_deletion = function(){
      
      var where_conditions = [];
      $('tr.selectable.selected input.row_where_condition').each(function(){
        where_conditions.push($(this).val());
      });
      
      var nonce = $.ajaxResponse.responseText;
      var generate_form = '<form method="post" action="' + location.href + '" id="data-deletion" style="display: none;">';
      generate_form += '<input type="hidden" name="action" value="delete_data">';
      generate_form += '<input type="hidden" name="table" value="' + _.last($.sessionKey.split('-')) + '">';
      generate_form += '<input type="hidden" name="_wpnonce" value="' + nonce + '">';
      generate_form += '<input type="hidden" name="where_conditions" value="' + where_conditions.join(',') + '">';
      generate_form += '</form>';
      $('#cdbtDeleteData .modal-body').append(generate_form).find('#data-deletion').submit();
      
    };
    
  };
  var Callback = new CallbackClass();
  
  /**
   * Modal dialog window of Bootstrap initialize
   */
  var init_modal = function(){
    var post_data = {};
    if (arguments.length > 0) {
      post_data = arguments[0];
    }
    
    if ($('div.modal').size() > 0) {
      $('div.modal').remove();
    }
    
    cdbtCallAjax( $.ajaxUrl, 'post', _.extend(post_data, { 'event': 'retrieve_modal' }), 'html', 'render_modal' );
    
  };
  
  /**
   * Wizard components of Fuel UX renderer
   */
  if (typeof wizard !== 'undefined') {
    $('#welcome-wizard').wizard();
  }
  
  /**
   * Repeater components of Fuel UX renderer
   */
  if (typeof repeater !== 'undefined') {
    _.each(repeater, function(k,v){ return repeater[v](); });
    
    var adjustCellSize = function() {
      $('.repeater table.table thead th').each(function(){
        var label_elm = $(this).find('.repeater-list-heading');
        $(this).removeAttr('style');
        label_elm.removeAttr('style');
        // if ($.isDebug) { console.info([$(this).attr('class'), $(this).width(), $(this).height(), parseInt(label_elm.css('width')), parseInt(label_elm.css('height')), label_elm.width(), label_elm.height()]); }
        //var fixed_width = _.max([ $(this).width(), parseInt(label_elm.css('width')), label_elm.width() ]);
        var fixed_width = parseInt(label_elm.css('width'));
        //var fixed_height = _.max([ $(this).height(), parseInt(label_elm.css('height')), label_elm.height() ]);
        var fixed_height = parseInt(label_elm.css('height'));
        // if ($.isDebug) { console.info([ fixed_width, fixed_height ]); }
        $(this).removeAttr('style').css({ width: fixed_width+'px', height: fixed_height+'px' });
        label_elm.removeAttr('style').css({ width: fixed_width+'px', height: fixed_height+'px' });
      });
    };
    
    
    var is_view_via_shortcode = $('.repeater').attr('id').indexOf('cdbt-repeater-view-') === 0;
    var is_edit_via_shortcode = $('.repeater').attr('id').indexOf('cdbt-repeater-edit-') === 0;
    
    if (is_view_via_shortcode || is_edit_via_shortcode) {
      
      $('.dropdown-toggle').dropdown();
      $(document).on('click', '.repeater-views', function(){
        adjustCellSize();
      });
      
      /**
       * When edit via shortcode
       */
      
      // Button effect
      var effect_buttons = function(){
        var selectedItem = $('.repeater[id^="cdbt-repeater-edit-"]').repeater('list_getSelectedItems');
        var edit_button = $('button#repeater-editor-edit');
        var delete_button = $('button#repeater-editor-delete');
        if (selectedItem.length === 1) {
          edit_button.attr('class', 'btn btn-primary').removeAttr('disabled');
          delete_button.attr('class', 'btn btn-default');
        } else if (selectedItem.length > 1) {
          edit_button.attr('class', 'btn btn-default').attr('disabled', 'disabled');
          delete_button.attr('class', 'btn btn-primary');
        } else {
          edit_button.attr('class', 'btn btn-default').removeAttr('disabled');
          delete_button.attr('class', 'btn btn-default');
        }
      };
      $('.repeater[id^="cdbt-repeater-edit-"]').on('selected.fu.repeaterList deselected.fu.repeaterList', function(){
        effect_buttons();
      });
      $('#repeater-check-switch').on('click', function(){
        effect_buttons();
      });
      
      // Event handler
      $('button[id^="repeater-editor-"]').on('click', function(){
        var dataAction = _.last($(this).attr('id').split('-'));
        var targetTable = _.last($('.repeater').attr('id').split('-'));
        var selectedItem = $('.repeater[id^="cdbt-repeater-edit-'+targetTable+'"]').repeater('list_getSelectedItems');
        var post_data = {};
        $.sessionKey = 'cdbt-edit-' + targetTable;
        
        $common_modal_hide = "$('input[name=\"custom-database-tables[operate_action]\"]').val('edit'); $('form.navbar-form').trigger('submit');";
        
        switch(dataAction){
          case 'refresh': 
            post_data = {
              'session_key': $.sessionKey, 
              'default_action': 'edit', 
              'target_table': targetTable, 
              'callback_url': window.location.href, 
            };
            return cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
            
          case 'edit': 
            post_data = {
              id: 'cdbtEditData', 
              insertContent: true, 
              modalTitle: '', 
              modalBody: '', 
            };
            if (selectedItem.length === 0) {
              post_data.modalTitle = 'no_selected_item';
            } else if (selectedItem.length > 1) {
              post_data.modalTitle = 'too_many_selected_item';
            } else {
              post_data.modalSize = 'large';
              post_data.modalTitle = 'edit_data_form';
              post_data.modalExtras = { 
                'table_name': targetTable, 
                'action_url': window.location.href, 
                'where_clause': $('tr.selectable.selected input.row_where_condition').val(), 
              };
            }
            $.ajaxResponse.targetTable = targetTable;
            init_modal( post_data );
            
            break;
          case 'delete': 
            post_data = {
              id: 'cdbtDeleteData', 
              insertContent: true, 
              modalTitle: '', 
              modalBody: '', 
              modalExtras: { items: selectedItem.length },
            };
            if (selectedItem.length === 0) {
              post_data.modalTitle = 'no_selected_item';
            } else {
              post_data.modalTitle = 'delete_data';
            }
            init_modal( post_data );
            
            break;
          default: 
            break;
        }
      });
      
      $(document).on('show.bs.modal', '#cdbtEditData', function(){
        var post_data = {
          'session_key': $.sessionKey, 
          'table_name': _.last($.sessionKey.split('-')), 
          'operate_action': 'edit_data', 
          'event': 'render_edit_form', 
          'shortcode': $('#edit-data-form').val(), 
        };
        return cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'html', 'load_into_modal' );
      });
      
      
      $(document).on('click', '#run_update_data', function(){
        var form = $('#cdbtEditData div.cdbt-entry-data-form form');
        form.children('input[name="_wp_http_referer"]').val(location.href);
        form.submit();
      });
      
      
      // Run of deleting data after confirmation
      $(document).on('click', '#run_delete_data', function(){
        var post_data = {
          'table': _.last($.sessionKey.split('-')), 
          'event': 'retrieve_nonce', 
        };
        return cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'text', 'submit_data_deletion' );
      });
      
    }
    adjustCellSize();
    
    
  }
  
  /**
   * Datepicker components of Fuel UX renderer
   */
  if ($('.cdbt-datepicker').size() > 0) {
    $('.cdbt-datepicker').each(function(){
      if ($(this).data().momentLocale && $(this).data().momentFormat) {
        $(this).datepicker({ 
          momentConfig: { culture: $(this).data().momentLocale, format: $(this).data().momentFormat } 
        });
      }
      if (typeof $(this).data().date === 'undefined') {
        $(this).datepicker('getDate', new Date()); 
      }
    });
    setInterval( function(){ 'use strict'; Callback.reload_timer(); }, 1000 );
  }
  
  /**
   * Entry forms via shortcode `cdbt-entry`
   */
  if ($('.cdbt-entry-data-form').size() > 0) {
    
    $('.dropdown-toggle').dropdown();
    
  }
  
  
  /**
   * Common ajax closure
   */
  var cdbtCallAjax = function(){
    if (arguments.length < 2) {
      return false;
    }
    var ajax_url = arguments[0];
    var method = arguments[1];
    var post_data = typeof arguments[2] !== 'undefined' ? arguments[2] : null;
    var data_type = typeof arguments[3] !== 'undefined' ? arguments[3] : 'text';
    var callback_function = typeof arguments[4] !== 'undefined' ? arguments[4] : null;
    
    var jqXHR = $.ajax({
      async: true,
      url: ajax_url,
      type: method,
      data: post_data,
      dataType: data_type,
      cache: false,
      beforeSend: function(xhr, set) {
        // return;
      }
    });
    
    jqXHR.done(function(data, stat, xhr) {
      if ($.isDebug) {
        console.log({
          done: stat,
          data: data,
          xhr: xhr
        });
        //alert( xhr.responseText );
      }
      if ('script' !== data_type) {
        $.ajaxResponse = { 'responseText': jqXHR.responseText, 'status': jqXHR.status, 'statusText': jqXHR.statusText };
      } else {
        return data;
      }
      if ('' !== callback_function) {
        return Callback[callback_function]();
      }
    });
    
    jqXHR.fail(function(xhr, stat, err) {
      if ($.isDebug) {
        console.log({
          fail: stat,
          error: err,
          xhr: xhr
        });
        //alert( xhr.responseText );
      }
    });
    
    jqXHR.always(function(res1, stat, res2) {
      if ($.isDebug) {
        console.log({
          always: stat,
          res1: res1,
          res2: res2
        });
        if (stat === 'success') {
          //alert('Ajax Finished!');
        }
      }
    });
    
  };
  
  
  /**
   * Common display notice handler
   */
  if ('' !== $.emitMessage) {
    var modal_title = 'notice' === $.emitType ? 'notices_updated' : 'notices_error';
    var post_data = {
      id: 'cdbtModal', 
      insertContent: true, 
      modalTitle: modal_title, 
      modalBody: $.emitMessage, 
    };
    init_modal( post_data );
    $.emitMessage = cdbt_main_vars.emit_message = '';
    $.emitType = 'error';
  }
  
  
  
  
});
/**
 * Common processing that does not depend on jQuery
 */
function setCookie(ck_name, ck_value, expiredays) {
  // SetCookie
  var path = '/';
  var extime = new Date().getTime();
  var cltime = new Date(extime + (60*60*24*1000*expiredays));
  var exdate = cltime.toUTCString();
  var tmp_data = new Array(ck_value);
  var fix_data = tmp_data.filter(function (x, i, self) { return self.indexOf(x) === i; });
  var s = '';
  s += ck_name + '=' + escape(fix_data.join(','));
  s += '; path=' + path;
  s += expiredays ? '; expires=' + exdate + '; ' : '; ';
  document.cookie = s;
}
function getCookie(ck_name) {
  // GetCookie
  var st = '', ed = '', res = '';
  if (document.cookie.length > 0) {
    st = document.cookie.indexOf(ck_name + '=');
    if (st !== -1) {
      st = st + ck_name.length + 1;
      ed = document.cookie.indexOf(';', st);
      if (ed === -1) {
        ed = document.cookie.length;
      }
      res = unescape(document.cookie.substring(st, ed));
    }
  }
  return res;
}
function removeCookie(ck_name) {
  // removeCookie
  var path = '/';
  if (!ck_name || document.cookie.indexOf(ck_name + '=') !== -1) { return; }
  document.cookie = escape(ck_name) + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT' + (path ? '; path=' + path : '');
}
