<?php
/*
Plugin Name: Educator Certificates
Plugin URI: http://educatorplugin.com/
Description: Adds certificates feature to the Educator plugin
Author: educatorplugin
Version: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: edr-cert
*/

if ( ! defined( 'ABSPATH' ) ) exit();

require 'includes/edr-crt-main.php';

new Edr_Crt_Main( __FILE__ );
