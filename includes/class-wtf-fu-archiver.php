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

class Wtf_Fu_Archiver extends ZipArchive {

    private $include_archives = true;

    /*
     * files with these extensions will be ignored if $include_archives is false.
     */
    private $archive_extensions = array('zip', 'rar', 'gz', '7z', 'bz2', 'cab');

    /**
     * recursively add files from a directory.
     * 
     * @param type $dir
     * @param type $base
     */
    private function addDirectory($dir, $base = 0) {

        $ret = true;
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $ret = $this->addDirectory($file, $base);
            } else {
                $localname = substr($file, $base);
                $ret = $this->add_file($file, $localname);
            }
            if ($ret !== true) {
                break;
            }
        }
        return $ret;
    }

    /**
     * Create a zip file archive of the passed file or directory.
     * Will recursively add all files if a directory is specified in $filename.
     * 
     * @param type $zipfile       name of the archive file to create.
     * @param type $inc_archives  true to include other archive files in the
     *                            this archive. default is true. 
     * @return bool true if archive was successfully created.
     */
    public function create_archive($zipfile, $inc_archives = true) {

        log_me("creating archive $zipfile");
        $overwrite = false;
        if (file_exists($zipfile)) {
            $overwrite = true;
        }
        $this->include_archives = (bool) $inc_archives;

        $ret = $this->open($zipfile, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE);

        if ($ret !== true) {
            return ("Error opening Archive $zipfile " . $this->error_message($ret));
        }
        return $ret;
    }

    public function add_file_or_dir($filename) {

        log_me(array("add_file_or_dir" => $filename));
        $ret = true;
          
        $basename = basename($filename);
        
        if (is_dir($filename)) {
            $ret = $this->addDirectory($filename, strlen($filename) - strlen($basename));
        } else {
            $ret = $this->add_file($filename, $basename);
        }
        
        return $ret;
    }

    public function close_archive() {
        return($this->close());
    }

    /**
     * adds a file is not excluded.
     * checks for file existance before attempting to add the file.
     * 
     * @param type $filename
     * @param type $localname
     * @return mixed true or error message if failure.
     */
    private function add_file($filename, $localname) {

        $ret = true;
        if ($this->include_archives || !$this->is_archive($localname)) {
            if (file_exists($filename)) {
                /* call base class to actually add the file */
                $ret = parent::addFile($filename, $localname);
                if ($ret === false) {
                    $ret = "Error adding the file $filename to the archive";
                }
            } else {
                log_me("file $filename does not exist");
                $ret = "file $filename does not exist";
            }
        }
        return $ret;
    }

    /**
     * Returns true if the filename has an extension defined as an 
     * archive extension in $this->archive_extensions.
     * 
     * @param type $filename
     * @return bool true if filename has an archive extsension.
     */
    private function is_archive($filename) {
        $ext = $this->get_file_extension($filename);
        return (in_array($ext, $this->archive_extensions));
    }

    /**
     * returns the filename extension eg zip, png etc.
     * this is the final string after the last '.' in 
     * $flename
     * 
     * @param string $filename
     * @return string the file extension.
     */
    private function get_file_extension($filename) {
        $filename = strtolower($filename);
        $exts = explode('.', $filename);
        $n = count($exts) - 1;
        $exts = $exts[$n];
        return $exts;
    }

    private function error_message($code) {

        $message = "failed with error code $code ";

        switch ($code) {
            case ZipArchive::ER_EXISTS :
                $message .= 'File already exists.';
                break;
            case ZipArchive::ER_INCONS :
                $message .= 'Zip archive inconsistent.';
                break;
            case ZipArchive::ER_INVAL :
                $message .= 'Invalid argument.';
                break;
            case ZipArchive::ER_MEMORY :
                $message .= 'Malloc failure.';
                break;
            case ZipArchive::ER_NOENT :
                $message .= 'No such file.';
                break;
            case ZipArchive::ER_NOZIP :
                $message .= 'Not a zip archive.';
                break;
            case ZipArchive::ER_OPEN :
                $message .= 'Can\'t open file.';
                break;
            case ZipArchive::ER_READ :
                $message .= 'Read error.';
                break;
            case ZipArchive::ER_SEEK :
                $message .= 'Seek error.';
                break;
        }
        return $message;
    }

}
