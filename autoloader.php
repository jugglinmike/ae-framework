<?php

class FrameworkAutoloader {

  private $_path_cache = array();

  protected $_directories;

  public function loadClass ($class) {
    // All my classes and file names should match. THIS IS LAW.
    // lowercase all the things
    $lower_class = strtolower($class); //lololol
    $class_filename = $lower_class.'.php';
    $folder_root = __DIR__;

    //check if it's in the cache and the file still exists
    if (array_key_exists($lower_class, $this->_path_cache) && file_exists($this->_path_cache[$lower_class])) {
      require_once $this->_path_cache[$lower_class];
    } else {
      $_directories = new RecursiveDirectoryIterator($folder_root);

      foreach(new RecursiveIteratorIterator($_directories) as $file) {
        if ($file->getExtension() !== 'php'){
          continue;
        }

        $full_path = $file->getRealPath();

        // if it's my file, then require it
        if (strtolower($file->getFilename()) == $class_filename) {
          require_once $full_path;
          $this->_path_cache[$lower_class] = $full_path;
          return;
        }

        // if it's a php file get the class name, and if it's not in the array, cache it
        $lower_filename = strtolower($file->getBasename('.php'));
        $this->_path_cache[$lower_filename] = $full_path;
      }
    }
  }

  public function __construct() {
    // register it or it won't load a damn thing
    spl_autoload_register(array($this, 'loadClass'));
  }
}

$autoload = new FrameworkAutoloader();






