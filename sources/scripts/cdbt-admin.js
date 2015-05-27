/*!
 * Custom DataBase Tables v2.0.0 (http://ka2.org)
 * Copyright 2014-2015 ka2@ka2.org
 * Licensed under GPLv2 (http://www.gnu.org/licenses/gpl.txt)
 */
$(function() {
  
  /**
   * Utility functions
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
   * 
   */
  
  
  /**
   * Common display notice handler
   */
  $('#message').show();
  
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
    repeater();
    
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
      console.log({
        done: stat,
        data: data,
        xhr: xhr
      });
      //alert( xhr.responseText );
      return data;
    });
    
    jqXHR.fail(function(xhr, stat, err) {
      console.log({
        fail: stat,
        error: err,
        xhr: xhr
      });
      //alert( xhr.responseText );
    });
    
    jqXHR.always(function(res1, stat, res2) {
      console.log({
        always: stat,
        res1: res1,
        res2: res2
      });
      if (stat === 'success') {
        //alert('Ajax Finished!');
      }
    });
    
  };
  
  /**
   * `<a>` tag was clicked, then executes an AJAX processing before transition to the link destination.
   */
  $('a').on( 'click', function(e) {
    e.preventDefault();
    if (typeof $(this).attr('data-ajax-url') !== 'undefined' && $(this).attr('data-ajax-url') !== '') {
      var post_data = {};
      if (typeof $(this).attr('data-ajax-data') !== 'undefined' && $(this).attr('data-ajax-data') !== '') {
        var data_list = $(this).attr('data-ajax-data').split(',');
        _.each(data_list, function(val) {
          var splits = val.split(':');
          post_data[splits[0]] = splits[1];
        });
      }
      post_data.callback_url = $(this).attr('href');
      cdbtCallAjax( $(this).attr('data-ajax-url'), 'post', post_data, 'script' );
    } else {
      location.href = $(this).attr('href');
    }
  });
  
  
  
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
