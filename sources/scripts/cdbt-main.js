/*!
 * Custom DataBase Tables v2.1.31 (http://ka2.org)
 * Copyright 2014-2016 ka2@ka2.org
 * Licensed under GPLv2 (http://www.gnu.org/licenses/gpl.txt)
 */
/*
 * :: cookies.js ::
 *
 * A complete cookies reader/writer framework with full unicode support.
 *
 * source:  https://developer.mozilla.org/en-US/docs/DOM/document.cookie
 * localize: https://developer.mozilla.org/ja/docs/Web/API/Document/cookie
 *
 * Syntaxes:
 *
 * - docCookies.setItem(name, value[, end[, path[, domain[, secure]]]])
 * - docCookies.getItem(name)
 * - docCookies.removeItem(name[, path])
 * - docCookies.hasItem(name)
 * - docCookies.keys()
 *
 */
var docCookies = {
  getItem: function (sKey) {
    if (!sKey || !this.hasItem(sKey)) { return null; }
    return unescape(document.cookie.replace(new RegExp("(?:^|.*;\\s*)" + escape(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*((?:[^;](?!;))*[^;]?).*"), "$1"));
  },
  setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
    if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return; }
    var sExpires = "";
    if (vEnd) {
      switch (vEnd.constructor) {
        case Number:
          sExpires = vEnd === Infinity ? "; expires=Tue, 19 Jan 2038 03:14:07 GMT" : "; max-age=" + vEnd;
          break;
        case String:
          sExpires = "; expires=" + vEnd;
          break;
        case Date:
          sExpires = "; expires=" + vEnd.toGMTString();
          break;
      }
    }
    document.cookie = escape(sKey) + "=" + escape(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
  },
  removeItem: function (sKey, sPath) {
    if (!sKey || !this.hasItem(sKey)) { return; }
    document.cookie = escape(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (sPath ? "; path=" + sPath : "");
  },
  hasItem: function (sKey) {
    return (new RegExp("(?:^|;\\s*)" + escape(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
  },
  keys: /* optional method: you can safely remove it! */ function () {
    var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
    for (var nIdx = 0; nIdx < aKeys.length; nIdx++) { aKeys[nIdx] = unescape(aKeys[nIdx]); }
    return aKeys;
  }
};
/* jQuery main */
//$(document).ready(function() {
jQuery(document).ready(function($){
  
  /**
   * Utility functions
   * 1. Return as an object by parsing the query string of the current URL
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
   * 2. 1em to pixels
   *
   * @param int em [optional]
   */
  $.em2pxl = function(em) {
    if ('' === em) { em = 1; }
    var div = $('<div style="width:'+em+'em;"></div>').appendTo('body');
    var pixel = div.width();
    div.remove();
    return pixel;
  };
  
  /**
   * 3. Survey the width of strings
   *
   * @param string str
   * @param bool stripTags [optional]
   */
  $.strWidth = function(str) {
    if ('' === arguments[0]) { return 0; }
    var ruler = $('<span style="visibility:hidden;position:absolute;white-space:nowrap;"></span>').appendTo('body');
    var width;
    if (arguments[1] !== undefined && arguments[1]) {
      str = $('<div/>').html(str).text();
    }
    width = ruler.text(str).get(0).offsetWidth;
    ruler.remove();
    return width;
  };
  
  /**
   * 4. Survey the size of image
   *
   * @param string src [optional]
   */
  $.imageSize = function(src) {
    var imgSize = { w: 0, h: 0 };
    var img = new Image();
    img.src = src;
    imgSize.w = img.width;
    imgSize.h = img.height;
    return imgSize;
  };
  
  /**
   * 5. Perform mutual conversion between the actual strings and the html entity
   * 
   * @param string str [required]
   * @param string proc [optional] "decode" (is default) or "encode"
   */
  $.htmlEntities = function(str, proc) {
    if ( 'encode' === proc ) {
      var buffer = [];
      for ( var i=str.length-1; i>=0; i-- ) {
        buffer.unshift( ['&#', str[i].charCodeAt(), ';'].join('') );
      }
      return buffer.join('');
    } else {
      return str.replace( /&#(\d+);/g, function( match, dec ) {
        return String.fromCharCode( dec );
      });
    }
  };
  
  
  /**
   * Localize the variables passed from wordpress
   */
  $.isDebug = 'true' === cdbt_main_vars.is_debug ? true : false;
  $.ajaxUrl = cdbt_main_vars.ajax_url;
  $.nonce = '';
  $.modalNotices = 'true' === cdbt_main_vars.notices_via_modal ? true : false;
  $.emitMessage = cdbt_main_vars.emit_message;
  $.emitType = cdbt_main_vars.emit_type;
  $.localErrMsg = decodeURIComponent(cdbt_main_vars.local_err_msg.replace(/\+/g, ' '));
  $.onTimer = true;
  if ($.isDebug) {
    // check debug mode
    console.info( $.extend({ debugMode: 'ON', modalNotices: $.modalNotices, onTimer: $.onTimer }, $.QueryString) );
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
      
      if ( $.onTimer ) {
        var now = new Date();
        $('.cdbt-datepicker').datepicker( 'getDate', now );
        $('.datepicker-combobox-hour[data-on-timer="true"] input[type="text"]').val(('00' + now.getHours()).slice(-2));
        $('.datepicker-combobox-minute[data-on-timer="true"] input[type="text"]').val(('00' + now.getMinutes()).slice(-2));
        $('.datepicker-combobox-second[data-on-timer="true"] input[type="text"]').val(('00' + now.getSeconds()).slice(-2));
      }
      
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
      
      // For rendering JSON data (since 2.1.31)
      if ( /^\[\{.*\}\]$/im.test($('.cdbt-modal .modal-body').text().trim()) ) {
        $('.cdbt-modal .modal-body').html('<textarea class="inner-preview cdbt-clipboard">'+$('.cdbt-modal .modal-body').text().trim()+'</textarea>').queue(function(){
          var __height = $(window).height() - $('.modal-header').height() - $('.modal-footer').height() - ($('#wpadminbar').size() > 0 ? $('#wpadminbar').height() : 0);
          $('.inner-preview').css({ height: Math.ceil(__height * 0.6)+'px' });
          
        });
      }
    };
    
    /**
     * Insert the content into the modal dialog
     */
    this.load_into_modal = function(){
      
      if ( $('div.cdbt-modal').size() > 0 ) {
        $('div.modal-body').html( $.ajaxResponse.responseText ).trigger('create');
        $.ajaxResponse.responseText = '';
        var modalForm = $('form#' + $('div.modal-body').find('form').attr('id') );
        // Initialize the form components of fuel ux
        modalForm.find('.dropdown-toggle').dropdown();
        modalForm.find('.checkbox-custom').checkbox('enable');
        modalForm.find('.combobox').combobox('enable');
        modalForm.find('.infinitescroll').infinitescroll('enable');
        modalForm.find('.loader').loader('reset');
        modalForm.find('.pillbox').pillbox();
        modalForm.find('.placard').placard('enable');
        modalForm.find('.radio').radio('enable');
        modalForm.find('.search').search('enable');
        modalForm.find('.selectlist').selectlist('enable');
        modalForm.find('.spinbox').spinbox();
        modalForm.find('.tree').tree('render');
        modalForm.find('.wizard').wizard();
        modalForm.find('.repeater').repeater('render');
        modalForm.find('.datepicker').each(function(){
          var parse_id = $(this).attr('id').replace('entry-data-', '').split('-');
          var id = parse_id[0];
          var prev_date = modalForm.find('input[name="custom-database-tables['+id+'][prev_date]"]').val();
          $(this).datepicker({ 
            date: new Date(prev_date), 
            allowPastDates: true, 
            restrictDateSelection: true, 
            momentConfig: { culture: $(this).data('momentLocale'), format: $(this).data('momentFormat') }, 
          });
          $.onTimer = false;
        });
        // Initialize other
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
      if ( $('.panel-table-wrapper table[id^="cdbt-table-edit-' + _.last($.sessionKey.split('-')) + '"]').size() > 0 ) {
        // For table layout
        $('.panel-table-wrapper table[id^="cdbt-table-edit-' + _.last($.sessionKey.split('-')) + '"]').find('tr.selectable.selected label').each(function(){
          where_conditions.push($(this).data('row'));
        });
      } else {
        // For repeater layout
        $('.repeater[id^="cdbt-repeater-edit-' + _.last($.sessionKey.split('-')) + '"]').find('tr.selectable.selected label.repeater-select-checkbox').each(function(){
          where_conditions.push($(this).data('row'));
        });
      }
      where_conditions = _.uniq(where_conditions);
      
      var nonce = $.ajaxResponse.responseText;
      var generate_form = '<form method="post" action="' + location.href + '" id="data-deletion-' + nonce + '" style="display: none;">';
      generate_form += '<input type="hidden" name="action" value="delete_data">';
      generate_form += '<input type="hidden" name="table" value="' + _.last($.sessionKey.split('-')) + '">';
      generate_form += '<input type="hidden" name="_wpnonce" value="' + nonce + '">';
      generate_form += '<input type="hidden" name="where_conditions" value="' + where_conditions.join(',') + '">';
      generate_form += '</form>';
      $('#cdbtDeleteData .modal-body').append(generate_form);
      var delete_form = $('#cdbtDeleteData .modal-body').find('#data-deletion-' + nonce);
      docCookies.setItem( 'once_action', delete_form.attr( 'id' ) );
      delete_form.submit();
      
    };
    
    /**
     * Set global var
     */
    this.set_global_var = function(){
      // do nothing
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
    // @since 2.1.31 Modified
    $(document).on('hidden.bs.modal', 'div.cdbt-modal, #append-dynamic-modal', function(e){
      //if ( $('.cdbt-entry-data-form').size() > 0 ) {
      if ( $(e.target).attr('id') === 'cdbtEditData' ) {
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
  
  // Check empty fields
  // @since 2.0.7 Added new
  // @since 2.0.9 Change to common function
  var checkEmptyFields = function( formId ){
    if ( $('form#' + formId).size() === 1 ) {
      var required_fields = 0;
      var empty_fields = 0;
      $('form#' + formId).find('.form-group').each( function() {
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
  
  
  /**
   * Dynamic table components renderer
   */
  if (typeof DynamicTables !== 'undefined') {
    _.each($('.cdbt-table-wrapper').find('table').map(function(){return this.id; }).get(), function(v){
      var table = new DynamicTables[v]();
      return table.render();
    });
    
    $(document).on('click', '.cdbt-table-wrapper .modal-preview', function(){
      var bin_data = $(this).children('img').attr('src');
      var raw_data = '';
      var post_data = {
        id: 'cdbtModal', 
        insertContent: true, 
        modalTitle: '', 
        modalBody: '', 
      };
      if (typeof bin_data !== 'undefined') {
        raw_data = bin_data;
        var maxImageHeight = $(window).height() - 200 - ($('#wpadminbar').size() ? $('#wpadminbar').outerHeight() : 0);
        post_data.modalTitle = 'image_preview';
        post_data.modalSize = 'large';
        post_data.modalBody = '<div class="preview-image-body"><img src="'+ raw_data +'" class="center-block" style="max-height:'+maxImageHeight+'px"></div>';
      } else {
        raw_data = $(this).text();
        post_data.modalTitle = 'binary_downloader';
        post_data.modalExtras = { 
          'table_name': $(this).data().targetTable, 
          'target_column': $(this).data().columnName, 
          'where_clause': $(this).data().whereConditions, 
        };
      }
      init_modal( post_data );
      
    });
    
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
      // For shortcode "cdbt-view" or "cdbt-edit" on repeater component
      
      $('.dropdown-toggle').dropdown();
      
      $(document).on('rendered.fu.repeater', '.repeater', function(e){
        $(this).find('.sorted').removeClass('sorted');
        var cols = $(this).data('cols').split(',');
        var currentSortCol, currentSortDirection;
        var sortCache = typeof docCookies.getItem === 'function' ? docCookies.getItem('cdbtSortCache') : '';
        var sortCookie = JSON.parse(sortCache);
        if (typeof sortCookie === 'object' && _.size(sortCookie) > 0 && typeof sortCookie[$(this).attr('id')] !== 'undefined') {
          currentSortCol = is_edit_via_shortcode ? cols[sortCookie[$(this).attr('id')][0]] : cols[sortCookie[$(this).attr('id')][0]];
          currentSortDirection = sortCookie[$(this).attr('id')][1];
        } else {
          currentSortCol = $(this).data().currentSortCol;
          currentSortDirection = $(this).data().currentSortDirection;
        }
        if (_.contains(cols, currentSortCol)) {
          $(this).find('thead th.sortable').each(function(){
            var indexNum = _.indexOf(cols, currentSortCol);
            indexNum = is_edit_via_shortcode ? indexNum + 1 : indexNum;
            if ($(this).index() === indexNum) {
              $(this).addClass('sorted').find('.sortable').addClass('sorted');
              var up_down = currentSortDirection === 'asc' ? 'up' : 'down';
              $(this).find('.rlc').attr('class', 'glyphicon rlc glyphicon-chevron-' + up_down);
            } else {
              $(this).find('.rlc').attr('class', 'glyphicon rlc');
            }
          });
        }
      });
      
      $(document).on('click', '.repeater-views', function(){
        adjustCellSize();
      });
      
      $(document).on('click', 'th.sortable', function(e){
        var parentRepeater = $(this).parents('.repeater');
        var sortCache = typeof docCookies.getItem === 'function' ? docCookies.getItem('cdbtSortCache') : '';
        if (parentRepeater.data('cols') === undefined) {
          return false;
        }
        var cols = parentRepeater.data('cols').split(',');
        var indexNum, currentSortDirection, newSortDirection;
        var clickIndex = is_edit_via_shortcode ? $(this).index() - 1 : $(this).index();
        if ('' !== sortCache) {
          var sortCookie = JSON.parse(sortCache);
          if (typeof sortCookie === 'object' && _.size(sortCookie) > 0 && typeof sortCookie[parentRepeater.attr('id')] !== 'undefined') {
            indexNum = sortCookie[parentRepeater.attr('id')][0];
            currentSortDirection = sortCookie[parentRepeater.attr('id')][1];
            if (indexNum !== clickIndex) {
              indexNum = clickIndex;
            } else {
              parentRepeater.data().currentSortDirection = currentSortDirection;
            }
            parentRepeater.data().currentSortCol = cols[indexNum];
          } else {
            indexNum = clickIndex;
            parentRepeater.data().currentSortCol = cols[indexNum];
            currentSortDirection = parentRepeater.data().currentSortDirection;
          }
          newSortDirection = currentSortDirection === 'desc' ? 'asc' : 'desc';
        } else {
          indexNum = clickIndex;
          parentRepeater.data().currentSortCol = cols[indexNum];
          currentSortDirection = parentRepeater.data().currentSortDirection;
          newSortDirection = currentSortDirection === 'desc' ? 'asc' : 'desc';
        }
        sortCache = {};
        sortCache[parentRepeater.attr('id')] = new Array( indexNum, newSortDirection, String(e.timeStamp) );
        docCookies.setItem('cdbtSortCache', JSON.stringify(sortCache));
        parentRepeater.repeater('render');
        adjustCellSize();
      });
      
      $(document).on('click', '.repeater .modal-preview', function(){
        var bin_data = $(this).children('img').attr('src');
        var raw_data = '';
        var post_data = {
          id: 'cdbtModal', 
          insertContent: true, 
          modalTitle: '', 
          modalBody: '', 
        };
        if (typeof bin_data !== 'undefined') {
          raw_data = bin_data;
          var maxImageHeight = $(window).height() - 200 - ($('#wpadminbar').size() ? $('#wpadminbar').outerHeight() : 0);
          post_data.modalTitle = 'image_preview';
          post_data.modalSize = 'large';
          post_data.modalBody = '<div class="preview-image-body"><img src="'+ raw_data +'" class="center-block" style="max-height:'+maxImageHeight+'px"></div>';
        } else {
          var repeater_id = $(this).parents('.repeater').attr('id');
          raw_data = $(this).text();
          post_data.modalTitle = 'binary_downloader';
          post_data.modalExtras = { 
            'table_name': repeater_id.replace(/^cdbt\-repeater\-(view|edit)\-/, ''), 
            'target_column': $(this).data().columnName, 
            'where_clause': $(this).data().whereConditions, 
          };
        }
        init_modal( post_data );
        
      });
      
    }
    adjustCellSize();
  }
  
  /**
   * When edit via shortcode
   * @since 2.1.31 Updated for static table format
   */
  
  // Button effect
  var effect_buttons = function( id ){
    var selectedItem, edit_button, delete_button;
    if (id.indexOf('cdbt-repeater-edit-') > -1) {
      // For repeater
      selectedItem = $('#'+id).repeater('list_getSelectedItems');
      edit_button = $('#'+id+' button#repeater-editor-edit');
      delete_button = $('#'+id+' button#repeater-editor-delete');
    } else {
      // For table
      selectedItem = $('#'+id).find('tbody>tr.selected');
      edit_button = $('.cdbt-table-editor[for="'+id+'"] button#table-editor-edit');
      delete_button = $('.cdbt-table-editor[for="'+id+'"] button#table-editor-delete');
    }
    if (selectedItem.length === 1) {
      edit_button.removeClass('btn-default').addClass('btn-primary').prop('disabled', false);
      delete_button.removeClass('btn-default').addClass('btn-primary').prop('disabled', false);
    } else
    if (selectedItem.length > 1) {
      edit_button.removeClass('btn-primary').addClass('btn-default').prop('disabled', true);
      delete_button.removeClass('btn-default').addClass('btn-primary').prop('disabled', false);
    } else {
      edit_button.removeClass('btn-primary').addClass('btn-default').prop('disabled', true);
      delete_button.removeClass('btn-primary').addClass('btn-default').prop('disabled', true);
    }
  };
  
  // Event handler (for repeater and static table format)
  $('.repeater[id^="cdbt-repeater-edit-"]').on('selected.fu.repeaterList deselected.fu.repeaterList', function(e){
    effect_buttons( $(e.target).attr('id') );
  });
  $(document).on('click', '#repeater-check-switch', function(e){
    effect_buttons( $(e.target).attr('id') );
  });
  $(document).on('click', '.cdbt-table-wrapper table[id^="cdbt-table-edit-"] tr.selectable', function(e){
    if ($(e.target).parents('tbody').size() === 1) {
      $(e.target).parents('tr.selectable').toggleClass('selected').find('.checkbox label').checkbox('toggle');
      effect_buttons( $(e.target).parents('table').attr('id') );
    } else
    if ($(e.target).parents('th.editable-checkbox').size() === 1 || 'editable-checkbox' === e.target.className) {
      $(e.target).parents('tr').find('.checkbox label').checkbox('toggle');
      if ($(e.target).parents('tr').find('.checkbox label').checkbox('isChecked')){
        $(e.target).parents('table').find('tr.selectable').addClass('selected').find('.checkbox label').checkbox('check');
      } else {
        $(e.target).parents('table').find('tr.selectable').removeClass('selected').find('.checkbox label').checkbox('uncheck');
      }
      effect_buttons( $(e.target).parents('table').attr('id') );
    } else {
      return false;
    }
  });
  $(document).on('change', '.cdbt-table-wrapper table[id^="cdbt-table-edit-"] .checkbox input', function(e){
    if ($(e.target).parents('tbody').size() === 1) {
      $(e.target).parents('tr.selectable').toggleClass('selected');
      effect_buttons( $(e.target).parents('table').attr('id') );
    }
  });
  
  $('button[id^="repeater-editor-"],button[id^="table-editor-"]').on('click', function(){
    var dataAction = _.last( $(this).attr('id').split('-') );
    var isRepeater = $(this).attr('id').indexOf('repeater-editor') > -1 ? true : false;
    var targetTable, selectedItem, post_data = {};
    if (isRepeater) {
      targetTable = $(this).parents('.repeater').attr('id').replace('cdbt-repeater-edit-', '');
      selectedItem = $(this).parents('.repeater').repeater('list_getSelectedItems');
    } else {
      var __tmp = $(this).parents('.cdbt-table-wrapper').find('table').attr('id').replace('cdbt-table-edit-', '');
      targetTable = _.initial(__tmp.split('-')).join('-');
      selectedItem = $(this).parents('.cdbt-table-wrapper').find('tbody>tr.selected');
    }
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
          var _whereClause;
          if (isRepeater) {
            _whereClause = $('tr.selectable.selected label').data('row');
          } else {
            _whereClause = selectedItem.find('td.editable-checkbox label').data('row');
          }
          post_data.modalSize = 'large';
          post_data.modalTitle = 'edit_data_form';
          post_data.modalExtras = { 
            'table_name': targetTable, 
            'action_url': window.location.href, 
            'where_clause': _whereClause, 
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
    var formId = form.attr( 'id' );
    form.children('input[name="_wp_http_referer"]').val(location.href);
    if ( checkEmptyFields( formId ) ) {
      docCookies.setItem( 'once_action', form.attr( 'id' ) );
      form.submit();
    } else {
      // Added since 2.0.9
      $(this).popover({ animation: true, content: $.localErrMsg, placement: 'top', trigger: 'hover' }).popover('show');
      $(this).on('hidden.bs.popover', function(){
        $(this).popover('destroy');
      });
      //return false;
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
  
  
  /**
   * Datepicker components of Fuel UX renderer
   */
  if ($('.cdbt-datepicker').size() > 0) {
    var targetForm = $( 'form#' + $('.cdbt-datepicker').parents('form').attr('id') );
    targetForm.find('.cdbt-datepicker').each(function(){
      if ($(this).data().momentLocale && $(this).data().momentFormat) {
        $(this).datepicker({ 
          momentConfig: { culture: $(this).data().momentLocale, format: $(this).data().momentFormat } 
        });
      }
      if ( typeof $(this).data('date') === 'undefined' ) {
        $(this).datepicker( 'getDate', new Date() );
      } else {
        $(this).datepicker( 'setDate', $(this).data('date') );
      }
      var prevDate = $(this).parent().next('input[type=hidden]').val();
      if ( prevDate === '0000-00-00 00:00:00' ) {
        $.onTimer = true;
      } else {
        $.onTimer = false;
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
    
    $(document).on('click', 'button[id^="btn-entry/"]', function(e){
      e.preventDefault();
      var tmp = $(this).attr('id').split('/');
      var entry_form = $('#' + tmp[1]);
      var check_result = checkEmptyFields( tmp[1] );
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
        docCookies.setItem( 'once_action', entry_form.attr( 'id' ) );
        entry_form.submit();
      } else {
      	// Added since 2.0.9
        post_data = {
          id: 'cdbtModal', 
          insertContent: true, 
          modalTitle: 'empty_required_field', 
          modalBody: '', 
        };
        init_modal( post_data );
        //return false;
      }
    });
    
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
  
  
  /**
   * Helper UI scripts for custom column renderer
   */
  $(document).on('click', 'a.collapse-col-data', function(){
    post_data = {
      id: 'cdbtModal', 
      insertContent: true, 
      modalTitle: 'view_item_full', 
      modalBody: $(this).data('raw'), 
    };
    init_modal( post_data );
  });
  
  
});
/**
 * Convert datetime format as common utility function for repeater and dynamicTable
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
  if ('' === format) {
    return arguments[0];
  }
  var formatStrings = format.split('');
  var converted = '';
  var lastDayOfMonth = function(dateObj) {
    var tmp = new Date(dateObj.getFullYear(), dateObj.getMonth() + 1, 1);
    tmp.setTime(tmp.getTime() - 1);
    return tmp.getDate();
  };
  var isLeapYear = function() {
    var tmp = new Date(datetime.getFullYear(), 0, 1);
    var sum = 0;
    for (var i = 0; i < 12; i++) {
      tmp.setMonth(i);
      sum += lastDayOfMonth(tmp);
    }
    return (sum === 365) ? 0 : 1;
  };
  var dateCount = function() {
    var tmp = new Date(datetime.getFullYear(), 0, 1);
    var sum = -1;
    for (var i=0; i<datetime.getMonth(); i++) {
      tmp.setMonth(i);
      sum += lastDayOfMonth(tmp);
    }
    return sum + datetime.getDate();
  };
  _.each(formatStrings, function(str){
    var res, tmp, sum;
    var month = { Jan: 'January', Feb: 'February', Mar: 'March', Apr: 'April', May: 'May', Jun: 'June', Jul: 'July', Aug: 'August', Sep: 'September', Oct: 'October', Nov: 'November', Dec: 'December' };
    var day = { Sun: 'Sunday', Mon: 'Monday', Tue: 'Tuesday', Wed: 'Wednesday', Thu: 'Thurseday', Fri: 'Friday', Sat: 'Saturday' };
    var half_hours = function(){ var h = datetime.getHours(); return h > 12 ? h - 12 : h; };
    var ampm = function(){ var h = datetime.getHours(); return h > 12 ? 'pm' : 'am'; };
    switch(str){
      case 'Y': // Full year
      case 'o': // Full year (ISO-8601)
        res = datetime.getFullYear();
        break;
      case 'y': // Two digits year
        res = ('' + datetime.getFullYear()).slice(-2);
        break;
      case 'm': // Zerofill month
        res = ('0' + (datetime.getMonth() + 1)).slice(-2);
        break;
      case 'n': // Month
        res = datetime.getMonth() + 1;
        break;
      case 'F': // Full month name
        res = _.values(month)[datetime.getMonth()];
        break;
      case 'M': // Short month name
        res = _.keys(month)[datetime.getMonth()];
        break;
      case 'd': // Zerofill day
        res = ('0' + datetime.getDate()).slice(-2);
        break;
      case 'j': // Day
        res = datetime.getDate();
        break;
      case 'S': // Day with suffix
        var suffix = [ 'st', 'nd', 'rd', 'th' ];
        var suffix_index = function(){
          var d = datetime.getDate();
          if ( d === 1 || d === 2 || d === 3 || d === 21 || d === 22 || d === 23 || d === 31 ) {
            return Number(('' + d).slice(-1) - 1);
          } else {
            return 3;
          }
        };
        res = suffix[suffix_index()];
        break;
      case 'w': // Day of the week (number)
      case 'W': // Day of the week (ISO-8601 number)
        res = datetime.getDay();
        break;
      case 'l': // Day of the week (full)
        res = _.values(day)[datetime.getDay()];
        break;
      case 'D': // Day of the week (short)
        res = _.keys(day)[datetime.getDay()];
        break;
      case 'N': // Day of the week (ISO-8601 number)
        res = datetime.getDay() === 0 ? 7 : datetime.getDay();
        break;
      case 'a': // am or pm
        res = ampm();
        break;
      case 'A': // AM or PM
        res = ampm().toUpperCase();
        break;
      case 'g': // Half hours
        res = half_hours();
        break;
      case 'h': // Zerofill half hours
        res = ('0' + half_hours()).slice(-2);
        break;
      case 'G': // Full hours
        res = datetime.getHours();
        break;
      case 'H': // Zerofill full hours
        res = ('0' + datetime.getHours()).slice(-2);
        break;
      case 'i': // Zerofill minutes
        res = ('0' + datetime.getMinutes()).slice(-2);
        break;
      case 's': // Zerofill seconds
        res = ('0' + datetime.getSeconds()).slice(-2);
        break;
      case 'z': // Day of the year
        res = dateCount();
      	break;
      case 't': // Days of specific month
        res = lastDayOfMonth(datetime);
      	break;
      case 'L': // Whether a leap year
      	res = isLeapYear();
      	break;
      case 'c': // Date of ISO-8601
        res = arguments[0].replace(' ', 'T') + '+00:00';
        break;
      case 'r': // Date of RFC-2822
        res = datetime;
        break;
      case 'u': // Micro second
      	res = '000000';
        break;
      case 'e': // Timezone extention
      case 'T': // Timezone
      case 'B': // Swatch time
      case 'I': // Whether a summer time
      case 'O': // Diff from GMT
      case 'P': // Diff from GMT
      case 'Z': // Offset second of timezone
      case 'U': // Unix Epoch seconds
        res = '';
        break;
      default:
        res = str;
        break;
    }
    
    converted += res;
  });
  return converted;
};
/**
 * Utility: Multibyte string functions
 */
var isSurrogatePear = function( upper, lower ) {
  return 0xD800 <= upper && upper <= 0xDBFF && 0xDC00 <= lower && lower <= 0xDFFF;
};
var mb_strlen = function( str ) {
  var ret = 0;
  for (var i = 0; i < str.length; i++,ret++) {
    var upper = str.charCodeAt(i);
    var lower = str.length > (i + 1) ? str.charCodeAt(i + 1) : 0;
    if ( isSurrogatePear( upper, lower ) ) {
      i++;
    }
  }
  return ret;
};
var mb_substr = function( str, begin, end ) {
  var ret = '';
  for (var i = 0, len = 0; i < str.length; i++, len++) {
    var upper = str.charCodeAt(i);
    var lower = str.length > (i + 1) ? str.charCodeAt(i + 1) : 0;
    var s = "";
    if( isSurrogatePear( upper, lower ) ) {
      i++;
      s = String.fromCharCode(upper, lower);
    } else {
      s = String.fromCharCode(upper);
    }
    if (begin <= len && len < end) {
      ret += s;
    }
  }
  return ret;
};
var strip_tags = function( str, allowed ) {
  allowed = ( ( ( allowed || '' ) + '' ).toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
  var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
  return str.replace( commentsAndPhpTags, '' ).replace( tags, function ($0, $1) {
    return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
  });
};
var cdbtCustomColumnFilter = function( value, truncate ){
  truncate = truncate || 100;
  var raw_str = $('<div/>').html( strip_tags( _.unescape( value ), '<a><b><strong><em><i>' ) ).text();
  if ( mb_strlen( raw_str ) > truncate ) {
    var truncate_str = mb_substr( raw_str, 0, truncate - 1 );
    value = truncate_str + '<a href="javascript:;" class="btn btn-default btn-sm collapse-col-data" data-raw="'+ value +'"><i class="fa fa-ellipsis-h" aria-hidden="true"></i> <i class="fa fa-level-up" aria-hidden="true"></i></a>';
  } else {
    value = raw_str;
  }
  
  return value;
};

