/*!
 * Custom DataBase Tables v2.0.7 (http://ka2.org)
 * Copyright 2014-2015 ka2@ka2.org
 * Licensed under GPLv2 (http://www.gnu.org/licenses/gpl.txt)
 */
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
/* jQuery main */
$(document).ready(function() {
  
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
  $.modalNotices = 'true' === cdbt_main_vars.notices_via_modal ? true : false;
  $.emitMessage = cdbt_main_vars.emit_message;
  $.emitType = cdbt_main_vars.emit_type;
  if ($.isDebug) {
    // check debug mode
    console.info( $.extend({ debugMode: 'ON', modalNotices: $.modalNotices }, $.QueryString) );
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
      
      if ($('div.cdbt-modal').size() > 0) {
        $('div.cdbt-modal').remove();
        $('#append-dynamic-modal').remove();
      }
      
      $('body').append( $.ajaxResponse.responseText );
      
    };
    
    /**
     * Insert the content into the modal dialog
     */
    this.load_into_modal = function(){
      
      if ($('div.cdbt-modal').size() > 0) {
        $('div.modal-body').html( $.ajaxResponse.responseText ).trigger('create');
        $.ajaxResponse.responseText = '';
        // Initialize the form components of fuel ux
        $('.dropdown-toggle').dropdown();
        $('.checkbox-custom').checkbox('enable');
        $('.combobox').combobox('enable');
        $('.infinitescroll').infinitescroll('enable');
        $('.loader').loader('reset');
        $('.pillbox').pillbox();
        $('.placard').placard('enable');
        $('.radio').radio('enable');
        $('.search').search('enable');
        $('.selectlist').selectlist('enable');
        /*$('.selectlist').each(function(){
          console.info($(this).attr('id'));
          $('#'+$(this).attr('id')).selectlist('enable');
        });*/
        $('.spinbox').spinbox();
        $('.tree').tree('render');
        $('.wizard').wizard();
        $('.repeater').repeater('render');
        $('.datepicker').each(function(){
          var parse_id = $(this).attr('id').replace('entry-data-', '').split('-');
          var id = parse_id[0];
          var prev_date = $('input[name="custom-database-tables['+id+'][prev_date]"]').val();
          $(this).datepicker({ 
            date: new Date(prev_date), 
            allowPastDates: true, 
            restrictDateSelection: true, 
            momentConfig: { culture: $(this).data('momentLocale'), format: $(this).data('momentFormat') }, 
          });
        });
        // Initialize other
        //dynamicTableRender();
        $('div.modal-body').find('input,textarea,select').each(function(){
          if (typeof $(this).attr('required') !== 'undefined') {
            $(this).prop('required', true);
          }
        });
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
      var generate_form = '<form method="post" action="' + location.href + '" id="data-deletion-' + nonce + '" style="display: none;">';
      generate_form += '<input type="hidden" name="action" value="delete_data">';
      generate_form += '<input type="hidden" name="table" value="' + _.last($.sessionKey.split('-')) + '">';
      generate_form += '<input type="hidden" name="_wpnonce" value="' + nonce + '">';
      generate_form += '<input type="hidden" name="where_conditions" value="' + where_conditions.join(',') + '">';
      generate_form += '</form>';
      $('#cdbtDeleteData .modal-body').append(generate_form);
      var delete_form = $('#cdbtDeleteData .modal-body').find('#data-deletion-' + nonce);
      setCookie( 'once_action', delete_form.attr( 'id' ) );
      delete_form.submit();
      
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
    
    if ($('div.cdbt-modal').size() > 0) {
      $('div.cdbt-modal').remove();
      $('#append-dynamic-modal').remove();
    }
    
    cdbtCallAjax( $.ajaxUrl, 'post', _.extend(post_data, { 'event': 'retrieve_modal' }), 'html', 'render_modal' );
    
    // Whether correspond of redirection after data registration
    // 
    // @since 2.0.5
    $(document).on('hide.bs.modal', 'div.cdbt-modal, #append-dynamic-modal', function(){
      if ( $('.cdbt-entry-data-form').size() > 0 ) {
        if ( $('#cdbt-entry-redirection').val() !== '' ) {
          location.replace( $('#cdbt-entry-redirection').val() );
        } else {
        	// This do not take over the session for multiple transmission prevention.
          location.replace( location.href );
        }
      }
    });
    
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
      $('.table-frozen tr').removeAttr('style');
      $('.repeater table tr').each(function(){
        $(this).children('th').removeAttr('style');
        $(this).children('td').removeAttr('style');
        $(this).find('.repeater-list-heading').removeAttr('style');
        $('.repeater-list-heading').each(function(){
          $(this).css('width', $(this).parent('th').width() + 16 + 'px');
        });
      });
      var tr_i = 0;
      $('.repeater-list-wrapper>table tr').each(function(){
        var base_height = $(this).height() + 1;
        $(this).css('height', base_height+'px');
        $('.table-frozen tr').eq(tr_i).css('height', base_height+'px');
        tr_i++;
      });
    };
    
    
    var is_view_via_shortcode = $('.repeater').attr('id').indexOf('cdbt-repeater-view-') === 0;
    var is_edit_via_shortcode = $('.repeater').attr('id').indexOf('cdbt-repeater-edit-') === 0;
    
    if (is_view_via_shortcode || is_edit_via_shortcode) {
      
      $('.dropdown-toggle').dropdown();
      
      $(document).on('click', '.repeater-views', function(){
        //adjustCellSize();
      });
      
      $(document).on('click', 'th.sortable', function(){
        //adjustCellSize();
      });
      
      $(document).on('click', '.modal-preview', function(){
        var bin_data = $(this).children('img').attr('src');
        var raw_data = '';
        var post_data = {
          id: 'cdbtModal', 
          insertContent: true, 
          modalTitle: '', 
          modalBody: '', 
        };
        if (typeof bin_data !== 'undefined') {
          if (bin_data.indexOf('data:') === 0) {
          /*
            bin_data = bin_data.split(',');
            raw_data = bin_data.length > 1 ? bin_data[1] : '';
          } else {
          */
            raw_data = bin_data;
          }
          post_data.modalTitle = 'image_preview';
          post_data.modalSize = 'large';
          post_data.modalBody = '<div class="preview-image-body"><img src="'+ raw_data +'" class="center-block"></div>';
        } else {
          var repeater_id = $(this).parents('.repeater').attr('id');
        	raw_data = $(this).text();
          post_data.modalTitle = 'binary_downloader';
          post_data.modalExtras = { 
            'table_name': repeater_id.substr(repeater_id.lastIndexOf('-') + 1), 
            'target_column': $(this).attr('data-column-name'), 
            'where_clause': $(this).attr('data-where-conditions'), 
          };
        }
        init_modal( post_data );
        
      });
      
      /**
       * When edit via shortcode
       */
      
      // Check empty fields
      // Added since version 2.0.7
      var checkEmptyFields = function(){
        if ( $('.cdbt-entry-data-form form').size() > 0 ) {
          var required_fields = 0;
          var empty_fields = 0;
          $('.form-group').each( function() {
            if ( $(this).find('.required').size() > 0 || $(this).find('.cdbt-form-required').size() > 0 ) {
              required_fields += 1;
              var checked = 0;
              var inputted = 0;
              if ( $(this).find('.checkbox').size() > 0 ) {
                $(this).find('.checkbox-custom').each( function() {
                  if ( $(this).checkbox('isChecked') ) {
                    checked++;
                  }
                });
                if ( checked === 0 ) {
                  empty_fields++;
                }
              } else
              if ( $(this).find('.selectlist').size() > 0 ) {
                if ( ! $(this).find('.selectlist').selectlist('selectedItem').selected ) {
                  empty_fields++;
                }
              } else
              if ( $(this).find('input[type="text"]').size() > 0 ) {
                $(this).find('input[type="text"]').each( function() {
                  if ( $(this).val() !== '' ) {
                    inputted++;
                  }
                });
                if ( inputted !== $(this).find('input[type="text"]').size() ) {
                  empty_fields++;
                }
              } else
              if ( $(this).find('input[type="number"]').size() > 0 ) {
                $(this).find('input[type="number"]').each( function() {
                  if ( $(this).val() !== '' ) {
                    inputted++;
                  }
                });
                if ( inputted !== $(this).find('input[type="number"]').size() ) {
                  empty_fields++;
                }
              } else
              if ( $(this).find('textarea').size() > 0 ) {
                $(this).find('textarea').each( function() {
                  if ( $(this).val() !== '' ) {
                    inputted++;
                  }
                });
                if ( inputted !== $(this).find('textarea').size() ) {
                  empty_fields++;
                }
              } else
              if ( $(this).find('input[type="file"]').size() > 0 ) {
                // do nothing
              }
            }
            
          });
          if ( required_fields === 0 || empty_fields === 0 ) {
            return true;
          } else {
            return false;
          }
        } else {
          return true;
        }
      };
      
      
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
      $(document).on('click', '#repeater-check-switch', function(){
        effect_buttons();
      });
      
      // Event handler
      $(document).on('click', 'button[id^="repeater-editor-"]', function(){
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
              'event': 'reload_page', 
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
              post_data.id = 'cdbtEditWarning';
              post_data.modalTitle = 'no_selected_item';
            } else if (selectedItem.length > 1) {
              post_data.id = 'cdbtEditWarning';
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
            return false;
        }
      });
      
      $(document).on('show.bs.modal', '#cdbtEditData', function(){
        var post_data = {
          'session_key': $.sessionKey, 
          'table_name': _.last($.sessionKey.split('-')), 
          'operate_action': 'edit_data', 
          'event': 'render_edit_form', 
          'shortcode': '[' + $('#edit-data-form').val() + ']', 
        };
        return cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'html', 'load_into_modal' );
      });
      
      
      $(document).on('change', '#cdbtEditData div.cdbt-entry-data-form form .checkbox-custom input', function () {
        if ($(this).parent().hasClass('multiple')) {
          var counter_elm = $('input[name="' + $(this).attr('name').replace('[]', '') + '[checked]"]');
          var checked_count = Number(counter_elm.val());
          if ( $(this).prop('checked') ) {
            checked_count += 1;
          } else {
            checked_count -= 1;
          }
          checked_count = checked_count < 0 ? 0 : checked_count;
          counter_elm.val(checked_count);
        }
      });
      
      
      var countMultipleChecked = function(){
        var $this = $('#cdbtEditData .checkbox');
        $this.parent().each(function(){
        	if ($(this).children().children('.checkbox-custom.multiple')) {
            var counter_elm = $('input[name="' + $(this).children().children('.checkbox-custom').children('input').attr('name').replace('[]', '') + '[checked]"]');
            var checked_count = 0; //Number(counter_elm.val());
            $(this).find('.checkbox-custom input').each(function(){
              if ( $(this).prop('checked') ) {
                checked_count++;
              }
            });
            counter_elm.val(checked_count);
          }
        });
      };
      
      
      $(document).on('click', '#run_update_data', function(e){
        e.preventDefault();
        var form = $('#cdbtEditData div.cdbt-entry-data-form form');
        form.children('input[name="_wp_http_referer"]').val(location.href);
        /* Disabled since v2.0.7
        var check_result = true;
        form.find('.checkbox-custom').each(function(){
          if ($(this).hasClass('multiple')) {
            countMultipleChecked();
            var counter_elm = $('input[name="' + $(this).children('input').attr('name').replace('[]', '') + '[checked]"]');
            var checked_count = Number(counter_elm.val());
            if ($(this).hasClass('required')) {
              if (checked_count === 0) {
                check_result = false;
              }
            }
          } else {
            if (!$(this).checkbox('isChecked')) {
              //console.info([ $(this).html(), $(this).children('input').attr('name') ]);
              $(this).children('input').val('0').prop('checked', true);
            } else {
              $(this).children('input').val('1').prop('checked', true);
            }
          }
        });
        if (check_result) {
          form.submit();
        } else {
          return false;
        }
        */
        if ( checkEmptyFields() ) {
          setCookie( 'once_action', form.attr( 'id' ) );
          form.submit();
        } else {
          return false;
        }
      });
      
      
      // Run of deleting data after confirmation
      $(document).on('click', '#run_delete_data', function(){
        $('#run_delete_data').off();
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
  if ($('.cdbt-entry-data-form form').size() > 0) {
    
    $('.dropdown-toggle').dropdown();
    
    $('.cdbt-entry-data-form form .checkbox-custom').on('checked.fu.checkbox unchecked.fu.checkbox', function () {
      if ($(this).hasClass('multiple')) {
        var counter_elm = $('input[name="' + $(this).children('input').attr('name').replace('[]', '') + '[checked]"]');
        var checked_count = Number(counter_elm.val());
        if ( $(this).checkbox('isChecked') ) {
          checked_count += 1;
        } else {
          checked_count -= 1;
        }
        counter_elm.val(checked_count);
      }
    });
    
    /*
    $(document).on('submit', '.cdbt-entry-data-form form', function(){
        $('.cdbt-entry-data-form form').off();
    });
    */
    
    $(document).on('click', 'button[id^="btn-entry/"]', function(e){
      e.preventDefault();
      var tmp = $(this).attr('id').split('/');
      var entry_form = $('#' + tmp[1]);
      var check_result = true;
      entry_form.find('.checkbox-custom').each(function(){
        if ($(this).hasClass('multiple')) {
          if ($(this).hasClass('required')) {
            var counter_elm = $('input[name="' + $(this).children('input').attr('name').replace('[]', '') + '[checked]"]');
            var checked_count = Number(counter_elm.val());
            if (checked_count === 0) {
              //$(this).children('input').prop('required', true);
              check_result = false;
            }
          }
        }
      });
      if ( check_result ) {
        setCookie( 'once_action', entry_form.attr( 'id' ) );
        entry_form.submit();
      } else {
        return false;
      }
    });
    
  }
  
  
  /**
   * Dynamic table components renderer
   */
  if (typeof dynamicTable !== 'undefined') {
    _.each(dynamicTable, function(k,v){ return dynamicTable[v](); });
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
    var process_data = typeof arguments[5] !== 'undefined' ? arguments[5] : null;
    var content_type = typeof arguments[6] !== 'undefined' ? arguments[6] : null;
    var ajax_param = {
      async: true,
      url: ajax_url,
      type: method,
      data: post_data,
      dataType: data_type,
      cache: false,
      beforeSend: function(xhr, set) {
        // return;
      }
    };
    if ( process_data !== null ) {
      ajax_param.append( 'processData', process_data );
    }
    if ( content_type !== null ) {
      ajax_param.append( 'contentType', content_type );
    }
    
    var jqXHR = $.ajax( ajax_param );
    
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
      modalHideEvent: "$('#cdbtModal .modal-body').html(''); $('#cdbtModal').remove(); $.emitMessage = cdbt_main_vars.emit_message = ''; $.emitType = 'error';"
    };
    init_modal( post_data );
    $.emitMessage = cdbt_main_vars.emit_message = '';
    $.emitType = 'error';
  }
  
  
  
  
});
/**
 * Convert datetime format as common utility function for repeater
 */
var convert_datetime = function() {
  if (arguments.length < 2) {
    return arguments.length === 1 ? arguments[0] : false;
  }
  if (typeof arguments[0] === 'undefined' || !arguments[0]) {
    return '-';
  }
  var datetime_string = arguments[0].replace(/\-/g, '/');
  var datetime = new Date(datetime_string);
  var format = arguments[1].join(' ');
  // year
  format = format.replace(/Y/g, datetime.getFullYear());
  format = format.replace(/y/g, ('' + datetime.getFullYear()).slice(-2));
  // month
  format = format.replace(/m/g, ('0' + (datetime.getMonth() + 1)).slice(-2));
  format = format.replace(/n/g, (datetime.getMonth() + 1));
  var month = { Jan: 'January', Feb: 'February', Mar: 'March', Apr: 'April', May: 'May', Jun: 'June', Jul: 'July', Aug: 'August', Sep: 'September', Oct: 'October', Nov: 'November', Dec: 'December' };
  format = format.replace(/F/g, _.find(month, datetime.getMonth()));
  format = format.replace(/F/g, _.findKey(month, datetime.getMonth()));
  // day
  format = format.replace(/d/g, ('0' + datetime.getDate()).slice(-2));
  format = format.replace(/j/g, datetime.getDate());
  var suffix = [ 'st', 'nd', 'rd', 'th' ];
  var suffix_index = function(){ var d = datetime.getDate(); return d > 3 ? 3 : d - 1; };
  format = format.replace(/S/g, suffix[suffix_index()]);
  var day = { Sun: 'Sunday', Mon: 'Monday', Tue: 'Tuesday', Wed: 'Wednesday', Thu: 'Thurseday', Fri: 'Friday', Sat: 'Saturday' };
  format = format.replace(/l/g, _.find(day, datetime.getDay()));
  format = format.replace(/D/g, _.findKey(day, datetime.getDay()));
  // time
  var half_hours = function(){ var h = datetime.getHours(); return h > 12 ? h - 12 : h; };
  var ampm = function(){ var h = datetime.getHours(); return h > 12 ? 'pm' : 'am'; };
  format = format.replace(/a/g, ampm());
  format = format.replace(/A/g, ampm().toUpperCase());
  format = format.replace(/g/g, half_hours());
  format = format.replace(/h/g, ('0' + half_hours()).slice(-2));
  format = format.replace(/G/g, datetime.getHours());
  format = format.replace(/H/g, ('0' + datetime.getHours()).slice(-2));
  format = format.replace(/i/g, ('0' + datetime.getMinutes()).slice(-2));
  format = format.replace(/s/g, ('0' + datetime.getSeconds()).slice(-2));
  format = format.replace(/T/g, '');
  // other
  format = format.replace(/c/g, (arguments[0].replace(' ', 'T') + '+00:00'));
  format = format.replace(/r/g, datetime);
  
  return format.indexOf('Na') !== -1 ? arguments[0] : format;
};
