# Custom DataBase Tables

__C__ustom __D__ata__B__ase __T__ables is commonly called "__CDBT__",  it is the plugin for the WordPress. Using this plugin, you can create new table on the database of the WordPress as you like. Then you can do input, output and data updating to created table. Also you can do import and export by format of CSV, TSV, JSON and SQL, and duplicate specific table. Those operation is able to be done easily from the GUI tools on the administration screen of WordPress. In addition, you can provide the functional data manipulation interface to the Web front-end by using some shortcodes or APIs.

* Source: [https://github.com/ka215/cdbt](https://github.com/ka215/cdbt)
* Public SVN Repository: [http://wordpress.org/plugins/custom-database-tables/](https://wordpress.org/support/plugin/custom-database-tables)
* Home Page: [https://ka2.org/cdbt/](https://ka2.org/cdbt/)
* Twitter: [@ka2bowy](https://twitter.com/ka2bowy)
* Forum: [WordPress.org](https://wordpress.org/support/plugin/custom-database-tables)
* Donate link: [donations](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=2YZY4HWYSWEWG&lc=en_US&currency_code=USD&item_name=)

## Requirements

| Prerequisite    | How to check | How to install
| --------------- | ------------ | ------------- |
| PHP >= 5.4.x    | `php -v`     | php.net([English](http://php.net/manual/en/install.php)/[Japanese](http://php.net/manual/ja/install.php)) |
| Node.js >=5.4.x  | `node -v`    | [nodejs.org](http://nodejs.org/) |
| gulp >= 3.9.x  | `gulp -v`    | `npm install -g gulp` |
| Bower >= 1.7.2 | `bower -v`   | `npm install -g bower` |

For more installation notes, refer to the [Install gulp and Bower](#install-gulp-and-bower) section in this document.

### Install gulp and Bower {#install-gulp-and-bower}

Building the theme requires [node.js](http://nodejs.org/download/). We recommend you update to the latest version of npm: `npm install -g npm@latest`.

From the command line:

1. Install [gulp](http://gulpjs.com) and [Bower](http://bower.io/) globally with `npm install -g gulp bower`
2. Navigate to the theme directory, then run `npm install`
3. Run `bower install`

You now have all the necessary dependencies to run the build process.

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

[Please see the tutorial for more use of the plugin](https://ka2.org/cdbt/v1/tutorial/) (Sorry, Japanese only)

## Documentation

### [Plugin Documentation](https://ka2.org/cdbt/toc/) (Sorry, Japanese only)

* [Plugin configuration files](https://ka2.org/cdbt/toc/)
* [Plugin methods](https://ka2.org/cdbt/toc/)
* [Plugin APIs](https://ka2.org/cdbt/toc/)
* [Plugin shortcodes](https://ka2.org/cdbt/toc/)

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

GPLv2 or later

## Contributing

1. Fork it
  * Create your feature branch (`git checkout -b my-new-feature`)
  * Install the dependencies and run gulp (`npm i && gulp`)
  * Commit your changes (`git commit -am 'Add some feature'`)
  * Push to the branch (`git push origin my-new-feature`)
  * Create new Pull Request
2. Donate
  * If you thought this plugin felt to be useful, you want to cooperate in the development, or would like to contribute, I'm very happy that you make a donation to me. The magnitude of the amount of the donation is not a problem. Your feelings will become motivation of myself to develop this plugin.

## Thanks to the following:

* [Bootstrap](http://getbootstrap.com/) for the foundation of UI/UX.
* [Fuel UX](http://getfuelux.com/) for outputting main components as UX.
* [Underscore.js](http://underscorejs.org/) for operating data source at frontend.
* [moment.js](http://momentjs.com/) for viewing localization date on the components.
* [Font Awesome](http://fortawesome.github.io/Font-Awesome/) for building an intuitive UI.
* [phpMyAdmin](http://www.phpmyadmin.net/) for the inspiration.

## Support

I've prepared a [support forum](https://wordpress.org/support/plugin/custom-database-tables). 
Please to slip issuance in here questions about plugin.

Crafted by [ka2](https://ka2.org/) ([@ka2bowy](https://twitter.com/ka2bowy)).
