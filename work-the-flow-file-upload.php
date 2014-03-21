<?php
/*
 * @wordpress-plugin
 * Plugin Name:       Work the Flow - File Upload
 * Plugin URI:        http://wtf-fu.com
 * Description:       Manage workflow and front-end html5 file uploads. Front end user file uploads may be embedded in workflows or used independantly via shortcodes. Back end provides management of user upload files including archiving. Utilizes javascript libraries by blueimp/jQuery-File-Upload.
 * Version:           1.1.2
 * Author:            Lynton Reed
 * Author URI:        http://wtf-fu.com
 * Text Domain:       wtf-fu
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */
 
/*  
 *   Copyright 2013  Lynton Reed  (email : lynton@wtf-fu.com)
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License, version 2, as 
 *   published by the Free Software Foundation.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Work the Flow - File Upload Plugin
 *
 * This is the main entry point for the Wtf-Fu plugin.
 *
 * @package   Wtf-Fu
 * @author    Lynton Reed <lynton@wtf-fu.com>
 * @license   GPL-2.0+
 * @link      http://wtf-fu.com
 * @copyright 2013 Lynton Reed
 */




// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'includes/wtf-fu-common-utils.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/class-wtf-fu.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Wtf_Fu', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Wtf_Fu', 'deactivate' ) );

/*
 * Instantiate or retrieve an instance of the main class, Wtf_Fu, once the 
 * plugin has loaded. Class Wtf_Fu is defined in `class-wtf-fu.php`
 */
add_action( 'plugins_loaded', array( 'Wtf_Fu', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/



/*
 * Load the admin class only if we are *really* admin and not admin just 
 * because of a front end AJAX call.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
    require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wtf-fu-admin.php' );
    add_action( 'plugins_loaded', array( 'Wtf_Fu_Admin', 'get_instance' ) );  
}
