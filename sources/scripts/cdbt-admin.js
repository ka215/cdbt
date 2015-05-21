/*!
 * Custom DataBase Tables v2.0.0 (http://ka2.org)
 * Copyright 2014-2015 ka2@ka2.org
 * Licensed under GPLv2 (http://www.gnu.org/licenses/gpl.txt)
 */
$(function() {

  $('#message').show();
  
  $('#welcome-wizard').wizard();
  
  if (typeof repeater !== 'undefined') {
    repeater();
  }
  
});
