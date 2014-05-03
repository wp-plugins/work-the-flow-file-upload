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

/* 
 * Functions that require php >= 5.3.0 
 * 
 * Introduced to add conditionally add namespaced classes without breaking 
 * with parse erros under php < 5.3.0
 */

function wtf_fu_53_do_inline_style_conversion($html, $css) {
    require_once(plugin_dir_path(__FILE__) . 'CssToInlineStyles-master/CssToInlineStyles.php');
    
    $obj = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($html, $css);
    return $obj->convert();
}
