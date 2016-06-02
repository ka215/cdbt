# Custom DataBase Tables

**C**ustom **D**ata**B**ase **T**ables is commonly called "**CDBT**",  it is the plugin for the WordPress. Using this plugin, you can create new table on the database of the WordPress as you like. Then you can do input, output and data updating to created table. Also you can do import and export by format of CSV, TSV, JSON and SQL, and duplicate specific table. Those operation is able to be done easily from the GUI tools on the administration screen of WordPress. In addition, you can provide the functional data manipulation interface to the Web front-end by using some shortcodes or APIs.

* Latest: 2.1.32beta
* Stable: 2.1.31
* Source: [https://github.com/ka215/cdbt](https://github.com/ka215/cdbt)
* Public SVN Repository: [http://wordpress.org/plugins/custom-database-tables/](https://wordpress.org/support/plugin/custom-database-tables)
* Home Page: [https://ka2.org/cdbt/](https://ka2.org/cdbt/)
* Twitter: [@ka2bowy](https://twitter.com/ka2bowy)
* Forum: [WordPress.org](https://wordpress.org/support/plugin/custom-database-tables)
* Donate link: [PayPal Donations](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=2YZY4HWYSWEWG&lc=en_US&currency_code=USD&item_name=)

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

[Please see the tutorial for more use of the plugin](https://ka2.org/cdbt/v2/tutorial/) (Sorry, Japanese only)

## Documentation

### [Plugin Documentation](https://ka2.org/cdbt/v2/) (Sorry, Japanese only)

* [Plugin Basic Information](https://ka2.org/cdbt/v2/basic-info/)
* [Plugin Structure](https://ka2.org/cdbt/v2/structure/)
* [Plugin Method Reference](https://ka2.org/cdbt/v2/methods/)
* [Plugin Action Reference](https://ka2.org/cdbt/v2/action-reference/)
* [Plugin Filter Reference](https://ka2.org/cdbt/v2/filter-reference/)

## Features

* You can create newly tables on the database. (CREATE TABLE)
* You can entry data from on the web to your created table. At that time, the entry form will be created automatically in accordance with the table structure.
* You can edit and remove data in the specific table from on the web.
* You can customize the viewing data as a table listed.
* It is corresponding to the input and output of binary data to the table.
* You can import and export with choosing a format of CSV, TSV, JSON and SQL.
* You can duplicate the specific table.
* Using the shortcodes, you can render the interactive data operation interface on the front-end WEB.
* You can set the permission as viewable users, registerable users and editable users to each tables.
* You can manage the core tables built-in WordPress, since the plugin version 2.0.
* Ofcourse, you can remove your created tables. (DROP TABLE)
* You can initialize data of the specific table. (TRUNCATE TABLE)
* You can modify table. (ALTER TABLE)
* Using built-in plugin methods can connect to various core processes as like CRUD.

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
  * If you thought this plugin felt to be useful, you want to cooperate in the development, or would like to contribute, I'm very happy that you make a donation to me. The magnitude of the amount of the donation is not a problem. Your feelings will become motivation of myself to develop this plugin.<br>[PayPal Donations](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=2YZY4HWYSWEWG&lc=en_US&currency_code=USD&item_name=)

## Thanks to the following:

* [Bootstrap](http://getbootstrap.com/) for the foundation of UI/UX.
* [Fuel UX](http://getfuelux.com/) for outputting main components as UX.
* [Underscore.js](http://underscorejs.org/) for operating data source at frontend.
* [moment.js](http://momentjs.com/) for viewing localization date on the components.
* [Font Awesome](http://fortawesome.github.io/Font-Awesome/) for building an intuitive UI.
* [kinetic](https://github.com/davetayls/jquery.kinetic) for viewing component to multidevice.
* [clipboard.js](https://github.com/zenorocha/clipboard.js) for improving the usability of data.
* [phpMyAdmin](http://www.phpmyadmin.net/) for the inspiration.

## Support

I've prepared a [support forum](https://wordpress.org/support/plugin/custom-database-tables). 
Please to slip issuance in here questions about plugin.

Crafted by [ka2](https://ka2.org/) ([@ka2bowy](https://twitter.com/ka2bowy)).
