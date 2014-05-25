<?php
/*  Copyright 2013  Lynton Reed  (email : lynton@wtf-fu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * Fired when the plugin is uninstalled.
 */
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-wtf-fu-option-definitions.php';

$options = get_option(wtf_fu_OPTIONS_DATA_PLUGIN_KEY);

if ($options == false) {
    // no options exist so nothing to be done.
    error_log("exiting ..uninstall.php called but plugin options= " . print_r($options, true));
    exit;
}

error_log("uninstall.php called plugin options=" . print_r($options, true));

if (array_key_exists('remove_all_data_on_uninstall', $options ) && $options['remove_all_data_on_uninstall'] == true) {
    
    global $wpdb;
    
    /*
     * Delete everything from the options table with a matching name of '%wtf-fu%' 
     */
    $results = $wpdb->get_results(
            $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE '%s'", "%wtf-fu%"));
    
    //error_log("1. results=" . print_r($results, true));

    /*
     * Delete all user options from the usermeta table.
     */
    $results = $wpdb->get_results(
            $wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '%s'", "%wtf-fu%"));
    
    //error_log("2. results=" . print_r($results, true));
}


