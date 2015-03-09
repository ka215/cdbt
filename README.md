# Custom DataBase Tables

Custom DB Tables called "__CDBT__" is plugin of WordPress. This plugin is able to create new tables on database of WordPress as you like. Also, the input and output and editing data to created tables, as importing and exporting by CSV can be done easily from the GUI tool on the admin panel of WordPress. In addition, you can provide the functionality to the data input and editing from the Web front-end by using some shortcodes or API.

* Public SVN Repository: [http://wordpress.org/plugins/custom-database-tables/](https://wordpress.org/support/plugin/custom-database-tables)
* Home Page: [http://cdbt.ka2.org/](http://cdbt.ka2.org/)
* Twitter: [@ka2bowy](https://twitter.com/ka2bowy)
* Forum: [support forum](http://ka2.org/cdbt-forum/forum/support-forum/) or [WordPress.org](https://wordpress.org/support/plugin/custom-database-tables)
* Donate link: [donations](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=2YZY4HWYSWEWG&lc=en_US&currency_code=USD&item_name=)

## Installation

Install after downloaded from WordPress admin panel or [download here](https://github.com/ka215/cdbt) and put the plugins directory.

How to install WordPress provisions are as follows.

1. Upload `custom-database-tables` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the Custom DB Tables submenu in setting menu

That's it. Now you can easily start creating custom table in database of WordPress

## Usage

Navigate to the Custom DB Tables submenu in setting menu of WordPress admin panel.

First of all, please try to create the database required for the first.

[Please see the tutorial for more use of the plugin](http://ka2.org/cdbt/tutorials/) (Sorry, Japanese only)

## Documentation

### [Plugin Documentation](http://ka2.org/cdbt/documentation/) (Sorry, Japanese only)

* [Plugin configuration files](http://ka2.org/cdbt/documentation/plugin-files/)
* [Plugin methods](http://ka2.org/cdbt/documentation/methods/)
* [Plugin APIs](http://ka2.org/cdbt/documentation/apis/)
* [Plugin shortcodes](http://ka2.org/cdbt/documentation/shortcodes/)

## Features

* You can create any tables to the database.
* Data entry from on the web to your created table. At that time, the entry form will be created automatically in accordance with the table structure.
* Entry data will able to edit from on the web.
* Entry data can view as a table listed.
* Correspond to the input and output of binary data to the table.
* You can import data from the table by the CSV file, and data export.
* Edit page and viewer, and entry form of your created tables can be also attached on the front end WEB by using shortcodes.
* About viewing, editing and using entry form for each tables, you are also possible to grant access limited by user privileges of WordPress.
* Your created tables can deleted. (drop table)
* All data of any table can initialize. (truncate table)
* You can modify table. (alter table)
* Using API functions can connect to CRUD of plugin core.

## License

GPLv2

## Contributing

1. Fork it
* Create your feature branch (`git checkout -b my-new-feature`)
* Install the dependencies and run gulp (`npm i && gulp`)
* Commit your changes (`git commit -am 'Add some feature'`)
* Push to the branch (`git push origin my-new-feature`)
* Create new Pull Request
2. Donate
  If you thought this plugin felt to be useful, you want to cooperate in the development, or would like to contribute, I'm very happy that you make a donation to me. The magnitude of the amount of the donation is not a problem. Your feelings will become motivation of myself to develop this plugin.



## Thanks to the following:

* [Bootstrap](http://getbootstrap.com/) for the foundation of UI/UX.
* [phpMyAdmin](http://www.phpmyadmin.net/) for the inspiration.

## Support

I've prepared a [support forum](http://ka2.org/cdbt-forum/forum/support-forum/). 
Please to slip issuance in here questions about plugin.

Crafted by [ka2](http://ka2.org/) ([@ka2bowy](https://twitter.com/ka2bowy)).
