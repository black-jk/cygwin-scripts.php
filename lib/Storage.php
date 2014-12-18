<?php
  if (defined('INC_CLASS_STORAGE')) return;
  define('INC_CLASS_STORAGE', TRUE);
  
  class Storage {
    
    // ====================================================================================================
    
    public $name;
    
    // --------------------------------------------------
    
    function __construct($name = "Storage", $read = TRUE, $dir = STORAGE_ROOT) {
      $this->setDir($dir);
      $this->setName($name);
      
      if ($read && file_exists($this->getFilePath())) {
        $this->read();
      }
    }
    
    
    
    // ====================================================================================================
    
    protected $_dir;
    protected $_filename;
    
    public function getFilePath() {
      return "{$this->_dir}/{$this->_filename}";
    }
    
    // --------------------------------------------------
    
    public function setDir($dir) {
      $dir = realpath($dir);
      if (!$dir) {
        throw new Exception("[" . __CLASS__ . "] [{$this->name}] [dir] value empty!");
      }
      $this->_dir = $dir;
    }
    
    // --------------------------------------------------
    
    public function setName($name) {
      $this->name = $name;
      $this->_filename = "{$name}.data";
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Data]
    // ----------------------------------------------------------------------------------------------------
    
    public $data = array();
    
    // --------------------------------------------------
    
    public function read() {
      $path = $this->getFilePath();
      Logger::debug("[" . __CLASS__ . "] [{$this->name}] [read] {$path}");
      
      $source = file_get_contents($path);
      $this->data = unserialize($source);
    }
    
    // --------------------------------------------------
    
    public function save() {
      $dir = dirname($this->_dir);
      if (!is_dir($dir)) {
        Logger::debug("[" . __CLASS__ . "] [{$this->name}] [mkdir] {$dir}");
        exec("mkdir -p {$dir}");
      }
      
      $path = $this->getFilePath();
      Logger::debug("[" . __CLASS__ . "] [{$this->name}] [write] {$path}");
      $serialized_data = serialize($this->data);
      return file_put_contents($path, $serialized_data);
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
  }
?>
