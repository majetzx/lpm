# Live PHP Manual
### Ajax-powered PHP manual search engine

[https://github.com/majetzx/lpm](https://github.com/majetzx/lpm)

## About
Live PHP Manual (`lpm`) is a PHP manual search engine, with AJAX features.

Most of the functions, modules, INI-directives, classes, keywords, operators, etc.
defined by PHP and found in its manual are available through this search engine.

Results are shown as you type, for an easier and faster access to the PHP manual.

## Version
3.0-beta -- 2013-04-05

## Requirements
- PHP 5.3 or more
- local installation of the PHP documentation, many HTML files version
([see here for downloads](http://www.php.net/download-docs.php))

## Installation
Copy all files inside your web server, adapt `config.inc.php` to your setup,
mainly the `PHP_MANUAL_ROOT` variable.

If needed, copy the `ini.list.html` file from the documentation in the `extra`
directory and run `generate_ini.php` to generate the latest version of INI directives.
(A version is already provided.)

## Copying
Copyright (c) 2005-2013 Jerome Marilleau

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
