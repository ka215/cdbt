=== Custom DataBase Tables ===
Contributors: ka2
Donate link: https://ka2.org/donation/cdbt/
Tags: custom database tables, MySQL, database, table, create, delete, select, insert, update, truncate, drop, alter table, import, export, CSV
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: 2.1.34
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Using this database management tool, You can create freely own table in  MySQL database, and do input and output of data in an intuitive operation.

== Description ==

WordPress database is easy to use with simple, but if you want to handle the data that does not conform to the provided initial table structure, or considering the use of as the CMS, you should create a newly table as a better case. 

This plugin provides the ability to be able to add a new table freely in the database (direct on MySQL) of WordPress in such a case, and can be management of data in a simple user interface. **However, since the plugin version 2.x, you need to be PHP version 5.4 or more on the environment**. 

When you use the various shortcodes, built-in methods, and APIs that is provided a rich on this plugin, WordPress might be transformed into a powerful CMS.

[Please see here for more documentation of the plugin](https://ka2.org/cdbt/).

== Installation ==

1. Upload `custom-database-tables` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the Custom DB Tables submenu in setting menu

That's it. Now you can easily start creating custom table in database of WordPress

If needed, please install the legacy version any it is from [here](https://wordpress.org/plugins/custom-database-tables/developers/).
Also, lastest legacy version is [1.1.15](https://downloads.wordpress.org/plugin/custom-database-tables.1.1.15.zip).

== Frequently Asked Questions ==

= Can not activate the latest plugin =

This plugin must be working environment of PHP 5.4 since version 2.
Please use the version 1 system in previous environment of PHP 5.3.
You can be downloaded [past versions from here](https://wordpress.org/plugins/custom-database-tables/developers/).

= Does this plugin create table on direct mysql? =

Yes, can create tables in a MySQL database and manage that's tables while use this plugin.
However, in the current version plugin can connect to only a MySQL database was installed of WordPress, yet. In other words, it can connect only a MySQL database connection settings are defined in "wp-config.php".

= Is there any limit of the scope of the table? =

You need a table that is managed by the plug-in is an "ID" is the primary key. The column that contains the update date and registration date of the line will also be necessary. Column These keys are added automatically when you create a table.

= Can put table that have 100,000 or more rows? =

There is no particular restriction on the amount of data that is stored in a table. Processing performance on a table with a large number of rows will depend on the structure such as a table or database server.
However, you should enable the "Ajax Loading" in the shortcode's options if you want to handle the table that has a large amount of data by the shortcodes.

= What should we do when there is not displayed the data via shortcode? =

In most cases, that's cause of the jQuery file conflict. Therefore please try to do procedures as follows:

1. Go to the "General Settings" tab on the "CDBT Plugin Options" screen.
2. Uncheck the checkbox of "jQuery" in the "Front-end Screen" column at the "Loading Resources" item of the "Advanced Plugin Settings".
3. Click the "Save Changes" button.

Note: you should change in the "Administration Screen" column at above step 2 If the same trouble is occurred in the tables management screen.

= How do we use the shortcode at the outer the post content? =

The shortcodes of this plugin basically work within the post content. If you want to work the shortcodes outside of the post content (as direct built-in template, or in the widget), you should insert code below.

`
if ( ! is_admin() ) {
  global $cdbt;
  add_action( 'init', array( $cdbt, 'cdbt_pre_shortcode_render' ), 10, 2 );
}
`

= How can we protect the "wp-admin" directory by using as like ".htaccess"? = 

Because this plugin is working all Ajax processing via the "wp-admin/admin-ajax.php", only that file in the ".htaccess" must have been to be able to access.
In that case, please add a description of the following to the ".htaccess" under the "wp-admin".

`
<FilesMatch "(admin-ajax.php)$">
  Satisfy Any
  Order allow,deny
  Allow from all
  Deny from none
</FilesMatch>
`

= How shall we act on if using custom permalink structure? =

When you use this plugin on the site that has custom permalink structure as like "your-domain/custom-path/wp-admin", you should add code of filter hook below.

`
function my_cdbt_shortcode_custom_component_options( $component_options, $shortcode_name, $table ){
  if ( is_admin() && $shortcode_name === "cdbt-edit" ) {
    $component_options['actionUrl'] = admin_url( str_replace( '/wp-admin', '', $component_options['actionUrl'] ) );
  }
  return $component_options;
}
add_filter( 'cdbt_shortcode_custom_component_options', 'my_cdbt_shortcode_custom_component_options', 10, 3 );
`


== Screenshots ==

1. On this plugin, you will be able to manipulate various tables of the database from wordpress management screen.
2. You can create free their own table in the database.
3. Your database creation can be intuitively designed with a drag-and-drop.
4. Since version 2, you can also manage the core tables that is built in WordPress.
5. You can easily operate data in any tables via simply and useful interface.
6. By the multi-functional shortcodes, you are able to provide any tables to the front-end visitors.
7. With the Web API features, you will be able to allow table operation from other than your own site.
8. Sorry, please use the previous version 1.x if your environment has PHP 5.3 or less.
9. The legacy version of the plugin is possible to get from [here](https://downloads.wordpress.org/plugin/custom-database-tables.1.1.15.zip).

== Changelog ==

= 2.1.34 =
* Fixed a bug that memory overflow occurs when renders table that has many data via shortcode (if checked "Adding Ajax Support", and undefined "Max Rows Per Page").
* Fixed a bug that renders via non-Ajax repeater layout when using custom shortcode even if checked "Adding Ajax Support".
* Fixed a bug that had used the display label as search value when set number as filter value in the "Filter Definition" of the shortcode options.
* Fixed a bug that faild to insert data if hide the column that has "not null" and "empty default".
* Improved layout of combobox field rendered via the "cdbt-entry" shortcode.
* Improved processes when finished output buffering in use this plugin.
* Enhanced internal processes to concat searchable data by the "cdbt_find_concat_columns" filter. Then added new filters named "cdbt_find_concat_separator" and "cdbt_find_concat_value".
* Added new action of named "cdbt_before_truncate_table" for truncating data in table that has foreign key.
* Added some cases to the [FAQ](https://wordpress.org/plugins/custom-database-tables/faq/).
* Updated the links for donating.

= 2.1.33 =
* Added new feature of ajax loading on the shortcode.
* Added a new filter named "cdbt_shortcode_datetime_format" for customizing the display date time format on the shortcode.
* Added the platform for using the add-ons to extend plugin.
* Enhanced the data narrowing on the shortcode (supported "IN" and "BETWEEN" queries). [More details can be found here](http://demo.ka2.org/sample-shortcode-for-using-the-enhanced-narrowing-condition/).
* Improved the UI and a bug about the related the "created" as an auto insertion column.
* Fixed a bug that was not working properly to truncate string that contains the quotation.
* Were disabled "clipboard.js" on the Safari because unsupported.
* Others, added newly 2 methods, 3 action hooks and improved already existing some methods.

= 2.1.32 =
* Changed the string truncation processing of table data in the management screen, from current fixed 100 characters to newly 40 characters as the initial value.
* Added new features that is able to choose the clickable columns and the truncating columns in the shortcode of "cdbt-view" and "cdbt-edit".
* Added new features that is able to toggle the draggable table and choose the footer interface on the table layout of the shortcode.
* In the "cdbt_shortcode_custom_forms" filter, it became able to get rightly "cdbt-edit" at the second argument of shortcode name.
* Added new filter of "cdbt_shortcode_query_conditions" to be able to filter the WHERE clause before issuing sql query in the "cdbt-view" and the "cdbt-edit".
* Fixed a bug that has been excluded such columns from search results if the column value is null when narrowed down the data by the UNION query.

= 2.1.31 =
* Changed the version notation specifications of the plugin: (Major version number).(Minor version number).(Cumulative version number) and append the development stage display if necessary.
* Supported completely to the static table component of non-repeater as an output format of "cdbt-view" and "cdbt-edit" shortcode.
* Added a new feature to render data of JSON format via "cdbt-view" shortcode.
* Added the ability to copy the specific string like shortcode, referenceable SQL with one click to the clipboard.
* Revised English in the management screen (almost entirely completed).
* Merged the Cookie control processing that was different in the front-end and the management screen.
* Fixed a bug that process of value truncation does not work if it contains as like a slash or spaces at the column name.
* Fixed a bug that could not import the data using the SQL file.
* Besides, fixed some minor bugs, and enhanced several interfaces.

= 2.0.12 =
* Fixed a bug in when converting the datetime format of the data outputted.
* Adjusted the behavior of the data sorting of repeater components that are output in "cdbt-view" and "cdbt-edit".
* Fixed a bug that did not retrieve data in the specified row on the popped out editing form if had hidden the column of primary key at the "cdbt-edit".
* Fixed a bug that session continuation of the specified table in the table management was unstable.
* Corresponded for WordPress 4.5.x.

= 2.0.11 =
* Added the "cdbt_admin_truncate_strings" filter for the number of character truncation for the table management.
* Added new option for changing the label name of registration button on the "cdbt-entry".
* Modified compatible with the column name that contains a space.
* Fixed a bug in the takeover process of the API host settings and the table permission settings at the time of upgrade to v2.x from v1.x.
* Fixed a bug that occur an error if the table contain column of "set" type without values.

= 2.0.10 =
* Revised some of the source text (translation original text).
* Added new feature that can truncate the value of the string type column by the specified number of characters.
* Fixed a bug that could not open "Messages" option when the debug mode is OFF.
* Fixed a bug that can edit only data of the first shortcode if you have multiple shortcodes of "cdbt-edit" in one page.
* Fixed a bug of loading process by the setting of "include assets".

= 2.0.9 =
* Added new feature of option that can overwrite notification messages.
* Added the feature to refer the column information for the specified table at the time of shortcode registration and editing.
* Did enhancement some components on the dashboard of the plugin management.
* Fixed a bug that was a flaw in the acquisition condition of the column of the datetime format when you edit data in the table without primary key.
* Fixed a bug that registration button of the shortcode "cdbt-entry" was not working at version 2.0.8.

= 2.0.8 =
* Fixed a bug that had been created the same number of shortcodes and rows, if you had registered the data when using multiple shortcodes at the same time on one page.
* Fixed a bug in the filter of "cdbt_select_clause_optimaize".
* Fixed a bug when using "Included assets setting" in the plugin options.
* Fixed a bug that "get_data" method did not work if in column of the target row included a particular character as like single-byte space, when at the table without a primary key.
* Added the section of "SQL of Create Table" into the "Operate Table" tab on the "CDBT Tables Management".
* Prevented php notice error when opening modal on the "cdbt-edit" shortcode.

= 2.0.7 =
* Added option to change the plugin menu position.
* Added option to change of loading any assets.
* Some concatenate assets had subdivided. (bootstrap, fuel UX).
* Added option whether to use a modal as the notification method on the management screen.
* Added the initialization function of the option settings.
* Added the function "get_table_charset" for get the charset of the table.
* Added option to prevent the duplicate sending.
* Added the sanitization option in the table setting. (the WordPress core table does not  sanitized).
* Added to be able to specify conditions of the join operator to the "find_data" (you can use a "narrow_operator" attribute at "[cdbt-view]" and "[cdbt-edit]").
* Added of the filter `cdbt_select_clause_optimaize` for filtering the select clause of SQL query.
* Changed of the "run_query" to public method, and it became to able to use PDO and mysqli library.
* Changed of the "compare_reservation_tables" to public method.
* Changed to use methods of "pack" and "unpack" of the binary data conversion process.
* Had bundled the latest assets. ("jQuery v2.2.1", "font-awesome v4.5.0", "fuelux v3.13.1", "moment v2.11.2")
* Fixed a bug that could not edit data if table has a multiple checkbox form.
* Fixed a bug that had not display for interpreting as a "false" of boolean value if the registration data is "0".
* Fixed a bug that the values obtained from bit type field was often null.
* Other, fixed the 9 bugs, had enhancemented the 6 features.

= 2.0.6 =
* Fixed a bug in the session and cookie processing for the continuous transmission prevention.
* Fixed a bug that edit button can not click on the shortcode "cdbt-edit" at the frontend side.
* Enabled the feature of filter in the shortcode "cdbt-edit", because it has been implementation leakage.
* Fixed the wrong loading order of multi-language translation file.
* Added attributes of "narrow_keyword" and "sort_order" to the shortcode of "cdbt-edit".

= 2.0.5 =
* Fixed a bug that had been left in the processing of the attribute of "hidden_cols" at shortcode "cdbt-entry".
* Fixed a bug that can not be entered for a table with a specific column name at the shortcode "cdbt-entry".
* Transmitting data at shortcode cdbt-entry is corresponding an issue where would have been multiplexed registered. (for correspondence of some tickets)
* Fixed a bug that will be not generated SQL when you set of "None" to the "key/index" at the "table creator".
* Fixed a bug that unnecessary attributes to the shortcode that is generated after the preview at the shortcodes management will be added.
* Added a filter "cdbt_before_insert_data" just before you insert the data into the database via "insert_data" method.
* Added a filter "cdbt_before_update_data" and "cdbt_before_update_where" just before you update the data to the database via "update_data" method.
* Added an attribute of "redirect_url" in which you can specify the redirect URL after the data registration at shortcode "cdbt-entry".

= 2.0.4 =
* Fixed the conflict of some javascripts (Ex, Deregister a bundled jquery in wordpress).
* Resolved the problem of case in which fuelux class is missing from the body.
* Changed to use customized jQueryUI from jQueryUI that is bundled with WordPress.
* The untranslation text on the dashboard had been localized. (for ticket: [English version](https://wordpress.org/support/topic/english-version-11?replies=1) )
* Fixed a bug that occurs error when the debug log file does not exist.
* Fixed a bug that can not click edit button when the data editing on the modal window. (for ticket: [Can't validate Edit popup](https://wordpress.org/support/topic/cant-validate-edit-popup?replies=1) )
* Fixed a bug of the rendering order of immediately text before the shortcode was wrong. (for ticket: [Shortcode displays before the text that precedes it.](https://wordpress.org/support/topic/shortcode-displays-before-the-text-that-precedes-it?replies=1) )
* Fixed a bug that attribute of "hidden_cols" does not work at cdbt-entry. (for ticket: [Custom DataBase Tables cdbt-entry](https://wordpress.org/support/topic/custom-database-tables%E3%80%80-cdbt-entry?replies=1) )
* Fixed a bug that useless output has been performed when will be activated plugin.

= 2.0.3 =
* Fixed a bug that WebAPI request URI generation was not working.
* Changed some WebAPIs management processes.
* Removed of the not allowed processing of "base64_encode()" and "base64_decode()", then replaced by other processing.
* Corrected that some buttons is not working after import to table.

= 2.0.2 =
* Fixed a bug that "table creator" does not work from v2.0.1.
* Fixed a bug that warning occurs when it get a table authority.

= 2.0.1 =
* Added of checking the PHP version when activated of this plugin.
* Added the operating environment in the change log and description of plugin.
* Fixed the bug that attribute of "sort_order" and  "exclude_cols" in  the shortcodes was not working.
* Others, have fixed some minor problems.

= 2.0.0 =
* Changed the operating environment of the plugin to PHP5.4 or more.
* Added a new feature that is able to manage the wordpress core tables.
* Added a new feature that is able to duplicate specific table.
* Added a new feature that is able to import and export of using more file types than csv.
* Changed of outputting list to the repeater component via fuel ux.
* It was implemented debug mode.
* Others, have strengthen a variety of features.

= 1.1.15 =
* Hotfixed a bug that was some leakage to rename the function of "cdbt_sanitize_for_php()".

= 1.1.14 =
* Added a new shortcode of "cdbt-submit" to submit custom insert and update queries from web front-end.
* Added a protected method of run_query() for running custom queries.
* Added an attribute "image_render" for direct viewing the images stored in database on outputted list by "cdbt-extract".
* Added a new method of update_where() to update with where clause that customizable of conditions.
* Changed to be able to omit the "entry-page" attribute from shortcode of "cdbt-edit".
* Adjusted a such etcetera as changing the dependencies of each class.
* Modified a shortcode of "cdbt-extract" as become no error and skip process if that attributes has not exists column.
* Fixed a bug that shortcode of "cdbt-extract" can not show modal for image preview.
* Fixed a bug that was defining a "null" as strings when set a null in default value of column of varchar on the table-creator.
* Fixed a bug that was removed the already have saved binary data when edited other columns in same record.

= 1.1.13 =
* Added a feature that switched to full or shorten code at display example of shortcode on the home position.
* Added a feature of the binary file (image) preview on "cdbt-extract" shortcode.
* Added a button that can create a table immediately in the home position when the table not yet been created or the table is not specified.
* Adjusted the user interface in some page and shortcodes.
* Fixed a bug that can not insert data into the column that contains a comma or a space in the field name.
* Fixed a bug that can not insert data into a column of type "bit" from the input data page.
* Fixed a bug that had not deleted data of plugin when you will uninstall this plugin with enable of the cleanup options.

= 1.1.12 =
* Fixed the bug that could not use the features such as creating table on Firefox and Internet Explorer browser.
* Fixed the improper regex in SQL validation process for alter table.

= 1.1.11 =
* Fixed the improper regex in SQL validation process. [here is issue detail](https://github.com/ka215/cdbt/issues/7)
* Fixed a bug when importing CSV file. [here is issue detail](https://github.com/ka215/cdbt/issues/6)
* Fixed a bug that same request is called again when closed the alert at the time of data registration completion.
* Updated some of the translation text.

= 1.1.10 =
* Fixed a typo of plugin UI. [here is issue detail](https://github.com/ka215/cdbt/issues/5)
* Fixed a bug that has generating a bad SQL of bool type column, when create table using "table-creator". [here is issue detail](https://github.com/ka215/cdbt/issues/4)

= 1.1.9 =
* Fixed a bug that have included no data in the downloaded a csv file when you export data of the table that does not have the "created" column.

= 1.1.8 =
* Resolved the problem that sortable content (on the Table Creator) of jQuery UI in some browsers, such as Firefox can be selected.
* Have unique reduction to grant a prefix to the constant name of the plug-core, was modified to be able to avoid a conflict of constants.

= 1.1.7 =
* Added a shortcode "cdbt-extract" that view lists of specifying number from the results of sorting and searching the data in the table.
* Fixed a bug where at the time of site access gone useless header sent if the API key does not exists in the query.

= 1.1.6 =
* Changed the reading position of the plugins dedicated inline JavaScript in management page.
* Newly added the API function that outputs the search result of table data in JSON and JSONP format.
* Added the ability to access the managable tables under the plugin from an external site by using generated API key.
* Extended the mime-type of importable CSV file: "application/vnd.ms-excel", "application/octet-stream", "text/plain", "text/csv", "text/tsv"
* Updated methods (delete_data, find_data, insert_data, update_data).
* Added a registration datetime (column named "created") in the target columns when editing table data.

= 1.1.5 =
* Fixed a bug that resume of external table does not work if the table that you created in plugins have none.

= 1.1.4 =
* Added the ability to sort the column for each data list when editing and viewing data (also possible ON / OFF in shortcodes).
* Improved bad usability that pagination is bloated when there is a large amount of data.
* Improved the user experience of each page of the data registration, viewing, and edit.
* Fixed a bug that causes an error when you enter zero for the column of floating point data type when registering.
* Fixed a bug in the search function of the page of the viewing and editing of data.
* Modal window within the content that is output in the shortcode, has solved the problem that can not be manipulated by the theme you want to use.

= 1.1.3 =
* Was extended to allow updating of the table structure using presets.
* Fixed bug that couldn't be updated table option with do not issue a SQL of alter table.
* Fixed bug that happen error when deployed the array in block of foreach arguments on the specific version of PHP.

= 1.1.2 =
* Fixed bug that failed to upload a CSV that is not Excel format.
* Fixed bug that was failing of setting up plugin options when updated the plugin version.

= 1.1.1 =
* Fixed a bug when uninstall the plugin.

= 1.1.0 =
* Have been confirmed in the normal operation on the WordPress 4.0
* Be able to incorporated into plugin as a managable table the tables that already exists (this feature is an experimental implemented yet).
* Can resume table from in the past table settings.
* Did optimize processing when the plugin is stop and uninstall.
* Fixed a bug in the create_table method.
* Changed specifications of update_data method and insert_data method.
* Add to new method get_table_list.

= 1.0.0 =
* Add to new feature to modify table (alter table)
* Some debugs, and has improved the user interface

= 0.9.6 =
* Updated the translate-template file (.pot)
* Changed how to import stylesheet and javascript
* Add some screenshot images and revise readme.txt

= 0.9.5 =
* First beta release

= 0.9.1 =
* First review version (alpha release)


== Other Notes ==

All official development on this plugin is on GitHub. Published version will bump here on WordPress.org. You can find the repository at [https://github.com/ka215/cdbt](https://github.com/ka215/cdbt).

Detailed documentation has published at the site of author. If you are free, Please try to qv.

* [Version 2.x related documentation](https://ka2.org/cdbt/v2/)
* [Version 1.x related documentation](https://ka2.org/cdbt/v1/)

(Sorry, for about documentation will be only the Japanese version currently.)

== Upgrade Notice ==

= 2.1.31 =
Minor upgrade version 2.1 has improved the user experience.

= 2.0.8 =
Hotfix of fixing the bug that had set an invalid select clause in select query.

= 2.0.6 =
Because there had improvemented around the session, please use after you delete your browser's cache and Cookie once.
