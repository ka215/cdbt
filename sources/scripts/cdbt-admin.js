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
  $.isDebug = 'true' === cdbt_admin_vars.is_debug ? true : false;
  $.ajaxUrl = cdbt_admin_vars.ajax_url;
  $.ajaxNonce = cdbt_admin_vars.ajax_nonce;
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
        $('.conbobox').combobox();
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
    // repeater();
    _.each(repeater, function(k,v){ return repeater[v](); });
    
    var locationToOperation = function( post_raw_data ) {
      var post_data = {
        'session_key': post_raw_data.sessionKey, 
        'default_action': post_raw_data.operateAction, 
        'target_table': post_raw_data.targetTable, 
        'callback_url': post_raw_data.baseUrl, 
      };
      return cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
    };
    
    if (_.contains([ 'cdbtAdminTables', 'cdbtWpCoreTables' ], $('.repeater').attr('id'))) {
      $('.cdbt-repeater-left-main>a').on('click', function(){
        locationToOperation( _.extend($(this).data(), { sessionKey: 'operate_table' }) );
      });
      
      $('.operate-table-btn-group>button').on('click', function(){
        locationToOperation( _.extend($(this).data(), { sessionKey: 'operate_table' }) );
      });
      
      $('.operate-data-btn-group>button').on('click', function(){
        locationToOperation( _.extend($(this).data(), { sessionKey: 'operate_data' }) );
      });
    }
    
    
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
  if ('' !== $('#message').text()) {
    if ($.isDebug) {
      var post_data = {
        id: 'cdbtModal', 
        insertContent: true, 
        modalTitle: 'notices_' + $('#message').attr('class'), 
        modalBody: $('#message').html(), 
      };
      init_modal( post_data );
    } else {
      $('#message').show();
    }
  }
  
  
  /**
   * Helper UI scripts for create table section
   */
  if ('cdbt_tables' === $.QueryString.page && 'create_table' === $.QueryString.tab) {
    // Table name live preview
    var livePreview = function(table_name) {
      if ($('#instance_prefix_switcher').checkbox('isChecked')) {
        table_name = $('#create-table-table_name div.input-group-addon').text() + table_name;
      }
      $('#live_preview code').text(table_name);
      $('input[name="custom-database-tables[table_name]"]').val(table_name);
    };
    $('#instance_table_name').on('change keypress keyup paste', function(){
      livePreview($(this).val());
    });
    
    // Table prefix switching
    var prefixSwitcher = function(is_chk) {
      if (is_chk) {
        $('#create-table-table_name div.input-group-addon').removeClass('sr-only');
      } else {
        $('#create-table-table_name div.input-group-addon').addClass('sr-only');
      }
      livePreview($('#instance_table_name').val());
    };
    $('.checkbox input[name="instance_prefix_switcher"]').on('change', function(){
      prefixSwitcher( $('#instance_prefix_switcher').checkbox('isChecked') );
    });
    prefixSwitcher( $('#instance_prefix_switcher').checkbox('isChecked') );
    
    // Make a template from the set value
    $('#create-sql-support').on('click', function(){
      
      // use underscore.js
      var sql_template = _.template("CREATE TABLE <%= tableName %> ( <%= columnDefinition %> ) <%= tableOptions %>;");
      
      var table_name = '', table_options = [], columns = [], keyindex = [];
      if ('' !== $('input[name="custom-database-tables[table_name]"]').val()) {
        table_name = '`' + $('input[name="custom-database-tables[table_name]"]').val() + '`';
      }
      
      if ('' !== $('input[name="custom-database-tables[table_db_engine]"]').val()) {
        table_options.push( 'ENGINE=' + $('input[name="custom-database-tables[table_db_engine]"]').val() );
      }
      if ('' !== $('input[name="custom-database-tables[table_charset]"]').val()) {
        table_options.push( 'DEFAULT CHARSET=' + $('input[name="custom-database-tables[table_charset]"]').val() );
      }
      if ('' !== $('input[name="custom-database-tables[table_comment]"]').val()) {
        table_options.push( 'COMMENT=\'' + $('input[name="custom-database-tables[table_comment]"]').val() + '\'' );
      }
      
      if ($('#automatically-add-columns1').checkbox('isChecked')) {
        columns.push( '`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT \'ID\'' );
        keyindex.push( 'PRIMARY KEY (`ID`)' );
        table_options.push( 'AUTO_INCREMENT=1' );
      }
      if ($('#automatically-add-columns2').checkbox('isChecked')) {
        columns.push( '`created` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\' COMMENT \'Created Datetime\'' );
      }
      if ($('#automatically-add-columns3').checkbox('isChecked')) {
        columns.push( '`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT \'Updated Datetime\'' );
      }
      columns = columns.concat(keyindex);
      
      $('#create-table-create_table_sql').val( sql_template({ tableName: table_name, columnDefinition: "\n" + columns.join(",\n"), tableOptions: "\n" + table_options.join(' ')  }) );
      
    });
    
  }
  
  
  /**
   * Helper UI scripts for modify table section
   */
  if ('cdbt_tables' === $.QueryString.page && 'modify_table' === $.QueryString.tab) {
    // Change modify table
    $('#modify-table-action-change_table').on('click', function(e) {
      e.preventDefault();
      if ('' === $('#modify-table-change_table').selectlist('selectedItem').value) {
        return false;
      }
      
      post_data = {
        'session_key': 'modify_table', 
        'target_table': $('#modify-table-change_table').selectlist('selectedItem').value, 
        'callback_url': './admin.php?page=cdbt_tables&tab=modify_table', 
      };
      cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
    });
    
    // Undo button action
    $('button[id^="btn-undo-modify-table-"]').on('click', function() {
      var undo_target = $(this).attr('id').replace('btn-undo-', '');
      $('#' + undo_target).val($(this).data().prevValue);
    });
    
    // The effect occurs if changed
    $('input,button,.dropdown-menu>li>a').on('click blur', function(){
      $('.form-group').each(function(){
        var current_value = $(this).find('input[id^="modify-table-"]').val();
        if ('undefined' !== current_value && '' !== current_value) {
          if (typeof $(this).find('button[id^="btn-undo-"]') !== 'undefined') {
            if ($(this).find('button[id^="btn-undo-"]').attr('data-prev-value')) {
              var prev_value = $(this).find('button[id^="btn-undo-"]').attr('data-prev-value');
              if (current_value !== String(prev_value)) {
                $(this).addClass('has-success');
              } else {
                $(this).removeClass('has-success');
              }
            }
          }
        }
      });
    });
    $('textarea[id^="modify-table-"]').on('blur', function(){
      if ($(this).val() !== $('#btn-undo-' + $(this).attr('id')).data().prevValue) {
        $(this).parents('.form-group').addClass('has-success');
      } else {
        $(this).parents('.form-group').removeClass('has-success');
      }
    });
    
    // Count modification items
    var count_modification_items = function(){
      var selector_str = '';
      var modification_items = 0;
      if (arguments.length !== 1) {
        selector_str = '.form-group';
      } else {
        selector_str = '#' + arguments[0] + ' .form-group';
      }
      
      $(selector_str).each(function(){
        if ($(this).hasClass('has-success')) {
          modification_items++;
        }
      });
      
      return modification_items;
    };
    
    $('#submit-alter-table, #submit-update-options').on('click', function(e){
      e.preventDefault();
      var form_id = $(this).parents('form').attr('id');
      if (count_modification_items(form_id) > 0) {
        $('#' + form_id).trigger('submit');
      } else {
        post_data = {
        	id: 'cdbtModal', 
          insertContent: true, 
          modalTitle: 'changing_item_none', 
          modalBody: '', 
        };
        init_modal( post_data );
      }
    });
    
  }
  
  
  /**
   * Helper UI scripts for operate table section
   */
  if ('cdbt_tables' === $.QueryString.page && 'operate_table' === $.QueryString.tab) {
    
    $('button[id^="operate-table-action-"]').on('click', function(e) {
//      if ('' === $('#operate-table-target_table>ul.dropdown-menu').find('li[data-selected="true"]').attr('data-value')) {
      if ('' === $('#operate-table-target_table').selectlist('selectedItem').value) {
        e.preventDefault();
        return false;
      }
      var new_action = _.last($(this).attr('id').split('-'));
      if ('change_table' === new_action) {
        new_action = 'detail';
      }
      $('input[name="custom-database-tables[operate_action]"]').val(new_action);
      $('button[id^="operate-table-action-"]').removeClass('active');
      $(this).addClass('active');
      
//      $common_modal_hide = "$('input[name=\"custom-database-tables[operate_action]\"]').val('detail'); $('button[id^=\"operate-table-action-\"]').removeClass('active'); $('button[id^=\"operate-table-action-detail\"]').addClass('active');";
      $common_modal_hide = "$('input[name=\"custom-database-tables[operate_action]\"]').val('detail'); $('form.navbar-form').trigger('submit');";
      
      var post_data = {};
      if ('' === $('input[name="custom-database-tables[operate_current_table]"]').val()) {
        post_data = {
        	id: 'cdbtModal', 
          insertContent: true, 
          modalTitle: 'table_unknown', 
          modalBody: '', 
          modalHideEvent: $common_modal_hide, 
        };
        init_modal( post_data );
      } else {
        switch(new_action) {
        	case 'detail': 
        	case 'import': 
        	case 'export': 
        	case 'duplicate': 
            $('section').each(function() {
              if (new_action === $(this).attr('id')) {
                $(this).attr('class', 'show');
              } else {
                $(this).attr('class', 'hidden');
              }
            });
        	  break;
          case 'truncate': 
            post_data = {
        	    id: 'cdbtModal', 
              insertContent: true, 
              modalTitle: 'truncate_table', 
              modalBody: '', 
              modalHideEvent: $common_modal_hide, 
              modalExtras: { 'table_name': $('input[name="custom-database-tables[operate_current_table]"]').val() }, 
            };
            init_modal( post_data );
            break;
          case 'modify': 
            post_data = {
              'session_key': 'modify_table', 
              'target_table': $('input[name="custom-database-tables[operate_current_table]"]').val(), 
              'callback_url': './admin.php?page=cdbt_tables&tab=modify_table', 
            };
            cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
            break;
          case 'backup': 
            
            // Have not yet implemented
            
            break;
          case 'drop': 
            post_data = {
        	    id: 'cdbtModal', 
              insertContent: true, 
              modalTitle: 'drop_table', 
              modalBody: '', 
              modalHideEvent: $common_modal_hide, 
              modalExtras: { 'table_name': $('input[name="custom-database-tables[operate_current_table]"]').val() }, 
            };
            init_modal( post_data );
            break;
          default:
            
            $('form.navbar-form').trigger('submit');
            
            break;
        }
      }
      
    });
    
    // OnChange import file type
    $('#import-table-upload_filetype').on('changed.fu.selectlist', function(){
      if (_.contains(['csv', 'tsv'], $(this).selectlist('selectedItem').value)) {
        toggle_item('switching-item-add_first_line', 'show');
      } else {
        toggle_item('switching-item-add_first_line', 'hide');
      }
    });
    var toggle_item = function(selector, action){
      if ('show' === action) {
        $('#' + selector).show();
      } else {
        $('#' + selector).hide();
      }
    };
    // When onLoaded toggle
    if (_.contains(['csv', 'tsv'], $('#import-table-upload_filetype').selectlist('selectedItem').value)) {
      toggle_item('switching-item-add_first_line', 'show');
    } else {
      toggle_item('switching-item-add_first_line', 'hide');
    }
    
    
    // Submit import step 1
    $('#button-submit-import_step1').on('click', function(e){
      var values = _.pluck($('#import-table-add_first_line').pillbox('items'), 'value');
      $('#import-table-add_first_line-instance').val(values.join(','));
    });
    
    // Switch of all checking of checkbox
    $('button[id^="switch-checkbox-"]').on('click', function(){
      var target_checkbox_container_id = 'export_columns' === $(this).attr('id').replace('switch-checkbox-', '') ? 'export-table-target_columns' : 'import-table-target_columns';
      $('#' + target_checkbox_container_id + ' .checkbox input').checkbox('toggle');
    });
    
    // Switch the display according to the selected value of the select list
    $('#export-table-download_filetype').on('changed.fu.selectlist', function(){
      if (_.contains(['csv', 'tsv'], $(this).selectlist('selectedItem').value)) {
        $('#switching-item-add_index_line').show();
      } else {
        $('#switching-item-add_index_line').hide();
      }
    });
    if ($('#export-table-download_filetype').size() > 0) {
      if (_.contains(['csv', 'tsv'], $('#export-table-download_filetype').selectlist('selectedItem').value)) {
        $('#switching-item-add_index_line').show();
      } else {
        $('#switching-item-add_index_line').hide();
      }
    }
    
    // Run of exporting table util first confirmation
    $('#button-submit-export_table').on('click', function(e){
      e.preventDefault();
      var export_columns = [];
      $('[id^="export-table-target_columns"]').each(function(){
        if ($(this).checkbox('isChecked') && typeof $(this).children().children('input').val() !== 'undefined') {
          export_columns.push($(this).children().children('input').val());
        }
      });
      var post_data = {
        id: 'cdbtModal', 
        insertContent: true, 
        modalTitle: 'export_table', 
        modalHideEvent: "$('input[name=\"custom-database-tables[operate_action]\"]').val('export');", 
        modalExtras: {
          'export_filetype': $('#export-table-download_filetype').selectlist('selectedItem').value, 
          'add_index_line': $('#export-table-add_index_line').checkbox('isChecked') ? $('#export-table-add_index_line input').val() : '', 
          'output_encoding': $('#export-table-output_encoding').selectlist('selectedItem').value, 
          'export_columns': export_columns, 
          'export_table': $('[name="custom-database-tables[export_table]"]').val(), 
        },
      };
      init_modal( post_data );
    
    });
    
    // Run of exporting table after confirmation
    $(document).on('click', '#run_export_table', function(){
      $('#cdbt_file_download_flag').val('true');
      $('#form-export_table').submit();
    });
    
    // Run of truncating table after confirmation
    $(document).on('click', '#run_truncate_table', function(){
      var post_data = {
        'table_name': $('input[name="custom-database-tables[operate_current_table]"]').val(), 
        'operate_action': $('input[name="custom-database-tables[operate_action]"]').val(), 
        'event': 'truncate_table', 
      };
      cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
    });
    
    // Run of dropping table after confirmation
    $(document).on('click', '#run_drop_table', function(){
      var post_data = {
        'table_name': $('input[name="custom-database-tables[operate_current_table]"]').val(), 
        'operate_action': $('input[name="custom-database-tables[operate_action]"]').val(), 
        'event': 'drop_table', 
      };
      cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
    });
    
  }
  
  
  /**
   * Helper UI scripts for operate data section
   */
  if ('cdbt_tables' === $.QueryString.page && 'operate_data' === $.QueryString.tab) {
  
    $('button[id^="operate-table-action-"]').on('click', function(e) {
      if ('' === $('#operate-table-target_table>ul.dropdown-menu').find('li[data-selected="true"]').attr('data-value')) {
        e.preventDefault();
        return false;
      }
      var new_action = _.last($(this).attr('id').split('-'));
      if ('change_table' === new_action) {
        new_action = 'view';
      }
      $('input[name="custom-database-tables[operate_action]"]').val(new_action);
      $('button[id^="operate-table-action-"]').removeClass('active');
      $(this).addClass('active');
      
      $common_modal_hide = "$('input[name=\"custom-database-tables[operate_action]\"]').val('view'); $('form.navbar-form').trigger('submit');";
      
      var post_data = {};
      if ('' === $('input[name="custom-database-tables[operate_current_table]"]').val()) {
        post_data = {
        	id: 'cdbtModal', 
          insertContent: true, 
          modalTitle: 'table_unknown', 
          modalBody: '', 
          modalHideEvent: $common_modal_hide, 
        };
        init_modal( post_data );
      } else {
        switch(new_action) {
        	case 'view': 
        	case 'entry': 
        	case 'edit': 
            
            post_data = {
              'session_key': $.QueryString.tab, 
              'default_action': new_action, 
              'target_table': $('section').attr('data-target_table'), 
              'callback_url': './admin.php?page=' + $.QueryString.page + '&tab=' + $.QueryString.tab, 
            };
            return cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
            
          default:
            
            $('form.navbar-form').trigger('submit');
            
            break;
        }
      }
      
    });
    
    // Button effect
    $('.repeater[id^="cdbt-repeater-edit-"]').on('selected.fu.repeaterList deselected.fu.repeaterList', function(){
      var selectedItem = $(this).repeater('list_getSelectedItems');
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
    });
    
    // Event handler
    $('button[id^="repeater-editor-"]').on('click', function(){
      var dataAction = _.last($(this).attr('id').split('-'));
      var selectedItem = $('.repeater[id^="cdbt-repeater-edit-"]').repeater('list_getSelectedItems');
      var post_data = {};
//      console.info([dataAction, selectedItem.length]);
      
      $common_modal_hide = "$('input[name=\"custom-database-tables[operate_action]\"]').val('edit'); $('form.navbar-form').trigger('submit');";
      
      switch(dataAction){
        case 'refresh': 
          post_data = {
            'session_key': $.QueryString.tab, 
            'default_action': 'edit', 
            'target_table': $('section').attr('data-target_table'), 
            'callback_url': './admin.php?page=' + $.QueryString.page + '&tab=' + $.QueryString.tab, 
          };
          return cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
          
        case 'edit': 
          post_data = {
            id: 'cdbtEditData', // 'cdbtModal', 
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
              'table_name': $('section').attr('data-target_table'), 
              'action_url': '/wp-admin/admin.php?page=' + $.QueryString.page + '&tab=' + $.QueryString.tab, // location.href
              'page': $.QueryString.page, 
              'active_tab': $.QueryString.tab, 
              'where_clause': $('tr.selectable.selected input.row_where_condition').val(), 
            };
          }
          init_modal( post_data );
          
          break;
        case 'delete': 
          post_data = {
            id: 'cdbtDeleteData', // 'cdbtModal', 
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
        'session_key': $.QueryString.tab, 
        'table_name': $('input[name="custom-database-tables[operate_current_table]"]').val(), 
        'operate_action': $('input[name="custom-database-tables[operate_action]"]').val(), 
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
      var where_conditions = [];
      $('tr.selectable.selected input.row_where_condition').each(function(){
        where_conditions.push($(this).val());
      });
      var post_data = {
        'table_name': $('input[name="custom-database-tables[operate_current_table]"]').val(), 
        'operate_action': $('input[name="custom-database-tables[operate_action]"]').val(), 
        'event': 'delete_data', 
        'where_conditions': where_conditions, 
      };
      return cdbtCallAjax( $.ajaxUrl, 'post', post_data, 'script' );
    });
    
    
    
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
