=== Custom DataBase Tables ===
Contributors: ka2
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=2YZY4HWYSWEWG&lc=en_US&currency_code=USD&item_name=
Tags: custom database tables, MySQL, database, table, create, delete, select, insert, update, truncate, drop, alter table, import, export, CSV
Requires at least: 4.0
Tested up to: 4.4.2
Stable tag: 2.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Custom DataBase Tables plugin allows you to perform data storage and reference by creating a free tables in database of WordPress.

== Description ==

WordPress database is easy to use with simple, but if you want to handle the data that does not conform to the provided initial table structure, or considering the use of as the CMS, is more better to create a new table. 

This plugin provides the ability to be able to add a new table freely in the database (direct on MySQL) of WordPress in such a case, and can be management of data in a simple user interface. **At the since plugin version 2.x works with PHP5.4 or more**. 

If you make by full use the various APIs, methods, and shortcodes that is provided a rich set on this plugin, WordPress might be transformed into a powerful CMS.

[Please visit here for more description of the plugin](http://cdbt.ka2.org/).

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
* Changed of the "run_query" to public method, and it became to able to use PDO and mysqli library.
* Changed of the "compare_reservation_tables" to public method.
* Changed to use methods of "pack" and "unpack" of the binary data conversion process.
* Had bundled the latest assets. ("jQuery v2.2.1", "font-awesome v4.5.0", "fuelux v3.13.1", "moment v2.11.2")
* Fixed a bug that could not edit data if table has a multiple checkbox form.
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

= 2.0.6 =
Because there had improvemented around the session, please use after you delete your browser's cache and Cookie once.
