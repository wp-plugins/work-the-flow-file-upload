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
 * Utilities for use on admin side only
 */
if (!is_admin()) {
    die('you must be an administrator to gain access to this file.');
}

/**
 * Admin only utilities for deletion of files and other back end only 
 * utility methods.
 */
class Wtf_Fu_Admin_Utils {
    

    /**
     * Safely delete a file or directory.
     * 
     * Will recursively delete entire directory tree if $file_to_delete
     * is a directory.
     * 
     * Does sanity checks to make sure that files are under 
     * wp_upload_dir() and paths do not contain '/../'
     * 
     * @param type $file_to_delete
     */
    static function safe_delete_files($file_to_delete, $echo = false) {

        /*
         * First make sure everything is ok with the path to delete.
         * We only allow deletes to occur under the wp_upload directory.
         */

        if (!file_exists($file_to_delete)) {
            return false;
        }

        $paths = wp_upload_dir();
        $safe_path = $paths['basedir'];

        $safe_length = strlen($safe_path);

        if ($safe_length < 15 || substr($file_to_delete, 0, $safe_length) !== $safe_path) {
            die("failed to remove files for directory $dir only "
                    . "files under $safe_path are allowed to be deleted.");
        }

        /*
         * Make sure there are no ..'s in the path following the base path. 
         */
        $the_rest = substr($file_to_delete, $safe_length);
        
        /* 
         * Check for match of 
         * ../ or ..\ or  /../  or \..\  or \../ or /..\ 
         * in the path after the upload directory.
         * 
         * Note that this partial path should always begin with / or \ 
         * but just in case wp_upload_dir returns a trailing / in the future, 
         * we don't assume that it will here.
         * 
         * If for some reason wp is set up on a .. path that is ok.
         * as long as it matches the upload path.
         * 
         * But it is not ok after the upload path.
         */
        $pattern = '/[\\\\\\/]?\\.\\.[\\\\\\/]/';

        if ( preg_match($pattern, $the_rest) ) {
            die("relative directory paths may not be used in files for deletion. "
                    . "$file_to_delete");
        }

        /*
         * ok. now it should be safe to proceed with the deleting.
         */
        if (is_dir($file_to_delete)) {
            $ret = self::recurse_rmdir($file_to_delete, $echo);
        } else {
            $ret = unlink($file_to_delete);
        }
        if ($echo) {
            echo '<br/>' . $file_to_delete . $ret ? ' deleted successfully.' : '  FAILED TO DELETE.';
        }
        return $ret;
    }

    /**
     * Recursively removes a directory of files.
     * 
     * Use with extreme caution. Do NOT call this directly 
     * 
     * Use safe_delete_files() or create your own safe wrapper instead 
     * which first does some sanity checking on the filenames. 
     * 
     * This method is made private to enforce use of a wrapper.
     * 
     * @param string  $dir  the directory to delete.
     */
    private static function recurse_rmdir($dir, $echo = false) {

        $deleted_files = array();
        $deleted_files[$dir] = array();

        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $ret = self::recurse_rmdir($file);
            } else {
                $ret = unlink($file);
            }
            if ($echo) {
                echo sprintf("<p>File      : %s %s</p>",
                  $file, $ret ? ' deleted successfully.' : '  FAILED to delete.');
            }
        }
        
        $ret = rmdir($dir);

        if ($echo) {
            echo sprintf("<p>Directory : %s %s</p>",
                  $dir, $ret ? ' removed successfully.' : '  FAILED to remove.');
        }
        
        return $ret;
    }
    
    

} // End class
