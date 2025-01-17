# wp-mpdf #

**Contributors:** fkrauthan  , jmenges
**Donate link:** [fkrauthan.de](https://fkrauthan.de)  
**Wordpress plugin page:** [wordpress.org/plugins/wp-mpdf/](https://wordpress.org/plugins/wp-mpdf/)  
**Tags:** print, printer, wp-mpdf, pdf, mpdf  
**Requires at least:** 2.9  
**Tested up to:** 5.7.2
**Stable tag:** 3.6.1


FORK of the original plugin with additional options for setting the theme on a post level and some other minor tweeks.


Print Wordpress posts as PDF. Optional with Geshi highlighting.

## Description ##

Print Wordpress posts as PDF. Optional with Geshi highlighting. It also has support for password protected posts and only logged in users can print post as pdf support.  


## Changelog ##

### 3.6.1 ###
* Fixed release tag to prevent install issues

### 3.6 ###
* Removed manual cron job and used wp-cron instead for cache population (as per wordpress guidelines)
* Removed PHP 4 support (don't think anyone is running that anymore)

### 3.5.2 ###
* Added some small security improvements for the admin page
* Fixed a PHP notice when "allow to print all pages" is disabled (thanks to grandeljay)

### 3.5.1 ###
* Added some small security fixes to the admin page

### 3.5 ###
* Added support to change page format inside template (thanks to conlaccento)

### 3.4 ###
* Made codebase PHP 7.3 compatible (thanks to nopticon)
* Fixed issues with newer wordpress versions (thanks to nopticon)

### 3.3 ###
* Fixed some small bugs
* Updated mpdf to version 6
* Updated geshi to latest version
* Made plugin compatible with PHP 7

### Earlier versions ###

For the changelog of earlier versions, please refer to the separate [changelog.md](./changelog.md) file.


## Installation ##

1. Upload the whole plugin folder to your `/wp-content/plugins/` folder.
1. Set write permission (777) to the plugin dir folder => `/wp-content/plugins/wp-mpdf/cache`
1. Go to the plugins page and activate the plugin.
1. Add to your template "`<?php if(function_exists('mpdf_pdfbutton')) mpdf_pdfbutton(); ?>`" as a small button or "`<?php if(function_exists('mpdf_pdfbutton')) mpdf_pdfbutton(false, 'my link', 'my login text'); ?>`" as a textlink. The second text specifies the text which should displayed if you have checked "needs login" and a user isn't loggend in. (if you wish to open the pdf print in a new tab you may pass "true" for the first parameter)
1. You can adjust some options: in your admin interface, click on plugins and then on wp-mpdf. For allowing or disabling pdf export you can use the checkbox when creating/editing a post or a page.
1. Place your templates into `/wp-content/wp-mpdf-themes`

The mpdf_pdfbutton function signature: `function mpdf_pdfbutton($opennewtab=false, $buttontext = '', $logintext = 'Login!', $print_button = true, $nofollow = false, $options = array())`

The options array supports 'pdf_lock_image' => '/my/image/path/relative/to/wordpress/route' and 'pdf_image' => '/my/image/path/relative/to/wordpress/route' to overwrite which icon should be used.


## License ##

This file is part of wp-mpdf.

**wp-mpdf is free software:** you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.


wp-mpdf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with wp-mpdf. If not, see [www.gnu.org/licenses/](http://www.gnu.org/licenses/).


## Publish to wordpress ##

### Setup svn ###

You need to install the subversion client on your local system

### Commit changes to git ###

Make sure that all your changes are committed to the repository. The deployment script will stop in case there are uncommitted changes to prevent any mistakes.


### Publish new version ###

You just need to execute the `release.sh` script. It will take care of some basic validation, and the full publish process.

	./release.sh
