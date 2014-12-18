<?php
  if (defined('INC_CLASS_BASE_CONTROLLER')) return;
  define('INC_CLASS_BASE_CONTROLLER', TRUE);
  
  class BaseController {
    
    public $app_name = 'Base';
    
    // --------------------------------------------------
    
    function __construct() {
      $this->_initialize();
    }
    
    // --------------------------------------------------
    
    protected function _initialize() {
      Main::$application = $this;
      Main::$applicationName = $this->app_name;
      
      $logger = Logger::getInstance();
      $logger->app_name = $this->app_name;
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Methods]
    // ----------------------------------------------------------------------------------------------------
    
    public function appInfo() {
      echo "=================================================\n" .
           "[{$this->app_name}]\n" .
           "=================================================\n";
    }
    
    // --------------------------------------------------
    
    public function begin() {
      $this->appInfo();
      Console::out("[START]", OUTPUT_DEBUG, array('bol' => "  ", 'eol' => "\n"));
    }
    
    // --------------------------------------------------
    
    public function done() {
      Console::out("[DONE]", OUTPUT_DEBUG, array('bol' => "  ", 'eol' => "\n"));
    }
    
    // ----------------------------------------------------------------------------------------------------
    
    public function execute($actionName) {
      if (!method_exists($this, $actionName)) {
        // [TODO] __CLASS__ == BaseController !?
        throw new Exception("[" . __CLASS__ . "] method '{$actionName}' not exist!");
      }
      $this->$actionName();
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    // [Actions]
    // ----------------------------------------------------------------------------------------------------
    
    public function main() {
      $this->help();
    }
    
    // --------------------------------------------------
    
    public function help() {
      echo "  [HELP]\n    Not ready yet!\n";
    }
    
    
    
    // ----------------------------------------------------------------------------------------------------
    
  }
?>
