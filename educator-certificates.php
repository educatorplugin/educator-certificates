<?php
/*
Plugin Name: Educator Certificates
Plugin URI: http://educatorplugin.com/add-ons/educator-certificates/
Description: Adds the certificates feature to the Educator plugin
Author: educatorteam
Author URI: http://educatorplugin.com/
Version: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: edr-crt
*/

/*
Copyright (C) 2015 http://educatorplugin.com/ - contact@educatorplugin.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) exit();

require 'includes/edr-crt-main.php';

new Edr_Crt_Main( __FILE__ );
